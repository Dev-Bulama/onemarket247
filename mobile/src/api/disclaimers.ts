import apiClient from './client';

export interface DisclaimerData {
  id: number;
  title: string;
  content: string;
  trigger: string;
  requires_acceptance: boolean;
}

export const disclaimersApi = {
  general: (guestId: string | null) =>
    apiClient.get<{ data: DisclaimerData | null }>('/disclaimers/general', { params: guestId ? { guest_id: guestId } : {} }),
  active: (trigger: string, guestId: string | null) =>
    apiClient.get<{ data: DisclaimerData | null }>('/disclaimers/active', { params: { trigger, guest_id: guestId ?? undefined } }),
  accept: (disclaimerId: number, guestId: string | null) =>
    apiClient.post(`/disclaimers/${disclaimerId}/accept`, guestId ? { guest_id: guestId } : {}),
};
