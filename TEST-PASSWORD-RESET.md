# Test Instrukcja - Reset Hasła

## Przygotowanie

**Upewnij się że działa:**
- ✅ Backend: http://localhost:8080
- ✅ Frontend: http://localhost:5173

```bash
# Sprawdź backend
curl http://localhost:8080/api/health

# Sprawdź frontend (w przeglądarce)
# Otwórz: http://localhost:5173
```

## Test Flow - Krok po kroku

### 1. Forgot Password (Zapomniałem hasła)

1. Otwórz: http://localhost:5173/forgot-password
2. Wpisz email: `janczewski.piotr@gmail.com`
3. Kliknij "Wyślij link do resetu"
4. **Oczekiwany rezultat**: Zielony komunikat "If an account exists..."

**Debug jeśli nie działa:**
- Otwórz Developer Tools (F12)
- Zakładka Console - sprawdź błędy
- Zakładka Network - sprawdź request do `/api/auth/forgot-password`
  - Status powinien być 200
  - Response: `{"success":true, ...}`

### 2. Email (Sprawdź skrzynkę)

1. Otwórz skrzynkę: janczewski.piotr@gmail.com
2. Znajdź email "Reset hasła - UPapp"
3. **Uwaga:** Email może się spóźnić 1-5 minut (SMTP delay)
4. Sprawdź folder SPAM jeśli nie ma w inbox

**Debug jeśli email nie przychodzi:**
```bash
# Sprawdź backend logs
cat /tmp/upapp-backend-test2.log | grep -i "password\|email\|error"
```

### 3. Kliknij Link z Emaila

Email zawiera link: `http://localhost:5173/reset-password?token=...`

1. Kliknij link w emailu
2. **Oczekiwany rezultat**: Strona "Ustaw nowe hasło"
3. **Debug w Developer Tools Console:**
   - Powinieneś zobaczyć:
     ```
     ResetPasswordPage mounted
     Token from URL: [długi string]
     Full URL: http://localhost:5173/reset-password?token=...
     ```

**Jeśli widzisz "Brak tokenu resetującego":**
- Token nie został przekazany w URL
- Sprawdź czy link zawiera `?token=...`
- Sprawdź Console czy token jest null

### 4. Ustaw Nowe Hasło

1. Wpisz nowe hasło (minimum 8 znaków): `newpassword123`
2. Potwierdź hasło: `newpassword123`
3. Kliknij "Zresetuj hasło"

**Oczekiwany rezultat:**
- Alert: "Hasło zostało zresetowane. Możesz się teraz zalogować."
- Przekierowanie do: http://localhost:5173/login

**Debug w Console:**
```
Submitting password reset...
Token: [długi string]
Password length: 15
```

**Możliwe błędy i rozwiązania:**

| Błąd | Przyczyna | Rozwiązanie |
|------|-----------|-------------|
| "Invalid or expired reset token" | Token wygasł (>24h) lub nieprawidłowy | Wygeneruj nowy link (krok 1) |
| "Token and new password are required" | Token nie dotarł do backend | Sprawdź Console i Network tab |
| "Password must be at least 8 characters" | Hasło za krótkie | Użyj min 8 znaków |
| "Hasła nie są identyczne" | Błąd w potwierdzeniu | Wpisz dokładnie to samo |
| "Błąd połączenia z serwerem" | Backend nie działa | Sprawdź http://localhost:8080/api/health |

### 5. Zaloguj się Nowym Hasłem

1. Strona logowania: http://localhost:5173/login
2. Email: `janczewski.piotr@gmail.com`
3. Hasło: `newpassword123` (nowe hasło)
4. Kliknij "Zaloguj"

**Oczekiwany rezultat:**
- Zalogowanie sukces
- Przekierowanie do Dashboard

## Zmiany w tej wersji

✅ **Token ważny przez 24h** (było: 1h)  
✅ **Mniejsza ikona błędu** (było: h-5, teraz: h-4)  
✅ **Lepsze error messages** - pokazuje szczegóły  
✅ **Debug logging** - Console pokazuje co się dzieje  
✅ **CORS fix** - obsługuje porty 5173, 5174, 5175  

## Szybki Test (bez emaila)

Jeśli chcesz przetestować bez czekania na email, użyj curl:

```bash
# 1. Request reset
curl -X POST http://localhost:8080/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{"email":"janczewski.piotr@gmail.com"}'

# 2. Pobierz token z DynamoDB (wymaga AWS CLI)
# lub sprawdź backend logs

# 3. Test reset bezpośrednio
curl -X POST http://localhost:8080/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -H "Origin: http://localhost:5173" \
  -d '{"token":"WKLEJ-TOKEN-TUTAJ","password":"newpassword123"}'
```

## Kontakt

Jeśli nadal nie działa:
1. Skopiuj błędy z Console (F12)
2. Skopiuj request/response z Network tab
3. Opisz dokładnie który krok nie działa
