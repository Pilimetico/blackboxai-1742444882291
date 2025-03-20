<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar sesión de administrador
checkAdminSession();

$error = '';
$success = '';

try {
    $db = Database::getInstance();

    // Procesar cambios de estado
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateCSRFToken($_POST['csrf_token']);

        if (isset($_POST['update_status'])) {
            $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
            $newStatus = filter_input(INPUT_POST, 'new_status', FILTER_SANITIZE_STRING);
            $ticketId = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
            
            // Iniciar transacción
            $db->beginTransaction();
            
            // Actualizar estado de la reserva
            $db->update('reservations', 
                ['status' => $newStatus],
                'id = ?',
                [$reservationId]
            );

            // Si se confirma el pago, actualizar el estado del ticket
            if ($newStatus === 'confirmed') {
                $db->update('tickets',
                    ['payment_status' => 'paid'],
                    'id = ?',
                    [$ticketId]
                );
            }

            $db->commit();
            $success = 'Estado actualizado correctamente';
            
            logAdminActivity('update_reservation_status', "Reserva ID: $reservationId, Nuevo estado: $newStatus");
        }
    }

    // Filtros
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $payment = isset($_GET['payment']) ? $_GET['payment'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    // Construir consulta
    $query = "SELECT r.*, t.ticket_number, t.payment_status, rf.title as raffle_title 
              FROM reservations r 
              JOIN tickets t ON r.ticket_id = t.id 
              JOIN raffles rf ON t.raffle_id = rf.id 
              WHERE 1=1";
    $params = [];

    if ($status) {
        $query .= " AND r.status = ?";
        $params[] = $status;
    }
    if ($payment) {
        $query .= " AND t.payment_status = ?";
        $params[] = $payment;
    }
    if ($search) {
        $query .= " AND (t.ticket_number LIKE ? OR r.customer_name LIKE ? OR r.customer_phone LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }

    $query .= " ORDER BY r.created_at DESC";
    
    // Ejecutar consulta
    $reservations = $db->query($query, $params)->fetchAll();

} catch (Exception $e) {
    error_log("Error en gestión de reservas: " . $e->getMessage());
    $error = "Error al procesar la solicitud";
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - <?php echo htmlspecialchars($siteName); ?></title>
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
                <a href="reservations.php" class="flex items-center px-4 py-3 bg-blue-900">
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
                <h2 class="text-2xl font-semibold text-gray-800">Gestión de Reservas</h2>
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

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado de Reserva
                        </label>
                        <select id="status" name="status" 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="reserved" <?php echo $status === 'reserved' ? 'selected' : ''; ?>>Reservado</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmado</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>

                    <div>
                        <label for="payment" class="block text-sm font-medium text-gray-700 mb-2">
                            Estado de Pago
                        </label>
                        <select id="payment" name="payment"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos</option>
                            <option value="paid" <?php echo $payment === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                            <option value="pending" <?php echo $payment === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                        </select>
                    </div>

                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                            Buscar
                        </label>
                        <input type="text" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Número, nombre o teléfono"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reservations List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cliente
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rifa / Boleto
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pago
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
                            <?php if (empty($reservations)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No se encontraron reservas
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($reservation['customer_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($reservation['customer_phone']); ?>
                                            </div>
                                            <?php if ($reservation['customer_email']): ?>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($reservation['customer_email']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($reservation['raffle_title']); ?>
                                            </div>
                                            <div class="text-sm font-medium text-blue-600">
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
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $reservation['payment_status'] === 'paid' 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo $reservation['payment_status'] === 'paid' ? 'Pagado' : 'Pendiente'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <form method="POST" class="inline-block">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="ticket_id" value="<?php echo $reservation['ticket_id']; ?>">
                                                
                                                <?php if ($reservation['status'] === 'reserved'): ?>
                                                    <button type="submit" name="new_status" value="confirmed"
                                                            class="text-green-600 hover:text-green-900 mr-3">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="submit" name="new_status" value="cancelled"
                                                            class="text-red-600 hover:text-red-900"
                                                            onclick="return confirm('¿Está seguro de cancelar esta reserva?')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
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