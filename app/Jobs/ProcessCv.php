<?php

namespace App\Jobs;

use App\Models\Candidate;
use App\Models\RelevantJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

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

        $openAiFileId = null;

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
            | STEP 2: Detect file type
            |--------------------------------------------------------------------------
            */

            $extension = strtolower(
                pathinfo($fullPath, PATHINFO_EXTENSION)
            );

            $mimeType = $candidate->cv_mime_type
                ?: mime_content_type($fullPath);

            Log::info('Starting direct OpenAI CV processing.', [
                'candidate_id' => $candidate->id,
                'file' => $filePath,
                'extension' => $extension,
                'mime_type' => $mimeType,
            ]);

            /*
            |--------------------------------------------------------------------------
            | STEP 3: Validate supported CV types
            |--------------------------------------------------------------------------
            */

            $supportedExtensions = [
                'pdf',
                'doc',
                'docx',
                'jpg',
                'jpeg',
                'png',
                'webp',
            ];

            if (!in_array($extension, $supportedExtensions, true)) {
                throw new \RuntimeException(
                    'Unsupported CV file type: ' . $extension
                );
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 4: Upload original CV directly to OpenAI
            |--------------------------------------------------------------------------
            |
            | No Tesseract
            | No Poppler
            | No PDF parser
            | No PHPWord
            |
            */

            $uploadedFile = OpenAI::files()->upload([
                'purpose' => 'user_data',
                'file' => fopen($fullPath, 'r'),
            ]);

            $openAiFileId = $uploadedFile->id;

            Log::info('CV uploaded to OpenAI successfully.', [
                'candidate_id' => $candidate->id,
                'openai_file_id' => $openAiFileId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | STEP 5: Send CV directly to OpenAI
            |--------------------------------------------------------------------------
            */

            $data = $this->extractCvDataWithAI(
                $openAiFileId,
                $extension
            );

            /*
            |--------------------------------------------------------------------------
            | STEP 6: Update candidate
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
            | STEP 7: Save skills
            |--------------------------------------------------------------------------
            */

            $this->saveSkills(
                $candidate,
                $data['skills'] ?? []
            );

            $this->saveRelevantJobs(
                $candidate,
                $data['relevant_jobs'] ?? []
            );

            /*
            |--------------------------------------------------------------------------
            | STEP 8: Save work experiences
            |--------------------------------------------------------------------------
            */

            $this->saveExperiences(
                $candidate,
                $data['work_experience'] ?? []
            );

            /*
            |--------------------------------------------------------------------------
            | STEP 9: Mark successful processing
            |--------------------------------------------------------------------------
            */

            Log::info('CV processed successfully by OpenAI.', [
                'candidate_id' => $candidate->id,
                'name' => $candidate->full_name,
            ]);

        } catch (\Throwable $e) {

            Log::error('CV processing failed.', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage(),
                'file' => $candidate->cv_file,
            ]);

            $candidate->update([
                'full_name' => 'Processing Failed',
            ]);

            throw $e;

        } finally {

            /*
            |--------------------------------------------------------------------------
            | STEP 10: Delete temporary OpenAI file
            |--------------------------------------------------------------------------
            |
            | We do not need to keep every uploaded CV inside OpenAI Files.
            |
            */

            if ($openAiFileId) {
                try {
                    OpenAI::files()->delete($openAiFileId);

                    Log::info('Temporary OpenAI CV file deleted.', [
                        'candidate_id' => $candidate->id,
                        'openai_file_id' => $openAiFileId,
                    ]);

                } catch (\Throwable $e) {

                    Log::warning(
                        'Could not delete temporary OpenAI CV file.',
                        [
                            'candidate_id' => $candidate->id,
                            'openai_file_id' => $openAiFileId,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | OPENAI CV PARSER
    |--------------------------------------------------------------------------
    */

 private function extractCvDataWithAI(
    string $fileId,
    string $extension
): array {

    $instructions = <<<'PROMPT'
You are a STRICT CV/RESUME extraction engine.

Your ONLY job is to extract candidate information from the candidate's
actual CV/resume.

Do NOT enrich the CV with information from attached supporting documents.
Do NOT infer information that is not explicitly present in the CV.

==================================================
1. ABSOLUTE CV-ONLY RULE
==================================================

The uploaded file may contain a CV together with additional scanned or
attached documents.

ONLY use information that is visibly and explicitly part of the
candidate's CV/resume.

Treat the CV itself as the ONLY source of truth.

COMPLETELY IGNORE any information coming from:
- Passport
- CNIC / National ID Card
- Driving License
- Identity Card
- Domicile
- Visa documents
- Bank documents
- Company ID / access cards
- Result Cards
- Mark Sheets
- Transcripts
- Degree certificates
- Training certificates
- Course certificates
- Employment letters
- Recommendation letters
- Other supporting documents
- Barcodes
- QR codes
- Document serial numbers
- Passport numbers
- CNIC numbers
- Driving license numbers
- Registration numbers
- Certificate numbers
- Roll numbers
- Marks
- Percentages
- CGPA / GPA
- Grades
- Document issue dates
- Document expiry dates

If the uploaded PDF/image contains a CV plus other documents,
IGNORE the other documents completely.

Do NOT use supporting documents to fill missing CV information.

Do NOT discover additional education, skills, experience, contact
details, or personal information from supporting documents.

==================================================
2. DO NOT EXPAND THE CV
==================================================

Only return information that is explicitly written in the candidate's
actual CV/resume.

If the CV lists only two degrees, return only those two degrees.

If a result card contains another qualification that is NOT written in
the CV, do NOT add it.

If a certificate contains a skill that is NOT written in the CV, do NOT
add it.

If a driving license contains a vehicle category or driving information,
do NOT add it to the candidate's profession, skills, education, or
experience.

Never supplement or complete the CV using attached documents.

==================================================
3. FULL NAME
==================================================

Find the candidate's real name from the CV.

Use contextual understanding.

Do NOT use these as names:
- NAME
- RESUME
- CV
- CURRICULUM VITAE
- PROFILE
- ABOUT ME
- CONTACT
- PERSONAL INFORMATION
- CAREER OBJECTIVE
- PROFESSIONAL SUMMARY
- CURRICULUM

Prefer the actual candidate name shown on the CV.

==================================================
4. EMAIL
==================================================

Extract the candidate's email address ONLY when it appears in the
candidate's CV.

Do not use email addresses found only in unrelated attached documents.

==================================================
5. PHONE
==================================================

Extract the candidate's phone/mobile/contact number ONLY when it appears
in the candidate's CV.

Do not use phone numbers found only in passport, ID card, driving license,
or other attached documents.

Do not confuse dates, IDs, roll numbers, registration numbers, or
document numbers with phone numbers.

==================================================
6. PROFESSION
==================================================

Identify the candidate's primary professional role/job title from the CV.

Use:
- title under the candidate's name
- current/latest job title
- professional summary
- career objective
- strongest professional context

Return the most appropriate profession.

Do not use a profession inferred only from a driving license, certificate,
ID card, or other supporting document.

==================================================
7. EXPERIENCE
==================================================

Extract total professional experience only when it is stated in the CV.

If the total is not explicitly stated, calculate it ONLY when employment
dates shown in the CV make the calculation reasonably reliable.

Use only actual employment/work information from the CV.

Do not use dates from passports, certificates, result cards, licenses,
IDs, or other supporting documents.

==================================================
8. EDUCATION — VERY STRICT
==================================================

Return ONLY the names of degrees, diplomas, or qualifications explicitly
listed in the candidate's CV.

The education field must contain ONLY qualification names.

Examples:
- BS Electrical Engineering
- Bachelor of Science in Electrical Engineering
- DAE Electrical
- Diploma in Civil Engineering
- B.Com
- Bachelor of Computer Science
- Master of Business Administration
- Intermediate
- Matriculation

DO NOT include:
- University/college names unless they are part of the actual
  qualification title written in the CV
- Marks
- Total marks
- Obtained marks
- Percentage
- CGPA
- GPA
- Grade
- Roll number
- Registration number
- Enrollment number
- Certificate number
- Serial number
- Subjects
- Result details
- Result dates
- Admission dates
- Graduation dates
- Degree issue dates
- Certificate issue/expiry dates
- Transcript details
- Marksheet details
- Board verification details
- Result card information
- Any other document-specific information

IMPORTANT:

If the candidate's CV says:

BS Electrical Engineering
DAE Electrical

then return only:

BS Electrical Engineering
DAE Electrical

If an attached result card contains:
3147/3550
Grade A+
Roll No. XXXXX
Registration No. XXXXX
subjects
result date

IGNORE ALL OF IT.

Do NOT use a result card, transcript, marksheet, degree certificate,
or other supporting document to discover additional education.

Only qualifications explicitly present on the CV are allowed.

If there are multiple degrees/diplomas explicitly written on the CV,
include all of those qualification names and nothing else.

==================================================
9. SKILLS — VERY STRICT AND PROFESSION-RELEVANT
==================================================

Return ONLY genuine professional skills that are directly relevant to
the candidate's identified profession.

This is NOT a general keyword extraction task.

A skill must pass this test:

"Is this a real professional skill and is it directly relevant to this
candidate's profession?"

If YES -> include it.
If NO -> exclude it.

Extract relevant professional skills from the CV's:
- Skills section
- Technical Skills
- Professional Skills
- Expertise
- Competencies
- Technologies
- Tools
- Software
- Work Experience descriptions
- Project descriptions
- Professional Summary

However, ONLY keep skills that are relevant to the candidate's actual
profession.

Do NOT include irrelevant skills merely because they appear somewhere
in the CV.

--------------------------------------------------
GENERIC / PERSONAL QUALITIES
--------------------------------------------------

Do NOT treat generic personal qualities as professional skills unless
they are clearly profession-specific and genuinely useful for evaluating
the candidate for that profession.

Generally EXCLUDE:
- Teamwork
- Problem Solving
- Hardworking
- Honest
- Reliable
- Punctual
- Friendly
- Motivated
- Self-motivated
- Fast Learner
- Time Management
- Good Communication
- Willing to Relocate
- Positive Attitude
- Sincerity
- Adaptability

--------------------------------------------------
DOCUMENT / PERSONAL INFORMATION
--------------------------------------------------

NEVER return these as skills:
- Passport information
- CNIC information
- Driving License information
- ID card information
- Domicile information
- Result card information
- Marksheet information
- Certificate numbers
- Registration numbers
- Roll numbers
- Document dates
- Document serial numbers
- QR/barcode content
- Unrelated document text

--------------------------------------------------
PROFESSION EXAMPLE
--------------------------------------------------

If profession = Excavator Operator, relevant skills may include:
- Excavator Operation
- Hydraulic Excavator Operation
- Heavy Equipment Operation
- Earthmoving Equipment Operation
- Excavation
- Site Preparation
- Equipment Inspection
- Equipment Maintenance
- Construction Safety
- Material Loading and Unloading

Do NOT include unrelated skills simply because they appear elsewhere.

If profession = Laravel Developer, relevant skills may include:
- Laravel
- PHP
- MySQL
- REST API Development
- JavaScript
- Git
- Laravel Sanctum

Do NOT include unrelated skills such as driving, cooking, passport
information, document handling, or unrelated hobbies.

If profession = Electrician, relevant skills may include:
- Electrical Installation
- Electrical Maintenance
- Wiring
- Troubleshooting
- Circuit Testing
- Electrical Safety
- Control Panels
- Motor Maintenance

--------------------------------------------------
SKILL DEDUPLICATION
--------------------------------------------------

Return each meaningful skill once.

For example:
Electrical Maintenance
Electrical Installation
Troubleshooting

are separate relevant skills.

Do not repeat the same skill multiple times.

==================================================

RELEVANT JOBS
==================================================

Based ONLY on the candidate's actual CV/resume, identify job positions
for which this candidate is genuinely suitable.

Use only:
- profession
- actual work experience
- professional skills
- education/qualification
- career background

Do NOT use:
- Passport
- CNIC
- Driving License
- ID Cards
- Domicile
- Result Cards
- Mark Sheets
- Transcripts
- Degree Certificates
- Training Certificates
- Course Certificates
- Employment Letters
- Recommendation Letters
- Other Supporting Documents

Do NOT invent qualifications, skills, experience, or jobs.

Do NOT suggest jobs just because a single keyword vaguely matches.

Only suggest positions that a recruiter could reasonably consider this
candidate for based on the actual CV.

Prefer specific professional job titles.

Return 3 to 6 relevant job titles when enough information exists.

If only 1 or 2 jobs are genuinely supported, return only those.

If no suitable job can be determined, return [].

Do not return duplicate job titles.

Examples:

Profession: Excavator Operator

Relevant Jobs:
- Excavator Operator
- Heavy Equipment Operator
- Earthmoving Equipment Operator
- Construction Equipment Operator

Do NOT suggest Civil Engineer, Site Engineer, Mechanical Engineer,
etc. unless the actual CV supports those positions.

==================================================
10. WORK EXPERIENCE
==================================================

Extract actual employment/work experience explicitly present in the CV.

For every work experience return:
- company
- designation
- duration
- description

Only use work experience written in the CV.

Do NOT treat:
- certificates
- result cards
- passport pages
- driving license pages
- ID cards
- training cards
- document text

as employment experience.

Preserve meaningful job responsibilities from the CV, but do not copy
unrelated document information.

==================================================
11. SCANNED / MULTI-PAGE FILES
==================================================

The uploaded file may contain many pages.

Do NOT assume every page belongs to the CV.

Identify the actual CV/resume content and ignore all attached supporting
documents.

A page that is clearly:
- passport
- CNIC
- driving license
- result card
- marksheet
- transcript
- certificate
- company ID card
- other supporting document

must NOT be used as a source for the candidate fields.

If the information exists only on such a page, treat it as NOT AVAILABLE.

==================================================
12. NO GUESSING / NO INFERENCE FROM ATTACHMENTS
==================================================

Never invent information.

Never infer missing information from supporting documents.

Never use information from one document to complete another document.

If information is not explicitly available in the CV, return null or [].

==================================================
13. FINAL VALIDATION
==================================================

Before returning the JSON, perform these checks:

1. Did I use ONLY the actual CV/resume?
2. Did I completely ignore passport/CNIC/driving license/ID cards?
3. Did I completely ignore result cards/marksheets/transcripts?
4. Did I completely ignore unrelated certificates and supporting documents?
5. Does education contain ONLY degree/diploma/qualification names?
6. Did I remove marks, CGPA, grades, roll numbers, registration numbers,
   certificate numbers, subjects, result-card information and dates?
7. Are all skills directly relevant to the candidate's profession?
8. Did I remove generic, irrelevant, personal, or document-related items
   from skills?
9. Did I use only actual CV work experience?
10. Did I avoid guessing or adding information?

If any value fails these rules, REMOVE IT before returning the result.

==================================================
14. OUTPUT
==================================================

Return ONLY valid JSON.

No markdown.
No explanation.
No comments.
No extra fields.
No ```json.

Return EXACTLY:

{
    "full_name": null,
    "email": null,
    "phone": null,
    "profession": null,
    "experience": null,
    "education": null,
    "skills": [],
    "relevant_jobs": [],
    "work_experience": [
        {
            "company": null,
            "designation": null,
            "duration": null,
            "description": null
        }
    ]
}
PROMPT;


    /*
    |--------------------------------------------------------------------------
    | Build file input
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            $extension,
            ['jpg', 'jpeg', 'png', 'webp'],
            true
        )
    ) {

        $input = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => $instructions,
                    ],
                    [
                        'type' => 'input_image',
                        'file_id' => $fileId,
                    ],
                ],
            ],
        ];

    } else {

        $input = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => $instructions,
                    ],
                    [
                        'type' => 'input_file',
                        'file_id' => $fileId,
                    ],
                ],
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | OpenAI Responses API
    |--------------------------------------------------------------------------
    */

    $response = OpenAI::responses()->create([
        'model' => 'gpt-5-mini',

        'input' => $input,

        'text' => [
            'format' => [
                'type' => 'json_schema',

                'name' => 'cv_candidate_data',

                'strict' => true,

                'schema' => [
                    'type' => 'object',

                    'properties' => [

                        'full_name' => [
                            'type' => [
                                'string',
                                'null',
                            ],
                        ],

                        'email' => [
                            'type' => [
                                'string',
                                'null',
                            ],
                        ],

                        'phone' => [
                            'type' => [
                                'string',
                                'null',
                            ],
                        ],

                        'profession' => [
                            'type' => [
                                'string',
                                'null',
                            ],
                        ],

                        'experience' => [
                            'type' => [
                                'string',
                                'null',
                            ],
                        ],

                        /*
                        |--------------------------------------------------------------------------
                        | ONLY CV-listed degree/diploma/qualification names
                        |--------------------------------------------------------------------------
                        */

                        'education' => [
                            'type' => [
                                'string',
                                'null',
                            ],
                        ],

                        /*
                        |--------------------------------------------------------------------------
                        | ONLY profession-relevant skills
                        |--------------------------------------------------------------------------
                        */

                        'skills' => [
                            'type' => 'array',

                            'items' => [
                                'type' => 'string',
                            ],
                        ],

                        'relevant_jobs' => [
                            'type' => 'array',

                            'items' => [
                                'type' => 'string',
                            ],
                        ],

                        /*
                        |--------------------------------------------------------------------------
                        | ALL work experiences
                        |--------------------------------------------------------------------------
                        */

                        'work_experience' => [
                            'type' => 'array',

                            'items' => [
                                'type' => 'object',

                                'properties' => [

                                    'company' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],

                                    'designation' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],

                                    'duration' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],

                                    'description' => [
                                        'type' => [
                                            'string',
                                            'null',
                                        ],
                                    ],
                                ],

                                'required' => [
                                    'company',
                                    'designation',
                                    'duration',
                                    'description',
                                ],

                                'additionalProperties' => false,
                            ],
                        ],
                    ],

                    'required' => [
                        'full_name',
                        'email',
                        'phone',
                        'profession',
                        'experience',
                        'education',
                        'skills',
                        'relevant_jobs',
                        'work_experience',
                    ],

                    'additionalProperties' => false,
                ],
            ],
        ],
    ]);


    /*
    |--------------------------------------------------------------------------
    | Get response text
    |--------------------------------------------------------------------------
    */

    $content = $response->outputText ?? '';

    if (!$content) {
        throw new \RuntimeException(
            'OpenAI returned an empty CV extraction response.'
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
            'OpenAI returned invalid CV JSON.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize fields
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

    $data['skills'] =
        isset($data['skills']) &&
        is_array($data['skills'])
            ? $data['skills']
            : [];

    $data['work_experience'] =
        isset($data['work_experience']) &&
        is_array($data['work_experience'])
            ? $data['work_experience']
            : [];


    /*
    |--------------------------------------------------------------------------
    | Remove empty skills
    |--------------------------------------------------------------------------
    */

    $data['skills'] = array_values(
        array_filter(
            array_map(
                fn ($skill) => trim((string) $skill),
                $data['skills']
            ),
            fn ($skill) => $skill !== ''
        )
    );

    $data['relevant_jobs'] =
        isset($data['relevant_jobs']) &&
        is_array($data['relevant_jobs'])
            ? $data['relevant_jobs']
            : [];

    $data['relevant_jobs'] = array_values(
        array_unique(
            array_filter(
                array_map(
                    function ($job) {
                        $job = trim((string) $job);
                        return preg_replace('/\s+/', ' ', $job);
                    },
                    $data['relevant_jobs']
                ),
                fn ($job) => $job !== ''
            )
        )
    );


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

        if (!method_exists($candidate, 'skills')) {
            Log::warning(
                'Candidate skills relationship does not exist.',
                [
                    'candidate_id' => $candidate->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove old skills
        |--------------------------------------------------------------------------
        */

        $candidate->skills()->delete();

        /*
        |--------------------------------------------------------------------------
        | Save new skills
        |--------------------------------------------------------------------------
        */

        foreach ($skills as $skill) {

            if (is_array($skill)) {
                continue;
            }

            $skill = trim((string) $skill);

            if ($skill === '') {
                continue;
            }

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
            Log::warning(
                'Candidate experiences relationship does not exist.',
                [
                    'candidate_id' => $candidate->id,
                ]
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove old experiences
        |--------------------------------------------------------------------------
        */

        $candidate->experiences()->delete();

        /*
        |--------------------------------------------------------------------------
        | Save new experiences
        |--------------------------------------------------------------------------
        */

        foreach ($experiences as $experience) {

            if (!is_array($experience)) {
                continue;
            }

            $candidate->experiences()->create([
                'company' =>
                    $experience['company'] ?? null,

                'job_title' =>
                    $experience['designation'] ?? null,

                'duration' =>
                    $experience['duration'] ?? null,

                'description' =>
                    $experience['description'] ?? null,
            ]);
        }
    }

    private function saveRelevantJobs(
        Candidate $candidate,
        array $jobs
    ): void {

        if (!method_exists($candidate, 'relevantJobs')) {
            Log::warning(
                'Candidate relevantJobs relationship does not exist.',
                [
                    'candidate_id' => $candidate->id,
                ]
            );

            return;
        }

        $jobIds = [];

        foreach ($jobs as $jobTitle) {

            if (is_array($jobTitle)) {
                continue;
            }

            $jobTitle = trim((string) $jobTitle);

            if ($jobTitle === '') {
                continue;
            }

            $jobTitle = preg_replace(
                '/\s+/',
                ' ',
                $jobTitle
            );

            $job = RelevantJob::firstOrCreate([
                'title' => $jobTitle,
            ]);

            $jobIds[] = $job->id;
        }

        $candidate->relevantJobs()->sync(
            array_values(array_unique($jobIds))
        );
    }
}

