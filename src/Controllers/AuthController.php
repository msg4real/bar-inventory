<?php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class AuthController
{
    public function __construct(private $container) {}

    private function db() { return $this->container->get('db'); }

    private function render(Response $res, string $tpl, array $data = []): Response
    {
        $settings = $this->db()->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $data     = array_merge(['settings' => $settings, 'user' => $_SESSION['user'] ?? null], $data);
        extract($data);
        ob_start();
        require __DIR__ . "/../../templates/{$tpl}.php";
        $res->getBody()->write(ob_get_clean());
        return $res->withHeader('Content-Type', 'text/html');
    }

    // ── Setup ─────────────────────────────────────────────────────────────────

    public function setupPage(Request $req, Response $res): Response
    {
        $complete = $this->db()->query("SELECT value FROM settings WHERE key = 'setup_complete'")->fetchColumn();
        if ($complete === '1') {
            return (new SlimResponse())->withHeader('Location', '/')->withStatus(302);
        }
        return $this->render($res, 'auth/setup', ['error' => null]);
    }

    public function setupPost(Request $req, Response $res): Response
    {
        $body    = $req->getParsedBody();
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';
        $confirm  = $body['confirm']  ?? '';
        $pin      = $body['pin']      ?? '';
        $appName  = trim($body['app_name'] ?? 'Bar Inventory');

        if (!$username || strlen($password) < 6) {
            return $this->render($res, 'auth/setup', ['error' => 'Username required and password must be at least 6 characters.']);
        }
        if ($password !== $confirm) {
            return $this->render($res, 'auth/setup', ['error' => 'Passwords do not match.']);
        }

        $db   = $this->db();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")->execute([$username, $hash]);
        $userId = $db->lastInsertId();

        $db->prepare("UPDATE settings SET value = ? WHERE key = 'setup_complete'")->execute(['1']);
        $db->prepare("UPDATE settings SET value = ? WHERE key = 'app_name'")->execute([$appName]);
        if ($pin) {
            $db->prepare("UPDATE settings SET value = ? WHERE key = 'admin_pin'")->execute([password_hash($pin, PASSWORD_BCRYPT)]);
        }

        $_SESSION['user'] = ['id' => (int)$userId, 'username' => $username, 'role' => 'admin'];
        return (new SlimResponse())->withHeader('Location', '/')->withStatus(302);
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function loginPage(Request $req, Response $res): Response
    {
        return $this->render($res, 'auth/login', [
            'error' => null,
            'next'  => $req->getQueryParams()['next'] ?? '/',
        ]);
    }

    public function loginPost(Request $req, Response $res): Response
    {
        $body     = $req->getParsedBody();
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';
        $next     = $body['next'] ?? '/';

        $stmt = $this->db()->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->render($res, 'auth/login', ['error' => 'Invalid username or password.', 'next' => $next]);
        }

        $this->db()->prepare("UPDATE users SET last_login = datetime('now') WHERE id = ?")->execute([$user['id']]);
        $_SESSION['user'] = ['id' => (int)$user['id'], 'username' => $user['username'], 'role' => $user['role']];

        return (new SlimResponse())->withHeader('Location', $next)->withStatus(302);
    }

    public function logout(Request $req, Response $res): Response
    {
        session_destroy();
        return (new SlimResponse())->withHeader('Location', '/login')->withStatus(302);
    }

    // ── Forgot password ───────────────────────────────────────────────────────

    public function forgotPage(Request $req, Response $res): Response
    {
        return $this->render($res, 'auth/forgot', ['error' => null, 'sent' => false]);
    }

    public function forgotPost(Request $req, Response $res): Response
    {
        $body  = $req->getParsedBody();
        $email = trim($body['email'] ?? '');

        // Always show success to avoid user enumeration
        if (!$email) {
            return $this->render($res, 'auth/forgot', ['error' => null, 'sent' => true]);
        }

        $db   = $this->db();
        $user = $db->prepare("SELECT * FROM users WHERE username = ?")->execute([$email]) ? null : null;
        // Try username OR email field
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR username = ?");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $db->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
            $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)")
               ->execute([$user['id'], $token, $expires]);

            $settings = $db->query("SELECT key, value FROM settings")->fetchAll(\PDO::FETCH_KEY_PAIR);
            $appName  = $settings['app_name'] ?? 'Bar Inventory';
            $appUrl   = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $link     = "$appUrl/reset-password?token=$token";

            try {
                $mailer = \App\Mailer::fromSettings($settings);
                $mailer->send($email, "Reset your $appName password", "
                    <div style='font-family:sans-serif;max-width:480px;margin:0 auto;padding:2rem'>
                        <h2 style='color:#c8862a'>$appName</h2>
                        <p>Hi {$user['username']},</p>
                        <p>Someone requested a password reset for your account. Click the button below to set a new password. This link expires in <strong>1 hour</strong>.</p>
                        <p style='margin:2rem 0'>
                            <a href='$link' style='background:#c8862a;color:#1a0e00;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block'>Reset Password</a>
                        </p>
                        <p style='color:#888;font-size:13px'>If you didn't request this, ignore this email — your password won't change.</p>
                        <p style='color:#888;font-size:12px'>Or copy this link: $link</p>
                    </div>
                ");
            } catch (\Exception $e) {
                // Log but don't reveal to user
                error_log("Password reset email failed: " . $e->getMessage());
            }
        }

        return $this->render($res, 'auth/forgot', ['error' => null, 'sent' => true]);
    }

    public function resetPage(Request $req, Response $res): Response
    {
        $token = $req->getQueryParams()['token'] ?? '';
        $valid = $this->validateResetToken($token);
        return $this->render($res, 'auth/reset', [
            'token' => $token,
            'valid' => $valid,
            'error' => null,
            'done'  => false,
        ]);
    }

    public function resetPost(Request $req, Response $res): Response
    {
        $body     = $req->getParsedBody();
        $token    = $body['token']    ?? '';
        $password = $body['password'] ?? '';
        $confirm  = $body['confirm']  ?? '';

        $reset = $this->validateResetToken($token);
        if (!$reset) {
            return $this->render($res, 'auth/reset', ['token' => $token, 'valid' => false, 'error' => 'Invalid or expired link.', 'done' => false]);
        }
        if (strlen($password) < 6) {
            return $this->render($res, 'auth/reset', ['token' => $token, 'valid' => true, 'error' => 'Password must be at least 6 characters.', 'done' => false]);
        }
        if ($password !== $confirm) {
            return $this->render($res, 'auth/reset', ['token' => $token, 'valid' => true, 'error' => 'Passwords do not match.', 'done' => false]);
        }

        $db   = $this->db();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $reset['user_id']]);
        $db->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);

        return $this->render($res, 'auth/reset', ['token' => '', 'valid' => true, 'error' => null, 'done' => true]);
    }

    private function validateResetToken(string $token): array|false
    {
        if (!$token) return false;
        $stmt = $this->db()->prepare("
            SELECT * FROM password_resets
            WHERE token = ? AND used = 0 AND expires_at > datetime('now')
        ");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: false;
    }

    // ── Admin password reset ──────────────────────────────────────────────────

    public function adminResetPassword(Request $req, Response $res, array $args): Response
    {
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            return (new SlimResponse())->withStatus(403);
        }
        $body     = $req->getParsedBody();
        $password = $body['password'] ?? '';
        if (strlen($password) < 6) {
            $res->getBody()->write(json_encode(['error' => 'Password must be at least 6 characters']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->db()->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, (int)$args['id']]);
        $res->getBody()->write(json_encode(['ok' => true]));
        return $res->withHeader('Content-Type', 'application/json');
    }
}
