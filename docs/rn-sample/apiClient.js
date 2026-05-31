const API_BASE_URL = 'https://YOUR_HOST';

let accessToken = '';

export function setAccessToken(token) {
  accessToken = token || '';
}

function buildHeaders(extraHeaders = {}) {
  const headers = {
    Accept: 'application/json',
    ...extraHeaders,
  };

  if (accessToken) {
    headers.Authorization = `Bearer ${accessToken}`;
  }

  return headers;
}

async function request(path, options = {}) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: buildHeaders(options.headers || {}),
  });

  const payload = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(payload.error || payload.message || `Request failed (${response.status})`);
  }

  return payload;
}

export async function login(email, password) {
  const payload = await request('/api/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });

  setAccessToken(payload.access_token);
  return payload;
}

export function getProfile() {
  return request('/api/user/profile');
}

export function getRooms() {
  return request('/api/rooms');
}

export function getBookings() {
  return request('/api/bookings');
}

export function createBooking(roomId, startDate, endDate, guests) {
  return request('/api/bookings', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ roomId, startDate, endDate, guests }),
  });
}

export function logout() {
  setAccessToken('');
  return request('/api/auth/logout', { method: 'POST' });
}

export { API_BASE_URL };