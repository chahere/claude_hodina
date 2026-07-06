import { Controller } from '@hotwired/stimulus';

/**
 * J5Y-A-bis — Interface guidée pour les plages horaires de nouveau point.
 *
 * EasyAdmin rend encore un vrai textarea Symfony, mais le contrôleur le transforme
 * côté formulaire en lignes lisibles : Libellé / Jours / Début / Fin.
 * Le textarea reste la source soumise au backend au format historique :
 *   Label;jours ouvrés;08:00;12:00
 */
export default class extends Controller {
  connect() {
    this.textarea = this.element.querySelector('textarea[data-delivery-point-windows-source]')
      || this.element.querySelector('textarea[name*="quickDeliveryPointTimeWindows"]')
      || this.element.querySelector('textarea');

    if (!this.textarea || this.element.dataset.hodinaDeliveryPointWindowsReady === '1') {
      return;
    }

    this.element.dataset.hodinaDeliveryPointWindowsReady = '1';
    this.installStyles();
    this.textarea.classList.add('hodina-delivery-windows-builder__source');
    this.textarea.setAttribute('aria-hidden', 'true');
    this.textarea.tabIndex = -1;

    this.builder = this.createBuilder();
    this.textarea.insertAdjacentElement('afterend', this.builder);

    const rows = this.parseRawValue(this.textarea.value);
    if (rows.length > 0) {
      rows.forEach((row) => this.addRowFromData(row, false));
    } else {
      this.addRowFromData({ label: 'Matin', day: 'jours ouvrés', start: '08:00', end: '12:00' }, false);
      this.addRowFromData({ label: 'Après-midi', day: 'jours ouvrés', start: '14:00', end: '18:00' }, false);
    }

    this.updateSource();
  }

  createBuilder() {
    const wrapper = document.createElement('div');
    wrapper.className = 'hodina-delivery-windows-builder';

    wrapper.innerHTML = `
      <div class="hodina-delivery-windows-builder__intro">
        <strong>Créer les plages du nouveau point sans écrire de code.</strong>
        <span>Renseigne un libellé, les jours concernés, puis l’heure de début et de fin.</span>
      </div>

      <div class="hodina-delivery-windows-builder__rows" data-delivery-point-windows-rows></div>

      <button type="button" class="btn btn-secondary btn-sm hodina-delivery-windows-builder__add" data-delivery-point-windows-add>
        + Ajouter une plage horaire
      </button>

      <div class="hodina-delivery-windows-builder__preview">
        <span class="hodina-delivery-windows-builder__preview-title">Résumé qui sera envoyé à Hodina :</span>
        <div data-delivery-point-windows-preview>Aucune plage prête à créer.</div>
      </div>

      <p class="form-text text-muted hodina-delivery-windows-builder__help">
        <strong>Jours ouvrés</strong> = lundi à vendredi. <strong>Jours ouvrables</strong> = lundi à samedi.
        Ces plages servent uniquement si tu crées un nouveau point depuis ce produit.
        Pour modifier un point existant, utilise le menu <strong>Plages points de remise</strong>.
      </p>
    `;

    this.rowsContainer = wrapper.querySelector('[data-delivery-point-windows-rows]');
    this.preview = wrapper.querySelector('[data-delivery-point-windows-preview]');
    wrapper.querySelector('[data-delivery-point-windows-add]').addEventListener('click', (event) => this.addRow(event));

    return wrapper;
  }

  addRow(event) {
    if (event) {
      event.preventDefault();
    }

    this.addRowFromData({ label: '', day: 'jours ouvrés', start: '', end: '' }, true);
  }

