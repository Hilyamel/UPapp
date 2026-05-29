# Phase 4: Frontend Setup - React Application

> **STATUS:** This file needs conversion to executable format with superpowers standards (checkboxes, TDD, 2-5 min tasks).
> **PRIORITY:** Convert when ready to implement frontend (after Phase 3 complete).

## Overview

The frontend is a React 18 single-page application (SPA) built with Vite, using PrimeReact for UI components, FontAwesome for icons, and LocalForage for offline storage.

**Note:** Below is reference documentation. Will be converted to executable tasks when implementing frontend.

## Technology Stack

- **React 18.2+** - UI library
- **Vite 5.0+** - Build tool and dev server
- **React Router DOM 6.21+** - Client-side routing
- **PrimeReact 10.5+** - UI component library (DataTable, Dialog, Button, etc.)
- **FontAwesome 6.5+** - Icon library
- **Axios 1.6+** - HTTP client
- **LocalForage 1.10+** - IndexedDB wrapper for offline storage
- **ESLint** - Code linting
- **Prettier** - Code formatting

## Directory Structure

```
frontend/
├── package.json
├── vite.config.js
├── .eslintrc.js
├── .prettierrc
├── index.html
├── public/
│   └── favicon.ico
└── src/
    ├── main.jsx                    # Entry point
    ├── App.jsx                     # Main app with routing
    ├── index.css                   # Global styles
    │
    ├── components/
    │   ├── Auth/
    │   │   ├── GoogleOAuth.jsx     # Google OAuth button + callback
    │   │   ├── MagicLinkLogin.jsx  # Magic link email form
    │   │   └── ProtectedRoute.jsx  # Route guard
    │   ├── Admin/
    │   │   ├── UserListTable.jsx   # PrimeReact DataTable
    │   │   ├── UserEditDialog.jsx  # Edit user dialog
    │   │   └── UserDeleteConfirm.jsx
    │   ├── Forms/
    │   │   ├── DUPForm.jsx         # Dzienniczek Uczuć i Potrzeb
    │   │   ├── TUPForm.jsx         # Tabela Uczuć i Potrzeb
    │   │   ├── DOSForm.jsx         # Dziennik Osądów
    │   │   ├── CollapsibleList.jsx # Feelings/needs selector
    │   │   ├── FormSummary.jsx     # Display saved form
    │   │   └── FormList.jsx        # List forms with filters
    │   ├── Common/
    │   │   ├── LoadingSpinner.jsx
    │   │   ├── Toast.jsx           # Notification component
    │   │   ├── ErrorBoundary.jsx   # Error handling
    │   │   ├── ConnectionStatus.jsx # Online/offline indicator
    │   │   └── Navigation.jsx      # Top navigation bar
    │   └── Deploy/
    │       ├── DeployPanel.jsx     # Build & SFTP GUI
    │       └── BuildProgress.jsx   # Progress indicator
    │
    ├── pages/
    │   ├── LoginPage.jsx           # Login with OAuth + Magic Link
    │   ├── DashboardPage.jsx       # List all forms
    │   ├── FormPage.jsx            # Edit form (DUP/TUP/DOS)
    │   ├── FormSummaryPage.jsx     # View saved form
    │   └── AdminDashboard.jsx      # Admin user management
    │
    ├── services/
    │   ├── api.js                  # Axios client configuration
    │   ├── auth.js                 # Authentication API calls
    │   ├── forms.js                # Forms API calls
    │   ├── reference.js            # Reference data (feelings/needs)
    │   ├── admin.js                # Admin API calls
    │   └── offline.js              # IndexedDB operations
    │
    ├── hooks/
    │   ├── useAuth.js              # Authentication hook
    │   ├── useForm.js              # Form state + auto-save
    │   └── useOffline.js           # Online/offline detection
    │
    └── store/
        └── AuthContext.jsx         # Global auth state
```

## Package Configuration

### package.json

```json
{
  "name": "upapp-frontend",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "lint": "eslint . --ext js,jsx --report-unused-disable-directives --max-warnings 0",
    "format": "prettier --write \"src/**/*.{js,jsx,css,json}\""
  },
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.21.3",
    "axios": "^1.6.5",
    "localforage": "^1.10.0",
    "primereact": "^10.5.0",
    "primeicons": "^7.0.0",
    "@fortawesome/fontawesome-free": "^6.5.0"
  },
  "devDependencies": {
    "@vitejs/plugin-react": "^4.2.1",
    "vite": "^5.0.11",
    "eslint": "^8.56.0",
    "eslint-plugin-react": "^7.33.2",
    "eslint-plugin-react-hooks": "^4.6.0",
    "prettier": "^3.2.0"
  }
}
```

