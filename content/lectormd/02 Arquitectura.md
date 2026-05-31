---
title: Arquitectura
date: 2026-05-31
author: LectorMD Team
tags: [arquitectura, desarrollo, tecnologías]
image: img/libro02.png
image_position: center
image_wrapper: true
---

## Stack tecnológico

| Componente   | Tecnología                        |
|-------------|-----------------------------------|
| Backend     | PHP 8.2 (sin frameworks externos) |
| Frontend    | JavaScript vanilla (SPA)          |
| Servidor    | Apache 2.4 + mod_rewrite          |
| Base de datos | MySQL 8.0 (no utilizada aún)    |
| Contenedor  | Docker + docker-compose           |

## Estructura del backend (PHP)

```
src/
├── ProjectManager.php    ← Carga config/projects.json y valida tokens
├── ContentManager.php    ← Escanea directorios y sirve archivos
├── MarkdownParser.php    ← Convierte markdown a HTML
└── Database.php          ← Conexión PDO a MySQL (singleton, opcional)
```

### ProjectManager

Lee `config/projects.json` y expone los proyectos. Cada proyecto puede tener un token opcional. Normaliza el array `exclude` partiendo por comas, así `["img, privado"]` equivale a `["img", "privado"]`. El método `getContentManager()` devuelve un `ContentManager` configurado con el directorio y exclusiones del proyecto.

### ContentManager

Escanea recursivamente el directorio del proyecto usando `RecursiveDirectoryIterator`. Filtra archivos según la lista de exclusión (`exclude`). Acepta cualquier extensión de archivo — los `.md` se pasan al `MarkdownParser`, el resto se sirven como texto plano escapado.

### MarkdownParser

Parser escrito a mano que soporta:

- Cabeceras (`#` a `######`)
- Negrita (`**texto**`) y cursiva (`*texto*`)
- Listas ordenadas y no ordenadas (anidadas)
- Citas (`>`)
- Bloques de código (con delimitadores ` ``` `)
- Código en línea (`` `codigo` ``)
- Enlaces `[texto](url)` e imágenes `![alt](url)`
- Frontmatter YAML (`title`, `date`, `tags`, `author`, `image`, `image_position`, `image_wrapper`, `div_class`)

## Frontend (SPA)

```
public/
├── index.html        ← Shell de la SPA
├── index.php         ← Router API + fallback a SPA
├── css/style.css     ← Estilos con variables CSS (temas)
└── js/app.js         ← Lógica completa del cliente
```

### app.js

Flujo de inicio:

1. Carga la lista de proyectos desde `/api/projects`
2. Parsea `location.pathname` para detectar URL compartible
3. Si hay URL, navega al proyecto/archivo indicado
4. Si no, carga el primer proyecto y su primer archivo

Eventos:
- `change` en `<select>` de proyectos → `switchProject()`
- `click` en archivo del menú → `loadFile()` + `pushState`
- `popstate` → navega según la URL del historial
- `click` en botón de descarga → reconstruye el `.md` y lo descarga como Blob

## Docker

```
docker-compose.yml
├── php-apache (puerto ${WEB_PORT:-9000})
│   ├── Dockerfile (php:8.2-apache)
│   ├── public/ montado como DocumentRoot
│   └── .htaccess con rewrite a index.php
└── mysql (puerto ${DB_PORT:-3307})
    └── imagen mysql:8.0
```

## Rutas estáticas

Los recursos estáticos (CSS, JS, imágenes) usan rutas **absolutas** con `/` inicial:

```html
<link rel="stylesheet" href="/css/style.css">
<script src="/js/app.js"></script>
```

Esto asegura que funcionen desde cualquier URL compartible (ej. `/lectormd/02%20Arquitectura.md`), evitando que el navegador las resuelva contra la ruta del documento.

## Flujo de petición

```
Navegador → /lectormd/02%20Arquitectura.md
  → Apache (mod_rewrite: ¿existe el archivo? No)
  → index.php (router)
      → ¿Coincide con /api/*? No
      → Sirve index.html (SPA)
  → JavaScript parsea location.pathname
      → projectId = "lectormd", filePath = "02 Arquitectura.md"
      → switchProject("lectormd")
          → GET /api/projects/lectormd/files (lista de archivos)
      → loadFile("lectormd", "02 Arquitectura.md")
          → encodeURIComponent → /api/projects/lectormd/files/02%20Arquitectura.md
          → GET (JSON con metadata + html)
      → renderiza el documento
```
