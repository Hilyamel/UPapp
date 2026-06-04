# empAItyczne - Konfiguracja Claude API

## Problem został rozwiązany

**Root cause**: System nie używał pliku `empathy-prompt.txt`, tylko miał zahardkodowane odpowiedzi w `FormHandler.php`.

**Rozwiązanie**: Utworzono `ClaudeService` który:
- Ładuje prompt z `config/empathy-prompt.txt`
- Wywołuje Claude API (model: claude-sonnet-4-20250514)
- Formatuje dane z formularzy (TUP, DUP, DOS)
- Ma fallback gdy API niedostępne

## Konfiguracja

### 1. Uzyskaj API Key od Anthropic

1. Zarejestruj się na https://console.anthropic.com/
2. Przejdź do Settings → API Keys
3. Utwórz nowy klucz API

### 2. Dodaj klucz do .env

Utwórz plik `.env` (jeśli nie istnieje) lub dodaj linię:

```bash
ANTHROPIC_API_KEY=sk-ant-api03-xxxxxxxxxx
```

### 3. Uruchom testy

**Unit testy** (działają bez API key):
```bash
php vendor/bin/phpunit tests/Unit/Services/ClaudeServiceTest.php --testdox
```

**Integration testy** (wymagają API key):
```bash
php vendor/bin/phpunit tests/Integration/ClaudeAPIIntegrationTest.php --testdox
```

**Wszystkie testy**:
```bash
php vendor/bin/phpunit --testdox
```

## Co zostało zrobione

### Nowe pliki:
1. `src/Services/ClaudeService.php` - serwis do komunikacji z Claude API
2. `tests/Unit/Services/ClaudeServiceTest.php` - testy jednostkowe (6 testów)
3. `tests/Integration/ClaudeAPIIntegrationTest.php` - testy integracyjne (7 testów)

### Zmodyfikowane pliki:
1. `src/Handlers/FormHandler.php` - używa teraz `ClaudeService` zamiast zahardkodowanych odpowiedzi
2. `.env.example` - dodano `ANTHROPIC_API_KEY`

## Testy pokrywają:

### Unit testy:
- ✅ Ładowanie system promptu z pliku
- ✅ Formatowanie danych dla TUP (z T-table)
- ✅ Formatowanie danych dla DUP (prefiksowane pola)
- ✅ Formatowanie danych dla DOS (osądy → uczucia)
- ✅ Fallback gdy brak API key
- ✅ Obsługa pustych danych

### Integration testy (wymagają API key):
- ✅ Kompletny formularz TUP → feedback z NVC language
- ✅ Niekompletny formularz TUP → pytania pomocnicze
- ✅ Formularz DOS → feedback
- ✅ Formularz DUP → feedback
- ✅ Emotikony (1-3 na feedback)
- ✅ Długość odpowiedzi (1-5 zdań)
- ✅ Czas odpowiedzi (<10 sekund) - benchmark

## Jak działa teraz empAItyczne

1. Użytkownik klika przycisk **empAItyczne**
2. `FormHandler::generateNVCFeedback()` wywołuje `ClaudeService::generateEmpatheticFeedback()`
3. `ClaudeService`:
   - Ładuje pełny prompt z `config/empathy-prompt.txt` (z pełnymi listami uczuć i potrzeb NVC)
   - Formatuje dane formularza
   - Wysyła request do Claude API
   - Zwraca feedback z emotikonami
4. W przypadku błędu API → fallback do prostej odpowiedzi

## Dalsze kroki

Jeśli chcesz dodać więcej testów dla innych funkcjonalności:

```bash
# Struktura testów
tests/
├── Unit/              # Testy jednostkowe (bez zależności zewnętrznych)
│   ├── Services/
│   ├── Handlers/
│   └── Repositories/
└── Integration/       # Testy integracyjne (z API, DB, itp.)
    └── ClaudeAPIIntegrationTest.php
```

Zgodnie z CI/CD norms:
- Unit testy powinny działać zawsze (bez API keys, bez DB)
- Integration testy mogą być skipowane jeśli brak konfiguracji
- Benchmark testy monitorują performance
