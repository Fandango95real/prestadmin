# PrestaShop CSV Manager

Application web PHP pour gérer vos produits PrestaShop via des fichiers CSV. Compatible avec PrestaShop 8.2.

## 🎯 Fonctionnalités

- **Export CSV** : Téléchargez tous vos produits (ID, nom, référence, prix, stock) dans un fichier CSV
- **Gestion des stocks** : Gérez les quantités de vos produits et déclinaisons
- **Gestion des déclinaisons** : Support complet des produits avec déclinaisons (tailles, couleurs, etc.)
- **Export avec déclinaisons** : Exportez tous les produits avec leurs déclinaisons pour une gestion fine des prix et stocks
- **Filtre par catégorie** : Exportez tous les produits ou seulement ceux d'une catégorie spécifique
- **Import CSV sélectif** : Mettez à jour les prix et/ou les stocks selon vos besoins
- **Import par lots** : Traitement par lots de 25 produits pour éviter les timeouts
- **Progression en temps réel** : Suivez l'avancement de l'import avec barre de progression et compteurs
- **Détection automatique du format** : L'import détecte automatiquement le format (standard ou avec déclinaisons)
- **Compatibilité Excel français** : Export avec BOM UTF-8 pour ouverture directe dans Excel
- **Pagination optimisée** : Gestion efficace des boutiques avec des milliers de produits
- **Interface intuitive** : Interface web simple et moderne
- **API PrestaShop** : Utilise l'API REST native de PrestaShop

## 📋 Prérequis

- PHP 7.4 ou supérieur
- Extension PHP cURL activée
- PrestaShop 8.2 avec l'API Web Service activée
- Clé API PrestaShop avec les permissions sur les produits

## 🚀 Installation

1. **Clonez ou téléchargez le projet**
   ```bash
   git clone https://github.com/Fandango95real/prestadmin.git
   cd prestadmin
   ```

2. **Configurez votre serveur web**
   - Placez les fichiers dans votre répertoire web (ex: `/var/www/html/prestashop-csv`)
   - Assurez-vous que PHP est installé et configuré

3. **Créez le dossier exports** (optionnel, il sera créé automatiquement)
   ```bash
   mkdir exports
   chmod 755 exports
   ```

4. **Configurez PrestaShop**
   - Connectez-vous à votre back-office PrestaShop
   - Allez dans **Configuration avancée > Web Service**
   - **Activez** le service web
   - Cliquez sur **Ajouter une nouvelle clé**
   - Donnez un nom à la clé (ex: "CSV Manager")
   - Activez les permissions pour **products** :
     - ✅ GET (lecture)
     - ✅ PUT (modification)
   - Activez les permissions pour **combinations** (déclinaisons) :
     - ✅ GET (lecture)
     - ✅ PUT (modification)
   - Activez les permissions pour **stock_availables** (stocks) :
     - ✅ GET (lecture)
     - ✅ PUT (modification)
   - Activez les permissions pour **categories** :
     - ✅ GET (lecture)
   - Copiez la clé générée

## 📖 Utilisation

### 1. Configuration initiale

1. Ouvrez l'application dans votre navigateur
2. Saisissez l'URL de votre boutique PrestaShop (ex: `https://monsite.com`)
3. Saisissez votre clé API
4. Cliquez sur **Enregistrer la configuration**

La connexion sera testée automatiquement.

### 2. Exporter les produits

1. Sur la page d'accueil, cliquez sur **Exporter vers CSV**
2. **Sélectionnez une catégorie** dans la liste déroulante (ou "Tous les produits")
3. **Choisissez le type d'export** :
   - **Export standard** : Exporte uniquement les produits simples (ID, Nom, Référence, Prix, Quantité)
   - **Export avec déclinaisons** : Exporte tous les produits avec leurs déclinaisons et leurs stocks
4. Cliquez sur **Télécharger le fichier CSV**
5. Le fichier CSV sera téléchargé automatiquement

**Astuce** : Pour les boutiques avec beaucoup de produits, il est recommandé d'exporter par catégorie pour éviter les timeouts.

