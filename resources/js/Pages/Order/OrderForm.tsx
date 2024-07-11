import { useState, useEffect } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import {
  type Order,
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
  ExtraWorks,
  ProductCost
} from '@/types'
import Select from 'react-select'
import { type OrderFormValues } from './OrderCommon'
import SearchIcon from '@/Components/Icons/SearchIcon'
import ProductModal from './ProductModal'
import ProductTable from './ProductTable'

/*
    owners={owners}
    types_of_housing={types_of_housing}
    installation_teams={installation_teams}
    methods_of_payment={methods_of_payment}
    services={services}
    travel_costs={travel_costs}
    supervisors={supervisors}
    duration_of_works={duration_of_works}
    products_config={products_config} */

const OrderForm = ({
  submitCount,
  errors,
  isCreate,
  clients,
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
  extra_works,
  product_costs
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
  extra_works: ExtraWorks[]
  product_costs: ProductCost[]
}) => {
  const [isCreated, setIsCreated] = useState<boolean>(true)
  const [showProductModal, setShowProductModal] = useState<boolean>(false)
  return (
    <>
      <Form className='space-y-5'>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Client Information</legend>
          <div className='grid gap-4 grid-cols-4'>
            <div className={submitCount ? (errors.last_name) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="last_name">Last Name</label>
              <div className='flex'>
                <Field
                  id="last_name"
                  name="last_name"
                  className="form-input ltr:rounded-r-none rtl:rounded-l-none"
                  autoComplete="last_name"
                  placeholder='Last Name'
                />
                <button onClick={(e) => {
                  e.preventDefault()
                  alert('search from bigin')
                }} className="bg-[#eee] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md px-3 font-semibold border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]">
                  <SearchIcon className="text-[#eee]" />
                </button>
              </div>
              {(submitCount && errors.last_name) ? <InputError message={errors.last_name} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.client_name) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="client_name">First Name</label>
              <Field
                id="client_name"
                name="client_name"
                className="form-input"
                autoComplete="client_name"
                placeholder='First Name'
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
            <div className={submitCount ? (errors.job_address) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="job_address">Job Address</label>
              <Field
                id="job_address"
                name="job_address"
                className="form-input"
                autoComplete="job_address"
                placeholder='Job Address'
              />
              {(submitCount && errors.job_address) ? <InputError message={errors.job_address} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.owners) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="client">Owner</label>
              <Select
                id='owners'
                placeholder="Select Owners"
                name='owners'
                defaultValue={ null /* selectedClient ?? null */ }
                onChange={(value) => { /* setFieldValue('client_id', value?.value) */ }}
                isMulti={true}
                options={owners.map((owner) => { return { label: owner.name, value: owner.id } })}
              />
              {(submitCount && errors.owners) ? <InputError message={errors.owners.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.type_of_work_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="type_of_work">Type of Work</label>
              <Field
                id="type_of_works"
                name="type_of_works"
                className="form-select"
                autoComplete="type_of_works"
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
              >
                <option value="">Type of Housing</option>
                {types_of_housing.map((type_of_housing, index) => (
                  <option key={index} value={type_of_housing.id}>{type_of_housing.name}</option>
                ))}
              </Field>
              {(submitCount && errors.type_of_housing_id) ? <InputError message={errors.type_of_housing_id} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.installation_teams) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="installation_teams">Installation Team</label>
              <Select
                id='installation_teams'
                placeholder="Installation Team"
                name='installation_teams'
                defaultValue={ null /* selectedClient ?? null */ }
                onChange={(value) => { /* setFieldValue('client_id', value?.value) */ }}
                options={installation_teams.map((installation_team) => { return { label: installation_team.user?.name, value: installation_team.id } })}
              />
              {(submitCount && errors.installation_teams) ? <InputError message={errors.installation_teams.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.supervisor_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="supervisor_id">Supervisor</label>
              <Select
                id='supervisor_id'
                placeholder="Supervisor"
                name='supervisor_id'
                defaultValue={ null /* selectedClient ?? null */ }
                onChange={(value) => { /* setFieldValue('client_id', value?.value) */ }}
                options={supervisors.map((supervisor) => { return { label: supervisor.name, value: supervisor.id } })}
              />
              {(submitCount && errors.supervisor_id) ? <InputError message={errors.supervisor_id} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.travel_cost_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="travel_cost_id">County</label>
              <Select
                id='travel_cost_id'
                placeholder="Travel Cost"
                name='travel_cost_id'
                defaultValue={ null /* selectedClient ?? null */ }
                onChange={(value) => { /* setFieldValue('client_id', value?.value) */ }}
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
                defaultValue={ null /* selectedClient ?? null */ }
                onChange={(value) => { /* setFieldValue('client_id', value?.value) */ }}
                options={duration_of_works.map((duration_of_work) => { return { label: duration_of_work.name, value: duration_of_work.id } })}
              />
              {(submitCount && errors.duration_of_work_id) ? <InputError message={errors.duration_of_work_id.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.additional_travel_costs) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="additional_travel_costs">Aditional Travel Cost</label>
              <Field
                id="additional_travel_costs"
                name="additional_travel_costs"
                className="form-input text-right"
                autoComplete="additional_travel_costs"
                placeholder='Aditional Travel Cost'
                type='number'
              />
              {(submitCount && errors.additional_travel_costs) ? <InputError message={errors.additional_travel_costs} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.method_of_payment) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="method_of_payment">Payment Method</label>
              <Field
                id="method_of_payment"
                name="method_of_payment"
                className="form-select"
                autoComplete="method_of_payment"
                placeholder='Method of Payment'
                as="select"
              >
                <option value="">Method of Payment</option>
                {methods_of_payment.map((method_of_payment, index) => (
                  <option key={index} value={method_of_payment}>{method_of_payment}</option>
                ))}
              </Field>
              {(submitCount && errors.type_of_housing_id) ? <InputError message={errors.type_of_housing_id} className="mt-2" /> : ''}
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
              >
                <option value="">Service</option>
                {services.map((service, index) => (
                  <option key={index} value={service}>{service}</option>
                ))}
              </Field>
              {(submitCount && errors.type_of_housing_id) ? <InputError message={errors.type_of_housing_id} className="mt-2" /> : ''}
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
                value={values.contract_signing_date}
                className="form-input"
                onChange={(date: Date) => {
                  // setData('dates', date)
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
                name="payment_factory_date"
                value={values.payment_factory_date}
                className="form-input"
                onChange={(date: Date) => {
                  // setData('dates', date)
                }}
              />
              {(submitCount && errors.payment_factory_date) ? <InputError message={errors.payment_factory_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.delivery_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="delivery_date">Delivery Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="delivery_date"
                value={values.delivery_date}
                className="form-input"
                onChange={(date: Date) => {
                  // setData('dates', date)
                }}
              />
              {(submitCount && errors.delivery_date) ? <InputError message={errors.delivery_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.installation_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="installation_date">Installation Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="installation_date"
                value={values.installation_date}
                className="form-input"
                onChange={(date: Date) => {
                  // setData('dates', date)
                }}
              />
              {(submitCount && errors.installation_date) ? <InputError message={errors.installation_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.city_permits) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                <div className='flex'>
                  <Field
                    id="city_permits"
                    name="city_permits"
                    className="form-checkbox"
                    type='checkbox'
                  />
                  <label htmlFor="city_permits">City Permits</label>
                </div>
                {(submitCount && errors.city_permits) ? <div className='block'><InputError message={errors.city_permits} className="mt-2" /></div> : ''}
            </div>
            <div className={submitCount ? (errors.association_permits) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                <div className='flex'>
                  <Field
                    id="association_permits"
                    name="association_permits"
                    className="form-checkbox"
                    type='checkbox'
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
                  />
                  <label htmlFor="equipment_rental">Equipment Rental</label>
                </div>
                {(submitCount && errors.equipment_rental) ? <div className='block'><InputError message={errors.equipment_rental} className="mt-2" /></div> : ''}
            </div>
          </div>
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Product Information</legend>
          {values.type_of_work_id !== 0 && (
            <div className='flex items-center justify-end'>
              <button onClick={(e) => {
                e.preventDefault()
                setShowProductModal(true)
              }} className="btn btn-primary">Add Product</button>
            </div>
          )}
          <ProductTable orderProducts={[]} />
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
        extraWorks={extra_works}
        typeOfWork={values.type_of_work_id}
        productCosts={product_costs}
        onClose={() => {
          setShowProductModal(false)
        }}
        isCreated={isCreated}
      />
    </>
  )
}

export default OrderForm
