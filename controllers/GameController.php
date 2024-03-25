<?php
require 'helpers/debugger.php';
require 'helpers/jwt.php';
require 'helpers/response.php';
require 'helpers/randomStringGenerator.php';
require "models/Easy2Model.php";
require "models/OperatorModel.php";
require "models/GameSessionModel.php";
require "models/GameStartModel.php";
require "models/InternalWalletModel.php";

class GameController {
    private $pdo;

    private static $counter = 0;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addPlayerBalance()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $internalWalletModel = new InternalWalletModel($this->pdo);
        $gameSessionModel = new GameSessionModel($this->pdo);
        $operatorId = $gameSessionModel->checkOperatorName($payload['operator_name']);
        $playerName = $payload['player_name'];
        if ($operatorId['id'] === null) {
            jsonResponse([
                'code' => '400',
                'status' => 'failed',
                'message' => "Operator does not exist"
            ], 400);
        }
        $internalWalletModel->insertInternalWallet(
            $operatorId['id'],
            $payload['player_name'],
            $payload['balance'],
        );

        jsonResponse([
            'code' => '200',
            'status' => 'success',
            'message' => "Operator has successfully add balance for $playerName"
        ], 200);
    }
    public function createSession() 
    {
        $gameSessionModel = new GameSessionModel($this->pdo);
        $gameStart = new GameStartModel($this->pdo);
        $internalWalletModel = new InternalWalletModel($this->pdo);
        $payload = json_decode(file_get_contents('php://input'), true);
        $operatorId = $gameSessionModel->checkOperatorName($payload['operator_name']);
        $gameTypErrors = $gameSessionModel->limitGameType($payload['game_type']);
        $playerName =  $payload['player_name'];
        $dataToHash = $payload['operator_name'] . $playerName . $payload['bet_limit'] . $payload['balance'] . $gameSessionModel->checkOperatorPass(($operatorId['id']));     
        $generatedHash = md5($dataToHash);
        $playerInfo = $internalWalletModel->queryPlayerWallet($payload['player_name']);
        $sessionExpiration = $gameSessionModel->checkSessionExpiration($playerInfo['id']);
        $queryDateClose = $gameStart->queryGameStarts($sessionExpiration['internal_wallet_id']);
       
        switch (true) {
            case !empty($gameTypErrors):
                jsonResponse([
                    'code' => '400',
                    'status' => 'error',
                    'message' => $gameTypErrors
                ], 422);
                break;
            case $operatorId['id'] === null:
                jsonResponse([
                    'code' => '400',
                    'status' => 'failed',
                    'message' => 'Operator does not exist'
                ], 400);
                break;
            case date('Y-m-d H:i:s') > $sessionExpiration['expire_at'] && $sessionExpiration['expire_at'] !== null:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => 'Game session has expired already',
                ], 422);
                break;
            case $generatedHash !== $payload['hash']:
                jsonResponse([
                    'code' => '400',
                    'status' => 'failed',
                    'message' => 'Invalid operator credentials',
                ], 422);
                break;
            case date('Y-m-d H:i:s') < $sessionExpiration['expire_at'] && 
            $playerInfo['player_name'] === $payload['player_name'] &&
            $queryDateClose['date_close'] === null:
                jsonResponse([
                    'code' => '200',
                    'status' => 'success',
                    'message' => "Game session is currently running for $playerName.",
                ], 200);
                break;
            default:
                $token = $gameSessionModel->insertGameSession(
                    $playerInfo['id'],
                    $payload['bet_limit'], 
                    $payload['hash'],
                    $payload['game_type'],
                );
                
                jsonResponse([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Game session has started successfully',
                    'token' => $token,
                ], 200);
                break;
        }
    }

    public function gameType() 
    {
        $gameType = $_GET['gameType'];
        return $gameType;
    }

    public function queryGameType()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        $parsedUrl = parse_url($requestUri);
        $gameType = basename($parsedUrl['path']);
        // dd($gameType);
        return $gameType;
    }
    public function startGame() 
    {
        $token = $_GET['key'];
        $dateTimeNow = date('Y-m-d H:i:s');
        $gameStart = new GameStartModel($this->pdo);
        $gameSessionColumns = $gameStart->queryGameSessions($token);

        $dateCloseAndId = $gameStart->checkStartedGame($gameSessionColumns['id']);

        switch (true) {
            case $gameSessionColumns['expire_at'] === null:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => 'Token is invalid',
                ], 400);
                break;
            case $dateTimeNow > $gameSessionColumns['expire_at']:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => 'You are about to open a session that has expired. Please go back to the game selection',
                ], 422);
                break;
            case $dateCloseAndId['date_close'] !== null:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => $this->gameType() . ' game for this token has been closed already',
                ], 422);
            case $dateCloseAndId['game_session_id'] !== null:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => $this->gameType() . ' game is validated already',
                ], 422);
            case $gameSessionColumns['game_type'] !== $this->gameType():
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => 'Game type from the url is not the same from the game you selected',
                ], 422);
            default:
                $gameStart->startGame($gameSessionColumns['id'], $dateTimeNow);
                jsonResponse([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Game has start for ' . $this->gameType()
                ], 200);
        }
    }

    public function closeGame($gameSessionId)
    {  
        $gameStart = new GameStartModel($this->pdo);
        $sessionId = $gameStart->checkGameSessionId($gameSessionId);
        $dateCloseAndId = $gameStart->checkStartedGame($sessionId['id']);
        
        if ($dateCloseAndId['game_session_id'] === null) {
            jsonResponse([
                'code' => '404',
                'status' => 'error',
                'message' => 'Game session not found'
            ], 404);
        }

        if ($dateCloseAndId['date_close'] !== null) {
            jsonResponse([
                'code' => '400',
                'status' => 'error',
                'message' => 'You are trying to close a session that has been closed already.'
            ], 400);
        }


        $sql = "UPDATE started_games SET date_close = :date_close WHERE game_session_id = :game_session_id";
        $dateNow = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':date_close', $dateNow);
        $stmt->bindParam(':game_session_id', $gameSessionId);
        $stmt->execute();
        
        jsonResponse([
            'code' => '200',
            'status' => 'success',
            'message' => 'Game has been closed successfully'
        ], 200);
    }

    public function easy2Bet () 
    {   
        // session_start();
        $easy2Model = new Easy2Model($this->pdo);
        $gameStartModel = new GameStartModel($this->pdo);
        $payload = json_decode(file_get_contents('php://input'), true);
        $maxRound = $easy2Model->getMaxRound();
        $payload['round'] = $maxRound + 1;
        $rambolito = $payload['rambolito'] ?? null;
        $advanceDraws = $payload['advance_draws'] ?? null;
        $consecutiveDraws = $payload['consecutive_draws'] ?? null;
        $luckyPick = $payload['lucky_pick'] ?? null;
        $errors = $easy2Model->validateEasy2BetPayload($payload);
        $findStartedGame = $easy2Model->queryStartedGame($payload['started_game_id']);
        $queryGameSessions = $gameStartModel->checkGameSessionId($findStartedGame['game_session_id']);
        // $selectedNumbers = $payload['selected_numbers'];
        // $_SESSION['selected_numbers'] = $selectedNumbers;
        // if($advanceDraws) {
        //     $_SESSION['counter'] = 0;
        // }

        // $_SESSION['counter']++;

        // if ($_SESSION['counter'] < $consecutiveDraws && $selectedNumbers !== $_SESSION['selected_numbers']) {
        //     $_SESSION['selected_numbers'] = null;
        //     jsonResponse([
        //         'code' => '422',
        //         'status' => 'failed',
        //         'message' => 'You cannot select other betting number while consecutive draws you chose is not satisfied'
        //     ], 400);
        // } else {
        //     $_SESSION['counter'] = 0;
        // }

        // dd($_SESSION['selected_numbers']);
        
        switch (true) {
            case !empty($errors):
                jsonResponse([
                    'code' => '422',
                    'status' => 'error',
                    'message' => $errors
                ], 422);
                break;
            case !$findStartedGame:
                jsonResponse([
                    'code' => '400',
                    'status' => 'failed',
                    'message' => 'There is no started game session associated in this round.'
                ], 400);
                break;
            case $findStartedGame['date_close'] !== null:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => 'Betting in this session has been closed.'
                ], 422);
                break;
            case date('Y-m-d H:i:s') > $queryGameSessions['expire_at']:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => 'Betting in this game session has expired.'
                ], 422);
                break;
            case $this->queryGameType() !== $queryGameSessions['game_type']:
                jsonResponse([
                    'code' => '422',
                    'status' => 'failed',
                    'message' => 'The game where you are going to bet is not the game you selected from game selection.'
                ], 422);
            default:
                $easy2Model->insertEasy2Bet(
                    $payload['started_game_id'],
                    $payload['round'],
                    $payload['bet_amount'],
                    $payload['selected_numbers'],
                    $rambolito,
                    $advanceDraws,
                    $consecutiveDraws,
                    $luckyPick
                );
                jsonResponse([
                    'code' => '200',
                    'status' => 'success',
                    'message' => 'Bet is successful',
                ], 200);
                
                break;
        }
    }

    public function getCounterValue() {
        return self::$counter;
    }
}
