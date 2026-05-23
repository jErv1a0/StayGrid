# 🚀 React Native App Development Prompt

## Project: StayGrid Mobile App

### Overview
Build a React Native mobile app that connects to the StayGrid Symfony API backend. The app should allow users to browse rooms, make bookings, and manage their reservations.

---

## 🔑 API Configuration

### Base URL
**Development:**
```
http://YOUR_PC_IP:8000/api
```
Replace `YOUR_PC_IP` with your actual PC IP address (find with `ipconfig` command).

**Example:**
```
http://192.168.1.100:8000/api
```

### Authentication
**Type:** Session-based (NOT API keys)
- Login creates a session cookie
- All subsequent requests must include `credentials: 'include'`
- Session persists across app usage

---

## 📱 App Requirements

### Core Features
1. **User Authentication**
   - Login screen
   - Logout functionality
   - Session persistence

2. **Room Browsing**
   - List all available rooms
   - Room details view
   - Search/filter rooms

3. **Booking System**
   - Create new bookings
   - View my bookings
   - Cancel bookings
   - Booking status updates

4. **User Profile**
   - View profile information
   - Edit profile (optional)

---

## 🔧 Technical Implementation

### 1. API Service Setup

Create an API service file:

```javascript
// api.js
const API_BASE_URL = 'http://192.168.1.100:8000/api'; // CHANGE THIS TO YOUR PC IP

class ApiService {
  constructor() {
    this.baseURL = API_BASE_URL;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;

    const defaultOptions = {
      headers: {
        'Content-Type': 'application/json',
      },
      credentials: 'include', // IMPORTANT: Sends cookies
      ...options
    };

    const response = await fetch(url, defaultOptions);
    return response;
  }

  async get(endpoint) {
    return this.request(endpoint);
  }

  async post(endpoint, data) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  }

  async put(endpoint, data) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data)
    });
  }

  async delete(endpoint) {
    return this.request(endpoint, {
      method: 'DELETE'
    });
  }
}

export default new ApiService();
```

### 2. Authentication Implementation

```javascript
// auth.js
import api from './api';
import AsyncStorage from '@react-native-async-storage/async-storage';

export const authService = {
  // Login user
  async login(email, password) {
    try {
      const response = await api.post('/login', { email, password });
      const data = await response.json();

      if (response.ok) {
        // Save user data locally
        await AsyncStorage.setItem('user', JSON.stringify(data.user));
        return { success: true, user: data.user };
      } else {
        return { success: false, error: data.error };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  // Logout user
  async logout() {
    try {
      await api.post('/logout');
      await AsyncStorage.removeItem('user');
      return { success: true };
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  // Get current user from storage
  async getCurrentUser() {
    try {
      const userJson = await AsyncStorage.getItem('user');
      return userJson ? JSON.parse(userJson) : null;
    } catch (error) {
      return null;
    }
  },

  // Check if user is logged in
  async isLoggedIn() {
    const user = await this.getCurrentUser();
    return user !== null;
  }
};
```

### 3. Room Service

```javascript
// roomService.js
import api from './api';

export const roomService = {
  // Get all rooms
  async getRooms(page = 1, limit = 20) {
    try {
      const response = await api.get(`/room_listings?page=${page}&itemsPerPage=${limit}`);
      const data = await response.json();

      if (response.ok) {
        return { success: true, rooms: data.member, total: data.totalItems };
      } else {
        return { success: false, error: 'Failed to fetch rooms' };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  // Get single room
  async getRoom(id) {
    try {
      const response = await api.get(`/room_listings/${id}`);
      const room = await response.json();

      if (response.ok) {
        return { success: true, room };
      } else {
        return { success: false, error: 'Room not found' };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  }
};
```

### 4. Booking Service

