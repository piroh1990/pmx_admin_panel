<?php
require_once __DIR__ . '/auth.php';

startSecureSession();
logout();

header('Location: index.php');
exit;
