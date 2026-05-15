# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PrestaShop CSV Manager is a PHP web application for managing PrestaShop 8.2 products via CSV files. It handles export/import of products, combinations (product variants), prices, and stock through PrestaShop's REST API (XML-based).

**Key technologies:**
- PHP 7.4+ with cURL
- PrestaShop 8.2 REST API (XML format)
- AJAX-based batch processing (client-side)
- Session-based authentication
- No database (all data from PrestaShop API)

## Architecture

### Three-Layer Design

1. **API Layer** (`PrestaShopAPI.php`)
   - Single class handling all PrestaShop API interactions
   - Methods for products, combinations, categories, and stock_availables
   - Private `makeRequest()` method wraps cURL calls
   - Private `logOperation()` for operation tracking

2. **UI Layer** (HTML pages with embedded PHP)
   - `index.php`: Configuration and homepage
   - `export.php`: Export interface with real-time progress
   - `import.php`: Import interface with batch progress

3. **AJAX Endpoints** (batch processing)
   - `export_batch.php`: Processes export in chunks of 10 products
   - `import_batch.php`: Processes import in chunks of 10 products
   - `log_operation.php`: Records operations to operations.log

### Why Batch Processing?

PrestaShop API is slow (XML parsing, multiple requests for combinations). Large catalogs (1000+ products) would timeout. Solution: client-side JavaScript fetches data in small batches via AJAX, assembles results, generates CSV in browser.

**Export flow:**
```
export.php (UI) 
  → JS loops: fetch export_batch.php?offset=X&limit=10
  → Assembles all products client-side
  → Generates CSV with BOM UTF-8
  → Downloads via blob URL
  → Calls log_operation.php to record
```

**Import flow:**
```
import.php (UI)
  → JS parses CSV client-side
  → Splits into batches of 10
  → Sequential AJAX: POST import_batch.php with batch
  → Updates progress after each batch
  → Calls log_operation.php to record
```

## Critical Implementation Details

### Session Management
- Credentials stored in `$_SESSION['shop_url']` and `$_SESSION['api_key']`
- `$_SESSION['connection_validated']` guards all pages (redirects to index.php if false)
- Session invalidated when user modifies credentials on index.php
- `invalidate_session.php`: AJAX endpoint to clear session

### Version Management
- **Central configuration:** `version.php` defines constants:
  - `APP_VERSION` (current: 1.3)
  - `APP_NAME`
  - `APP_PRESTASHOP_VERSION`
- All UI files include `version.php` and use constants in footers
- **To update version:** only modify `version.php` and `README.md` changelog

### CSV Format Specifics
**Export:**
- **BOM UTF-8** required (Excel français compatibility)
- **French decimal format:** comma (`,`) not period (`.`)
- **Separator:** semicolon (`;`)
- Generated **client-side** in JavaScript for performance
- Two formats:
  - Standard: `ID;Nom;Référence;Prix;Quantité`
  - Combinations: `ProductID;CombinationID;ProductName;CombinationName;Reference;Price;Quantité`
  - `CombinationID=0` means simple product (no variants)

**Import:**
- Auto-detects format (checks for `ProductID` header vs `ID`)
- Decimal: accepts both `,` and `.` (normalized server-side)
- Updates only checked fields (price and/or stock)

### PrestaShop API Quirks

**Resources used:**
- `products`: product data (name, price, reference)
- `combinations`: product variants (size, color, etc.)
- `stock_availables`: stock quantities (separate resource!)
- `categories`: category list for filters

**Stock management is complex:**
- Stock is NOT in product XML
- Must query `stock_availables` with filters: `filter[id_product]=X&filter[id_product_attribute]=Y`
- Each combination has its own `stock_available` entry
- Simple products use `id_product_attribute=0`
- Update requires fetching `stock_available` ID first, then PUT

**Pagination:**
- API parameter: `limit=offset,count` (e.g., `limit=50,25` = skip 50, get 25)
- Use `display=[id]` for fast counting (returns only IDs, not full XML)
- Combinations query per product: slow! Optimize by batching

**Category filter:**
- Use `filter[id_category_default]=X` on products endpoint
- `categoryId=0` or omitted = all products

### Logging System

Operations logged to `operations.log` (excluded from git):
```
[YYYY-MM-DD HH:MM:SS] Shop: https://shop.url | IP: x.x.x.x | Action: EXPORT | Type: déclinaisons | Categorie: toutes | Produits: 1654
```

**When logs are written:**
- Export: after CSV generation, via `log_operation.php` AJAX call
- Import: after all batches complete, via `log_operation.php` AJAX call

