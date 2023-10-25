import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { clientSchema, type Client } from './ClientCommon'
import ClientForm from './ClientForm'
import { type PageProps } from '@/types'

export default function Edit ({ auth, states, client }: PageProps & { states: string[], client: Client }) {
  const initialValues: Client = {
    id: client.id ?? 0,
    name: client.name ?? '',
    email: client.email ?? '',
    address: client.address ?? '',
    city: client.city ?? '',
    state: client.state ?? '',
    zip: client.zip ?? '',
    phone: client.phone ?? ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Client>) => {
    router.put(route('client.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit Client'
      >
        <Head title="Edit" />
        <Formik<Client>
          initialValues={initialValues}
          validationSchema={clientSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount }) => (
            <ClientForm
              errors={errors}
              submitCount={submitCount}
              states={states}
              isCreate={false}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
