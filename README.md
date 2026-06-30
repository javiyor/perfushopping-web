# Perfushopping Web

PHP 8.4 + MySQL. Catalogo + carrito + checkout minorista (Mercado Pago) y mayorista (transferencia).

## Config

- Copiar `web/.env.example` a `web/.env` y completar DB/SMTP/MP.
- En Hostinger: document root apunta a `web/public`.

## Base de datos

- Ejecutar `web/db/schema_web.sql` (tablas nuevas de la web).
- Ejecutar `web/db/patches.sql` (idzona en provincias + Patagonia + mapeo).
- Cargar `envios` (tarifas de Correo Argentino por zona, idtransporte=6).
- (Opcional) Ejecutar `web/db/seed_local_delivery.sql` para whitelist/valores de delivery local.

## Correr local

Desde `web/`:

```bash
php -S localhost:8000 -t public public/index.php
```

## Endpoints clave

- Webhook Mercado Pago: `/mp/webhook` (configurar en MP como `https://perfushopping.ar/mp/webhook`)
- Activacion usuario: `/activate?token=...`

## VFP Sync API

Endpoint: `POST /api/v1/sync`

- Auth: header `Authorization: Bearer <SYNC_TOKEN>` o `X-Sync-Token: <SYNC_TOKEN>`
- Body JSON:

```json
{
  "products": [{"idprodu": 123, "produ": "...", "precio": 1000, "precio1": 900, "imagen": "foto.jpg", "enweb": 1}],
  "gustos": [{"idcodgusto": 10, "idprodu": 123, "nomgusto": "AZUL", "stockact": 5, "discont": 0}],
  "images": [{"idimagen": 1, "rutaimg": "foto_variante.jpg", "idprodu": 123, "idcodgusto": 10}],
  "stock_resumen": [{"idcodgusto": 10, "stock_real": 4.00}]
}
```

Notas:
- `producto.imagen` y `imagen.rutaimg` guardan solo nombre de archivo (la web resuelve `/upload/<nombre>`)
- Si `images[].idimagen` se omite o es `0`, el server genera `MAX(idimagen)+1`.

## VFP Upload API

Endpoint: `POST /api/v1/upload`

- Auth: header `Authorization: Bearer <SYNC_TOKEN>` o `X-Sync-Token: <SYNC_TOKEN>`
- Body JSON:

```json
{
  "filename": "armani-code-men.jpg",
  "content_base64": "<BASE64>"
}
```

Guarda el archivo en `public_html/upload/`.

## VFP curl (multipart) compatible

Si desde VFP ya usas `curl -F`, podes usar:

- URL: `https://perfushopping.ar/upload/subir_imagen.php`
- Fields:
  - `token` (UPLOAD_TOKEN si existe, sino SYNC_TOKEN)
  - `imagen=@C:\ruta\archivo.jpg`
  - `nombre` (opcional) para forzar nombre destino

### Imagenes de gustos (tabla `imagen`)

El mismo endpoint acepta (opcional) registrar la imagen en la tabla `imagen`:

- `idprodu` (int)
- `idcodgusto` (int)
- `idimagen` (opcional): si se envia, reemplaza esa fila. Si no, crea una nueva (max 6 por gusto).

Ejemplo curl:

```bash
curl -X POST "https://perfushopping.ar/upload/subir_imagen.php" \
  -F "token=..." \
  -F "idprodu=123" \
  -F "idcodgusto=10" \
  -F "nombre=valeria-velvet-60-1.jpg" \
  -F "imagen=@C:\\fotos\\valeria velvet 60 1.jpg"
```