**Format du fichier exporté (standard) :**
```csv
ID;Nom;Référence;Prix;Quantité
1;Produit exemple;REF001;19,99;150
2;Autre produit;REF002;29,99;75
```

**Format du fichier exporté (avec déclinaisons) :**
```csv
ProductID;CombinationID;ProductName;CombinationName;Reference;Price;Quantité
1158;42;T-Shirt;Rouge - S;REF-1158-R-S;19,99;25
1158;43;T-Shirt;Rouge - M;REF-1158-R-M;21,99;18
1159;0;Produit simple;-;REF-1159;15,00;100
```

**Notes importantes** :
- Dans le format avec déclinaisons, `CombinationID = 0` indique un produit sans déclinaisons
- Les prix utilisent la virgule comme séparateur décimal (format français)
- Le fichier contient un BOM UTF-8 pour compatibilité avec Excel français

### 3. Importer et mettre à jour les prix et/ou stocks

1. Modifiez le fichier CSV exporté :
   - **Pour les prix** : Modifiez la colonne "Prix" ou "Price"
   - **Pour les stocks** : Modifiez la colonne "Quantité"
2. Sur la page d'accueil, cliquez sur **Importer CSV**
3. Sélectionnez votre fichier CSV modifié
4. **Choisissez les colonnes à mettre à jour** :
   - ✅ **Mettre à jour les prix** (coché par défaut)
   - ☐ **Mettre à jour les stocks** (décoché par défaut)
5. Cliquez sur **Importer et mettre à jour**
6. Suivez la progression en temps réel :
   - Barre de progression
   - Compteurs de produits traités, réussis et en erreur
   - Liste des erreurs en temps réel
7. Consultez le résumé final de l'import

**Fonctionnalités de l'import** :
- ✅ **Import sélectif** : Choisissez de mettre à jour uniquement les prix, uniquement les stocks, ou les deux
- ✅ **Traitement par lots** : 25 produits traités par lot pour éviter les timeouts
- ✅ **Progression en direct** : Suivez l'avancement avec une barre de progression et des statistiques en temps réel
- ✅ **Gestion des erreurs** : Les erreurs sont affichées immédiatement pendant le traitement
- ✅ **Détection automatique** : Le format du CSV (standard ou avec déclinaisons) est détecté automatiquement

**Note** : L'import continue même si certains produits génèrent des erreurs. Le résumé final affichera tous les détails.

## 📁 Structure du projet

```
prestadmin/
├── index.php              # Page principale avec configuration
├── export.php             # Page d'export CSV
├── import.php             # Page d'import CSV avec progression
├── import_batch.php       # Endpoint AJAX pour traitement par lots
├── PrestaShopAPI.php      # Classe API PrestaShop
├── style.css              # Feuille de style
├── exports/               # Dossier pour les fichiers CSV (créé auto)
├── .gitignore             # Fichiers exclus du dépôt Git
└── README.md              # Documentation
```

## 🔧 Structure de la classe PrestaShopAPI

### Méthodes principales

```php
// Constructeur
new PrestaShopAPI($shopUrl, $apiKey, $debug = false)

// Tester la connexion
$api->testConnection(): bool

// Vérifier les permissions de la clé API
$api->checkPermissions(): array

// Récupérer toutes les catégories
$api->getAllCategories(): array

// Récupérer tous les produits (avec filtre optionnel par catégorie)
$api->getAllProducts($categoryId = 0, $limit = 50): array

// Mettre à jour le prix d'un produit
$api->updateProductPrice($productId, $newPrice): bool

// Récupérer le stock d'un produit ou déclinaison
$api->getStock($productId, $combinationId = 0): int

// Mettre à jour le stock d'un produit ou déclinaison
$api->updateStock($productId, $quantity, $combinationId = 0): bool

// Récupérer les déclinaisons d'un produit
$api->getProductCombinations($productId): array

// Récupérer tous les produits avec leurs déclinaisons
$api->getAllProductsWithCombinations($categoryId = 0): array

// Mettre à jour le prix d'une déclinaison
$api->updateCombinationPrice($combinationId, $newPrice): bool

// Exporter vers CSV (produits simples, avec filtre optionnel par catégorie)
$api->exportToCSV($filename, $categoryId = 0): array

// Exporter vers CSV avec déclinaisons (avec filtre optionnel par catégorie)
$api->exportCombinationsToCSV($filename, $categoryId = 0): array

// Importer depuis CSV (détection automatique du format, mise à jour sélective)
$api->importFromCSV($filename, $updatePrice = true, $updateStock = false): array
```

