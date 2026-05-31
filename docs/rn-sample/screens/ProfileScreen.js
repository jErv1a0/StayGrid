import React, { useEffect, useState } from 'react';
import { View, Text, Button, StyleSheet, ActivityIndicator, FlatList } from 'react-native';
import { getBookings } from '../apiClient';

export default function ProfileScreen({ tunnelHost, authToken }) {
  const [profile, setProfile] = useState(null);
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(false);

  const fetchProfile = async () => {
    setLoading(true);
    try {
      const resp = await fetch(`${tunnelHost}/api/user/profile`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${authToken}`,
        }
      });
      const data = await resp.json();

      if (resp.ok) {
        setProfile(data.user);
      } else {
        setProfile({ error: resp.status, body: data.error || JSON.stringify(data) });
      }
    } catch (e) {
      setProfile({ error: 'network', message: e.message });
    }
    setLoading(false);
  };

  const fetchBookings = async () => {
    try {
      const data = await getBookings();
      setBookings(Array.isArray(data) ? data : data.data || []);
    } catch (e) {
      setBookings([{ error: e.message }]);
    }
  };

  useEffect(() => {
    fetchProfile();
    fetchBookings();
  }, []);

  if (loading) return <ActivityIndicator style={{ marginTop: 20 }} />;

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Profile</Text>
      {profile ? (
        <View>
          <Text>ID: {profile.id}</Text>
          <Text>Email: {profile.email}</Text>
          <Text>Name: {profile.fullName}</Text>
          <Text>Verified: {String(profile.isVerified)}</Text>
        </View>
      ) : (
        <Text>No profile loaded</Text>
      )}
      <Text style={styles.sectionTitle}>My Bookings</Text>
      {bookings.length > 0 ? (
        <FlatList
          data={bookings}
          keyExtractor={(item, index) => String(item.id ?? index)}
          renderItem={({ item }) => (
            <View style={styles.bookingCard}>
              <Text style={styles.bookingTitle}>Booking #{item.id}</Text>
              <Text>Room: {item.room_name || item.room_id || 'N/A'}</Text>
              <Text>Check-in: {item.check_in || 'N/A'}</Text>
              <Text>Check-out: {item.check_out || 'N/A'}</Text>
              <Text>Guests: {item.guests ?? 'N/A'}</Text>
              <Text>Status: {item.status || 'N/A'}</Text>
            </View>
          )}
        />
      ) : (
        <Text>No bookings yet</Text>
      )}
      <Button title="Refresh" onPress={fetchProfile} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { padding: 16 },
  title: { fontSize: 18, fontWeight: '700', marginBottom: 12 },
  sectionTitle: { fontSize: 16, fontWeight: '700', marginTop: 16, marginBottom: 8 },
  bookingCard: { borderWidth: 1, borderColor: '#ddd', borderRadius: 8, padding: 10, marginBottom: 10 },
  bookingTitle: { fontWeight: '700', marginBottom: 4 },
});
