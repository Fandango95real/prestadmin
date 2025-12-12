<?php
session_start();

// Vérifie que la connexion a été validée
if (!isset($_SESSION['connection_validated']) || $_SESSION['connection_validated'] !== true) {
    die("Connexion non validée. Veuillez d'abord vous connecter via index.php");
}

require_once 'PrestaShopAPI.php';

echo "<h1>Debug des déclinaisons du produit 1158</h1>";
echo "<pre>";

// Active le mode debug
$api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], true);

echo "=== Étape 1: Récupération du produit 1158 ===\n\n";
try {
    $result = $api->makeRequest("products/1158");

    if ($result && isset($result->product)) {
        echo "Produit trouvé: " . $result->product->name->language[0] . "\n";
        echo "Prix du produit: " . $result->product->price . "\n\n";

        if (isset($result->product->associations->combinations->combination)) {
            $combos = $result->product->associations->combinations->combination;

            // Si une seule déclinaison, l'API retourne un objet au lieu d'un array
            if (!is_array($combos)) {
                $combos = [$combos];
            }

            echo "Nombre de déclinaisons trouvées: " . count($combos) . "\n\n";

            echo "=== Étape 2: Détails de chaque déclinaison ===\n\n";

            foreach ($combos as $index => $combo) {
                $combinationId = (string)$combo->id;
                echo "--- Déclinaison #" . ($index + 1) . " (ID: $combinationId) ---\n";

                try {
                    $combDetails = $api->makeRequest("combinations/$combinationId");

                    if ($combDetails && isset($combDetails->combination)) {
                        $c = $combDetails->combination;

                        echo "  Référence: " . (isset($c->reference) ? $c->reference : 'N/A') . "\n";
                        echo "  Impact prix: " . (isset($c->price) ? $c->price : 'N/A') . "\n";

                        // Récupère le nom de la déclinaison
                        if (isset($c->associations->product_option_values->product_option_value)) {
                            $optionValues = $c->associations->product_option_values->product_option_value;
                            if (!is_array($optionValues)) {
                                $optionValues = [$optionValues];
                            }

                            echo "  Options (" . count($optionValues) . "):\n";

                            foreach ($optionValues as $optVal) {
                                $optValId = (string)$optVal->id;
                                echo "    - ID option value: $optValId\n";

                                try {
                                    $optValDetails = $api->makeRequest("product_option_values/$optValId", ['display' => '[name]']);
                                    if ($optValDetails && isset($optValDetails->product_option_value->name->language[0])) {
                                        echo "      Nom: " . (string)$optValDetails->product_option_value->name->language[0] . "\n";
                                    }
                                } catch (Exception $e) {
                                    echo "      ERREUR récupération nom: " . $e->getMessage() . "\n";
                                }
                            }
                        }

                        echo "  ✅ Déclinaison OK\n\n";
                    } else {
                        echo "  ❌ ERREUR: Pas de détails retournés\n\n";
                    }

                } catch (Exception $e) {
                    echo "  ❌ ERREUR récupération déclinaison: " . $e->getMessage() . "\n\n";
                }
            }

        } else {
            echo "⚠️ Aucune déclinaison trouvée dans les associations\n";
        }
    } else {
        echo "❌ Produit non trouvé\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== Étape 3: Test de la méthode getProductCombinations() ===\n\n";

try {
    $combinations = $api->getProductCombinations(1158);
    echo "Nombre de déclinaisons retournées: " . count($combinations) . "\n\n";

    foreach ($combinations as $index => $combo) {
        echo "Déclinaison #" . ($index + 1) . ":\n";
        echo "  ID: " . $combo['id'] . "\n";
        echo "  Nom: " . $combo['combination_name'] . "\n";
        echo "  Référence: " . $combo['reference'] . "\n";
        echo "  Prix final: " . $combo['price'] . "\n";
        echo "  Impact prix: " . $combo['price_impact'] . "\n\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
