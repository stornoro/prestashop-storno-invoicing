# Storno Invoicing — PrestaShop Module

Automatic invoice generation for PrestaShop 1.7+ / 8.x / 9.x via the Storno API, designed for self-hosted instances. Your data stays on your own infrastructure.

## Requirements

- PrestaShop 1.7.0+ (compatible with 8.x and 9.x)
- PHP 7.4+ with cURL extension enabled
- Self-hosted Storno instance (Docker) on the same server or local network
- Storno **Professional** license (39 RON/month) — required for webhooks

## Installation

### 1. Deploy Storno (Self-Hosted)

```bash
mkdir /opt/storno && cd /opt/storno
curl -O https://raw.githubusercontent.com/stornoro/storno/main/deploy/docker-compose.yml
curl -O https://raw.githubusercontent.com/stornoro/storno/main/deploy/.env.example
cp .env.example .env
```

Edit `.env`:
```bash
APP_SECRET=$(openssl rand -hex 32)
JWT_PASSPHRASE=$(openssl rand -hex 32)
MYSQL_ROOT_PASSWORD=strong-password
MYSQL_PASSWORD=strong-password
LICENSE_KEY=your-license-key     # from app.storno.ro → Settings → Licensing
FRONTEND_URL=https://invoices.yourdomain.com
PUBLIC_API_BASE=https://invoices.yourdomain.com/api
```

```bash
docker compose --profile local-db up -d
docker compose exec backend php bin/console doctrine:schema:create
docker compose exec backend php bin/console doctrine:migrations:sync-metadata-storage
docker compose exec backend php bin/console doctrine:migrations:version --add --all --no-interaction
docker compose exec backend php bin/console app:user:create \
  --email=admin@yourdomain.com --password=your-password --admin
```

> Storno runs on port 8901 (frontend) and 8900 (API). Configure a reverse proxy (nginx/caddy) for HTTPS.

