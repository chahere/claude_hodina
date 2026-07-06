// assets/admin.js
// Entrée EasyAdmin uniquement.
// Important : ne pas importer stimulus_bootstrap.js ici, sinon Stimulus est démarré deux fois.
import { startStimulusApp } from '@symfony/stimulus-bundle';
import StockController from './controllers/stock_controller.js';
import ProductImagesController from './controllers/product_images_controller.js';
import DeliveryPointWindowsController from './controllers/delivery_point_windows_controller.js';

const app = startStimulusApp();

app.register('stock', StockController);
app.register('product-images', ProductImagesController);
app.register('delivery-point-windows', DeliveryPointWindowsController);

console.log('[ADMIN] Stimulus loaded');
function normalizeMenuText(value) {
    return (value || '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

function installCollapsibleAdminMenuStyles() {
    if (document.getElementById('hodina-admin-collapsible-menu-style')) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'hodina-admin-collapsible-menu-style';
    style.textContent = `
        .hodina-admin-sidebar-enhanced {
            overflow-y: auto;
            max-height: 100vh;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        .hodina-admin-sidebar-enhanced .main-menu,
        .hodina-admin-sidebar-enhanced .menu,
        .hodina-admin-sidebar-enhanced nav {
            padding-bottom: 5.5rem;
        }

        .hodina-admin-menu-section-toggle {
            appearance: none;
            border: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            width: 100%;
            padding: 0;
            text-align: left;
            font: inherit;
            text-transform: inherit;
            letter-spacing: inherit;
        }

        .hodina-admin-menu-section-toggle::after {
            content: '▾';
            margin-left: auto;
            font-size: .8em;
            opacity: .75;
            transition: transform .15s ease;
        }

        .hodina-admin-menu-section-toggle[aria-expanded="false"]::after {
            transform: rotate(-90deg);
        }

        .hodina-admin-menu-hidden {
            display: none !important;
        }
    `;
    document.head.appendChild(style);
}

function findAdminSidebar() {
    return document.querySelector('.sidebar, .main-sidebar, aside.sidebar, aside');
}

function getMenuSectionLabel(element) {
    const clone = element.cloneNode(true);
    clone.querySelectorAll('i, svg, .fa, .fas, .far, .fab').forEach((icon) => icon.remove());
    return clone.textContent.replace(/[▾▸]/g, '').trim();
}

function isMenuSectionHeaderCandidate(candidate, row) {
    if (candidate.matches('.menu-header, .menu-section')) {
        return true;
    }

    // Les entrées réelles du menu EasyAdmin sont aussi des <li>.
    // Si une entrée porte le même libellé que sa section, par exemple
    // section "Utilisateurs" + lien "Utilisateurs", elle ne doit pas
    // devenir une section repliable elle-même.
    return !row.querySelector('a[href]');
}

function findMenuSectionRows(sidebar, sectionNames) {
    const allowed = new Set(sectionNames.map(normalizeMenuText));
    const rows = [];
    const seen = new Set();

    sidebar.querySelectorAll('li, .menu-header, .menu-section').forEach((candidate) => {
        const label = getMenuSectionLabel(candidate);
        const normalized = normalizeMenuText(label);

        if (!allowed.has(normalized)) {
            return;
        }

        const row = candidate.closest('li') || candidate;
        if (!isMenuSectionHeaderCandidate(candidate, row)) {
            return;
        }

        if (seen.has(row)) {
            return;
        }

        seen.add(row);
        rows.push({ row, label });
    });

    return rows;
}

function collectSectionItems(sectionRows) {
    return sectionRows.map(({ row, label }, index) => {
        const nextRow = sectionRows[index + 1]?.row || null;
        const items = [];
        let current = row.nextElementSibling;

        while (current && current !== nextRow) {
            items.push(current);
            current = current.nextElementSibling;
        }

        return { row, label, items };
    });
}

function sectionContainsActiveItem(items) {
    return items.some((item) => item.matches('.active, .selected, [aria-current="page"]')
        || item.querySelector('.active, .selected, [aria-current="page"]'));
}

function setMenuSectionCollapsed(section, collapsed) {
    section.items.forEach((item) => item.classList.toggle('hodina-admin-menu-hidden', collapsed));

    const toggle = section.row.querySelector('.hodina-admin-menu-section-toggle');
    if (toggle) {
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }
}

function makeMenuSectionToggle(section, storageKeyPrefix, shouldCollapseByDefault) {
    if (section.row.dataset.hodinaMenuSectionReady === '1') {
        return;
    }
    section.row.dataset.hodinaMenuSectionReady = '1';

    const label = getMenuSectionLabel(section.row) || section.label;
    const storageKey = `${storageKeyPrefix}:${normalizeMenuText(label)}`;
    const savedState = window.localStorage.getItem(storageKey);
    const collapseInitially = savedState === null
        ? shouldCollapseByDefault(section)
        : savedState === 'collapsed';

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'hodina-admin-menu-section-toggle';
    toggle.setAttribute('aria-expanded', collapseInitially ? 'false' : 'true');
    toggle.textContent = label;

    section.row.textContent = '';
    section.row.appendChild(toggle);
    section.row.classList.add('hodina-admin-menu-section');

    setMenuSectionCollapsed(section, collapseInitially);

    toggle.addEventListener('click', () => {
        const isCollapsed = toggle.getAttribute('aria-expanded') === 'false';
        const nextCollapsed = !isCollapsed;
        setMenuSectionCollapsed(section, nextCollapsed);
        window.localStorage.setItem(storageKey, nextCollapsed ? 'collapsed' : 'expanded');
    });
}

function initCollapsibleAdminMenu() {
    const sidebar = findAdminSidebar();
    if (!sidebar) {
        return;
    }

    const sectionNames = ['Logistique', 'Catalogue', 'Commandes', 'Clients', 'Vendeurs', 'Livreurs', 'Logs', 'Réglages'];
    const sectionRows = findMenuSectionRows(sidebar, sectionNames);
    if (sectionRows.length === 0) {
        return;
    }

    installCollapsibleAdminMenuStyles();
    sidebar.classList.add('hodina-admin-sidebar-enhanced');

    const sections = collectSectionItems(sectionRows);
    const isMobile = window.matchMedia('(max-width: 991px)').matches;
    const storageKeyPrefix = 'hodina:admin-menu:collapsed:v1';

    sections.forEach((section) => {
        makeMenuSectionToggle(section, storageKeyPrefix, (candidate) => {
            if (!isMobile) {
                return false;
            }

            return !sectionContainsActiveItem(candidate.items);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCollapsibleAdminMenu, { once: true });
} else {
    initCollapsibleAdminMenu();
}
