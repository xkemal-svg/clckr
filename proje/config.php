<?php

return [
    'db' => [
        'host' => getenv('CLOACKER_DB_HOST') ?: '127.0.0.1',
        'name' => getenv('CLOACKER_DB_NAME') ?: 'cloacker_db',
        'user' => getenv('CLOACKER_DB_USER') ?: 'hayalimbilgi',
        'pass' => getenv('CLOACKER_DB_PASS') ?: '4Rmsy2PnTvcSi54wfgW5LIKJw',
        'charset' => 'utf8mb4',
    ],
    'api_keys' => [
        'iphub' => getenv('CLOACKER_IPHUB_KEY') ?: 'MzAzMTA6TnFGamJkcjlSbVQ3UjhhU3ZRVTlMcHMwNnlqVkVOTHg=',
        'abuseipdb' => getenv('CLOACKER_ABUSEIPDB_KEY') ?: '82e4913cef929eb468387f5cdd4870d4c9306f04e0336221788ada18a6b6d8cb25b3e9bb62efd6de',
        'ip2location' => getenv('CLOACKER_IP2LOCATION_KEY') ?: 'A0825577B69569641F776450EACDF02F',
    ],
    'security' => [
        'session_timeout' => (int)(getenv('CLOACKER_SESSION_TIMEOUT') ?: 900),
        'max_login_attempts' => (int)(getenv('CLOACKER_MAX_LOGIN_ATTEMPTS') ?: 5),
        'login_lockout_minutes' => (int)(getenv('CLOACKER_LOGIN_LOCKOUT_MINUTES') ?: 15),
        'bot_score_threshold' => (int)(getenv('CLOACKER_BOT_SCORE_THRESHOLD') ?: 2),
    ],
];

