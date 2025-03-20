<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Verificar sesión de administrador
checkAdminSession();

$error = '';
$success = '';
$raffle = null;

try {
    $db = Database::getInstance();

    // Obtener rifa si es edición
    if (isset($_GET['id'])) {
        $raffleId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $raffle = $db->query(
            "SELECT * FROM raffles WHERE id = ? LIMIT 1",
            [$raffleId]
        )->fetch();

        if (!$raffle) {
            header('Location: raffles.php');
            exit();
        }
    }

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateCSRFToken($_POST['csrf_token']);

        // Validar datos
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';
        $tags = isset($_POST['tags']) ? implode(',', array_map('trim', $_POST['tags'])) : '';

        if (empty($title)) {
            throw new Exception('El título es obligatorio');
        }

        // Preparar datos
        $raffleData = [
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'tags' => $tags
        ];

        // Procesar imagen si se subió una nueva
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleFileUpload(
                $_FILES['image'],
                'uploads/raffles',
                ['image/jpeg', 'image/png', 'image/gif']
            );

            if (!$uploadResult['success']) {
                throw new Exception('Error al subir la imagen: ' . $uploadResult['error']);
            }

            $raffleData['image'] = $uploadResult['path'];

            // Si es edición, eliminar imagen anterior
            if ($raffle && !empty($raffle['image'])) {
                $oldImagePath = __DIR__ . '/../' . $raffle['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }

        // Guardar en base de datos
        if ($raffle) {
            // Actualizar
            $db->update('raffles', $raffleData, 'id = ?', [$raffle['id']]);
            logAdminActivity('update_raffle', "Rifa ID: {$raffle['id']}");
            $success = 'Rifa actualizada exitosamente';
        } else {
            // Crear nueva
            $newRaffleId = $db->insert('raffles', $raffleData);
            logAdminActivity('create_raffle', "Nueva rifa ID: $newRaffleId");
            $success = 'Rifa creada exitosamente';
        }

        // Recargar datos si es edición
        if ($raffle) {
            $raffle = $db->query(
                "SELECT * FROM raffles WHERE id = ? LIMIT 1",
                [$raffle['id']]
            )->fetch();
        }
    }
} catch (Exception $e) {
    error_log("Error en edición de rifa: " . $e->getMessage());
    $error = $e->getMessage();
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $raffle ? 'Editar' : 'Nueva'; ?> Rifa - <?php echo htmlspecialchars($siteName); ?></title>
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
                <h2 class="text-2xl font-semibold text-gray-800">
                    <?php echo $raffle ? 'Editar Rifa' : 'Nueva Rifa'; ?>
                </h2>
                <a href="raffles.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver a Rifas
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

            <!-- Raffle Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                                Título
                            </label>
                            <input type="text" id="title" name="title" required
                                   value="<?php echo $raffle ? htmlspecialchars($raffle['title']) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Estado
                            </label>
                            <select id="status" name="status"
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active" <?php echo ($raffle && $raffle['status'] === 'active') ? 'selected' : ''; ?>>
                                    Activa
                                </option>
                                <option value="inactive" <?php echo ($raffle && $raffle['status'] === 'inactive') ? 'selected' : ''; ?>>
                                    Inactiva
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php 
                            echo $raffle ? htmlspecialchars($raffle['description']) : ''; 
                        ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Etiquetas
                        </label>
                        <div class="space-y-2">
                            <?php
                            $availableTags = ['Nuevo sorteo', 'Vendidas', 'Descuentos'];
                            $selectedTags = $raffle ? explode(',', $raffle['tags']) : [];
                            foreach ($availableTags as $tag):
                            ?>
                                <label class="inline-flex items-center mr-6">
                                    <input type="checkbox" name="tags[]" value="<?php echo $tag; ?>"
                                           <?php echo in_array($tag, $selectedTags) ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="ml-2"><?php echo $tag; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Imagen
                        </label>
                        <div class="mt-1 flex items-center">
                            <?php if ($raffle && !empty($raffle['image'])): ?>
                                <div class="mr-4">
                                    <img src="<?php echo htmlspecialchars($raffle['image']); ?>" 
                                         alt="Imagen actual" 
                                         class="h-20 w-20 object-cover rounded">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   onchange="previewImage(this)">
                        </div>
                        <div id="imagePreview" class="mt-2"></div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="raffles.php" 
                           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <?php echo $raffle ? 'Actualizar' : 'Crear'; ?> Rifa
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('h-20', 'w-20', 'object-cover', 'rounded', 'mt-2');
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>