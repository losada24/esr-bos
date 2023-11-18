import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { clientSchema, type Client } from './ClientCommon'
import ClientForm from './ClientForm'
import { type PageProps, type Role, type Company } from '@/types'
import { isAdmin } from '@/Utils/user'

export default function Edit ({ auth, states, client, companies }: PageProps & { states: string[], client: Client, companies: Company[] }) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const initialValues: Client = {
    id: client.id ?? 0,
    name: client.name ?? '',
    email: client.email ?? '',
    address: client.address ?? '',
    city: client.city ?? '',
    state: client.state ?? '',
    zip: client.zip ?? '',
    phone: client.phone ?? '',
    company_id: client.company_id ?? 0
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
              isAdmin={IS_ADMIN}
              companies={companies}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
