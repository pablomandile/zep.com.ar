<p align="center">
  <img src="public/img/zep-logo.png" alt="Zep: acortá tus URLs. Ve más lejos." width="420">
</p>

<p align="center">
  <strong>Acortador de URLs propio en <a href="https://zep.com.ar">zep.com.ar</a></strong><br>
  Pegás una URL larga, apretás <em>Acortar</em> y obtenés el link más corto posible.
</p>

---

## Qué hace

- **Acortar en un clic.** Interfaz minimalista tipo Google Shortener. Si pegás `ejemplo.com/algo`, se toma como `https://ejemplo.com/algo`.
- **El código más corto posible.** Los códigos son aleatorios y arrancan en 3 caracteres (unas 185 mil combinaciones). Cuando ese espacio se llena, pasan a 4, 5 y así. No son secuenciales, así que nadie puede recorrer los links uno por uno, y no usan caracteres ambiguos (`0 O 1 l I`).
- **Alias personalizados.** Por ejemplo `zep.com.ar/promo`, con letras, números, `-` y `_`.
- **Estadísticas de clics.** Clics por día, visitantes únicos, de dónde vienen, dispositivos, navegadores, sistemas operativos y países. Los bots y las vistas previas de WhatsApp, Slack y otras apps no se cuentan.
- **Código QR.** Se genera en el navegador y se descarga en PNG o SVG.
- **Vencimiento y desactivación.** Un link vencido o desactivado responde con una página de "no disponible" (HTTP 410).
- **API REST** con tokens personales, para acortar desde otras apps.
- **Cuentas con aprobación.** El registro es público (con email o con Google), pero cada cuenta queda pendiente hasta que el admin la aprueba. El admin recibe un mail con cada registro nuevo.
- **Panel de administración.** Aprobar, rechazar o revocar usuarios, y ver o desactivar cualquier link.
- **Términos y privacidad.** Hay que aceptarlos al registrarse, y la app los vuelve a pedir si cambia la versión. Están en el marco de la Ley 25.326.
- Interfaz en español rioplatense, con modo claro y oscuro, y adaptada a celulares.

## Stack

| | |
|---|---|
| Backend | Laravel 13 · PHP 8.4 · MySQL 8 |
| Frontend | Inertia 3 · Vue 3 (TypeScript) · Tailwind CSS 4 · shadcn-vue (reka-ui) |
| Auth | Fortify (email, 2FA, passkeys) · Socialite (Google) · Sanctum (tokens de API) |
| Rutas tipadas | Wayfinder |
| Tests | Pest / PHPUnit |

## Cómo funciona por dentro

```
zep.com.ar/{code}  →  RedirectController  →  302 al destino  (+ registro del clic)
                                          →  410 si está vencido o desactivado
                                          →  404 si no existe
```

- **La redirección es un `302`, no un `301`.** Los navegadores cachean los 301 y esos clics dejarían de contarse.
- **`links.code` usa la collation `utf8mb4_bin`.** La de MySQL por defecto no distingue mayúsculas, y `aB3` y `Ab3` chocarían.
- **La ruta `{code}` está registrada como `fallback()`**, así que siempre queda detrás de las rutas de la app y de los paquetes. Además hay una lista de palabras reservadas (`login`, `admin`, `api`…) en [`config/shortener.php`](config/shortener.php).
- **Un link borrado no libera su código**, para que un link viejo que alguien tenga guardado nunca lleve a otro destino.
- **Los clics no guardan la IP en claro**: guardan un hash con sal, que alcanza para contar visitantes únicos.

| Pieza | Archivo |
|---|---|
| Generador de códigos | [`app/Services/ShortCodeGenerator.php`](app/Services/ShortCodeGenerator.php) |
| Crear un link (web y API) | [`app/Actions/CreateShortLink.php`](app/Actions/CreateShortLink.php) + [`StoreLinkRequest`](app/Http/Requests/StoreLinkRequest.php) |
| Redirección y clics | [`RedirectController`](app/Http/Controllers/RedirectController.php) + [`ClickRecorder`](app/Services/ClickRecorder.php) |
| Estadísticas | [`app/Services/LinkStats.php`](app/Services/LinkStats.php) |
| Aprobación de cuentas | [`EnsureUserIsApproved`](app/Http/Middleware/EnsureUserIsApproved.php), [`OnboardNewUser`](app/Actions/OnboardNewUser.php) |
| Login con Google | [`GoogleController`](app/Http/Controllers/Auth/GoogleController.php) |
| Términos y privacidad | [`resources/legal/`](resources/legal) + [`EnsureTermsAccepted`](app/Http/Middleware/EnsureTermsAccepted.php) |
| API v1 | [`routes/api.php`](routes/api.php) + [`Api/V1/LinkController`](app/Http/Controllers/Api/V1/LinkController.php) |

## API

Los tokens se crean en **Configuración → API**, con permisos `links:read` y/o `links:write`.

```http
Authorization: Bearer TU_TOKEN
Accept: application/json
```

