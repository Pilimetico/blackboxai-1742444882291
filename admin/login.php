<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

// Si ya hay una sesión activa, redirigir al dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            throw new Exception('Por favor complete todos los campos');
        }

        $db = Database::getInstance();
        $admin = $db->query(
            "SELECT * FROM admin_users WHERE username = ? LIMIT 1",
            [$username]
        )->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            throw new Exception('Usuario o contraseña incorrectos');
        }

        // Iniciar sesión
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        // Registrar actividad
        logAdminActivity('login', 'Inicio de sesión exitoso');

        // Redirigir al dashboard
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error de login: " . $e->getMessage());
    }
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Panel de Administración</h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($siteName); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Usuario
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" id="username" name="username" required
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md 
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" id="password" name="password" required
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md 
                                      focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full flex justify-center items-center px-4 py-2 border border-transparent 
                               rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 
                               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Iniciar Sesión
                </button>
            </form>
        </div>

        <div class="text-center mt-4">
            <a href="../" class="text-sm text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-1"></i>
                Volver al inicio
            </a>
        </div>
    </div>

    <script>
        // Animación de fade-in
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('opacity-0');
            setTimeout(() => {
                document.body.classList.remove('opacity-0');
                document.body.classList.add('transition-opacity', 'duration-500', 'opacity-100');
            }, 100);
        });
    </script>
</body>
</html>