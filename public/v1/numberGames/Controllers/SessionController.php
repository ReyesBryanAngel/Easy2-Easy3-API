<?php
namespace v1\NumberGames\Controllers;

use v1\numberGames\Requests\numberGamesRequest;
use v1\numberGames\Helpers\JsonWebToken;
use v1\numberGames\Utils\Oauth;
use v1\Database\Database;
class SessionController {
    private $numberGamesRequest;
    private $jwt;
    private $oauth;
    private $db;
    private $conn;
    public function __construct() {
        $this->numberGamesRequest = new numberGamesRequest();
        $this->jwt = new JsonWebToken();
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->oauth = new Oauth($this->conn);
    }

    public function createSession() 
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $gameId = $payload['gameType'] == "EASY2" ? 2 : 3;
        $errors = $this->numberGamesRequest->validateSession($payload);
        if(!empty($errors)) {
            return $this->oauth->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => $errors
            ], 422);
        }
        $operator = $this->oauth->queryOperators($payload['operatorId']);
        $referenceId = strtoupper($this->oauth->generateUniqueReferenceId(8));
        $signature = md5($operator['operator_id'].$payload['playerId']);
        $gameSession = $this->oauth->queryGameSession($payload['playerId']);
        $isBalanceWithdrawn = $gameSession['balance_withdrawn'] ?? null;

        $payloadToEncrypt = array(
            "operatorId" => $payload['operatorId'],
            "playerId" => $payload['playerId'],
            "gameType" => $payload['gameType'],
            "exitUrl" => $operator['exit_url']
        );

        switch (true) {
            case $operator['game_api_key'] !== $payload['secretKey']:
                $this->oauth->jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => 'Invalid operator credentials',
                ], 422);
                break;
            case !in_array($payload['gameType'], ['EASY2', 'EASY3']):
                $this->oauth->jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => 'Game is not included in the available games.',
                ], 422);
                break;
            default:
            $generatedToken = $this->jwt->generateToken($payloadToEncrypt);
            $transactions = $this->oauth->getTransactions($gameSession['player_id']);
            $previousBalance = $transactions[0]['current_bal'] ?? null;
            $this->oauth->insertOrUpdateSession($payload['playerId'], $payload['operatorId'], $generatedToken, $gameId);
            if (!$gameSession) {
                $this->oauth->insertTransactions(
                    $operator['operator_id'], 
                    $payload['playerId'], 
                    $payload['balance'], 
                    $referenceId, 
                    $signature,
                    $previousBalance,
                    $isBalanceWithdrawn
                );
            } else {
                $transactions = $this->oauth->getTransactions($gameSession['player_id']);
                $transactionStatusId = $transactions[0]['transaction_status_id'] ?? null;

                if (
                    date('Y-m-d H:i:s') > $gameSession['expiration_date'] || 
                    $gameSession['expiration_date'] === null || 
                    $gameSession['balance_withdrawn'] ||
                    $transactionStatusId != 1
                    ) {
                    $this->oauth->insertTransactions(
                        $operator['operator_id'], 
                        $payload['playerId'], 
                        $payload['balance'], 
                        $referenceId, 
                        $signature,
                        $previousBalance,
                        $isBalanceWithdrawn
                    );
                }
            }

            $this->oauth->jsonResponse([
                'code' => 200,
                'status' => 'success',
                'message' => 'Game session has started successfully',
                'token' => $generatedToken
            ], 200);
            break;
        }
    }

    public function startGame() 
    {
        $token = $_GET['key'];
        $dateTimeNow = date('Y-m-d H:i:s');
        $gameSession = $this->oauth->querySessionViaToken($token, $this->oauth);
        $queryOperators = $this->oauth->queryOperators($gameSession['operator_id']);
        $gameType = $gameSession['game_id'] === 2 ? 'EASY2' : 'EASY3';
        $gameFromParams = $_GET['gameType'];
        $transactions = $this->oauth->getTransactions($gameSession['player_id']);
        $latestBalance = $transactions[0]['current_bal'] ?? null;
    
        switch (true) {
            case $dateTimeNow > $gameSession['expiration_date']:
                $this->oauth->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'You are about to open a session that has expired. Please go back to the game selection',
                ], 422);
                break;
            case $gameType !== $gameFromParams:
                $this->oauth->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'Game type from the url is not the same from the game you selected',
                ], 422);
                break;
            default:
                $this->oauth->jsonResponse([
                    'code' => 200,
                    'status' => 'success',
                    'message' => "$gameType has start for player " . $gameSession['player_id'],
                    'sessionDetails' => [
                        'exitUrl' => $queryOperators['exit_url'],
                        'playerBalance' => $latestBalance,
                    ]
                ], 200);
                break;
        }
    }

    public function refreshSession()
    {
        $bearerToken = $this->jwt->getBearerToken();
        $isJwtValid = $this->jwt->verifyToken($bearerToken);
        $playerIdFromToken = $isJwtValid->playerId ?? null;
    
        $payload = json_decode(file_get_contents('php://input'), true);
        $errors = $this->numberGamesRequest->validatePlayerId($payload);
        if(!empty($errors)) {
            return $this->oauth->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => $errors
            ], 422);
        }
        $gameSession = $this->oauth->queryGameSession($payload['playerId']);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $operator = $this->oauth->queryOperators($gameSession['operator_id']);
        $expireAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
        if ($playerIdFromToken !== $payload['playerId']) {
            $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'The token is not belong to the player who is playing.',
            ], 400);    
        }
        $payloadToEncrypt = array(
            "operatorId" => $gameSession['operator_id'],
            "playerId" => $gameSession['player_id'],
            "gameType" => $gameSession['game_id'] === 2 ? 'EASY2': 'EASY3',
            "exitUrl" => $operator['exit_url']
        );
        $generatedToken = $this->jwt->generateToken($payloadToEncrypt);    
        $this->oauth->tokenExtend($generatedToken, $expireAt, $payload);

        $this->oauth->jsonResponse([
            'code' => 200,
            'status' => 'success',
            'message' => 'Game session successfully refreshed.',
            'token' => $generatedToken
        ], 200);
    }

    public function checkSession() 
    {
        $playerId = $_GET['playerId'] ?? null;
        if(!$playerId) {
            return $this->oauth->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => 'Player id does not exist'
            ], 422);
        }
        $playerToken = $this->oauth->queryGameSession($playerId);
        if (!$playerToken) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player is not found'
            ], 200);
        }
        $this->oauth->jsonResponse([
            'code' => 200,
            'status' => 'success',
            'message' => 'Player token fetched successfully.',
            'token' => $playerToken['token']
        ], 200);

    }
}
