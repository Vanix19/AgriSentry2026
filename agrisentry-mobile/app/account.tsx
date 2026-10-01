import React, { useState } from 'react';
import ProfileSettings from '@/components/ProfileSettings';
import { useAppTheme } from '@/contexts/theme-context';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ScrollView, View, Text, TextInput, TouchableOpacity, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuth } from '@/contexts/auth-context';
import { apiFetch } from '@/services/api';

export default function AccountScreen() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const { isDark } = useAppTheme();
  const styles = makeStyles(isDark);
  const [section,setSection]=useState<"profile"|"security">(user?.password_change_required?"security":"profile");
  const [username, setUsername] = useState('');
  const [channel, setChannel] = useState('email');
  const [otp, setOtp] = useState('');
  const [current, setCurrent] = useState('');
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  const [recoveryStep,setRecoveryStep]=useState<'request'|'verify'|'reset'|'done'>('request');
  const [resetToken,setResetToken]=useState('');
  async function submit(sendCode = false, code = otp) {
    if (busy) return;
    setBusy(true); setMessage('');
    try {
      const endpoint = sendCode ? 'otp' : user ? 'change' : recoveryStep==='verify' ? 'verify' : 'reset';
      const result = await apiFetch<{message:string;reset_token?:string}>('/password/'+endpoint, {method:'POST',body:JSON.stringify({username,channel,otp:code,reset_token:resetToken,current_password:current,password,password_confirmation:confirmation})});
      setMessage(result.message);
      if(sendCode) {setRecoveryStep('verify');setOtp('');setResetToken('');}
      else if(!user && recoveryStep==='verify') {setResetToken(result.reset_token!);setRecoveryStep('reset');setOtp('');}
      else if(!user) setRecoveryStep('done');
      if (!sendCode) {setPassword('');setConfirmation('');setCurrent('');if(user) await logout();}
    } catch(e) {setMessage(e instanceof Error ? e.message : 'Could not update password.');}
    finally {setBusy(false);}
  }
  return <SafeAreaView style={styles.safeArea}><ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
    <TouchableOpacity onPress={()=>router.replace(user?'/species':'/login')}><Text style={styles.link}>← {user?'Livestock selection':'Sign in'}</Text></TouchableOpacity>
    <Text style={styles.title}>{user ? 'Settings' : 'Recover your account'}</Text>
    {user && <><Text style={styles.label}>Manage your profile and account security.</Text><View style={{flexDirection:"row",gap:10}}>{(["profile","security"] as const).map(tab=><TouchableOpacity key={tab} onPress={()=>setSection(tab)} style={[styles.button,{flex:1,opacity:section===tab?1:0.6}]}><Text style={styles.buttonText}>{tab==="profile"?"Personal information":"Password & security"}</Text></TouchableOpacity>)}</View>{section==="profile"&&<ProfileSettings />}</>}
    {(!user||section==="security")&&<View style={styles.card}><Text style={styles.sectionTitle}>{user?'Password & security':'Password recovery'}</Text>
    {user?.password_change_required && <Text style={styles.label}>Your Admin provided your initial password. You can set your own password now.</Text>}
    {!user && recoveryStep==='request' && <>
      <Text style={styles.label}>Step 1 of 3 - Request a code</Text>
      <Text style={styles.label}>Username</Text><TextInput style={styles.input} value={username} onChangeText={setUsername} autoCapitalize="none" />
      <TouchableOpacity accessibilityRole="button" onPress={()=>setChannel(channel==='email'?'phone':'email')}><Text style={styles.link}>Send to: {channel==='email'?'Registered email (Gmail)':'Registered phone'} — tap to change</Text></TouchableOpacity>
      <TouchableOpacity style={styles.button} disabled={busy} onPress={()=>submit(true)}><Text style={styles.buttonText}>Send OTP</Text></TouchableOpacity>
    </>}
    {!user && recoveryStep==='verify' && <>
      <Text style={styles.label}>Step 2 of 3 · Enter the code for {username}</Text>
      <Text style={styles.label}>Six-digit OTP</Text><TextInput style={styles.input} value={otp} editable={!busy} onChangeText={value=>{const code=value.replace(/[^0-9]/g,'').slice(0,6);setOtp(code);if(code.length===6) void submit(false,code);}} keyboardType="number-pad" maxLength={6} autoComplete="one-time-code" />
      <TouchableOpacity style={styles.button} disabled={busy || otp.length!==6} onPress={()=>submit()}><Text style={styles.buttonText}>Verify OTP</Text></TouchableOpacity>
    </>}
    {(!!user || recoveryStep==='reset') && <>
    {!user && <Text style={styles.label}>Step 3 of 3 - Set your new password</Text>}
    {user && <><Text style={styles.label}>Current password</Text><TextInput style={styles.input} value={current} onChangeText={setCurrent} secureTextEntry /></>}
    <Text style={styles.label}>New password (at least 8 characters)</Text><TextInput style={styles.input} value={password} onChangeText={setPassword} secureTextEntry />
    <Text style={styles.label}>Confirm new password</Text><TextInput style={styles.input} value={confirmation} onChangeText={setConfirmation} secureTextEntry />
    <TouchableOpacity style={styles.button} disabled={busy} onPress={()=>submit()}><Text style={styles.buttonText}>{busy?'Please wait…':'Save password'}</Text></TouchableOpacity>
    </>}
    <Text style={styles.label} accessibilityLiveRegion="polite">{message}</Text>
    {!user && recoveryStep==='verify' && <>
      <TouchableOpacity disabled={busy} onPress={()=>submit(true)}><Text style={styles.link}>Resend OTP</Text></TouchableOpacity>
      <TouchableOpacity disabled={busy} onPress={()=>{setRecoveryStep('request');setResetToken('');setOtp('');setPassword('');setConfirmation('');setMessage('');}}><Text style={styles.link}>Change account or delivery method</Text></TouchableOpacity>
    </>}
    {!user && recoveryStep==='reset' && <TouchableOpacity disabled={busy} onPress={()=>{setRecoveryStep('request');setResetToken('');setPassword('');setConfirmation('');setMessage('');}}><Text style={styles.link}>Start again</Text></TouchableOpacity>}
    {!user && recoveryStep==='done' && <TouchableOpacity style={styles.button} onPress={()=>router.replace('/login')}><Text style={styles.buttonText}>Back to sign in</Text></TouchableOpacity>}
    </View>}
  </ScrollView></SafeAreaView>;
}
const makeStyles=(dark:boolean)=>StyleSheet.create({
safeArea:{flex:1,backgroundColor:dark?'#020B12':'#F8FAFC'},container:{flexGrow:1,width:'100%',maxWidth:760,alignSelf:'center',padding:20,paddingBottom:48,gap:16},title:{fontSize:28,fontWeight:'800',color:dark?'#F8FAFC':'#111827'},sectionTitle:{fontSize:19,fontWeight:'700',color:dark?'#F8FAFC':'#111827'},label:{fontSize:13,color:dark?'#98A3B7':'#64748B',lineHeight:20},card:{backgroundColor:dark?'#07121A':'#FFFFFF',borderWidth:1,borderColor:dark?'#2A3642':'#E5E7EB',borderRadius:18,padding:20,gap:12},input:{borderWidth:1,borderColor:dark?'#2A3642':'#CBD5E1',color:dark?'#F8FAFC':'#111827',borderRadius:9,padding:12,fontSize:16},button:{backgroundColor:'#16A34A',padding:14,borderRadius:9},buttonText:{color:'white',textAlign:'center',fontWeight:'700'},link:{color:dark?'#4ADE80':'#16A34A',fontWeight:'600',paddingVertical:8}});
