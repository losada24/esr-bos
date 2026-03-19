import { useState, useRef, useMemo } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import {
  type Client,
  type User,
  type OptionType,
  type Attachment
} from '@/types'
import Select, { type SingleValue } from 'react-select'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type OrderFormValues } from './OrderCommon'
import ReferralFields from '@/Components/ReferralFields'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

const OrderForm = ({
  submitCount,
  errors,
  isCreate,
  // clients,
  setFieldValue,
  values,
  owners,
  attachments,
  status,
  sources
}: {
  submitCount: number
  errors: FormikErrors<OrderFormValues>
  isCreate: boolean
  clients: Client[]
  setFieldValue: (field: string, value: any) => void
  values: OrderFormValues
  owners: User[]
  attachments?: Attachment[]
  status: string[]
  sources: string[]
}) => {
  const inputRef = useRef<google.maps.places.SearchBox | null>(null)
  const libraries: any[] = ['places']
  const menoLibraries = useMemo(() => libraries, [])
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: menoLibraries
  })

  const handleOnPlaceChanged = () => {
    const searchBox = inputRef.current
    if (searchBox) {
      const places = searchBox.getPlaces()
      if (places && places.length > 0) {
        // Usamos setFieldValue para actualizar el valor del campo 'address'
        // setFieldValue('job_address', places[0].formatted_address ?? '')
        const place = places[0]
        if (place.address_components) {
          const addressComponents = place.address_components

          // Función para obtener un componente por tipo
          const getComponent = (type: string) =>
            addressComponents.find((component) => component.types.includes(type))?.long_name ?? ''

          // Extraemos cada parte de la dirección
          const address = `${getComponent('street_number')} ${getComponent('route')}, ${getComponent('subpremise')}`.trim()
          const city = getComponent('locality') // Ciudad
          const state = getComponent('administrative_area_level_1') // Estado
          const zip = getComponent('postal_code') // Código postal

          // Actualizamos los campos en Formik
          setFieldValue('job_address', address)
          setFieldValue('city', city)
          setFieldValue('job_state', state)
          setFieldValue('job_zip', zip)
        }
      }
    }
  }
  const [isCreated] = useState<boolean>(true)
  const [showProductModal, setShowProductModal] = useState<boolean>(false)
  const [attachmentsArray, setAttachmentsList] = useState<Attachment[]>(attachments ?? [])
  const removeAttachmentProduct = (index: number) => {
    if (confirm('Are you sure you want to delete this attachment?')) {
      router.delete(route('order.drop_attachment', { id: attachmentsArray[index].id }), {
        onSuccess: () => {
          const attachmentsList = attachmentsArray.filter((_, i) => i !== index)
          setAttachmentsList(attachmentsList)
          setFieldValue('attachments', attachmentsList)
        }
      })
    }
  }
  const selectedStatus: SingleValue<OptionType> = {
    value: values.status ?? '',
    label: status.find((status) => status === values.status) ?? ''
  }
  const selectedSource: SingleValue<OptionType> = {
    value: values.source ?? '',
    label: sources.find((source) => source === values.source) ?? ''
  }

  //console.log('selectedStatus', selectedStatus)
  return (
    <>
      <Form className='space-y-5'>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Request Information</legend>
          <div className='grid gap-4 grid-cols-4'>
            <div className={submitCount ? (errors.client_name) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="client_name"> Name</label>
              <Field
                id="client_name"
                name="client_name"
                className="form-input"
                autoComplete="client_name"
                placeholder='Client Name'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('client_name', e.target.value)
                }}
              />
              {(submitCount && errors.client_name) ? <InputError message={errors.client_name} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="phone">Phone</label>
              <Field
                id="phone"
                name="phone"
                className="form-input"
                autoComplete="phone"
                placeholder='Phone'
              />
              {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
            </div>
             <div className={submitCount ? (errors.status) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="status">Status</label>
              <Select
                id='status'
                placeholder="status"
                name='status'
                defaultValue={selectedStatus}
                isMulti={false}
                onChange={(value) => { setFieldValue('status', value) }}
                options={status.map((status) => { return { label: status, value: status } })}
              />
              {(submitCount && errors.status) ? <InputError message={errors.status} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.source) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="source">Sources</label>
              <Select
                id='source'
                placeholder="sources"
                name='source'
                defaultValue={selectedSource}
                isMulti={false}
                onChange={(value) => {
                  setFieldValue('source', value)
                  setFieldValue('referral_id', null)
                  setFieldValue('referrer_client_id', null)
                  setFieldValue('referrer_user_id', null)
                  setFieldValue('refer_name', '')
                  setFieldValue('refer_phone', '')
                  setFieldValue('refer_email', '')
                }}
                options={sources.map((source) => { return { label: source, value: source } })}
              />
              {(submitCount && errors.source) ? <InputError message={errors.source} className="mt-2" /> : ''}
            </div>
            <ReferralFields
              values={values as any}
              errors={errors as Record<string, any>}
              submitCount={submitCount}
              setFieldValue={setFieldValue}
            />
              <div className='col-span-3'>
              <label htmlFor="notes"> Notes</label>
              <Field
                id="notes"
                name="notes"
                component="textarea"
                rows="3"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
              />
            </div>
          </div>
        </fieldset>
        <div className="flex items-center justify-between mt-4">
          <Link className='btn btn-danger uppercase' href={route('frontdesk.index')}>Cancel</Link>
          <PrimaryButton className="btn btn-primary" type='submit'>
            {isCreate ? 'Create' : 'Save'}
          </PrimaryButton>
        </div>
      </Form>
    </>
  )
}

export default OrderForm
