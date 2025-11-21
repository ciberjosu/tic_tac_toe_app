<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('No autenticado');
}
require 'db.php';

$result = $_POST['result'] ?? '';
if (!in_array($result, ['win','loss','draw'], true)) {
    http_response_code(400);
    exit('Resultado inválido');
}

$stmt = $mysqli->prepare('INSERT INTO results (user_id, result) VALUES (?, ?)');
$stmt->bind_param('is', $_SESSION['user_id'], $result);
$stmt->execute();
echo 'OK';
