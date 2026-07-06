#!/bin/bash
set -e

echo "=== Pull desde GitHub ==="
git pull origin main

echo "=== Copiando webroot ==="
cp public/index.php ../public_html/
cp public/.htaccess ../public_html/
cp -r public/assets ../public_html/

echo "=== Copiando src y templates ==="
cp -r src/* ../src/
cp -r templates/* ../templates/

echo "=== Deploy completado ==="
