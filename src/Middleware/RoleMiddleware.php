<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class RoleMiddleware
{
    public function __construct(private $container, private string $required) {}

    public function __invoke(Request $request, Handler $handler): Response
    {
        $role = $_SESSION['user']['role'] ?? 'viewer';
        $hierarchy = ['viewer' => 0, 'editor' => 1, 'admin' => 2];

        $userLevel     = $hierarchy[$role]     ?? 0;
        $requiredLevel = $hierarchy[$this->required] ?? 99;

        if ($userLevel < $requiredLevel) {
            if (str_starts_with($request->getUri()->getPath(), '/api/')) {
                $res = new SlimResponse();
                $res->getBody()->write(json_encode(['error' => 'Forbidden']));
                return $res->withStatus(403)->withHeader('Content-Type', 'application/json');
            }
            return (new SlimResponse())->withHeader('Location', '/')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
