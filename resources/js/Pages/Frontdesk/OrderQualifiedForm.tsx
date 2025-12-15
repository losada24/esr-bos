import { useState, useRef, useMemo, useEffect, useCallback, type FocusEvent } from 'react'
import { useJsApiLoader } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import {
  type User,
  type OptionType,
  type Attachment,
  type CompanyContact
} from '@/types'
import Select, { type SingleValue } from 'react-select'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { capitalizeWords } from '@/Utils/string'
import { type OrderFormValues } from './OrderCommon'
import PlusIcon from '@/Components/Icons/PlusIcon'
import { ORDER_TYPES } from '@/Utils/constants'
import CompanyModal from './CompanyModal'
import { type Source } from '@/types/interfaces/order'
import ClientModal from './ClientModal'
import { type Client } from '../Client/ClientCommon'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
const GOOGLE_MAPS_LIBRARIES: Array<'places'> = ['places']

const parseAddressComponents = (components: google.maps.GeocoderAddressComponent[] = []) => {
  const find = (type: string) =>
    components.find((component) => component.types.includes(type)) ?? null

  const streetNumber = find('street_number')?.long_name ?? ''
  const route = find('route')?.long_name ?? ''
  const subpremise = find('subpremise')?.long_name ?? ''
  const city =
    find('locality')?.long_name ??
    find('postal_town')?.long_name ??
    find('administrative_area_level_2')?.long_name ??
    ''
  const stateComponent = find('administrative_area_level_1')
  const stateLong = stateComponent?.long_name ?? ''
  const stateShort = stateComponent?.short_name ?? ''
  const postalCode = find('postal_code')?.long_name ?? ''

  return {
    streetNumber,
    route,
    subpremise,
    city,
    stateLong,
    stateShort,
    postalCode
  }
}

const buildJobAddress = (streetNumber: string, route: string, subpremise: string) => {
  const base = [streetNumber, route].filter(Boolean).join(' ').trim()
  if (!base) return ''
  if (!subpremise) return base
  return `${base} ${subpremise}`.trim()
}

