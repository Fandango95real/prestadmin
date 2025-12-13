<?php
session_start();

// Augmente les timeouts pour éviter les erreurs 524
set_time_limit(600); // 10 minutes
ini_set('max_execution_time', 600);

// Vérifie que la connexion a été validée
if (!isset($_SESSION['connection_validated']) || $_SESSION['connection_validated'] !== true) {
    header('Location: index.php');
    exit;
}

require_once 'PrestaShopAPI.php';

$message = '';
$messageType = '';
$categories = [];

// Charge les catégories au chargement de la page
try {
    $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);
    $categories = $api->getAllCategories();
} catch (Exception $e) {
    $messageType = 'error';
    $message = 'Erreur lors du chargement des catégories: ' . $e->getMessage();
}

if (isset($_POST['export'])) {
    try {
        $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

        $categoryId = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $exportType = isset($_POST['export_type']) ? $_POST['export_type'] : 'standard';

        // Génère le fichier CSV
        $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';
        $exportsDir = __DIR__ . '/exports';
        $filepath = $exportsDir . '/' . $filename;

        // Crée le dossier exports s'il n'existe pas
        if (!is_dir($exportsDir)) {
            if (!mkdir($exportsDir, 0777, true)) {
                throw new Exception("Impossible de créer le dossier exports. Vérifiez les permissions.");
            }
            chmod($exportsDir, 0777);
        }

        // Vérifie les permissions d'écriture
        if (!is_writable($exportsDir)) {
            throw new Exception("Le dossier exports n'est pas accessible en écriture. Permissions actuelles: " . substr(sprintf('%o', fileperms($exportsDir)), -4));
        }

        // Choix du type d'export
        if ($exportType === 'combinations') {
            $result = $api->exportCombinationsToCSV($filepath, $categoryId);
        } else {
            $result = $api->exportToCSV($filepath, $categoryId);
        }

        if ($result['count'] == 0) {
            $messageType = 'error';
            $message = 'Aucun produit trouvé dans cette catégorie.';
        } else {
            // Télécharge le fichier
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);

            // Supprime le fichier après téléchargement
            unlink($filepath);
            exit;
        }

    } catch (Exception $e) {
        $messageType = 'error';
        $message = 'Erreur: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export CSV - PrestaShop CSV Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📥 Export des produits</h1>
            <p class="subtitle">Téléchargez vos produits au format CSV</p>
        </header>

        <div class="card">
            <a href="index.php" class="btn btn-primary" style="margin-bottom: 20px;">← Retour</a>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <h2>Exporter les produits</h2>
            <p>Sélectionnez une catégorie ou exportez tous les produits.</p>

            <form method="POST" style="margin-top: 20px;">
                <div class="form-group">
                    <label for="category_id">Catégorie</label>
                    <select id="category_id" name="category_id" class="select-input">
                        <option value="0">📦 Tous les produits</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Choisissez une catégorie spécifique ou exportez tous les produits</small>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Type d'export</label>
                    <div style="margin-top: 10px;">
                        <label style="display: block; margin-bottom: 10px; cursor: pointer;">
                            <input type="radio" name="export_type" value="standard" checked style="margin-right: 8px;">
                            <strong>Export standard</strong> - Produits simples uniquement (ID, Nom, Référence, Prix)
                        </label>
                        <label style="display: block; cursor: pointer;">
                            <input type="radio" name="export_type" value="combinations" style="margin-right: 8px;">
                            <strong>Export avec déclinaisons</strong> - Inclut toutes les déclinaisons de produits (Taille, Couleur, etc.)
                        </label>
                    </div>
                    <small>Choisissez "avec déclinaisons" pour gérer finement les prix de chaque variante</small>
                </div>

                <button type="submit" name="export" class="btn btn-success">
                    📥 Télécharger le fichier CSV
                </button>
            </form>

            <div class="info-card" style="margin-top: 30px;">
                <h3>ℹ️ Informations sur l'export</h3>

                <div style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px;">📦 Export standard</h4>
                    <p>Le fichier CSV contiendra les colonnes suivantes:</p>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <li><strong>ID:</strong> Identifiant unique du produit</li>
                        <li><strong>Nom:</strong> Nom du produit</li>
                        <li><strong>Référence:</strong> Référence du produit</li>
                        <li><strong>Prix:</strong> Prix de vente (HT)</li>
                    </ul>
                </div>

                <div>
                    <h4 style="margin-bottom: 10px;">🎨 Export avec déclinaisons</h4>
                    <p>Le fichier CSV contiendra les colonnes suivantes:</p>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <li><strong>ProductID:</strong> Identifiant unique du produit</li>
                        <li><strong>CombinationID:</strong> Identifiant de la déclinaison (0 pour produit simple)</li>
                        <li><strong>ProductName:</strong> Nom du produit</li>
                        <li><strong>CombinationName:</strong> Nom de la déclinaison (ex: "Rouge - S")</li>
                        <li><strong>Reference:</strong> Référence de la déclinaison</li>
                        <li><strong>Price:</strong> Prix de vente (HT)</li>
                    </ul>
                    <p style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-left: 3px solid #3498db;">
                        <strong>Note:</strong> Ce format permet de gérer les prix fixes et relatifs de chaque déclinaison
                    </p>
                </div>

                <p style="margin-top: 15px;">
                    <strong>Format:</strong> CSV avec séparateur point-virgule (;)<br>
                    <strong>Encodage:</strong> UTF-8<br>
                    <strong>Séparateur décimal:</strong> Virgule (,) - Format français compatible Excel
                </p>
            </div>
        </div>

        <div class="card help-card">
            <h3>💡 Conseils</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Pour les boutiques avec beaucoup de produits, exportez par catégorie</li>
                <li>Vous pouvez modifier les prix dans le fichier CSV avec Excel ou LibreOffice</li>
                <li>Ne modifiez pas les colonnes ID, ProductID, CombinationID, Nom et Référence</li>
                <li>Les prix utilisent la virgule comme séparateur décimal (format français)</li>
                <li><strong>Export avec déclinaisons:</strong> Un produit avec 3 tailles et 2 couleurs générera 6 lignes dans le CSV</li>
                <li><strong>Produits simples dans export déclinaisons:</strong> Ils apparaîtront avec CombinationID = 0</li>
                <li>Après modification, utilisez la fonction "Importer" pour mettre à jour les prix</li>
            </ul>
        </div>
    </div>

    <footer>
        <p>PrestaShop CSV Manager - Version 1.0</p>
    </footer>
</body>
</html>
