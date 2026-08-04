#!/usr/bin/env bash
#
# Установка патча «OSMO на Metronic 8.2.1».
#
#   bash patch/bin/install.sh /путь/к/репозиторию/metronic
#
# Второй аргумент необязателен — путь к каталогу demo48 внутри репозитория
# metronic. По умолчанию: <repo>/html/metronic_html_v8.2.1_demo48/demo48
#
set -e

PATCH_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PROJECT_DIR="$(pwd)"
METRONIC_REPO="${1:-}"
DEMO_DIR="${2:-}"

echo "Проект:  $PROJECT_DIR"
echo "Патч:    $PATCH_DIR"

if [ ! -f "$PROJECT_DIR/artisan" ]; then
  echo "Ошибка: запускать из корня Laravel-проекта (рядом с artisan)."
  exit 1
fi

# ---------------------------------------------------------------- 1. Файлы
echo
echo "1/4 Копирую файлы патча…"
for d in app config resources public; do
  if [ -d "$PATCH_DIR/$d" ]; then
    cp -R "$PATCH_DIR/$d/." "$PROJECT_DIR/$d/"
    echo "  [+] $d"
  fi
done

# ------------------------------------------------------------- 2. Ассеты
echo
echo "2/4 Ассеты Metronic…"
if [ -n "$METRONIC_REPO" ]; then
  if [ -z "$DEMO_DIR" ]; then
    DEMO_DIR="$METRONIC_REPO/html/metronic_html_v8.2.1_demo48/demo48"
  fi

  if [ -d "$DEMO_DIR/assets" ]; then
    mkdir -p "$PROJECT_DIR/public/metronic/assets"
    cp -R "$DEMO_DIR/assets/." "$PROJECT_DIR/public/metronic/assets/"
    echo "  [+] public/metronic/assets"
  else
    echo "  [!] не нашёл $DEMO_DIR/assets — скопируйте ассеты вручную"
  fi
else
  echo "  [пропуск] путь к репозиторию metronic не указан."
  echo "            Скопируйте demo48/assets в public/metronic/assets вручную."
fi

# ------------------------------------------------------- 3. Правка кода
echo
echo "3/4 Правлю config/app.php, Kernel.php и старую шапку…"
php "$PATCH_DIR/bin/patch-sources.php"

# ---------------------------------------------------------------- 4. Кэш
echo
echo "4/4 Чищу кэши…"
php artisan view:clear || true
php artisan config:clear || true
php artisan route:clear || true

echo
echo "Готово. Переключатель оформления — в шапке сайта."
echo "Проверьте путь к Font Awesome Pro в config/ui.php (ключ extra_css)."
