<?php
/**
 * Composant Header - e-Booking Honey Group
 * Usage : <?php include 'includes/header.php'; ?>
 */
$page = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">Honey Group</a>

        <button class="burger" id="burgerBtn" aria-label="Ouvrir le menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav">
            <ul>
                <li><a href="index.php" class="<?= $page === 'index.php' ? 'active' : '' ?>">Accueil</a></li>
                <li><a href="recherche.php" class="<?= in_array($page, ['recherche.php', 'hotel.php']) ? 'active' : '' ?>">Hôtels</a></li>
                <li><a href="voitures.php" class="<?= $page === 'voitures.php' ? 'active' : '' ?>">Voitures</a></li>
                <li><a href="admin/login.php" class="<?= $page === 'login.php' ? 'active' : '' ?>">Espace Admin</a></li>
            </ul>
        </nav>
    </div>
</header>
<div class="barre-tricolore"></div>