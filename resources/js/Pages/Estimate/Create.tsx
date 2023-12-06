import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { estimateSchema } from './EstimateCommon'
import EstimateForm from './EstimateForm'
import { type PageProps, type Order, type Client } from '@/types'

export default function Create ({ auth, frame_colors, glass_colors, clients }: PageProps & { frame_colors: string[], glass_colors: string[], clients: Client[] }) {
  const initialValues: Order = {
    id: 0,
    frame_color: '',
    glass_color: '',
    name: '',
    notes: '',
    project_name: '',
    markup: 0,
    client_id: 0,
    tax_amount: 0,
    tax_rate: 0,
    installation: 0,
    permit: 0,
    other: 0,
    external_purchase_id: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Order>) => {
    router.post(route('estimate.store'), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Estimate"
      >
          <Head title="Create Estimate" />
          <Formik<Order>
            initialValues={initialValues}
            validationSchema={estimateSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue }) => (
              <EstimateForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                glass_colors={glass_colors}
                frame_colors={frame_colors}
                clients={clients}
                setFieldValue={setFieldValue}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
