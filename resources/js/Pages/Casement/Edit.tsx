import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Product, type Casement } from '@/types'
import { type CasementProps, casementSchema } from './CasementCommon'
import CasementForm from './CasementForm'

export default function Edit ({ auth, product, frame_colors, glass_colors, muntin_patterns, muntin_styles, external_products, opening }: PageProps & {
  frame_colors: string[]
  glass_colors: string[]
  muntin_patterns: string[]
  muntin_styles: string[]
  opening: string[]
  external_products: CasementProps[]
  product: Product }) {
  const initialValues: Casement = {
    id: product.id,
    order_id: product.order_id,
    mark: product.line_item_name,
    width: product.width,
    height: product.height,
    frame_color: product.frame_color,
    glass_color: product.glass_color,
    glass_type: product.glass_type,
    low_e: product.low_e,
    privacy: product.privacy,
    qty: product.qty,
    markup: product.markup,
    order_glass_type: product?.order?.glass_type ?? '',
    muntin_panels: product?.extras?.muntin_panels ?? false,
    panel_a: product?.extras?.panel_a ?? false,
    panel_b: product?.extras?.panel_b ?? false,
    screen: product?.extras?.screen ?? false,
    muntin_pattern: product?.extras?.muntin_pattern ?? '',
    muntin_interior_style: product?.extras?.muntin_interior_style ?? '',
    muntin_exterior_style: product?.extras?.muntin_exterior_style ?? '',
    horizontal_lines: product?.extras?.horizontal_lines ?? 0,
    vertical_lines: product?.extras?.vertical_lines ?? 0,
    config: product?.extras?.config ?? '',
    opening: product?.extras?.opening ?? ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Casement>) => {
    router.put(route('casement.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit Casement'
      >
        <Head title="Edit" />
        <Formik<Casement>
          initialValues={initialValues}
          validationSchema={casementSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, values, setFieldValue }) => (
            <CasementForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              glass_colors={glass_colors}
              frame_colors={frame_colors}
              estimate_id={product.order_id}
              values={values}
              muntin_patterns={muntin_patterns}
              muntin_styles={muntin_styles}
              external_products={external_products}
              opening={opening}
              setFieldValue={setFieldValue}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
