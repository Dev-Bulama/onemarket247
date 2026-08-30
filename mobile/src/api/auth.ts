import apiClient from './client';
import { ApiResponse, User } from '../types';

interface AuthPayload {
  token: string;
  user: User;
}

export const authApi = {
  register: (data: { name: string; email: string; phone?: string; password: string; password_confirmation: string }) =>
    apiClient.post<ApiResponse<AuthPayload>>('/auth/register', data),

  login: (email: string, password: string, deviceName = 'mobile-app') =>
    apiClient.post<ApiResponse<AuthPayload>>('/auth/login', { email, password, device_name: deviceName }),

  logout: () => apiClient.post('/auth/logout'),

  forgotPassword: (email: string) => apiClient.post('/auth/forgot-password', { email }),

  resetPassword: (data: { token: string; email: string; password: string; password_confirmation: string }) =>
    apiClient.post('/auth/reset-password', data),

  profile: () => apiClient.get<ApiResponse<User>>('/profile'),

  updateProfile: (data: Partial<{ name: string; phone: string }>) =>
    apiClient.patch<ApiResponse<User>>('/profile', data),

  updatePassword: (data: { current_password: string; password: string; password_confirmation: string }) =>
    apiClient.post('/profile/password', data),
};
