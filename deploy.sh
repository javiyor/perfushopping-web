#!/bin/bash
set -e

echo "=== Pull desde GitHub ==="
git pull origin master

echo "=== Copiando webroot ==="
cp public/*.php ../public_html/
cp public/.htaccess ../public_html/

echo "=== Copiando assets (uno a uno, sin anidar) ==="
mkdir -p ../public_html/assets
cp public/assets/app.css ../public_html/assets/
cp public/assets/app.js ../public_html/assets/
cp -r public/assets/brand ../public_html/assets/

echo "=== Copiando src y templates ==="
cp -r src/* ../src/
cp -r templates/* ../templates/

echo "=== Deploy completado ==="
