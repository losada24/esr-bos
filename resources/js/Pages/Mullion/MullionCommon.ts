import * as Yup from 'yup'

export const mullionSchema = Yup.object({
  id: Yup.number(),
  mark: Yup.string().required('Name is required').max(255, 'Max 255 characters'),
  qty: Yup.number().required('Qty is required').min(1, 'Min 1'),
  width: Yup.number().required('Width is required'),
  height: Yup.number().required('Height is required').min(26, 'Min 26').test('max_allowed_height', 'Height is greater than max allowed height', (value, validationContext) => {
    const {
      createError,
      parent: { max_allowed_height }
    } = validationContext
    const heightValue = value ?? 0
    if (max_allowed_height < heightValue) {
      return createError({
        message: `Maximun height is ${max_allowed_height} inches. Please adjust the height.`,
        path: 'height'
      })
    }
    return true
  }),
  markup: Yup.number().required('Markup is required'),
  frame_color: Yup.string().required('Frame color is required'),
  config: Yup.string().required('Configuration is required'),
  max_allowed_height: Yup.number()
})

export interface MullionProps {
  configuration: string
  width: number
  height: number
}
