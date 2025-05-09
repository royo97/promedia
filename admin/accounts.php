<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit();
}

// Cambiar todas las referencias de 'stock' a 'profiles_available'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $product_id = (int)$_POST['product_id'];
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insertar la cuenta
        $stmt = $pdo->prepare("INSERT INTO accounts (product_id, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$product_id, $email, $password]);
        $account_id = $pdo->lastInsertId();
        
        // 2. Obtener información del producto para crear los perfiles
        $stmt = $pdo->prepare("SELECT name, profiles_count, has_pin FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // 3. Generar los perfiles automáticamente
            generateProfilesForAccount($account_id, $product['name'], $product['has_pin'], $product['profiles_count']);
            
            // 4. Actualizar la disponibilidad de perfiles (no stock)
            $stmt = $pdo->prepare("UPDATE products SET profiles_available = profiles_available + ? WHERE id = ?");
            $stmt->execute([$product['profiles_count'], $product_id]);
        }
        
        $pdo->commit();
        $success = "Cuenta agregada correctamente con ".$product['profiles_count']." perfiles";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error al agregar cuenta: " . $e->getMessage();
    }
}

// Obtener productos para el select
$products = $pdo->query("SELECT * FROM products")->fetchAll();

// Obtener cuentas
$accounts = $pdo->query("
    SELECT a.*, p.name as product_name 
    FROM accounts a
    JOIN products p ON a.product_id = p.id
    ORDER BY a.is_sold, a.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Cuentas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container mt-5">
        <h2 class="mb-4">Gestionar Cuentas</h2>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Agregar Nueva Cuenta</h5>
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Servicio</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Seleccionar servicio</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= $product['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="text" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="add_account" class="btn btn-primary">Agregar Cuenta</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Lista de Cuentas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Servicio</th>
                                <th>Correo</th>
                                <th>Contraseña</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><?= $account['id'] ?></td>
                                <td><?= $account['product_name'] ?></td>
                                <td><?= $account['email'] ?></td>
                                <td><?= $account['password'] ?></td>
                                <td>
                                    <?php if ($account['is_sold']): ?>
                                        <span class="badge bg-danger">Vendida</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Disponible</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($account['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>