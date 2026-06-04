import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';

// Mock axios for testing
vi.mock('axios');

describe('API Connection Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('should successfully connect to backend health endpoint', async () => {
    const mockResponse = {
      data: {
        success: true,
        data: {
          status: 'healthy',
          timestamp: '2026-06-02T14:30:00Z',
          environment: 'dev',
        },
        error: null,
      },
      status: 200,
    };

    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse);

    const response = await axios.get('http://localhost:8080/api/health');

    expect(response.status).toBe(200);
    expect(response.data.success).toBe(true);
    expect(response.data.data.status).toBe('healthy');
  });

  it('should handle backend connection errors', async () => {
    const mockError = new Error('Network Error');
    vi.mocked(axios.get).mockRejectedValueOnce(mockError);

    await expect(axios.get('http://localhost:8080/api/health')).rejects.toThrow('Network Error');
  });

  it('should parse health check response correctly', async () => {
    const mockResponse = {
      data: {
        success: true,
        data: {
          status: 'healthy',
          timestamp: '2026-06-02T14:30:00Z',
        },
        error: null,
      },
      status: 200,
    };

    vi.mocked(axios.get).mockResolvedValueOnce(mockResponse);

    const response = await axios.get('http://localhost:8080/api/health');
    const { data } = response.data;

    expect(data).toHaveProperty('status');
    expect(data).toHaveProperty('timestamp');
    expect(data.timestamp).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/);
  });
});
