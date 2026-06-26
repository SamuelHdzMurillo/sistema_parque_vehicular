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

        const errorAlert = document.querySelector('.alert-error');
        if (errorAlert) {
            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
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
            const defaultTab = container.dataset.defaultTab || '';
            if (hash && container.querySelector('[data-tab="' + hash + '"]')) {
                activate(hash);
            } else if (defaultTab && container.querySelector('[data-tab="' + defaultTab + '"]')) {
                activate(defaultTab);
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

    function initLucesAutofill() {
        const source = document.querySelector('[data-luces-autofill]');
        const grid = document.querySelector('[data-dash-lights]');
        if (!source || !grid) return;

        function applyLuces(luces) {
            const set = new Set(luces || []);
            grid.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
                input.checked = set.has(input.value);
            });
            grid.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function hasCheckedLuces() {
            return grid.querySelectorAll('input[type="checkbox"]:checked').length > 0;
        }

        function fetchAndApply(force) {
            const id = source.value;
            if (!id) {
                if (force) {
                    applyLuces([]);
                }
                return;
            }
            fetch(appFetchUrl('vehiculos/' + id + '/luces'), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.ok) {
                        return;
                    }
                    if (force || !hasCheckedLuces()) {
                        applyLuces(data.luces);
                    }
                })
                .catch(function () {});
        }

        source.addEventListener('change', function () {
            fetchAndApply(true);
        });
        if (source.value) {
            fetchAndApply(false);
        }
    }

    function herramientaSlug(nombre) {
        let slug = String(nombre || '').trim().toLowerCase();
        slug = slug.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        if (!slug) {
            slug = 'otro_' + Math.random().toString(36).slice(2, 10);
        }
        return slug.slice(0, 40);
    }

    function herramientaLabel(codigo) {
        return String(codigo || '')
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    function initHerramientasChecklist() {
        document.querySelectorAll('[data-herramientas-checklist]').forEach(function (wrap) {
            const catalogGrid = wrap.querySelector('[data-herramientas-catalog]');
            const customGrid = wrap.querySelector('[data-herramientas-custom]');
            const input = wrap.querySelector('[data-herramientas-custom-input]');
            const addBtn = wrap.querySelector('[data-herramientas-custom-add]');
            if (!catalogGrid || !customGrid || !input || !addBtn) {
                return;
            }

            let catalogCodes = [];
            try {
                catalogCodes = JSON.parse(wrap.getAttribute('data-catalog-codes') || '[]');
            } catch (e) {
                catalogCodes = [];
            }

            const fieldName = (catalogGrid.querySelector('input[type="checkbox"]') || {}).name || 'herramientas_salida[]';

            function existingCodes() {
                const codes = new Set();
                wrap.querySelectorAll('input[type="checkbox"][name="' + fieldName + '"]').forEach(function (cb) {
                    codes.add(cb.value);
                });
                return codes;
            }

            function bindRemove(btn, label) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (label && label.parentNode) {
                        label.parentNode.removeChild(label);
                    }
                });
            }

            customGrid.querySelectorAll('.herramienta-custom-remove').forEach(function (btn) {
                bindRemove(btn, btn.closest('.herramienta-custom-item'));
            });

            function addCustomItem(codigo, labelText, checked) {
                if (existingCodes().has(codigo)) {
                    const existing = wrap.querySelector('input[type="checkbox"][name="' + fieldName + '"][value="' + codigo + '"]');
                    if (existing) {
                        existing.checked = checked !== false;
                    }
                    return;
                }

                const label = document.createElement('label');
                label.className = 'checklist-item herramienta-custom-item';
                label.style.cursor = 'pointer';

                const inner = document.createElement('div');
                inner.className = 'checklist-item-name';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = fieldName;
                checkbox.value = codigo;
                checkbox.checked = checked !== false;

                const text = document.createTextNode(' ' + labelText + ' ');

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-secondary herramienta-custom-remove';
                removeBtn.title = 'Quitar';
                removeBtn.setAttribute('aria-label', 'Quitar herramienta');
                removeBtn.innerHTML = '&times;';

                inner.appendChild(checkbox);
                inner.appendChild(text);
                inner.appendChild(removeBtn);
                label.appendChild(inner);
                customGrid.appendChild(label);
                bindRemove(removeBtn, label);
            }

            function addFromInput() {
                const nombre = input.value.trim();
                if (!nombre) {
                    input.focus();
                    return;
                }
                const codigo = herramientaSlug(nombre);
                addCustomItem(codigo, herramientaLabel(codigo), true);
                input.value = '';
                input.focus();
            }

            addBtn.addEventListener('click', addFromInput);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addFromInput();
                }
            });

            wrap.addEventListener('herramientas:apply', function (e) {
                const list = (e.detail && e.detail.herramientas) || [];
                const set = new Set(list);
                catalogGrid.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    cb.checked = set.has(cb.value);
                });
                customGrid.querySelectorAll('.herramienta-custom-item').forEach(function (label) {
                    const cb = label.querySelector('input[type="checkbox"]');
                    if (!cb) {
                        return;
                    }
                    if (set.has(cb.value)) {
                        cb.checked = true;
                        set.delete(cb.value);
                    } else {
                        label.remove();
                    }
                });
                set.forEach(function (codigo) {
                    if (catalogCodes.indexOf(codigo) === -1) {
                        addCustomItem(codigo, herramientaLabel(codigo), true);
                    }
                });
            });
        });
    }

    function initHerramientasAutofill() {
        const source = document.querySelector('[data-herramientas-autofill]');
        const checklist = document.querySelector('[data-herramientas-checklist]');
        if (!source || !checklist) {
            return;
        }

        function hasCheckedHerramientas() {
            return checklist.querySelectorAll('[data-herramientas-catalog] input[type="checkbox"]:checked, [data-herramientas-custom] input[type="checkbox"]:checked').length > 0;
        }

        function applyHerramientas(list) {
            checklist.dispatchEvent(new CustomEvent('herramientas:apply', {
                bubbles: true,
                detail: { herramientas: list || [] }
            }));
        }

        function fetchAndApply(force) {
            const id = source.value;
            if (!id) {
                if (force) {
                    applyHerramientas([]);
                }
                return;
            }
            fetch(appFetchUrl('vehiculos/' + id + '/herramientas'), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.ok) {
                        return;
                    }
                    if (force || !hasCheckedHerramientas()) {
                        applyHerramientas(data.herramientas);
                    }
                })
                .catch(function () {});
        }

        source.addEventListener('change', function () {
            fetchAndApply(true);
        });
        if (source.value) {
            fetchAndApply(false);
        }
    }

    /* ——— Kilometraje del vehículo en formularios ——— */
    function formatKmHint(km) {
        return Number(km || 0).toLocaleString('es-MX');
    }

    function findKmHint(target) {
        const group = target.closest('.form-group');
        if (group) {
            const hint = group.querySelector('[data-km-hint]');
            if (hint) {
                return hint;
            }
        }
        const next = target.nextElementSibling;
        if (next && (next.matches('[data-km-hint]') || next.matches('.form-hint') || next.matches('small'))) {
            return next;
        }
        return null;
    }

    function buildKmHintText(target, km, form) {
        const kmNum = Number(km || 0);
        const historico = isKmHistorico(form);
        let msg = 'Kilometraje actual del vehículo: ' + formatKmHint(kmNum) + ' km.';
        if (historico) {
            msg += ' Registro histórico: puede ingresar el kilometraje que tenía el vehículo en esa fecha.';
            return msg;
        }
        if (target.hasAttribute('data-km-regreso')) {
            const salidaEl = form.querySelector('#km_salida') || form.querySelector('[name="km_salida"]');
            const salida = salidaEl ? Number(salidaEl.value || 0) : 0;
            const minKm = Math.max(kmNum, salida);
            msg += ' Mínimo para regreso: ' + formatKmHint(minKm) + ' km (salida: ' + formatKmHint(salida) + ').';
        } else {
            msg += ' Debe ser igual o mayor.';
        }
        return msg;
    }

    function isKmHistorico(form) {
        if (!form) {
            return false;
        }
        const toggle = form.querySelector('[data-km-historic-toggle]');
        return toggle ? toggle.checked : false;
    }

    function initKmAutofill() {
        document.querySelectorAll('[data-km-source]').forEach(function (source) {
            const form = source.closest('form');
            if (!form) {
                return;
            }
            const targets = form.querySelectorAll('[data-km-target]');
            if (targets.length === 0) {
                return;
            }

            function vehiculoKm() {
                const option = source.options[source.selectedIndex];
                if (!option || !option.value) {
                    return null;
                }
                return option.getAttribute('data-km') || '0';
            }

            function apply() {
                const km = vehiculoKm();
                if (km === null) {
                    return;
                }
                const historico = isKmHistorico(form);
                targets.forEach(function (target) {
                    const mode = target.getAttribute('data-km-mode') || 'fill';
                    let minKm = historico ? 0 : Number(km);
                    if (!historico && target.hasAttribute('data-km-regreso')) {
                        const salidaEl = form.querySelector('#km_salida') || form.querySelector('[name="km_salida"]');
                        minKm = Math.max(minKm, salidaEl ? Number(salidaEl.value || 0) : 0);
                    }
                    target.min = String(minKm);
                    if (!historico && mode === 'fill' && (!target.value || Number(target.value) < Number(km))) {
                        target.value = km;
                    }
                    const hint = findKmHint(target);
                    if (hint) {
                        hint.textContent = buildKmHintText(target, km, form);
                    }
                });
            }

            source.addEventListener('change', apply);
            const historicoToggle = form.querySelector('[data-km-historic-toggle]');
            if (historicoToggle) {
                historicoToggle.addEventListener('change', apply);
            }
            const salidaEl = form.querySelector('#km_salida') || form.querySelector('[name="km_salida"]');
            if (salidaEl) {
                salidaEl.addEventListener('input', apply);
                salidaEl.addEventListener('change', apply);
            }
            apply();
        });

        document.querySelectorAll('[data-km-hint][data-km-value]').forEach(function (hint) {
            const km = hint.getAttribute('data-km-value') || '0';
            const historico = hint.hasAttribute('data-km-historic')
                || (hint.closest('form') && isKmHistorico(hint.closest('form')));
            let msg = 'Kilometraje actual del vehículo: ' + formatKmHint(km) + ' km.';
            if (historico) {
                msg += ' Registro histórico: puede ingresar el kilometraje que tenía el vehículo en esa fecha.';
            } else if (hint.hasAttribute('data-km-regreso-static')) {
                const salida = Number(hint.getAttribute('data-km-salida') || 0);
                const minKm = Math.max(Number(km), salida);
                msg += ' Mínimo para regreso: ' + formatKmHint(minKm) + ' km (salida: ' + formatKmHint(salida) + ').';
            } else if (!historico) {
                msg += ' Debe ser igual o mayor.';
            }
            hint.textContent = msg;
        });

        document.querySelectorAll('[data-km-historic-toggle]').forEach(function (toggle) {
            const form = toggle.closest('form');
            if (!form) {
                return;
            }
            function refreshStaticHints() {
                const hintEl = form.querySelector('[data-km-hint][data-km-value]');
                const km = hintEl ? (hintEl.getAttribute('data-km-value') || '0') : '0';
                form.querySelectorAll('[data-km-hint][data-km-value]').forEach(function (hint) {
                    let msg = 'Kilometraje actual del vehículo: ' + formatKmHint(km) + ' km.';
                    if (toggle.checked) {
                        msg += ' Registro histórico: puede ingresar el kilometraje que tenía el vehículo en esa fecha.';
                    } else {
                        msg += ' Debe ser igual o mayor.';
                    }
                    hint.textContent = msg;
                });
                const kmInput = form.querySelector('#kilometraje, [name="kilometraje"]');
                if (kmInput) {
                    kmInput.min = toggle.checked ? '0' : km;
                }
            }
            toggle.addEventListener('change', refreshStaticHints);
            refreshStaticHints();
        });
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

    /* ——— Lightbox de galerías ——— */
    function initLightbox() {
        const links = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
        if (links.length === 0) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.className = 'lightbox-overlay';
        overlay.innerHTML =
            '<button type="button" class="lightbox-btn lightbox-close" aria-label="Cerrar">&times;</button>' +
            '<button type="button" class="lightbox-btn lightbox-prev" aria-label="Anterior">&#8249;</button>' +
            '<img alt="Imagen ampliada">' +
            '<button type="button" class="lightbox-btn lightbox-next" aria-label="Siguiente">&#8250;</button>' +
            '<span class="lightbox-counter"></span>';
        document.body.appendChild(overlay);

        const img = overlay.querySelector('img');
        const counter = overlay.querySelector('.lightbox-counter');
        const btnPrev = overlay.querySelector('.lightbox-prev');
        const btnNext = overlay.querySelector('.lightbox-next');
        let current = 0;

        function render() {
            const href = links[current].getAttribute('href');
            img.setAttribute('src', href);
            counter.textContent = (current + 1) + ' / ' + links.length;
            const multiple = links.length > 1;
            btnPrev.style.display = multiple ? '' : 'none';
            btnNext.style.display = multiple ? '' : 'none';
            counter.style.display = multiple ? '' : 'none';
        }

        function open(index) {
            current = index;
            render();
            overlay.classList.add('open');
        }

        function close() {
            overlay.classList.remove('open');
        }

        function go(step) {
            current = (current + step + links.length) % links.length;
            render();
        }

        links.forEach(function (link, index) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                open(index);
            });
        });

        overlay.querySelector('.lightbox-close').addEventListener('click', close);
        btnPrev.addEventListener('click', function () { go(-1); });
        btnNext.addEventListener('click', function () { go(1); });
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                close();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (!overlay.classList.contains('open')) {
                return;
            }
            if (e.key === 'Escape') { close(); }
            else if (e.key === 'ArrowLeft') { go(-1); }
            else if (e.key === 'ArrowRight') { go(1); }
        });
    }

    /* ——— Previsualización de imágenes antes de subir ——— */
    function initImagePreview() {
        const input = document.getElementById('fotos');
        const preview = document.getElementById('fotos-preview');
        if (!input || !preview) {
            return;
        }
        input.addEventListener('change', function () {
            preview.innerHTML = '';
            Array.prototype.slice.call(input.files).forEach(function (file) {
                if (!file.type.startsWith('image/')) {
                    return;
                }
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.onload = function () { URL.revokeObjectURL(image.src); };
                preview.appendChild(image);
            });
        });
    }

    /* ——— Catálogo de conductores en comisiones ——— */
    function initConductorSelect() {
        const select = document.querySelector('[data-conductor-select]');
        if (!select) return;

        const nombreInput = document.getElementById('conductor_nombre');
        const telefonoHint = document.querySelector('[data-conductor-telefono]');

        function sync() {
            const option = select.options[select.selectedIndex];
            if (!option || !option.value) {
                if (telefonoHint) telefonoHint.textContent = '';
                return;
            }
            const nombre = option.getAttribute('data-nombre') || option.textContent.trim();
            const telefono = option.getAttribute('data-telefono') || '';
            if (nombreInput) {
                nombreInput.value = nombre;
            }
            if (telefonoHint) {
                telefonoHint.textContent = telefono ? 'Teléfono: ' + telefono : '';
            }
        }

        select.addEventListener('change', sync);
        sync();
    }

    /* ——— Responsable de regreso en comisiones ——— */
    function initResponsableRegresoSelect() {
        const select = document.querySelector('[data-responsable-regreso-select]');
        if (!select) return;

        const nombreInput = document.getElementById('responsable_regreso_nombre');

        function sync() {
            const option = select.options[select.selectedIndex];
            if (!nombreInput) return;
            if (!option || !option.value) {
                nombreInput.value = '';
                return;
            }
            nombreInput.value = option.getAttribute('data-nombre') || option.textContent.trim();
        }

        select.addEventListener('change', sync);
        if (select.value) {
            sync();
        }
    }

    /* ——— Modales y catálogos dinámicos ——— */
    function lockBodyModal() {
        document.body.classList.add('modal-open');
    }

    function unlockBodyModal() {
        if (!document.querySelector('.modal-overlay.open')) {
            document.body.classList.remove('modal-open');
        }
    }

    function getTopModal() {
        const openModals = document.querySelectorAll('.modal-overlay.open');
        if (openModals.length === 0) {
            return null;
        }
        return openModals[openModals.length - 1];
    }

    function appFetchUrl(path) {
        const base = document.body.getAttribute('data-app-base') || '';
        const normalized = String(path || '').replace(/^\//, '');
        if (!base) {
            return '/' + normalized;
        }
        return base + '/' + normalized;
    }

    function rebuildSelect(select, items, buildOption, options) {
        options = options || {};
        const current = options.selectedId ? String(options.selectedId) : select.value;
        const firstOption = select.options[0];
        const emptyLabel = firstOption && firstOption.value === ''
            ? firstOption.textContent
            : 'Seleccione…';

        select.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = emptyLabel;
        select.appendChild(empty);

        items.forEach(function (item) {
            select.appendChild(buildOption(item));
        });

        if (current && Array.prototype.some.call(select.options, function (opt) {
            return opt.value === current;
        })) {
            select.value = current;
        }

        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    window.SICV = window.SICV || {};

    window.SICV.refreshCatalog = function (type, options) {
        options = options || {};
        const endpoints = {
            planteles: 'catalogos/api/planteles',
            areas: 'catalogos/api/areas',
            conductores: 'catalogos/api/conductores',
            proveedores: 'catalogos/api/proveedores',
            responsables: 'catalogos/api/responsables'
        };
        const endpoint = endpoints[type];
        if (!endpoint) {
            return Promise.resolve();
        }

        let fetchUrl = appFetchUrl(endpoint);
        if (type === 'proveedores' && options.tipo) {
            fetchUrl += '?tipo=' + encodeURIComponent(options.tipo);
        }

        return fetch(fetchUrl, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    return;
                }

                const items = result.data.items || [];

                if (type === 'planteles') {
                    document.querySelectorAll('[data-plantel-select]').forEach(function (select) {
                        rebuildSelect(select, items, function (item) {
                            const option = document.createElement('option');
                            option.value = String(item.id);
                            option.textContent = item.label;
                            return option;
                        }, options);
                    });
                }

                if (type === 'areas') {
                    document.querySelectorAll('[data-area-select], [data-conductor-area-select], [data-responsable-area-select]').forEach(function (select) {
                        rebuildSelect(select, items, function (item) {
                            const option = document.createElement('option');
                            option.value = String(item.id);
                            option.textContent = item.label;
                            return option;
                        }, options);
                    });
                }

                if (type === 'conductores') {
                    document.querySelectorAll('[data-conductor-select], [data-responsable-regreso-select]').forEach(function (select) {
                        rebuildSelect(select, items, function (item) {
                            const option = document.createElement('option');
                            option.value = String(item.id);
                            option.textContent = item.label;
                            option.setAttribute('data-nombre', item.nombre);
                            option.setAttribute('data-telefono', item.telefono);
                            return option;
                        }, options);
                    });
                }

                if (type === 'proveedores') {
                    document.querySelectorAll('[data-proveedor-select]').forEach(function (select) {
                        const selectTipo = select.getAttribute('data-proveedor-tipo') || '';
                        const refreshTipo = options.tipo || '';
                        if (selectTipo === 'combustible') {
                            if (refreshTipo !== 'combustible') {
                                return;
                            }
                        } else if (refreshTipo === 'combustible') {
                            return;
                        }
                        rebuildSelect(select, items, function (item) {
                            const option = document.createElement('option');
                            option.value = String(item.id);
                            option.textContent = item.label;
                            option.setAttribute('data-rfc', item.rfc || '');
                            option.setAttribute('data-telefono', item.telefono || '');
                            option.setAttribute('data-email', item.email || '');
                            option.setAttribute('data-direccion', item.direccion || '');
                            return option;
                        }, options);
                    });
                }

                if (type === 'responsables') {
                    document.querySelectorAll('[data-responsable-select]').forEach(function (select) {
                        const current = options.selectedId ? String(options.selectedId) : select.value;
                        select.innerHTML = '';
                        items.forEach(function (item) {
                            const option = document.createElement('option');
                            option.value = String(item.id);
                            option.textContent = item.label;
                            select.appendChild(option);
                        });
                        if (current && Array.prototype.some.call(select.options, function (opt) {
                            return opt.value === current;
                        })) {
                            select.value = current;
                        }
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }
            })
            .catch(function () {});
    };

    function submitQuickForm(form, submitBtn, onSuccess, onError) {
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        return fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok || !result.data.ok) {
                    onError(result.data.error || 'No se pudo guardar el registro.');
                    return;
                }
                return onSuccess(result.data);
            })
            .catch(function () {
                onError('Error de conexión. Intente de nuevo.');
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
    }

    /* ——— Modal rápido de área solicitante ——— */
    function initAreaQuickModal() {
        const modal = document.querySelector('[data-area-quick-modal]');
        const openBtns = document.querySelectorAll('[data-area-quick-open]');
        if (!modal || openBtns.length === 0) return;

        const form = modal.querySelector('[data-area-quick-form]');
        const errorBox = modal.querySelector('[data-area-quick-error]');
        const submitBtn = modal.querySelector('[data-area-quick-submit]');

        function showError(msg) {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.hidden = false;
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function open() {
            clearError();
            if (document.querySelector('.modal-overlay.open')) {
                modal.classList.add('modal-overlay--stacked');
            }
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            lockBodyModal();
            const firstInput = form ? form.querySelector('input:not([type="hidden"])') : null;
            if (firstInput) {
                firstInput.focus();
            }
        }

        function close() {
            modal.classList.remove('open');
            modal.classList.remove('modal-overlay--stacked');
            modal.setAttribute('aria-hidden', 'true');
            unlockBodyModal();
            if (form) {
                form.reset();
            }
            clearError();
        }

        openBtns.forEach(function (btn) {
            btn.addEventListener('click', open);
        });

        modal.querySelectorAll('[data-area-quick-close]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && getTopModal() === modal) {
                close();
            }
        });

        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearError();
            submitQuickForm(form, submitBtn, function (data) {
                return window.SICV.refreshCatalog('areas', { selectedId: data.area.id }).then(function () {
                    close();
                });
            }, showError);
        });
    }

    /* ——— Modal rápido de plantel ——— */
    function initPlantelQuickModal() {
        const modal = document.querySelector('[data-plantel-quick-modal]');
        const openBtns = document.querySelectorAll('[data-plantel-quick-open]');
        if (!modal || openBtns.length === 0) return;

        const form = modal.querySelector('[data-plantel-quick-form]');
        const errorBox = modal.querySelector('[data-plantel-quick-error]');
        const submitBtn = modal.querySelector('[data-plantel-quick-submit]');

        function showError(msg) {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.hidden = false;
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function open() {
            clearError();
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            lockBodyModal();
            const firstInput = form ? form.querySelector('input:not([type="hidden"])') : null;
            if (firstInput) {
                firstInput.focus();
            }
        }

        function close() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            unlockBodyModal();
            if (form) {
                form.reset();
            }
            clearError();
        }

        openBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                open();
            });
        });

        modal.querySelectorAll('[data-plantel-quick-close]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && getTopModal() === modal) {
                close();
            }
        });

        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearError();
            submitQuickForm(form, submitBtn, function (data) {
                return window.SICV.refreshCatalog('planteles', { selectedId: data.plantel.id }).then(function () {
                    close();
                });
            }, showError);
        });
    }

    /* ——— Modal rápido de conductor ——— */
    function initConductorQuickModal() {
        const modal = document.querySelector('[data-conductor-quick-modal]');
        const openBtns = document.querySelectorAll('[data-conductor-quick-open]');
        if (!modal || openBtns.length === 0) return;

        const form = modal.querySelector('[data-conductor-quick-form]');
        const errorBox = modal.querySelector('[data-conductor-quick-error]');
        const submitBtn = modal.querySelector('[data-conductor-quick-submit]');
        const modalAreaSelect = modal.querySelector('[data-conductor-area-select]');
        let targetSelectId = 'conductor_id';

        function showError(msg) {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.hidden = false;
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function open(btn) {
            targetSelectId = btn.getAttribute('data-target-select') || 'conductor_id';
            clearError();
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            lockBodyModal();

            const areaSolicitante = document.getElementById('area_solicitante_id');
            if (modalAreaSelect && areaSolicitante && areaSolicitante.value) {
                modalAreaSelect.value = areaSolicitante.value;
            }

            const firstInput = form ? form.querySelector('input:not([type="hidden"])') : null;
            if (firstInput) {
                firstInput.focus();
            }
        }

        function close() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            unlockBodyModal();
            if (form) {
                form.reset();
            }
            clearError();
        }

        function applyConductor(conductor) {
            return window.SICV.refreshCatalog('conductores').then(function () {
                const target = document.getElementById(targetSelectId);
                if (target) {
                    target.value = String(conductor.id);
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        openBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                open(btn);
            });
        });

        modal.querySelectorAll('[data-conductor-quick-close]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && getTopModal() === modal) {
                close();
            }
        });

        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearError();
            submitQuickForm(form, submitBtn, function (data) {
                return applyConductor(data.conductor).then(function () {
                    close();
                });
            }, showError);
        });
    }

    /* ——— Modal rápido de proveedor ——— */
    function initProveedorQuickModal() {
        const modal = document.querySelector('[data-proveedor-quick-modal]');
        const openBtns = document.querySelectorAll('[data-proveedor-quick-open]');
        if (!modal || openBtns.length === 0) return;

        const form = modal.querySelector('[data-proveedor-quick-form]');
        const errorBox = modal.querySelector('[data-proveedor-quick-error]');
        const submitBtn = modal.querySelector('[data-proveedor-quick-submit]');

        function showError(msg) {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.hidden = false;
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function open() {
            clearError();
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            lockBodyModal();
            const firstInput = form ? form.querySelector('input:not([type="hidden"])') : null;
            if (firstInput) {
                firstInput.focus();
            }
        }

        function close() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            unlockBodyModal();
            if (form) {
                form.reset();
            }
            clearError();
        }

        openBtns.forEach(function (btn) {
            btn.addEventListener('click', open);
        });

        modal.querySelectorAll('[data-proveedor-quick-close]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && getTopModal() === modal) {
                close();
            }
        });

        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearError();
            submitQuickForm(form, submitBtn, function (data) {
                const tipoInput = form.querySelector('input[name="tipo"]');
                const tipo = tipoInput ? String(tipoInput.value || '') : '';
                const refreshOptions = { selectedId: data.proveedor.id };
                if (tipo === 'combustible') {
                    refreshOptions.tipo = 'combustible';
                }
                return window.SICV.refreshCatalog('proveedores', refreshOptions).then(function () {
                    close();
                });
            }, showError);
        });
    }

    /* ——— Modal rápido de responsable ——— */
    function initResponsableQuickModal() {
        const modal = document.querySelector('[data-responsable-quick-modal]');
        const openBtns = document.querySelectorAll('[data-responsable-quick-open]');
        if (!modal || openBtns.length === 0) return;

        const form = modal.querySelector('[data-responsable-quick-form]');
        const errorBox = modal.querySelector('[data-responsable-quick-error]');
        const submitBtn = modal.querySelector('[data-responsable-quick-submit]');

        function showError(msg) {
            if (!errorBox) return;
            errorBox.textContent = msg;
            errorBox.hidden = false;
        }

        function clearError() {
            if (!errorBox) return;
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function open() {
            clearError();
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            lockBodyModal();
            const firstInput = form ? form.querySelector('input:not([type="hidden"])') : null;
            if (firstInput) {
                firstInput.focus();
            }
        }

        function close() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            unlockBodyModal();
            if (form) {
                form.reset();
            }
            clearError();
        }

        openBtns.forEach(function (btn) {
            btn.addEventListener('click', open);
        });

        modal.querySelectorAll('[data-responsable-quick-close]').forEach(function (btn) {
            btn.addEventListener('click', close);
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && getTopModal() === modal) {
                close();
            }
        });

        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearError();
            submitQuickForm(form, submitBtn, function (data) {
                return window.SICV.refreshCatalog('responsables', { selectedId: data.responsable.id }).then(function () {
                    close();
                });
            }, showError);
        });
    }

    /* ——— Combustible (medidor + select) ——— */
    function combustibleGaugeLevel(pct) {
        if (pct <= 0) {
            return 'empty';
        }
        if (pct <= 25) {
            return 'low';
        }
        return 'normal';
    }

    function normalizeCombustiblePct(pct) {
        if (Number.isNaN(pct)) {
            return null;
        }
        pct = Math.max(0, Math.min(100, pct));
        return Math.round(pct / 25) * 25;
    }

    function syncCombustibleGauge(group) {
        const select = group.querySelector('[data-combustible-value]');
        const range = group.querySelector('[data-combustible-range]');
        const fill = group.querySelector('[data-combustible-fill]');
        const display = group.querySelector('[data-combustible-display]');
        if (!select) {
            return;
        }

        let pct = normalizeCombustiblePct(parseInt(select.value, 10));
        if (pct === null) {
            pct = select.required ? 100 : 0;
            select.value = String(pct);
        }

        if (range) {
            range.value = String(pct);
        }
        if (fill) {
            fill.style.height = pct + '%';
            fill.setAttribute('data-level', combustibleGaugeLevel(pct));
        }
        if (display) {
            display.textContent = pct + '%';
        }

        group.querySelectorAll('[data-combustible-preset]').forEach(function (btn) {
            const preset = parseInt(btn.getAttribute('data-combustible-preset'), 10);
            btn.classList.toggle('is-active', preset === pct);
        });
    }

    function setCombustiblePct(group, pct) {
        const select = group.querySelector('[data-combustible-value]');
        if (!select) {
            return;
        }
        pct = normalizeCombustiblePct(pct);
        if (pct === null) {
            return;
        }
        select.value = String(pct);
        syncCombustibleGauge(group);
    }

    function initCombustibleFields() {
        document.querySelectorAll('[data-combustible-gauge]').forEach(function (group) {
            const select = group.querySelector('[data-combustible-value]');
            const range = group.querySelector('[data-combustible-range]');

            syncCombustibleGauge(group);

            if (select) {
                select.addEventListener('change', function () {
                    syncCombustibleGauge(group);
                });
            }

            if (range) {
                range.addEventListener('input', function () {
                    setCombustiblePct(group, parseInt(range.value, 10));
                });
            }

            group.querySelectorAll('[data-combustible-preset]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const preset = btn.getAttribute('data-combustible-preset');
                    if (preset !== null) {
                        setCombustiblePct(group, parseInt(preset, 10));
                    }
                });
            });
        });

        document.querySelectorAll('form').forEach(function (form) {
            if (form.hasAttribute('data-combustible-submit-bound')) {
                return;
            }
            form.setAttribute('data-combustible-submit-bound', '1');
            form.addEventListener('submit', function () {
                form.querySelectorAll('[data-combustible-gauge]').forEach(function (group) {
                    const select = group.querySelector('[data-combustible-value]');
                    if (!select || !select.name) {
                        return;
                    }
                    setCombustiblePct(group, parseInt(select.value, 10));
                });
            });
        });
    }

    /* ——— Folio con prefijo fijo ——— */
    function syncFolioInput(wrap, finalize) {
        const prefixEl = wrap.querySelector('[data-folio-prefix]');
        const seqInput = wrap.querySelector('[data-folio-seq]');
        const hidden = wrap.querySelector('[data-folio-value]');
        if (!prefixEl || !seqInput || !hidden) {
            return;
        }

        const pad = parseInt(wrap.getAttribute('data-folio-pad') || '4', 10);
        const raw = String(seqInput.value).replace(/\D/g, '');

        if (!finalize) {
            if (seqInput.value !== raw) {
                seqInput.value = raw;
            }
            if (raw === '') {
                return;
            }
            const num = parseInt(raw, 10);
            if (!isNaN(num) && num > 0) {
                hidden.value = prefixEl.textContent + String(num).padStart(pad, '0');
            }
            return;
        }

        let num = parseInt(raw, 10);
        if (raw === '' || isNaN(num) || num < 1) {
            const fallback = wrap.getAttribute('data-folio-default-seq') || String(1).padStart(pad, '0');
            seqInput.value = fallback;
            num = parseInt(fallback, 10);
        } else {
            seqInput.value = String(num).padStart(pad, '0');
        }

        hidden.value = prefixEl.textContent + String(num).padStart(pad, '0');
    }

    function initFolioInputs() {
        document.querySelectorAll('[data-folio-input]').forEach(function (wrap) {
            const seqInput = wrap.querySelector('[data-folio-seq]');
            if (!seqInput || wrap.hasAttribute('data-folio-bound')) {
                return;
            }
            wrap.setAttribute('data-folio-bound', '1');

            seqInput.addEventListener('input', function () {
                syncFolioInput(wrap, false);
            });

            seqInput.addEventListener('blur', function () {
                syncFolioInput(wrap, true);
            });

            seqInput.addEventListener('focus', function () {
                seqInput.select();
            });
        });

        document.querySelectorAll('form').forEach(function (form) {
            if (form.hasAttribute('data-folio-submit-bound')) {
                return;
            }
            if (!form.querySelector('[data-folio-input]')) {
                return;
            }
            form.setAttribute('data-folio-submit-bound', '1');
            form.addEventListener('submit', function () {
                form.querySelectorAll('[data-folio-input]').forEach(function (w) {
                    syncFolioInput(w, true);
                });
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
        initCombustibleFields();
        initFolioInputs();
        initLucesAutofill();
        initHerramientasChecklist();
        initHerramientasAutofill();
        initConductorSelect();
        initResponsableRegresoSelect();
        initAreaQuickModal();
        initPlantelQuickModal();
        initConductorQuickModal();
        initProveedorQuickModal();
        initResponsableQuickModal();
        initLightbox();
        initImagePreview();
        window.SICV.initSignaturePads();

        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', toggleTheme);
        });
    });
})();
