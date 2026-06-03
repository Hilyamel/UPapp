/**
 * Forms API client methods.
 */
import apiClient from './api';

export interface FormData {
  [key: string]: any;
}

export interface Form {
  id: string;
  user_id: string;
  form_type: 'TUP' | 'DUP' | 'DOS';
  form_data: FormData;
  completion_status: 'draft' | 'completed';
  title?: string;
  created_at: string;
  updated_at: string;
}

export interface FormSummary {
  id: string;
  form_type: string;
  completion_status: string;
  title?: string;
  created_at: string;
  updated_at: string;
  sections: SummarySection[];
}

export interface SummarySection {
  title: string;
  layout?: 'single-column' | 'two-column';
  fields?: SummaryField[];
  columns?: SummaryColumn[];
}

export interface SummaryField {
  label: string;
  value: string;
}

export interface SummaryColumn {
  title: string;
  text: string;
  selected?: string;
}

export interface FormFilters {
  formType?: 'TUP' | 'DUP' | 'DOS';
  completionStatus?: 'draft' | 'completed';
  limit?: number;
  offset?: number;
}

/**
 * Create a new form submission.
 */
export async function createForm(
  formType: 'TUP' | 'DUP' | 'DOS',
  formData: FormData,
  completionStatus: 'draft' | 'completed' = 'draft'
): Promise<Form> {
  const response = await apiClient.post('/api/forms', {
    form_type: formType,
    form_data: formData,
    completion_status: completionStatus,
  });
  return response.data.data; // Backend returns {success, data, error}
}

/**
 * Get list of user's forms.
 */
export async function listForms(filters: FormFilters = {}): Promise<Form[]> {
  try {
    const params = new URLSearchParams();
    if (filters.formType) params.append('form_type', filters.formType);
    if (filters.completionStatus) params.append('completion_status', filters.completionStatus);
    if (filters.limit) params.append('limit', filters.limit.toString());
    if (filters.offset) params.append('offset', filters.offset.toString());

    const query = params.toString();
    const response = await apiClient.get(query ? `/api/forms?${query}` : '/api/forms');
    return response.data.data; // Backend returns {success, data, error}
  } catch (error) {
    console.error('Failed to list forms:', error);
    return [];
  }
}

/**
 * Get a specific form.
 */
export async function getForm(formId: string): Promise<Form> {
  const response = await apiClient.get(`/api/forms/${formId}`);
  return response.data.data; // Backend returns {success, data, error}
}

/**
 * Update a form (auto-save).
 */
export async function updateForm(
  formId: string,
  formData: FormData,
  completionStatus?: 'draft' | 'completed'
): Promise<Form> {
  const payload: any = { form_data: formData };
  if (completionStatus) {
    payload.completion_status = completionStatus;
  }

  const response = await apiClient.put(`/api/forms/${formId}`, payload);
  return response.data.data; // Backend returns {success, data, error}
}

/**
 * Delete a specific form.
 */
export async function deleteForm(formId: string): Promise<void> {
  await apiClient.delete(`/api/forms/${formId}`);
}

/**
 * Get form summary (only filled fields).
 */
export async function getFormSummary(formId: string): Promise<FormSummary> {
  const response = await apiClient.get(`/api/forms/${formId}/summary`);
  return response.data.data; // Backend returns {success, data, error}
}

/**
 * Generate AI empathetic feedback for a form.
 */
export async function generateAIFeedback(formId: string): Promise<any> {
  const response = await apiClient.post(`/api/forms/${formId}/ai-feedback`);
  return response.data.data; // Backend returns {success, data, error}
}
