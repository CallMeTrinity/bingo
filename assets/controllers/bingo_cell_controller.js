import {Controller} from "@hotwired/stimulus"

const ANIMATION_MS = 1200
const BINGO_ANIMATION_MS = 2000

export default class extends Controller {
    static targets = ["cell"]
    static values = { url: String, active: Boolean }

    connect() {
        this.cellTarget.classList.add("bingo-cell")
    }

    disconnect() {
        this.cellTarget.classList.remove("bingo-cell")
    }

    async toggle() {
        const res = await fetch(this.urlValue, { method: 'POST' })
        if (!res.ok) return

        const data = await res.json()
        this.activeValue = data.active
        this.cellTarget.classList.toggle('active', this.activeValue)

        if (data.newlyBingo) {
            this.#animate(data.newlyCompleted, 'bingo-celebration', BINGO_ANIMATION_MS)
        } else if (data.newlyCompleted && data.newlyCompleted.length > 0) {
            this.#animate(data.newlyCompleted, 'just-completed', ANIMATION_MS)
        }
    }

    #animate(positions, className, duration) {
        positions.forEach((pos, i) => {
            const cell = document.querySelector(`[data-position="${pos}"]`)
            if (!cell) return
            cell.style.setProperty('--anim-delay', `${i * 80}ms`)
            cell.classList.add(className)
            setTimeout(() => {
                cell.classList.remove(className)
                cell.style.removeProperty('--anim-delay')
            }, duration + i * 80)
        })
    }
}
