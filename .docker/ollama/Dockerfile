# Vulnerabilities reported are in the Ubuntu 24.04 base — no upstream fix available in 0.18.0 (latest).
# Pin to SHA for supply-chain integrity; upgrade when a patched release is available.
FROM ollama/ollama:0.18.0@sha256:e23e6499890325d31f3454cdc83f7a613a2423c8a9e167bbafea0b0e3c27d7c6

COPY .docker/ollama/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
