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
curl http://localhost:8080/api/projects
```

```json
[
  {
    "id": "lectormd",
    "name": "LectorMD",
    "locked": false
  },
  {
    "id": "secret",
    "name": "Proyecto Secret",
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
curl -X POST http://localhost:8080/api/projects/secret/unlock \
  -H 'Content-Type: application/json' \
  -d '{"token":"secret123"}'
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
curl http://localhost:8080/api/projects/lectormd/files
```

```json
{
  "files": [
    { "path": "01 Index.md", "name": "01 Index", "mtime": 1717000000 },
    { "path": "02 Arquitectura.md", "name": "02 Arquitectura", "mtime": 1717000000 },
    { "path": "Manual/manual-de-usuario.md", "name": "manual-de-usuario", "mtime": 1717000000 }
  ]
}
```

Los proyectos bloqueados requieren la cookie `token_{id}`:

```bash
curl --cookie "token_secret=secret123" \
  http://localhost:8080/api/projects/secret/files
```

## `GET /api/projects/{id}/files/{path}`

Obtiene el contenido de un archivo.

```bash
curl "http://localhost:8080/api/projects/lectormd/files/02%20Arquitectura.md"
```

```json
{
  "path": "02 Arquitectura.md",
  "name": "02 Arquitectura",
  "mtime": 1780196526,
  "metadata": {
    "title": "Arquitectura",
    "date": "2026-05-31",
    "tags": ["arquitectura", "desarrollo", "tecnologías"]
  },
  "body": "## Stack tecnológico\n\n| Componente ...",
  "html": "<h2>Stack tecnológico</h2>\n<p>...</p>"
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
