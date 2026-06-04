/**
 * Reference data API client (feelings and needs).
 */
import apiClient from './api';

export interface Feeling {
  id: string;
  name_pl: string;
  category: 'fulfilled' | 'unfulfilled';
  subcategory: string;
  sort_order: number;
}

export interface Need {
  id: string;
  name_pl: string;
  category: string;
  sort_order: number;
}

export interface FeelingsData {
  fulfilled: { [subcategory: string]: Feeling[] };
  unfulfilled: { [subcategory: string]: Feeling[] };
}

export interface NeedsData {
  [category: string]: Need[];
}

/**
 * Get all feelings grouped by category and subcategory.
 */
export async function getFeelings(): Promise<FeelingsData> {
  const response = await apiClient.get('/api/reference/feelings');
  return response.data.data; // Backend returns {success, data, error}
}

/**
 * Get specific feeling by ID.
 */
export async function getFeeling(feelingId: string): Promise<Feeling> {
  const response = await apiClient.get(`/api/reference/feelings/${feelingId}`);
  return response.data.data;
}

/**
 * Get multiple feelings by IDs.
 */
export async function batchGetFeelings(feelingIds: string[]): Promise<Feeling[]> {
  const response = await apiClient.post('/api/reference/feelings/batch', {
    ids: feelingIds,
  });
  return response.data.data.feelings;
}

/**
 * Get all needs grouped by category.
 */
export async function getNeeds(): Promise<NeedsData> {
  const response = await apiClient.get('/api/reference/needs');
  return response.data.data; // Backend returns {success, data, error}
}

/**
 * Get specific need by ID.
 */
export async function getNeed(needId: string): Promise<Need> {
  const response = await apiClient.get(`/api/reference/needs/${needId}`);
  return response.data.data;
}

/**
 * Get multiple needs by IDs.
 */
export async function batchGetNeeds(needIds: string[]): Promise<Need[]> {
  const response = await apiClient.post('/api/reference/needs/batch', {
    ids: needIds,
  });
  return response.data.data.needs;
}
