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
You are an EXTREMELY thorough CV/resume data extraction engine.

Your task is NOT to summarize the CV.

Your task is to read the ENTIRE CV and extract ALL meaningful information that belongs to the requested database fields.

Think like a professional recruiter AND a document extraction system.

The original CV must be treated as the source of truth.

==================================================
CRITICAL REQUIREMENT: DO NOT LOSE INFORMATION
==================================================

You MUST inspect the ENTIRE uploaded CV.

Read:

- header
- name
- contact information
- email
- phone
- professional title
- profile
- about me
- career objective
- summary
- education
- qualifications
- certifications
- courses
- skills
- technical skills
- professional skills
- languages
- work experience
- employment history
- internships
- projects
- achievements
- any other information that clearly belongs to the requested fields.

Do NOT summarize away information.

Do NOT select only the "best" skill.

Do NOT select only the latest education.

Do NOT select only the latest job.

Do NOT return only the first matching item.

Extract ALL relevant information visible in the CV.

==================================================
NAME
==================================================

Find the candidate's real name.

Use contextual understanding.

Do NOT use these as names:

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
CURRICULUM

If the name appears in large text at the top, it is highly likely to be the candidate's name.

Preserve the name exactly as written, except for obvious unnecessary whitespace.

==================================================
EMAIL
==================================================

Extract the actual email address wherever it appears.

Search the ENTIRE document.

Do not return null if a clearly readable email exists.

Do not confuse website URLs with email addresses.

==================================================
PHONE
==================================================

Extract the candidate's actual phone/mobile/contact number.

Search the entire CV.

Preserve the number as written.

Do not invent country codes.

Do not confuse dates, IDs or postal codes with phone numbers.

==================================================
PROFESSION
==================================================

Identify the candidate's primary/current professional role.

Use information such as:

- title under name
- current job title
- latest job designation
- professional summary
- career objective
- strongest professional context

Return the most appropriate professional title.

==================================================
EXPERIENCE
==================================================

Extract total professional experience if explicitly stated.

If it is not explicitly stated, calculate it ONLY when employment dates make the calculation reasonably reliable.

Do not invent experience.

Examples:

"5 years"
"3 Years 6 Months"
"2 years"
"Not specified"

==================================================
EDUCATION
==================================================

THIS FIELD IS VERY IMPORTANT.

Extract EVERY education/qualification entry.

If the CV contains:

University A
University B
Bachelor's
Master's
Diploma
College
School
Certification
Qualification

do NOT choose only one.

Preserve ALL relevant education information inside the SINGLE "education" field.

Use a clear format such as:

Borcelle University | 2026-2030 | Senior Accountant
Borcelle University | 2023-2026 | Senior Accountant

If there are multiple universities, include ALL of them.

If there are multiple degrees, include ALL of them.

If there are dates, preserve the dates.

If there is a field of study, preserve it.

If there is a qualification title, preserve it.

Do NOT replace complete education with only the university name.

==================================================
SKILLS
==================================================

THIS FIELD IS EXTREMELY IMPORTANT.

Extract ALL identifiable skills.

Do NOT summarize.

Do NOT select only the first few skills.

Do NOT remove repeated-looking skills unless they are literally identical duplicates.

Extract skills from:

- Skills section
- Technical Skills
- Professional Skills
- Expertise
- Competencies
- Technologies
- Tools
- Software
- job descriptions
- project descriptions
- professional summary

If a skill is clearly identifiable from the CV, include it.

Examples:

Auditing
Financial Accounting
Financial Reporting
Microsoft Excel
QuickBooks
Financial Analysis

Return ALL skills as separate array items.

==================================================
WORK EXPERIENCE
==================================================

Extract EVERY identifiable work experience.

Do NOT return only the latest job.

For EVERY job return:

company
designation
duration
description

If the CV contains:

Company A
Company B

both MUST be returned.

If the CV contains:

Accountant
Financial Accountant

both MUST be returned if they represent separate employment entries.

Preserve meaningful descriptions.

Do not shorten descriptions unnecessarily.

==================================================
DUPLICATE INFORMATION
==================================================

Do not lose information merely because similar information appears twice.

For example, if:

Auditing

appears in two places, one clean "Auditing" entry is acceptable.

But if:

Financial Accounting
Financial Reporting
Auditing

are present, ALL three must be returned.

==================================================
TABLES AND LAYOUT
==================================================

Pay special attention to:

- columns
- tables
- bullet points
- headers
- sidebars
- footer information
- text near icons
- text under headings

Information may not be arranged like a normal paragraph.

Understand the visual/document layout.

==================================================
IMAGE / SCANNED CV
==================================================

If this is an image or scanned document:

VISUALLY INSPECT THE DOCUMENT.

Read all clearly visible text.

Do not depend on OCR.

Pay attention to text inside:

- headers
- columns
- tables
- bullet lists
- sidebars
- footer
- contact section

==================================================
NO GUESSING
==================================================

Never invent information.

If information genuinely does not exist, return null.

If no skills exist, return [].

If no work experience exists, return [].

==================================================
OUTPUT REQUIREMENT
==================================================

Return ONLY valid JSON.

No markdown.

No explanation.

No comments.

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
    "work_experience": [
        {
            "company": null,
            "designation": null,
            "duration": null,
            "description": null
        }
    ]
}

IMPORTANT:

The goal is MAXIMUM INFORMATION RECALL.

Do NOT summarize the CV.

Do NOT omit information simply because there is a lot of it.

Extract everything relevant to these fields.
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
                        | ALL education entries stay in one string field
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
                        | ALL skills
                        |--------------------------------------------------------------------------
                        */

                        'skills' => [
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
}

