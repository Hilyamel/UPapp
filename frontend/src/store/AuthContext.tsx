import { createContext, useState, useEffect, ReactNode } from 'react';
import apiClient from '../services/api';

interface User {
  id: string;
  email: string;
  full_name?: string;
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (email: string, password: string, fullName: string) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const checkAuth = async () => {
    try {
      const response = await apiClient.get('/api/auth/me');
      setUser(response.data.data);
    } catch (error) {
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    checkAuth();
  }, []);

  const login = async (email: string, password: string) => {
    const response = await apiClient.post('/api/auth/login', { email, password });
    setUser(response.data.data.user);
  };

  const register = async (email: string, password: string, fullName: string) => {
    const response = await apiClient.post('/api/auth/register', {
      email,
      password,
      full_name: fullName
    });
    setUser(response.data.data.user);
  };

  const logout = async () => {
    await apiClient.post('/api/auth/logout');
    setUser(null);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isAuthenticated: !!user,
        loading,
        login,
        register,
        logout,
        checkAuth
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}
