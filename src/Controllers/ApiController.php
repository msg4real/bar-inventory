<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ApiController
{
    public function __construct(private $container) {}

    private function db() { return $this->container->get('db'); }

    private function json(Response $res, mixed $data, int $status = 200): Response
    {
        $res->getBody()->write(json_encode($data));
        return $res->withStatus($status)->withHeader('Content-Type', 'application/json');
    }

    private function role(): string  { return $_SESSION['user']['role'] ?? 'viewer'; }
    private function canEdit(): bool { return in_array($this->role(), ['admin', 'editor']); }

    // ── Bottles ───────────────────────────────────────────────────────────────

    public function listBottles(Request $req, Response $res): Response
    {
        $bottles = $this->db()->query("SELECT * FROM bottles ORDER BY name ASC")->fetchAll();
        foreach ($bottles as &$b) {
            $b['custom_data'] = json_decode($b['custom_data'] ?: '{}', true) ?? [];
        }
        return $this->json($res, $bottles);
    }

    public function createBottle(Request $req, Response $res): Response
    {
        if (!$this->canEdit()) return $this->json($res, ['error' => 'Forbidden'], 403);

        $b    = $req->getParsedBody() ?? [];
        $stmt = $this->db()->prepare("
            INSERT INTO bottles (name, brand, category, vintage, abv, country, fill, barcode, notes, custom_data, created_by)
            VALUES (:name, :brand, :category, :vintage, :abv, :country, :fill, :barcode, :notes, :custom_data, :created_by)
        ");
        $stmt->execute([
            ':name'        => $b['name']     ?? '',
            ':brand'       => $b['brand']    ?? '',
            ':category'    => $b['category'] ?? 'Other',
            ':vintage'     => $b['vintage']  ?? '',
            ':abv'         => isset($b['abv']) && $b['abv'] !== '' ? (float)$b['abv'] : null,
            ':country'     => $b['country']  ?? '',
            ':fill'        => (int)($b['fill'] ?? 100),
            ':barcode'     => $b['barcode']  ?? '',
            ':notes'       => $b['notes']    ?? '',
            ':custom_data' => json_encode($b['custom_data'] ?? []),
            ':created_by'  => $_SESSION['user']['id'] ?? null,
        ]);
        $id     = $this->db()->lastInsertId();
        $bottle = $this->db()->query("SELECT * FROM bottles WHERE id = $id")->fetch();
        $bottle['custom_data'] = json_decode($bottle['custom_data'] ?: '{}', true) ?? [];
        return $this->json($res, $bottle, 201);
    }

    public function updateBottle(Request $req, Response $res, array $args): Response
    {
        if (!$this->canEdit()) return $this->json($res, ['error' => 'Forbidden'], 403);

        $b  = $req->getParsedBody() ?? [];
        $id = (int)$args['id'];
        $this->db()->prepare("
            UPDATE bottles SET
                name = :name, brand = :brand, category = :category, vintage = :vintage,
                abv = :abv, country = :country, fill = :fill, barcode = :barcode,
                notes = :notes, custom_data = :custom_data, updated_at = datetime('now')
            WHERE id = :id
        ")->execute([
            ':name'        => $b['name']     ?? '',
            ':brand'       => $b['brand']    ?? '',
            ':category'    => $b['category'] ?? 'Other',
            ':vintage'     => $b['vintage']  ?? '',
            ':abv'         => isset($b['abv']) && $b['abv'] !== '' ? (float)$b['abv'] : null,
            ':country'     => $b['country']  ?? '',
            ':fill'        => (int)($b['fill'] ?? 100),
            ':barcode'     => $b['barcode']  ?? '',
            ':notes'       => $b['notes']    ?? '',
            ':custom_data' => json_encode($b['custom_data'] ?? []),
            ':id'          => $id,
        ]);
        $bottle = $this->db()->query("SELECT * FROM bottles WHERE id = $id")->fetch();
        $bottle['custom_data'] = json_decode($bottle['custom_data'] ?: '{}', true) ?? [];
        return $this->json($res, $bottle);
    }

    public function deleteBottle(Request $req, Response $res, array $args): Response
    {
        if (!$this->canEdit()) return $this->json($res, ['error' => 'Forbidden'], 403);
        $this->db()->prepare("DELETE FROM bottles WHERE id = ?")->execute([(int)$args['id']]);
        return $this->json($res, ['ok' => true]);
    }

    public function bulkCreate(Request $req, Response $res): Response
    {
        if (!$this->canEdit()) return $this->json($res, ['error' => 'Forbidden'], 403);

        $bottles = ($req->getParsedBody() ?? [])['bottles'] ?? [];
        $stmt    = $this->db()->prepare("
            INSERT INTO bottles (name, brand, category, vintage, abv, country, fill, barcode, notes, custom_data, created_by)
            VALUES (:name, :brand, :category, :vintage, :abv, :country, :fill, :barcode, :notes, :custom_data, :created_by)
        ");
        $ids = [];
        foreach ($bottles as $b) {
            $stmt->execute([
                ':name'        => $b['name']     ?? 'Unknown',
                ':brand'       => $b['brand']    ?? '',
                ':category'    => $b['category'] ?? 'Other',
                ':vintage'     => $b['vintage']  ?? '',
                ':abv'         => isset($b['abv']) && $b['abv'] !== '' ? (float)$b['abv'] : null,
                ':country'     => $b['country']  ?? '',
                ':fill'        => (int)($b['fill'] ?? 100),
                ':barcode'     => $b['barcode']  ?? '',
                ':notes'       => $b['notes']    ?? '',
                ':custom_data' => json_encode($b['custom_data'] ?? []),
                ':created_by'  => $_SESSION['user']['id'] ?? null,
            ]);
            $ids[] = $this->db()->lastInsertId();
        }
        return $this->json($res, ['created' => count($ids), 'ids' => $ids], 201);
    }

    // ── Barcode lookup ────────────────────────────────────────────────────────

    public function barcodeLookup(Request $req, Response $res, array $args): Response
    {
        $code = preg_replace('/\D/', '', $args['code'] ?? '');
        if (strlen($code) < 6) {
            return $this->json($res, ['error' => 'Invalid barcode'], 400);
        }

        $ctx = stream_context_create(['http' => [
            'timeout' => 6,
            'header'  => "User-Agent: BarInventoryApp/1.0\r\n",
        ]]);

        // 1. Open Food Facts
        $raw = @file_get_contents("https://world.openfoodfacts.org/api/v0/product/{$code}.json", false, $ctx);
        if ($raw) {
            $d = json_decode($raw, true);
            if (($d['status'] ?? 0) === 1 && !empty($d['product'])) {
                $p    = $d['product'];
                $name = trim($p['product_name'] ?? $p['product_name_en'] ?? '');
                if ($name) {
                    $brand      = trim(explode(',', $p['brands'] ?? '')[0]);
                    $rawCountry = str_replace(['en:', '-'], ['', ' '], $p['countries_tags'][0] ?? '');
                    $country    = ucfirst($rawCountry);
                    $abv        = $p['nutriments']['alcohol'] ?? $p['nutriments']['alcohol_100g'] ?? '';
                    return $this->json($res, [
                        'name'     => $name,
                        'brand'    => $brand,
                        'country'  => $country,
                        'category' => $this->guessCategory("$name $brand " . ($p['categories'] ?? '')),
                        'vintage'  => '',
                        'abv'      => $abv !== '' ? (string)$abv : '',
                        'notes'    => substr($p['generic_name'] ?? '', 0, 120),
                        'barcode'  => $code,
                    ]);
                }
            }
        }

        // 2. UPC Item DB
        $ctx2 = stream_context_create(['http' => ['timeout' => 6]]);
        $raw2 = @file_get_contents("https://api.upcitemdb.com/prod/trial/lookup?upc={$code}", false, $ctx2);
        if ($raw2) {
            $d    = json_decode($raw2, true);
            $item = $d['items'][0] ?? null;
            if ($item && !empty($item['title'])) {
                return $this->json($res, [
                    'name'     => $item['title'],
                    'brand'    => $item['brand']  ?? '',
                    'country'  => '',
                    'category' => $this->guessCategory($item['title'] . ' ' . ($item['brand'] ?? '') . ' ' . ($item['category'] ?? '')),
                    'vintage'  => '',
                    'abv'      => '',
                    'notes'    => substr($item['description'] ?? '', 0, 120),
                    'barcode'  => $code,
                ]);
            }
        }

        return $this->json($res, ['error' => 'Barcode not found'], 404);
    }

    private function guessCategory(string $t): string
    {
        $t   = strtolower($t);
        $map = [
            'bourbon' => 'Bourbon',   'scotch'     => 'Scotch',
            'whiskey' => 'Whiskey',   'whisky'     => 'Whiskey',
            'vodka'   => 'Vodka',     'gin'        => 'Gin',
            'rum'     => 'Rum',       'tequila'    => 'Tequila',
            'mezcal'  => 'Mezcal',    'cognac'     => 'Cognac',
            'brandy'  => 'Brandy',    'champagne'  => 'Champagne',
            'ros'     => 'Rosé',      'red wine'   => 'Red Wine',
            'white wine' => 'White Wine', 'wine'   => 'Wine',
            'beer'    => 'Beer',      'ale'        => 'Beer',
            'lager'   => 'Beer',      'stout'      => 'Beer',
        ];
        foreach ($map as $k => $v) {
            if (str_contains($t, $k)) return $v;
        }
        return 'Other';
    }

    // ── Recipes ───────────────────────────────────────────────────────────────

    public function listRecipes(Request $req, Response $res): Response
    {
        $recipes = $this->db()->query("SELECT * FROM recipes ORDER BY name ASC")->fetchAll();
        foreach ($recipes as &$r) {
            $stmt = $this->db()->prepare("SELECT * FROM recipe_ingredients WHERE recipe_id = ? ORDER BY sort_order");
            $stmt->execute([$r['id']]);
            $r['ingredients'] = $stmt->fetchAll();
        }
        return $this->json($res, $recipes);
    }

    public function createRecipe(Request $req, Response $res): Response
    {
        if (!$this->canEdit()) return $this->json($res, ['error' => 'Forbidden'], 403);

        $b  = $req->getParsedBody() ?? [];
        $db = $this->db();
        $db->prepare("
            INSERT INTO recipes (name, description, category, glass, garnish, instructions, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $b['name']         ?? '',
            $b['description']  ?? '',
            $b['category']     ?? 'Tiki',
            $b['glass']        ?? '',
            $b['garnish']      ?? '',
            $b['instructions'] ?? '',
            $_SESSION['user']['id'] ?? null,
        ]);
        $id = $db->lastInsertId();
        foreach (($b['ingredients'] ?? []) as $i => $ing) {
            $db->prepare("INSERT INTO recipe_ingredients (recipe_id, name, amount, unit, bottle_id, sort_order) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$id, $ing['name'] ?? '', $ing['amount'] ?? '', $ing['unit'] ?? '', $ing['bottle_id'] ?: null, $i]);
        }
        return $this->json($res, ['id' => (int)$id], 201);
    }

    public function updateRecipe(Request $req, Response $res, array $args): Response
    {
        if (!$this->canEdit()) return $this->json($res, ['error' => 'Forbidden'], 403);

        $b  = $req->getParsedBody() ?? [];
        $id = (int)$args['id'];
        $db = $this->db();
        $db->prepare("
            UPDATE recipes SET name = ?, description = ?, category = ?, glass = ?, garnish = ?, instructions = ?, updated_at = datetime('now')
            WHERE id = ?
        ")->execute([$b['name']??'', $b['description']??'', $b['category']??'Tiki', $b['glass']??'', $b['garnish']??'', $b['instructions']??'', $id]);
        $db->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = ?")->execute([$id]);
        foreach (($b['ingredients'] ?? []) as $i => $ing) {
            $db->prepare("INSERT INTO recipe_ingredients (recipe_id, name, amount, unit, bottle_id, sort_order) VALUES (?, ?, ?, ?, ?, ?)")
               ->execute([$id, $ing['name']??'', $ing['amount']??'', $ing['unit']??'', $ing['bottle_id']??null, $i]);
        }
        return $this->json($res, ['ok' => true]);
    }

    public function deleteRecipe(Request $req, Response $res, array $args): Response
    {
        if (!$this->canEdit()) return $this->json($res, ['error' => 'Forbidden'], 403);
        $this->db()->prepare("DELETE FROM recipes WHERE id = ?")->execute([(int)$args['id']]);
        return $this->json($res, ['ok' => true]);
    }

    // ── Export ────────────────────────────────────────────────────────────────

    public function exportXlsx(Request $req, Response $res): Response
    {
        $bottles = $this->db()->query("SELECT * FROM bottles ORDER BY name")->fetchAll();
        $lines   = ['"Name","Brand","Category","Vintage","ABV","Country","Fill %","Barcode","Notes","Added"'];
        foreach ($bottles as $b) {
            $lines[] = implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', (string)($v ?? '')) . '"',
                [$b['name'],$b['brand'],$b['category'],$b['vintage'],$b['abv'],
                 $b['country'],$b['fill'],$b['barcode'],$b['notes'],substr($b['created_at'],0,10)]
            ));
        }
        $res->getBody()->write(implode("\n", $lines));
        return $res
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="bar-inventory-' . date('Y-m-d') . '.csv"');
    }

    // ── Admin API ─────────────────────────────────────────────────────────────

    public function saveSettings(Request $req, Response $res): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);

        $body    = $req->getParsedBody() ?? [];
        $stmt    = $this->db()->prepare("UPDATE settings SET value = ? WHERE key = ?");
        $allowed = ['app_name','app_logo','theme','theme_custom','require_login',
                    'default_category','default_fill','currency','currency_symbol',
                    'admin_pin','export_include_empty'];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $body)) continue;
            $val = ($key === 'admin_pin' && $body[$key])
                ? password_hash($body[$key], PASSWORD_BCRYPT)
                : $body[$key];
            $stmt->execute([$val, $key]);
        }
        return $this->json($res, ['ok' => true]);
    }

    public function saveFields(Request $req, Response $res): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);

        $body = $req->getParsedBody() ?? [];
        $db   = $this->db();

        if (!empty($body['builtin'])) {
            $stmt = $db->prepare("UPDATE field_config SET label = ?, visible = ? WHERE field_name = ?");
            foreach ($body['builtin'] as $f) {
                $stmt->execute([$f['label'], (int)$f['visible'], $f['field_name']]);
            }
        }

        if (!empty($body['custom'])) {
            foreach ($body['custom'] as $f) {
                if (!empty($f['id'])) {
                    $db->prepare("UPDATE custom_fields SET label = ?, type = ?, enabled = ? WHERE id = ?")
                       ->execute([$f['label'], $f['type'], (int)$f['enabled'], (int)$f['id']]);
                } else {
                    $name = preg_replace('/\W+/', '_', strtolower($f['label']));
                    $db->prepare("INSERT INTO custom_fields (name, label, type, enabled) VALUES (?, ?, ?, 1)")
                       ->execute([$name, $f['label'], $f['type']]);
                }
            }
        }

        if (!empty($body['delete_custom'])) {
            $stmt = $db->prepare("DELETE FROM custom_fields WHERE id = ?");
            foreach ($body['delete_custom'] as $id) {
                $stmt->execute([(int)$id]);
            }
        }

        return $this->json($res, ['ok' => true]);
    }

    public function saveCustomTheme(Request $req, Response $res): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);
        $body = $req->getParsedBody() ?? [];
        $name = trim($body['name'] ?? '');
        $vars = $body['vars'] ?? '{}';
        if (!$name) return $this->json($res, ['error' => 'Name required'], 400);
        $varsJson = is_array($vars) ? json_encode($vars) : $vars;
        $db = $this->db();
        $db->prepare("INSERT INTO custom_themes (name, vars) VALUES (?,?) ON CONFLICT(name) DO UPDATE SET vars=excluded.vars")
           ->execute([$name, $varsJson]);
        $id = (int)($db->lastInsertId() ?: $db->query("SELECT id FROM custom_themes WHERE name=" . $db->quote($name))->fetchColumn());
        // Cache in settings so layout can load without extra query
        $db->prepare("INSERT INTO settings (key,value) VALUES (?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value")
           ->execute(["custom_theme_{$id}", $varsJson]);
        return $this->json($res, ['ok' => true, 'id' => $id, 'name' => $name]);
    }

    public function deleteCustomTheme(Request $req, Response $res, array $args): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);
        $this->db()->prepare("DELETE FROM custom_themes WHERE id = ?")->execute([(int)$args['id']]);
        return $this->json($res, ['ok' => true]);
    }

    public function testSmtp(Request $req, Response $res): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);
        $settings = $this->db()->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        try {
            $mailer = \App\Mailer::fromSettings($settings);
            $mailer->send($settings['smtp_user'], 'Bar Inventory — SMTP Test', '<p>SMTP is working correctly.</p>');
            return $this->json($res, ['ok' => true]);
        } catch (\Exception $e) {
            return $this->json($res, ['error' => $e->getMessage()], 500);
        }
    }

    public function saveCategories(Request $req, Response $res): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);
        $body = $req->getParsedBody() ?? [];
        $cats = array_values(array_filter(array_map('trim', $body['categories'] ?? [])));
        $this->db()->prepare("UPDATE settings SET value = ? WHERE key = 'categories'")->execute([json_encode($cats)]);
        return $this->json($res, ['ok' => true, 'categories' => $cats]);
    }

    public function createUser(Request $req, Response $res): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);

        $b    = $req->getParsedBody() ?? [];
        $hash = password_hash($b['password'] ?? '', PASSWORD_BCRYPT);
        $this->db()->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)")
             ->execute([trim($b['username'] ?? ''), $hash, $b['role'] ?? 'viewer']);
        return $this->json($res, ['id' => (int)$this->db()->lastInsertId()], 201);
    }

    public function updateUser(Request $req, Response $res, array $args): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);

        $b  = $req->getParsedBody() ?? [];
        $id = (int)$args['id'];

        if (!empty($b['password'])) {
            $this->db()->prepare("UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?")
                 ->execute([trim($b['username'] ?? ''), $b['role'] ?? 'viewer', password_hash($b['password'], PASSWORD_BCRYPT), $id]);
        } else {
            $this->db()->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?")
                 ->execute([trim($b['username'] ?? ''), $b['role'] ?? 'viewer', $id]);
        }
        return $this->json($res, ['ok' => true]);
    }

    public function deleteUser(Request $req, Response $res, array $args): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);

        $id = (int)$args['id'];
        if ($id === (int)($_SESSION['user']['id'] ?? 0)) {
            return $this->json($res, ['error' => 'Cannot delete yourself'], 400);
        }
        $this->db()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        return $this->json($res, ['ok' => true]);
    }

    public function uploadLogo(Request $req, Response $res): Response
    {
        if ($this->role() !== 'admin') return $this->json($res, ['error' => 'Forbidden'], 403);

        $files = $req->getUploadedFiles();
        $logo  = $files['logo'] ?? null;
        if (!$logo || $logo->getError() !== UPLOAD_ERR_OK) {
            return $this->json($res, ['error' => 'Upload failed'], 400);
        }
        $ext     = strtolower(pathinfo($logo->getClientFilename(), PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        if (!in_array($ext, $allowed)) {
            return $this->json($res, ['error' => 'Invalid file type'], 400);
        }
        $filename = 'logo.' . $ext;
        $path     = (getenv('DATA_PATH') ?: '/data') . '/uploads/' . $filename;
        $logo->moveTo($path);
        $url = '/uploads/' . $filename . '?v=' . time();
        $this->db()->prepare("UPDATE settings SET value = ? WHERE key = 'app_logo'")->execute([$url]);
        return $this->json($res, ['url' => $url]);
    }
}
