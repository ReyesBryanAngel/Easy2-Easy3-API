<?php

class GameSessionModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function checkOperatorName($operatorName)
    {
        $query = "SELECT id FROM operators WHERE operator_name = :value";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $operatorName);
        $statement->execute();
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        
        return $result ?? null;
    }

    public function checkOperatorPass($id)
    {
        $query = "SELECT api_key FROM operators WHERE id = :value";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $id);
        $statement->execute();
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['api_key'] : null;
    }

    public function checkSessionExpiration($internalWalletId) 
    {
        $query = "SELECT internal_wallet_id, expire_at FROM game_sessions WHERE internal_wallet_id = :value ORDER BY expire_at DESC LIMIT 1";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':value', $internalWalletId);
        $statement->execute();
        
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result ?? null;
    }

    public function limitGameType($gameType)
    {
        $errors = [];
        $allowedGameTypes = [
            'startGame',
            'easy2',
            'easy3'
        ];
        if (!in_array($gameType, $allowedGameTypes)) {
            $errors[$gameType] =  'The ' . $gameType . ' is not included in game list.';
        }

        return $errors;
    }

    public function insertGameSession($internalWalletId, $betLimit, $hash, $gameType)
    {
        $expireAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $createdAt = date('Y-m-d H:i:s');
        $sql = "INSERT INTO game_sessions (internal_wallet_id, bet_limit, game_type, created_at, expire_at, token) 
                VALUES (:internal_wallet_id, :bet_limit, :game_type, :created_at, :expire_at, :token)";
        $token = md5($hash . $createdAt);

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':internal_wallet_id', $internalWalletId);
        $stmt->bindParam(':bet_limit', $betLimit);
        $stmt->bindParam(':game_type', $gameType);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->bindParam(':expire_at', $expireAt);
        $stmt->bindParam(':token', $token);

        $stmt->execute();

        return $token;
    }
}

