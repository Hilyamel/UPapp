/**
 * DOS Form (Dziennik Osądów) component.
 */
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getFeelings, getNeeds, FeelingsData, NeedsData } from '../../services/reference';
import useForm from '../../hooks/useForm';
import CollapsibleList from '../Common/CollapsibleList';

interface DOSFormProps {
  formId?: string | null;
}

export default function DOSForm({ formId = null }: DOSFormProps) {
  const navigate = useNavigate();
  const { formData, aiFeedback, updateField, submitForm, isSaving, lastSaved, error } = useForm(formId, 'DOS');

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

  // Merge fulfilled and unfulfilled feelings
  const allFeelings = {
    ...feelings.fulfilled,
    ...feelings.unfulfilled,
  };

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
      <h1>Dziennik Osądów (DOS)</h1>

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

      <div style={{
        background: '#fff3e0',
        border: '1px solid #ff9800',
        borderRadius: '4px',
        padding: '15px',
        marginBottom: '20px'
      }}>
        <p style={{ margin: 0, fontSize: '14px' }}>
          <strong>Wskazówka:</strong> Dziennik osądów pomaga przekształcić myśli osądzające
          w uczucia i potrzeby. Napisz swój osąd, a następnie zastanów się, jakie uczucia
          i potrzeby za nim stoją.
        </p>
      </div>

      <form onSubmit={handleSubmit}>
        {/* Question 1: Who */}
        <div style={{ marginBottom: '30px' }}>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
            1. Kogo dotyczy ten osąd?
          </label>
          <input
            type="text"
            value={formData.person || ''}
            onChange={(e) => updateField('person', e.target.value)}
            placeholder="np. Jan K., kolega z pracy..."
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
          <small style={{ color: '#666', fontSize: '13px' }}>
            Wskazówka: Używaj inicjałów lub pseudonimów dla prywatności
          </small>
        </div>

        {/* Question 2: What is the judgment */}
        <div style={{ marginBottom: '30px' }}>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
            2. Jak brzmi ten osąd? Operuj konkretami.
          </label>
          <textarea
            value={formData.judgment || ''}
            onChange={(e) => updateField('judgment', e.target.value)}
            placeholder="Napisz swój osąd dokładnie tak, jak pojawia się w Twojej głowie..."
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

        {/* Question 3: Feelings (optional) */}
        <div style={{ marginBottom: '30px' }}>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
            3. Jakie moje uczucia wiążą się z tym osądem?
          </label>

          <input
            type="text"
            value={formData.feelings_freetext || ''}
            onChange={(e) => updateField('feelings_freetext', e.target.value)}
            placeholder="Wpisz własne uczucia..."
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
            title="Uczucia - wybierz z listy"
            groups={allFeelings}
            selected={formData.feelings_selected || []}
            onChange={(selected) => updateField('feelings_selected', selected)}
          />
        </div>

        {/* Question 4: Needs (optional) */}
        <div style={{ marginBottom: '30px' }}>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
            4. O jakich potrzebach informują mnie te różne osądy?
          </label>

          <input
            type="text"
            value={formData.needs_freetext || ''}
            onChange={(e) => updateField('needs_freetext', e.target.value)}
            placeholder="Wpisz własne potrzeby..."
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

        {/* Reflection section */}
        <div style={{
          background: '#e8f5e9',
          border: '2px solid #4caf50',
          borderRadius: '8px',
          padding: '20px',
          marginBottom: '30px'
        }}>
          <h3 style={{ color: '#2e7d32', marginTop: 0 }}>Refleksja</h3>
          <p style={{ fontSize: '14px', lineHeight: '1.6', margin: 0 }}>
            Jak się teraz czujesz po przełożeniu osądu na uczucia i potrzeby?
            <br />
            Czy widzisz sytuację inaczej?
            <br />
            Czy pojawiło się jakieś zrozumienie lub współczucie - dla siebie lub dla tej osoby?
          </p>
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