**Note:** `PrestaShopAPI::exportToCSV()` and `importFromCSV()` have log calls but are **not used** by current batch system. Only `log_operation.php` logs operations now.

## File Roles

| File | Purpose |
|------|---------|
| `PrestaShopAPI.php` | Core API class (products, combinations, stock, categories) |
| `version.php` | Version configuration (APP_VERSION = 1.3) |
| `index.php` | Homepage: connection config, test, navigation |
| `export.php` | Export UI with batch progress (client-side assembly) |
| `export_batch.php` | AJAX: returns 10 products (or combinations) as JSON |
| `import.php` | Import UI with batch progress and selective update |
| `import_batch.php` | AJAX: processes 10 products from CSV, returns results |
| `log_operation.php` | AJAX: logs operation via reflection (calls private method) |
| `invalidate_session.php` | AJAX: clears session when user edits credentials |
| `debug_combinations.php` | Debug tool (not used in production) |
| `config.example.php` | Example config (not currently used) |
| `style.css` | Shared CSS for all pages |

## Development Workflow

### Testing Changes
1. **No automated tests exist** - manual testing required
2. Test environment: need PrestaShop 8.2 instance with API enabled
3. Required API permissions (in PrestaShop admin):
   - `products`: GET, PUT
   - `combinations`: GET, PUT
   - `stock_availables`: GET, PUT
   - `categories`: GET

### Making Changes

**For version bump:**
1. Update `version.php` (change `APP_VERSION`)
2. Add entry to `README.md` changelog section
3. Commit: `git commit -m "chore: Bump version to X.Y"`

**For UI changes:**
- All pages use same CSS (`style.css`)
- Progress bars: expect `#progress-section`, `#progress-bar`, `#progress-text` IDs
- Batch size: 10 products/items (configurable in JS, search `batchSize = 10`)

**For API changes:**
- Modify `PrestaShopAPI.php` methods
- Update both direct methods (e.g., `exportToCSV`) AND batch endpoints
- Test with large catalogs (1000+ products) for timeout issues

**For CSV format changes:**
- Export: modify JS `generateCSV()` function in `export.php`
- Import: update header detection and parsing in `import.php` JS + `import_batch.php` PHP

### Common Issues

**Export shows wrong totals / stops early:**
- Check `hasMore` logic in `export_batch.php` (line ~99)
- Must count unique **products**, not **lines** (one product = multiple combinations)
- Use `$uniqueProducts` array to track

**Import fails silently:**
- Check `import_batch.php` error responses (line ~138-144)
- Errors returned as JSON: `{"success": N, "errors": ["product: error"]}`
- Import continues even with errors (partial success)

**Timeout issues:**
- Increase batch size cautiously (10 is safe, 50+ may timeout)
- Check PHP timeouts: `set_time_limit(600)` in export.php
- API timeout: `CURLOPT_TIMEOUT` in PrestaShopAPI.php (currently 300s)

**Session lost:**
- Check if `session_start()` called at top of file
- Ensure `connection_validated` check present
- Debug: check `invalidate_session.php` not called unintentionally

## PrestaShop API Reference

**Base URL:** `{shop_url}/api/{resource}?key={api_key}`  
**Auth:** HTTP Basic (username = api_key, no password)

**Key methods in PrestaShopAPI:**
- `getAllProducts($categoryId, $limit, $offset)`: paginated products
- `getAllProductsWithCombinations($categoryId, $limit, $offset)`: products + combinations
- `getProductCombinations($productId)`: all variants of a product
- `getStock($productId, $combinationId)`: quantity for product/combination
- `updateStock($productId, $quantity, $combinationId)`: update quantity
- `updateProductPrice($productId, $newPrice)`: simple product price
- `updateCombinationPrice($combinationId, $newPrice)`: variant price
- `getAllCategories()`: category list for dropdown
- `countProductLines($categoryId, $exportType)`: fast count (uses `display=[id]`)

**Performance tip:** Use `display=[id,name,price]` instead of full XML when possible.

## Git Workflow

**Current branch strategy:**
- Development branch: `claude/prestashop-csv-product-sync-{SESSION_ID}`
- Push after each significant change
- Do NOT push to main/master directly

**Excluded from git** (see .gitignore):
- `*.csv` files
- `*.log` files (operations.log)
- `exports/` directory contents
- `sessions/` directory
- `webhook.php` and `webhook.log`
- IDE configs (.vscode, .idea)

**Session continuity files:**
- This `CLAUDE.md` file
- `README.md` (user documentation + changelog)
- `version.php` (version tracking)
