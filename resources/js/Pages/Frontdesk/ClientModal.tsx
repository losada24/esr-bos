import React, { useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Client } from '@/Pages/Client/ClientCommon'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
/// import { clientSchema } from './CompanyContactCommon'
import { SOURCES } from '@/Utils/constants'
import { clientSchema } from '../CompanyContact/CompanyContactCommon'
import { on } from 'events'

const ClientModal = ({
  showModal,
  onClose,
  //addClient,
  sourcesClients,
  onConfirm
}: {
  showModal: boolean
  onClose: CallableFunction
  onConfirm: (client: Client) => void
  sourcesClients: string[]
}) => {
  const initialValues: Client = {
    id: 0,
    name: '',
    email: '',
    phone: '',
    contact_type: '',
    other_phone: '',
    secondary_email: '',
    source: '',
    vip_clients: false,
    vip_notes: '',
    refer_name: '',
    refer_phone: '',
    referral_id: 0
  }

  // const handleSubmit = async (values: any /*, helpers: FormikHelpers<Client> */) => {
  // if (addClient) {
  // addClient(values)
  // }
  // onClose(false)
  // }
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  const handleSubmit = async (
    values: Client,
    helpers: FormikHelpers<Client>
  ) => {
    try {
      const response = await fetch(route('client.store'), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          ...values,
          from_modal: true
        })
      })
      if (!response.ok) {
        const errorData = await response.json()
        helpers.setErrors(errorData.errors || {})
        helpers.setSubmitting(false)
        return
      }
      const data = await response.json()
      console.log(data)
      if (data.client) {
        onConfirm(data.client)
        onClose()
      }
    } catch (error) {
      console.error(error)
      helpers.setSubmitting(false)
    }
  }

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Add Client</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className="h-[550px] overflow-y-scroll">
            <Formik<Client>
                initialValues={initialValues}
                validationSchema={clientSchema}
                onSubmit={handleSubmit}
              >
                {({ errors, submitCount, setFieldValue, values }) => (
                  <Form>
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
                        {sourcesClients.map((source, index) => (
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
                      <div className="flex items-center justify-between mt-4">
                        <button className='btn btn-danger uppercase' onClick={ (e) => {
                          e.preventDefault()
                          onClose(false)
                        }}>Cancel</button>
                        <PrimaryButton className="btn btn-primary" type='submit'>
                          Add Client
                        </PrimaryButton>
                      </div>
                  </Form>
                )}
            </Formik>
          </div>
        </div>
    </Modal>
  )
}

export default ClientModal