const OrderQualifiedForm = ({
  submitCount,
  errors,
  isCreate,
  clients,
  setFieldValue,
  values,
  owners,
  attachments,
  status,
  sources,
  order_types,
  companies,
  sourcesClients,
  frame_colors,
  glass_colors,
  glass_types,
  glass_coatings,
  languages
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
  sources: Source[]
  order_types: string[]
  companies: CompanyContact[]
  sourcesClients: string[]
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  glass_coatings: string[]
  languages: string[]
}) => {
  const jobAddressInputRef = useRef<HTMLInputElement | null>(null)
  const autocompleteInstanceRef = useRef<google.maps.places.Autocomplete | null>(null)
  const autocompleteListenerRef = useRef<google.maps.MapsEventListener | null>(null)
  const geocoderRef = useRef<google.maps.Geocoder | null>(null)
  const menoLibraries = useMemo(() => GOOGLE_MAPS_LIBRARIES, [])
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: menoLibraries
  })

  const [companiesList, setCompaniesList] = useState<CompanyContact[]>(companies)
  const [clientsList, setClientsList] = useState<Client[]>(clients)
  const [showCompanyModal, setShowCompanyModal] = useState<boolean>(false)
  const [showClientModal, setShowClientModal] = useState<boolean>(false)

  useEffect(() => {
    if (!isLoaded) return
    geocoderRef.current = new google.maps.Geocoder()
  }, [isLoaded])

  /* const addCompany = (company: CompanyContact) => {
    setCompaniesList(prev => [...prev, company])
    setFieldValue('company_contact_id', company.id)// Opcional: selecciona automáticamente la compañía creada
  } */
  const [modalTargetField, setModalTargetField] = useState<string | null>(null)
  const [modalTargetClientField, setModalTargetClientField] = useState<string | null>(null)

  const onCompanyCreated = (company: CompanyContact) => {
    setCompaniesList(prev =>
      prev.some(c => c.id === company.id) ? prev : [...prev, company]
    )
    if (modalTargetField) setFieldValue(modalTargetField, company.id)
    setShowCompanyModal(false)
    setModalTargetField(null)
  }
  const companyOptions = companiesList.map(c => ({ value: c.id, label: c.name }))
  /* const selectedCompany = values.company_contact_id
    ? {
        value: values.company_contact_id,
        label: companiesList.find(c => c.id === values.company_contact_id)?.name ?? ''
      }
    : null */
  const selectedCompany = values.company_contact_id
    ? companyOptions.find(o => o.value === values.company_contact_id) ?? null
    : null

  const selectedAssociateCompany1 = values.associate_company_contact_id_1
    ? companyOptions.find(o => o.value === values.associate_company_contact_id_1) ?? null
    : null

  const selectedAssociateCompany2 = values.associate_company_contact_id_2
    ? companyOptions.find(o => o.value === values.associate_company_contact_id_2) ?? null
    : null

  /* const addClient = (client: Client) => {
    setClientsList(prev => [...prev, client])
    setFieldValue('client_id', client.id)// Opcional: selecciona automáticamente el cliente creado
  } */
  const onClientCreated = (client: Client) => {
    setClientsList(prev =>
      prev.some(c => c.id === client.id) ? prev : [...prev, client]
    )
    console.log('modalTargetClientField', modalTargetClientField)
    console.log('client.id', client.id)
    console.log('setClientsList', setClientsList.toString())
    if (modalTargetClientField) setFieldValue(modalTargetClientField, client.id)
    setShowClientModal(false)
    setModalTargetClientField(null)
  }
  const clientsOptions = clientsList.map(c => ({ value: c.id, label: c.name }))

  const selectedClient = values.client_id
    ? clientsOptions.find(o => o.value === values.client_id) ?? null
    : null

  const selectedAssociateClient1 = values.associate_client_id_1
    ? clientsOptions.find(o => o.value === values.associate_client_id_1) ?? null
    : null

  const selectedAssociateClient2 = values.associate_client_id_2
    ? clientsOptions.find(o => o.value === values.associate_client_id_2) ?? null
    : null

  /* const selectedClient = values.client_id
    ? {
        value: values.client_id,
        label: clientsList.find(c => c.id === values.client_id)?.name ?? ''
      }
    : null */

  const syncAddressFromString = useCallback((address: string) => {
    const trimmedAddress = address.trim()
    setFieldValue('job_address', trimmedAddress)
    if (!trimmedAddress || !geocoderRef.current) return

    geocoderRef.current.geocode({ address: trimmedAddress }, (results, status) => {
      if (status !== 'OK' || !results || results.length === 0) return
      const components = results[0].address_components ?? []
      const {
        streetNumber,
        route,
        subpremise,
        city,
        stateLong,
        stateShort,
        postalCode
      } = parseAddressComponents(components)

      const jobAddress = buildJobAddress(streetNumber, route, subpremise)
      if (jobAddress) {
        setFieldValue('job_address', jobAddress)
      }

      if (city) setFieldValue('city', city)
      if (stateShort || stateLong) setFieldValue('job_state', stateShort || stateLong)
      if (postalCode) setFieldValue('job_zip', postalCode)
    })
  }, [setFieldValue])

  const handlePlaceResult = useCallback((placeResult?: google.maps.places.PlaceResult | null) => {
    const inputValue = jobAddressInputRef.current?.value ?? ''
    const formattedAddress =
      placeResult?.formatted_address ??
      (placeResult as { formattedAddress?: string } | undefined)?.formattedAddress ??
      inputValue
    const components =
      placeResult?.address_components ??
      (placeResult as { addressComponents?: google.maps.GeocoderAddressComponent[] } | undefined)?.addressComponents ??
      []

    const {
      streetNumber,
      route,
      subpremise,
      city,
      stateLong,
      stateShort,
      postalCode
    } = parseAddressComponents(components)

    const jobAddress = buildJobAddress(streetNumber, route, subpremise) || formattedAddress
    if (jobAddress) {
      setFieldValue('job_address', jobAddress)
    } else if (formattedAddress) {
      setFieldValue('job_address', formattedAddress)
    }

    if (city) {
      setFieldValue('city', city)
    }
    if (stateShort || stateLong) setFieldValue('job_state', stateShort || stateLong)
    if (postalCode) setFieldValue('job_zip', postalCode)

    if ((!city || !(stateShort || stateLong) || !postalCode) && (jobAddress || formattedAddress)) {
      syncAddressFromString(jobAddress || formattedAddress)
    }
  }, [setFieldValue, syncAddressFromString])

  useEffect(() => {
    if (!isLoaded || typeof google === 'undefined' || !google.maps?.places || !jobAddressInputRef.current) return

    const options: google.maps.places.AutocompleteOptions = {
      fields: ['address_components', 'formatted_address'],
      types: ['address']
    }

    const autocomplete = new google.maps.places.Autocomplete(jobAddressInputRef.current, options)
    autocompleteInstanceRef.current = autocomplete

    const listener = autocomplete.addListener('place_changed', () => {
      handlePlaceResult(autocomplete.getPlace())
    })
    autocompleteListenerRef.current = listener

    return () => {
      if (autocompleteListenerRef.current) {
        google.maps.event.removeListener(autocompleteListenerRef.current)
        autocompleteListenerRef.current = null
      }
      autocompleteInstanceRef.current = null
    }
  }, [isLoaded, handlePlaceResult])
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
    label: sources.find(source => source.name === values.source)?.name ?? ''
  }

