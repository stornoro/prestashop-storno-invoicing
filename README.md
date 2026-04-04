# Storno Invoicing — Modul PrestaShop

Modul de facturare automată pentru PrestaShop 1.7+ / 8.x / 9.x care creează și emite facturi prin API-ul Storno, găzduit pe serverul propriu (self-hosted). Datele nu părăsesc infrastructura dvs.

## Cerințe

- PrestaShop 1.7.0+ (compatibil cu 8.x și 9.x)
- PHP 7.4+ cu extensia cURL activată
- Instanță Storno self-hosted (Docker) pe același server sau în rețeaua locală
- Licență Storno **Professional** (39 RON/lună) — necesară pentru webhooks

## Instalare

### 1. Instalare Storno Self-Hosted

```bash
mkdir /opt/storno && cd /opt/storno
curl -O https://raw.githubusercontent.com/stornoro/storno/main/deploy/docker-compose.yml
curl -O https://raw.githubusercontent.com/stornoro/storno/main/deploy/.env.example
cp .env.example .env
```

Editează `.env`:
```bash
APP_SECRET=$(openssl rand -hex 32)
JWT_PASSPHRASE=$(openssl rand -hex 32)
MYSQL_ROOT_PASSWORD=parola-bd
MYSQL_PASSWORD=parola-bd
LICENSE_KEY=cheia-de-licenta     # din app.storno.ro → Settings → Licensing
FRONTEND_URL=https://facturi.domeniu.ro
PUBLIC_API_BASE=https://facturi.domeniu.ro/api
```

```bash
docker compose --profile local-db up -d
docker compose exec backend php bin/console doctrine:schema:create
docker compose exec backend php bin/console doctrine:migrations:sync-metadata-storage
docker compose exec backend php bin/console doctrine:migrations:version --add --all --no-interaction
docker compose exec backend php bin/console app:user:create \
  --email=admin@domeniu.ro --password=parola --admin
```

> Storno va rula pe portul 8901 (frontend) și 8900 (API). Configurați un reverse proxy (nginx/caddy) pentru HTTPS.

### 2. Crearea API Key

1. Deschide `https://facturi.domeniu.ro` și autentifică-te
2. Du-te la **Setări → Chei API**
3. Creează o cheie nouă cu scopurile:
   - `client.view`, `client.create`
   - `invoice.create`, `invoice.issue`, `invoice.send`, `invoice.view`
4. Copiază token-ul (se afișează o singură dată!)
5. Notează UUID-ul companiei din **Setări → Companie**

### 3. Instalare modul PrestaShop

```bash
# Copiază modulul în PrestaShop
cp -r stornoinvoicing/ /var/www/prestashop/modules/

# Setează permisiuni
chown -R www-data:www-data /var/www/prestashop/modules/stornoinvoicing/
```

Apoi în **Back Office → Module Manager**:
1. Caută "Storno Invoicing"
2. Click **Install**
3. Click **Configure**

### 4. Configurare

| Setare | Valoare | Notă |
|--------|---------|------|
| **API URL** | `https://facturi.domeniu.ro` | URL-ul instanței Storno |
| **API Key** | `af_...` | Token-ul creat la pasul 2 |
| **Company UUID** | `550e8400-...` | UUID-ul companiei |
| **Trigger on Order Status** | `Shipped` / `Expediată` | Factura se creează la expediere |
| **Auto-issue invoices** | Da | Generează automat număr + PDF + XML |
| **Auto-submit to e-Factura** | Da | Trimite automat la ANAF |
| **Default VAT Rate** | 19 | Rata TVA implicită |
| **Shipping VAT Rate** | 19 | TVA pe transport |

### 5. Înregistrare Webhook (opțional)

Click **Register Webhook** din pagina de configurare. Acesta permite Storno să notifice PrestaShop când:
- Factura este validată de ANAF
- Factura este respinsă de ANAF
- Se înregistrează o plată

## Flux de funcționare

```
1. Client plasează comandă în PrestaShop
2. Operator marchează comanda ca "Expediată"
3. Modulul se activează automat:
   a. Creează clientul în Storno (sau îl găsește dacă există)
   b. Creează factura cu toate produsele din comandă
   c. Emite factura (generează număr, PDF, XML)
   d. Trimite la e-Factura ANAF (dacă auto-submit este activ)
4. Pe pagina comenzii apare panoul "Storno Invoice" cu:
   - Numărul facturii (link spre Storno)
   - Statusul (draft → issued → validated)
   - Eventuale erori
5. Webhook-ul actualizează statusul când ANAF validează/respinge factura
```

## Securitatea datelor

- Toate datele rămân pe serverul dvs. (Storno self-hosted)
- Comunicarea PrestaShop ↔ Storno se face local (sau prin rețeaua internă)
- Singura comunicare externă este cu ANAF (obligatorie legal pentru e-Factura)
- API Key-ul este stocat securizat în configurația PrestaShop
- Webhook-urile sunt protejate cu semnătură HMAC-SHA256

## Mapare date

| PrestaShop | Storno |
|------------|--------|
| Referința comenzii | `orderNumber` + `internalNote` |
| Client (companie) | `client.type = company` |
| Client (persoană) | `client.type = individual` |
| CUI / DNI | `client.cui` |
| Cod TVA | `client.vatCode` |
| Produse | `invoice.lines[]` |
| Transport | Linie separată "Transport" |
| Discounturi | Linie negativă "Discount" |
| Ambalare cadou | Linie separată "Ambalare cadou" |
| Metodă plată | Mapare automată (transfer/card/cash) |

## Depanare

**Verificare conexiune:** Click "Test Connection" din pagina de configurare.

**Loguri:** Modulul logează toate acțiunile în **Back Office → Advanced Parameters → Logs** (caută "Storno").

**Webhook log:** Tabelul `ps_storno_webhook_log` conține toate evenimentele primite.

**Erori comune:**
- `HTTP 401` — API Key invalid sau expirat
- `HTTP 403` — Scopuri insuficiente pe API Key
- `HTTP 422` — Date invalide (verifică liniile facturii)
- `cURL error` — Storno nu este accesibil (verifică URL-ul și rețeaua)

## Compatibilitate PrestaShop 9

Modulul folosește hook-uri standard (`actionValidateOrder`, `actionOrderStatusPostUpdate`, `displayAdminOrder`) care sunt suportate în PrestaShop 9. La migrare nu sunt necesare modificări.

## Structura fișierelor

```
stornoinvoicing/
├── stornoinvoicing.php          # Clasa principală
├── classes/
│   └── StornoApi.php            # Client API Storno
├── controllers/
│   └── front/
│       └── webhook.php          # Endpoint webhook
├── sql/
│   ├── install.php              # Creare tabele
│   └── uninstall.php            # Ștergere tabele
├── views/
│   ├── css/
│   │   └── admin.css            # Stiluri admin
│   └── templates/
│       └── hook/
│           └── admin_order.tpl  # Panou pe pagina comenzii
└── README.md
```
