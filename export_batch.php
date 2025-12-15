<?php
session_start();

// Augmente les timeouts
set_time_limit(300); // 5 minutes par batch
ini_set('max_execution_time', 300);

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

if (!$data || !isset($data['offset']) || !isset($data['limit']) || !isset($data['exportType']) || !isset($data['categoryId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

require_once 'PrestaShopAPI.php';

try {
    $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

    $offset = intval($data['offset']);
    $limit = intval($data['limit']);
    $exportType = $data['exportType']; // 'standard' ou 'combinations'
    $categoryId = intval($data['categoryId']);

    $results = [
        'products' => [],
        'count' => 0,
        'hasMore' => false
    ];

    if ($exportType === 'combinations') {
        // Export avec déclinaisons
        $items = $api->getAllProductsWithCombinations($categoryId, $limit, $offset);

        foreach ($items as $item) {
            $results['products'][] = [
                'ProductID' => $item['product_id'],
                'CombinationID' => $item['combination_id'],
                'ProductName' => $item['product_name'],
                'CombinationName' => $item['combination_name'],
                'Reference' => $item['reference'],
                'Price' => str_replace('.', ',', $item['price']), // Format français
                'Quantité' => $item['quantity']
            ];
        }

        $results['count'] = count($items);
        $results['hasMore'] = count($items) === $limit;

    } else {
        // Export standard
        $products = $api->getAllProducts($categoryId, $limit, $offset);

        foreach ($products as $product) {
            $results['products'][] = [
                'ID' => $product['id'],
                'Nom' => $product['name'],
                'Référence' => $product['reference'],
                'Prix' => str_replace('.', ',', $product['price']), // Format français
                'Quantité' => $product['quantity']
            ];
        }

        $results['count'] = count($products);
        $results['hasMore'] = count($products) === $limit;
    }

    // Retourne les résultats
    header('Content-Type: application/json');
    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
