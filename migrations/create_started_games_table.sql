CREATE TABLE IF NOT EXISTS started_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_session_id INT NOT NULL,
    FOREIGN KEY (game_session_id) REFERENCES game_sessions(id),
    date_open DATETIME NOT NULL,
    date_close DATETIME NULL
);
