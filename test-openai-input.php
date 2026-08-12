<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use OpenAI\Laravel\Facades\OpenAI;

$response = OpenAI::responses()->create([
    'model' => 'gpt-5-mini',
    'input' => 'Reply with exactly: OPENAI TEST OK',
]);

echo $response->outputText . PHP_EOL;