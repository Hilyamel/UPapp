import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import apiClient from '../services/api';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [needsVerification, setNeedsVerification] = useState(false);
  const [resendSuccess, setResendSuccess] = useState('');
  const [resendLoading, setResendLoading] = useState(false);
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setNeedsVerification(false);
    setResendSuccess('');

    if (!email || !password) {
      setError('Proszę wypełnić wszystkie pola');
      return;
    }

    setLoading(true);

    try {
      await login(email, password);
      navigate('/dashboard');
    } catch (err: any) {
      const errorCode = err.response?.data?.error?.code;
      const errorMessage = err.response?.data?.error?.message || 'Nieprawidłowy email lub hasło';

      setError(errorMessage);

      if (errorCode === 'EMAIL_NOT_VERIFIED') {
        setNeedsVerification(true);
      }
    } finally {
      setLoading(false);
    }
  };

  const handleResendVerification = async () => {
    setResendLoading(true);
    setResendSuccess('');
    setError('');

    try {
      await apiClient.post('/api/auth/resend-verification', { email });
      setResendSuccess('Email weryfikacyjny został wysłany. Sprawdź swoją skrzynkę.');
      setNeedsVerification(false);
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Nie udało się wysłać emaila');
    } finally {
      setResendLoading(false);
    }
  };

  return (
    <div style={{ maxWidth: '400px', margin: '100px auto', padding: '20px' }}>
      <h1>Logowanie</h1>

      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: '15px' }}>
          <label htmlFor="email" style={{ display: 'block', marginBottom: '5px' }}>
            Adres email
          </label>
          <input
            id="email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            style={{ width: '100%', padding: '8px', fontSize: '16px' }}
          />
        </div>

        <div style={{ marginBottom: '15px' }}>
          <label htmlFor="password" style={{ display: 'block', marginBottom: '5px' }}>
            Hasło
          </label>
          <input
            id="password"
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            style={{ width: '100%', padding: '8px', fontSize: '16px' }}
          />
        </div>

        {error && (
          <div style={{
            color: '#d32f2f',
            background: '#ffebee',
            padding: '10px',
            borderRadius: '4px',
            marginBottom: '15px'
          }}>
            {error}
          </div>
        )}

        {resendSuccess && (
          <div style={{
            color: '#2e7d32',
            background: '#e8f5e9',
            padding: '10px',
            borderRadius: '4px',
            marginBottom: '15px'
          }}>
            {resendSuccess}
          </div>
        )}

        {needsVerification && (
          <button
            type="button"
            onClick={handleResendVerification}
            disabled={resendLoading}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '14px',
              background: '#ff9800',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: resendLoading ? 'not-allowed' : 'pointer',
              opacity: resendLoading ? 0.6 : 1,
              marginBottom: '15px'
            }}
          >
            {resendLoading ? 'Wysyłanie...' : 'Wyślij ponownie email weryfikacyjny'}
          </button>
        )}

        <button
          type="submit"
          disabled={loading}
          style={{
            width: '100%',
            padding: '12px',
            fontSize: '16px',
            background: '#4a90e2',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            cursor: loading ? 'not-allowed' : 'pointer',
            opacity: loading ? 0.6 : 1,
          }}
        >
          {loading ? 'Logowanie...' : 'Zaloguj się'}
        </button>

        <p style={{ marginTop: '15px', textAlign: 'center' }}>
          <Link to="/forgot-password" style={{ color: '#4a90e2', textDecoration: 'none' }}>
            Nie pamiętasz hasła?
          </Link>
        </p>
      </form>

      <p style={{ marginTop: '20px', textAlign: 'center' }}>
        Nie masz konta? <Link to="/register">Zarejestruj się</Link>
      </p>
    </div>
  );
}
