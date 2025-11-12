import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router, useForm } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { type ClientEditFormType, clientSchema, type ClientFormType } from './ClientCommon'
import ClientForm from './ClientForm'
import { type User, type PageProps, type ClientAddress, type CompanyContact } from '@/types'
import { useState, useRef } from 'react'
import AddressModal from './AddressModal'
import TagPicker, { type TagItem } from '@/Components/TagPicker'

export default function Edit ({ auth, clients, tags, clientAddress, contact_type, sources, companies }: PageProps & { auth: User, clients: ClientEditFormType, tags: TagItem[], clientAddress: ClientAddress, contact_type: string[], sources: string[], companies: CompanyContact[] }) {

  const formikRef = useRef<any>()
  const [showAddressModal, setShowAddressModal] = useState<boolean>(false)
  const [address, setAddress] = useState<string[]>([])
  const [currentAddress, setCurrentAddress] = useState<string>('')
  const initialValues: ClientFormType = {
    id: clients.id,
    name: clients.name,
    email: clients.email,
    address: clients?.client_address?.[0]?.address ?? '',
    appointment_date: clients?.client_address?.[0]?.appointment_date ?? null,
    phone: clients.phone,
    confirmed: false,
    notes: clients?.client_address?.[0]?.notes ?? '',
    contact_type: clients.contact_type,
    other_phone: clients.other_phone,
    secondary_email: clients.secondary_email,
    source: clients.source,
    vip_clients: clients.vip_clients ?? false,
    vip_notes: clients.vip_notes ?? '',
    refer_name: clients?.referral?.name ?? '',
    refer_phone: clients?.referral?.phone ?? '',
    company_contact_id: clients?.company_contact_id ?? 0,
    tags: tags ?? []
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
      router.post(route('client.update', values.id), {
        _method: 'PUT',
        ...values
      }, {
        forceFormData: true,
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
          pageTitle="Edit Contact"
      >
          <Head title="Edit Contact" />
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
                isCreate={false}
                values={values}
                contact_type={contact_type}
                sources={sources}
                companies={companies}
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
