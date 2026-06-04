import React, { Component, ReactNode } from 'react';

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export default class ErrorBoundary extends Component<Props, State> {
  constructor(props: Props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{
          maxWidth: '600px',
          margin: '100px auto',
          padding: '30px',
          textAlign: 'center',
          border: '2px solid #d32f2f',
          borderRadius: '8px',
          background: '#ffebee'
        }}>
          <h1 style={{ color: '#d32f2f' }}>Coś poszło nie tak</h1>
          <p style={{ marginTop: '15px', color: '#666' }}>
            Przepraszamy, wystąpił nieoczekiwany błąd. Spróbuj odświeżyć stronę.
          </p>
          <button
            onClick={() => window.location.reload()}
            style={{
              marginTop: '20px',
              padding: '12px 24px',
              background: '#4a90e2',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer',
              fontSize: '16px'
            }}
          >
            Odśwież stronę
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}
