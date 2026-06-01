import { Controller } from '@hotwired/stimulus';
import { domToBlob } from 'modern-screenshot';

export default class extends Controller {
    static values = {
        filename: { type: String, default: 'bingo.png' },
    }

    async download() {
        const node = document.querySelector('[data-bingo-export]')
        if (!node) return

        try {
            await document.fonts.ready
            const blob = await domToBlob(node, {
                scale: 2,
                backgroundColor: '#faf7f2',
            })

            const url = URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = this.filenameValue
            link.click()
            URL.revokeObjectURL(url)
        } catch (err) {
            console.error('Export échoué:', err)
        }
    }
}
