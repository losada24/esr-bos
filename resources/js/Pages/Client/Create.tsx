import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { clientSchema, type Client } from './ClientCommon'
import ClientForm from './ClientForm'
import { type PageProps, type Role, type Company } from '@/types'
import { isAdmin } from '@/Utils/user'

export default function Create ({ auth, states, companies }: PageProps & { states: string[], companies: Company[] }) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const initialValues: Client = {
    id: 0,
    name: '',
    email: '',
    address: '',
    city: '',
    state: '',
    zip: '',
    phone: '',
    company_id: 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Client>) => {
    router.post(route('client.store'), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Client"
      >
          <Head title="Create Client" />
          <Formik<Client>
            initialValues={initialValues}
            validationSchema={clientSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount }) => (
              <ClientForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                states={states}
                isAdmin={IS_ADMIN}
                companies={companies}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
