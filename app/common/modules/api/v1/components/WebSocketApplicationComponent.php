<?php

namespace common\modules\api\v1\components;

use yii\base\Component;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use common\modules\api\v1\models\game\Game;

class WebSocketApplicationComponent extends Component implements MessageComponentInterface
{
    /**
     * @var \SplObjectStorage
     */
    protected $connections;

    /**
     * @var array
     */
    protected $games;

    /**
     * @var array
     */
    protected $userInGame = [];

    /**
     * @inheritdoc
     */
    public function init() {
        $this->connections = [];
        $this->games = [];
        $this->userInGame = [];

        parent::init();

        var_dump('Init complate');
    }

    public function onOpen(ConnectionInterface $connect) {
        var_dump(sprintf('Connect: %s', $connect->resourceId));
        $this->connections[$connect->resourceId] = $connect;
        $connect->send(json_encode([
            'event'  => 'game',
            'action' => 'auth',
            'id'     => $connect->resourceId
        ]));
    }

    public function onMessage(ConnectionInterface $connect = null, $message) {
        var_dump(sprintf('Message: %s', $message));
        try {
            $message = json_decode($message);

            $data   = null;
            $userId = $message->user;

            if(!isset($this->games[$message->game])) {
                /** @var Room $room */
                $game = Game::findOne($message->game);
                /*
                 * Индекс карты это ее номер, значение это идентификатор пользователя у которого эта
                 * карта на руках если значение положительное, если значение отрицательное то это позиция в истории
                 */
                $cards = array_fill(1, 360, null);
                foreach (array_rand($cards,7) as $index) {
                    $cards[$index] = $userId;
                }

                $data = [
                    'game'      => $game,
                    'leader'    => $userId,
                    'status'    => 'prepare',
                    'phase'     => 'add',
                    'players'   => [
                        $userId => 0
                    ],
                    'get'       => [],
                    'show'      => [],
                    'cards'     => $cards,
                    'requests'  => [],
                    'gameCount' => 0
                ];

                $this->games[$message->game] = $data;
            } else {
                $data = $this->games[$message->game];
            }

            $result = [
                'event' => $message->event
            ];

            switch($message->event) {
                case 'request':
                    $result['action'] = $message->action;
                    switch($message->action) {
                        case 'key':
                            $result['key'] = '#' . hash('md5', time());
                            $data['requests'][] = $result['key'];
                            $this->games[$message->game] = $data;
                            $connect->send(json_encode($result));
                            var_dump(sprintf('Send: %s', json_encode($result)));
                            break;
                    }
                    break;
                case 'game':
                    $result['action'] = $message->action;
                    switch($message->action) {
                        case 'info':
                            if(array_key_exists($userId, $data['players']) || !empty($message->key)) {
                                $this->userInGame[$userId] = $data['game']->id;
                                if(!empty($message->key)) {
                                    $key = array_search($message->key, $data['requests']);
                                    if ($key === false) {
                                        var_dump(sprintf(
                                            'Key: %s not found in %s',
                                            $message->key,
                                            var_export($data['requests'])
                                        ));
                                        break;
                                    } else {
                                        $data['players'][$userId] = 0;
                                        $cards = $data['cards'];
                                        foreach (array_rand(array_filter($cards, function($value, $key) {
                                            return $value == null;
                                        }, ARRAY_FILTER_USE_BOTH),7) as $index) {
                                            $cards[$index] = $userId;
                                        }
                                        $data['cards'] = $cards;
                                        unset($data['requests'][$key]);
                                    }
                                }

                                $result['leader'] = $data['leader'];
                                $result['status'] = $data['status'];
                                $result['players'] = $data['players'];
                                $result['cards'] = [];
                                $result['story'] = [];
                                foreach ($data['cards'] as $card => $value) {
                                    if ($value > 0 && $value == $userId) {
                                        $result['cards'][] = $card;
                                    } elseif ($value < 0) {
                                        $result['story'][] = [$card, $value];
                                    }
                                }

                                $this->games[$message->game] = $data;

                                foreach ($data['players'] as $uId => $points) {
                                    if (isset($this->connections[$uId]) && $uId !== $connect->resourceId) {
                                        $data = [
                                            'event' => 'game',
                                            'action' => 'newUser',
                                            'players' => $data['players']
                                        ];

                                        $this->connections[$uId]->send(json_encode($data));
                                        var_dump(sprintf('Send: %s', json_encode($data)));
                                    }
                                }

                                $connect->send(json_encode($result));
                                var_dump(sprintf('Send: %s', json_encode($result)));
                            }
                            break;
                        case 'start':
                            if ($data['leader'] == $userId && $data['status'] == 'prepare') {
                                $data['status'] = 'play';
                                // Разрешаем показать по одной карте
                                $data['show'] = [];
                                // Разрешаем получить по одной карте
                                $data['get'] = array_keys($data['players']);
                                $cards = $data['cards'];
                                // Выкладываем на стол одну из колоды
                                $storyCard = count(array_filter($cards, function($value, $key) {
                                    return $value < 0;
                                }, ARRAY_FILTER_USE_BOTH));
                                $cardId = array_rand(array_filter($cards, function($value, $key) {
                                    return $value == null;
                                }, ARRAY_FILTER_USE_BOTH),1);
                                $cards[$cardId] = -(++$storyCard);
                                $data['cards'] = $cards;
                                $this->games[$message->game] = $data;

                                $result['story'] = [
                                    [$cardId, -(++$storyCard)]
                                ];

                                foreach ($data['players'] as $uId => $points) {
                                    if (isset($this->connections[$uId])) {
                                        $this->connections[$uId]->send(json_encode($result));
                                        var_dump(sprintf('Send: %s', json_encode($result)));
                                    }
                                }
                            }
                            break;
                        case 'show':
                            if ($data['phase'] != 'choose') {
                                $result['error'] = 'Ведущий еще не выложил свою карту!';
                                $connect->send(json_encode($result));
                                var_dump(sprintf('Send: %s', json_encode($result)));
                            } else {
                                if (!array_key_exists($userId, $data['show'])) {
                                    $data['show'][$userId] = $message->cardId;
                                    $this->games[$message->game] = $data;

                                    $result['show'] = $data['show'];

                                    foreach ($data['players'] as $uId => $points) {
                                        if (isset($this->connections[$uId])) {
                                            $this->connections[$uId]->send(json_encode($result));
                                            var_dump(sprintf('Send: %s', json_encode($result)));
                                        }
                                    }
                                }
                            }

                            break;
                        case 'add':
                            if ($data['leader'] == $userId) {
                                if($data['phase'] == 'add' && !empty($message->cardId)) {
                                    $cards = $data['cards'];
                                    $storyCard = count(array_filter($cards, function($value, $key) {
                                        return $value < 0;
                                    }, ARRAY_FILTER_USE_BOTH));
                                    $cards[$message->cardId] = -(++$storyCard);
                                    $data['cards'] = $cards;
                                    $data['phase'] = 'choose';

                                    $result['add'] = $message->cardId;

                                    foreach ($data['players'] as $uId => $points) {
                                        if (isset($this->connections[$uId])) {
                                            $this->connections[$uId]->send(json_encode($result));
                                            var_dump(sprintf('Send: %s', json_encode($result)));
                                        }
                                    }
                                } elseif($data['phase'] == 'choose' && in_array($message->approveId, $data['show']) && !empty($message->approveId)) {
                                    $cards = $data['cards'];
                                    $storyCard = count(array_filter($cards, function($value, $key) {
                                        return $value < 0;
                                    }, ARRAY_FILTER_USE_BOTH));
                                    $cards[$message->approveId] = -(++$storyCard);
                                    $data['cards'] = $cards;

                                    $result['add'] = $message->approveId;

                                    $chain = array_merge(array_keys($data['players']),array_keys($data['players']));
                                    $found = false;
                                    foreach ($chain as $nuid) {
                                        if ($nuid == $data['leader']) {
                                            $found = true;
                                            continue;
                                        }

                                        if ($found) {
                                            $data['leader'] = $nuid;
                                            break;
                                        }
                                    }
                                    $data['phase'] = 'add';

                                    $userPrice = array_search($message->approveId, $data['show']);
                                    if ($userPrice !== false) {
                                        $data['players'][$userPrice]++;
                                    }

                                    $result['show'] = $userPrice;
                                    $result['leader'] = $data['leader'];

                                    // Разрешаем показать по одной карте
                                    $data['show'] = [];
                                    // Разрешаем получить по одной карте
                                    $data['get'] = array_keys($data['players']);

                                    foreach ($data['players'] as $uId => $points) {
                                        if (isset($this->connections[$uId])) {
                                            $this->connections[$uId]->send(json_encode($result));
                                            var_dump(sprintf('Send: %s', json_encode($result)));
                                        }
                                    }
                                }

                                $this->games[$message->game] = $data;
                            }
                            break;
                        case 'get':
                            if (in_array($userId, $data['get'])) {
                                $cards = $data['cards'];
                                $availableCards = array_filter($cards, function($value, $key) {
                                    return $value == null;
                                }, ARRAY_FILTER_USE_BOTH);
                                $cardId = array_rand($availableCards,1);
                                $cards[$cardId] = $userId;
                                $data['cards'] = $cards;
                                $key = array_search($userId, $data['get']);
                                if ($key !== false) {
                                    unset($data['get'][$key]);
                                }
                                $this->games[$message->game] = $data;
                                $result['cardId'] = $cardId;

                                $connect->send(json_encode($result));
                                var_dump(sprintf('Send: %s', json_encode($result)));
                            }
                            break;
                    }
                    break;
            }
        } catch(\Exception $ex) {
            var_dump($ex->getMessage() . ' in ' . $ex->getFile() . ' on '. $ex->getLine());
        }
    }

    public function onClose(ConnectionInterface $connect) {
        var_dump(sprintf('Close: %s', $connect->resourceId));
        $gameId = $this->userInGame[$connect->resourceId];
        if(isset($this->games[$gameId])) {
            if(empty($this->games[$gameId]['players'])) {
                unset($this->games[$gameId]);
            } else {
                unset($this->games[$gameId]['players'][$connect->resourceId]);
            }
        }
        unset($this->connections[$connect->resourceId]);
    }

    public function onError(ConnectionInterface $connect, \Exception $e) {
        var_dump(sprintf('Error: %s', $e->getMessage()));
        $connect->close();
    }
}