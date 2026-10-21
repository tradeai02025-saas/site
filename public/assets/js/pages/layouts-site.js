(function () {
            var nav = document.getElementById('siteNav');
            var toggle = document.getElementById('siteNavToggle');

            if (toggle && nav) {
                toggle.addEventListener('click', function () {
                    var open = nav.classList.toggle('is-open');
                    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
                });
                nav.querySelectorAll('.site-nav-links a').forEach(function (a) {
                    a.addEventListener('click', function () {
                        nav.classList.remove('is-open');
                        toggle.setAttribute('aria-expanded', 'false');
                        toggle.setAttribute('aria-label', 'Abrir menu');
                    });
                });
            }

            var onScroll = function () {
                if (nav) nav.classList.toggle('is-scrolled', window.scrollY > 8);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();

            var reveals = document.querySelectorAll('.reveal');
            if ('IntersectionObserver' in window && reveals.length) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
                reveals.forEach(function (el) { io.observe(el); });
            } else {
                reveals.forEach(function (el) { el.classList.add('is-visible'); });
            }
        })();