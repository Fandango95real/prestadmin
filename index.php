<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrestaShop CSV Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>PrestaShop CSV Manager</h1>
            <p class="subtitle">Exportez et importez vos produits via CSV</p>
        </header>

        <div class="card">
            <h2>Connexion à votre boutique PrestaShop</h2>

            <form id="configForm" method="POST" action="">
                <div class="form-group">
                    <label for="shop_url">URL de votre boutique</label>
                    <input
                        type="text"
                        id="shop_url"
                        name="shop_url"
                        placeholder="https://monsite.com"
                        value="<?php echo isset($_SESSION['shop_url']) ? htmlspecialchars($_SESSION['shop_url']) : ''; ?>"
                        required
                    >
                    <small>L'URL complète de votre boutique PrestaShop</small>
                </div>

                <div class="form-group">
                    <label for="api_key">Clé API</label>
                    <input
                        type="text"
                        id="api_key"
                        name="api_key"
                        placeholder="VOTRE_CLE_API_PRESTASHOP"
                        value="<?php echo isset($_SESSION['api_key']) ? htmlspecialchars($_SESSION['api_key']) : ''; ?>"
                        required
                    >
                    <small>Trouvez votre clé API dans: Configuration avancée > Web Service</small>
                </div>

                <button type="submit" name="save_config" class="btn btn-primary">
                    Tester la connexion
                </button>
            </form>

            <?php
            if (isset($_POST['save_config'])) {
                $_SESSION['shop_url'] = trim($_POST['shop_url']);
                $_SESSION['api_key'] = trim($_POST['api_key']);

                require_once 'PrestaShopAPI.php';

                try {
                    $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

                    if ($api->testConnection()) {
                        echo '<div class="alert alert-success">✓ Connexion réussie !</div>';
                    } else {
                        echo '<div class="alert alert-error">✗ Échec de la connexion. Vérifiez vos paramètres.</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-error">✗ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>
        </div>

        <?php if (isset($_SESSION['shop_url']) && isset($_SESSION['api_key'])): ?>
        <div class="actions-container">
            <div class="card action-card">
                <h3>📥 Exporter les produits</h3>
                <p>Téléchargez tous vos produits dans un fichier CSV</p>
                <form method="POST" action="export.php">
                    <button type="submit" name="export" class="btn btn-success">
                        Exporter vers CSV
                    </button>
                </form>
            </div>

            <div class="card action-card">
                <h3>📤 Importer / Mettre à jour</h3>
                <p>Mettez à jour les prix de vos produits depuis un fichier CSV</p>
                <form method="POST" action="import.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <input
                            type="file"
                            name="csv_file"
                            accept=".csv"
                            required
                            class="file-input"
                        >
                    </div>
                    <button type="submit" name="import" class="btn btn-warning">
                        Importer CSV
                    </button>
                </form>
            </div>
        </div>

        <div class="card info-card">
            <h3>ℹ️ Format du fichier CSV</h3>
            <p>Le fichier CSV doit contenir les colonnes suivantes (séparées par des points-virgules) :</p>
            <div class="code-block">
                ID;Nom;Référence;Prix<br>
                1;Produit exemple;REF001;19.99<br>
                2;Autre produit;REF002;29.99
            </div>
            <p><strong>Important:</strong> Seul le prix sera modifié lors de l'import. Les autres colonnes servent à identifier le produit.</p>
        </div>
        <?php endif; ?>

        <div class="card help-card">
            <h3>🔑 Comment obtenir votre clé API PrestaShop ?</h3>
            <ol>
                <li>Connectez-vous à votre back-office PrestaShop</li>
                <li>Allez dans <strong>Configuration avancée > Web Service</strong></li>
                <li>Activez le service web si ce n'est pas déjà fait</li>
                <li>Cliquez sur <strong>Ajouter une nouvelle clé</strong></li>
                <li>Donnez un nom à la clé et activez les permissions pour "products" (GET et PUT)</li>
                <li>Copiez la clé générée</li>
            </ol>
        </div>
    </div>

    <footer>
        <p>PrestaShop CSV Manager - Version 1.0 - Compatible PrestaShop 8.2</p>
    </footer>
</body>
</html>
