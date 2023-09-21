import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userUpdateSchema, type UserPageProps, type User } from './UserCommon'
import UserForm from './UserForm'

export default function Edit ({ auth, roles, user }: UserPageProps) {
  
  const initialValues: User = {
    id: user?.data.id ?? 0,
    name: user?.data.name ?? '',
    email: user?.data.email ?? '',
    password: '',
    password_confirmation: '',
    role: user?.data.role ?? 0
  }

  const handleSubmit = async (values: User, helpers: FormikHelpers<User>) => {
    router.put(route('user.update', values.id), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Edit User'
      >
        <Head title="Edit" />
        <Formik<User>
          initialValues={initialValues}
          validationSchema={userUpdateSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, touched }) => (
            <UserForm
              errors={errors}
              submitCount={submitCount}
              touched={touched}
              roles={roles}
              isCreate={false}
            />
          )}
        </Formik>
      </AuthenticatedLayout>
  )
}
