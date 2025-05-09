<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// Obtener información del usuario
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Obtener productos disponibles
$products = getProducts();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda de Streaming</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Saldo Disponible</h5>
                        <p class="card-text h3">$<?= number_format($user['balance'], 2) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="mb-4">Servicios Disponibles</h2>
        
        <div class="row">
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="/assets/images/<?= strtolower($product['name']) ?>.jpg" class="card-img-top" alt="<?= $product['name'] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $product['name'] ?></h5>
                        <p class="card-text"><?= $product['description'] ?></p>
                        <p class="card-text"><strong>Precio: $<?= number_format($product['price'], 2) ?></strong></p>
                        <p class="card-text">
                            Perfiles disponibles: <?= $product['profiles_available'] ?><br>
                            <small class="text-muted">(<?= ceil($product['profiles_available'] / $product['profiles_count']) ?> cuentas completas)</small>
                        </p>
                    </div>
                                    <div class="card-footer">
                    <form action="cart.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="action" value="add_to_cart">
                        <button type="submit" name="add_to_cart" class="btn btn-primary w-100">
                            <i class="bi bi-cart-plus"></i> Agregar al Carrito
                        </button>
                    </form>
                </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>