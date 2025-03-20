<?php
require_once 'config.php';
require_once 'db.php';

// Función para sanitizar entrada
function sanitize($input) {
    if (is_array($input)) {
        foreach($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
    } else {
        $input = trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    return $input;
}

// Función para validar sesión de administrador
function checkAdminSession() {
    session_start();
    if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Función para enviar notificación WhatsApp
function sendWhatsAppNotification($customerData, $ticketData) {
    try {
        $message = getWhatsAppTemplate();
        $message = str_replace(
            ['{customer_name}', '{customer_phone}', '{ticket_number}', '{raffle_title}'],
            [$customerData['name'], $customerData['phone'], $ticketData['number'], $ticketData['raffle_title']],
            $message
        );

        $whatsappNumber = COUNTRY_CODE . ADMIN_WHATSAPP;
        $encodedMessage = urlencode($message);
        $whatsappUrl = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text={$encodedMessage}";

        return ['success' => true, 'url' => $whatsappUrl];
    } catch (Exception $e) {
        error_log("Error WhatsApp: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para obtener plantilla de mensaje WhatsApp
function getWhatsAppTemplate() {
    $db = Database::getInstance();
    $result = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'whatsapp_message_template' LIMIT 1");
    $template = $result->fetch();
    return $template['setting_value'] ?? 'Nueva reserva: {customer_name} - {ticket_number}';
}

// Función para manejar subida de archivos
function handleFileUpload($file, $destination, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    try {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Parámetros inválidos.');
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new Exception('No se envió ningún archivo.');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception('El archivo excede el tamaño permitido.');
            default:
                throw new Exception('Error desconocido.');
        }

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido.');
        }

        $fileName = sprintf('%s-%s', uniqid(), $file['name']);
        $uploadPath = __DIR__ . '/../' . $destination;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $filePath = $uploadPath . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Error al mover el archivo subido.');
        }

        return ['success' => true, 'path' => $destination . '/' . $fileName];
    } catch (Exception $e) {
        error_log("Error en subida de archivo: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para generar token CSRF
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Función para validar token CSRF
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        throw new Exception('Token CSRF inválido');
    }
    return true;
}

// Función para verificar si un número está bloqueado
function isPhoneBlocked($phone) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT COUNT(*) as blocked FROM blocked_numbers 
            WHERE phone_number = ? AND block_until > NOW()",
            [$phone]
        );
        $result = $stmt->fetch();
        return $result['blocked'] > 0;
    } catch (Exception $e) {
        error_log("Error al verificar bloqueo: " . $e->getMessage());
        return false;
    }
}

// Función para bloquear un número
function blockPhone($phone, $minutes) {
    try {
        $db = Database::getInstance();
        $blockUntil = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        
        return $db->insert('blocked_numbers', [
            'phone_number' => $phone,
            'block_until' => $blockUntil
        ]);
    } catch (Exception $e) {
        error_log("Error al bloquear número: " . $e->getMessage());
        return false;
    }
}

// Función para obtener configuración del sitio
function getSetting($key) {
    try {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1",
            [$key]
        );
        $setting = $result->fetch();
        return $setting['setting_value'] ?? null;
    } catch (Exception $e) {
        error_log("Error al obtener configuración: " . $e->getMessage());
        return null;
    }
}

// Función para actualizar configuración
function updateSetting($key, $value) {
    try {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?",
            [$key, $value, $value]
        );
        return true;
    } catch (Exception $e) {
        error_log("Error al actualizar configuración: " . $e->getMessage());
        return false;
    }
}

// Función para registrar actividad del admin
function logAdminActivity($action, $details = '') {
    try {
        $adminId = $_SESSION['admin_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $db = Database::getInstance();
        $db->insert('admin_activity_log', [
            'admin_id' => $adminId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $ip
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
        return false;
    }
}