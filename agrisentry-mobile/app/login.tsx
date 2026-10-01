import React, { useEffect, useRef, useState } from "react";
import { ActivityIndicator, Animated, Easing, Image, KeyboardAvoidingView, Platform, ScrollView, StatusBar, StyleSheet, Text, TextInput, TouchableOpacity, useWindowDimensions, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { SafeAreaView } from "react-native-safe-area-context";
import { useLocalSearchParams, useRouter } from "expo-router";
import { useAuth } from "@/contexts/auth-context";
import { apiFetch } from "@/services/api";

function useBob(delay: number) {
  const value = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    const loop = Animated.loop(
      Animated.sequence([
        Animated.delay(delay),
        Animated.timing(value, { toValue: -8, duration: 950, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
        Animated.timing(value, { toValue: 0, duration: 950, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      ])
    );
    loop.start();
    return () => loop.stop();
  }, [value, delay]);
  return value;
}

function HeroAnimal({ source, delay, style }: { source: number; delay: number; style?: object }) {
  const translateY = useBob(delay);
  return <Animated.Image source={source} style={[styles.heroAnimal, style, { transform: [{ translateY }] }]} resizeMode="contain" />;
}

function Feature({ icon, title, text }: { icon: keyof typeof Ionicons.glyphMap; title: string; text: string }) {
  return <View style={styles.feature}><Ionicons name={icon} size={24} color="#18833D" /><View style={styles.featureCopy}><Text style={styles.featureTitle}>{title}</Text><Text style={styles.featureText}>{text}</Text></View></View>;
}

function LoginForm({ roleLabel, compact }: { roleLabel?: string; compact?: boolean }) {
  const { login } = useAuth(); const router = useRouter();
  const [username, setUsername] = useState(""); const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false); const [error, setError] = useState<string | null>(null); const [submitting, setSubmitting] = useState(false);
  const [challenge,setChallenge] = useState('');
  const [otp,setOtp] = useState('');
  const [message,setMessage] = useState('');
  const handleLogin = async () => {
    if (submitting) return;
    if (!username.trim() || !password) { setError("Please enter your username and password."); return; }
    setSubmitting(true); setError(null);
    try {
      const result = await apiFetch<{challenge:string;message:string}>('/mobile/login',{method:'POST',body:JSON.stringify({username:username.trim(),password,device_name:'agrisentry-mobile'})});
      setChallenge(result.challenge); setPassword(''); setMessage(result.message);
    } catch (e) { setError(e instanceof Error ? e.message : 'Could not send code.'); }
    finally { setSubmitting(false); }
  };
  const verifyOtp = async (code = otp) => {
    if (submitting || !/^[0-9]{6}$/.test(code)) return;
    setSubmitting(true); setError(null);
    try { await login(challenge,code); }
    catch(e) { setError(e instanceof Error ? e.message : 'Could not verify code.'); setOtp(''); }
    finally { setSubmitting(false); }
  };
  const resend = async () => {
    if (submitting) return;
    setSubmitting(true);setError(null);setMessage('');
    try {
      const result = await apiFetch<{challenge:string;message:string}>('/mobile/login/resend',{method:'POST',body:JSON.stringify({challenge})});
      setChallenge(result.challenge);setOtp('');setMessage(result.message);
    } catch(e) {setError(e instanceof Error ? e.message : 'Could not resend code.');}
    finally {setSubmitting(false);}
  };
  return (
    <View style={[styles.formCard, compact && styles.formCardCompact]}>
      {!!message && <View accessibilityLiveRegion="polite" style={{backgroundColor:'#E9F9EF',borderRadius:10,padding:14,marginBottom:16,flexDirection:'row',gap:10}}><Text style={{color:'#15803D',flex:1}}>{message}</Text><TouchableOpacity accessibilityLabel="Dismiss notification" onPress={()=>setMessage('')}><Ionicons name="close" size={20} color="#15803D" /></TouchableOpacity></View>}
      <Image source={require("@/assets/images/agrisentry-logo-v2-transparent.png")} style={styles.formLogo} resizeMode="contain" />
      <Text style={styles.title}>Sign in to AgriSentry</Text>
      <View style={styles.accessLine}><View style={styles.line} /><Text style={styles.access}>{"Your role is identified automatically"}</Text><View style={styles.line} /></View>
      {error && <View style={styles.errorBox}><Text style={styles.errorText}>{error}</Text></View>}
      {!challenge ? <>
      <Text style={styles.label}>Username</Text>
      <View style={styles.inputRow}><Ionicons name="person-outline" size={21} color="#97A0AA" /><TextInput style={styles.input} placeholder="e.g. admin" placeholderTextColor="#9AA1AA" autoCapitalize="none" autoCorrect={false} value={username} onChangeText={setUsername} /></View>
      <Text style={styles.label}>Password</Text>
      <View style={styles.inputRow}><Ionicons name="lock-closed-outline" size={20} color="#97A0AA" /><TextInput style={styles.input} placeholder="••••••••" placeholderTextColor="#9AA1AA" secureTextEntry={!showPassword} value={password} onChangeText={setPassword} onSubmitEditing={handleLogin} /><TouchableOpacity onPress={() => setShowPassword(!showPassword)} hitSlop={10}><Ionicons name={showPassword ? "eye-off-outline" : "eye-outline"} size={21} color="#97A0AA" /></TouchableOpacity></View>
      <TouchableOpacity style={[styles.loginButton, submitting && styles.loginButtonDisabled]} onPress={handleLogin} disabled={submitting}>{submitting ? <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}><ActivityIndicator color="#FFFFFF" /><Text style={styles.loginButtonText}>Sending code…</Text></View> : <Text style={styles.loginButtonText}>Continue</Text>}</TouchableOpacity>
      </> : <>
      <Text style={styles.label}>Step 2 of 2 - Verify your sign-in</Text>

      <Text style={styles.label}>Six-digit OTP</Text>
      <View style={styles.inputRow}><TextInput style={styles.input} value={otp} editable={!submitting} keyboardType="number-pad" autoComplete="one-time-code" maxLength={6} onChangeText={value=>{const code=value.replace(/[^0-9]/g,'').slice(0,6);setOtp(code);if(code.length===6) void verifyOtp(code);}} /></View>
      <TouchableOpacity style={styles.loginButton} disabled={submitting} onPress={()=>verifyOtp()}><Text style={styles.loginButtonText}>{submitting?'Verifying...':'Verify & sign in'}</Text></TouchableOpacity>
      <TouchableOpacity disabled={submitting} onPress={resend}><Text style={styles.backText}>Resend OTP</Text></TouchableOpacity>
      <TouchableOpacity disabled={submitting} onPress={()=>{setChallenge('');setOtp('');setError(null);setMessage('');}}><Text style={styles.backText}>Back to sign in</Text></TouchableOpacity>
      </>}
      <TouchableOpacity onPress={() => router.push("/account")}><Text style={styles.backText}>Forgot password? Recover with OTP</Text></TouchableOpacity>
    </View>
  );
}

export default function LoginScreen() {
  const { roleLabel } = useLocalSearchParams<{ role?: string; roleLabel?: string }>();
  const { width } = useWindowDimensions(); const desktop = width >= 760;

  if (desktop) {
    return (
      <SafeAreaView style={styles.safeArea}>
        <StatusBar barStyle="dark-content" />
        <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === "ios" ? "padding" : undefined}>
          <View style={styles.layoutDesktop}>
            <View style={styles.brandPanelDesktop}>
              <Image source={require("@/assets/images/farm-hero-v2.png")} style={StyleSheet.absoluteFillObject} resizeMode="cover" />
              <View style={styles.farmOverlay} pointerEvents="none" />
              <View style={styles.brandContent}>
                <Image source={require("@/assets/images/agrisentry-logo-v2-transparent.png")} style={styles.logoImage} resizeMode="contain" />
                <Text style={styles.statement}>Smart monitoring.{"\n"}Healthier herds.{"\n"}<Text style={styles.statementGreen}>Stronger future.</Text></Text>
                <View style={styles.rule} /><Text style={styles.brandDescription}>IoT-powered insights to keep your livestock healthy and productive.</Text>
              </View>
              <View style={[styles.heroLivestock, styles.heroLivestockDesktop]} pointerEvents="none">
                <HeroAnimal source={require("@/assets/images/goat-hero-clear.png")} delay={0} style={styles.heroGoatDesktop} />
                <HeroAnimal source={require("@/assets/images/pig-hero-clear.png")} delay={280} style={styles.heroPigDesktop} />
                <HeroAnimal source={require("@/assets/images/cow-hero-clear.png")} delay={550} style={styles.heroCowDesktop} />
              </View>
              <View style={styles.featureRow}>
                <Feature icon="shield-checkmark-outline" title="Secure" text="Your data is always protected" />
                <Feature icon="wifi-outline" title="Connected" text="Real-time IoT monitoring" />
                <Feature icon="bar-chart-outline" title="Insightful" text="Actionable insights for better decisions" />
              </View>
            </View>
            <View style={styles.formPanelDesktop}>
              <View style={styles.ring} pointerEvents="none" />
              <LoginForm roleLabel={roleLabel} />
            </View>
          </View>
        </KeyboardAvoidingView>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar barStyle="dark-content" />
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === "ios" ? "padding" : undefined}>
        <ScrollView contentContainerStyle={styles.mobileScroll} keyboardShouldPersistTaps="handled">
          <View style={styles.mobileHero}>
            <Image source={require("@/assets/images/farm-hero-v2.png")} style={StyleSheet.absoluteFillObject} resizeMode="cover" />
            <View style={styles.farmOverlay} pointerEvents="none" />
            <Image source={require("@/assets/images/agrisentry-logo-v2-transparent.png")} style={styles.mobileLogo} resizeMode="contain" />
            <Text style={styles.mobileTitle}>AgriSentry</Text>
            <Text style={styles.mobileTagline}>Smart monitoring. Healthier herds. <Text style={styles.statementGreen}>Stronger future.</Text></Text>
          </View>

          <View style={styles.mobileAnimalsRow}>
            <HeroAnimal source={require("@/assets/images/goat-hero-clear.png")} delay={0} style={styles.heroGoatMobile} />
            <HeroAnimal source={require("@/assets/images/pig-hero-clear.png")} delay={280} style={styles.heroPigMobile} />
            <HeroAnimal source={require("@/assets/images/cow-hero-clear.png")} delay={550} style={styles.heroCowMobile} />
          </View>

          <View style={styles.mobileFeatureRow}>
            <Feature icon="shield-checkmark-outline" title="Secure" text="Your data is always protected" />
            <Feature icon="wifi-outline" title="Connected" text="Real-time IoT monitoring" />
            <Feature icon="bar-chart-outline" title="Insightful" text="Actionable insights for better decisions" />
          </View>

          <LoginForm roleLabel={roleLabel} compact />
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: "#F8FBF9" },
  flex: { flex: 1 },

  /* ---- mobile (stacked, scrollable — nothing absolutely overlaid on text) ---- */
  mobileScroll: { flexGrow: 1, paddingBottom: 32 },
  mobileHero: { minHeight: 220, alignItems: "center", justifyContent: "center", paddingVertical: 28, paddingHorizontal: 24, overflow: "hidden" },
  mobileLogo: { width: 64, height: 64, marginBottom: 10 },
  mobileTitle: { fontSize: 24, fontWeight: "800", color: "#082B29", marginBottom: 6 },
  mobileTagline: { fontSize: 15, lineHeight: 21, fontWeight: "700", color: "#082B29", textAlign: "center", maxWidth: 320 },
  mobileAnimalsRow: { flexDirection: "row", alignItems: "flex-end", justifyContent: "center", gap: 18, paddingVertical: 14, backgroundColor: "#F2F8F1" },
  mobileFeatureRow: { paddingHorizontal: 20, paddingVertical: 16, backgroundColor: "#F2F8F1", gap: 12 },

  /* ---- desktop split-screen ---- */
  layoutDesktop: { flex: 1, flexDirection: "row" },
  brandPanelDesktop: { flex: 0.92, overflow: "hidden", backgroundColor: "#F2F8F1", justifyContent: "center" },
  farmOverlay: { ...StyleSheet.absoluteFillObject, backgroundColor: "rgba(245,251,247,0.42)" },
  brandContent: { paddingHorizontal: 44, paddingVertical: 28, maxWidth: 460 },
  logoImage: { width: 94, height: 94, marginBottom: 22 },
  statement: { fontSize: 32, lineHeight: 43, fontWeight: "800", color: "#082B29", letterSpacing: -0.7 },
  statementGreen: { color: "#249148" },
  rule: { width: 44, height: 2, backgroundColor: "#249148", marginTop: 28, marginBottom: 22 },
  brandDescription: { maxWidth: 260, fontSize: 15, lineHeight: 23, color: "#62707C" },
  heroLivestock: { flexDirection: "row", alignItems: "flex-end", justifyContent: "space-around" },
  heroLivestockDesktop: { position: "absolute", left: 20, right: 20, bottom: 118, height: 220 },
  heroAnimal: { height: "100%" },
  heroGoatMobile: { width: 78, height: 90 }, heroPigMobile: { width: 66, height: 76 }, heroCowMobile: { width: 88, height: 96 },
  heroGoatDesktop: { width: 165 }, heroPigDesktop: { width: 145 }, heroCowDesktop: { width: 190 },
  featureRow: { position: "absolute", left: 28, right: 28, bottom: 24, padding: 16, borderRadius: 12, backgroundColor: "rgba(255,255,255,0.68)", flexDirection: "row", justifyContent: "space-between" },
  feature: { flex: 1, flexDirection: "row", gap: 8, paddingHorizontal: 5 }, featureCopy: { flex: 1 }, featureTitle: { color: "#173632", fontSize: 12, fontWeight: "800" }, featureText: { color: "#65726F", fontSize: 9, lineHeight: 13, marginTop: 3 },
  formPanelDesktop: { flex: 1.08, backgroundColor: "#FCFDFC", alignItems: "center", justifyContent: "center", padding: 24, overflow: "hidden" },
  ring: { position: "absolute", width: 360, height: 360, borderRadius: 180, borderWidth: 1, borderColor: "#DDECE0", right: -145, top: "23%" },

  /* ---- shared form card ---- */
  formCard: { width: "100%", maxWidth: 540, backgroundColor: "rgba(255,255,255,0.94)", borderRadius: 22, padding: 30, alignItems: "stretch", shadowColor: "#153A25", shadowOpacity: 0.07, shadowRadius: 22, shadowOffset: { width: 0, height: 9 }, elevation: 3 },
  formCardCompact: { alignSelf: "center", width: "90%", marginHorizontal: 20, marginTop: 8, padding: 24 },
  formLogo: { width: 72, height: 72, alignSelf: "center", marginBottom: 12 },
  title: { color: "#082B29", fontSize: 24, lineHeight: 30, fontWeight: "800", textAlign: "center" },
  accessLine: { flexDirection: "row", alignItems: "center", gap: 12, marginTop: 9, marginBottom: 22 },
  line: { height: 1, flex: 1, backgroundColor: "#B8DABF" }, access: { color: "#63707A", fontSize: 14 },
  errorBox: { backgroundColor: "#FDEAEA", borderRadius: 9, padding: 10, marginBottom: 14 }, errorText: { color: "#B42318", fontSize: 13, fontWeight: "600" },
  label: { color: "#142E2C", fontSize: 14, fontWeight: "800", marginBottom: 8 },
  inputRow: { height: 54, flexDirection: "row", alignItems: "center", gap: 10, borderRadius: 10, borderWidth: 1, borderColor: "#DEE4E1", backgroundColor: "#FBFCFB", paddingHorizontal: 14, marginBottom: 17 },
  input: { flex: 1, height: "100%", color: "#142E2C", fontSize: 16 },
  loginButton: { height: 51, borderRadius: 9, backgroundColor: "#14943C", alignItems: "center", justifyContent: "center", marginTop: 3, shadowColor: "#14943C", shadowOpacity: 0.2, shadowRadius: 8, elevation: 2 },
  loginButtonDisabled: { opacity: 0.65 }, loginButtonText: { color: "#FFFFFF", fontSize: 16, fontWeight: "800" },
  or: { textAlign: "center", color: "#737C85", fontSize: 14, marginVertical: 18 },
  backLink: { alignSelf: "center", flexDirection: "row", alignItems: "center", gap: 8, padding: 3 }, backText: { color: "#248D43", fontSize: 15, fontWeight: "700" },
});
