# Diseño propuesto: conservación de conversaciones de sistemaPS por 12 meses

**Estado:** diseño sujeto a validación legal y técnica. No hay migración aplicada, tarea CLI implementada ni cron habilitado. Este documento no autoriza depuración ni eliminación de datos productivos.

## 1. Alcance de la decisión

La propuesta elegida por Perfushopping es conservar las conversaciones comerciales de sistemaPS hasta 12 meses desde la última interacción del cliente, para WhatsApp Business, Instagram Direct y Facebook Messenger. El criterio aún requiere validación legal/técnica y un proceso operativo efectivo.

El plazo es sólo para el historial de conversación comercial. No se aplica automáticamente a facturas, comprobantes, pedidos, contabilidad, reclamos, casos abiertos ni a datos de clientes necesarios para una relación comercial vigente. Cada excepción debe quedar documentada y revisarse; al terminar, retirar el hold para que el caso vuelva a ser elegible.

## 2. Hallazgos del esquema y del flujo actual

Las definiciones están duplicadas en `db/patches_social_inbox.sql` y en el método privado `SocialInboxRepo::ensureTables()`:

- `social_contacts`: canal, ID externo, nombre, teléfono y `web_user_id` opcional. No hay clave foránea a conversaciones. El flujo `upsertContact()` no asigna `web_user_id`, aunque puede haber filas creadas o relacionadas por otros procesos.
- `social_conversations`: estado, asignación, notas internas, `last_message_at`, preview, fechas de cierre/actualización y contacto. No tiene fecha de última interacción del cliente, vínculo con pedido/reclamo ni marca de hold de retención.
- `social_messages`: dirección (`in`/`out`), tipo, ID de Meta, cuerpo, referencias de adjuntos en JSON, payload original, `provider_created_at` y `created_at`. No hay clave foránea a conversación.
- `social_conversation_notes`: notas libres, fecha y administrador; no hay clave foránea ni registro general de cambios de estado.
- `social_webhook_events`: almacena el cuerpo JSON completo del webhook y hora recibida, deduplicado por SHA-256 del cuerpo. No contiene el ID de conversación/mensaje y no tiene rutina de caducidad.

### Cómo calcular la última interacción

**Ancla propuesta:** el máximo timestamp de mensajes aceptados con `direction = 'in'`, porque representan una comunicación recibida del cliente. Los mensajes salientes del negocio, cambios de estado, asignaciones, notas, lectura, cierre/reapertura manual y duplicados de webhook no reinician el plazo. Si el responsable decide que mensajes salientes cuenten como “interacción”, debe cambiarse la regla antes de implementar.

`social_conversations.last_message_at` no es una fuente correcta: se actualiza con `NOW()` tanto al guardar mensajes entrantes como luego de una respuesta saliente. No refleja necesariamente el momento del proveedor ni distingue quién habló. `updated_at` también cambia por tomar/liberar/cerrar/reabrir/leer/agregar notas y no equivale a una interacción del cliente.

`social_messages.provider_created_at` es la mejor fuente para mensajes entrantes cuando existe. El webhook lo forma con `gmdate()` (UTC); `created_at` usa `NOW()` con la sesión MySQL fijada en `-03:00` por `Db::pdo()`. Son columnas `DATETIME`, que no convierten zona horaria por sí mismas: **compararlas directamente mezcla UTC y UTC-03:00**. Para un evento tardío, es más seguro iniciar el período desde el momento en que sistemaPS lo recibió, no retroceder el plazo; registrar ambas horas normalizadas UTC en una columna nueva y usar la mayor (`max(timestamp del proveedor, recepción UTC)`). Eventos sin timestamp válido usan la recepción UTC.

Otros hallazgos que afectan la selección:

