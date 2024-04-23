import * as Yup from 'yup'
import { RUSH_GLASS_TYPE } from '@/Utils/constants'
import { getGlassHeight, getGlassWidth } from '@/Utils/FixedWindows'

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
  glass_type: Yup.string().required('Glass type is required'),
  // MUNTIN VALIDATIONS
  muntin_panels: Yup.boolean(),
  panel_a: Yup.boolean()
    .when(['muntin_panels'], {
      is: (muntin_panels: boolean) => muntin_panels,
      then: Yup.boolean().equals([true], 'Please select at least one panel')
    }),
  muntin_pattern: Yup.string()
    .when(['muntin_panels'], {
      is: (muntin_panels: boolean) => muntin_panels,
      then: Yup.string().required('Muntin Pattern is required')
    }),
  muntin_interior_style: Yup.string()
    .when(['muntin_panels', 'muntin_exterior_style'], {
      is: (muntin_panels: boolean, muntin_exterior_style: string) => {
        return muntin_panels && (muntin_exterior_style === '' || muntin_exterior_style === undefined)
      },
      then: Yup.string().required('Interior or Exterior Style is required')
    }),
  muntin_exterior_style: Yup.string(),
  vertical_lines: Yup.number()
    .when(['muntin_panels'], {
      is: (muntin_panels: boolean) => muntin_panels,
      then: Yup.number().test('width', 'Invalid horizontal lites', (value, validationContext) => {
        const {
          createError,
          parent: { width }
        } = validationContext

        const totalWhiteSpaces = value ?? 0
        if (totalWhiteSpaces !== 0 && totalWhiteSpaces < 2) {
          return createError({
            message: 'Minimun amount of lites is 2. Please adjust the number of lites.',
            path: 'vertical_lines'
          })
        }

        const glassWidth = getGlassWidth(width)
        const whiteSpaceAndMuntinSize = (totalWhiteSpaces - 1) + (totalWhiteSpaces * 2)

        if (glassWidth < whiteSpaceAndMuntinSize) {
          return createError({
            message: 'Horizontal lites exceed glass width. Please adjust the number of lites.',
            path: 'vertical_lines'
          })
        }
        return true
      })
    }),
  horizontal_lines: Yup.number()
    .when(['muntin_panels'], {
      is: (muntin_panels: boolean) => muntin_panels,
      then: Yup.number().test('height', 'Invalid vertical lites', (value, validationContext) => {
        const {
          createError,
          parent: { height }
        } = validationContext

        const totalWhiteSpaces = value ?? 0
        if (totalWhiteSpaces !== 0 && totalWhiteSpaces < 2) {
          return createError({
            message: 'Minimun amount of lites is 2. Please adjust the number of lites.',
            path: 'horizontal_lines'
          })
        }

        const glassHeight = getGlassHeight(height)
        const whiteSpaceAndMuntinSize = (totalWhiteSpaces - 1) + (totalWhiteSpaces * 2)

        if (glassHeight < whiteSpaceAndMuntinSize) {
          return createError({
            message: 'Vertical lites exceed glass height. Please adjust the number of lites.',
            path: 'horizontal_lines'
          })
        }

        return true
      })
    })
  // END MUNTIN VALIDATIONS
})
