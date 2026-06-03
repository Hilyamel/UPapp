
interface ConnectionScaleProps {
  label: string;
  value: number;
  onChange: (value: number) => void;
}

export default function ConnectionScale({ label, value, onChange }: ConnectionScaleProps) {
  const scaleValues = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

  return (
    <div style={{
      padding: '20px',
      background: '#f5f5f5',
      borderRadius: '8px',
      border: '1px solid #e0e0e0'
    }}>
      <label style={{
        display: 'block',
        marginBottom: '15px',
        fontWeight: 'bold',
        fontSize: '16px'
      }}>
        {label}
      </label>

      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        gap: '10px'
      }}>
        {scaleValues.map((num) => (
          <button
            key={num}
            type="button"
            onClick={() => onChange(num)}
            style={{
              width: '50px',
              height: '50px',
              borderRadius: '50%',
              border: value === num ? '3px solid #4a90e2' : '2px solid #ccc',
              background: value === num ? '#4a90e2' : 'white',
              color: value === num ? 'white' : '#333',
              fontSize: '18px',
              fontWeight: 'bold',
              cursor: 'pointer',
              transition: 'all 0.2s'
            }}
          >
            {num}
          </button>
        ))}
      </div>

      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        marginTop: '10px',
        fontSize: '14px',
        color: '#666'
      }}>
        <span>Brak kontaktu</span>
        <span>Pełny kontakt</span>
      </div>
    </div>
  );
}
