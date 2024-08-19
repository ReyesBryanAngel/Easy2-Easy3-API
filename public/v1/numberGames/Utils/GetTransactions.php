<?php

namespace v1\numberGames\Utils;

use v1\numberGames\Traits\numberGamesTrait;
use PDO;

class GetTransactions {
    use numberGamesTrait;
    private $conn;

    public function __construct(PDO $conn) {
        $this->setConnection($conn);
    }

    public function getTransactions($playerId)
    {
        $query = "SELECT transactions.transaction_status_id,
                 transactions.transaction_id,
                 transactions.amount, 
                 transactions.current_bal, 
                 transactions.date_created,
                 transactions.player_id,
                 transactions.previous_bal,
                 transactions.event_id,
                 transactions.board_id,
                 transactions.round_count,
                 bets.bet, 
                 bets.lucky_pick,
                 bets.win_type_id,
                 boards.draw_date
          FROM transactions
          LEFT JOIN 
              (SELECT bet, lucky_pick, win_type_id, time_of_bet,
              round_count FROM bets) AS bets 
          ON transactions.date_created = bets.time_of_bet
          LEFT JOIN 
            (SELECT draw_date, event_id, board_id FROM boards) AS
            boards ON 
            boards.board_id = transactions.board_id AND
            boards.event_id = transactions.event_id
          WHERE transactions.player_id = :playerId
          ORDER BY transactions.date_created DESC";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(':playerId', $playerId);
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
}