### Exemple d'utilisation

```php
require_once 'PrestaShopAPI.php';

$api = new PrestaShopAPI('https://monsite.com', 'VOTRE_CLE_API');

// Récupérer toutes les catégories
$categories = $api->getAllCategories();

// Export standard de tous les produits
$result = $api->exportToCSV('produits.csv');
echo "Produits exportés : " . $result['count'];

// Export avec déclinaisons
$result = $api->exportCombinationsToCSV('produits_declinaisons.csv');
echo "Produits exportés : " . $result['count'];

// Export d'une catégorie spécifique avec déclinaisons
$result = $api->exportCombinationsToCSV('produits_cat5_declinaisons.csv', 5);
echo "Produits exportés : " . $result['count'];

// Import - mise à jour des prix uniquement
$results = $api->importFromCSV('produits_modifies.csv', true, false);
echo "Produits mis à jour : " . $results['success'];

// Import - mise à jour des stocks uniquement
$results = $api->importFromCSV('stocks.csv', false, true);
echo "Stocks mis à jour : " . $results['success'];

// Import - mise à jour des prix ET des stocks
$results = $api->importFromCSV('complet.csv', true, true);
echo "Produits mis à jour : " . $results['success'];

// Récupérer les déclinaisons d'un produit
$combinations = $api->getProductCombinations(1158);
foreach ($combinations as $combo) {
    echo "Déclinaison {$combo['id']}: {$combo['combination_name']} - {$combo['price']}€ - Stock: {$combo['quantity']}\n";
}

// Mettre à jour le prix et le stock d'un produit
$api->updateProductPrice(1158, 19.99);
$api->updateStock(1158, 150);

// Mettre à jour le prix et le stock d'une déclinaison
$api->updateCombinationPrice(42, 19.99);
$api->updateStock(1158, 25, 42); // productId, quantity, combinationId
```

## 📊 Format CSV

### Structure requise

- **Séparateur** : Point-virgule (`;`)
- **Encodage** : UTF-8
- **Première ligne** : En-têtes obligatoires
- **Détection automatique** : L'import reconnaît automatiquement le format

### Format standard (produits simples)

| Colonne    | Type   | Description                    | Modifiable à l'import |
|------------|--------|--------------------------------|----------------------|
| ID         | int    | Identifiant du produit         | ❌ Non               |
| Nom        | string | Nom du produit                 | ❌ Non               |
| Référence  | string | Référence du produit           | ❌ Non               |
| Prix       | float  | Prix HT du produit (virgule)   | ✅ Oui               |
| Quantité   | int    | Stock disponible               | ✅ Oui               |

**Exemple valide :**

```csv
ID;Nom;Référence;Prix;Quantité
1;T-Shirt Rouge;TSH-001;15,99;150
2;Pantalon Bleu;PAN-002;45,50;75
3;Chaussures Noires;CHU-003;89,99;0
```

### Format avec déclinaisons

| Colonne           | Type   | Description                            | Modifiable à l'import |
|-------------------|--------|----------------------------------------|----------------------|
| ProductID         | int    | Identifiant du produit                 | ❌ Non               |
| CombinationID     | int    | Identifiant de la déclinaison (0 si aucune) | ❌ Non               |
| ProductName       | string | Nom du produit                         | ❌ Non               |
| CombinationName   | string | Nom de la déclinaison (ex: "Rouge - S") | ❌ Non               |
| Reference         | string | Référence de la déclinaison            | ❌ Non               |
| Price             | float  | Prix HT de la déclinaison (virgule)    | ✅ Oui               |
| Quantité          | int    | Stock disponible pour cette déclinaison | ✅ Oui               |

