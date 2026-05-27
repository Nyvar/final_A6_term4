// constants/api.ts
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

/** Matches `api/rest.http` — path after host:port */
export const API_PATH = '/final_A6_term4/api';  // Changed: added /api
export const API_PORT = '8888';
export const API_BASE_URL_STORAGE_KEY = 'api_base_url';

const DEFAULT_LAN_IP = '192.168.1.2';

let runtimeOverride: string | null = null;

export function setApiBaseUrlOverride(url: string | null): void {
  runtimeOverride = url?.trim().replace(/\/+$/, '') || null;
}

export async function loadApiBaseUrlOverride(): Promise<string | null> {
  try {
    const stored = await AsyncStorage.getItem(API_BASE_URL_STORAGE_KEY);
    if (stored?.trim()) {
      runtimeOverride = stored.trim().replace(/\/+$/, '');
      return runtimeOverride;
    }
  } catch {
    // Ignore storage errors.
  }
  return null;
}

export async function saveApiBaseUrlOverride(url: string): Promise<void> {
  const normalized = url.trim().replace(/\/+$/, '');
  runtimeOverride = normalized;
  await AsyncStorage.setItem(API_BASE_URL_STORAGE_KEY, normalized);
}

export function getApiBaseUrl(): string {
  if (runtimeOverride) {
    return runtimeOverride;
  }

  const fromEnv = process.env.EXPO_PUBLIC_API_BASE_URL?.trim();
  if (fromEnv) {
    return fromEnv.replace(/\/+$/, '');
  }

  const fromExtra = Constants.expoConfig?.extra?.apiBaseUrl;
  if (typeof fromExtra === 'string' && fromExtra.trim()) {
    return fromExtra.trim().replace(/\/+$/, '');
  }

  if (Platform.OS === 'android') {
    return `http://10.0.2.2:${API_PORT}${API_PATH}`;
  }

  if (Platform.OS === 'web') {
    return `http://localhost:${API_PORT}${API_PATH}`;
  }

  // iOS / physical device: use your PC Wi-Fi IP (same network as phone).
  return `http://${DEFAULT_LAN_IP}:${API_PORT}${API_PATH}`;
}