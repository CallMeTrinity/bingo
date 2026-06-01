import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["dialog", "content"]
    static values = { open: Boolean }

    connect() {
        if (this.openValue) {
            this.open()
        }
    }

    open(event) {
        event?.preventDefault()
        this.dialogTarget.showModal()
    }

    async edit(event) {
        event?.preventDefault()
        event?.stopPropagation()

        const id = event.params.id
        const response = await fetch(`/bingo/item/${id}/edit`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        if (!response.ok) return

        this.contentTarget.innerHTML = await response.text()
        this.dialogTarget.showModal()
    }

    async submit(event) {
        event.preventDefault()
        const form = event.target

        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })

        const contentType = response.headers.get('content-type') || ''
        if (!contentType.includes('application/json')) {
            this.contentTarget.innerHTML = await response.text()
            return
        }

        const data = await response.json()
        const cell = this.element.querySelector(
            `[data-bingo-cell-position-value="${data.stats.position}"]`
        )
        if (cell) {
            cell.outerHTML = data.cellHtml
        }

        this.dispatch('updated', {
            prefix: 'bingo-cell',
            detail: {
                linePositions: data.stats.linePositions,
                completedLines: data.stats.completedLines,
                completed: data.stats.completed,
                total: data.stats.total,
            },
        })

        this.dialogTarget.close()
    }

    close(event) {
        event?.preventDefault()
        this.dialogTarget.close()
    }

    backdropClose(event) {
        if (event.target === this.dialogTarget) {
            this.dialogTarget.close()
        }
    }
}
