<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar sesión de administrador
checkAdminSession();

$error = '';
$success = '';

try {
    $db = Database::getInstance();

    // Procesar bloqueo de número
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateCSRFToken($_POST['csrf_token']);

        if (isset($_POST['block_number'])) {
            $phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
            $blockMinutes = filter_input(INPUT_POST, 'block_minutes', FILTER_VALIDATE_INT);

            if (empty($phoneNumber) || !$blockMinutes || $blockMinutes < 1) {
                throw new Exception('Por favor ingrese un número válido y tiempo de bloqueo');
            }

            // Verificar si ya está bloqueado
            $existing = $db->query(
                "SELECT * FROM blocked_numbers WHERE phone_number = ? AND block_until > NOW()",
                [$phoneNumber]
            )->fetch();

            if ($existing) {
                throw new Exception('Este número ya está bloqueado');
            }

            // Bloquear número
            $blockUntil = date('Y-m-d H:i:s', strtotime("+{$blockMinutes} minutes"));
            $db->insert('blocked_numbers', [
                'phone_number' => $phoneNumber,
                'block_until' => $blockUntil
            ]);

            logAdminActivity('block_number', "Número: $phoneNumber, Duración: $blockMinutes minutos");
            $success = 'Número bloqueado exitosamente';
        }

        // Desbloquear número
        if (isset($_POST['unblock_number'])) {
            $blockId = filter_input(INPUT_POST, 'block_id', FILTER_VALIDATE_INT);
            
            $db->delete('blocked_numbers', 'id = ?', [$blockId]);
            
            logAdminActivity('unblock_number', "Block ID: $blockId");
            $success = 'Número desbloqueado exitosamente';
        }
    }

    // Obtener números bloqueados activos
    $blockedNumbers = $db->query(
        "SELECT * FROM blocked_numbers 
         WHERE block_until > NOW() 
         ORDER BY created_at DESC"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Error en gestión de números bloqueados: " . $e->getMessage());
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
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Block Number Form -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Bloquear Nuevo Número</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="block_number" value="1">

                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Número de Teléfono
                        </label>
                        <input type="text" id="phone_number" name="phone_number" required
                               placeholder="Ej: 123456789"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="block_minutes" class="block text-sm font-medium text-gray-700 mb-2">
                            Tiempo de Bloqueo (minutos)
                        </label>
                        <input type="number" id="block_minutes" name="block_minutes" required
                               min="1" value="60"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-ban mr-2"></i>
                            Bloquear Número
                        </button>
                    </div>
                </form>
            </div>

            <!-- Blocked Numbers List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b">
                    <h3 class="text-lg font-semibold text-gray-800">Números Actualmente Bloqueados</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Número
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Bloqueado Hasta
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tiempo Restante
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($blockedNumbers)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                        No hay números bloqueados actualmente
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($blockedNumbers as $block): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($block['phone_number']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($block['block_until'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $remaining = strtotime($block['block_until']) - time();
                                            $minutes = round($remaining / 60);
                                            ?>
                                            <div class="text-sm text-gray-500">
                                                <?php echo $minutes; ?> minutos
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form method="POST" class="inline-block" 
                                                  onsubmit="return confirm('¿Está seguro de desbloquear este número?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="unblock_number" value="1">
                                                <input type="hidden" name="block_id" value="<?php echo $block['id']; ?>">
                                                <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-unlock"></i>
                                                    Desbloquear
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

    <script>
        // Actualizar tiempo restante cada minuto
        setInterval(function() {
            document.querySelectorAll('tr').forEach(function(row) {
                const timeCell = row.querySelector('td:nth-child(3)');
                if (timeCell) {
                    const minutes = parseInt(timeCell.textContent);
                    if (!isNaN(minutes) && minutes > 1) {
                        timeCell.querySelector('div').textContent = (minutes - 1) + ' minutos';
                    }
                }
            });
        }, 60000);
    </script>
</body>
</html>