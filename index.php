<?php
require './vendor/autoload.php';
require './public/v1/routes/api.php';
require './public/v1/numberGames/Helpers/response.php';

$request = $_SERVER['REQUEST_URI'];
$urlComponents = parse_url($request);
$path = $urlComponents['path'];
$query = isset($urlComponents['query']) ? $urlComponents['query'] : '';
$routesWithParams = [
    '/api/v1/get-round',
    '/api/v1/player-details',
    '/api/v1/player-currentBets',
    '/api/v1/get-transactions',
    '/api/v1/board-validator',
    '/api/v1/current-board',
    '/api/v1/winning-history',
    '/api/v1/check-session',
];

if (!array_key_exists($path, $numberGamesRoutes)) {
    jsonResponse([
        'code' => 404,
        'status' => 'failed',
        'message' => '404 Not Found'
    ], 404);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (in_array($path, $routesWithParams)) {
            parse_str($query, $params);
            call_user_func($numberGamesRoutes[$path]);
        } else {
            jsonResponse([
                'code' => 405,
                'status' => 'failed',
                'message' => 'Method Not Allowed'
            ], 405);
        }
        break;
    case 'POST':
        if (!in_array($path, $routesWithParams)) {
            call_user_func($numberGamesRoutes[$path]);
        } else {
            jsonResponse([
                'code' => 405,
                'status' => 'failed',
                'message' => 'Method Not Allowed'
            ], 405);
        }
        break;
    default:
        jsonResponse([
            'code' => 405,
            'status' => 'failed',
            'message' => 'Method Not Allowed'
        ], 405);
        break;
}
