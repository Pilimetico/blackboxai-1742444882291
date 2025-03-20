<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'includes/block_settings.php';

checkAdminSession();

$error = '';
$success = '';

try {
    $db = Database::getInstance();
    
    // Clean expired blocks
    cleanExpiredBlocks();

    // Update block settings
    if (isset($_POST['update_settings'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $blockEnabled = isset($_POST['block_enabled']);
        $blockDuration = filter_input(INPUT_POST, 'block_duration', FILTER_VALIDATE_INT);
        
        if ($blockDuration < 1) {
            throw new Exception('La duración debe ser mayor a 0 minutos');
        }

        if (updateBlockSettings($blockEnabled, $blockDuration)) {
            $success = 'Configuración actualizada exitosamente';
            logAdminActivity('update_block_settings', "Enabled: $blockEnabled, Duration: $blockDuration");
        } else {
            throw new Exception('Error al actualizar la configuración');
        }
    }

    // Add new block
    if (isset($_POST['add_block'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $duration = filter_input(INPUT_POST, 'duration', FILTER_VALIDATE_INT);
        
        if (empty($phone) || !$duration) {
            throw new Exception('Número de teléfono y duración son requeridos');
        }

        if (blockPhone($phone, $duration)) {
            $success = 'Número bloqueado exitosamente';
            logAdminActivity('add_block', "Phone: $phone, Duration: $duration");
        } else {
            throw new Exception('Error al bloquear el número');
        }
    }

    // Remove block
    if (isset($_POST['remove_block'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $blockId = filter_input(INPUT_POST, 'block_id', FILTER_VALIDATE_INT);
        
        $db->delete('blocked_numbers', 'id = ?', [$blockId]);
        $success = 'Bloqueo eliminado exitosamente';
        logAdminActivity('remove_block', "Block ID: $blockId");
    }

    // Get current settings
    $settings = getBlockSettings();

    // Get active blocks
    $blocks = $db->query(
        "SELECT * FROM blocked_numbers WHERE block_until > NOW() ORDER BY created_at DESC"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Error in blocked numbers: " . $e->getMessage());
    $error = $e->getMessage();
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Números Bloqueados - <?php echo htmlspecialchars($siteName); ?></title>
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
                <a href="settings.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-cog w-6"></i>
                    <span>Configuración</span>
                </a>
                <a href="blocked_numbers.php" class="flex items-center px-4 py-3 bg-blue-900">
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
                <h2 class="text-2xl font-semibold text-gray-800">Números Bloqueados</h2>
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

            <!-- Block Settings -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Configuración de Bloqueo</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="update_settings" value="1">

                    <div class="flex items-center space-x-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="block_enabled" 
                                   <?php echo $settings['block_enabled'] ? 'checked' : ''; ?>
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2">Bloquear números temporalmente</span>
                        </label>

                        <div class="flex items-center space-x-2">
                            <input type="number" name="block_duration" 
                                   value="<?php echo $settings['block_duration']; ?>"
                                   min="1" required
                                   class="w-20 px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span>minutos</span>
                        </div>

                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>

            <!-- Add New Block -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Bloquear Nuevo Número</h3>
                <form method="POST" class="flex space-x-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_block" value="1">

                    <div class="flex-1">
                        <input type="text" name="phone" required placeholder="Número de teléfono"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="w-32">
                        <input type="number" name="duration" required placeholder="Minutos" min="1"
                               value="<?php echo $settings['block_duration']; ?>"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Bloquear Número
                    </button>
                </form>
            </div>

            <!-- Active Blocks -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold">Bloqueos Activos</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bloqueado hasta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($blocks)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                        No hay números bloqueados actualmente
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($blocks as $block): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo htmlspecialchars($block['phone_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo date('d/m/Y H:i', strtotime($block['block_until'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo date('d/m/Y H:i', strtotime($block['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <form method="POST" class="inline-block" 
                                                  onsubmit="return confirm('¿Está seguro de eliminar este bloqueo?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="block_id" value="<?php echo $block['id']; ?>">
                                                <input type="hidden" name="remove_block" value="1">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>