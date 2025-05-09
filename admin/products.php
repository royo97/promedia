<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit();
}

// Variables para mensajes
$success = '';
$error = '';

// Procesar formulario de nuevo producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $has_pin = isset($_POST['has_pin']) ? 1 : 0;
    $profiles_count = intval($_POST['profiles_count']);

    // Validaciones
    if (empty($name) || empty($description) || $price <= 0 || $stock < 0 || $profiles_count <= 0) {
        $error = "Todos los campos son requeridos y deben ser válidos";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, has_pin, profiles_count) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $stock, $has_pin, $profiles_count]);
            
            $success = "Producto agregado correctamente";
        } catch (PDOException $e) {
            $error = "Error al agregar producto: " . $e->getMessage();
        }
    }
}

// En el procesamiento del formulario de productos:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // ... validaciones previas ...
    
    try {
        $pdo->beginTransaction();
        
        // Insertar el producto
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, profiles_available, has_pin, profiles_count) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $stock, $has_pin, $profiles_count]);
        $product_id = $pdo->lastInsertId();
        
        // Crear cuentas basadas en los perfiles necesarios
        $accounts_needed = ceil($stock / $profiles_count);
        for ($i = 0; $i < $accounts_needed; $i++) {
            // Insertar cuenta
            $email = generateAccountEmail($name);
            $password = generateRandomPassword();
            
            $stmt = $pdo->prepare("INSERT INTO accounts (product_id, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$product_id, $email, $password]);
            $account_id = $pdo->lastInsertId();
            
            // Generar perfiles según el tipo de servicio
            generateProfilesForAccount($account_id, $name, $has_pin, $profiles_count);
        }
        
        $pdo->commit();
        $success = "Producto agregado con ".$accounts_needed." cuentas creadas";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error al agregar producto: " . $e->getMessage();
    }
}

// Funciones auxiliares en functions.php
function generateAccountEmail($service_name) {
    $prefix = strtolower(str_replace(' ', '', $service_name));
    return $prefix.'_'.uniqid().'@streamingprovider.com';
}

function generateRandomPassword($length = 12) {
    return bin2hex(random_bytes($length/2));
}

