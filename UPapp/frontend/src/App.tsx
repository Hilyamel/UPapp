import { useState, useEffect } from 'react';
import { Card } from 'primereact/card';
import { Message } from 'primereact/message';
import { healthCheck } from './services/api';

interface HealthData {
  status: string;
  environment: string;
  services: {
    api: string;
    dynamodb: string;
  };
}

function App() {
  const [healthStatus, setHealthStatus] = useState<string>('checking');
  const [healthData, setHealthData] = useState<HealthData | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const checkHealth = async () => {
      try {
        const response = await healthCheck();
        if (response.success) {
          setHealthData(response.data);
          setHealthStatus(response.data.status);
        }
      } catch (err) {
        setError('Failed to connect to backend');
        setHealthStatus('error');
      }
    };

    checkHealth();
  }, []);

  return (
    <div style={{ padding: '2rem' }}>
      <Card title="UPapp - NVC Forms Application">
        <div style={{ marginBottom: '1rem' }}>
          <h2>Welcome to UPapp</h2>
          <p>Nonviolent Communication (NVC) Forms Application</p>
        </div>

        {healthStatus === 'checking' && (
          <Message severity="info" text="Checking backend connection..." />
        )}

        {healthStatus === 'healthy' && healthData && (
          <Message
            severity="success"
            text={`Backend connected - Environment: ${healthData.environment}`}
          />
        )}

        {healthStatus === 'degraded' && (
          <Message
            severity="warn"
            text="Backend running but some services degraded. Check console for details."
          />
        )}

        {healthStatus === 'error' && (
          <Message severity="error" text={error || 'Failed to connect to backend'} />
        )}

        <div style={{ marginTop: '2rem' }}>
          <h3>Project Status</h3>
          <ul>
            <li>✅ Frontend: React + Vite + TypeScript configured</li>
            <li>✅ UI Library: PrimeReact + Font Awesome loaded</li>
            <li>{healthData?.services.api === 'ok' ? '✅' : '⏳'} Backend: PHP + Slim</li>
            <li>
              {healthData?.services.dynamodb === 'ok' ? '✅' : '⏳'} Database: DynamoDB
              connection
            </li>
          </ul>
        </div>
      </Card>
    </div>
  );
}

export default App;
