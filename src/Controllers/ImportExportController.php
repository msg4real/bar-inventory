<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ImportExportController
{
    use Renderable;

    public function __construct(private $container) {}

    private function db() { return $this->container->get('db'); }

    // ── Export page ──────────────────────────────────────────────────────────

    public function exportPage(Request $req, Response $res): Response
    {
        $db       = $this->db();
        $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $count    = $db->query("SELECT COUNT(*) FROM bottles")->fetchColumn();
        $rcount   = $db->query("SELECT COUNT(*) FROM recipes")->fetchColumn();
        return $this->render($res, 'export/index', compact('settings', 'count', 'rcount'));
    }

    // ── Import page ──────────────────────────────────────────────────────────

    public function importPage(Request $req, Response $res): Response
    {
        $db       = $this->db();
        $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        return $this->render($res, 'import/index', compact('settings'));
    }

    // ── Import bottles API ───────────────────────────────────────────────────

    public function importBottles(Request $req, Response $res): Response
    {
        $body    = (function() use ($req) { $p = $req->getParsedBody(); if (is_array($p) && !empty($p)) return $p; $r = (string)$req->getBody(); return $r ? (json_decode($r,true) ?: []) : []; })();
        $rows    = $body['rows']   ?? [];
        $actions = $body['actions'] ?? []; // ['skip'|'overwrite'|'new'] keyed by index

        $db   = $this->db();
        $done = 0; $skipped = 0;

        $insertStmt = $db->prepare("
            INSERT INTO bottles (name, brand, category, vintage, abv, country, fill, barcode, notes, custom_data, created_by)
            VALUES (:name,:brand,:category,:vintage,:abv,:country,:fill,:barcode,:notes,:custom_data,:created_by)
        ");
        $updateStmt = $db->prepare("
            UPDATE bottles SET brand=:brand, category=:category, vintage=:vintage,
                abv=:abv, country=:country, fill=:fill, barcode=:barcode,
                notes=:notes, custom_data=:custom_data, updated_at=datetime('now')
            WHERE id=:id
        ");

        foreach ($rows as $i => $row) {
            $action = $actions[$i] ?? 'new';
            if ($action === 'skip') { $skipped++; continue; }

            $params = [
                ':name'        => trim($row['name']     ?? ''),
                ':brand'       => trim($row['brand']    ?? ''),
                ':category'    => trim($row['category'] ?? 'Other'),
                ':vintage'     => trim($row['vintage']  ?? ''),
                ':abv'         => ($row['abv'] ?? '') !== '' ? (float)$row['abv'] : null,
                ':country'     => trim($row['country']  ?? ''),
                ':fill'        => (int)($row['fill']    ?? 100),
                ':barcode'     => trim($row['barcode']  ?? ''),
                ':notes'       => trim($row['notes']    ?? ''),
                ':custom_data' => '{}',
                ':created_by'  => $_SESSION['user']['id'] ?? null,
            ];

            if ($action === 'overwrite' && !empty($row['_existing_id'])) {
                $p = $params;
                unset($p[':custom_data'], $p[':created_by']);
                $p[':id'] = (int)$row['_existing_id'];
                $updateStmt->execute($p);
            } else {
                $insertStmt->execute($params);
            }
            $done++;
        }

        $res->getBody()->write(json_encode(['imported' => $done, 'skipped' => $skipped]));
        return $res->withHeader('Content-Type', 'application/json');
    }

    // ── Import recipes API ───────────────────────────────────────────────────

    public function importRecipes(Request $req, Response $res): Response
    {
        $body    = (function() use ($req) { $p = $req->getParsedBody(); if (is_array($p) && !empty($p)) return $p; $r = (string)$req->getBody(); return $r ? (json_decode($r,true) ?: []) : []; })();
        $rows    = $body['rows']    ?? [];
        $actions = $body['actions'] ?? [];

        $db   = $this->db();
        $done = 0; $skipped = 0;

        foreach ($rows as $i => $row) {
            $action = $actions[$i] ?? 'new';
            if ($action === 'skip') { $skipped++; continue; }

            if ($action === 'overwrite' && !empty($row['_existing_id'])) {
                $db->prepare("
                    UPDATE recipes SET name=?,description=?,category=?,glass=?,garnish=?,instructions=?,updated_at=datetime('now')
                    WHERE id=?
                ")->execute([
                    $row['name']??'', $row['description']??'', $row['category']??'Tiki',
                    $row['glass']??'', $row['garnish']??'', $row['instructions']??'',
                    (int)$row['_existing_id']
                ]);
                $db->prepare("DELETE FROM recipe_ingredients WHERE recipe_id=?")->execute([(int)$row['_existing_id']]);
                $rid = (int)$row['_existing_id'];
            } else {
                $db->prepare("
                    INSERT INTO recipes (name,description,category,glass,garnish,instructions,created_by)
                    VALUES (?,?,?,?,?,?,?)
                ")->execute([
                    $row['name']??'', $row['description']??'', $row['category']??'Tiki',
                    $row['glass']??'', $row['garnish']??'', $row['instructions']??'',
                    $_SESSION['user']['id'] ?? null
                ]);
                $rid = (int)$db->lastInsertId();
            }

            // Parse ingredients string "2 oz Rum; 1 oz Lime Juice"
            $ings = array_filter(array_map('trim', explode(';', $row['ingredients'] ?? '')));
            foreach ($ings as $idx => $ing) {
                $db->prepare("INSERT INTO recipe_ingredients (recipe_id,name,amount,unit,sort_order) VALUES (?,?,?,?,?)")
                   ->execute([$rid, $ing, '', '', $idx]);
            }
            $done++;
        }

        $res->getBody()->write(json_encode(['imported' => $done, 'skipped' => $skipped]));
        return $res->withHeader('Content-Type', 'application/json');
    }
}