1. `ingestInbound()` reabre una conversación cerrada antes de comprobar si el `meta_message_id` ya fue procesado. Un webhook duplicado puede cambiar `status/closed_at` sin representar nueva actividad del cliente; el deduplicado debe ocurrir antes del cambio de estado.
2. Un mensaje entrante reabre actualmente la última conversación del contacto, limpia `closed_at` y luego guarda el mensaje. Un mensaje nuevo sí debe renovar la fecha efectiva; la reapertura manual de `SocialInboxController::reopen()` no debe hacerlo.
3. El envío desde la bandeja sólo está implementado para WhatsApp. Instagram/Messenger aparecen como canales entrantes en este código; la actividad de salida de esos canales podría depender de Meta u otra herramienta no revisada.
4. `attachments_json` y `raw_json` conservan referencias/datos del adjunto dentro del mensaje. No se encontró un almacenamiento local binario asociado en esta bandeja. Al purgar el mensaje también hay que purgar esos campos; ello no elimina archivos retenidos directamente por Meta.
5. `internal_note` duplica la última nota y `social_conversation_notes.note` puede contener texto personal libre. Ambos deben eliminarse con el historial, salvo hold documentado.
6. No existe relación estructural entre conversación y pedido/factura/reclamo, ni se encontró una tabla formal de reclamos. El estado “cerrado” no prueba por sí solo que no haya una obligación o solicitud pendiente.
7. El payload completo en `social_webhook_events.payload` puede duplicar mensajes, adjuntos y metadatos sin vínculo con su conversación. Es una contradicción que impide garantizar que la purga de `social_messages` elimine todas las copias de sistemaPS.
8. No se encontró una tabla de auditoría de retención/cambios de conversación ni configuración de backups. Las notas y webhooks no sustituyen una auditoría ni revelan el ciclo real de copias.

## 3. Elegibilidad segura

Un barrido sólo debería incluir una conversación si, simultáneamente:

- Tiene `status = 'cerrado'`, `closed_at IS NOT NULL`, no está asignada y `unread_count = 0`.
- Tiene `last_customer_interaction_at_utc` conocido y éste es anterior al corte UTC de 12 meses. Una fecha nula o timestamp heredado ambiguo **excluye** la fila hasta una revisión.
- No tiene hold abierto de pedido, relación comercial vigente, reclamo, solicitud de derechos u otra causa legal/contractual documentada.
- No tiene una solicitud de privacidad pendiente ni una situación de litigio/retención legal comunicada.
- Ya se verificó cómo tratar su contacto y todos los registros webhook duplicados correspondientes.

Si un caso se reabre, se vincula a un reclamo/pedido en seguimiento o llega un nuevo mensaje entrante, deja de ser elegible. La reanudación y el cierre posterior no sustituyen la fecha real del nuevo mensaje. Todo estado/hold que bloquee o libere una purga debe tener actor, momento y motivo en auditoría.

### Consulta de simulación ilustrativa

**No ejecutar tal cual en producción:** requiere las migraciones descritas abajo y una revisión de los nombres/estados de excepción. El modo simulación reportaría conteos por motivo; no ejecutaría `DELETE`.

```sql
SELECT c.id, c.channel, c.status, c.closed_at,
       c.last_customer_interaction_at_utc
FROM social_conversations c
WHERE c.status = 'cerrado'
  AND c.closed_at IS NOT NULL
  AND c.assigned_admin_id IS NULL
  AND c.unread_count = 0
  AND c.last_customer_interaction_at_utc IS NOT NULL
  AND c.last_customer_interaction_at_utc < UTC_TIMESTAMP() - INTERVAL 12 MONTH
  AND NOT EXISTS (
      SELECT 1
      FROM social_conversation_retention_holds h
      WHERE h.conversation_id = c.id AND h.released_at_utc IS NULL
  );
```

La consulta no puede excluir reclamos/pedidos pendientes hasta que se defina el proceso de registro de holds y se complete el vínculo operativo. El dry-run debe listar el conteo elegible y los excluidos por estado, fecha desconocida, no leído/asignado, hold y datos sin relación webhook resuelta; las salidas no deben incluir cuerpos, teléfonos, nombres ni payloads.

## 4. Migraciones y tareas necesarias (propuestas, no aplicadas)

Mantener sincronizadas las tablas de `patches_social_inbox.sql` y `SocialInboxRepo::ensureTables()`, o reemplazar el DDL dinámico por migraciones versionadas en una tarea independiente. En cualquier caso, probar primero en copia no productiva y verificar versión/compatibilidad exacta de MySQL.

El siguiente DDL es **boceto para revisión, no migración ejecutable**. Antes de redactar una migración final hay que confirmar tipos/versiones y decidir el plazo independiente de las bitácoras:

