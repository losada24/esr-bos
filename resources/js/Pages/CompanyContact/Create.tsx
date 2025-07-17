import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { companyContactSchema, type CompanyContact } from './CompanyContactCommon'
import CompanyContactForm from './CompanyContactForm'
import { type User, type PageProps } from '@/types'
import { type Client } from '@/Pages/Client/ClientCommon'
import { useState, useRef } from 'react'

export default function Create ({ auth, contact_type, sources }: PageProps & { auth: User, contact_type: string[], sources: string[] }) {
  const [clients, setClients] = useState<Client[]>([])
  const initialValues: CompanyContact = {
    id: 0,
    name: '',
    email: '',
    phone: '',
    website: '',
    billing_street: '',
    billing_city: '',
    billing_state: '',
    billing_code: '',
    bid_due_date: null
  }

  /* const setModalAddress = (address: string) => {
    if (formikRef.current) {
      formikRef.current.setFieldValue('address', address)
      formikRef.current.setFieldValue('confirmed', true)
      formikRef.current.submitForm()
    }
  } */

  const handleSubmit = async (values: any, helpers: FormikHelpers<CompanyContact>) => {
    values.clients = clients
    console.log('Submitting values:', values)
    router.post(route('company_contact.store', values), {
      _method: 'POST'
    }, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Company"
      >
          <Head title="Create Company" />
          <Formik<CompanyContact>
            initialValues={initialValues}
            validationSchema={companyContactSchema}
            onSubmit={handleSubmit}
            // innerRef={formikRef}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <CompanyContactForm
                errors={errors}
                setFieldValue={setFieldValue}
                submitCount={submitCount}
                isCreate={true}
                values={values}
                clients={clients}
                setClients={setClients}
                // contact_type={contact_type}
                sources={sources}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
