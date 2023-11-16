import React from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { estimateSchema } from './EstimateCommon'
import EstimateForm from './EstimateForm'
import { type PageProps, type Order, type Client } from '@/types'

export default function Edit ({ auth, estimate, frame_colors, glass_colors, clients }: PageProps & { estimate: Order, frame_colors: string[], glass_colors: string[], clients: Client[] }) {
  const initialValues: Order = {
    id: estimate.id,
    frame_color: estimate.frame_color,
    glass_color: estimate.glass_color,
    name: estimate.name,
    notes: estimate.notes ?? '',
    project_name: estimate.project_name,
    markup: estimate.markup,
    client_id: estimate.client_id,
    tax_amount: estimate.tax_amount,
    tax_rate: estimate.tax_rate,
    installation: estimate.installation,
    permit: estimate.permit,
    other: estimate.other
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
          {({ errors, submitCount, setFieldValue }) => (
            <EstimateForm
              errors={errors}
              submitCount={submitCount}
              isCreate={false}
              glass_colors={glass_colors}
              frame_colors={frame_colors}
              clients={clients}
              setFieldValue={setFieldValue}
              selectedClient={{ label: estimate.client?.name ?? '', value: estimate.client_id }}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
