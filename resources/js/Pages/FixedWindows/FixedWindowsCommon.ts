import * as Yup from 'yup'
import { RUSH_GLASS_TYPE } from '@/Utils/constants'

export const fixedWindowsSchema = Yup.object({
  id: Yup.number(),
  mark: Yup.string().required('Name is required').max(255, 'Max 255 characters'),
  qty: Yup.number().required('Qty is required').min(1, 'Min 1'),
  width: Yup.number().required('Width is required').min(12, 'Min width 12').max(74, 'Max width 74'),
  height: Yup.number().required('Height is required').min(12, 'Min height 12').max(120, 'Max height 120'),
  markup: Yup.number().required('Markup is required'),
  frame_color: Yup.string().required('Frame color is required'),
  glass_color: Yup.string().required('Glass color is required'),
  privacy: Yup.string()
    .when(['order_glass_type'], {
      is: (order_glass_type: string) => order_glass_type !== RUSH_GLASS_TYPE,
      then: Yup.string().required('Privacy is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
    }),
  low_e: Yup.string()
    .when(['order_glass_type'], {
      is: (order_glass_type: string) => order_glass_type !== RUSH_GLASS_TYPE,
      then: Yup.string().required('Glass coating is required').max(255, 'Max 255 characters'),
      otherwise: Yup.string().nullable().max(255, 'Glass coating is required')
    }),
  glass_type: Yup.string().required('Glass type is required')
})
