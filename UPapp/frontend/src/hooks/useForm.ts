/**
 * useForm hook with auto-save functionality.
 */
import { useState, useEffect, useCallback, useRef } from 'react';
import { createForm, updateForm, getForm, FormData } from '../services/forms';

export function useForm(initialFormId: string | null = null, formType: 'TUP' | 'DUP' | 'DOS' = 'DUP') {
  const [formId, setFormId] = useState<string | null>(initialFormId);
  const [formData, setFormData] = useState<FormData>({});
  const [isSaving, setIsSaving] = useState(false);
  const [lastSaved, setLastSaved] = useState<Date | null>(null);
  const [error, setError] = useState<string | null>(null);

  const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const AUTO_SAVE_DELAY = 30000; // 30 seconds

  // Load form data on mount
  useEffect(() => {
    if (initialFormId) {
      loadForm(initialFormId);
    }
  }, [initialFormId]);

  const loadForm = async (id: string) => {
    try {
      const response = await getForm(id);
      setFormData(response.form_data);
      setFormId(id);
    } catch (err) {
      setError('Nie udało się załadować formularza');
      console.error('Load error:', err);
    }
  };

  const saveForm = useCallback(async (completionStatus: 'draft' | 'completed' = 'draft') => {
    setIsSaving(true);
    setError(null);

    try {
      if (formId) {
        // Update existing form
        await updateForm(formId, formData, completionStatus);
      } else {
        // Create new form
        const response = await createForm(formType, formData, completionStatus);
        setFormId(response.id);
      }
      setLastSaved(new Date());
    } catch (err) {
      setError('Nie udało się zapisać formularza');
      console.error('Save error:', err);
    } finally {
      setIsSaving(false);
    }
  }, [formId, formData, formType]);

  const updateField = useCallback((fieldName: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      [fieldName]: value,
    }));

    // Clear existing timeout
    if (saveTimeoutRef.current) {
      clearTimeout(saveTimeoutRef.current);
    }

    // Schedule auto-save
    saveTimeoutRef.current = setTimeout(() => {
      saveForm();
    }, AUTO_SAVE_DELAY);
  }, [saveForm]);

  const submitForm = useCallback(async () => {
    await saveForm('completed');
  }, [saveForm]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }
    };
  }, []);

  return {
    formId,
    formData,
    updateField,
    saveForm,
    submitForm,
    isSaving,
    lastSaved,
    error,
  };
}

export default useForm;
