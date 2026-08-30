import { launchCamera } from 'react-native-image-picker';
import { pick, types, isErrorWithCode, errorCodes } from '@react-native-documents/picker';
import { PickedFile } from '../api/vendor';

/**
 * Lets a vendor supply a document either as a real file (PDF or image, via
 * the system document/file picker) or a camera photo of a physical
 * document — the two ways people actually have an ID/business-registration
 * document handy. Returns null if the user cancelled, never throws for
 * that case (only for a genuine picker failure).
 */
export async function pickDocumentFile(): Promise<PickedFile | null> {
  try {
    const [file] = await pick({ type: [types.pdf, types.images], allowMultiSelection: false });
    if (!file?.uri) return null;
    return { uri: file.uri, name: file.name ?? 'document', type: file.type ?? 'application/octet-stream' };
  } catch (error) {
    if (isErrorWithCode(error) && error.code === errorCodes.OPERATION_CANCELED) return null;
    throw error;
  }
}

export function takeDocumentPhoto(): Promise<PickedFile | null> {
  return new Promise(resolve => {
    launchCamera({ mediaType: 'photo' }, response => {
      const asset = response.assets?.[0];
      if (!asset?.uri) { resolve(null); return; }
      resolve({ uri: asset.uri, name: asset.fileName ?? 'photo.jpg', type: asset.type ?? 'image/jpeg' });
    });
  });
}
