# LectorMD

**Visualizador de documentación markdown** — sirve carpetas de archivos `.md` como un sitio web navegable con múltiples proyectos, temas claro/oscuro, y control de acceso por token.

## Características

- **Múltiples proyectos** — organiza tu documentación en proyectos independientes
- **Cualquier extensión** — `.md` se renderiza con formato; el resto se muestra como texto plano
- **Frontmatter YAML** — `title`, `date`, `tags`, `author`, `image` y más
- **Tema oscuro/claro** — alterna con persistencia en `localStorage`
- **Control de acceso** — protege proyectos con token vía cookie (30 días)
- **URLs compartibles** — cada archivo tiene su URL única (`/idproyecto/ruta/archivo`)
- **Menú colapsable** — panel lateral con búsqueda y agrupación por subcarpetas
- **Descarga de `.md`** — botón para descargar el markdown original
- **Docker** — entorno reproducible con PHP 8.2 + Apache + MySQL

## Inicio rápido

```bash
docker compose up -d
# Abre http://localhost:9000
```

Personaliza el puerto en `.env`:

```
WEB_PORT=9000
DB_PORT=3307
```

## Configuración de proyectos

Los proyectos se definen en `config/projects.json`:

```json
[
  {
    "id": "main",
    "name": "Principal",
    "dir": "content/proyecto1",
    "exclude": ["img"]
  },
  {
    "id": "secreto",
    "name": "Proyecto Secreto",
    "dir": "content/secreto1",
    "exclude": ["img"],
    "token": "secreto123"
  }
]
```

| Campo     | Descripción                                                |
|-----------|------------------------------------------------------------|
| `id`      | Identificador único (usado en URLs y cookies)              |
| `name`    | Nombre visible en el selector                              |
| `dir`     | Ruta al directorio del contenido                           |
| `exclude` | Carpetas a ocultar del menú (ej. `["img", "privado"]`)     |
| `token`   | Si se define, el proyecto requiere este token para acceder |

## Estructura del proyecto

```
lectormd/
├── config/projects.json    ← Definición de proyectos
├── content/                ← Contenido markdown por proyecto
│   ├── proyecto1/          ← Proyecto "Principal"
│   ├── proyecto2/          ← Proyecto "Secundario"
│   ├── secreto1/           ← Proyecto con token
│   └── lectormd/           ← Documentación del propio LectorMD
├── docker/php/             ← Dockerfile + Apache config + php.ini
├── docs/                   ← Documentación del proyecto
├── public/
│   ├── index.php           ← Router API
│   ├── index.html          ← SPA shell
│   ├── css/style.css       ← Estilos con variables CSS
│   └── js/app.js           ← Lógica del frontend
└── src/
    ├── ProjectManager.php  ← Carga proyectos y valida tokens
    ├── ContentManager.php  ← Escanea directorios y sirve archivos
    ├── MarkdownParser.php  ← Convierte markdown a HTML
    └── Database.php        ← Conexión PDO a MySQL (opcional)
```

## API REST

| Método | Ruta                                      | Descripción                      |
|--------|-------------------------------------------|----------------------------------|
| GET    | `/api/projects`                           | Lista proyectos                  |
| POST   | `/api/projects/{id}/unlock`               | Valida token                     |
| GET    | `/api/projects/{id}/files`                | Lista archivos de un proyecto    |
| GET    | `/api/projects/{id}/files/{ruta}`         | Obtiene contenido de un archivo  |

## Tecnologías

- **Backend**: PHP 8.2 (sin frameworks externos)
- **Frontend**: JavaScript vanilla (SPA), CSS con variables
- **Servidor**: Apache 2.4 + mod_rewrite
- **Base de datos**: MySQL 8.0 vía PDO (opcional)
- **Infra**: Docker + Docker Compose

## Documentación

El proyecto incluye documentación completa accesible desde la propia aplicación en el proyecto **LectorMD**:

| Documento               | Contenido                                    |
|-------------------------|----------------------------------------------|
| `index.md`              | Visión general, características, inicio rápido |
| `arquitectura.md`       | Stack, backend, frontend, Docker, flujo      |
| `configuracion.md`      | `.env`, `projects.json`, `.htaccess`         |
| `api.md`                | Referencia completa de endpoints REST        |
| `manual-de-usuario.md`  | Guía de uso paso a paso                      |

Accede desde: `http://localhost:9000/lectormd`

## Licencia

MIT
