<?php

class GameStartModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function queryGameSessions($token)
    {
        $query = "SELECT id, expire_at, game_type FROM game_sessions WHERE token = :value ORDER BY created_at DESC LIMIT 1";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $token);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?? null;
    }

    public function checkStartedGame($startedGameId)
    {
        $query = "SELECT game_session_id, date_close FROM started_games WHERE game_session_id = :value";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $startedGameId);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?? null;
    }

    public function checkGameSessionId($gameSessionId)
    {
        $query = "SELECT id, game_type, expire_at FROM game_sessions WHERE id = :value";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $gameSessionId);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);
    
        return $result ?? null;
    }
    

    public function queryGameStarts($id)
    {
        $query = "SELECT game_session_id, date_close FROM started_games WHERE game_session_id = :value";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $id);
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result ?? null;
    }

    public function startGame($gameSessionId, $dateOpen)
    {
        $sql = "INSERT INTO started_games (game_session_id, date_open) 
                VALUES (:game_session_id, :date_open)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':game_session_id', $gameSessionId);
        $stmt->bindParam(':date_open', $dateOpen);

        $stmt->execute();
    }
}