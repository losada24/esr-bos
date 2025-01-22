import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { userSchema, type UserPageProps, type User, type UserFormValues } from './UserCommon'
import UserForm from './UserForm'

export default function Create ({ auth, roles }: UserPageProps) {
  // const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const initialValues: UserFormValues = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: [],
    featured_image: '',
    phone: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<UserFormValues>) => {
    const userValues = {
      ...values,
      role: values.role.map((role: any) => role.value)
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
                isCreate={true}
                // companies={companies}
                // isAdmin={IS_ADMIN}
                setFieldValue={setFieldValue}
                modalProps={null}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
