import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userSchema, type UserPageProps, type User } from './UserCommon'
import UserForm from './UserForm'
import { type Role } from '@/types'
import { isAdmin } from '@/Utils/user'

export default function Create ({ auth, roles, companies }: UserPageProps) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const initialValues: User = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 0,
    company_id: 0,
    markup: 0
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<User>) => {
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
            {({ errors, submitCount }) => (
              <UserForm
                errors={errors}
                submitCount={submitCount}
                roles={roles}
                isCreate={true}
                companies={companies}
                isAdmin={IS_ADMIN}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
