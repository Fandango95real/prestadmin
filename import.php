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
$results = null;
$details = [];

if (isset($_POST['import']) && isset($_FILES['csv_file'])) {
    try {
        // Vérifie le fichier uploadé
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erreur lors de l\'upload du fichier');
        }

        $tmpFile = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];

        // Vérifie l'extension
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            throw new Exception('Le fichier doit être au format CSV');
        }

        // Crée l'instance API
        $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

        // Importe le fichier
        $results = $api->importFromCSV($tmpFile);

        if ($results['success'] > 0) {
            $messageType = 'success';
            $message = "✓ Import terminé avec succès ! {$results['success']} produit(s) mis à jour sur {$results['total']}.";
        } else {
            $messageType = 'error';
            $message = "✗ Aucun produit n'a pu être mis à jour.";
        }

        $details = $results['errors'];

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
    <title>Import CSV - PrestaShop CSV Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📤 Import des produits</h1>
            <p class="subtitle">Mettez à jour vos prix depuis un fichier CSV</p>
        </header>

        <div class="card">
            <a href="index.php" class="btn btn-primary" style="margin-bottom: 20px;">← Retour</a>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($results): ?>
                <div class="stats">
                    <div class="stat-box">
                        <div class="stat-number"><?php echo $results['total']; ?></div>
                        <div class="stat-label">Produits traités</div>
                    </div>
                    <div class="stat-box" style="border-left-color: #10b981;">
                        <div class="stat-number" style="color: #10b981;"><?php echo $results['success']; ?></div>
                        <div class="stat-label">Mis à jour</div>
                    </div>
                    <div class="stat-box" style="border-left-color: #ef4444;">
                        <div class="stat-number" style="color: #ef4444;"><?php echo count($results['errors']); ?></div>
                        <div class="stat-label">Erreurs</div>
                    </div>
                </div>

                <?php if (!empty($details)): ?>
                    <div style="margin-top: 20px;">
                        <h3>📋 Détails des erreurs</h3>
                        <div style="max-height: 300px; overflow-y: auto; margin-top: 10px;">
                            <?php foreach ($details as $error): ?>
                                <div style="padding: 8px; background: #fee2e2; margin-bottom: 5px; border-radius: 4px; color: #991b1b;">
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 20px;">
                    <a href="import.php" class="btn btn-primary">Importer un autre fichier</a>
                </div>

            <?php else: ?>

                <h2>Importer un fichier CSV</h2>
                <p>Sélectionnez un fichier CSV pour mettre à jour les prix de vos produits.</p>

                <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="csv_file">Fichier CSV</label>
                        <input
                            type="file"
                            id="csv_file"
                            name="csv_file"
                            accept=".csv"
                            required
                            class="file-input"
                        >
                        <small>Format accepté: CSV (séparateur point-virgule)</small>
                    </div>

                    <button type="submit" name="import" class="btn btn-warning">
                        📤 Importer et mettre à jour
                    </button>
                </form>

                <div class="info-card" style="margin-top: 30px;">
                    <h3>📋 Format du fichier CSV requis</h3>
                    <p>Votre fichier CSV doit respecter le format suivant:</p>

                    <div class="code-block">
                        ID;Nom;Référence;Prix<br>
                        1;Produit exemple;REF001;19.99<br>
                        2;Autre produit;REF002;29.99<br>
                        3;Troisième produit;REF003;39.50
                    </div>

                    <ul style="margin-left: 20px; margin-top: 15px;">
                        <li><strong>Séparateur:</strong> Point-virgule (;)</li>
                        <li><strong>Première ligne:</strong> En-têtes (ID;Nom;Référence;Prix)</li>
                        <li><strong>Prix:</strong> Utilisez le point comme séparateur décimal</li>
                        <li><strong>Important:</strong> Seule la colonne "Prix" sera modifiée</li>
                    </ul>
                </div>

            <?php endif; ?>
        </div>

        <div class="card help-card">
            <h3>⚠️ Attention</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Les prix seront mis à jour immédiatement dans votre boutique</li>
                <li>Il est recommandé de faire un export avant l'import pour sauvegarder vos données</li>
                <li>Vérifiez bien le format de votre fichier CSV avant l'import</li>
                <li>Les prix sont en Hors Taxes (HT)</li>
                <li>L'opération peut prendre plusieurs secondes selon le nombre de produits</li>
            </ul>
        </div>

        <div class="card help-card" style="background: #f0f9ff; border-left-color: #3b82f6;">
            <h3>💡 Astuce</h3>
            <p>Pour modifier rapidement vos prix:</p>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Exportez vos produits en CSV</li>
                <li>Ouvrez le fichier avec Excel ou LibreOffice</li>
                <li>Modifiez uniquement la colonne "Prix"</li>
                <li>Enregistrez le fichier (format CSV, séparateur point-virgule)</li>
                <li>Importez le fichier sur cette page</li>
            </ol>
        </div>
    </div>

    <footer>
        <p>PrestaShop CSV Manager - Version 1.0</p>
    </footer>
</body>
</html>