| Método | Ruta | Permiso | Descripción |
|---|---|---|---|
| `POST` | `/api/v1/links` | `links:write` | Crear: `{ url, alias?, expires_at? }`. Devuelve `201`, o `200` si ya habías acortado esa URL |
| `GET` | `/api/v1/links` | `links:read` | Listar (paginado; `?search=`, `?per_page=`) |
| `GET` | `/api/v1/links/{code}` | `links:read` | Detalle |
| `PATCH` | `/api/v1/links/{code}` | `links:write` | Cambiar `url`, `is_active` o `expires_at` |
| `DELETE` | `/api/v1/links/{code}` | `links:write` | Eliminar (`204`) |
| `GET` | `/api/v1/links/{code}/stats` | `links:read` | Estadísticas (`?from=&to=` en formato `YYYY-MM-DD`) |

Límite: 60 pedidos por minuto por token. Los errores de validación vuelven con `422`.

```bash
curl -X POST https://zep.com.ar/api/v1/links \
  -H "Authorization: Bearer $ZEP_TOKEN" \
  -H "Accept: application/json" \
  -d url="https://una-url-muy-larga.com/con/muchos/segmentos"
```

```php
// Desde otra app Laravel
$short = Http::withToken(config('services.zep.token'))
    ->acceptJson()
    ->post('https://zep.com.ar/api/v1/links', ['url' => $longUrl])
    ->json('data.short_url');
```

```json
{
  "data": {
    "code": "aB3",
    "short_url": "https://zep.com.ar/aB3",
    "original_url": "https://una-url-muy-larga.com/con/muchos/segmentos",
    "is_custom": false,
    "is_active": true,
    "is_expired": false,
    "expires_at": null,
    "clicks": 0,
    "last_clicked_at": null,
    "created_at": "2026-10-27T21:30:00-03:00",
    "updated_at": "2026-10-27T21:30:00-03:00"
  }
}
```

## Desarrollo local

Requisitos: PHP 8.4, Composer, Node 22, MySQL 8 (por ejemplo, con Laragon).

```bash
git clone https://github.com/pablomandile/zep.com.ar.git zep
cd zep
composer install
npm install
cp .env.example .env
php artisan key:generate
# Crear la base `zep` y completar DB_*, ADMIN_EMAIL y GOOGLE_* en .env
php artisan migrate --seed   # datos de prueba: admin, usuarios en cada estado y links con clics
npm run dev                   # o `npm run build`
```

Con Laragon queda en `http://zep.test`. El usuario del seeder es el de `ADMIN_EMAIL`, con la contraseña `password`.

### Variables de entorno propias

| Variable | Para qué |
|---|---|
| `SHORT_DOMAIN` | Dominio de los links cortos (`zep.com.ar`; en local, `zep.test`) |
| `ADMIN_EMAIL` | La cuenta con este email entra aprobada y como admin, y recibe los avisos de registro |
| `SHORT_CODE_MIN_LENGTH` | Largo inicial de los códigos (por defecto, 3) |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | Login con Google |
| `LEGAL_CONTACT_EMAIL` | Contacto que figura en los términos y la privacidad |

### Google OAuth

En la [consola de Google Cloud](https://console.cloud.google.com/apis/credentials), en el cliente OAuth de Zep:

- **Orígenes de JavaScript autorizados:** `http://zep.test` y `https://zep.com.ar`
- **URIs de redirección autorizados:** `http://zep.test/auth/google/callback` y `https://zep.com.ar/auth/google/callback`
- En la pantalla de consentimiento: la política de privacidad es `https://zep.com.ar/privacidad` y los términos, `https://zep.com.ar/terminos`.

### Tests y calidad

```bash
php artisan test        # Pest
vendor/bin/pint         # estilo PHP
npm run check           # formato y lint del frontend (Vite+)
npm run types:check     # vue-tsc
```

## Deploy (Hostinger compartido)

El server no tiene Node, así que **se compila en local** y se sube `public/build`.

1. `npm run build`
2. Subir el código y `public/build` por SSH/scp, haciendo antes un backup de lo que hay en el server.
3. En el server: `composer install --no-dev -o`, `php artisan migrate --force` y `php artisan config:cache route:cache view:cache`.
4. `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://zep.com.ar`, `SHORT_DOMAIN=zep.com.ar`, la base `u302133020_zep` y las credenciales de Google y del mail.
5. Cron del scheduler en hPanel.

Para verificar: `curl -I https://zep.com.ar/<code>` tiene que devolver `302` con su `Location`.

> El middleware de Inertia manda `Cache-Control: no-store` en las respuestas XHR. Sin eso, el CDN de Hostinger termina mostrando el JSON crudo de Inertia al volver a una pestaña. Hay un test que lo cubre: [`tests/Feature/InertiaCacheTest.php`](tests/Feature/InertiaCacheTest.php).

## Términos y privacidad

Los textos están en [`resources/legal/`](resources/legal) (Markdown) y su versión vigente, en [`config/legal.php`](config/legal.php). **Cada frase tiene que poder señalarse en el código.** Si cambia el comportamiento (qué se guarda, qué se borra, con quién se comparte), hay que actualizar el texto y subir la versión: eso vuelve a pedir la aceptación a todos. Los tests de [`TermsTest`](tests/Feature/TermsTest.php) atan lo que promete la política (por ejemplo, que eliminar la cuenta borra links, clics, tokens y sesiones) al código.
