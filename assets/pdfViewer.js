import * as pdfjsLib from 'pdfjs-dist/build/pdf.mjs';
import { TextLayer } from 'pdfjs-dist/build/pdf.mjs';
import 'pdfjs-dist/web/pdf_viewer.css';

pdfjsLib.GlobalWorkerOptions.workerSrc = '/build/pdf.worker.mjs';

// Normalize text: remove accents, convert to lowercase (keep spaces for accurate positioning)
function normalize(str) {
    return str
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // remove accents
        .toLowerCase()
        .trim();
}

// Check if position is at a word boundary
function isWordBoundary(text, pos) {
    if (pos < 0 || pos >= text.length) return true; // Start or end of text
    const char = text[pos];
    // Word boundary: space, punctuation, special chars, bullets, dashes, etc.
    // Includes: spaces, common punctuation, brackets, quotes, slashes,
    // em/en dashes (–—), bullets (•◦●), and other common separators
    return /[\s\.,;:!?¿¡\-–—\(\)\[\]\{\}\"\'\/\\•◦○●∙·<>«»°*+=@#$%^&|~`]/.test(char);
}

// Terms to highlight (normalized, filtered)
const highlightTerms = JSON.parse(decodeURIComponent(window.highlight || '[]'))
    .map(term => normalize(term))
    .filter(term => term.length > 0);

console.log('🔍 Terms to highlight:', highlightTerms);
const pdfPath = `/pdfs/${window.pdfPath}`;
const pageNumber = parseInt(window.pageNumber || '1', 10);

const canvas = document.getElementById('pdf-canvas');
const container = document.querySelector('.container');

pdfjsLib.getDocument(pdfPath).promise
    .then(pdf => pdf.getPage(pageNumber))
    .then(page => {
        const scale = 1.5;
        const viewport = page.getViewport({ scale });

        // Set canvas dimensions
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        // CRITICAL: Set container dimensions to match canvas
        container.style.width = `${viewport.width}px`;
        container.style.height = `${viewport.height}px`;

        const context = canvas.getContext('2d');

        // Render the canvas
        page.render({ canvasContext: context, viewport });

        // Text layer
        return page.getTextContent().then(async textContent => {
            const textLayerDiv = document.createElement('div');
            textLayerDiv.className = 'textLayer';
            // PDF.js v5 requires exact dimensions and scale factor
            textLayerDiv.style.width = `${viewport.width}px`;
            textLayerDiv.style.height = `${viewport.height}px`;
            textLayerDiv.style.setProperty('--scale-factor', scale.toString());
            container.appendChild(textLayerDiv);

            const textLayer = new TextLayer({
                textContentSource: textContent,
                container: textLayerDiv,
                viewport: viewport,
            });

            await textLayer.render();

            // Wait for the text layer to finish rendering before highlighting
            setTimeout(() => {
                if (highlightTerms.length === 0) return;

                const spans = [...textLayerDiv.querySelectorAll('span')];
                let totalHits = 0;

                // Create a mapping between normalized and original positions
                const positionMap = [];
                let normalizedOffset = 0;

                spans.forEach((span, spanIndex) => {
                    const originalText = span.textContent;
                    const normalizedText = normalize(originalText);

                    // Map each character in normalized text to original position
                    for (let i = 0; i < normalizedText.length; i++) {
                        positionMap.push({
                            spanIndex,
                            normalizedPos: normalizedOffset + i,
                            originalPos: i,
                            char: normalizedText[i]
                        });
                    }
                    normalizedOffset += normalizedText.length;
                });

                const normalizedFullText = positionMap.map(p => p.char).join('');
                console.log('📄 Normalized full text (first 200 chars):', normalizedFullText.substring(0, 200));
                console.log('📏 Total text length:', normalizedFullText.length);

                // Step 1: Collect all matches first (don't modify spans yet)
                const allMatches = [];

                highlightTerms.forEach(term => {
                    let startIndex = 0;
                    let foundCount = 0;

                    while (true) {
                        const index = normalizedFullText.indexOf(term, startIndex);
                        if (index === -1) break;

                        // Check word boundaries
                        const beforePos = index - 1;
                        const afterPos = index + term.length;
                        const beforeChar = beforePos >= 0 ? normalizedFullText[beforePos] : '';
                        const afterChar = afterPos < normalizedFullText.length ? normalizedFullText[afterPos] : '';

                        const isValidMatch = isWordBoundary(normalizedFullText, beforePos) &&
                                            isWordBoundary(normalizedFullText, afterPos);

                        // Hybrid intelligent mode: Detect malformed text (words >30 chars)
                        // Look at surrounding context to see if we're in a malformed section
                        let isHybridMatch = false;
                        if (!isValidMatch) {
                            // Check if we're in a suspiciously long word (likely malformed PDF text layer)
                            const contextStart = Math.max(0, index - 30);
                            const contextEnd = Math.min(normalizedFullText.length, index + term.length + 30);
                            const context = normalizedFullText.substring(contextStart, contextEnd);

                            // Count spaces in context - if very few, likely malformed
                            const spaceCount = (context.match(/\s/g) || []).length;
                            const contextLength = context.length;
                            const spaceRatio = spaceCount / contextLength;

                            // If context has normal spacing (>15%), this is likely a real compound word like "javascript"
                            // Don't match it even in hybrid mode
                            if (spaceRatio >= 0.15) {
                                isHybridMatch = false;
                                console.log(`⚠️ Skipped "${term}" at ${index} (compound word in normal text, space ratio: ${(spaceRatio * 100).toFixed(1)}%)`);
                            } else {
                                // Low space ratio (<15%) indicates malformed PDF
                                // But still check: if EITHER side has letters, it might be part of compound word
                                const beforeIsAlpha = /[a-z0-9]/.test(beforeChar);
                                const afterIsAlpha = /[a-z0-9]/.test(afterChar);

                                // Skip if after-side has letters AND term is short (e.g., "java" in "◦javascript")
                                // This catches cases like "◦javascript" where bullet is before but "script" continues after
                                if (afterIsAlpha && term.length < 8) {
                                    isHybridMatch = false;
                                    console.log(`⚠️ Skipped "${term}" at ${index} (likely part of compound word, continues with "${afterChar}...")`);
                                } else if (beforeIsAlpha && term.length < 8) {
                                    // Also skip if before-side has letters (e.g., "script" in "javascript◦")
                                    isHybridMatch = false;
                                    console.log(`⚠️ Skipped "${term}" at ${index} (likely part of compound word, starts with "...${beforeChar}")`);
                                } else {
                                    // Malformed PDF text - accept the match
                                    isHybridMatch = true;
                                    console.log(`🔧 Hybrid match for "${term}" at ${index} (malformed PDF text detected, space ratio: ${(spaceRatio * 100).toFixed(1)}%)`);
                                }
                            }
                        }

                        if (!isValidMatch && !isHybridMatch) {
                            console.log(`⚠️ Skipped "${term}" at position ${index} (not a complete word, context: "${normalizedFullText.substring(Math.max(0, index - 5), index + term.length + 5)}")`);
                            startIndex = index + 1;
                            continue;
                        }

                        foundCount++;
                        const matchType = isHybridMatch ? 'hybrid' : 'exact';
                        console.log(`✅ Found "${term}" at position ${index} (occurrence #${foundCount}, ${matchType}), context: "${normalizedFullText.substring(Math.max(0, index - 10), index + term.length + 10)}"`);
                        allMatches.push({
                            startPos: index,
                            endPos: index + term.length - 1
                        });

                        startIndex = index + 1;
                    }

                    console.log(`🔢 Total valid occurrences of "${term}": ${foundCount}`);
                });

                // Step 2: Group matches by span and apply all highlights at once
                const spanHighlights = new Map(); // spanIndex -> array of {start, end} in original text

                allMatches.forEach(match => {
                    const startMapping = positionMap.find(p => p.normalizedPos === match.startPos);
                    const endMapping = positionMap.find(p => p.normalizedPos === match.endPos);

                    if (startMapping && endMapping) {
                        const startSpanIdx = startMapping.spanIndex;
                        const endSpanIdx = endMapping.spanIndex;

                        if (startSpanIdx === endSpanIdx) {
                            // Single span
                            if (!spanHighlights.has(startSpanIdx)) {
                                spanHighlights.set(startSpanIdx, []);
                            }
                            spanHighlights.get(startSpanIdx).push({
                                start: startMapping.originalPos,
                                end: endMapping.originalPos + 1
                            });
                        } else {
                            // Multi-span (mark entire spans)
                            for (let i = startSpanIdx; i <= endSpanIdx; i++) {
                                if (!spanHighlights.has(i)) {
                                    spanHighlights.set(i, []);
                                }
                                if (i === startSpanIdx) {
                                    spanHighlights.get(i).push({
                                        start: startMapping.originalPos,
                                        end: spans[i].textContent.length
                                    });
                                } else if (i === endSpanIdx) {
                                    spanHighlights.get(i).push({
                                        start: 0,
                                        end: endMapping.originalPos + 1
                                    });
                                } else {
                                    // Entire span
                                    spanHighlights.get(i).push({
                                        start: 0,
                                        end: spans[i].textContent.length
                                    });
                                }
                            }
                        }
                        totalHits++;
                    }
                });

                // Step 3: Apply highlights using mark tags with careful CSS
                spanHighlights.forEach((highlights, spanIdx) => {
                    const span = spans[spanIdx];
                    const text = span.textContent;

                    // Sort and merge overlapping ranges
                    highlights.sort((a, b) => a.start - b.start);
                    const merged = [];
                    highlights.forEach(h => {
                        if (merged.length === 0 || merged[merged.length - 1].end < h.start) {
                            merged.push(h);
                        } else {
                            merged[merged.length - 1].end = Math.max(merged[merged.length - 1].end, h.end);
                        }
                    });

                    // Build HTML with marks - use substring to preserve exact spacing
                    let html = '';
                    let lastPos = 0;
                    merged.forEach(range => {
                        // Use substring instead of slice to be precise
                        html += text.substring(lastPos, range.start);
                        html += '<mark>' + text.substring(range.start, range.end) + '</mark>';
                        lastPos = range.end;
                    });
                    html += text.substring(lastPos);

                    span.innerHTML = html;
                });

                console.log('✨ Total matches highlighted:', totalHits);
            }, 300);
        });
    })
    .catch(err => {
        console.error('Error loading PDF:', err);
    });
