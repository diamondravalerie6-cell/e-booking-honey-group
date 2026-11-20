<?php
/**
 * Composant Footer - e-Booking Honey Group
 * Usage : <?php include 'includes/footer.php'; ?>
 */
?>
<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-col">
            <h4>Honey Group</h4>
            <p>LOT IVB 48 Andravoahangy, Antananarivo 101 - Madagascar</p>
        </div>
        <div class="footer-col">
            <h4>Contact</h4>
            <p>Tél : +261 32 22 714 93</p>
            <p>Email : honeygroup.mg17@gmail.com</p>
        </div>
        <div class="footer-col">
            <h4>Liens utiles</h4>
            <ul>
                <li><a href="<?php echo $base ?? ''; ?>index.php">Accueil</a></li>
                <li><a href="<?php echo $base ?? ''; ?>recherche.php">Hôtels</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Honey Group. Tous droits réservés.</p>
    </div>
</footer>
