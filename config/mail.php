<?php

/* config/mail.php */

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

    'boxes' => [
        1 => [
            'host' => env('MAIL_HOST'),
            'port' => env('MAIL_PORT'),
            'encryption' => env('MAIL_ENCRYPTION'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'from_address' => env('MAIL_FROM_ADDRESS'),
            'from_name' => env('MAIL_FROM_NAME'),
        ],
        2 => [
            'host' => env('MAIL_HOST_2'),
            'port' => env('MAIL_PORT_2'),
            'encryption' => env('MAIL_ENCRYPTION_2'),
            'username' => env('MAIL_USERNAME_2'),
            'password' => env('MAIL_PASSWORD_2'),
            'from_address' => env('MAIL_FROM_ADDRESS_2'),
            'from_name' => env('MAIL_FROM_NAME_2'),
        ],
        3 => [
            'host' => env('MAIL_HOST_3'),
            'port' => env('MAIL_PORT_3'),
            'encryption' => env('MAIL_ENCRYPTION_3'),
            'username' => env('MAIL_USERNAME_3'),
            'password' => env('MAIL_PASSWORD_3'),
            'from_address' => env('MAIL_FROM_ADDRESS_3'),
            'from_name' => env('MAIL_FROM_NAME_3'),
        ],
        4 => [
            'host' => env('MAIL_HOST_4'),
            'port' => env('MAIL_PORT_4'),
            'encryption' => env('MAIL_ENCRYPTION_4'),
            'username' => env('MAIL_USERNAME_4'),
            'password' => env('MAIL_PASSWORD_4'),
            'from_address' => env('MAIL_FROM_ADDRESS_4'),
            'from_name' => env('MAIL_FROM_NAME_4'),
        ],
        5 => [
            'host' => env('MAIL_HOST_5'),
            'port' => env('MAIL_PORT_5'),
            'encryption' => env('MAIL_ENCRYPTION_5'),
            'username' => env('MAIL_USERNAME_5'),
            'password' => env('MAIL_PASSWORD_5'),
            'from_address' => env('MAIL_FROM_ADDRESS_5'),
            'from_name' => env('MAIL_FROM_NAME_5'),
        ],
        6 => [
            'host' => env('MAIL_HOST_6'),
            'port' => env('MAIL_PORT_6'),
            'encryption' => env('MAIL_ENCRYPTION_6'),
            'username' => env('MAIL_USERNAME_6'),
            'password' => env('MAIL_PASSWORD_6'),
            'from_address' => env('MAIL_FROM_ADDRESS_6'),
            'from_name' => env('MAIL_FROM_NAME_6'),
        ],
        7 => [
            'host' => env('MAIL_HOST_7'),
            'port' => env('MAIL_PORT_7'),
            'encryption' => env('MAIL_ENCRYPTION_7'),
            'username' => env('MAIL_USERNAME_7'),
            'password' => env('MAIL_PASSWORD_7'),
            'from_address' => env('MAIL_FROM_ADDRESS_7'),
            'from_name' => env('MAIL_FROM_NAME_7'),
        ],
        8 => [
            'host' => env('MAIL_HOST_8'),
            'port' => env('MAIL_PORT_8'),
            'encryption' => env('MAIL_ENCRYPTION_8'),
            'username' => env('MAIL_USERNAME_8'),
            'password' => env('MAIL_PASSWORD_8'),
            'from_address' => env('MAIL_FROM_ADDRESS_8'),
            'from_name' => env('MAIL_FROM_NAME_8'),
        ],
        9 => [
            'host' => env('MAIL_HOST_9'),
            'port' => env('MAIL_PORT_9'),
            'encryption' => env('MAIL_ENCRYPTION_9'),
            'username' => env('MAIL_USERNAME_9'),
            'password' => env('MAIL_PASSWORD_9'),
            'from_address' => env('MAIL_FROM_ADDRESS_9'),
            'from_name' => env('MAIL_FROM_NAME_9'),
        ],
        10 => [
            'host' => env('MAIL_HOST_10'),
            'port' => env('MAIL_PORT_10'),
            'encryption' => env('MAIL_ENCRYPTION_10'),
            'username' => env('MAIL_USERNAME_10'),
            'password' => env('MAIL_PASSWORD_10'),
            'from_address' => env('MAIL_FROM_ADDRESS_10'),
            'from_name' => env('MAIL_FROM_NAME_10'),
        ],
    ],

];