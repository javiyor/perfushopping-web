#!/bin/bash
set -e

echo "=== Pull desde GitHub ==="
git fetch origin
# Si hay historia reescrita (force push), alinear sin merge
if ! git pull --ff-only origin master 2>/dev/null; then
  echo "Historias divergentes -> reset --hard origin/master"
  git reset --hard origin/master
fi

echo "=== Copiando webroot ==="
cp public/*.php ../public_html/
cp public/.htaccess ../public_html/
cp public/manifest.webmanifest ../public_html/
cp public/admin-sw.js ../public_html/

echo "=== Copiando assets (uno a uno, sin anidar) ==="
mkdir -p ../public_html/assets
cp public/assets/app.css ../public_html/assets/
cp public/assets/app.js ../public_html/assets/
cp -r public/assets/brand ../public_html/assets/

echo "=== Copiando src y templates ==="
cp -r src/* ../src/
cp -r templates/* ../templates/

echo "=== Deploy completado ==="
