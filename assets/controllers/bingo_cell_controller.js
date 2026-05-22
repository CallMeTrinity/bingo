import {Controller} from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["cell"]
    static values = { url: String, active: Boolean }
    connect() {
        this.cellTarget.classList.add("bingo-cell")
    }


    async toggle() {
        const res = await fetch(this.urlValue, { method: 'POST' })
        if (res.ok) {
            this.activeValue = !this.activeValue
            this.cellTarget.classList.toggle('active', this.activeValue)
        }
    }
    disconnect() {
        this.cellTarget.classList.remove("bingo-cell")
    }
}
