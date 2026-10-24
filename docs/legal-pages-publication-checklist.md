# Publicación de páginas legales (sistemaPS / Perfushopping)

## Estado

El titular autorizó hacer públicas estas páginas. El commit `e3feeff` se publicó en `origin/master` y `demo/master`; el hook reportó actualizar `organizing-web-php`. Se verificó desde Internet que las dos rutas legales sirven el contenido actualizado sin avisos de borrador. Se conservan divulgaciones explícitas sobre proveedores/regiones que no se pudieron verificar y sobre la propuesta de retención que todavía no está implementada.

## Comprobaciones del proyecto

- Document root documentado: `web/public`; en producción el `deploy.sh` copia los archivos públicos PHP y `.htaccess` a `../public_html`, y copia `src/` y `templates/` a sus directorios hermanos.
- Las solicitudes públicas pasan por `public/index.php` y `Router`; `/admin` conserva sus rutas y middleware actuales. Las nuevas rutas legales no requieren autenticación.
- `.htaccess` reescribe rutas no correspondientes a archivos/directorios a `index.php`; no hay un `robots.txt` en `public/` que bloquee estas páginas.
- El despliegue habitual se realiza mediante el remoto `demo` (hook `post-receive` que actualiza `organizing-web`) después de publicar en `origin/master`.

## Hechos de tratamiento comprobados en el código

- Registro de cuentas con nombre, correo, teléfono, hash de contraseña y datos de aceptación de términos (fecha, IP y agente de usuario).
- Pedidos y checkout con identidad/contacto, domicilio de entrega, artículos, importes y datos de operación; carrito abierto con sesión, usuario/correo cuando exista, artículos, cantidades y total.
- Registro de navegación en `web_visitas` (URL, IP, ID de sesión, usuario/producto y fecha) y eventos de CRO/marketing. El layout público carga Meta Pixel y emite `PageView`.
- Identificador `pfs_sid` en `localStorage` y cookie; cookie de referido `ref_code` con expiración de 30 días; cookie de sesión web.
- Bandeja social que puede recibir webhooks de WhatsApp, Instagram y Facebook y guardar texto/identificadores/metadatos del evento. El envío implementado desde bandeja es WhatsApp; Instagram/Messenger outbound figura como pendiente en UI.
- La aplicación consulta una casilla por IMAP y usa SMTP para comunicaciones. Las variables del proveedor real se configuran en el entorno y no se inspeccionaron.
- Hay una integración de Mercado Pago, una integración de Nave (desactivada en `NaveService::enabled()` en el código actual), facturación electrónica ARCA y envío Correo Argentino cuando se selecciona esa modalidad. La habilitación/configuración de producción y el uso histórico no se verificaron.
- No se encontró una política general de borrado automático para pedidos, comunicaciones, carritos abandonados o analítica. No publicar períodos de retención inventados.
- **Pendiente de seguridad:** `MetaWebhookController::isSignatureValid()` acepta el evento si `META_APP_SECRET` está vacío. Confirmar en el entorno de producción que el secreto está configurado; valorar hacer que el webhook falle cerrado cuando la integración esté activa.

## Límites y tareas que siguen abiertos tras la autorización de publicación

El titular confirmó los datos legales de `empre`: `razon_emp = Natalia Gabriela Feresin`, `dire_emp = 9 de Julio 1610` y `mail = clientes@perfushopping.com.ar`. También aprobó `perfushoppingbv@gmail.com` como correo específico de privacidad y las declaraciones de no venta de datos y medidas razonables de seguridad. El plazo de hasta 12 meses para conversaciones de sistemaPS se publica como **propuesta no operativa**, sujeto a validación legal/técnica e implementación efectiva.

1. Confirmar proveedor legal, regiones y subprocesadores según contratos y entorno; la política no atribuye países ni configuraciones no verificadas.
2. Decidir/implementar preferencias de consentimiento del Pixel; la política informa que no se encontró un panel para este píxel.
3. Confirmar que la casilla `perfushoppingbv@gmail.com` está activa/monitorizada y nombrar responsables. El procesamiento de solicitudes es manual y sin autoservicio público.
4. Implementar y validar la propuesta de 12 meses antes de presentarla como criterio operativo. No ejecutar borrados retroactivos sin revisar casos, pedidos, reclamos y obligaciones legales.
5. Obtener plazos fiscales/contables y el ciclo real de logs/backups; la política no inventa duraciones.
6. Someter bases jurídicas, transferencias, AAIP y plazos de respuesta a revisión legal local.
7. Verificar el secreto productivo del webhook Meta y evaluar el comportamiento fail-open; revisar `privacy_v2` para nuevas aceptaciones.
8. Resolver los hallazgos técnicos de `docs/social-conversation-retention-12m.md`: timestamp entrante normalizado, deduplicación antes de reabrir, holds, payloads webhook, adjuntos, bitácora y backups. No habilitar cron hasta aprobación expresa.

## Verificación después de completar los datos

1. Mantener el texto explícito sobre lo no implementado; no presentar la propuesta de 12 meses como depuración activa hasta completar el proceso y validarlo.
2. Ejecutar `php -l` para archivos PHP modificados y `git diff --check`.
3. Publicar los cambios aprobados a `origin/master` y después a `demo/master` según el flujo acordado.
4. Desde un cliente externo, comprobar con `curl -sS -L -D -` que estas URLs responden HTTP 200 y no redirigen a `/login`:
   - `https://perfushopping.ar/politica-de-privacidad`
   - `https://perfushopping.ar/eliminacion-de-datos`
5. Comprobar que el contenido esperado y los enlaces cruzados están presentes. Probar acceso sin cookies de sesión/autenticación.
6. Validar ambas URLs en el Sharing Debugger/validador de Meta. No afirmar disponibilidad pública hasta que estas comprobaciones se completen.

## Estado público observado en esta revisión

Verificación GET posterior al despliegue, desde Internet: `/politica-de-privacidad` 200, `/eliminacion-de-datos` 200, `/privacy` 200 (alias de la política; el pie apunta a la canónica), `/terms` 200 y `/admin` 302 a `/admin/login`. El servidor de borde reporta `hcdn`. Los dos HTML legales contienen `perfushoppingbv@gmail.com`, no incluyen el aviso de borrador y aclaran que la depuración de 12 meses aún no está automatizada.
