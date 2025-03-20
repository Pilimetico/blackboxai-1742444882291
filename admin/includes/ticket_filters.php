<?php
function getTicketRanges($raffleId) {
    $db = Database::getInstance();
    $tickets = $db->query(
        "SELECT MIN(CAST(ticket_number AS UNSIGNED)) as min_number, 
                MAX(CAST(ticket_number AS UNSIGNED)) as max_number 
         FROM tickets 
         WHERE raffle_id = ?",
        [$raffleId]
    )->fetch();

    if (!$tickets) {
        return [];
    }

    $min = (int)$tickets['min_number'];
    $max = (int)$tickets['max_number'];
    $ranges = [];
    $step = 1000;

    for ($i = $min; $i <= $max; $i += $step) {
        $rangeEnd = min($i + $step - 1, $max);
        $ranges[] = [
            'start' => $i,
            'end' => $rangeEnd,
            'label' => "$i-$rangeEnd"
        ];
    }

    return $ranges;
}

function getTicketsInRange($raffleId, $start, $end) {
    $db = Database::getInstance();
    return $db->query(
        "SELECT t.*, r.status as reservation_status, r.customer_name 
         FROM tickets t 
         LEFT JOIN reservations r ON t.id = r.ticket_id 
         WHERE t.raffle_id = ? 
         AND CAST(t.ticket_number AS UNSIGNED) BETWEEN ? AND ?
         ORDER BY CAST(t.ticket_number AS UNSIGNED)",
        [$raffleId, $start, $end]
    )->fetchAll();
}
?>