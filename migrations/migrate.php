<?php

require 'connect.php';

if ($argc < 2) {
    echo "Usage: php migrate.php <migration_file.sql>\n";
    exit(1);
}

$migrationFile = $argv[1];

if (!file_exists($migrationFile)) {
    echo "Migration file not found: $migrationFile\n";
    exit(1);
}

$sql = file_get_contents($migrationFile);

try {
    if (!$pdo) {
        throw new Exception("Failed to connect to database.");
    }
    
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    echo "Migration from $migrationFile executed successfully.\n";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error executing migration from $migrationFile: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
