<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// Obtener ID de la orden
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Verificar que la orden pertenece al usuario
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: /user/dashboard.php');
        exit();
    }

    // Obtener items de la orden
    $stmt = $pdo->prepare("
        SELECT oi.*, a.email as account_email, a.password as account_password, 
               p.name as product_name, p.description as product_description
        FROM order_items oi
        JOIN accounts a ON oi.account_id = a.id
        JOIN products p ON a.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error al cargar la orden: " . $e->getMessage());
}

// Calcular fecha de expiración (30 días después de la compra)
$expiration_date = date('Y-m-d', strtotime('+30 days'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden Completada - Streaming Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .receipt-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .account-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .account-card:hover {
            background-color: #e9ecef;
        }
        .badge-expires {
            background-color: #fd7e14;
        }
        .divider {
            border-top: 1px dashed #dee2e6;
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/user_header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card receipt-card">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="bi bi-check-circle me-2"></i>¡Compra Exitosa!</h3>
                            <span class="badge bg-light text-dark">Orden #<?= $order_id ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-check2-circle text-success" style="font-size: 4rem;"></i>
                            <h2 class="mt-3">Gracias por tu compra</h2>
                            <p class="text-muted">Aquí están los detalles de tu orden</p>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Información de la Orden</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Número:</strong> <?= $order_id ?></li>
                                    <li><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></li>
                                    <li><strong>Total:</strong> $<?= number_format($order['total'], 2) ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Información del Cliente</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Usuario:</strong> <?= htmlspecialchars($order['username']) ?></li>
                                    <li><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <h4 class="mb-4"><i class="bi bi-collection me-2"></i>Cuentas Adquiridas</h4>
                        
                        <?php foreach ($order_items as $item): ?>
                        <div class="card account-card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                    <span class="badge badge-expires">Expira: <?= $expiration_date ?></span>
                                </div>
                                <p class="text-muted"><?= htmlspecialchars($item['product_description']) ?></p>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <label class="form-label text-muted">Email:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($item['account_email']) ?>" readonly>
                                                <button class="btn btn-outline-secondary copy-btn" data-text="<?= htmlspecialchars($item['account_email']) ?>">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-2">
                                            <label class="form-label text-muted">Contraseña:</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" value="<?= htmlspecialchars($item['account_password']) ?>" readonly>
                                                <button class="btn btn-outline-secondary copy-btn" data-text="<?= htmlspecialchars($item['account_password']) ?>">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary toggle-password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (strpos($item['product_name'], 'Netflix') !== false || 
                                          strpos($item['product_name'], 'Disney') !== false || 
                                          strpos($item['product_name'], 'HBO') !== false): ?>
                                <div class="mt-3">
                                    <h6><i class="bi bi-people me-2"></i>Perfiles Disponibles:</h6>
                                    <ul class="list-group">
                                        <?php 
                                        // Obtener perfiles para esta cuenta
                                        $profile_stmt = $pdo->prepare("
                                            SELECT * FROM account_profiles 
                                            WHERE account_id = ?
                                        ");
                                        $profile_stmt->execute([$item['account_id']]);
                                        $profiles = $profile_stmt->fetchAll();
                                        
                                        foreach ($profiles as $profile): 
                                        ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <?= htmlspecialchars($profile['profile_name']) ?>
                                                <?php if (!empty($profile['pin'])): ?>
                                                    <small class="text-muted">(PIN: <?= $profile['pin'] ?>)</small>
                                                <?php endif; ?>
                                            </span>
                                            <button class="btn btn-sm btn-outline-primary copy-btn" data-text="<?= $profile['pin'] ?>">
                                                <i class="bi bi-clipboard"></i> Copiar PIN
                                            </button>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="divider"></div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                            <a href="/user/dashboard.php" class="btn btn-outline-primary">
                                <i class="bi bi-house-door me-2"></i>Volver al Inicio
                            </a>
                            <a href="/user/orders.php" class="btn btn-primary">
                                <i class="bi bi-receipt me-2"></i>Ver Todas mis Órdenes
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <h5><i class="bi bi-info-circle me-2"></i>Instrucciones importantes:</h5>
                    <ul class="mb-0">
                        <li>Todas las cuentas tienen una validez de 30 días a partir de la fecha de compra.</li>
                        <li>Guarda esta información en un lugar seguro.</li>
                        <li>Para cualquier problema, contacta a soporte.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/user_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para copiar texto al portapapeles
        document.querySelectorAll('.copy-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const text = this.getAttribute('data-text');
                navigator.clipboard.writeText(text).then(() => {
                    const originalInnerHTML = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check"></i> Copiado';
                    setTimeout(() => {
                        this.innerHTML = originalInnerHTML;
                    }, 2000);
                });
            });
        });

        // Mostrar/ocultar contraseña
        document.querySelectorAll('.toggle-password').forEach(btn => {
            btn.addEventListener('click', function() {
                const input = this.closest('.input-group').querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Imprimir recibo
        function printReceipt() {
            window.print();
        }
    </script>
</body>
</html>