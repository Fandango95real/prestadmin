<?php
/**
 * Classe pour gérer l'API PrestaShop 8.2
 */
class PrestaShopAPI {
    private $shopUrl;
    private $apiKey;
    private $debug;

    /**
     * Constructeur
     * @param string $shopUrl URL du shop PrestaShop (ex: https://monsite.com)
     * @param string $apiKey Clé API PrestaShop
     * @param bool $debug Mode debug
     */
    public function __construct($shopUrl, $apiKey, $debug = false) {
        $this->shopUrl = rtrim($shopUrl, '/');
        $this->apiKey = $apiKey;
        $this->debug = $debug;
    }

    /**
     * Effectue une requête à l'API PrestaShop
     * @param string $resource Ressource à interroger (ex: 'products')
     * @param array $params Paramètres de la requête
     * @param string $method Méthode HTTP (GET, POST, PUT, DELETE)
     * @param string $xml Données XML pour POST/PUT
     * @return SimpleXMLElement|false
     */
    private function makeRequest($resource, $params = [], $method = 'GET', $xml = null) {
        $url = $this->shopUrl . '/api/' . $resource;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':');
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Timeout de 5 minutes
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Timeout de connexion 30s

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($xml) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($xml) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/xml']);
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($this->debug) {
            echo "URL: $url\n";
            echo "HTTP Code: $httpCode\n";
            echo "Response: $response\n";
        }

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("Erreur cURL: $error");
        }

        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("Erreur HTTP $httpCode: $response");
        }

        if (empty($response)) {
            return false;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new Exception("Erreur XML: " . print_r($errors, true));
        }

        return $xml;
    }

    /**
     * Teste la connexion à l'API
     * @return bool
     */
    public function testConnection() {
        try {
            $result = $this->makeRequest('products', ['limit' => 1]);
            return $result !== false;
        } catch (Exception $e) {
            if ($this->debug) {
                echo "Erreur de connexion: " . $e->getMessage() . "\n";
            }
            return false;
        }
    }

    /**
     * Vérifie les permissions de l'API
     * @return array Résultats des tests [success, errors, warnings]
     */
    public function checkPermissions() {
        $results = [
            'success' => true,
            'errors' => [],
            'warnings' => [],
            'details' => []
        ];

        // Test 1: GET sur products
        try {
            $result = $this->makeRequest('products', ['limit' => 1]);
            if ($result !== false) {
                $results['details']['products_get'] = true;
            } else {
                $results['success'] = false;
                $results['errors'][] = "Impossible de lire les produits (GET)";
                $results['details']['products_get'] = false;
            }
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = "Erreur GET products: " . $e->getMessage();
            $results['details']['products_get'] = false;
        }

        // Test 2: GET sur categories
        try {
            $result = $this->makeRequest('categories', ['limit' => 1]);
            if ($result !== false) {
                $results['details']['categories_get'] = true;
            } else {
                $results['success'] = false;
                $results['errors'][] = "Impossible de lire les catégories (GET)";
                $results['details']['categories_get'] = false;
            }
        } catch (Exception $e) {
            $results['success'] = false;
            $results['errors'][] = "Erreur GET categories: " . $e->getMessage();
            $results['details']['categories_get'] = false;
        }

        // Test 3: PUT sur products (test sans modification réelle)
        try {
            // Récupère un produit
            $result = $this->makeRequest('products', ['limit' => 1, 'display' => '[id]']);

            if ($result && isset($result->products->product[0])) {
                $productId = (string)$result->products->product[0]->id;

                // Essaie de récupérer le produit complet pour tester PUT
                $productFull = $this->makeRequest("products/$productId");

                if ($productFull && isset($productFull->product)) {
                    // Test PUT avec les mêmes données (pas de modification)
                    $xml = $productFull->asXML();

                    try {
                        $this->makeRequest("products/$productId", [], 'PUT', $xml);
                        $results['details']['products_put'] = true;
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), '401') !== false || strpos($e->getMessage(), '403') !== false) {
                            $results['success'] = false;
                            $results['errors'][] = "Permission PUT manquante sur products";
                            $results['details']['products_put'] = false;
                        } else {
                            // Autre erreur, on considère que la permission existe
                            $results['warnings'][] = "Test PUT products incomplet: " . $e->getMessage();
                            $results['details']['products_put'] = 'unknown';
                        }
                    }
                } else {
                    $results['warnings'][] = "Impossible de tester PUT products (produit non récupérable)";
                    $results['details']['products_put'] = 'unknown';
                }
            } else {
                $results['warnings'][] = "Impossible de tester PUT products (aucun produit)";
                $results['details']['products_put'] = 'unknown';
            }
        } catch (Exception $e) {
            $results['warnings'][] = "Test PUT products incomplet: " . $e->getMessage();
            $results['details']['products_put'] = 'unknown';
        }

        return $results;
    }

    /**
     * Récupère toutes les catégories
     * @return array Liste des catégories avec [id, name]
     */
    public function getAllCategories() {
        $categories = [];

        try {
            $result = $this->makeRequest('categories', ['display' => '[id,name]']);

            if (!$result || !isset($result->categories->category)) {
                return $categories;
            }

            foreach ($result->categories->category as $category) {
                $id = (string)$category->id;
                $name = (string)$category->name->language[0];

                $categories[] = [
                    'id' => $id,
                    'name' => $name
                ];
            }

        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des catégories: " . $e->getMessage());
        }

        return $categories;
    }

    /**
     * Récupère tous les produits avec pagination optimisée
     * @param int $categoryId ID de catégorie (optionnel, 0 = toutes)
     * @param int $limit Nombre de produits par page
     * @return array Liste des produits avec [id, name, reference, price]
     */
    public function getAllProducts($categoryId = 0, $limit = 50) {
        $products = [];
        $offset = 0;

        try {
            // Première requête pour obtenir les IDs seulement
            $params = ['display' => '[id]', 'limit' => "$offset,$limit"];

            if ($categoryId > 0) {
                $params['filter[id_category_default]'] = $categoryId;
            }

            while (true) {
                $params['limit'] = "$offset,$limit";
                $result = $this->makeRequest('products', $params);

                if (!$result || !isset($result->products->product)) {
                    break;
                }

                $productIds = [];
                foreach ($result->products->product as $product) {
                    $productIds[] = (string)$product->id;
                }

                if (empty($productIds)) {
                    break;
                }

                // Récupère les détails par lot
                $detailsParams = [
                    'display' => '[id,name,reference,price]',
                    'filter[id]' => '[' . implode('|', $productIds) . ']'
                ];

                $details = $this->makeRequest('products', $detailsParams);

                if ($details && isset($details->products->product)) {
                    foreach ($details->products->product as $product) {
                        $id = (string)$product->id;
                        $name = isset($product->name->language[0]) ? (string)$product->name->language[0] : '';
                        $reference = (string)$product->reference;
                        $price = (string)$product->price;

                        $products[] = [
                            'id' => $id,
                            'name' => $name,
                            'reference' => $reference,
                            'price' => $price
                        ];
                    }
                }

                // Si on a récupéré moins que la limite, on a tout
                if (count($productIds) < $limit) {
                    break;
                }

                $offset += $limit;

                // Évite les boucles infinies
                if ($offset > 10000) {
                    break;
                }
            }

        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des produits: " . $e->getMessage());
        }

        return $products;
    }

    /**
     * Met à jour le prix d'un produit
     * @param int $productId ID du produit
     * @param float $newPrice Nouveau prix
     * @return bool
     */
    public function updateProductPrice($productId, $newPrice) {
        try {
            // Récupère le produit actuel
            $result = $this->makeRequest("products/$productId");

            if (!$result || !isset($result->product)) {
                throw new Exception("Produit non trouvé");
            }

            // Modifie le prix
            $result->product->price = $newPrice;

            // Supprime les champs en lecture seule qui causent des erreurs
            $readOnlyFields = [
                'manufacturer_name',
                'quantity',
                'position_in_category',
                'position'
            ];

            foreach ($readOnlyFields as $field) {
                if (isset($result->product->$field)) {
                    unset($result->product->$field);
                }
            }

            // Convertit en XML
            $xml = $result->asXML();

            // Met à jour le produit
            $updateResult = $this->makeRequest("products/$productId", [], 'PUT', $xml);

            return true;

        } catch (Exception $e) {
            throw new Exception("Erreur lors de la mise à jour du produit $productId: " . $e->getMessage());
        }
    }

    /**
     * Exporte les produits vers un fichier CSV
     * @param string $filename Nom du fichier CSV
     * @param int $categoryId ID de catégorie (0 = toutes)
     * @return array Informations sur l'export [count, filename]
     */
    public function exportToCSV($filename = 'products_export.csv', $categoryId = 0) {
        try {
            $products = $this->getAllProducts($categoryId);

            if (empty($products)) {
                throw new Exception("Aucun produit à exporter");
            }

            $fp = @fopen($filename, 'w');

            if (!$fp) {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Raison inconnue';
                throw new Exception("Impossible de créer le fichier CSV '$filename'. Erreur: $errorMsg");
            }

            // En-têtes CSV
            fputcsv($fp, ['ID', 'Nom', 'Référence', 'Prix'], ';');

            // Données
            foreach ($products as $product) {
                fputcsv($fp, [
                    $product['id'],
                    $product['name'],
                    $product['reference'],
                    $product['price']
                ], ';');
            }

            fclose($fp);

            return [
                'count' => count($products),
                'filename' => $filename
            ];

        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'export CSV: " . $e->getMessage());
        }
    }

    /**
     * Importe et met à jour les prix depuis un fichier CSV
     * @param string $filename Nom du fichier CSV
     * @return array Résultats de l'import [success, errors]
     */
    public function importFromCSV($filename) {
        $results = [
            'success' => 0,
            'errors' => [],
            'total' => 0
        ];

        try {
            if (!file_exists($filename)) {
                throw new Exception("Fichier CSV introuvable");
            }

            $fp = fopen($filename, 'r');

            if (!$fp) {
                throw new Exception("Impossible d'ouvrir le fichier CSV");
            }

            // Lit l'en-tête
            $header = fgetcsv($fp, 0, ';');

            // Vérifie le format
            if (!$header || count($header) < 4) {
                fclose($fp);
                throw new Exception("Format CSV invalide. Attendu: ID;Nom;Référence;Prix");
            }

            // Lit les données
            while (($data = fgetcsv($fp, 0, ';')) !== false) {
                $results['total']++;

                if (count($data) < 4) {
                    $results['errors'][] = "Ligne " . ($results['total'] + 1) . ": données incomplètes";
                    continue;
                }

                $id = trim($data[0]);
                $price = trim($data[3]);

                // Valide les données
                if (empty($id) || !is_numeric($id)) {
                    $results['errors'][] = "Ligne " . ($results['total'] + 1) . ": ID invalide";
                    continue;
                }

                if (empty($price) || !is_numeric($price)) {
                    $results['errors'][] = "Ligne " . ($results['total'] + 1) . ": prix invalide";
                    continue;
                }

                // Met à jour le produit
                try {
                    $this->updateProductPrice($id, $price);
                    $results['success']++;
                } catch (Exception $e) {
                    $results['errors'][] = "Produit $id: " . $e->getMessage();
                }
            }

            fclose($fp);

        } catch (Exception $e) {
            throw new Exception("Erreur lors de l'import CSV: " . $e->getMessage());
        }

        return $results;
    }
}
