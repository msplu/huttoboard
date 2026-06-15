import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/*
 * Contrôleur du tableau kanban.
 *
 * Initialise un Sortable sur chaque colonne. Les tickets peuvent être
 * réordonnés et déplacés d'une colonne à l'autre. À chaque dépose, on envoie
 * au serveur le nouvel ordre de la colonne cible.
 */
export default class extends Controller {
    static targets = ['column'];
    static values = {
        moveUrl: String,
        csrf: String,
    };

    connect() {
        // Neutralise le drag natif HTML5 (les cartes sont des liens <a>), qui sinon
        // entre en conflit avec le glisser-déposer de SortableJS au premier essai.
        this.preventNativeDrag = (event) => event.preventDefault();
        this.element.addEventListener('dragstart', this.preventNativeDrag);

        this.sortables = this.columnTargets.map((column) =>
            Sortable.create(column, {
                group: 'tickets',
                animation: 150,
                // forceFallback évite le drag HTML5 natif des liens <a>.
                forceFallback: true,
                fallbackOnBody: true,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: (event) => this.onEnd(event),
            })
        );
    }

    disconnect() {
        this.element.removeEventListener('dragstart', this.preventNativeDrag);
        this.sortables?.forEach((sortable) => sortable.destroy());
        this.sortables = [];
    }

    async onEnd(event) {
        const item = event.item;
        const toColumn = event.to;

        const ticketId = parseInt(item.dataset.ticketId, 10);
        const toColumnId = parseInt(toColumn.dataset.columnId, 10);
        const orderedIds = Array.from(toColumn.querySelectorAll('[data-ticket-id]'))
            .map((el) => parseInt(el.dataset.ticketId, 10));

        try {
            const response = await fetch(this.moveUrlValue, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    _token: this.csrfValue,
                    ticketId,
                    toColumnId,
                    orderedIds,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            this.refreshCounts();
        } catch (error) {
            console.error('Échec du déplacement du ticket :', error);
            // On recharge pour resynchroniser l'affichage avec le serveur.
            window.location.reload();
        }
    }

    /* Met à jour les compteurs de tickets affichés dans les en-têtes de colonnes. */
    refreshCounts() {
        this.columnTargets.forEach((column) => {
            const count = column.querySelectorAll('[data-ticket-id]').length;
            const badge = column.closest('.column')?.querySelector('.column-head .count');
            if (!badge) {
                return;
            }
            const parts = badge.textContent.split('/');
            const limitRaw = parts[1] ? parts[1].trim() : null;
            badge.textContent = limitRaw ? `${count} / ${limitRaw}` : `${count}`;
            if (limitRaw) {
                badge.classList.toggle('over', count > parseInt(limitRaw, 10));
            }
        });
    }
}
