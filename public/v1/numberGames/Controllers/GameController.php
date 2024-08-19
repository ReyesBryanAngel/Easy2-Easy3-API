<?php
namespace v1\NumberGames\Controllers;

use v1\numberGames\Requests\numberGamesRequest;
use v1\numberGames\Helpers\JsonWebToken;
use v1\numberGames\Utils\Bet;
use v1\NumberGames\Utils\WinningHistory;
use v1\NumberGames\Utils\PlayerCurrentBets;
use v1\NumberGames\Utils\GetTransactions;
use v1\NumberGames\Utils\DepositBalance;
use v1\NumberGames\Utils\ChooseBoard;
use v1\numberGames\Utils\GetBoards;
use v1\numberGames\Utils\Oauth;
use v1\Database\Database;
use v1\numberGames\Utils\WithdrawBalance;
class GameController {
    private $numberGamesRequest;
    private $jwt;
    private $oauth;
    private $getBoardsModel;
    private $chooseBoardModel;
    private $winningHistoryModel;
    private $getTransactionsModel;
    private $playerCurrentBetsModel;
    private $depositBalanceModel;
    private $withdrawBalanceModel;
    private $betModel;
    private $db;
    private $conn;
    public function __construct() {
        $this->numberGamesRequest = new numberGamesRequest();
        $this->jwt = new JsonWebToken();
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        
        $this->oauth = new Oauth($this->conn);
        $this->betModel = new Bet($this->conn);
        $this->getBoardsModel = new GetBoards($this->conn);
        $this->chooseBoardModel = new chooseBoard($this->conn);
        $this->winningHistoryModel = new winningHistory($this->conn);
        $this->getTransactionsModel = new GetTransactions($this->conn);
        $this->playerCurrentBetsModel = new PlayerCurrentBets($this->conn);
        $this->depositBalanceModel = new DepositBalance($this->conn);
        $this->withdrawBalanceModel = new WithdrawBalance($this->conn);
    }

