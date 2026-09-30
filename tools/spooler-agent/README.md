# Agente local de spooler de tickets

Imprime en la ticketera de la PC cada factura que entra en cola, como un
spooler: consulta `/api/print/jobs`, imprime y confirma con `/api/print/ack`.

## Opción A — sin instalar nada (recomendada para empezar)

1. En `/admin/impresion/impresoras` creá una impresora para el punto de venta.
2. En la PC del mostrador, abrí Chrome con impresión silenciosa y la página
   del spooler:

   ```
   chrome.exe --kiosk-printing https://perfushopping.ar/admin/impresion/spooler
   ```

3. Dejá esa pestaña abierta: cada factura emitida se imprime sola.
   Sin `--kiosk-printing`, Chrome pide confirmar cada ticket.

## Opción B — agente Python (PC dedicada, sin navegador)

1. Instalá Python 3 en la PC de la ticketera.
2. Generá el token en `/admin/impresion/impresoras` (columna Token).
3. Ejecutá:

   ```
   set PS_BASE=https://perfushopping.ar
   set PS_TOKEN=<token de la impresora>
   python spooler.py
   ```

   En Windows imprime con `notepad /p` a la impresora predeterminada;
   en Linux usa `lp` (CUPS). Cada ticket confirmado pasa a estado
   `impreso`; si falla queda en `error` con el motivo.
