Mobile Integration Guide — Bearer Token API

Overview
--------
This guide shows the mobile-ready contract for the StayGrid Symfony backend. React Native should call the same API as the web app, but use the bearer token returned by login instead of relying on browser cookies.

Base URL
--------
Local development:

```text
http://localhost:8000
```

On a device or emulator on the same network, use your computer IP or a tunnel:

```text
http://YOUR_PC_IP:8000
https://YOUR_NGROK_HOST.ngrok-free.app
```

Auth Contract
-------------
Login:

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "test@example.com",
  "password": "password123"
}
```

Successful response:

```json
{
  "success": true,
  "token_type": "Bearer",
  "access_token": "<jwt-like-token>",
  "expires_in": 604800,
  "user": {
    "id": 1,
    "email": "test@example.com",
    "fullName": "John Doe"
  }
}
```

Every protected request must send:

```http
Authorization: Bearer <access_token>
Accept: application/json
```

Endpoints
---------
- `POST /api/auth/register`
- `POST /api/auth/login`
- `GET /api/auth/me`
- `GET /api/user/profile`
- `POST /api/auth/logout`
- `GET /api/rooms`
- `GET /api/bookings`
- `POST /api/bookings`

Fetch example
-------------

```js
const API_BASE_URL = 'https://YOUR_HOST';

export async function login(email, password) {
  const response = await fetch(`${API_BASE_URL}/api/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ email, password }),
  });

  const payload = await response.json();

  if (!response.ok) {
    throw new Error(payload.error || 'Login failed');
  }

  return payload;
}

export async function fetchProfile(accessToken) {
  const response = await fetch(`${API_BASE_URL}/api/user/profile`, {
    method: 'GET',
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${accessToken}`,
    },
  });

  const payload = await response.json();

  if (!response.ok) {
    throw new Error(payload.error || 'Profile request failed');
  }

  return payload.user;
}
```

Axios example
-------------

```js
import axios from 'axios';

const api = axios.create({
  baseURL: 'https://YOUR_HOST',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

export function setAccessToken(accessToken) {
  api.defaults.headers.common.Authorization = `Bearer ${accessToken}`;
}

export async function login(email, password) {
  const { data } = await api.post('/api/auth/login', { email, password });
  return data;
}

export async function getRooms() {
  const { data } = await api.get('/api/rooms');
  return data.data;
}

export async function createBooking(roomId, startDate, endDate, guests) {
  const { data } = await api.post('/api/bookings', {
    roomId,
    startDate,
    endDate,
    guests,
  });

  return data.data;
}
```

Token storage
-------------
Use SecureStore, Keychain, or AsyncStorage to persist the token between app launches. For Expo, SecureStore is the safer default.

```js
import * as SecureStore from 'expo-secure-store';

await SecureStore.setItemAsync('staygrid_access_token', accessToken);
const savedToken = await SecureStore.getItemAsync('staygrid_access_token');
```

Notes
-----
- The backend still supports sessions for the web app, but mobile should prefer bearer tokens.
- You do not need WebView login for the React Native API flow.
- If you use a tunnel, keep the same host for login and authenticated requests.
