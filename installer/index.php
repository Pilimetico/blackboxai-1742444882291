<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Función para verificar si ya está instalado
function isInstalled() {
    return file_exists('../includes/config.php');
}

// Función para validar la conexión a la base de datos
function testDatabaseConnection($host, $user, $pass, $dbname) {
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para crear el archivo de configuración
function createConfigFile($data) {
    $config = "<?php\n";
    $config .= "defined('INSTALLED') or define('INSTALLED', true);\n\n";
    $config .= "// Database configuration\n";
    $config .= "define('DB_HOST', '" . addslashes($data['db_host']) . "');\n";
    $config .= "define('DB_NAME', '" . addslashes($data['db_name']) . "');\n";
    $config .= "define('DB_USER', '" . addslashes($data['db_user']) . "');\n";
    $config .= "define('DB_PASS', '" . addslashes($data['db_pass']) . "');\n\n";
    $config .= "// WhatsApp configuration\n";
    $config .= "define('COUNTRY_CODE', '" . addslashes($data['country_code']) . "');\n";
    $config .= "define('ADMIN_WHATSAPP', '" . addslashes($data['admin_whatsapp']) . "');\n\n";
    $config .= "// Security\n";
    $config .= "define('SECURITY_KEY', '" . bin2hex(random_bytes(32)) . "');\n";
    
    if (!is_dir('../includes')) {
        mkdir('../includes', 0755, true);
    }
    
    return file_put_contents('../includes/config.php', $config);
}

// Función para importar el esquema SQL
function importDatabase($pdo) {
    try {
        $sql = file_get_contents('../sql/schema.sql');
        $pdo->exec($sql);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para crear el usuario administrador
function createAdminUser($pdo, $username, $password) {
    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hash]);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

$error = '';
$success = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isInstalled()) {
        die('El sistema ya está instalado.');
    }

    // Validar campos requeridos
    $required_fields = ['db_host', 'db_name', 'db_user', 'db_pass', 'admin_user', 'admin_pass', 'country_code', 'admin_whatsapp'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error = 'Todos los campos son obligatorios';
            break;
        }
    }

    if (empty($error)) {
        // Probar conexión a la base de datos
        $db_test = testDatabaseConnection(
            $_POST['db_host'],
            $_POST['db_user'],
            $_POST['db_pass'],
            $_POST['db_name']
        );

        if ($db_test['success']) {
            // Crear archivo de configuración
            if (!createConfigFile($_POST)) {
                $error = 'No se pudo crear el archivo de configuración';
            } else {
                // Importar base de datos
                $db_import = importDatabase($db_test['pdo']);
                if (!$db_import['success']) {
                    $error = 'Error al importar la base de datos: ' . $db_import['error'];
                } else {
                    // Crear usuario administrador
                    $admin_create = createAdminUser($db_test['pdo'], $_POST['admin_user'], $_POST['admin_pass']);
                    if (!$admin_create['success']) {
                        $error = 'Error al crear el usuario administrador: ' . $admin_create['error'];
                    } else {
                        $success = 'Instalación completada con éxito';
                    }
                }
            }
        } else {
            $error = 'Error de conexión a la base de datos: ' . $db_test['error'];
        }
    }
}

// Si ya está instalado, redirigir
if (isInstalled() && empty($error)) {
    header('Location: ../admin/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Sistema de Rifas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .installer-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-label { font-weight: 500; }
        .alert { border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="installer-container">
            <h1 class="text-center mb-4">Instalador del Sistema de Rifas</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <br>
                    <a href="../admin/" class="alert-link">Ir al panel de administración</a>
                </div>
            <?php else: ?>
            
            <form method="post" class="needs-validation" novalidate>
                <h4 class="mb-3">Configuración de la Base de Datos</h4>
                <div class="mb-3">
                    <label class="form-label">Tipo de Conexión</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="connection_type" id="local" value="localhost" checked>
                        <label class="form-check-label" for="local">Localhost</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="connection_type" id="remote" value="remote">
                        <label class="form-check-label" for="remote">Remoto</label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="db_host" class="form-label">Host de la Base de Datos</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_name" class="form-label">Nombre de la Base de Datos</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="db_user" class="form-label">Usuario de la Base de Datos</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="db_pass" class="form-label">Contraseña de la Base de Datos</label>
                    <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                </div>

                <h4 class="mb-3 mt-4">Configuración del Administrador</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="admin_user" class="form-label">Usuario Administrador</label>
                        <input type="text" class="form-control" id="admin_user" name="admin_user" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="admin_pass" class="form-label">Contraseña Administrador</label>
                        <input type="password" class="form-control" id="admin_pass" name="admin_pass" required>
                    </div>
                </div>

                <h4 class="mb-3 mt-4">Configuración de WhatsApp</h4>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="country_code" class="form-label">Indicativo del País</label>
                        <input type="text" class="form-control" id="country_code" name="country_code" placeholder="Ej: 34" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="admin_whatsapp" class="form-label">Número de WhatsApp</label>
                        <input type="text" class="form-control" id="admin_whatsapp" name="admin_whatsapp" placeholder="Sin indicativo del país" required>
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-primary btn-lg" type="submit">Instalar Sistema</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cambiar el host según el tipo de conexión
        document.querySelectorAll('input[name="connection_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('db_host').value = this.value === 'localhost' ? 'localhost' : '';
            });
        });

        // Validación del formulario
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>