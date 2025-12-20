/**
 * Translation UI Manager
 * Manages the translation overlay and status display.
 * Single Responsibility: UI state and rendering for translations.
 */

export class TranslationUI {
	constructor(elements) {
		this.elements = {
			translateBtn: elements.translateBtn,
			showOriginalBtn: elements.showOriginalBtn,
			targetLanguageSelect: elements.targetLanguageSelect,
			translationStatus: elements.translationStatus,
			translationOverlay: elements.translationOverlay,
			canvas: elements.canvas,
		}

		this._setupEventListeners()
	}

	/**
	 * Setup UI event listeners
	 */
	_setupEventListeners() {
		this.elements.showOriginalBtn.addEventListener("click", () => {
			this.hideTranslation()
			this.updateStatus("", "")
		})
	}

	/**
	 * Update status badge
	 * @param {string} text - Status text
	 * @param {string} type - Status type (info|loading|success|error)
	 */
	updateStatus(text, type = "info") {
		this.elements.translationStatus.textContent = text
		this.elements.translationStatus.className = `status-badge status-${type}`
		this.elements.translationStatus.style.display = text ? "inline-block" : "none"
	}

	/**
	 * Show translation overlay with translated text
	 * @param {string} translatedText - The translated text to display
	 */
	showTranslation(translatedText) {
		const canvas = this.elements.canvas
		const overlay = this.elements.translationOverlay

		overlay.style.width = `${canvas.width}px`
		overlay.style.height = `${canvas.height}px`
		overlay.innerHTML = `<div class="translation-text">${translatedText.replace(/\n/g, "<br>")}</div>`
		overlay.style.display = "block"

		this.elements.translateBtn.style.display = "none"
		this.elements.targetLanguageSelect.disabled = true
		this.elements.showOriginalBtn.style.display = "inline-block"
	}

	/**
	 * Hide translation overlay
	 */
	hideTranslation() {
		this.elements.translationOverlay.style.display = "none"
		this.elements.translateBtn.style.display = "inline-block"
		this.elements.targetLanguageSelect.disabled = false
		this.elements.showOriginalBtn.style.display = "none"
	}

	/**
	 * Set translate button state
	 * @param {boolean} disabled - Whether button should be disabled
	 */
	setTranslateButtonState(disabled) {
		this.elements.translateBtn.disabled = disabled
	}

	/**
	 * Get selected target language
	 * @returns {string} - Selected language code
	 */
	getSelectedLanguage() {
		return this.elements.targetLanguageSelect.value
	}

	/**
	 * Register translate button click handler
	 * @param {Function} handler - Click handler function
	 */
	onTranslateClick(handler) {
		this.elements.translateBtn.addEventListener("click", handler)
	}
}
