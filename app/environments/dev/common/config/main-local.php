<?php

return [
    'components' => [
        'db'            => [
            'class'     => 'yii\db\Connection',
            'dsn'       => 'mysql:host=127.0.0.1;dbname=rest',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
        ],
        'log'           => [
            'traceLevel'    => 3,
            'targets'       => [
                [
                    'class'     => 'yii\log\FileTarget',
                    'levels'    => ['error', 'warning'],
                ],
            ],
        ],
    ],
];
