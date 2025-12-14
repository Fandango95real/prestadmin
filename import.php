<?php
session_start();

// Vérifie que la connexion a été validée
if (!isset($_SESSION['connection_validated']) || $_SESSION['connection_validated'] !== true) {
    header('Location: index.php');
    exit;
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
            <p class="subtitle">Mettez à jour vos prix et stocks depuis un fichier CSV</p>
        </header>

        <div class="card">
            <a href="index.php" class="btn btn-primary" style="margin-bottom: 20px;">← Retour</a>

            <!-- Formulaire d'upload -->
            <div id="upload-section">

                <h2>Importer un fichier CSV</h2>
                <p>Sélectionnez un fichier CSV pour mettre à jour les prix et stocks de vos produits.</p>

                <form id="import-form" style="margin-top: 20px;">
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

                    <div class="form-group" style="margin-top: 20px;">
                        <label>Colonnes à mettre à jour</label>
                        <div style="margin-top: 10px;">
                            <label style="display: block; margin-bottom: 10px; cursor: pointer;">
                                <input type="checkbox" name="update_price" id="update_price" value="1" checked style="margin-right: 8px;">
                                <strong>Mettre à jour les prix</strong>
                            </label>
                            <label style="display: block; cursor: pointer;">
                                <input type="checkbox" name="update_stock" id="update_stock" value="1" style="margin-right: 8px;">
                                <strong>Mettre à jour les stocks</strong>
                            </label>
                        </div>
                        <small>Sélectionnez au moins une option pour activer l'import</small>
                    </div>

                    <button type="submit" id="import_btn" class="btn btn-warning">
                        📤 Importer et mettre à jour
                    </button>
                </form>
            </div>

            <!-- Interface de progression (cachée par défaut) -->
            <div id="progress-section" style="display: none;">
                <h2>⏳ Import en cours...</h2>

                <div class="stats">
                    <div class="stat-box">
                        <div class="stat-number" id="stat-total">0</div>
                        <div class="stat-label">Produits à traiter</div>
                    </div>
                    <div class="stat-box" style="border-left-color: #3b82f6;">
                        <div class="stat-number" style="color: #3b82f6;" id="stat-processed">0</div>
                        <div class="stat-label">Traités</div>
                    </div>
                    <div class="stat-box" style="border-left-color: #10b981;">
                        <div class="stat-number" style="color: #10b981;" id="stat-success">0</div>
                        <div class="stat-label">Réussis</div>
                    </div>
                    <div class="stat-box" style="border-left-color: #ef4444;">
                        <div class="stat-number" style="color: #ef4444;" id="stat-errors">0</div>
                        <div class="stat-label">Erreurs</div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <div style="background: #e5e7eb; border-radius: 8px; height: 30px; overflow: hidden;">
                        <div id="progress-bar" style="background: linear-gradient(90deg, #3b82f6, #10b981); height: 100%; width: 0%; transition: width 0.3s ease;"></div>
                    </div>
                    <p id="progress-text" style="text-align: center; margin-top: 10px; color: #6b7280;">Préparation...</p>
                </div>

                <div id="error-list" style="margin-top: 20px; display: none;">
                    <h3>📋 Erreurs rencontrées</h3>
                    <div id="error-container" style="max-height: 300px; overflow-y: auto; margin-top: 10px;"></div>
                </div>
            </div>

            <!-- Résultats finaux (cachés par défaut) -->
            <div id="results-section" style="display: none;">
                <div id="final-message"></div>
                <div style="margin-top: 20px;">
                    <button onclick="location.reload()" class="btn btn-primary">Importer un autre fichier</button>
                </div>
            </div>
        </div>

        <div class="card help-card">
            <h3>⚠️ Attention</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Les prix et stocks seront mis à jour immédiatement dans votre boutique</li>
                <li>Il est recommandé de faire un export avant l'import pour sauvegarder vos données</li>
                <li>Vérifiez bien le format de votre fichier CSV avant l'import</li>
                <li>Les prix sont en Hors Taxes (HT)</li>
                <li>Les quantités doivent être des nombres entiers positifs (0 ou plus)</li>
                <li><strong>Déclinaisons:</strong> Le prix et stock de chaque déclinaison peuvent être mis à jour individuellement</li>
                <li>Le traitement se fait par lots de 25 produits pour éviter les timeouts</li>
                <li>Vous pouvez suivre la progression en temps réel pendant l'import</li>
            </ul>
        </div>

        <div class="card help-card" style="background: #f0f9ff; border-left-color: #3b82f6;">
            <h3>💡 Astuce</h3>
            <p>Pour modifier rapidement vos prix et stocks:</p>
            <ol style="margin-left: 20px; margin-top: 10px;">
                <li>Exportez vos produits en CSV (avec ou sans déclinaisons)</li>
                <li>Ouvrez le fichier avec Excel ou LibreOffice</li>
                <li>Modifiez les colonnes "Prix" et/ou "Quantité" selon vos besoins</li>
                <li>Enregistrez le fichier (format CSV, séparateur point-virgule)</li>
                <li>Importez le fichier sur cette page en sélectionnant les options appropriées</li>
                <li><strong>Astuce Pro:</strong> Vous pouvez ne modifier que les prix ou que les stocks en décochant une option</li>
            </ol>
        </div>
    </div>

    <footer>
        <p>PrestaShop CSV Manager - Version 1.2</p>
    </footer>

    <script>
        // Gestion de l'activation/désactivation du bouton d'import
        const updatePriceCheckbox = document.getElementById('update_price');
        const updateStockCheckbox = document.getElementById('update_stock');
        const importBtn = document.getElementById('import_btn');
        const importForm = document.getElementById('import-form');
        const csvFileInput = document.getElementById('csv_file');

        if (updatePriceCheckbox && updateStockCheckbox && importBtn) {
            function updateImportButtonState() {
                const atLeastOneChecked = updatePriceCheckbox.checked || updateStockCheckbox.checked;
                importBtn.disabled = !atLeastOneChecked;

                if (!atLeastOneChecked) {
                    importBtn.style.opacity = '0.5';
                    importBtn.style.cursor = 'not-allowed';
                } else {
                    importBtn.style.opacity = '1';
                    importBtn.style.cursor = 'pointer';
                }
            }

            updatePriceCheckbox.addEventListener('change', updateImportButtonState);
            updateStockCheckbox.addEventListener('change', updateImportButtonState);

            // État initial
            updateImportButtonState();
        }

        // Gestion du formulaire d'import
        if (importForm) {
            importForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const file = csvFileInput.files[0];
                if (!file) {
                    alert('Veuillez sélectionner un fichier CSV');
                    return;
                }

                const updatePrice = updatePriceCheckbox.checked;
                const updateStock = updateStockCheckbox.checked;

                if (!updatePrice && !updateStock) {
                    alert('Veuillez sélectionner au moins une option à mettre à jour');
                    return;
                }

                // Masque le formulaire et affiche la progression
                document.getElementById('upload-section').style.display = 'none';
                document.getElementById('progress-section').style.display = 'block';

                try {
                    // Lit le fichier CSV
                    const csvContent = await readFileAsText(file);

                    // Parse le CSV
                    const rows = parseCSV(csvContent);

                    if (rows.length === 0) {
                        throw new Error('Le fichier CSV est vide');
                    }

                    // La première ligne contient les en-têtes
                    const headers = rows[0];
                    const dataRows = rows.slice(1);

                    if (dataRows.length === 0) {
                        throw new Error('Le fichier CSV ne contient aucune donnée');
                    }

                    // Convertit les lignes en objets
                    const products = dataRows.map(row => {
                        const obj = {};
                        headers.forEach((header, index) => {
                            obj[header] = row[index] || '';
                        });
                        return obj;
                    });

                    // Initialise les stats
                    document.getElementById('stat-total').textContent = products.length;

                    // Traite par lots de 25 produits
                    const batchSize = 25;
                    const batches = [];
                    for (let i = 0; i < products.length; i += batchSize) {
                        batches.push(products.slice(i, i + batchSize));
                    }

                    let totalProcessed = 0;
                    let totalSuccess = 0;
                    const allErrors = [];

                    // Traite chaque lot séquentiellement
                    for (let i = 0; i < batches.length; i++) {
                        const batch = batches[i];
                        const batchNum = i + 1;

                        // Met à jour le texte de progression
                        document.getElementById('progress-text').textContent =
                            `Traitement du lot ${batchNum}/${batches.length}...`;

                        try {
                            // Envoie le lot au serveur
                            const response = await fetch('import_batch.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    batch: batch,
                                    updatePrice: updatePrice,
                                    updateStock: updateStock
                                })
                            });

                            if (!response.ok) {
                                throw new Error(`Erreur HTTP: ${response.status}`);
                            }

                            const result = await response.json();

                            if (result.error) {
                                throw new Error(result.error);
                            }

                            // Met à jour les stats
                            totalProcessed += result.processed;
                            totalSuccess += result.success;
                            allErrors.push(...result.errors);

                            document.getElementById('stat-processed').textContent = totalProcessed;
                            document.getElementById('stat-success').textContent = totalSuccess;
                            document.getElementById('stat-errors').textContent = allErrors.length;

                            // Met à jour la barre de progression
                            const progress = (totalProcessed / products.length) * 100;
                            document.getElementById('progress-bar').style.width = progress + '%';

                            // Affiche les erreurs si nécessaire
                            if (result.errors.length > 0) {
                                const errorList = document.getElementById('error-list');
                                const errorContainer = document.getElementById('error-container');
                                errorList.style.display = 'block';

                                result.errors.forEach(error => {
                                    const errorDiv = document.createElement('div');
                                    errorDiv.style.padding = '8px';
                                    errorDiv.style.background = '#fee2e2';
                                    errorDiv.style.marginBottom = '5px';
                                    errorDiv.style.borderRadius = '4px';
                                    errorDiv.style.color = '#991b1b';
                                    errorDiv.textContent = error;
                                    errorContainer.appendChild(errorDiv);
                                });
                            }

                        } catch (error) {
                            console.error('Erreur lors du traitement du lot:', error);
                            allErrors.push(`Erreur lot ${batchNum}: ${error.message}`);
                            document.getElementById('stat-errors').textContent = allErrors.length;
                        }
                    }

                    // Affiche les résultats finaux
                    document.getElementById('progress-section').style.display = 'none';
                    document.getElementById('results-section').style.display = 'block';

                    const finalMessage = document.getElementById('final-message');
                    if (totalSuccess > 0) {
                        finalMessage.innerHTML = `
                            <div class="alert alert-success">
                                ✓ Import terminé avec succès ! ${totalSuccess} produit(s) mis à jour sur ${products.length}.
                            </div>
                        `;
                    } else {
                        finalMessage.innerHTML = `
                            <div class="alert alert-error">
                                ✗ Aucun produit n'a pu être mis à jour.
                            </div>
                        `;
                    }

                    if (allErrors.length > 0) {
                        finalMessage.innerHTML += `
                            <div style="margin-top: 20px;">
                                <h3>📋 Détails des erreurs (${allErrors.length})</h3>
                                <div style="max-height: 300px; overflow-y: auto; margin-top: 10px;">
                                    ${allErrors.map(error => `
                                        <div style="padding: 8px; background: #fee2e2; margin-bottom: 5px; border-radius: 4px; color: #991b1b;">
                                            ${error}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                } catch (error) {
                    console.error('Erreur:', error);
                    document.getElementById('progress-section').style.display = 'none';
                    document.getElementById('results-section').style.display = 'block';
                    document.getElementById('final-message').innerHTML = `
                        <div class="alert alert-error">
                            ✗ Erreur: ${error.message}
                        </div>
                    `;
                }
            });
        }

        // Fonction pour lire un fichier comme texte
        function readFileAsText(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = (e) => reject(new Error('Erreur lors de la lecture du fichier'));
                reader.readAsText(file, 'UTF-8');
            });
        }

        // Fonction pour parser un CSV (séparateur point-virgule)
        function parseCSV(csvContent) {
            // Supprime le BOM UTF-8 si présent
            if (csvContent.charCodeAt(0) === 0xFEFF) {
                csvContent = csvContent.substr(1);
            }

            const lines = csvContent.split('\n');
            const result = [];

            for (let line of lines) {
                // Ignore les lignes vides
                line = line.trim();
                if (line === '') continue;

                // Parse la ligne (séparateur point-virgule)
                // Gère les champs entre guillemets
                const fields = [];
                let currentField = '';
                let inQuotes = false;

                for (let i = 0; i < line.length; i++) {
                    const char = line[i];

                    if (char === '"') {
                        inQuotes = !inQuotes;
                    } else if (char === ';' && !inQuotes) {
                        fields.push(currentField.trim());
                        currentField = '';
                    } else {
                        currentField += char;
                    }
                }

                // Ajoute le dernier champ
                fields.push(currentField.trim());

                result.push(fields);
            }

            return result;
        }
    </script>
</body>
</html>
