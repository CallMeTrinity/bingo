import { Controller } from "@hotwired/stimulus"

const CONFETTI_COLORS = ['#cdb4db', '#ffc8dd', '#b8e0d2', '#bde0fe', '#fde4a6']
const CONFETTI_PIECES = 22
const CONFETTI_LIFETIME_MS = 1600

export default class extends Controller {
    static targets = [
        "cell",
        "completedCount",
        "completedLines",
        "remaining",
        "progressFraction",
        "progressLabel",
        "progressRing",
        "confettiLayer",
        "editToggle",
    ]

    // Mode édition (mobile) : un tap sur une case ouvre la modale d'édition
    // au lieu de la cocher. Lu par bingo_cell_controller via .is-editing.
    toggleEdit() {
        const editing = this.element.classList.toggle('is-editing')
        this.editToggleTarget.setAttribute('aria-pressed', String(editing))
        this.editToggleTarget.classList.toggle('btn--primary', editing)
        this.editToggleTarget.classList.toggle('btn--secondary', !editing)
        this.editToggleTarget.textContent = editing ? '✓ Terminer' : '✎ Modifier les cases'
    }

    sync(event) {
        const { linePositions = [], completedLines = 0, completed = 0, total = 0, becameDone, x, y } = event.detail

        const inLine = new Set(linePositions)
        this.cellTargets.forEach(cell => {
            cell.classList.toggle('in-line', inLine.has(Number(cell.dataset.bingoCellPositionValue)))
        })

        this.completedCountTarget.textContent = `${completed}/${total}`
        this.completedLinesTarget.textContent = completedLines
        this.remainingTarget.textContent = total - completed
        this.progressFractionTarget.innerHTML = `${completed}<span class="muted">/${total}</span>`

        if (total > 0) {
            const pct = (completed / total) * 100
            this.progressLabelTarget.textContent = `${Math.round(pct)}%`
            this.progressRingTarget.setAttribute('stroke-dasharray', `${(completed / total) * 97.4} 97.4`)
        }

        if (becameDone) {
            this.#fireConfetti(x, y)
        }
    }

    #fireConfetti(x, y) {
        const burst = document.createElement('div')
        burst.style.position = 'absolute'
        burst.style.left = `${x}px`
        burst.style.top = `${y}px`

        for (let i = 0; i < CONFETTI_PIECES; i++) {
            const angle = (Math.PI * 2 * i) / CONFETTI_PIECES + Math.random() * 0.3
            const dist = 60 + Math.random() * 140
            const size = 6 + Math.random() * 8
            const shape = i % 3

            const piece = document.createElement('span')
            piece.className = 'confetti-piece'
            piece.style.width = `${size}px`
            piece.style.height = `${shape === 1 ? size * 0.5 : size}px`
            piece.style.background = CONFETTI_COLORS[i % CONFETTI_COLORS.length]
            piece.style.borderRadius = shape === 0 ? '50%' : shape === 1 ? '2px' : '4px'
            piece.style.setProperty('--cx', `${Math.cos(angle) * dist}px`)
            piece.style.setProperty('--cy', `${Math.sin(angle) * dist + 80}px`)
            piece.style.setProperty('--cr', `${(Math.random() - 0.5) * 720}deg`)
            piece.style.setProperty('--dur', `${0.8 + Math.random() * 0.6}s`)
            burst.appendChild(piece)
        }

        this.confettiLayerTarget.appendChild(burst)
        setTimeout(() => burst.remove(), CONFETTI_LIFETIME_MS)
    }
}
