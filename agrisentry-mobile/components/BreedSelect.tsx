import React, { useState } from 'react';
import { View, Text, TouchableOpacity, ScrollView } from 'react-native';

export default function BreedSelect({value,onChange}:{value:string;onChange:(value:string)=>void}) {
  const [open,setOpen]=useState(false);
  return <View>
    <TouchableOpacity accessibilityRole="button" accessibilityLabel="Breed" accessibilityState={{expanded:open}} onPress={()=>setOpen(!open)} style={{padding:14,borderWidth:1,borderColor:'#94a3b8',borderRadius:8,backgroundColor:'#fff'}}><Text>{value || 'Select breed'} ▾</Text></TouchableOpacity>
    {open && <ScrollView style={{maxHeight:180,backgroundColor:'#fff'}} nestedScrollEnabled>{['Boer','Anglo-Nubian','Saanen','Alpine','Toggenburg','Native','Crossbreed','Other'].map(breed=><TouchableOpacity key={breed} onPress={()=>{onChange(breed);setOpen(false);}} style={{padding:12}}><Text>{breed}</Text></TouchableOpacity>)}</ScrollView>}
  </View>;
}
