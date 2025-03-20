<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

checkAdminSession();

$error = '';
$success = '';

try {
    $db = Database::getInstance();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateCSRFToken($_POST['csrf_token']);

        if (isset($_POST['update_settings'])) {
            // Site settings
            $siteName = filter_input(INPUT_POST, 'site_name', FILTER_SANITIZE_STRING);
            updateSetting('site_name', $siteName);

            // WhatsApp settings
            $countryCode = filter_input(INPUT_POST, 'country_code', FILTER_SANITIZE_STRING);
            $adminWhatsapp = filter_input(INPUT_POST, 'admin_whatsapp', FILTER_SANITIZE_STRING);
            $messageTemplate = filter_input(INPUT_POST, 'whatsapp_message_template', FILTER_SANITIZE_STRING);

            updateSetting('country_code', $countryCode);
            updateSetting('admin_whatsapp', $adminWhatsapp);
            updateSetting('whatsapp_message_template', $messageTemplate);

            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload(
                    $_FILES['logo'],
                    'uploads/system',
                    ['image/jpeg', 'image/png', 'image/svg+xml']
                );

                if ($uploadResult['success']) {
                    updateSetting('logo_path', $uploadResult['path']);
                } else {
                    throw new Exception('Error al subir el logo: ' . $uploadResult['error']);
                }
            }

            // Handle banner upload
            if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = handleFileUpload(
                    $_FILES['banner'],
                    'uploads/system',
                    ['image/jpeg', 'image/png']
                );

                if ($uploadResult['success']) {
                    updateSetting('banner_path', $uploadResult['path']);
                } else {
                    throw new Exception('Error al subir el banner: ' . $uploadResult['error']);
                }
            }

            $success = 'Configuración actualizada exitosamente';
            logAdminActivity('update_settings', "Updated system settings");
        }
    }

    // Get current settings
    $settings = [
        'site_name' => getSetting('site_name'),
        'logo_path' => getSetting('logo_path'),
        'banner_path' => getSetting('banner_path'),
        'country_code' => getSetting('country_code'),
        'admin_whatsapp' => getSetting('admin_whatsapp'),
        'whatsapp_message_template' => getSetting('whatsapp_message_template')
    ];

} catch (Exception $e) {
    error_log("Error in settings: " . $e->getMessage());
    $error = $e->getMessage();
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="bg-blue-800 text-white w-64 flex-shrink-0">
            <div class="p-4">
                <h1 class="text-lg font-semibold"><?php echo htmlspecialchars($siteName); ?></h1>
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
                <a href="file_manager.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-folder w-6"></i>
                    <span>Gestor de Archivos</span>
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
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Settings Form -->
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="update_settings" value="1">

                <!-- Site Settings -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Configuración General</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Sitio
                            </label>
                            <input type="text" id="site_name" name="site_name" 
                                   value="<?php echo htmlspecialchars($settings['site_name']); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Logo
                            </label>
                            <div class="flex items-center space-x-4">
                                <?php if (!empty($settings['logo_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" 
                                         alt="Logo actual" 
                                         class="h-12 object-contain">
                                <?php endif; ?>
                                <input type="file" name="logo" accept="image/*"
                                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Banner Principal
                            </label>
                            <div class="flex items-center space-x-4">
                                <?php if (!empty($settings['banner_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($settings['banner_path']); ?>" 
                                         alt="Banner actual" 
                                         class="h-20 object-cover rounded">
                                <?php endif; ?>
                                <input type="file" name="banner" accept="image/*"
                                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Settings -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold mb-4">Configuración de WhatsApp</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="country_code" class="block text-sm font-medium text-gray-700 mb-2">
                                Código de País
                            </label>
                            <input type="text" id="country_code" name="country_code" 
                                   value="<?php echo htmlspecialchars($settings['country_code']); ?>"
                                   placeholder="Ej: 34"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="admin_whatsapp" class="block text-sm font-medium text-gray-700 mb-2">
                                Número de WhatsApp
                            </label>
                            <input type="text" id="admin_whatsapp" name="admin_whatsapp" 
                                   value="<?php echo htmlspecialchars($settings['admin_whatsapp']); ?>"
                                   placeholder="Sin código de país"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="whatsapp_message_template" class="block text-sm font-medium text-gray-700 mb-2">
                            Plantilla de Mensaje
                        </label>
                        <textarea id="whatsapp_message_template" name="whatsapp_message_template" rows="4"
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php 
                            echo htmlspecialchars($settings['whatsapp_message_template']); 
                        ?></textarea>
                        <p class="mt-2 text-sm text-gray-500">
                            Variables disponibles: {customer_name}, {customer_phone}, {ticket_number}, {raffle_title}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 
                                   transition-colors flex items-center">
                        <i class="fas fa-save mr-2"></i>
                        Guardar Configuración
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Preview uploaded images
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const img = this.previousElementSibling?.querySelector('img');
                    if (img) {
                        img.src = URL.createObjectURL(this.files[0]);
                    }
                }
            });
        });
    </script>
</body>
</html>