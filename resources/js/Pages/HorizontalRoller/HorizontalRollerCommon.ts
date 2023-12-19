import * as Yup from 'yup'

export const horizontalRollerSchema = Yup.object({
  id: Yup.number(),
  mark: Yup.string().required('Name is required').max(255, 'Max 255 characters'),
  qty: Yup.number().required('Qty is required').min(1, 'Min 1'),
  width: Yup.number().required('Width is required').min(20, 'Min width 20').max(111, 'Max width 111'),
  height: Yup.number().required('Height is required').min(19, 'Min height 19').max(74, 'Max height 74'),
  markup: Yup.number().required('Markup is required'),
  frame_color: Yup.string().required('Frame color is required'),
  glass_color: Yup.string().required('Glass color is required'),
  privacy: Yup.string().required('Privacy is required'),
  low_e: Yup.string().required('Glass coating is required'),
  glass_type: Yup.string().required('Glass type is required'),
  screen: Yup.boolean(),
  config: Yup.string().required('Config is required'),
  handle: Yup.string().required('Handle is required')
})
