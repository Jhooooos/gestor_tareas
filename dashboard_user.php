<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT t.*, c.nombre as categoria_nombre 
                      FROM tickets t 
                      JOIN categorias c ON t.categoria_id = c.id 
                      WHERE t.usuario_id = ? 
                      ORDER BY t.created_at DESC");
$stmt->execute([$user_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT estado, COUNT(*) as total 
                      FROM tickets 
                      WHERE usuario_id = ? 
                      GROUP BY estado");
$stmt->execute([$user_id]);
$tickets_por_estado = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario - <?= APP_NAME ?></title>
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
                        <a class="nav-link active" href="dashboard_user.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_ticket.php">Nuevo Ticket</a>
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
            <div class="col-md-3">
                <div class="card bg-secondary mb-4">
                    <div class="card-header">
                        <h5>Resumen de Tickets</h5>
                    </div>
                    <div class="card-body">
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
                        <div class="mt-3">
                            <span class="fw-bold">Total:</span>
                            <span class="float-end"><?= count($tickets) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card bg-secondary">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Mis Tickets</h5>
                        <a href="create_ticket.php" class="btn btn-primary btn-sm">Nuevo Ticket</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tickets)): ?>
                            <div class="alert alert-info">No hay tickets registrados.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Categoría</th>
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
        </div>
    </div>

    <!-- Botón Chatbot -->
    <button onclick="toggleChatbot()" class="btn btn-info position-fixed" style="bottom: 20px; right: 20px; z-index: 1001;">
        Chatbot
    </button>

    <!-- Ventana del Chatbot -->
    <div id="chatbot" style="position: fixed; bottom: 70px; right: 20px; width: 300px; background: #fff; color: #000; border: 1px solid #ccc; border-radius: 10px; display: none; z-index: 1000;">
        <div style="padding: 10px; border-bottom: 1px solid #ddd; background: #007bff; color: #fff;">
            <strong>Asistente Virtual</strong>
            <button onclick="toggleChatbot()" style="float: right; background: none; border: none; color: white;">&times;</button>
        </div>
        <div id="chat-log" style="padding: 10px; max-height: 200px; overflow-y: auto; font-size: 14px;"></div>
        <form id="chat-form" style="padding: 10px; border-top: 1px solid #ddd;">
            <input type="text" id="user-message" class="form-control" placeholder="Escribe tu pregunta..." required>
            <button type="submit" class="btn btn-primary w-100 mt-2">Enviar</button>
        </form>
    </div>

    <script>
        function toggleChatbot() {
            const chatbot = document.getElementById("chatbot");
            chatbot.style.display = chatbot.style.display === "none" ? "block" : "none";
        }

        document.getElementById("chat-form").addEventListener("submit", async function(e) {
            e.preventDefault();
            const userMessage = document.getElementById("user-message").value.trim();
            const chatLog = document.getElementById("chat-log");

            if (!userMessage) return;

            chatLog.innerHTML += `<div><strong>Tú:</strong> ${userMessage}</div>`;
            document.getElementById("user-message").value = '';

            try {
                const response = await fetch("https://api.openai.com/v1/chat/completions", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Authorization": "Bearer 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"
                    },
                    body: JSON.stringify({
                        model: "gpt-3.5-turbo",
                        messages: [{ role: "user", content: userMessage }]
                    })
                });

                const data = await response.json();
                const reply = data.choices?.[0]?.message?.content || "No se pudo obtener respuesta.";
                chatLog.innerHTML += `<div><strong>Bot:</strong> ${reply}</div>`;
                chatLog.scrollTop = chatLog.scrollHeight;
            } catch (error) {
                chatLog.innerHTML += `<div><strong>Bot:</strong> Error al conectarse con el servidor.</div>`;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
