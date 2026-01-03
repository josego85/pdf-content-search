/**
 * Language constants and mappings for the translation system.
 * Single source of truth for all language-related data.
 */

export const LANGUAGES = {
	ES: { code: "es", name: "Español", englishName: "Spanish" },
	EN: { code: "en", name: "Inglés", englishName: "English" },
	DE: { code: "de", name: "Alemán", englishName: "German" },
}

/**
 * Get language name by code
 * @param {string} code - ISO 639-1 language code
 * @returns {string} - Language name in Spanish
 */
export function getLanguageName(code) {
	const language = Object.values(LANGUAGES).find((lang) => lang.code === code)
	return language ? language.name : code.toUpperCase()
}

/**
 * Get language label (uppercase code) for badges
 * @param {string} code - ISO 639-1 language code
 * @returns {string} - Uppercase language code
 */
export function getLanguageLabel(code) {
	return code ? code.toUpperCase() : ""
}

/**
 * Get all available language codes
 * @returns {string[]} - Array of language codes
 */
export function getAvailableLanguageCodes() {
	return Object.values(LANGUAGES).map((lang) => lang.code)
}

/**
 * Check if language code is valid
 * @param {string} code - ISO 639-1 language code
 * @returns {boolean}
 */
export function isValidLanguageCode(code) {
	return Object.values(LANGUAGES).some((lang) => lang.code === code)
}
