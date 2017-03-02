<?php

return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'webSocketApplication' => [
            'class' => \common\modules\api\v1\components\WebSocketApplicationComponent::class
        ]
    ],
    'modules' => [
        'api' => [
            'class'   => 'common\modules\api\Module',
            'modules' => [
                'v1' => [
                    'class' => 'common\modules\api\v1\Module'
                ],
            ],
        ],
    ]
];
