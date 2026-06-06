/**
 * OK10 Form (Obrachunek wg kroku 10 AA) component.
 */
import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { getFeelings, getNeeds, FeelingsData, NeedsData } from '../../services/reference';
import useForm from '../../hooks/useForm';
import CollapsibleList from '../Common/CollapsibleList';

interface OK10FormProps {
  formId?: string | null;
}

export default function OK10Form({ formId = null }: OK10FormProps) {
  const navigate = useNavigate();
  const { formData, aiFeedback, updateField, submitForm, requestAIFeedback, isSaving, isLoadingAI, lastSaved, error } = useForm(formId, 'OK10');

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

  const handleAIFeedback = async () => {
    await requestAIFeedback();
  };

  if (loading) {
    return <div style={{ padding: '20px' }}>Ładowanie...</div>;
  }

  // Combine all feelings into one groups object
  const feelingsGroups = { ...feelings.fulfilled, ...feelings.unfulfilled };

  // needs is already in the right format
  const needsGroups = needs;

  return (
    <div style={{ maxWidth: '800px', margin: '0 auto', padding: '20px' }}>
      <h1 style={{ color: '#ab47bc' }}>Obrachunek wg kroku 10 AA (OK10)</h1>

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
        {/* 1. Kto */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            1. Kto
          </label>
          <input
            type="text"
            value={formData.who || ''}
            onChange={(e) => updateField('who', e.target.value)}
            placeholder="Kto..."
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 2. Co */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            2. Co
          </label>
          <textarea
            value={formData.what || ''}
            onChange={(e) => updateField('what', e.target.value)}
            placeholder="Co się stało..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 3. Myśli i uczucia */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            3. Myśli i uczucia
          </label>
          <textarea
            value={formData.thoughts_feelings || ''}
            onChange={(e) => updateField('thoughts_feelings', e.target.value)}
            placeholder="Jakie miałem myśli i uczucia..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
          <CollapsibleList
            title="Inne uczucia"
            groups={feelingsGroups}
            selected={formData.feelings_nvc_selected || []}
            onChange={(ids) => updateField('feelings_nvc_selected', ids)}
          />
        </div>

        {/* 4. Naruszone potrzeby */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            4. Naruszone potrzeby
          </label>
          <div style={{
            background: '#fff3e0',
            border: '2px solid #f57c00',
            borderRadius: '4px',
            padding: '15px',
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
            gap: '10px'
          }}>
            {[
              { id: 'security', label: 'Poczucie bezpieczeństwa' },
              { id: 'sexual', label: 'Potrzeby seksualne' },
              { id: 'social', label: 'Osobiste relacje / potrzeby społeczne' },
              { id: 'ambition', label: 'Ambicja / duma / prestiż' }
            ].map(need => (
              <label key={need.id} style={{ display: 'flex', alignItems: 'center', cursor: 'pointer' }}>
                <input
                  type="checkbox"
                  checked={(formData.violated_needs || []).includes(need.id)}
                  onChange={(e) => {
                    const current = formData.violated_needs || [];
                    const updated = e.target.checked
                      ? [...current, need.id]
                      : current.filter((id: string) => id !== need.id);
                    updateField('violated_needs', updated);
                  }}
                  style={{ marginRight: '8px' }}
                />
                <span>{need.label}</span>
              </label>
            ))}
          </div>
        </div>

        {/* 5. Inne potrzeby */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            5. Inne potrzeby
          </label>
          <textarea
            value={formData.other_needs || ''}
            onChange={(e) => updateField('other_needs', e.target.value)}
            placeholder="Inne potrzeby..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
          <CollapsibleList
            title="Inne potrzeby"
            groups={needsGroups}
            selected={formData.needs_nvc_selected || []}
            onChange={(ids) => updateField('needs_nvc_selected', ids)}
          />
        </div>

        {/* 6. Wady */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            6. Wady
          </label>
          <textarea
            value={formData.flaws || ''}
            onChange={(e) => updateField('flaws', e.target.value)}
            placeholder="Wady..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 7. Pozorne korzyści */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            7. Pozorne korzyści (błędne przekonania)
          </label>
          <textarea
            value={formData.false_benefits || ''}
            onChange={(e) => updateField('false_benefits', e.target.value)}
            placeholder="Pozorne korzyści..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 8. Ewidentne straty */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            8. Ewidentne straty
          </label>
          <textarea
            value={formData.evident_losses || ''}
            onChange={(e) => updateField('evident_losses', e.target.value)}
            placeholder="Ewidentne straty..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 9. Co powinno być (zalety) */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            9. Co powinno być (zalety)
          </label>
          <textarea
            value={formData.what_should_be || ''}
            onChange={(e) => updateField('what_should_be', e.target.value)}
            placeholder="Co powinno być..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 10. Krzywdy wobec mnie */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            10. Krzywdy - wobec mnie (kr 8.5)
          </label>
          <textarea
            value={formData.harms_against_me || ''}
            onChange={(e) => updateField('harms_against_me', e.target.value)}
            placeholder="Krzywdy wobec mnie..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 11. Moje wobec otoczenia */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            11. Moje wobec otoczenia
          </label>
          <textarea
            value={formData.mine_toward_others || ''}
            onChange={(e) => updateField('mine_toward_others', e.target.value)}
            placeholder="Moje wobec otoczenia..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 12. Decyzja o wybaczeniu */}
        <div style={{ marginBottom: '20px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            12. Decyzja o wybaczeniu / próbie pojednania
          </label>
          <textarea
            value={formData.forgiveness_decision || ''}
            onChange={(e) => updateField('forgiveness_decision', e.target.value)}
            placeholder="Decyzja o wybaczeniu..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* 13. Zmiany postawy */}
        <div style={{ marginBottom: '30px' }}>
          <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>
            13. Zmiany postawy pod wpływem zalet, w kierunku miłości
          </label>
          <textarea
            value={formData.attitude_changes || ''}
            onChange={(e) => updateField('attitude_changes', e.target.value)}
            placeholder="Zmiany postawy..."
            rows={2}
            style={{
              width: '100%',
              padding: '10px',
              fontSize: '16px',
              borderRadius: '4px',
              border: '1px solid #ccc'
            }}
          />
        </div>

        {/* Closing text */}
        <div style={{
          background: '#455a64',
          color: 'white',
          padding: '12px',
          borderRadius: '4px',
          fontSize: '15px',
          textAlign: 'center',
          marginBottom: '20px',
          lineHeight: '1.5'
        }}>
          <p>
            Teraz przejrzyj podsumowanie swojego wpisu. Możesz coś dopisać, jeśli chcesz lub kliknąć po feedback empAI.
            Odczyta on wpisy pierwszych rubryk: do potrzeb włącznie. Nie odniesie się do innych rubryk, bo to nie przestrzeń dla maszyny.
            Ale może pomóc Ci uzupełnić uczucia lub potrzeby, które mogł_ś przeoczyć.
          </p>
          <p>
            Rozważ podzielenie się z kimś tym podsumowaniem, osobiście lub przez telefon. Doświadczenie wielu z nas wskazuje,
            że gdy otwieramy się na inną, uważnie słuchającą osobę, otwieramy się pełniej na Siłę Większą.
          </p>
          <p>
            Możesz stosować Obrachunek K10 jako pamiętnik, wyłącznie dla siebie. Życzę Ci, by był narzędziem wzmacniania
            więzi z Twoją SW i z innymi ważnymi dla Ciebie osobami.
          </p>
        </div>

        {/* AI Feedback section */}
        {aiFeedback && (
          <div style={{
            background: '#e8f5e9',
            border: '1px solid #66bb6a',
            borderRadius: '4px',
            padding: '15px',
            marginBottom: '20px'
          }}>
            <h3 style={{ color: '#2e7d32', marginTop: 0 }}>empAI feedback:</h3>
            <div style={{ whiteSpace: 'pre-wrap', lineHeight: '1.6' }}>{aiFeedback}</div>
          </div>
        )}

        {/* Action buttons */}
        <div style={{
          display: 'flex',
          gap: '15px',
          justifyContent: 'center',
          flexWrap: 'wrap'
        }}>
          <button
            type="submit"
            disabled={isSaving}
            style={{
              background: '#2196f3',
              color: 'white',
              border: 'none',
              padding: '12px 24px',
              fontSize: '13px',
              borderRadius: '4px',
              cursor: isSaving ? 'not-allowed' : 'pointer',
              opacity: isSaving ? 0.6 : 1
            }}
          >
            {isSaving ? 'Zapisywanie...' : 'Zapisz'}
          </button>

          <button
            type="button"
            onClick={handleAIFeedback}
            disabled={isLoadingAI || isSaving}
            style={{
              background: '#66bb6a',
              color: 'white',
              border: 'none',
              padding: '12px 24px',
              fontSize: '14px',
              borderRadius: '4px',
              cursor: (isLoadingAI || isSaving) ? 'not-allowed' : 'pointer',
              opacity: (isLoadingAI || isSaving) ? 0.6 : 1
            }}
          >
            {isLoadingAI ? 'Generowanie...' : 'empAI'}
          </button>
        </div>
      </form>
    </div>
  );
}
