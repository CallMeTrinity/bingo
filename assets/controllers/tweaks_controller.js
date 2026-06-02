import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['panel'];
    static values = {
        url: String,
        csrfToken: String,
    };

    toggle() {
        this.panelTarget.classList.toggle('hidden');
        this.isOpen = !this.isOpen;
        if (this.isOpen) {
            this.outsideClickHandler = (event) => {
                if (!this.element.contains(event.target)) this.close();
            };
            document.addEventListener('click', this.outsideClickHandler, true);
        } else if (this.outsideClickHandler) {
            document.removeEventListener('click', this.outsideClickHandler, true);
            this.outsideClickHandler = null;
        }
    }

    setPalette({params}){
        document.documentElement.dataset.palette = params.value;
        localStorage.setItem('bingo.palette', params.value);
        this.sync({palette: params.value});
    }

    setDensity({params}){
        document.documentElement.dataset.density = params.value;
        localStorage.setItem('bingo.density', params.value);
        this.sync({density: params.value});
    }

    sync(data){
        const formData = new FormData();
        formData.append('_token', this.csrfTokenValue);
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }
        fetch(this.urlValue, {
            method: 'POST',
            body: formData,
        }).catch(() => {/* Silently fail, it's not critical if this doesn't work */});
    }
    close() {
        this.panelTarget.classList.add('hidden');
        this.isOpen = false;
        if (this.outsideClickHandler) {
            document.removeEventListener('click', this.outsideClickHandler, true);
            this.outsideClickHandler = null;
        }
    }

    disconnect() {
        if (this.outsideClickHandler) {
            document.removeEventListener('click', this.outsideClickHandler, true);
        }
    }
}
