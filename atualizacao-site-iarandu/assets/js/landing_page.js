(function () {
        /* ---------- Vídeo (facade YouTube) ---------- */
        var wrap = document.getElementById('aranduVideo');
        var play = document.getElementById('aranduVideoPlay');
        if (wrap && play) {
            play.addEventListener('click', function () {
                var id = (wrap.getAttribute('data-youtube-id') || '').trim();
                if (!id) { return; }
                var iframe = document.createElement('iframe');
                iframe.setAttribute('src', 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0');
                iframe.setAttribute('title', 'Demonstração do sistema IArandú');
                iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
                iframe.setAttribute('allowfullscreen', '');
                wrap.innerHTML = '';
                wrap.appendChild(iframe);
            });
        }

        /* ---------- Blueprint interativo (hero) ---------- */
        var bp = document.getElementById('aranduBlueprint');
        if (bp) {
            var nodes = Array.prototype.slice.call(bp.querySelectorAll('[data-bp]'));
            var titleEl = document.getElementById('bpTitle');
            var descEl = document.getElementById('bpDesc');
            var hub = document.getElementById('aranduBlueprintHub');
            var idx = 0, auto = true, timer = null;
            var bpReduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function select(i, manual) {
                if (i < 0 || i >= nodes.length) return;
                idx = i;
                nodes.forEach(function (n, k) {
                    var on = k === i;
                    n.classList.toggle('is-active', on);
                    n.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                var node = nodes[i];
                if (titleEl) titleEl.textContent = node.getAttribute('data-label');
                if (descEl) descEl.textContent = node.getAttribute('data-desc');
                if (manual && timer) { auto = false; clearInterval(timer); timer = null; }
            }
            nodes.forEach(function (n, k) {
                n.addEventListener('click', function () { select(k, true); });
                n.addEventListener('mouseenter', function () { if (auto) select(k, false); });
                n.addEventListener('focus', function () { select(k, true); });
            });
            select(0, false);

            function startAuto() {
                if (timer || !auto || bpReduce) return;
                timer = setInterval(function () { if (auto) select((idx + 1) % nodes.length, false); }, 4000);
            }
            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { startAuto(); }
                        else if (timer) { clearInterval(timer); timer = null; }
                    });
                }, { threshold: 0.4 });
                io.observe(bp);
            } else { startAuto(); }
        }

        /* ---------- Dashboard navegável (case) ---------- */
        var win = document.getElementById('aranduWindow');
        if (win) {
            var navBtns = Array.prototype.slice.call(win.querySelectorAll('[data-view-target]'));
            var views = Array.prototype.slice.call(win.querySelectorAll('.aw-view'));
            var urlEl = document.getElementById('awUrl');
            var urlMap = {
                pis: 'pedidos de inserção', inicio: 'início', mensagens: 'mensagens',
                clientes: 'clientes', fornecedores: 'fornecedores', representantes: 'representantes',
                reatribuir: 'reatribuir pis', tipos: 'tipos de documentos', usuarios: 'usuários',
                status: 'status', generic: 'sistema'
            };

            function showView(target) {
                var found = false;
                views.forEach(function (v) {
                    var on = v.getAttribute('data-view') === target;
                    if (on) found = true;
                    v.hidden = !on;
                });
                if (!found) { // item sem tela própria -> genérica
                    target = 'generic';
                    views.forEach(function (v) { v.hidden = v.getAttribute('data-view') !== 'generic'; });
                }
                navBtns.forEach(function (b) {
                    var on = b.getAttribute('data-view-target') === target;
                    if (b.classList.contains('arandu-sidebar-item')) b.classList.toggle('active', on);
                    if (b.classList.contains('aw-tab')) b.classList.toggle('is-active', on);
                });
                if (urlEl) urlEl.textContent = 'app.trade · ' + (urlMap[target] || 'sistema');
            }
            navBtns.forEach(function (b) {
                b.addEventListener('click', function () { showView(b.getAttribute('data-view-target')); });
            });

            /* Filtros de PI */
            var filterBtns = Array.prototype.slice.call(win.querySelectorAll('.arandu-filter'));
            var piEls = Array.prototype.slice.call(win.querySelectorAll('[data-pi]'));
            filterBtns.forEach(function (fb) {
                fb.addEventListener('click', function () {
                    filterBtns.forEach(function (x) { x.classList.remove('active'); });
                    fb.classList.add('active');
                    var g = fb.getAttribute('data-filter');
                    piEls.forEach(function (r) { r.hidden = !(g === 'todos' || r.getAttribute('data-group') === g); });
                });
            });

            /* Detalhe + validação de documentos */
            var detail = document.getElementById('awDetail');
            function setDetail(pi) {
                if (!detail) return;
                Array.prototype.slice.call(detail.querySelectorAll('[data-detail]')).forEach(function (d) {
                    d.hidden = (d.getAttribute('data-detail') !== pi);
                });
                piEls.forEach(function (r) { r.classList.toggle('is-selected', pi !== '' && r.getAttribute('data-pi') === pi); });
            }
            piEls.forEach(function (r) {
                r.addEventListener('click', function () { setDetail(r.getAttribute('data-pi')); });
            });
            if (detail) {
                detail.addEventListener('click', function (e) {
                    var closeBtn = e.target.closest('.aw-detail-close');
                    if (closeBtn) { e.stopPropagation(); setDetail(''); return; }
                    var ap = e.target.closest('.aw-doc-approve');
                    var rj = e.target.closest('.aw-doc-reject');
                    if (ap || rj) {
                        e.stopPropagation();
                        var chip = e.target.closest('.aw-doc');
                        if (!chip) return;
                        chip.classList.remove('aw-doc--wait');
                        chip.classList.add(ap ? 'aw-doc--ok' : 'aw-doc--bad');
                        var acts = chip.querySelector('.aw-doc-actions');
                        if (acts) acts.remove();
                    }
                });
            }
        }

        /* ---------- Carrossel de depoimentos ---------- */
        var car = document.getElementById('aranduCarousel');
        if (car) {
            var track = car.querySelector('.arandu-carousel-track');
            var slides = Array.prototype.slice.call(track.children);
            var dotsWrap = car.querySelector('.arandu-carousel-dots');
            var prevBtn = car.querySelector('[data-car-prev]');
            var nextBtn = car.querySelector('[data-car-next]');

            function slidePos(s) { return s.offsetLeft - track.offsetLeft; }

            slides.forEach(function (s, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'arandu-carousel-dot';
                b.setAttribute('aria-label', 'Ir ao depoimento ' + (i + 1));
                b.addEventListener('click', function () { track.scrollTo({ left: slidePos(s), behavior: 'smooth' }); });
                dotsWrap.appendChild(b);
            });
            var dots = Array.prototype.slice.call(dotsWrap.children);

            function update() {
                var idx = 0, min = Infinity;
                slides.forEach(function (s, i) {
                    var d = Math.abs(slidePos(s) - track.scrollLeft);
                    if (d < min) { min = d; idx = i; }
                });
                dots.forEach(function (d, i) { d.classList.toggle('is-active', i === idx); });
                if (prevBtn) prevBtn.disabled = track.scrollLeft <= 4;
                if (nextBtn) nextBtn.disabled = (track.scrollLeft + track.clientWidth) >= (track.scrollWidth - 4);
            }
            function step(dir) {
                var w = slides[0] ? (slides[0].getBoundingClientRect().width + 16) : track.clientWidth;
                track.scrollBy({ left: dir * w, behavior: 'smooth' });
            }
            if (prevBtn) prevBtn.addEventListener('click', function () { step(-1); });
            if (nextBtn) nextBtn.addEventListener('click', function () { step(1); });
            track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
            window.addEventListener('resize', function () { window.requestAnimationFrame(update); });
            update();
        }
    })();