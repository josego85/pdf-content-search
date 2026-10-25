/**
 * PDF Viewer - Main Entry Point
 * Refactored with separation of concerns
 */

import * as pdfjsLib from "pdfjs-dist/build/pdf.mjs"
import { TextLayer } from "pdfjs-dist/build/pdf.mjs"
import "pdfjs-dist/web/pdf_viewer.css"
import "./styles/pdfViewer.css"

import { LANGUAGES } from "./constants/languages.js"
import { TranslationController } from "./controllers/TranslationController.js"
// Import refactored modules
import { TranslationUI } from "./ui/TranslationUI.js"

// Configure PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = "/build/pdf.worker.mjs"

// =========================================================================
// PDF Rendering & Highlighting
// (Keeping existing complex logic as-is for now)
// =========================================================================

function normalize(str) {
	return str
		.normalize("NFD")
		.replace(/[\u0300-\u036f]/g, "")
		.toLowerCase()
		.trim()
}

function isWordBoundary(text, pos) {
	if (pos < 0 || pos >= text.length) {
		return true
	}
	const char = text[pos]
	return /[\s.,;:!?¿¡\-–—()[\]{}"'/\\•◦○●∙·<>«»°*+=@#$%^&|~`]/.test(char)
}

function isHybridMatch(text, index, term) {
	const beforePos = index - 1
	const afterPos = index + term.length
	const contextStart = Math.max(0, index - 30)
	const contextEnd = Math.min(text.length, index + term.length + 30)
	const context = text.substring(contextStart, contextEnd)
	const spaceRatio = (context.match(/\s/g) || []).length / context.length

	if (spaceRatio >= 0.15) {
		return false
	}

	const beforeChar = beforePos >= 0 ? text[beforePos] : ""
	const afterChar = afterPos < text.length ? text[afterPos] : ""
	const beforeIsAlpha = /[a-z0-9]/.test(beforeChar)
	const afterIsAlpha = /[a-z0-9]/.test(afterChar)

	return !(afterIsAlpha && term.length < 8) && !(beforeIsAlpha && term.length < 8)
}

function findMatchesForTerm(text, term) {
	const matches = []
	let startIndex = 0
	let index = text.indexOf(term, startIndex)

	while (index !== -1) {
		const beforePos = index - 1
		const afterPos = index + term.length
		const isValidMatch = isWordBoundary(text, beforePos) && isWordBoundary(text, afterPos)

		if (isValidMatch || isHybridMatch(text, index, term)) {
			matches.push({
				startPos: index,
				endPos: index + term.length - 1,
			})
		}

		startIndex = index + 1
		index = text.indexOf(term, startIndex)
	}

	return matches
}

function addSpanHighlight(spanHighlights, spanIndex, range) {
	if (!spanHighlights.has(spanIndex)) {
		spanHighlights.set(spanIndex, [])
	}

	spanHighlights.get(spanIndex).push(range)
}

function getHighlightRangeForSpan(spanIndex, startMapping, endMapping, spans) {
	if (spanIndex === startMapping.spanIndex) {
		return {
			start: startMapping.originalPos,
			end: spans[spanIndex].textContent.length,
		}
	}

	if (spanIndex === endMapping.spanIndex) {
		return {
			start: 0,
			end: endMapping.originalPos + 1,
		}
	}

	return {
		start: 0,
		end: spans[spanIndex].textContent.length,
	}
}

function addMatchHighlights(match, positionMap, spans, spanHighlights) {
	const startMapping = positionMap.find((position) => position.normalizedPos === match.startPos)
	const endMapping = positionMap.find((position) => position.normalizedPos === match.endPos)

	if (!startMapping || !endMapping) {
		return
	}

	if (startMapping.spanIndex === endMapping.spanIndex) {
		addSpanHighlight(spanHighlights, startMapping.spanIndex, {
			start: startMapping.originalPos,
			end: endMapping.originalPos + 1,
		})
		return
	}

	for (let spanIndex = startMapping.spanIndex; spanIndex <= endMapping.spanIndex; spanIndex++) {
		addSpanHighlight(
			spanHighlights,
			spanIndex,
			getHighlightRangeForSpan(spanIndex, startMapping, endMapping, spans),
		)
	}
}

const highlightTerms = JSON.parse(decodeURIComponent(window.highlight || "[]"))
	.map((term) => normalize(term))
	.filter((term) => term.length > 0)

const pdfPath = `/pdfs/${window.pdfPath}`
const pageNumber = parseInt(window.pageNumber || "1", 10)

const canvas = document.getElementById("pdf-canvas")
const container = document.getElementById("pdf-container")

// Render PDF with highlighting
pdfjsLib
	.getDocument(pdfPath)
	.promise.then((pdf) => pdf.getPage(pageNumber))
	.then((page) => {
		const scale = 1.5
		const viewport = page.getViewport({ scale })

		canvas.height = viewport.height
		canvas.width = viewport.width
		container.style.width = `${viewport.width}px`
		container.style.height = `${viewport.height}px`

		const context = canvas.getContext("2d")
		page.render({ canvasContext: context, viewport })

		return page.getTextContent().then(async (textContent) => {
			const textLayerDiv = document.createElement("div")
			textLayerDiv.className = "textLayer"
			textLayerDiv.style.width = `${viewport.width}px`
			textLayerDiv.style.height = `${viewport.height}px`
			textLayerDiv.style.setProperty("--scale-factor", scale.toString())
			container.appendChild(textLayerDiv)

			const textLayer = new TextLayer({
				textContentSource: textContent,
				container: textLayerDiv,
				viewport: viewport,
			})

			await textLayer.render()

			// Highlighting logic
			setTimeout(() => {
				if (highlightTerms.length === 0) {
					return
				}

				const spans = [...textLayerDiv.querySelectorAll("span")]
				const positionMap = []
				let normalizedOffset = 0

				spans.forEach((span, spanIndex) => {
					const originalText = span.textContent
					const normalizedText = normalize(originalText)

					// Insert virtual space between non-adjacent spans (OCR text layers
					// position each word as a separate span without explicit spaces)
					if (normalizedText.length > 0 && positionMap.length > 0) {
						const lastChar = positionMap[positionMap.length - 1].char
						if (lastChar !== " " && normalizedText[0] !== " ") {
							positionMap.push({
								spanIndex,
								normalizedPos: normalizedOffset,
								originalPos: -1,
								char: " ",
							})
							normalizedOffset += 1
						}
					}

					for (let i = 0; i < normalizedText.length; i++) {
						positionMap.push({
							spanIndex,
							normalizedPos: normalizedOffset + i,
							originalPos: i,
							char: normalizedText[i],
						})
					}
					normalizedOffset += normalizedText.length
				})

				const normalizedFullText = positionMap.map((p) => p.char).join("")
				const allMatches = highlightTerms.flatMap((term) =>
					findMatchesForTerm(normalizedFullText, term),
				)

				const spanHighlights = new Map()

				allMatches.forEach((match) => {
					addMatchHighlights(match, positionMap, spans, spanHighlights)
				})

				// Insert <mark> tags for precise character-level matching
				spanHighlights.forEach((highlights, spanIdx) => {
					const span = spans[spanIdx]
					const text = span.textContent

					highlights.sort((a, b) => a.start - b.start)
					const merged = []
					highlights.forEach((h) => {
						if (merged.length === 0 || merged[merged.length - 1].end < h.start) {
							merged.push(h)
						} else {
							merged[merged.length - 1].end = Math.max(merged[merged.length - 1].end, h.end)
						}
					})

					let html = ""
					let lastPos = 0
					merged.forEach((range) => {
						html += text.substring(lastPos, range.start)
						html += `<mark>${text.substring(range.start, range.end)}</mark>`
						lastPos = range.end
					})
					html += text.substring(lastPos)
					span.innerHTML = html
				})

				// Create overlay rects from <mark> bounding boxes
				const textLayerRect = textLayerDiv.getBoundingClientRect()
				const highlightLayer = document.createElement("div")
				highlightLayer.className = "highlightLayer"
				textLayerDiv.appendChild(highlightLayer)

				const marks = [...textLayerDiv.querySelectorAll("mark")]
				if (marks.length === 0) {
					return
				}

				// Detect OCR scale mismatch
				const avgHeight =
					marks.reduce((sum, m) => sum + m.getBoundingClientRect().height, 0) / marks.length
				const expectedHeight = viewport.height / 40
				const scaleFactor =
					avgHeight > 0 && expectedHeight / avgHeight > 1.3 ? expectedHeight / avgHeight : 1

				marks.forEach((mark) => {
					const rect = mark.getBoundingClientRect()
					const w = rect.width + 4
					const h = rect.height * scaleFactor
					const cx = rect.left + rect.width / 2 - textLayerRect.left
					const cy = rect.top + rect.height / 2 - textLayerRect.top

					const overlay = document.createElement("div")
					overlay.className = "highlight-rect"
					overlay.style.left = `${cx - w / 2}px`
					overlay.style.top = `${cy - h / 2}px`
					overlay.style.width = `${w}px`
					overlay.style.height = `${h}px`
					highlightLayer.appendChild(overlay)
				})
			}, 300)
		})
	})
	.catch((err) => {
		console.error("Error loading PDF:", err)
	})

// =========================================================================
// Translation System (Refactored with MVC pattern)
// =========================================================================

// Populate language select dynamically from constants
const targetLanguageSelect = document.getElementById("target-language")
Object.values(LANGUAGES).forEach((lang) => {
	const option = document.createElement("option")
	option.value = lang.code
	option.textContent = lang.name
	targetLanguageSelect.appendChild(option)
})

// Initialize Translation UI
const translationUI = new TranslationUI({
	translateBtn: document.getElementById("translate-btn"),
	showOriginalBtn: document.getElementById("show-original-btn"),
	targetLanguageSelect: targetLanguageSelect,
	translationStatus: document.getElementById("translation-status"),
	translationOverlay: document.getElementById("translation-overlay"),
	canvas: canvas,
})

// Initialize Translation Controller
const _translationController = new TranslationController(translationUI, window.pdfPath, pageNumber)
