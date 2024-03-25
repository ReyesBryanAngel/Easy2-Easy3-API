CREATE TABLE IF NOT EXISTS easy2_bets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    started_game_id INT NOT NULL,
    FOREIGN KEY (started_game_id) REFERENCES started_games(id),
    round int NOT NULL,
    bet_amount INT NOT NULL,
    selected_numbers VARCHAR(5) NOT NULL,
    rambolito boolean NULL,
    advance_draws boolean NULL,
    consecutive_draws int NULL,
    lucky_pick boolean NULL
);
