/**
 * useForm hook with auto-save functionality.
 */
import { useState, useEffect, useCallback, useRef } from 'react';
import { createForm, updateForm, getForm, FormData, generateAIFeedback } from '../services/forms';

export function useForm(initialFormId: string | null = null, formType: 'TUP' | 'DUP' | 'DOS' | 'OK10' = 'DUP') {
  const [formId, setFormId] = useState<string | null>(initialFormId);
  const [formData, setFormData] = useState<FormData>({});
  const [aiFeedback, setAiFeedback] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoadingAI, setIsLoadingAI] = useState(false);
  const [lastSaved, setLastSaved] = useState<Date | null>(null);
  const [error, setError] = useState<string | null>(null);

  const saveTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
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
      setAiFeedback(response.ai_feedback || null);
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

  const requestAIFeedback = useCallback(async () => {
    if (!formId) {
      // Save first if not yet saved
      await saveForm('draft');
      // Wait a bit for formId to be set
      await new Promise(resolve => setTimeout(resolve, 500));
    }

    setIsLoadingAI(true);
    setError(null);

    try {
      const currentFormId = formId || (await createForm(formType, formData, 'draft')).id;
      const response = await generateAIFeedback(currentFormId, false);

      if (response.status === 'success') {
        setAiFeedback(response.feedback);
      } else if (response.status === 'feedback_exists') {
        // User already has feedback, could prompt to overwrite
        const overwrite = window.confirm('Feedback już istnieje. Czy chcesz go nadpisać?');
        if (overwrite) {
          const newResponse = await generateAIFeedback(currentFormId, true);
          setAiFeedback(newResponse.feedback);
        } else {
          setAiFeedback(response.existing_feedback);
        }
      }
    } catch (err) {
      setError('Nie udało się wygenerować feedbacku AI');
      console.error('AI feedback error:', err);
    } finally {
      setIsLoadingAI(false);
    }
  }, [formId, formType, formData, saveForm]);

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
    aiFeedback,
    updateField,
    saveForm,
    submitForm,
    requestAIFeedback,
    isSaving,
    isLoadingAI,
    lastSaved,
    error,
  };
}

export default useForm;
