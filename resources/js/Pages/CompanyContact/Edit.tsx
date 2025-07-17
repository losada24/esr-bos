import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { companyContactSchema, type CompanyContact } from './CompanyContactCommon'
import CompanyContactForm from './CompanyContactForm'
import { type User, type PageProps, type ClientAddress } from '@/types'
import { useState, useRef } from 'react'
import AddressModal from './AddressModal'
import { type Client } from '../Client/ClientCommon'

export default function Edit ({ auth, companyContact, sources, clientslist }: PageProps & { auth: User, companyContact: CompanyContact, sources: string[], clientslist: Client[] }) {
  const [clients, setClients] = useState<Client[]>(clientslist)
  const initialValues: CompanyContact = {
    id: companyContact.id,
    name: companyContact.name,
    email: companyContact.email,
    phone: companyContact.phone,
    website: companyContact.website,
    billing_street: companyContact.billing_street,
    billing_city: companyContact.billing_city,
    billing_state: companyContact.billing_state,
    billing_code: companyContact.billing_code,
    bid_due_date: companyContact.bid_due_date
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<CompanyContact>) => {
    console.log(values)
    values.clients = clients
    router.post(route('company_contact.update', values.id), {
      _method: 'PUT',
      ...values
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
          pageTitle="Edit Company"
      >
          <Head title="Edit Company" />
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
                isCreate={false}
                values={values}
                 clients={clients}
                setClients={setClients}
                // contact_type={contact_type}
                sources={sources}
                // contact_type={contact_type}
                // sources={sources}
              />
            )}
          </Formik>
      </AuthenticatedLayout>
  )
}
