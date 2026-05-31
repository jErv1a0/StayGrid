import React, { useState } from 'react';
import { SafeAreaView, View, Text, Button, StyleSheet, TextInput, Alert, ActivityIndicator } from 'react-native';
import ProfileScreen from './screens/ProfileScreen';
import BookingScreen from './screens/BookingScreen';
import { setAccessToken } from './apiClient';

// Replace with your tunnel host (ngrok/localtunnel)
const TUNNEL_HOST = 'https://abcd1234.ngrok.io';

export default function App() {
  const [loggedIn, setLoggedIn] = useState(false);
  const [route, setRoute] = useState('home');
  const [email, setEmail] = useState('test@example.com');
  const [password, setPassword] = useState('');
  const [authToken, setAuthToken] = useState('');
  const [loggingIn, setLoggingIn] = useState(false);

  const handleLogin = async () => {
    setLoggingIn(true);

    try {
      const response = await fetch(`${TUNNEL_HOST}/api/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      const data = await response.json();

      if (!response.ok) {
        Alert.alert('Login failed', data.error || 'Unable to authenticate');
        return;
      }

      setAuthToken(data.access_token);
      setAccessToken(data.access_token);
      setLoggedIn(true);
      setRoute('profile');
    } catch (error) {
      Alert.alert('Network error', error.message);
    } finally {
      setLoggingIn(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      {!loggedIn ? (
        <View style={styles.fill}>
          <Text style={styles.title}>StayGrid RN Sample</Text>
          <Text style={styles.subtitle}>Bearer token login against the Symfony API</Text>
          <Text style={styles.label}>Email</Text>
          <TextInput value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" style={styles.input} />
          <Text style={styles.label}>Password</Text>
          <TextInput value={password} onChangeText={setPassword} secureTextEntry style={styles.input} />
          {loggingIn ? <ActivityIndicator style={styles.spinner} /> : <Button title="Login" onPress={handleLogin} />}
        </View>
      ) : (
        <View style={styles.fill}>
          <View style={styles.nav}>
            <Button title="Profile" onPress={() => setRoute('profile')} />
            <Button title="Booking" onPress={() => setRoute('booking')} />
            <Button title="Logout" onPress={() => { setLoggedIn(false); setRoute('home'); setAuthToken(''); setAccessToken(''); }} />
          </View>

          {route === 'profile' && <ProfileScreen tunnelHost={TUNNEL_HOST} authToken={authToken} />}
          {route === 'booking' && <BookingScreen tunnelHost={TUNNEL_HOST} authToken={authToken} />}
        </View>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
  fill: { flex: 1, padding: 16 },
  title: { fontSize: 22, fontWeight: '700', textAlign: 'center', marginTop: 12 },
  subtitle: { fontSize: 14, textAlign: 'center', marginBottom: 20, color: '#555' },
  label: { fontSize: 14, marginBottom: 6, fontWeight: '600' },
  input: { borderWidth: 1, borderColor: '#ddd', padding: 10, marginBottom: 14, borderRadius: 8 },
  spinner: { marginVertical: 12 },
  nav: { flexDirection: 'row', justifyContent: 'space-around', padding: 8 }
});
