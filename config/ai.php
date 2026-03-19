<?php

return [
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'max_tokens' => env('AI_MAX_TOKENS', 500),
    'temperature' => env('AI_TEMPERATURE', 0.7),
];
