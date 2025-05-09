<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// Verificar permisos de administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit();
}

// Variables para mensajes
$success = '';
$error = '';

// Procesar recarga de saldo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $user_id = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $action = $_POST['balance_action'];
    
    try {
        $pdo->beginTransaction();
        
        // Obtener saldo actual
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_balance = $stmt->fetchColumn();
        
        // Calcular nuevo saldo
        if ($action === 'add') {
            $new_balance = $current_balance + $amount;
        } else {
            $new_balance = $current_balance - $amount;
            if ($new_balance < 0) $new_balance = 0;
        }
        
        // Actualizar saldo
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $user_id]);
        
        // Registrar la transacción
        $stmt = $pdo->prepare("INSERT INTO balance_transactions (user_id, amount, type, admin_id) VALUES (?, ?, ?, ?)");
        $type = $action === 'add' ? 'admin_add' : 'admin_subtract';
        $stmt->execute([$user_id, $amount, $type, $_SESSION['user_id']]);
        
        $pdo->commit();
        $success = "Saldo actualizado correctamente";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error al actualizar saldo: " . $e->getMessage();
    }
}

// Procesar cambio de rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->execute([$is_admin, $user_id]);
        $success = "Rol de usuario actualizado correctamente";
    } catch (PDOException $e) {
        $error = "Error al actualizar rol: " . $e->getMessage();
    }
}

// Obtener todos los usuarios
$users = [];
try {
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(o.id) as total_orders,
               SUM(o.total) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .user-card {
            transition: all 0.3s ease;
        }
        .user-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .admin-badge {
            background-color: #0d6efd;
        }
        .user-badge {
            background-color: #6c757d;
        }
        .balance-form {
            max-width: 300px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold"><i class="bi bi-people me-2"></i>Gestión de Usuarios</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Usuarios</li>
                    </ol>
                </nav>
            </div>
        </div>

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

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Usuarios</h5>
                    <span class="badge bg-primary">Total: <?= count($users) ?></span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Saldo</th>
                                <th>Compras</th>
                                <th>Registro</th>
                                <th>Rol</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="user-card">
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-success">$<?= number_format($user['balance'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info" data-bs-toggle="tooltip" title="Total gastado">
                                        <?= $user['total_orders'] ?> ($<?= number_format($user['total_spent'] ?? 0, 2) ?>)
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge admin-badge">Admin</span>
                                    <?php else: ?>
                                        <span class="badge user-badge">Usuario</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#balanceModal<?= $user['id'] ?>">
                                        <i class="bi bi-wallet2"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#roleModal<?= $user['id'] ?>">
                                        <i class="bi bi-person-gear"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal para recargar saldo -->
                            <div class="modal fade" id="balanceModal<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Gestionar Saldo</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Usuario:</label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Saldo Actual:</label>
                                                    <input type="text" class="form-control" value="$<?= number_format($user['balance'], 2) ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Acción:</label>
                                                    <select class="form-select" name="balance_action" required>
                                                        <option value="add">Agregar saldo</option>
                                                        <option value="subtract">Restar saldo</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Cantidad:</label>
                                                    <input type="number" step="0.01" min="0.01" class="form-control" name="amount" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" name="update_balance" class="btn btn-primary">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal para cambiar rol -->
                            <div class="modal fade" id="roleModal<?= $user['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Cambiar Rol de Usuario</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Usuario:</label>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_admin" id="isAdmin<?= $user['id'] ?>" <?= $user['is_admin'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="isAdmin<?= $user['id'] ?>">
                                                            ¿Es administrador?
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" name="change_role" class="btn btn-primary">Guardar Cambios</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>