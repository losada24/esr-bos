import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type PageProps, type Product, type Client, type SingleHunt } from '@/types'
import SingleHuntForm from './SingleHuntForm'
import { singleHuntSchema } from './SingleHuntCommon'

interface ProductWithExtras extends Product {
  extras: {
    screen: boolean
  }
}

export default function Edit ({ auth, product, frame_colors, glass_colors }: PageProps & {
  frame_colors: string[]
  glass_colors: string[]
  clients: Client[]
  product: ProductWithExtras }) {
  const initialValues: SingleHunt = {
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
    order_glass_type: product?.order?.glass_type ?? ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<SingleHunt>) => {
    router.put(route('single-hunt.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit Single Hung'
      >
        <Head title="Edit" />
        <Formik<SingleHunt>
          initialValues={initialValues}
          validationSchema={singleHuntSchema}
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
              // glassType={product?.order?.glass_type ?? ''}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
