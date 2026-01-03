# Assets Architecture

## 📁 Refactored Structure

The frontend application has been refactored following the **Separation of Concerns** and **MVC** patterns.

```
assets/
├── constants/
│   ├── api.js                     # API configuration and polling settings
│   └── languages.js               # Language constants (single source of truth)
├── services/
│   └── TranslationApiService.js   # Translation API communication
├── ui/
│   └── TranslationUI.js           # UI state management and rendering
├── controllers/
│   └── TranslationController.js   # Translation business logic
├── styles/
│   └── pdfViewer.css              # PDF viewer styles
├── pdfViewer.js                   # Main entry point (orchestrator)
└── app.js                         # Main application
```

## 🏗️ Architecture Pattern

### 1. **constants/languages.js**
**Responsibility:** Define all supported languages in a single place.

```javascript
export const LANGUAGES = {
    ES: { code: 'es', name: 'Español', englishName: 'Spanish' },
    EN: { code: 'en', name: 'Inglés', englishName: 'English' },
    // ... more languages
};

export function getLanguageName(code) { /* ... */ }
export function isValidLanguageCode(code) { /* ... */ }
```

**Advantages:**
- ✅ Single place to add/modify languages
- ✅ Easy to extend with new languages
- ✅ Centralized validation

### 2. **constants/api.js**
**Responsibility:** API endpoints and polling configuration.

```javascript
export const API_ENDPOINTS = {
    TRANSLATE: '/api/pdf/translate',
    STATUS: '/api/pdf/translation-status'
};

export const POLLING_CONFIG = {
    INTERVAL_MS: 1000,      // Poll every 1 second
    MAX_ATTEMPTS: 300,      // 5 minutes total
    TIMEOUT_MS: 300000
};
```

**Advantages:**
- ✅ Centralized configuration
- ✅ Easy to adjust polling behavior
- ✅ No magic numbers in code

### 3. **services/TranslationApiService.js**
**Responsibility:** HTTP communication with backend.

```javascript
export class TranslationApiService {
    async requestTranslation(filename, page, targetLanguage) { /* ... */ }
    async checkTranslationStatus(filename, page, targetLanguage) { /* ... */ }
    async pollForTranslation(filename, page, targetLanguage, onProgress) { /* ... */ }
}
```

**Advantages:**
- ✅ Clear separation of API logic
- ✅ Easy to test
- ✅ Reusable in other components
- ✅ Debug logging for troubleshooting

### 4. **ui/TranslationUI.js**
**Responsibility:** DOM manipulation and visual state.

```javascript
export class TranslationUI {
    updateStatus(text, type) { /* ... */ }
    showTranslation(translatedText) { /* ... */ }
    hideTranslation() { /* ... */ }
    setTranslateButtonState(disabled) { /* ... */ }
}
```

**Advantages:**
- ✅ Encapsulates UI logic
- ✅ Doesn't mix business logic with presentation
- ✅ Easy to modify styles without touching logic

### 5. **controllers/TranslationController.js**
**Responsibility:** Orchestrate translation workflow.

```javascript
export class TranslationController {
    async handleTranslateClick() { /* ... */ }
    _handleImmediateTranslation(data, targetLanguage) { /* ... */ }
    _handleQueuedTranslation(targetLanguage) { /* ... */ }
}
```

**Advantages:**
- ✅ Centralized business logic
- ✅ Coordinates API Service and UI
- ✅ Easy to follow execution flow

### 6. **pdfViewer.js**
**Responsibility:** Entry point and orchestration.

```javascript
// PDF Rendering (existing complex logic)
pdfjsLib.getDocument(pdfPath).promise.then(/* ... */);

// Translation System (refactored)
const translationUI = new TranslationUI({ /* ... */ });
const translationController = new TranslationController(/* ... */);
```

**Advantages:**
- ✅ Cleaner main file
- ✅ Easy to understand what each part does
- ✅ Keeps complex highlighting logic intact

## 🔄 Translation Workflow

```
User clicks "Translate"
         │
         ▼
TranslationController.handleTranslateClick()
         │
         ├─── TranslationUI.updateStatus("Starting...")
         │
         ▼
TranslationApiService.requestTranslation()
         │
         ├─── If cached/DB: Returns immediately
         │    └─── TranslationController._handleImmediateTranslation()
         │         └─── TranslationUI.showTranslation()
         │
         └─── If queued: Starts polling
              └─── TranslationController._handleQueuedTranslation()
                   └─── TranslationApiService.pollForTranslation()
                        └─── TranslationUI.showTranslation()
```

## 🔍 Frontend Polling System

The frontend polls the backend every **1 second** to check translation status:

```javascript
// constants/api.js
export const POLLING_CONFIG = {
    INTERVAL_MS: 1000,      // Check every 1 second
    MAX_ATTEMPTS: 300,      // Up to 300 attempts (5 minutes)
    TIMEOUT_MS: 300000      // 5 minute total timeout
};
```

**Why fast polling?**
- ✅ Better UX - User sees completion within 1-2 seconds
- ✅ Low overhead - Simple status check query
- ✅ Works with cache - Backend returns cached results instantly

**Console logging:**
The service includes debug logging for troubleshooting:
```
[Polling] Starting for document.pdf page 42 to es
[Polling] Attempt 1/300: {status: 'processing', ready: false}
[Polling] Attempt 2/300: {status: 'processing', ready: false}
...
[Polling] Attempt 80/300: {status: 'success', ready: true, source: 'cache'}
[Polling] Translation ready!
```

## 🌍 Adding New Languages

To add a new language, you only need to modify **one file**:

```javascript
// constants/languages.js
export const LANGUAGES = {
    // ... existing languages
    ZH: { code: 'zh', name: 'Chinese', englishName: 'Chinese' },
    JA: { code: 'ja', name: 'Japanese', englishName: 'Japanese' },
    KO: { code: 'ko', name: 'Korean', englishName: 'Korean' },
};
```

The rest of the system will detect it automatically.

## 🧪 Testing

Each module can be tested independently:

```javascript
// Test TranslationApiService
const service = new TranslationApiService();
await service.requestTranslation('test.pdf', 1, 'es');

// Test TranslationUI
const ui = new TranslationUI(mockElements);
ui.updateStatus('Test', 'success');

// Test TranslationController
const controller = new TranslationController(mockUI, 'test.pdf', 1);
await controller.handleTranslateClick();
```

## 📝 Future Improvements

1. **Refactor PDF Rendering** - Extract highlighting logic to separate service
2. **TypeScript** - Add types for better maintainability
3. **Unit Tests** - Automated tests for each module
4. **Error Boundaries** - More robust error handling
5. **Progressive Enhancement** - Progressive UX improvements

## 🔧 Development

```bash
# Compile assets in development
npm run dev

# Compile assets for production
npm run build

# Watch mode (auto-recompile)
npm run watch
```

## 📚 Backend Documentation

See queue system documentation in [messenger-worker.md](messenger-worker.md) and [translation-tracking.md](translation-tracking.md).