```javascript
// bookingService.js
import api from './api';

export const bookingService = {
  // Get my bookings
  async getMyBookings() {
    try {
      const response = await api.get('/bookings');
      const data = await response.json();

      if (response.ok) {
        return { success: true, bookings: data.member };
      } else {
        return { success: false, error: 'Failed to fetch bookings' };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  // Create booking
  async createBooking(roomId, startDate, endDate, userId) {
    try {
      const bookingData = {
        room: `/api/room_listings/${roomId}`,
        startDate: startDate, // ISO string: '2026-05-01T14:00:00Z'
        endDate: endDate,     // ISO string: '2026-05-05T10:00:00Z'
        status: 'pending',
        user: `/api/log_in_users/${userId}`
      };

      const response = await api.post('/bookings', bookingData);
      const booking = await response.json();

      if (response.ok) {
        return { success: true, booking };
      } else {
        return { success: false, error: 'Failed to create booking' };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  },

  // Cancel booking
  async cancelBooking(bookingId) {
    try {
      const response = await api.delete(`/bookings/${bookingId}`);

      if (response.ok) {
        return { success: true };
      } else {
        return { success: false, error: 'Failed to cancel booking' };
      }
    } catch (error) {
      return { success: false, error: error.message };
    }
  }
};
```

---

## 📱 Screen Components

### 1. Login Screen

```javascript
// LoginScreen.js
import React, { useState } from 'react';
import { View, Text, TextInput, Button, Alert } from 'react-native';
import { authService } from '../services/auth';

export default function LoginScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!email || !password) {
      Alert.alert('Error', 'Please fill in all fields');
      return;
    }

    setLoading(true);
    const result = await authService.login(email, password);
    setLoading(false);

    if (result.success) {
      navigation.replace('Home');
    } else {
      Alert.alert('Login Failed', result.error);
    }
  };

  return (
    <View style={{ flex: 1, padding: 20, justifyContent: 'center' }}>
      <Text style={{ fontSize: 24, marginBottom: 20, textAlign: 'center' }}>
        StayGrid Login
      </Text>

      <TextInput
        placeholder="Email"
        value={email}
        onChangeText={setEmail}
        keyboardType="email-address"
        autoCapitalize="none"
        style={{ borderWidth: 1, padding: 10, marginBottom: 10 }}
      />

      <TextInput
        placeholder="Password"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
        style={{ borderWidth: 1, padding: 10, marginBottom: 20 }}
      />

      <Button
        title={loading ? 'Logging in...' : 'Login'}
        onPress={handleLogin}
        disabled={loading}
      />
    </View>
  );
}
```

### 2. Room List Screen

```javascript
// RoomListScreen.js
import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, Alert } from 'react-native';
import { roomService } from '../services/room';

export default function RoomListScreen({ navigation }) {
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadRooms();
  }, []);

  const loadRooms = async () => {
    const result = await roomService.getRooms();
    setLoading(false);

    if (result.success) {
      setRooms(result.rooms);
    } else {
      Alert.alert('Error', result.error);
    }
  };

  const renderRoom = ({ item }) => (
    <TouchableOpacity
      style={{ borderWidth: 1, padding: 15, marginBottom: 10 }}
      onPress={() => navigation.navigate('RoomDetail', { roomId: item.id })}
    >
      <Text style={{ fontSize: 18, fontWeight: 'bold' }}>
        Room {item.number}
      </Text>
      <Text>{item.category}</Text>
      <Text>${item.pricePerNight}/night</Text>
      <Text>Capacity: {item.capacity}</Text>
    </TouchableOpacity>
  );

  return (
    <View style={{ flex: 1, padding: 20 }}>
      <Text style={{ fontSize: 24, marginBottom: 20 }}>Available Rooms</Text>

      {loading ? (
        <Text>Loading rooms...</Text>
      ) : (
        <FlatList
          data={rooms}
          renderItem={renderRoom}
          keyExtractor={(item) => item.id.toString()}
        />
      )}
    </View>
  );
}
```

### 3. Booking Screen

