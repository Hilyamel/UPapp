# Phase 5: Authentication Implementation

> **STATUS:** This file needs conversion to executable format with superpowers standards (checkboxes, TDD, 2-5 min tasks).
> **PRIORITY:** Convert when ready to implement auth (after Phases 3-4 complete).

## Overview

UPapp implements passwordless authentication using two methods:
1. **Google OAuth 2.0** - Sign in with Google account
2. **Magic Link** - Email-based one-time login link

**Note:** Below is reference documentation. Will be converted to executable tasks when implementing authentication.

Both methods use JWT tokens stored in HTTP-only cookies for session management.

## Authentication Flow Diagram

```
┌─────────────┐
│   User      │
└──────┬──────┘
       │
       ├─── Option 1: Google OAuth ────────────────────┐
       │                                                 │
       │    1. Click "Sign in with Google"              │
       │    2. Redirect to Google                       │
       │    3. User authorizes                          │
       │    4. Google redirects back with code          │
       │    5. Backend exchanges code for user info     │
       │    6. Backend creates/updates user             │
       │    7. Backend generates JWT                    │
       │    8. JWT set in HTTP-only cookie              │
       │                                                 │
       ├─── Option 2: Magic Link ──────────────────────┤
       │                                                 │
       │    1. Enter email address                      │
       │    2. Backend generates secure token           │
       │    3. Backend stores token in DynamoDB         │
       │    4. Backend sends email with link            │
       │    5. User clicks link in email                │
       │    6. Backend verifies token                   │
       │    7. Backend creates/updates user             │
       │    8. Backend generates JWT                    │
       │    9. JWT set in HTTP-only cookie              │
       │                                                 │
       └─────────────────────────────────────────────────┘
                           │
                    ┌──────▼───────┐
                    │  Authenticated │
                    │     Session    │
                    └────────────────┘
```

## Google OAuth 2.0 Implementation

### Prerequisites

1. **Create Google Cloud Project**:
   - Go to https://console.cloud.google.com/
   - Create new project "UPapp"
   - Enable Google+ API

2. **Configure OAuth Consent Screen**:
   - User Type: External
   - App name: UPapp
   - Scopes: email, profile, openid

3. **Create OAuth 2.0 Credentials**:
   - Application type: Web application
   - Authorized redirect URIs: `http://localhost:5173/auth/google/callback`, `https://yourdomain.com/auth/google/callback`
   - Copy Client ID and Client Secret

### Frontend Implementation

**src/components/Auth/GoogleOAuth.jsx**:

```javascript
import { Button } from 'primereact/button'

export const GoogleOAuth = () => {
  const handleGoogleLogin = () => {
    const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID
    const redirectUri = `${window.location.origin}/auth/google/callback`
    const scope = 'openid email profile'
    const state = crypto.randomUUID() // CSRF protection
    
    // Store state in sessionStorage for verification
    sessionStorage.setItem('oauth_state', state)
    
    const authUrl =
      `https://accounts.google.com/o/oauth2/v2/auth?` +
      `client_id=${clientId}&` +
      `redirect_uri=${encodeURIComponent(redirectUri)}&` +
      `response_type=code&` +
      `scope=${encodeURIComponent(scope)}&` +
      `state=${state}`

    window.location.href = authUrl
  }

  return (
    <Button
      label="Sign in with Google"
      icon="pi pi-google"
      onClick={handleGoogleLogin}
      className="p-button-outlined w-full"
    />
  )
}
```

**OAuth Callback Handler**:

```javascript
import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { authService } from '../../services/auth'
import { useAuth } from '../../hooks/useAuth'

export const GoogleOAuthCallback = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { login } = useAuth()
  const [error, setError] = useState(null)

  useEffect(() => {
    const code = searchParams.get('code')
    const state = searchParams.get('state')
    const storedState = sessionStorage.getItem('oauth_state')

    // Verify state for CSRF protection
    if (state !== storedState) {
      setError('Invalid state parameter')
      return
    }

    if (code) {
      authService
        .googleAuth(code)
        .then((userData) => {
          sessionStorage.removeItem('oauth_state')
          login(userData)
          navigate('/dashboard')
        })
        .catch((err) => {
          console.error('Google auth failed:', err)
          setError('Authentication failed')
          setTimeout(() => navigate('/login'), 2000)
        })
    }
  }, [searchParams])

  if (error) {
    return <div className="p-error">{error}</div>
  }

  return (
    <div className="flex flex-column align-items-center justify-content-center" style={{ height: '100vh' }}>
      <i className="pi pi-spin pi-spinner" style={{ fontSize: '3rem' }}></i>
      <p>Authenticating with Google...</p>
    </div>
  )
}
```

### Backend Implementation

**src/Services/GoogleOAuthService.php**:

```php
<?php
namespace UpApp\Services;

