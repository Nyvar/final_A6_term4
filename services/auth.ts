// services/auth.ts
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
  user_id?: number;
  username?: string;
  email?: string;
  [key: string]: unknown;
};

export type LoginResult =
  | { ok: true; token: string; user: LoginApiResponse }
  | { ok: false; message: string };

export async function login(username: string, password: string): Promise<LoginResult> {
  try {
    console.log('Attempting login for username:', username);
    console.log('API Base URL:', getApiBaseUrl());
    
    const baseUrl = getApiBaseUrl();
    const url = `${baseUrl}/?action=login`;
    
    console.log('Request URL:', url);
    
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ username, password }),
    });

    console.log('Response status:', response.status);
    
    const text = await response.text();
    console.log('Raw response:', text);
    
    let data: LoginApiResponse;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error('Failed to parse JSON:', e);
      return {
        ok: false,
        message: 'Invalid response from server',
      };
    }
    
    console.log('Parsed response:', data);

    if (!response.ok) {
      return {
        ok: false,
        message: data.message || `Login failed (HTTP ${response.status})`,
      };
    }

    // Try different possible token field names
    const token = data.token || data.access_token || data.data?.token;
    
    if (!token) {
      console.error('No token found in response. Response keys:', Object.keys(data));
      return {
        ok: false,
        message: data.message || 'Login failed: server did not return a token.',
      };
    }

    // Store token and user data
    await AsyncStorage.setItem(TOKEN_KEY, token);
    await AsyncStorage.setItem(USER_KEY, JSON.stringify({
      username: username,
      user_id: data.user_id,
      email: data.email,
      ...data
    }));

    console.log('Login successful, token stored');
    return { ok: true, token, user: data };
  } catch (error) {
    console.error('Login error:', error);
    if (error instanceof ApiError) {
      return { ok: false, message: error.message };
    }
    if (error instanceof Error) {
      return { ok: false, message: `Network error: ${error.message}` };
    }
    return {
      ok: false,
      message: `Cannot connect to API at ${getApiBaseUrl()}. Check if your backend server is running.`,
    };
  }
}

export async function logout(): Promise<void> {
  try {
    await AsyncStorage.multiRemove([TOKEN_KEY, USER_KEY]);
    console.log('Logged out successfully');
  } catch (error) {
    console.error('Logout error:', error);
  }
}

export async function demoLogin(): Promise<LoginResult> {
  // For demo, try to login with demo credentials first
  try {
    const result = await login('ks1', '123456');
    if (result.ok) {
      return result;
    }
  } catch (error) {
    console.log('Demo login with real API failed, using local demo mode');
  }
  
  // Fallback to local demo mode if API is not available
  const token = DEMO_TOKEN;
  const user = {
    user_id: 999,
    username: 'demo',
    email: 'demo@example.com',
    name: 'Demo User',
  };

  await AsyncStorage.setItem(TOKEN_KEY, token);
  await AsyncStorage.setItem(USER_KEY, JSON.stringify(user));

  return { ok: true, token, user };
}

export async function getStoredToken(): Promise<string | null> {
  try {
    const token = await AsyncStorage.getItem(TOKEN_KEY);
    console.log('Retrieved stored token:', token ? 'Yes' : 'No');
    return token;
  } catch (error) {
    console.error('Error getting stored token:', error);
    return null;
  }
}

export async function getStoredUser(): Promise<any | null> {
  try {
    const userStr = await AsyncStorage.getItem(USER_KEY);
    return userStr ? JSON.parse(userStr) : null;
  } catch (error) {
    console.error('Error getting stored user:', error);
    return null;
  }
}