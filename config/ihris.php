<?php
return [
    'api_base_url' => env('API_BASE', 'https://ihris.bayambang.gov.ph/api'),
    'service_email' => env('IHRIS_SERVICE_EMAIL', 'admin@bayambang.gov.ph'),
    'service_password' => env('IHRIS_SERVICE_PASSWORD', 'admin123'),
    'test_api_base_url' => env('TEST_API_BASE', 'https://testihris.bayambang.gov.ph/api'),
];