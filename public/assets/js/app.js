/**
 * SICV ERP — JavaScript global
 */
(function () {
    'use strict';

    const STORAGE_THEME = 'sicv_theme';
    const STORAGE_SIDEBAR = 'sicv_sidebar_collapsed';

    /* ——— Tema oscuro ——— */
    function initTheme() {
        const saved = localStorage.getItem(STORAGE_THEME);
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = saved || (prefersDark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
        updateThemeIcon(theme);
    }

    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem(STORAGE_THEME, next);
        updateThemeIcon(next);
    }

    function updateThemeIcon(theme) {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-label', theme === 'dark' ? 'Modo claro' : 'Modo oscuro');
            btn.innerHTML = theme === 'dark'
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
        });
    }

    /* ——— Sidebar ——— */
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainWrapper = document.getElementById('main-wrapper');
        const overlay = document.getElementById('sidebar-overlay');
        const toggleBtns = document.querySelectorAll('[data-sidebar-toggle]');

        if (!sidebar) return;

        const isMobile = function () { return window.innerWidth < 992; };

        function openSidebar() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('visible');
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('visible');
        }

        function toggleSidebar() {
            if (isMobile()) {
                sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainWrapper) mainWrapper.classList.toggle('expanded');
                localStorage.setItem(STORAGE_SIDEBAR, sidebar.classList.contains('collapsed') ? '1' : '0');
            }
        }

        if (!isMobile() && localStorage.getItem(STORAGE_SIDEBAR) === '1') {
            sidebar.classList.add('collapsed');
            if (mainWrapper) mainWrapper.classList.add('expanded');
        }

        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', toggleSidebar);
        });

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeSidebar();
            }
        });

        document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobile()) closeSidebar();
            });
        });
    }

    /* ——— Menú de usuario ——— */
    function initUserMenu() {
        const trigger = document.getElementById('user-menu-trigger');
        const dropdown = document.getElementById('user-dropdown');
        if (!trigger || !dropdown) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });

        document.addEventListener('click', function () {
            dropdown.classList.remove('open');
        });
    }

    /* ——— Alertas dismissibles ——— */
    function initAlerts() {
        document.querySelectorAll('.alert-close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const alert = btn.closest('.alert');
                if (alert) alert.remove();
            });
        });
    }

    /* ——— Tabs ——— */
    function initTabs() {
        document.querySelectorAll('[data-tabs]').forEach(function (container) {
            const buttons = container.querySelectorAll('.tab-btn');
            const panels = container.querySelectorAll('.tab-panel');

            function activate(id) {
                buttons.forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.tab === id);
                });
                panels.forEach(function (panel) {
                    panel.classList.toggle('active', panel.id === 'tab-' + id);
                });
                if (history.replaceState) {
                    history.replaceState(null, '', '#' + id);
                }
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activate(btn.dataset.tab);
                });
            });

            const hash = window.location.hash.replace('#', '');
            if (hash && container.querySelector('[data-tab="' + hash + '"]')) {
                activate(hash);
            } else if (buttons.length) {
                activate(buttons[0].dataset.tab);
            }
        });
    }

    /* ——— Búsqueda global (atajo Ctrl+K) ——— */
    function initGlobalSearch() {
        const input = document.getElementById('global-search');
        if (!input) return;

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                input.focus();
                input.select();
            }
        });
    }

    /* ——— Signature Pad ——— */
    window.SICV = window.SICV || {};

    window.SICV.SignaturePad = function (canvas, hiddenInput) {
        if (!canvas || !canvas.getContext) return null;

        const ctx = canvas.getContext('2d');
        let drawing = false;
        let hasStroke = false;

        function resize() {
            const rect = canvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            ctx.scale(ratio, ratio);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#1a1a2e';
            ctx.lineWidth = 2;
        }

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        function start(e) {
            e.preventDefault();
            drawing = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!drawing) return;
            e.preventDefault();
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            hasStroke = true;
            sync();
        }

        function stop() {
            drawing = false;
            sync();
        }

        function sync() {
            if (hiddenInput && hasStroke) {
                hiddenInput.value = canvas.toDataURL('image/jpeg', 0.92);
            }
        }

        function clear() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasStroke = false;
            if (hiddenInput) hiddenInput.value = '';
        }

        resize();
        window.addEventListener('resize', function () {
            const data = hasStroke ? canvas.toDataURL('image/jpeg', 0.92) : null;
            resize();
            if (data) {
                const img = new Image();
                img.onload = function () { ctx.drawImage(img, 0, 0, canvas.offsetWidth, canvas.offsetHeight); };
                img.src = data;
            }
        });

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stop);
        canvas.addEventListener('mouseleave', stop);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stop);

        return { clear: clear, isEmpty: function () { return !hasStroke; } };
    };

    window.SICV.initSignaturePads = function () {
        document.querySelectorAll('[data-signature-pad]').forEach(function (wrapper) {
            const canvas = wrapper.querySelector('canvas');
            const input = wrapper.querySelector('input[type="hidden"]');
            const clearBtn = wrapper.querySelector('[data-signature-clear]');
            const pad = window.SICV.SignaturePad(canvas, input);
            if (clearBtn && pad) {
                clearBtn.addEventListener('click', function () { pad.clear(); });
            }
        });
    };

    function initDashLights() {
        document.querySelectorAll('[data-dash-lights]').forEach(function (grid) {
            const countEl = document.querySelector('[data-dash-lights-count]');

            function sync() {
                const checked = grid.querySelectorAll('input[type="checkbox"]:checked');
                grid.querySelectorAll('.dash-light-card').forEach(function (card) {
                    const input = card.querySelector('input[type="checkbox"]');
                    const status = card.querySelector('.dash-light-status');
                    const isOn = input && input.checked;
                    card.classList.toggle('is-on', isOn);
                    if (status) {
                        status.textContent = isOn ? 'Encendida' : 'Apagada';
                    }
                });
                if (countEl) {
                    countEl.textContent = String(checked.length);
                }
            }

            grid.addEventListener('change', sync);
            sync();
        });
    }

    /* ——— Autocompletar km del vehículo seleccionado ——— */
    function initKmAutofill() {
        const source = document.querySelector('[data-km-source]');
        const target = document.querySelector('[data-km-target]');
        if (!source || !target) return;

        function apply() {
            const option = source.options[source.selectedIndex];
            const km = option ? option.getAttribute('data-km') : null;
            if (km === null || km === '') return;
            target.value = km;
            target.min = km;
        }

        source.addEventListener('change', apply);
        if (source.value && target.value === '') {
            apply();
        }
    }

    /* ——— Confirmación de acciones destructivas ——— */
    function initConfirm() {
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                const msg = el.getAttribute('data-confirm');
                if (msg && !window.confirm(msg)) {
                    e.preventDefault();
                }
            });
        });
    }

    /* ——— Init ——— */
    document.addEventListener('DOMContentLoaded', function () {
        initTheme();
        initSidebar();
        initUserMenu();
        initAlerts();
        initTabs();
        initGlobalSearch();
        initConfirm();
        initDashLights();
        initKmAutofill();
        window.SICV.initSignaturePads();

        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', toggleTheme);
        });
    });
})();
