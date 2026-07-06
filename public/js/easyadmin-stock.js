(function () {
  function toggleStock() {
    const unlimited = document.querySelector('[name$="[isUnlimitedStock]"]');
    const stock = document.querySelector('[name$="[stockQty]"]');
    if (!unlimited || !stock) return;

    stock.disabled = unlimited.checked;
    if (unlimited.checked) stock.value = '';
  }

  document.addEventListener('DOMContentLoaded', () => {
    toggleStock();

    const unlimited = document.querySelector('[name$="[isUnlimitedStock]"]');
    if (!unlimited) return;

    unlimited.addEventListener('change', toggleStock);
  });
})();
