'use client';

import { createContext, useCallback, useEffect, useMemo, useState } from 'react';

import { authService } from '@/services/authService';
import { getToken, setToken } from '@/services/apiClient';

// Holds auth + tenant state for the whole app. The current company (tenant) is
// simply the authenticated user's company — the backend enforces isolation, the
// frontend just reflects it.
export const AuthContext = createContext(null);

// status: 'loading' (checking existing session) | 'authenticated' | 'guest'
export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [status, setStatus] = useState('loading');

  // On first load, if we have a token, try to restore the session.
  useEffect(() => {
    let active = true;

    async function restore() {
      if (!getToken()) {
        setStatus('guest');
        return;
      }

      try {
        const me = await authService.me();
        if (active) {
          setUser(me);
          setStatus('authenticated');
        }
      } catch {
        setToken(null);
        if (active) {
          setStatus('guest');
        }
      }
    }

    restore();

    return () => {
      active = false;
    };
  }, []);

  const login = useCallback(async (credentials) => {
    const { token, user: loggedIn } = await authService.login(credentials);
    setToken(token);
    setUser(loggedIn);
    setStatus('authenticated');
    return loggedIn;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authService.logout();
    } finally {
      setToken(null);
      setUser(null);
      setStatus('guest');
    }
  }, []);

  const value = useMemo(
    () => ({
      user,
      company: user?.company ?? null, // the current tenant
      role: user?.role ?? null,
      status,
      isAuthenticated: status === 'authenticated',
      login,
      logout,
    }),
    [user, status, login, logout],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
