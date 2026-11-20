<?php
/**
 * hotel.php - Fiche détaillée d'un hôtel (US-3, US-4 du cahier des charges)
 */
require 'includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$hotel = $id > 0 ? get_hotel_by_id($id) : null;

if (!$hotel) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hotel ? htmlspecialchars($hotel['nom_etablissement']) : 'Hôtel introuvable'; ?> - Honey Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <?php if (!$hotel): ?>
                <p>Cet hôtel n'existe pas ou n'est plus disponible. <a href="recherche.php">Retour à la recherche</a>.</p>
            <?php else: ?>
                <div class="detail-layout">
                    <div>
                        <div class="detail-photo"><?php if (!empty($hotel['photo'])): ?>
    <img src="img/hotels/<?php echo htmlspecialchars($hotel['photo']); ?>"
         alt="<?php echo htmlspecialchars($hotel['nom_etablissement']); ?>">
<?php else: ?>
    Photo
<?php endif; ?></div>
                        <h1 class="detail-title"><?php echo htmlspecialchars($hotel['nom_etablissement']); ?></h1>
                        <p class="detail-meta"><?php echo htmlspecialchars($hotel['ville']); ?> — Note : 4,5/5 (120 avis)</p>
                        <h3 style="margin-bottom:8px;">Description</h3>
                        <p>Hôtel confortable situé à <?php echo htmlspecialchars($hotel['ville']); ?>, idéal pour un séjour d'affaires ou de tourisme. Équipements : Wi-Fi gratuit, petit-déjeuner inclus, piscine.</p>
                    </div>

                    <div class="booking-box">
                        <p class="price"><?php echo format_ar($hotel['tarif_nuit_moyen']); ?> / nuit</p>
                        <form action="reservation.php" method="GET" id="bookingForm">
                            <input type="hidden" name="hotel_id" value="<?php echo (int) $hotel['id']; ?>">
                            <label for="date_debut">Date d'arrivée</label>
                            <input type="date" id="date_debut" name="date_debut" required>
                            <label for="date_fin">Date de départ</label>
                            <input type="date" id="date_fin" name="date_fin" required>
                            <p class="meta" id="estimation" style="margin-bottom:14px;">Sélectionnez vos dates pour voir le montant estimé.</p>
                            <button type="submit" class="btn btn-primary" style="width:100%;">Réserver</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
    <?php if ($hotel): ?>
    <script>
        // Calcul dynamique du devis (US-4 du cahier des charges)
        (function () {
            var tarif = <?php echo (float) $hotel['tarif_nuit_moyen']; ?>;
            var debut = document.getElementById('date_debut');
            var fin = document.getElementById('date_fin');
            var estimation = document.getElementById('estimation');

            function updateEstimation() {
                if (debut.value && fin.value) {
                    var d1 = new Date(debut.value);
                    var d2 = new Date(fin.value);
                    var nuits = Math.round((d2 - d1) / (1000 * 60 * 60 * 24));
                    if (nuits > 0) {
                        var total = nuits * tarif;
                        estimation.textContent = nuits + ' nuit(s) — Total estimé : ' + total.toLocaleString('fr-FR') + ' Ar';
                    } else {
                        estimation.textContent = 'La date de départ doit être après la date d\'arrivée.';
                    }
                }
            }
            debut.addEventListener('change', updateEstimation);
            fin.addEventListener('change', updateEstimation);
        })();
    </script>
    <?php endif; ?>
</body>
</html>
