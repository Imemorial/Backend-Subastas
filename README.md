# BitsAuction API (Laravel 12)

API REST para la plataforma de subastas por tokens.

## Requisitos

- PHP 8.2+
- Composer
- MySQL 8 (o SQLite para desarrollo rápido)

## Instalación

```bash
cd backend
composer install
cp .env.example .env   # Windows: copy .env.example .env
php artisan key:generate
```

### MySQL (.env)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bitsauction
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
php artisan schedule:work
```

En desarrollo, **`schedule:work`** es necesario para que las subastas programadas se activen solas a su fecha/hora (revisa cada 5 segundos).

API disponible en `http://localhost:8000/api/v1`

## Usuarios de prueba (seeder)

| Rol | Email | Contraseña | Bits |
|-----|-------|------------|------|
| **Admin** | `admin@bitsauction.test` | `password` | 0 |
| **Cliente** | `cliente@bitsauction.test` | `password` | 150 |

## Roles

- **admin** — CRUD de productos, crear/gestionar subastas, ver analíticas de margen
- **client** — Pujar, comprar Bits, ver historial

## Endpoints principales

### Públicos
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`
- `GET /api/v1/auctions` — subastas activas
- `GET /api/v1/auctions/{id}`
- `GET /api/v1/bit-packs`

### Cliente (Bearer token Sanctum)
- `GET /api/v1/auth/me`
- `POST /api/v1/auth/logout`
- `POST /api/v1/auctions/{id}/bids` — pujar (1 Bit)
- `POST /api/v1/bit-packs/purchase`
- `GET /api/v1/me/bids`
- `GET /api/v1/me/transactions`

### Admin (`role: admin`)
- `GET|POST /api/v1/admin/products` — listar / crear producto (+ imagen)
- `GET|PUT|DELETE /api/v1/admin/products/{id}`
- `GET /api/v1/admin/auctions`
- `POST /api/v1/admin/auctions` — crear subasta desde producto
- `POST /api/v1/admin/auctions/{id}/activate`
- `POST /api/v1/admin/auctions/{id}/pause`
- `POST /api/v1/admin/auctions/{id}/resume`
- `GET /api/v1/admin/analytics/weekly-margin`

## Crear producto + subasta (admin)

```bash
# 1. Login admin
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@bitsauction.test","password":"password"}'

# 2. Subir producto con imagen
curl -X POST http://localhost:8000/api/v1/admin/products \
  -H "Authorization: Bearer {TOKEN}" \
  -F "name=AirPods Pro 2" \
  -F "real_cost=180" \
  -F "retail_value=279" \
  -F "status=published" \
  -F "image=@/ruta/imagen.jpg"

# 3. Crear subasta activa
curl -X POST http://localhost:8000/api/v1/admin/auctions \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{"product_id":1,"start_immediately":true}'
```

## Motor de margen

- `AuctionMarginService` — evalúa si la subasta puede cerrar (17%–25%)
- `WeeklyMarginBalancerService` — balanceo semanal global (17%–20%)
- `php artisan auctions:process-timers` — activa subastas programadas y procesa expiración (cron cada 5s vía `schedule:work`)

## Tests

```bash
php artisan test
```
