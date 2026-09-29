import apiClient from './client';

export const locationApi = {
  updateConsent: (enabled: boolean) => apiClient.post<{ data: { enabled: boolean } }>('/location/consent', { enabled }),
  ping: (latitude: number, longitude: number, accuracy?: number) =>
    apiClient.post('/location/ping', { latitude, longitude, accuracy }),
};
