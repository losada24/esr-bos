import React, { useEffect, useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Pipelines, type CompanyContact } from '@/types'
import { Field, Form, Formik, type FormikErrors, type FormikHelpers } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type Tasks } from '@/types/interfaces/pipelines'
import { loadOrderFormObj, Order, orderQuantifiedSchema , type OrderFormValues } from './OrderCommon'
import { router } from '@inertiajs/react'

const QuantifiedModal = ({
  task,
  onClose,
  previousStatusId,
  setProjectList,
  updateOrderStatus,
  lostStatusId,
  showModal,
  lossReasonFrontdesk,
  sources,
  order_types
  // errors
}: {
  task: Tasks | null
  onClose: () => void
  setProjectList: React.Dispatch<React.SetStateAction<Pipelines[]>>
  updateOrderStatus: (orderId: number, newStatus: string) => Promise<void>
  lostStatusId: string
  showModal: boolean
  lossReasonFrontdesk: string[]
  sources: string[]
  previousStatusId: string | null
  order_types: string[]
  // errors: FormikErrors<OrderFormValues>

}) => {
   // const initialValues: OrderFormValues = loadOrderFormObj(order)
  const [orderFormData, setOrderFormData] = useState<OrderFormValues | null>(null)
  useEffect(() => {
    const fetchOrder = async () => {
      if (showModal && task?.id) {
        setOrderFormData(null)
        try {
          const response = await fetch(route('frontdesk.show-quantified-modal', { order: task.id }))
          if (!response.ok) throw new Error('Error al obtener los datos de la orden')
          const data: Order = await response.json()
          const loadedForm = loadOrderFormObj(data)
          setOrderFormData(loadedForm)
        } catch (error) {
          console.error('Error cargando la orden:', error)
        }
      }
    }

    fetchOrder()
  }, [showModal, task])
   console.log('Loss Reason Frontdesk:', task)
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  const handleSubmit = async (
    values: OrderFormValues,
    helpers: FormikHelpers<OrderFormValues>
  ) => {
    try {
      const response = await fetch(route('frontdesk.update-status-quantified', { order: task?.id ?? 0 }), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json'
        },
        body: JSON.stringify({
          ...values,
          from_modal: true,
          id: task?.id,
          status: lostStatusId,
          notes: values.notes
        })
      })

      if (!response.ok) {
        const errorData = await response.json()
        helpers.setErrors(errorData.errors || {})
        helpers.setSubmitting(false)
        return
      }
      await response.json()
      setTimeout(() => {
        router.visit(route('frontdesk.index'))
      }, 100)
    } catch (error) {
      console.error(error)
      helpers.setSubmitting(false)
    }
  }
  if (!orderFormData) return null
  console.log('Order Form Data:', orderFormData)

  const handleClose = () => {
    if (task && previousStatusId) {
      setProjectList(prev =>
        prev.map(pipeline => {
          if (pipeline.id.toString() === previousStatusId) {
            const taskExists = pipeline.tasks.some(t => t.id === task.id)
            if (!taskExists) {
              return { ...pipeline, tasks: [...pipeline.tasks, task] }
            }
          }
          return pipeline
        })
      )
    }
    onClose()
  }
  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={handleClose}
    >
      <div className="w-full max-w-4xl mx-auto bg-white dark:bg-[#121c2c] rounded-xl overflow-hidden">
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Create Order</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { handleClose() }}>
            <CloseIcon />
          </button>
        </div>
      <div className='p-5'>
      <div className="max-h-[1000px] overflow-y-auto">
            <Formik<OrderFormValues>
                initialValues={orderFormData}
                validationSchema={orderQuantifiedSchema}
                onSubmit={handleSubmit}
              >
      {({ errors, submitCount, setFieldValue, values }) => (
          <Form className='space-y-5' >
            <fieldset className='p-3 border rounded-xl'>
            <legend className='text-lg font-semibold px-3'>Client Information</legend>
            <div className='grid gap-4 grid-cols-3'>
                <div className={submitCount ? (errors.client_name) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="client_name">Name</label>
                  <Field
                    id="client_name"
                    name="client_name"
                    className="form-input"
                    autoComplete="client_name"
                    placeholder='Client Name'
                  />
                  {(submitCount && errors.client_name) ? <InputError message={errors.client_name} className="mt-2" /> : ''}
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
                }}
              >
                <option value="">Source</option>
                {sources.map((source, index) => (
                  <option key={index} value={source}>{source}</option>
                ))}
              </Field>
              {(submitCount && errors.source) ? <InputError message={errors.source} className="mt-2" /> : ''}
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
                  <div className='grid gap-4 grid-cols-3'>
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
                   
                  <div className={submitCount ? (errors.project_amount) ? 'has-error' : 'has-success' : ''}>
                     { /* <label htmlFor="project_amount">Project Amount</label>
                     <Field
                        id="project_amount"
                        name="project_amount"
                        type="number"
                        className="form-input"
                        autoComplete="project_amount"
                        placeholder='Project Amount'
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                          const value = e.target.value
                          setFieldValue('project_amount', value ? parseFloat(value) : 0)
                        }}
                      /> */}
                      {(submitCount && errors.project_amount) ? <InputError message={errors.project_amount} className="mt-2" /> : ''}
                    </div>
                    </div>
                     <div className={submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}>
                      <label htmlFor="description">Description</label>
                      <Field
                        id="description"
                        name="description"
                        component="textarea"
                        rows="3"
                        className="form-textarea resize-none placeholder:text-white-dark"
                        placeholder='Description'
                      />
                      {(submitCount && errors.description) ? <InputError message={errors.description} className="mt-2" /> : ''}
                    </div>
              </fieldset>
              <div className="flex items-center justify-between mt-4">
                <button className='btn btn-danger uppercase' onClick={handleClose}>Cancel</button>
                <PrimaryButton className="btn btn-primary" type='submit'>
                  Save
                </PrimaryButton>
              </div>
          </Form>
)}
            </Formik>
          </div>
        </div>
        </div>
    </Modal>
  )
}

export default QuantifiedModal
