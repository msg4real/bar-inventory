<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AdminController
{
    use Renderable;

    public function __construct(private $container) {}

    public function index(Request $req, Response $res): Response
    {
        $db       = $this->container->get('db');
        $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $pin      = $settings['admin_pin'] ?? '';
        $needsPin = $pin && empty($_SESSION['admin_unlocked']);
        return $this->render($res, 'admin/index', compact('settings', 'needsPin'));
    }

    public function verifyPin(Request $req, Response $res): Response
    {
        $db   = $this->container->get('db');
        $pin  = $db->query("SELECT value FROM settings WHERE key = 'admin_pin'")->fetchColumn();
        $body = $req->getParsedBody();

        if ($pin && password_verify($body['pin'] ?? '', $pin)) {
            $_SESSION['admin_unlocked'] = true;
            $payload = json_encode(['ok' => true]);
        } else {
            $payload = json_encode(['error' => 'Incorrect PIN']);
        }

        $res->getBody()->write($payload);
        return $res->withHeader('Content-Type', 'application/json');
    }

    public function fields(Request $req, Response $res): Response
    {
        $db       = $this->container->get('db');
        $builtin  = $db->query("SELECT * FROM field_config ORDER BY sort_order")->fetchAll();
        $custom   = $db->query("SELECT * FROM custom_fields ORDER BY sort_order")->fetchAll();
        $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        return $this->render($res, 'admin/fields', compact('builtin', 'custom', 'settings'));
    }

    public function users(Request $req, Response $res): Response
    {
        $db    = $this->container->get('db');
        $users = $db->query("SELECT id, username, role, created_at, last_login FROM users ORDER BY username")->fetchAll();
        return $this->render($res, 'admin/users', compact('users'));
    }

    public function emailSettings(Request $req, Response $res): Response
    {
        $db       = $this->container->get('db');
        $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        return $this->render($res, 'admin/email', compact('settings'));
    }

    public function theme(Request $req, Response $res): Response
    {
        $db           = $this->container->get('db');
        $settings     = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $customThemes = $db->query("SELECT * FROM custom_themes ORDER BY name")->fetchAll();
        return $this->render($res, 'admin/theme', compact('settings', 'customThemes'));
    }

    public function branding(Request $req, Response $res): Response
    {
        $db       = $this->container->get('db');
        $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        return $this->render($res, 'admin/branding', compact('settings'));
    }

}

