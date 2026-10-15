#!/usr/bin/env bash
#
# Menjalankan E-Rapor di komputer lokal.
#
# Menyalakan peladen, pemroses antrean, pemantau log dan Vite sekaligus, sama
# seperti "composer dev".

set -euo pipefail

cd "$(dirname "$0")"

MERAH=$'\033[31m'; TEBAL=$'\033[1m'; MATI=$'\033[0m'
fail() { printf '%serror%s %s\n' "$MERAH" "$MATI" "$1" >&2; exit 1; }

[ -f .env ] || fail ".env belum ada. Jalankan ./install.sh lebih dahulu."
[ -d vendor ] || fail "Dependensi belum terpasang. Jalankan ./install.sh lebih dahulu."

APP_URL=$(php scripts/config-value.php app.url)

printf '%sE-Rapor%s berjalan di %s\n' "$TEBAL" "$MATI" "$APP_URL"
printf 'Tekan Ctrl+C untuk berhenti.\n\n'

# composer dev menjalankan keempat proses dan menghentikannya bersama-sama, jadi
# tidak perlu mengelola latar belakang sendiri di sini.
exec composer dev
