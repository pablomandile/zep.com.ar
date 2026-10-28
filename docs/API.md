# API de Zep — v1

API REST para crear y administrar links cortos de **zep.com.ar** desde otras apps.

- **URL base:** `https://zep.com.ar/api/v1`
- **Formato:** JSON (UTF-8)
- **Autenticación:** token Bearer de la cuenta del administrador
- **Acceso:** **privado**. La API no está abierta a los usuarios de Zep: solo funciona con tokens que crea el administrador.

## Índice

1. [Primeros pasos](#primeros-pasos)
2. [Autenticación y permisos](#autenticación-y-permisos)
3. [Convenciones](#convenciones)
4. [El objeto Link](#el-objeto-link)
5. [Endpoints](#endpoints)
6. [Errores](#errores)
7. [Límite de pedidos](#límite-de-pedidos)
8. [Reglas para acortar](#reglas-para-acortar)
9. [Cómo se comportan los links cortos](#cómo-se-comportan-los-links-cortos)
10. [Ejemplos de cliente](#ejemplos-de-cliente)

---

## Primeros pasos

1. Entrá a https://zep.com.ar con la cuenta del **administrador**. Las demás cuentas no ven la sección API.
2. Andá a **Configuración → API** y creá un token con un nombre que identifique a la app que lo va a usar (por ejemplo, `mi-billetera`). Elegí los permisos.
3. Copiá el token: **se muestra una sola vez**. Guardalo en el `.env` de la otra app, nunca en el código ni en el frontend.
4. Probalo:

```bash
curl -X POST https://zep.com.ar/api/v1/links \
  -H "Authorization: Bearer $ZEP_TOKEN" \
  -H "Accept: application/json" \
  -d url="https://una-url-muy-larga.com/con/muchos/segmentos"
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

El link corto está en `data.short_url`.

---

## Autenticación y permisos

Todos los pedidos llevan el token en el header `Authorization`:

```http
Authorization: Bearer 12|xYz...
Accept: application/json
```

Cada token tiene uno o los dos permisos siguientes:

| Permiso       | Habilita                                        |
| ------------- | ----------------------------------------------- |
| `links:read`  | Listar links, ver el detalle y las estadísticas |
| `links:write` | Crear, editar y eliminar links                  |

Una app que solo acorta necesita `links:write`. Si además consulta clics, necesita también `links:read`.

**Quién puede usar la API**

- Solo el **administrador** (la cuenta de `ADMIN_EMAIL`) puede crear tokens, y la API solo acepta tokens de una cuenta administradora. Un token de cualquier otra cuenta recibe `403`, aunque esa cuenta esté aprobada.
- **Usá un token distinto para cada app.** Así, si uno se filtra, lo revocás sin cortarle el acceso a las demás.
- Los tokens no vencen. Se revocan desde **Configuración → API**, y el revocado deja de funcionar en el pedido siguiente.
- Un token solo ve y toca **los links de la cuenta del admin**. Un link de otra cuenta responde `404`, igual que uno que no existe. Los links creados por la API aparecen en _Mis enlaces_, mezclados con los que se crean desde la web.

---

## Convenciones

- **Cuerpo del pedido:** JSON (`Content-Type: application/json`) o formulario (`application/x-www-form-urlencoded`). Las dos formas valen igual.
- **Mandá siempre `Accept: application/json`**, así cualquier error vuelve como JSON.
- **Fechas en las respuestas:** ISO 8601 con la zona de Buenos Aires, por ejemplo `2026-12-31T23:59:00-03:00`.
- **Fechas en los pedidos:** ISO 8601. Conviene mandarlas **con zona**, sea `Z` o `-03:00`. Una fecha sin zona, como `2026-12-31 23:59`, se toma como hora de Buenos Aires.
- **Respuesta de un objeto:** viene dentro de `data`.
- **Listados:** `data` (un array), `links` y `meta` (la paginación).

---

## El objeto Link

| Campo             | Tipo           | Descripción                                                                     |
| ----------------- | -------------- | ------------------------------------------------------------------------------- |
| `code`            | string         | El código del link. **Distingue mayúsculas**: `aB3` y `ab3` son links distintos |
| `short_url`       | string         | El link corto completo, listo para compartir (`https://zep.com.ar/aB3`)         |
| `original_url`    | string         | La URL de destino                                                               |
| `is_custom`       | boolean        | `true` si el código es un alias elegido; `false` si es aleatorio                |
| `is_active`       | boolean        | `false` si se desactivó a mano                                                  |
| `is_expired`      | boolean        | `true` si `expires_at` ya pasó                                                  |
| `expires_at`      | string \| null | Fecha de vencimiento, o `null` si no vence                                      |
| `clicks`          | integer        | Clics totales desde que se creó. No cuenta bots ni vistas previas               |
| `last_clicked_at` | string \| null | Fecha del último clic                                                           |
| `created_at`      | string         | Fecha de creación                                                               |
| `updated_at`      | string         | Fecha de la última modificación                                                 |

Un link **redirige** solo cuando `is_active` es `true` y `is_expired` es `false`.

---

## Endpoints

| Método   | Ruta                  | Permiso       | Qué hace                  |
| -------- | --------------------- | ------------- | ------------------------- |
| `POST`   | `/links`              | `links:write` | Crea un link              |
| `GET`    | `/links`              | `links:read`  | Lista tus links           |
| `GET`    | `/links/{code}`       | `links:read`  | Muestra un link           |
| `PATCH`  | `/links/{code}`       | `links:write` | Edita un link             |
| `DELETE` | `/links/{code}`       | `links:write` | Elimina un link           |
| `GET`    | `/links/{code}/stats` | `links:read`  | Devuelve las estadísticas |

### Crear un link

`POST /links`

| Campo        | Tipo   | Obligatorio | Descripción                                                                                              |
| ------------ | ------ | ----------- | -------------------------------------------------------------------------------------------------------- |
| `url`        | string | sí          | URL de destino, de hasta 2048 caracteres. Si no tiene esquema, se le agrega `https://`                   |
| `alias`      | string | no          | Código elegido: de 3 a 50 caracteres, con letras, números, `-` y `_`. Ver [reglas](#reglas-para-acortar) |
| `expires_at` | string | no          | Fecha de vencimiento, que tiene que ser futura                                                           |

```bash
curl -X POST https://zep.com.ar/api/v1/links \
  -H "Authorization: Bearer $ZEP_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://ejemplo.com/promo","alias":"promo-2026","expires_at":"2026-12-31T23:59:00-03:00"}'
```

**Respuestas**

- `201 Created`: se creó el link. Devuelve el [objeto Link](#el-objeto-link).
- `200 OK`: **ya habías acortado esa URL** y se devuelve el link existente, sin crear otro. Pasa solo si mandás la URL sin `alias` ni `expires_at` y el link anterior es aleatorio, está activo y no vence.
- `422`: la URL o el alias no son válidos (ver [Errores](#errores)).

Por eso, para obtener el link corto alcanza con leer `data.short_url`: la respuesta trae lo mismo con `200` que con `201`.

### Listar links

`GET /links`

| Parámetro  | Por defecto | Descripción                                               |
| ---------- | ----------- | --------------------------------------------------------- |
| `search`   | —           | Filtra por código o URL de destino (coincidencia parcial) |
| `per_page` | `25`        | Cuántos links trae cada página, de 1 a 100                |
| `page`     | `1`         | Número de página                                          |

Los links vienen ordenados del más nuevo al más viejo.

```bash
curl "https://zep.com.ar/api/v1/links?search=promo&per_page=10" \
  -H "Authorization: Bearer $ZEP_TOKEN" -H "Accept: application/json"
```

```json
{
    "data": [
        {
            "code": "promo-2026",
            "short_url": "https://zep.com.ar/promo-2026",
            "...": "..."
        }
    ],
    "links": {
        "first": "https://zep.com.ar/api/v1/links?search=promo&per_page=10&page=1",
        "last": "https://zep.com.ar/api/v1/links?search=promo&per_page=10&page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 10,
        "from": 1,
        "to": 1,
        "total": 1,
        "path": "https://zep.com.ar/api/v1/links",
        "links": []
    }
}
```

Para recorrer todas las páginas, seguí `links.next` hasta que venga `null`.

### Ver un link

`GET /links/{code}`

Devuelve el [objeto Link](#el-objeto-link) con `200`, o `404` si no existe o no es tuyo.

### Editar un link

`PATCH /links/{code}`

Mandá solo los campos que querés cambiar.

| Campo        | Tipo           | Descripción                                                                       |
| ------------ | -------------- | --------------------------------------------------------------------------------- |
| `url`        | string         | Nuevo destino. El link corto sigue siendo el mismo                                |
| `is_active`  | boolean        | `false` lo desactiva (deja de redirigir) y `true` lo reactiva                     |
| `expires_at` | string \| null | Nueva fecha de vencimiento, que tiene que ser futura. `null` quita el vencimiento |

**El código no se puede cambiar**: un link que ya se compartió tiene que seguir apuntando a lo mismo. Si querés otro código, creá un link nuevo.

```bash
curl -X PATCH https://zep.com.ar/api/v1/links/promo-2026 \
  -H "Authorization: Bearer $ZEP_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"is_active":false,"expires_at":null}'
```

Devuelve el objeto Link actualizado con `200`.

### Eliminar un link

`DELETE /links/{code}`

Responde `204 No Content`, sin cuerpo. Desde ese momento el link corto da `404`.

**El código no se libera**: nadie puede volver a usarlo, ni siquiera vos, para que un link viejo que circule por ahí nunca termine llevando a otro lado. Si solo querés pausarlo, usá `PATCH` con `is_active: false`.

### Estadísticas

`GET /links/{code}/stats`

| Parámetro | Por defecto           | Descripción                                                                 |
| --------- | --------------------- | --------------------------------------------------------------------------- |
| `from`    | 29 días antes de `to` | Primer día del período (`YYYY-MM-DD`)                                       |
| `to`      | hoy                   | Último día del período, inclusive. Tiene que ser igual o posterior a `from` |

Los días se cuentan en horario de Buenos Aires.

```bash
curl "https://zep.com.ar/api/v1/links/promo-2026/stats?from=2026-10-01&to=2026-10-31" \
  -H "Authorization: Bearer $ZEP_TOKEN" -H "Accept: application/json"
```

```json
{
    "data": {
        "code": "promo-2026",
        "short_url": "https://zep.com.ar/promo-2026",
        "from": "2026-10-01",
        "to": "2026-10-31",
        "total_clicks": 152,
        "period_clicks": 87,
        "unique_visitors": 61,
        "last_clicked_at": "2026-10-27T18:04:11-03:00",
        "per_day": [
            { "date": "2026-10-01", "clicks": 0 },
            { "date": "2026-10-02", "clicks": 4 }
        ],
        "referers": [
            { "label": "Directo", "clicks": 40 },
            { "label": "instagram.com", "clicks": 31 }
        ],
        "devices": [
            { "label": "mobile", "clicks": 70 },
            { "label": "desktop", "clicks": 17 }
        ],
        "browsers": [{ "label": "Chrome", "clicks": 55 }],
        "os": [{ "label": "Android", "clicks": 48 }],
        "countries": [{ "label": "Desconocido", "clicks": 87 }]
    }
}
```

| Campo             | Descripción                                                                                     |
| ----------------- | ----------------------------------------------------------------------------------------------- |
| `total_clicks`    | Clics desde que se creó el link, sin importar el período                                        |
| `period_clicks`   | Clics dentro del período                                                                        |
| `unique_visitors` | Visitantes distintos en el período. Se cuentan por un hash de la IP; la IP no se guarda         |
| `per_day`         | Un elemento por día del período, **incluidos los días sin clics**                               |
| `referers`        | De qué sitio vino el clic. `Directo` reúne los clics sin referer: apps, mails y enlaces pegados |
| `devices`         | `mobile`, `tablet` o `desktop`                                                                  |
| `browsers`, `os`  | Navegador y sistema operativo                                                                   |
| `countries`       | Código ISO de dos letras. Si el hosting no informa el país, sale `Desconocido`                  |

Cada lista de desglose trae como máximo 10 elementos, ordenados de más a menos clics.

---

## Errores

Los errores vuelven con un `message`, y los de validación agregan `errors` con el detalle de cada campo. Los mensajes están en español y se pueden mostrar tal cual al usuario.

| Código | Cuándo                                        | Ejemplo de `message`                    |
| ------ | --------------------------------------------- | --------------------------------------- |
| `401`  | Falta el token, o es inválido o fue revocado  | `Unauthenticated.`                      |
| `403`  | El token no es de una cuenta administradora   | `Esto es solo para el administrador.`   |
| `403`  | Al token le falta el permiso del endpoint     | `Invalid ability provided.`             |
| `404`  | El link no existe, no es tuyo o fue eliminado | `No tenés ningún link con ese código.`  |
| `422`  | Datos inválidos                               | `Ese alias ya está en uso. Elegí otro.` |
| `429`  | Pasaste el límite de pedidos                  | `Too Many Attempts.`                    |

Ejemplo de `422`:

```json
{
    "message": "Ese alias ya está en uso. Elegí otro.",
    "errors": {
        "alias": ["Ese alias ya está en uso. Elegí otro."]
    }
}
```

Los `422` más comunes:

| Campo        | Mensaje                                                               |
| ------------ | --------------------------------------------------------------------- |
| `url`        | `Pegá la URL que querés acortar.`                                     |
| `url`        | `Eso no parece una URL válida.`                                       |
| `url`        | `No se puede acortar un link de este mismo sitio.`                    |
| `url`        | `Ese link ya es de un acortador. Pegá la URL original.`               |
| `alias`      | `Ese alias ya está en uso. Elegí otro.`                               |
| `alias`      | `Ese alias está reservado. Elegí otro.`                               |
| `alias`      | `El alias solo puede tener letras, números, guiones y guiones bajos.` |
| `expires_at` | `La fecha de vencimiento tiene que ser futura.`                       |

---

## Límite de pedidos

Cada token puede hacer **60 pedidos por minuto**. Cada respuesta informa cuántos le quedan:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 57
```

Si pasás el límite, la API responde `429` con el header `Retry-After`, que indica cuántos segundos esperar.

---

## Reglas para acortar

**URL de destino**

- Solo se aceptan `http` y `https`. `ejemplo.com/algo` se toma como `https://ejemplo.com/algo`.
- No se puede acortar un link de `zep.com.ar`, porque haría un loop.
- No se aceptan links de otros acortadores, para evitar cadenas: `bit.ly`, `goo.gl`, `tinyurl.com`, `t.co`, `ow.ly`, `is.gd`, `buff.ly`, `cutt.ly`, `rebrand.ly`, `shorturl.at`, `rb.gy` y `tiny.cc`.

**Códigos aleatorios**

- Tienen 3 caracteres y se alargan solo cuando los cortos se van ocupando.
- No usan caracteres ambiguos (`0 O 1 l I`), así que se pueden dictar.

**Alias**

- De 3 a 50 caracteres: letras, números, `-` y `_`.
- Distinguen mayúsculas: `Promo` y `promo` son alias distintos.
- No pueden coincidir con palabras reservadas para rutas de la app, como `admin`, `api`, `login`, `dashboard`, `links` o `settings`. Esta comparación no distingue mayúsculas.
- No pueden ser un código que ya exista, ni uno que haya pertenecido a un link eliminado.

---

## Cómo se comportan los links cortos

| Situación del link               | Qué ve quien lo abre                               |
| -------------------------------- | -------------------------------------------------- |
| Activo y vigente                 | Redirección `302` al destino                       |
| Desactivado (`is_active: false`) | Página «Este enlace no está disponible», con `410` |
| Vencido                          | Página «Este enlace no está disponible», con `410` |
| Eliminado o inexistente          | `404`                                              |

- La redirección es `302` y no `301`. Así el navegador no la guarda, cada visita pasa por Zep y se cuenta, y si cambiás el destino con `PATCH` el cambio rige desde el clic siguiente.
- No cuentan como clics los bots ni las vistas previas que generan WhatsApp, Telegram, Slack, Facebook, X, LinkedIn, Discord y similares cuando alguien pega el link.

---

## Ejemplos de cliente

### Laravel

`.env` de la otra app:

```env
ZEP_TOKEN=12|xYz...
```

`config/services.php`:

```php
'zep' => [
    'url' => env('ZEP_URL', 'https://zep.com.ar/api/v1'),
    'token' => env('ZEP_TOKEN'),
],
```

Un servicio chico:

```php
namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Zep
{
    /**
     * Devuelve el link corto. Si la URL ya estaba acortada, devuelve el mismo.
     */
    public function shorten(string $url, ?string $alias = null, ?\DateTimeInterface $expiresAt = null): string
    {
        return $this->client()
            ->post('/links', array_filter([
                'url' => $url,
                'alias' => $alias,
                'expires_at' => $expiresAt?->format(DATE_ATOM),
            ]))
            ->throw()
            ->json('data.short_url');
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(string $code, ?string $from = null, ?string $to = null): array
    {
        return $this->client()
            ->get("/links/{$code}/stats", array_filter(compact('from', 'to')))
            ->throw()
            ->json('data');
    }

    public function deactivate(string $code): void
    {
        $this->client()->patch("/links/{$code}", ['is_active' => false])->throw();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(config('services.zep.url'))
            ->withToken(config('services.zep.token'))
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 500, throw: false);
    }
}
```

Para leer un `422` y mostrarle el error al usuario:

```php
use Illuminate\Http\Client\RequestException;

try {
    $short = app(Zep::class)->shorten($url, 'promo-2026');
} catch (RequestException $e) {
    if ($e->response->status() === 422) {
        $mensaje = $e->response->json('message'); // "Ese alias ya está en uso. Elegí otro."
    }
    throw $e;
}
```

### JavaScript (`fetch`)

**El token no tiene que llegar al navegador.** Este código va en un servidor (Node, una función serverless), nunca en el frontend.

```js
const res = await fetch('https://zep.com.ar/api/v1/links', {
    method: 'POST',
    headers: {
        Authorization: `Bearer ${process.env.ZEP_TOKEN}`,
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({ url: 'https://ejemplo.com/una/ruta/larga' }),
});

if (!res.ok) {
    const { message } = await res.json();
    throw new Error(`Zep ${res.status}: ${message}`);
}

const { data } = await res.json();
console.log(data.short_url);
```

### Python (`requests`)

```python
import os
import requests

res = requests.post(
    "https://zep.com.ar/api/v1/links",
    headers={
        "Authorization": f"Bearer {os.environ['ZEP_TOKEN']}",
        "Accept": "application/json",
    },
    json={"url": "https://ejemplo.com/una/ruta/larga"},
    timeout=10,
)
res.raise_for_status()
print(res.json()["data"]["short_url"])
```
