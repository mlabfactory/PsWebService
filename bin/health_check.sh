#!/bin/bash

# Health Check Script
# Legge le URL da un file requests.json e verifica lo stato HTTP
# Formato atteso: oggetto JSON con valori che hanno un campo "url"

REQUESTS_FILE="vendor/mdf/json-database-storage/storage/database/requests.json"
TIMEOUT=10
ALL_OK=true
CHECKED=0

# Colori (disabilitabili con NO_COLOR=1)
if [ -z "$NO_COLOR" ] && [ -t 1 ]; then
  GREEN="\033[0;32m"
  RED="\033[0;31m"
  YELLOW="\033[1;33m"
  CYAN="\033[0;36m"
  BOLD="\033[1m"
  RESET="\033[0m"
else
  GREEN="" RED="" YELLOW="" CYAN="" BOLD="" RESET=""
fi

# Verifica dipendenze
for cmd in curl jq; do
  if ! command -v "$cmd" &>/dev/null; then
    echo "Errore: '$cmd' non è installato." >&2
    exit 2
  fi
done

# Verifica file
if [ ! -f "$REQUESTS_FILE" ]; then
  echo "Errore: file '$REQUESTS_FILE' non trovato." >&2
  echo "Uso: $0 [percorso/requests.json]" >&2
  exit 2
fi

echo -e "${BOLD}=======================================================${RESET}"
echo -e "${BOLD} Health Check - $(date '+%Y-%m-%d %H:%M:%S')${RESET}"
echo -e "${BOLD} File: $REQUESTS_FILE${RESET}"
echo -e "${BOLD}=======================================================${RESET}"

# Estrae URL univoche dal JSON (rimuove duplicati con sort -u)
URLS=$(jq -r '.[].url' "$REQUESTS_FILE" 2>/dev/null | sort -u)

if [ -z "$URLS" ]; then
  echo -e "${RED}Nessuna URL trovata nel file JSON.${RESET}"
  exit 2
fi

TOTAL=$(echo "$URLS" | wc -l | tr -d ' ')
echo -e " URL uniche trovate: ${BOLD}$TOTAL${RESET}"
echo ""

while IFS= read -r URL; do
  [ -z "$URL" ] && continue

  # Nasconde credenziali tipo https://KEY@host nella visualizzazione
  DISPLAY_URL=$(echo "$URL" | sed -E 's|https?://[^@]+@|https://***@|g')

  echo -e "${CYAN}→ $DISPLAY_URL${RESET}"

  HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    --max-time "$TIMEOUT" \
    --location \
    "$URL" 2>/dev/null)
  EXIT_CODE=$?

  if [ $EXIT_CODE -ne 0 ]; then
    case $EXIT_CODE in
      6)  REASON="host non raggiungibile" ;;
      7)  REASON="connessione rifiutata" ;;
      28) REASON="timeout (>${TIMEOUT}s)" ;;
      *)  REASON="curl error $EXIT_CODE" ;;
    esac
    echo -e "  STATUS: ${RED}❌ ERRORE - $REASON${RESET}"
    ALL_OK=false
  elif [ "$HTTP_CODE" -ge 200 ] && [ "$HTTP_CODE" -lt 300 ]; then
    echo -e "  STATUS: ${GREEN}✅ OK (HTTP $HTTP_CODE)${RESET}"
  elif [ "$HTTP_CODE" -ge 300 ] && [ "$HTTP_CODE" -lt 400 ]; then
    echo -e "  STATUS: ${YELLOW}↪  REDIRECT (HTTP $HTTP_CODE)${RESET}"
  elif [ "$HTTP_CODE" -ge 500 ]; then
    echo -e "  STATUS: ${RED}❌ SERVER ERROR (HTTP $HTTP_CODE)${RESET}"
    ALL_OK=false
  elif [ "$HTTP_CODE" -ge 400 ]; then
    echo -e "  STATUS: ${YELLOW}⚠️  CLIENT ERROR (HTTP $HTTP_CODE)${RESET}"
    ALL_OK=false
  else
    echo -e "  STATUS: ${YELLOW}⚠️  RISPOSTA INATTESA (HTTP $HTTP_CODE)${RESET}"
    ALL_OK=false
  fi

  CHECKED=$((CHECKED + 1))
  echo ""

done <<< "$URLS"

echo -e "${BOLD}=======================================================${RESET}"
echo -e " URL controllate: ${BOLD}$CHECKED / $TOTAL${RESET}"
if $ALL_OK; then
  echo -e " RISULTATO: ${GREEN}${BOLD}✅ TUTTI I CHECK SUPERATI${RESET}"
  exit 0
else
  echo -e " RISULTATO: ${RED}${BOLD}❌ UNO O PIÙ CHECK FALLITI${RESET}"
  exit 1
fi
