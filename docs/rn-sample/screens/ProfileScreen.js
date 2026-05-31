import React, { useEffect, useState } from 'react';
import { View, Text, Button, StyleSheet, ActivityIndicator } from 'react-native';

export default function ProfileScreen({ tunnelHost, authToken }) {
  const [profile, setProfile] = useState(null);
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

  useEffect(() => { fetchProfile(); }, []);

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
      <Button title="Refresh" onPress={fetchProfile} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { padding: 16 },
  title: { fontSize: 18, fontWeight: '700', marginBottom: 12 }
});
