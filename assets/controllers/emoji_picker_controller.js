import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'popup'];

    async open() {
        if (this.isOpen) return;

        if (!this.picker) {
            await import('emoji-picker-element');
            this.picker = document.createElement('emoji-picker');
            this.picker.addEventListener('emoji-click', (e) => this.onPick(e));
            this.popupTarget.appendChild(this.picker);
        }

        this.popupTarget.classList.remove('hidden');
        this.isOpen = true;

        this.outsideClickHandler = (event) => {
            if (!this.element.contains(event.target)) this.close();
        };
        setTimeout(() => document.addEventListener('click', this.outsideClickHandler, true), 0);
    }

    close() {
        this.popupTarget.classList.add('hidden');
        this.isOpen = false;
        if (this.outsideClickHandler) {
            document.removeEventListener('click', this.outsideClickHandler, true);
            this.outsideClickHandler = null;
        }
    }

    onPick(event) {
        this.inputTarget.value = event.detail.unicode;
        this.inputTarget.dispatchEvent(new Event('input', { bubbles: true }));
        this.close();
    }

    disconnect() {
        if (this.outsideClickHandler) {
            document.removeEventListener('click', this.outsideClickHandler, true);
        }
    }
}
