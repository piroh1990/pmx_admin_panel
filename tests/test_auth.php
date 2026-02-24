<?php
// test_auth.php
require_once __DIR__ . '/../auth.php';

// Mock session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Testing Auth Logic...\n";

// 1. Test valid login
echo "1. Valid login (admin:password)... ";
$result = attemptLogin('admin', 'password');
if ($result['success'] === true) {
    echo "PASS\n";
} else {
    echo "FAIL (Expected success, got: " . $result['message'] . ")\n";
}
logout(); // clear session

// 2. Test invalid password
echo "2. Invalid password (admin:wrong)... ";
$result = attemptLogin('admin', 'wrong');
if ($result['success'] === false) {
    echo "PASS\n";
} else {
    echo "FAIL (Expected failure)\n";
}
logout();

// 3. Test non-existent user
echo "3. Non-existent user (nobody:password)... ";
$result = attemptLogin('nobody', 'password');
if ($result['success'] === false) {
    echo "PASS\n";
} else {
    echo "FAIL (Expected failure)\n";
}
logout();

echo "Done.\n";
