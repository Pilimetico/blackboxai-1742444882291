<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'includes/file_manager.php';

checkAdminSession();

$error = '';
$success = '';
$currentPath = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
$fileManager = new FileManager('../uploads');

try {
    // Handle file upload
    if (isset($_POST['upload']) && isset($_FILES['file'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $result = $fileManager->uploadFile($_FILES['file'], $currentPath);
        $success = 'Archivo subido exitosamente';
        logAdminActivity('upload_file', "File: {$result['path']}");
    }

    // Handle directory creation
    if (isset($_POST['create_directory'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $dirName = filter_input(INPUT_POST, 'directory_name', FILTER_SANITIZE_STRING);
        $newPath = trim($currentPath . '/' . $dirName, '/');
        
        if ($fileManager->createDirectory($newPath)) {
            $success = 'Directorio creado exitosamente';
            logAdminActivity('create_directory', "Directory: $newPath");
        }
    }

    // Handle file/directory deletion
    if (isset($_POST['delete'])) {
        validateCSRFToken($_POST['csrf_token']);
        
        $deletePath = filter_input(INPUT_POST, 'path', FILTER_SANITIZE_STRING);
        
        if ($fileManager->deleteFile($deletePath)) {
            $success = 'Elemento eliminado exitosamente';
            logAdminActivity('delete_file', "Path: $deletePath");
        }
    }

    // List files and directories
    $files = $fileManager->listFiles($currentPath);

} catch (Exception $e) {
    error_log("Error in file manager: " . $e->getMessage());
    $error = $e->getMessage();
}

$siteName = getSetting('site_name') ?? 'Sistema de Rifas';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Archivos - <?php echo htmlspecialchars($siteName); ?></title>
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
                <a href="blocked_numbers.php" class="flex items-center px-4 py-3 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-ban w-6"></i>
                    <span>Números Bloqueados</span>
                </a>
                <a href="file_manager.php" class="flex items-center px-4 py-3 bg-blue-900">
                    <i class="fas fa-folder w-6"></i>
                    <span>Gestor de Archivos</span>
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
                <h2 class="text-2xl font-semibold text-gray-800">Gestor de Archivos</h2>
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

            <!-- Breadcrumb -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <div class="flex items-center space-x-2">
                    <a href="?path=" class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-home"></i>
                    </a>
                    <?php
                    $paths = $currentPath ? explode('/', $currentPath) : [];
                    $currentBreadcrumb = '';
                    foreach ($paths as $path):
                        $currentBreadcrumb .= '/' . $path;
                    ?>
                        <span class="text-gray-500">/</span>
                        <a href="?path=<?php echo urlencode(trim($currentBreadcrumb, '/')); ?>" 
                           class="text-blue-600 hover:text-blue-800">
                            <?php echo htmlspecialchars($path); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex space-x-4">
                    <!-- Upload File -->
                    <form method="POST" enctype="multipart/form-data" class="flex-1">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="flex space-x-2">
                            <input type="file" name="file" required
                                   class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" name="upload"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-upload mr-2"></i>
                                Subir Archivo
                            </button>
                        </div>
                    </form>

                    <!-- Create Directory -->
                    <form method="POST" class="flex-1">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <div class="flex space-x-2">
                            <input type="text" name="directory_name" required placeholder="Nombre del directorio"
                                   class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" name="create_directory"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                <i class="fas fa-folder-plus mr-2"></i>
                                Crear Directorio
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Files List -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tamaño</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modificado</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($files)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No hay archivos en este directorio
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($files as $file): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($file['is_dir']): ?>
                                                <a href="?path=<?php echo urlencode($file['path']); ?>" 
                                                   class="flex items-center text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-folder text-yellow-400 mr-2"></i>
                                                    <?php echo htmlspecialchars($file['name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <div class="flex items-center">
                                                    <i class="fas fa-file text-gray-400 mr-2"></i>
                                                    <?php echo htmlspecialchars($file['name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo $file['is_dir'] ? 'Directorio' : ($file['type'] ?? 'Desconocido'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo $file['is_dir'] ? '-' : $fileManager->formatSize($file['size']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php echo date('d/m/Y H:i', $file['modified']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <?php if (!$file['is_dir']): ?>
                                                <a href="../uploads/<?php echo $file['path']; ?>" 
                                                   target="_blank"
                                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <form method="POST" class="inline-block" 
                                                  onsubmit="return confirm('¿Está seguro de eliminar este elemento?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="path" value="<?php echo $file['path']; ?>">
                                                <input type="hidden" name="delete" value="1">
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