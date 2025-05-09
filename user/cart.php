<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// Procesar adición al carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $user_id = (int)$_SESSION['user_id'];
    
    try {
        // Verificar disponibilidad de perfiles
        $stmt = $pdo->prepare("
            SELECT profiles_available 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$product_id]);
        $available_profiles = $stmt->fetchColumn();
        
        if ($available_profiles < 1) {
            $_SESSION['cart_error'] = "No hay perfiles disponibles para este producto";
            header('Location: /user/dashboard.php');
            exit();
        }
        
        // Agregar al carrito (ahora manejamos cantidad de perfiles)
        $stmt = $pdo->prepare("
            INSERT INTO cart (user_id, product_id, quantity) 
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE quantity = quantity + 1
        ");
        $stmt->execute([$user_id, $product_id]);
        
        header('Location: cart.php');
        exit();
        
    } catch (PDOException $e) {
        $error = "Error al agregar al carrito: " . $e->getMessage();
    }
}
// Resto del código del carrito...

// Variables para mensajes
$error = '';
$success = '';

// Obtener el carrito del usuario
$cart_items = [];
$total = 0;

try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.description 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    // Calcular total
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    $error = "Error al cargar el carrito: " . $e->getMessage();
}

// Procesar actualización de cantidad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $_SESSION['user_id']]);
            $success = "Carrito actualizado correctamente";
            header("Refresh:0"); // Recargar para ver cambios
        } catch (PDOException $e) {
            $error = "Error al actualizar cantidad: " . $e->getMessage();
        }
    } else {
        $error = "La cantidad debe ser mayor a cero";
    }
}

// Procesar eliminación de item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cart_id = (int)$_POST['cart_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$cart_id, $_SESSION['user_id']]);
        $success = "Producto eliminado del carrito";
        header("Refresh:0"); // Recargar para ver cambios
    } catch (PDOException $e) {
        $error = "Error al eliminar producto: " . $e->getMessage();
    }
}

