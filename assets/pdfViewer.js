import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist/build/pdf';
import { TextLayerBuilder, EventBus } from 'pdfjs-dist/web/pdf_viewer';
import 'pdfjs-dist/web/pdf_viewer.css';

GlobalWorkerOptions.workerSrc = '/build/pdf.worker.js';

// Normalize text: remove accents, convert to lowercase, and remove spaces for flexible searching
function normalizeNoSpaces(str) {
    return str
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // remove accents
        .replace(/\s+/g, '') // remove spaces
        .toLowerCase();
}

// Terms to highlight (normalized)
const highlightTerms = JSON.parse(decodeURIComponent(window.highlight || '[]')).map(normalizeNoSpaces);
const pdfPath = `/pdfs/${window.pdfPath}`;
const pageNumber = parseInt(window.pageNumber || '1', 10);

const canvas = document.getElementById('pdf-canvas');
const container = document.querySelector('.container');
const eventBus = new EventBus();

getDocument(pdfPath).promise
    .then(pdf => pdf.getPage(pageNumber))
    .then(page => {
        const scale = 1.5;
        const viewport = page.getViewport({ scale });

        canvas.height = viewport.height;
        canvas.width = viewport.width;
        const context = canvas.getContext('2d');

        // Render the canvas
        page.render({ canvasContext: context, viewport });

        // Text layer
        return page.getTextContent().then(textContent => {
            const textLayerDiv = document.createElement('div');
            textLayerDiv.className = 'textLayer';
            container.appendChild(textLayerDiv);

            const textLayer = new TextLayerBuilder({
                textLayerDiv,
                pageIndex: page.pageIndex,
                viewport,
                eventBus,
            });

            textLayer.setTextContent(textContent);
            textLayer.render();

            // Wait for the text layer to finish rendering before highlighting
            // After rendering the textLayer and adding it to the DOM:

            setTimeout(() => {
                if (highlightTerms.length === 0) return;

                const spans = [...textLayerDiv.querySelectorAll('span')];
                const fullText = spans.map(span => span.textContent).join('');
                const normalizedFullText = normalizeNoSpaces(fullText);

                let totalHits = 0;

                highlightTerms.forEach(term => {
                    let startIndex = 0;

                    while (true) {
                        const index = normalizedFullText.indexOf(term, startIndex);
                        if (index === -1) break;

                        // Now find which span(s) contain this index and mark it

                        let charCount = 0;
                        let markStartSpanIndex = -1;
                        let markStartOffset = -1;
                        let markEndSpanIndex = -1;
                        let markEndOffset = -1;

                        for (let i = 0; i < spans.length; i++) {
                            const spanText = normalizeNoSpaces(spans[i].textContent);
                            const spanLen = spanText.length;

                            if (markStartSpanIndex === -1 && charCount + spanLen > index) {
                                markStartSpanIndex = i;
                                markStartOffset = index - charCount;
                            }
                            if (markStartSpanIndex !== -1 && charCount + spanLen >= index + term.length) {
                                markEndSpanIndex = i;
                                markEndOffset = index + term.length - charCount;
                                break;
                            }
                            charCount += spanLen;
                        }

                        if (markStartSpanIndex !== -1 && markEndSpanIndex !== -1) {
                            // Now insert <mark> in the corresponding spans
                            // If start and end are in the same span:
                            if (markStartSpanIndex === markEndSpanIndex) {
                                const span = spans[markStartSpanIndex];
                                const text = span.textContent;
                                const before = text.slice(0, markStartOffset);
                                const marked = text.slice(markStartOffset, markEndOffset);
                                const after = text.slice(markEndOffset);

                                span.innerHTML = before + '<mark>' + marked + '</mark>' + after;
                            } else {
                                // Multiple spans: more complex, can be done step-by-step or simplified.

                                // To simplify, highlight all text from markStartOffset in start span,
                                // all full intermediate spans, and up to markEndOffset in end span.

                                // Start with start span:
                                let span = spans[markStartSpanIndex];
                                const textStart = span.textContent;
                                const before = textStart.slice(0, markStartOffset);
                                const markedStart = textStart.slice(markStartOffset);
                                span.innerHTML = before + '<mark>' + markedStart + '</mark>';

                                // For intermediate spans between start+1 and end-1:
                                for (let si = markStartSpanIndex + 1; si < markEndSpanIndex; si++) {
                                    span = spans[si];
                                    span.innerHTML = '<mark>' + span.textContent + '</mark>';
                                }

                                // For the end span:
                                span = spans[markEndSpanIndex];
                                const textEnd = span.textContent;
                                const markedEnd = textEnd.slice(0, markEndOffset);
                                const after = textEnd.slice(markEndOffset);
                                span.innerHTML = '<mark>' + markedEnd + '</mark>' + after;
                            }
                            totalHits++;
                        }

                        startIndex = index + term.length;
                    }
                });

                console.log('🔍 Total matches highlighted:', totalHits);
            }, 200);
        });
    })
    .catch(err => {
        console.error('Error loading PDF:', err);
    });