### vite.config.js

```javascript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'dist',
    assetsDir: 'assets',
    sourcemap: false,
    minify: 'terser',
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8080',
        changeOrigin: true,
      },
    },
  },
})
```

### .eslintrc.js

```javascript
module.exports = {
  env: { browser: true, es2020: true },
  extends: [
    'eslint:recommended',
    'plugin:react/recommended',
    'plugin:react/jsx-runtime',
    'plugin:react-hooks/recommended',
  ],
  parserOptions: { ecmaVersion: 'latest', sourceType: 'module' },
  settings: { react: { version: '18.2' } },
  plugins: ['react-hooks'],
  rules: {
    'react/prop-types': 'off',
    'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
  },
}
```

### .prettierrc

```json
{
  "semi": false,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5",
  "printWidth": 100
}
```

### .env.example

```env
VITE_API_BASE_URL=http://localhost:8080/api/v1
VITE_GOOGLE_CLIENT_ID=your-google-oauth-client-id.apps.googleusercontent.com
VITE_ADMIN_EMAIL=janczewski.piotr@gmail.com
```

## Core Services

### src/services/api.js

```javascript
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  withCredentials: true, // Send cookies with requests
  headers: {
    'Content-Type': 'application/json',
  },
})

// Response interceptor for error handling
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Redirect to login on unauthorized
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
```

### src/services/auth.js

```javascript
import api from './api'

export const authService = {
  // Google OAuth
  googleAuth: async (code) => {
    const response = await api.post('/auth/google', { code })
    return response.data
  },

  // Magic Link
  requestMagicLink: async (email) => {
    const response = await api.post('/auth/magic-link/request', { email })
    return response.data
  },

  verifyMagicLink: async (token) => {
    const response = await api.post('/auth/magic-link/verify', { token })
    return response.data
  },

  // Session management
  getCurrentUser: async () => {
    const response = await api.get('/auth/me')
    return response.data
  },

  logout: async () => {
    await api.post('/auth/logout')
  },
}
```

### src/services/forms.js

```javascript
import api from './api'

export const formService = {
  create: async (formType, formData) => {
    const response = await api.post('/forms', { formType, formData })
    return response.data
  },

  list: async (filters = {}) => {
    const params = new URLSearchParams(filters)
    const response = await api.get(`/forms?${params}`)
    return response.data
  },

  get: async (formId) => {
    const response = await api.get(`/forms/${formId}`)
    return response.data
  },

  update: async (formId, formData) => {
    const response = await api.put(`/forms/${formId}`, { formData })
    return response.data
  },

  delete: async (formId) => {
    await api.delete(`/forms/${formId}`)
  },

  getSummary: async (formId) => {
    const response = await api.get(`/forms/${formId}/summary`)
    return response.data
  },
}
```

### src/services/offline.js

```javascript
import localforage from 'localforage'

const formsStore = localforage.createInstance({
  name: 'upapp',
  storeName: 'forms',
})

const pendingStore = localforage.createInstance({
  name: 'upapp',
  storeName: 'pending_sync',
})

export const offlineService = {
  // Save form to IndexedDB
  saveForm: async (formId, formData) => {
    await formsStore.setItem(formId, formData)
  },

  // Get form from IndexedDB
  getForm: async (formId) => {
    return await formsStore.getItem(formId)
  },

  // Get all forms from IndexedDB
  getAllForms: async () => {
    const forms = []
    await formsStore.iterate((value, key) => {
      forms.push({ id: key, ...value })
    })
    return forms
  },

  // Add to pending sync queue
  addPendingSync: async (operation) => {
    const id = Date.now().toString()
    await pendingStore.setItem(id, operation)
  },

  // Get pending sync operations
  getPendingSync: async () => {
    const operations = []
    await pendingStore.iterate((value, key) => {
      operations.push({ id: key, ...value })
    })
    return operations
  },

  // Remove from pending sync
  removePendingSync: async (id) => {
    await pendingStore.removeItem(id)
  },

  // Clear all offline data
  clear: async () => {
    await formsStore.clear()
    await pendingStore.clear()
  },
}
```

