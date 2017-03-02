(function() {
    "use strict";

    angular
    .module ('jh', [])
    .config (function($interpolateProvider, $sceDelegateProvider, $httpProvider) {
        $interpolateProvider.startSymbol('[[ ').endSymbol(' ]]');
        $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
        $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
    })
    .constant ("CONFIG", {
        ROUTING: {
            GAME:  {
                NEW: '/api/v1/frontend/json/game',
                GET: '/api/v1/frontend/json/game/{{ID}}',
            },
            API: {
                SEND: '{{ path("game_api_send") }}'
            }
        },
        GAME: {
            STATUS: {
                PREPARE: 'prepare',
                WATCHING: 'watching',
                PLAY: 'play'
            },
            PHASE: {
                WAIT: 'wait',
                GET: 'get',
                ADD: 'add'
            },
            ROLE: {
                LEADER: 'leader',
                PLAYER: 'player',
            }
        }
    }).filter('orderObjectBy', function() {
        return function(items, field, reverse) {
            var filtered = [];
            angular.forEach(items, function(item) {
                filtered.push(item);
            });
            filtered.sort(function (a, b) {
                return (a[field] > b[field] ? 1 : -1);
            });
            if(reverse) filtered.reverse();

            return filtered;
        };
    });
})();