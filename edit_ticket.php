<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Verificar si se proporcionó un ID de ticket
if (!isset($_GET['id'])) {
    redirect(isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php');
}

$ticket_id = intval($_GET['id']);
$error = '';
$success = '';

// Obtener información del ticket
$stmt = $pdo->prepare("SELECT t.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre 
                       FROM tickets t 
                       JOIN categorias c ON t.categoria_id = c.id 
                       JOIN usuarios u ON t.usuario_id = u.id 
                       WHERE t.id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si el ticket existe y si el usuario tiene permiso para editarlo
if (!$ticket) {
    redirect(isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php');
}

if (!isAdmin() && $ticket['usuario_id'] != $_SESSION['user_id']) {
    redirect('dashboard_user.php');
}

// Obtener categorías
$stmt = $pdo->query("SELECT * FROM categorias");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket'])) {
    $titulo = sanitizeInput($_POST['titulo']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $categoria_id = intval($_POST['categoria_id']);
    $prioridad = sanitizeInput($_POST['prioridad']);
    $estado = isAdmin() ? sanitizeInput($_POST['estado']) : $ticket['estado'];
    
    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($categoria_id)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        // Actualizar ticket
        $stmt = $pdo->prepare("UPDATE tickets SET 
                              titulo = ?, 
                              descripcion = ?, 
                              categoria_id = ?, 
                              prioridad = ?, 
                              estado = ?, 
                              updated_at = CURRENT_TIMESTAMP 
                              WHERE id = ?");
        
        if ($stmt->execute([$titulo, $descripcion, $categoria_id, $prioridad, $estado, $ticket_id])) {
            $success = "Ticket actualizado exitosamente.";
            // Actualizar datos del ticket en la variable
            $ticket['titulo'] = $titulo;
            $ticket['descripcion'] = $descripcion;
            $ticket['categoria_id'] = $categoria_id;
            $ticket['prioridad'] = $prioridad;
            $ticket['estado'] = $estado;
        } else {
            $error = "Error al actualizar el ticket. Inténtalo de nuevo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ticket #<?= $ticket_id ?> - <?= APP_NAME ?></title>
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
                        <a class="nav-link" href="<?= isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php' ?>">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_tickets.php">Tickets</a>
                    </li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">Usuarios</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-secondary">
                    <div class="card-header">
                        <h4>Editar Ticket #<?= $ticket_id ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form action="edit_ticket.php?id=<?= $ticket_id ?>" method="POST">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($ticket['titulo']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="5" required><?= htmlspecialchars($ticket['descripcion']) ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="categoria_id" class="form-label">Categoría</label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <option value="">Seleccionar categoría</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= $categoria['id'] ?>" <?= $ticket['categoria_id'] == $categoria['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($categoria['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prioridad" class="form-label">Prioridad</label>
                                    <select class="form-select" id="prioridad" name="prioridad">
                                        <option value="baja" <?= $ticket['prioridad'] == 'baja' ? 'selected' : '' ?>>Baja</option>
                                        <option value="media" <?= $ticket['prioridad'] == 'media' ? 'selected' : '' ?>>Media</option>
                                        <option value="alta" <?= $ticket['prioridad'] == 'alta' ? 'selected' : '' ?>>Alta</option>
                                        <option value="critica" <?= $ticket['prioridad'] == 'critica' ? 'selected' : '' ?>>Crítica</option>
                                    </select>
                                </div>
                            </div>
                            <?php if (isAdmin()): ?>
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="abierto" <?= $ticket['estado'] == 'abierto' ? 'selected' : '' ?>>Abierto</option>
                                        <option value="en_progreso" <?= $ticket['estado'] == 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                                        <option value="resuelto" <?= $ticket['estado'] == 'resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                        <option value="cerrado" <?= $ticket['estado'] == 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between">
                                <a href="<?= isAdmin() ? 'view_tickets.php' : 'dashboard_user.php' ?>" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" name="update_ticket" class="btn btn-primary">Actualizar Ticket</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>