function generateProfilesForAccount($account_id, $service_name, $has_pin, $profiles_count) {
    global $pdo;
    
    $profiles = [];
    
    // Configuración específica por servicio
    switch(strtoupper($service_name)) {
        case 'NETFLIX':
            $profiles = [
                ['name' => 'Perfil 1', 'pin' => '1001'],
                ['name' => 'Perfil 2', 'pin' => '2002'],
                ['name' => 'Perfil 3', 'pin' => '3003'],
                ['name' => 'Perfil 4', 'pin' => '4004'],
                ['name' => 'Perfil 5', 'pin' => '5005'],
                ['name' => 'Perfil 3', 'pin' => '3003'] // Perfil extra
            ];
            break;
            
        case 'DISNEY PREMIUM':
        case 'DISNEY+':
            $profiles = [
                ['name' => 'Perfil 1', 'pin' => '1001'],
                ['name' => 'Perfil 2', 'pin' => '2002'],
                ['name' => 'Perfil 3', 'pin' => '3003'],
                ['name' => 'Perfil 4', 'pin' => '4004'],
                ['name' => 'Perfil 5', 'pin' => '5005'],
                ['name' => 'Perfil 6', 'pin' => '6006'],
                ['name' => 'Perfil 7', 'pin' => '7007']
            ];
            break;
            
        case 'PRIME VIDEO':
            $profiles = [
                ['name' => 'Perfil 1', 'pin' => '11111'],
                ['name' => 'Perfil 2', 'pin' => '22222'],
                ['name' => 'Perfil 3', 'pin' => '33333'],
                ['name' => 'Perfil 4', 'pin' => '44444'],
                ['name' => 'Perfil 5', 'pin' => '55555'],
                ['name' => 'Perfil 6', 'pin' => '66666']
            ];
            break;
            
        case 'VIX':
            $profiles = [
                ['name' => 'Perfil 1', 'pin' => null],
                ['name' => 'Perfil 2', 'pin' => null],
                ['name' => 'Perfil 3', 'pin' => null],
                ['name' => 'Perfil 4', 'pin' => null],
                ['name' => 'Perfil 5', 'pin' => null]
            ];
            break;
            
        case 'HBO':
        case 'HBO MAX':
            $profiles = [
                ['name' => 'Perfil 1', 'pin' => '1001'],
                ['name' => 'Perfil 2', 'pin' => '2002'],
                ['name' => 'Perfil 3', 'pin' => '3003'],
                ['name' => 'Perfil 4', 'pin' => '4004'],
                ['name' => 'Perfil 5', 'pin' => '5005']
            ];
            break;
            
        default:
            // Perfiles genéricos si no coincide con ningún servicio conocido
            for ($i = 1; $i <= $profiles_count; $i++) {
                $profiles[] = ['name' => 'Perfil '.$i, 'pin' => $has_pin ? str_repeat($i, 5) : null];
            }
    }
    
    // Insertar perfiles en la base de datos
    foreach ($profiles as $profile) {
        $stmt = $pdo->prepare("INSERT INTO account_profiles (account_id, profile_name, pin, is_available) 
                              VALUES (?, ?, ?, TRUE)");
        $stmt->execute([$account_id, $profile['name'], $profile['pin']]);
    }
}

// Obtener todos los productos
$products = [];
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al cargar productos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .badge-outline {
            border: 1px solid #6c757d;
            color: #6c757d;
            background-color: transparent;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/admin_header.php'; ?>
    
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="fw-bold"><i class="bi bi-box-seam me-2"></i>Gestión de Productos</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/admin/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Productos</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Agregar Producto</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre del Servicio</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="price" class="form-label">Precio</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label for="stock" class="form-label">Stock Inicial</label>
                                <input type="number" min="0" class="form-control" id="stock" name="stock" required>
                            </div>
                            <div class="mb-3">
                                <label for="profiles_count" class="form-label">Número de Perfiles</label>
                                <input type="number" min="1" class="form-control" id="profiles_count" name="profiles_count" required>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="has_pin" name="has_pin" checked>
                                <label class="form-check-label" for="has_pin">Requiere PIN</label>
                            </div>
                            <button type="submit" name="add_product" class="btn btn-primary w-100">
                                <i class="bi bi-save me-2"></i>Guardar Producto
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Lista de Productos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Precio</th>
                                        <th>Stock</th>
                                        <th>Perfiles</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
    <td><?= htmlspecialchars($product['id']) ?></td>
    <td>
        <strong><?= htmlspecialchars($product['name']) ?></strong>
        <small class="d-block text-muted"><?= htmlspecialchars($product['description']) ?></small>
    </td>
    <td>$<?= number_format($product['price'], 2) ?></td>
    <td>
        <?php if ($product['profiles_available'] > 5): ?>
            <span class="badge bg-success"><?= $product['profiles_available'] ?> perfiles</span>
        <?php elseif ($product['profiles_available'] > 0): ?>
            <span class="badge bg-warning text-dark"><?= $product['profiles_available'] ?> perfiles</span>
        <?php else: ?>
            <span class="badge bg-danger">Agotado</span>
        <?php endif; ?>
        <small class="d-block text-muted">
            <?= ceil($product['profiles_available'] / $product['profiles_count']) ?> cuentas
        </small>
    </td>
    <td>
        <span class="badge bg-info"><?= $product['profiles_count'] ?></span>
        <?= $product['has_pin'] ? '<span class="badge bg-secondary ms-1">PIN</span>' : '' ?>
    </td>
    <td>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $product['id'] ?>">
            <i class="bi bi-pencil"></i>
        </button>
    </td>
</tr>


                                    <!-- Modal de Edición -->
                                    <div class="modal fade" id="editModal<?= $product['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Editar <?= htmlspecialchars($product['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Precio</label>
                                                            <input type="number" step="0.01" min="0" class="form-control" name="price" value="<?= $product['price'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Stock</label>
                                                            <input type="number" min="0" class="form-control" name="stock" value="<?= $product['stock'] ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="update_product" class="btn btn-primary">Guardar Cambios</button>
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
        </div>
    </div>

    <?php include __DIR__ . '/../includes/admin_footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para manejar la interfaz
        document.addEventListener('DOMContentLoaded', function() {
            // Activar tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>