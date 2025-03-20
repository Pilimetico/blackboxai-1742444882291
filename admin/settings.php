<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar sesión de administrador
checkAdminSession();

$error = '';
$success = '';

try {
    $db = Database::getInstance();

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateCSRFToken($_POST['csrf_token']);

        // Actualizar configuración general
        if (isset($_POST['update_settings'])) {
            $siteName = filter_input(INPUT_POST, 'site_name', FILTER_SANITIZE_STRING);
            $whatsappTemplate = filter_input(INPUT_POST, 'whatsapp_template', FILTER_SANITIZE_STRING);

            // Actualizar nombre del sitio
            updateSetting('site_name', $siteName);
            
            // Actualizar plantilla de WhatsApp
            updateSetting('whatsapp_message_template', $whatsappTemplate);

            // Procesar logo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload(
                    $_FILES['logo'],
                    'uploads/settings',
                    ['image/jpeg', 'image/png', 'image/gif']
                );

                if ($uploadResult['success']) {
                    updateSetting('logo_path', $uploadResult['path']);
                } else {
                    throw new Exception('Error al subir el logo: ' . $uploadResult['error']);
                }
            }

            // Procesar banner
            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload(
                    $_FILES['banner'],
                    'uploads/settings',
                    ['image/jpeg', 'image/png', 'image/gif']
                );

                if ($uploadResult['success']) {
                    updateSetting('banner_path', $uploadResult['path']);
                } else {
                    throw new Exception('Error al subir el banner: ' . $uploadResult['error']);
                }
            }

            // Procesar favicon
            if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload(
                    $_FILES['favicon'],
                    'uploads/settings',
                    ['image/x-icon', 'image/png']
                );

                if ($uploadResult['success']) {
                    updateSetting('favicon_path', $uploadResult['path']);
                } else {
                    throw new Exception('Error al subir el favicon: ' . $uploadResult['error']);
                }
            }

            logAdminActivity('update_settings', 'Configuración actualizada');
            $success = 'Configuración actualizada exitosamente';
        }

        // Cambiar contraseña
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            if ($newPassword !== $confirmPassword) {
                throw new Exception('Las contraseñas nuevas no coinciden');
            }

            // Verificar contraseña actual
            $admin = $db->query(
                "SELECT * FROM admin_users WHERE id = ? LIMIT 1",
                [$_SESSION['admin_id']]
            )->fetch();

            if (!password_verify($currentPassword, $admin['password'])) {
                throw new Exception('La contraseña actual es incorrecta');
            }

            // Actualizar contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->update(
                'admin_users',
                ['password' => $hashedPassword],
                'id = ?',
                [$_SESSION['admin_id']]
            );

            logAdminActivity('change_password', 'Contraseña actualizada');
            $success = 'Contraseña actualizada exitosamente';
        }
    }

    // Obtener configuración actual
    $settings = [
        'site_name' => getSetting('site_name'),
        'logo_path' => getSetting('logo_path'),
        'banner_path' => getSetting('banner_path'),
        'favicon_path' => getSetting('favicon_path'),
        'whatsapp_template' => getSetting('whatsapp_message_template')
    ];

} catch (Exception $e) {
    error_log("Error en configuración: " . $e->getMessage());
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - <?php echo htmlspecialchars($settings['site_name']); ?></title>
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
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="bg-blue-800 text-white w-64 flex-shrink-0">
            <div class="p-4">
                <h1 class="text-lg font-semibold"><?php echo htmlspecialchars($settings['site_name']); ?></h1>
                <p class="text-sm text-blue-200">Panel de Administración</p>
            </div>
            
            <nav class="mt-4">
                <a href="index.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-tachometer-alt w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="raffles.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-ticket-alt w-6"></i>
                    <span>Rifas</span>
                </a>
                <a href="reservations.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-bookmark w-6"></i>
                    <span>Reservas</span>
                </a>
                <a href="settings.php" class="flex items-center px-4 py-3 bg-blue-900">
                    <i class="fas fa-cog w-6"></i>
                    <span>Configuración</span>
                </a>
                <a href="blocked_numbers.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-ban w-6"></i>
                    <span>Números Bloqueados</span>
                </a>
                <a href="logout.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors mt-auto">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800">Configuración del Sistema</h2>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- General Settings -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Configuración General</h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="update_settings" value="1">

                    <div>
                        <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre del Sitio
                        </label>
                        <input type="text" id="site_name" name="site_name" required
                               value="<?php echo htmlspecialchars($settings['site_name']); ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="whatsapp_template" class="block text-sm font-medium text-gray-700 mb-2">
                            Plantilla de Mensaje WhatsApp
                        </label>
                        <textarea id="whatsapp_template" name="whatsapp_template" rows="4" required
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php 
                            echo htmlspecialchars($settings['whatsapp_template']); 
                        ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">
                            Variables disponibles: {customer_name}, {customer_phone}, {ticket_number}, {raffle_title}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Logo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                            <?php if (!empty($settings['logo_path'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" 
                                         alt="Logo actual" 
                                         class="h-20 object-contain">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" accept="image/*"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Banner -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Banner</label>
                            <?php if (!empty($settings['banner_path'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($settings['banner_path']); ?>" 
                                         alt="Banner actual" 
                                         class="h-20 object-contain">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="banner" accept="image/*"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Favicon -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                            <?php if (!empty($settings['favicon_path'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($settings['favicon_path']); ?>" 
                                         alt="Favicon actual" 
                                         class="h-8 object-contain">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="favicon" accept="image/x-icon,image/png"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Cambiar Contraseña</h3>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="change_password" value="1">

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Contraseña Actual
                        </label>
                        <input type="password" id="current_password" name="current_password" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Nueva Contraseña
                        </label>
                        <input type="password" id="new_password" name="new_password" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirmar Nueva Contraseña
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <i class="fas fa-key mr-2"></i>
                            Cambiar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>