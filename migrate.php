<?php
$dbPath = $argv[1] ?? '/data/bar.db';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA journal_mode=WAL');
$pdo->exec('PRAGMA foreign_keys=ON');

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    username    TEXT NOT NULL UNIQUE,
    password    TEXT NOT NULL,
    role        TEXT NOT NULL DEFAULT 'viewer', -- admin|editor|viewer
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    last_login  TEXT
);

CREATE TABLE IF NOT EXISTS settings (
    key         TEXT PRIMARY KEY,
    value       TEXT
);

CREATE TABLE IF NOT EXISTS bottles (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            TEXT NOT NULL,
    brand           TEXT,
    category        TEXT DEFAULT 'Other',
    vintage         TEXT,
    abv             REAL,
    country         TEXT,
    fill            INTEGER DEFAULT 100,
    barcode         TEXT,
    notes           TEXT,
    custom_data     TEXT DEFAULT '{}',
    created_by      INTEGER,
    created_at      TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS custom_fields (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    label       TEXT NOT NULL,
    type        TEXT NOT NULL DEFAULT 'text', -- text|number|date|boolean
    required    INTEGER DEFAULT 0,
    sort_order  INTEGER DEFAULT 0,
    enabled     INTEGER DEFAULT 1,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS field_config (
    field_name  TEXT PRIMARY KEY,  -- built-in field name
    label       TEXT,
    visible     INTEGER DEFAULT 1,
    sort_order  INTEGER DEFAULT 0
);

CREATE TABLE IF NOT EXISTS recipes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    description TEXT,
    category    TEXT DEFAULT 'Tiki',
    glass       TEXT,
    garnish     TEXT,
    instructions TEXT,
    created_by  INTEGER,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    recipe_id   INTEGER NOT NULL,
    name        TEXT NOT NULL,
    amount      TEXT,
    unit        TEXT,
    bottle_id   INTEGER,
    sort_order  INTEGER DEFAULT 0,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (bottle_id) REFERENCES bottles(id) ON DELETE SET NULL
);
");

// Password reset tokens table
$pdo->exec("
CREATE TABLE IF NOT EXISTS password_resets (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL,
    token      TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    used       INTEGER DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS custom_themes (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL UNIQUE,
    vars       TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
");

// Default settings
$defaults = [
    'app_name'          => 'Bar Inventory',
    'app_logo'          => '',
    'theme'             => 'dark-gold',
    'theme_custom'      => '{}',
    'require_login'     => '0',
    'default_category'  => 'Whiskey',
    'default_fill'      => '100',
    'currency'          => 'USD',
    'currency_symbol'   => '$',
    'admin_pin'         => '',
    'setup_complete'    => '0',
    'export_include_empty' => '1',
    'categories' => json_encode(['Bourbon','Scotch','Whiskey','Vodka','Gin','Rum','Tequila','Mezcal','Cognac','Brandy','Champagne','Rosé','Red Wine','White Wine','Wine','Beer','Liqueur','Other']),
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => '587',
    'smtp_user'     => '',
    'smtp_pass'     => '',
    'smtp_from'     => '',
    'smtp_from_name'=> 'Bar Inventory',
];

$stmt = $pdo->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
foreach ($defaults as $k => $v) {
    $stmt->execute([$k, $v]);
}

// Default field config for built-in fields
$fields = [
    ['brand',    'Brand',    1, 1],
    ['category', 'Category', 1, 2],
    ['vintage',  'Vintage',  1, 3],
    ['abv',      'ABV %',    1, 4],
    ['country',  'Country',  1, 5],
    ['fill',     'Fill Level',1,6],
    ['barcode',  'Barcode',  1, 7],
    ['notes',    'Notes',    1, 8],
];
$stmt = $pdo->prepare("INSERT OR IGNORE INTO field_config (field_name, label, visible, sort_order) VALUES (?,?,?,?)");
foreach ($fields as $f) {
    $stmt->execute($f);
}

echo "✅ Database ready: $dbPath\n";
