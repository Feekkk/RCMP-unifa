<?php

/**
 * Copy this file to `services/email_config.php` and fill in values.
 * That real config file should stay out of git.
 */
return [
    // Options: 'mail' (PHP mail()) or 'smtp' (reserved for later)
    'driver' => 'SMTP',

    'from' => [
        'email' => 'unifa.rcmp@unikl.edu.my',
        'name' => 'RCMP UniFa',
    ],

    // Used by the 'smtp' driver (not implemented yet; config-only for now)
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls', // 'tls' | 'ssl' | null
        'username' => 'unifa.rcmp@unikl.edu.my',
        'password' => 'Unifa@2026',
    ],
];