See the [self-hosting guide](https://docs.storno.ro/getting-started/self-hosting) for full details.

### 2. Create an API Key

1. Open your Storno instance and log in
2. Go to **Settings → API Keys** ([docs](https://docs.storno.ro/api-reference/api-keys/create))
3. Create a new key with these [scopes](https://docs.storno.ro/api-reference/api-keys/scopes):
   - `client.view`, `client.create`
   - `invoice.create`, `invoice.issue`, `invoice.send`, `invoice.view`
4. Copy the token (shown only once!)
5. Note the Company UUID from **Settings → Company**

### 3. Install the PrestaShop Module

```bash
# Copy the module into PrestaShop
cp -r stornoinvoicing/ /var/www/prestashop/modules/

# Set permissions
chown -R www-data:www-data /var/www/prestashop/modules/stornoinvoicing/
```

Then in **Back Office → Module Manager**:
1. Search for "Storno Invoicing"
2. Click **Install**
3. Click **Configure**

### 4. Configuration

The module configuration is split into 4 sections, each linking to the relevant [docs.storno.ro](https://docs.storno.ro) page:

#### API Connection

| Setting | Example | Docs |
|---------|---------|------|
| **API URL** | `https://invoices.yourdomain.com` | [Self-hosting](https://docs.storno.ro/getting-started/self-hosting) |
| **API Key** | `af_...` | [Create API key](https://docs.storno.ro/api-reference/api-keys/create) |
| **Company UUID** | `550e8400-...` | [Companies](https://docs.storno.ro/api-reference/companies/list) |

#### Invoice Settings

| Setting | Default | Description | Docs |
|---------|---------|-------------|------|
| **Trigger on Order Status** | Payment accepted | Invoice is created when the order reaches this status (e.g. Shipped) | — |
| **Auto-issue invoices** | Yes | Assigns number, generates PDF + XML immediately after creation | [Invoice lifecycle](https://docs.storno.ro/concepts/document-lifecycle) |
| **Auto-apply VAT rules** | Yes | Reverse charge (0% for EU VIES clients), OSS rates, non-EU export exemption | [e-Factura integration](https://docs.storno.ro/concepts/einvoice-integration) |
| **Document Series** | Default (auto) | Invoice numbering series from Storno | [Series numbering](https://docs.storno.ro/concepts/series-numbering) |
| **Invoice Language** | From Storno | PDF language (ro/en/de/fr) | [Create invoice](https://docs.storno.ro/api-reference/invoices/create) |
| **Payment Term** | 30 days | Days from issue date to due date | — |
| **Default VAT Rate** | 21% | Fallback when PrestaShop product has no tax | [VAT rates](https://docs.storno.ro/api-reference/vat-rates/list) |
| **Shipping VAT Rate** | 21% | VAT on the shipping cost line | — |
| **Unit of Measure** | buc | Unit shown on invoice lines | — |
| **Invoice Notes** | (empty) | Public notes on every invoice | — |
| **Internal Note Format** | `PrestaShop #{reference}` | Internal-only note with `{reference}` and `{id}` placeholders | — |

> **e-Factura:** ANAF submission is handled automatically by Storno based on the company's `efacturaDelayHours` setting (Settings → e-Factura). The PrestaShop module does not need to configure anything for this.

#### Invoice Line Labels

| Setting | Default | Description |
|---------|---------|-------------|
| **Shipping Line** | Transport | Description for the shipping cost line |
| **Discount Line** | Discount | Description for discount lines (negative amount) |
| **Gift Wrapping Line** | Ambalare cadou | Description for gift wrapping costs |

#### Payment Method Mapping

Each PrestaShop payment module is mapped to a Storno payment method shown on the invoice.

| PrestaShop Module | Default | Options |
|-------------------|---------|---------|
| ps_wirepayment | Bank Transfer | Bank Transfer, Cash, Card, Cheque, Other |
| ps_checkpayment | Cheque | " |
| ps_cashondelivery | Cash | " |
| stripe | Card | " |
| paypal | Card | " |
| Default (other) | Other | " |

### 5. Register Webhook (optional)

Click **Register Webhook** from the configuration page. This lets Storno notify PrestaShop when:
- An invoice is validated by ANAF
- An invoice is rejected by ANAF
- A payment is recorded

See [webhooks documentation](https://docs.storno.ro/concepts/webhooks-events) for details.

## How It Works

```
1. Customer places an order in PrestaShop
2. Operator marks the order as "Shipped" (or configured status)
3. The module activates automatically:
   a. Creates the client in Storno (or finds existing by CUI)
   b. Creates the invoice with all order products
   c. Issues the invoice (assigns number, generates PDF, XML)
   d. Storno submits to e-Factura ANAF automatically (per efacturaDelayHours)
4. On the order page, a "Storno Invoice" panel shows:
   - Invoice number (links to Storno)
   - Status (draft → issued → validated)
   - Any errors
5. Webhook updates the status when ANAF validates/rejects the invoice
```

## VAT Handling

When **Auto-apply VAT rules** is enabled, Storno determines the correct VAT automatically:

| Scenario | Client | VAT | Category |
|----------|--------|-----|----------|
| Domestic sale | Romanian company | 21% | S (Standard) |
| Reverse charge | EU with valid VIES | **0%** | **AE** (Reverse charge) |
| OSS | EU without VIES, company has OSS | Destination country rate | S |
| Non-EU export | Client in US/UK etc. | 0% | Z (Zero-rated) |

## Data Security

- All data stays on your server (self-hosted Storno)
- PrestaShop ↔ Storno communication is local (or via internal network)
- The only external communication is with ANAF (legally required for e-Factura)
- API Key is stored securely in PrestaShop configuration
- Webhooks are protected with HMAC-SHA256 signatures

## Data Mapping

| PrestaShop | Storno |
|------------|--------|
| Order reference | `orderNumber` + `internalNote` |
| Client (company) | `client.type = company` |
| Client (individual) | `client.type = individual` |
| CUI / DNI | `client.cui` |
| VAT code | `client.vatCode` |
| Products | `invoice.lines[]` |
| Shipping | Separate line (configurable label) |
| Discounts | Negative line (configurable label) |
| Gift wrapping | Separate line (configurable label) |
| Payment method | Configurable mapping per module |

## Translations

The module uses **English as the base language**. Romanian translations are included in `translations/ro.php`.

To add more languages, create `translations/{iso}.php` following the same format. PrestaShop will also let you translate strings via **Back Office → International → Translations → Installed module translations**.

## Troubleshooting

**Test connection:** Click "Test Connection" from the configuration page.

**Logs:** The module logs all actions to **Back Office → Advanced Parameters → Logs** (search for "Storno").

**Webhook log:** The `ps_storno_webhook_log` table contains all received events.

**Common errors:**
- `HTTP 401` — Invalid or expired API Key
- `HTTP 403` — Insufficient API Key scopes
- `HTTP 422` — Invalid data (check invoice lines)
- `cURL error` — Storno is not reachable (check URL and network)

## PrestaShop 9 Compatibility

The module uses standard hooks (`actionValidateOrder`, `actionOrderStatusPostUpdate`, `displayAdminOrder`) which are supported in PrestaShop 9. No changes are needed during migration.

## File Structure

```
stornoinvoicing/
├── stornoinvoicing.php          # Main module class
├── classes/
│   └── StornoApi.php            # Storno API client
├── controllers/
│   └── front/
│       └── webhook.php          # Webhook endpoint
├── sql/
│   ├── install.php              # Table creation
│   └── uninstall.php            # Table removal
├── translations/
│   └── ro.php                   # Romanian translations
├── views/
│   ├── css/
│   │   └── admin.css            # Admin styles
│   └── templates/
│       └── hook/
│           └── admin_order.tpl  # Order page panel
└── README.md
```
