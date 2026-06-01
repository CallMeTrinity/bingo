import { Controller } from '@hotwired/stimulus';

/**
 * Form ajax submit avec swap des erreurs in-place + gating client-side.
 *
 * Markup attendu sur le <form> :
 *   data-controller="form"
 *   data-action="submit->form#submit"
 *
 * Optionnel : un checkbox de conditions doit être coché pour activer le submit :
 *   <input type="checkbox"
 *          data-form-target="terms"
 *          data-action="change->form#toggleSubmit">
 *   <button data-form-target="submit" disabled>…</button>
 *
 * Le serveur doit :
 *   - sur succès : renvoyer JSON { redirect: '/url' }
 *   - sur erreur de validation : renvoyer le fragment HTML (statut 422)
 */
export default class extends Controller {
    static targets = ['fields', 'terms', 'submit']

    // Auto-resync quand le HTML est swap après une erreur de validation —
    // les nouveaux nodes terms/submit re-déclenchent ces callbacks.
    termsTargetConnected() { this.syncSubmit() }
    submitTargetConnected() { this.syncSubmit() }

    toggleSubmit() {
        this.syncSubmit()
    }

    syncSubmit() {
        if (!this.hasSubmitTarget || !this.hasTermsTarget) return
        this.submitTarget.disabled = !this.termsTarget.checked
    }

    async submit(event) {
        event.preventDefault()
        const form = this.element

        if (this.hasSubmitTarget) this.submitTarget.disabled = true

        try {
            const response = await fetch(form.action, {
                method: form.method.toUpperCase(),
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json, text/html',
                },
            })

            const contentType = response.headers.get('content-type') || ''

            if (contentType.includes('application/json')) {
                const data = await response.json()
                if (data.redirect) {
                    window.location.assign(data.redirect)
                    return
                }
            }

            if (contentType.includes('text/html')) {
                this.fieldsTarget.innerHTML = await response.text()
            }
        } catch (err) {
            console.error('Submission échouée:', err)
        } finally {
            this.syncSubmit()
        }
    }
}
