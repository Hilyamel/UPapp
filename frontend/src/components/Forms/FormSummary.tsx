/**
 * Form summary component - displays all fields with section headers.
 */
import React, { useEffect, useState } from 'react';
import { getFormSummary, FormSummary as FormSummaryType } from '../../services/forms';

interface FormSummaryProps {
  formId: string;
}

export default function FormSummary({ formId }: FormSummaryProps) {
  const [summary, setSummary] = useState<FormSummaryType | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    async function loadSummary() {
      try {
        const summaryData = await getFormSummary(formId);
        setSummary(summaryData);
      } catch (err) {
        setError('Nie udało się załadować podsumowania');
        console.error(err);
      } finally {
        setLoading(false);
      }
    }

    if (formId) {
      loadSummary();
    }
  }, [formId]);

  if (loading) {
    return <div style={{ padding: '20px' }}>Ładowanie podsumowania...</div>;
  }

  if (error) {
    return (
      <div style={{
        color: '#d32f2f',
        background: '#ffebee',
        padding: '15px',
        borderRadius: '4px',
        margin: '20px'
      }}>
        {error}
      </div>
    );
  }

  if (!summary || !summary.sections || summary.sections.length === 0) {
    return (
      <div style={{ padding: '20px', textAlign: 'center', color: '#666' }}>
        Brak danych w tym formularzu.
      </div>
    );
  }

  return (
    <div style={{ padding: '20px' }}>
      <div style={{
        background: '#f5f5f5',
        padding: '15px',
        borderRadius: '4px',
        marginBottom: '20px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div>
            <strong>Typ formularza:</strong> {summary.form_type}
          </div>
          <div>
            <strong>Status:</strong>{' '}
            <span style={{
              padding: '4px 8px',
              borderRadius: '4px',
              background: summary.completion_status === 'completed' ? '#e8f5e9' : '#fff3e0',
              color: summary.completion_status === 'completed' ? '#2e7d32' : '#e65100',
            }}>
              {summary.completion_status === 'completed' ? 'Ukończony' : 'Wersja robocza'}
            </span>
          </div>
        </div>
        <div style={{ marginTop: '10px', fontSize: '14px', color: '#666' }}>
          Utworzono: {new Date(summary.created_at).toLocaleString('pl-PL')}
          {' | '}
          Ostatnia zmiana: {new Date(summary.updated_at).toLocaleString('pl-PL')}
        </div>
      </div>

      <h2>Podsumowanie formularza</h2>

      {summary.sections.map((section, sectionIndex) => (
        <div key={sectionIndex} style={{
          marginBottom: '20px',
          padding: '15px',
          background: 'white',
          border: '1px solid #e0e0e0',
          borderRadius: '8px'
        }}>
          <h3 style={{
            marginTop: 0,
            marginBottom: '12px',
            color: '#4a90e2',
            borderBottom: '2px solid #4a90e2',
            paddingBottom: '6px',
            fontSize: '18px'
          }}>
            {section.title}
          </h3>

          {section.layout === 'two-column' && section.columns ? (
            // Two-column layout for feelings and needs
            <div style={{ display: 'flex', gap: '20px' }}>
              {section.columns.map((column, colIndex) => (
                <div key={colIndex} style={{ flex: 1 }}>
                  <div style={{
                    fontWeight: '600',
                    marginBottom: '10px',
                    color: '#333',
                    fontSize: '16px'
                  }}>
                    {column.title}
                  </div>
                  <div style={{
                    whiteSpace: 'pre-wrap',
                    lineHeight: '1.6',
                    color: column.text === '-' ? '#999' : '#666',
                    fontStyle: column.text === '-' ? 'italic' : 'normal',
                    marginBottom: '10px'
                  }}>
                    {column.text}
                  </div>
                  {column.selected && column.selected !== '-' && (
                    <div style={{
                      whiteSpace: 'pre-wrap',
                      lineHeight: '1.6',
                      color: '#666',
                      fontStyle: 'italic'
                    }}>
                      {column.selected}
                    </div>
                  )}
                </div>
              ))}
            </div>
          ) : (
            // Regular single-column layout
            section.fields && section.fields.map((field, fieldIndex) => (
              <div key={fieldIndex} style={{
                marginBottom: '10px',
                paddingBottom: '10px',
                borderBottom: fieldIndex < section.fields!.length - 1 ? '1px solid #f0f0f0' : 'none'
              }}>
                <div style={{ fontWeight: '600', marginBottom: '4px', color: '#333', fontSize: '14px' }}>
                  {field.label}
                </div>
                <div style={{
                  whiteSpace: 'pre-wrap',
                  lineHeight: '1.5',
                  color: field.value === '-' ? '#999' : '#666',
                  fontStyle: field.value === '-' ? 'italic' : 'normal',
                  fontSize: '14px'
                }}>
                  {field.value}
                </div>
              </div>
            ))
          )}
        </div>
      ))}

      {/* AI Feedback Display */}
      {summary.ai_feedback && (
        <div style={{
          marginTop: '20px',
          padding: '15px',
          background: '#e8f5e9',
          borderRadius: '8px',
          borderLeft: '4px solid #66bb6a'
        }}>
          <h3 style={{ marginTop: 0, marginBottom: '10px', color: '#2e7d32', fontSize: '18px' }}>
            💭 Feedback empAItyczny
          </h3>
          <p style={{ lineHeight: '1.6', color: '#1b5e20', margin: 0, fontSize: '14px' }}>
            {summary.ai_feedback}
          </p>
        </div>
      )}
    </div>
  );
}
