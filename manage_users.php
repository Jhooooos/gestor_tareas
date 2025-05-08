<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Obtener lista de usuarios
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar cambio de rol
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $usuario_id = intval($_POST['usuario_id']);
    $nuevo_rol = sanitizeInput($_POST['nuevo_rol']);
    
    // No permitir cambiar el rol del usuario admin principal
    if ($usuario_id === 1) {
        $error = "No puedes cambiar el rol del administrador principal.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
        if ($stmt->execute([$nuevo_rol, $usuario_id])) {
            $success = "Rol actualizado exitosamente.";
            // Actualizar lista de usuarios
            $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Error al actualizar el rol. Inténtalo de nuevo.";
        }
    }
}

// Procesar eliminación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $usuario_id = intval($_POST['usuario_id']);
    
    // No permitir eliminar el usuario admin principal
    if ($usuario_id === 1) {
        $error = "No puedes eliminar al administrador principal.";
    } else {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // Primero eliminar los tickets del usuario
            $stmt = $pdo->prepare("DELETE FROM tickets WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            
            // Luego eliminar el usuario
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            
            $pdo->commit();
            $success = "Usuario eliminado exitosamente.";
            // Actualizar lista de usuarios
            $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al eliminar el usuario: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><?= APP_NAME ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard_admin.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_ticket.php">Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_users.php">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">Configuración</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card bg-secondary">
            <div class="card-header">
                <h4>Gestionar Usuarios</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <?php if (empty($usuarios)): ?>
                    <div class="alert alert-info">No hay usuarios registrados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?= $usuario['id'] ?></td>
                                        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                        <td><?= htmlspecialchars($usuario['email']) ?></td>
                                        <td><?= htmlspecialchars($usuario['telefono'] ?? 'N/A') ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                                <select name="nuevo_rol" class="form-select form-select-sm d-inline w-auto" <?= $usuario['id'] === 1 ? 'disabled' : '' ?>>
                                                    <option value="user" <?= $usuario['rol'] === 'user' ? 'selected' : '' ?>>Usuario</option>
                                                    <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                                </select>
                                                <?php if ($usuario['id'] !== 1): ?>
                                                    <button type="submit" name="change_role" class="btn btn-sm btn-primary ms-2">Cambiar</button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($usuario['created_at'])) ?></td>
                                        <td>
                                            <?php if ($usuario['id'] !== 1): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario? Todos sus tickets también serán eliminados.')">Eliminar</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>