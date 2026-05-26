// services/api.ts
import { getApiBaseUrl } from '@/constants/api';
import { getToken } from './storage';

export class ApiError extends Error {
  status: number;
  data: unknown;

  constructor(message: string, status: number, data: unknown) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

export async function apiPost<T extends Record<string, unknown>>(
  action: string,
  body: Record<string, unknown>
): Promise<T> {
  const baseUrl = getApiBaseUrl();
  const url = `${baseUrl}/?action=${encodeURIComponent(action)}`;

  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  const text = await response.text();
  let data: T | null = null;

  try {
    data = text ? (JSON.parse(text) as T) : null;
  } catch {
    data = null;
  }

  if (!response.ok) {
    const message =
      (data && typeof data === 'object' && 'message' in data && typeof data.message === 'string'
        ? data.message
        : null) || `Request failed (${response.status})`;
    throw new ApiError(message, response.status, data);
  }

  return (data ?? {}) as T;
}

// Dashboard
export async function getDashboard(currency: string = 'USD', date_filter: string = 'month') {
  const token = await getToken();
  return apiPost('get_dashboard', { token, currency, date_filter });
}

// Currencies
export async function getCurrencies() {
  const token = await getToken();
  return apiPost('get_currencies', { token });
}

export async function updateCurrencyBalance(currency_code: string, amount: number) {
  const token = await getToken();
  return apiPost('update_currency_balance', { token, currency_code, amount });
}

// Categories
export async function getCategories(record_type: 'expense' | 'income') {
  const token = await getToken();
  return apiPost('get_categories', { token, record_type });
}

export async function addCategory(category_name: string, record_type: string, color: string) {
  const token = await getToken();
  return apiPost('add_category', { token, category_name, record_type, color });
}

export async function deleteCategory(category_id: number) {
  const token = await getToken();
  return apiPost('delete_category', { token, category_id });
}

// Payment Methods
export async function getPaymentMethods() {
  const token = await getToken();
  return apiPost('get_payment_methods', { token });
}

// Transactions
export async function addExpense(
  amount: number,
  category_id: number,
  payment_method_id: number,
  currency_code: string,
  note: string = ''
) {
  const token = await getToken();
  return apiPost('add_expense', { token, amount, category_id, payment_method_id, currency_code, note });
}

export async function addIncome(amount: number, currency_code: string, note: string = '') {
  const token = await getToken();
  return apiPost('add_income', { token, amount, currency_code, note });
}

export async function transferMoney(
  amount: number,
  from_currency: string,
  to_currency: string,
  exchange_rate: number,
  note: string = ''
) {
  const token = await getToken();
  return apiPost('transfer', { token, amount, from_currency, to_currency, exchange_rate, note });
}

export async function deleteTransaction(record_type: string, record_id: number) {
  const token = await getToken();
  return apiPost('delete_transaction', { token, record_type, record_id });
}

// Profile
export async function getProfile() {
  const token = await getToken();
  return apiPost('get_profile', { token });
}

export async function updateProfile(username: string, email: string) {
  const token = await getToken();
  return apiPost('update_profile', { token, username, email });
}

export async function changePassword(current_password: string, new_password: string, confirm_password: string) {
  const token = await getToken();
  return apiPost('change_password', { token, current_password, new_password, confirm_password });
}

export async function exportTransactions() {
  const token = await getToken();
  return apiPost('export_transactions', { token });
}

export async function testApiConnection(): Promise<{ ok: boolean; message: string }> {
  const baseUrl = getApiBaseUrl();
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 8000);

  try {
    const response = await fetch(`${baseUrl}/?action=login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: '__ping__', password: '__ping__' }),
      signal: controller.signal,
    });
    clearTimeout(timeout);
    return {
      ok: true,
      message: `Connected to ${baseUrl} (HTTP ${response.status})`,
    };
  } catch (error) {
    clearTimeout(timeout);
    const detail = error instanceof Error ? error.message : 'Unknown error';
    return {
      ok: false,
      message: `Cannot reach ${baseUrl}. ${detail}`,
    };
  }
}

