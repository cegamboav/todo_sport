<?php

return [

    'dev_mode' => env('LICENSE_DEV_MODE', true),

    'path' => storage_path('licenses/current.license'),

    'default_grace_days' => 14,

    'public_keys' => [
        // key_id => base64 public key (L1)
    ],

];
