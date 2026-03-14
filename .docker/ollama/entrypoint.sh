#!/bin/sh
# ============================================
# Ollama Custom Entrypoint
# ============================================
# Starts the Ollama server and ensures required
# models are downloaded before the service is
# considered operational.
# ============================================

set -e

TRANSLATION_MODEL="${OLLAMA_MODEL:-qwen2.5:7b}"
EMBEDDING_MODEL="${OLLAMA_EMBEDDING_MODEL:-nomic-embed-text}"

echo "[ollama-init] Starting Ollama server..."
ollama serve &
OLLAMA_PID=$!

echo "[ollama-init] Waiting for server to be ready..."
until ollama list > /dev/null 2>&1; do
    sleep 2
done
echo "[ollama-init] Server ready."

# Pull translation model if missing
if ! ollama list | grep -q "^${TRANSLATION_MODEL}"; then
    echo "[ollama-init] Pulling translation model: ${TRANSLATION_MODEL} (~4.7GB, may take several minutes)..."
    ollama pull "${TRANSLATION_MODEL}"
    echo "[ollama-init] Translation model ready."
else
    echo "[ollama-init] Translation model already present: ${TRANSLATION_MODEL}"
fi

# Pull embedding model if missing
if ! ollama list | grep -q "^${EMBEDDING_MODEL}"; then
    echo "[ollama-init] Pulling embedding model: ${EMBEDDING_MODEL} (~274MB)..."
    ollama pull "${EMBEDDING_MODEL}"
    echo "[ollama-init] Embedding model ready."
else
    echo "[ollama-init] Embedding model already present: ${EMBEDDING_MODEL}"
fi

echo "[ollama-init] All models ready. Handing off to Ollama server process."

# Keep server running (wait for ollama serve to exit)
wait "${OLLAMA_PID}"
