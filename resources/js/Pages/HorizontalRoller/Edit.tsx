import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Product, type Client, type HorizontalRoller } from '@/types'
import SingleHuntForm from './HorizontalRollerForm'
import { horizontalRollerSchema } from './HorizontalRollerCommon'

interface ProductWithExtras extends Product {
  extras: {
    screen: boolean
    handle: string
    config: string
  }
}

export default function Edit ({ auth, product, frame_colors, glass_colors, handle, config }: PageProps & {
  frame_colors: string[]
  glass_colors: string[]
  config: string[]
  handle: string[]
  clients: Client[]
  product: ProductWithExtras }) {
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
    screen: product.extras.screen,
    handle: product.extras.handle,
    config: product.extras.config,
    order_glass_type: product?.order?.glass_type ?? ''
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
          {({ errors, submitCount, values }) => (
            <SingleHuntForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              glass_colors={glass_colors}
              frame_colors={frame_colors}
              estimate_id={product.order_id}
              values={values}
              handle={handle}
              config={config}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
