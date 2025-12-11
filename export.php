<?php
session_start();

// Vérifie que la configuration existe
if (!isset($_SESSION['shop_url']) || !isset($_SESSION['api_key'])) {
    header('Location: index.php');
    exit;
}

require_once 'PrestaShopAPI.php';

$message = '';
$messageType = '';
$products = [];

if (isset($_POST['export'])) {
    try {
        $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

        // Récupère tous les produits
        $products = $api->getAllProducts();

        if (empty($products)) {
            $messageType = 'error';
            $message = 'Aucun produit trouvé dans votre boutique.';
        } else {
            // Génère le fichier CSV
            $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = __DIR__ . '/exports/' . $filename;

            // Crée le dossier exports s'il n'existe pas
            if (!is_dir(__DIR__ . '/exports')) {
                mkdir(__DIR__ . '/exports', 0755, true);
            }

            $api->exportToCSV($filepath);

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
            <p>Cliquez sur le bouton ci-dessous pour télécharger tous vos produits dans un fichier CSV.</p>

            <form method="POST" style="margin-top: 20px;">
                <button type="submit" name="export" class="btn btn-success">
                    📥 Télécharger le fichier CSV
                </button>
            </form>

            <div class="info-card" style="margin-top: 30px;">
                <h3>ℹ️ Informations sur l'export</h3>
                <p>Le fichier CSV contiendra les colonnes suivantes:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li><strong>ID:</strong> Identifiant unique du produit</li>
                    <li><strong>Nom:</strong> Nom du produit</li>
                    <li><strong>Référence:</strong> Référence du produit</li>
                    <li><strong>Prix:</strong> Prix de vente (HT)</li>
                </ul>
                <p style="margin-top: 15px;">
                    <strong>Format:</strong> CSV avec séparateur point-virgule (;)<br>
                    <strong>Encodage:</strong> UTF-8
                </p>
            </div>
        </div>

        <div class="card help-card">
            <h3>💡 Conseils</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Vous pouvez modifier les prix dans le fichier CSV avec Excel ou LibreOffice</li>
                <li>Ne modifiez pas les colonnes ID, Nom et Référence</li>
                <li>Utilisez le point comme séparateur décimal pour les prix (ex: 19.99)</li>
                <li>Après modification, utilisez la fonction "Importer" pour mettre à jour les prix</li>
            </ul>
        </div>
    </div>

    <footer>
        <p>PrestaShop CSV Manager - Version 1.0</p>
    </footer>
</body>
</html>
