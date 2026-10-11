# Integración Meta sistemaPS — README técnico

Bandeja omnicanal (WhatsApp + Instagram + Messenger) integrada al admin existente. No se creó proyecto nuevo.

## Arquitectura (Paso 0 + 1, 11/10/2026)

- `public/index.php` — rutas `GET/POST /webhooks/meta`, `/admin/mensajes*`.
- `src/Controller/MetaWebhookController.php` — verificación (`hub.verify_token`), firma HMAC SHA-256, deduplicación, ingesta de entrantes WA/IG/FB + normalización de `statuses` de WhatsApp (`normalizeStatuses()`).
- `src/Service/Meta/` — `MetaChannelInterface`, `MetaHttp` (cURL POST JSON, sin log de secretos), `WhatsAppChannel` (Cloud API `POST /{v}/{phone-id}/messages`, texto hasta 4096 caracteres).
- `src/Repo/SocialInboxRepo.php` — tablas `social_*` autocreadas + columnas `send_status`, `error_code`, `sent_at` en `social_messages`; métodos `applyStatus()`, `insertOutbound()`, `touchAfterOutbound()`.
- `src/Admin/SocialInboxController.php` — acción `responder` (sólo WhatsApp, exige conversación abierta y canal configurado).
- `templates/admin/mensajes/inbox.php` — caja "Responder por WhatsApp" + badges de estado por mensaje saliente.

## Decisiones anti-duplicación

- Se **extienden** las tablas `social_*` existentes en lugar de crear el esquema `meta_*` propuesto en el plan.
- Se **extiende** la bandeja `/admin/mensajes` en lugar de crear otra.
- `src/Repo/MetaRepo.php` no es de Meta (es catálogo: rubros/marcas/provincias); no tocar.

## Flujos

- Entrante: webhook → `ingestInbound()` (crea contacto/conversación, suma `unread_count`; si estaba cerrada, reabre como `nuevo`).
- Estado WA: webhook `statuses` → `applyStatus()` por `meta_message_id` (`sent/delivered/read/failed` + `error_code`).
- Saliente: bandeja → `WhatsAppChannel::sendText()` → se guarda `out` con `wamid` (`sent`) o `failed` + error; ante éxito se actualiza preview, `unread_count=0` y `first_response_at`.

## Pruebas ejecutadas (11/10/2026, fixtures, sin credenciales)

Script temporal (eliminado tras correr): 11/11 OK.
- `buildTextPayload()`: forma `messaging_product/to/type/text.body`.
- `parseSendResponse()`: éxito 200 con `wamid`, error 400 código 131047 (ventana 24 h), error de red.
- `normalizeStatuses()`: `delivered/read/failed` (con `errors[0].code`), descarta estados desconocidos y vacíos.
- `php -l` en los 9 archivos tocados/creados: sin errores.

## Pendiente de verificación con credenciales reales

- Verificación del webhook en Meta Developers, recepción real por canal, envío real y avance de estados, ventana 24 h/plantillas.
- **Paso 2**: `InstagramChannel` + `MessengerChannel` (misma interfaz) y reply en bandeja para esos canales.
- **Pasos 3-4**: CRM (vincular `clientes.idclien`, estados comerciales, panel lateral), respuestas rápidas, panel Configuración > Meta, filtros/polling.
