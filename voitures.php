<?php
/**
 * voitures.php - Recherche de véhicules de location
 */
require 'includes/functions.php';

$categories_dispo = ['4x4', 'citadine', 'SUV'];

$categorie = $_GET['categorie'] ?? '';
if (!in_array($categorie, $categories_dispo, true)) {
    $categorie = '';
}
$chauffeur = $_GET['chauffeur'] ?? '';
if (!in_array($chauffeur, ['0', '1'], true)) {
    $chauffeur = '';
}
$debut = $_GET['debut'] ?? '';
$fin = $_GET['fin'] ?? '';

// Nombre de jours de location (0 si les dates ne sont pas valides)
$nb_jours = 0;
$d1 = DateTime::createFromFormat('Y-m-d', $debut);
$d2 = DateTime::createFromFormat('Y-m-d', $fin);
if ($d1 && $d2 && $d2 >= $d1) {
    $nb_jours = max(1, (int) $d1->diff($d2)->days);
}

$vehicules = get_vehicules($categorie !== '' ? $categorie : null, $chauffeur !== '' ? $chauffeur : null);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Location de voitures à Madagascar - Honey Group">
    <title>Location de voitures - Honey Group</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <section class="section">
        <div class="container">
            <h1 class="page-title">Location de voitures</h1>

            <form class="search-bar-slim" action="voitures.php" method="GET">
                <div class="field">
                    <label for="categorie">Type de véhicule</label>
                    <select id="categorie" name="categorie">
                        <option value="">Tous</option>
                        <?php foreach ($categories_dispo as $c): ?>
                            <option value="<?php echo $c; ?>" <?php echo $categorie === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="chauffeur">Chauffeur</label>
                    <select id="chauffeur" name="chauffeur">
                        <option value="" <?php echo $chauffeur === '' ? 'selected' : ''; ?>>Avec ou sans</option>
                        <option value="1" <?php echo $chauffeur === '1' ? 'selected' : ''; ?>>Avec chauffeur</option>
                        <option value="0" <?php echo $chauffeur === '0' ? 'selected' : ''; ?>>Sans chauffeur</option>
                    </select>
                </div>
                <div class="field">
                    <label for="debut">Prise en charge</label>
                    <input type="date" id="debut" name="debut" value="<?php echo htmlspecialchars($debut); ?>">
                </div>
                <div class="field">
                    <label for="fin">Restitution</label>
                    <input type="date" id="fin" name="fin" value="<?php echo htmlspecialchars($fin); ?>">
                </div>
                <div class="field" style="flex: 0 0 auto; display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Rechercher</button>
                </div>
            </form>

            <div class="results-list">
                <p class="meta">
                    <?php echo count($vehicules); ?> véhicule(s) trouvé(s)<?php echo $nb_jours > 0 ? ' pour ' . $nb_jours . ' jour(s)' : ''; ?>
                </p>

                <?php if (empty($vehicules)): ?>
                    <p>Aucun véhicule ne correspond à votre recherche.</p>
                <?php endif; ?>

                <?php foreach ($vehicules as $v): ?>
                    <a href="vehicule.php?id=<?php echo (int) $v['id']; ?>&debut=<?php echo urlencode($debut); ?>&fin=<?php echo urlencode($fin); ?>" class="result-card">
                        <div class="card-image">
                            <?php if (!empty($v['photo'])): ?>
                                <img src="img/voitures/<?php echo htmlspecialchars($v['photo']); ?>"
                                     alt="<?php echo htmlspecialchars($v['modele']); ?>">
                            <?php else: ?>
                                Photo
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($v['modele']); ?></h3>
                            <p class="meta">
                                <?php echo htmlspecialchars($v['categorie_vehicule']); ?>
                                — <?php echo (int) $v['nb_places']; ?> places
                                — <?php echo $v['avec_chauffeur'] ? 'avec chauffeur' : 'sans chauffeur'; ?>
                                — proposé par <?php echo htmlspecialchars($v['nom_entreprise']); ?>
                            </p>
                            <p class="price"><?php echo format_ar($v['tarif_journalier']); ?> / jour</p>
                            <?php if ($nb_jours > 0): ?>
                                <p class="price">Total estimé : <?php echo format_ar($v['tarif_journalier'] * $nb_jours); ?> (<?php echo $nb_jours; ?> j)</p>
                            <?php endif; ?>
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