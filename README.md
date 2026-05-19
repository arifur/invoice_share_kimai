# Invoice Share Bundle

A Kimai 2 plugin that automatically generates unique, shareable public links for invoices. Anyone with the link can view or download the invoice without needing a Kimai account.

## Features

- **Automatic Link Generation** — A unique UUID-based public link is automatically generated for every new invoice created in Kimai.
- **Public Access** — No login is required to view the invoice via the shared link. The route is configured with `PUBLIC_ACCESS` so anyone with the URL can access it.
- **PDF Support** — If a PDF invoice file exists, it is served directly in the browser. Otherwise, a clean HTML invoice view is rendered.
- **Expiry Dates** — Each link has an expiration date (default: 6 months from creation). Expired links automatically return a 404.
- **Public Toggle** — Links can be toggled between public and private. Private links are inaccessible.
- **Invoice List Integration** — Adds a "URL" column to the Kimai invoice listing page showing the public link for each invoice.

## Requirements

- Kimai 2 version **2.25.0** or higher

## Installation

### Step 1: Copy the plugin files

Copy the `InvoiceShareBundle` directory into your Kimai installation's plugin directory:

```bash
cp -r InvoiceShareBundle /path/to/kimai/var/plugins/
```

### Step 2: Run database migrations

Create the required database table by running the plugin's migration:

```bash
bin/console doctrine:migrations:migrate --em=default --configuration=var/plugins/InvoiceShareBundle/Migrations/doctrine_migrations.yaml
```

### Step 3: Clear the cache

```bash
bin/console cache:clear
```

### Step 4: Verify installation

Log into Kimai and navigate to **System** → **Plugins**. You should see **Invoice Share** listed as an active plugin.

## Usage

1. **Create an invoice** in Kimai as you normally would.
2. The plugin automatically generates a unique public link (UUID-based) for the invoice.
3. Go to the **Invoices** listing page — a new **URL** column will display the public link for each invoice.
4. Click the link or copy it and share it with your customer.
5. When someone visits the link:
   - If a PDF file exists for the invoice, it is displayed inline in the browser.
   - If no PDF exists, a styled HTML page with the invoice details is rendered.
   - If the link has expired or is marked as non-public, a 404 page is shown.

## Production Deployment

When deploying this plugin to a production server, follow these guidelines:

### 1. Deploy the Plugin Files

If you use Git for deployment, add the plugin as a submodule or copy it manually:

```bash
# Option A: Copy the plugin
scp -r InvoiceShareBundle user@production-server:/path/to/kimai/var/plugins/

# Option B: Via Git submodule
cd /path/to/kimai
git submodule add https://github.com/your-org/InvoiceShareBundle var/plugins/InvoiceShareBundle
```

### 2. Run Database Migrations

On the production server, run the migration to create the `kimai2_invoice_share` table:

```bash
cd /path/to/kimai
php bin/console doctrine:migrations:migrate --em=default --configuration=var/plugins/InvoiceShareBundle/Migrations/doctrine_migrations.yaml
```

### 3. Clear and Warm Up the Cache

```bash
cd /path/to/kimai
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### 4. Set Proper File Permissions

Ensure the web server user (e.g., `www-data`, `nginx`, `apache`) has read access to the plugin files:

```bash
cd /path/to/kimai
chown -R www-data:www-data var/plugins/InvoiceShareBundle/
chmod -R 755 var/plugins/InvoiceShareBundle/
```

### 5. Verify the Plugin is Active

- Log into the Kimai admin panel.
- Navigate to **System** → **Plugins**.
- Confirm **Invoice Share** is listed as active.

### 6. Test the Public Route

After deployment, verify the public invoice route is accessible:

```bash
# Replace INVOICE_UUID with an actual UUID from your kimai2_invoice_share table
curl -I https://your-kimai-domain.com/invoice/INVOICE_UUID
```

If the route is inaccessible, ensure your web server (Apache/Nginx) is configured to allow Symfony routing (no rewrite rules blocking the `/invoice/` path).

### 7. Security Checklist for Production

- **HTTPS** — Ensure your Kimai instance is served over HTTPS so shared invoice URLs are encrypted in transit.
- **Firewall** — The `/invoice/{uuid}` route is public by design (no authentication required). If you need to restrict access further, consider IP whitelisting at the web server level.
- **UUID Strength** — The plugin uses `md5(uniqid())` to generate UUIDs. For higher security, modify the UUID generation to use `bin2hex(random_bytes(16))` instead.
- **Expiry Policy** — Review the default 6-month expiry period in `InvoiceShareSubscriber` and adjust to match your organization's data retention policy.

### 8. Updating the Plugin on Production

When updating the plugin to a new version:

```bash
cd /path/to/kimai

# If copied manually, re-copy the updated files
# If using Git submodule
git submodule update --remote var/plugins/InvoiceShareBundle

# Run any new migrations
php bin/console doctrine:migrations:migrate --em=default --configuration=var/plugins/InvoiceShareBundle/Migrations/doctrine_migrations.yaml

# Clear cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

## How It Works

| Component | Description |
|---|---|
| `InvoiceShareSubscriber` | Listens for the `InvoiceCreatedEvent` and automatically creates a new `InvoiceShare` entity with a random UUID, 6-month expiry, and public visibility. |
| `PublicInvoiceController` | Handles the public route `/invoice/{uuid}` — looks up the UUID, checks expiry and visibility, then serves the PDF or HTML view. |
| `InvoiceShareExtension` | Provides Twig functions (`invoice_share_url`, `invoice_share_add_column`) used in templates to display the public URL and add the URL column to the invoice data table. |
| `InvoiceShareRepository` | Manages database queries for looking up invoice links by UUID or by Invoice entity. |

## Configuration

The plugin works out of the box with sensible defaults:

| Setting | Default | Description |
|---|---|---|
| Link expiry | 6 months | Duration after which the public link expires. Modify `InvoiceShareSubscriber` to change. |
| Public by default | `true` | New links are publicly accessible by default. |

## Uninstallation

1. Remove the plugin directory:
   ```bash
   rm -rf /path/to/kimai/var/plugins/InvoiceShareBundle
   ```

2. Drop the database table (if desired):
   ```sql
   DROP TABLE kimai2_invoice_share;
   ```

3. Clear the cache:
   ```bash
   bin/console cache:clear
   ```

## License

This plugin is licensed under the **MIT License**.