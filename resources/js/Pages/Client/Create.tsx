import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { clientSchema, type ClientFormType } from './ClientCommon'
import ClientForm from './ClientForm'
import { type PageProps } from '@/types'
import { useState } from 'react'

export default function Create ({ auth }: PageProps) {
  const [address, setAddress] = useState<string[]>()
  const initialValues: ClientFormType = {
    id: 0,
    name: '',
    email: '',
    address: '',
    appointment_date: new Date(),
    phone: ''
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<ClientFormType>) => {
    console.log(values)
    const response = await fetch(
      `/client/is_unique/${values.email}/${values.phone}`)
    const data = await response.json()

    console.log(data)
    /* router.post(route('client.store'), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    }) */
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Client"
      >
          <Head title="Create Client" />
          <Formik<ClientFormType>
            initialValues={initialValues}
            validationSchema={clientSchema}
            onSubmit={handleSubmit}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <ClientForm
                errors={errors}
                setFieldValue={setFieldValue}
                submitCount={submitCount}
                isCreate={true}
                values={values}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
