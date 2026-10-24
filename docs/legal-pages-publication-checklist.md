# Publicación de páginas legales (sistemaPS / Perfushopping)

## Estado

Se prepararon las páginas locales y sus rutas públicas. **No publicar todavía**: ambas plantillas identifican los campos legales faltantes como pendientes. No se hizo push ni se desplegaron las páginas.

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

El titular confirmó que los datos legales están en `empre`: `razon_emp = Natalia Gabriela Feresin`, `dire_emp = 9 de Julio 1610` y `mail = clientes@perfushopping.com.ar`. Esos valores ya se reflejaron en los borradores. Para el correo se usó el valor de `empre`; el borrador inicial contenía una dirección distinta (`perfushoppingbv@gmail.com`).

1. Confirmar los plazos concretos de conservación y el proceso operativo de borrado/anonimización por categoría, incluidas obligaciones fiscales y conversaciones.
2. Confirmar los proveedores tecnológicos efectivamente contratados, nombres legales y países donde procesan/almacenan datos; verificar la configuración activa de Meta, pagos, hosting/correo y logística.
3. Confirmar el funcionamiento de consentimiento/preferencias frente a Meta Pixel: el pixel se carga actualmente desde el layout público sin que se haya localizado un gestor de consentimiento.
4. Confirmar cómo se verificará la identidad y quién responderá solicitudes de acceso, rectificación y supresión. No existe un autoservicio público de borrado.
5. Revisar con asesoría legal las bases jurídicas, el deber de información, la situación de registro de bases de datos ante AAIP, las transferencias internacionales y el texto final. El plazo legal correcto no es un plazo único: acceso, 10 días corridos; rectificación/actualización/supresión, 5 días hábiles, sujeto a las excepciones legales.
6. Al aprobar y publicar la nueva versión, revisar el identificador de aceptación de privacidad en registro (`privacy_v2`) y decidir si corresponde solicitar nueva aceptación a cuentas existentes.
7. Aprobar las afirmaciones comerciales del borrador (incluida la declaración de que no se venden datos) y confirmar si hay otras comunicaciones promocionales fuera del flujo observado en el código.

## Verificación después de completar los datos

1. Resolver todos los pendientes del checklist, actualizar los textos finales de `templates/legal/privacy-policy.php` y `templates/legal/data-deletion.php`, y retirar las bandas de borrador.
2. Ejecutar `php -l` para archivos PHP modificados y `git diff --check`.
3. Publicar los cambios aprobados a `origin/master` y después a `demo/master` según el flujo acordado.
4. Desde un cliente externo, comprobar con `curl -sS -L -D -` que estas URLs responden HTTP 200 y no redirigen a `/login`:
   - `https://perfushopping.ar/politica-de-privacidad`
   - `https://perfushopping.ar/eliminacion-de-datos`
5. Comprobar que el contenido esperado y los enlaces cruzados están presentes. Probar acceso sin cookies de sesión/autenticación.
6. Validar ambas URLs en el Sharing Debugger/validador de Meta. No afirmar disponibilidad pública hasta que estas comprobaciones se completen.

## Estado público observado en esta revisión

Antes de añadir los cambios, ambos URLs respondieron HTTP 404 desde la consulta externa. Después de publicar, deben volver a verificarse desde fuera del entorno de desarrollo.
