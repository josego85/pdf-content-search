import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist/build/pdf';
import {
    TextLayerBuilder,
    EventBus
} from 'pdfjs-dist/web/pdf_viewer';
import 'pdfjs-dist/web/pdf_viewer.css';

GlobalWorkerOptions.workerSrc = '/build/pdf.worker.js';

const pdfPath = `/pdfs/${window.pdfPath}`;
const highlightTerm = (window.highlightTerm || '').toLowerCase();
const pageNumber = parseInt(window.pageNumber || 1, 10);

const canvas = document.getElementById('pdf-canvas');
const container = document.querySelector('.container');

const eventBus = new EventBus();

getDocument(pdfPath).promise
    .then(pdf => pdf.getPage(pageNumber))
    .then(page => {
        const scale = 1.5;
        const viewport = page.getViewport({ scale });

        // Render canvas
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const context = canvas.getContext('2d');
        page.render({ canvasContext: context, viewport });

        // Render text layer
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

            setTimeout(() => {
                if (!highlightTerm) return;

                const spans = textLayerDiv.querySelectorAll('span');
                spans.forEach(span => {
                    const originalText = span.textContent;
                    if (originalText.toLowerCase().includes(highlightTerm)) {
                        span.innerHTML = originalText.replace(
                            new RegExp(`(${highlightTerm})`, 'gi'),
                            '<mark>$1</mark>'
                        );
                    }
                });
            }, 200);
        });
    })
    .catch(error => {
        console.error('PDF rendering error:', error);
    });
