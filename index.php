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
                // Réinitialise la validation à chaque nouveau test
                $_SESSION['connection_validated'] = false;

                require_once 'PrestaShopAPI.php';

                try {
                    $api = new PrestaShopAPI($_SESSION['shop_url'], $_SESSION['api_key'], false);

                    if ($api->testConnection()) {
                        echo '<div class="alert alert-success">✓ Connexion réussie !</div>';

                        // Vérifie les permissions
                        $permissions = $api->checkPermissions();

                        if ($permissions['success']) {
                            // Marque la connexion comme validée
                            $_SESSION['connection_validated'] = true;

                            echo '<div class="alert alert-success">';
                            echo '<strong>✓ Toutes les permissions sont configurées correctement :</strong><br>';
                            echo '<ul style="margin: 10px 0 0 20px;">';
                            if (isset($permissions['details']['products_get']) && $permissions['details']['products_get'])
                                echo '<li>✅ Lecture des produits (GET products)</li>';
                            if (isset($permissions['details']['products_put']) && $permissions['details']['products_put'])
                                echo '<li>✅ Modification des produits (PUT products)</li>';
                            if (isset($permissions['details']['combinations_get']) && $permissions['details']['combinations_get'])
                                echo '<li>✅ Lecture des déclinaisons (GET combinations)</li>';
                            if (isset($permissions['details']['combinations_put']) && $permissions['details']['combinations_put'])
                                echo '<li>✅ Modification des déclinaisons (PUT combinations)</li>';
                            if (isset($permissions['details']['product_option_values_get']) && $permissions['details']['product_option_values_get'])
                                echo '<li>✅ Lecture des attributs (GET product_option_values)</li>';
                            if (isset($permissions['details']['categories_get']) && $permissions['details']['categories_get'])
                                echo '<li>✅ Lecture des catégories (GET categories)</li>';
                            echo '</ul>';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-error">';
                            echo '<strong>✗ Permissions manquantes :</strong><br>';
                            echo '<ul style="margin: 10px 0 0 20px;">';
                            foreach ($permissions['errors'] as $error) {
                                echo '<li>' . htmlspecialchars($error) . '</li>';
                            }
                            echo '</ul>';
                            echo '<br><strong>Configuration requise dans le back-office PrestaShop :</strong><br>';
                            echo '<ul style="margin: 10px 0 0 20px;">';
                            echo '<li>products → GET ✅ + PUT ✅</li>';
                            echo '<li>combinations → GET ✅ + PUT ✅</li>';
                            echo '<li>product_option_values → GET ✅</li>';
                            echo '<li>categories → GET ✅</li>';
                            echo '</ul>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-error">✗ Échec de la connexion. Vérifiez vos paramètres.</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-error">✗ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>
        </div>

        <?php if (isset($_SESSION['connection_validated']) && $_SESSION['connection_validated'] === true): ?>
        <div class="actions-container">
            <div class="card action-card">
                <h3>📥 Exporter les produits</h3>
                <p>Téléchargez tous vos produits dans un fichier CSV</p>
                <a href="export.php" class="btn btn-success" style="text-decoration: none; display: inline-block;">
                    Exporter vers CSV
                </a>
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
        <p>PrestaShop CSV Manager - Version 1.1 - Compatible PrestaShop 8.2</p>
    </footer>

    <script>
        // Invalide la connexion si l'utilisateur modifie les champs
        const shopUrlInput = document.getElementById('shop_url');
        const apiKeyInput = document.getElementById('api_key');

        if (shopUrlInput && apiKeyInput) {
            const originalUrl = shopUrlInput.value;
            const originalKey = apiKeyInput.value;

            function checkChanges() {
                if (shopUrlInput.value !== originalUrl || apiKeyInput.value !== originalKey) {
                    // Envoie une requête pour invalider la session
                    fetch('invalidate_session.php', {
                        method: 'POST'
                    });
                }
            }

            shopUrlInput.addEventListener('input', checkChanges);
            apiKeyInput.addEventListener('input', checkChanges);
        }
    </script>
</body>
</html>
