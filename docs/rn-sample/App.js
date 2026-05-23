import React, { useState } from 'react';
import { SafeAreaView, View, Text, Button, StyleSheet } from 'react-native';
import LoginWebView from './components/LoginWebView';
import ProfileScreen from './screens/ProfileScreen';
import BookingScreen from './screens/BookingScreen';

// Replace with your tunnel host (ngrok/localtunnel)
const TUNNEL_HOST = 'https://abcd1234.ngrok.io';

export default function App() {
  const [loggedIn, setLoggedIn] = useState(false);
  const [route, setRoute] = useState('home');

  const onLoginSuccess = () => {
    setLoggedIn(true);
    setRoute('profile');
  };

  return (
    <SafeAreaView style={styles.container}>
      {!loggedIn ? (
        <View style={styles.fill}>
          <Text style={styles.title}>StayGrid RN Sample — Login</Text>
          <LoginWebView tunnelHost={TUNNEL_HOST} onLoginSuccess={onLoginSuccess} />
        </View>
      ) : (
        <View style={styles.fill}>
          <View style={styles.nav}>
            <Button title="Profile" onPress={() => setRoute('profile')} />
            <Button title="Booking" onPress={() => setRoute('booking')} />
            <Button title="Logout" onPress={() => { setLoggedIn(false); setRoute('home'); }} />
          </View>

          {route === 'profile' && <ProfileScreen tunnelHost={TUNNEL_HOST} />}
          {route === 'booking' && <BookingScreen tunnelHost={TUNNEL_HOST} />}
        </View>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
  fill: { flex: 1 },
  title: { fontSize: 18, fontWeight: '700', textAlign: 'center', margin: 12 },
  nav: { flexDirection: 'row', justifyContent: 'space-around', padding: 8 }
});
