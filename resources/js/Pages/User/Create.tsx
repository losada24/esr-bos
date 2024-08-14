import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userSchema, type UserPageProps, type User } from './UserCommon'
import UserForm from './UserForm'

export default function Create ({ auth, roles }: UserPageProps) {
  // const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const initialValues: User = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 0,
    featured_image: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<User>) => {
    router.post(route('user.store'), values, {
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
          <Formik<User>
            initialValues={initialValues}
            validationSchema={userSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue }) => (
              <UserForm
                errors={errors}
                submitCount={submitCount}
                roles={roles}
                isCreate={true}
                // companies={companies}
                // isAdmin={IS_ADMIN}
                setFieldValue={setFieldValue}
                modalProps={null}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
