<?php
use v1\NumberGames\Controllers\SessionController;
use v1\NumberGames\Controllers\GameController;

$numberGamesRoutes = [
    '/api/v1/oauth' => [new SessionController(), 'createSession'],
    '/api/v1/start-game' => [new SessionController(), 'startGame'],
    '/api/v1/refresh-session' => [new SessionController(), 'refreshSession'],
    '/api/v1/check-session' => [new SessionController(), 'checkSession'],
    '/api/v1/get-events' => [new GameController(), 'getEvents'],
    '/api/v1/get-boards' => [new GameController(), 'getBoards'],
    '/api/v1/choose-board' => [new GameController(), 'chooseBoard'],
    '/api/v1/get-round' => [new GameController(), 'getRound'],
    '/api/v1/player-details' => [new GameController(), 'playerDetails'],
    '/api/v1/player-currentBets' => [new GameController(), 'playerCurrentBets'],
    '/api/v1/winning-history' => [new GameController(), 'winningHistory'],
    '/api/v1/get-transactions' => [new GameController(), 'getTransactions'],
    '/api/v1/place-bet' => [new GameController(), 'bet'],
    '/api/v1/current-board' => [new GameController(), 'currentBoard'],
    '/api/v1/board-validator' => [new GameController(), 'boardValidator'],
    '/api/v1/withdraw-balance' => [new GameController(), 'withdrawBalance'],
    '/api/v1/deposit-balance' => [new GameController(), 'depositBalance']
];