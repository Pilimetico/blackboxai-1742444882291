<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

session_start();

// Registrar la actividad de cierre de sesión
if (isset($_SESSION['admin_id'])) {
    logAdminActivity('logout', 'Cierre de sesión');
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: login.php');
exit();