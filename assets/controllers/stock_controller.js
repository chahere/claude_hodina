import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  connect() {
    this.toggle();
  }

  toggle() {
    const unlimited = document.querySelector('[name$="[isUnlimitedStock]"]');
    const stock = document.querySelector('[name$="[stockQty]"]');
    if (!unlimited || !stock) return;

    stock.disabled = unlimited.checked;
    if (unlimited.checked) stock.value = '';
  }
}
