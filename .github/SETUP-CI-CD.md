# Konfiguracja CI/CD - Automatyczne testy z emailem

## Co zostało zrobione

Utworzono GitHub Actions workflow który:
- ✅ Uruchamia testy automatycznie przy push/PR do `main` lub `develop`
- ✅ Uruchamia się tylko gdy zmienią się pliki w `backend/`
- ✅ Najpierw unit testy (zawsze), potem integration testy (jeśli jest API key)
- ✅ Wysyła email z raportem (sukces lub failure)
- ✅ Załącza pełne wyniki testów jako attachment

## Konfiguracja GitHub Secrets

Przejdź do swojego repo na GitHub → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

Dodaj następujące secrets (te same wartości co w `.env`):

### Wymagane:
```
SMTP_HOST              smtp.gmail.com
SMTP_PORT              587
SMTP_USERNAME          twoj-email@gmail.com
SMTP_PASSWORD          haslo-do-smtp (lub app-specific password)
SMTP_FROM_EMAIL        twoj-email@gmail.com
SMTP_RECIPIENT_EMAIL   twoj-email@gmail.com (tu przyjdzie raport)
```

### Opcjonalne (dla integration testów):
```
ANTHROPIC_API_KEY      sk-ant-api03-xxxxxxxxxx
```

## Gmail - App-Specific Password

Jeśli używasz Gmail z 2FA (co jest zalecane):

1. Idź do https://myaccount.google.com/apppasswords
2. Wybierz "Mail" i "Other device"
3. Wpisz "GitHub Actions UPapp"
4. Skopiuj wygenerowane hasło (16 znaków)
5. Użyj tego hasła jako `SMTP_PASSWORD` w GitHub Secrets

## Jak to działa

### Automatyczne uruchomienie:
- **Push** do `main` lub `develop` → testy się uruchamiają
- **Pull Request** do `main` lub `develop` → testy się uruchamiają
- Zmiany poza `backend/` → **nie** uruchamiają testów (oszczędność minut)

### Email raport zawiera:
- Status: ✅ Success lub ❌ Failure
- Branch i commit
- Autor zmian
- Link do pełnych logów na GitHub
- Attachment: pełne wyniki testów (text file)

### Przykład email subject:
```
UPapp Tests - success - main
UPapp Tests - failure - develop
```

## Testowanie lokalnie

Przed push możesz przetestować lokalnie:

```bash
cd backend

# Unit testy (zawsze działają)
php vendor/bin/phpunit tests/Unit --testdox

# Integration testy (wymagają API key w .env)
php vendor/bin/phpunit tests/Integration --testdox

# Wszystkie testy
php vendor/bin/phpunit --testdox
```

## Sprawdzanie statusu

1. Przejdź do repo na GitHub
2. Kliknij zakładkę **Actions**
3. Zobaczysz listę wszystkich uruchomień workflow
4. Zielony checkmark ✅ = testy przeszły
5. Czerwony X ❌ = testy nie przeszły

## Workflow działa dla zmian w:
- `backend/src/**` - kod źródłowy
- `backend/tests/**` - testy
- `backend/config/**` - konfiguracja (np. empathy-prompt.txt)
- `backend/composer.json` - zależności
- `.github/workflows/backend-tests.yml` - sam workflow

## Troubleshooting

### Email nie przychodzi:
1. Sprawdź czy wszystkie secrets są ustawione w GitHub
2. Sprawdź folder SPAM
3. Sprawdź logi workflow na GitHub (zakładka Actions)
4. Upewnij się że używasz App-Specific Password (nie zwykłego hasła Gmail)

### Integration testy są skipowane:
- To normalne - działają tylko gdy `ANTHROPIC_API_KEY` jest ustawiony w GitHub Secrets
- Unit testy zawsze działają

### Workflow się nie uruchamia:
- Upewnij się że zmieniasz pliki w `backend/`
- Sprawdź czy push jest do `main` lub `develop` branch
- Sprawdź zakładkę Actions na GitHub czy workflow jest enabled

## Dalsze usprawnienia (opcjonalne)

Możesz dodać:
- Workflow dla frontend (npm test, build)
- Code coverage reporting
- Deployment po sukcesie testów
- Slack notifications zamiast email
- Matrix testing (różne wersje PHP)

## Koszt

GitHub Actions:
- **Public repos**: Unlimited minutes (free)
- **Private repos**: 2000 minutes/month free (potem $0.008/minute)

Ten workflow zużywa ~2-3 minuty na run, więc:
- Public repo: **bezpłatne**
- Private repo: ~600-1000 runs/month w darmowym limicie
