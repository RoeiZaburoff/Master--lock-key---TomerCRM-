<?php
// Copy to local.php in the PRIVATE config directory. Never commit the real file.
return [
    'APP_NAME' => 'MasterLocknKey CRM',
    'APP_URL' => 'https://crm.masterlocknkey.com',
    'APP_TIMEZONE' => 'America/New_York',
    'APP_DEBUG' => false,
    'REQUIRE_HTTPS' => true,
    'DB_HOST' => 'localhost',
    'DB_PORT' => 3306,
    'DB_NAME' => 'YOUR_SITEGROUND_DATABASE',
    'DB_USER' => 'YOUR_SITEGROUND_DATABASE_USER',
    'DB_PASSWORD' => '',
    'GOOGLE_PLACES_API_KEY' => '',
    'WEBSITE_ENABLED' => false,
    'WEBSITE_KEYS' => [
        'wordpress-main' => ['secret' => '', 'company_id' => 1],
    ],
    'RETELL_ENABLED' => false,
    'RETELL_API_KEY' => '',
    'RETELL_AGENT_COMPANIES' => [], // 'agent_...' => actual company_id
    'RETELL_STORE_TRANSCRIPT' => false,
    'INTEGRATION_RETENTION_DAYS' => 30,
];
