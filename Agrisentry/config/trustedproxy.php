<?php

return [
    // Read by Laravel's proxy middleware after configuration has bootstrapped.
    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),
];
