<?php
/**
 * Authentification administrateur.
 * - Sessions PHP avec cookie HttpOnly + SameSite
 * - Mots de passe vérifiés avec password_verify() (hachage bcrypt)
 * - Jeton CSRF pour les formulaires sensibles
 * - Limitation basique des tentatives de connexion (par session)
 */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // le cookie disparaît à la fermeture du navigateur
        'path'     => '/',
        'httponly' => true,       // inaccessible au JavaScript (protège contre le vol par XSS)
        'samesite' => 'Lax',
        // 'secure' => true,      // À activer dès que le site est en HTTPS
    ]);
    session_start();
}

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 300;

/* ---------- Protection CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return !empty($_SESSION['csrf_token']) && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/* ---------- Limitation des tentatives ---------- */

/** Secondes restantes avant de pouvoir réessayer (0 = pas de blocage). */
function login_lock_remaining(): int
{
    $until = $_SESSION['login_locked_until'] ?? 0;
    return max(0, $until - time());
}

/* ---------- Connexion / déconnexion ---------- */

/** Tente de connecter un administrateur. Retourne true en cas de succès. */
function admin_login(string $email, string $password): bool
{
    if (login_lock_remaining() > 0) {
        return false;
    }

    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'SELECT id, nom, mot_de_passe FROM utilisateurs WHERE email = :email AND role = \'admin\' LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();

    if ($admin) {
        $ok = password_verify($password, $admin['mot_de_passe']);
    } else {
        // Même durée de calcul si le compte n'existe pas (évite de révéler quels emails sont admin)
        password_verify($password, password_hash('inutile', PASSWORD_DEFAULT));
        $ok = false;
    }

    if ($ok) {
        session_regenerate_id(true);   // empêche la fixation de session
        $_SESSION['admin_id']  = (int) $admin['id'];
        $_SESSION['admin_nom'] = $admin['nom'];
        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
        return true;
    }

    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
        $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
        $_SESSION['login_attempts'] = 0;
    }
    return false;
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

/** À appeler en tête de chaque page admin : redirige vers la connexion si besoin. */
function require_admin(): void
{
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
