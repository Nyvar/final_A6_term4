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
  // The action should be appended as a query parameter, but baseUrl already includes /api
  const url = `${baseUrl}/?action=${encodeURIComponent(action)}`;
  
  console.log(`API Call: ${action} to ${url}`);
  console.log('Request body:', body);

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json'  // Add this to request JSON
      },
      body: JSON.stringify(body),
    });

    console.log('Response status:', response.status);
    console.log('Content-Type:', response.headers.get('content-type'));

    // Check if response is JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text();
      console.error('Non-JSON response received:', text.substring(0, 200));
      throw new ApiError(
        'Server returned HTML instead of JSON. Please check if the API endpoint is correct.',
        response.status,
        null
      );
    }

    const text = await response.text();
    console.log(`API Response (${action}):`, text);
    
    let data: T | null = null;

    try {
      data = text ? (JSON.parse(text) as T) : null;
    } catch (error) {
      console.error(`Failed to parse JSON for ${action}:`, error);
      throw new ApiError('Invalid JSON response from server', response.status, null);
    }

    if (!response.ok) {
      const message = (data && typeof data === 'object' && 'message' in data && typeof data.message === 'string')
        ? data.message
        : `Request failed (${response.status})`;
      throw new ApiError(message, response.status, data);
    }

    return (data ?? {}) as T;
  } catch (error) {
    if (error instanceof ApiError) {
      throw error;
    }
    throw new ApiError(
      error instanceof Error ? error.message : 'Network request failed',
      0,
      null
    );
  }
}

// The rest of the functions remain the same...
export async function getDashboard(currency: string = 'USD', date_filter: string = 'month') {
  const token = await getToken();
  return apiPost('get_dashboard', { token, currency, date_filter });
}

export async function getCurrencies() {
  const token = await getToken();
  return apiPost('get_currencies', { token });
}

export async function updateCurrencyBalance(currency_code: string, amount: number) {
  const token = await getToken();
  return apiPost('update_currency_balance', { token, currency_code, amount });
}

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

export async function getPaymentMethods() {
  const token = await getToken();
  return apiPost('get_payment_methods', { token });
}

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
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ username: '__ping__', password: '__ping__' }),
      signal: controller.signal,
    });
    clearTimeout(timeout);
    
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      return {
        ok: false,
        message: `Server returned HTML. Make sure your API server is running at ${baseUrl}`,
      };
    }
    
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