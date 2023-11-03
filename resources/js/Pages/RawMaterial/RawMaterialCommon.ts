import * as Yup from 'yup'

const MAX_FILE_SIZE = 502400 // 500KB

const validFileExtensions: Record<string, string[]> = { image: ['jpg', 'gif', 'png', 'jpeg', 'svg', 'webp'] }

function isValidFileType (fileName: string, fileType: keyof typeof validFileExtensions): boolean {
  let result = true
  if (fileName !== undefined) {
    const allowedExtensions = validFileExtensions[fileType]
    const fileExtension = fileName.split('.').pop()?.toLowerCase()
    result = allowedExtensions.includes(fileExtension ?? '')
  }
  return result
}

function isValidFileSize (fileSize: number): boolean {
  return fileSize <= MAX_FILE_SIZE
}

export const rawMaterialSchema = Yup.object({
  id: Yup.number(),
  name: Yup.string().required('Name is required').max(255, 'Max 255 characters'),
  notes: Yup.string().max(255, 'Max 255 characters'),
  qty: Yup.number().required('Number is required'),
  unit_of_measurement: Yup.string().required('Unit of measurement is required'),
  cost_per_unit: Yup.number().required('Cost per Unit is required'),
  featured_image: Yup.mixed()
    .when('id', {
      is: (id: number) => id === 0,
      then: Yup.mixed().required('Featured image is required'),
      otherwise: Yup.mixed().nullable()
    })
    .test('is-valid-type', 'Not a valid image type', value => isValidFileType(value?.name, 'image'))
    .test('is-valid-size', 'Max allowed size is 500KB', value => isValidFileSize(value?.size ?? 0))
})
