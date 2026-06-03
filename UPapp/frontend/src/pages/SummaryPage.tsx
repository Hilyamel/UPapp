import { useParams, useNavigate } from 'react-router-dom';
import FormSummary from '../components/Forms/FormSummary';

export default function SummaryPage() {
  const { formId } = useParams<{ formId: string }>();
  const navigate = useNavigate();

  if (!formId) {
    return (
      <div style={{ maxWidth: '1000px', margin: '100px auto', padding: '20px', textAlign: 'center' }}>
        <p>Brak ID formularza</p>
        <button
          onClick={() => navigate('/dashboard')}
          style={{
            padding: '10px 20px',
            background: '#4a90e2',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            cursor: 'pointer',
          }}
        >
          Wróć do panelu
        </button>
      </div>
    );
  }

  return (
    <div style={{ maxWidth: '1000px', margin: '0 auto', padding: '20px' }}>
      <button
        onClick={() => navigate('/dashboard')}
        style={{
          padding: '8px 16px',
          background: '#f5f5f5',
          border: '1px solid #ccc',
          borderRadius: '4px',
          cursor: 'pointer',
          marginBottom: '20px',
        }}
      >
        ← Wróć do panelu
      </button>

      <FormSummary formId={formId} />
    </div>
  );
}
