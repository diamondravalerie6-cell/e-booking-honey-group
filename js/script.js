document.addEventListener('DOMContentLoaded', function () {
    var burgerBtn = document.getElementById('burgerBtn');
    var mainNav = document.getElementById('mainNav');

    if (burgerBtn && mainNav) {
        burgerBtn.addEventListener('click', function () {
            var isOpen = mainNav.classList.toggle('open');
            burgerBtn.setAttribute('aria-expanded', isOpen);
        });
    }
});
