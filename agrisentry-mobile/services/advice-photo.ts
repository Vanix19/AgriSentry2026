import { Platform } from 'react-native';
import * as Clipboard from 'expo-clipboard';
import * as FileSystem from 'expo-file-system/legacy';
import type { ImagePickerAsset } from 'expo-image-picker';

export type AdvicePhoto = { uri: string; name: string; mimeType: string; file?: File; temporary?: boolean };
const MAX_BYTES = 2 * 1024 * 1024;

export function validatePhoto(mimeType: string, size: number) {
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(mimeType) || size > MAX_BYTES) {
    throw new Error('Choose a JPG, PNG, or WebP photo up to 2 MB.');
  }
}

export async function pickerPhoto(asset: ImagePickerAsset): Promise<AdvicePhoto> {
  const mimeType = asset.mimeType || (asset.uri.toLowerCase().endsWith('.png') ? 'image/png' : 'image/jpeg');
  let size = asset.fileSize ?? asset.file?.size;
  if (size === undefined && Platform.OS !== 'web') {
    const info = await FileSystem.getInfoAsync(asset.uri);
    if (info.exists) size = info.size;
  }
  if (size === undefined) throw new Error('Could not read this photo. Please select another picture.');
  validatePhoto(mimeType, size);
  return { uri: asset.uri, name: asset.fileName || 'photo.jpg', mimeType, file: asset.file };
}

export async function clipboardPhoto(): Promise<AdvicePhoto> {
  const image = await Clipboard.getImageAsync({ format: 'jpeg', jpegQuality: 0.85 });
  if (!image) throw new Error('No copied photo found. Copy an image first, or choose one from your photos.');
  const base64 = image.data.split(',')[1];
  if (!base64) throw new Error('Could not read the copied photo.');
  validatePhoto('image/jpeg', Math.ceil(base64.length * 3 / 4));
  if (Platform.OS === 'web') {
    const blob = await (await fetch(image.data)).blob();
    return { uri: image.data, name: 'pasted-photo.jpg', mimeType: 'image/jpeg', file: new File([blob], 'pasted-photo.jpg', { type: 'image/jpeg' }) };
  }
  const uri = `${FileSystem.cacheDirectory}advice-photo-${Date.now()}-${Math.random().toString(36).slice(2)}.jpg`;
  await FileSystem.writeAsStringAsync(uri, base64, { encoding: FileSystem.EncodingType.Base64 });
  return { uri, name: 'pasted-photo.jpg', mimeType: 'image/jpeg', temporary: true };
}

export async function deleteTemporaryPhoto(photo: AdvicePhoto) {
  if (photo.temporary) await FileSystem.deleteAsync(photo.uri, { idempotent: true });
}

export function adviceForm(question: string, photo: AdvicePhoto | null) {
  const body = new FormData();
  if (question) body.append('question', question);
  if (photo) {
    if (Platform.OS === 'web' && photo.file) body.append('photo', photo.file, photo.name);
    else body.append('photo', { uri: photo.uri, name: photo.name, type: photo.mimeType } as unknown as Blob);
  }
  return body;
}
