CREATE TABLE IF NOT EXISTS game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internal_wallet_id INT NOT NULL,
    FOREIGN KEY (internal_wallet_id) REFERENCES internal_wallets(id),
    bet_limit VARCHAR(36) NOT NULL,
    player_level VARCHAR(36) NULL,
    game_type VARCHAR(36),
    created_at DATETIME NOT NULL,
    expire_at DATETIME NOT NULL,
    ip_address VARCHAR(36),
    country_code VARCHAR(3),
    token VARCHAR(50)
);
