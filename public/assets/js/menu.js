/* Public menu: star rating + active category highlight */
(function () {
    'use strict';
    var page = document.querySelector('.menu-page');
    if (!page) return;
    var slug = page.dataset.slug;
    var csrf = page.dataset.csrf;

    /* ---- star rating ---- */
    document.querySelectorAll('.mi-rate').forEach(function (box) {
        var itemId = box.dataset.id;
        box.querySelectorAll('.star').forEach(function (star) {
            star.addEventListener('click', function () {
                var stars = parseInt(star.dataset.stars, 10);
                var body = new URLSearchParams();
                body.set('item_id', itemId);
                body.set('stars', stars);
                body.set('_csrf', csrf);
                var rateUrl = window.location.pathname.replace(/\/$/, '') + '/rate';
                fetch(rateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                }).then(function (r) { return r.json(); }).then(function (res) {
                    if (!res.ok) return;
                    var allStars = box.querySelectorAll('.star');
                    allStars.forEach(function (s) {
                        s.classList.toggle('on', parseInt(s.dataset.stars, 10) <= Math.round(res.avg));
                    });
                    var info = box.querySelector('.rate-info');
                    if (info) info.textContent = res.avg + ' (' + res.count + ')';
                }).catch(function () {});
            });
        });
    });

    /* ---- highlight active category while scrolling ---- */
    var links = document.querySelectorAll('.cat-nav a');
    var sections = [];
    links.forEach(function (a) {
        var sec = document.querySelector(a.getAttribute('href'));
        if (sec) sections.push({ a: a, sec: sec });
    });
    if ('IntersectionObserver' in window && sections.length) {
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    links.forEach(function (l) { l.classList.remove('active'); });
                    var match = sections.find(function (s) { return s.sec === en.target; });
                    if (match) match.a.classList.add('active');
                }
            });
        }, { rootMargin: '-50% 0px -45% 0px' });
        sections.forEach(function (s) { obs.observe(s.sec); });
    }

    /* auto-dismiss toasts */
    document.querySelectorAll('.toast').forEach(function (t) {
        setTimeout(function () { t.style.opacity = '0'; }, 3000);
    });
})();
