import apiClient from './client';
import { ApiResponse, ChatMessage, Conversation, Pagination, PaginatedResponse } from '../types';

export interface ConversationThreadResponse {
  data: { conversation: Conversation; messages: ChatMessage[] };
  meta: { pagination: Pagination };
}

export const chatApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<Conversation>>('/conversations', { params: { page } }),

  start: (data: { vendor_id?: number; product_id?: number; message?: string }) =>
    apiClient.post<ApiResponse<Conversation>>('/conversations', data),

  show: (id: number, page = 1) =>
    apiClient.get<ConversationThreadResponse>(`/conversations/${id}`, { params: { page } }),

  sendMessage: (id: number, body: string) =>
    apiClient.post<ApiResponse<ChatMessage>>(`/conversations/${id}/messages`, { body }),
};
