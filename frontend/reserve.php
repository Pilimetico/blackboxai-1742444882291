<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Validar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Sanitizar y validar datos de entrada
    $raffleId = filter_input(INPUT_POST, 'raffle_id', FILTER_VALIDATE_INT);
    $ticketNumber = filter_input(INPUT_POST, 'ticket_number', FILTER_SANITIZE_STRING);
    $customerName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $customerPhone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $customerEmail = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (!$raffleId || !$ticketNumber || !$customerName || !$customerPhone) {
        throw new Exception('Todos los campos son obligatorios');
    }

    // Verificar si el número está bloqueado
    if (isPhoneBlocked($customerPhone)) {
        throw new Exception('Este número está temporalmente bloqueado');
    }

    $db = Database::getInstance();
    
    // Iniciar transacción
    $db->beginTransaction();

    // Verificar si la rifa existe y está activa
    $raffle = $db->query(
        "SELECT * FROM raffles WHERE id = ? AND status = 'active' LIMIT 1",
        [$raffleId]
    )->fetch();

    if (!$raffle) {
        throw new Exception('La rifa no existe o no está activa');
    }

    // Verificar si el boleto está disponible
    $ticket = $db->query(
        "SELECT * FROM tickets WHERE raffle_id = ? AND ticket_number = ? LIMIT 1",
        [$raffleId, $ticketNumber]
    )->fetch();

    if ($ticket) {
        throw new Exception('Este boleto ya está reservado');
    }

    // Crear el ticket
    $ticketId = $db->insert('tickets', [
        'raffle_id' => $raffleId,
        'ticket_number' => $ticketNumber,
        'payment_status' => 'pending'
    ]);

    // Crear la reserva
    $reservationId = $db->insert('reservations', [
        'ticket_id' => $ticketId,
        'customer_name' => $customerName,
        'customer_phone' => $customerPhone,
        'customer_email' => $customerEmail,
        'status' => 'reserved'
    ]);

    // Preparar datos para la notificación de WhatsApp
    $customerData = [
        'name' => $customerName,
        'phone' => $customerPhone
    ];

    $ticketData = [
        'number' => $ticketNumber,
        'raffle_title' => $raffle['title']
    ];

    // Enviar notificación WhatsApp
    $whatsappResult = sendWhatsAppNotification($customerData, $ticketData);

    if (!$whatsappResult['success']) {
        throw new Exception('Error al enviar notificación WhatsApp');
    }

    // Confirmar transacción
    $db->commit();

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Reserva creada exitosamente',
        'whatsapp_url' => $whatsappResult['url']
    ]);

} catch (Exception $e) {
    // Revertir transacción si hubo error
    if (isset($db) && $db->getConnection()->inTransaction()) {
        $db->rollBack();
    }

    // Registrar error
    error_log("Error en reserva: " . $e->getMessage());

    // Respuesta de error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}