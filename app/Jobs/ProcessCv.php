<?php

namespace App\Jobs;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ProcessCv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Candidate ID.
     */
    public int $candidateId;

    /**
     * Number of attempts.
     */
    public int $tries = 3;

    /**
     * Job timeout.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(int $candidateId)
    {
        $this->candidateId = $candidateId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $candidate = Candidate::find($this->candidateId);

        if (!$candidate) {
            Log::error('CV processing failed: Candidate not found.', [
                'candidate_id' => $this->candidateId,
            ]);

            return;
        }

        try {

            /*
            |--------------------------------------------------------------------------
            | STEP 1: Get CV file
            |--------------------------------------------------------------------------
            */

            $filePath = $candidate->cv_file;

            if (!$filePath) {
                throw new \RuntimeException(
                    'CV file path is missing.'
                );
            }

            $fullPath = Storage::disk('public')->path($filePath);

            if (!file_exists($fullPath)) {
                throw new \RuntimeException(
                    'CV file does not exist: ' . $fullPath
                );
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 2: Extract text from CV
            |--------------------------------------------------------------------------
            */

            $text = $this->extractTextFromFile(
                $fullPath,
                $candidate->cv_mime_type
            );

            /*
            |--------------------------------------------------------------------------
            | STEP 3: Normalize extracted text
            |--------------------------------------------------------------------------
            */

            $text = $this->normalizeText($text);

            if (trim($text) === '') {
                throw new \RuntimeException(
                    'No readable text could be extracted from the CV.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 4: Send complete CV text to OpenAI
            |--------------------------------------------------------------------------
            */

            $data = $this->extractCvDataWithAI($text);

            /*
            |--------------------------------------------------------------------------
            | STEP 5: Update candidate basic information
            |--------------------------------------------------------------------------
            */

            $candidate->update([
                'full_name' => $data['full_name'] ?? 'Not specified',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'profession' => $data['profession'] ?? null,
                'experience' => $data['experience'] ?? null,
                'education' => $data['education'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | STEP 6: Save Skills
            |--------------------------------------------------------------------------
            */

            $this->saveSkills(
                $candidate,
                $data['skills'] ?? []
            );

            /*
            |--------------------------------------------------------------------------
            | STEP 7: Save Work Experiences
            |--------------------------------------------------------------------------
            */

            $this->saveExperiences(
                $candidate,
                $data['work_experience'] ?? []
            );

            Log::info('CV processed successfully.', [
                'candidate_id' => $candidate->id,
                'name' => $candidate->full_name,
            ]);

        } catch (\Throwable $e) {

            Log::error('CV processing failed.', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
                'file' => $candidate->cv_file,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Show processing error in candidate record
            |--------------------------------------------------------------------------
            */

            $candidate->update([
                'full_name' => 'Processing Failed',
            ]);

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EXTRACT TEXT FROM FILE
    |--------------------------------------------------------------------------
    */

    private function extractTextFromFile(
        string $path,
        ?string $mimeType
    ): string {

        $extension = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        if (
            $extension === 'pdf' ||
            ($mimeType && str_contains($mimeType, 'pdf'))
        ) {
            return $this->parsePdfText($path);
        }

        /*
        |--------------------------------------------------------------------------
        | DOC / DOCX
        |--------------------------------------------------------------------------
        */

        if (
            in_array($extension, ['doc', 'docx'], true)
        ) {
            return $this->parseWordText($path);
        }

        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $extension,
                ['jpg', 'jpeg', 'png'],
                true
            )
        ) {
            return $this->ocrImage($path);
        }

        throw new \RuntimeException(
            'Unsupported CV file type: ' . $extension
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PDF TEXT EXTRACTION
    |--------------------------------------------------------------------------
    */

    private function parsePdfText(string $path): string
    {
        try {

            $text = (new PdfParser())
                ->parseFile($path)
                ->getText();

            /*
            |--------------------------------------------------------------------------
            | Normal PDF with selectable text
            |--------------------------------------------------------------------------
            */

            if (trim($text) !== '') {
                return $text;
            }

            /*
            |--------------------------------------------------------------------------
            | Scanned PDF
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            | Tesseract directly reading PDF is not supported reliably.
            | We try OCR fallback below.
            |
            |--------------------------------------------------------------------------
            */

            return $this->ocrPdf($path);

        } catch (\Throwable $e) {

            Log::warning(
                'PDF text extraction failed. Trying OCR.',
                [
                    'file' => $path,
                    'error' => $e->getMessage(),
                ]
            );

            return $this->ocrPdf($path);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OCR PDF
    |--------------------------------------------------------------------------
    |
    | Uses ImageMagick/Ghostscript conversion if available.
    |
    |--------------------------------------------------------------------------
    */

    private function ocrPdf(string $path): string
    {
        /*
        |--------------------------------------------------------------------------
        | First attempt:
        | Tesseract can sometimes process PDF depending on installation.
        |--------------------------------------------------------------------------
        */

        try {

            $text = (new TesseractOCR($path))
                ->executable(
                    'C:\Program Files\Tesseract-OCR\tesseract.exe'
                )
                ->lang('eng')
                ->psm(6)
                ->run();

            if (trim($text) !== '') {
                return $text;
            }

        } catch (\Throwable $e) {

            Log::warning(
                'Direct PDF OCR failed.',
                [
                    'error' => $e->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | If direct OCR doesn't work, return empty.
        |--------------------------------------------------------------------------
        |
        | We don't want to crash the whole application with an invalid
        | conversion command.
        |
        |--------------------------------------------------------------------------
        */

        throw new \RuntimeException(
            'This PDF appears to be scanned/image based and OCR could not read it. ' .
            'Please make sure Tesseract is correctly installed and configured.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | WORD TEXT EXTRACTION
    |--------------------------------------------------------------------------
    */

    private function parseWordText(string $path): string
    {
        try {

            $phpWord = IOFactory::load($path);

            $text = '';

            foreach ($phpWord->getSections() as $section) {

                foreach ($section->getElements() as $element) {

                    /*
                    |--------------------------------------------------------------------------
                    | Normal text
                    |--------------------------------------------------------------------------
                    */

                    if (method_exists($element, 'getText')) {

                        $value = $element->getText();

                        if (is_string($value)) {
                            $text .= $value . "\n";
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Tables
                    |--------------------------------------------------------------------------
                    */

                    if (method_exists($element, 'getRows')) {

                        foreach ($element->getRows() as $row) {

                            foreach ($row->getCells() as $cell) {

                                foreach (
                                    $cell->getElements()
                                    as $cellElement
                                ) {

                                    if (
                                        method_exists(
                                            $cellElement,
                                            'getText'
                                        )
                                    ) {

                                        $value =
                                            $cellElement->getText();

                                        if (
                                            is_string($value)
                                        ) {
                                            $text .=
                                                $value . ' ';
                                        }
                                    }
                                }
                            }

                            $text .= "\n";
                        }
                    }
                }
            }

            if (trim($text) !== '') {
                return $text;
            }

            throw new \RuntimeException(
                'Word document contains no readable text.'
            );

        } catch (\Throwable $e) {

            Log::warning(
                'Word extraction failed.',
                [
                    'file' => $path,
                    'error' => $e->getMessage(),
                ]
            );

            throw new \RuntimeException(
                'Could not extract text from Word document: ' .
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE OCR
    |--------------------------------------------------------------------------
    */

    private function ocrImage(string $path): string
    {
        try {

            return (new TesseractOCR($path))
                ->executable(
                    'C:\Program Files\Tesseract-OCR\tesseract.exe'
                )
                ->lang('eng')
                ->psm(6)
                ->run();

        } catch (\Throwable $e) {

            throw new \RuntimeException(
                'Tesseract OCR failed: ' .
                $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE TEXT
    |--------------------------------------------------------------------------
    */

    private function normalizeText(string $text): string
    {
        $text = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        $text = str_replace(
            "\t",
            ' ',
            $text
        );

        $text = preg_replace(
            '/[ ]{2,}/',
            ' ',
            $text
        );

        $text = preg_replace(
            "/\n{3,}/",
            "\n\n",
            $text
        );

        return trim($text);
    }

    /*
    |--------------------------------------------------------------------------
    | AI CV PARSER
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | This does NOT depend on fixed CV headings.
    |
    |--------------------------------------------------------------------------
    */

    private function extractCvDataWithAI(string $text): array
    {
        /*
        |--------------------------------------------------------------------------
        | Prevent extremely large OpenAI requests
        |--------------------------------------------------------------------------
        */

        $text = mb_substr(
            $text,
            0,
            30000
        );

        $prompt = <<<PROMPT
You are an expert CV/resume parser and professional recruiter.

Your task is to understand the ENTIRE CV and extract structured information.

IMPORTANT:

1. Do NOT depend on fixed headings.

2. The CV may have NO headings at all.

3. The CV may have headings such as:
   Profile
   About Me
   Career Objective
   Professional Summary
   Employment
   Work History
   Academic Background
   Qualifications
   Technical Expertise
   Competencies
   or completely different headings.

4. Understand the CV based on CONTEXT, exactly like a human recruiter.

5. Find the candidate's REAL FULL NAME.

6. Do NOT use these as a name:
   NAME
   RESUME
   CV
   CURRICULUM VITAE
   PROFILE
   ABOUT ME
   CONTACT
   PERSONAL INFORMATION
   CAREER OBJECTIVE
   PROFESSIONAL SUMMARY

7. If the candidate's name appears near the top of the CV, use contextual clues to identify it.

8. Extract email if available.

9. Extract phone/mobile/contact number if available.

10. Identify the candidate's most appropriate current or primary profession/job title.

11. Extract total professional experience if clearly available.

12. If total experience is not explicitly written, calculate it ONLY when the employment dates clearly allow a reliable calculation.

13. Extract the most relevant education/qualification.

14. Extract ALL clearly identifiable professional and technical skills.

15. Extract ALL identifiable work experiences.

16. For every work experience extract:
    company
    designation
    duration
    description

17. Include internships only if they are clearly part of the candidate's professional experience.

18. Do NOT confuse company names with candidate names.

19. Do NOT confuse university names with candidate names.

20. Do NOT confuse job titles with candidate names.

21. Do NOT confuse addresses with candidate names.

22. Do NOT invent information.

23. Do NOT guess missing information.

24. If information does not exist, return null.

25. If no skills exist, return [].

26. If no work experience exists, return [].

27. Preserve meaningful information from the CV.

28. Return ONLY valid JSON.

29. Do not return markdown.

30. Do not return ```json.

Return exactly this structure:

{
    "full_name": null,
    "email": null,
    "phone": null,
    "profession": null,
    "experience": null,
    "education": null,
    "skills": [],
    "work_experience": [
        {
            "company": null,
            "designation": null,
            "duration": null,
            "description": null
        }
    ]
}

CV TEXT:

$text
PROMPT;

        /*
        |--------------------------------------------------------------------------
        | OpenAI request
        |--------------------------------------------------------------------------
        */

        $response = OpenAI::chat()->create([
            'model' => 'gpt-5-mini',

            'messages' => [
                [
                    'role' => 'system',
                    'content' =>
                        'You are a highly accurate CV and resume information extraction system. Return only valid JSON.',
                ],

                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],

            'response_format' => [
                'type' => 'json_object',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get AI response
        |--------------------------------------------------------------------------
        */

        $content =
            $response->choices[0]->message->content ?? '';

        if (!$content) {

            throw new \RuntimeException(
                'OpenAI returned an empty response.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Decode JSON
        |--------------------------------------------------------------------------
        */

        $data = json_decode(
            $content,
            true
        );

        if (!is_array($data)) {

            throw new \RuntimeException(
                'OpenAI returned invalid JSON.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure required fields
        |--------------------------------------------------------------------------
        */

        $data['full_name'] =
            $data['full_name'] ?? null;

        $data['email'] =
            $data['email'] ?? null;

        $data['phone'] =
            $data['phone'] ?? null;

        $data['profession'] =
            $data['profession'] ?? null;

        $data['experience'] =
            $data['experience'] ?? null;

        $data['education'] =
            $data['education'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Skills
        |--------------------------------------------------------------------------
        */

        if (
            !isset($data['skills']) ||
            !is_array($data['skills'])
        ) {
            $data['skills'] = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Work Experience
        |--------------------------------------------------------------------------
        */

        if (
            !isset($data['work_experience']) ||
            !is_array($data['work_experience'])
        ) {
            $data['work_experience'] = [];
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE SKILLS
    |--------------------------------------------------------------------------
    */

    private function saveSkills(
        Candidate $candidate,
        array $skills
    ): void {

        /*
        |--------------------------------------------------------------------------
        | If relationship exists
        |--------------------------------------------------------------------------
        */

        if (!method_exists($candidate, 'skills')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove old skills
        |--------------------------------------------------------------------------
        */

        $candidate->skills()->delete();

        foreach ($skills as $skill) {

            if (is_array($skill)) {
                continue;
            }

            $skill = trim((string) $skill);

            if ($skill === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | This assumes CandidateSkill model/table relationship
            | already exists in your project.
            |--------------------------------------------------------------------------
            */

            $candidate->skills()->create([
                'skill' => $skill,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE EXPERIENCES
    |--------------------------------------------------------------------------
    */

    private function saveExperiences(
        Candidate $candidate,
        array $experiences
    ): void {

        if (!method_exists($candidate, 'experiences')) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove old experiences
        |--------------------------------------------------------------------------
        */

        $candidate->experiences()->delete();

        foreach ($experiences as $experience) {

            if (!is_array($experience)) {
                continue;
            }

            $candidate->experiences()->create([
                'company' =>
                    $experience['company'] ?? null,

                'job_title' => $experience['designation'] ?? null,

                'duration' =>
                    $experience['duration'] ?? null,

                'description' =>
                    $experience['description'] ?? null,
            ]);
        }
    }
}