<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class RecipeController
{
    use Renderable;

    public function __construct(private $container) {}

    public function index(Request $req, Response $res): Response
    {
        $db      = $this->container->get('db');
        $recipes = $db->query("SELECT * FROM recipes ORDER BY name ASC")->fetchAll();
        foreach ($recipes as &$r) {
            $stmt = $db->prepare("
                SELECT ri.*, b.name AS bottle_name, b.fill AS bottle_fill
                FROM recipe_ingredients ri
                LEFT JOIN bottles b ON b.id = ri.bottle_id
                WHERE ri.recipe_id = ?
                ORDER BY ri.sort_order
            ");
            $stmt->execute([$r['id']]);
            $r['ingredients'] = $stmt->fetchAll();
        }
        $bottles = $db->query("SELECT id, name FROM bottles ORDER BY name")->fetchAll();
        return $this->render($res, 'inventory/recipes', compact('recipes', 'bottles'));
    }

    public function show(Request $req, Response $res, array $args): Response
    {
        $db     = $this->container->get('db');
        $id     = (int)$args['id'];
        $recipe = $db->query("SELECT * FROM recipes WHERE id = $id")->fetch();
        if (!$recipe) {
            return $res->withHeader('Location', '/recipes')->withStatus(302);
        }
        $stmt = $db->prepare("
            SELECT ri.*, b.name AS bottle_name, b.fill AS bottle_fill
            FROM recipe_ingredients ri
            LEFT JOIN bottles b ON b.id = ri.bottle_id
            WHERE ri.recipe_id = ?
            ORDER BY ri.sort_order
        ");
        $stmt->execute([$id]);
        $recipe['ingredients'] = $stmt->fetchAll();
        return $this->render($res, 'inventory/recipe_show', compact('recipe'));
    }
}
