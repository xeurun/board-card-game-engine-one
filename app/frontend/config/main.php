<?php

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id'                    => 'app-frontend',
    'basePath'              => dirname(__DIR__),
    'controllerNamespace'   => 'frontend\controllers',
    'bootstrap'             => ['log'],
    'components'            => [
        'response' => [
            'format'    => (preg_match('/^\/debug/', $_SERVER['REQUEST_URI']) || preg_match('/^\/gii/', $_SERVER['REQUEST_URI'])) ?
                yii\web\Response::FORMAT_HTML : yii\web\Response::FORMAT_JSON,
            'charset'   => 'UTF-8',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => array_merge(
                require(__DIR__ . '/../../common/config/rules/v1/frontend.php')
            ),
        ],
    ],
    'params' => $params,
];
