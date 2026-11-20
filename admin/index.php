<?php
/**
 * admin/index.php - Tableau de bord administrateur (US-11, US-12, US-13)
 * Accès réservé aux administrateurs connectés.
 */
require '../includes/functions.php';
require '../includes/auth.php';

require_admin();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$message = null;
$messageType = 'success';

// Message "flash" laissé par l'action précédente (évite le renvoi du formulaire au rafraîchissement)
if (!empty($_SESSION['flash_admin'])) {
    $message = $_SESSION['flash_admin']['message'];
    $messageType = $_SESSION['flash_admin']['type'];
    unset($_SESSION['flash_admin']);
}

/* ---------- Traitement des actions (POST) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flashMessage = null;
    $flashType = 'success';

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $flashMessage = 'Session expirée. Veuillez réessayer.';
        $flashType = 'error';

    } elseif (isset($_POST['id_reservation'], $_POST['action_reservation'])) {
        // Valider / annuler une réservation
        $statutsAutorises = [
            'valider' => 'confirmee',
            'annuler' => 'annulee',
        ];
        $action = $_POST['action_reservation'];

        if (isset($statutsAutorises[$action])) {
            $ok = update_reservation_statut((int) $_POST['id_reservation'], $statutsAutorises[$action]);
            if ($ok) {
                $flashMessage = $action === 'valider'
                    ? 'Réservation confirmée avec succès.'
                    : 'Réservation annulée avec succès.';
            } else {
                $flashMessage = 'Action impossible. Vérifiez le statut de la réservation.';
                $flashType = 'error';
            }
        } else {
            $flashMessage = 'Action non autorisée.';
            $flashType = 'error';
        }

    } elseif (isset($_POST['id_partenaire'], $_POST['action'])) {
        // Valider / refuser un partenaire
        $statut = $_POST['action'] === 'valider' ? 'valide' : 'refuse';
        $ok = update_partenaire_statut((int) $_POST['id_partenaire'], $statut);
        if ($ok) {
            $flashMessage = $statut === 'valide' ? 'Partenaire validé avec succès.' : 'Partenaire refusé.';
        } else {
            $flashMessage = 'Impossible de modifier le statut du partenaire.';
            $flashType = 'error';
        }
    }

    if ($flashMessage !== null) {
        $_SESSION['flash_admin'] = ['message' => $flashMessage, 'type' => $flashType];
    }
    header('Location: index.php');
    exit;
}

/* ---------- Données affichées ---------- */
$kpis = get_admin_kpis();
$demandes = get_partenaires_en_attente();
$reservations = get_admin_reservations();

$nbEnAttente = count(array_filter($reservations, function ($r) {
    return $r['statut'] === 'en_attente';
}));

$libellesStatut = [
    'en_attente' => 'En attente',
    'confirmee'  => 'Confirmée',
    'annulee'    => 'Annulée',
];
$libellesActivite = [
    'hotel'            => 'Hôtel',
    'location_voiture' => 'Loueur de voitures',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur - Honey Group</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-inner">
            <a href="../index.php" class="logo">Honey Group</a>
            <nav class="admin-nav">
                <span class="meta">Connecté : <?php echo htmlspecialchars($_SESSION['admin_nom'] ?? 'Administrateur'); ?></span>
                <a href="../index.php" class="btn btn-secondary btn-small">Voir le site</a>
                <form method="POST" action="logout.php" class="inline-form">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-secondary btn-small">Se déconnecter</button>
                </form>
            </nav>
        </div>
    </header>
    <div class="barre-tricolore"></div>

    <section class="section admin-dashboard">
        <div class="container">
            <h1 class="page-title">Tableau de bord administrateur</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <p class="label">Chiffre d'affaires global</p>
                    <p class="value"><?php echo format_ar($kpis['chiffre_affaires']); ?></p>
                </div>
                <div class="kpi-card">
                    <p class="label">Réservations actives</p>
                    <p class="value"><?php echo (int) $kpis['reservations_actives']; ?></p>
                </div>
                <div class="kpi-card commission">
                    <p class="label">Commission totale générée</p>
                    <p class="value"><?php echo format_ar($kpis['commission_totale']); ?></p>
                </div>
            </div>

            <h2>Demandes de partenaires en attente</h2>

            <?php if (empty($demandes)): ?>
                <p>Aucune demande en attente pour le moment.</p>
            <?php endif; ?>

            <?php foreach ($demandes as $demande): ?>
                <div class="partner-request">
                    <div>
                        <strong><?php echo htmlspecialchars($demande['nom_entreprise']); ?></strong>
                        — <?php echo htmlspecialchars($libellesActivite[$demande['type_activite']] ?? $demande['type_activite']); ?>
                        — <?php echo htmlspecialchars($demande['email']); ?>
                    </div>
                    <div class="actions">
                        <form method="POST" action="index.php" class="inline-form">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id_partenaire" value="<?php echo (int) $demande['id']; ?>">
                            <input type="hidden" name="action" value="valider">
                            <button type="submit" class="btn btn-success btn-small">Valider</button>
                        </form>
                        <form method="POST" action="index.php" class="inline-form"
                              onsubmit="return confirm('Refuser cette demande de partenaire ?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id_partenaire" value="<?php echo (int) $demande['id']; ?>">
                            <input type="hidden" name="action" value="refuser">
                            <button type="submit" class="btn btn-danger btn-small">Refuser</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <h2 style="margin-top:40px;">Réservations des clients</h2>

            <p>
                <strong><?php echo $nbEnAttente; ?></strong> réservation(s) en attente sur
                <strong><?php echo count($reservations); ?></strong> réservation(s) au total.
            </p>

            <?php if (empty($reservations)): ?>
                <p>Aucune réservation enregistrée.</p>
            <?php else: ?>
                <div class="admin-table-wrapper">
                    <table class="admin-reservations-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Client</th>
                                <th>Prestation</th>
                                <th>Dates</th>
                                <th>Montant</th>
                                <th>Commission</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $r): ?>
                                <tr>
                                    <td>#<?php echo (int) $r['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($r['client_nom'] ?? 'Client inconnu', ENT_QUOTES, 'UTF-8'); ?>
                                        <br>
                                        <small><?php echo htmlspecialchars($r['client_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($r['prestations'] ?? 'Prestation non renseignée', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (!empty($r['date_debut'])): ?>
                                            <?php echo format_date_fr($r['date_debut']); ?>
                                            au
                                            <?php echo format_date_fr($r['date_fin']); ?>
                                        <?php else: ?>
                                            Non renseignées
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_ar($r['montant_total']); ?></td>
                                    <td><?php echo format_ar($r['montant_commission']); ?></td>
                                    <td><?php echo htmlspecialchars($libellesStatut[$r['statut']] ?? $r['statut'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($r['statut'] === 'en_attente'): ?>
                                            <form method="POST" action="index.php" style="margin-bottom:6px;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id_reservation" value="<?php echo (int) $r['id']; ?>">
                                                <input type="hidden" name="action_reservation" value="valider">
                                                <button type="submit" class="btn btn-success btn-small">Valider</button>
                                            </form>
                                            <form method="POST" action="index.php"
                                                  onsubmit="return confirm('Voulez-vous annuler cette réservation ?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="id_reservation" value="<?php echo (int) $r['id']; ?>">
                                                <input type="hidden" name="action_reservation" value="annuler">
                                                <button type="submit" class="btn btn-danger btn-small">Annuler</button>
                                            </form>
                                        <?php else: ?>
                                            <span>Action déjà traitée</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</body>
</html>