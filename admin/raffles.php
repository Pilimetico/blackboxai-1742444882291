<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar sesión de administrador
checkAdminSession();

$error = '';
$success = '';

try {
    $db = Database::getInstance();

    // Eliminar rifa si se solicita
    if (isset($_POST['delete_raffle']) && isset($_POST['raffle_id'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $raffleId = filter_input(INPUT_POST, 'raffle_id', FILTER_VALIDATE_INT);
        
        // Eliminar la imagen asociada
        $raffle = $db->query("SELECT image FROM raffles WHERE id = ?", [$raffleId])->fetch();
        if ($raffle && !empty($raffle['image'])) {
            $imagePath = __DIR__ . '/../' . $raffle['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Eliminar la rifa
        $db->delete('raffles', 'id = ?', [$raffleId]);
        $success = 'Rifa eliminada exitosamente';
        
        logAdminActivity('delete_raffle', "Rifa ID: $raffleId");
    }

    // Cambiar estado de la rifa
    if (isset($_POST['toggle_status']) && isset($_POST['raffle_id'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $raffleId = filter_input(INPUT_POST, 'raffle_id', FILTER_VALIDATE_INT);
        $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
        
        $db->update('raffles', 
            ['status' => $newStatus],
            'id = ?',
            [$raffleId]
        );
        
        $success = 'Estado de la rifa actualizado';
        
        logAdminActivity('toggle_raffle_status', "Rifa ID: $raffleId, Nuevo estado: $newStatus");
    }

    // Obtener todas las rifas
    $raffles = $db->query(
        "SELECT r.*, 
                (SELECT COUNT(*) FROM tickets t WHERE t.raffle_id = r.id) as total_tickets,
                (SELECT COUNT(*) FROM tickets t WHERE t.raffle_id = r.id AND t.payment_status = 'paid') as paid_tickets
         FROM raffles r 
         ORDER BY r.created_at DESC"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Error en gestión de rifas: " . $e->getMessage());
    $error = "Error al procesar la solicitud";
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Rifas - <?php echo htmlspecialchars($siteName); ?></title>
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
                <a href="raffles.php" class="flex items-center px-4 py-3 bg-blue-900">
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
                <h2 class="text-2xl font-semibold text-gray-800">Gestión de Rifas</h2>
                <a href="raffle_edit.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Nueva Rifa
                </a>
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

            <!-- Raffles List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rifa
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Boletos
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Etiquetas
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($raffles)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No hay rifas registradas
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($raffles as $raffle): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <?php if (!empty($raffle['image'])): ?>
                                                    <img class="h-10 w-10 rounded-full object-cover" 
                                                         src="<?php echo htmlspecialchars($raffle['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($raffle['title']); ?>">
                                                <?php endif; ?>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($raffle['title']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="raffle_id" value="<?php echo $raffle['id']; ?>">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="new_status" 
                                                       value="<?php echo $raffle['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="<?php echo $raffle['status'] === 'active' 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-gray-100 text-gray-800'; ?> px-2 inline-flex text-xs leading-5 
                                                    font-semibold rounded-full">
                                                    <?php echo $raffle['status'] === 'active' ? 'Activa' : 'Inactiva'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo $raffle['paid_tickets']; ?> / <?php echo $raffle['total_tickets']; ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                Pagados / Total
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($raffle['tags'])): ?>
                                                <?php foreach (explode(',', $raffle['tags']) as $tag): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars(trim($tag)); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($raffle['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="raffle_edit.php?id=<?php echo $raffle['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="inline" 
                                                  onsubmit="return confirm('¿Está seguro de eliminar esta rifa?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="raffle_id" value="<?php echo $raffle['id']; ?>">
                                                <input type="hidden" name="delete_raffle" value="1">
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