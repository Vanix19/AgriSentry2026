import { Platform, Share } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import { API_URL } from '@/constants/config';
import { getToken } from './api';

/** Download authenticated exports without putting credentials in a URL. */
export async function downloadExport(path:string, filename:string, mime='application/pdf') {
  const token=await getToken();
  const headers:Record<string,string>={'ngrok-skip-browser-warning':'true',Accept:mime};
  if(token)headers.Authorization=`Bearer ${token}`;
  if(Platform.OS==='web') {
    const response=await fetch(API_URL+path,{headers});
    if(!response.ok)throw new Error('Could not download this file. Check your access and try again.');
    const url=URL.createObjectURL(await response.blob()),link=document.createElement('a');
    link.href=url;link.download=filename;document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),10000);return;
  }
  const file=(FileSystem.cacheDirectory||FileSystem.documentDirectory)+filename;
  try {
    const result=await FileSystem.downloadAsync(API_URL+path,file,{headers});
    if(result.status!==200)throw new Error('Could not download this file. Check your access and try again.');
    if(Platform.OS==='android') {
      const permission=await FileSystem.StorageAccessFramework.requestDirectoryPermissionsAsync();
      if(!permission.granted)return;
      const destination=await FileSystem.StorageAccessFramework.createFileAsync(permission.directoryUri,filename,mime);
      const contents=await FileSystem.readAsStringAsync(file,{encoding:FileSystem.EncodingType.Base64});
      await FileSystem.writeAsStringAsync(destination,contents,{encoding:FileSystem.EncodingType.Base64});
    } else await Share.share({url:file,title:filename});
  } finally { await FileSystem.deleteAsync(file,{idempotent:true}).catch(()=>undefined); }
}
