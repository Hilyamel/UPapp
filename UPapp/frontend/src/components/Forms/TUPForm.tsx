/**
 * TUP Form (Tabela Uczuć i Potrzeb) component.
 */
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import useForm from '../../hooks/useForm';
import ConnectionScale from '../Common/ConnectionScale';
import TTable from './TTable';

interface TUPFormProps {
  formId?: string | null;
}

export default function TUPForm({ formId = null }: TUPFormProps) {
  const navigate = useNavigate();
  const { formData, updateField, submitForm, isSaving, lastSaved, error } = useForm(formId, 'TUP');
  const [isSituationExpanded, setIsSituationExpanded] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    await submitForm();
    navigate('/dashboard');
  };

  return (
    <div style={{ maxWidth: '1000px', margin: '0 auto', padding: '20px' }}>
      <h1>Ćwiczenie: Tabela Uczuć i Potrzeb (TUP)</h1>

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
        {/* 1. Connection scale BEFORE */}
        <div style={{ marginBottom: '30px' }}>
          <ConnectionScale
            label="Gdzie jesteś TERAZ na skali kontaktu ze sobą i/lub z osobą/osobami, których sprawa dotyczy?"
            value={formData.connection_scale_before || 0}
            onChange={(value) => updateField('connection_scale_before', value)}
          />
        </div>

        {/* 2. Situation description (collapsible) */}
        <div style={{ marginBottom: '30px' }}>
          <button
            type="button"
            onClick={() => setIsSituationExpanded(!isSituationExpanded)}
            style={{
              width: '100%',
              padding: '12px',
              background: '#f5f5f5',
              border: '1px solid #ccc',
              borderRadius: '4px',
              cursor: 'pointer',
              textAlign: 'left',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              fontSize: '16px',
              fontWeight: 'bold',
            }}
          >
            <span>Opis sytuacji</span>
            <span>{isSituationExpanded ? '▼' : '▶'}</span>
          </button>

          {isSituationExpanded && (
            <div style={{
              marginTop: '10px',
              padding: '15px',
              background: '#fafafa',
              border: '1px solid #e0e0e0',
              borderRadius: '4px'
            }}>
              <p style={{ fontSize: '14px', color: '#666', marginBottom: '10px' }}>
                Opisz sytuację, która wywołała w Tobie reakcję. Staraj się być konkretny/a.
              </p>
              <textarea
                value={formData.situation_description || ''}
                onChange={(e) => updateField('situation_description', e.target.value)}
                placeholder="Opisz co się wydarzyło..."
                rows={4}
                style={{
                  width: '100%',
                  padding: '10px',
                  fontSize: '14px',
                  borderRadius: '4px',
                  border: '1px solid #ccc'
                }}
              />
            </div>
          )}
        </div>

        {/* 3. Observation, Quote, Judgments */}
        <div style={{ marginBottom: '30px' }}>
          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Obserwacja
            </label>
            <input
              type="text"
              value={formData.observation || ''}
              onChange={(e) => updateField('observation', e.target.value)}
              placeholder="Co konkretnie zaobserwowałeś/aś?"
              style={{
                width: '100%',
                padding: '10px',
                fontSize: '14px',
                borderRadius: '4px',
                border: '1px solid #ccc'
              }}
            />
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Cytat
            </label>
            <input
              type="text"
              value={formData.quote || ''}
              onChange={(e) => updateField('quote', e.target.value)}
              placeholder="Co dokładnie zostało powiedziane?"
              style={{
                width: '100%',
                padding: '10px',
                fontSize: '14px',
                borderRadius: '4px',
                border: '1px solid #ccc'
              }}
            />
          </div>

          <div style={{ marginBottom: '15px' }}>
            <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
              Osądy
            </label>
            <textarea
              value={formData.judgments || ''}
              onChange={(e) => updateField('judgments', e.target.value)}
              placeholder="Jakie osądy pojawiają się w Twojej głowie?"
              rows={3}
              style={{
                width: '100%',
                padding: '10px',
                fontSize: '14px',
                borderRadius: '4px',
                border: '1px solid #ccc'
              }}
            />
          </div>
        </div>

        {/* 4. First T-table: Your feelings and needs */}
        <div style={{ marginBottom: '30px' }}>
          <TTable
            leftTitle="Twoje Uczucia"
            rightTitle="Twoje Potrzeby"
            leftFreetext={formData.your_feelings_freetext}
            onLeftFreetextChange={(value) => updateField('your_feelings_freetext', value)}
            leftSelected={formData.your_feelings_selected}
            onLeftSelectedChange={(value) => updateField('your_feelings_selected', value)}
            rightFreetext={formData.your_needs_freetext}
            onRightFreetextChange={(value) => updateField('your_needs_freetext', value)}
            rightSelected={formData.your_needs_selected}
            onRightSelectedChange={(value) => updateField('your_needs_selected', value)}
          />
        </div>

        {/* 5. Pause section */}
        <div style={{
          background: '#e8f5e9',
          border: '2px solid #4caf50',
          borderRadius: '8px',
          padding: '20px',
          marginBottom: '30px',
          textAlign: 'center'
        }}>
          <h3 style={{ color: '#2e7d32', marginTop: 0 }}>Pauza</h3>
          <p style={{ fontSize: '16px', lineHeight: '1.6' }}>
            Zatrzymaj się na chwilę. Odetchnij głęboko kilka razy.
            <br />
            Pozwól sobie poczuć swoje uczucia i potrzeby.
            <br />
            Nie musisz nic zmieniać - po prostu bądź z tym, co jest.
          </p>
        </div>

        {/* 6. Second T-table: Their feelings and needs */}
        <div style={{ marginBottom: '30px' }}>
          <TTable
            leftTitle="Jego/Jej Uczucia"
            rightTitle="Jego/Jej Potrzeby"
            leftFreetext={formData.their_feelings_freetext}
            onLeftFreetextChange={(value) => updateField('their_feelings_freetext', value)}
            leftSelected={formData.their_feelings_selected}
            onLeftSelectedChange={(value) => updateField('their_feelings_selected', value)}
            rightFreetext={formData.their_needs_freetext}
            onRightFreetextChange={(value) => updateField('their_needs_freetext', value)}
            rightSelected={formData.their_needs_selected}
            onRightSelectedChange={(value) => updateField('their_needs_selected', value)}
          />
        </div>

        {/* 7. "Give yourself a moment" section */}
        <div style={{
          background: '#fff3e0',
          border: '2px solid #ff9800',
          borderRadius: '8px',
          padding: '20px',
          marginBottom: '30px'
        }}>
          <p style={{ fontSize: '16px', lineHeight: '1.6', margin: 0 }}>
            <strong>Daj sobie chwilę...</strong>
            <br /><br />
            Jak się teraz czujesz? Co się zmieniło, odkąd spojrzałeś/aś na sytuację
            przez pryzmat uczuć i potrzeb - swoich i drugiej osoby?
            <br /><br />
            Czy widzisz możliwość połączenia? Czy rozumiesz lepiej, co było ważne
            dla was obojga?
          </p>
        </div>

        {/* 8. Connection scale AFTER */}
        <div style={{ marginBottom: '30px' }}>
          <ConnectionScale
            label="Gdzie jesteś TERAZ na skali kontaktu ze sobą i/lub z osobą/osobami?"
            value={formData.connection_scale_after || 0}
            onChange={(value) => updateField('connection_scale_after', value)}
          />

          {formData.connection_scale_before && formData.connection_scale_after && (
            <div style={{
              marginTop: '15px',
              padding: '12px',
              background: '#e3f2fd',
              borderRadius: '4px',
              textAlign: 'center'
            }}>
              <strong>
                Zmiana: {formData.connection_scale_after - formData.connection_scale_before > 0 ? '+' : ''}
                {formData.connection_scale_after - formData.connection_scale_before}
              </strong>
            </div>
          )}
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
