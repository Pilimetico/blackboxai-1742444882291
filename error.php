<?php
$errorCode = $_SERVER['REDIRECT_STATUS'] ?? 404;
$errorMessages = [
    403 => 'Acceso Prohibido',
    404 => 'Página No Encontrada',
    500 => 'Error Interno del Servidor',
    'default' => 'Error Desconocido'
];

$errorMessage = $errorMessages[$errorCode] ?? $errorMessages['default'];
$siteName = 'Sistema de Rifas';

// Try to get site name from config if available
if (file_exists('includes/config.php')) {
    include_once 'includes/config.php';
    if (function_exists('getSetting')) {
        $siteName = getSetting('site_name') ?? $siteName;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $errorMessage; ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="text-center mb-8">
            <h1 class="text-6xl font-bold text-gray-800 mb-4"><?php echo $errorCode; ?></h1>
            <p class="text-xl text-gray-600 mb-8"><?php echo htmlspecialchars($errorMessage); ?></p>
            <div class="space-y-4">
                <a href="/" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    Ir al Inicio
                </a>
                <button onclick="history.back()" class="block w-full text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver Atrás
                </button>
            </div>
        </div>
    </div>
</body>
</html>