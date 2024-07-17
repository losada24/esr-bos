import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { installationTeamSchema, type InstallationTeamFormValues } from './InstallationTeamCommon'
import InstallationTeamForm from './InstallationTeamForm'
import { type User, type PageProps, type TypeOfHousing } from '@/types'

export default function Create ({ auth, type_of_housings, users }: PageProps & { users: User[], type_of_housings: TypeOfHousing[] }) {
  const initialValues: InstallationTeamFormValues = {
    id: 0,
    number_of_member: 0,
    worker_compensation_expiration_date: null,
    liability_expiration_date: null,
    user_id: { value: 0, label: '' },
    attachments: [],
    type_housing: [],
    worker_compensation_attach: '',
    liability_expiration_attach: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<InstallationTeamFormValues>) => {
    const installation_team = {
      number_of_member: values.number_of_member,
      worker_compensation_expiration_date: values.worker_compensation_expiration_date,
      liability_expiration_date: values.liability_expiration_date,
      user_id: values.user_id.value,
      worker_compensation_attach: values.worker_compensation_attach,
      liability_expiration_attach: values.liability_expiration_attach,
      type_of_housings: values.type_housing.map((typeHousing: any) => typeHousing.value)
    }

    router.post(route('installation_team.store'), installation_team, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Installation Team"
      >
          <Head title="Create Installation Team" />
          <Formik<InstallationTeamFormValues>
            initialValues={initialValues}
            validationSchema={installationTeamSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <InstallationTeamForm
                errors={errors}
                submitCount={submitCount}
                isCreate={true}
                type_of_housings={type_of_housings}
                users={users}
                setFieldValue={setFieldValue}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
