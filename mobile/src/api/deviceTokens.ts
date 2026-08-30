import apiClient from './client';

export const deviceTokensApi = {
  register: (token: string, platform: string) => apiClient.post('/device-tokens', { token, platform }),
  unregister: (token: string) => apiClient.delete('/device-tokens', { data: { token } }),
};
