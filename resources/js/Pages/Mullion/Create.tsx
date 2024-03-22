import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Order, type Mullion } from '@/types'
import MullionForm from './MullionForm'
import { mullionSchema, type MullionProps } from './MullionCommon'
import { createNextMarkWithLeadingZero } from '@/Utils/mark'

export default function Create ({ auth, frame_colors, estimate, external_products }: PageProps & {
  frame_colors: string[]
  external_products: MullionProps[]
  estimate: Order
}) {
  const initialValues: Mullion & { max_allowed_height: number } = {
    id: 0,
    order_id: estimate.id,
    mark: createNextMarkWithLeadingZero(estimate?.products_count ?? 0, 3),
    width: 0,
    height: 0,
    frame_color: estimate.frame_color,
    qty: 0,
    markup: estimate.markup,
    config: '',
    max_allowed_height: 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Mullion>) => {
    router.post(route('mullion.store'), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={'Add Mullion'}
      >
          <Head title={'Add Mullion'} />
          <Formik<Mullion>
            initialValues={initialValues}
            validationSchema={mullionSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, values, setFieldValue }) => (
              <MullionForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                frame_colors={frame_colors}
                estimate_id={estimate.id}
                values={values}
                external_products={external_products}
                setFieldValue={setFieldValue}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
