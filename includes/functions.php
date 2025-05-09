<?php
require_once __DIR__ . '/../config/database.php';

// Función para autenticar usuarios
function authenticate($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

// Función para registrar usuarios
function registerUser($username, $email, $password) {
    global $pdo;
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $email, $hashedPassword]);
}

// Función para obtener todos los productos
function getProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM products");
    return $stmt->fetchAll();
}

// Otras funciones necesarias...

// Agrega esta función al final de functions.php
function get_user_balance($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return $result ? $result['balance'] : 0;
    } catch (PDOException $e) {
        error_log("Error al obtener saldo: " . $e->getMessage());
        return 0;
    }
}
?>