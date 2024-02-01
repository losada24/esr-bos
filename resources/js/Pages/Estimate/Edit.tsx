import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { estimateSchema } from './EstimateCommon'
import EstimateForm from './EstimateForm'
import { type PageProps, type Order, type Client } from '@/types'

export default function Edit ({ auth, estimate, frame_colors, glass_colors, clients, glass_types }: PageProps & {
  estimate: Order
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  clients: Client[] }) {
  const initialValues: Order = {
    id: estimate.id,
    frame_color: estimate.frame_color,
    glass_color: estimate.glass_color,
    name: estimate.name,
    notes: estimate.notes ?? '',
    project_name: estimate.project_name ?? '',
    markup: estimate.markup,
    client_id: estimate.client_id,
    tax_amount: estimate.tax_amount,
    tax_rate: estimate.tax_rate,
    installation: estimate.installation,
    permit: estimate.permit,
    other: estimate.other,
    external_purchase_id: estimate.external_purchase_id ?? '',
    glass_type: estimate.glass_type,
    rg_other_price: estimate.rg_other_price,
    order_promotion: estimate.order_promotion,
    subdealer_other: estimate.subdealer_other
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Order>) => {
    router.put(route('estimate.update', values.id), values, {
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
        <Formik<Order>
          initialValues={initialValues}
          validationSchema={estimateSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, setFieldValue, values }) => (
            <EstimateForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              glass_colors={glass_colors}
              frame_colors={frame_colors}
              glass_types={glass_types}
              clients={clients}
              setFieldValue={setFieldValue}
              selectedClient={{ label: estimate.client?.name ?? '', value: estimate.client_id }}
              values={values}
              user={auth.user}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
