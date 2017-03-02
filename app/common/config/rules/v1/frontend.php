<?php

return [
    [
        'class'         => \yii\rest\UrlRule::className(),
        'prefix'        => 'api/v1/frontend/json',
        'controller'    => [
            'game' => 'api/v1/frontend/game',
            'turn' => 'api/v1/frontend/turn',
        ],
    ],
];
