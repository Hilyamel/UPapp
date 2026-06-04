import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

export default function ChangePasswordPage() {
  const navigate = useNavigate();
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    // Validation
    if (newPassword.length < 8) {
      setError('Nowe hasło musi mieć minimum 8 znaków');
      return;
    }

    if (newPassword !== confirmPassword) {
      setError('Nowe hasła nie są identyczne');
      return;
    }

    if (currentPassword === newPassword) {
      setError('Nowe hasło musi być inne niż obecne');
      return;
    }

    setLoading(true);

    try {
      await api.post('/auth/change-password', {
        current_password: currentPassword,
        new_password: newPassword
      });

      setSuccess(true);
      setTimeout(() => {
        navigate('/dashboard');
      }, 2000);
    } catch (err: any) {
      setError(err.response?.data?.error?.message || 'Wystąpił błąd. Spróbuj ponownie.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ maxWidth: '500px', margin: '50px auto', padding: '20px' }}>
      <h2 style={{ marginBottom: '20px' }}>Zmień hasło</h2>

      {success && (
        <div style={{
          background: '#e8f5e9',
          color: '#2e7d32',
          padding: '15px',
          borderRadius: '4px',
          marginBottom: '20px',
          border: '1px solid #66bb6a'
        }}>
          ✓ Hasło zostało zmienione. Przekierowanie...
        </div>
      )}

      {error && (
        <div style={{
          background: '#ffebee',
          color: '#d32f2f',
          padding: '15px',
          borderRadius: '4px',
          marginBottom: '20px',
          border: '1px solid #f44336'
        }}>
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: '15px' }}>
          <label htmlFor="current-password" style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            Obecne hasło
          </label>
          <input
            id="current-password"
            type="password"
            value={currentPassword}
            onChange={(e) => setCurrentPassword(e.target.value)}
            required
            disabled={loading || success}
            style={{ width: '100%', padding: '10px', fontSize: '16px', borderRadius: '4px', border: '1px solid #ccc' }}
          />
        </div>

        <div style={{ marginBottom: '15px' }}>
          <label htmlFor="new-password" style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            Nowe hasło (minimum 8 znaków)
          </label>
          <input
            id="new-password"
            type="password"
            value={newPassword}
            onChange={(e) => setNewPassword(e.target.value)}
            required
            minLength={8}
            disabled={loading || success}
            style={{ width: '100%', padding: '10px', fontSize: '16px', borderRadius: '4px', border: '1px solid #ccc' }}
          />
        </div>

        <div style={{ marginBottom: '20px' }}>
          <label htmlFor="confirm-password" style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            Potwierdź nowe hasło
          </label>
          <input
            id="confirm-password"
            type="password"
            value={confirmPassword}
            onChange={(e) => setConfirmPassword(e.target.value)}
            required
            minLength={8}
            disabled={loading || success}
            style={{ width: '100%', padding: '10px', fontSize: '16px', borderRadius: '4px', border: '1px solid #ccc' }}
          />
        </div>

        <div style={{ display: 'flex', gap: '10px' }}>
          <button
            type="submit"
            disabled={loading || success}
            style={{
              flex: 1,
              padding: '12px',
              fontSize: '16px',
              background: loading || success ? '#ccc' : '#4a90e2',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: loading || success ? 'not-allowed' : 'pointer'
            }}
          >
            {loading ? 'Zmieniam...' : 'Zmień hasło'}
          </button>

          <button
            type="button"
            onClick={() => navigate('/dashboard')}
            disabled={loading}
            style={{
              flex: 1,
              padding: '12px',
              fontSize: '16px',
              background: '#757575',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: loading ? 'not-allowed' : 'pointer'
            }}
          >
            Anuluj
          </button>
        </div>
      </form>
    </div>
  );
}
