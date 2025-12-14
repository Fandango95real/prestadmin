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

if (!$data || !isset($data['batch']) || !isset($data['updatePrice']) || !isset($data['updateStock'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

require_once 'PrestaShopAPI.php';

try {
    $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

    $batch = $data['batch'];
    $updatePrice = $data['updatePrice'];
    $updateStock = $data['updateStock'];

    $results = [
        'success' => 0,
        'errors' => [],
        'processed' => 0
    ];

    foreach ($batch as $row) {
        $results['processed']++;

        try {
            // Détermine le type de produit (simple ou avec déclinaison)
            $hasDeclinaison = isset($row['ProductID']) && isset($row['CombinationID']);

            if ($hasDeclinaison) {
                // Format avec déclinaisons
                $productId = intval($row['ProductID']);
                $combinationId = intval($row['CombinationID']);
                $price = isset($row['Price']) ? $row['Price'] : null;
                $quantity = isset($row['Quantité']) ? $row['Quantité'] : null;

                // Normalise le prix (virgule -> point)
                if ($price !== null) {
                    $price = str_replace(',', '.', $price);
                    $price = floatval($price);
                }

                // Normalise la quantité
                if ($quantity !== null) {
                    $quantity = str_replace(',', '.', $quantity);
                    $quantity = intval($quantity);
                    if ($quantity < 0) {
                        throw new Exception("La quantité doit être >= 0");
                    }
                }

                // Met à jour le prix si demandé
                if ($updatePrice && $price !== null) {
                    if ($combinationId == 0) {
                        // Produit simple
                        if (!$api->updateProductPrice($productId, $price)) {
                            throw new Exception("Échec mise à jour prix");
                        }
                    } else {
                        // Déclinaison
                        if (!$api->updateCombinationPrice($combinationId, $price)) {
                            throw new Exception("Échec mise à jour prix déclinaison");
                        }
                    }
                }

                // Met à jour le stock si demandé
                if ($updateStock && $quantity !== null) {
                    if (!$api->updateStock($productId, $quantity, $combinationId)) {
                        throw new Exception("Échec mise à jour stock");
                    }
                }

                $results['success']++;

            } else {
                // Format standard (simple)
                $productId = intval($row['ID']);
                $price = isset($row['Prix']) ? $row['Prix'] : null;
                $quantity = isset($row['Quantité']) ? $row['Quantité'] : null;

                // Normalise le prix (virgule -> point)
                if ($price !== null) {
                    $price = str_replace(',', '.', $price);
                    $price = floatval($price);
                }

                // Normalise la quantité
                if ($quantity !== null) {
                    $quantity = str_replace(',', '.', $quantity);
                    $quantity = intval($quantity);
                    if ($quantity < 0) {
                        throw new Exception("La quantité doit être >= 0");
                    }
                }

                // Met à jour le prix si demandé
                if ($updatePrice && $price !== null) {
                    if (!$api->updateProductPrice($productId, $price)) {
                        throw new Exception("Échec mise à jour prix");
                    }
                }

                // Met à jour le stock si demandé
                if ($updateStock && $quantity !== null) {
                    if (!$api->updateStock($productId, $quantity, 0)) {
                        throw new Exception("Échec mise à jour stock");
                    }
                }

                $results['success']++;
            }

        } catch (Exception $e) {
            $productName = $hasDeclinaison
                ? ($row['ProductName'] ?? 'Produit inconnu')
                : ($row['Nom'] ?? 'Produit inconnu');

            $results['errors'][] = $productName . ': ' . $e->getMessage();
        }
    }

    // Retourne les résultats
    header('Content-Type: application/json');
    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
