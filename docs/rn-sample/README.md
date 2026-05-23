StayGrid RN Sample (Expo)

Quickstart
----------
1. Install dependencies:

```bash
cd docs/rn-sample
yarn install
# or npm install
```

2. Expose your local backend with ngrok (or localtunnel):

```bash
ngrok http 8000
# note the https URL (e.g. https://abcd1234.ngrok.io)
```

3. Edit `App.js` and set `TUNNEL_HOST` to your ngrok https URL.

4. Run the app:

```bash
yarn start
# then run on device or simulator (Expo)
```

What it does
------------
- `LoginWebView` opens your site's login page in a WebView. After successful login the session cookie (PHPSESSID) is set for the tunnel host.
- `ProfileScreen` calls `GET /api/user/profile` with `credentials: 'include'` to fetch the authenticated user profile.
- `BookingScreen` posts to `POST /api/bookings` with booking payload; uses `credentials: 'include'` so the server receives the session cookie.

Notes
-----
- This is a minimal sample for testing. For production/mobile OAuth, consider using PKCE or server-side flows.
- If cookies don't persist on Android, enable third-party cookies or use `@react-native-cookies/cookies` to read/persist cookies.

Security
--------
- Only use HTTPS tunnels for mobile testing. Do not expose private dev servers publicly without controls.

License
-------
MIT (sample code)
