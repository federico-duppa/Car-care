# 🚗 Car-care — Registro de mantenimiento del auto

App web simple para llevar el registro de un auto: cargas de combustible (con
cálculo de consumo), mantenimientos y gastos (seguro, patente, peajes, multas…).
Pensada para uso personal, con login por Google restringido a tu propia cuenta.

Construida con **Laravel 13 + Blade + Tailwind**, pensada para deployar en
**Laravel Cloud** (con auto-pausa / scale-to-zero para costo mínimo).

## Funcionalidades

- **Combustible**: fecha, odómetro, litros, costo, tanque lleno. Calcula
  consumo (L/100km y km/L) con método tanque-lleno a tanque-lleno, precio por
  litro y costo de combustible.
- **Mantenimiento**: aceite, filtros, frenos, neumáticos, etc., con fecha, km,
  costo y taller.
- **Gastos**: impuestos/patente, seguro, estacionamiento, multas, peajes,
  lavado, accesorios. Soporta gastos recurrentes.
- **Tablero**: consumo promedio, costo por km, gasto total, gasto mensual
  (últimos 6 meses) y gastos por categoría.
- **Multi-vehículo**: el modelo soporta varios autos con selector.
- **Export CSV** de cada sección.
- **Login con Google** restringido por la variable `ALLOWED_EMAILS`.

## Cómo funciona el login (importante)

No hay registro abierto ni contraseñas. Te logueás con Google y la app sólo
deja entrar a las direcciones listadas en `ALLOWED_EMAILS` (separadas por
comas). Si la lista está vacía, **no entra nadie** (falla cerrado). El email no
está hardcodeado: se lee de variable de entorno.

---

## Desarrollo local

Requisitos: PHP 8.3+, Composer, Node 18+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# Base de datos local: SQLite (un archivo)
touch database/database.sqlite
php artisan migrate --seed     # --seed carga datos de ejemplo (opcional)

npm run build                  # o `npm run dev` para hot reload
php artisan serve
```

Para probar el login con Google localmente necesitás credenciales OAuth (ver
abajo) con la URI de callback `http://localhost:8000/auth/google/callback`, y
poner tu mail en `ALLOWED_EMAILS` dentro de `.env`.

Tests:

```bash
php artisan test
```

---

## Crear credenciales de Google OAuth

1. Entrá a <https://console.cloud.google.com/> → creá un proyecto.
2. **APIs y servicios → Pantalla de consentimiento OAuth**: tipo *Externo*,
   completá lo mínimo, agregá tu mail como *usuario de prueba*.
3. **Credenciales → Crear credenciales → ID de cliente OAuth → Aplicación web**.
4. En **URIs de redireccionamiento autorizados** agregá:
   - Local: `http://localhost:8000/auth/google/callback`
   - Producción: `https://TU-DOMINIO/auth/google/callback`
5. Copiá el **Client ID** y **Client Secret** a las variables de entorno.

---

## Deploy en Laravel Cloud

> Resultado: la app queda viva, mantiene estado en Postgres gestionado, se
> auto-pausa cuando no la usás y tiene un tope de gasto. Costo realista para un
> usuario: ~USD 5/mes (el plan Starter incluye USD 5 de créditos de uso).

1. **Conectá el repo**: en <https://cloud.laravel.com> creá una aplicación
   apuntando a este repositorio y a la rama que uses.
2. **Base de datos**: agregá un **Postgres gestionado**. Laravel Cloud inyecta
   las variables de conexión automáticamente. La base es independiente del
   compute, así que **los datos sobreviven a la auto-pausa y a los deploys**.
3. **Variables de entorno** (en el panel de la app):
   ```
   APP_NAME="Mi Auto"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://TU-DOMINIO
   APP_KEY=            # generá una con `php artisan key:generate --show`
   DB_CONNECTION=pgsql # normalmente lo setea Laravel Cloud
   SESSION_DRIVER=database
   QUEUE_CONNECTION=database

   ALLOWED_EMAILS=tu-email@gmail.com
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   GOOGLE_REDIRECT_URI=https://TU-DOMINIO/auth/google/callback
   APP_CURRENCY=ARS
   ```
4. **Comando de build**: `npm ci && npm run build`
5. **Comando de deploy** (post-deploy): `php artisan migrate --force`
6. **Scale-to-zero**: activá la hibernación en la configuración de compute.
7. **Spending limit**: poné un tope de gasto (ej. USD 10) como red de seguridad.
8. En Google Cloud, agregá la URI de callback de producción (paso 4 de OAuth).

### Backups

La base vive en el Postgres gestionado de Laravel Cloud (con sus backups).
Igual conviene exportar de vez en cuando con los botones **Exportar CSV** de
cada sección para tener una copia propia.

---

## Estructura del proyecto

```
app/
  Http/Controllers/        Dashboard, Vehiculo, Combustible, Mantenimiento, Gasto, Export, Auth/Google
  Http/Middleware/         ShareVehiculos (resuelve el vehículo activo)
  Models/                  Vehiculo, CargaCombustible, Mantenimiento, Gasto, User
  Services/VehiculoStats   Cálculos de consumo, costo/km y totales
database/migrations/       vehiculos, carga_combustibles, mantenimientos, gastos
resources/views/           Blade (dashboard, combustible, mantenimientos, gastos, vehiculos)
tests/                     SmokeTest (Feature) + VehiculoStatsTest (Unit)
```
