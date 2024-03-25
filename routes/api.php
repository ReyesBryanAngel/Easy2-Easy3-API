<?php
$routes = [
    '/api/v1/addBalance' => 'GameController@addPlayerBalance',
    '/api/v1/create-session' => 'GameController@createSession',
    '/api/v1/startGame' => 'GameController@startGame',
    '/api/v1/easy2' => 'GameController@easy2Bet',
    '/api/v1/easy2-declarator' => 'GameController@easy2Declarator',
    '/api/v1/closeGame/{gameSessionId}' => 'GameController@closeGame',
];