import React, { useState } from 'react';
import { View, Text, TextInput, Button, StyleSheet, Alert } from 'react-native';

export default function BookingScreen({ tunnelHost }) {
  const [roomId, setRoomId] = useState('101');
  const [startDate, setStartDate] = useState('2026-06-01');
  const [endDate, setEndDate] = useState('2026-06-05');
  const [guests, setGuests] = useState('2');

  const createBooking = async () => {
    try {
      const resp = await fetch(`${tunnelHost}/api/bookings`, {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ roomId: parseInt(roomId, 10), startDate, endDate, guests: parseInt(guests, 10) })
      });
      if (resp.status === 201 || resp.ok) {
        const data = await resp.json();
        Alert.alert('Success', `Booking created: ${JSON.stringify(data)}`);
      } else {
        const text = await resp.text();
        Alert.alert('Error', `Status ${resp.status}: ${text}`);
      }
    } catch (e) {
      Alert.alert('Network error', e.message);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Create Booking</Text>
      <Text>Room ID</Text>
      <TextInput value={roomId} onChangeText={setRoomId} style={styles.input} />
      <Text>Start Date (YYYY-MM-DD)</Text>
      <TextInput value={startDate} onChangeText={setStartDate} style={styles.input} />
      <Text>End Date (YYYY-MM-DD)</Text>
      <TextInput value={endDate} onChangeText={setEndDate} style={styles.input} />
      <Text>Guests</Text>
      <TextInput value={guests} onChangeText={setGuests} keyboardType="numeric" style={styles.input} />
      <Button title="Create Booking" onPress={createBooking} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { padding: 16 },
  title: { fontSize: 18, fontWeight: '700', marginBottom: 12 },
  input: { borderWidth: 1, borderColor: '#ddd', padding: 8, marginBottom: 8 }
});
