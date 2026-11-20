<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
/**
 * Fonctions d'accès aux données pour la plateforme e-Booking.
 * Toutes les requêtes utilisent des requêtes préparées (PDO).
 */
require_once __DIR__ . '/db.php';

/** Retourne les hôtels, avec filtre optionnel par ville. */
function get_hotels(?string $ville = null): array
{
    $pdo = get_db_connection();

    if ($ville) {
        $stmt = $pdo->prepare(
            'SELECT h.*, p.nom_entreprise FROM hotels h
             JOIN partenaires p ON p.id = h.id_partenaire
             WHERE h.ville LIKE :ville AND p.statut = \'valide\'
             ORDER BY h.tarif_nuit_moyen ASC'
        );
        $stmt->execute([':ville' => '%' . $ville . '%']);
    } else {
        $stmt = $pdo->query(
            'SELECT h.*, p.nom_entreprise FROM hotels h
             JOIN partenaires p ON p.id = h.id_partenaire
             WHERE p.statut = \'valide\'
             ORDER BY h.tarif_nuit_moyen ASC'
        );
    }

    return $stmt->fetchAll();
}
/** Retourne les véhicules, avec filtres optionnels (catégorie, chauffeur). */
function get_vehicules(?string $categorie = null, ?string $chauffeur = null): array
{
    $pdo = get_db_connection();

    $sql = 'SELECT v.*, p.nom_entreprise FROM vehicules v
            JOIN partenaires p ON p.id = v.id_partenaire
            WHERE p.statut = \'valide\'';
    $params = [];

    if ($categorie) {
        $sql .= ' AND v.categorie_vehicule = :cat';
        $params[':cat'] = $categorie;
    }
    if ($chauffeur === '0' || $chauffeur === '1') {
        $sql .= ' AND v.avec_chauffeur = :ch';
        $params[':ch'] = (int) $chauffeur;
    }
    $sql .= ' ORDER BY v.tarif_journalier ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
/** Retourne un véhicule par son id (partenaire validé uniquement). */
function get_vehicule_by_id(int $id): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'SELECT v.*, p.nom_entreprise FROM vehicules v
         JOIN partenaires p ON p.id = v.id_partenaire
         WHERE v.id = :id AND p.statut = \'valide\''
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Retourne un hôtel précis par son id. */
function get_hotel_by_id(int $id): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $hotel = $stmt->fetch();

    return $hotel ?: null;
}

/** Calcule le nombre de nuits entre deux dates (minimum 1). */
function nights_between(string $dateDebut, string $dateFin): int
{
    try {
        $d1 = new DateTime($dateDebut);
        $d2 = new DateTime($dateFin);
        $diff = $d1->diff($d2)->days;
        return max(1, $diff);
    } catch (Exception $e) {
        return 1;
    }
}

/**
 * Crée une réservation complète (réservation + ligne + commission) pour un hôtel.
 * Retourne l'id de la réservation créée.
 */
