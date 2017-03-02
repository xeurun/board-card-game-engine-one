<?php

namespace common\modules\api\v1\controllers\console;

use yii\console\Controller;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

/**
 * Команды управления сервером
 */
class ServerCommandController extends Controller
{
    /**
     * Запуск сервера
     */
    public function actionStart()
    {
        $ws = new WsServer(\Yii::$app->webSocketApplication);
        $ws->disableVersion(0); // old, bad, protocol version
        $server = IoServer::factory(
            new HttpServer($ws),
            8080
        );

        $server->run();
    }
}