<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar sesión de administrador
checkAdminSession();

try {
    $db = Database::getInstance();
    
    // Obtener estadísticas
    $stats = [
        'total_raffles' => $db->query("SELECT COUNT(*) as count FROM raffles")->fetch()['count'],
        'active_raffles' => $db->query("SELECT COUNT(*) as count FROM raffles WHERE status = 'active'")->fetch()['count'],
        'total_reservations' => $db->query("SELECT COUNT(*) as count FROM reservations")->fetch()['count'],
        'pending_payments' => $db->query("SELECT COUNT(*) as count FROM tickets WHERE payment_status = 'pending'")->fetch()['count']
    ];

    // Obtener últimas reservas
    $latest_reservations = $db->query(
        "SELECT r.*, t.ticket_number, rf.title as raffle_title 
         FROM reservations r 
         JOIN tickets t ON r.ticket_id = t.id 
         JOIN raffles rf ON t.raffle_id = rf.id 
         ORDER BY r.created_at DESC 
         LIMIT 5"
    )->fetchAll();

} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    $error = "Error al cargar los datos del dashboard";
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($siteName); ?></title>
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
                <a href="index.php" class="flex items-center px-4 py-3 bg-blue-900">
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
                <h2 class="text-2xl font-semibold text-gray-800">Dashboard</h2>
                <div class="flex items-center">
                    <span class="text-gray-600 mr-2">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Rifas -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-ticket-alt fa-2x"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500">Total Rifas</p>
                            <p class="text-2xl font-semibold"><?php echo $stats['total_raffles']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Rifas Activas -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500">Rifas Activas</p>
                            <p class="text-2xl font-semibold"><?php echo $stats['active_raffles']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Reservas -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-bookmark fa-2x"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500">Total Reservas</p>
                            <p class="text-2xl font-semibold"><?php echo $stats['total_reservations']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Pagos Pendientes -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500">Pagos Pendientes</p>
                            <p class="text-2xl font-semibold"><?php echo $stats['pending_payments']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Reservations -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h3 class="text-xl font-semibold">Últimas Reservas</h3>
                </div>
                <div class="p-6">
                    <?php if (empty($latest_reservations)): ?>
                        <p class="text-gray-500 text-center py-4">No hay reservas recientes</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cliente
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rifa
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Boleto
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($latest_reservations as $reservation): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($reservation['customer_name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($reservation['customer_phone']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($reservation['raffle_title']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    #<?php echo htmlspecialchars($reservation['ticket_number']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusClasses = [
                                                    'reserved' => 'bg-yellow-100 text-yellow-800',
                                                    'cancelled' => 'bg-red-100 text-red-800',
                                                    'confirmed' => 'bg-green-100 text-green-800'
                                                ];
                                                $statusText = [
                                                    'reserved' => 'Reservado',
                                                    'cancelled' => 'Cancelado',
                                                    'confirmed' => 'Confirmado'
                                                ];
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $statusClasses[$reservation['status']]; ?>">
                                                    <?php echo $statusText[$reservation['status']]; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>