**Exemple valide :**

```csv
ProductID;CombinationID;ProductName;CombinationName;Reference;Price;Quantité
1158;42;T-Shirt;Rouge - S;TSH-R-S;19,99;25
1158;43;T-Shirt;Rouge - M;TSH-R-M;21,99;18
1158;44;T-Shirt;Bleu - S;TSH-B-S;19,99;0
1159;0;Pantalon Simple;-;PAN-001;45,50;100
```

**Notes importantes :**
- `CombinationID = 0` indique un produit sans déclinaisons
- Chaque ligne représente une déclinaison unique
- Un produit avec 3 tailles et 2 couleurs générera 6 lignes (3 × 2)
- **Format français** : Virgule comme séparateur décimal pour les prix
- **Quantités** : Nombres entiers positifs (0 = rupture de stock)
- **BOM UTF-8** : Le fichier contient un BOM UTF-8 pour compatibilité Excel français

## ⚙️ Configuration

### Variables de session

L'application utilise les sessions PHP pour stocker :
- `shop_url` : URL de la boutique PrestaShop
- `api_key` : Clé API

### Sécurité

- Les identifiants sont stockés en session (pas de fichier)
- Connexion HTTPS recommandée
- Vérification SSL désactivée par défaut (modifiable dans `PrestaShopAPI.php`)

Pour activer la vérification SSL :
```php
// Dans PrestaShopAPI.php, méthode makeRequest()
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```

## 🐛 Résolution des problèmes

### Erreur de connexion à l'API

**Problème** : "Échec de la connexion"

**Solutions** :
- Vérifiez que l'URL de la boutique est correcte
- Vérifiez que la clé API est valide
- Assurez-vous que le Web Service est activé dans PrestaShop
- Vérifiez les permissions de la clé API (GET et PUT sur products)

### Erreur lors de l'import

**Problème** : "Format CSV invalide"

**Solutions** :
- Vérifiez que le séparateur est bien le point-virgule (`;`)
- Assurez-vous que la première ligne contient les en-têtes
- Vérifiez l'encodage du fichier (UTF-8)
- Utilisez le point (`.`) comme séparateur décimal pour les prix

### Extension cURL non trouvée

**Problème** : "Call to undefined function curl_init()"

**Solution** :
```bash
# Debian/Ubuntu
sudo apt-get install php-curl
sudo service apache2 restart

# CentOS/RHEL
sudo yum install php-curl
sudo service httpd restart
```

### Timeout lors de l'import

**Problème** : Le script s'arrête avant la fin (versions < 1.2)

**Solution** : À partir de la version 1.2, l'import utilise automatiquement un traitement par lots de 25 produits qui évite les timeouts. Si vous utilisez une version antérieure, augmentez le timeout PHP :
```php
// Au début de import.php
set_time_limit(300); // 5 minutes
ini_set('max_execution_time', 300);
```

**Note v1.2+** : Le traitement par lots avec AJAX permet de gérer des imports de milliers de produits sans risque de timeout.

## 🔐 Permissions PrestaShop

### Permissions minimales requises

Pour la clé API, activez uniquement :

| Ressource        | GET | POST | PUT | DELETE |
|------------------|-----|------|-----|--------|
| products         | ✅  | ❌   | ✅  | ❌     |
| combinations     | ✅  | ❌   | ✅  | ❌     |
| stock_availables | ✅  | ❌   | ✅  | ❌     |
| categories       | ✅  | ❌   | ❌  | ❌     |

### Comment créer la clé API

1. Back-office PrestaShop → **Configuration avancée** → **Web Service**
2. Activez le Web Service
3. Cliquez sur **Ajouter une nouvelle clé**
4. Remplissez :
   - **Nom de la clé** : CSV Manager
   - **Statut** : Activé
5. Dans **Permissions** :
   - Recherchez "products" → Cochez **GET** et **PUT**
   - Recherchez "combinations" → Cochez **GET** et **PUT**
   - Recherchez "stock_availables" → Cochez **GET** et **PUT**
   - Recherchez "categories" → Cochez **GET**
