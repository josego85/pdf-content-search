/**
 * Translation API Service
 * Handles all communication with the translation backend API.
 * Single Responsibility: API communication for translations.
 */

import { API_ENDPOINTS, POLLING_CONFIG } from '../constants/api.js';

export class TranslationApiService {
    /**
     * Request a translation (returns immediately if cached, or queues for async processing)
     * @param {string} filename - PDF filename
     * @param {number} page - Page number
     * @param {string} targetLanguage - Target language code
     * @returns {Promise<Object>} - Translation response
     */
    async requestTranslation(filename, page, targetLanguage) {
        const response = await fetch(API_ENDPOINTS.TRANSLATE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename, page, target_language: targetLanguage }),
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Translation request failed');
        }

        return response.json();
    }

    /**
     * Check translation status (for polling)
     * @param {string} filename - PDF filename
     * @param {number} page - Page number
     * @param {string} targetLanguage - Target language code
     * @returns {Promise<Object>} - Status response
     */
    async checkTranslationStatus(filename, page, targetLanguage) {
        const response = await fetch(API_ENDPOINTS.STATUS, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ filename, page, target_language: targetLanguage }),
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Status check failed');
        }

        return response.json();
    }

    /**
     * Poll for translation completion
     * @param {string} filename - PDF filename
     * @param {number} page - Page number
     * @param {string} targetLanguage - Target language code
     * @param {Function} onProgress - Optional callback for progress updates
     * @returns {Promise<Object>} - Final translation result
     */
    async pollForTranslation(filename, page, targetLanguage, onProgress = null) {
        for (let attempt = 0; attempt < POLLING_CONFIG.MAX_ATTEMPTS; attempt++) {
            try {
                const result = await this.checkTranslationStatus(filename, page, targetLanguage);

                // Notify progress if callback provided
                if (onProgress) {
                    onProgress(attempt + 1, POLLING_CONFIG.MAX_ATTEMPTS);
                }

                // Translation ready
                if (result.status === 'success' && result.ready) {
                    return result;
                }

                // Error occurred
                if (result.status === 'error') {
                    throw new Error(result.message || 'Translation error');
                }

                // Still processing, wait before next poll
                await this._sleep(POLLING_CONFIG.INTERVAL_MS);
            } catch (error) {
                // On last attempt, throw the error
                if (attempt === POLLING_CONFIG.MAX_ATTEMPTS - 1) {
                    throw error;
                }
                // Otherwise, wait and retry
                await this._sleep(POLLING_CONFIG.INTERVAL_MS);
            }
        }

        throw new Error('Translation timeout - exceeded maximum wait time');
    }

    /**
     * Sleep helper for polling
     * @param {number} ms - Milliseconds to sleep
     * @returns {Promise<void>}
     */
    _sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}
