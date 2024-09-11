<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class GetBoards {
    use numberGamesTrait;
    private $conn;

    public function __construct(PDO $conn) {
        $this->setConnection($conn);
    }

    public function mergePlayerCountToBoardInfo($boardsOfCurrentEvent, $playerCountPerBoard)
    {
        $resultArray = [];
        $playerCounts = [];

        foreach ($playerCountPerBoard as $playerCount) {
            $playerCounts[$playerCount['boardId']] = $playerCount;
        }

        foreach ($boardsOfCurrentEvent as $board) {
            if (isset($playerCounts[$board['boardId']])) {
                $resultArray[] = array_merge($board, $playerCounts[$board['boardId']]);
            } else {
                $resultArray[] = $board;
            }
        }

        return $resultArray;
    }

    public function queryOpenBoards($eventId, $gameId)
    {
        $query = "SELECT * FROM boards WHERE event_id = :event_id AND 
            game_id = :game_id AND 
            board_status = 'open'";
        $boardStmt = $this->conn->prepare($query);
        $boardStmt->bindParam(':event_id', $eventId);
        $boardStmt->bindParam(':game_id', $gameId);
        $boardStmt->execute();
        $boardResults = $boardStmt->fetchAll(PDO::FETCH_ASSOC);
    
        $filteredBoards = [];
    
        foreach ($boardResults as $result) {
            $boardId = $result['board_id'];
            $roundEventId = $result['event_id'];
            $roundGameId = $result['game_id'];
            
            $queryRounds = "SELECT event_id, board_id, round_count FROM rounds WHERE event_id = :eventId AND
                board_id = :boardId AND
                game_id = :gameId";
            $roundStmt = $this->conn->prepare($queryRounds);
            $roundStmt->bindParam(':eventId', $roundEventId);
            $roundStmt->bindParam(':boardId', $boardId);
            $roundStmt->bindParam(':gameId', $roundGameId);
            $roundStmt->execute();
            $roundResults = $roundStmt->fetch(PDO::FETCH_ASSOC);
    
            if ($roundResults) {
                $filteredBoards[] = $result;
            }
        }
    
        return $this->snakeCaseConverter($filteredBoards);
    }
    

    public function countPlayerWhoJoinedTheBoard($boardsOfCurrentEvents, $playerId, $isLeftTheBoard)
    {
        $updatedBoardsData = [];
        $currentDateTime = date('Y-m-d H:i:s');
        
        foreach ($boardsOfCurrentEvents as $boardsOfCurrentEvent) {
            $eventId = $boardsOfCurrentEvent['eventId'] ?? null;
            $boardId = $boardsOfCurrentEvent['boardId'] ?? null;
            
            $queryGameSession = "SELECT event_id, board_id, player_id FROM game_session WHERE 
                expiration_date > :currentDateTime AND
                event_id = :eventId AND
                board_id = :boardId AND
                board_left IS NULL";
            
            $stmtGameSession = $this->conn->prepare($queryGameSession);
            $stmtGameSession->bindParam(':eventId', $eventId);
            $stmtGameSession->bindParam(':boardId', $boardId);
            $stmtGameSession->bindParam(':currentDateTime', $currentDateTime);
            $stmtGameSession->execute();

            $gameSessionResults = $stmtGameSession->fetchAll(PDO::FETCH_ASSOC);

            $uniquePlayers = [];
            $duplicates = [];

            foreach ($gameSessionResults as $key => $gameSessionResult) {
                $player_id = $gameSessionResult['player_id'];

                if (isset($uniquePlayers[$player_id])) {
                    $duplicates[] = $gameSessionResult;
                    unset($gameSessionResults[$key]);
                } else {
                    $uniquePlayers[$player_id] = $gameSessionResult;
                }
            }

            foreach ($gameSessionResults as $gameSessionResult) {
                $gameSessionBoardId = $gameSessionResult['board_id'] ?? null;
                $numberOfPlayers = count($gameSessionResults);

                if (isset($playerId) && $isLeftTheBoard) {
                    $sql = "UPDATE game_session SET board_left = true WHERE player_id = :playerId";
                    $leftBoardStmt = $this->conn->prepare($sql);
                    $leftBoardStmt->bindParam(':playerId', $playerId);
                    $leftBoardStmt->execute();
                }

                $updatedBoardsData[] = [
                    'boardId' => $gameSessionBoardId,
                    'numberOfPlayers' => $numberOfPlayers
                ];
            }
        }

        return $updatedBoardsData;
    }

    public function queryEvent($eventId)
    {
        $query = "SELECT * FROM events WHERE event_id = :event_id AND event_status = 'open'";
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':event_id', $eventId);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return $this->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Event is not yet opened.',
            ], 400); 
        }

        return $result;
    }

}