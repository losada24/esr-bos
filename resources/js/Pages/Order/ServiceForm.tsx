import { useMemo, useRef, useState, useEffect } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import Select from 'react-select'
import {
  type OrderProduct,
  type ProductCategory,
  type ProductConfig,
  type TravelCost,
  type TypeOfWork,
  type TypeOfProduct,
  type ProductCost,
  type DurationOfWork,
  type InstallationTeam
} from '@/types'
import { type FormikErrors } from 'formik'
import { getOrderProducts, type OrderFormValues } from './OrderCommon'
import { capitalizeWords } from '@/Utils/string'
import ProductTable from './ProductTable'
import ProductModal from './ProductModal'
import { SERVICES, PAYMENT_METHODS } from '@/Utils/constants'
import SearchIcon from '@/Components/Icons/SearchIcon'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY

type Option = { label: string, value: number }

const ServiceForm = ({
  submitCount,
  errors,
  isCreate,
  setFieldValue,
  values,
  supervisors,
  methods_of_payment,
  services,
  travel_costs,
  status,
  type_of_products,
  product_category,
  products_config,
  extraWorks,
  product_costs,
  type_of_works,
  duration_of_works,
  installation_teams,
  type_of_financing: typeOfFinancingOptions
}: {
  submitCount: number
  errors: FormikErrors<OrderFormValues>
  isCreate: boolean
  setFieldValue: (field: string, value: any) => void
  values: OrderFormValues
  supervisors: Array<{ id: number, name: string }>
  methods_of_payment: string[]
  services: string[]
  travel_costs: TravelCost[]
  status: string[]
  type_of_products: TypeOfProduct[]
  product_category: ProductCategory[]
  products_config: ProductConfig[]
  extraWorks: Array<{ id: number, name: string }>
  product_costs: ProductCost[]
  type_of_works: TypeOfWork[]
  duration_of_works: DurationOfWork[]
  installation_teams: InstallationTeam[]
  type_of_financing: string[]
}) => {
  const inputRef = useRef<google.maps.places.SearchBox | null>(null)
  const libraries: any[] = ['places']
  const memoLibraries = useMemo(() => libraries, [])
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: memoLibraries
  })

  const handleOnPlaceChanged = () => {
    const searchBox = inputRef.current
    if (searchBox) {
      const places = searchBox.getPlaces()
      if (places && places.length > 0) {
        const place = places[0]
        if (place.address_components) {
          const addressComponents = place.address_components
          const getComponent = (type: string) =>
            addressComponents.find((component) => component.types.includes(type))?.long_name ?? ''

          const streetNumber = getComponent('street_number')
          const route = getComponent('route')
          const subpremise = getComponent('subpremise')

          const addressParts = [streetNumber, route].filter(Boolean).join(' ')
          const address = subpremise ? `${addressParts}, ${subpremise}` : addressParts

          const city = getComponent('locality')
          const state = getComponent('administrative_area_level_1')
          const zip = getComponent('postal_code')

          setFieldValue('job_address', address)
          setFieldValue('city', city)
          setFieldValue('job_state', state)
          setFieldValue('job_zip', zip)
        }
      }
    }
  }

  const [orderProducts, setOrderProducts] = useState<OrderProduct[]>(
    values.order_products?.map((orderProduct) => getOrderProducts(orderProduct)) ?? []
  )
  const [showProductModal, setShowProductModal] = useState<boolean>(false)
  const [isCreated] = useState<boolean>(true)

  const addOrderProduct = (orderProduct: OrderProduct) => {
    const orderProductsList = [...orderProducts, orderProduct]
    setOrderProducts(orderProductsList)
    setFieldValue('orderProducts', orderProductsList)
  }

  const removeOrderProduct = (index: number) => {
    const orderProductList = orderProducts.filter((_, i) => i !== index)
    setOrderProducts(orderProductList)
    setFieldValue('orderProducts', orderProductList)
  }

  const updateOrderProduct = (index: number) => {
    console.log('updateOrderProduct', index)
  }

  const installationTeamOptions: Option[] = installation_teams.map((team) => ({
    label: team.user?.name ?? team.company_name ?? `Team ${team.id}`,
    value: team.id
  }))
  const supervisorOptions: Option[] = supervisors.map((supervisor) => ({
    label: supervisor.name,
    value: supervisor.id
  }))
  useEffect(() => {
    const initialProducts = values.order_products?.map((orderProduct) => getOrderProducts(orderProduct)) ?? []
    setOrderProducts(initialProducts)
  }, [values.order_products])
  useEffect(() => {
    setFieldValue('orderProducts', orderProducts)
  }, [orderProducts, setFieldValue])

  const selectedSupervisor = (() => {
    const supervisorValue: any = values.supervisor_id
    if (supervisorValue && typeof supervisorValue === 'object') {
      if ('value' in supervisorValue) {
        return supervisorOptions.find(option => option.value === supervisorValue.value) ?? supervisorValue
      }
      if ('id' in supervisorValue) {
        return supervisorOptions.find(option => option.value === supervisorValue.id)
      }
    }
    if (typeof supervisorValue === 'number') {
      return supervisorOptions.find(option => option.value === supervisorValue) ?? null
    }
    return null
  })()

  return (
    <>
      <Form className='space-y-5'>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Client Information</legend>
          <div className='grid gap-4 grid-cols-3'>
            <div className={submitCount ? (errors.client_name ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="client_name">Name</label>
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
                <button
                  onClick={(e) => {
                    e.preventDefault()
                    alert('search from begin')
                  }}
                  className="bg-[#eee] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md px-3 font-semibold border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                >
                  <SearchIcon className="text-[#eee]" />
                </button>
              </div>
              {(submitCount && errors.client_name) ? <InputError message={errors.client_name} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.phone ? 'has-error' : 'has-success') : ''}>
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
            <div className={submitCount ? (errors.email ? 'has-error' : 'has-success') : ''}>
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
            <div className='flex items-center gap-2'>
              <Field
                id="vip_clients"
                name="vip_clients"
                className="form-checkbox"
                type='checkbox'
                checked={values.vip_clients}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('vip_clients', e.target.checked)
                  if (!e.target.checked) {
                    setFieldValue('vip_notes', '')
                  }
                }}
              />
              <label htmlFor="vip_clients" className='font-semibold'>VIP</label>
            </div>
            {values.vip_clients && (
              <div className='col-span-3'>
                <label htmlFor="vip_notes">VIP Notes</label>
                <Field
                  id="vip_notes"
                  name="vip_notes"
                  component="textarea"
                  rows="3"
                  className="form-textarea resize-none placeholder:text-white-dark"
                  placeholder='VIP Notes'
                />
                {(submitCount && errors.vip_notes) ? <InputError message={errors.vip_notes} className="mt-2" /> : ''}
              </div>
            )}
          </div>
        </fieldset>

        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Service Details</legend>
          <div className='grid gap-4 grid-cols-4'>
            <div className={submitCount ? (errors.name ? 'has-error' : 'has-success') : ''}>
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
            <div className={submitCount ? (errors.order_number ? 'has-error' : 'has-success') : ''}>
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
            <div className={submitCount ? (errors.job_address ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="job_address">Job Address</label>
              {isLoaded && (
                <StandaloneSearchBox
                  onLoad={(ref) => {
                    inputRef.current = ref
                  }}
                  onPlacesChanged={handleOnPlaceChanged}
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
                className="form-input"
                autoComplete="city"
                placeholder='City'
              />
              {(submitCount && errors.city) ? <InputError message={errors.city} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.job_state ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="job_state">State</label>
              <Field
                id="job_state"
                name="job_state"
                className="form-input"
                autoComplete="state"
                placeholder='State'
              />
              {(submitCount && errors.job_state) ? <InputError message={errors.job_state} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.job_zip ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="job_zip">ZIP Code</label>
              <Field
                id="job_zip"
                name="job_zip"
                className="form-input"
                autoComplete="postal-code"
                placeholder='ZIP Code'
              />
              {(submitCount && errors.job_zip) ? <InputError message={errors.job_zip} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.service ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="service">Service</label>
              <Field
                id="service"
                name="service"
                className="form-select"
                as="select"
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
                  setFieldValue('service', e.target.value)
                }}
              >
                <option value="" disabled hidden>Select Service</option>
                {services.map((service, index) => (
                  <option key={index} value={service}>{service}</option>
                ))}
              </Field>
              {(submitCount && errors.service) ? <InputError message={errors.service} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.additional_travel_costs ? 'has-error' : 'has-success') : ''}>
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
            <div className={submitCount ? (errors.travel_cost_id ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="travel_cost_id">Travel</label>
              <Field
                id="travel_cost_id"
                name="travel_cost_id"
                className="form-select"
                as="select"
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
                  const value = parseInt(e.target.value)
                  setFieldValue('travel_cost_id', Number.isNaN(value) ? 0 : value)
                }}
              >
                <option value="0">Select Travel</option>
                {travel_costs.map((travel) => (
                  <option key={travel.id} value={travel.id}>{travel.name}</option>
                ))}
              </Field>
              {(submitCount && errors.travel_cost_id) ? <InputError message={errors.travel_cost_id.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.is_new_travel_cost ? 'has-error inline-flex flex-col' : 'has-success inline-flex') : 'inline-flex items-end'}>
              <div className='flex'>
                <Field
                  id="is_new_travel_cost"
                  name="is_new_travel_cost"
                  className="form-checkbox"
                  type='checkbox'
                  checked={values.is_new_travel_cost}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('is_new_travel_cost', e.target.checked)
                    if (!e.target.checked) {
                      setFieldValue('new_travel_cost', 0)
                    }
                  }}
                />
                <label htmlFor="is_new_travel_cost" className="ml-2">New Travel Cost</label>
              </div>
              {(submitCount && errors.is_new_travel_cost) ? <div className='block'><InputError message={errors.is_new_travel_cost.toString()} className="mt-2" /></div> : ''}
            </div>
            {values.is_new_travel_cost && (
              <div className={submitCount ? (errors.new_travel_cost ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="new_travel_cost">New Travel Cost Amount</label>
                <Field
                  id="new_travel_cost"
                  name="new_travel_cost"
                  className="form-input text-right"
                  autoComplete="new_travel_cost"
                  placeholder='New Travel Cost'
                  type='number'
                />
                {(submitCount && errors.new_travel_cost) ? <InputError message={errors.new_travel_cost.toString()} className="mt-2" /> : ''}
              </div>
            )}
            <div className={submitCount ? (errors.installation_teams ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="installation_teams">Installation Team</label>
              <Select
                id='installation_teams'
                placeholder="Select Installation Teams"
                name='installation_teams'
                isMulti={true}
                value={(values.installation_teams ?? []).map((team: any) => ({
                  label: team.label ?? team.user?.name ?? installationTeamOptions.find((opt) => opt.value === (team.value ?? team.id))?.label ?? '',
                  value: team.value ?? team.id
                }))}
                onChange={(value) => {
                  setFieldValue('installation_teams', value)
                }}
                options={installationTeamOptions}
              />
              {(submitCount && errors.installation_teams) ? <InputError message={errors.installation_teams.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.supervisor_id ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="supervisor_id">Supervisor</label>
              <Select
                id='supervisor_id'
                placeholder="Select Supervisor"
                name='supervisor_id'
                isMulti={false}
                value={selectedSupervisor}
                onChange={(value) => {
                  setFieldValue('supervisor_id', value ? value.value : 0)
                }}
                options={supervisorOptions}
              />
              {(submitCount && errors.supervisor_id) ? <InputError message={errors.supervisor_id} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.duration_of_work_id ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="duration_of_work_id">Duration of Work</label>
              <Field
                id="duration_of_work_id"
                name="duration_of_work_id"
                className="form-select"
                as="select"
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
                  const value = parseInt(e.target.value)
                  setFieldValue('duration_of_work_id', Number.isNaN(value) ? 0 : value)
                }}
              >
                <option value="0">Select Duration</option>
                {duration_of_works.map((duration) => (
                  <option key={duration.id} value={duration.id}>{duration.name}</option>
                ))}
              </Field>
              {(submitCount && errors.duration_of_work_id) ? <InputError message={errors.duration_of_work_id.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.method_of_payment ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="method_of_payment">Project Payment Method</label>
              <Field
                id="method_of_payment"
                name="method_of_payment"
                className="form-select"
                as="select"
                value={values.method_of_payment ?? ''}
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
                  const value = e.target.value
                  setFieldValue('method_of_payment', value)
                  if (value !== PAYMENT_METHODS.FINANCED && value !== PAYMENT_METHODS.CASH_AND_FINANCE) {
                    setFieldValue('type_of_financing', '')
                  }
                }}
              >
                <option value="" disabled hidden>Select Method</option>
                {methods_of_payment.map((method, index) => (
                  <option key={index} value={method}>{method}</option>
                ))}
              </Field>
              {(submitCount && errors.method_of_payment) ? <InputError message={errors.method_of_payment} className="mt-2" /> : ''}
            </div>
            {(values.method_of_payment === PAYMENT_METHODS.FINANCED || values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE) && (
              <div className={submitCount ? (errors.type_of_financing ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="type_of_financing">Type of Financing</label>
                <Field
                  id="type_of_financing"
                  name="type_of_financing"
                  className="form-select"
                  as="select"
                  onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
                    setFieldValue('type_of_financing', e.target.value)
                  }}
                  value={values.type_of_financing ?? ''}
                >
                  <option value="">Select Type</option>
                  {typeOfFinancingOptions.map((financing, index) => (
                    <option key={index} value={financing}>{financing}</option>
                  ))}
                </Field>
                {(submitCount && errors.type_of_financing) ? <InputError message={errors.type_of_financing} className="mt-2" /> : ''}
              </div>
            )}
            <div className={submitCount ? (errors.status ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="status">Status</label>
              <Field
                id="status"
                name="status"
                className="form-select"
                as="select"
                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
                  setFieldValue('status', e.target.value)
                }}
              >
                <option value="" disabled hidden>Select Status</option>
                {status.map((currentStatus, index) => (
                  <option key={index} value={currentStatus}>{currentStatus}</option>
                ))}
              </Field>
              {(submitCount && errors.status) ? <InputError message={errors.status} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.project_amount ? 'has-error' : 'has-success') : ''}>
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
          </div>
        </fieldset>

        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Dates</legend>
          <div className='grid gap-4 grid-cols-3'>
            <div className={submitCount ? (errors.entry_date ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="entry_date">Entry Date</label>
              <Flatpickr
                id="entry_date"
                value={values.entry_date ?? ''}
                options={{
                  dateFormat: 'Y-m-d'
                }}
                className="form-input"
                onChange={(date: any) => {
                  const formattedDate = date.length > 0 ? new Date(date[0]).toISOString().slice(0, 10) : ''
                  setFieldValue('entry_date', formattedDate)
                }}
              />
              {(submitCount && errors.entry_date) ? <InputError message={errors.entry_date} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.delivery_date ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="delivery_date">Delivery / Pickup Date</label>
              <Flatpickr
                id="delivery_date"
                value={values.delivery_date ?? ''}
                options={{
                  dateFormat: 'Y-m-d'
                }}
                className="form-input"
                onChange={(date: any) => {
                  const formattedDate = date.length > 0 ? new Date(date[0]).toISOString().slice(0, 10) : ''
                  setFieldValue('delivery_date', formattedDate)
                }}
              />
              {(submitCount && errors.delivery_date) ? <InputError message={errors.delivery_date} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.installation_date ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="installation_date">Installation Date</label>
              <Flatpickr
                id="installation_date"
                value={values.installation_date ?? ''}
                options={{
                  dateFormat: 'Y-m-d'
                }}
                className="form-input"
                onChange={(date: any) => {
                  const formattedDate = date.length > 0 ? new Date(date[0]).toISOString().slice(0, 10) : ''
                  setFieldValue('installation_date', formattedDate)
                }}
              />
              {(submitCount && errors.installation_date) ? <InputError message={errors.installation_date} className="mt-2" /> : ''}
            </div>
          </div>
        </fieldset>

        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Notes</legend>
          <div className='grid gap-4 grid-cols-1'>
            <div className={submitCount ? (errors.notes ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="notes">Notes</label>
              <Field
                id="notes"
                name="notes"
                component="textarea"
                rows="4"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
              />
              {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
            </div>
          </div>
        </fieldset>

        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Attachments</legend>
          <div className='grid gap-4 grid-cols-1'>
            <div className={submitCount ? (errors.attachments ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="attachments">Attachments</label>
              <input
                id="attachments"
                name="attachments"
                type="file"
                accept="*"
                className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
                multiple={true}
                onChange={(event: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('attachments', event.currentTarget.files)
                }}
              />
              {(submitCount && errors.attachments) ? <InputError message={errors.attachments.toString()} className="mt-2" /> : ''}
            </div>
          </div>
        </fieldset>

        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Product Information</legend>
          <div className='flex items-center justify-end mb-3'>
            <button
              onClick={(e) => {
                e.preventDefault()
                setShowProductModal(true)
              }}
              className="btn btn-primary"
            >
              Add Product
            </button>
          </div>
          <ProductTable
            orderProducts={orderProducts}
            type_of_products={type_of_products}
            product_category={product_category}
            products_config={products_config}
            service={values.service ?? SERVICES.SERVICE}
            values={values}
            travel_costs={travel_costs}
            type_of_works={type_of_works}
            removeOrderProduct={(index: number) => { removeOrderProduct(index) }}
            updateOrderProduct={(index: number) => { updateOrderProduct(index) }}
            extraWorks={extraWorks}
            product_costs={product_costs}
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
        listTypeOfWork={type_of_works}
        productCosts={product_costs}
        onClose={(value: boolean) => { setShowProductModal(value) }}
        isCreated={isCreated}
        addOrderProduct={addOrderProduct}
        service={values.service ?? SERVICES.SERVICE}
      />
    </>
  )
}

export default ServiceForm
