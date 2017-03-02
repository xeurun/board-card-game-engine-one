<?php

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id'                    => 'app-backend',
    'basePath'              => dirname(__DIR__),
    'controllerNamespace'   => 'backend\controllers',
    'bootstrap'             => ['log'],
    'components'            => [
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => array_merge(
                require(__DIR__ . '/../../common/config/rules/v1/backend.php')
            ),
        ],
    ],
    'params' => $params,
];
