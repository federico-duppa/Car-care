# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Qué es

App Laravel 13 (Blade + Tailwind) para llevar el registro de mantenimiento de un auto: combustible (con cálculo de consumo), mantenimientos y gastos. Uso personal, login solo con Google. Deploy en Laravel Cloud (Postgres + scale-to-zero). En dev local usa SQLite.

## ⚠️ Mobile-first

**La interfaz principal es la de mobile.** El usuario usa la app desde el celular. Al tocar cualquier vista Blade, verificá y priorizá el layout en pantallas chicas (menú hamburguesa, apilado vertical, sin overflow horizontal). Las clases `sm:`/`lg:` son la variante de escritorio, no al revés. Ojo con elementos que viven solo en la barra de escritorio (`hidden sm:flex`): si agregás un control ahí, también va en el menú responsive de `layouts/navigation.blade.php`.

## Comandos

```bash
# Setup local (SQLite)
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # seeder = datos demo (incl. cotizaciones USD)

# Dev
php artisan serve               # http://localhost:8000
npm run dev                     # Vite con hot reload (o `npm run build` para assets prod)

# Tests
php artisan test
php artisan test --filter VehiculoStatsTest          # una clase
php artisan test --filter test_usd_totals_sum_each_row_at_its_rate  # un test

# Tras tocar vistas/clases nuevas de Tailwind: SIEMPRE recompilar o no aparecen
npm run build

# Completar cotizaciones USD de registros viejos
php artisan rates:backfill
```

Tras editar Blade con clases Tailwind nuevas hay que correr `npm run build`; si no, las clases no existen en el CSS compilado y el estilo "no aplica" (bug fácil de pasar por alto).

## Autenticación

- Login **solo con Google** (Socialite). No hay registro ni contraseñas — todo el scaffolding de Breeze para eso fue eliminado; quedan solo `AuthenticatedSessionController` (create/destroy) y `Auth\GoogleController`.
- Acceso restringido por `ALLOWED_EMAILS` (env, lista separada por comas). Vacío = no entra nadie (falla cerrado). El filtro está en `GoogleController::callback`.
- `users.password` es nullable; el usuario se crea/actualiza por email en el callback.

## Arquitectura

Cada registro de dinero (`CargaCombustible`, `Mantenimiento`, `Gasto`) pertenece a un `Vehiculo`, que pertenece a un `User`. Todo está scopeado por usuario; los controladores verifican `user_id` en cada acción (ver `authorizeRecord`).

**Vehículo activo (`app/Http/Middleware/ShareVehiculos.php`).** Middleware appendeado al grupo `web` (registrado en `bootstrap/app.php`). En cada request autenticado resuelve el vehículo activo (de sesión, fallback al primero) y la **moneda/cotización activa**, y los comparte con todas las vistas + los expone en `$request->attributes`. Los controladores leen el vehículo activo de `$request->attributes->get('vehiculo')`, no de la ruta.

**Cálculos (`app/Services/VehiculoStats.php`).** Centraliza consumo, costo/km y totales. Es **consciente de moneda y cotización**: se construye con `VehiculoStats::for($vehiculo, $moneda, $tipo, $fallbackRate)`. El consumo usa método **tanque-lleno a tanque-lleno** (las cargas parciales se acumulan hasta el próximo tanque lleno). Tiene tests unitarios — si cambiás la lógica, actualizalos.

**Ancla en dólares (anti-inflación).** En Argentina los pesos no sirven como ancla temporal. Cada registro guarda la cotización del dólar de **su propia fecha** en dos columnas: `usd_blue` y `usd_oficial`. El snapshot se hace solo en el trait `app/Models/Concerns/ConvertibleAUsd.php` (evento `saving`), trayendo la cotización vía `ExchangeRateService::forDate($fecha, $tipo)`. La UI tiene toggle ARS/USD + selector blue/oficial (sesión: `moneda`, `usd_tipo`). Conversión:
- Montos por fila: `show_money($row->montoArs(), $row->usdRate(current_usd_tipo()))`.
- Totales ya convertidos por el service: `money_active($valor)`.
- Montos crudos en ARS: `money($ars)`. Helpers en `app/helpers.php`.
- `VehiculoStats` suma el USD por fila en SQL dividiendo por la columna de la cotización elegida, con fallback al dólar actual para filas sin snapshot.

**Cotizaciones (`app/Services/ExchangeRateService.php`).** Siempre activo, sin variable de entorno. Actual desde dolarapi.com, histórica desde argentinadatos.com. Cacheado, **a prueba de fallos** (cualquier error → null, nunca rompe un guardado ni un render). Se auto-desactiva en tests (`app()->runningUnitTests()`) para no tocar la red; por eso las pruebas de conversión setean `usd_blue`/`usd_oficial` a mano.

## Convenciones del proyecto

- **Laravel 13**: los modelos usan atributos PHP (`#[Fillable]`, `#[Hidden]`) y `casts()` como método; los comandos usan `#[Signature]`/`#[Description]`.
- **Migraciones**: comparten timestamp por defecto → ordenan alfabéticamente. Por eso `vehiculos` tiene un timestamp un segundo anterior (debe crearse antes que las tablas hijas con foreign keys; SQLite no lo valida pero Postgres sí).
- **Idioma**: nombres de dominio, tablas, columnas y UI en español (`vehiculos`, `cargas`, `gastos`, `montoArs`). Código/comentarios técnicos en inglés.
- **Vistas**: componentes Blade reutilizables `<x-stat>`, `<x-flash>` (+ los de Breeze: `<x-input-label>`, `<x-text-input>`, etc.). Listas con `index.blade.php`, altas/ediciones con un único `form.blade.php` por recurso.

## Deploy (Laravel Cloud)

Postgres gestionado (estado sobrevive a la hibernación), build `npm ci && npm run build`, deploy `php artisan migrate --force`. `SESSION_DRIVER=database` y `QUEUE_CONNECTION=database` (importantes con scale-to-zero). La feature USD no requiere config, pero producción debe permitir salida HTTPS a dolarapi.com y argentinadatos.com. Ver `README.md` para la guía completa (incl. credenciales de Google OAuth).
