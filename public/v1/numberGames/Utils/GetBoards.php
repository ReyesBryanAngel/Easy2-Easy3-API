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
            board_status = 'open'
        ";
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':event_id', $eventId);
        $statement->bindParam(':game_id', $gameId);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        $formattedResults = [];
        foreach ($results as $result) {
            $formattedResult = [];
            foreach ($result as $key => $value) {
                $formattedResult[$this->snakeToCamelCase($key)] = $value;
            }
            $formattedResults[] = $formattedResult;
        }

        return $formattedResults;
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