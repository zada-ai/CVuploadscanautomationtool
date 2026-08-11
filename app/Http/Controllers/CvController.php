<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Jobs\ProcessCv;

class CvController extends Controller
{
    /**
     * Upload CV and process it in background.
     */
    public function upload(Request $request)
    {
        try {
            $request->validate([
                'cv' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            ]);

            $file = $request->file('cv');

            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();

            /*
            |--------------------------------------------------------------------------
            | STEP 1: Upload file immediately
            |--------------------------------------------------------------------------
            */

            $filePath = $file->store('cvs', 'public');

            /*
            |--------------------------------------------------------------------------
            | STEP 2: Create basic candidate record
            |--------------------------------------------------------------------------
            */

            $candidate = Candidate::create([
                'full_name' => 'Processing...',
                'email' => null,
                'phone' => null,
                'profession' => null,
                'experience' => null,
                'education' => null,
                'cv_file' => $filePath,
                'cv_original_name' => $originalName,
                'cv_mime_type' => $mimeType,
            ]);

            /*
            |--------------------------------------------------------------------------
            | STEP 3: Send CV processing to background queue
            |--------------------------------------------------------------------------
            */

            ProcessCv::dispatch($candidate->id);

            /*
            |--------------------------------------------------------------------------
            | STEP 4: Immediately return to user
            |--------------------------------------------------------------------------
            */

            return back()->with(
                'success',
                'CV uploaded successfully. It is now being processed.'
            );

        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'CV upload error: ' . $e->getMessage()
            );
        }
    }


    /**
     * Admin candidate listing.
     */
    public function index()
    {
        $candidates = Candidate::with([
            'skills',
            'experiences'
        ])
            ->orderByDesc('created_at')
            ->get();

        return view('admin', compact('candidates'));
    }


    /**
     * Extract text depending on CV file type.
     */
    private function extractTextFromFile(
        string $path,
        string $mimeType
    ): string {

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        if (str_contains($mimeType, 'pdf')) {
            return $this->parsePdfText($path);
        }

        /*
        |--------------------------------------------------------------------------
        | DOC / DOCX
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $mimeType,
                'wordprocessingml.document'
            ) ||
            str_contains(
                $mimeType,
                'msword'
            )
        ) {
            return $this->parseWordText($path);
        }

        /*
        |--------------------------------------------------------------------------
        | JPG / JPEG / PNG
        |--------------------------------------------------------------------------
        */

        return $this->ocrImage($path);
    }


    /**
     * Extract text from PDF.
     */
    private function parsePdfText(string $path): string
    {
        try {

            $text = (new PdfParser())
                ->parseFile($path)
                ->getText();

            /*
            |--------------------------------------------------------------------------
            | If PDF has readable text
            |--------------------------------------------------------------------------
            */

            if (trim($text) !== '') {
                return $text;
            }

            /*
            |--------------------------------------------------------------------------
            | Otherwise OCR fallback
            |--------------------------------------------------------------------------
            */

            return $this->ocrImage($path);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Scanned PDF fallback
            |--------------------------------------------------------------------------
            */

            return $this->ocrImage($path);
        }
    }


    /**
     * Extract text from Word document.
     */
    private function parseWordText(string $path): string
    {
        try {

            $phpWord = IOFactory::load($path);

            $text = '';

            foreach ($phpWord->getSections() as $section) {

                foreach ($section->getElements() as $element) {

                    if (method_exists($element, 'getText')) {

                        $value = $element->getText();

                        if (is_string($value)) {
                            $text .= $value . "\n";
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | If Word text exists
            |--------------------------------------------------------------------------
            */

            if (trim($text) !== '') {
                return $text;
            }

            /*
            |--------------------------------------------------------------------------
            | OCR fallback
            |--------------------------------------------------------------------------
            */

            return $this->ocrImage($path);

        } catch (\Throwable $e) {

            return $this->ocrImage($path);
        }
    }


    /**
     * Tesseract OCR.
     */
    private function ocrImage(string $path): string
    {
        return (new TesseractOCR($path))
            ->executable(
                'C:\Program Files\Tesseract-OCR\tesseract.exe'
            )
            ->lang('eng')
            ->psm(6)
            ->run();
    }


    /**
     * Normalize extracted CV text.
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


    /**
     * OpenAI CV parser.
     *
     * This is the MAIN parser.
     */
    private function extractCvDataWithAI(string $text): array
    {
        /*
        |--------------------------------------------------------------------------
        | Prevent extremely large requests
        |--------------------------------------------------------------------------
        */

        $text = mb_substr(
            $text,
            0,
            30000
        );

        $prompt = <<<PROMPT
You are an expert CV/resume parser.

Read the complete CV text below and extract the candidate information.

IMPORTANT RULES:

1. Do NOT depend on fixed headings such as:
   Name
   Skills
   Experience
   Education

2. Every CV can have a completely different layout.

3. Understand the CV like a human recruiter.

4. Find the candidate's REAL name from context.

5. Never use labels such as:
   NAME
   PROFILE
   CURRICULUM VITAE
   RESUME
   CONTACT
   PERSONAL INFORMATION
   as the candidate's name.

6. Extract email if available.

7. Extract phone/mobile/contact number if available.

8. Identify the candidate's most appropriate profession or job title.

9. Extract total professional experience if clearly available.

10. Extract the most relevant education/qualification.

11. Extract ALL clearly identifiable skills.

12. Extract ALL identifiable work experiences.

13. For every work experience extract:
    company
    designation
    duration
    description

14. If information does not exist, return null.

15. If no skills exist, return [].

16. If no work experience exists, return [].

17. NEVER invent information.

18. Do not guess missing information.

19. Return ONLY valid JSON.

20. Do not return markdown.

21. Do not return ```json.

22. Preserve meaningful information from the CV.

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
        | Convert JSON to PHP array
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
        | Ensure skills array
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
        | Ensure experience array
        |--------------------------------------------------------------------------
        */

        if (
            !isset($data['work_experience']) ||
            !is_array($data['work_experience'])
        ) {
            $data['work_experience'] = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure expected fields exist
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

        return $data;
    }


    /*
    |--------------------------------------------------------------------------
    | FALLBACK REGEX METHODS
    |--------------------------------------------------------------------------
    |
    | These are kept so that if OpenAI temporarily fails,
    | the CV can still be processed using the old parser.
    |
    |--------------------------------------------------------------------------
    */


    private function cleanLines(string $text): array
    {
        $lines = preg_split(
            "/\n/",
            $text
        );

        $clean = [];

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line !== '') {

                $clean[] = preg_replace(
                    '/\s+/',
                    ' ',
                    $line
                );
            }
        }

        return $clean;
    }


    private function isHeadingLine(string $line): bool
    {
        $normalized = strtoupper(
            preg_replace(
                '/[^A-Z ]/',
                ' ',
                $line
            )
        );

        $normalized = preg_replace(
            '/\s+/',
            ' ',
            trim($normalized)
        );

        if ($normalized === '') {
            return false;
        }

        $headings = [
            'NAME',
            'NATIONALITY',
            'DATE OF BIRTH',
            'MARITAL STATUS',
            'PERMANENT ADDRESS',
            'CONTACT',
            'MOBILE',
            'PHONE',
            'EMAIL',
            'ADDRESS',
            'OBJECTIVE',
            'CAREER OBJECTIVE',
            'PROFILE',
            'SUMMARY',
            'EDUCATION',
            'QUALIFICATION',
            'EXPERIENCE',
            'WORK EXPERIENCE',
            'SKILLS',
            'HOBBIES',
            'PROJECTS',
            'CERTIFICATIONS',
            'PERSONAL INFORMATION',
            'TECHNICAL SKILLS',
        ];

        foreach ($headings as $heading) {

            if (str_contains(
                $normalized,
                $heading
            )) {
                return true;
            }
        }

        return false;
    }


    private function extractName(string $text): ?string
    {
        $lines = $this->cleanLines($text);

        foreach ($lines as $index => $line) {

            if (
                preg_match(
                    '/^NAME\s*:?\s*(.*)$/i',
                    $line,
                    $match
                )
            ) {

                $value = trim($match[1]);

                if (
                    $value === '' &&
                    isset($lines[$index + 1])
                ) {
                    $value =
                        trim($lines[$index + 1]);
                }

                if (
                    $this->isValidName($value)
                ) {
                    return $this->cleanExtractedValue(
                        $value
                    );
                }
            }
        }

        foreach ($lines as $index => $line) {

            if (
                preg_match(
                    '/\bS\/O\b/i',
                    $line
                ) ||
                preg_match(
                    '/\bFATHER\b/i',
                    $line
                )
            ) {

                if (isset($lines[$index - 1])) {

                    $possibleName =
                        trim($lines[$index - 1]);

                    if (
                        $this->isValidName(
                            $possibleName
                        )
                    ) {
                        return $this->cleanExtractedValue(
                            $possibleName
                        );
                    }
                }
            }
        }

        foreach ($lines as $line) {

            if (
                $this->isHeadingLine($line) ||
                strtolower(trim($line)) ===
                'curriculum vitae'
            ) {
                continue;
            }

            if (
                preg_match(
                    '/\b(NATIONALITY|NATONALITY|DATE OF BIRTH|DOB|MARITAL STATUS|FATHER|CONTACT|MOBILE|PHONE)\b/i',
                    $line
                )
            ) {

                $possible =
                    $this->extractPossibleNameFromLine(
                        $line
                    );

                if ($possible !== null) {
                    return $possible;
                }
            }
        }

        foreach ($lines as $line) {

            if (
                $line === '' ||
                $this->isHeadingLine($line)
            ) {
                continue;
            }

            if (
                preg_match(
                    "/^[A-Za-z][A-Za-z .'-]{2,}$/",
                    $line
                )
            ) {

                $wordCount =
                    str_word_count($line);

                if (
                    $wordCount >= 2 &&
                    $wordCount <= 5 &&
                    $this->isValidName($line)
                ) {
                    return $this->cleanExtractedValue(
                        $line
                    );
                }
            }
        }

        return null;
    }


    private function extractPossibleNameFromLine(
        string $line
    ): ?string {

        $line = preg_replace(
            '/[^A-Za-z\s]/',
            ' ',
            $line
        );

        $parts = array_values(
            array_filter(
                preg_split(
                    '/\s+/',
                    $line
                ),
                fn($part) =>
                    trim($part) !== ''
            )
        );

        if (count($parts) < 2) {
            return null;
        }

        for (
            $take = 2;
            $take <= min(4, count($parts));
            $take++
        ) {

            $candidate = implode(
                ' ',
                array_slice(
                    $parts,
                    -$take
                )
            );

            if (
                $this->isValidName(
                    $candidate
                )
            ) {
                return $this->cleanExtractedValue(
                    $candidate
                );
            }
        }

        return null;
    }


    private function extractEmail(
        string $text
    ): ?string {

        if (
            preg_match(
                '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
                $text,
                $match
            )
        ) {

            return strtolower(
                trim($match[0])
            );
        }

        return null;
    }


    private function extractPhone(
        string $text
    ): ?string {

        $patterns = [

            '/\b(\+92|0092)[\s\-]?\d{3}[\s\-]?\d{7}\b/',

            '/\b03\d{2}[\s\-]?\d{7}\b/',

            '/\b03\d{9}\b/',
        ];

        foreach ($patterns as $pattern) {

            if (
                preg_match(
                    $pattern,
                    $text,
                    $match
                )
            ) {

                return trim(
                    $match[0]
                );
            }
        }

        return $this->extractPhoneFromDigits(
            $text
        );
    }


    private function extractPhoneFromDigits(
        string $text
    ): ?string {

        $lines = $this->cleanLines(
            $text
        );

        foreach ($lines as $line) {

            if (
                preg_match(
                    '/\b(contact|mobile|phone)\b/i',
                    $line
                )
            ) {

                preg_match_all(
                    '/\d+/',
                    $line,
                    $matches
                );

                $digits = implode(
                    '',
                    $matches[0]
                );

                if (strlen($digits) >= 10) {

                    return substr(
                        $digits,
                        -10
                    );
                }
            }
        }

        return null;
    }


    private function extractProfession(
        string $text
    ): ?string {

        $patterns = [

            '/(?:PROFESSION|DESIGNATION|JOB TITLE|POSITION)\s*:?\s*([A-Za-z][A-Za-z .\-]{2,60})/i',

            '/(?:WORKING AS|WORKED AS|WORK AS|EMPLOYED AS)\s+(?:A|AN)?\s*([A-Za-z][A-Za-z \-]{2,60})/i',
        ];

        foreach ($patterns as $pattern) {

            if (
                preg_match(
                    $pattern,
                    $text,
                    $match
                )
            ) {

                $value =
                    $this->cleanExtractedValue(
                        $match[1]
                    );

                if (
                    $value !== '' &&
                    !$this->isHeadingLine($value) &&
                    $this->isValidProfession($value)
                ) {
                    return $value;
                }
            }
        }

        return null;
    }


    private function extractExperience(
        string $text
    ): ?string {

        if (
            preg_match(
                '/(\d+(?:\.\d+)?)\s*(?:years|yrs|year)\b/i',
                $text,
                $match
            )
        ) {

            return trim(
                $match[1] . ' Years'
            );
        }

        if (
            preg_match(
                '/(\d+)\s*(?:months|mos|month)\b/i',
                $text,
                $match
            )
        ) {

            return trim(
                $match[1] . ' Months'
            );
        }

        return null;
    }


    private function extractEducation(
        string $text
    ): ?string {

        $patterns = [

            '/\b(BS|BSc|BA|BBA|BE|BSCE|BSCS|BCS|MS|MSc|MBA|MA|PhD|Doctorate|MPhil|Diploma|Intermediate|FSc|FA|Matric)\b/i',

            '/\b(BS\s+[A-Za-z ]+|BSc\s+[A-Za-z ]+|BS\s+[A-Za-z ]+Engineering)\b/i',
        ];

        foreach ($patterns as $pattern) {

            if (
                preg_match(
                    $pattern,
                    $text,
                    $match
                )
            ) {

                return $this->cleanExtractedValue(
                    $match[1]
                );
            }
        }

        return null;
    }


    private function extractSkills(
        string $text
    ): array {

        $knownSkills = [

            'AutoCAD',
            'Primavera',
            'MS Project',
            'SolidWorks',
            'ANSYS',
            'MATLAB',
            'PLC',
            'ETAP',

            'PHP',
            'Laravel',
            'JavaScript',
            'HTML',
            'CSS',
            'React',
            'Vue',
            'Node.js',

            'Microsoft Office',
            'Excel',
            'Photoshop',
            'Illustrator',
            'Figma',

            'Communication',
            'Leadership',
            'Management',
            'Teamwork',
            'Customer Service',

            'Sales',
            'Marketing',
            'WordPress',
            'Git',
            'GitHub',
        ];

        $skills = [];

        $section = $this->extractSection(
            $text,
            [
                'SKILLS',
                'TECHNICAL SKILLS',
                'PROFESSIONAL SKILLS'
            ]
        );

        if ($section) {

            $parts = preg_split(
                '/[\n,;\t]+/',
                $section
            );

            foreach ($parts as $part) {

                $skill =
                    $this->cleanExtractedValue(
                        $part
                    );

                if (
                    $skill !== '' &&
                    !$this->isHeadingLine($skill)
                ) {
                    $skills[] = $skill;
                }
            }
        }

        foreach ($knownSkills as $skill) {

            if (
                preg_match(
                    '/\b' .
                    preg_quote($skill, '/') .
                    '\b/i',
                    $text
                )
            ) {
                $skills[] = $skill;
            }
        }

        return array_values(
            array_unique(
                array_filter($skills)
            )
        );
    }


    private function extractSection(
        string $text,
        array $headers
    ): ?string {

        $lines = $this->cleanLines(
            $text
        );

        $lowerHeaders =
            array_map(
                'strtolower',
                $headers
            );

        foreach (
            $lines as $index => $line
        ) {

            $lineLower =
                strtolower($line);

            foreach (
                $lowerHeaders as $header
            ) {

                if (
                    str_contains(
                        $lineLower,
                        $header
                    )
                ) {

                    $section = [];

                    for (
                        $i = $index + 1;
                        $i < count($lines);
                        $i++
                    ) {

                        if (
                            $this->isHeadingLine(
                                $lines[$i]
                            )
                        ) {
                            break;
                        }

                        $section[] =
                            $lines[$i];
                    }

                    return implode(
                        ' ',
                        $section
                    );
                }
            }
        }

        return null;
    }


    private function cleanExtractedValue(
        string $value
    ): string {

        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                $value
            ),
            " \t\n\r\0\x0B:.-"
        );
    }


    private function isValidName(
        string $value
    ): bool {

        $value = trim($value);

        if (
            strlen($value) < 3 ||
            strlen($value) > 80
        ) {
            return false;
        }

        $invalid = [

            'NAME',
            'NATIONALITY',
            'DATE OF BIRTH',
            'MARITAL STATUS',
            'CONTACT',
            'CAREER OBJECTIVE',
            'WORK EXPERIENCE',
            'SKILLS',
            'EDUCATION',
            'QUALIFICATION',
            'SELF INTRODUCTION',
            'SUMMARY',
            'PROFILE',
            'OBJECTIVE',
            'PERSONAL INFORMATION',
        ];

        if (
            in_array(
                strtoupper($value),
                $invalid,
                true
            )
        ) {
            return false;
        }

        return (bool) preg_match(
            "/^[A-Za-z][A-Za-z .'-]{2,}$/",
            $value
        );
    }


    private function isValidProfession(
        string $value
    ): bool {

        $value = trim($value);

        if (
            $value === '' ||
            strlen($value) > 80
        ) {
            return false;
        }

        return (bool) preg_match(
            '/^[A-Za-z][A-Za-z .\-]{2,80}$/',
            $value
        );
    }
}