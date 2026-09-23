import apiClient from './client';
import { ApiResponse } from '../types';

export type PickedFile = { uri: string; name: string; type: string };

export interface AgentSummary {
  id: number;
  full_name: string;
  phone?: string | null;
}

export interface AgentApplicationReceipt {
  id: number;
  reference_number: string;
  status: string;
  status_label: string;
  created_at: string;
}

export interface AgentApplicationPayload {
  full_name: string;
  email: string;
  phone?: string;
  country_id?: number;
  state_id?: number;
  city_id?: number;
  postal_code?: string;
  address?: string;
  identity_type?: string;
  identity_number?: string;
  notes?: string;
  identity_document: PickedFile;
  proof_of_address_document?: PickedFile;
  terms: boolean;
}

function appendFields(form: FormData, data: object, skip: string[] = []) {
  Object.entries(data).forEach(([key, value]) => {
    if (skip.includes(key) || value === undefined || value === null) return;
    if (typeof value === 'boolean') {
      form.append(key, value ? '1' : '0');
      return;
    }
    form.append(key, String(value));
  });
}

const APPLICATION_FILE_KEYS = ['identity_document', 'proof_of_address_document'];

export const agentApplicationApi = {
  apply: (data: AgentApplicationPayload) => {
    const form = new FormData();
    appendFields(form, data, APPLICATION_FILE_KEYS);
    form.append('identity_document', data.identity_document as unknown as Blob);
    if (data.proof_of_address_document) {
      form.append('proof_of_address_document', data.proof_of_address_document as unknown as Blob);
    }
    return apiClient.post<ApiResponse<AgentApplicationReceipt>>('/agent/apply', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};

export const agentsApi = {
  list: () => apiClient.get<ApiResponse<AgentSummary[]>>('/agents'),
};
