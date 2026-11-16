# SportZone – tienda online de material deportivo

Práctica de tienda online con **PHP + MongoDB Atlas + Bootstrap**, ejecutada con **Docker**.

## Cómo arrancarla
1. Abra **Docker Desktop**.
2. En una terminal dentro de la carpeta:
   ```
   docker compose up -d --build
   ```
3. En su navegador: http://localhost:8080

## Usuarios de prueba
| Rol | Email | Contraseña |
|---|---|---|
| Administrador | admin@sports.com | admin123 |
| Comprador | ares@correo.com | ares123 |

## Reglas de stock
- Al **añadir** al carrito se resta 1 de stock (queda reservado). Sin stock no se puede comprar.
- Si se quita una línea o se cancela el pedido (comprador o administrador) las unidades **vuelven al stock**.
- El comprador solo puede cancelar pedidos pendientes; el administrador también los aceptados.
