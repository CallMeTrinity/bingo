import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static values = { url: String, active: Boolean, position: Number }

    async toggle(event) {
        const res = await fetch(this.urlValue, { method: 'POST' })
        if (!res.ok) return

        const data = await res.json()
        const becameDone = data.active && !this.activeValue
        this.activeValue = data.active
        this.element.classList.toggle('done', this.activeValue)

        this.dispatch('updated', {
            detail: { ...data, becameDone, x: event.clientX, y: event.clientY },
        })
    }
}
