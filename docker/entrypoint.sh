#!/bin/bash
set -e

DATA_PATH="${DATA_PATH:-/data}"
APP_PATH="/var/www/html"
CERT_DIR="$DATA_PATH/certs"
UPLOADS_DIR="$DATA_PATH/uploads"

echo "🍾 Bar Inventory starting..."

# ── Seed app files on first run ──────────────────────────────────────────────
if [ ! -f "$APP_PATH/public/index.php" ]; then
    echo "📂 Seeding app files to $APP_PATH ..."
    cp -r /var/www/html-image/. "$APP_PATH/"
    echo "✅ App files ready — edit them at /ont/homeserver/bar/app on the host"
fi

# ── Data directories ─────────────────────────────────────────────────────────
mkdir -p "$CERT_DIR" "$UPLOADS_DIR"

# ── Self-signed cert (diagnostic HTTPS only) ─────────────────────────────────
if [ ! -f "$CERT_DIR/cert.pem" ] || [ ! -f "$CERT_DIR/key.pem" ]; then
    echo "🔐 Generating self-signed TLS certificate (for direct access only)..."
    openssl req -x509 -newkey rsa:2048 -nodes \
        -keyout "$CERT_DIR/key.pem" \
        -out    "$CERT_DIR/cert.pem" \
        -days 3650 \
        -subj "/CN=bar-inventory" \
        -addext "subjectAltName=IP:127.0.0.1,DNS:localhost,DNS:bar-inventory" \
        2>/dev/null
    echo "✅ Certificate generated — browser will warn (self-signed, diagnostic only)"
fi

# ── Uploads symlink ──────────────────────────────────────────────────────────
if [ ! -L "$APP_PATH/public/uploads" ]; then
    rm -rf "$APP_PATH/public/uploads"
    ln -s "$UPLOADS_DIR" "$APP_PATH/public/uploads"
fi

# ── Database migrations ──────────────────────────────────────────────────────
echo "📦 Running migrations..."
php "$APP_PATH/migrate.php" "$DATA_PATH/bar.db"

# ── Permissions ──────────────────────────────────────────────────────────────
chown -R www-data:www-data "$DATA_PATH" "$APP_PATH"
chmod 700 "$CERT_DIR"
chmod 600 "$CERT_DIR"/*.pem 2>/dev/null || true

echo "✅ Ready"
echo "   → https://your-domain.com        (via NPM — proper cert)"
echo "   → https://192.168.x.x:8443       (direct — self-signed, browser will warn)"

exec apache2-foreground
