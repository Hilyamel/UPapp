import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export default function RegisterPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [fullName, setFullName] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);
  const { register } = useAuth();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!email || !password || !fullName) {
      setError('Proszę wypełnić wszystkie pola');
      return;
    }

    if (password.length < 8) {
      setError('Hasło musi mieć co najmniej 8 znaków');
      return;
    }

    setLoading(true);

    try {
      await register(email, password, fullName);
      setSuccess(true);
      setError('');
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Nie udało się utworzyć konta');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div style={{ maxWidth: '500px', margin: '100px auto', padding: '20px' }}>
        <div style={{
          background: '#e8f5e9',
          border: '2px solid #4caf50',
          borderRadius: '8px',
          padding: '30px',
          textAlign: 'center'
        }}>
          <div style={{ fontSize: '48px', marginBottom: '20px' }}>✉️</div>
          <h1>Sprawdź swoją skrzynkę email</h1>
          <p style={{ marginTop: '20px', color: '#666', lineHeight: '1.6' }}>
            Wysłaliśmy wiadomość z linkiem weryfikacyjnym na adres:
          </p>
          <p style={{ fontWeight: 'bold', margin: '10px 0', fontSize: '18px' }}>
            {email}
          </p>
          <p style={{ marginTop: '20px', color: '#666', lineHeight: '1.6' }}>
            Kliknij link w wiadomości, aby aktywować swoje konto i móc się zalogować.
          </p>
          <p style={{ marginTop: '30px', textAlign: 'center' }}>
            <Link to="/login" style={{ color: '#4a90e2' }}>Wróć do logowania</Link>
          </p>
        </div>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '400px', margin: '100px auto', padding: '20px' }}>
      <h1>Rejestracja</h1>

      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: '15px' }}>
          <label htmlFor="fullName" style={{ display: 'block', marginBottom: '5px' }}>
            Imię i nazwisko
          </label>
          <input
            id="fullName"
            type="text"
            value={fullName}
            onChange={(e) => setFullName(e.target.value)}
            required
            style={{ width: '100%', padding: '8px', fontSize: '16px' }}
          />
        </div>

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
          <small style={{ color: '#666' }}>Minimum 8 znaków</small>
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
          {loading ? 'Rejestrowanie...' : 'Zarejestruj się'}
        </button>
      </form>

      <p style={{ marginTop: '20px', textAlign: 'center' }}>
        Masz już konto? <Link to="/login">Zaloguj się</Link>
      </p>
    </div>
  );
}
