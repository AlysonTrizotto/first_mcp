FROM ollama/ollama:latest

# Definir argumentos
ARG OLLAMA_MODEL=llama3.2

# Configurações
ENV OLLAMA_HOST=0.0.0.0
ENV OLLAMA_PORT=11434
ENV OLLAMA_MODEL=${OLLAMA_MODEL}

# Copiar script de inicialização
COPY docker/ollama/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 11434

CMD ["/start.sh"]
