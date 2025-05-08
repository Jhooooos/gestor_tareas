<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Obtener estadísticas
$stmt = $pdo->query("SELECT COUNT(*) as total_usuarios FROM usuarios");
$total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total_usuarios'];

$stmt = $pdo->query("SELECT COUNT(*) as total_tickets FROM tickets");
$total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['total_tickets'];

$stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado");
$tickets_por_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimos tickets
$stmt = $pdo->query("SELECT t.*, c.nombre as categoria_nombre, u.nombre as usuario_nombre 
                     FROM tickets t 
                     JOIN categorias c ON t.categoria_id = c.id 
                     JOIN usuarios u ON t.usuario_id = u.id 
                     ORDER BY t.created_at DESC LIMIT 5");
$ultimos_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?= APP_NAME ?></title>
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
                        <a class="nav-link active" href="dashboard_admin.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view_tickets.php">Tickets</a>
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
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card bg-secondary">
                    <div class="card-header">
                        <h5>Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Usuarios Registrados</h6>
                            <h3><?= $total_usuarios ?></h3>
                        </div>
                        <div class="mb-3">
                            <h6>Tickets Totales</h6>
                            <h3><?= $total_tickets ?></h3>
                        </div>
                        <div>
                            <h6>Tickets por Estado</h6>
                            <?php foreach ($tickets_por_estado as $ticket): ?>
                                <div class="mb-2">
                                    <span class="badge bg-<?= 
                                        $ticket['estado'] == 'abierto' ? 'warning' : 
                                        ($ticket['estado'] == 'en_progreso' ? 'info' : 
                                        ($ticket['estado'] == 'resuelto' ? 'success' : 'secondary')) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $ticket['estado'])) ?>
                                    </span>
                                    <span class="float-end"><?= $ticket['total'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card bg-secondary mb-4">
                    <div class="card-header">
                        <h5>Últimos Tickets</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($ultimos_tickets)): ?>
                            <div class="alert alert-info">No hay tickets recientes.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Usuario</th>
                                            <th>Categoría</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ultimos_tickets as $ticket): ?>
                                            <tr>
                                                <td>#<?= $ticket['id'] ?></td>
                                                <td><?= htmlspecialchars($ticket['titulo']) ?></td>
                                                <td><?= htmlspecialchars($ticket['usuario_nombre']) ?></td>
                                                <td><?= htmlspecialchars($ticket['categoria_nombre']) ?></td>
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
                            <div class="text-end mt-3">
                                <a href="view_tickets.php" class="btn btn-primary">Ver Todos los Tickets</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card bg-secondary">
                    <div class="card-header">
                        <h5>Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="create_ticket.php" class="btn btn-primary w-100">Crear Ticket</a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="manage_users.php" class="btn btn-success w-100">Gestionar Usuarios</a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="settings.php" class="btn btn-info w-100">Configuración</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>