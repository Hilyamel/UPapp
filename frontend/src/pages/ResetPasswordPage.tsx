import React, { useState } from 'react';
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import api from '../services/api';

export default function ResetPasswordPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const token = searchParams.get('token');

  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  // Debug: log token on mount
  React.useEffect(() => {
    console.log('ResetPasswordPage mounted');
    console.log('Token from URL:', token);
    console.log('Full URL:', window.location.href);
  }, [token]);

  if (!token) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="max-w-md w-full bg-white p-8 rounded-lg shadow">
          <h2 className="text-2xl font-bold text-red-600 mb-4">Brak tokenu resetującego</h2>
          <p className="text-gray-600 mb-4">
            Link do resetowania hasła jest nieprawidłowy lub wygasł.
          </p>
          <Link to="/forgot-password" className="text-blue-600 hover:text-blue-500">
            Wyślij nowy link
          </Link>
        </div>
      </div>
    );
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    console.log('Submitting password reset...');
    console.log('Token:', token);
    console.log('Password length:', password.length);

    // Validation
    if (password.length < 8) {
      setError('Hasło musi mieć minimum 8 znaków');
      return;
    }

    if (password !== confirmPassword) {
      setError('Hasła nie są identyczne');
      return;
    }

    setLoading(true);

    try {
      await api.post('/api/auth/reset-password', {
        token,
        password
      });

      // Success - redirect to login
      alert('Hasło zostało zresetowane. Możesz się teraz zalogować.');
      navigate('/login');
    } catch (err: any) {
      console.error('=== RESET PASSWORD ERROR ===');
      console.error('Full error object:', err);
      console.error('Error code:', err.code);
      console.error('Error message:', err.message);
      console.error('Response:', err.response);
      console.error('Response status:', err.response?.status);
      console.error('Response data:', err.response?.data);
      console.error('Request config:', err.config);
      console.error('============================');

      // Show detailed error message
      let errorMessage = 'Wystąpił błąd. Spróbuj ponownie.';

      if (err.response?.data?.error?.message) {
        errorMessage = err.response.data.error.message;
      } else if (err.response?.status === 400) {
        errorMessage = 'Nieprawidłowy token lub hasło. Sprawdź wymagania.';
      } else if (err.response?.status === 0 || err.code === 'ERR_NETWORK') {
        errorMessage = 'Błąd połączenia z serwerem. Sprawdź czy backend działa.';
      } else if (err.message) {
        errorMessage = err.message;
      }

      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Ustaw nowe hasło
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            Wprowadź nowe hasło do swojego konta (minimum 8 znaków).
          </p>
        </div>

        {error && (
          <div className="rounded-md bg-red-50 border border-red-200 p-3">
            <div className="flex items-start">
              <div className="flex-shrink-0">
                <svg className="h-4 w-4 text-red-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-2 flex-1">
                <p className="text-sm text-red-700">{error}</p>
              </div>
            </div>
          </div>
        )}

        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          <div className="rounded-md shadow-sm space-y-4">
            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700">
                Nowe hasło
              </label>
              <input
                id="password"
                name="password"
                type="password"
                required
                minLength={8}
                className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Minimum 8 znaków"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                disabled={loading}
              />
            </div>

            <div>
              <label htmlFor="confirm-password" className="block text-sm font-medium text-gray-700">
                Potwierdź hasło
              </label>
              <input
                id="confirm-password"
                name="confirm-password"
                type="password"
                required
                minLength={8}
                className="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                placeholder="Powtórz hasło"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                disabled={loading}
              />
            </div>
          </div>

          <div>
            <button
              type="submit"
              disabled={loading}
              className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              {loading ? 'Resetowanie...' : 'Zresetuj hasło'}
            </button>
          </div>

          <div className="text-center">
            <Link to="/login" className="font-medium text-blue-600 hover:text-blue-500">
              Powrót do logowania
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
