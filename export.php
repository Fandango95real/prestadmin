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

            <!-- Formulaire d'export -->
            <div id="export-form-section">
                <h2>Exporter les produits</h2>
                <p>Sélectionnez une catégorie ou exportez tous les produits.</p>

                <form id="export-form" style="margin-top: 20px;">
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

                <button type="submit" id="export_btn" class="btn btn-success">
                    📥 Télécharger le fichier CSV
                </button>
            </form>
            </div>

            <!-- Interface de progression (cachée par défaut) -->
            <div id="progress-section" style="display: none;">
                <h2>⏳ Export en cours...</h2>

                <div class="stats">
                    <div class="stat-box" style="border-left-color: #3b82f6;">
                        <div class="stat-number" style="color: #3b82f6;" id="stat-retrieved">0</div>
                        <div class="stat-label">Produits récupérés</div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <div style="background: #e5e7eb; border-radius: 8px; height: 30px; overflow: hidden;">
                        <div id="progress-bar" style="background: linear-gradient(90deg, #3b82f6, #10b981); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                    </div>
                    <p id="progress-text" style="text-align: center; margin-top: 10px; color: #6b7280;">Récupération des produits...</p>
                </div>

                <div id="error-message" style="margin-top: 20px; display: none;">
                    <div class="alert alert-error" id="error-content"></div>
                </div>
            </div>

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
                        <li><strong>Quantité:</strong> Stock disponible</li>
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
                        <li><strong>Quantité:</strong> Stock disponible pour cette déclinaison</li>
                    </ul>
                    <p style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-left: 3px solid #3498db;">
                        <strong>Note:</strong> Ce format permet de gérer les prix, stocks et attributs de chaque déclinaison individuellement
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
                <li>Vous pouvez modifier les prix et quantités dans le fichier CSV avec Excel ou LibreOffice</li>
                <li>Ne modifiez pas les colonnes ID, ProductID, CombinationID, Nom et Référence</li>
                <li>Les prix utilisent la virgule comme séparateur décimal (format français)</li>
                <li>Les quantités doivent être des nombres entiers positifs (0 ou plus)</li>
                <li><strong>Export avec déclinaisons:</strong> Un produit avec 3 tailles et 2 couleurs générera 6 lignes dans le CSV</li>
                <li><strong>Produits simples dans export déclinaisons:</strong> Ils apparaîtront avec CombinationID = 0</li>
                <li>Après modification, utilisez la fonction "Importer" pour mettre à jour les prix et/ou stocks</li>
            </ul>
        </div>
    </div>

    <footer>
        <p>PrestaShop CSV Manager - Version 1.2</p>
    </footer>

    <script>
        const exportForm = document.getElementById('export-form');
        const exportBtn = document.getElementById('export_btn');

        if (exportForm) {
            exportForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const categoryId = parseInt(document.getElementById('category_id').value);
                const exportType = document.querySelector('input[name="export_type"]:checked').value;

                // Masque le formulaire et affiche la progression
                document.getElementById('export-form-section').style.display = 'none';
                document.getElementById('progress-section').style.display = 'block';

                try {
                    // Étape 1: Compte le nombre total d'articles
                    document.getElementById('progress-text').textContent = 'Comptage des articles...';

                    const countResponse = await fetch('export_batch.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            countOnly: true,
                            exportType: exportType,
                            categoryId: categoryId
                        })
                    });

                    if (!countResponse.ok) {
                        throw new Error(`Erreur HTTP lors du comptage: ${countResponse.status}`);
                    }

                    const countResult = await countResponse.json();
                    if (countResult.error) {
                        throw new Error(countResult.error);
                    }

                    const totalArticles = countResult.total;
                    console.log(`Total d'articles à exporter: ${totalArticles}`);

                    if (totalArticles === 0) {
                        throw new Error('Aucun produit à exporter');
                    }

                    // Étape 2: Récupère les produits par lots
                    const allProducts = [];
                    let offset = 0;
                    const batchSize = 10; // 10 articles par lot pour mise à jour fréquente
                    let hasMore = true;
                    let totalRetrieved = 0;

                    let batchNumber = 0;
                    while (hasMore) {
                        batchNumber++;
                        document.getElementById('progress-text').textContent =
                            `Export: ${totalRetrieved}/${totalArticles} articles (lot ${batchNumber})`;

                        console.log(`Lot ${batchNumber}: offset=${offset}, limit=${batchSize}, categoryId=${categoryId}`);

                        const response = await fetch('export_batch.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                offset: offset,
                                limit: batchSize,
                                exportType: exportType,
                                categoryId: categoryId
                            })
                        });

                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }

                        const result = await response.json();
                        console.log(`Résultat lot ${batchNumber}:`, result);

                        if (result.error) {
                            throw new Error(result.error);
                        }

                        // Ajoute les produits récupérés
                        allProducts.push(...result.products);
                        totalRetrieved += result.count;

                        // Met à jour l'affichage
                        document.getElementById('stat-retrieved').textContent = totalRetrieved;

                        // Met à jour la barre de progression
                        const progress = (totalRetrieved / totalArticles) * 100;
                        document.getElementById('progress-bar').style.width = progress + '%';

                        // Vérifie s'il y a encore des produits
                        hasMore = result.hasMore;
                        console.log(`hasMore=${hasMore}, count=${result.count}, totalRetrieved=${totalRetrieved}`);
                        offset += batchSize;

                        // Petite pause pour éviter de surcharger le serveur
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }

                    // Génère le fichier CSV
                    document.getElementById('progress-text').textContent =
                        `Génération du fichier CSV (${totalRetrieved}/${totalArticles} articles)...`;

                    const csvContent = generateCSV(allProducts, exportType);

                    // Télécharge le fichier
                    downloadCSV(csvContent, exportType, categoryId);

                    // Affiche le message de succès
                    document.getElementById('progress-text').textContent =
                        `✓ Export terminé ! ${totalRetrieved} produit(s) exporté(s)`;
                    document.getElementById('progress-bar').style.width = '100%';

                    // Redirige vers la page d'accueil après 2 secondes
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);

                } catch (error) {
                    console.error('Erreur:', error);
                    document.getElementById('error-message').style.display = 'block';
                    document.getElementById('error-content').textContent =
                        'Erreur lors de l\'export: ' + error.message;
                    document.getElementById('progress-text').textContent =
                        '✗ Export échoué';
                }
            });
        }

        // Génère le contenu CSV
        function generateCSV(products, exportType) {
            let csv = '\uFEFF'; // BOM UTF-8

            if (exportType === 'combinations') {
                // En-tête pour export avec déclinaisons
                csv += 'ProductID;CombinationID;ProductName;CombinationName;Reference;Price;Quantité\n';

                // Lignes de données
                products.forEach(product => {
                    csv += [
                        product.ProductID,
                        product.CombinationID,
                        escapeCSV(product.ProductName),
                        escapeCSV(product.CombinationName),
                        escapeCSV(product.Reference),
                        product.Price,
                        product.Quantité
                    ].join(';') + '\n';
                });
            } else {
                // En-tête pour export standard
                csv += 'ID;Nom;Référence;Prix;Quantité\n';

                // Lignes de données
                products.forEach(product => {
                    csv += [
                        product.ID,
                        escapeCSV(product.Nom),
                        escapeCSV(product.Référence),
                        product.Prix,
                        product.Quantité
                    ].join(';') + '\n';
                });
            }

            return csv;
        }

        // Échappe les valeurs CSV (gère les guillemets et point-virgules)
        function escapeCSV(value) {
            if (!value) return '';
            value = String(value);
            if (value.includes(';') || value.includes('"') || value.includes('\n')) {
                return '"' + value.replace(/"/g, '""') + '"';
            }
            return value;
        }

        // Télécharge le fichier CSV
        function downloadCSV(csvContent, exportType, categoryId) {
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');

            // Nom du fichier
            const date = new Date().toISOString().slice(0, 10);
            const catSuffix = categoryId > 0 ? `_cat${categoryId}` : '_all';
            const typeSuffix = exportType === 'combinations' ? '_combinations' : '';
            const filename = `products${catSuffix}${typeSuffix}_${date}.csv`;

            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.style.display = 'none';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
