<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    public function __construct(private $container) {}

    public function __invoke(Request $request, Handler $handler): Response
    {
        $db       = $this->container->get('db');
        $settings = $db->query("SELECT key,value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Not set up yet — redirect to setup
        if (empty($settings['setup_complete']) || $settings['setup_complete'] !== '1') {
            $path = $request->getUri()->getPath();
            if (!str_starts_with($path, '/setup') && !str_starts_with($path, '/api/health')) {
                return $this->redirect('/setup');
            }
            return $handler->handle($request);
        }

        // Auth not required
        if (empty($settings['require_login']) || $settings['require_login'] === '0') {
            // Still attach a guest session for role checks
            if (empty($_SESSION['user'])) {
                $_SESSION['user'] = ['id' => 0, 'username' => 'guest', 'role' => 'editor'];
            }
            return $handler->handle($request);
        }

        // Auth required — check session
        if (!empty($_SESSION['user']['id'])) {
            return $handler->handle($request);
        }

        // API routes return 401
        if (str_starts_with($request->getUri()->getPath(), '/api/')) {
            $res = new SlimResponse();
            $res->getBody()->write(json_encode(['error' => 'Unauthorised']));
            return $res->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        return $this->redirect('/login?next=' . urlencode($request->getUri()->getPath()));
    }

    private function redirect(string $url): Response
    {
        $res = new SlimResponse();
        return $res->withHeader('Location', $url)->withStatus(302);
    }
}