use GuzzleHttp\Client;
use UpApp\Repositories\UserRepository;
use Ramsey\Uuid\Uuid;

class GoogleOAuthService
{
    private UserRepository $userRepository;
    private Client $httpClient;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->httpClient = new Client();
    }

    public function authenticate(string $code): array
    {
        // Exchange authorization code for tokens
        $tokens = $this->exchangeCodeForTokens($code);
        
        // Get user info from Google
        $userInfo = $this->getUserInfo($tokens['access_token']);
        
        // Find or create user
        $user = $this->userRepository->findByEmail($userInfo['email']);
        
        if (!$user) {
            $user = $this->createUser($userInfo);
        } else {
            $this->updateLastLogin($user['userId']);
        }
        
        return $user;
    }

    private function exchangeCodeForTokens(string $code): array
    {
        $response = $this->httpClient->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'code' => $code,
                'client_id' => $_ENV['GOOGLE_CLIENT_ID'],
                'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'],
                'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'],
                'grant_type' => 'authorization_code',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function getUserInfo(string $accessToken): array
    {
        $response = $this->httpClient->get('https://www.googleapis.com/oauth2/v2/userinfo', [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    private function createUser(array $userInfo): array
    {
        $userId = Uuid::uuid4()->toString();
        $isAdmin = ($userInfo['email'] === $_ENV['ADMIN_EMAIL']);

        $user = [
            'userId' => $userId,
            'email' => $userInfo['email'],
            'authProvider' => 'google',
            'googleId' => $userInfo['id'],
            'isAdmin' => $isAdmin,
            'isActive' => true,
            'createdAt' => date('c'),
            'updatedAt' => date('c'),
            'lastLoginAt' => date('c'),
        ];

        $this->userRepository->create($user);
        return $user;
    }

    private function updateLastLogin(string $userId): void
    {
        $this->userRepository->updateLastLogin($userId);
    }
}
```

## Magic Link Implementation

### How It Works

1. User enters email address
2. Backend generates cryptographically secure token
3. Token stored in DynamoDB with 15-minute expiry (TTL)
4. Email sent with link: `https://app.com/auth/magic-link/verify?token=ABC123`
5. User clicks link within 15 minutes
6. Backend verifies token, creates/logs in user
7. Token marked as used (one-time use only)

### Frontend Implementation

**src/components/Auth/MagicLinkLogin.jsx**:

```javascript
import { useState } from 'react'
import { InputText } from 'primereact/inputtext'
import { Button } from 'primereact/button'
import { Message } from 'primereact/message'
import { authService } from '../../services/auth'

export const MagicLinkLogin = () => {
  const [email, setEmail] = useState('')
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState(null)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setMessage(null)

    try {
      await authService.requestMagicLink(email)
      setMessage({
        severity: 'success',
        text: 'Check your email for a login link! The link expires in 15 minutes.',
      })
      setEmail('')
    } catch (error) {
      setMessage({
        severity: 'error',
        text: error.response?.data?.error || 'Failed to send magic link',
      })
    } finally {
      setLoading(false)
    }
  }

  const isValidEmail = (email) => {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  }

  return (
    <form onSubmit={handleSubmit} className="p-fluid">
      <div className="p-field p-mb-3">
        <label htmlFor="email">Email Address</label>
        <InputText
          id="email"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
          placeholder="your@email.com"
          disabled={loading}
        />
      </div>

      {message && <Message severity={message.severity} text={message.text} className="p-mb-3" />}

      <Button
        type="submit"
        label="Send Magic Link"
        icon="pi pi-envelope"
        loading={loading}
        disabled={!isValidEmail(email)}
      />

      <small className="p-mt-2 block text-center text-500">
        We'll email you a secure login link
      </small>
    </form>
  )
}
```

**Magic Link Verification**:

```javascript
import { useEffect, useState } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { authService } from '../../services/auth'
import { useAuth } from '../../hooks/useAuth'

export const MagicLinkVerify = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { login } = useAuth()
  const [status, setStatus] = useState('verifying') // verifying | success | error

  useEffect(() => {
    const token = searchParams.get('token')

    if (!token) {
      setStatus('error')
      return
    }

    authService
      .verifyMagicLink(token)
      .then((userData) => {
        setStatus('success')
        login(userData)
        setTimeout(() => navigate('/dashboard'), 1000)
      })
      .catch((err) => {
        console.error('Magic link verification failed:', err)
        setStatus('error')
      })
  }, [searchParams])

  return (
    <div className="flex flex-column align-items-center justify-content-center" style={{ height: '100vh' }}>
      {status === 'verifying' && (
        <>
          <i className="pi pi-spin pi-spinner" style={{ fontSize: '3rem' }}></i>
          <p>Verifying magic link...</p>
        </>
      )}
      {status === 'success' && (
        <>
          <i className="pi pi-check-circle text-green-500" style={{ fontSize: '3rem' }}></i>
          <p>Login successful! Redirecting...</p>
        </>
      )}
      {status === 'error' && (
        <>
          <i className="pi pi-times-circle text-red-500" style={{ fontSize: '3rem' }}></i>
          <p>Invalid or expired magic link</p>
          <Button label="Back to Login" onClick={() => navigate('/login')} className="p-mt-3" />
        </>
      )}
    </div>
  )
}
```

### Backend Implementation

**src/Services/MagicLinkService.php**:

```php
<?php
namespace UpApp\Services;

use UpApp\Repositories\UserRepository;
use UpApp\Repositories\MagicLinkRepository;
use Ramsey\Uuid\Uuid;

class MagicLinkService
{
    private UserRepository $userRepository;
    private MagicLinkRepository $magicLinkRepository;
    private EmailService $emailService;

    public function __construct(
        UserRepository $userRepository,
        MagicLinkRepository $magicLinkRepository,
        EmailService $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->magicLinkRepository = $magicLinkRepository;
        $this->emailService = $emailService;
    }

    public function sendMagicLink(string $email): void
    {
        // Generate cryptographically secure token
        $token = bin2hex(random_bytes(32)); // 64 characters
        
        $now = time();
        $expiresAt = $now + 900; // 15 minutes

        // Store token in DynamoDB
        $this->magicLinkRepository->create([
            'token' => $token,
            'email' => $email,
            'createdAt' => date('c', $now),
            'expiresAt' => date('c', $expiresAt),
            'usedAt' => null,
            'TTL' => $expiresAt, // DynamoDB auto-deletes after expiry
        ]);

        // Send email
        $link = "{$_ENV['FRONTEND_URL']}/auth/magic-link/verify?token={$token}";
        $this->emailService->sendMagicLink($email, $link);
    }

    public function verifyMagicLink(string $token): array
    {
        // Find magic link
        $magicLink = $this->magicLinkRepository->findByToken($token);

        if (!$magicLink) {
            throw new \Exception('Invalid magic link');
        }

        // Check if already used
        if ($magicLink['usedAt'] !== null) {
            throw new \Exception('Magic link already used');
        }

        // Check if expired
        if (strtotime($magicLink['expiresAt']) < time()) {
            throw new \Exception('Magic link expired');
        }

        // Mark as used
        $this->magicLinkRepository->markAsUsed($token);

        // Find or create user
        $user = $this->userRepository->findByEmail($magicLink['email']);

        if (!$user) {
            $user = $this->createUser($magicLink['email']);
        } else {
            $this->userRepository->updateLastLogin($user['userId']);
        }

        return $user;
    }

    private function createUser(string $email): array
    {
        $userId = Uuid::uuid4()->toString();
        $isAdmin = ($email === $_ENV['ADMIN_EMAIL']);

        $user = [
            'userId' => $userId,
            'email' => $email,
            'authProvider' => 'magiclink',
            'isAdmin' => $isAdmin,
            'isActive' => true,
            'createdAt' => date('c'),
            'updatedAt' => date('c'),
            'lastLoginAt' => date('c'),
        ];

        $this->userRepository->create($user);
        return $user;
    }
}
```

**src/Services/EmailService.php**:

```php
<?php
namespace UpApp\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    private function configureSMTP(): void
    {
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['SMTP_HOST'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['SMTP_USERNAME'];
        $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = (int)$_ENV['SMTP_PORT'];
        $this->mailer->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
    }

    public function sendMagicLink(string $toEmail, string $link): void
    {
        try {
            $this->mailer->addAddress($toEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Your UPapp Login Link';
            $this->mailer->Body = $this->getMagicLinkEmailBody($link);
            $this->mailer->AltBody = "Click to login: {$link}\n\nThis link expires in 15 minutes.";

            $this->mailer->send();
            $this->mailer->clearAddresses();
        } catch (Exception $e) {
            throw new \Exception("Failed to send email: {$this->mailer->ErrorInfo}");
        }
    }

    private function getMagicLinkEmailBody(string $link): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 20px 0; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Login to UPapp</h2>
                <p>Click the button below to securely log in to your account:</p>
                <a href="{$link}" class="button">Login to UPapp</a>
                <p><strong>This link expires in 15 minutes.</strong></p>
                <p>If you didn't request this email, you can safely ignore it.</p>
                <div class="footer">
                    <p>UPapp - Nonviolent Communication Forms</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
```

## JWT Session Management

### Token Structure

```json
{
  "sub": "550e8400-e29b-41d4-a716-446655440000",
  "email": "user@example.com",
  "isAdmin": false,
  "iat": 1717000000,
  "exp": 1717604800
}
```

### Security Features

1. **HTTP-only cookies**: Token not accessible via JavaScript (XSS protection)
2. **SameSite=Lax**: CSRF protection
3. **Secure flag in production**: HTTPS-only
4. **7-day expiry**: Balance between convenience and security
5. **No refresh tokens**: Simpler, users re-authenticate after 7 days

### Logout Implementation

**Frontend**:
```javascript
const handleLogout = async () => {
  await authService.logout()
  logout() // Clear AuthContext
  navigate('/login')
}
```

**Backend**:
```php
public function logout(Request $request, Response $response): Response
{
    $response->getBody()->write(json_encode(['success' => true]));
    return $this->jwtHelper->clearTokenCookie($response)
        ->withHeader('Content-Type', 'application/json');
}
```

## Admin User Detection

Admin status is determined at first login:

```php
$isAdmin = ($email === $_ENV['ADMIN_EMAIL']);
```

**Environment variable**:
```env
ADMIN_EMAIL=janczewski.piotr@gmail.com
```

**JWT includes admin flag**:
```php
$token = $this->jwtHelper->generateToken([
    'sub' => $user['userId'],
    'email' => $user['email'],
    'isAdmin' => $user['isAdmin'], // true for admin
]);
```

**Admin middleware checks flag**:
```php
$isAdmin = $request->getAttribute('isAdmin', false);
if (!$isAdmin) {
    return $response->withStatus(403);
}
```

## Security Best Practices

1. **Never log tokens** - Don't log JWT or magic link tokens
2. **Use HTTPS in production** - Set Secure cookie flag
3. **Strong JWT secret** - Generate with `openssl rand -base64 32`
4. **Rate limit auth endpoints** - Prevent brute force attacks
5. **Email verification** - Confirm email delivery (optional enhancement)
6. **Token rotation** - Consider refresh tokens for longer sessions (future)
7. **Audit logging** - Log all authentication events

## Testing

### Manual Testing

**Google OAuth**:
1. Click "Sign in with Google"
2. Authorize app
3. Verify redirect to dashboard
4. Check cookie in DevTools
5. Refresh page - should stay logged in

**Magic Link**:
1. Enter email address
2. Check email inbox
3. Click magic link
4. Verify redirect to dashboard
5. Try using link again - should fail (one-time use)
6. Wait 15+ minutes - link should expire

**Admin Access**:
1. Login as janczewski.piotr@gmail.com
2. Verify admin link visible in navigation
3. Access admin panel
4. Login as different email
5. Verify admin link hidden
6. Try accessing /admin directly - should return 403

### Automated Testing (Future)

- Unit tests for token generation/verification
- Integration tests for auth endpoints
- E2E tests for complete auth flows

## Troubleshooting

**Google OAuth fails**:
- Check Client ID and Secret in .env
- Verify redirect URI matches Google Console
- Check CORS settings

**Magic link not received**:
- Check SMTP credentials in .env
- Check spam folder
- Verify email service logs

**JWT verification fails**:
- Check JWT_SECRET matches between sessions
- Verify token not expired
- Check cookie settings (HttpOnly, SameSite)

**Admin access denied**:
- Verify ADMIN_EMAIL in .env matches user email exactly
- Check JWT payload includes isAdmin=true
- Verify AdminMiddleware applied to admin routes

## Next Steps

1. Implement all authentication components
2. Test both auth flows thoroughly
3. Configure email service (Gmail, SendGrid, etc.)
4. Setup Google OAuth credentials
5. Test admin access controls
6. Add rate limiting to auth endpoints
