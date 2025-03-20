# Technical Implementation Guide

## Directory Structure
```
/
├── admin/                 # Panel de administración
│   ├── assets/           # Recursos del panel
│   ├── includes/         # Funciones específicas del admin
│   ├── templates/        # Plantillas del panel
│   └── index.php         # Dashboard principal
├── assets/               # Recursos públicos
│   ├── css/             # Estilos
│   ├── js/              # JavaScript
│   ├── uploads/         # Archivos subidos
│   └── images/          # Imágenes del sistema
├── includes/            # Núcleo del sistema
│   ├── config.php       # Configuración
│   ├── database.php     # Clase de base de datos
│   ├── functions.php    # Funciones generales
│   └── autoload.php     # Cargador de clases
├── installer/           # Sistema de instalación
│   ├── assets/         # Recursos del instalador
│   ├── includes/       # Funciones del instalador
│   └── index.php       # Instalador
└── public/             # Frontend público
    ├── templates/      # Plantillas frontend
    └── index.php       # Página principal
```

## Ejemplos de Implementación

### 1. Instalador (installer/index.php)
```php
<?php
class Installer {
    private $steps = [
        'requirements' => 'Verificar Requisitos',
        'database' => 'Configuración de Base de Datos',
        'admin' => 'Crear Administrador',
        'whatsapp' => 'Configuración de WhatsApp',
        'finish' => 'Finalizar Instalación'
    ];

    public function checkRequirements() {
        return [
            'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'gd' => extension_loaded('gd'),
            'curl' => extension_loaded('curl'),
            'writable' => is_writable('../includes')
        ];
    }

    public function createConfig($data) {
        $config = [
            'db' => [
                'host' => $data['db_host'],
                'name' => $data['db_name'],
                'user' => $data['db_user'],
                'pass' => $data['db_pass']
            ],
            'whatsapp' => [
                'country_code' => $data['country_code'],
                'number' => $data['whatsapp_number']
            ],
            'security' => [
                'key' => bin2hex(random_bytes(32))
            ]
        ];
        
        return file_put_contents(
            '../includes/config.php',
            '<?php return ' . var_export($config, true) . ';'
        );
    }
}
```

### 2. Sistema de Reservas (public/reserve.php)
```php
<?php
class ReservationSystem {
    private $db;
    private $blockDuration = 30; // minutos

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function reserveTickets($raffleId, $tickets, $customer) {
        try {
            $this->db->beginTransaction();

            // Verificar disponibilidad
            if (!$this->areTicketsAvailable($raffleId, $tickets)) {
                throw new Exception('Algunos boletos no están disponibles');
            }

            // Crear reserva
            $reservationId = $this->createReservation($customer);

            // Asignar boletos
            foreach ($tickets as $ticketNumber) {
                $this->assignTicket($reservationId, $raffleId, $ticketNumber);
            }

            // Bloquear temporalmente si está activado
            if ($this->isTemporaryBlockEnabled()) {
                $this->blockTickets($tickets, $this->blockDuration);
            }

            $this->db->commit();
            return $this->sendWhatsAppNotification($customer, $tickets);

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
```

### 3. Panel de Admin (admin/includes/RaffleManager.php)
```php
<?php
class RaffleManager {
    private $db;
    private $uploadDir = '../assets/uploads/raffles/';

    public function createRaffle($data, $image) {
        // Validar datos
        $this->validateRaffleData($data);

        // Procesar imagen
        $imagePath = $this->handleImageUpload($image);

        // Crear rifa
        $raffleId = $this->db->insert('raffles', [
            'title' => $data['title'],
            'description' => $data['description'],
            'image' => $imagePath,
            'total_tickets' => $data['total_tickets'],
            'price' => $data['price'],
            'tags' => json_encode($data['tags']),
            'status' => 'active'
        ]);

        // Generar boletos
        $this->generateTickets($raffleId, $data['total_tickets']);

        return $raffleId;
    }

    public function getRaffleStats($raffleId) {
        return [
            'total' => $this->getTotalTickets($raffleId),
            'sold' => $this->getSoldTickets($raffleId),
            'reserved' => $this->getReservedTickets($raffleId),
            'available' => $this->getAvailableTickets($raffleId)
        ];
    }
}
```

