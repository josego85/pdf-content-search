#!/bin/bash
# ============================================
# Ollama Models Auto-Download & Healthcheck
# ============================================
# Downloads required models if missing
# Returns 0 if all models exist, 1 otherwise
# ============================================

set -e

TRANSLATION_MODEL="${OLLAMA_MODEL:-qwen2.5:7b}"
EMBEDDING_MODEL="${OLLAMA_EMBEDDING_MODEL:-nomic-embed-text}"

# Colors
BLUE='\033[0;34m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Ollama is ready
if ! ollama list &>/dev/null; then
    echo -e "${YELLOW}⏳ Ollama not ready yet...${NC}"
    exit 1
fi

# Check and download translation model
if ! ollama list | grep -q "^${TRANSLATION_MODEL}"; then
    echo -e "${BLUE}📥 Downloading translation model: ${TRANSLATION_MODEL}${NC}"
    echo -e "${YELLOW}⏳ This may take 3-5 minutes (4.7GB)...${NC}"
    ollama pull "${TRANSLATION_MODEL}"
    echo -e "${GREEN}✅ Translation model ready${NC}"
else
    echo -e "${GREEN}✅ Translation model already exists${NC}"
fi

# Check and download embedding model
if ! ollama list | grep -q "^${EMBEDDING_MODEL}"; then
    echo -e "${BLUE}📥 Downloading embedding model: ${EMBEDDING_MODEL}${NC}"
    echo -e "${YELLOW}⏳ This may take 30-60 seconds (274MB)...${NC}"
    ollama pull "${EMBEDDING_MODEL}"
    echo -e "${GREEN}✅ Embedding model ready${NC}"
else
    echo -e "${GREEN}✅ Embedding model already exists${NC}"
fi

echo -e "${GREEN}✅ All Ollama models ready${NC}"
exit 0
