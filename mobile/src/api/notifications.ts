import apiClient from './client';
import { AppNotification, PaginatedResponse } from '../types';

export const notificationsApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<AppNotification>>('/notifications', { params: { page } }),
  markRead: (id: number) => apiClient.patch(`/notifications/${id}/read`),
};