### 4. Frontend (public/templates/raffle-grid.php)
```php
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($raffles as $raffle): ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Imagen de la rifa -->
            <img src="<?= htmlspecialchars($raffle['image']) ?>" 
                 alt="<?= htmlspecialchars($raffle['title']) ?>"
                 class="w-full h-48 object-cover">
            
            <!-- Contenido -->
            <div class="p-6">
                <div class="flex justify-between items-start">
                    <h3 class="text-xl font-bold">
                        <?= htmlspecialchars($raffle['title']) ?>
                    </h3>
                    
                    <!-- Etiquetas -->
                    <div class="flex gap-2">
                        <?php foreach (json_decode($raffle['tags']) as $tag): ?>
                            <span class="px-2 py-1 text-xs rounded
                                       <?= $this->getTagClass($tag) ?>">
                                <?= htmlspecialchars($tag) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Selector de boletos -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700">
                        Seleccionar Rango
                    </label>
                    <select class="ticket-range-selector mt-1 block w-full rounded-md border-gray-300">
                        <?php foreach ($this->getTicketRanges($raffle['total_tickets']) as $range): ?>
                            <option value="<?= $range['start'] ?>-<?= $range['end'] ?>">
                                <?= $range['start'] ?> - <?= $range['end'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Grid de boletos -->
                <div class="ticket-grid mt-4 grid grid-cols-5 gap-2">
                    <!-- Generado dinámicamente por JavaScript -->
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

### 5. API de WhatsApp (includes/WhatsAppAPI.php)
```php
<?php
class WhatsAppAPI {
    private $countryCode;
    private $adminNumber;
    private $messageTemplate;

    public function __construct($config) {
        $this->countryCode = $config['country_code'];
        $this->adminNumber = $config['admin_number'];
        $this->messageTemplate = $this->getMessageTemplate();
    }

    public function sendReservationNotification($reservation, $tickets) {
        $message = $this->formatMessage($reservation, $tickets);
        $url = $this->generateWhatsAppUrl($message);
        
        return [
            'success' => true,
            'url' => $url
        ];
    }

    private function formatMessage($reservation, $tickets) {
        return strtr($this->messageTemplate, [
            '{customer_name}' => $reservation['name'],
            '{customer_phone}' => $reservation['phone'],
            '{tickets}' => implode(', ', $tickets),
            '{total}' => count($tickets),
            '{datetime}' => date('d/m/Y H:i')
        ]);
    }

    private function generateWhatsAppUrl($message) {
        $number = $this->countryCode . $this->adminNumber;
        return "https://api.whatsapp.com/send?phone={$number}&text=" . urlencode($message);
    }
}
```

## Notas de Implementación

### Seguridad
1. Implementar validación CSRF en todos los formularios
2. Sanitizar todas las entradas de usuario
3. Usar prepared statements para consultas SQL
4. Implementar rate limiting para prevenir abusos
5. Validar archivos subidos (tipo, tamaño, extensión)

### Optimización
1. Implementar caché para consultas frecuentes
2. Optimizar imágenes al subirlas
3. Usar lazy loading para imágenes
4. Implementar paginación para listas largas
5. Minificar assets (CSS, JS)

### Base de Datos
1. Usar índices apropiados
2. Implementar foreign keys
3. Usar transacciones para operaciones críticas
4. Optimizar consultas complejas
5. Mantener consistencia en los datos

### Frontend
1. Diseño responsive
2. Validación de formularios en cliente y servidor
3. Feedback visual para acciones
4. Loading states para operaciones asíncronas
5. Manejo de errores amigable