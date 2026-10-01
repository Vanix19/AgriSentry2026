import { DarkTheme, DefaultTheme, ThemeProvider } from '@react-navigation/native';
import { Stack, useRouter } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { useEffect, useRef } from 'react';
import { ActivityIndicator, View } from 'react-native';
import 'react-native-reanimated';

import { AuthProvider, useAuth } from '@/contexts/auth-context';
import { AppThemeProvider, useAppTheme } from '@/contexts/theme-context';

export const unstable_settings = {
  anchor: 'login',
};

function RootNavigator() {
  const { user, loading } = useAuth();
  const router = useRouter();
  const wasAuthenticated = useRef(false);

  useEffect(() => {
    if (user) {
      const firstSession = !wasAuthenticated.current;
      wasAuthenticated.current = true;
      if (firstSession) router.replace(user.password_change_required ? '/account' : '/species');
    }
  }, [user, router]);

  // Web logout goes straight to /login (not the species picker). Mirror that here —
  // but only *after* the logged-out state has actually committed, in a separate effect
  // tick. Navigating synchronously right after clearing the session races
  // Stack.Protected's guard re-evaluation, which can silently swallow the navigation.
  useEffect(() => {
    if (!loading && !user && wasAuthenticated.current) {
      wasAuthenticated.current = false;
      router.replace('/login');
    }
  }, [user, loading, router]);

  if (loading) {
    return (
      <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: '#F8FAFC' }}>
        <ActivityIndicator size="large" color="#16A34A" />
      </View>
    );
  }

  return (
    <Stack>
      <Stack.Protected guard={!user}>
        <Stack.Screen name="login" options={{ headerShown: false }} />
        <Stack.Screen name="role" options={{ headerShown: false }} />
      </Stack.Protected>
      <Stack.Protected guard={!!user}>
        <Stack.Screen name="species" options={{ headerShown: false }} />
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
        <Stack.Screen name="goat/[id]" options={{ headerShown: false }} />
        <Stack.Screen name="collars" options={{ headerShown: false }} />
        <Stack.Screen name="reports" options={{ headerShown: false }} />
        <Stack.Screen name="medical-records" options={{ headerShown: false }} />
      </Stack.Protected>
      <Stack.Screen name="account" options={{ headerShown: false }} />
    </Stack>
  );
}

function ThemedRoot() {
  const { isDark } = useAppTheme();
  return (
    <ThemeProvider value={isDark ? DarkTheme : DefaultTheme}>
      <AuthProvider>
        <RootNavigator />
      </AuthProvider>
      <StatusBar style={isDark ? "light" : "dark"} />
    </ThemeProvider>
  );
}

export default function RootLayout() {
  return (
    <AppThemeProvider>
      <ThemedRoot />
    </AppThemeProvider>
  );
}
