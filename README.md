# Bar Inventory

A self-hosted home bar bottle and recipe inventory app.

## Stack
- PHP 8.2 + Slim 4
- SQLite (single file database)
- Apache
- Docker + Docker Compose

## Setup

### With Docker (recommended)
```bash
docker compose up -d --build
```

Access via `https://your-server:8443` or via Nginx Proxy Manager.

On first run, browse to `/setup` to create your admin account.

### Data
All persistent data is stored in `/opt/homeserver/bar/data`:
- `bar.db` — SQLite database
- `uploads/` — logo and images
- `certs/` — self-signed SSL cert

### App files
Source files are bind-mounted from `/opt/homeserver/bar/app`.  
Edit files directly — no rebuild needed for PHP/template changes.

## Features
- Bottle inventory with fill level tracking
- Barcode scanning (camera) with Open Food Facts lookup
- Recipe manager (tiki-focused)
- Import/Export (CSV + XLSX)
- Multi-user with roles (Admin, Editor, Viewer)
- Forgot password via Gmail SMTP
- Custom themes
- Mobile responsive

## Ports
- `8080` — HTTP (used by Nginx Proxy Manager)
- `8443` — HTTPS direct access (self-signed, for diagnostics)

## NPM (Nginx Proxy Manager)
Point proxy host to container name `bar-inventory` on port `80`.