  addRowFromData(data, shouldUpdate = true) {
    const row = document.createElement('div');
    row.className = 'hodina-delivery-window-row';
    row.setAttribute('data-delivery-point-windows-row', '1');

    row.innerHTML = `
      <div class="hodina-delivery-window-row__field hodina-delivery-window-row__field--label">
        <label>Libellé</label>
        <input type="text" class="form-control form-control-sm" data-delivery-point-windows-label placeholder="Matin, Après-midi, Soir...">
      </div>

      <div class="hodina-delivery-window-row__field">
        <label>Jours concernés</label>
        <select class="form-select form-select-sm" data-delivery-point-windows-day>
          <option value="tous les jours">Tous les jours</option>
          <option value="jours ouvrés">Jours ouvrés — lundi à vendredi</option>
          <option value="jours ouvrables">Jours ouvrables — lundi à samedi</option>
          <option value="1">Lundi</option>
          <option value="2">Mardi</option>
          <option value="3">Mercredi</option>
          <option value="4">Jeudi</option>
          <option value="5">Vendredi</option>
          <option value="6">Samedi</option>
          <option value="7">Dimanche</option>
        </select>
      </div>

      <div class="hodina-delivery-window-row__field hodina-delivery-window-row__field--time">
        <label>Heure début</label>
        <input type="time" class="form-control form-control-sm" data-delivery-point-windows-start>
      </div>

      <div class="hodina-delivery-window-row__field hodina-delivery-window-row__field--time">
        <label>Heure fin</label>
        <input type="time" class="form-control form-control-sm" data-delivery-point-windows-end>
      </div>

      <button type="button" class="btn btn-link btn-sm text-danger hodina-delivery-window-row__remove" data-delivery-point-windows-remove>
        Supprimer
      </button>
    `;

    row.querySelector('[data-delivery-point-windows-label]').value = data.label || '';
    row.querySelector('[data-delivery-point-windows-day]').value = data.day || 'jours ouvrés';
    row.querySelector('[data-delivery-point-windows-start]').value = data.start || '';
    row.querySelector('[data-delivery-point-windows-end]').value = data.end || '';

    row.querySelectorAll('input, select').forEach((input) => {
      input.addEventListener('input', () => this.updateSource());
      input.addEventListener('change', () => this.updateSource());
    });

    row.querySelector('[data-delivery-point-windows-remove]').addEventListener('click', (event) => this.removeRow(event));

    this.rowsContainer.appendChild(row);

    if (shouldUpdate) {
      this.updateSource();
    }
  }

  removeRow(event) {
    event.preventDefault();

    const row = event.target.closest('[data-delivery-point-windows-row]');
    if (!row) {
      return;
    }

    row.remove();

    if (this.rowsContainer.querySelectorAll('[data-delivery-point-windows-row]').length === 0) {
      this.addRowFromData({ label: 'Matin', day: 'jours ouvrés', start: '08:00', end: '12:00' }, false);
    }

    this.updateSource();
  }

  updateSource() {
    const lines = [];

    this.rowsContainer.querySelectorAll('[data-delivery-point-windows-row]').forEach((row) => {
      const label = row.querySelector('[data-delivery-point-windows-label]').value.trim();
      const day = row.querySelector('[data-delivery-point-windows-day]').value.trim();
      const start = row.querySelector('[data-delivery-point-windows-start]').value.trim();
      const end = row.querySelector('[data-delivery-point-windows-end]').value.trim();

      if (label === '' && start === '' && end === '') {
        return;
      }

      lines.push([label || 'Plage', day || 'jours ouvrés', start, end].join(';'));
    });

    this.textarea.value = lines.join('\n');
    this.refreshPreview(lines);
  }

  refreshPreview(lines) {
    if (!this.preview) {
      return;
    }

    if (lines.length === 0) {
      this.preview.textContent = 'Aucune plage prête à créer.';
      return;
    }

    this.preview.innerHTML = lines
      .map((line) => this.escapeHtml(this.humanizeLine(line)))
      .join('<br>');
  }

  humanizeLine(line) {
    const parts = line.split(';');
    if (parts.length < 4) {
      return line;
    }

    return `${parts[0]} — ${this.humanizeDay(parts[1])} — ${parts[2]} à ${parts[3]}`;
  }

