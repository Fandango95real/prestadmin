# PrestaShop CSV Manager

Application web PHP pour gérer vos produits PrestaShop via des fichiers CSV. Compatible avec PrestaShop 8.2.

## 🎯 Fonctionnalités

- **Export CSV** : Téléchargez tous vos produits (ID, nom, référence, prix) dans un fichier CSV
- **Filtre par catégorie** : Exportez tous les produits ou seulement ceux d'une catégorie spécifique
- **Import CSV** : Mettez à jour les prix de vos produits à partir d'un fichier CSV
- **Pagination optimisée** : Gestion efficace des boutiques avec des milliers de produits
- **Interface intuitive** : Interface web simple et moderne
- **API PrestaShop** : Utilise l'API REST native de PrestaShop
- **Timeouts augmentés** : Évite les erreurs 524 sur les gros catalogues

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
3. Cliquez sur **Télécharger le fichier CSV**
4. Le fichier CSV sera téléchargé automatiquement

**Astuce** : Pour les boutiques avec beaucoup de produits, il est recommandé d'exporter par catégorie pour éviter les timeouts.

**Format du fichier exporté :**
```csv
ID;Nom;Référence;Prix
1;Produit exemple;REF001;19.99
2;Autre produit;REF002;29.99
```

### 3. Importer et mettre à jour les prix

1. Modifiez le fichier CSV exporté (changez les prix dans la colonne "Prix")
2. Sur la page d'accueil, cliquez sur **Importer CSV**
3. Sélectionnez votre fichier CSV modifié
4. Cliquez sur **Importer et mettre à jour**
5. Consultez les résultats de l'import

## 📁 Structure du projet

```
prestadmin/
├── index.php              # Page principale avec configuration
├── export.php             # Page d'export CSV
├── import.php             # Page d'import CSV
├── PrestaShopAPI.php      # Classe API PrestaShop
├── style.css              # Feuille de style
├── exports/               # Dossier pour les fichiers CSV (créé auto)
└── README.md              # Documentation
```

## 🔧 Structure de la classe PrestaShopAPI

### Méthodes principales

```php
// Constructeur
new PrestaShopAPI($shopUrl, $apiKey, $debug = false)

// Tester la connexion
$api->testConnection(): bool

// Récupérer toutes les catégories
$api->getAllCategories(): array

// Récupérer tous les produits (avec filtre optionnel par catégorie)
$api->getAllProducts($categoryId = 0, $limit = 50): array

// Mettre à jour le prix d'un produit
$api->updateProductPrice($productId, $newPrice): bool

// Exporter vers CSV (avec filtre optionnel par catégorie)
$api->exportToCSV($filename, $categoryId = 0): array

// Importer depuis CSV
$api->importFromCSV($filename): array
```

### Exemple d'utilisation

```php
require_once 'PrestaShopAPI.php';

$api = new PrestaShopAPI('https://monsite.com', 'VOTRE_CLE_API');

// Récupérer toutes les catégories
$categories = $api->getAllCategories();

// Export de tous les produits
$result = $api->exportToCSV('produits.csv');
echo "Produits exportés : " . $result['count'];

// Export d'une catégorie spécifique
$result = $api->exportToCSV('produits_categorie_5.csv', 5);
echo "Produits exportés : " . $result['count'];

// Import
$results = $api->importFromCSV('produits_modifies.csv');
echo "Produits mis à jour : " . $results['success'];
```

## 📊 Format CSV

### Structure requise

- **Séparateur** : Point-virgule (`;`)
- **Encodage** : UTF-8
- **Première ligne** : En-têtes obligatoires

### Colonnes

| Colonne    | Type   | Description                    | Modifiable à l'import |
|------------|--------|--------------------------------|----------------------|
| ID         | int    | Identifiant du produit         | ❌ Non               |
| Nom        | string | Nom du produit                 | ❌ Non               |
| Référence  | string | Référence du produit           | ❌ Non               |
| Prix       | float  | Prix HT du produit             | ✅ Oui               |

### Exemple valide

```csv
ID;Nom;Référence;Prix
1;T-Shirt Rouge;TSH-001;15.99
2;Pantalon Bleu;PAN-002;45.50
3;Chaussures Noires;CHU-003;89.99
```

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

**Problème** : Le script s'arrête avant la fin

**Solution** : Augmentez le timeout PHP
```php
// Au début de import.php
set_time_limit(300); // 5 minutes
ini_set('max_execution_time', 300);
```

## 🔐 Permissions PrestaShop

### Permissions minimales requises

Pour la clé API, activez uniquement :

| Ressource  | GET | POST | PUT | DELETE |
|------------|-----|------|-----|--------|
| products   | ✅  | ❌   | ✅  | ❌     |
| categories | ✅  | ❌   | ❌  | ❌     |

### Comment créer la clé API

1. Back-office PrestaShop → **Configuration avancée** → **Web Service**
2. Activez le Web Service
3. Cliquez sur **Ajouter une nouvelle clé**
4. Remplissez :
   - **Nom de la clé** : CSV Manager
   - **Statut** : Activé
5. Dans **Permissions** :
   - Recherchez "products" → Cochez **GET** et **PUT**
   - Recherchez "categories" → Cochez **GET**
6. Cliquez sur **Enregistrer**
7. Copiez la clé générée

## 📝 Notes importantes

- **Prix HT** : Les prix dans PrestaShop sont stockés Hors Taxes
- **Sauvegarde** : Faites toujours un export avant d'importer pour avoir une sauvegarde
- **Performance** : L'application utilise la pagination pour gérer efficacement les gros catalogues
- **Timeouts** : Augmentés à 10 minutes pour éviter les erreurs 524 sur les gros catalogues
- **Pagination** : Les produits sont récupérés par lots de 50 pour optimiser les performances
- **Catégories** : Exportez par catégorie pour accélérer le traitement sur les grandes boutiques

## 🆕 Fonctionnalités futures

- [ ] Gestion des déclinaisons de produits
- [ ] Mise à jour de plusieurs champs (stock, description, etc.)
- [ ] Import de nouveaux produits
- [ ] Historique des imports
- [ ] Validation avancée des données
- [ ] Mode batch pour les grosses boutiques

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

**Version** : 1.0
**Date** : Décembre 2024
**Compatible** : PrestaShop 8.2
