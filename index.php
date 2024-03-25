<?php
require 'connect.php';
require 'controllers/GameController.php';
require 'routes/api.php';

spl_autoload_register(function ($className) {
    $filePath = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    if (file_exists($filePath)) {
        require $filePath;
    }
});

$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$route = $parsedUrl['path'];

// // Split the route into parts
// $routeParts = explode('/', $route);

// // Find the corresponding route in the routes array
// foreach ($routes as $routePattern => $controllerAction) {
//     // Split the route pattern into parts
//     $patternParts = explode('/', $routePattern);

//     // Check if the number of parts match
//     if (count($patternParts) === count($routeParts)) {
//         $match = true;

//         // Check if each part matches
//         foreach ($patternParts as $key => $part) {
//             if ($part !== $routeParts[$key] && strpos($part, '{') !== 0) {
//                 // If the parts don't match and it's not a placeholder, the route doesn't match
//                 $match = false;
//                 break;
//             }
//         }

//         if ($match) {
//             // Extract parameters from the route if it matches
//             $parameters = [];
//             foreach ($patternParts as $key => $part) {
//                 if (strpos($part, '{') === 0) {
//                     // This part is a placeholder, extract the parameter
//                     $parameterName = trim($part, '{}');
//                     $parameters[$parameterName] = $routeParts[$key];
//                 }
//             }

//             // Now you can use the extracted parameters and call the corresponding controller action
//             list($controller, $action) = explode('@', $controllerAction);
//             $gameController = new $controller($pdo);
//             $gameController->$action($parameters);

//             // Stop further processing as the route has been matched
//             exit;
//         }
//     }
// }

$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$route = $parsedUrl['path'];
$sessionId = basename($parsedUrl['path']);
// dd(is_numeric($sessionId));

$gameController = new GameController($pdo);
// dd($routes[$route]);
switch (true) {
    case $routes[$route] === "GameController@addPlayerBalance":
        $gameController->addPlayerBalance();
        break;
    case $routes[$route] === "GameController@easy2Bet":
        $gameController->easy2Bet();
        break;
    case $routes[$route] === "GameController@createSession":
        $gameController->createSession();
        break;
    case $routes[$route] === "GameController@startGame":
        $gameController->startGame();
        break;
    case is_numeric($sessionId):
        $gameController->closeGame((int)$sessionId);
        break;
    default:
        break;
}


