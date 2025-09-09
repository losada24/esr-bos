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
  sourcesClients
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
}) => {
  const inputRef = useRef<google.maps.places.SearchBox | null>(null)
  const libraries: any[] = ['places']
  const menoLibraries = useMemo(() => libraries, [])
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: menoLibraries
  })

  const [companiesList, setCompaniesList] = useState<CompanyContact[]>(companies)
  const [clientsList, setClientsList] = useState<Client[]>(clients)
  const [showCompanyModal, setShowCompanyModal] = useState<boolean>(false)
  const [showClientModal, setShowClientModal] = useState<boolean>(false)

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
    label: sources.find(source => source.name === values.source)?.name ?? ''
  }
const selectedSourceClients: SingleValue<OptionType> = {
    value: values.source ?? '',
    label: sourcesClients.find((source) => source === values.source) ?? ''
  }
  //console.log('selectedStatus', selectedStatus)
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
                  <label htmlFor="address"> Job Address</label>
                    <Field
                      id="job_address"
                      name="job_address"
                      className="form-textarea resize-none placeholder:text-white-dark"
                      autoComplete="off"
                      placeholder="Address"
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
              <div className='col-span-4'>
              <label htmlFor="description"> Description</label>
              <Field
                id="description"
                name="description"
                component="textarea"
                rows="3"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Description'
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
