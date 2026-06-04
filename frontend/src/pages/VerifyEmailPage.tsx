import { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import apiClient from '../services/api';
import LoadingSpinner from '../components/Common/LoadingSpinner';

export default function VerifyEmailPage() {
  const [searchParams] = useSearchParams();
  const [status, setStatus] = useState<'verifying' | 'success' | 'error'>('verifying');
  const [message, setMessage] = useState('');
  const token = searchParams.get('token');

  useEffect(() => {
    const verifyEmail = async () => {
      if (!token) {
        setStatus('error');
        setMessage('Brak tokenu weryfikacyjnego');
        return;
      }

      try {
        const response = await apiClient.get(`/api/auth/verify-email?token=${token}`);
        setStatus('success');
        setMessage(response.data.data.message);
      } catch (err: any) {
        setStatus('error');
        setMessage(err.response?.data?.error?.message || 'Nie udało się zweryfikować email');
      }
    };

    verifyEmail();
  }, [token]);

  if (status === 'verifying') {
    return <LoadingSpinner message="Weryfikacja adresu email..." />;
  }

  if (status === 'success') {
    return (
      <div style={{ maxWidth: '500px', margin: '100px auto', padding: '20px' }}>
        <div style={{
          background: '#e8f5e9',
          border: '2px solid #4caf50',
          borderRadius: '8px',
          padding: '30px',
          textAlign: 'center'
        }}>
          <div style={{ fontSize: '48px', marginBottom: '20px' }}>✓</div>
          <h1>Email zweryfikowany!</h1>
          <p style={{ marginTop: '20px', color: '#666', lineHeight: '1.6' }}>
            {message}
          </p>
          <Link
            to="/login"
            style={{
              display: 'inline-block',
              marginTop: '30px',
              padding: '12px 30px',
              background: '#4a90e2',
              color: 'white',
              textDecoration: 'none',
              borderRadius: '4px',
              fontSize: '16px'
            }}
          >
            Przejdź do logowania
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '500px', margin: '100px auto', padding: '20px' }}>
      <div style={{
        background: '#ffebee',
        border: '2px solid #d32f2f',
        borderRadius: '8px',
        padding: '30px',
        textAlign: 'center'
      }}>
        <div style={{ fontSize: '48px', marginBottom: '20px' }}>✗</div>
        <h1>Błąd weryfikacji</h1>
        <p style={{ marginTop: '20px', color: '#666', lineHeight: '1.6' }}>
          {message}
        </p>
        <p style={{ marginTop: '30px' }}>
          <Link to="/login" style={{ color: '#4a90e2' }}>Wróć do logowania</Link>
        </p>
      </div>
    </div>
  );
}
