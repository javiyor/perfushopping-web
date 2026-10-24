# Publicación de páginas legales (sistemaPS / Perfushopping)

## Estado

Las rutas públicas existen y respondieron HTTP 200 en la verificación del 23-10-2026. La versión actualmente servida tiene aviso de borrador y no incluye los cambios locales más recientes. **No publicar la revisión local todavía**: quedan datos de proveedores, preferencias del Pixel, proceso operativo y revisión legal por confirmar.

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

## Datos que el titular debe confirmar antes de publicar

El titular confirmó los datos legales de `empre`: `razon_emp = Natalia Gabriela Feresin`, `dire_emp = 9 de Julio 1610` y `mail = clientes@perfushopping.com.ar`. También aprobó `perfushoppingbv@gmail.com` como correo específico de privacidad y las declaraciones de no venta de datos y medidas razonables de seguridad. El plazo de hasta 12 meses para conversaciones de sistemaPS se seleccionó como **propuesta**; permanece sujeto a validación legal/técnica y a implementación efectiva.

1. Confirmar los proveedores tecnológicos contratados, nombres legales y países donde procesan/almacenan datos; verificar configuración de Meta, pagos, hosting/correo y logística.
2. Resolver consentimiento/preferencias frente al Meta Pixel, que actualmente se carga desde el layout público sin un gestor localizado.
3. Definir responsable y procedimiento para solicitudes; comprobar que la casilla `perfushoppingbv@gmail.com` está activa y monitorizada. No existe autoservicio público de borrado.
4. Diseñar y asignar la aplicación operativa de la propuesta de hasta 12 meses, incluidas excepciones por relaciones/casos activos, reclamos y obligaciones legales. Los registros existentes requieren revisión; no ejecutar borrados retroactivos sin esa revisión.
5. Obtener de administración/contabilidad los plazos legales aplicables a pedidos, comprobantes y facturas; confirmar plazos de logs y backups con el proveedor.
6. Revisar con asesoría legal las bases jurídicas, el deber de información, la situación de registro de bases de datos ante AAIP, las transferencias internacionales y el texto final. Plazos de referencia: acceso, 10 días corridos; rectificación/actualización/supresión, 5 días hábiles, sujeto a excepciones legales.
7. Verificar en entorno seguro el secreto productivo del webhook Meta y corregir el comportamiento fail-open si corresponde; revisar `privacy_v2` y si corresponde informar/recabar aceptación a cuentas existentes.
8. Confirmar si hay comunicaciones promocionales adicionales a los flujos observados en el código.
9. Resolver antes del job los hallazgos de `docs/social-conversation-retention-12m.md`: `last_message_at` no es la última interacción del cliente; los timestamps UTC/local se mezclan; reaperturas manuales y webhooks duplicados alteran estados; los payloads crudos de webhook no están ligados a conversaciones y pueden conservar copias.

## Verificación después de completar los datos

1. Resolver los pendientes, actualizar los textos finales de `templates/legal/privacy-policy.php` y `templates/legal/data-deletion.php`, y retirar las bandas de borrador sólo cuando la información publicada describa el proceso real.
2. Ejecutar `php -l` para archivos PHP modificados y `git diff --check`.
3. Publicar los cambios aprobados a `origin/master` y después a `demo/master` según el flujo acordado.
4. Desde un cliente externo, comprobar con `curl -sS -L -D -` que estas URLs responden HTTP 200 y no redirigen a `/login`:
   - `https://perfushopping.ar/politica-de-privacidad`
   - `https://perfushopping.ar/eliminacion-de-datos`
5. Comprobar que el contenido esperado y los enlaces cruzados están presentes. Probar acceso sin cookies de sesión/autenticación.
6. Validar ambas URLs en el Sharing Debugger/validador de Meta. No afirmar disponibilidad pública hasta que estas comprobaciones se completen.

## Estado público observado en esta revisión

Ambas URLs responden HTTP 200 y muestran los encabezados y avisos de borrador. El contenido servido incluye el correo general `clientes@perfushopping.com.ar`, pero no el canal aprobado `perfushoppingbv@gmail.com` ni las modificaciones locales actuales. La modificación local aún no se ha desplegado; verificar la versión nuevamente después de autorizar su publicación.
