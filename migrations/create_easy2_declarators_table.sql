CREATE TABLE IF NOT EXISTS easy2_declarators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    easy2_id INT NOT NULL,
    FOREIGN KEY (easy2_id) REFERENCES game_sessions(id),
    game_result VARCHAR(36) NOT NULL,
    round INT NOT NULL
);