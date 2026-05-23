import React from 'react';
import { Platform, View, StyleSheet } from 'react-native';
import { WebView } from 'react-native-webview';

export default function LoginWebView({ tunnelHost, onLoginSuccess }) {
  const loginUrl = `${tunnelHost}/login`;

  const handleNavigationStateChange = (navState) => {
    const { url } = navState;
    // Adjust these checks depending on your app's post-login redirect
    if (url.startsWith(`${tunnelHost}/user/profile`) || url.includes('connect/google/check')) {
      onLoginSuccess && onLoginSuccess();
    }
  };

  return (
    <View style={styles.fill}>
      <WebView
        source={{ uri: loginUrl }}
        onNavigationStateChange={handleNavigationStateChange}
        javaScriptEnabled
        domStorageEnabled
        sharedCookiesEnabled={true}
        thirdPartyCookiesEnabled={true}
        originWhitelist={["*"]}
      />
    </View>
  );
}

const styles = StyleSheet.create({ fill: { flex: 1 } });
