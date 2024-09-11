<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class ChooseBoard {
    use numberGamesTrait;
    private $conn;

    public function __construct(PDO $conn) {
        $this->setConnection($conn);
    }

    public function queryBoardViaEvent($boardId, $eventId)
    {
        $query = "SELECT * FROM boards WHERE board_id = :boardId AND event_id = :eventId AND board_status = 'open'";
        $statement = $this->conn->prepare($query);
        $statement->bindParam(':boardId', $boardId);
        $statement->bindParam(':eventId', $eventId);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$result || $result['board_status'] == 'close') {
            return $this->jsonResponse([
                'code' => 400,
                'status' => 'failed',
                'message' => 'Board is either closed or does not exist.',
            ], 400); 
        }

        return $result ?? false;
    }

    public function indicateBoardToPlay($eventId, $boardId, $playerId)
    {
        $sql = "UPDATE game_session SET event_id = :eventId, board_id = :boardId, board_left = null WHERE player_id = :playerId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':eventId', $eventId);
        $stmt->bindParam(':boardId', $boardId);
        $stmt->bindParam(':playerId', $playerId);
        $stmt->execute();
    }

    public function updateTransaction($playerId, $eventId, $boardId, $roundCount)
    {
        $sql = "UPDATE transactions
                SET 
                    event_id = :eventId, 
                    board_id = :boardId,
                    round_count = :roundCount
                WHERE transaction_id = (
                    SELECT transaction_id
                    FROM (
                        SELECT transaction_id
                        FROM transactions
                        WHERE player_id = :playerId
                        ORDER BY date_created DESC
                        LIMIT 1
                    ) AS subquery
                );
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':eventId', $eventId);
        $stmt->bindParam(':boardId', $boardId);
        $stmt->bindParam(':playerId', $playerId);
        $stmt->bindParam(':roundCount', $roundCount);
        $stmt->execute();
    }
}
