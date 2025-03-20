<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'includes/ticket_filters.php';

checkAdminSession();

$error = '';
$success = '';
$raffle = null;
$tickets = [];
$selectedRange = null;

try {
    $db = Database::getInstance();

    // Get raffle details
    if (!isset($_GET['id'])) {
        header('Location: raffles.php');
        exit();
    }

    $raffleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $raffle = $db->query(
        "SELECT * FROM raffles WHERE id = ? LIMIT 1",
        [$raffleId]
    )->fetch();

    if (!$raffle) {
        header('Location: raffles.php');
        exit();
    }

    // Get ticket ranges
    $ranges = getTicketRanges($raffleId);

    // Get tickets for selected range
    if (isset($_GET['range_start']) && isset($_GET['range_end'])) {
        $start = filter_input(INPUT_GET, 'range_start', FILTER_VALIDATE_INT);
        $end = filter_input(INPUT_GET, 'range_end', FILTER_VALIDATE_INT);
        $tickets = getTicketsInRange($raffleId, $start, $end);
        $selectedRange = "$start-$end";
    }

} catch (Exception $e) {
    error_log("Error in raffle view: " . $e->getMessage());
    $error = "Error al cargar los datos de la rifa";
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Rifa - <?php echo htmlspecialchars($siteName); ?></title>
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
                <h2 class="text-2xl font-semibold text-gray-800">
                    <?php echo htmlspecialchars($raffle['title']); ?>
                </h2>
                <div class="flex space-x-4">
                    <a href="raffle_edit.php?id=<?php echo $raffle['id']; ?>" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>
                        Editar Rifa
                    </a>
                    <a href="raffles.php" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Raffle Details -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Detalles de la Rifa</h3>
                        <div class="space-y-2">
                            <p><strong>Estado:</strong> 
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $raffle['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $raffle['status'] === 'active' ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </p>
                            <p><strong>Fecha de Creación:</strong> 
                                <?php echo date('d/m/Y H:i', strtotime($raffle['created_at'])); ?>
                            </p>
                            <?php if (!empty($raffle['tags'])): ?>
                                <p><strong>Etiquetas:</strong></p>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (explode(',', $raffle['tags']) as $tag): ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                            <?php echo htmlspecialchars(trim($tag)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($raffle['image'])): ?>
                        <div>
                            <img src="<?php echo htmlspecialchars($raffle['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($raffle['title']); ?>"
                                 class="w-full h-48 object-cover rounded-lg">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ticket Ranges -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Filtrar por Rango de Boletos</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($ranges as $range): ?>
                        <a href="?id=<?php echo $raffle['id']; ?>&range_start=<?php echo $range['start']; ?>&range_end=<?php echo $range['end']; ?>"
                           class="px-4 py-2 text-center rounded-lg border <?php echo $selectedRange === $range['label'] 
                               ? 'bg-blue-100 border-blue-500 text-blue-700' 
                               : 'hover:bg-gray-50'; ?>">
                            <?php echo $range['label']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tickets List -->
            <?php if (!empty($tickets)): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pago</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                #<?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($ticket['reservation_status']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $ticket['reservation_status'] === 'confirmed' 
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo $ticket['reservation_status'] === 'confirmed' ? 'Confirmado' : 'Reservado'; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Disponible
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo $ticket['customer_name'] ? htmlspecialchars($ticket['customer_name']) : '-'; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $ticket['payment_status'] === 'paid'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo $ticket['payment_status'] === 'paid' ? 'Pagado' : 'Pendiente'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($selectedRange): ?>
                <div class="text-center py-8 text-gray-500">
                    No hay boletos en este rango
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>