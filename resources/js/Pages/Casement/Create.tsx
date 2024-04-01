import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Order, type Casement } from '@/types'
import { type CasementProps, casementSchema } from './CasementCommon'
import { createNextMarkWithLeadingZero } from '@/Utils/mark'
import CasementForm from './CasementForm'

export default function Create ({ auth, frame_colors, glass_colors, estimate, muntin_patterns, muntin_styles, external_products, opening }: PageProps & {
  frame_colors: string[]
  glass_colors: string[]
  opening: string[]
  muntin_patterns: string[]
  muntin_styles: string[]
  external_products: CasementProps[]
  estimate: Order
}) {
  const initialValues: Casement = {
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
    screen: true,
    muntin_panels: false,
    panel_a: false,
    panel_b: false,
    muntin_pattern: '',
    muntin_interior_style: '',
    muntin_exterior_style: '',
    horizontal_lines: 0,
    vertical_lines: 0,
    config: '',
    opening: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Casement>) => {
    router.post(route('casement.store'), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={'Add Casement Window'}
      >
          <Head title={'Add Casement Window'} />
          <Formik<Casement>
            initialValues={initialValues}
            validationSchema={casementSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, values, setFieldValue }) => (
              <CasementForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                glass_colors={glass_colors}
                frame_colors={frame_colors}
                muntin_patterns={muntin_patterns}
                muntin_styles={muntin_styles}
                estimate_id={estimate.id}
                values={values}
                external_products={external_products}
                setFieldValue={setFieldValue}
                opening={opening}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
