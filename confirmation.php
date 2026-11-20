<?php
/**
 * confirmation.php - Confirmation de réservation (US-5, US-6)
 */
require 'includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$reservation = $id > 0 ? get_reservation_details($id) : null;

// Statut lisible à partir de la valeur en base
function libelle_statut($statut) {
    switch ($statut) {
        case 'en_attente': return 'En attente de validation';
        case 'confirmee':  return 'Confirmée';
        case 'annulee':    return 'Annulée';
        default:           return ucfirst(str_replace('_', ' ', $statut));
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation enregistrée - Honey Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <?php if (!$reservation): ?>
                <p>Réservation introuvable. <a href="index.php">Retour à l'accueil</a>.</p>
            <?php else: ?>
                <div class="confirmation-box">
                    <div class="confirmation-icon">&#10003;</div>
                    <h1>Réservation enregistrée !</h1>
                    <p>Votre réservation n°<?php echo (int) $reservation['id']; ?> est en attente de validation par l'établissement.</p>
                    <div class="confirmation-recap">
                        <?php if (!empty($reservation['client'])): ?>
                            <p>
                                <strong>Client :</strong> <?php echo htmlspecialchars($reservation['client']['nom']); ?><br>
                                <strong>Email :</strong> <?php echo htmlspecialchars($reservation['client']['email']); ?>
                            </p>
                        <?php endif; ?>

                        <?php foreach ($reservation['lignes'] as $ligne): ?>
                            <p>
                                <strong><?php echo htmlspecialchars($ligne['hotel_nom'] ?? $ligne['vehicule_modele'] ?? ucfirst($ligne['type_prestation'])); ?></strong>
                                <?php if (!empty($ligne['ville'])): ?>
                                    — <?php echo htmlspecialchars($ligne['ville']); ?>
                                <?php endif; ?>
                                <br>
                                Du <?php echo format_date_fr($ligne['date_debut']); ?>
                                au <?php echo format_date_fr($ligne['date_fin']); ?>
                            </p>
                        <?php endforeach; ?>

                        <p style="margin-top:10px; font-weight:700; color:var(--color-bleu);">Total : <?php echo format_ar($reservation['montant_total']); ?></p>
                        <p class="meta">Statut : <?php echo htmlspecialchars(libelle_statut($reservation['statut'])); ?></p>
                    </div>

                    <div class="confirmation-actions">
                        <a href="recherche.php" class="btn btn-primary">Voir d'autres hôtels</a>
                        <a href="voitures.php" class="btn btn-secondary">Voir les voitures</a>
                        <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>
</html>