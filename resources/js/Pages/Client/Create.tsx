import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { clientSchema, type ClientFormType } from './ClientCommon'
import ClientForm from './ClientForm'
import {type CompanyContact, type PageProps } from '@/types'
import { useState, useRef } from 'react'
import AddressModal from './AddressModal'
import { type TagItem } from '@/Components/TagPicker'

export default function Create ({ auth, contact_type, sources, companies }: PageProps & { contact_type: string[], sources: string[], companies: CompanyContact[]}) {
  const formikRef = useRef<any>()
  const [showAddressModal, setShowAddressModal] = useState<boolean>(false)
  const [address, setAddress] = useState<string[]>([])
  const [currentAddress, setCurrentAddress] = useState<string>('')
  const [companiesList, setCompaniesList] = useState<CompanyContact[]>(companies)
  const initialValues: ClientFormType = {
    id: 0,
    name: '',
    email: '',
    address: '',
    appointment_date: null,
    phone: '',
    confirmed: false,
    notes: '',
    contact_type: '',
    other_phone: '',
    secondary_email: '',
    source: '',
    vip_clients: false,
    vip_notes: '',
    company_contact_id: 0
    // tags: []
  }

  const setModalAddress = (address: string) => {
    if (formikRef.current) {
      formikRef.current.setFieldValue('address', address)
      formikRef.current.setFieldValue('confirmed', true)
      formikRef.current.submitForm()
    }
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<ClientFormType>) => {
    console.log(values)
    const response = await fetch(
      `/client/is_unique/${values.phone}/${values.address}`)
    const data = await response.json()

    if (data.length === 0 || values.confirmed) {
      router.post(route('client.store'), values, {
        onError: (errors: any) => {
          helpers.setErrors(errors)
        }
      })
    } else {
      setAddress(data)
      setCurrentAddress(values.address)
      setShowAddressModal(true)
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle="Create Contact"
      >
          <Head title="Create Contact" />
          <Formik<ClientFormType>
            initialValues={initialValues}
            validationSchema={clientSchema}
            onSubmit={handleSubmit}
            innerRef={formikRef}
          >
            {({ errors, submitCount, setFieldValue, values }) => (
              <ClientForm
                errors={errors}
                setFieldValue={setFieldValue}
                submitCount={submitCount}
                isCreate={true}
                values={values}
                contact_type={contact_type}
                companies={companiesList}
                sources={sources}
                // tags={tags}
              />
            )}
          </Formik>
          <AddressModal
            showModal={showAddressModal}
            onClose={() => { setShowAddressModal(false) }}
            address={address}
            currentAddress={currentAddress}
            setModalAddress={setModalAddress}
          />
      </AuthenticatedLayout>
  )
}