const selectedSourceClients: SingleValue<OptionType> = {
    value: values.source ?? '',
    label: sourcesClients.find((source) => source === values.source) ?? ''
  }
  console.log('frame_colors ->', frame_colors)
  return (
    <>
      <Form className='space-y-5'>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Order Information</legend>
          <div className='grid gap-4 grid-cols-4'>
             <div className={submitCount ? (errors.order_type) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="order_type">Order Type</label>
            <Field
              id="order_type"
              name="order_type"
              className="form-select"
              autoComplete="order_type"
              placeholder='Order Type'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('order_type', e.target.value)
                // setFieldValue('status', '') // Reset status when order type changes
                setFieldValue('source', '') // Reset source when order type changes
                setFieldValue('company_contact_id', null) // Reset company contact when order type changes
                setFieldValue('associate_company_contact_id_1', null) // Reset associate company contact 1 when order type changes
                setFieldValue('associate_company_contact_id_2', null) // Reset associate company contact 2 when order type changes
                setFieldValue('associate_client_id_1', null) // Reset associate client 1 when order type changes
                setFieldValue('associate_client_id_2', null) // Reset associate client 2 when order type changes
              }}
            >
              <option value="">Order Type</option>
              {order_types.map((order_type, index) => (
                <option key={index} value={order_type}>{order_type}</option>
              ))}
            </Field>
            {(submitCount && errors.order_type) ? <InputError message={errors.order_type} className="mt-2" /> : ''}
            </div>
             <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="name">Order Name</label>
                <Field
                  id="name"
                  name="name"
                  type="text"
                  className="form-input"
                  autoComplete="name"
                  placeholder='Order Name'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    const value = e.target.value
                    setFieldValue('name', value)
                  }}
                />
                {(submitCount && errors.name) ? <InputError message={errors.name} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.job_address ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="job_address"> Job Address</label>
                    <Field
                      id="job_address"
                      name="job_address"
                      className="form-input placeholder:text-white-dark"
                      autoComplete="off"
                      placeholder="Address"
                      innerRef={jobAddressInputRef}
                      onBlur={(e: FocusEvent<HTMLInputElement>) => {
                        const value = e.target.value ?? ''
                        if (value.trim()) {
                          syncAddressFromString(value)
                        }
                      }}
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setFieldValue('job_address', e.target.value)
                      }}
                    />
                {(submitCount && errors.job_address) ? <InputError message={errors.job_address} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.city ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="city">City</label>
                <Field
                  id="city"
                  name="city"
                  className="form-input placeholder:text-white-dark"
                  autoComplete="off"
                  placeholder="City"
                />
                  {(submitCount && errors.city) ? <InputError message={errors.city} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.job_state ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="state">State</label>
                <Field
                  id="job_state"
                  name="job_state"
                  className="form-input placeholder:text-white-dark"
                  autoComplete="off"
                  placeholder="State"
                />
                {(submitCount && errors.job_state) ? <InputError message={errors.job_state} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.job_zip ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="zip">ZIP Code</label>
                <Field
                  id="job_zip"
                  name="job_zip"
                  className="form-input placeholder:text-white-dark"
                  autoComplete="off"
                  placeholder="ZIP Code"
                />
                {(submitCount && errors.job_zip) ? <InputError message={errors.job_zip} className="mt-2" /> : ''}
              </div>
                <div className={submitCount ? (errors.client_id ? 'has-error' : 'has-success') : ''}>
                    <label htmlFor="client_id">Contact Name</label>
                    <div className="flex items-center">
                      <div className="flex-grow">
                        <Select
                          id="client_id"
                          inputId="client_id"
                          name="client_id"
                          placeholder="Client"
                          value={selectedClient}
                          isMulti={false}
                         onChange={(option) => {
                           const clientId = (option as any)?.value ?? null
                           setFieldValue('client_id', clientId)
                           if (clientId == null) {
                             setFieldValue('company_contact_id', null)
                             return
                           }
                           const c = clientsList.find(x => Number(x.id) === clientId)
                           const companyId = c?.company_contact_id != null ? Number(c.company_contact_id) : null
                           setFieldValue('company_contact_id', companyId)
                         }}
                          options={clientsOptions}
                          styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                        />
                      </div>
                      <button
                        type="button"
                        title="Create Client"
                        onClick={() => { setModalTargetClientField('client_id'); setShowClientModal(true) }}
                        className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                      >
                        <PlusIcon className="text-[#fff]" />
                      </button>
                    </div>
                    {(submitCount && errors.client_id) ? <InputError message={errors.client_id} className="mt-2" /> : null}
                  </div>
                  <div className={submitCount ? (errors.schedule_appointment) ? 'has-error' : 'has-success' : ''}>
                           <label htmlFor="schedule_appointment">Appointment Date</label>
                           <Flatpickr
                             options={{
                               enableTime: true,
                               dateFormat: 'Y-m-d H:i'
                             }}
                             name="schedule_appointment"
                             className="form-input"
                             value={values.schedule_appointment ?? ''}
                             onChange={([date]) => {
                               if (!date) return
                               const year = date.getFullYear()
                               const month = String(date.getMonth() + 1).padStart(2, '0') // Meses empiezan en 0
                               const day = String(date.getDate()).padStart(2, '0')
                               const hours = String(date.getHours()).padStart(2, '0')
                               const minutes = String(date.getMinutes()).padStart(2, '0')
                               // Combinar en el formato deseado
                               const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}`
                               setFieldValue('schedule_appointment', formattedDate)
                             }}
                           />
                           {(submitCount && typeof errors.schedule_appointment === 'string') ? <InputError message={errors.schedule_appointment} className="mt-2" /> : ''}
                         </div>
             {/* <div className={submitCount ? (errors.source) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="source">Clients Source</label>
              <Select
                id='source'
                placeholder="sources"
                name='source'
                defaultValue={selectedSourceClients}
                isMulti={false}
                onChange={(value) => { setFieldValue('source', value) }}
                options={sourcesClients.map((source) => { return { label: source, value: source } })}
              />
              {(submitCount && errors.source) ? <InputError message={errors.source} className="mt-2" /> : ''}
            </div> */}
               {(values.order_type === ORDER_TYPES.COMMERCIAL) && (
                <>
                <div className={submitCount ? (errors.company_contact_id ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="company_contact_id">Company</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        inputId="company_contact_id"
                        name="company_contact_id"
                        placeholder="Company"
                        value={selectedCompany}
                        isMulti={false}
                        onChange={(option) => {
                          setFieldValue('company_contact_id', (option as any)?.value ?? 0)
                        }}
                        options={companyOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Company"
                      onClick={() => { setModalTargetField('company_contact_id'); setShowCompanyModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && errors.company_contact_id) ? <InputError message={errors.company_contact_id as any} className="mt-2" /> : null}
                </div>

               {/* <div className={submitCount ? ((errors as any).associate_company_contact_id_1 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_company_contact_id_1">Other Company Associate 1</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_company_contact_id_1"
                        name="associate_company_contact_id_1"
                        placeholder="Company"
                        value={selectedAssociateCompany1}
                        isMulti={false}
                        onChange={(option) => {
                          const companyId = option ? Number((option as any).value) : null
                          setFieldValue('associate_company_contact_id_1', companyId)

                          if (companyId == null) {
                            setFieldValue('associate_client_id_1', null)
                            return
                          }

                          const matches = clientsList.filter(c => Number(c.company_contact_id) === companyId)
                          setFieldValue('associate_client_id_1', matches.length > 0 ? Number(matches[0].id) : null)
                        }}
                        options={companyOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Company"
                      onClick={() => { setModalTargetField('associate_company_contact_id_1'); setShowCompanyModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_company_contact_id_1)
                    ? <InputError message={(errors as any).associate_company_contact_id_1} className="mt-2" />
                    : null}
                </div>
                <div className={submitCount ? ((errors as any).associate_client_id_1 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_client_id_1">Other Contact Associate 1</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_client_id_1"
                        inputId="associate_client_id_1"
                        name="associate_client_id_1"
                        placeholder="Client"
                        value={selectedAssociateClient1}
                        isMulti={false}
                         onChange={(option) => {
                           setFieldValue('associate_client_id_1', (option as any)?.value ?? null)
                         }}
                        options={clientsOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Client"
                      onClick={() => { setModalTargetClientField('associate_client_id_1'); setShowClientModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_client_id_1)
                    ? <InputError message={(errors as any).associate_client_id_1} className="mt-2" />
                    : null}
                </div>
                  <div className={submitCount ? ((errors as any).associate_company_contact_id_2 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_company_contact_id_2">Other Company Associate 2</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_company_contact_id_2"
                        name="associate_company_contact_id_2"
                        placeholder="Company"
                        value={selectedAssociateCompany2}
                        isMulti={false}
                        onChange={(option) => {
                          const companyId = option ? Number((option as any).value) : null
                          setFieldValue('associate_company_contact_id_2', companyId)

                          if (companyId == null) {
                            setFieldValue('associate_client_id_2', null)
                            return
                          }

                          const matches = clientsList.filter(c => Number(c.company_contact_id) === companyId)
                          setFieldValue('associate_client_id_2', matches.length > 0 ? Number(matches[0].id) : null)
                        }}
                        options={companyOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Company"
                      onClick={() => { setModalTargetField('associate_company_contact_id_2'); setShowCompanyModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_company_contact_id_2)
                    ? <InputError message={(errors as any).associate_company_contact_id_2} className="mt-2" />
                    : null}
                </div>
                <div className={submitCount ? ((errors as any).associate_client_id_2 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_client_id_2">Other Contact Associate 2</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_client_id_2"
                        inputId="associate_client_id_2"
                        name="associate_client_id_2"
                        placeholder="Client"
                        value={selectedAssociateClient2}
                        isMulti={false}
                        onChange={(option) => {
                          setFieldValue('associate_client_id_2', (option as any)?.value ?? null)
                        }}
                        options={clientsOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Client"
                      onClick={() => { setModalTargetClientField('associate_client_id_2'); setShowClientModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_client_id_2)
                    ? <InputError message={(errors as any).associate_client_id_2} className="mt-2" />
                    : null}
                </div> */}
                 <div className={submitCount ? (errors.bid_due_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="bid_due_date">Bid Due Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="bid_due_date"
                value={values.bid_due_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('bid_due_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.bid_due_date) ? <InputError message={errors.bid_due_date.toString()} className="mt-2" /> : ''}
            </div>

             <div className={submitCount ? (errors.source) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="source">Sources</label>
              <Select
                id='source'
                placeholder="sources"
                name='source'
                defaultValue={selectedSource}
                isMulti={false}
                onChange={(value) => { setFieldValue('source', value) }}
               options={sources.map((source) => { return { label: source.name, value: source.id } })}
              />
              {(submitCount && errors.source) ? <InputError message={errors.source} className="mt-2" /> : ''}
            </div>
                </>
               )}
             {/* <div className={submitCount ? (errors.status) ? 'has-error' : 'has-success' : ''}>
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
            </div> */}
             <div className='flex mt-8'>
                <Field
                  id="is_supply"
                  name="is_supply"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('is_supply', e.target.checked)
                  }}
                />
                <label htmlFor="is_supply" className='font-bold inline-flex'>Supply</label>
              </div>
              {/* <div className='col-span-4'>
              <label htmlFor="description"> Description</label>
              <Field
                id="description"
                name="description"
                component="textarea"
                rows="3"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Description'
              />
            </div> */}
            <div className={`col-span-4 ${submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}`}>
              <label htmlFor="notes"> Notes</label>
              <Field
                id="notes"
                name="notes"
                component="textarea"
                rows="3"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
              />
              {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
            </div>
            </div>
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
        <legend className='text-lg font-semibold px-3'>Sales Information</legend>
        <div className='grid grid-cols-1 gap-4'>
        <fieldset className='p-3 border rounded-xl w-full'>
          <legend className='text-sm font-semibold px-3'>Type of Work and / or Service</legend>
          <div className="flex items-center gap-6 mt-4">
            <div className="flex items-center gap-2">
                <Field
                  id="sale"
                  name="sale"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('sale', e.target.checked)
                  }}
                />
                <label htmlFor="sale" className='font-bold inline-flex'>Sale</label>
              </div>
             <div className="flex items-center gap-2">
                <Field
                  id="installation"
                  name="installation"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('installation', e.target.checked)
                  }}
                />
                <label htmlFor="installation" className='font-bold inline-flex'>Installation</label>
            </div>
            <div className="flex items-center gap-2">
                <Field
                  id="permit"
                  name="permit"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('permit', e.target.checked)
                  }}
                />
                <label htmlFor="permit" className='font-bold inline-flex'>Permit</label>
            </div>
             <div className={submitCount ? (errors.hoa ? 'has-error' : 'has-success') : ''}>
            <div className="flex items-center gap-2 mt-4 md:mt-0">
              <Field
                id="hoa"
                name="hoa"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('hoa', e.target.checked)
                }}
              />
              <label htmlFor="hoa" className='font-bold inline-flex'>HOA</label>
            </div>
            {(submitCount && errors.hoa && typeof errors.hoa === 'string')
              ? <InputError message={errors.hoa} className="mt-2" />
              : null}
          </div>
           {/* <div className="flex items-center gap-2">
                <Field
                  id="replacement"
                  name="replacement"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('replacement', e.target.checked)
                  }}
                />
              <label htmlFor="replacement" className='font-bold inline-flex'>Replacement</label>
          </div> */}
          {/* <div className="flex items-center gap-2">
                <Field
                  id="new_construction"
                  name="new_construction"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('new_construction', e.target.checked)
                  }}
                />
              <label htmlFor="new_construction" className='font-bold inline-flex'>New Construction</label>
          </div> */}
          <div className="flex items-center gap-2">
                <Field
                  id="financing"
                  name="financing"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('financing', e.target.checked)
                  }}
                />
              <label htmlFor="financing" className='font-bold inline-flex'>Financing</label>
          </div>
          </div>
        </fieldset>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        </div>
       <fieldset className="p-3 border rounded-xl w-full col-start-1 mt-4">
          <legend className="text-sm font-semibold px-3"> Project Specifications</legend>
          <div className="grid grid-cols-6 gap-6 mt-4">
            <div className={submitCount ? (errors.frame_color) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="frame_color">Frame Color</label>
            <Field
              id="frame_color"
              name="frame_color"
              className="form-select"
              autoComplete="frame_color"
              placeholder='frame_color'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('frame_color', e.target.value)
              }}
            >
              <option value="">Frame Color</option>
              {frame_colors?.map((frame_color, index) => (
                <option key={index} value={frame_color}>{frame_color}</option>
              ))}
            </Field>
            {(submitCount && errors.frame_color) ? <InputError message={errors.frame_color} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.glass_color) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="glass_color">Glass Color</label>
            <Field
              id="glass_color"
              name="glass_color"
              className="form-select"
              autoComplete="glass_color"
              placeholder='glass_color'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('glass_color', e.target.value)
              }}
            >
              <option value="">Glass Color</option>
              {glass_colors?.map((glass_color, index) => (
                <option key={index} value={glass_color}>{glass_color}</option>
              ))}
            </Field>
            {(submitCount && errors.glass_color) ? <InputError message={errors.glass_color} className="mt-2" /> : ''}
            </div>

            
          <div className={submitCount ? (errors.language ? 'has-error' : 'has-success') : ''}>
            <label htmlFor="language">Language</label>
            <Field
              id="language"
              name="language"
              className="form-select"
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('language', e.target.value)
              }}
            >
              <option value="">Select Language</option>
              {languages.map((language) => (
                <option key={language} value={language}>
                  {capitalizeWords(language)}
                </option>
              ))}
            </Field>
            {(submitCount && errors.language && typeof errors.language === 'string')
              ? <InputError message={errors.language} className="mt-2" />
              : null}
          </div>
          {/* <div className={submitCount ? (errors.glass_color) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="glass_type">Glass Type</label>
            <Field
              id="glass_type"
              name="glass_type"
              className="form-select"
              autoComplete="glass_type"
              placeholder='glass_type'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('glass_type', e.target.value)
              }}
            >
              <option value="">Glass Type</option>
              {glass_types?.map((glass_type, index) => (
                <option key={index} value={glass_type}>{glass_type}</option>
              ))}
            </Field>
            {(submitCount && errors.glass_type) ? <InputError message={errors.glass_type} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.glass_coating) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="glass_coating">Glass Coating</label>
            <Field
              id="glass_coating"
              name="glass_coating"
              className="form-select"
              autoComplete="glass_coating"
              placeholder='glass_coating'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('glass_coating', e.target.value)
              }}
            >
              <option value="">Glass Coating</option>
              {glass_coatings?.map((glass_coating, index) => (
                <option key={index} value={glass_coating}>{glass_coating}</option>
              ))}
            </Field>
            {(submitCount && errors.glass_coating) ? <InputError message={errors.glass_coating} className="mt-2" /> : ''}
            </div> */}
             <div className={submitCount ? (errors.door_quantity) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="door_quantity">Door Quantity</label>
              <Field
                id="door_quantity"
                name="door_quantity"
                className="form-input text-right"
                autoComplete="door_quantity"
                placeholder='Door Quantity'
                type='number'
              />
              {(submitCount && errors.door_quantity) ? <InputError message={errors.door_quantity} className="mt-2" /> : ''}
            </div>
             <div className={submitCount ? (errors.window_quantity) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="window_quantity">Window Quantity</label>
                <Field
                  id="window_quantity"
                  name="window_quantity"
                  className="form-input text-right"
                  autoComplete="window_quantity"
                  placeholder='Window Quantity'
                  type='number'
                />
                {(submitCount && errors.window_quantity) ? <InputError message={errors.window_quantity} className="mt-2" /> : ''}
              </div>
             {/* <div className="flex items-center gap-2">
                <Field
                  id="screen"
                  name="screen"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('screen', e.target.checked)
                  }}
                />
              <label htmlFor="screen" className='font-bold inline-flex'>Screen</label>
            </div>
            <div className="flex items-center gap-2">
                <Field
                  id="door_design"
                  name="door_design"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('door_design', e.target.checked)
                  }}
                />
              <label htmlFor="door_design" className='font-bold inline-flex'>Door Design</label>
            </div>
            <div className="flex items-center gap-2">
              <Field
                id="mountin"
                name="mountin"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('mountin', e.target.checked)
                }}
              />
            <label htmlFor="mountin" className='font-bold inline-flex'>Mounting</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="bar"
                name="bar"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('bar', e.target.checked)
                }}
              />
            <label htmlFor="bar" className='font-bold inline-flex'>Bar</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="shutter_hole"
                name="shutter_hole"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('shutter_hole', e.target.checked)
                }}
              />
            <label htmlFor="shutter_hole" className='font-bold inline-flex'>Shutter Hole</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="floor_cutting"
                name="floor_cutting"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('floor_cutting', e.target.checked)
                }}
              />
            <label htmlFor="floor_cutting" className='font-bold inline-flex'>Floor Cutting</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="interior_finish"
                name="interior_finish"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('interior_finish', e.target.checked)
                }}
              />
            <label htmlFor="interior_finish" className='font-bold inline-flex'>Interior Finish</label>
          </div> */}
            </div>
        </fieldset>
        </div>
        </fieldset>
        <div className="flex items-center justify-between mt-4">
          <Link className='btn btn-danger uppercase' href={route('frontdesk.index')}>Cancel</Link>
          <PrimaryButton className="btn btn-primary" type='submit'>
            {isCreate ? 'Create' : 'Save'}
          </PrimaryButton>
        </div>
        <CompanyModal
          showModal={showCompanyModal}
          onClose={() => { setShowCompanyModal(false); setModalTargetField(null) }}
          onConfirm={onCompanyCreated}
        />
        <ClientModal
          showModal={showClientModal}
          // onClose={() => { setShowClientModal(false) }}
           onClose={() => { setShowClientModal(false); setModalTargetClientField(null) }}
           onConfirm={onClientCreated}
          // addClient={addClient}
          sourcesClients={sourcesClients}
      />
      </Form>
    </>
  )
}

export default OrderQualifiedForm
