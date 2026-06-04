/**
 * DUP Form (Dzienniczek Uczuć i Potrzeb) component.
 */
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getFeelings, getNeeds, FeelingsData, NeedsData } from '../../services/reference';
import useForm from '../../hooks/useForm';
import CollapsibleList from '../Common/CollapsibleList';

interface DUPFormProps {
  formId?: string | null;
}

export default function DUPForm({ formId = null }: DUPFormProps) {
  const navigate = useNavigate();
  const { formData, aiFeedback, updateField, submitForm, isSaving, lastSaved, error } = useForm(formId, 'DUP');

  const [feelings, setFeelings] = useState<FeelingsData>({ fulfilled: {}, unfulfilled: {} });
  const [needs, setNeeds] = useState<NeedsData>({});
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadData() {
      try {
        const [feelingsData, needsData] = await Promise.all([
          getFeelings(),
          getNeeds()
        ]);
        setFeelings(feelingsData);
        setNeeds(needsData);
      } catch (err) {
        console.error('Failed to load reference data:', err);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await submitForm();
    navigate('/dashboard');
  };

  if (loading) {
    return <div style={{ padding: '20px' }}>Ładowanie...</div>;
  }

  return (
    <div style={{ maxWidth: '800px', margin: '0 auto', padding: '20px' }}>
      <h1>Dzienniczek Uczuć i Potrzeb (DUP)</h1>

      {error && (
        <div style={{
          color: '#d32f2f',
          background: '#ffebee',
          padding: '10px',
          borderRadius: '4px',
          marginBottom: '15px'
        }}>
          {error}
        </div>
      )}

      {lastSaved && (
        <div style={{
          color: '#2e7d32',
          fontSize: '14px',
          marginBottom: '15px'
        }}>
          Ostatnio zapisano: {lastSaved.toLocaleTimeString('pl-PL')}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        {/* Section 1: What someone said */}
        <div style={{ marginBottom: '30px' }}>
          <h3>1. Co ktoś powiedział lub zrobił</h3>
          <textarea
            value={formData.what_someone_said || ''}
            onChange={(e) => updateField('what_someone_said', e.target.value)}
            placeholder="Opisz konkretną sytuację..."
            rows={4}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* Section 2: Fulfilled feelings */}
        <div style={{ marginBottom: '30px' }}>
          <h3>2. Uczucia sygnalizujące zaspokojenie potrzeb</h3>

          <input
            type="text"
            value={formData.fulfilled_feelings_freetext || ''}
            onChange={(e) => updateField('fulfilled_feelings_freetext', e.target.value)}
            placeholder="np. radosny, zadowolony..."
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              marginBottom: '15px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />

          <CollapsibleList
            title="Uczucia zaspokojenia - wybierz z listy"
            groups={feelings.fulfilled}
            selected={formData.fulfilled_feelings_selected || []}
            onChange={(selected) => updateField('fulfilled_feelings_selected', selected)}
          />
        </div>

        {/* Section 3: Unfulfilled feelings */}
        <div style={{ marginBottom: '30px' }}>
          <h3>3. Uczucia sygnalizujące niezaspokojenie potrzeb</h3>

          <input
            type="text"
            value={formData.unfulfilled_feelings_freetext || ''}
            onChange={(e) => updateField('unfulfilled_feelings_freetext', e.target.value)}
            placeholder="np. smutny, zły..."
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              marginBottom: '15px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />

          <CollapsibleList
            title="Uczucia niezaspokojenia - wybierz z listy"
            groups={feelings.unfulfilled}
            selected={formData.unfulfilled_feelings_selected || []}
            onChange={(selected) => updateField('unfulfilled_feelings_selected', selected)}
          />
        </div>

        {/* Section 4: Needs */}
        <div style={{ marginBottom: '30px' }}>
          <h3>4. Potrzeby</h3>

          <input
            type="text"
            value={formData.needs_freetext || ''}
            onChange={(e) => updateField('needs_freetext', e.target.value)}
            placeholder="np. autonomia, zrozumienie..."
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              marginBottom: '15px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />

          <CollapsibleList
            title="Potrzeby - wybierz z listy"
            groups={needs}
            selected={formData.needs_selected || []}
            onChange={(selected) => updateField('needs_selected', selected)}
          />
        </div>

        {/* Action buttons */}
        <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '30px' }}>
          <button
            type="button"
            onClick={() => navigate('/dashboard')}
            style={{
              padding: '12px 24px',
              background: '#f5f5f5',
              border: '1px solid #ccc',
              borderRadius: '4px',
              cursor: 'pointer',
              fontSize: '16px',
            }}
          >
            Anuluj
          </button>
          <button
            type="submit"
            disabled={isSaving}
            style={{
              padding: '12px 24px',
              background: '#4a90e2',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: isSaving ? 'not-allowed' : 'pointer',
              fontSize: '16px',
              opacity: isSaving ? 0.6 : 1,
            }}
          >
            {isSaving ? 'Zapisywanie...' : 'Zakończ i zapisz'}
          </button>
        </div>
      </form>

    </div>
  );
}
