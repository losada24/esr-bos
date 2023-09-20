import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userSchema, type UserPageProps, type User } from './UserCommon'
import UserForm from './UserForm'

export default function Create ({ auth, roles }: UserPageProps) {
  const initialValues: User = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 0
  }

  const handleSubmit = async (values: User, helpers: FormikHelpers<User>) => {
    router.post(route('user.store'), values, {
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
            {({ errors, submitCount, touched }) => (
              <UserForm
                errors={errors}
                submitCount={submitCount}
                touched={touched}
                roles={roles}
                isCreate={true}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
