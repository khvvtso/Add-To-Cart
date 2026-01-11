<?php
require_once 'php/db_project.php';
try {
    $result = $pdo_project->query('DESCRIBE user_products')->fetchAll();
    echo "Table Structure:\n";
    print_r($result);
    
    echo "\n\nKey Constraints:\n";
    $constraints = $pdo_project->query('SHOW INDEX FROM user_products')->fetchAll();
    print_r($constraints);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
