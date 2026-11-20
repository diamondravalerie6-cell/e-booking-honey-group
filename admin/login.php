<?php
/**
 * admin/login.php - Connexion à l'espace administrateur
 */
require '../includes/functions.php';
require '../includes/auth.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Session expirée, veuillez réessayer.';
    } elseif (($wait = login_lock_remaining()) > 0) {
        $error = 'Trop de tentatives. Réessayez dans ' . ceil($wait / 60) . ' minute(s).';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (admin_login($email, $password)) {
            header('Location: index.php');
            exit;
        }
        // Message volontairement identique que l'email ou le mot de passe soit faux
        $error = 'Identifiants incorrects.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion administrateur - Honey Group</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-inner">
            <a href="../index.php" class="logo">Honey Group</a>
        </div>
    </header>
    <div class="barre-tricolore"></div>

    <section class="section">
        <div class="container">
            <div class="auth-box form-box">
                <h1 class="auth-title">Espace administrateur</h1>
                <p class="meta auth-subtitle">Connectez-vous pour accéder au tableau de bord.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <?php echo csrf_field(); ?>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required autofocus autocomplete="username">

                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">

                    <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                </form>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</body>
</html>