import { Field, Form } from 'formik'
import { useRef, useMemo, useState, useEffect } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import { SOURCES, CONTACT_TYPES } from '@/Utils/constants'
import InputError from '@/Components/InputError'
import Select, { type SingleValue } from 'react-select'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, useForm } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type ClientFormType } from './ClientCommon'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type CompanyContact, type OptionType } from '@/types'
import SearchIcon from '@/Components/Icons/SearchIcon'
import PlusIcon from '@/Components/Icons/PlusIcon'
import CompanyModal from './CompanyModal'
import TagPicker, { TagItem } from '@/Components/TagPicker'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

const ClientForm = ({ submitCount, errors, isCreate, setFieldValue, values, contact_type, sources, companies,tags }: {
  submitCount: number
  errors: FormikErrors<ClientFormType>
  setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void
  isCreate: boolean
  values: ClientFormType
  contact_type: string[]
  sources: string[]
  companies: CompanyContact[]
  tags: TagItem[]
}) => {
  const inputRef = useRef<google.maps.places.SearchBox | null>(null)
  const libraries: any[] = ['places']
  const menoLibraries = useMemo(() => libraries, [])
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: menoLibraries
  })

  
   const { data, setData, processing,  patch } = useForm<{ tags: TagItem[] }>({
    tags: tags ?? []
  })

  /* const selectedCompany: SingleValue<OptionType> = {
    value: values.company_contact_id ?? 0,
    label: companies.find((company) => company.id === values.company_contact_id)?.name ?? ''
  } */
  const [showClientModal, setShowClientModal] = useState<boolean>(false)
  const [companiesList, setCompaniesList] = useState<CompanyContact[]>(companies)

  /* useEffect(() => {
    setCompaniesList(companies)
  }, [companies]) */
  const addCompany = (company: CompanyContact) => {
    setCompaniesList(prev => [...prev, company])
    setFieldValue('company_contact_id', company.id)// Opcional: selecciona automáticamente la compañía creada
}

  const selectedCompany = values.company_contact_id
    ? {
        value: values.company_contact_id,
        label: companiesList.find(c => c.id === values.company_contact_id)?.name ?? ''
      }
    : null

  const handleOnPlaceChanged = () => {
    const searchBox = inputRef.current
    if (searchBox) {
      const places = searchBox.getPlaces()
      if (places && places.length > 0) {
        // Usamos setFieldValue para actualizar el valor del campo 'address'
        setFieldValue('address', places[0].formatted_address ?? '')
      }
    }
  }

  return (
    console.log(values),
    <Form className='space-y-5'>
      <div className='grid gap-4 grid-cols-3'>
      <div className={submitCount ? (errors.contact_type) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="contact_type">Contact Type</label>
        <Field
          id="contact_type"
          name="contact_type"
          className="form-select"
          autoComplete="contact_type"
          placeholder='Contact Type'
          as="select"
          onChange={(e: { target: { value: string } }) => {
            setFieldValue('contact_type', e.target.value)
            setFieldValue('cost_delivery', 0)
            setFieldValue('type_of_financing', '')
            setFieldValue('company_contact_id', 0)
          }}
        >
          <option value="">Contact Type</option>
          {contact_type.map((contact_type, index) => (
            <option key={index} value={contact_type}>{contact_type}</option>
          ))}
        </Field>
        {(submitCount && errors.contact_type) ? <InputError message={errors.contact_type} className="mt-2" /> : ''}
        </div>
        <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="name">Name</label>
          <Field
            id="name"
            name="name"
            className="form-input"
            autoComplete={false}
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
            autoComplete={false}
            placeholder='Email'
          />
          {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.secondary_email) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="secondary_email">Secondary Email</label>
          <Field
            id="secondary_email"
            name="secondary_email"
            type="email"
            className="form-input"
            autoComplete={false}
            placeholder='Secondary Email'
          />
          {(submitCount && errors.secondary_email) ? <InputError message={errors.secondary_email} className="mt-2" /> : ''}
        </div>
        <div className={submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="phone">Phone</label>
          <Field
            id="phone"
            name="phone"
            className="form-input"
            autoComplete={false}
            placeholder='Phone'
          />
          {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.other_phone) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="email">Other Phone</label>
          <Field
            id="other_phone"
            name="other_phone"
            className="form-input"
            autoComplete={false}
            placeholder='Other Phone'
          />
          {(submitCount && errors.other_phone) ? <InputError message={errors.other_phone} className="mt-2" /> : ''}
        </div>
        <div className={submitCount ? (errors.source) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="source">Source</label>
        <Field
          id="source"
          name="source"
          className="form-select"
          autoComplete="source"
          placeholder='Source'
          as="select"
          onChange={(e: { target: { value: string } }) => {
            setFieldValue('source', e.target.value)
            setFieldValue('cost_delivery', 0)
            setFieldValue('type_of_financing', '')
            setFieldValue('refer_name', '')
            setFieldValue('refer_phone', '')
          }}
        >
          <option value="">Source</option>
          {sources.map((source, index) => (
            <option key={index} value={source}>{source}</option>
          ))}
        </Field>
        {(submitCount && errors.source) ? <InputError message={errors.source} className="mt-2" /> : ''}
        </div>
        {(values.source === SOURCES.EXTERNAL_REFERAL || values.source === SOURCES.INTERNAL_REFERAL) && (
        <>
        <div className={submitCount ? (errors.refer_name) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="refer_name">Refer Name</label>
          <Field
            id="refer_name"
            name="refer_name"
            className="form-input"
            autoComplete={false}
            placeholder='Refer Name'
          />
          {(submitCount && errors.refer_name) ? <InputError message={errors.refer_name} className="mt-2" /> : ''}
        </div>

        <div className={submitCount ? (errors.refer_phone) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="refer_phone">Refer Phone</label>
          <Field
            id="refer_phone"
            name="refer_phone"
            className="form-input"
            autoComplete={false}
            placeholder='Refer Phone'
          />
          {(submitCount && errors.refer_phone) ? <InputError message={errors.refer_phone} className="mt-2" /> : ''}
        </div>
        </>
        )}
       {(values.contact_type === CONTACT_TYPES.COMMERCIAL_CONTACT) && (
         <div className={submitCount ? (errors.company_contact_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="status">Company</label>
              <div className="flex items-center">
              < div className="flex-grow">
              <Select
                id='company_contact_id'
                placeholder="company"
                name='company_contact_id'
                value={selectedCompany }
                isMulti={false}
               onChange={(option) => {
                 setFieldValue('company_contact_id', option?.value ?? 0)
               }}
                options={companiesList.map(company => ({ value: company.id, label: company.name }))}
                styles={{
                  control: (base) => ({
                    ...base,
                    minHeight: '40px'
                  })
                }}
              />
              </div>
               <button type= "button" title="Create Company" onClick={() => { setShowClientModal(true) }} className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]">
                <PlusIcon className="text-[#fff]" />
              </button>
              </div>
              {(submitCount && errors.company_contact_id) ? <InputError message={errors.company_contact_id} className="mt-2" /> : ''}
            </div>
       )}

        <div className='flex mt-8'>
            <Field
              id="vip_clients"
              name="vip_clients"
              className="form-checkbox"
              type='checkbox'
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                setFieldValue('vip_clients', e.target.checked)
                if (!e.target.checked) {
                  setFieldValue('vip_notes', ' ')
                }
              }}
            />
            <label htmlFor="vip_clients" className='font-bold inline-flex'>VIP</label>
      </div>
      {Number(values.vip_clients) === 1 && (
            <div className='col-span-3'>
              <label htmlFor="vip_notes">Vip Notes</label>
              <Field
                id="vip_notes"
                name="vip_notes"
                component="textarea"
                rows="3"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
              />
            </div>
      )}
      </div>
       <div>
        <label className="block text-sm font-medium text-slate-700">Tags</label>
        <div className="mt-1">
          <TagPicker
            value={data.tags}
            onChange={(t) => { setData('tags', t) }}
            placeholder="Agregar tag"
          />
        </div>
      </div>
      <div className='grid gap-4 grid-cols-2'>
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
                autoComplete={false}
                placeholder='Address'
              />
            </StandaloneSearchBox>
          }
          {(submitCount && errors.address) ? <InputError message={errors.address} className="mt-2" /> : ''}
        </div>
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
              if (!date) return
              const year = date.getFullYear()
              const month = String(date.getMonth() + 1).padStart(2, '0') // Meses empiezan en 0
              const day = String(date.getDate()).padStart(2, '0')
              const hours = String(date.getHours()).padStart(2, '0')
              const minutes = String(date.getMinutes()).padStart(2, '0')

              // Combinar en el formato deseado
              const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}`
              setFieldValue('appointment_date', formattedDate)
            }}
          />
          {(submitCount && typeof errors.appointment_date === 'string') ? <InputError message={errors.appointment_date} className="mt-2" /> : ''}
        </div>
      </div>
      <div className='col-span-4'>
        <label htmlFor="notes">Notes</label>
        <Field
          id="notes"
          name="notes"
          component="textarea"
          rows="4"
          className="form-textarea resize-none placeholder:text-white-dark"
          placeholder='Notes'
        />
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('client.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
     <CompanyModal
        showModal={showClientModal}
        onClose={() => { setShowClientModal(false) }}
        addCompany={addCompany}
      />
    </Form>
  )
}

export default ClientForm
