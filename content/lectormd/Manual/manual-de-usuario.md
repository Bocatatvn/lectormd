---
title: Manual de Usuario
date: 2026-05-31
author: LectorMD Team
tags: [manual, usuario, guía]
image: img/libro05.png
image_position: center
image_wrapper: true
---

## Primeros pasos

Accede a LectorMD desde el navegador en `http://localhost:8080`. Verás el panel lateral con el menú de navegación.

![Vista general](img/libro01.png)

## Interfaz

### Panel lateral (menú)

El menú contiene:

- **Selector de proyectos**: desplegable en la parte superior para cambiar entre proyectos
- **Buscador**: campo de texto para filtrar archivos en tiempo real
- **Lista de archivos**: archivos del proyecto activo. Las subcarpetas aparecen como cabeceras no cliqueables
- **Estado**: indicador de conexión en la parte inferior
- **Tema**: botón para alternar entre modo oscuro y claro

### Botón de menú (hamburguesa)

El botón con tres líneas en la esquina superior izquierda abre y cierra el panel lateral. El menú permanece abierto por defecto y solo se cierra al hacer clic en el botón.

## Navegación

### Seleccionar proyecto

Usa el desplegable en la parte superior del menú. Los proyectos protegidos aparecen con un candado 🔒.

![Selector de proyectos](img/libro02.png)

### Abrir un archivo

Haz clic en cualquier archivo de la lista. Los archivos `.md` se renderizan con formato completo. Los archivos con otras extensiones se muestran como texto plano.

### Subcarpetas

Los archivos dentro de subcarpetas se agrupan bajo una cabecera con el nombre de la carpeta. Las cabeceras no son cliqueables.

### Búsqueda

Escribe en el campo de búsqueda para filtrar archivos por nombre o ruta. El filtro se aplica en tiempo real.

## URLs compartibles

Cada archivo tiene una URL única que puedes compartir:

```
http://localhost:8080/lectormd/02%20Arquitectura.md
http://localhost:8080/lectormd/01%20Index.md
http://localhost:8080/secret/index.md
```

Los espacios en los nombres de archivo se codifican automáticamente como `%20`. Al abrir una URL compartible, el proyecto y archivo se cargan automáticamente.

## Proyectos protegidos

Cuando seleccionas un proyecto protegido:

1. Aparece un diálogo pidiendo el token
2. Introduce el token y haz clic en "Acceder"
3. Si es correcto, se guarda en una cookie por 30 días
4. El proyecto se desbloquea y puedes navegar normalmente

![Diálogo de token](img/libro03.png)

Si el token es incorrecto, verás un mensaje de error. Puedes cancelar el diálogo para volver al proyecto anterior.

## Tema oscuro / claro

Haz clic en el botón 🌙/☀️ en la esquina inferior derecha del menú para alternar entre modo oscuro y claro. La preferencia se guarda en `localStorage` y persiste entre sesiones.

## Descarga de archivos

Cada documento `.md` tiene un botón "⬇ Descargar" en la parte superior del visor. Al hacer clic, se descarga el archivo markdown original reconstruido.

![Botón de descarga](img/libro04.png)

## Atajos y comportamiento

- **Escape** en el diálogo de token: cancela
- **Enter** en el diálogo de token: envía
- **Navegación del historial**: los botones atrás/adelante del navegador funcionan con las URLs compartibles
- **El menú no se cierra** al seleccionar un archivo — solo se cierra explícitamente con el botón de hamburguesa

## Imágenes en documentos

Puedes incluir imágenes en tus documentos usando la sintaxis markdown estándar:

```markdown
![Texto alternativo](img/mi-imagen.png)
```

También puedes usar frontmatter para imágenes destacadas:

```yaml
---
title: Mi Documento
image: img/destacada.png
image_position: left
image_wrapper: true
---
```

`image_position` puede ser `left`, `right` o `center`. `image_wrapper` envuelve la imagen en un contenedor con clase `content-image`.

## Enlaces

Puedes enlazar a otros archivos del proyecto de forma relativa:

```markdown
[Ver arquitectura](02%20Arquitectura.md)
[Manual de usuario](Manual/manual-de-usuario.md)
```

O usar URLs absolutas externas:

```markdown
[Ejemplo de imagen externa](https://ejemplo.com/imagen.png)
```

## Consejos

- **Organiza tus proyectos con subcarpetas** para mantener el menú limpio
- **Usa frontmatter** para darle título, fecha y etiquetas a tus documentos
- **Las carpetas `img/` se excluyen automáticamente** del menú, pero las imágenes son accesibles por su ruta
- **Puedes compartir cualquier URL** y al abrirla se cargará el proyecto y archivo indicado
