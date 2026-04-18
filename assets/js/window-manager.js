/* =============================================
   Window Manager
   Gestionnaire de fenêtres flottantes
   ============================================= */
(function () {
    'use strict';

    const wins = {};   // id -> { url, title, icon, minimized, maximized, prevGeom }
    let zTop = 100;
    let winCount = 0;

    /* ---- API publique ---- */
    window.WM = {
        open:       openWindow,
        close:      closeWindow,
        minimize:   minimizeWindow,
        restore:    restoreWindow,
        toggleMax:  toggleMaximize
    };

    /* ---- Ouvrir / créer ---- */
    function openWindow(url, title, icon) {
        // Dédoublonnage par URL (sans paramètre embedded)
        const baseUrl = stripEmbedded(url);
        for (const id in wins) {
            if (stripEmbedded(wins[id].url) === baseUrl) {
                wins[id].minimized ? restoreWindow(id) : focusWindow(id);
                return;
            }
        }
        const id = 'w' + (++winCount);
        createWindow(id, url, title, icon);
    }

    function createWindow(id, url, title, icon) {
        showDesktop();

        const desktop = document.getElementById('win-desktop');
        const n = Object.keys(wins).length;
        const cascade = n % 10;
        const x = 20 + cascade * 28;
        const y = 10 + cascade * 22;
        const w = Math.min(1100, Math.max(580, Math.round(desktop.clientWidth * 0.72)));
        const h = Math.max(400, Math.round(desktop.clientHeight * 0.80));

        const frame = document.createElement('div');
        frame.id = id;
        frame.className = 'win-frame';
        frame.style.cssText = `left:${x}px;top:${y}px;width:${w}px;height:${h}px;z-index:${++zTop}`;
        frame.innerHTML = buildFrameHTML(id, title, icon);
        desktop.appendChild(frame);

        // Comportements
        makeDraggable(frame.querySelector('.win-titlebar'), frame, id);
        makeResizable(frame, id);
        frame.addEventListener('mousedown', () => focusWindow(id));
        frame.querySelector('.win-titlebar').addEventListener('dblclick', function (e) {
            if (!e.target.closest('.win-controls')) toggleMaximize(id);
        });

        // Iframe ou page de lancement (sites externes bloqués par X-Frame-Options)
        if (isExternalUrl(url)) {
            frame.querySelector('.win-body').innerHTML = buildExternalLaunchHTML(url, title);
        } else {
            const iframe = frame.querySelector('iframe');
            iframe.addEventListener('load', () => onIframeLoad(iframe, id));
            iframe.src = embedUrl(url);
        }

        wins[id] = { url, title, icon, minimized: false, maximized: false, prevGeom: null };
        createTab(id, title, icon);
        focusWindow(id);
    }

    function buildFrameHTML(id, title, icon) {
        return `
        <div class="win-titlebar">
            <i class="${esc(icon)} win-icon"></i>
            <span class="win-title">${esc(title)}</span>
            <div class="win-controls">
                <button class="win-btn win-min" onclick="WM.minimize('${id}')" title="Réduire">&#8722;</button>
                <button class="win-btn win-max" onclick="WM.toggleMax('${id}')" title="Agrandir">&#9633;</button>
                <button class="win-btn win-cls" onclick="WM.close('${id}')" title="Fermer">&#10005;</button>
            </div>
        </div>
        <div class="win-body">
            <div class="win-loader"><i class="fas fa-circle-notch fa-spin"></i></div>
            <iframe></iframe>
        </div>
        <div class="win-resize" data-d="e"></div>
        <div class="win-resize" data-d="s"></div>
        <div class="win-resize" data-d="w"></div>
        <div class="win-resize" data-d="se"></div>
        <div class="win-resize" data-d="sw"></div>`;
    }

    /* ---- URL externe (X-Frame-Options) ---- */
    function isExternalUrl(url) {
        try { return new URL(url, location.origin).origin !== location.origin; }
        catch(e) { return false; }
    }

    function buildExternalLaunchHTML(url, title) {
        const safeUrl = url.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
        return `<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;text-align:center;padding:40px;gap:16px;font-family:inherit">
            <i class="fas fa-globe" style="font-size:52px;color:#ddd"></i>
            <div style="font-size:18px;font-weight:600;color:#333">${esc(title)}</div>
            <div style="font-size:12px;color:#bbb;word-break:break-all;max-width:400px">${esc(url)}</div>
            <a href="${safeUrl}" target="_blank" style="background:#e4002b;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-size:14px;display:inline-flex;align-items:center;gap:8px;margin-top:8px">
                <i class="fas fa-external-link-alt"></i>&nbsp;Ouvrir dans un onglet
            </a>
            <p style="font-size:12px;color:#ccc;max-width:320px;line-height:1.5;margin:0">Les sites externes ne peuvent pas être intégrés directement pour des raisons de sécurité.</p>
        </div>`;
    }

    /* ---- Chargement iframe ---- */
    function onIframeLoad(iframe, id) {
        try {
            const loc = iframe.contentWindow.location;
            if (!loc || loc.href === 'about:blank') return;
            // Réinjecter embedded=1 si la navigation l'a perdu
            if (!loc.search.includes('embedded=1')) {
                iframe.src = embedUrl(loc.href);
                return;
            }
        } catch (e) {
            // Cross-origin (lien externe) : masquer le loader quand même
            const frame = document.getElementById(id);
            if (frame) frame.querySelector('.win-loader').style.display = 'none';
            return;
        }

        const frame = document.getElementById(id);
        if (frame) frame.querySelector('.win-loader').style.display = 'none';
    }

    /* ---- Onglets ---- */
    function createTab(id, title, icon) {
        const list = document.getElementById('win-tabs-list');
        const tab = document.createElement('div');
        tab.id = 'tab-' + id;
        tab.className = 'win-tab';
        tab.innerHTML = `
            <i class="${esc(icon)} tab-icon"></i>
            <span class="tab-title">${esc(title)}</span>
            <button class="tab-close" onclick="event.stopPropagation();WM.close('${id}')" title="Fermer">&#10005;</button>`;
        tab.addEventListener('click', () => {
            wins[id] && wins[id].minimized ? restoreWindow(id) : focusWindow(id);
        });
        list.appendChild(tab);
    }

    /* ---- Focus ---- */
    function focusWindow(id) {
        document.querySelectorAll('.win-frame').forEach(f => f.classList.remove('win-focused'));
        document.querySelectorAll('.win-tab').forEach(t => t.classList.remove('active'));

        const frame = document.getElementById(id);
        if (frame && wins[id] && !wins[id].minimized) {
            frame.style.zIndex = ++zTop;
            frame.classList.add('win-focused');
        }
        const tab = document.getElementById('tab-' + id);
        if (tab) tab.classList.add('active');
    }

    /* ---- Minimiser / Restaurer ---- */
    function minimizeWindow(id) {
        const frame = document.getElementById(id);
        if (!frame || !wins[id]) return;
        wins[id].minimized = true;
        frame.classList.add('win-hidden');
        const tab = document.getElementById('tab-' + id);
        if (tab) { tab.classList.remove('active'); tab.classList.add('win-tab-minimized'); }

        const next = Object.keys(wins).filter(w => w !== id && !wins[w].minimized).pop();
        if (next) focusWindow(next);
    }

    function restoreWindow(id) {
        const frame = document.getElementById(id);
        if (!frame || !wins[id]) return;
        wins[id].minimized = false;
        frame.classList.remove('win-hidden');
        const tab = document.getElementById('tab-' + id);
        if (tab) tab.classList.remove('win-tab-minimized');
        focusWindow(id);
    }

    /* ---- Maximiser / Restaurer ---- */
    function toggleMaximize(id) {
        const frame = document.getElementById(id);
        if (!frame || !wins[id]) return;
        if (wins[id].maximized) {
            const s = wins[id].prevGeom;
            frame.style.left   = s.l;
            frame.style.top    = s.t;
            frame.style.width  = s.w;
            frame.style.height = s.h;
            frame.classList.remove('win-maximized');
            wins[id].maximized = false;
        } else {
            wins[id].prevGeom = { l: frame.style.left, t: frame.style.top, w: frame.style.width, h: frame.style.height };
            frame.classList.add('win-maximized');
            wins[id].maximized = true;
        }
    }

    /* ---- Fermer ---- */
    function closeWindow(id) {
        document.getElementById(id)?.remove();
        document.getElementById('tab-' + id)?.remove();
        delete wins[id];

        if (Object.keys(wins).length === 0) {
            hideDesktop();
        } else {
            const next = Object.keys(wins).filter(w => !wins[w].minimized).pop();
            if (next) focusWindow(next);
        }
    }

    /* ---- Bureau ---- */
    function showDesktop() {
        document.getElementById('win-desktop').classList.add('wm-on');
        document.getElementById('page-content-main').classList.add('wm-hidden');
        document.getElementById('win-tabbar').classList.add('wm-on');
    }

    function hideDesktop() {
        document.getElementById('win-desktop').classList.remove('wm-on');
        document.getElementById('page-content-main').classList.remove('wm-hidden');
        document.getElementById('win-tabbar').classList.remove('wm-on');
    }

    /* ---- Drag ---- */
    function makeDraggable(handle, frame, id) {
        handle.addEventListener('mousedown', function (e) {
            if (e.target.closest('.win-controls') || wins[id]?.maximized) return;
            e.preventDefault();
            const ox = e.clientX - frame.offsetLeft;
            const oy = e.clientY - frame.offsetTop;

            function onMove(e) {
                const d = document.getElementById('win-desktop');
                frame.style.left = Math.max(0, Math.min(e.clientX - ox, d.clientWidth  - frame.offsetWidth))  + 'px';
                frame.style.top  = Math.max(0, Math.min(e.clientY - oy, d.clientHeight - 36))                  + 'px';
            }
            function onUp() {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    /* ---- Redimensionnement ---- */
    function makeResizable(frame, id) {
        frame.querySelectorAll('.win-resize').forEach(handle => {
            handle.addEventListener('mousedown', function (e) {
                if (wins[id]?.maximized) return;
                e.stopPropagation();
                e.preventDefault();
                const dir = this.dataset.d;
                const sx = e.clientX, sy = e.clientY;
                const sw = frame.offsetWidth, sh = frame.offsetHeight;
                const sl = frame.offsetLeft;

                function onMove(e) {
                    const dx = e.clientX - sx, dy = e.clientY - sy;
                    if (dir.includes('e'))  frame.style.width  = Math.max(340, sw + dx) + 'px';
                    if (dir.includes('s'))  frame.style.height = Math.max(220, sh + dy) + 'px';
                    if (dir.includes('w')) {
                        const nw = Math.max(340, sw - dx);
                        frame.style.width = nw + 'px';
                        frame.style.left  = (sl + sw - nw) + 'px';
                    }
                }
                function onUp() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                }
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        });
    }

    /* ---- Utilitaires ---- */
    function embedUrl(url) {
        try {
            const u = new URL(url, location.origin);
            u.searchParams.set('embedded', '1');
            return u.toString();
        } catch (e) {
            return url + (url.includes('?') ? '&' : '?') + 'embedded=1';
        }
    }

    function stripEmbedded(url) {
        try {
            const u = new URL(url, location.origin);
            u.searchParams.delete('embedded');
            return u.toString();
        } catch (e) { return url; }
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    /* ---- Détection mobile ---- */
    function isMobile() {
        return window.innerWidth < 1024 || window.matchMedia('(pointer: coarse)').matches;
    }

    /* ---- Initialisation ---- */
    document.addEventListener('DOMContentLoaded', function () {

        // Sidebar backdrop (mobile) : clic en dehors pour fermer
        const backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
        backdrop.addEventListener('click', function () {
            document.getElementById('sidebar').classList.remove('active');
            backdrop.classList.remove('active');
        });
        // Ouvrir/fermer le backdrop avec la sidebar
        document.querySelector('.sidebar-toggle')?.addEventListener('click', function () {
            const open = document.getElementById('sidebar').classList.contains('active');
            backdrop.classList.toggle('active', open);
        });

        // Sur mobile : navigation normale, pas de fenêtres
        if (isMobile()) return;

        // Mode bureau actif (layout flex)
        document.body.classList.add('wm-ready');

        // Intercepter les liens de la sidebar
        document.querySelectorAll('.sidebar-nav a:not([target="_blank"])').forEach(function (link) {
            link.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (!href || href.includes('logout') || href.startsWith('#')) return;
                if (this.dataset.reload) { window.location.href = href; return; }
                e.preventDefault();

                const iconEl = this.querySelector('i');
                const icon = iconEl ? iconEl.className : 'fas fa-window-maximize';

                // Titre = texte du lien sans badge et sans icône
                const clone = this.cloneNode(true);
                clone.querySelectorAll('i, .sidebar-badge-retard').forEach(n => n.remove());
                const title = clone.textContent.trim() || 'Module';

                WM.open(href, title, icon);
            });
        });
    });

})();
