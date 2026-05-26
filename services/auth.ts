import AsyncStorage from '@react-native-async-storage/async-storage';

import { getApiBaseUrl } from '@/constants/api';
import { apiPost, ApiError } from '@/services/api';

export const TOKEN_KEY = 'token';
export const USER_KEY = 'user';
export const DEMO_TOKEN = 'demo-token';

type LoginApiResponse = {
  token?: string;
  message?: string;
  success?: boolean;
  [key: string]: unknown;
};

export type LoginResult =
  | { ok: true; token: string; user: LoginApiResponse }
  | { ok: false; message: string };

export async function login(username: string, password: string): Promise<LoginResult> {
  try {
    const data = await apiPost<LoginApiResponse>('login', { username, password });

    const token = typeof data.token === 'string' ? data.token : '';
    if (!token) {
      return {
        ok: false,
        message: data.message ?? 'Login failed: server did not return a token.',
      };
    }

    await AsyncStorage.setItem(TOKEN_KEY, token);
    await AsyncStorage.setItem(USER_KEY, JSON.stringify({ ...data, username }));

    return { ok: true, token, user: data };
  } catch (error) {
    if (error instanceof ApiError) {
      return { ok: false, message: error.message };
    }
    return {
      ok: false,
      message: `Cannot connect to API at ${getApiBaseUrl()}. Check your server and network.`,
    };
  }
}

export async function logout(): Promise<void> {
  await AsyncStorage.multiRemove([TOKEN_KEY, USER_KEY]);
}

export async function demoLogin(): Promise<LoginResult> {
  const token = DEMO_TOKEN;
  const user = {
    user_id: 0,
    username: 'demo',
    email: 'demo@example.com',
    name: 'Demo User',
  };

  await AsyncStorage.setItem(TOKEN_KEY, token);
  await AsyncStorage.setItem(USER_KEY, JSON.stringify(user));

  return { ok: true, token, user };
}

export async function getStoredToken(): Promise<string | null> {
  return AsyncStorage.getItem(TOKEN_KEY);
}
