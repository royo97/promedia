<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit();
}

// Obtener estadísticas para el dashboard
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE is_admin = FALSE");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as total_sales FROM accounts WHERE is_sold = TRUE");
$total_sales = $stmt->fetch()['total_sales'];

$stmt = $pdo->query("SELECT SUM(price) as total_revenue FROM order_items");
$total_revenue = $stmt->fetch()['total_revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container mt-5">
        <h2 class="mb-4">Panel de Administración</h2>
        
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Usuarios</h5>
                        <p class="card-text h4"><?= $total_users ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Productos</h5>
                        <p class="card-text h4"><?= $total_products ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Ventas</h5>
                        <p class="card-text h4"><?= $total_sales ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title">Ingresos</h5>
                        <p class="card-text h4">$<?= number_format($total_revenue, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <a href="products.php" class="btn btn-primary me-2">Gestionar Productos</a>
                        <a href="accounts.php" class="btn btn-success me-2">Gestionar Cuentas</a>
                        <a href="users.php" class="btn btn-info">Gestionar Usuarios</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>