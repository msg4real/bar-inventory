<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

trait Renderable
{
    private function render(Response $res, string $tpl, array $data = []): Response
    {
        $db       = $this->container->get('db');
        $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);

        $user    = $_SESSION['user'] ?? null;
        $canEdit = in_array($user['role'] ?? 'viewer', ['admin', 'editor']);
        $isAdmin = ($user['role'] ?? '') === 'admin';

        $data = array_merge(
            compact('settings', 'user', 'canEdit', 'isAdmin'),
            $data
        );

        extract($data);

        ob_start();
        require __DIR__ . "/../../templates/{$tpl}.php";
        $content = ob_get_clean();

        ob_start();
        require __DIR__ . '/../../templates/partials/layout.php';
        $res->getBody()->write(ob_get_clean());

        return $res->withHeader('Content-Type', 'text/html');
    }
}
