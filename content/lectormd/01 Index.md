---
title: LectorMD
date: 2026-05-31
author: LectorMD Team
tags: [inicio, documentación, bienvenida]
image: img/libro01.png
image_position: center
image_wrapper: true
---

LectorMD es un lector y visualizador de documentación basado en markdown. Convierte archivos `.md` en un sitio web navegable con soporte para múltiples proyectos, temas claro/oscuro, y control de acceso por token.

## Características

- **Múltiples proyectos**: organiza tu documentación en proyectos independientes
- **Archivos markdown**: renderizado completo con cabeceras, listas, tablas, bloques de código, citas e imágenes
- **Cualquier extensión**: archivos no-`.md` se muestran como texto plano
- **Tema oscuro/claro**: alterna entre ambos con persistencia en `localStorage`
- **Control de acceso**: protege proyectos con token vía cookie
- **URLs compartibles**: cada archivo tiene su propia URL (`/idproyecto/ruta/archivo`)
- **Menú colapsable**: panel lateral con navegación por proyectos y archivos
- **Búsqueda**: filtrado en tiempo real de archivos dentro del proyecto activo
- **Descarga**: botón para descargar el `.md` original de cada documento
- **Docker**: entorno reproducible con PHP 8.2 + Apache + MySQL

## Inicio rápido

```
docker compose up -d
```

Luego abre [http://localhost:9000](http://localhost:9000).

## Estructura del proyecto

```
lectormd/
├── config/           ← Configuración de proyectos
├── content/          ← Contenido markdown (proyectos)
├── docker/           ← Dockerfile de PHP
├── docs/             ← Documentación del proyecto
├── public/           ← Web root (index.html, JS, CSS)
├── src/              ← PHP backend
├── .env              ← Variables de entorno
└── docker-compose.yml
```

## Proyectos incluidos

| Proyecto   | ID        | Ruta                    | Protección |
|------------|-----------|-------------------------|------------|
| Principal  | `main`    | `content/proyecto1/`    | —          |
| Secundario | `extra`   | `content/proyecto2/`    | —          |
| Secreto    | `secreto` | `content/secreto1/`     | 🔒 Token   |
| LectorMD   | `lectormd`| `content/lectormd/`     | —          |
