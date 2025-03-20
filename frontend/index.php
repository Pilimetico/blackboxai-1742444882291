<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    $db = Database::getInstance();
    // Get active raffles with ticket counts
    $raffles = $db->query(
        "SELECT r.*, 
                COUNT(DISTINCT t.id) as total_tickets,
                COUNT(DISTINCT CASE WHEN t.payment_status = 'paid' THEN t.id END) as sold_tickets
         FROM raffles r
         LEFT JOIN tickets t ON r.id = t.raffle_id
         WHERE r.status = 'active'
         GROUP BY r.id
         ORDER BY r.created_at DESC"
    )->fetchAll();
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $raffles = [];
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
$logo = getSetting('logo_path') ?? 'assets/img/default-logo.png';
$bannerImage = getSetting('banner_path') ?? 'assets/img/hero-bg.jpg';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de rifas en línea con reserva de boletos y confirmación por WhatsApp">
    <meta name="theme-color" content="#3B82F6">
    <title><?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="apple-touch-icon" href="assets/img/favicon.svg">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm fixed w-full top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center">
                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="Logo" class="h-12">
                    <span class="ml-3 text-xl font-semibold text-gray-800">
                        <?php echo htmlspecialchars($siteName); ?>
                    </span>
                </a>
                <nav class="hidden md:flex space-x-6">
                    <a href="#rifas-activas" class="text-gray-600 hover:text-blue-600 transition-colors">
                        Rifas Activas
                    </a>
                    <a href="#como-participar" class="text-gray-600 hover:text-blue-600 transition-colors">
                        Cómo Participar
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section flex items-center justify-center text-white text-center pt-24">
        <div class="container mx-auto px-4 py-20">
            <h1 class="text-4xl md:text-6xl font-bold mb-4">Participa y Gana</h1>
            <p class="text-xl md:text-2xl mb-8">Explora nuestras rifas activas y elige tu número de la suerte</p>
            <a href="#rifas-activas" 
               class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 
                      transition-colors text-lg font-semibold">
                Ver Rifas Disponibles
            </a>
        </div>
    </section>

    <!-- How to Participate -->
    <section id="como-participar" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Cómo Participar</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-search text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">1. Elige tu Rifa</h3>
                    <p class="text-gray-600">Explora nuestras rifas activas y selecciona la que más te guste</p>
                </div>
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-ticket-alt text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">2. Reserva tu Número</h3>
                    <p class="text-gray-600">Selecciona tu número preferido y realiza la reserva</p>
                </div>
                <div class="text-center">
                    <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fab fa-whatsapp text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">3. Confirma por WhatsApp</h3>
                    <p class="text-gray-600">Completa tu participación confirmando a través de WhatsApp</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Active Raffles -->
    <section id="rifas-activas" class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Rifas Activas</h2>
            
            <?php if (empty($raffles)): ?>
                <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                    <i class="fas fa-ticket-alt text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 text-xl">No hay rifas disponibles en este momento.</p>
                    <p class="text-gray-500 mt-2">¡Vuelve pronto para nuevas oportunidades!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($raffles as $raffle): ?>
                        <div class="raffle-card bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($raffle['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($raffle['title']); ?>"
                                     class="w-full h-48 object-cover">
                                <?php if (!empty($raffle['tags'])): ?>
                                    <div class="absolute top-4 right-4 flex flex-wrap gap-2">
                                        <?php foreach (explode(',', $raffle['tags']) as $tag): ?>
                                            <?php
                                            $tagClass = match (trim(strtolower($tag))) {
                                                'nuevo sorteo' => 'tag-new',
                                                'vendidas' => 'tag-sold',
                                                'descuentos' => 'tag-discount',
                                                default => 'bg-blue-100 text-blue-800'
                                            };
                                            ?>
                                            <span class="tag <?php echo $tagClass; ?> text-xs px-2 py-1 rounded-full">
                                                <?php echo htmlspecialchars(trim($tag)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-6">
                                <h3 class="text-xl font-semibold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($raffle['title']); ?>
                                </h3>
                                
                                <p class="text-gray-600 mb-4">
                                    <?php echo nl2br(htmlspecialchars($raffle['description'])); ?>
                                </p>

                                <div class="flex justify-between items-center mb-4 text-sm">
                                    <span class="text-gray-500">
                                        <i class="fas fa-ticket-alt mr-1"></i>
                                        Vendidos: <?php echo $raffle['sold_tickets']; ?>/<?php echo $raffle['total_tickets']; ?>
                                    </span>
                                    <span class="text-gray-500">
                                        <i class="far fa-clock mr-1"></i>
                                        <?php echo date('d/m/Y', strtotime($raffle['created_at'])); ?>
                                    </span>
                                </div>
                                
                                <button onclick="openReservationModal(<?php echo $raffle['id']; ?>)" 
                                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 
                                               transition-colors flex items-center justify-center font-semibold">
                                    <i class="fas fa-ticket-alt mr-2"></i>
                                    Reservar Boleto
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h4 class="text-lg font-semibold mb-4">Sobre Nosotros</h4>
                    <p class="text-gray-400">
                        Sistema de rifas en línea con reserva de boletos y confirmación por WhatsApp.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Enlaces Rápidos</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="#rifas-activas" class="text-gray-400 hover:text-white transition-colors">
                                Rifas Activas
                            </a>
                        </li>
                        <li>
                            <a href="#como-participar" class="text-gray-400 hover:text-white transition-colors">
                                Cómo Participar
                            </a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contacto</h4>
                    <p class="text-gray-400">
                        <i class="fab fa-whatsapp mr-2"></i>
                        WhatsApp para reservas
                    </p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Reservation Modal -->
    <div id="reservationModal" class="fixed inset-0 bg-black bg-opacity-50 modal-backdrop hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 transform transition-all">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-semibold">Reservar Boleto</h3>
                <button onclick="closeReservationModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="reservationForm" onsubmit="submitReservation(event)" class="space-y-6">
                <input type="hidden" name="raffle_id" id="raffle_id">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="name">
                        Nombre Completo
                    </label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                                  transition-colors">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="phone">
                        Teléfono
                    </label>
                    <input type="tel" id="phone" name="phone" required
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                                  transition-colors">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="email">
                        Email (opcional)
                    </label>
                    <input type="email" id="email" name="email"
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                                  transition-colors">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2" for="ticket_number">
                        Número de Boleto
                    </label>
                    <input type="number" id="ticket_number" name="ticket_number" required min="1"
                           class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                                  transition-colors ticket-number-input">
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 
                               transition-colors flex items-center justify-center font-semibold">
                    <i class="fas fa-check mr-2"></i>
                    Confirmar Reserva
                    <span id="submitSpinner" class="loading-spinner ml-2 hidden"></span>
                </button>
            </form>
        </div>
    </div>

    <!-- Custom JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>