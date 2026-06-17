<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'meshy' => [
        // Clef Meshy.ai (https://www.meshy.ai). Si null OU MESHY_FAKE=true,
        // le service container bind FakeMeshyClient (renvoie placeholders).
        'api_key'  => env('MESHY_API_KEY'),
        'base_url' => env('MESHY_BASE_URL', 'https://api.meshy.ai'),
        'fake'     => env('MESHY_FAKE', false),
        // TTL max d'attente d'une tâche avant marking failed (sécurité).
        'task_timeout_seconds' => env('MESHY_TASK_TIMEOUT', 1800),
        // Délai entre 2 polls (job dispatché en retry).
        'poll_interval_seconds' => env('MESHY_POLL_INTERVAL', 15),
    ],

];
