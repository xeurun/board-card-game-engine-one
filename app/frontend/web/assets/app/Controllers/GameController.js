(function() {
    "use strict";

    function GameController ($http, $rootScope, $scope, CONFIG)
    {
        var self = this,
            connect = null,
            gameId = null;

        this.pending = {};
        this.story = {};
        this.myCards = {};
        this.players = {};
        this.selectedCard = null;
        this.gameStatus = null;
        this.leader = false;
        this.status = 'Ожидание начала игры';
        this.userId = 0;
        this.leaderId = 0;
        this.gameStatuses = CONFIG.GAME.STATUS;

        /**
         * Создание новой игры
         */
        this.new = function() {
            connecting();
        };

        /**
         * Присоедениться
         */
        this.join = function() {
            if (location.hash != "") {
                var params = location.hash.split('|');
                if (params.length == 2) {
                    gameId = params[1];
                    connecting(params[0])
                }
            }
        };

        /**
         * Выбор карты
         * @param cardId
         */
        this.select = function(cardId) {
            self.selectedCard = self.myCards[cardId];
        };

        /**
         * Начало игры
         */
        this.start = function() {
            self.gameStatus = CONFIG.GAME.STATUS.PLAY;
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'start'
            });
        };

        this.call = function() {
            $rootScope.$broadcast('websocket:send', {
                event: 'request',
                action: 'key'
            });
        };

        /**
         * Взять карту из колоды
         */
        this.get = function() {
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'get'
            });
        };

        /**
         * Предложить карту в историю
         */
        this.show = function() {
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'show',
                cardId: self.selectedCard.id
            });
        };

        /**
         * Добавить карту в историю (для лидера)
         */
        this.add = function(id) {
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'add',
                cardId: self.selectedCard.id,
                approveId: id
            });
        };

        /**
         * Соеденение с сервером
         */
        function connecting(hash) {
            if (!connect) {
                connect = new WebSocket('ws://188.166.49.99:8080');

                connect.onopen = function (e) {
                    console.log('Open');
                };
                connect.nclose = function (e) {
                    console.log('Close: ' + e);
                };
                connect.onerror = function (e) {
                    console.log('Error: ' + e);
                };
                connect.onmessage = function (e) {
                    console.log('Get message: ' + e.data);
                    var message = JSON.parse(e.data);
                    $rootScope.$broadcast('event:' + message.event, message);
                };

                $rootScope.$on('websocket:send', function (event, message) {
                    message.game = gameId;
                    message.user = self.userId;
                    console.log('Send message: ' + JSON.stringify(message));
                    connect.send(JSON.stringify(message));
                });

                $rootScope.$on('event:request', function(event, message) {
                    switch(message.action) {
                        case 'key':
                            prompt('Код для подключения', window.location.href + message.key + '|' + gameId);
                            break;
                        case 'check':
                            break;
                    }

                    $scope.$apply();
                });

                $rootScope.$on('event:game', function(event, message) {
                    if(message.error) {
                        alert(message.error);
                        return;
                    }

                    switch(message.action) {
                        case 'auth':
                            self.userId = message.id;
                            if (!gameId) {
                                $http.post(CONFIG.ROUTING.GAME.NEW).success(function(data) {
                                    if (data.id) {
                                        gameId = data.id;
                                        self.gameStatus = CONFIG.GAME.STATUS.PREPARE;
                                        $rootScope.$broadcast('websocket:send', {
                                            event: 'game',
                                            action: 'info',
                                            key: hash
                                        });
                                    } else {
                                        alert('Чот ошибка');
                                        connect.close();
                                    }
                                });
                            } else {
                                self.gameStatus = CONFIG.GAME.STATUS.PLAY;
                                $rootScope.$broadcast('websocket:send', {
                                    event: 'game',
                                    action: 'info',
                                    key: hash
                                });
                            }
                            break;
                        case 'newUser':
                            self.players = message.players;
                            break;
                        case 'info':
                            self.leader = message.leader == self.userId;
                            self.leaderId = message.leader;
                            self.players = message.players;
                            angular.forEach(message.cards, function(value, key) {
                                self.myCards[value] = {
                                    'id': value,
                                    'link': 'assets/cards/Pic.' + value + '.png'
                                }
                            });
                            angular.forEach(message.story, function(value, key) {
                                self.story[value[0]] = {
                                    'id': value[0],
                                    'link': 'assets/cards/Pic.' + value[0] + '.png',
                                    'position': Object.keys(self.story).length
                                }
                            });
                            break;
                        case 'show':
                            angular.forEach(message.show, function(value, key) {
                                self.pending[value] = {
                                    'id': value,
                                    'link': 'assets/cards/Pic.' + value + '.png'
                                };
                            });
                            break;
                        case 'get':
                            self.myCards[message.cardId] = {
                                'id': message.cardId,
                                'link': 'assets/cards/Pic.' + message.cardId + '.png'
                            };
                            break;
                        case 'add':
                            delete self.myCards[message.add];

                            if (message.show) {
                                self.players[message.show] = parseInt(self.players[message.show]) + 1;
                            }

                            if (message.clear) {
                                self.story = {};
                                self.story[message.clear[0]] = {
                                    'id': message.clear[0],
                                    'link': 'assets/cards/Pic.' + message.clear[0] + '.png',
                                    'position': Object.keys(self.story).length
                                }
                            }

                            if (self.leader) {
                                self.status = 'Ожидание предложений от игроков, выбор карты';
                            } else {
                                if (self.status == 'Ожидание добавления карты лидером') {
                                    self.status = 'Предложение своего варианта карты, ожидание выбора карты лидером';
                                } else {
                                    self.status = 'Ожидание добавления карты лидером';
                                }
                            }

                            if (message.leader) {
                                self.leader = message.leader == self.userId;
                                self.leaderId = message.leader;
                                self.pending = {};
                                if (self.leader) {
                                    self.status = 'Ожидание добавление карты';
                                } else {
                                    self.status = 'Ожидание добавления карты лидером';
                                }
                            }

                            self.story[message.add] = {
                                'id': message.add,
                                'link': 'assets/cards/Pic.' + message.add + '.png',
                                'position': Object.keys(self.story).length
                            };
                            break;
                        case 'start':
                            angular.forEach(message.story, function(value, key) {
                                self.story[value[0]] = {
                                    'id': value[0],
                                    'link': 'assets/cards/Pic.' + value[0] + '.png',
                                    'position': Object.keys(self.story).length
                                }
                            });

                            if (self.leader) {
                                self.status = 'Ожидание добавление карты';
                            } else {
                                self.status = 'Ожидание добавления карты лидером';
                            }
                            break;
                    }

                    $scope.$apply();
                });
            }
        }
    };

    angular.module('jh').controller('GameController', GameController);
})();
