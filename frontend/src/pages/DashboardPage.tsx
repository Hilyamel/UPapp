import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { listForms, deleteForm, generateAIFeedback, updateForm } from '../services/forms';
import apiClient from '../services/api';

const dashboardStyles = `
  @media (max-width: 768px) {
    .quick-actions-grid {
      grid-template-columns: 1fr !important;
    }
  }
`;

export default function DashboardPage() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [forms, setForms] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [showDeleteAccountDialog, setShowDeleteAccountDialog] = useState(false);
  const [aiFeedback, setAiFeedback] = useState<string | null>(null);
  const [aiLoading, setAiLoading] = useState(false);
  const [aiError, setAiError] = useState<string | null>(null);
  const [editingTitleId, setEditingTitleId] = useState<string | null>(null);
  const [editingTitleValue, setEditingTitleValue] = useState<string>('');

  useEffect(() => {
    loadForms();
  }, []);

  const loadForms = async () => {
    setLoading(true);
    try {
      const data = await listForms();
      setForms(data);
    } catch (err) {
      console.error('Failed to load forms:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/login');
    } catch (err) {
      console.error('Logout failed:', err);
    }
  };

  const handleDeleteAccount = async () => {
    if (!window.confirm('Czy na pewno chcesz usunąć swoje konto? Ta operacja jest nieodwracalna i usunie wszystkie Twoje dane.')) {
      return;
    }

    try {
      await apiClient.delete('/api/auth/account');
      await logout();
      navigate('/register');
    } catch (err: any) {
      alert(err.response?.data?.error?.message || 'Nie udało się usunąć konta');
    }
  };

  const handleAIFeedback = async (e: React.MouseEvent, formId: string, overwrite: boolean = false) => {
    e.stopPropagation();
    setAiLoading(true);
    setAiError(null);
    setAiFeedback(null);

    try {
      const response = await generateAIFeedback(formId, overwrite);

      // Check if feedback already exists and user hasn't chosen to overwrite
      if (response.status === 'feedback_exists') {
        setAiLoading(false);

        const shouldOverwrite = window.confirm(
          'Ten formularz ma już feedback empAI.\n\nCzy chcesz wygenerować nowy feedback?\n\n✓ TAK - wygeneruj nowy (bez uwzględnienia poprzedniego)\n✗ NIE - pozostaw obecny'
        );

        if (shouldOverwrite) {
          // Retry with overwrite=true (generates fresh feedback without old one)
          return handleAIFeedback(e, formId, true);
        } else {
          // User chose not to overwrite - do nothing, just close
          return;
        }
      }

      // Success - show new feedback
      setAiFeedback(response.feedback);
      // Refresh forms list to show updated form with ai_feedback
      loadForms();
    } catch (err: any) {
      if (err.response?.status === 429) {
        setAiError('Dzienny limit zapytań do AI wyczerpany');
      } else {
        setAiError('Nie udało się wygenerować feedbacku AI');
      }
    } finally {
      setAiLoading(false);
    }
  };

  const handleDeleteForm = async (e: React.MouseEvent, formId: string) => {
    e.stopPropagation();
    if (!window.confirm('Czy na pewno chcesz usunąć ten formularz?')) {
      return;
    }

    try {
      await deleteForm(formId);
      loadForms();
    } catch (err) {
      alert('Nie udało się usunąć formularza');
    }
  };

  const handleStartEditTitle = (e: React.MouseEvent, formId: string, currentTitle: string) => {
    e.stopPropagation();
    setEditingTitleId(formId);
    setEditingTitleValue(currentTitle);
  };

  const handleSaveTitle = async (e: React.MouseEvent, formId: string, formData: any) => {
    e.stopPropagation();
    if (editingTitleValue.trim() === '') {
      alert('Tytuł nie może być pusty');
      return;
    }

    try {
      await updateForm(formId, formData, undefined);
      await apiClient.put(`/api/forms/${formId}`, { title: editingTitleValue });
      setEditingTitleId(null);
      loadForms();
    } catch (err) {
      alert('Nie udało się zaktualizować tytułu');
    }
  };

  const handleCancelEditTitle = (e: React.MouseEvent) => {
    e.stopPropagation();
    setEditingTitleId(null);
    setEditingTitleValue('');
  };

  return (
    <div style={{ maxWidth: '1200px', margin: '0 auto', padding: '20px' }}>
      <style>{dashboardStyles}</style>
      {/* Header */}
      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: '30px',
        borderBottom: '2px solid #e0e0e0',
        paddingBottom: '15px'
      }}>
        <h1>Witaj w aplikacji NVC UpApp</h1>
        <div style={{ display: 'flex', gap: '10px', alignItems: 'center' }}>
          <span>{user?.email}</span>
          <button
            onClick={() => navigate('/change-password')}
            style={{
              padding: '8px 16px',
              background: '#2196f3',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer'
            }}
          >
            Zmień hasło
          </button>
          <button
            onClick={handleLogout}
            style={{
              padding: '8px 16px',
              background: '#f44336',
              color: 'white',
              border: 'none',
              borderRadius: '4px',
              cursor: 'pointer'
            }}
          >
            Wyloguj
          </button>
        </div>
      </div>

      {/* Quick actions */}
      <div style={{ marginBottom: '30px' }}>
        <h2>Szybkie akcje</h2>
        <div className="quick-actions-grid" style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(4, 1fr)',
          gap: '20px',
          marginTop: '15px'
        }}>
          <button
            onClick={() => navigate('/form/TUP')}
            style={{
              padding: '30px',
              minWidth: '200px',
              background: '#4a90e2',
              border: 'none',
              borderRadius: '8px',
              cursor: 'pointer',
              transition: 'all 0.2s',
            }}
            onMouseOver={(e) => e.currentTarget.style.background = '#3a7bc8'}
            onMouseOut={(e) => e.currentTarget.style.background = '#4a90e2'}
          >
            <h3 style={{ color: 'white', margin: '0 0 10px 0' }}>TUP</h3>
            <p style={{ margin: 0, color: 'white' }}>Tabela Uczuć i Potrzeb</p>
          </button>

          <button
            onClick={() => navigate('/form/DUP')}
            style={{
              padding: '30px',
              minWidth: '200px',
              background: '#66bb6a',
              border: 'none',
              borderRadius: '8px',
              cursor: 'pointer',
              transition: 'all 0.2s',
            }}
            onMouseOver={(e) => e.currentTarget.style.background = '#57a05a'}
            onMouseOut={(e) => e.currentTarget.style.background = '#66bb6a'}
          >
            <h3 style={{ color: 'white', margin: '0 0 10px 0' }}>DUP</h3>
            <p style={{ margin: 0, color: 'white' }}>Dzienniczek Uczuć i Potrzeb</p>
          </button>

          <button
            onClick={() => navigate('/form/DOS')}
            style={{
              padding: '30px',
              minWidth: '200px',
              background: '#ffa726',
              border: 'none',
              borderRadius: '8px',
              cursor: 'pointer',
              transition: 'all 0.2s',
            }}
            onMouseOver={(e) => e.currentTarget.style.background = '#f57c00'}
            onMouseOut={(e) => e.currentTarget.style.background = '#ffa726'}
          >
            <h3 style={{ color: 'white', margin: '0 0 10px 0' }}>DOS</h3>
            <p style={{ margin: 0, color: 'white' }}>Dziennik Osądów</p>
          </button>

          <button
            onClick={() => navigate('/form/OK10')}
            style={{
              padding: '30px',
              minWidth: '200px',
              background: '#ab47bc',
              border: 'none',
              borderRadius: '8px',
              cursor: 'pointer',
              transition: 'all 0.2s',
            }}
            onMouseOver={(e) => e.currentTarget.style.background = '#9c27b0'}
            onMouseOut={(e) => e.currentTarget.style.background = '#ab47bc'}
          >
            <h3 style={{ color: 'white', margin: '0 0 10px 0' }}>OK10</h3>
            <p style={{ margin: 0, color: 'white' }}>Obrachunek wg kroku 10 AA</p>
          </button>
        </div>
      </div>

      {/* Saved forms section */}
      <div style={{ marginTop: '60px' }}>
        <h3>Zapisane formularze</h3>

        {loading ? (
          <p>Ładowanie...</p>
        ) : forms.length === 0 ? (
          <p style={{ color: '#666', fontStyle: 'italic', textAlign: 'center', padding: '40px' }}>
            Brak zapisanych formularzy
          </p>
        ) : (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>
            {forms.map(form => (
              <div
                key={form.id}
                style={{
                  padding: '15px',
                  background: 'white',
                  border: '1px solid #e0e0e0',
                  borderRadius: '4px',
                  cursor: 'pointer',
                }}
                onClick={() => navigate(`/summary/${form.id}`)}
              >
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <div style={{ flex: 1 }}>
                    <div style={{ display: 'flex', gap: '10px', alignItems: 'center', marginBottom: '8px' }}>
                      <span style={{
                        padding: '4px 8px',
                        background: '#e3f2fd',
                        color: '#1976d2',
                        borderRadius: '4px',
                        fontSize: '12px',
                        fontWeight: 'bold'
                      }}>
                        {form.form_type}
                      </span>
                      <span style={{
                        padding: '4px 8px',
                        background: form.completion_status === 'completed' ? '#e8f5e9' : '#fff3e0',
                        color: form.completion_status === 'completed' ? '#2e7d32' : '#e65100',
                        borderRadius: '4px',
                        fontSize: '12px'
                      }}>
                        {form.completion_status === 'completed' ? 'Ukończony' : 'Wersja robocza'}
                      </span>
                    </div>
                    {form.title && (
                      <div style={{ fontSize: '16px', fontWeight: '500', marginBottom: '4px', color: '#333', display: 'flex', alignItems: 'center', gap: '8px' }}>
                        {editingTitleId === form.id ? (
                          <>
                            <input
                              type="text"
                              value={editingTitleValue}
                              onChange={(e) => setEditingTitleValue(e.target.value)}
                              onClick={(e) => e.stopPropagation()}
                              style={{
                                flex: 1,
                                padding: '4px 8px',
                                border: '1px solid #4a90e2',
                                borderRadius: '4px',
                                fontSize: '16px',
                              }}
                              autoFocus
                            />
                            <button
                              onClick={(e) => handleSaveTitle(e, form.id, form.form_data)}
                              style={{
                                padding: '4px 12px',
                                background: '#4caf50',
                                color: 'white',
                                border: 'none',
                                borderRadius: '4px',
                                cursor: 'pointer',
                                fontSize: '14px',
                              }}
                            >
                              ✓
                            </button>
                            <button
                              onClick={handleCancelEditTitle}
                              style={{
                                padding: '4px 12px',
                                background: '#d32f2f',
                                color: 'white',
                                border: 'none',
                                borderRadius: '4px',
                                cursor: 'pointer',
                                fontSize: '14px',
                              }}
                            >
                              ✗
                            </button>
                          </>
                        ) : (
                          <>
                            <span>{form.title}</span>
                            <button
                              onClick={(e) => handleStartEditTitle(e, form.id, form.title)}
                              style={{
                                padding: '2px 6px',
                                background: 'transparent',
                                color: '#666',
                                border: '1px solid #ccc',
                                borderRadius: '4px',
                                cursor: 'pointer',
                                fontSize: '12px',
                              }}
                            >
                              ✎
                            </button>
                          </>
                        )}
                      </div>
                    )}
                    <div style={{ fontSize: '14px', color: '#666' }}>
                      Utworzono: {new Date(form.created_at).toLocaleString('pl-PL')}
                    </div>
                  </div>
                  <div style={{ display: 'flex', gap: '8px' }}>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        navigate(`/form/${form.form_type}?id=${form.id}`);
                      }}
                      style={{
                        padding: '8px 16px',
                        background: '#4a90e2',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '14px',
                      }}
                    >
                      Edytuj
                    </button>
                    <button
                      onClick={(e) => handleAIFeedback(e, form.id)}
                      disabled={aiLoading}
                      style={{
                        padding: '8px 16px',
                        background: '#9c27b0',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: aiLoading ? 'wait' : 'pointer',
                        fontSize: '14px',
                        opacity: aiLoading ? 0.6 : 1,
                      }}
                    >
                      {aiLoading ? 'Ładowanie...' : 'empAI'}
                    </button>
                    <button
                      onClick={(e) => handleDeleteForm(e, form.id)}
                      style={{
                        padding: '8px 16px',
                        background: '#d32f2f',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '14px',
                      }}
                    >
                      Usuń
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* AI Feedback Modal */}
      {(aiFeedback || aiError) && (
        <div
          onClick={() => { setAiFeedback(null); setAiError(null); }}
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0, 0, 0, 0.5)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 1000,
          }}
        >
          <div
            onClick={(e) => e.stopPropagation()}
            style={{
              background: 'white',
              padding: '30px',
              borderRadius: '8px',
              maxWidth: '600px',
              width: '90%',
              maxHeight: '80vh',
              overflow: 'auto',
            }}
          >
            <h2 style={{ marginTop: 0, color: '#9c27b0' }}>empAI</h2>
            {aiError ? (
              <div style={{ color: '#d32f2f', padding: '15px', background: '#ffebee', borderRadius: '4px' }}>
                {aiError}
              </div>
            ) : (
              <div style={{
                whiteSpace: 'pre-wrap',
                lineHeight: '1.6',
                color: '#333',
                fontSize: '16px'
              }}>
                {aiFeedback}
              </div>
            )}
            <button
              onClick={() => { setAiFeedback(null); setAiError(null); }}
              style={{
                marginTop: '20px',
                padding: '10px 20px',
                background: '#9c27b0',
                color: 'white',
                border: 'none',
                borderRadius: '4px',
                cursor: 'pointer',
              }}
            >
              Zamknij
            </button>
          </div>
        </div>
      )}

      {/* Footer with account management */}
      <div style={{
        marginTop: '60px',
        paddingTop: '20px',
        borderTop: '1px solid #e0e0e0',
        textAlign: 'center'
      }}>
        <button
          onClick={handleDeleteAccount}
          style={{
            padding: '8px 16px',
            background: '#d32f2f',
            color: 'white',
            border: 'none',
            borderRadius: '4px',
            cursor: 'pointer',
            marginBottom: '20px',
            transition: 'all 0.2s',
          }}
          onMouseOver={(e) => e.currentTarget.style.background = '#b71c1c'}
          onMouseOut={(e) => e.currentTarget.style.background = '#d32f2f'}
        >
          Usuń konto
        </button>

        <div style={{ fontSize: '14px', color: '#666' }}>
          Copyright <a href="https://www.mindincoach.com" target="_blank" rel="noopener noreferrer" style={{ color: '#4a90e2', textDecoration: 'none' }}>www.mindincoach.com</a>. All rights reserved
        </div>
      </div>
    </div>
  );
}
