import apiClient from './client';
import { ApiResponse, PaginatedResponse } from '../types';

export interface BlogPostSummary {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  cover_image: string | null;
  author_name?: string | null;
  published_at: string;
}

export interface BlogPostDetail extends Omit<BlogPostSummary, 'excerpt'> {
  body: string;
  seo_title?: string | null;
  seo_description?: string | null;
}

export interface StaticPageSection {
  heading: string | null;
  body: string;
}

export interface StaticPage {
  title: string;
  sections: StaticPageSection[];
}

export interface FaqPage {
  title: string;
  questions: { question: string; answer: string }[];
}

export const blogApi = {
  list: (page = 1) => apiClient.get<PaginatedResponse<BlogPostSummary>>('/blog', { params: { page } }),
  show: (slug: string) => apiClient.get<ApiResponse<BlogPostDetail>>(`/blog/${slug}`),
};

export const pagesApi = {
  aboutUs: () => apiClient.get<ApiResponse<StaticPage>>('/pages/about-us'),
  partnership: () => apiClient.get<ApiResponse<StaticPage>>('/pages/partnership'),
  privacy: () => apiClient.get<ApiResponse<StaticPage>>('/pages/privacy'),
  terms: () => apiClient.get<ApiResponse<StaticPage>>('/pages/terms'),
  faq: () => apiClient.get<ApiResponse<FaqPage>>('/pages/faq'),
  contact: (data: { name: string; email: string; subject: string; message: string }) =>
    apiClient.post<ApiResponse<null>>('/contact', data),
};
