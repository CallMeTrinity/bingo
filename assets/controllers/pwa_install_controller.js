import { Controller } from "@hotwired/stimulus"

/**
 * Affiche le bouton « Installer l'app » quand le navigateur a émis
 * `beforeinstallprompt`. L'événement est capturé très tôt par le script
 * inline de base.html.twig (window.deferredInstallPrompt), car il peut
 * tirer avant le chargement des modules ES.
 */
export default class extends Controller {
    connect() {
        const standalone = window.matchMedia('(display-mode: standalone)').matches
            || window.navigator.standalone === true
        if (standalone) return

        if (window.deferredInstallPrompt) this.show()

        this.onInstallable = () => this.show()
        this.onInstalled = () => this.hide()
        window.addEventListener('pwa:installable', this.onInstallable)
        window.addEventListener('appinstalled', this.onInstalled)
    }

    disconnect() {
        window.removeEventListener('pwa:installable', this.onInstallable)
        window.removeEventListener('appinstalled', this.onInstalled)
    }

    async install() {
        const prompt = window.deferredInstallPrompt
        if (!prompt) return

        prompt.prompt()
        await prompt.userChoice
        window.deferredInstallPrompt = null
        this.hide()
    }

    show() {
        this.element.classList.remove('hidden')
    }

    hide() {
        this.element.classList.add('hidden')
    }
}