## Authentication Components

### src/store/AuthContext.jsx

```javascript
import { createContext, useState, useEffect } from 'react'
import { authService } from '../services/auth'

export const AuthContext = createContext()

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(true)
  const [isAuthenticated, setIsAuthenticated] = useState(false)

  useEffect(() => {
    checkAuth()
  }, [])

  const checkAuth = async () => {
    try {
      const userData = await authService.getCurrentUser()
      setUser(userData)
      setIsAuthenticated(true)
    } catch (error) {
      setUser(null)
      setIsAuthenticated(false)
    } finally {
      setLoading(false)
    }
  }

  const login = async (userData) => {
    setUser(userData)
    setIsAuthenticated(true)
  }

  const logout = async () => {
    await authService.logout()
    setUser(null)
    setIsAuthenticated(false)
  }

  return (
    <AuthContext.Provider value={{ user, isAuthenticated, loading, login, logout, checkAuth }}>
      {children}
    </AuthContext.Provider>
  )
}
```

### src/hooks/useAuth.js

```javascript
import { useContext } from 'react'
import { AuthContext } from '../store/AuthContext'

export const useAuth = () => {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return context
}
```

### src/components/Auth/GoogleOAuth.jsx

```javascript
import { useEffect } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { Button } from 'primereact/button'
import { authService } from '../../services/auth'
import { useAuth } from '../../hooks/useAuth'

export const GoogleOAuth = () => {
  const navigate = useNavigate()
  const { login } = useAuth()

  const handleGoogleLogin = () => {
    const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID
    const redirectUri = `${window.location.origin}/auth/google/callback`
    const scope = 'openid email profile'
    const authUrl =
      `https://accounts.google.com/o/oauth2/v2/auth?` +
      `client_id=${clientId}&` +
      `redirect_uri=${encodeURIComponent(redirectUri)}&` +
      `response_type=code&` +
      `scope=${encodeURIComponent(scope)}`

    window.location.href = authUrl
  }

  return (
    <Button
      label="Login with Google"
      icon="pi pi-google"
      onClick={handleGoogleLogin}
      className="p-button-outlined"
    />
  )
}

// Callback component
export const GoogleOAuthCallback = () => {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const { login } = useAuth()

  useEffect(() => {
    const code = searchParams.get('code')
    if (code) {
      authService
        .googleAuth(code)
        .then((userData) => {
          login(userData)
          navigate('/dashboard')
        })
        .catch((error) => {
          console.error('Google auth failed:', error)
          navigate('/login?error=auth_failed')
        })
    }
  }, [searchParams])

  return <div>Authenticating with Google...</div>
}
```

### src/components/Auth/MagicLinkLogin.jsx

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
      setMessage({ severity: 'success', text: 'Check your email for login link!' })
      setEmail('')
    } catch (error) {
      setMessage({ severity: 'error', text: 'Failed to send magic link' })
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <div className="p-fluid">
        <div className="p-field">
          <label htmlFor="email">Email</label>
          <InputText
            id="email"
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            placeholder="your@email.com"
          />
        </div>

        {message && <Message severity={message.severity} text={message.text} />}

        <Button type="submit" label="Send Magic Link" loading={loading} className="p-mt-2" />
      </div>
    </form>
  )
}
```

### src/components/Auth/ProtectedRoute.jsx

```javascript
import { Navigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'

export const ProtectedRoute = ({ children, adminOnly = false }) => {
  const { isAuthenticated, user, loading } = useAuth()

  if (loading) {
    return <div>Loading...</div>
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  if (adminOnly && !user?.isAdmin) {
    return <Navigate to="/dashboard" replace />
  }

  return children
}
```

## Form Components

### src/hooks/useForm.js

