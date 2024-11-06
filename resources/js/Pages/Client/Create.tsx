import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { Formik, type FormikHelpers } from 'formik'
import { clientSchema, type ClientFormType } from './ClientCommon'
import ClientForm from './ClientForm'
import { type PageProps } from '@/types'
import { useState, useRef } from 'react'
import AddressModal from './AddressModal'

export default function Create ({ auth }: PageProps) {
  const formikRef = useRef<any>()
  const [showAddressModal, setShowAddressModal] = useState<boolean>(false)
  const [address, setAddress] = useState<string[]>([])
  const [currentAddress, setCurrentAddress] = useState<string>('')
  const initialValues: ClientFormType = {
    id: 0,
    name: '',
    email: '',
    address: '',
    appointment_date: null,
    phone: '',
    confirmed: false,
    notes: ''
  }

  const setModalAddress = (address: string) => {
    if (formikRef.current) {
      formikRef.current.setFieldValue('address', address)
      formikRef.current.setFieldValue('confirmed', true)
      formikRef.current.submitForm()
    }
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<ClientFormType>) => {
    // console.log(values)
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
          pageTitle="Create Client"
      >
          <Head title="Create Client" />
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
