## Overview

- **API Type**: RESTful JSON API
- **Framework**: Symfony 7.3 + API Platform
- **Authentication**: Session-based (works cross-device)
- **Database**: MySQL (same as your web app)
- **Status**: ✅ Ready to use

---

## Base URL

### For Local Development (your PC)
```
http://localhost:8000/api
```

### For Mobile Device (on same network)
```
http://YOUR_PC_IP:8000/api
```

**To find your PC's IP:**
```bash
ipconfig  # On Windows, look for IPv4 Address
```

Example:
```
http://192.168.1.100:8000/api
```

---

## Authentication Flow

### 1️⃣ Login

**Endpoint:** `POST /api/login`

**Request:**
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "test@example.com",
    "fullName": "John Doe",
    "roles": ["ROLE_CLIENT"],
    "isVerified": true
  }
}
```

**Response (Failed - 401):**
```json
{
  "error": "Invalid credentials"
}
```

### 2️⃣ All Authenticated Requests

Add this header to every request after login:
```
Cookie: PHPSESSID=session_value_here
```

Or use `credentials: 'include'` in fetch (automatically sends cookies).

### 3️⃣ Logout

**Endpoint:** `POST /api/logout`

**Response:**
```json
{
  "success": true,
  "message": "Logged out"
}
```

---

## API Endpoints

### 🏨 Rooms API

#### Get All Rooms
```
GET /api/room_listings
GET /api/room_listings?page=1&itemsPerPage=20
```

**Response:**
```json
{
  "@context": "/api/contexts/RoomListing",
  "@type": "Collection",
  "member": [
    {
      "@id": "/api/room_listings/1",
      "id": 1,
      "number": "101",
      "category": "Deluxe",
      "description": "Spacious room with ocean view",
      "capacity": 2,
      "pricePerNight": "150.00",
      "isAvailable": true,
      "image": "room101.jpg",
      "location": "Floor 1",
      "isBlocked": false
    }
  ],
  "totalItems": 25
}
```

#### Get Single Room
```
GET /api/room_listings/{id}
```

#### Create Room (Admin)
```
POST /api/room_listings
Content-Type: application/ld+json

{
  "number": "102",
  "category": "Standard",
  "description": "Cozy room",
  "capacity": 1,
  "pricePerNight": "100.00",
  "isAvailable": true
}
```

#### Update Room (Admin)
```
PUT /api/room_listings/{id}
Content-Type: application/ld+json

{
  "pricePerNight": "120.00"
}
```

#### Delete Room (Admin)
```
DELETE /api/room_listings/{id}
```

---

### 📅 Bookings API

#### Get My Bookings
```
GET /api/bookings
```

**Response:**
```json
{
  "@context": "/api/contexts/Booking",
  "@type": "Collection",
  "member": [
    {
      "@id": "/api/bookings/1",
      "id": 1,
      "room": "/api/room_listings/1",
      "startDate": "2026-05-01T14:00:00Z",
      "endDate": "2026-05-05T10:00:00Z",
      "status": "confirmed",
      "user": "/api/log_in_users/1"
    }
  ]
}
```

#### Create Booking
```
POST /api/bookings
Content-Type: application/ld+json
Cookie: PHPSESSID=...

{
  "room": "/api/room_listings/1",
  "startDate": "2026-05-01T14:00:00Z",
  "endDate": "2026-05-05T10:00:00Z",
  "status": "pending",
  "user": "/api/log_in_users/1"
}
```

#### Update Booking Status
```
PUT /api/bookings/{id}
Content-Type: application/ld+json
Cookie: PHPSESSID=...

