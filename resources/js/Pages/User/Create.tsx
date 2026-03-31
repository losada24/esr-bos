import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userSchema, type UserPageProps, type UserFormValues } from './UserCommon'
import UserForm from './UserForm'

export default function Create ({ auth, roles, statuses, owner_options }: UserPageProps) {
  // const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const initialValues: UserFormValues = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: [],
    delegated_owner_ids: [],
    featured_image: '',
    phone: '',
    status: statuses[0]?.value?.toString() ?? ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<UserFormValues>) => {
    const userValues = {
      ...values,
      role: values.role.map((role: any) => role.value),
      delegated_owner_ids: values.delegated_owner_ids.map((owner: any) => owner.value),
    }
    router.post(route('user.store'), userValues, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create User"
      >
          <Head title="Create User" />
          <Formik<UserFormValues>
            initialValues={initialValues}
            validationSchema={userSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <UserForm
                errors={errors}
                submitCount={submitCount}
                roles={roles}
                statuses={statuses}
                isCreate={true}
                // companies={companies}
                // isAdmin={IS_ADMIN}
                setFieldValue={setFieldValue}
                modalProps={null}
                values={values}
                ownerOptions={owner_options}
              />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
