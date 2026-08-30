import axios, { AxiosError, InternalAxiosRequestConfig } from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API_BASE_URL, API_TIMEOUT } from '../config/api';

// In-memory token cache — avoids an AsyncStorage round trip on every request.
let cachedToken: string | null | undefined = undefined;

export async function setAuthToken(token: string | null) {
  cachedToken = token;
  if (token) {
    await AsyncStorage.setItem('auth_token', token);
  } else {
    await AsyncStorage.removeItem('auth_token');
  }
}

async function getToken(): Promise<string | null> {
  if (cachedToken !== undefined) return cachedToken;
  cachedToken = await AsyncStorage.getItem('auth_token');
  return cachedToken;
}

// Same in-memory-cache-over-AsyncStorage pattern as the auth token above —
// read on every request via the interceptor below, written by
// localeStore.setLanguage()/setCurrency() (see src/store/localeStore.ts).
let cachedLanguage: string | null | undefined = undefined;
let cachedCurrency: string | null | undefined = undefined;

export async function setPreferredLanguage(code: string | null) {
  cachedLanguage = code;
  if (code) await AsyncStorage.setItem('preferred_language', code);
  else await AsyncStorage.removeItem('preferred_language');
}

export async function setPreferredCurrency(code: string | null) {
  cachedCurrency = code;
  if (code) await AsyncStorage.setItem('preferred_currency', code);
  else await AsyncStorage.removeItem('preferred_currency');
}

async function getPreferredLanguage(): Promise<string | null> {
  if (cachedLanguage !== undefined) return cachedLanguage;
  cachedLanguage = await AsyncStorage.getItem('preferred_language');
  return cachedLanguage;
}

async function getPreferredCurrency(): Promise<string | null> {
  if (cachedCurrency !== undefined) return cachedCurrency;
  cachedCurrency = await AsyncStorage.getItem('preferred_currency');
  return cachedCurrency;
}

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: API_TIMEOUT,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
});

/**
 * Called once on app startup after the bootstrap fetch resolves (see
 * bootstrapStore.ts) — switches every subsequent API call to whichever
 * URL the admin-configured environment resolved to, which may differ
 * from the API_BASE_URL this client was created with (that's just the
 * pre-bootstrap fallback for the very first request).
 */
export function setBaseUrl(url: string) {
  if (url && url !== apiClient.defaults.baseURL) {
    apiClient.defaults.baseURL = url;
  }
}

// Retry safe read-only requests on network failure / 5xx (never on 4xx or writes)
const RETRY_METHODS = new Set(['get', 'head']);
const MAX_RETRIES = 2;

function isRetryable(error: AxiosError): boolean {
  if (!RETRY_METHODS.has((error.config?.method ?? '').toLowerCase())) return false;
  if (!error.response) return true; // network error
  return error.response.status >= 500;
}

function sleep(ms: number) {
  return new Promise<void>(resolve => setTimeout(() => resolve(), ms));
}

apiClient.interceptors.request.use(async (config: InternalAxiosRequestConfig) => {
  const token = await getToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  const language = await getPreferredLanguage();
  if (language) config.headers['X-Language'] = language;
  const currency = await getPreferredCurrency();
  if (currency) config.headers['X-Currency'] = currency;
  return config;
});

apiClient.interceptors.response.use(
  response => response,
  async (error: AxiosError) => {
    const config = error.config as (InternalAxiosRequestConfig & { _retryCount?: number }) | undefined;

    if (error.response?.status === 401) {
      cachedToken = null;
      await AsyncStorage.removeItem('auth_token');
      await AsyncStorage.removeItem('user');
      return Promise.reject(error);
    }

    if (isRetryable(error) && config) {
      config._retryCount = (config._retryCount ?? 0) + 1;
      if (config._retryCount <= MAX_RETRIES) {
        await sleep(500 * Math.pow(2, config._retryCount - 1));
        return apiClient(config);
      }
    }

    return Promise.reject(error);
  },
);

/** Human-readable message from any API error — every error response body
 * follows App\Support\Api\ApiResponse::error()'s {message, errors, error_code} shape. */
export function apiErrorMessage(error: unknown, fallback = 'Something went wrong. Please try again.'): string {
  const err = error as AxiosError<{ message?: string }>;
  return err?.response?.data?.message || (err?.message === 'Network Error' ? 'No internet connection.' : fallback);
}

export default apiClient;
