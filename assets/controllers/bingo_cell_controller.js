import {Controller} from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["cell"]
    connect() {
        this.cellTarget.classList.add("bingo-cell")
    }

    mark() {
        this.cellTarget.classList.toggle("marked")
    }
    disconnect() {
        this.cellTarget.classList.remove("bingo-cell")
    }
}