```javascript
// BookingScreen.js
import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, Alert, Button } from 'react-native';
import { bookingService } from '../services/booking';
import { authService } from '../services/auth';

export default function BookingScreen() {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    const currentUser = await authService.getCurrentUser();
    setUser(currentUser);

    const result = await bookingService.getMyBookings();
    setLoading(false);

    if (result.success) {
      setBookings(result.bookings);
    } else {
      Alert.alert('Error', result.error);
    }
  };

  const handleCancelBooking = async (bookingId) => {
    Alert.alert(
      'Cancel Booking',
      'Are you sure you want to cancel this booking?',
      [
        { text: 'No', style: 'cancel' },
        {
          text: 'Yes',
          onPress: async () => {
            const result = await bookingService.cancelBooking(bookingId);
            if (result.success) {
              Alert.alert('Success', 'Booking cancelled');
              loadData(); // Refresh list
            } else {
              Alert.alert('Error', result.error);
            }
          }
        }
      ]
    );
  };

  const renderBooking = ({ item }) => (
    <View style={{ borderWidth: 1, padding: 15, marginBottom: 10 }}>
      <Text style={{ fontSize: 16, fontWeight: 'bold' }}>
        Room: {item.room}
      </Text>
      <Text>Status: {item.status}</Text>
      <Text>From: {new Date(item.startDate).toLocaleDateString()}</Text>
      <Text>To: {new Date(item.endDate).toLocaleDateString()}</Text>

      {item.status === 'pending' && (
        <Button
          title="Cancel Booking"
          onPress={() => handleCancelBooking(item.id)}
          color="red"
        />
      )}
    </View>
  );

  return (
    <View style={{ flex: 1, padding: 20 }}>
      <Text style={{ fontSize: 24, marginBottom: 20 }}>My Bookings</Text>

      {loading ? (
        <Text>Loading bookings...</Text>
      ) : bookings.length === 0 ? (
        <Text>No bookings found</Text>
      ) : (
        <FlatList
          data={bookings}
          renderItem={renderBooking}
          keyExtractor={(item) => item.id.toString()}
        />
      )}
    </View>
  );
}
```

---

## 🔧 App Setup

### 1. Create React Native App
```bash
npx react-native init StayGridMobile
cd StayGridMobile
```

### 2. Install Dependencies
```bash
npm install @react-navigation/native @react-navigation/stack
npm install @react-native-async-storage/async-storage
npm install react-native-screens react-native-safe-area-context
```

### 3. Configure Navigation
```javascript
// App.js
import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import LoginScreen from './screens/LoginScreen';
import HomeScreen from './screens/HomeScreen';
import RoomListScreen from './screens/RoomListScreen';
import BookingScreen from './screens/BookingScreen';

const Stack = createStackNavigator();

export default function App() {
  return (
    <NavigationContainer>
      <Stack.Navigator initialRouteName="Login">
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="Home" component={HomeScreen} />
        <Stack.Screen name="RoomList" component={RoomListScreen} />
        <Stack.Screen name="Booking" component={BookingScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}
```

---

## 🧪 Testing Instructions

### 1. Start Symfony Server
```bash
cd /path/to/staygrid
symfony serve --no-tls
```

### 2. Find PC IP
```bash
ipconfig  # Windows
ifconfig  # Linux/Mac
```

### 3. Update API URL
In your `api.js` file, change the base URL to your PC's IP:
```javascript
const API_BASE_URL = 'http://192.168.1.100:8000/api'; // Your IP here
```

### 4. Test Login
Use test credentials from your database.

### 5. Test on Device
- Connect phone to same WiFi as PC
- Use PC IP in API URL
- Test all features

---

## ⚠️ Important Notes

### Session Handling
- **Always use `credentials: 'include'`** in fetch requests
- Login creates a session that persists across app restarts
- If session expires, user needs to login again

### Error Handling
- Check `response.ok` before parsing JSON
- Handle network errors gracefully
- Show user-friendly error messages

### Data Format
- Dates: Use ISO format (`2026-05-01T14:00:00Z`)
- Room references: `/api/room_listings/{id}`
- User references: `/api/log_in_users/{id}`

### Security
- Never store passwords in AsyncStorage
- Only store user profile data
- Clear user data on logout

---

## 📋 API Endpoints Reference

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/login` | Login user |
| POST | `/logout` | Logout user |
| GET | `/room_listings` | Get all rooms |
| GET | `/room_listings/{id}` | Get room details |
| GET | `/bookings` | Get my bookings |
| POST | `/bookings` | Create booking |
| DELETE | `/bookings/{id}` | Cancel booking |

---

## 🚀 Next Steps

1. Set up React Native project
2. Implement API services
3. Create screens and navigation
4. Test with real API
5. Add error handling and loading states
6. Style the app
7. Test on real device

---

## 📞 Support

If you encounter issues:
1. Check that Symfony server is running
2. Verify API URL uses correct PC IP
3. Test endpoints with Postman first
4. Check browser console for errors
5. Ensure `credentials: 'include'` is used

---

**Happy coding!** 🎉
</content>
<parameter name="filePath">c:\Users\Jerv\Documents\Dev\staygrid\staygrid\REACT_NATIVE_PROMPT.md