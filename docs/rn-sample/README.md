StayGrid RN Sample (Expo)

Quickstart
----------
1. Install dependencies:

```bash
cd docs/rn-sample
yarn install
# or npm install
```

2. Edit [apiClient.js](apiClient.js) and replace `https://YOUR_HOST` with your Symfony backend or tunnel host:

```text
https://YOUR_HOST
```

3. Run the app:

```bash
yarn start
# then run on device or simulator (Expo)
```

What it does
------------
- `apiClient.js` centralizes the API base URL and attaches `Authorization: Bearer <token>` automatically.
- `ProfileScreen` calls `GET /api/user/profile` with the bearer token.
- `ProfileScreen` also calls `GET /api/bookings/my` so the signed-in user can see their own bookings.
- `BookingScreen` posts to `POST /api/bookings` with the same bearer token.
- The backend login endpoint returns `access_token`, `token_type`, and `expires_in` for mobile storage.

How to use it
-------------
Store the token after login, then pass it into the screens:

```jsx
<ProfileScreen tunnelHost={TUNNEL_HOST} authToken={accessToken} />
<BookingScreen tunnelHost={TUNNEL_HOST} authToken={accessToken} />
```

Exact fetch example
-------------------

```js
const loginResponse = await fetch(`${TUNNEL_HOST}/api/auth/login`, {
	method: 'POST',
	headers: {
		'Content-Type': 'application/json',
		Accept: 'application/json',
	},
	body: JSON.stringify({ email, password }),
});

const loginData = await loginResponse.json();
const accessToken = loginData.access_token;

const profileResponse = await fetch(`${TUNNEL_HOST}/api/user/profile`, {
	headers: {
		Accept: 'application/json',
		Authorization: `Bearer ${accessToken}`,
	},
});

const profileData = await profileResponse.json();
```

Exact axios example
------------------

```js
import axios from 'axios';

const api = axios.create({
	baseURL: TUNNEL_HOST,
	headers: {
		Accept: 'application/json',
		'Content-Type': 'application/json',
	},
});

export function setAccessToken(accessToken) {
	api.defaults.headers.common.Authorization = `Bearer ${accessToken}`;
}

export async function loadProfile() {
	const { data } = await api.get('/api/user/profile');
	return data.user;
}
```

Token storage
-------------
Use SecureStore, Keychain, or AsyncStorage to keep the token between app launches. For Expo, SecureStore is the safest default.

Notes
-----
- This sample is bearer-token based, so it does not depend on browser cookies or WebView login.
- Keep your tunnel or LAN host stable while testing so login and authenticated requests hit the same backend origin.

License
-------
MIT (sample code)
