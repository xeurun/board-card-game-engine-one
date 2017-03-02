(function() {
    "use strict";

    function RoomController ($rootScope, $scope, CONFIG) {
        var self = this;
        this.DEBUG = false;
        this.STATUS = CONFIG.GAME.STATUS;
        this.PHASE = CONFIG.GAME.PHASE;
        this.CARDTYPE = CONFIG.GAME.CARDTYPE;
        this.PLAYERID = -1;
        this.requestSended = false;
        this.selectedCard = null;
        this.selectMode = false;
        this.selectedCards = {};
        this.game = {
            status: self.STATUS.AUTH,
            phase: self.PHASE.GET,
            players: [],
            requests: {},
            deck: [],
            onTable: [],
            turn: null,
            player: null
        };

        $rootScope.$on('event:auth', function(event, message) {
            if (message.player) {
                self.PLAYERID = message.player;
                if (message.status === 'play') {
                    self.game.status = self.STATUS.PLAY;
                    $rootScope.$broadcast('websocket:send', {
                        event: 'game',
                        action: 'info'
                    });
                } else {
                    self.game.status = self.STATUS.WAITING;
                    self.game.players = message.players;
                }
            } else {
                if (message.status === 'play') {
                    self.game.status = self.STATUS.WATCHING;
                    $rootScope.$broadcast('websocket:send', {
                        event: 'game',
                        action: 'info'
                    });
                } else {
                    self.game.status = self.STATUS.PREPARE;
                    self.game.players = message.players;
                }
            }

            $scope.$apply();
        });

        $rootScope.$on('event:request', function(event, message) {
            if(CONFIG.ROOM.ISCREATOR) {
                switch(message.action) {
                    case 'request':
                        self.game.requests[message.user] = message.username;
                        break;
                    case 'allow':
                        self.game.players.push(message.username);
                        break;
                }
            } else {
                switch(message.action) {
                    case 'request':
                        if(message.sended) {
                            self.requestSended = true;
                            alert('Запрос отправлен');
                        }
                        break;
                    case 'deny':
                        self.requestSended = false;
                        alert('Отказ');
                        break;
                    case 'allow':
                        if(message.user === CONFIG.USER.ID) {
                            self.PLAYERID = message.player;
                            self.game.status = self.STATUS.WAITING;
                            alert('Вас добавили в игру');
                        }
                        self.game.players.push(message.username);
                        break;
                }
            }

            $scope.$apply();
        });

        $rootScope.$on('event:game', function(event, message) {
            switch(message.action) {
                case 'start':
                    self.game.deck = message.deck;
                    self.game.turn = message.turn;
                    self.game.phase = message.phase;
                    self.game.status = self.STATUS.PLAY;
                    self.game.onTable = message.onTable;
                    self.game.players = message.players;
                    self.game.requests = null;
                    break;
                case 'info':
                    self.game.deck = message.deck;
                    self.game.turn = message.turn;
                    self.game.phase = message.phase;
                    self.game.status = message.status;
                    self.game.onTable = message.onTable;
                    self.game.players = message.players;
                    break;
                case 'card':
                    self.game.players[message.player].cards[message.card.id] = message.card;
                    self.game.phase = message.phase;
                    break;
                case 'throw':
                    self.game.onTable[message.card.id] = message.card;
                    self.game.phase = message.phase;
                    self.game.turn = message.turn;
                    self.game.players[message.player].points = message.points;
                    delete self.game.players[message.player].cards[message.card.id];
                    self.selectedCard = null;
                    break;
                case 'submit':
                    if(message.recipe) {
                        var player = self.game.players[message.player]
                        var parent = player.cards[message.card];
                        if(parent) {
                            parent.recipe = true;
                        }
                        player.points = message.points;
                        angular.forEach(message.cards, function(value, key) {
                            var card = player.cards[value];
                            if(card) {
                                card.parent = parent.id;
                            }
                        });
                        self.selectMode = false;
                        self.selectedCards = {};
                    } else {
                        if(self.PLAYERID === message.player) {
                            alert('Неправильная комбинация!');
                        }
                    }
                    break;
            }

            $scope.$apply();
        });

        this.play = function() {
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'start'
            });
        };

        this.allowRequest = function(userId) {
            $rootScope.$broadcast('websocket:send', {
                event: 'request',
                action: 'allow',
                allowId: userId
            });

            delete self.game.requests[userId];
        };

        this.denyRequest = function(userId) {
            $rootScope.$broadcast('websocket:send', {
                event: 'request',
                action: 'deny',
                denyId: userId
            });

            delete self.game.requests[userId];
        };

        this.sendRequest = function() {
            $rootScope.$broadcast('websocket:send', {
                event: 'request',
                action: 'request'
            });
        };

        this.getCardFromDeck = function() {
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'card',
                playerId: self.PLAYERID
            });
        };

        this.isCreator = function() {
            return CONFIG.ROOM.ISCREATOR;
        };

        this.isPlayer = function() {
            return self.PLAYERID !== -1;
        };

        this.selectCard = function(id, fromTable) {
            if(self.game.phase === CONFIG.GAME.PHASE.TURN) {
                if(self.selectMode) {
                    if(id !== self.selectedCard.id && self.selectedCards.id === undefined) {
                        if(fromTable) {
                            self.selectedCards[id] = self.game.onTable[id];
                        } else {
                            self.selectedCards[id] = self.game.players[self.PLAYERID].cards[id];
                        }
                    }
                } else if(!fromTable) {
                    self.selectedCard = self.game.players[self.PLAYERID].cards[id];
                }
            }
        };

        this.unselectCard = function(id) {
            delete self.selectedCards[id];
        };

        this.throwOnTable = function() {
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'throw',
                card: self.selectedCard.id
            });
        };

        this.useCard = function() {
            switch(self.selectedCard.type) {
                case CONFIG.GAME.CARDTYPE.SPELL_TRANSFORM:
                    // Заменить собранный рецепт на рецепт из шкафа который считается собранным, карты вернуть
                    // сыграть еще раз
                    break;
                case CONFIG.GAME.CARDTYPE.SPELL_KNOWLEDGE:
                    // Взять из шкафа любую карту
                    // сыграть еще раз
                    break;
                case CONFIG.GAME.CARDTYPE.SPELL_DESTRUCTION:
                    // Выбрать из собранных рецептов одну карту, сделать ее собранной остальные выложить на стол
                    // сыграть еще раз
                    // Очки не давать
                    break;
            }
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'use',
                card: self.selectedCard.id
            });
        };

        this.makeCard = function() {
            alert('Выбирай карты которые хочешь использовать');
            self.selectMode = true;
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'use',
                card: self.selectedCard.id
            });
        };

        this.submitCard = function() {
            var cardIds = [];
            angular.forEach(self.selectedCards, function(value, key) {
                cardIds.push(value.id);
            });
            $rootScope.$broadcast('websocket:send', {
                event: 'game',
                action: 'submit',
                card: self.selectedCard.id,
                cards: cardIds
            });
        };

        this.cancelCard = function() {
            self.selectMode = false;
            self.selectedCards = {};
        };

        this.selectedCardIsSpell = function() {
            if(self.selectedCard) {
                return [
                    CONFIG.GAME.CARDTYPE.SPELL_TRANSFORM,
                    CONFIG.GAME.CARDTYPE.SPELL_KNOWLEDGE,
                    CONFIG.GAME.CARDTYPE.SPELL_DESTRUCTION
                ].indexOf(self.selectedCard.type) >= 0;
            }

            return false;
        };

        this.phaseTurnAndSelectedCard = function() {
            return self.game.phase === self.PHASE.TURN && self.game.turn.id === self.PLAYERID && self.selectedCard !== null;
        };
    };

    angular.module('jh').controller('RoomController', RoomController);
})();