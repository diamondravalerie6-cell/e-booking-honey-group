<?php
/**
 * reservation.php - Tunnel de réservation (hôtels et véhicules)
 * GET : affiche le récapitulatif + formulaire de coordonnées
 * POST : valide, enregistre la réservation en base, redirige vers la confirmation
 */
require 'includes/functions.php';

$errors = [];
$type = 'hotel';          // 'hotel' ou 'vehicule'
$hotelId = 0;
$vehiculeId = 0;
$dateDebut = '';
$dateFin = '';
$item = null;             // l'hôtel ou le véhicule concerné
$nom = '';
$email = '';

/** Vérifie qu'une chaîne est une vraie date au format AAAA-MM-JJ. */
function is_valid_date(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/** Nombre de jours de location (minimum 1, même jour = 1 jour). */
function days_between_rental(string $debut, string $fin): int
{
    $d1 = new DateTime($debut);
    $d2 = new DateTime($fin);
    return max(1, (int) $d1->diff($d2)->days);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotelId    = (int) ($_POST['hotel_id'] ?? 0);
    $vehiculeId = (int) ($_POST['vehicule_id'] ?? 0);
    $dateDebut  = trim($_POST['date_debut'] ?? '');
    $dateFin    = trim($_POST['date_fin'] ?? '');
    $nom        = trim($_POST['nom'] ?? '');
    $email      = trim($_POST['email'] ?? '');

    $type = $vehiculeId > 0 ? 'vehicule' : 'hotel';
    $item = $type === 'vehicule' ? get_vehicule_by_id($vehiculeId) : get_hotel_by_id($hotelId);

    if (!$item) {
        // Sans article valide, inutile d'afficher le formulaire
        header('Location: ' . ($type === 'vehicule' ? 'voitures.php' : 'recherche.php'));
        exit;
    }
    if (mb_strlen($nom) < 3 || mb_strlen($nom) > 100) {
        $errors[] = "Veuillez saisir votre nom complet (3 à 100 caractères).";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)
        || mb_strlen($email) > 150
        || !preg_match('/\.[a-z]{2,}$/i', $email)
        || preg_match('/\.(comS|con|cmo)$/i', $email)) {
        $errors[] = "L'adresse email est invalide.";
    }
    if (!is_valid_date($dateDebut) || !is_valid_date($dateFin)) {
        $errors[] = "Les dates sont invalides.";
    } elseif ($dateDebut < date('Y-m-d')) {
        $errors[] = "La date de début ne peut pas être dans le passé.";
    } elseif ($type === 'hotel' && $dateFin <= $dateDebut) {
        $errors[] = "La date de départ doit être après la date d'arrivée.";
    } elseif ($type === 'vehicule' && $dateFin < $dateDebut) {
        $errors[] = "La restitution ne peut pas être avant la prise en charge.";
    }

    if (empty($errors)) {
        try {
            $idUtilisateur = find_or_create_utilisateur($nom, $email);
            if ($type === 'vehicule') {
                $idReservation = create_vehicule_reservation($idUtilisateur, $vehiculeId, $dateDebut, $dateFin);
            } else {
                $idReservation = create_hotel_reservation($idUtilisateur, $hotelId, $dateDebut, $dateFin);
            }
            header('Location: confirmation.php?id=' . $idReservation);
            exit;
        } catch (Exception $e) {
            $errors[] = "Une erreur est survenue lors de l'enregistrement de la réservation.";
        }
    }
} else {
    // Affichage initial (GET depuis la fiche hôtel ou la fiche véhicule)
    $hotelId    = (int) ($_GET['hotel_id'] ?? 0);
    $vehiculeId = (int) ($_GET['vehicule_id'] ?? 0);
    $dateDebut  = $_GET['date_debut'] ?? '';
    $dateFin    = $_GET['date_fin'] ?? '';

    $type = $vehiculeId > 0 ? 'vehicule' : 'hotel';
    $item = $type === 'vehicule' ? get_vehicule_by_id($vehiculeId) : get_hotel_by_id($hotelId);

    $datesOk = is_valid_date($dateDebut) && is_valid_date($dateFin)
        && ($type === 'vehicule' ? $dateFin >= $dateDebut : $dateFin > $dateDebut);

    if (!$item || !$datesOk) {
        header('Location: ' . ($type === 'vehicule' ? 'voitures.php' : 'recherche.php'));
        exit;
    }
}

// Calcul de la durée et du total
if ($type === 'vehicule') {
    $duree = days_between_rental($dateDebut, $dateFin);
    $total = $duree * (float) $item['tarif_journalier'];
    $unite = 'jour';
} else {
    $duree = nights_between($dateDebut, $dateFin);
    $total = $duree * (float) $item['tarif_nuit_moyen'];
    $unite = 'nuit';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser votre réservation - Honey Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h1 style="color:var(--color-bleu); margin-bottom:24px;">Finaliser votre réservation</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
                </div>
            <?php endif; ?>

            <div class="tunnel-layout">
                <div class="recap-box">
                    <?php if ($type === 'vehicule'): ?>
                        <h3>Récapitulatif de votre location</h3>
                        <p><?php echo htmlspecialchars($item['modele']); ?> — <?php echo htmlspecialchars($item['categorie_vehicule']); ?>
                           (<?php echo $item['avec_chauffeur'] ? 'avec chauffeur' : 'sans chauffeur'; ?>)</p>
                    <?php else: ?>
                        <h3>Récapitulatif de votre séjour</h3>
                        <p><?php echo htmlspecialchars($item['nom_etablissement']); ?> — <?php echo htmlspecialchars($item['ville']); ?></p>
                    <?php endif; ?>
                    <p>Du <?php echo format_date_fr($dateDebut); ?> au <?php echo format_date_fr($dateFin); ?>
                       (<?php echo $duree; ?> <?php echo $unite; ?><?php echo $duree > 1 ? 's' : ''; ?>)</p>
                    <p class="total">Total : <?php echo format_ar($total); ?></p>
                </div>

                <div class="form-box">
                    <h3>Vos coordonnées</h3>
                    <form method="POST" action="reservation.php">
                        <?php if ($type === 'vehicule'): ?>
                            <input type="hidden" name="vehicule_id" value="<?php echo (int) $vehiculeId; ?>">
                        <?php else: ?>
                            <input type="hidden" name="hotel_id" value="<?php echo (int) $hotelId; ?>">
                        <?php endif; ?>
                        <input type="hidden" name="date_debut" value="<?php echo htmlspecialchars($dateDebut); ?>">
                        <input type="hidden" name="date_fin" value="<?php echo htmlspecialchars($dateFin); ?>">

                        <label for="nom">Nom complet</label>
                        <input type="text" id="nom" name="nom" required minlength="3" maxlength="100"
                               value="<?php echo htmlspecialchars($nom); ?>">

                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required maxlength="150"
                               value="<?php echo htmlspecialchars($email); ?>">

                        <button type="submit" class="btn btn-primary" style="width:100%;">Confirmer la réservation</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
</body>
</html>