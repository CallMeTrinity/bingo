import { Controller } from "@hotwired/stimulus"

/**
 * Enregistre le service worker et affiche un toast quand une nouvelle
 * version attend d'être activée. « Recharger » envoie SKIP_WAITING au SW
 * en attente, puis `controllerchange` déclenche le reload de la page.
 */
export default class extends Controller {
    static targets = ["toast"]

    connect() {
        if (!('serviceWorker' in navigator)) return

        navigator.serviceWorker
            .register('/sw.js')
            .then((registration) => {
                this.registration = registration

                // SW déjà en attente (onglet rouvert après un déploiement)
                if (registration.waiting && navigator.serviceWorker.controller) {
                    this.show()
                }

                registration.addEventListener('updatefound', () => {
                    const installing = registration.installing
                    installing?.addEventListener('statechange', () => {
                        if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                            this.show()
                        }
                    })
                })
            })
            .catch(() => {})

        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (this.refreshing) return
            this.refreshing = true
            window.location.reload()
        })
    }

    reload() {
        this.registration?.waiting?.postMessage('SKIP_WAITING')
    }

    dismiss() {
        this.hide()
    }

    show() {
        if (this.hasToastTarget) this.toastTarget.classList.remove('hidden')
    }

    hide() {
        if (this.hasToastTarget) this.toastTarget.classList.add('hidden')
    }
}
