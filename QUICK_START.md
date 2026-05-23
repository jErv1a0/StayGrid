# 🚀 Quick Start - StayGrid API for React Native

## Your Backend is Ready! ✅

Your Symfony backend is now a **headless API** that serves both your web app AND React Native app.

---

## TL;DR - Get Started in 5 Minutes

### 1. Start the Server
```bash
cd c:\Users\Jerv\Documents\Dev\staygrid\staygrid
symfony serve --no-tls
```

### 2. Find Your PC IP
```bash
ipconfig
```
Look for IPv4 Address. Example: `192.168.1.100`

### 3. Test Login in React Native
```javascript
const API_URL = 'http://192.168.1.100:8000/api'; // Use YOUR PC IP!

fetch(`${API_URL}/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include', // IMPORTANT!
  body: JSON.stringify({
    email: 'test@example.com',
    password: 'password'
  })
})
.then(r => r.json())
.then(data => console.log('Logged in:', data.user))
.catch(e => console.error('Error:', e));
```

### 4. Get Rooms
```javascript
fetch(`${API_URL}/room_listings`, {
  credentials: 'include'
})
.then(r => r.json())
.then(data => console.log('Rooms:', data.member));
```

---

## Available API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| **POST** | `/api/login` | Login user |
| **POST** | `/api/logout` | Logout user |
| **GET** | `/api/room_listings` | Get all rooms |
| **POST** | `/api/room_listings` | Create room (admin) |
| **GET** | `/api/room_listings/{id}` | Get specific room |
| **PUT** | `/api/room_listings/{id}` | Update room |
| **DELETE** | `/api/room_listings/{id}` | Delete room |
| **GET** | `/api/bookings` | Get my bookings |
| **POST** | `/api/bookings` | Create booking |
| **GET** | `/api/bookings/{id}` | Get booking details |
| **PUT** | `/api/bookings/{id}` | Update booking |
| **DELETE** | `/api/bookings/{id}` | Cancel booking |
| **GET** | `/api/log_in_users/{id}` | Get user profile |

---

## ⚠️ Important Tips

### 1. Use Your PC's IP, NOT localhost
**❌ Wrong:**
```javascript
const API_URL = 'http://localhost:8000/api'; // Won't work on phone!
```

**✅ Correct:**
```javascript
const API_URL = 'http://192.168.1.100:8000/api'; // Your actual PC IP
```

### 2. Always Use `credentials: 'include'`
This sends cookies automatically and keeps the session alive:
```javascript
fetch(url, {
  credentials: 'include', // ⭐ Required for auth!
})
```

### 3. Login First
Before calling any protected endpoint, login:
```javascript
// 1. Login
await fetch(`${API_URL}/login`, { ... });

// 2. Then call other endpoints (they use the session from login)
await fetch(`${API_URL}/room_listings`, { credentials: 'include' });
```

---

## Testing Without React Native First

Use **Postman** or **curl** to test:

```bash
# 1. Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  -c cookies.txt

# 2. Get rooms (using saved cookies)
curl http://localhost:8000/api/room_listings -b cookies.txt

# 3. Create booking
curl -X POST http://localhost:8000/api/bookings \
  -H "Content-Type: application/ld+json" \
  -d '{
    "room": "/api/room_listings/1",
    "startDate": "2026-05-01T00:00:00Z",
    "endDate": "2026-05-05T00:00:00Z",
    "status": "pending",
    "user": "/api/log_in_users/1"
  }' \
  -b cookies.txt
```

---

## File Structure

```
staygrid/
├── API_DOCUMENTATION.md        ← Detailed API docs
├── QUICK_START.md              ← This file
├── src/
│   ├── Entity/
│   │   ├── LogInUsers.php      ← User (with @ApiResource)
│   │   ├── RoomListing.php     ← Rooms (with @ApiResource)
│   │   └── Booking.php         ← Bookings (with @ApiResource)
│   └── Controller/
│       ├── ApiAuthController.php ← Login/Logout/User endpoints
│       └── ... (your other controllers)
├── config/
│   └── packages/
│       ├── api_platform.yaml   ← API config
│       ├── security.yaml       ← Auth config
│       └── nelmio_cors.yaml    ← CORS config
└── ... (rest of your project)
```

---

## Architecture

```
Your PC (Symfony Backend)
   ↓ API (JSON)
   ├─→ Web Browser (Twig templates)
   └─→ React Native App (Phone)
   
Both use the same database & business logic!
```

---

## What's Configured ✅

- ✅ API Platform (auto-generates endpoints)
- ✅ Session authentication (no JWT needed)
- ✅ CORS enabled for mobile
- ✅ Serialization groups for security
- ✅ Room endpoints (CRUD)
- ✅ Booking endpoints (CRUD)
- ✅ User endpoints
- ✅ Login/Logout endpoints

---

## Common Errors & Fixes

### Error: "Connection refused"
**Fix:** 
- Is Symfony server running? (`symfony serve`)
- Are you using the right IP? (`ipconfig`)
- Is port 8000 open in firewall?

### Error: "401 Unauthorized"
**Fix:**
- Did you login first?
- Are you using `credentials: 'include'`?
- Are you sending the right email/password?

### Error: "CORS error"
**Fix:**
- CORS is configured for localhost/127.0.0.1
- For distant servers, update `CORS_ALLOW_ORIGIN` in `.env`

### Error: "404 Not Found"
**Fix:**
- Check the endpoint URL spelling
- Verify the resource ID exists
- Check the HTTP method (GET vs POST, etc)

---

## Next Steps

1. **Start server:** `symfony serve --no-tls`
2. **Test endpoints:** Use Postman or curl first
3. **Build React Native:** Use the examples above
4. **Deploy:** Docker/Nginx when ready

---

## Full Documentation

For detailed API responses, parameters, and examples:
→ See `API_DOCUMENTATION.md`

---

## Need Help?

Check:
1. Are cookies being sent? (`credentials: 'include'`)
2. Is the API URL correct? (use your PC IP, not localhost)
3. Did you login? (call `/api/login` first)
4. Check browser console for errors

You're all set! 🚀