```javascript
import { useState, useEffect, useCallback } from 'react'
import { formService } from '../services/forms'
import { offlineService } from '../services/offline'
import { useOffline } from './useOffline'
import { debounce } from '../utils/debounce'

export const useForm = (formId, formType) => {
  const [formData, setFormData] = useState({})
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const { isOnline } = useOffline()

  // Load form data
  useEffect(() => {
    if (formId) {
      loadForm()
    } else {
      setLoading(false)
    }
  }, [formId])

  const loadForm = async () => {
    try {
      // Try online first
      if (isOnline) {
        const data = await formService.get(formId)
        setFormData(data.formData)
        // Save to offline cache
        await offlineService.saveForm(formId, data)
      } else {
        // Load from offline cache
        const cachedData = await offlineService.getForm(formId)
        if (cachedData) {
          setFormData(cachedData.formData)
        }
      }
    } catch (error) {
      console.error('Failed to load form:', error)
    } finally {
      setLoading(false)
    }
  }

  // Auto-save with debounce (500ms)
  const debouncedSave = useCallback(
    debounce(async (data) => {
      await saveForm(data)
    }, 500),
    [formId, isOnline]
  )

  const saveForm = async (data) => {
    setSaving(true)
    try {
      if (isOnline) {
        if (formId) {
          await formService.update(formId, data)
        } else {
          const response = await formService.create(formType, data)
          window.history.replaceState(null, '', `/forms/${response.formId}`)
        }
      } else {
        // Save to offline queue
        await offlineService.saveForm(formId || 'draft', { formType, formData: data })
        await offlineService.addPendingSync({
          type: formId ? 'update' : 'create',
          formId,
          formType,
          formData: data,
        })
      }
    } catch (error) {
      console.error('Failed to save form:', error)
    } finally {
      setSaving(false)
    }
  }

  const updateField = (field, value) => {
    const newData = { ...formData, [field]: value }
    setFormData(newData)
    debouncedSave(newData)
  }

  return {
    formData,
    loading,
    saving,
    updateField,
    saveForm,
  }
}
```

### src/hooks/useOffline.js

```javascript
import { useState, useEffect } from 'react'

export const useOffline = () => {
  const [isOnline, setIsOnline] = useState(navigator.onLine)

  useEffect(() => {
    const handleOnline = () => setIsOnline(true)
    const handleOffline = () => setIsOnline(false)

    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    return () => {
      window.removeEventListener('online', handleOnline)
      window.removeEventListener('offline', handleOffline)
    }
  }, [])

  return { isOnline }
}
```

## Main Application

### src/App.jsx

```javascript
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from './store/AuthContext'
import { ProtectedRoute } from './components/Auth/ProtectedRoute'
import { LoginPage } from './pages/LoginPage'
import { DashboardPage } from './pages/DashboardPage'
import { FormPage } from './pages/FormPage'
import { AdminDashboard } from './pages/AdminDashboard'
import { GoogleOAuthCallback } from './components/Auth/GoogleOAuth'

// PrimeReact CSS
import 'primereact/resources/themes/lara-light-blue/theme.css'
import 'primereact/resources/primereact.min.css'
import 'primeicons/primeicons.css'

// FontAwesome CSS
import '@fortawesome/fontawesome-free/css/all.min.css'

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/auth/google/callback" element={<GoogleOAuthCallback />} />

          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <DashboardPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/forms/:formType"
            element={
              <ProtectedRoute>
                <FormPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/forms/:formType/:formId"
            element={
              <ProtectedRoute>
                <FormPage />
              </ProtectedRoute>
            }
          />

          <Route
            path="/admin"
            element={
              <ProtectedRoute adminOnly>
                <AdminDashboard />
              </ProtectedRoute>
            }
          />

          <Route path="/" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

export default App
```

### src/main.jsx

```javascript
import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App.jsx'
import './index.css'

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
)
```

## Key Features to Implement

1. **Auto-save**: Debounced save on field change (500ms)
2. **Offline support**: Save to IndexedDB when offline, sync when online
3. **Connection indicator**: Show online/offline status
4. **Form validation**: Client-side validation before save
5. **Error handling**: Global error boundary
6. **Loading states**: Show spinners during API calls
7. **Toast notifications**: Success/error messages
8. **Admin UI**: PrimeReact DataTable for user management
9. **Responsive design**: Mobile-friendly layout

## Testing Strategy

- Manual testing of all user flows
- Test offline mode: Disconnect network, edit forms, reconnect
- Test authentication: Google OAuth and Magic Link
- Test auto-save: Edit form, verify saves happen automatically
- Test admin panel: Login as admin, manage users

## Next Steps

1. Create all component files
2. Implement form components (DUP, TUP, DOS)
3. Test authentication flows
4. Implement admin panel
5. Add offline sync functionality
6. Build for production and test deployment
