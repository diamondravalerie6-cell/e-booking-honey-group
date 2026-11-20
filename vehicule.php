<?php
/**
 * vehicule.php - Fiche détaillée d'un véhicule
 */
require 'includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$vehicule = $id > 0 ? get_vehicule_by_id($id) : null;

// Dates éventuellement reprises depuis la recherche
$debut = $_GET['debut'] ?? '';
$fin = $_GET['fin'] ?? '';

if (!$vehicule) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $vehicule ? htmlspecialchars($vehicule['modele']) : 'Véhicule introuvable'; ?> - Honey Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <?php if (!$vehicule): ?>
                <p>Ce véhicule n'existe pas ou n'est plus disponible. <a href="voitures.php">Retour à la recherche</a>.</p>
            <?php else: ?>
                <div class="detail-layout">
                    <div>
                        <div class="detail-photo">
                            <?php if (!empty($vehicule['photo'])): ?>
                                <img src="img/voitures/<?php echo htmlspecialchars($vehicule['photo']); ?>"
                                     alt="<?php echo htmlspecialchars($vehicule['modele']); ?>">
                            <?php else: ?>
                                Photo
                            <?php endif; ?>
                        </div>
                        <h1 class="detail-title"><?php echo htmlspecialchars($vehicule['modele']); ?></h1>
                        <p class="detail-meta">
                            <?php echo htmlspecialchars($vehicule['categorie_vehicule']); ?>
                            — <?php echo (int) $vehicule['nb_places']; ?> places
                            — <?php echo $vehicule['avec_chauffeur'] ? 'avec chauffeur' : 'sans chauffeur'; ?>
                        </p>
                        <h3 style="margin-bottom:8px;">Description</h3>
                        <p>Véhicule de type <?php echo htmlspecialchars($vehicule['categorie_vehicule']); ?> proposé par <?php echo htmlspecialchars($vehicule['nom_entreprise']); ?>, adapté aux routes de Madagascar. <?php echo $vehicule['avec_chauffeur'] ? 'Chauffeur professionnel inclus.' : 'Location sans chauffeur.'; ?></p>
                    </div>

                    <div class="booking-box">
                        <p class="price"><?php echo format_ar($vehicule['tarif_journalier']); ?> / jour</p>
                        <form action="reservation.php" method="GET" id="bookingForm">
                            <input type="hidden" name="vehicule_id" value="<?php echo (int) $vehicule['id']; ?>">
                            <label for="date_debut">Prise en charge</label>
                            <input type="date" id="date_debut" name="date_debut" value="<?php echo htmlspecialchars($debut); ?>" required>
                            <label for="date_fin">Restitution</label>
                            <input type="date" id="date_fin" name="date_fin" value="<?php echo htmlspecialchars($fin); ?>" required>
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
    <?php if ($vehicule): ?>
    <script>
        // Calcul dynamique du devis
        (function () {
            var tarif = <?php echo (float) $vehicule['tarif_journalier']; ?>;
            var debut = document.getElementById('date_debut');
            var fin = document.getElementById('date_fin');
            var estimation = document.getElementById('estimation');

            function updateEstimation() {
                if (debut.value && fin.value) {
                    var d1 = new Date(debut.value);
                    var d2 = new Date(fin.value);
                    if (d2 < d1) {
                        estimation.textContent = 'La restitution doit être après la prise en charge.';
                        return;
                    }
                    var jours = Math.max(1, Math.round((d2 - d1) / (1000 * 60 * 60 * 24)));
                    var total = jours * tarif;
                    estimation.textContent = jours + ' jour(s) — Total estimé : ' + total.toLocaleString('fr-FR') + ' Ar';
                }
            }
            debut.addEventListener('change', updateEstimation);
            fin.addEventListener('change', updateEstimation);
            updateEstimation();
        })();
    </script>
    <?php endif; ?>
</body>
</html>