---
title: API REST
date: 2026-05-31
author: LectorMD Team
tags: [api, desarrollo, backend]
image: img/libro04.png
image_position: center
image_wrapper: true
---

Todas las rutas API devuelven JSON con `Content-Type: application/json; charset=utf-8`.

## `GET /api/projects`

Lista todos los proyectos disponibles.

```bash
curl http://localhost:9000/api/projects
```

```json
[
  {
    "id": "main",
    "name": "Principal",
    "locked": false
  },
  {
    "id": "secreto",
    "name": "Proyecto Secreto",
    "locked": true
  }
]
```

| Campo    | Tipo      | Descripción                    |
|----------|-----------|--------------------------------|
| `id`     | `string`  | Identificador del proyecto     |
| `name`   | `string`  | Nombre visible                 |
| `locked` | `boolean` | `true` si requiere token       |

## `POST /api/projects/{id}/unlock`

Valida un token para el proyecto.

```bash
curl -X POST http://localhost:9000/api/projects/secreto/unlock \
  -H 'Content-Type: application/json' \
  -d '{"token":"secreto123"}'
```

**Respuesta exitosa:**
```json
{ "ok": true }
```

**Respuesta fallida:**
```json
{ "ok": false, "error": "Token inválido" }
```

## `GET /api/projects/{id}/files`

Lista los archivos de un proyecto.

```bash
curl http://localhost:9000/api/projects/main/files
```

```json
{
  "files": [
    { "path": "bienvenida.md", "name": "bienvenida", "mtime": 1717000000 },
    { "path": "subcarpeta/otro-doc.md", "name": "otro-doc", "mtime": 1717000000 }
  ]
}
```

Los proyectos bloqueados requieren la cookie `token_{id}`:

```bash
curl --cookie "token_secreto=secreto123" \
  http://localhost:9000/api/projects/secreto/files
```

## `GET /api/projects/{id}/files/{path}`

Obtiene el contenido de un archivo.

```bash
curl http://localhost:9000/api/projects/main/files/bienvenida.md
```

```json
{
  "path": "bienvenida.md",
  "name": "bienvenida",
  "mtime": 1717000000,
  "metadata": {
    "title": "Bienvenida",
    "date": "2026-05-30",
    "tags": ["inicio"]
  },
  "body": "# Bienvenida\n\nContenido...",
  "html": "<h1>Bienvenida</h1>\n<p>Contenido...</p>"
}
```

| Campo      | Tipo     | Descripción                                 |
|------------|----------|---------------------------------------------|
| `path`     | `string` | Ruta relativa del archivo                   |
| `name`     | `string` | Nombre sin extensión                        |
| `mtime`    | `number` | Timestamp Unix de modificación              |
| `metadata` | `object` | Frontmatter YAML parseado                   |
| `body`     | `string` | Cuerpo del documento (sin frontmatter)      |
| `html`     | `string` | HTML renderizado (markdown o texto plano)   |

> **Nota sobre UTF-8:** Las rutas con espacios o caracteres UTF-8 deben codificarse por segmento. El backend usa `rawurldecode()` en el parámetro `{path}`, por lo que `%20` se decodifica correctamente. El frontend aplica `encodeURIComponent` a cada segmento individualmente.

## Códigos de error

| Código | Significado                     |
|--------|---------------------------------|
| `403`  | Acceso denegado / token inválido |
| `404`  | Archivo no encontrado           |
