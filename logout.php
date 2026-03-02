<?php
require_once __DIR__ . '/auth.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (verifyCsrfToken($csrfToken)) {
        logout();
    }
}

header('Location: index.php');
exit;
