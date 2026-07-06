(function () {
    'use strict';

    function normalize(text) {
        return (text || '').replace(/\s+/g, ' ').trim();
    }

    function translateCollectionButtons() {
        var candidates = document.querySelectorAll('button, a, span');

        candidates.forEach(function (element) {
            var text = normalize(element.textContent);

            if (text === 'Add a new item') {
                element.textContent = element.textContent.replace('Add a new item', 'Ajouter un nouvel item');
            }

            if (text === 'Delete') {
                element.textContent = element.textContent.replace('Delete', 'Supprimer');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        translateCollectionButtons();

        var observer = new MutationObserver(function () {
            translateCollectionButtons();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})();
