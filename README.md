# Prototype fonctionnel (MVP) — e-Booking Honey Group

Livrable 4 de la Phase 2 : un prototype démontrant le parcours de réservation
complet et le calcul des devis, conformément au cahier des charges.

## 1. Parcours couvert par ce MVP

- Recherche d'hôtels par ville (`index.php` → `recherche.php`)
- Fiche détaillée avec calcul dynamique du devis en JavaScript (`hotel.php`)
- Tunnel de réservation avec enregistrement réel en base de données (`reservation.php`)
- Confirmation de réservation (`confirmation.php`)
- Connexion administrateur sécurisée (`admin/login.php`, `admin/logout.php`)
- Tableau de bord administrateur : KPIs réels + validation des partenaires (`admin/index.php`)

Conformément au cahier des charges (section 1.2), ce MVP se concentre sur le
parcours prioritaire. La location de véhicules, l'espace partenaire complet,
et l'authentification utilisateur sont prévus en évolutions suivantes.

## 2. Installation

1. Copier le dossier `ebooking-mvp/` dans `C:\xampp\htdocs\`
2. Démarrer Apache et MySQL dans XAMPP
3. Importer `sql/e-booking-schema.sql` via phpMyAdmin (crée la base `e_booking`
   avec des données de test : 2 hôtels, 1 partenaire en attente de validation)
4. Vérifier les identifiants dans `includes/db.php` (par défaut : root / sans mot de passe)
5. Ouvrir `http://localhost/ebooking-mvp/`

## 3. Tester le parcours complet

1. Sur la page d'accueil, lancer une recherche (ou cliquer sur un hôtel mis en avant)
2. Ouvrir la fiche d'un hôtel, choisir des dates → le montant estimé se met à jour automatiquement
3. Cliquer sur « Réserver », remplir nom + email → « Confirmer la réservation »
4. La page de confirmation affiche le numéro de réservation réellement enregistré en base
5. Aller sur `admin/` : tu es redirigé vers la page de connexion. Connecte-toi avec le compte
   administrateur (voir section 4), puis constate que les KPIs reflètent ta réservation
6. Dans la section « Demandes de partenaires en attente », valider ou refuser « Sahy Lodge » —
   une fois validé, ses hôtels apparaîtront dans les résultats de recherche publics

## 4. Connexion administrateur

- Compte de démonstration : `admin@honeygroup.mg` (le mot de passe t'a été communiqué
  séparément, il n'est volontairement pas écrit dans le code).
- Base déjà installée avant cette fonctionnalité : importer **une seule fois**
  `sql/migration-admin.sql` (ajoute la colonne `role` et le compte admin).
- Nouvelle installation : `sql/e-booking-schema.sql` contient déjà tout.
- **Changer le mot de passe** : générer un nouveau hash avec
  `php -r "echo password_hash('NouveauMotDePasse', PASSWORD_DEFAULT);"` puis mettre à jour
  la colonne `mot_de_passe` du compte dans phpMyAdmin.

## 5. Sécurité appliquée

- Mots de passe stockés hachés (bcrypt) et vérifiés avec `password_verify()`
- Sessions PHP : cookie HttpOnly + SameSite, régénération de l'identifiant à la connexion
- Protection CSRF (jeton) sur la connexion, la déconnexion et les actions admin
- Message d'erreur identique que l'email ou le mot de passe soit faux
- Blocage temporaire (5 min) après 5 échecs de connexion
- Accès aux pages admin vérifié côté serveur (`require_admin()`)
- Requêtes préparées PDO partout (anti-injection SQL)
- Validation des entrées utilisateur côté serveur (email, dates, nom)
- Échappement systématique à l'affichage (`htmlspecialchars`) contre les failles XSS
- Transaction SQL (`beginTransaction`/`commit`/`rollBack`) lors de la création d'une
  réservation, pour garantir que réservation + ligne + commission sont créées ensemble
  ou pas du tout

## 6. Limites connues de ce MVP (à faire évoluer)

- Pas d'authentification utilisateur complète (le compte est créé automatiquement
  à partir du nom/email saisi lors de la réservation)
- La limitation des tentatives de connexion repose sur la session (contournable en changeant de navigateur) ; une version de production utiliserait un blocage par adresse IP en base
- Pas encore de HTTPS : activer l'option `secure` du cookie de session une fois le site en ligne
- La location de véhicules n'est pas encore implémentée (seuls les hôtels le sont)
- Les photos sont des espaces réservés (placeholders), à remplacer par de vraies images
