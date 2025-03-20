<?php
/**
 * Sistema de Rifas - Validation Script
 * Checks system requirements and file permissions
 */

class SystemValidator {
    private $results = [
        'php' => [],
        'extensions' => [],
        'directories' => [],
        'files' => [],
        'database' => []
    ];

    private $requiredPHPVersion = '7.4.0';
    private $requiredExtensions = [
        'pdo',
        'pdo_mysql',
        'gd',
        'fileinfo',
        'json',
        'session',
        'openssl'
    ];

    private $directoriesToCheck = [
        'admin',
        'frontend',
        'includes',
        'installer',
        'sql',
        'uploads'
    ];

    private $filesToCheck = [
        '.htaccess',
        'error.php',
        'admin/index.php',
        'frontend/index.php',
        'includes/functions.php',
        'installer/index.php',
        'sql/schema.sql'
    ];

    public function validate() {
        $this->checkPHPVersion();
        $this->checkExtensions();
        $this->checkDirectories();
        $this->checkFiles();
        return $this->results;
    }

    private function checkPHPVersion() {
        $currentVersion = PHP_VERSION;
        $this->results['php'][] = [
            'name' => 'PHP Version',
            'required' => '>= ' . $this->requiredPHPVersion,
            'current' => $currentVersion,
            'status' => version_compare($currentVersion, $this->requiredPHPVersion, '>=')
        ];
    }

    private function checkExtensions() {
        foreach ($this->requiredExtensions as $extension) {
            $this->results['extensions'][] = [
                'name' => $extension,
                'status' => extension_loaded($extension),
                'current' => extension_loaded($extension) ? 'Installed' : 'Not Installed'
            ];
        }
    }

    private function checkDirectories() {
        foreach ($this->directoriesToCheck as $directory) {
            $path = __DIR__ . '/' . $directory;
            $exists = is_dir($path);
            $writable = $exists ? is_writable($path) : false;
            $permissions = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';

            $this->results['directories'][] = [
                'path' => $directory,
                'exists' => $exists,
                'writable' => $writable,
                'permissions' => $permissions,
                'status' => $exists && $writable
            ];
        }
    }

    private function checkFiles() {
        foreach ($this->filesToCheck as $file) {
            $path = __DIR__ . '/' . $file;
            $exists = file_exists($path);
            $readable = $exists ? is_readable($path) : false;
            $permissions = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'N/A';

            $this->results['files'][] = [
                'path' => $file,
                'exists' => $exists,
                'readable' => $readable,
                'permissions' => $permissions,
                'status' => $exists && $readable
            ];
        }
    }
}

// Only run if accessed directly
if (php_sapi_name() !== 'cli' && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    $validator = new SystemValidator();
    $results = $validator->validate();
    
    // Output as HTML
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema de Rifas - Validación</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
            body { font-family: 'Poppins', sans-serif; }
        </style>
    </head>
    <body class="bg-gray-100 min-h-screen p-8">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Sistema de Rifas</h1>
                <p class="text-gray-600">Validación del Sistema</p>
            </div>

            <!-- PHP Version -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Versión de PHP</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requerido</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actual</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['php'] as $check): ?>
                                <tr>
                                    <td class="px-6 py-4"><?php echo $check['required']; ?></td>
                                    <td class="px-6 py-4"><?php echo $check['current']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($check['status']): ?>
                                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                                        <?php else: ?>
                                            <span class="text-red-600"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Extensions -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Extensiones PHP</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($results['extensions'] as $extension): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <span class="font-medium"><?php echo $extension['name']; ?></span>
                            <?php if ($extension['status']): ?>
                                <span class="text-green-600"><i class="fas fa-check"></i></span>
                            <?php else: ?>
                                <span class="text-red-600"><i class="fas fa-times"></i></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Directories -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Directorios</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Directorio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Existe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permisos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['directories'] as $dir): ?>
                                <tr>
                                    <td class="px-6 py-4"><?php echo $dir['path']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($dir['exists']): ?>
                                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                                        <?php else: ?>
                                            <span class="text-red-600"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4"><?php echo $dir['permissions']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($dir['status']): ?>
                                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                                        <?php else: ?>
                                            <span class="text-red-600"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Files -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold mb-4">Archivos</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Archivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Existe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permisos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['files'] as $file): ?>
                                <tr>
                                    <td class="px-6 py-4"><?php echo $file['path']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($file['exists']): ?>
                                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                                        <?php else: ?>
                                            <span class="text-red-600"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4"><?php echo $file['permissions']; ?></td>
                                    <td class="px-6 py-4">
                                        <?php if ($file['status']): ?>
                                            <span class="text-green-600"><i class="fas fa-check"></i></span>
                                        <?php else: ?>
                                            <span class="text-red-600"><i class="fas fa-times"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>