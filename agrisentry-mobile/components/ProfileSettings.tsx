import React, { useEffect, useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, ActivityIndicator } from 'react-native';
import { apiFetch } from '@/services/api';
import { useAuth } from '@/contexts/auth-context';
import { useAppTheme } from '@/contexts/theme-context';

export default function ProfileSettings() {
  const { refreshUser } = useAuth();
  const { isDark } = useAppTheme();
  const colors={text:isDark?'#F8FAFC':'#111827',muted:isDark?'#98A3B7':'#64748B',card:isDark?'#07121A':'#FFFFFF',border:isDark?'#2A3642':'#E5E7EB'};
  const [profile,setProfile]=useState({name:'',username:'',email:'',phone_number:''});
  const [password,setPassword]=useState('');
  const [loading,setLoading]=useState(true);
  const [busy,setBusy]=useState(false);
  const [message,setMessage]=useState('');
  const [failed,setFailed]=useState(false);
  async function load() {
    setLoading(true);setFailed(false);
    try {const {user}=await apiFetch<{user:{name:string;username:string;email:string;phone_numbers:{phone_number:string;is_primary:boolean}[]}}>('/settings');
      setProfile({name:user.name,username:user.username,email:user.email,phone_number:(user.phone_numbers.find(phone=>phone.is_primary)||user.phone_numbers[0])?.phone_number||''});
    } catch(e){setFailed(true);setMessage(e instanceof Error?e.message:'Could not load your profile.');} finally{setLoading(false);}
  }
  useEffect(()=>{void load();},[]);
  async function save() {
    setBusy(true);setMessage('');
    try {await apiFetch('/settings',{method:'PUT',body:JSON.stringify({...profile,current_password:password})});setPassword('');await refreshUser();setMessage('Your profile has been updated.');}
    catch(e){setMessage(e instanceof Error?e.message:'Could not save your profile.');}finally{setBusy(false);}
  }
  return <View style={{padding:20,borderRadius:18,borderWidth:1,borderColor:colors.border,backgroundColor:colors.card,gap:12}}>
    <Text style={{fontSize:19,fontWeight:'700',color:colors.text}}>Personal information</Text>
    <Text style={{color:colors.muted,lineHeight:20}}>Update your details and recovery contacts. Your role is assigned by the Admin.</Text>
    {loading?<ActivityIndicator color="#16A34A"/>:failed?<TouchableOpacity onPress={load}><Text style={{color:colors.text}}>Retry loading profile</Text></TouchableOpacity>:<>
      {(['name','username','email','phone_number'] as const).map(key=><View key={key} style={{gap:7}}>
        <Text style={{color:colors.muted,fontSize:13,fontWeight:'600'}}>{{name:'Full name',username:'Username',email:'Recovery email',phone_number:'Primary phone number'}[key]}</Text>
        <TextInput accessibilityLabel={key} value={profile[key]} onChangeText={value=>setProfile(current=>({...current,[key]:value}))} autoCapitalize={key==='name'?'words':'none'} keyboardType={key==='email'?'email-address':key==='phone_number'?'phone-pad':'default'} style={{padding:12,borderWidth:1,borderColor:colors.border,borderRadius:9,color:colors.text,fontSize:16}} />
      </View>)}
      <Text style={{color:colors.muted,fontSize:12}}>Leave phone blank to keep your current registered numbers.</Text>
      <Text style={{color:colors.muted,fontSize:13,fontWeight:'600'}}>Confirm with current password</Text><TextInput accessibilityLabel="Current password for profile update" value={password} onChangeText={setPassword} secureTextEntry style={{padding:12,borderWidth:1,borderColor:colors.border,borderRadius:9,color:colors.text,fontSize:16}} />
      <TouchableOpacity disabled={busy} onPress={save} style={{backgroundColor:'#16A34A',padding:14,borderRadius:9,opacity:busy?.6:1}}><Text style={{color:'#fff',fontWeight:'700',textAlign:'center'}}>{busy?'Saving…':'Save profile'}</Text></TouchableOpacity>
    </>}
    {!!message&&<Text accessibilityLiveRegion="polite" style={{color:colors.text}}>{message}</Text>}
  </View>;
}
