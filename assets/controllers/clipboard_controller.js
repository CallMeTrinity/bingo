import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {text: String}
    static targets = ['feedback']

    async copy() {
        try {
            await navigator.clipboard.writeText(this.textValue)
            clearTimeout(this.hideTimer)
            this.feedbackTarget.classList.add('opacity-100')
            this.hideTimer = setTimeout(() => this.feedbackTarget.classList.remove('opacity-100'), 1500)
        } catch(error) {
            console.error('Failed to copy: ', error)
        }
    }

    disconnect() {
        clearTimeout(this.hideTimer)
    }
}
