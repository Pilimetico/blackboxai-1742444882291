<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    $db = Database::getInstance();
    // Obtener rifas activas
    $raffles = $db->query(
        "SELECT * FROM raffles WHERE status = 'active' ORDER BY created_at DESC"
    )->fetchAll();
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $raffles = [];
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
$logo = getSetting('logo_path') ?? 'assets/img/default-logo.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($siteName); ?></title>
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
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center">
                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" class="h-12">
                    <span class="ml-3 text-xl font-semibold text-gray-800">
                        <?php echo htmlspecialchars($siteName); ?>
                    </span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <?php if (empty($raffles)): ?>
            <div class="text-center py-12">
                <i class="fas fa-ticket-alt text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">No hay rifas disponibles en este momento.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($raffles as $raffle): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition-transform hover:scale-105">
                        <img src="<?php echo htmlspecialchars($raffle['image']); ?>" 
                             alt="<?php echo htmlspecialchars($raffle['title']); ?>"
                             class="w-full h-48 object-cover">
                        
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h2 class="text-xl font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($raffle['title']); ?>
                                </h2>
                                <?php if (!empty($raffle['tags'])): ?>
                                    <?php foreach (explode(',', $raffle['tags']) as $tag): ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                            <?php echo htmlspecialchars(trim($tag)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-gray-600 mb-4">
                                <?php echo nl2br(htmlspecialchars($raffle['description'])); ?>
                            </p>
                            
                            <button onclick="openReservationModal(<?php echo $raffle['id']; ?>)" 
                                    class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 
                                           transition-colors flex items-center justify-center">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                Reservar Boleto
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Reservation Modal -->
    <div id="reservationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold">Reservar Boleto</h3>
                <button onclick="closeReservationModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="reservationForm" onsubmit="submitReservation(event)" class="space-y-4">
                <input type="hidden" name="raffle_id" id="raffle_id">
                
                <div>
                    <label class="block text-gray-700 mb-2" for="name">Nombre Completo</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2" for="phone">Teléfono</label>
                    <input type="tel" id="phone" name="phone" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2" for="email">Email (opcional)</label>
                    <input type="email" id="email" name="email"
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-gray-700 mb-2" for="ticket_number">Número de Boleto</label>
                    <input type="number" id="ticket_number" name="ticket_number" required min="1"
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 
                               transition-colors flex items-center justify-center">
                    <i class="fas fa-check mr-2"></i>
                    Confirmar Reserva
                </button>
            </form>
        </div>
    </div>

    <script>
        function openReservationModal(raffleId) {
            document.getElementById('raffle_id').value = raffleId;
            document.getElementById('reservationModal').classList.remove('hidden');
            document.getElementById('reservationModal').classList.add('flex');
        }

        function closeReservationModal() {
            document.getElementById('reservationModal').classList.add('hidden');
            document.getElementById('reservationModal').classList.remove('flex');
            document.getElementById('reservationForm').reset();
        }

        async function submitReservation(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('reserve.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('¡Reserva exitosa! Serás redirigido a WhatsApp para confirmar.');
                    window.location.href = result.whatsapp_url;
                    closeReservationModal();
                } else {
                    alert(result.error || 'Error al procesar la reserva');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la reserva');
            }
        }
    </script>
</body>
</html>