<?php
/**
 * index.php - Accueil e-Booking Honey Group
 */
require 'includes/functions.php';

$hotelsPopulaires = array_slice(get_hotels(), 0, 3);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Honey Group - Réservez votre hôtel ou véhicule de location à Madagascar.">
    <title>Honey Group - Réservation d'hôtels à Madagascar</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="hero">
        <div class="container">
            <h1>Trouvez votre hôtel à Madagascar</h1>
            <form class="search-bar" action="recherche.php" method="GET">
                <div class="field">
                    <label for="ville">Ville / région</label>
                    <input type="text" id="ville" name="ville" placeholder="Ex: Antananarivo">
                </div>
                <div class="field">
                    <label for="arrivee">Date d'arrivée</label>
                    <input type="date" id="arrivee" name="arrivee">
                </div>
                <div class="field">
                    <label for="depart">Date de départ</label>
                    <input type="date" id="depart" name="depart">
                </div>
                <div class="field" style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2>Nos hôtels mis en avant</h2>
            <div class="card-grid">
                <?php if (empty($hotelsPopulaires)): ?>
                    <p>Aucun hôtel disponible pour le moment.</p>
                <?php endif; ?>
                <?php foreach ($hotelsPopulaires as $hotel): ?>
                    <a href="hotel.php?id=<?php echo (int) $hotel['id']; ?>" class="card">
                        <div class="card-image">
    <?php if (!empty($hotel['photo'])): ?>
        <img src="img/hotels/<?php echo htmlspecialchars($hotel['photo']); ?>"
             alt="<?php echo htmlspecialchars($hotel['nom_etablissement']); ?>">
    <?php else: ?>
        Photo
    <?php endif; ?>
</div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($hotel['nom_etablissement']); ?></h3>
                            <p class="meta"><?php echo htmlspecialchars($hotel['ville']); ?></p>
                            <p class="price">à partir de <?php echo format_ar($hotel['tarif_nuit_moyen']); ?> / nuit</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>
</html>
