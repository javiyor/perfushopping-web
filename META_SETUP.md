# META sistemaPS — Guía de configuración (Perfushopping)

## 1. Qué configurar en Meta Developers (app `sistemaPS`)

1. Crear/usar la app `sistemaPS` en https://developers.facebook.com/apps.
2. Agregar el producto **WhatsApp** > API Setup:
   - Anotar **Phone number ID** (`WA_PHONE_NUMBER_ID`) y **WhatsApp Business Account ID** (`WA_WABA_ID`).
   - Generar token de acceso (idealmente **permanente / System User**) y guardarlo como `WA_ACCESS_TOKEN`.
3. Agregar el producto **Messenger**:
   - Crear/vincular la **página de Facebook**, generar el **Page Access Token** (`FB_PAGE_ACCESS_TOKEN`) y anotar el **Page ID** (`FB_PAGE_ID`).
4. Agregar el producto **Instagram** (según el login que use sistemaPS):
   - Con **Instagram Login**: vincular la cuenta profesional y generar `IG_ACCESS_TOKEN`; anotar `IG_ACCOUNT_ID` y poner `IG_LOGIN_TYPE=instagram`.
   - Con **Facebook Login**: respetar ese flujo y sus permisos; poner `IG_LOGIN_TYPE=facebook`.
5. Configurar el **webhook** de la app:
   - URL de devolución: `https://perfushopping.ar/webhooks/meta`
   - Token de verificación: el valor de `META_VERIFY_TOKEN` (inventar una cadena larga y guardarla en el `.env`).
   - Suscribir los campos `messages` (WhatsApp) y `messages`/`messaging_*` (Instagram/Messenger).
   - Verificar que la app esté **suscrita** a cada número/cuenta/página (WABA > Webhooks, página > suscripciones).
6. Completar el `.env` del servidor con las variables de `.env.example` (sección Meta sistemaPS). Nunca commitear el `.env`.

## 2. Variables (ver `.env.example`)

| Variable | Uso |
|---|---|
| `META_APP_ID`, `META_APP_SECRET` | Firma HMAC del webhook |
| `META_GRAPH_API_VERSION` | Versión Graph API (defecto `v25.0`) |
| `META_VERIFY_TOKEN` | Verificación del webhook |
| `WA_ACCESS_TOKEN`, `WA_PHONE_NUMBER_ID`, `WA_WABA_ID` | Envío WhatsApp Cloud API |
| `IG_LOGIN_TYPE`, `IG_ACCOUNT_ID`, `IG_ACCESS_TOKEN` | Paso 2 (Instagram) |
| `FB_PAGE_ID`, `FB_PAGE_ACCESS_TOKEN` | Paso 2 (Messenger) |

## 3. Verificación

1. En Meta Developers, "Verificar y guardar" el webhook → debe devolver el `hub.challenge` (el endpoint responde `forbidden` 403 si el token no coincide).
2. Enviar un WhatsApp al número de la empresa → debe aparecer en `/admin/mensajes` (tab Sin asignar).
3. Responder desde la bandeja → el contacto recibe el mensaje y el estado avanza Enviado → Entregado → Leído.
4. Fuera de la **ventana de 24 h** WhatsApp rechaza el texto libre (error 131047): en ese caso se necesita una **plantilla aprobada** (pendiente, no incluido en Paso 1).

## 4. Límites conocidos

- Sin credenciales reales sólo se puede validar con fixtures (ver `META_INTEGRATION_README.md`).
- Instagram/Messenger: sólo recepción por ahora; el envío llega en el Paso 2.
- Los tokens no se muestran nunca en pantallas ni logs.
