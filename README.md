# Lumora Logistics

Lumora Logistics is a Laravel-based fulfillment console designed as the operational companion to the Lumora Beauty storefront. The first release includes a branded operations dashboard, order and shipment domain models, versioned JSON API endpoints, signed webhook ingestion, idempotent event storage, queued order synchronization, and a database-backed demo workspace.

## Local setup

The project uses PHP, Composer, a MySQL-compatible database server, Node.js, and Vite. Create a database and configure the DB_* values in .env before running migrations. After cloning the project, install dependencies and prepare the database:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

The dashboard is available at `http://127.0.0.1:8000`. The demo seed creates the Lumora Beauty workspace, Brooklyn fulfillment location, sample orders, shipments, tracking events, and Amelia Morgan's operations user.

For local queue processing, run a worker in a second terminal:

```bash
php artisan queue:work
```

The queue connection is configured as `database` by default. In production, keep a supervised queue worker and scheduler running so webhook processing and any future reconciliation jobs remain available.

## Configuration

Add these values to `.env` and replace the demo values before production use:

```dotenv
LOGISTICS_API_KEY=replace-with-a-long-random-integration-key
LOGISTICS_WEBHOOK_SECRET=replace-with-a-long-random-webhook-secret
QUEUE_CONNECTION=database
```

The integration API expects the key in an `X-Integration-Key` header. Webhooks use `X-Event-Id`, `X-Event-Type`, and an optional `X-Webhook-Signature` header containing an HMAC-SHA256 digest of the raw request body using `LOGISTICS_WEBHOOK_SECRET`.

## API surface

All integration endpoints are versioned under `/api/v1`.

| Method | Endpoint | Authentication | Description |
|---|---|---|---|
| `GET` | `/api/v1/health` | None | Service availability check |
| `GET` | `/api/v1/orders` | `X-Integration-Key` | Paginated order list with optional `status` and `search` filters |
| `POST` | `/api/v1/orders` | `X-Integration-Key` | Create or update an order using `external_id` |
| `GET` | `/api/v1/orders/{id}` | `X-Integration-Key` | Read an order with items and shipments |
| `GET` | `/api/v1/shipments` | `X-Integration-Key` | Paginated shipment list with optional status and carrier filters |
| `POST` | `/api/v1/shipments` | `X-Integration-Key` | Create a shipment and initial tracking event |
| `GET` | `/api/v1/shipments/{id}` | `X-Integration-Key` | Read shipment details and tracking timeline |
| `POST` | `/api/v1/shipments/{id}/status` | `X-Integration-Key` | Add a status event and update shipment state |
| `POST` | `/api/v1/webhooks/order-events` | Signed event | Receive `order.created`, `order.updated`, and `order.paid` events |

### Storefront order upsert example

```bash
curl -X POST http://127.0.0.1:8000/api/v1/orders \
  -H 'Content-Type: application/json' \
  -H 'X-Integration-Key: replace-with-your-key' \
  -d '{
    "external_id": "store-order-1001",
    "order_number": "LM-1001",
    "customer_name": "Taylor Reed",
    "customer_email": "taylor@example.com",
    "shipping_address": {
      "line1": "1 Example Street",
      "city": "New York",
      "state": "NY",
      "postal_code": "10001",
      "country": "US"
    },
    "status": "paid",
    "total": 84.50,
    "items": [
      {"sku": "SERUM-01", "name": "Velvet Glow Serum", "quantity": 2, "unit_price": 42.25}
    ]
  }'
```

### Signed webhook example

Create the raw JSON body first, calculate its HMAC-SHA256 digest using the shared secret, and send the same raw bytes with the signature header. The application stores the event before queuing processing. Repeated `X-Event-Id` values are acknowledged as duplicates and are not queued twice.

```bash
BODY='{"id":"evt-1001","type":"order.created","data":{"order":{"id":"store-order-1001","number":"LM-1001","customer":{"name":"Taylor Reed"},"total":84.50}}}'
SIGNATURE=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$LOGISTICS_WEBHOOK_SECRET" -hex | sed 's/^.* //')

curl -X POST http://127.0.0.1:8000/api/v1/webhooks/order-events \
  -H 'Content-Type: application/json' \
  -H 'X-Event-Id: evt-1001' \
  -H 'X-Event-Type: order.created' \
  -H "X-Webhook-Signature: $SIGNATURE" \
  --data "$BODY"
```

## Connecting the e-commerce website

The e-commerce platform should call `POST /api/v1/orders` whenever an order is created, paid, updated, or cancelled. It should also register the webhook URL `/api/v1/webhooks/order-events` for near-real-time events where supported. Include the source order ID as `external_id`, preserve the source order number, send the normalized customer and shipping address fields, and include line items with SKU and quantities.

The first connector intentionally uses a generic payload shape because the platform has not yet been specified. A provider-specific adapter should be added after confirming whether the store is Shopify, WooCommerce, Magento, or a custom API. The adapter should map the provider payload to the generic order contract, preserve the provider's raw event under `metadata`, and retry transient failures.

## Orders module

The admin Orders module is available at `/orders` and is protected by the same `auth` and `logistics` middleware as the dashboard. It supports server-side search across order ID, order number, customer name, and email; status filters for all, paid, processing, and cancelled orders; pagination; order detail pages at `/orders/{order}`; customer shipping details; line items; totals; linked shipments; and tracking-event timelines. The dashboard Orders navigation item now links to this module instead of acting as an in-page placeholder.

The existing `/api/v1/orders` endpoint remains the integration surface for the e-commerce website. Storefront order events should continue to be sent through that API or the signed webhook endpoint so the Orders page displays synchronized records.

## Admin authentication

