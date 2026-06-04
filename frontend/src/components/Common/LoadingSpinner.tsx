
interface LoadingSpinnerProps {
  message?: string;
}

export default function LoadingSpinner({ message = 'Ładowanie...' }: LoadingSpinnerProps) {
  return (
    <div style={{
      display: 'flex',
      flexDirection: 'column',
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: '200px',
      gap: '15px'
    }}>
      <div style={{
        width: '50px',
        height: '50px',
        border: '5px solid #f3f3f3',
        borderTop: '5px solid #4a90e2',
        borderRadius: '50%',
        animation: 'spin 1s linear infinite'
      }} />
      <p style={{ color: '#666', fontSize: '16px' }}>{message}</p>
      <style>{`
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}
