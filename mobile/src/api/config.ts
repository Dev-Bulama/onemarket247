import apiClient from './client';
import { ApiResponse, AppConfig, City, Country, State } from '../types';

export interface LanguageOption {
  id: number;
  code: string;
  name: string;
  native_name: string;
  direction: 'ltr' | 'rtl';
  is_default: boolean;
}

export interface CurrencyOption {
  id: number;
  code: string;
  symbol: string;
  is_default: boolean;
}

export const configApi = {
  get: () => apiClient.get<ApiResponse<AppConfig>>('/config'),
};

export const referenceApi = {
  countries: () => apiClient.get<ApiResponse<Country[]>>('/countries'),
  states: (countryId: number) => apiClient.get<ApiResponse<State[]>>(`/countries/${countryId}/states`),
  cities: (stateId: number) => apiClient.get<ApiResponse<City[]>>(`/states/${stateId}/cities`),
  languages: () => apiClient.get<ApiResponse<LanguageOption[]>>('/languages'),
  currencies: () => apiClient.get<ApiResponse<CurrencyOption[]>>('/currencies'),
};
