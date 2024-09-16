import { Field, Form } from 'formik'
import { useRef, useMemo } from 'react'
import { useJsApiLoader, StandaloneSearchBox, type Library } from '@react-google-maps/api'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import {type ClientFormType } from './ClientCommon'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

const ClientForm = ({ submitCount, errors, isCreate, setFieldValue, values }: {
  submitCount: number
  errors: FormikErrors<ClientFormType>
  setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void
  isCreate: boolean
  values: ClientFormType
}) => {
  const inputRef = useRef<HTMLInputElement | null>(null)
  const libraries: Library[] = ['places']
  const menoLibraries = useMemo(() => libraries, [])
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: menoLibraries
  })

  const handleOnPlaceChanged = () => {
    const address = inputRef.current?.getPlaces()
    if (address.length > 0) {
      setFieldValue('address', address[0].formatted_address)
    }
  }

  return (
    <Form className='space-y-5'>
      <div className='grid gap-4 grid-cols-3'>
        <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="name">Name</label>
          <Field
            id="name"
            name="name"
            className="form-input"
            autoComplete="name"
            placeholder='Name'
          />
          {(submitCount && errors.name) ? <InputError message={errors.name} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.email) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="email">Email</label>
          <Field
            id="email"
            name="email"
            type="email"
            className="form-input"
            placeholder='Email'
          />
          {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
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
      </div>
      <div className={submitCount ? (errors.address) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="address">Address</label>
        {isLoaded &&
          <StandaloneSearchBox
            onLoad={(ref) => { inputRef.current = ref }}
            onPlacesChanged={handleOnPlaceChanged}
          >
            <Field
              id="address"
              name="address"
              className="form-textarea resize-none placeholder:text-white-dark"
              placeholder='Address'
            />
          </StandaloneSearchBox>
        }
        {(submitCount && errors.address) ? <InputError message={errors.address} className="mt-2" /> : ''}
      </div>
      <div className='grid gap-4 grid-cols-2'>
        <div className={submitCount ? (errors.appointment_date) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="appointment_date">Appointment Date</label>
          <Flatpickr
            options={{
              enableTime: true,
              dateFormat: 'Y-m-d H:i'
            }}
            name="appointment_date"
            className="form-input"
            value={values.appointment_date ?? ''}
            onChange={([date]) => {
              const year = date.getFullYear()
              const month = String(date.getMonth() + 1).padStart(2, '0') // Meses empiezan en 0
              const day = String(date.getDate()).padStart(2, '0')
              const hours = String(date.getHours()).padStart(2, '0')
              const minutes = String(date.getMinutes()).padStart(2, '0')

              // Combinar en el formato deseado
              const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}`
              console.log(formattedDate)
              setFieldValue('appointment_date', formattedDate)
            }}
          />
          {(submitCount && errors.appointment_date) ? <InputError message={errors.appointment_date.toString()} className="mt-2" /> : ''}
        </div>
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('client.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default ClientForm
