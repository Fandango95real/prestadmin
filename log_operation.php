<?php
session_start();

// Vérifie que la connexion a été validée
if (!isset($_SESSION['connection_validated']) || $_SESSION['connection_validated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Session non valide']);
    exit;
}

// Vérifie que c'est une requête AJAX POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupère les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['action']) || !isset($data['details'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

require_once 'PrestaShopAPI.php';

try {
    $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

    // Utilise la réflexion pour appeler la méthode privée logOperation
    $reflection = new ReflectionClass($api);
    $method = $reflection->getMethod('logOperation');
    $method->setAccessible(true);
    $method->invoke($api, $data['action'], $data['details']);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
