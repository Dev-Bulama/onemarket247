import apiClient from './client';
import { ApiResponse, AppConfig, City, Country, State } from '../types';

export const configApi = {
  get: () => apiClient.get<ApiResponse<AppConfig>>('/config'),
};

export const referenceApi = {
  countries: () => apiClient.get<ApiResponse<Country[]>>('/countries'),
  states: (countryId: number) => apiClient.get<ApiResponse<State[]>>(`/countries/${countryId}/states`),
  cities: (stateId: number) => apiClient.get<ApiResponse<City[]>>(`/states/${stateId}/cities`),
};
