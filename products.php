<?php
require_once 'config.php';

function getAllProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM products");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "Lỗi: " . $e->getMessage();
        return [];
    }
}

function getProductById($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        echo "Lỗi: " . $e->getMessage();
        return null;
    }
}
?> 