```sql
ALTER TABLE social_conversations
  ADD COLUMN last_customer_interaction_at_utc DATETIME NULL,
  ADD KEY idx_social_retention_eligible
    (status, last_customer_interaction_at_utc, closed_at);

ALTER TABLE social_messages
  ADD COLUMN customer_interaction_at_utc DATETIME NULL;

CREATE TABLE social_conversation_retention_holds (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  hold_type VARCHAR(32) NOT NULL,
  reference_type VARCHAR(32) DEFAULT NULL,
  reference_id VARCHAR(120) DEFAULT NULL,
  reason_code VARCHAR(64) NOT NULL,
  opened_by INT UNSIGNED NOT NULL,
  opened_at_utc DATETIME NOT NULL,
  review_at_utc DATETIME DEFAULT NULL,
  released_by INT UNSIGNED DEFAULT NULL,
  released_at_utc DATETIME DEFAULT NULL,
  KEY idx_social_hold_conversation (conversation_id, released_at_utc),
  KEY idx_social_hold_review (review_at_utc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE social_retention_runs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  run_uuid CHAR(36) NOT NULL UNIQUE,
  mode VARCHAR(16) NOT NULL,
  criteria_version VARCHAR(32) NOT NULL,
  cutoff_utc DATETIME NOT NULL,
  started_at_utc DATETIME NOT NULL,
  finished_at_utc DATETIME DEFAULT NULL,
  candidate_count INT UNSIGNED NOT NULL DEFAULT 0,
  excluded_count INT UNSIGNED NOT NULL DEFAULT 0,
  deleted_count INT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(24) NOT NULL,
  actor VARCHAR(120) NOT NULL,
  code_version VARCHAR(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE social_retention_run_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  run_id BIGINT UNSIGNED NOT NULL,
  conversation_id INT UNSIGNED DEFAULT NULL,
  outcome VARCHAR(24) NOT NULL,
  reason_code VARCHAR(64) NOT NULL,
  messages_count INT UNSIGNED NOT NULL DEFAULT 0,
  notes_count INT UNSIGNED NOT NULL DEFAULT 0,
  affected_contact TINYINT(1) NOT NULL DEFAULT 0,
  acted_at_utc DATETIME NOT NULL,
  KEY idx_social_run_item_run (run_id),
  KEY idx_social_run_item_conversation (conversation_id),
  CONSTRAINT fk_social_run_item_run FOREIGN KEY (run_id)
    REFERENCES social_retention_runs(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`social_conversation_status_history` (conversation ID, estado anterior/nuevo, acción, actor, UTC y código de motivo) es otra migración necesaria para acreditar cierres/reaperturas y holds. No debe tener `ON DELETE CASCADE`; definir con legal un plazo de auditoría aparte y evaluar seudonimizar el ID de conversación al borrar el contenido.

1. **Timestamp normalizado y ancla:** añadir `social_messages.customer_interaction_at_utc DATETIME NULL` y `social_conversations.last_customer_interaction_at_utc DATETIME NULL` con índice para elegibilidad. Al ingerir una entrada, guardar en UTC el máximo entre el timestamp UTC válido del proveedor y la recepción UTC, y actualizar la conversación con `GREATEST()` para tolerar eventos fuera de orden. No actualizarlo en mensajes salientes, notas, cierres, lecturas ni reaperturas manuales.
2. **Backfill en lotes:** calcular el máximo de mensajes entrantes. Los `provider_created_at` conocidos son UTC por `gmdate()`; como `created_at` se generó bajo sesión `-03:00`, normalizar su fallback con +3 horas UTC. Validar esto en staging y contra el histórico real antes de usarlo. Las filas sin base temporal clara quedan sin fecha y excluidas; producir conteos de dry-run antes/después, sin borrar.
3. **Deduplicación y reapertura:** mover la comprobación de `meta_message_id` antes de la reapertura; hacer la ingestión idempotente y atómica para que una repetición no abra casos ni altere el ancla. Probar errores parciales y reintentos de Meta.
4. **Holds de conservación:** tabla `social_conversation_retention_holds` sin cascada destructiva, con conversation ID, tipo/código de motivo, referencia mínima a pedido/reclamo/caso externo, fecha de apertura/revisión/liberación y actor. No copiar información fiscal ni texto sensible en el motivo. Hace falta interfaz/procedimiento para crear, revisar y liberar holds. Si no existe una referencia verificable del estado del caso, se conserva la conversación.
5. **Webhook raw y dedupe:** rediseñar el registro de eventos para procesar el payload dentro de una transacción e idempotencia y no retener el JSON personal completo una vez procesado; conservar temporalmente sólo la clave de dedupe/resultado necesarios. Los eventos ya almacenados no se pueden asociar de forma confiable a una conversación desde el esquema actual. Diseñar una auditoría/mapeo de mensajes en staging y un plan de revisión antes de migrar esos payloads. Definir con Meta el horizonte real de reintentos antes de fijar caducidad para hashes de dedupe.
6. **Auditoría de estado y depuración:** registrar los cambios de estado/reapertura/hold y cada ejecución. La bitácora no debe guardar cuerpo, adjuntos ni payload; limitar acceso y definir su propio plazo legal de conservación. Para correlación mínima puede guardar conversation ID pseudónimo, actor, UTC, código de motivo, versión de criterio y conteos afectados; ID pseudónimo sigue pudiendo ser dato personal y requiere retención separada.
7. **Purga consistente:** bajo transacción y bloqueo de ejecución única, revalidar elegibilidad justo antes de actuar. Eliminar en este orden lógico y sólo para la conversación elegible: mensajes (incluidos `raw_json`/`attachments_json`), notas, `internal_note`, preview/identificadores directos de la conversación, y luego la conversación; eliminar `social_contacts` sólo si no quedan conversaciones, no hay hold, ni `web_user_id` o relación de cliente vigente. Mantener intactos `orders`, facturas, comprobantes, tablas contables y evidencia exigida. La implementación debe ser batch, transaccional por conversación y reanudable.
8. **Backups:** determinar proveedor, ventana, cifrado y expiración efectiva. La purga del vivo no elimina datos en snapshots/backups; las copias deben expirar según el ciclo real, no reintroducir datos borrados al restaurar (replay de tombstones/lista de purga, si corresponde), y quedar fuera de la promesa de 12 meses hasta validar la solución.

## 5. Interfaz CLI propuesta

`php bin/social-retention.php --dry-run` sería el único modo disponible durante validación. Debe comprobar que se ejecuta desde CLI, impedir acceso web, usar solo consultas de lectura, tomar lock de proceso, imprimir `as_of_utc`, `cutoff_utc`, versión del criterio, conteo elegible y motivos de exclusión, y escribir un resumen sin PII en la bitácora.

El modo `--apply` no se implementará/habilitará hasta aprobación legal y técnica explícita, migraciones terminadas, manejo de holds, retención de webhooks/backups resuelta y varias simulaciones revisadas. Incluso después deberá exigir una bandera de entorno separada, límite de lote, transacción/revalidación, registro de actor/versión, modo de emergencia y aprobación operacional; no se programará cron en este cambio.

## 6. Conservación separada

- **Pedidos, facturas, comprobantes y registros contables:** mantener según plazos legales/fiscales/contractuales definidos por administración/contador. No borrar ni modificar desde la tarea de conversaciones.
- **Reclamos/solicitudes pendientes:** hold hasta resolución y período adicional que indique asesoría. La tabla actual de bandeja no permite identificarlos automáticamente.
- **Clientes con relación vigente:** mantener los datos necesarios para la relación; purgar sólo el historial conversacional que no esté vinculado a una causa de conservación y tras revisar referencias.
- **Meta:** la tarea sólo alcanzaría copias bajo control de sistemaPS. La retención/eliminación de datos que Meta conserva directamente se rige por los controles y términos de Meta, no por el `DELETE` local.
- **Auditoría y backups:** tienen ciclo de vida y justificación propios; no heredan automáticamente los 12 meses de la conversación.

## 7. Puerta de aprobación previa a implementación

Pendiente acordar formalmente: definición de “última interacción” (se propone último inbound del cliente), estados que admiten purga, categorías/obligaciones de hold, operador de solicitudes y revisión, horizonte de retención de eventos de webhook, reglas de backup/restauración, plazo de auditoría, niveles de acceso, pruebas en staging y autorización expresa de un modo `--apply`. Hasta entonces: sólo diseño/documentación, cero cron, cero eliminación real.
