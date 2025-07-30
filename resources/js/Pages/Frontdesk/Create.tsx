import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import OrderForm from './OrderForm'
import {
  type PageProps,
  type Client,
  type User

} from '@/types'
import { getValueIdNotNull, orderFormObj, orderSchema, type OrderFormValues } from './OrderCommon'

export default function Create ({
  auth,
  clients,
  owners,
  status,
  sources
}: PageProps & {
  clients: Client[]
  owners: User[]
  status: string[]
  sources: string[]
}) {
  const initialValues: OrderFormValues = orderFormObj
  // console.log('Initial values:', initialValues)
  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderFormValues>) => {
    const order = {
      ...values,
      status: typeof values.status === 'string' ? values.status : getValueIdNotNull(values.status),
      source: typeof values.source === 'string' ? values.source : getValueIdNotNull(values.source)
    }

    console.log('Order data:', order)

    router.post(route('frontdesk.store'), order, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Request"
      >
          <Head title="Create Request" />
          <Formik<OrderFormValues>
            initialValues={initialValues}
            validationSchema={orderSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <OrderForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                clients={clients}
                setFieldValue={setFieldValue}
                values={values}
                owners={owners}
                status={status}
                sources={sources}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
