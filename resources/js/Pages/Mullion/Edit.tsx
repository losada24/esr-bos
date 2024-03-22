import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Product, type Mullion } from '@/types'
import MullionForm from './MullionForm'
import { type MullionProps, mullionSchema } from './MullionCommon'

export default function Edit ({ auth, product, frame_colors, external_products }: PageProps & {
  frame_colors: string[]
  external_products: MullionProps[]
  product: Product }) {
  const initialValues: Mullion & { max_allowed_height: number } = {
    id: product.id,
    order_id: product.order_id,
    mark: product.line_item_name,
    width: product.width,
    height: product.height,
    frame_color: product.frame_color,
    qty: product.qty,
    markup: product.markup,
    config: product?.extras?.config ?? '',
    max_allowed_height: external_products.find((p) => p.configuration === product?.extras?.config)?.height ?? 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Mullion>) => {
    router.put(route('mullion.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit Estimate'
      >
        <Head title="Edit" />
        <Formik<Mullion>
          initialValues={initialValues}
          validationSchema={mullionSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, values, setFieldValue }) => (
            <MullionForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              frame_colors={frame_colors}
              estimate_id={product.order_id}
              values={values}
              external_products={external_products}
              setFieldValue={setFieldValue}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