// Procesar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    // Verificar saldo suficiente
    $user_balance = get_user_balance($_SESSION['user_id']);
    
    if ($user_balance >= $total) {
        try {
            $pdo->beginTransaction();
            
            // 1. Verificar disponibilidad de perfiles
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM account_profiles ap
                    JOIN accounts a ON ap.account_id = a.id
                    WHERE a.product_id = ? AND ap.is_available = TRUE
                ");
                $stmt->execute([$item['product_id']]);
                $available = $stmt->fetchColumn();
                
                if ($available < $item['quantity']) {
                    throw new Exception("No hay suficientes perfiles disponibles para: " . $item['name']);
                }
            }
            
            // 2. Crear la orden
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total]);
            $order_id = $pdo->lastInsertId();
            
            // 3. Procesar cada item del carrito
            foreach ($cart_items as $item) {
                // Obtener perfiles disponibles (solución al error)
                $sql = "
                    SELECT ap.id, ap.account_id
                    FROM account_profiles ap
                    JOIN accounts a ON ap.account_id = a.id
                    WHERE a.product_id = ? AND ap.is_available = TRUE
                    LIMIT ".intval($item['quantity'])." FOR UPDATE
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$item['product_id']]);
                $profiles = $stmt->fetchAll();
                
                if (count($profiles) < $item['quantity']) {
                    throw new Exception("No se pudieron reservar suficientes perfiles para: " . $item['name']);
                }
                
                // Resto del proceso de compra...
                foreach ($profiles as $profile) {
                    // Marcar perfil como no disponible
                    $stmt = $pdo->prepare("
                        UPDATE account_profiles 
                        SET is_available = FALSE 
                        WHERE id = ?
                    ");
                    $stmt->execute([$profile['id']]);
                    
                    // Registrar en order_items
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, account_id, profile_id, price)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $order_id,
                        $profile['account_id'],
                        $profile['id'],
                        $item['price'] / $item['quantity']
                    ]);
                }
                
                // Actualizar disponibilidad de perfiles
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET profiles_available = profiles_available - ? 
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // 4. Actualizar saldo del usuario
            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$total, $_SESSION['user_id']]);
            
            // 5. Vaciar carrito
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            $pdo->commit();
            header('Location: /user/order_success.php?order_id=' . $order_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al procesar la compra: " . $e->getMessage();
        }
    } else {
        $error = "Saldo insuficiente. Por favor recarga tu cuenta.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Streaming Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .cart-item {
            transition: all 0.3s ease;
        }
        .cart-item:hover {
            background-color: #f8f9fa;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
        }
        .total-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .balance-info {
            font-size: 1.1rem;
        }
        .balance-info .badge {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/user_header.php'; ?>
    
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4"><i class="bi bi-cart3 me-2"></i>Mi Carrito</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i>Seguir Comprando
                    </a>
                    <div class="balance-info">
                        <span class="text-muted">Saldo disponible:</span>
                        <span class="badge bg-primary ms-2">$<?= number_format(get_user_balance($_SESSION['user_id']), 2) ?></span>
                    </div>
                </div>
                
                <?php if (empty($cart_items)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-cart-x" style="font-size: 3rem; color: #6c757d;"></i>
                        <h4 class="mt-3">Tu carrito está vacío</h4>
                        <p class="text-muted">Agrega productos para continuar</p>
                        <a href="dashboard.php" class="btn btn-primary mt-3">
                            <i class="bi bi-box-seam me-2"></i>Explorar Productos
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Precio</th>
                                                    <th>Cantidad</th>
                                                    <th>Subtotal</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($cart_items as $item): ?>
                                                <tr class="cart-item">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="/assets/images/<?= strtolower(str_replace(' ', '-', $item['name'])) ?>.jpg" 
                                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                                 width="60" class="rounded me-3">
                                                            <div>
                                                                <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                                <small class="text-muted"><?= htmlspecialchars($item['description']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                                            <input type="number" name="quantity" min="1" 
                                                                   value="<?= $item['quantity'] ?>" 
                                                                   class="form-control quantity-input">
                                                            <button type="submit" name="update_quantity" class="btn btn-sm btn-link mt-1">
                                                                <i class="bi bi-arrow-clockwise"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                                    <td>
                                                        <form method="POST" onsubmit="return confirm('¿Eliminar este producto del carrito?');">
                                                            <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                                            <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 mt-4 mt-lg-0">
                            <div class="card">
                                <div class="card-body total-box">
                                    <h5 class="card-title mb-4">Resumen de Compra</h5>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Subtotal:</span>
                                        <span>$<?= number_format($total, 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Impuestos:</span>
                                        <span>$0.00</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between mb-4">
                                        <strong>Total:</strong>
                                        <strong>$<?= number_format($total, 2) ?></strong>
                                    </div>
                                    
                                    <form method="POST">

                                    <?php if ($can_checkout): ?>
                                    <button type="submit" name="checkout" class="btn btn-primary w-100 py-2">
                                        <i class="bi bi-credit-card me-2"></i>Pagar Ahora
                                    </button>
                                    <?php else: ?>
                                    <div class="alert alert-warning mt-3">
                                        No se puede completar la compra: <?= htmlspecialchars($error) ?>
                                    </div>
                                    <?php endif; ?>
                                    </form>
                                    
                                    <div class="mt-3 text-center">
                                        <small class="text-muted">
                                            <i class="bi bi-lock-fill me-1"></i>Tu transacción es segura
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/user_footer.php'; ?>


    
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirmación antes de pagar
        document.querySelector('button[name="checkout"]')?.addEventListener('click', function(e) {
            const balance = <?= get_user_balance($_SESSION['user_id']) ?>;
            const total = <?= $total ?>;
            
            if (balance < total) {
                e.preventDefault();
                alert('Saldo insuficiente. Por favor recarga tu cuenta.');
                return false;
            }
            
            return confirm('¿Confirmar compra por $' + total.toFixed(2) + '?');
        });
    </script>
</body>
</html>