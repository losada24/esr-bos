import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Order, type FixedWindows } from '@/types'
import FixedWindowsForm from './FixedWindowsForm'
import { fixedWindowsSchema } from './FixedWindowsCommon'
import { createNextMarkWithLeadingZero } from '@/Utils/mark'

export default function Create ({ auth, frame_colors, glass_colors, estimate, muntin_patterns, muntin_styles }: PageProps & {
  frame_colors: string[]
  glass_colors: string[]
  muntin_patterns: string[]
  muntin_styles: string[]
  estimate: Order
}) {
  const initialValues: FixedWindows = {
    id: 0,
    order_id: estimate.id,
    mark: createNextMarkWithLeadingZero(estimate?.products_count ?? 0, 3),
    width: 0,
    height: 0,
    frame_color: estimate.frame_color,
    glass_color: estimate.glass_color,
    glass_type: '',
    low_e: 'NONE',
    privacy: '',
    qty: 0,
    markup: estimate.markup,
    order_glass_type: estimate.glass_type,
    muntin_panels: false,
    panel_a: false,
    muntin_pattern: '',
    muntin_interior_style: '',
    muntin_exterior_style: '',
    horizontal_lines: 0,
    vertical_lines: 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<FixedWindows>) => {
    router.post(route('fixed-windows.store'), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={'Add Fixed Window'}
      >
          <Head title={'Add Fixed Window'} />
          <Formik<FixedWindows>
            initialValues={initialValues}
            validationSchema={fixedWindowsSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, values, setFieldValue }) => (
              <FixedWindowsForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                glass_colors={glass_colors}
                frame_colors={frame_colors}
                muntin_patterns={muntin_patterns}
                muntin_styles={muntin_styles}
                estimate_id={estimate.id}
                values={values}
                setFieldValue={setFieldValue}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