{
  "status": "confirmed"
}
```

#### Cancel Booking (Delete)
```
DELETE /api/bookings/{id}
Cookie: PHPSESSID=...
```

---

### 👤 Users API

#### Get User Profile
```
GET /api/log_in_users/{id}
```

#### List All Users (Admin Only)
```
GET /api/log_in_users
Cookie: PHPSESSID=...  (must be ROLE_ADMIN)
```

---

## React Native Examples

### Setup
Store your API base URL:
```javascript
const API_URL = 'http://192.168.1.100:8000/api'; // Change to your PC IP
```

### 1. Login

```javascript
const login = async (email, password) => {
  try {
    const response = await fetch(`${API_URL}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include', // ⭐ Important: sends cookies
      body: JSON.stringify({
        email: email,
        password: password
      })
    });

    const data = await response.json();
    
    if (response.ok) {
      console.log('Logged in:', data.user);
      return data.user;
    } else {
      throw new Error(data.error || 'Login failed');
    }
  } catch (error) {
    console.error('Login error:', error);
    return null;
  }
};
```

### 2. Get Rooms

```javascript
const getRooms = async () => {
  try {
    const response = await fetch(`${API_URL}/room_listings`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
    });

    const data = await response.json();
    return data.member || []; // Array of rooms
  } catch (error) {
    console.error('Error fetching rooms:', error);
    return [];
  }
};
```

### 3. Create Booking

```javascript
const createBooking = async (roomId, startDate, endDate, userId) => {
  try {
    const response = await fetch(`${API_URL}/bookings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
      },
      credentials: 'include',
      body: JSON.stringify({
        room: `/api/room_listings/${roomId}`,
        startDate: startDate,
        endDate: endDate,
        status: 'pending',
        user: `/api/log_in_users/${userId}`
      })
    });

    const booking = await response.json();
    console.log('Booking created:', booking);
    return booking;
  } catch (error) {
    console.error('Booking error:', error);
    return null;
  }
};
```

### 4. Get My Bookings

```javascript
const getMyBookings = async () => {
  try {
    const response = await fetch(`${API_URL}/bookings`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include',
    });

    const data = await response.json();
    return data.member || [];
  } catch (error) {
    console.error('Error fetching bookings:', error);
    return [];
  }
};
```

### 5. Logout

```javascript
const logout = async () => {
  try {
    await fetch(`${API_URL}/logout`, {
      method: 'POST',
      credentials: 'include',
    });
    console.log('Logged out');
  } catch (error) {
    console.error('Logout error:', error);
  }
};
```

### Complete Example - React Native Component

```javascript
import React, { useState, useEffect } from 'react';
import { View, Button, FlatList, Text, TextInput } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function BookingScreen() {
  const API_URL = 'http://192.168.1.100:8000/api'; // Change this!
  
  const [rooms, setRooms] = useState([]);
  const [bookings, setBookings] = useState([]);
  const [user, setUser] = useState(null);
  const [email, setEmail] = useState('test@example.com');
  const [password, setPassword] = useState('password');

  useEffect(() => {
    checkLogin();
  }, []);

  const checkLogin = async () => {
    const savedUser = await AsyncStorage.getItem('user');
    if (savedUser) {
      setUser(JSON.parse(savedUser));
      fetchRooms();
      fetchBookings();
    }
  };

  const handleLogin = async () => {
    const response = await fetch(`${API_URL}/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();
    if (response.ok) {
      setUser(data.user);
      await AsyncStorage.setItem('user', JSON.stringify(data.user));
      fetchRooms();
      fetchBookings();
    } else {
      alert(data.error);
    }
  };

  const fetchRooms = async () => {
    const response = await fetch(`${API_URL}/room_listings`, {
      credentials: 'include'
    });
    const data = await response.json();
    setRooms(data.member || []);
  };

  const fetchBookings = async () => {
    const response = await fetch(`${API_URL}/bookings`, {
      credentials: 'include'
    });
    const data = await response.json();
    setBookings(data.member || []);
  };

  const handleBooking = async (roomId) => {
    try {
      const response = await fetch(`${API_URL}/bookings`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json' },
        credentials: 'include',
        body: JSON.stringify({
          room: `/api/room_listings/${roomId}`,
          startDate: new Date().toISOString(),
          endDate: new Date(Date.now() + 86400000).toISOString(),
          status: 'pending',
          user: `/api/log_in_users/${user.id}`
        })
      });

      if (response.ok) {
        alert('Booking created!');
        fetchBookings();
      }
    } catch (error) {
      alert('Booking failed: ' + error.message);
    }
  };

  const handleLogout = async () => {
    await fetch(`${API_URL}/logout`, {
      method: 'POST',
      credentials: 'include'
    });
    setUser(null);
    await AsyncStorage.removeItem('user');
  };

  if (!user) {
    return (
      <View style={{ flex: 1, padding: 20 }}>
        <TextInput
          placeholder="Email"
          value={email}
          onChangeText={setEmail}
          style={{ borderWidth: 1, padding: 10, marginBottom: 10 }}
        />
        <TextInput
          placeholder="Password"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          style={{ borderWidth: 1, padding: 10, marginBottom: 10 }}
        />
        <Button title="Login" onPress={handleLogin} />
      </View>
    );
  }

  return (
    <View style={{ flex: 1, padding: 20 }}>
      <Text style={{ fontSize: 18, marginBottom: 10 }}>
        Welcome, {user.fullName}!
      </Text>

      <Text style={{ fontSize: 16, marginBottom: 10 }}>Available Rooms:</Text>
      <FlatList
        data={rooms}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <View style={{ borderWidth: 1, padding: 10, marginBottom: 10 }}>
            <Text>Room {item.number} - ${item.pricePerNight}/night</Text>
            <Button
              title="Book"
              onPress={() => handleBooking(item.id)}
            />
          </View>
        )}
      />

      <Text style={{ fontSize: 16, marginTop: 20, marginBottom: 10 }}>
        Your Bookings:
      </Text>
      <FlatList
        data={bookings}
        keyExtractor={(item) => item.id.toString()}
        renderItem={({ item }) => (
          <View style={{ borderWidth: 1, padding: 10, marginBottom: 10 }}>
            <Text>Room: {item.room}</Text>
            <Text>Status: {item.status}</Text>
          </View>
        )}
      />

      <Button title="Logout" onPress={handleLogout} color="red" />
    </View>
  );
}
```

---

## Running the Server

### Start Development Server

```bash
cd c:\Users\Jerv\Documents\Dev\staygrid\staygrid
symfony serve
```

Or without TLS:
```bash
symfony serve --no-tls
```

The server will start at:
- `http://localhost:8000` (from your PC)
- `http://YOUR_IP:8000` (from your phone)

### Find Your PC IP

```bash
ipconfig
```

Look for "IPv4 Address" under your network adapter. Example: `192.168.1.100`

---

## Testing with Postman/cURL

### 1. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  -c cookies.txt
```

### 2. Get Rooms (using cookies from login)
```bash
curl http://localhost:8000/api/room_listings \
  -b cookies.txt
```

### 3. Create Booking
```bash
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

## API Documentation (Live)

View interactive API docs:
```
http://localhost:8000/api/docs
```

---

## Common Issues & Fixes

| Issue | Solution |
|-------|----------|
| **CORS Error** | API is configured for localhost/127.0.0.1, check `CORS_ALLOW_ORIGIN` in `.env` |
| **Session not persisting** | Make sure `credentials: 'include'` is set in fetch requests |
| **Mobile can't connect** | Use your PC's IP (not localhost), check firewall settings |
| **401 Unauthorized** | You're not logged in, call `/api/login` first |
| **404 Not Found** | Resource doesn't exist, check ID or endpoint URL |

---

## Architecture Overview

```
┌─────────────────────────┐
│   Your Symfony Backend   │
│   (Single Codebase)      │
│                          │
│  - Entities              │
│  - Database              │
│  - Business Logic        │
└────────────┬──────────────┘
             │
       ┌─────┴──────┐
       │             │
  ┌────▼─────┐  ┌───▼──────┐
  │     Web   │  │ React    │
  │  Frontend │  │ Native   │
  │ (Twig)    │  │   App    │
  └───────────┘  └──────────┘
       │             │
       └──────┬──────┘
              │
          API JSON
         (Both use)
```

---

## What's Configured ✅

- ✅ API Platform installed and enabled
- ✅ Serialization groups set up for entities
- ✅ Session-based authentication working
- ✅ CORS configured 
- ✅ Room listing endpoints (GET, POST, PUT, DELETE)
- ✅ Booking endpoints (GET, POST, PUT, DELETE)
- ✅ User endpoints (GET, POST)
- ✅ Login/Logout endpoints
- ✅ API documentation template created

---

## Next Steps

1. **Test the API**: Start the server and test endpoints with Postman or React Native
2. **Build React Native App**: Use the examples above to consume the API
3. **Deploy**: Use Docker/Nginx when ready for production

---

Enjoy your headless API! 🚀