6. Cliquez sur **Enregistrer**
7. Copiez la clé générée

## 📝 Notes importantes

- **Prix HT** : Les prix dans PrestaShop sont stockés Hors Taxes
- **Format français** : Les prix utilisent la virgule comme séparateur décimal
- **Sauvegarde** : Faites toujours un export avant d'importer pour avoir une sauvegarde
- **Performance** : L'application utilise la pagination pour gérer efficacement les gros catalogues
- **Import par lots** : Les produits sont traités par lots de 25 pour éviter les timeouts
- **Progression** : L'import affiche une progression en temps réel avec compteurs et barre de progression
- **Pagination** : Les produits sont récupérés par lots de 50 pour optimiser les performances
- **Catégories** : Exportez par catégorie pour accélérer le traitement sur les grandes boutiques
- **Excel français** : Les fichiers CSV contiennent un BOM UTF-8 pour s'ouvrir correctement dans Excel

## 🆕 Fonctionnalités futures

- [x] Gestion des déclinaisons de produits ✅ **Implémenté v1.1**
- [x] Mise à jour des stocks ✅ **Implémenté v1.2**
- [x] Import par lots avec progression ✅ **Implémenté v1.2**
- [x] Import sélectif (prix et/ou stocks) ✅ **Implémenté v1.2**
- [ ] Import de nouveaux produits
- [ ] Historique des imports
- [ ] Validation avancée des données
- [ ] Export/Import des images produits
- [ ] Gestion des catégories en masse
- [ ] Gestion des prix spéciaux et promotions

## 📄 Licence

Ce projet est sous licence MIT. Vous êtes libre de l'utiliser, le modifier et le distribuer.

## 👨‍💻 Auteur

Développé pour PrestaShop 8.2

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer de nouvelles fonctionnalités
- Soumettre des pull requests

## 📞 Support

Pour toute question ou problème :
1. Consultez la section "Résolution des problèmes"
2. Vérifiez la documentation PrestaShop
3. Ouvrez une issue sur GitHub

---

**Version** : 1.2
**Date** : Décembre 2025
**Compatible** : PrestaShop 8.2

## 📝 Changelog

### Version 1.2 (Décembre 2025)
- ✨ **Gestion des stocks** : Ajout colonne "Quantité" dans les exports CSV
- ✨ **Import sélectif** : Checkboxes pour choisir de mettre à jour les prix et/ou les stocks
- ✨ **Import par lots** : Traitement par lots de 25 produits pour éviter les timeouts
- ✨ **Progression en temps réel** : Barre de progression, compteurs live (traités, réussis, erreurs)
- ✨ **Log d'erreurs dynamique** : Affichage des erreurs pendant l'import
- ✨ **BOM UTF-8** : Export compatible avec Excel français (accents corrects)
- ✨ **Format français** : Virgule comme séparateur décimal dans les CSV
- 🔧 **API stock_availables** : Méthodes `getStock()`, `updateStock()` et `getStockAvailableId()`
- 🔧 **Endpoint AJAX** : Nouveau fichier `import_batch.php` pour traitement asynchrone
- 🔧 **Interface améliorée** : Simplification de la page d'accueil
- 📖 Documentation complète sur la gestion des stocks

### Version 1.1 (Décembre 2024)
- ✨ Ajout de la gestion complète des déclinaisons de produits
- ✨ Export CSV avec déclinaisons (ProductID, CombinationID, etc.)
- ✨ Import CSV avec détection automatique du format
- ✨ Mise à jour individuelle du prix de chaque déclinaison
- 🔧 Amélioration de l'interface utilisateur avec choix du type d'export
- 📖 Documentation complète des nouveaux formats CSV

### Version 1.0 (Décembre 2024)
- 🎉 Version initiale
- ✨ Export CSV des produits simples
- ✨ Import CSV pour mise à jour des prix
- ✨ Filtre par catégorie
- ✨ Pagination optimisée pour les gros catalogues
