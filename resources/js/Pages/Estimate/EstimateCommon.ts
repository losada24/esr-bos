import * as Yup from 'yup'

export const estimateSchema = Yup.object({
  id: Yup.number(),
  name: Yup.string().required('Name is required').max(255, 'Max 255 characters'),
  project_name: Yup.string().max(255, 'Max 255 characters'),
  client_id: Yup.number().required('Client is required'),
  frame_color: Yup.string().required('Frame color is required'),
  glass_color: Yup.string().required('Glass color is required'),
  markup: Yup.number().required('Markup is required'),
  notes: Yup.string().max(255, 'Max 255 characters')
})
