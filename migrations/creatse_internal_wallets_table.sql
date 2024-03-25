CREATE TABLE IF NOT EXISTS internal_wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operator_id INT NOT NULL,
    FOREIGN KEY (operator_id) REFERENCES operators(id),
    player_name VARCHAR(36) NOT NULL,
    balance INT NOT NULL,
    type_of_transaction VARCHAR(5) NULL,
    created_at DATETIME NOT NULL
);
