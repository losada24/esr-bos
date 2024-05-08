import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Product, type Client, type HorizontalRoller } from '@/types'
import HorizontalRollerForm from './HorizontalRollerForm'
import { horizontalRollerSchema } from './HorizontalRollerCommon'

export default function Edit ({ auth, product, frame_colors, glass_colors, handle, config, muntin_patterns, muntin_styles }: PageProps & {
  frame_colors: string[]
  glass_colors: string[]
  config: string[]
  handle: string[]
  clients: Client[]
  muntin_patterns: string[]
  muntin_styles: string[]
  product: Product }) {
  const initialValues: HorizontalRoller = {
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
    screen: product?.extras?.screen ?? false,
    handle: product?.extras?.handle ?? '',
    config: product?.extras?.config ?? '',
    order_glass_type: product?.order?.glass_type ?? '',
    muntin_panels: product?.extras?.muntin_panels ?? false,
    panel_a: product?.extras?.panel_a ?? false,
    panel_b: product?.extras?.panel_b ?? false,
    muntin_pattern: product?.extras?.muntin_pattern ?? '',
    muntin_interior_style: product?.extras?.muntin_interior_style ?? '',
    muntin_exterior_style: product?.extras?.muntin_exterior_style ?? '',
    horizontal_lines: product?.extras?.horizontal_lines ?? 0,
    vertical_lines: product?.extras?.vertical_lines ?? 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<HorizontalRoller>) => {
    router.put(route('horizontal-roller.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit Horizonral Roller'
      >
        <Head title="Edit" />
        <Formik<HorizontalRoller>
          initialValues={initialValues}
          validationSchema={horizontalRollerSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, values, setFieldValue }) => (
            <HorizontalRollerForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              glass_colors={glass_colors}
              frame_colors={frame_colors}
              estimate_id={product.order_id}
              values={values}
              handle={handle}
              config={config}
              muntin_patterns={muntin_patterns}
              muntin_styles={muntin_styles}
              setFieldValue={setFieldValue}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
