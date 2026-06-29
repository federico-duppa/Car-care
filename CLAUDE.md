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

## Funcionalidades actuales (inventario)

Lo que la app **ya hace** hoy, para no re-investigar el código en cada sesión:

**Entidades y datos.** `Vehiculo` (marca, modelo, año, patente, km_actual, notas). `CargaCombustible` (fecha, odómetro, litros, costo_total, tanque_lleno, estación, notas + snapshots `usd_blue`/`usd_oficial`). `Mantenimiento` (fecha, odómetro, tipo [8 predefinidos: aceite/filtros/frenos/neumáticos/correa/batería/service/otro], costo, taller, notas + snapshots). `Gasto` (fecha, categoría [8: impuestos/seguro/estacionamiento/multas/peajes/lavado/accesorios/otros], monto, descripción, flag `recurrente` + snapshots). Multi-vehículo con vehículo activo por sesión.

**Cálculos (`VehiculoStats`).** Consumo promedio y del último intervalo (L/100km tanque-lleno a tanque-lleno), km/L, distancia total, costo/km global, precio promedio por litro. Totales por rubro (combustible/mantenimiento/gastos) y general. Gastos por categoría. Gasto mensual últimos 6 meses. Todo consciente de moneda (ARS/USD blue u oficial vía snapshots).

**UI.** Dashboard con stat cards + gráfico de barras de gasto mensual (6 meses) + tabla gastos por categoría + últimos 5 mantenimientos. Listados paginados (25/pág) con alta/edición/borrado por recurso. Toggle ARS/USD + selector blue/oficial en el nav. Exportación **CSV** por sección (combustible, mantenimientos, gastos) con BOM UTF-8. Flash messages, empty states, confirmaciones de borrado. Mobile-first.

**Auth.** Solo Google OAuth con allow-list `ALLOWED_EMAILS` (falla cerrado). Single-user por cuenta.

**Gaps conocidos (lo que NO tiene).** Sin recordatorios/alertas de mantenimiento por km o fecha; sin vencimientos de seguro/patente/VTV; sin adjuntos (fotos de tickets, pólizas); sin gráficos de tendencia más allá del barchart mensual; sin filtros por fecha/búsqueda en listados; sin app nativa (es web responsive); sin módulo de documentos; sin OBD-II/GPS/geolocalización; sin backup/restore más allá del CSV manual; sin import (solo export).

## Convenciones del proyecto

- **Laravel 13**: los modelos usan atributos PHP (`#[Fillable]`, `#[Hidden]`) y `casts()` como método; los comandos usan `#[Signature]`/`#[Description]`.
- **Migraciones**: comparten timestamp por defecto → ordenan alfabéticamente. Por eso `vehiculos` tiene un timestamp un segundo anterior (debe crearse antes que las tablas hijas con foreign keys; SQLite no lo valida pero Postgres sí).
- **Idioma**: nombres de dominio, tablas, columnas y UI en español (`vehiculos`, `cargas`, `gastos`, `montoArs`). Código/comentarios técnicos en inglés.
- **Vistas**: componentes Blade reutilizables `<x-stat>`, `<x-flash>` (+ los de Breeze: `<x-input-label>`, `<x-text-input>`, etc.). Listas con `index.blade.php`, altas/ediciones con un único `form.blade.php` por recurso.

## Deploy (Laravel Cloud)

Postgres gestionado (estado sobrevive a la hibernación), build `npm ci && npm run build`, deploy `php artisan migrate --force`. `SESSION_DRIVER=database` y `QUEUE_CONNECTION=database` (importantes con scale-to-zero). La feature USD no requiere config, pero producción debe permitir salida HTTPS a dolarapi.com y argentinadatos.com. Ver `README.md` para la guía completa (incl. credenciales de Google OAuth).
