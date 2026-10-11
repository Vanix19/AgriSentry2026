import { Platform, Share } from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import { API_URL } from '@/constants/config';
import { getToken } from './api';

/** Download authenticated exports without putting credentials in a URL. */
export async function downloadExport(path:string, filename:string, mime='application/pdf') {
  // API paths are relative to /api; attachment links are full web URLs.
  const url=/^https?:\/\//.test(path)?path:API_URL+path;
  const token=await getToken();
  const headers:Record<string,string>={Accept:mime};
  if(token)headers.Authorization=`Bearer ${token}`;
  if(Platform.OS==='web') {
    const response=await fetch(url,{headers});
    if(!response.ok)throw new Error('Could not download this file. Check your access and try again.');
    const blobUrl=URL.createObjectURL(await response.blob()),link=document.createElement('a');
    link.href=blobUrl;link.download=filename;document.body.appendChild(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(blobUrl),10000);return;
  }
  const file=(FileSystem.cacheDirectory||FileSystem.documentDirectory)+filename;
  try {
    const result=await FileSystem.downloadAsync(url,file,{headers});
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
