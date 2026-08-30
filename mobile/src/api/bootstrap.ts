import axios from 'axios';
import { BOOTSTRAP_URL, API_TIMEOUT } from '../config/api';
import { ApiResponse } from '../types';

export interface BootstrapPayload {
  api_base_url: string | null;
  app_name: string;
  logo_url: string | null;
  splash_logo_url: string | null;
  min_app_version: string | null;
}

// A bare, separate axios instance — deliberately NOT the shared apiClient
// (src/api/client.ts), since apiClient's base URL is exactly what this
// call exists to determine. Always hits BOOTSTRAP_URL (production),
// never the resolved/overridden base URL from a previous bootstrap.
const bootstrapClient = axios.create({ baseURL: BOOTSTRAP_URL, timeout: API_TIMEOUT });

export const bootstrapApi = {
  fetch: () => bootstrapClient.get<ApiResponse<BootstrapPayload>>('/bootstrap'),
};