function create_hotel_reservation(
    int $idUtilisateur,
    int $idHotel,
    string $dateDebut,
    string $dateFin
): int {
    $pdo = get_db_connection();
    $hotel = get_hotel_by_id($idHotel);

    if (!$hotel) {
        throw new Exception('Hôtel introuvable.');
    }

    $nuits = nights_between($dateDebut, $dateFin);
    $sousTotal = $nuits * (float) $hotel['tarif_nuit_moyen'];

    // Récupère le taux de commission du partenaire propriétaire de l'hôtel
    $stmtPartenaire = $pdo->prepare('SELECT taux_commission FROM partenaires WHERE id = :id');
    $stmtPartenaire->execute([':id' => $hotel['id_partenaire']]);
    $partenaire = $stmtPartenaire->fetch();
    $tauxCommission = $partenaire ? (float) $partenaire['taux_commission'] : 10.0;
    $montantCommission = round($sousTotal * $tauxCommission / 100, 2);

    $pdo->beginTransaction();

    try {
        // 1. Créer la réservation
        $stmtRes = $pdo->prepare(
            'INSERT INTO reservations (id_utilisateur, date_reservation, montant_total, montant_commission, statut)
             VALUES (:id_utilisateur, CURDATE(), :montant_total, :montant_commission, :statut)'
        );
        $stmtRes->execute([
            ':id_utilisateur'     => $idUtilisateur,
            ':montant_total'      => $sousTotal,
            ':montant_commission' => $montantCommission,
            ':statut'             => 'en_attente',
        ]);
        $idReservation = (int) $pdo->lastInsertId();

        // 2. Créer la ligne de réservation
        $stmtLigne = $pdo->prepare(
            'INSERT INTO lignes_reservation (id_reservation, type_prestation, id_ressource, date_debut, date_fin, sous_total)
             VALUES (:id_reservation, \'hotel\', :id_ressource, :date_debut, :date_fin, :sous_total)'
        );
        $stmtLigne->execute([
            ':id_reservation' => $idReservation,
            ':id_ressource'   => $idHotel,
            ':date_debut'     => $dateDebut,
            ':date_fin'       => $dateFin,
            ':sous_total'     => $sousTotal,
        ]);

        // 3. Créer la commission associée
        $stmtComm = $pdo->prepare(
            'INSERT INTO commissions_paiements (id_reservation, id_partenaire, montant_du, statut_paiement)
             VALUES (:id_reservation, :id_partenaire, :montant_du, \'du\')'
        );
        $stmtComm->execute([
            ':id_reservation' => $idReservation,
            ':id_partenaire'  => $hotel['id_partenaire'],
            ':montant_du'     => $montantCommission,
        ]);

        $pdo->commit();

        return $idReservation;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Crée une réservation complète (réservation + ligne + commission) pour un véhicule.
 * Retourne l'id de la réservation créée.
 */
function create_vehicule_reservation(
    int $idUtilisateur,
    int $idVehicule,
    string $dateDebut,
    string $dateFin
): int {
    $pdo = get_db_connection();
    $vehicule = get_vehicule_by_id($idVehicule);

    if (!$vehicule) {
        throw new Exception('Véhicule introuvable.');
    }

    // Nombre de jours de location (minimum 1, même jour = 1 jour)
    $jours = max(1, (int) (new DateTime($dateDebut))->diff(new DateTime($dateFin))->days);
    $sousTotal = $jours * (float) $vehicule['tarif_journalier'];

    // Taux de commission du partenaire propriétaire du véhicule
    $stmtPartenaire = $pdo->prepare('SELECT taux_commission FROM partenaires WHERE id = :id');
    $stmtPartenaire->execute([':id' => $vehicule['id_partenaire']]);
    $partenaire = $stmtPartenaire->fetch();
    $tauxCommission = $partenaire ? (float) $partenaire['taux_commission'] : 10.0;
    $montantCommission = round($sousTotal * $tauxCommission / 100, 2);

    $pdo->beginTransaction();

    try {
        // 1. Créer la réservation
        $stmtRes = $pdo->prepare(
            'INSERT INTO reservations (id_utilisateur, date_reservation, montant_total, montant_commission, statut)
             VALUES (:id_utilisateur, CURDATE(), :montant_total, :montant_commission, :statut)'
        );
        $stmtRes->execute([
            ':id_utilisateur'     => $idUtilisateur,
            ':montant_total'      => $sousTotal,
            ':montant_commission' => $montantCommission,
            ':statut'             => 'en_attente',
        ]);
        $idReservation = (int) $pdo->lastInsertId();

        // 2. Créer la ligne de réservation
        $stmtLigne = $pdo->prepare(
            'INSERT INTO lignes_reservation (id_reservation, type_prestation, id_ressource, date_debut, date_fin, sous_total)
             VALUES (:id_reservation, \'vehicule\', :id_ressource, :date_debut, :date_fin, :sous_total)'
        );
        $stmtLigne->execute([
            ':id_reservation' => $idReservation,
            ':id_ressource'   => $idVehicule,
            ':date_debut'     => $dateDebut,
            ':date_fin'       => $dateFin,
            ':sous_total'     => $sousTotal,
        ]);

        // 3. Créer la commission associée
        $stmtComm = $pdo->prepare(
            'INSERT INTO commissions_paiements (id_reservation, id_partenaire, montant_du, statut_paiement)
             VALUES (:id_reservation, :id_partenaire, :montant_du, \'du\')'
        );
        $stmtComm->execute([
            ':id_reservation' => $idReservation,
            ':id_partenaire'  => $vehicule['id_partenaire'],
            ':montant_du'     => $montantCommission,
        ]);

        $pdo->commit();

        return $idReservation;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Date lisible : 2026-10-19 -> 19/10/2026 */
function format_date_fr($date)
{
    return date('d/m/Y', strtotime($date));
}

/** Récupère une réservation complète avec ses lignes, pour la page de confirmation. */
function get_reservation_details(int $idReservation): ?array
{
    $pdo = get_db_connection();

    $stmtRes = $pdo->prepare('SELECT * FROM reservations WHERE id = :id');
    $stmtRes->execute([':id' => $idReservation]);
    $reservation = $stmtRes->fetch();

    if (!$reservation) {
        return null;
    }

    
    $stmtLignes = $pdo->prepare(
        'SELECT lr.*,
                h.nom_etablissement AS hotel_nom, h.ville,
                v.modele AS vehicule_modele
         FROM lignes_reservation lr
         LEFT JOIN hotels h
                ON lr.type_prestation = \'hotel\'
               AND h.id = lr.id_ressource
         LEFT JOIN vehicules v
                ON lr.type_prestation = \'vehicule\'
               AND v.id = lr.id_ressource
         WHERE lr.id_reservation = :id'
    );
    $stmtLignes->execute([':id' => $idReservation]);
    $reservation['lignes'] = $stmtLignes->fetchAll();
    
 // Client associé à la réservation
    $stmtClient = $pdo->prepare('SELECT nom, email FROM utilisateurs WHERE id = :id');
    $stmtClient->execute([':id' => $reservation['id_utilisateur']]);
    $reservation['client'] = $stmtClient->fetch() ?: null;
    
    return $reservation;
}

/**
 * Trouve ou crée un utilisateur à partir de son nom/email (simplifié pour le MVP,
 * sans système de compte complet).
 */
function find_or_create_utilisateur(string $nom, string $email): int
{
    $pdo = get_db_connection();

    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        return (int) $existing['id'];
    }

    $stmtInsert = $pdo->prepare(
        'INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (:nom, :email, :mdp)'
    );
    // Mot de passe temporaire haché (le MVP ne gère pas encore l'authentification complète)
    $stmtInsert->execute([
        ':nom'   => $nom,
        ':email' => $email,
        ':mdp'   => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT),
    ]);

    return (int) $pdo->lastInsertId();
}

/** KPIs globaux pour le tableau de bord admin. */
function get_admin_kpis(): array
{
    $pdo = get_db_connection();

    $ca = $pdo->query('SELECT COALESCE(SUM(montant_total), 0) AS total FROM reservations WHERE statut != \'annulee\'')->fetch();
    $resActives = $pdo->query('SELECT COUNT(*) AS nb FROM reservations WHERE statut IN (\'en_attente\', \'confirmee\')')->fetch();
    $commissions = $pdo->query('SELECT COALESCE(SUM(montant_commission), 0) AS total FROM reservations WHERE statut != \'annulee\'')->fetch();

    return [
        'chiffre_affaires'      => (float) $ca['total'],
        'reservations_actives'  => (int) $resActives['nb'],
        'commission_totale'     => (float) $commissions['total'],
    ];
}

/** Liste les partenaires en attente de validation. */
function get_partenaires_en_attente(): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT * FROM partenaires WHERE statut = \'en_attente\' ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

/** Valide ou refuse un partenaire. */
function update_partenaire_statut(int $idPartenaire, string $statut): bool
{
    if (!in_array($statut, ['valide', 'refuse'], true)) {
        return false;
    }
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('UPDATE partenaires SET statut = :statut WHERE id = :id');
    return $stmt->execute([':statut' => $statut, ':id' => $idPartenaire]);
}

/** Formate un montant en Ariary. */
function format_ar($montant): string
{
    return number_format((float) $montant, 0, ',', ' ') . ' Ar';
}

/** Liste les réservations pour le tableau de bord administrateur. */
function get_admin_reservations(): array
{
    $pdo = get_db_connection();

    $sql = "
        SELECT
            r.id,
            r.id_utilisateur,
            r.date_reservation,
            r.montant_total,
            r.montant_commission,
            r.statut,
            r.created_at,
            u.nom AS client_nom,
            u.email AS client_email,
            GROUP_CONCAT(
                DISTINCT
                CASE
                    WHEN lr.type_prestation = 'hotel'
                    THEN CONCAT(
                        COALESCE(h.nom_etablissement, 'Hôtel inconnu'),
                        ' (', COALESCE(h.ville, ''), ')'
                    )
                    WHEN lr.type_prestation = 'vehicule'
                    THEN CONCAT(COALESCE(v.modele, 'Véhicule inconnu'), ' (voiture)')
                    ELSE CONCAT(
                        UPPER(COALESCE(lr.type_prestation, 'Autre')),
                        ' #', lr.id_ressource
                    )
                END
                SEPARATOR ', '
            ) AS prestations,
            MIN(lr.date_debut) AS date_debut,
            MAX(lr.date_fin) AS date_fin
        FROM reservations r
        LEFT JOIN utilisateurs u
            ON u.id = r.id_utilisateur
        LEFT JOIN lignes_reservation lr
            ON lr.id_reservation = r.id
        LEFT JOIN hotels h
            ON lr.type_prestation = 'hotel'
            AND h.id = lr.id_ressource 
        LEFT JOIN vehicules v
            ON lr.type_prestation = 'vehicule'
            AND v.id = lr.id_ressource
        
        GROUP BY
            r.id,
            r.id_utilisateur,
            r.date_reservation,
            r.montant_total,
            r.montant_commission,
            r.statut,
            r.created_at,
            u.nom,
            u.email
        ORDER BY r.created_at DESC, r.id DESC
    ";

    return $pdo->query($sql)->fetchAll();
}

/** Met à jour le statut d'une réservation depuis l'administration. */
function update_reservation_statut(int $idReservation, string $statut): bool
{
    if (!in_array($statut, ['confirmee', 'annulee'], true)) {
        return false;
    }

    $pdo = get_db_connection();

    $stmt = $pdo->prepare(
        "UPDATE reservations
         SET statut = :statut
         WHERE id = :id
           AND statut = 'en_attente'"
    );

    $stmt->execute([
        ':statut' => $statut,
        ':id' => $idReservation,
    ]);

    return $stmt->rowCount() > 0;
}
