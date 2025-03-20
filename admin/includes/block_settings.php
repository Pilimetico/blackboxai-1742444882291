<?php
function getBlockSettings() {
    $db = Database::getInstance();
    $settings = [
        'block_enabled' => false,
        'block_duration' => 30 // default 30 minutes
    ];

    try {
        $result = $db->query(
            "SELECT setting_value FROM settings WHERE setting_key IN ('block_enabled', 'block_duration')"
        )->fetchAll();

        foreach ($result as $row) {
            if ($row['setting_key'] === 'block_enabled') {
                $settings['block_enabled'] = (bool)$row['setting_value'];
            } elseif ($row['setting_key'] === 'block_duration') {
                $settings['block_duration'] = (int)$row['setting_value'];
            }
        }
    } catch (Exception $e) {
        error_log("Error getting block settings: " . $e->getMessage());
    }

    return $settings;
}

function updateBlockSettings($enabled, $duration) {
    $db = Database::getInstance();
    try {
        $db->beginTransaction();

        $db->query(
            "INSERT INTO settings (setting_key, setting_value) 
             VALUES ('block_enabled', ?) 
             ON DUPLICATE KEY UPDATE setting_value = ?",
            [$enabled ? '1' : '0', $enabled ? '1' : '0']
        );

        $db->query(
            "INSERT INTO settings (setting_key, setting_value) 
             VALUES ('block_duration', ?) 
             ON DUPLICATE KEY UPDATE setting_value = ?",
            [$duration, $duration]
        );

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating block settings: " . $e->getMessage());
        return false;
    }
}

function cleanExpiredBlocks() {
    $db = Database::getInstance();
    try {
        $db->query("DELETE FROM blocked_numbers WHERE block_until < NOW()");
        return true;
    } catch (Exception $e) {
        error_log("Error cleaning expired blocks: " . $e->getMessage());
        return false;
    }
}
?>