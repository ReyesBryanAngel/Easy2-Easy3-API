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
        $query = "SELECT
                    game_session.event_id,
                    game_session.board_id,
                    game_session.player_id,
                    transactions.transaction_status_id,
                    transactions.transaction_id,
                    transactions.amount, 
                    transactions.current_bal, 
                    transactions.date_created,
                    transactions.previous_bal,
                    transactions.round_count,
                    bets.bet, 
                    bets.lucky_pick,
                    bets.win_type_id,
                    boards.draw_date
                FROM game_session
                LEFT JOIN
                    (
                    SELECT 
                        transaction_status_id, 
                        transaction_id, 
                        amount, 
                        current_bal, 
                        date_created, 
                        player_id,
                        round_count,
                        previous_bal 
                    FROM transactions
                ) AS transactions
                ON
                    game_session.player_id = transactions.player_id
                LEFT JOIN 
                    (SELECT bet, lucky_pick, win_type_id, time_of_bet,
                    round_count FROM bets) AS bets 
                ON 
                    transactions.date_created = bets.time_of_bet
                LEFT JOIN 
                    (SELECT draw_date, event_id, board_id FROM boards) AS boards 
                ON 
                    boards.board_id = game_session.board_id AND
                    boards.event_id = game_session.event_id
                WHERE transactions.player_id = :playerId
                ORDER BY transactions.date_created DESC
        ";

        $statement = $this->conn->prepare($query);
        $statement->bindValue(':playerId', $playerId);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);


        return $this->snakeCaseConverter($results);
    }
}