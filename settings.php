<?php
require_once 'includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Obtener configuración actual (simulado)
$configuracion = [
    'app_name' => APP_NAME,
    'recaptcha_site_key' => 'TU_SITE_KEY_RECAPTCHA',
    'recaptcha_secret_key' => 'TU_SECRET_KEY_RECAPTCHA',
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => '587',
    'smtp_user' => 'user@example.com',
    'smtp_pass' => 'password',
    'twofa_enabled' => true
];

// Procesar formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $app_name = sanitizeInput($_POST['app_name']);
    $recaptcha_site_key = sanitizeInput($_POST['recaptcha_site_key']);
    $recaptcha_secret_key = sanitizeInput($_POST['recaptcha_secret_key']);
    $smtp_host = sanitizeInput($_POST['smtp_host']);
    $smtp_port = sanitizeInput($_POST['smtp_port']);
    $smtp_user = sanitizeInput($_POST['smtp_user']);
    $smtp_pass = sanitizeInput($_POST['smtp_pass']);
    $twofa_enabled = isset($_POST['twofa_enabled']) ? true : false;
    
    // Validaciones básicas
    if (empty($app_name) || empty($recaptcha_site_key) || empty($recaptcha_secret_key)) {
        $error = "Los campos principales son obligatorios.";
    } else {
        // En una aplicación real, aquí guardarías estos valores en la base de datos o archivo de configuración
        $success = "Configuración actualizada exitosamente.";
        
        // Actualizar valores mostrados
        $configuracion['app_name'] = $app_name;
        $configuracion['recaptcha_site_key'] = $recaptcha_site_key;
        $configuracion['recaptcha_secret_key'] = $recaptcha_secret_key;
        $configuracion['smtp_host'] = $smtp_host;
        $configuracion['smtp_port'] = $smtp_port;
        $configuracion['smtp_user'] = $smtp_user;
        $configuracion['smtp_pass'] = $smtp_pass;
        $configuracion['twofa_enabled'] = $twofa_enabled;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - <?= APP_NAME ?></title>
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
                        <a class="nav-link" href="view_tickets.php">Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">Usuarios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">Configuración</a>
                    </li>
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
                        <h4>Configuración del Sistema</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>
                        
                        <form action="settings.php" method="POST">
                            <h5 class="mb-3">Configuración General</h5>
                            <div class="mb-3">
                                <label for="app_name" class="form-label">Nombre de la Aplicación</label>
                                <input type="text" class="form-control" id="app_name" name="app_name" value="<?= htmlspecialchars($configuracion['app_name']) ?>" required>
                            </div>
                            
                            <h5 class="mb-3 mt-4">reCAPTCHA</h5>
                            <div class="mb-3">
                                <label for="recaptcha_site_key" class="form-label">Site Key</label>
                                <input type="text" class="form-control" id="recaptcha_site_key" name="recaptcha_site_key" value="<?= htmlspecialchars($configuracion['recaptcha_site_key']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="recaptcha_secret_key" class="form-label">Secret Key</label>
                                <input type="text" class="form-control" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?= htmlspecialchars($configuracion['recaptcha_secret_key']) ?>" required>
                            </div>
                            
                            <h5 class="mb-3 mt-4">Configuración SMTP (Correos)</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($configuracion['smtp_host']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="text" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($configuracion['smtp_port']) ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="smtp_user" class="form-label">SMTP Usuario</label>
                                <input type="text" class="form-control" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($configuracion['smtp_user']) ?>">
                            </div>
                            <div class="mb-3">
                                <label for="smtp_pass" class="form-label">SMTP Contraseña</label>
                                <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" value="<?= htmlspecialchars($configuracion['smtp_pass']) ?>">
                            </div>
                            
                            <h5 class="mb-3 mt-4">Seguridad</h5>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="twofa_enabled" name="twofa_enabled" <?= $configuracion['twofa_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="twofa_enabled">Habilitar autenticación de dos factores (2FA)</label>
                            </div>
                            
                            <div class="text-end mt-4">
                                <button type="submit" name="save_settings" class="btn btn-primary">Guardar Configuración</button>
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