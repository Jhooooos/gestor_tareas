<?php
require_once 'includes/config.php';

// Verificar si ya está logueado
if (isLoggedIn()) {
    redirect(isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php');
}

$error = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    // Verificar reCAPTCHA
    if (isset($_POST['g-recaptcha-response'])) {
        $recaptcha_secret = '6LfOLTIrAAAAAEYHeXTRbidK_ofGx28z6xtlrdmg'; // Tu clave secreta
        $recaptcha_response = $_POST['g-recaptcha-response'];

        // Usar cURL para mayor compatibilidad
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $recaptcha_result = curl_exec($ch);
        curl_close($ch);

        $recaptcha_json = json_decode($recaptcha_result);

        if (!$recaptcha_json->success) {
            $error = "Por favor, completa correctamente la verificación reCAPTCHA.";
        } else {
            // Buscar usuario en la base de datos
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Verificar si requiere 2FA
                if (!empty($user['two_factor_secret'])) {
                    $_SESSION['2fa_required'] = true;
                    $_SESSION['temp_user_id'] = $user['id'];
                    redirect('verify_2fa.php');
                } else {
                    // Iniciar sesión
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_rol'] = $user['rol'];
                    $_SESSION['user_nombre'] = $user['nombre'];
                    
                    redirect(isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php');
                }
            } else {
                $error = "Correo electrónico o contraseña incorrectos.";
            }
        }
    } else {
        $error = "Por favor, completa la verificación reCAPTCHA.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="bg-dark text-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h3>Iniciar Sesión</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form action="login.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <!-- reCAPTCHA -->
                                <div class="g-recaptcha" data-sitekey="6LfOLTIrAAAAAKVsgFkvVvR7HrqvmDPx3_H8yOZg"></div>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                        <hr>
                        <div class="text-center">
                            <a href="register.php" class="text-decoration-none">¿No tienes cuenta? Regístrate</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
