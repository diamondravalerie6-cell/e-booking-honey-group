<?php
/**
 * admin/logout.php - Déconnexion (uniquement via POST avec jeton CSRF)
 */
require '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify($_POST['csrf_token'] ?? null)) {
    admin_logout();
}

header('Location: login.php');
exit;
