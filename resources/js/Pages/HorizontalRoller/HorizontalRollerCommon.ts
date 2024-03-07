import * as Yup from 'yup'
import { RUSH_GLASS_TYPE } from '@/Utils/constants'
import { getMoveGlassHeight, getGlassWidth } from '@/Utils/HorizontalRoller'

export const horizontalRollerSchema = Yup.object({
  id: Yup.number(),
  mark: Yup.string().required('Name is required').max(255, 'Max 255 characters'),
  qty: Yup.number().required('Qty is required').min(1, 'Min 1'),
  width: Yup.number().required('Width is required').min(20, 'Min width 20').max(111, 'Max width 111'),
  height: Yup.number().required('Height is required').min(19, 'Min height 19').max(74, 'Max height 74'),
  markup: Yup.number().required('Markup is required'),
  frame_color: Yup.string().required('Frame color is required'),
  glass_color: Yup.string().required('Glass color is required'),
  privacy: Yup.string().when(['order_glass_type'], {
    is: (order_glass_type: string) => order_glass_type !== RUSH_GLASS_TYPE,
    then: Yup.string().required('Privacy is required').max(255, 'Max 255 characters'),
    otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
  }),
  low_e: Yup.string().when(['order_glass_type'], {
    is: (order_glass_type: string) => order_glass_type !== RUSH_GLASS_TYPE,
    then: Yup.string().required('Glass Coating is required').max(255, 'Max 255 characters'),
    otherwise: Yup.string().nullable().max(255, 'Max 255 characters')
  }),
  glass_type: Yup.string().required('Glass type is required'),
  screen: Yup.boolean(),
  config: Yup.string().required('Config is required'),
  handle: Yup.string().required('Handle is required'),
  // MUNTIN VALIDATIONS
  muntin_panels: Yup.boolean(),
  panel_a: Yup.boolean()
    .when(['muntin_panels'], {
      is: (muntin_panels: boolean) => muntin_panels,
      then: Yup.boolean().test('panels', 'Please select at least one panel', (value, validationContext) => {
        const {
          createError,
          parent: { panel_b }
        } = validationContext

        if (value !== true && panel_b !== true) {
          return createError({
            message: 'Please select at least one panel',
            path: 'panel_a'
          })
        }

        return true
      })
    }),
  panel_b: Yup.boolean(),
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
      then: Yup.number().test('width', 'Invalid horizontal lines', (value, validationContext) => {
        const {
          createError,
          parent: { width }
        } = validationContext

        const totalWhiteSpaces = value ?? 0
        if (totalWhiteSpaces !== 0 && totalWhiteSpaces < 2) {
          return createError({
            message: 'Minimun amount of lines is 2. Please adjust the number of lines.',
            path: 'vertical_lines'
          })
        }

        const glassWidth = getGlassWidth(width)
        const whiteSpaceAndMuntinSize = (totalWhiteSpaces - 1) + (totalWhiteSpaces * 2)

        if (glassWidth < whiteSpaceAndMuntinSize) {
          return createError({
            message: 'Horizontal lines exceed glass width. Please adjust the number of lines.',
            path: 'vertical_lines'
          })
        }
        return true
      })
    }),
  horizontal_lines: Yup.number()
    .when(['muntin_panels'], {
      is: (muntin_panels: boolean) => muntin_panels,
      then: Yup.number().test('height', 'Invalid vertical lines', (value, validationContext) => {
        const {
          createError,
          parent: { height }
        } = validationContext

        const totalWhiteSpaces = value ?? 0
        if (totalWhiteSpaces !== 0 && totalWhiteSpaces < 2) {
          return createError({
            message: 'Minimun amount of lines is 2. Please adjust the number of lines.',
            path: 'horizontal_lines'
          })
        }

        const glassHeight = getMoveGlassHeight(height)
        const whiteSpaceAndMuntinSize = (totalWhiteSpaces - 1) + (totalWhiteSpaces * 2)

        if (glassHeight < whiteSpaceAndMuntinSize) {
          return createError({
            message: 'Vertical lines exceed glass height. Please adjust the number of lines.',
            path: 'horizontal_lines'
          })
        }

        return true
      })
    })
  // END MUNTIN VALIDATIONS
})