  humanizeDay(value) {
    const map = {
      'tous les jours': 'tous les jours',
      'jours ouvrés': 'jours ouvrés',
      'jours ouvrables': 'jours ouvrables',
      '1': 'lundi',
      '2': 'mardi',
      '3': 'mercredi',
      '4': 'jeudi',
      '5': 'vendredi',
      '6': 'samedi',
      '7': 'dimanche',
    };

    return map[value] || value;
  }

  parseRawValue(value) {
    return (value || '')
      .split(/\r?\n/)
      .map((line) => line.trim())
      .filter((line) => line.length > 0)
      .map((line) => {
        const parts = line.split(/[;|,]/).map((part) => part.trim()).filter((part) => part.length > 0);
        if (parts.length >= 4) {
          return { label: parts[0], day: this.normalizeDay(parts[1]), start: parts[2], end: parts[3] };
        }
        if (parts.length === 3) {
          return { label: parts[0], day: 'tous les jours', start: parts[1], end: parts[2] };
        }
        if (parts.length === 2) {
          return { label: 'Plage', day: 'tous les jours', start: parts[0], end: parts[1] };
        }
        return null;
      })
      .filter((row) => row !== null);
  }

  normalizeDay(value) {
    const map = {
      '0': 'tous les jours',
      '1': '1',
      '2': '2',
      '3': '3',
      '4': '4',
      '5': '5',
      '6': '6',
      '7': '7',
      'tous': 'tous les jours',
      'tous les jours': 'tous les jours',
      'jours ouvrés': 'jours ouvrés',
      'jours ouvres': 'jours ouvrés',
      'ouvres': 'jours ouvrés',
      'ouvrés': 'jours ouvrés',
      'jours ouvrables': 'jours ouvrables',
      'ouvrables': 'jours ouvrables',
    };

    const key = (value || '').toString().trim().toLowerCase();
    return map[key] || key || 'jours ouvrés';
  }

  installStyles() {
    if (document.getElementById('hodina-delivery-point-windows-style')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'hodina-delivery-point-windows-style';
    style.textContent = `
      .hodina-delivery-windows-builder__source { display: none !important; }
      .hodina-delivery-windows-builder {
        margin-top: .75rem;
      }
      .hodina-delivery-windows-builder__intro {
        border: 1px solid rgba(22, 134, 95, .25);
        background: rgba(22, 134, 95, .08);
        border-radius: 14px;
        padding: .8rem .9rem;
        margin-bottom: .8rem;
      }
      .hodina-delivery-windows-builder__intro strong { display: block; margin-bottom: .2rem; }
      .hodina-delivery-windows-builder__intro span { color: var(--text-muted, #6b7280); }
      .hodina-delivery-window-row {
        display: grid;
        grid-template-columns: minmax(150px, 1.1fr) minmax(210px, 1.2fr) minmax(110px, .7fr) minmax(110px, .7fr) auto;
        gap: .75rem;
        align-items: end;
        border: 1px solid rgba(148, 163, 184, .35);
        border-radius: 14px;
        padding: .85rem;
        margin-bottom: .75rem;
        background: rgba(148, 163, 184, .05);
      }
      .hodina-delivery-window-row label {
        display: block;
        margin-bottom: .25rem;
        font-weight: 700;
        font-size: .82rem;
      }
      .hodina-delivery-window-row__remove { white-space: nowrap; text-decoration: none; }
      .hodina-delivery-windows-builder__add { margin-top: .25rem; }
      .hodina-delivery-windows-builder__preview {
        margin-top: .85rem;
        border: 1px dashed rgba(22, 134, 95, .45);
        border-radius: 14px;
        padding: .8rem .9rem;
        background: rgba(22, 134, 95, .05);
      }
      .hodina-delivery-windows-builder__preview-title {
        display: block;
        font-weight: 800;
        margin-bottom: .35rem;
      }
      .hodina-delivery-windows-builder__help { margin-top: .75rem; }
      @media (max-width: 900px) {
        .hodina-delivery-window-row { grid-template-columns: 1fr; }
        .hodina-delivery-window-row__remove { justify-self: start; }
      }
    `;
    document.head.appendChild(style);
  }

  escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
  }
}
