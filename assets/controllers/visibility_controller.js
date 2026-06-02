import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        csrfToken: String,
        isPublic: Boolean,
    }
    static targets = ['switch', 'shareSection', 'hint']

    async toggle() {
        const next = !this.isPublicValue;
        this.applyState(next);

        const formData = new FormData();
        formData.append('state', next ? '1' : '0');
        formData.append('_token', this.csrfTokenValue);

        try {
            const response = await fetch(this.urlValue, { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            this.applyState(data.isPublic);
        } catch (error) {
            console.error('Visibility toggle failed', error);
            this.applyState(!next);
        }
    }

    applyState(isPublic) {
        this.isPublicValue = isPublic;
        this.switchTarget.setAttribute('aria-checked', String(isPublic));
        this.shareSectionTarget.classList.toggle('opacity-50', !isPublic);
        this.shareSectionTarget.classList.toggle('pointer-events-none', !isPublic);
        this.hintTarget.textContent = isPublic
            ? 'Toute personne avec ce lien peut voir le bingo.'
            : 'Le lien est désactivé. Active-le pour partager.';
    }
}
