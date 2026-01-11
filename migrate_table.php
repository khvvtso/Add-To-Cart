<?php
require_once 'php/db_project.php';

try {
    // Check current table structure
    echo "Current table structure:\n";
    $result = $pdo_project->query('SHOW CREATE TABLE user_products')->fetch();
    echo $result['Create Table'] . "\n\n";
    
    // Drop the existing table and recreate with proper composite key
    echo "Fixing table structure...\n";
    
    // Save existing data
    $existingData = $pdo_project->query('SELECT * FROM user_products')->fetchAll();
    echo "Found " . count($existingData) . " existing cart items\n";
    
    // Drop old table
    $pdo_project->exec('DROP TABLE IF EXISTS user_products');
    echo "Old table dropped\n";
    
    // Create new table with composite primary key
    $pdo_project->exec('
        CREATE TABLE user_products (
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            PRIMARY KEY (user_id, product_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
        )
    ');
    echo "New table created with composite primary key (user_id, product_id)\n";
    
    // Restore data if any existed
    if (!empty($existingData)) {
        $stmt = $pdo_project->prepare('INSERT INTO user_products (user_id, product_id, quantity) VALUES (?, ?, ?)');
        foreach ($existingData as $row) {
            $stmt->execute([(int)$row['user_id'], (int)$row['product_id'], (int)$row['quantity']]);
        }
        echo "Restored " . count($existingData) . " cart items\n";
    }
    
    echo "\nVerifying new structure:\n";
    $result = $pdo_project->query('SHOW CREATE TABLE user_products')->fetch();
    echo $result['Create Table'] . "\n";
    
    echo "\n✓ Table migration completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    die(1);
}
?>
