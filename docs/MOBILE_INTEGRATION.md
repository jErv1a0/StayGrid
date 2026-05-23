Mobile Integration Guide — Session Cookies & Google OAuth

Overview
--------
This guide explains how to test the web app's session-based authentication from a React Native mobile app using an HTTPS tunnel (ngrok/localtunnel) and how to configure Google OAuth redirect URIs for mobile testing.

Prerequisites
-------------
- Local dev server running (e.g. `symfony server:start` or `php -S 127.0.0.1:8000 -t public`).
- ngrok (https://ngrok.com) or localtunnel (https://localtunnel.github.io/www/) installed.
- React Native dev environment for testing the app (Expo or plain RN).

Step 1 — Expose local site via HTTPS
-----------------------------------
Using ngrok (recommended):

1. Start ngrok forwarding to your local port (e.g., 8000):

```bash
ngrok http 8000
```

2. Note the HTTPS URL (e.g., `https://abcd1234.ngrok.io`).

Using localtunnel:

```bash
npx localtunnel --port 8000 --subdomain my-test-subdomain
```

Step 2 — Update app config / environment
----------------------------------------
Set your mobile app to access API endpoints at the tunnel host, e.g. `https://abcd1234.ngrok.io`.

Step 3 — Session cookies from React Native
------------------------------------------
Option A: Use a WebView for authentication pages (recommended for session flows)
- Open the login page inside a WebView pointed at the ngrok HTTPS URL.
- Ensure WebView allows cookies and third-party cookies on Android (`setAcceptThirdPartyCookies`).
- After login the cookie (`PHPSESSID`) will be set for the tunnel host; subsequent fetches to the tunnel host will send the cookie.

Option B: Use fetch + cookie management
- Perform credentialed requests from RN and persist cookies with libraries such as `react-native-cookiemanager` or `react-native-cookies`.
- Example with fetch (note: default fetch does not persist cookies across app restarts):

```js
fetch('https://abcd1234.ngrok.io/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password }),
  credentials: 'include',
});
```

Step 4 — Google OAuth mobile testing
------------------------------------
1. In Google Cloud Console, go to Credentials → OAuth 2.0 Client IDs. Create or edit a Web application client.
2. Add the ngrok HTTPS redirect URI exactly: `https://abcd1234.ngrok.io/connect/google/check` (or the route used by `connect_google_check`).
3. In `config/packages/knpu_oauth2_client.yaml`, set `redirect_route` to the route name (not a full URL). Ensure `redirect_uri` generation uses the current request host (ngrok).
4. Initiate Google login via the WebView (so the OAuth redirect sets cookies on the same host).

Step 5 — Testing flows
----------------------
- Login via WebView, then call protected endpoints from RN with `credentials: 'include'` or allow cookies in WebView navigation.
- Verify that `GET /api/user/profile` returns the logged-in user's data.

Notes & Troubleshooting
-----------------------
- Google OAuth requires that the redirect URI registered in Google Cloud exactly matches the URI used by the app.
- If cookies don't persist on Android, prefer WebView auth or handle cookies explicitly with `react-native-cookies`.
- For production mobile OAuth flows, consider using OAuth with PKCE or rely on server-side sessions with a secure cookie tied to your mobile client.

Contact
-------
If you want, I can add a sample React Native snippet showing cookie handling with `react-native-cookies` and a WebView-based login flow.


React Native Examples
---------------------
Below are minimal React Native snippets you can copy into your app to test session authentication against the ngrok/localtunnel host.

1) WebView-based login (recommended for session flows)

```jsx
// Example: components/LoginWebView.js
import React from 'react';
import { Platform } from 'react-native';
import { WebView } from 'react-native-webview';

export default function LoginWebView({ onLoginSuccess }) {
  const tunnelHost = 'https://abcd1234.ngrok.io'; // replace with your tunnel
  const loginUrl = `${tunnelHost}/login`; // your login route

  const handleNavigationStateChange = (navState) => {
    const { url } = navState;
    // Detect success redirect (adjust path to your app)
    if (url.startsWith(`${tunnelHost}/connect/google/check`) || url.startsWith(`${tunnelHost}/user/profile`)) {
      // Notify app that login likely succeeded
      onLoginSuccess && onLoginSuccess();
    }
  };

  return (
    <WebView
      source={{ uri: loginUrl }}
      onNavigationStateChange={handleNavigationStateChange}
      javaScriptEnabled
      domStorageEnabled
      sharedCookiesEnabled={true}
      thirdPartyCookiesEnabled={true}
      originWhitelist={["*"]}
      onMessage={(event) => { /* handle postMessage from page if used */ }}
      onLoadEnd={() => {
        if (Platform.OS === 'android') {
          // Android: ensure third-party cookies enabled in native WebView
          // react-native-webview handles most cases; if not, use native module to call
        }
      }}
    />
  );
}
```

2) Fetch with credentials (send session cookie with requests)

```js
// Example: make an authenticated API call from RN after login in WebView
const resp = await fetch('https://abcd1234.ngrok.io/api/user/profile', {
  method: 'GET',
  credentials: 'include', // ensure cookie is sent
  headers: {
    'Accept': 'application/json',
  },
});
const profile = await resp.json();
```

3) Read and persist cookies (optional) — `react-native-cookies`

Install:
```bash
yarn add @react-native-cookies/cookies
// or npm install @react-native-cookies/cookies
```

Usage:

```js
import { Cookies } from '@react-native-cookies/cookies';

// Read cookies for the tunnel host
const cookies = await Cookies.get('https://abcd1234.ngrok.io');
console.log('cookies', cookies);

// Optionally persist or forward cookies with fetch by setting headers (not preferred)
// Better: let WebView set the cookie and use fetch with credentials: 'include'
```

Notes
-----
- Use the exact HTTPS tunnel host when registering Google OAuth redirect URIs in Google Cloud Console.
- For Android, WebView may block third-party cookies by default on some devices; prefer WebView auth or manage cookies via native modules.
- When testing, clear browser cookies (inside WebView) between runs to avoid stale sessions.

If you'd like, I can add a small sample RN project (Expo) with these flows wired up.
