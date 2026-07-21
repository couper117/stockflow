import { api } from './apiClient';

// Auth-specific API calls, kept separate from the generic client.
export const authService = {
  login: (credentials) => api.post('/auth/login', credentials, { auth: false }),
  logout: () => api.post('/auth/logout'),
  me: () => api.get('/auth/me'),
};