The dashboard is protected by Laravel session authentication. Guests visiting `/` are redirected to `/login`. The login endpoint is `POST /login`, logout is `POST /logout`, and login attempts are throttled after repeated failures. The login form includes CSRF protection and a password visibility toggle.

The `users.role` column and `logistics` middleware restrict dashboard access to `admin`, `operations_manager`, `warehouse_staff`, and `support`. A user with another role, such as `customer` or `rider`, receives a `403` response and should use a separate rider or customer-facing application.

For the seeded local demo only, the account is:

```text
Email:    amelia@lumora.example
Password: password
Role:     admin
```

Change or remove this demo account before production. If an existing project already has users, add the role migration and update the intended operations users to an approved logistics role rather than seeding demo data into the live database.

## Visual system

The interface follows the attached storefront reference using warm ivory, soft blush, deep plum, terracotta coral, muted sage, and ochre gold. Headings use a refined serif treatment, while operational data uses a clean sans-serif. The dashboard uses a persistent plum navigation rail on desktop and a collapsible rail on mobile, with soft borders and restrained shadows rather than dense enterprise styling.

## Production checklist

Before exposing the API publicly, replace demo secrets, configure HTTPS, restrict database and queue access, set the correct `APP_ENV` and `APP_DEBUG` values, configure an external database or managed production database, set up a supervised queue worker, and configure monitoring for failed jobs and failed webhook deliveries. The provider-specific commerce connector should be tested against a staging storefront before production events are enabled.

## Sorting Center ERP

The Sorting Center module is available at `/sorting-center` for authenticated logistics staff. It is database-backed and follows the operational sequence **Receive Parcel → Scan Parcel → Read Delivery Address → Determine Delivery Area → Sort Parcel → Identify Rider → Assign Parcel → Rider Receives Assignment**.

A parcel can be received manually or created automatically when an e-commerce order is synchronized through `POST /api/v1/orders` or the signed webhook endpoint. The scan action records a row in `parcel_scans` and automatically checks active destination-area rules against the parcel city and postal code. Staff can override the detected area during sorting, assign a sort bin, and assign only an approved rider who is mapped to that destination area.

The primary ERP tables are `parcels`, `parcel_scans`, `destination_areas`, `rider_profiles`, `rider_area_assignments`, `erp_messages`, and `commission_transactions`. Parcel statuses currently include `received`, `scanned`, `sorted`, `assigned`, and `rider_received`. Every receive, scan, sort, and assignment operation is stored in the database rather than being a frontend-only simulation.

## Destination areas

Administrators can manage area rules at `/areas`. Create an area with a unique code, comma-separated city or municipality names, and optional comma-separated postal codes. When a parcel is scanned, active rules are evaluated automatically. Areas can be paused when the sorting center should temporarily stop using them.

## Rider registration and approval

Riders register at `/rider/register`. Registration creates a normal Laravel user with the `rider` role and a pending `rider_profiles` record. Administrators review registrations at `/riders`, approve or disapprove the registration, and assign one or more delivery areas. A rider cannot receive an assignment until the profile is approved and the rider is assigned to the parcel's destination area.

The rider application dashboard is `/rider/dashboard`. An approved rider can view assigned parcels and accept an assignment, changing its status from `assigned` to `rider_received`. Rider messaging is available at `/rider/messages`; administrator messaging is available at `/messages`.

For the demo seed, the following rider accounts are created with password `rider12345`:

| Account | Email | Assigned area |
|---|---|---|
| Rider 01 | `rider01@example.com` | Area A — Santa Cruz |
| Rider 02 | `rider02@example.com` | Area B — Pagsanjan |
| Rider 03 | `rider03@example.com` | Area C — Los Baños |

Change or remove these demo accounts before production use.

## Reports and commissions

The reports page is available at `/reports`. It summarizes parcel throughput by status, rider assignment volume, and commission totals for a selected date range. When staff assign a parcel, they may enter an optional commission amount. The amount is stored as a pending record in `commission_transactions` and is included in the reports view for later payout processing.

## Rebuilding the local demo database

After pulling these ERP changes into the existing Laravel project, run the following commands from the directory containing `artisan`:

```bash
php artisan migrate
php artisan db:seed --class=DatabaseSeeder
php artisan optimize:clear
npm install
npm run build
php artisan serve
```

Then open `http://127.0.0.1:8000/login` for the admin console or `http://127.0.0.1:8000/rider/register` for rider registration. The existing smoke checks can be repeated with `php artisan view:cache`, `php artisan route:cache`, and the local HTTP requests described in this README.

## Local database and MySQL Workbench

The website uses its own `logistics` database on the local XAMPP MariaDB server, through Laravel's `mysql` connection. MariaDB is the installed server; MySQL Workbench is the database client. Workbench may display a server compatibility warning for MariaDB.

Start **MySQL** in the XAMPP Control Panel when the server is stopped. PHP must have `pdo_mysql` enabled.

Create a Standard TCP/IP connection in Workbench with:

- Connection name: Logistics Website
- Hostname: 127.0.0.1
- Port: 3306
- Username: logistics_app
- Password: the DB_PASSWORD value in your local .env
- Default schema: logistics

The local database account is restricted to the logistics database. Do not publish .env or its backup. The password in .env.example is only a placeholder; a new installation needs its own database account and credentials.

The existing SQLite records were copied into this database without running demo seeders. The original database/database.sqlite and .env.backup are retained. New website changes now go to MySQL and are not synchronized back to SQLite.

For future schema changes, run `php artisan migrate`. After changing connection settings, run `php artisan config:clear` and restart running web servers and queue workers. Tests retain their separate in-memory SQLite configuration.