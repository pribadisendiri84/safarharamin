<?php

return [
    'max_attempts' => (int) env('ADMIN_LOGIN_MAX_ATTEMPTS', 3),
    'captcha_ttl_seconds' => (int) env('ADMIN_CAPTCHA_TTL_SECONDS', 300),
];
