import React, { useEffect, useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Client } from '@/Pages/Client/ClientCommon'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { clientSchema } from './CompanyContactCommon'
import ReferralFields from '@/Components/ReferralFields'

const ClientModal = ({
  showModal,
  onClose,
  addClient,
  sources,
  allowExistingSelection = false,
  selectedClients = []
}: {
  showModal: boolean
  onClose: CallableFunction
  addClient?: (client: Client) => void
  sources: string[]
  allowExistingSelection?: boolean
  selectedClients?: Client[]
}) => {
  const [existingClientQuery, setExistingClientQuery] = useState<string>('')
  const [existingClientResults, setExistingClientResults] = useState<Client[]>([])
  const [existingClientLoading, setExistingClientLoading] = useState<boolean>(false)
  const [existingClientError, setExistingClientError] = useState<string | null>(null)

  useEffect(() => {
    if (!showModal) {
      setExistingClientQuery('')
      setExistingClientResults([])
      setExistingClientLoading(false)
      setExistingClientError(null)
      return
    }

    if (!allowExistingSelection) {
      return
    }

    const trimmedQuery = existingClientQuery.trim()
    if (trimmedQuery.length < 2) {
      setExistingClientResults([])
      setExistingClientError(null)
      return
    }

    let ignore = false
    setExistingClientLoading(true)
    setExistingClientError(null)

    const timeoutId = window.setTimeout(async () => {
      try {
        const response = await fetch(route('client.search', { q: trimmedQuery }), {
          headers: {
            Accept: 'application/json'
          }
        })

        if (!response.ok) {
          throw new Error('Search failed')
        }

        const data = await response.json()
        if (ignore) {
          return
        }

        const selectedClientIds = selectedClients
          .filter((client) => Boolean(client.id))
          .map((client) => Number(client.id))

        const results = Array.isArray(data?.data) ? data.data : []
        setExistingClientResults(
          results.filter((client: Client) => !selectedClientIds.includes(Number(client.id)))
        )
      } catch (error) {
        if (!ignore) {
          setExistingClientError('No se pudo buscar clientes existentes.')
          setExistingClientResults([])
        }
      } finally {
        if (!ignore) {
          setExistingClientLoading(false)
        }
      }
    }, 250)

    return () => {
      ignore = true
      window.clearTimeout(timeoutId)
    }
  }, [allowExistingSelection, existingClientQuery, selectedClients, showModal])

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
    refer_email: '',
    referral_id: null,
    referrer_client_id: null,
    referrer_user_id: null
  }

  const handleSubmit = async (values: any, helpers: FormikHelpers<Client>) => {
    const phone = (values.phone ?? '').trim()

    helpers.setSubmitting(true)

    try {
      const response = await fetch(route('client.phone_exists', { phone }), {
        headers: {
          Accept: 'application/json'
        }
      })

      if (!response.ok) {
        helpers.setFieldError('phone', 'No se pudo validar el telefono.')
        return
      }

      const data = await response.json()
      if (data?.exists) {
        helpers.setFieldError('phone', 'El telefono ya existe.')
        return
      }

      if (addClient) {
        addClient(values)
      }
      onClose(false)
    } catch (error) {
      helpers.setFieldError('phone', 'No se pudo validar el telefono.')
    } finally {
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
            {allowExistingSelection && (
              <div className="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div className="mb-2 text-sm font-semibold text-slate-700">Link Existing Client</div>
                <div className="mb-3 text-xs text-slate-500">Search by name, email or phone and link the client to this company.</div>
                <input
                  type="text"
                  value={existingClientQuery}
                  onChange={(event) => {
                    setExistingClientQuery(event.target.value)
                  }}
                  className="form-input"
                  placeholder="Search client"
                />
                {existingClientLoading && (
                  <div className="mt-3 text-xs text-slate-500">Searching clients...</div>
                )}
                {!existingClientLoading && existingClientError && (
                  <InputError message={existingClientError} className="mt-3" />
                )}
                {!existingClientLoading && !existingClientError && existingClientQuery.trim().length >= 2 && (
                  <div className="mt-3 max-h-48 overflow-y-auto rounded border border-slate-200 bg-white">
                    {existingClientResults.length > 0
                      ? existingClientResults.map((client) => (
                        <button
                          key={client.id}
                          type="button"
                          className="flex w-full items-center justify-between border-b border-slate-100 px-3 py-2 text-left last:border-b-0 hover:bg-slate-50"
                          onClick={() => {
                            addClient?.(client)
                            onClose(false)
                          }}
                        >
                          <div>
                            <div className="text-sm font-medium text-slate-700">{client.name}</div>
                            <div className="text-xs text-slate-500">
                              {[client.phone, client.email].filter(Boolean).join(' · ') || 'No contact info'}
                            </div>
                          </div>
                          <span className="text-xs font-semibold text-slate-600">Associate</span>
                        </button>
                      ))
                      : (
                        <div className="px-3 py-2 text-xs text-slate-500">No existing clients found.</div>
                        )}
                  </div>
                )}
              </div>
            )}

            <div className="mb-4 text-sm font-semibold text-slate-700">Create New Client</div>
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
                          setFieldValue('referral_id', null)
                          setFieldValue('referrer_client_id', null)
                          setFieldValue('referrer_user_id', null)
                          setFieldValue('refer_name', '')
                          setFieldValue('refer_phone', '')
                          setFieldValue('refer_email', '')
                        }}
                      >
                        <option value="">Source</option>
                        {sources.map((source, index) => (
                          <option key={index} value={source}>{source}</option>
                        ))}
                      </Field>
                      {(submitCount && errors.source) ? <InputError message={errors.source} className="mt-2" /> : ''}
                      </div>
                      <ReferralFields
                        values={values}
                        errors={errors as Record<string, any>}
                        submitCount={submitCount}
                        setFieldValue={setFieldValue}
                      />

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