    public function getEvents()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $playerId = $payload['playerId'] ?? null;
        $gameSession = $this->oauth->queryGameSession($playerId);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $errors = $this->numberGamesRequest->validatePlayerIdAndGameId($payload);
        if(!empty($errors)) {
            return $this->oauth->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => $errors
            ], 422);
        }
        $availableEvents = $this->oauth->queryEvents($payload, $this->oauth);
        if ($playerIdFromToken !== $playerId) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'The token is not belong to the player who is playing.',
            ], 400);    
        }
        $this->oauth->jsonResponse([
            'status' => 'success',
            'code' => 200,
            'message' => "Retrieved the requested records.",
            'data' => $availableEvents
        ], 200);
    }

    public function getBoards()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $payload = json_decode(file_get_contents('php://input'), true);
        if(!empty($errors)) {
            return $this->getBoardsModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => $errors
            ], 422);
        }
        $gameSession = $this->getBoardsModel->queryGameSession($payload['playerId']);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $isLeftTheBoard = $payload['isLeftTheBoard'] ?? null;
        $payloadToValidate = [
            'eventId' => $payload['eventId'],
            'gameId' => $payload['gameId'],
            'playerId' => $payload['playerId']
        ];
        $errors = $this->numberGamesRequest->validateGetBoards($payloadToValidate);
        $boardsOfCurrentEvent = $this->getBoardsModel->queryOpenBoards($payload['eventId'], $payload['gameId']);
        // $this->getBoardsModel->dd($boardsOfCurrentEvent);
        $this->getBoardsModel->queryEvent($payload['eventId']);
        $playerCountPerBoard = $this->getBoardsModel->countPlayerWhoJoinedTheBoard($boardsOfCurrentEvent, $payload['playerId'], $isLeftTheBoard);
        $boardWithPlayerCounts = $this->getBoardsModel->mergePlayerCountToBoardInfo($boardsOfCurrentEvent, $playerCountPerBoard);
        $boardListToReturn = !empty($boardWithPlayerCounts) ? $boardWithPlayerCounts : $boardsOfCurrentEvent;
        if ($playerIdFromToken !== $payload['playerId']) {
            return $this->getBoardsModel->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'The token is not belong to the player who is playing.',
            ], 400);
        }  
        $this->getBoardsModel->jsonResponse([
            'status' => 'success',
            'code' => 200,
            'message' => "Boards retrieved successfully.",
            'data' => $boardListToReturn
        ], 200);
    }

    public function chooseBoard()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $payload = json_decode(file_get_contents('php://input'), true);
    
        $errors = $this->numberGamesRequest->validateChooseBoard($payload);
        if(!empty($errors)) {
            return $this->chooseBoardModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => $errors
            ], 422);
        }
    
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $board = $this->chooseBoardModel->queryBoardViaEvent($payload['boardId'], $payload['eventId']);
        $gameSession = $this->chooseBoardModel->queryGameSession($payload['playerId']);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $gameType = $gameSession['game_id'] === 2 ? 'EASY2' : 'EASY3';
        $currentRound = $this->chooseBoardModel->queryCurrentRound($board['game_id'], $payload['eventId'], $payload['boardId']);
    
        switch (true) {
            case $playerIdFromToken !== $payload['playerId']:
                $this->chooseBoardModel->jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => 'The token is not belong to the player who is playing.',
                ], 400);    
                break;
            case ($board['game_id'] === 2 && $gameType !== 'EASY2') || ($board['game_id'] === 3 && $gameType !== 'EASY3'):
                $this->chooseBoardModel->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => "This board id does not exist in $gameType"
                ], 422);
                break;
            default:
                $this->chooseBoardModel->indicateBoardToPlay($payload['eventId'], $payload['boardId'], $payload['playerId']);
                $this->chooseBoardModel->updateTransaction($payload['playerId'], $payload['eventId'], $payload['boardId'], $currentRound['round_count']);
                $this->chooseBoardModel->jsonResponse([
                    'code' => 200,
                    'status' => 'success',
                    'message' => "Board " . $payload['boardId'] . " has been successfully chosen."
                ], 200);
                break;
        }
    }

    public function currentBoard()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $playerId = $_GET['playerId'] ?? null;
        if(!$playerId) {
            return $this->oauth->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => 'Player id does not exist'
            ], 422);
        }
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $gameSession = $this->oauth->queryGameSession($playerId);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $openedBoard = $this->oauth->queryOpenBoards($gameSession['game_id'], $gameSession['board_id'], $gameSession['event_id']);
        switch (true) {
            case $playerIdFromToken !== $playerId:
                $this->oauth->jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => 'The token is not belong to the player who is playing.',
                ], 400);    
                break;
            case !$openedBoard:
                $this->oauth->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'No opened board yet.',
                ], 200);
                break;
        }
        $camelCaseOpenedBoard = [];
        foreach ($openedBoard as $key => $value) {
            $camelCaseKey = $this->oauth->snakeToCamelCase($key);
            $camelCaseOpenedBoard[$camelCaseKey] = $value;
        }
        $this->oauth->jsonResponse([
            'code' => 200,
            'status' => 'success',
            'message' => 'Successfully fetched opened board.',
            'data' => $camelCaseOpenedBoard
        ], 200);
    }

    public function boardValidator()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $playerId = $_GET['playerId'] ?? null;
        if(!$playerId) {
            return $this->winningHistoryModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => 'Player id does not exist'
            ], 422);
        }
        $gameSession = $this->oauth->queryGameSession($playerId);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $openedBoard = $this->oauth->queryOpenBoards($gameSession['game_id'], $gameSession['board_id'], $gameSession['event_id']);
        switch (true) {
            case $playerIdFromToken !== $playerId:
                $this->oauth->jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => 'The token is not belong to the player who is playing.',
                ], 400);    
                break;
            case !empty($result):
                $this->oauth->jsonResponse([
                    'code' => 200,
                    'status' => 'success',
                    'message' => $openedBoard['board_title'] . " is currently OPEN.",
                    'boardStatus' => $openedBoard['board_status']
                ], 200);
            default:
                $this->oauth->jsonResponse([
                    'code' => 200,
                    'status' => 'success',
                    'message' => "OPEN board unavailable.",
                    'boardStatus' => $openedBoard['board_status']
                ], 200);
                break;
        }
    }

    public function getRound()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $playerId = $_GET['playerId'] ?? null;
        if(!$playerId) {
            return $this->oauth->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => 'Player id does not exist'
            ], 422);
        }
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $gameSession = $this->oauth->queryGameSession($playerId);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $currentRound = $this->oauth->queryCurrentRound($gameSession['game_id'], $gameSession['event_id'], $gameSession['board_id']);
        if ($playerIdFromToken !== $playerId) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'The token is not belong to the player who is playing.',
            ], 400);    
        }
        $this->oauth->jsonResponse([
            'code' => 200,
            'status' => 'success',
            'message' => "Round has been fetched successfully",
            'round' => $currentRound['round_count']
        ], 200);

    }

    public function getTransactions()
    {
        $bearerToken = $this->jwt->getBearerToken();
        $isJwtValid = $this->jwt->verifyToken($bearerToken);
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $playerId = $_GET['playerId'] ?? null;
        if(!$playerId) {
            return $this->getTransactionsModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => 'Player id does not exist'
            ], 422);
        }
        $transactionHistory = $this->getTransactionsModel->getTransactions($playerId);

        if ($playerIdFromToken !== $playerId) {
            return $this->getTransactionsModel->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'The token is not belong to the player who is playing.',
            ], 400);    
        }

        $this->getTransactionsModel->jsonResponse([
            'code' => 200,
            'status' => 'success',
            'message' => "Successfully get transaction history.",
            'data' => $transactionHistory 
        ], 200);
    }

    public function bet()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $payload = json_decode(file_get_contents('php://input'), true);
        $errors = $this->numberGamesRequest->validateBets($payload);
        if(!empty($errors)) {
            return $this->betModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => $errors
            ], 422);
        }
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $luckyPick = $payload['luckyPick'] ? 1 : 0;
        $referenceId = strtoupper($this->betModel->generateUniqueReferenceId(8));
        $gameSession = $this->betModel->queryGameSession($payload['playerId']);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $operatorOfPlayer = $this->betModel->tracePlayerOperator($payload['playerId']);
        $signature = md5($operatorOfPlayer['operator_id'].$payload['playerId']);
        $gameType = $gameSession['game_id'] === 2 ? 'EASY2' : 'EASY3';
        $currentRound = $this->betModel->queryCurrentRound($gameSession['game_id'], $gameSession['event_id'], $gameSession['board_id']);
        $luckyPickGenerator = $gameType === 'EASY2' ? $this->betModel->luckPickGenerator2() : $this->betModel->luckPickGenerator3();
        $selectedNumbers = $luckyPick == true ? $luckyPickGenerator : $payload['selectedNumbers'] ?? null;
        $transactions = $this->betModel->getTransactions($payload['playerId']);
        $previousBalance = $transactions[0]['current_bal'] ?? null;
        $currentBalance = $previousBalance - $payload["betAmount"];
        $board = $this->betModel->queryOpenBoards($gameSession['game_id'], $gameSession['board_id'], $gameSession['event_id']);
       
        switch (true) {
            case $playerIdFromToken !== $payload['playerId']:
                $this->betModel->jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => 'The token is not belong to the player who is playing.',
                ], 400);    
                break;
            case $previousBalance < $payload['betAmount']:
                $this->betModel->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'Insufficient balance.'
                ], 422);
                break;
            case $payload['betAmount'] < $operatorOfPlayer['bet_minlimit']:
                jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => "bet amount did not meet the minimum bet limit of " . $operatorOfPlayer['company_name']
                ], 400);    
                break;
            case $board['draw_date'] <= date('Y-m-d H:i:s'):
                $this->betModel->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => "Betting time for round " . $currentRound['round_count'] . " of board " . $board['board_title'] .  "has ended."
                ], 422);
                break;
            default:
                $dateTimeNow = date('Y-m-d H:i:s');
                $this->betModel->insertTransaction(
                    $operatorOfPlayer['operator_id'], 
                    $payload["betAmount"], 
                    $payload['playerId'],
                    $gameSession['event_id'],
                    $gameSession['board_id'],
                    $currentRound['round_count'],
                    $previousBalance, 
                    $currentBalance,
                    $referenceId,
                    $signature,
                    3
                );
                $betId = $this->betModel->insertBets(
                    $payload['winTypeId'],
                    $gameSession['event_id'],
                    $gameSession['board_id'],
                    $currentRound['round_id'],
                    $currentRound['round_count'],
                    $selectedNumbers, 
                    $payload["betAmount"], 
                    $operatorOfPlayer['operator_id'],
                    $payload['playerId'],
                    $luckyPick,
                    $referenceId,
                    $dateTimeNow,
                    $gameType
                );
                $response = [
                    'code' => 200,
                    'status' => 'success',
                    'message' => "Bet is Successful for round " . $currentRound['round_count'] . " of board " . $board['board_title'],
                    'playerBalance' => $currentBalance,
                    'roundCount' => $currentRound['round_count'],
                    'betId' => $betId
                ];   
                if ($luckyPick) {
                    $response['selectedNumbers'] = $selectedNumbers;
                }    
                $this->betModel->jsonResponse($response, 200);
                break;
        }
    }

    public function playerCurrentBets()
    {
        $bearerToken = $this->jwt->getBearerToken();
        $isJwtValid = $this->jwt->verifyToken($bearerToken);
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $playerId = $_GET['playerId'] ?? null;
        if(!$playerId) {
            return $this->playerCurrentBetsModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => 'Player id does not exist'
            ], 422);
        }
        $gameSession = $this->playerCurrentBetsModel->queryGameSession($playerId);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $currentRound = $this->playerCurrentBetsModel->queryCurrentRound($gameSession['game_id'], $gameSession['event_id'], $gameSession['board_id']);
        $playerCurrentBets = $this->playerCurrentBetsModel->betsInCurrentRound(
            $playerId, 
            $gameSession['game_id'], 
            $gameSession['event_id'], 
            $gameSession['board_id'], 
            $currentRound['round_count']
        );

        if ($playerIdFromToken !== $playerId) {
            return $this->playerCurrentBetsModel->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'The token is not belong to the player who is playing.',
            ], 400);    
        }
    
        $this->playerCurrentBetsModel->jsonResponse([
            'code' => 200,
            'status' => 'success',
            'message' => 'Successfully get the current bets of the player.',
            'currentBets' => $playerCurrentBets
        ], 200);
    }

    public function playerDetails()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $playerId = $_GET['playerId'] ?? null;
        $operatorOfPlayer = $this->oauth->tracePlayerOperator($playerId);
        $gameSession = $this->oauth->queryGameSession($playerId);
        if (!$gameSession) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $transactions = $this->oauth->getTransactions($playerId);
        $currentBalance = $transactions[0]['current_bal'] ?? null;
        if ($playerIdFromToken !== $playerId) {
            return $this->oauth->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'The token is not belong to the player who is playing.',
            ], 400);    

        }
        $this->oauth->jsonResponse([
            'code' => 200,
            'status' => 'success',
            'message' => "Successfully get the player details.",
            'data' => [
                'balance' => $currentBalance,
                'sessionExpiration' => $gameSession['expiration_date'],
                'minBetLimit' => $operatorOfPlayer['bet_minlimit'],
                'maxBetLimit' => $operatorOfPlayer['bet_maxlimit'],
                'playerId' => $playerId
            ]
        ], 200);
    }

    public function winningHistory()
    {
        $isJwtValid = $this->jwt->verifyToken($this->jwt->getBearerToken());
        $playerIdFromToken = $isJwtValid->playerId ?? null;
        $playerId = $_GET['playerId'] ?? null;
        if(!$playerId) {
            return $this->winningHistoryModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => 'Player id does not exist'
            ], 422);
        }
        $gameSession = $this->winningHistoryModel->queryGameSession($playerId);
        if (!$gameSession) {
            return $this->winningHistoryModel->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Player id does not exist.',
            ], 400); 
        }
        $wonBets = $this->winningHistoryModel->wonBets($gameSession['game_id'], $playerId);
        $dateOfWonBets = $this->winningHistoryModel->dateOfWonBets($wonBets);
        $mergeDateAndWonBets = $this->winningHistoryModel->mergeDateAndWonBets($wonBets, $dateOfWonBets);
        switch (true) {
            case $playerIdFromToken !== $playerId:
                $this->winningHistoryModel->jsonResponse([
                    'code' => 400,
                    'status' => 'failed',
                    'message' => 'The token is not belong to the player who is playing.',
                ], 400);    
                break;
            default:
                $this->winningHistoryModel->jsonResponse([
                    'code' => 200,
                    'status' => 'message',
                    'message' => 'Winning history retrieved successfully.',
                    'data' => $mergeDateAndWonBets
                ], 200);
                break;
        }
    }

    public function depositBalance()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $updatedAt = date('Y-m-d H:i:s');
        $playerId = $payload['playerId'] ?? null;
        $balance = $payload['balance'] ?? null;
        $walletApiKeyPayload = $payload['walletApiKey'] ?? null;
        $referenceId = strtoupper($this->depositBalanceModel->generateUniqueReferenceId(8));
        $errors = $this->numberGamesRequest->validateDeposit($payload);
        $this->depositBalanceModel->insertTransactions($playerId, $balance, $walletApiKeyPayload, $updatedAt, $referenceId, $errors);
    }

    public function withdrawBalance()
    {
        $payload = json_decode(file_get_contents('php://input'), true);
        $errors = $this->numberGamesRequest->validateWithdraw($payload);
        if(!empty($errors)) {
            return $this->withdrawBalanceModel->jsonResponse([
                'code' => 422,
                'status' => 'failed',
                'message' => $errors
            ], 422);
        }

        $gameSession = $this->withdrawBalanceModel->queryGameSession($payload['playerId']);
        $operatorOfPLayerWithdrawing = $this->withdrawBalanceModel->getOperatorOfPLayerWithdrawing($gameSession['operator_id']);
        $walletApiKeyOfPLayerWithdrawing = $operatorOfPLayerWithdrawing['wallet_api_key'] ?? null;
        $signature = md5($gameSession['operator_id'].$payload['playerId']);
        $referenceId = strtoupper($this->withdrawBalanceModel->generateUniqueReferenceId(8));
        $transactions = $this->withdrawBalanceModel->getTransactions($payload['playerId']);
        $latestBalance = $transactions[0]['current_bal'];

        switch (true) {
            case $payload['walletApiKey'] !== $walletApiKeyOfPLayerWithdrawing:
                $this->withdrawBalanceModel->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'You have entered an invalid wallet api key.',
                ], 422);
                break;
            case $latestBalance == 0:
                $this->withdrawBalanceModel->jsonResponse([
                    'code' => 422,
                    'status' => 'failed',
                    'message' => 'Nothing to withdraw. Balance in game is 0 already.',
                ], 422);
                break;
            default:
                $this->withdrawBalanceModel->insertTransactions ( 
                    $gameSession['operator_id'], 
                    $payload['playerId'], 
                    $latestBalance,
                    $referenceId,
                    $signature,
                );
                $this->withdrawBalanceModel->endSession($payload['playerId']);
            
                $this->withdrawBalanceModel->jsonResponse([
                    'code' => 200,
                    'status' => 'message',
                    'message' => 'Balance has been withdrawn successfully.',
                    'balance' => $latestBalance
                ], 200);       
        }
    }
}
