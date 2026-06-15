import { Controller } from '@hotwired/stimulus';

/* Fait disparaître les messages flash après quelques secondes. */
export default class extends Controller {
    connect() {
        this.timeout = window.setTimeout(() => {
            this.element.querySelectorAll('.flash').forEach((flash) => {
                flash.style.transition = 'opacity .3s, transform .3s';
                flash.style.opacity = '0';
                flash.style.transform = 'translateX(20px)';
            });
            window.setTimeout(() => this.element.remove(), 350);
        }, 4000);
    }

    disconnect() {
        window.clearTimeout(this.timeout);
    }
}
