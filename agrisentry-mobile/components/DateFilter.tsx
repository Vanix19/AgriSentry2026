import React, { useState } from "react";
import { Modal, View, Text, TouchableOpacity } from "react-native";
import { useAppTheme } from "@/contexts/theme-context";

export default function DateFilter({ value, onChange, label = "Specific date" }: { value: string; onChange: (date: string) => void; label?: string }) {
  const { isDark } = useAppTheme();
  const [open, setOpen] = useState(false);
  const [month, setMonth] = useState(new Date());
  const color = isDark ? "#F8FAFC" : "#17251D";
  const button = { padding: 12, borderRadius: 8, borderWidth: 1, borderColor: isDark ? "#64748B" : "#94A3B8" };
  const days = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
  return <View style={{ marginVertical: 12 }}>
    <TouchableOpacity style={button} onPress={() => {setMonth(value ? new Date(value + "T00:00:00") : new Date());setOpen(true);}}><Text style={{color}}>{label}: {value || "Choose date"}</Text></TouchableOpacity>
    {value ? <TouchableOpacity style={{padding:10}} onPress={() => onChange("")}><Text style={{color}}>Clear date</Text></TouchableOpacity> : null}
    <Modal visible={open} transparent onRequestClose={() => setOpen(false)} animationType="fade">
      <View style={{flex:1, justifyContent:"center", padding:20, backgroundColor:"rgba(0,0,0,0.5)"}}><View style={{padding:16,borderRadius:16,backgroundColor:isDark ? "#09141D" : "#FFFFFF"}}>
        <View style={{flexDirection:"row",justifyContent:"space-between",alignItems:"center"}}>
          <TouchableOpacity accessibilityLabel="Previous month" style={button} onPress={() => setMonth(new Date(month.getFullYear(),month.getMonth()-1,1))}><Text style={{color}}>‹</Text></TouchableOpacity>
          <Text style={{color,fontWeight:"700"}}>{month.toLocaleDateString(undefined,{month:"long",year:"numeric"})}</Text>
          <TouchableOpacity accessibilityLabel="Next month" style={button} onPress={() => setMonth(new Date(month.getFullYear(),month.getMonth()+1,1))}><Text style={{color}}>›</Text></TouchableOpacity>
        </View>
        <View style={{flexDirection:"row",flexWrap:"wrap",marginVertical:16}}>
          {["Su","Mo","Tu","We","Th","Fr","Sa"].map(day => <Text key={day} style={{width:"14.28%",textAlign:"center",color,paddingVertical:8}}>{day}</Text>)}
          {Array.from({length:new Date(month.getFullYear(),month.getMonth(),1).getDay()},(_,i) => <View key={`blank${i}`} style={{width:"14.28%"}} />)}
          {Array.from({length:days},(_,i) => i+1).map(day => {const date = `${month.getFullYear()}-${String(month.getMonth()+1).padStart(2,"0")}-${String(day).padStart(2,"0")}`;return <TouchableOpacity key={day} accessibilityLabel={date} accessibilityState={{selected:value === date}} style={{width:"14.28%",paddingVertical:12,backgroundColor:value === date ? "#166534" : "transparent",borderRadius:8}} onPress={() => {onChange(date);setOpen(false);}}><Text style={{textAlign:"center",color:value === date ? "#FFFFFF" : color}}>{day}</Text></TouchableOpacity>;})}
        </View>
        <TouchableOpacity style={button} onPress={() => setOpen(false)}><Text style={{color,textAlign:"center"}}>Cancel</Text></TouchableOpacity>
      </View></View>
    </Modal>
  </View>;
}
