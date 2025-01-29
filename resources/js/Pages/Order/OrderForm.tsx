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
  type TypeOfWork,
  type DurationOfWork,
  type InstallationTeam,
  type ProductConfig,
  type TravelCost,
  type TypeOfHousing,
  type TypeOfProduct,
  type ProductCategory,
  type ProductCost,
  type OrderProduct,
  type OptionType,
  type Attachment
} from '@/types'
import Select, { type SingleValue } from 'react-select'
import { getOrderProducts, getValueIdNotNull, type OrderFormValues } from './OrderCommon'
import SearchIcon from '@/Components/Icons/SearchIcon'
import ProductModal from './ProductModal'
import ProductTable from './ProductTable'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { PAYMENT_METHODS, SERVICES } from '@/Utils/constants'
import { capitalizeWords } from '@/Utils/string'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

const OrderForm = ({
  submitCount,
  errors,
  isCreate,
  // clients,
  setFieldValue,
  values,
  owners,
  type_of_works,
  types_of_housing,
  installation_teams,
  supervisors,
  methods_of_payment,
  services,
  travel_costs,
  duration_of_works,
  products_config,
  type_of_products,
  product_category,
  product_costs,
  frame_colors,
  attachments,
  status,
  type_of_financing
}: {
  submitCount: number
  errors: FormikErrors<OrderFormValues>
  isCreate: boolean
  featured_image?: string
  clients: Client[]
  setFieldValue: (field: string, value: any) => void
  values: OrderFormValues
  owners: User[]
  type_of_works: TypeOfWork[]
  types_of_housing: TypeOfHousing[]
  installation_teams: InstallationTeam[]
  supervisors: User[]
  methods_of_payment: string[]
  services: string[]
  travel_costs: TravelCost[]
  duration_of_works: DurationOfWork[]
  products_config: ProductConfig[]
  type_of_products: TypeOfProduct[]
  product_category: ProductCategory[]
  product_costs: ProductCost[]
  frame_colors: string[]
  attachments?: Attachment[]
  status: string[]
  type_of_financing: string[]
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
  const [orderProducts, setOrderProducts] = useState<OrderProduct[]>(
    values.order_products?.map((orderProduct) => {
      return getOrderProducts(orderProduct)
    }) ?? []
  )
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

  const updateOrderProduct = (index: number) => {
    console.log('updateOrderProduct', index)
  }

  const addOrderProduct = (orderProduct: OrderProduct) => {
    const orderProductsList = [...orderProducts, orderProduct]
    setOrderProducts(orderProductsList)
    setFieldValue('orderProducts', orderProductsList)
  }

  const selectDeliveryAndInstallationDate = async (payment_factory_date: string, cityPermits: boolean) => {
    const travel_cost_id = 'value' in ((values.travel_cost_id) as any) ? (values.travel_cost_id as any).value : 0
    const response = await fetch(
      `/order/get_delivery_and_installation_date/${payment_factory_date}/${values.type_of_housing_id}/${travel_cost_id}/${values.service}/${cityPermits}`)
    const data = await response.json()
    console.log(response)

    setFieldValue('eta_date', data.estimate_eta_date)
    setFieldValue('delivery_date', data.estimate_delivery_date)
    setFieldValue('installation_date', data.estimate_installation_date)
    const duration_of_work_value = getValueIdNotNull(values.duration_of_work_id)
    if (duration_of_work_value !== '') {
      const duration_of_work = duration_of_works.find((duration_of_work) => duration_of_work.id === duration_of_work_value)
      if (duration_of_work) {
        const installation_end_date1 = new Date(data.estimate_installation_date)
        // console.log(installation_end_date1.getDate())
        installation_end_date1.setDate(installation_end_date1.getDate() + duration_of_work.number_of_day)
        const year = installation_end_date1.getFullYear()
        const month = String(installation_end_date1.getMonth() + 1).padStart(2, '0') // Agregar cero inicial si es necesario
        const day = String(installation_end_date1.getDate()).padStart(2, '0')// Agregar cero inicial si es necesario
        // Formato yyyy-mm-dd
        const fechaFormateada = `${year}-${month}-${day}`
        setFieldValue('installation_end_date', fechaFormateada)
      }
    }
  }

  const selectDeliveryAndPickupDate = async (payment_factory_date: string) => {
    const response = await fetch(
      `/order/get_delivery_and_pickup_date/${payment_factory_date}`)
    const data = await response.json()

    setFieldValue('eta_date', data.estimate_eta_date)
    setFieldValue('delivery_date', data.estimate_delivery_date)
  }

  const removeOrderProduct = (index: number) => {
    const orderProductList = orderProducts.filter((_, i) => i !== index)
    setOrderProducts(orderProductList)
    setFieldValue('orderProducts', orderProductList)
  }

  const selectedSupervisor: SingleValue<OptionType> = {
    value: values.supervisor_id ?? 0,
    label: supervisors.find((supervisor) => supervisor.id === values.supervisor_id)?.name ?? ''
  }

  const selectedStatus: SingleValue<OptionType> = {
    value: values.status ?? '',
    label: status.find((status) => status === values.status) ?? ''
  }

  const selectedTravelCost: SingleValue<OptionType> = {
    value: values.travel_cost_id ?? 0,
    label: travel_costs.find((travel_cost) => travel_cost.id === values.travel_cost_id)?.name ?? ''
  }

  const selectedDurationOfWork: SingleValue<OptionType> = {
    value: values.duration_of_work_id ?? 0,
    label: duration_of_works.find((duration_of_work) => duration_of_work.id === values.duration_of_work_id)?.name ?? ''
  }
  return (
    <>
      <Form className='space-y-5'>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Client Information</legend>
          <div className='grid gap-4 grid-cols-3'>
            <div className={submitCount ? (errors.last_name) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="last_name">Name</label>
              <div className='flex'>
                <Field
                  id="client_name"
                  name="client_name"
                  className="form-input rounded-r-none"
                  autoComplete="client_name"
                  placeholder='Name'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    const formattedValue = capitalizeWords(e.target.value)
                    setFieldValue('client_name', formattedValue)
                  }}
                />
                <button onClick={(e) => {
                  e.preventDefault()
                  alert('search from bigin')
                }} className="bg-[#eee] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md px-3 font-semibold border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]">
                  <SearchIcon className="text-[#eee]" />
                </button>
              </div>
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
            <div className={submitCount ? (errors.email) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="email">Email</label>
              <Field
                id="email"
                name="email"
                className="form-input"
                autoComplete="email"
                placeholder='Email'
              />
              {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
            </div>
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
                <div className=' flex mt-8'>
                  <Field
                    id="do_not_send_email"
                    name="do_not_send_email"
                    className="form-checkbox"
                    type='checkbox'
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                      setFieldValue('do_not_send_email', e.target.checked)
                    } }
                  />
                  <label htmlFor="do_not_send_email" className='font-bold inline-flex' >Do Not Send Email</label>
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
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Order Information</legend>
          <div className='grid gap-4 grid-cols-4'>
            <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="name">Name</label>
              <Field
                id="name"
                name="name"
                className="form-input"
                autoComplete="name"
                placeholder='Name'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  const formattedValue = capitalizeWords(e.target.value)
                  setFieldValue('name', formattedValue)
                }}
              />
              {(submitCount && errors.name) ? <InputError message={errors.name} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.order_number) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="order_number">Order Number</label>
              <Field
                id="order_number"
                name="order_number"
                className="form-input"
                autoComplete="order_number"
                placeholder='Order Number'
              />
              {(submitCount && errors.order_number) ? <InputError message={errors.order_number} className="mt-2" /> : ''}
            </div>
          { /* <div className={submitCount ? (errors.job_address) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="job_address">Job Address</label>
              <Field
                id="job_address"
                name="job_address"
                className="form-input"
                autoComplete="job_address"
                placeholder='Job Address'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  const formattedValue = capitalizeWords(e.target.value)
                  setFieldValue('job_address', formattedValue)
                }}
              />
              {(submitCount && errors.job_address) ? <InputError message={errors.job_address} className="mt-2" /> : ''}
            </div> */}
              <div className={submitCount ? (errors.job_address ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="address"> Job Address</label>
              {/* Aquí se configura el componente StandaloneSearchBox para autocompletar direcciones */}
              {isLoaded && (
                <StandaloneSearchBox
                  onLoad={(ref) => {
                    inputRef.current = ref // Asignamos la referencia al componente SearchBox
                  }}
                  onPlacesChanged={handleOnPlaceChanged} // Cuando cambie el lugar, ejecutamos la función para actualizar los campos
                >
                  <Field
                    id="job_address"
                    name="job_address"
                    className="form-textarea resize-none placeholder:text-white-dark"
                    autoComplete="off"
                    placeholder="Address"
                  />
                </StandaloneSearchBox>
              )}
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
              {submitCount && errors.job_zip && <InputError message={errors.job_zip} className="mt-2" />}
            </div>

            <div className={submitCount ? (errors.owners) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="owners">Owner</label>
              <Select
                id='owners'
                placeholder="Select Owners"
                name='owners'
                defaultValue={ values.owners.map((owner) => { return { label: owner.name, value: owner.id } }) }
                onChange={(value) => {
                  setFieldValue('owners', value)
                }}
                isMulti={true}
                options={owners.map((owner) => { return { label: owner.name, value: owner.id } })}
              />
              {(submitCount && errors.owners) ? <InputError message={errors.owners.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.service) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="service">Service</label>
              <Field
                id="service"
                name="service"
                className="form-select"
                autoComplete="service"
                placeholder='Service'
                as="select"
                onChange={(e: { target: { value: string } }) => {
                  setFieldValue('service', e.target.value)
                  setFieldValue('city_permits', false)
                  setFieldValue('cost_city_fee', 0)
                  setFieldValue('association_permits', false)
                  setFieldValue('equipment_rental', false)
                  setFieldValue('type_of_work_id', 0)
                  setFieldValue('type_of_housing_id', 0)
                  setFieldValue('travel_cost_id', 0)
                  setFieldValue('duration_of_work_id', 0)
                  setFieldValue('installation_date', null)
                  setFieldValue('installation_end_date', null)
                }}
              >
                <option value="">Service</option>
                {services.map((service, index) => (
                  <option key={index} value={service}>{service}</option>
                ))}
              </Field>
              {(submitCount && errors.service) ? <InputError message={errors.service} className="mt-2" /> : ''}
            </div>
            {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
            <div className={submitCount ? (errors.frame_color) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="frame_color">Frame Color</label>
              <Field
                id="frame_color"
                name="frame_color"
                className="form-select"
                autoComplete="frame_color"
                placeholder='Frame Color'
                as="select"
              >
                <option value="">Select Frame color</option>
                {frame_colors.map((frame_color, index) => (
                  <option key={index} value={frame_color}>{frame_color}</option>
                ))}
              </Field>
              {(submitCount && errors.frame_color) ? <InputError message={errors.frame_color} className="mt-2" /> : ''}
            </div>
            )}

            {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
            <>
            <div className={submitCount ? (errors.type_of_work_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="type_of_work">Type of Work</label>
              <Field
                id="type_of_work_id"
                name="type_of_work_id"
                className="form-select"
                autoComplete="type_of_work_id"
                placeholder='Type of Work'
                as="select"
                onChange={(e: { target: { value: string } }) => {
                  const type_of_work_id = parseInt(e.target.value)
                  setFieldValue('type_of_work_id', type_of_work_id)
                }}
              >
                <option value="0">Type of Work</option>
                {type_of_works.map((type_of_works, index) => (
                  <option key={index} value={type_of_works.id}>{type_of_works.name}</option>
                ))}
              </Field>
              {(submitCount && errors.type_of_work_id) ? <InputError message={errors.type_of_work_id} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.type_of_housing_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="type_of_housing_id">Type of Housing</label>
              <Field
                id="type_of_housing_id"
                name="type_of_housing_id"
                className="form-select"
                autoComplete="type_of_housing_id"
                placeholder='Type of Work'
                as="select"
                onChange={(e: { target: { value: string } }) => {
                  const type_of_housing_id = parseInt(e.target.value)
                  setFieldValue('type_of_housing_id', type_of_housing_id)
                }}
              >
                <option value="">Type of Housing</option>
                {types_of_housing.map((type_of_housing, index) => (
                  <option key={index} value={type_of_housing.id}>{type_of_housing.name}</option>
                ))}
              </Field>
              {(submitCount && errors.type_of_housing_id) ? <InputError message={errors.type_of_housing_id} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.travel_cost_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="travel_cost_id">County</label>
              <Select
                id='travel_cost_id'
                placeholder="Travel Cost"
                name='travel_cost_id'
                defaultValue={ selectedTravelCost }
                onChange={(value) => { setFieldValue('travel_cost_id', value) }}
                options={travel_costs.map((travel_cost) => { return { label: travel_cost.name, value: travel_cost.id } })}
              />
              {(submitCount && errors.travel_cost_id) ? <InputError message={errors.travel_cost_id.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.duration_of_work_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="duration_of_work_id">Duration of Work</label>
              <Select
                id='duration_of_work_id'
                placeholder="Duration of Work"
                name='duration_of_work_id'
                defaultValue={ selectedDurationOfWork }
                onChange={(value) => {
                  setFieldValue('duration_of_work_id', value)
                  if (values.installation_date) {
                    const duration_of_work_value = getValueIdNotNull(value)
                    // console.log(duration_of_work_value)
                    const duration_of_work = duration_of_works.find((duration_of_work) => duration_of_work.id === duration_of_work_value)
                    const installation_end_date = new Date(values.installation_date)
                    if (duration_of_work) {
                      installation_end_date.setDate(installation_end_date.getDate() + duration_of_work?.number_of_day ?? 0)
                      const year = installation_end_date.getFullYear()
                      const month = String(installation_end_date.getMonth() + 1).padStart(2, '0') // Agregar cero inicial si es necesario
                      const day = String(installation_end_date.getDate()).padStart(2, '0')// Agregar cero inicial si es necesario
                      // Formato yyyy-mm-dd
                      const fechaFormateada = `${year}-${month}-${day}`
                      setFieldValue('installation_end_date', fechaFormateada)
                    }
                  }
                }}
                options={duration_of_works.map((duration_of_work) => { return { label: duration_of_work.name, value: duration_of_work.id } })}
              />
              {(submitCount && errors.duration_of_work_id) ? <InputError message={errors.duration_of_work_id.toString()} className="mt-2" /> : ''}
            </div>
            </>
            )}
            <div className={submitCount ? (errors.additional_travel_costs) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="additional_travel_costs">Other Cost</label>
              <Field
                id="additional_travel_costs"
                name="additional_travel_costs"
                className="form-input text-right"
                autoComplete="additional_travel_costs"
                placeholder='Other Cost'
                type='number'
              />
              {(submitCount && errors.additional_travel_costs) ? <InputError message={errors.additional_travel_costs} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.entry_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="entry_date">Entry Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="entry_date"
                value={values.entry_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('entry_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.entry_date) ? <InputError message={errors.entry_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.contract_signing_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="contract_signing_date">Contract Signing Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="contract_signing_date"
                value={values.contract_signing_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('contract_signing_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.contract_signing_date) ? <InputError message={errors.contract_signing_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.payment_factory_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="payment_factory_date">Payment Factory Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                disabled={(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (values.type_of_work_id === 0 || values.type_of_housing_id === 0 || values.travel_cost_id === 0 || ((values.duration_of_work_id) as any).value === 0)}
                name="payment_factory_date"
                value={values.payment_factory_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  const payment_factory_date = date.toISOString().slice(0, 10)
                  if (values.service === SERVICES.DELIVERY_AND_INSTALLATION) {
                    selectDeliveryAndInstallationDate(payment_factory_date, values.city_permits)
                  } else {
                    selectDeliveryAndPickupDate(payment_factory_date)
                  }
                  setFieldValue('payment_factory_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.payment_factory_date) ? <InputError message={errors.payment_factory_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.eta_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="eta_date">Eta Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="eta_date"
                value={values.eta_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('eta_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.eta_date) ? <InputError message={errors.eta_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.delivery_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="delivery_date">Delivery/Pickup Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="delivery_date"
                value={values.delivery_date?.toString()}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('delivery_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.delivery_date) ? <InputError message={errors.delivery_date?.toString()} className="mt-2" /> : ''}
            </div>
            {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
              <>
                <div className={submitCount ? (errors.installation_date) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="installation_date">Installation Date</label>
                  <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}
                    name="installation_date"
                    value={values.installation_date?.toString()}
                    className="form-input"
                    onChange={([date]) => {
                      setFieldValue('installation_date', date.toISOString().slice(0, 10))
                      const duration_of_work_value = getValueIdNotNull(values.duration_of_work_id)
                      if (duration_of_work_value !== '') {
                        const duration_of_work = duration_of_works.find((duration_of_work) => duration_of_work.id === duration_of_work_value)
                        if (duration_of_work) {
                          const installation_end_date = new Date(date)
                          installation_end_date.setDate(installation_end_date.getDate() + duration_of_work.number_of_day - 1)
                          const year = installation_end_date.getFullYear()
                          const month = String(installation_end_date.getMonth() + 1).padStart(2, '0') // Agregar cero inicial si es necesario
                          const day = String(installation_end_date.getDate()).padStart(2, '0')// Agregar cero inicial si es necesario
                          // Formato yyyy-mm-dd
                          const fechaFormateada = `${year}-${month}-${day}`
                          setFieldValue('installation_end_date', fechaFormateada)
                        }
                      }
                    }}
                  />
                  {(submitCount && errors.installation_date) ? <InputError message={errors.installation_date?.toString()} className="mt-2" /> : ''}
                </div>
                <div className={submitCount ? (errors.installation_end_date) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="installation_end_date">Installation End Date</label>
                  <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}
                    name="installation_end_date"
                    value={values.installation_end_date?.toString()}
                    className="form-input"
                    onChange={([date]) => {
                      setFieldValue('installation_end_date', date.toISOString().slice(0, 10))
                    }}
                  />
                  {(submitCount && errors.installation_end_date) ? <InputError message={errors.installation_end_date?.toString()} className="mt-2" /> : ''}
                </div>
              </>
            )}
             {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
            <>
            <div className={submitCount ? (errors.city_permits) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                <div className='flex'>
                <Field
                      id="city_permits"
                      name="city_permits"
                      className="form-checkbox"
                      type="checkbox"
                      onChange={async (e: React.ChangeEvent<HTMLInputElement>) => {
                        const isChecked = e.target.checked
                        console.log('Checkbox changed to:', isChecked)
                        setFieldValue('city_permits', isChecked)
                        setFieldValue('cost_city_fee', 0)

                        // Obtener el valor de `payment_factory_date`
                        const paymentFactoryDate =
                          typeof values.payment_factory_date === 'string'
                            ? values.payment_factory_date
                            : values.payment_factory_date?.toISOString().slice(0, 10) ?? ''

                        // Pasar el valor actualizado directamente a la función
                        await selectDeliveryAndInstallationDate(paymentFactoryDate, isChecked)
                      }}
                    />
                 { /* <Field
                    id="city_permits"
                    name="city_permits"
                    className="form-checkbox"
                    type='checkbox'
                    onChange={(e: any) => {
                      setFieldValue('city_permits', e.target.checked)
                      setFieldValue('cost_city_fee', 0)
                      const payment_factory_date = typeof values.payment_factory_date === 'string' ? values.payment_factory_date : values.payment_factory_date?.toISOString().slice(0, 10) ?? ''
                      selectDeliveryAndInstallationDate(payment_factory_date)
                    }}
                  /> */ }
                  <label htmlFor="city_permits">City Permits</label>
                </div>
                {(submitCount && errors.city_permits) ? <div className='block'><InputError message={errors.city_permits} className="mt-2" /></div> : ''}
            </div>
            {(values.city_permits) && (
              <>
                <div className={submitCount ? (errors.cost_city_fee) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="cost_city_fee">City Fee Cost</label>
                  <Field
                    id="cost_city_fee"
                    name="cost_city_fee"
                    className="form-input text-right"
                    autoComplete="cost_city_fee"
                    placeholder='City Fee Cost'
                    type='number'
                  />
                  {(submitCount && errors.cost_city_fee) ? <InputError message={errors.cost_city_fee} className="mt-2" /> : ''}
                </div>
              </>
            )}
            <div className={submitCount ? (errors.association_permits) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                <div className='flex'>
                  <Field
                    id="association_permits"
                    name="association_permits"
                    className="form-checkbox"
                    type='checkbox'
                    onChange={(e: any) => {
                      setFieldValue('association_permits', e.target.checked)
                    }}
                  />
                  <label htmlFor="association_permits">Association Permits</label>
                </div>
                {(submitCount && errors.association_permits) ? <div className='block'><InputError message={errors.association_permits} className="mt-2" /></div> : ''}
            </div>
            <div className={submitCount ? (errors.equipment_rental) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                <div className='flex'>
                  <Field
                    id="equipment_rental"
                    name="equipment_rental"
                    className="form-checkbox"
                    type='checkbox'
                    onChange={(e: any) => {
                      setFieldValue('equipment_rental', e.target.checked)
                    }}
                  />
                  <label htmlFor="equipment_rental">Equipment Rental</label>
                </div>
                {(submitCount && errors.equipment_rental) ? <div className='block'><InputError message={errors.equipment_rental} className="mt-2" /></div> : ''}
            </div>
            </>)}
            <div className={submitCount ? (errors.method_of_payment) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="method_of_payment">Project Payment Method</label>
              <Field
                id="method_of_payment"
                name="method_of_payment"
                className="form-select"
                autoComplete="method_of_payment"
                placeholder='Method of Payment'
                as="select"
                onChange={(e: { target: { value: string } }) => {
                  setFieldValue('method_of_payment', e.target.value)
                  setFieldValue('cost_delivery', 0)
                  setFieldValue('type_of_financing', '')
                }}
              >
                <option value="">Method of Payment</option>
                {methods_of_payment.map((method_of_payment, index) => (
                  <option key={index} value={method_of_payment}>{method_of_payment}</option>
                ))}
              </Field>
              {(submitCount && errors.method_of_payment) ? <InputError message={errors.method_of_payment} className="mt-2" /> : ''}
            </div>
            {(values.method_of_payment === PAYMENT_METHODS.FINANCED || values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE) && (
              <div className={submitCount ? (errors.type_of_financing) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="method_of_payment">Type Of Financing</label>
                <Field
                  id="type_of_financing"
                  name="type_of_financing"
                  className="form-select"
                  autoComplete="type_of_financing"
                  placeholder='Type Of Financing'
                  as="select"
                  onChange={(e: { target: { value: string } }) => {
                    setFieldValue('type_of_financing', e.target.value)
                  }}
                >
                  <option value="">Type Of Financing</option>
                  {type_of_financing.map((financing, index) => (
                    <option key={index} value={financing}>{financing}</option>
                  ))}
                </Field>
                {(submitCount && errors.type_of_financing) ? <InputError message={errors.type_of_financing} className="mt-2" /> : ''}
              </div>
            )}

            {(values.method_of_payment === PAYMENT_METHODS.CASH || values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE) && (
              <div className={submitCount ? (errors.cost_delivery) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="cost_delivery">Delivery Cost</label>
                <Field
                  id="cost_delivery"
                  name="cost_delivery"
                  className="form-input text-right"
                  autoComplete="cost_delivery"
                  placeholder='Delivery Cost'
                  type='number'
                />
                {(submitCount && errors.cost_delivery) ? <InputError message={errors.cost_delivery} className="mt-2" /> : ''}
              </div>
            )}
            {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
              <>
                <div className={submitCount ? (errors.installation_teams) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="installationTeams">Installation Team</label>
                  <Select
                    id='installation_teams'
                    placeholder="Installation Team"
                    name='installation_teams'
                    defaultValue={ values.installation_teams.map((installation_team) => { return { label: installation_team.user?.name, value: installation_team.id } }) }
                    isMulti={true}
                    onChange={(value) => { setFieldValue('installation_teams', value) }}
                    options={installation_teams.filter((team_member) =>
                      team_member.type_housing?.find((type_of_housing) => type_of_housing.id === values.type_of_housing_id)
                    ).map((installation_team) => { return { label: installation_team.user?.name, value: installation_team.id } })}
                  />
                  {(submitCount && errors.installation_teams) ? <InputError message={errors.installation_teams.toString()} className="mt-2" /> : ''}
                </div>
                <div className={submitCount ? (errors.supervisor_id) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="supervisor_id">Supervisor</label>
                  <Select
                    id='supervisor_id'
                    placeholder="Supervisor"
                    name='supervisor_id'
                    defaultValue={ selectedSupervisor }
                    onChange={(value) => { setFieldValue('supervisor_id', value) }}
                    options={supervisors.map((supervisor) => { return { label: supervisor.name, value: supervisor.id } })}
                  />
                  {(submitCount && errors.supervisor_id) ? <InputError message={errors.supervisor_id} className="mt-2" /> : ''}
                </div>
              </>
            )}
            <div className={submitCount ? (errors.installation_teams) ? 'has-error' : 'has-success' : ''}>
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
            <div className={submitCount ? (errors.project_amount) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="project_amount">Project Amount</label>
              <Field
                id="project_amount"
                name="project_amount"
                className="form-input text-right"
                autoComplete="project_amount"
                placeholder='Project Amount'
                type='number'
              />
              {(submitCount && errors.project_amount) ? <InputError message={errors.project_amount} className="mt-2" /> : ''}
            </div>
           {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
              <>
               <div className={submitCount ? (errors.hide_on_weekends) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                    <div className='flex'>
                      <Field
                        id="hide_on_weekends"
                        name="hide_on_weekends"
                        className="form-checkbox"
                        type='checkbox'
                        onChange={(e: any) => {
                          setFieldValue('hide_on_weekends', e.target.checked)
                          // setFieldValue('initial_payment_percentage', 0)
                        }}
                      />
                      <label htmlFor="hide_on_weekends">Hide On Weekends</label>
                    </div>
                    {(submitCount && errors.hide_on_weekends) ? <div className='block'><InputError message={errors.hide_on_weekends} className="mt-2" /></div> : ''}
                </div>
               {/* <div className={submitCount ? (errors.initial_payment_percentage) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="initial_payment_percentage">Initial Payment Percentage</label>
                  <Field
                    id="initial_payment_percentage"
                    name="initial_payment_percentage"
                    className="form-input text-right"
                    autoComplete="initial_payment_percentage"
                    placeholder='Initial Payment Percentage'
                    type='number'
                  />
                  {(submitCount && errors.initial_payment_percentage) ? <InputError message={errors.initial_payment_percentage} className="mt-2" /> : ''}
                </div> */}
              </>
            )}
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
            <div className='col-span-4'>
              <label htmlFor="work_team_notes">Work Team Notes</label>
              <Field
                id="work_team_notes"
                name="work_team_notes"
                component="textarea"
                rows="4"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Work Team Notes'
              />
            </div>
            <div className='col-span-4'>
              <label htmlFor="attachments">Attachments</label>
              <input
                id="attachments"
                name="attachments"
                type="file"
                accept="*"
                className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
                placeholder="Qty"
                multiple={true}
                onChange={(event: any) => {
                  setFieldValue('attachments', event.currentTarget.files)
                }}
              />
              {attachments !== undefined && attachments.length > 0 && (
                <div className="flex flex-col rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b] mt-3">
                  {attachmentsArray.map((attachment, index) => {
                    return (
                      <div key={index} className="border-b border-[#e0e6ed] dark:border-[#1b2e4b] px-4 py-2.5 hover:bg-[#eee] dark:hover:bg-[#eee]/10 flex justify-between">
                        <span>{attachment.filename}</span>
                        <button
                          onClick={(e) => {
                            e.preventDefault()
                            removeAttachmentProduct(index)
                          }}
                          title='Delete Attachment'
                        >
                          <DeleteIcon />
                      </button>
                      </div>
                    )
                  })}
                </div>
              )}
            </div>
          </div>
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Product Information</legend>
          {((values.service === SERVICES.DELIVERY_AND_INSTALLATION && values.type_of_work_id !== 0) || (values.service === SERVICES.DELIVERY_ONLY || values.service === SERVICES.PICKUP)) && (
            <div className='flex items-center justify-end'>
              <button onClick={(e) => {
                e.preventDefault()
                setShowProductModal(true)
              }} className="btn btn-primary">Add Product</button>
            </div>
          )}
          <ProductTable
            orderProducts={orderProducts}
            type_of_products={type_of_products}
            product_category={product_category}
            products_config={products_config}
            service={values.service}
            values= {values}
            travel_costs={travel_costs}
            removeOrderProduct={(index: number) => { removeOrderProduct(index) }}
            updateOrderProduct={(index: number) => { updateOrderProduct(index) }}
          />
        </fieldset>
        <div className="flex items-center justify-between mt-4">
          <Link className='btn btn-danger uppercase' href={route('order.index')}>Cancel</Link>
          <PrimaryButton className="btn btn-primary" type='submit'>
            {isCreate ? 'Create' : 'Save'}
          </PrimaryButton>
        </div>
      </Form>
      <ProductModal
        showModal={showProductModal}
        typeOfProducts={type_of_products}
        productCategories={product_category}
        productConfigs={products_config}
        typeOfWork={values.type_of_work_id}
        productCosts={product_costs}
        service={values.service}
        onClose={() => {
          setShowProductModal(false)
        }}
        isCreated={isCreated}
        addOrderProduct={(product: OrderProduct) => { addOrderProduct(product) }}
      />
    </>
  )
}

export default OrderForm
