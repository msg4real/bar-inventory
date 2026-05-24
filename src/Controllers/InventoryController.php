<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class InventoryController
{
    use Renderable;

    public function __construct(private $container) {}

    public function index(Request $req, Response $res): Response
    {
        $db      = $this->container->get('db');
        $bottles = $db->query("SELECT * FROM bottles ORDER BY name ASC")->fetchAll();
        $fields  = $db->query("SELECT * FROM field_config ORDER BY sort_order")->fetchAll();
        $custom  = $db->query("SELECT * FROM custom_fields WHERE enabled=1 ORDER BY sort_order")->fetchAll();
        return $this->render($res, 'inventory/index', compact('bottles', 'fields', 'custom'));
    }

    public function scanPage(Request $req, Response $res): Response
    {
        return $this->render($res, 'inventory/scan', []);
    }

    public function show(Request $req, Response $res, array $args): Response
    {
        $db     = $this->container->get('db');
        $id     = (int)$args['id'];
        $bottle = $db->query("SELECT * FROM bottles WHERE id = $id")->fetch();
        if (!$bottle) {
            return $res->withHeader('Location', '/')->withStatus(302);
        }
        $bottle['custom_data'] = json_decode($bottle['custom_data'] ?: '{}', true);
        return $this->render($res, 'inventory/show', compact('bottle'));
    }
}
