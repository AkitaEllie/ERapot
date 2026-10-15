#!/usr/bin/env bash
#
# Memasang E-Rapor: dependensi, .env, migrasi, data contoh, dan aset frontend.
# Berhenti dengan pesan yang jelas bila ada yang kurang, bukan gagal di tengah.

set -euo pipefail

cd "$(dirname "$0")"

MERAH=$'\033[31m'; HIJAU=$'\033[32m'; KUNING=$'\033[33m'; TEBAL=$'\033[1m'; MATI=$'\033[0m'

info()  { printf '%s==>%s %s\n' "$TEBAL" "$MATI" "$1"; }
ok()    { printf '%s ok %s %s\n' "$HIJAU" "$MATI" "$1"; }
warn()  { printf '%speringatan%s %s\n' "$KUNING" "$MATI" "$1"; }
fail()  { printf '%serror%s %s\n' "$MERAH" "$MATI" "$1" >&2; exit 1; }

# --- Periksa perkakas -------------------------------------------------------

info "Memeriksa perkakas"

command -v php >/dev/null 2>&1 || fail "PHP tidak ditemukan. Pasang PHP 8.3 atau lebih baru."
command -v composer >/dev/null 2>&1 || fail "Composer tidak ditemukan. Lihat https://getcomposer.org."
command -v npm >/dev/null 2>&1 || fail "npm tidak ditemukan. Pasang Node.js 22 atau lebih baru."

PHP_OK=$(php -r 'echo version_compare(PHP_VERSION, "8.3.0", ">=") ? "1" : "0";')
[ "$PHP_OK" = "1" ] || fail "PHP $(php -r 'echo PHP_VERSION;') terlalu lama. Diperlukan 8.3 atau lebih baru."

for EXT in mbstring openssl pdo_mysql tokenizer xml ctype json curl fileinfo; do
    php -m | grep -qi "^${EXT}$" || fail "Ekstensi PHP '${EXT}' belum aktif. Aktifkan lalu jalankan skrip ini lagi."
done

ok "PHP $(php -r 'echo PHP_VERSION;'), Composer, dan npm tersedia"

# --- .env -------------------------------------------------------------------

if [ ! -f .env ]; then
    info "Membuat .env dari .env.example"
    cp .env.example .env
    ok ".env dibuat"
else
    ok ".env sudah ada, dibiarkan apa adanya"
fi

# --- Dependensi -------------------------------------------------------------

info "Memasang dependensi PHP"
composer install --no-interaction --prefer-dist

info "Memasang dependensi JavaScript"
npm install --no-audit --no-fund

# --- Kunci aplikasi ---------------------------------------------------------

if ! grep -qE '^APP_KEY=.+' .env; then
    info "Mengisi APP_KEY"
    php artisan key:generate --ansi
fi
ok "APP_KEY terisi"

# --- Basis data -------------------------------------------------------------

info "Memeriksa basis data"

# Skrip tidak membuat basis data sendiri: itu memerlukan kredensial root dan
# bisa berbeda di tiap komputer. Beri tahu perintahnya bila belum ada.
# Pembacaannya lewat Laravel, bukan parse .env, karena .env memuat komentar
# dan tanda kutip yang mudah salah dibaca.
#
# The "|| true" matters: set -e would otherwise end the script the moment the
# check reports the database is down, before the message below can explain it.
DB_REPORT=$(php scripts/check-database.php 2>&1) || true
DB_TARGET=$(printf '%s\n' "$DB_REPORT" | sed -n 's/^target=//p')
DB_NAME=$(printf '%s' "${DB_TARGET##*/}")
DB_USER=$(printf '%s' "${DB_TARGET%%@*}")

if ! printf '%s\n' "$DB_REPORT" | grep -qx 'ok'; then
    printf '\n' >&2
    fail "Basis data belum bisa dihubungi.

  Sasaran: ${DB_TARGET:-lihat .env}

  Buat basis datanya lebih dahulu:

      mysql -u${DB_USER:-root} -p -e \"CREATE DATABASE ${DB_NAME:-ERapot} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"

  Bila peladennya belum berjalan:

      sudo systemctl start mariadb

  Periksa juga DB_DATABASE, DB_USERNAME, dan DB_PASSWORD di .env."
fi

ok "Basis data terhubung (${DB_TARGET})"

# --- Migrasi dan data contoh ------------------------------------------------

info "Menjalankan migrasi"
php artisan migrate --force --ansi

# Data contoh bukan idempotent: ia membuat tahun ajaran dan kelas yang punya
# indeks unik, jadi menjalankannya dua kali akan gagal. Lewati bila basis data
# sudah berisi data, supaya skrip ini aman dijalankan ulang.
if php artisan tinker --execute='exit(App\Models\TahunAjaran::query()->exists() ? 1 : 0);' >/dev/null 2>&1; then
    info "Mengisi data contoh"
    php artisan db:seed --force --ansi
else
    warn "Basis data sudah berisi data, data contoh dilewati"
    warn "Untuk memulai dari awal: php artisan migrate:fresh --seed"
fi

# --- Penyimpanan dan aset ---------------------------------------------------

info "Menautkan penyimpanan"
php artisan storage:link --ansi >/dev/null 2>&1 || true

info "Membangun aset frontend"
npm run build

# --- Selesai ----------------------------------------------------------------

printf '\n'
ok "Pemasangan selesai"
printf '\n'
printf '  Jalankan aplikasinya:\n\n    ./run.sh\n\n'
printf '  Masuk dengan kata sandi %srahasia-kuat%s:\n\n' "$TEBAL" "$MATI"
printf '    %-22s Guru Wali Kelas\n' 'sari@tkcktc.sch.id'
printf '    %-22s Guru Mata Pelajaran\n' 'ratna@tkcktc.sch.id'
printf '    %-22s Tata Usaha\n' 'andre@tkcktc.sch.id'
printf '    %-22s Kepala Sekolah\n\n' 'mei@tkcktc.sch.id'
