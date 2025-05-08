<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Obtener parámetros de filtrado
$categoria_id = isset($_GET['categoria_id']) ? intval($_GET['categoria_id']) : null;
$estado = isset($_GET['estado']) ? sanitizeInput($_GET['estado']) : null;
$prioridad = isset($_GET['prioridad']) ? sanitizeInput($_GET['prioridad']) : null;

// Construir consulta base
$sql = "SELECT t.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre 
        FROM tickets t 
        JOIN categorias c ON t.categoria_id = c.id 
        JOIN usuarios u ON t.usuario_id = u.id";

$params = [];
$conditions = [];

// Aplicar filtros
if ($categoria_id) {
    $conditions[] = "t.categoria_id = ?";
    $params[] = $categoria_id;
}

if ($estado) {
    $conditions[] = "t.estado = ?";
    $params[] = $estado;
}

if ($prioridad) {
    $conditions[] = "t.prioridad = ?";
    $params[] = $prioridad;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY t.created_at DESC";

// Obtener tickets
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el filtro
$stmt = $pdo->query("SELECT * FROM categorias");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - <?= APP_NAME ?></title>
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
                        <a class="nav-link active" href="view_tickets.php">Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Usuarios</a>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Listado de Tickets</h4>
                <a href="create_ticket.php" class="btn btn-primary btn-sm">Nuevo Ticket</a>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="categoria_id" class="form-label">Categoría</label>
                            <select class="form-select" id="categoria_id" name="categoria_id">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= $categoria_id == $categoria['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="">Todos</option>
                                <option value="abierto" <?= $estado == 'abierto' ? 'selected' : '' ?>>Abierto</option>
                                <option value="en_progreso" <?= $estado == 'en_progreso' ? 'selected' : '' ?>>En Progreso</option>
                                <option value="resuelto" <?= $estado == 'resuelto' ? 'selected' : '' ?>>Resuelto</option>
                                <option value="cerrado" <?= $estado == 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="prioridad" class="form-label">Prioridad</label>
                            <select class="form-select" id="prioridad" name="prioridad">
                                <option value="">Todas</option>
                                <option value="baja" <?= $prioridad == 'baja' ? 'selected' : '' ?>>Baja</option>
                                <option value="media" <?= $prioridad == 'media' ? 'selected' : '' ?>>Media</option>
                                <option value="alta" <?= $prioridad == 'alta' ? 'selected' : '' ?>>Alta</option>
                                <option value="critica" <?= $prioridad == 'critica' ? 'selected' : '' ?>>Crítica</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </div>
                </form>

                <!-- Listado de tickets -->
                <?php if (empty($tickets)): ?>
                    <div class="alert alert-info">No hay tickets que coincidan con los filtros.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Usuario</th>
                                    <th>Categoría</th>
                                    <th>Prioridad</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td>#<?= $ticket['id'] ?></td>
                                        <td><?= htmlspecialchars($ticket['titulo']) ?></td>
                                        <td><?= htmlspecialchars($ticket['usuario_nombre']) ?></td>
                                        <td><?= htmlspecialchars($ticket['categoria_nombre']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $ticket['prioridad'] == 'baja' ? 'success' : 
                                                ($ticket['prioridad'] == 'media' ? 'primary' : 
                                                ($ticket['prioridad'] == 'alta' ? 'warning' : 'danger')) ?>">
                                                <?= ucfirst($ticket['prioridad']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $ticket['estado'] == 'abierto' ? 'warning' : 
                                                ($ticket['estado'] == 'en_progreso' ? 'info' : 
                                                ($ticket['estado'] == 'resuelto' ? 'success' : 'secondary')) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $ticket['estado'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                                        <td>
                                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                                            <a href="edit_ticket.php?id=<?= $ticket['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
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