const MAX_FILE_SIZE = 502400 // 500KB

export const validFileExtensions: Record<string, string[]> = { image: ['jpg', 'gif', 'png', 'jpeg', 'svg', 'webp'] }

export function isValidFileType (fileName: string, fileType: keyof typeof validFileExtensions): boolean {
  let result = true
  if (fileName !== undefined) {
    const allowedExtensions = validFileExtensions[fileType]
    const fileExtension = fileName.split('.').pop()?.toLowerCase()
    result = allowedExtensions.includes(fileExtension ?? '')
  }
  return result
}

export function isValidFileSize (fileSize: number): boolean {
  return fileSize <= MAX_FILE_SIZE
}
