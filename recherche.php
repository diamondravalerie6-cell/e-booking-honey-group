<?php
/**
 * recherche.php - Résultats de recherche d'hôtels
 */
require 'includes/functions.php';

$ville = isset($_GET['ville']) ? trim(filter_var($_GET['ville'], FILTER_SANITIZE_SPECIAL_CHARS)) : '';
$arrivee = $_GET['arrivee'] ?? '';
$depart = $_GET['depart'] ?? '';

$hotels = get_hotels($ville !== '' ? $ville : null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Résultats de recherche d'hôtels - Honey Group">
    <title>Résultats de recherche - Honey Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <form class="search-bar-slim" action="recherche.php" method="GET">
                <div class="field">
                    <label for="ville">Ville / région</label>
                    <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($ville); ?>">
                </div>
                <div class="field">
                    <label for="arrivee">Date d'arrivée</label>
                    <input type="date" id="arrivee" name="arrivee" value="<?php echo htmlspecialchars($arrivee); ?>">
                </div>
                <div class="field">
                    <label for="depart">Date de départ</label>
                    <input type="date" id="depart" name="depart" value="<?php echo htmlspecialchars($depart); ?>">
                </div>
                <div class="field" style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>

            <div class="search-layout">
                <aside class="filters">
                    <h3>Filtres</h3>
                    <div class="filter-group">
                        <label>Ville/région</label>
                        <p class="meta"><?php echo $ville !== '' ? htmlspecialchars($ville) : 'Toutes'; ?></p>
                    </div>
                    <div class="filter-group">
                        <label>Prix</label>
                        <p class="meta">Tous les tarifs</p>
                    </div>
                    <div class="filter-group">
                        <label>Catégorie</label>
                        <p class="meta">Toutes catégories</p>
                    </div>
                    <div class="filter-group">
                        <label>Services</label>
                        <p class="meta">Tous services</p>
                    </div>
                </aside>

                <div class="results-list">
                    <p class="meta"><?php echo count($hotels); ?> hôtel(s) trouvé(s)<?php echo $ville !== '' ? ' pour « ' . htmlspecialchars($ville) . ' »' : ''; ?></p>

                    <?php if (empty($hotels)): ?>
                        <p>Aucun hôtel ne correspond à votre recherche.</p>
                    <?php endif; ?>

                    <?php foreach ($hotels as $hotel): ?>
                        <a href="hotel.php?id=<?php echo (int) $hotel['id']; ?>" class="result-card">
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
                                <p class="meta"><?php echo htmlspecialchars($hotel['ville']); ?> — proposé par <?php echo htmlspecialchars($hotel['nom_entreprise']); ?></p>
                                <p class="price">à partir de <?php echo format_ar($hotel['tarif_nuit_moyen']); ?> / nuit</p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>
</html>
