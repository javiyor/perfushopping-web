#!/usr/bin/env python3
"""Agente local de spooler de tickets (referencia).

Consulta cada N segundos la cola de impresión del punto de venta y manda
cada ticket a la impresora predeterminada del sistema operativo.

Requiere solo la librería estándar de Python 3 (sin dependencias).

Configuración (variables de entorno o editar DEFAULTS):
    PS_BASE   ej: https://perfushopping.ar
    PS_TOKEN  token de la impresora (se genera en /admin/impresion/impresoras)
    PS_POLL   segundos entre consultas (default 10)

Impresión:
    Windows: usa `notepad /p archivo.txt` (imprime directo, sin diálogo).
    Linux:   usa `lp` (CUPS). Si no existe, deja el .txt en ./tickets/.

Para tickets sin ningún diálogo en el navegador, alternativa sin instalar
nada: abrir /admin/impresion/spooler en Chrome con --kiosk-printing.
"""

import json
import os
import subprocess
import sys
import tempfile
import time
import urllib.request

BASE = os.environ.get("PS_BASE", "https://perfushopping.ar").rstrip("/")
TOKEN = os.environ.get("PS_TOKEN", "")
POLL = int(os.environ.get("PS_POLL", "10") or 10)
WIDTH = 42


def api(path, payload=None):
    url = BASE + path
    data = json.dumps(payload).encode("utf-8") if payload is not None else None
    req = urllib.request.Request(
        url,
        data=data,
        headers={"Content-Type": "application/json"},
        method="POST" if payload is not None else "GET",
    )
    with urllib.request.urlopen(req, timeout=30) as res:
        return json.loads(res.read().decode("utf-8"))


def money(cents):
    try:
        v = float(cents or 0) / 100.0
    except (TypeError, ValueError):
        v = 0.0
    entero, dec = divmod(round(v * 100), 100)
    return "$%s,%02d" % (f"{entero:,}".replace(",", "."), dec)


def line(left, right=""):
    left = str(left or "")
    right = str(right or "")
    if len(left) + len(right) + 1 > WIDTH:
        left = left[: WIDTH - len(right) - 4] + "..."
    return left.ljust(WIDTH - len(right)) + right


def ticket_text(job):
    f = job.get("factura") or {}
    emp = job.get("empresa") or {}
    suc = job.get("sucursal") or {}
    out = []
    out.append((emp.get("razon_emp") or "PERFUSHOPPING S.R.L.").center(WIDTH))
    if emp.get("cuit"):
        out.append(("CUIT: " + str(emp["cuit"])).center(WIDTH))
    if suc.get("nomsuc"):
        out.append(("Suc: " + str(suc["nomsuc"])).center(WIDTH))
    if suc.get("direccion"):
        out.append(str(suc["direccion"]).center(WIDTH))
    if suc.get("telefono"):
        out.append(("Tel: " + str(suc["telefono"])).center(WIDTH))
    out.append("-" * WIDTH)
    out.append(str(f.get("codigo", "")))
    out.append("Fecha: " + str(f.get("fecha", "")))
    if f.get("cae"):
        out.append("CAE: " + str(f["cae"]))
    out.append("-" * WIDTH)
    for it in job.get("items") or []:
        out.append(str(it.get("producto", ""))[:WIDTH])
        qty = it.get("qty", 1)
        tot = money(it.get("total_cents", 0))
        out.append(line("  x%s" % qty, tot))
    out.append("-" * WIDTH)
    out.append(line("TOTAL:", money(f.get("total_cents", 0))))
    for pg in job.get("pagos") or []:
        out.append(line(str(pg.get("forma_pago", "")), money(pg.get("monto_cents", 0))))
    out.append("")
    out.append("Gracias por su compra".center(WIDTH))
    return "\n".join(out) + "\n"


def do_print(text):
    fd, path = tempfile.mkstemp(prefix="ticket_", suffix=".txt", dir=".")
    try:
        with os.fdopen(fd, "w", encoding="utf-8") as fh:
            fh.write(text)
        if sys.platform.startswith("win"):
            subprocess.run(["notepad", "/p", path], check=False)
        else:
            try:
                subprocess.run(["lp", path], check=False)
            except FileNotFoundError:
                os.makedirs("tickets", exist_ok=True)
                dst = os.path.join("tickets", os.path.basename(path))
                os.replace(path, dst)
                return "sin-impresora:" + dst
        return "ok"
    finally:
        try:
            if os.path.exists(path):
                os.remove(path)
        except OSError:
            pass


def ack(job_id, estado, mensaje=None):
    try:
        api("/api/print/ack", {
            "token": TOKEN,
            "job_id": job_id,
            "estado": estado,
            "mensaje": mensaje,
        })
    except Exception as exc:  # noqa: BLE001 - el loop no debe caerse
        print("ack fallo:", exc, flush=True)


def main():
    if not TOKEN:
        print("Falta PS_TOKEN (token de la impresora).", flush=True)
        sys.exit(2)
    print("Spooler %s cada %ss" % (BASE, POLL), flush=True)
    while True:
        try:
            data = api("/api/print/jobs?token=" + TOKEN)
        except Exception as exc:  # noqa: BLE001
            print("poll fallo:", exc, flush=True)
            time.sleep(POLL)
            continue
        for job in (data or {}).get("jobs", []):
            try:
                res = do_print(ticket_text(job))
                if res == "ok":
                    ack(job["job_id"], "impreso")
                else:
                    ack(job["job_id"], "error", res)
            except Exception as exc:  # noqa: BLE001
                ack(job.get("job_id", 0), "error", str(exc)[:200])
        time.sleep(POLL)


if __name__ == "__main__":
    main()
