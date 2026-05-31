---
title: Configuración
date: 2026-05-31
author: LectorMD Team
tags: [configuración, proyectos, docker, .env]
image: img/libro03.png
image_position: center
image_wrapper: true
---

## Variables de entorno (`.env`)

```
WEB_PORT=9000            # Puerto del servidor web
DB_PORT=3307             # Puerto de MySQL (mapeado)
MYSQL_DATABASE=lectormd
MYSQL_ROOT_PASSWORD=test
CONTENT_DIR=./content    # Directorio raíz de contenido
```

## Proyectos (`config/projects.json`)

Cada proyecto se define con un objeto JSON:

```json
{
  "id": "main",
  "name": "Principal",
  "dir": "content/proyecto1",
  "exclude": ["img"],
  "token": null
}
```

| Campo     | Tipo           | Descripción                                  |
|-----------|----------------|----------------------------------------------|
| `id`      | `string`       | Identificador único (usado en URLs y cookies)|
| `name`    | `string`       | Nombre visible en el selector                |
| `dir`     | `string`       | Ruta relativa al contenido del proyecto      |
| `exclude` | `string[]`     | Directorios a ocultar del menú               |
| `token`   | `string|null`  | Token de acceso (null = público)             |

### Omisión de directorios

El array `exclude` oculta carpetas completas del menú lateral. Cada elemento puede ser un directorio individual o varios separados por coma:

```json
"exclude": ["img", "privado"]
"exclude": ["img, privado"]
```

### Proyectos protegidos

Si un proyecto tiene `token`, el servidor devuelve 403 hasta que el cliente envíe una cookie `token_{id}` con el valor correcto. El flujo desde el frontend:

1. El proyecto aparece como 🔒 en el selector
2. Al seleccionarlo, se muestra un diálogo de token
3. Si el token es válido, se guarda en cookie por 30 días
4. Todas las peticiones siguientes incluyen la cookie automáticamente

### Ejemplo completo

```json
[
  {
    "id": "secreto",
    "name": "Proyecto Secreto",
    "dir": "content/secreto1",
    "exclude": ["img"],
    "token": "secreto123"
  }
]
```

## Apache / `.htaccess`

El archivo `public/.htaccess` reescribe todas las rutas que no sean archivos existentes a `index.php`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

Esto permite:
- Archivos estáticos (`css/style.css`, `js/app.js`) se sirven directamente
- Rutas API (`/api/projects/...`) son manejadas por `index.php`
- URLs compartibles (`/main/archivo.md`) también caen en `index.php`, que sirve el HTML de la SPA

## Puerto personalizado

Para cambiar el puerto, edita `.env` y reinicia:

```
WEB_PORT=8080
```

Luego `docker compose down && docker compose up -d`.
