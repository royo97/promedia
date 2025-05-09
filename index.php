<?php
session_start();

// Verifica si los archivos existen antes de incluirlos
$database_file = __DIR__ . '/config/database.php';
$functions_file = __DIR__ . '/includes/functions.php';

if (!file_exists($database_file)) {
    die("Error: No se encontró database.php");
}

if (!file_exists($functions_file)) {
    die("Error: No se encontró functions.php");
}

require_once $database_file;
require_once $functions_file;


// Redireccionar según el tipo de usuario si ya está logueado
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /user/dashboard.php');
    }
    exit();
}

// Obtener algunos productos destacados para mostrar en la página principal
$featured_products = [];
try {
    $stmt = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY RAND() LIMIT 3");
    $featured_products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Manejar error silenciosamente para la vista
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streaming Premium - Venta de Cuentas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">Streaming Premium</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/register.php">Registrarse</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
        <div class="container text-center">
            <h1 class="display-4">Cuentas Premium de Streaming</h1>
            <p class="lead">Obtén acceso a todas las plataformas de streaming al mejor precio</p>
            <a href="/auth/register.php" class="btn btn-light btn-lg mt-3">Regístrate Ahora</a>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Servicios Destacados</h2>
            <div class="row">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="/assets/images/<?= strtolower(str_replace(' ', '-', $product['name'])) ?>.jpg" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
                            <p class="card-text"><strong>$<?= number_format($product['price'], 2) ?></strong></p>
                        </div>
                        <div class="card-footer bg-white">
                            <a href="/auth/login.php" class="btn btn-primary w-100">Comprar Ahora</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-4">
                    <div class="feature-icon bg-primary bg-gradient text-white rounded-3 mb-3 p-3 d-inline-block">
                        <i class="bi bi-shield-check fs-2"></i>
                    </div>
                    <h3>Cuentas Premium</h3>
                    <p>Acceso completo a todas las funciones de cada plataforma.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-icon bg-primary bg-gradient text-white rounded-3 mb-3 p-3 d-inline-block">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                    <h3>Perfiles Multiples</h3>
                    <p>Cada cuenta incluye varios perfiles para toda la familia.</p>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-icon bg-primary bg-gradient text-white rounded-3 mb-3 p-3 d-inline-block">
                        <i class="bi bi-headset fs-2"></i>
                    </div>
                    <h3>Soporte 24/7</h3>
                    <p>Nuestro equipo está disponible para ayudarte en cualquier momento.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-5 bg-dark text-white">
        <div class="container text-center">
            <h2 class="mb-4">¿Listo para comenzar?</h2>
            <p class="lead mb-4">Regístrate ahora y obtén acceso inmediato a tus servicios de streaming favoritos.</p>
            <a href="/auth/register.php" class="btn btn-primary btn-lg">Crear Cuenta Gratis</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-black text-white">
        <div class="container text-center">
            <p class="mb-0">&copy; <?= date('Y') ?> Streaming Premium. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css"></script>
</body>
</html>