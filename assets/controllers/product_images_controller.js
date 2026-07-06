import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static values = { basePath: String };

  connect() {
    this._objectUrls = new Map();

    console.log('[product-images] connected');

    this.refreshAll();

    this._onChange = (event) => {
      const input = event.target;
      if (input && input.matches('input[type="file"]')) {
        this.updateOne(input);
      }
    };
    this.element.addEventListener('change', this._onChange);

    this._onClick = (event) => {
      if (event.target && event.target.closest('.field-collection-add-button, [data-ea-collection-field="add-button"]')) {
        window.setTimeout(() => this.refreshAll(), 0);
      }
    };
    this.element.addEventListener('click', this._onClick);
  }

  disconnect() {
    this.element.removeEventListener('change', this._onChange);
    this.element.removeEventListener('click', this._onClick);

    for (const url of this._objectUrls.values()) {
      try { URL.revokeObjectURL(url); } catch (e) {}
    }
    this._objectUrls.clear();
  }

  refreshAll() {
    const fileInputs = this.element.querySelectorAll('input[type="file"]');
    fileInputs.forEach((input) => this.updateOne(input));
  }

  updateOne(input) {
    const ui = this.ensureUi(input);

    const file = input.files && input.files[0];
    if (file) {
      this.showPreview(input, ui, URL.createObjectURL(file), file.name, true);
      return;
    }

    const storedFilename = this.findStoredFilenameFromEasyAdmin(input);
    if (storedFilename) {
      const base = (this.basePathValue || '/uploads/products/').replace(/\/?$/, '/');
      this.showPreview(input, ui, base + encodeURIComponent(storedFilename), storedFilename, false);
      return;
    }

    ui.name.textContent = '';
    ui.img.removeAttribute('src');
    ui.img.style.display = 'none';
  }

  showPreview(input, ui, src, label, revokePreviousObjectUrl) {
    const oldUrl = this._objectUrls.get(input);
    if (oldUrl) {
      try { URL.revokeObjectURL(oldUrl); } catch (e) {}
      this._objectUrls.delete(input);
    }

    if (revokePreviousObjectUrl) {
      this._objectUrls.set(input, src);
    }

    ui.name.textContent = label || '';
    ui.img.src = src;
    ui.img.style.display = '';
  }

  ensureUi(input) {
    const container = this.getImageFieldContainer(input);

    let wrapper = container.querySelector(':scope > .hodina-image-ui');
    if (!wrapper) {
      wrapper = document.createElement('div');
      wrapper.className = 'hodina-image-ui';
      wrapper.style.marginTop = '8px';
      wrapper.style.display = 'flex';
      wrapper.style.alignItems = 'center';
      wrapper.style.gap = '10px';
      wrapper.style.flexWrap = 'wrap';

      const img = document.createElement('img');
      img.alt = 'Aperçu image produit';
      img.loading = 'lazy';
      img.style.width = '96px';
      img.style.height = '96px';
      img.style.objectFit = 'cover';
      img.style.borderRadius = '10px';
      img.style.display = 'none';
      img.style.border = '1px solid rgba(127,127,127,0.30)';
      img.style.background = 'rgba(127,127,127,0.08)';

      const name = document.createElement('div');
      name.className = 'text-muted small';
      name.style.wordBreak = 'break-all';
      name.style.maxWidth = '280px';

      wrapper.appendChild(img);
      wrapper.appendChild(name);
      container.appendChild(wrapper);
    }

    return {
      img: wrapper.querySelector('img'),
      name: wrapper.querySelector('div'),
    };
  }

  getImageFieldContainer(input) {
    return input.closest('.field-image, .field-file, .field-fileupload, .ea-field, .form-group, .form-widget') || input.parentElement;
  }

  getCollectionItemScope(input) {
    return input.closest('.field-collection-item, .ea-form-collection-item, .collection-item, .accordion-item, .form-widget-compound, .field-collection-item-row') ||
      this.getImageFieldContainer(input) ||
      input.parentElement;
  }

  findStoredFilenameFromEasyAdmin(input) {
    const scopes = this.uniqueScopes([
      this.getImageFieldContainer(input),
      this.getCollectionItemScope(input),
    ]);

    const candidates = [];
    candidates.push(...this.extractCandidatesFromAttributes(input));

    for (const scope of scopes) {
      candidates.push(...this.extractCandidatesFromInputs(scope));
      candidates.push(...this.extractCandidatesFromLinks(scope));
      candidates.push(...this.extractCandidatesFromKnownFileNodes(scope));
    }

    for (const candidate of candidates) {
      const filename = this.extractImageFilename(candidate);
      if (filename) {
        return filename;
      }
    }

    return null;
  }

  extractCandidatesFromAttributes(input) {
    const values = [];

    for (const attr of ['placeholder', 'value', 'title', 'aria-label', 'data-filename', 'data-file-name']) {
      const value = input.getAttribute(attr);
      if (value) values.push(value);
    }

    const id = input.getAttribute('id');
    const container = this.getImageFieldContainer(input);
    if (id && container) {
      const escapedId = this.escapeCssIdentifier(id);
      const label = container.querySelector(`label[for="${escapedId}"]`);
      if (label?.textContent) values.push(label.textContent);
    }

    return values;
  }

  extractCandidatesFromInputs(scope) {
    const values = [];
    const inputs = scope.querySelectorAll('input, textarea');

    inputs.forEach((field) => {
      for (const attr of ['value', 'placeholder', 'title', 'aria-label', 'data-filename', 'data-file-name']) {
        const value = attr === 'value' ? field.value : field.getAttribute(attr);
        if (value) values.push(value);
      }
    });

    return values;
  }

  extractCandidatesFromLinks(scope) {
    const values = [];
    const elements = scope.querySelectorAll('a, button, [data-filename], [data-file-name]');

    elements.forEach((element) => {
      for (const attr of ['href', 'download', 'title', 'aria-label', 'data-filename', 'data-file-name']) {
        const value = element.getAttribute(attr);
        if (value) values.push(value);
      }

      if (element.textContent) {
        values.push(element.textContent);
      }
    });

    return values;
  }

  extractCandidatesFromKnownFileNodes(scope) {
    const values = [];
    const selectors = [
      '.custom-file-label',
      '.form-control',
      '.form-control-plaintext',
      '.input-group-text',
      '.field-file-name',
      '.filename',
      '.file-name',
      '.ea-fileupload-filename',
      '.fileupload-filename',
    ];

    scope.querySelectorAll(selectors.join(',')).forEach((element) => {
      if (element.textContent) {
        values.push(element.textContent);
      }
    });

    return values;
  }

  uniqueScopes(scopes) {
    const seen = new Set();
    const unique = [];

    for (const scope of scopes) {
      if (!scope || seen.has(scope)) continue;
      seen.add(scope);
      unique.push(scope);
    }

    return unique;
  }

  extractImageFilename(value) {
    if (!value) return null;

    const decoded = this.safeDecode(String(value).trim());
    if (!decoded || decoded === 'Choose file' || decoded === 'Aucun fichier choisi') return null;

    const match = decoded.match(/([^\\/\s"'<>]+\.(?:png|jpe?g|webp|gif|avif|svg))(?:[?#][^\s"'<>]*)?/i);
    if (!match) return null;

    return match[1].trim();
  }

  safeDecode(value) {
    try {
      return decodeURIComponent(value);
    } catch (e) {
      return value;
    }
  }

  escapeCssIdentifier(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(value);
    }

    return String(value).replace(/([ #;?%&,.+*~':"!^$[\]()=>|/@])/g, '\\$1');
  }
}
