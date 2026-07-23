import { type ChangeEvent, type ReactNode, useEffect, useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Client } from '@/Pages/Client/ClientCommon'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import InputError from '@/Components/InputError'
import { ORDER_TYPES } from '@/Utils/constants'
import ReferralFields from '@/Components/ReferralFields'
import { clientSchema } from '../CompanyContact/CompanyContactCommon'

const SectionField = ({
  label,
  htmlFor,
  children
}: {
  label: string
  htmlFor: string
  children: ReactNode
}) => (
  <label htmlFor={htmlFor} className="space-y-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
    <span>{label}</span>
    <div>{children}</div>
  </label>
)

const ClientModal = ({
  showModal,
  onClose,
  //addClient,
  sourcesClients,
  onConfirm,
  orderType
}: {
  showModal: boolean
  onClose: CallableFunction
  onConfirm: (client: Client) => void
  sourcesClients: string[]
  orderType?: string
}) => {
  type ClientFormValues = Client & { order_type?: string }
  const [existingClientQuery, setExistingClientQuery] = useState('')
  const [existingClientResults, setExistingClientResults] = useState<Client[]>([])
  const [existingClientLoading, setExistingClientLoading] = useState(false)
  const [existingClientError, setExistingClientError] = useState<string | null>(null)
  const initialValues: ClientFormValues = {
    id: 0,
    name: '',
    email: '',
    phone: '',
    phone_ext: '',
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
    referrer_user_id: null,
    order_type: orderType ?? ''
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  useEffect(() => {
    if (!showModal) {
      setExistingClientQuery('')
      setExistingClientResults([])
      setExistingClientLoading(false)
      setExistingClientError(null)
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
        if (!ignore) {
          setExistingClientResults(Array.isArray(data?.data) ? data.data : [])
        }
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
  }, [existingClientQuery, showModal])

  const submitClient = async (
    values: ClientFormValues,
    helpers: FormikHelpers<ClientFormValues>,
    forceCreate = false
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
          from_modal: true,
          force_create: forceCreate
        })
      })
      const data = await response.json()
      if (!response.ok) {
        if (
          !forceCreate &&
          values.order_type === ORDER_TYPES.COMMERCIAL &&
          data?.errors?.email_exists
        ) {
          const confirmCreate = window.confirm(
            'This email is already associated with a client. Do you want to create a new client anyway?'
          )
          if (confirmCreate) {
            await submitClient(values, helpers, true)
            return
          }
        }
        helpers.setErrors(data.errors || {})
        helpers.setSubmitting(false)
        return
      }
      if (data.client) {
        onConfirm(data.client)
        onClose()
      }
    } catch (error) {
      console.error(error)
      helpers.setSubmitting(false)
    }
  }
  const handleSubmit = async (
    values: ClientFormValues,
    helpers: FormikHelpers<ClientFormValues>
  ) => {
    await submitClient(values, helpers)
  }

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
      <div className="mx-auto w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl">
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <h3 className="text-lg font-semibold text-slate-800 dark:text-white">Add Client</h3>
          <button
            type="button"
            className="text-slate-400 transition hover:text-slate-600"
            onClick={() => { onClose(false) }}
          >
            <CloseIcon />
            <span className="sr-only">Close</span>
          </button>
        </div>
        <div className="p-5">
          <div className="max-h-[70vh] overflow-y-auto pr-1">
            <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div className="mb-2 text-sm font-semibold text-slate-700">Link Existing Client</div>
              <div className="mb-3 text-xs text-slate-500">Search an existing client and use it in this order.</div>
              <input
                type="text"
                value={existingClientQuery}
                onChange={(event) => {
                  setExistingClientQuery(event.target.value)
                }}
                className="form-input"
                placeholder="Search by name, email or phone"
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
                          onConfirm(client)
                          onClose()
                        }}
                      >
                        <div>
                          <div className="text-sm font-medium text-slate-700">{client.name}</div>
                          <div className="text-xs text-slate-500">
                            {[client.phone, client.email].filter(Boolean).join(' · ') || 'No contact info'}
                          </div>
                        </div>
                        <span className="text-xs font-semibold text-slate-600">Use Client</span>
                      </button>
                    ))
                    : (
                      <div className="px-3 py-2 text-xs text-slate-500">No existing clients found.</div>
                      )}
                </div>
              )}
            </div>

            <div className="mb-4 text-sm font-semibold text-slate-700">Create New Client</div>
            <Formik<ClientFormValues>
              initialValues={initialValues}
              validationSchema={clientSchema}
              onSubmit={handleSubmit}
            >
              {({ errors, submitCount, setFieldValue, values, isSubmitting }) => (
                <Form className="space-y-5">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div>
                      <SectionField label="Name" htmlFor="name">
                        <Field
                          id="name"
                          name="name"
                          className="form-input"
                          autoComplete="name"
                          placeholder="Name"
                        />
                      </SectionField>
                      <InputError message={submitCount && errors.name ? errors.name : null} className="mt-1" />
                    </div>
                    <div>
                      <SectionField label="Email" htmlFor="email">
                        <Field
                          id="email"
                          name="email"
                          type="email"
                          className="form-input"
                          autoComplete="off"
                          placeholder="Email"
                        />
                      </SectionField>
                      <InputError message={submitCount && errors.email ? errors.email : null} className="mt-1" />
                    </div>
                    <div>
                      <SectionField label="Secondary Email" htmlFor="secondary_email">
                        <Field
                          id="secondary_email"
                          name="secondary_email"
                          type="email"
                          className="form-input"
                          autoComplete="off"
                          placeholder="Secondary Email"
                        />
                      </SectionField>
                      <InputError message={submitCount && errors.secondary_email ? errors.secondary_email : null} className="mt-1" />
                    </div>
                    <div>
                      <SectionField label="Phone" htmlFor="phone">
                        <Field
                          id="phone"
                          name="phone"
                          className="form-input"
                          autoComplete="off"
                          placeholder="Phone"
                        />
                      </SectionField>
                      <InputError message={submitCount && errors.phone ? errors.phone : null} className="mt-1" />
                    </div>
                    <div>
                      <SectionField label="Ext" htmlFor="phone_ext">
                        <Field
                          id="phone_ext"
                          name="phone_ext"
                          className="form-input"
                          autoComplete="off"
                          placeholder="Ext"
                        />
                      </SectionField>
                      <InputError message={submitCount && errors.phone_ext ? errors.phone_ext : null} className="mt-1" />
                    </div>
                    <div>
                      <SectionField label="Other Phone" htmlFor="other_phone">
                        <Field
                          id="other_phone"
                          name="other_phone"
                          className="form-input"
                          autoComplete="off"
                          placeholder="Other Phone"
                        />
                      </SectionField>
                      <InputError message={submitCount && errors.other_phone ? errors.other_phone : null} className="mt-1" />
                    </div>
                    <div className="md:col-span-2">
                      <SectionField label="Source" htmlFor="source">
                        <Field
                          id="source"
                          name="source"
                          as="select"
                          className="form-select"
                          autoComplete="off"
                          onChange={(event: ChangeEvent<HTMLSelectElement>) => {
                            setFieldValue('source', event.target.value)
                            setFieldValue('referral_id', null)
                            setFieldValue('referrer_client_id', null)
                            setFieldValue('referrer_user_id', null)
                            setFieldValue('refer_name', '')
                            setFieldValue('refer_phone', '')
                            setFieldValue('refer_email', '')
                          }}
                        >
                          <option value="">Select source</option>
                          {sourcesClients.map((source, index) => (
                            <option key={index} value={source}>{source}</option>
                          ))}
                        </Field>
                      </SectionField>
                      <InputError message={submitCount && errors.source ? errors.source : null} className="mt-1" />
                    </div>
                    <ReferralFields
                      values={values}
                      errors={errors as Record<string, any>}
                      submitCount={submitCount}
                      setFieldValue={setFieldValue}
                    />
                  </div>

                  <div className="space-y-3 rounded-xl bg-slate-50 p-4">
                    <div className="flex items-center justify-between">
                      <p className="text-sm font-semibold text-slate-700">VIP Client</p>
                      <label className="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                        <Field
                          id="vip_clients"
                          name="vip_clients"
                          type="checkbox"
                          className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                          onChange={(event: ChangeEvent<HTMLInputElement>) => {
                            setFieldValue('vip_clients', event.target.checked)
                            if (!event.target.checked) {
                              setFieldValue('vip_notes', '')
                            }
                          }}
                        />
                        Enable
                      </label>
                    </div>
                    <InputError message={submitCount && errors.vip_clients ? errors.vip_clients : null} className="mt-1" />
                    {values.vip_clients && (
                      <div>
                        <label htmlFor="vip_notes" className="text-xs font-semibold uppercase tracking-wide text-slate-500">VIP Notes</label>
                        <Field
                          id="vip_notes"
                          name="vip_notes"
                          as="textarea"
                          rows={3}
                          className="form-textarea mt-2 resize-none placeholder:text-slate-400"
                          placeholder="Notes"
                        />
                        <InputError message={submitCount && errors.vip_notes ? errors.vip_notes : null} className="mt-1" />
                      </div>
                    )}
                  </div>

                  <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                    <button
                      type="button"
                      onClick={() => { onClose(false) }}
                      className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="rounded-lg bg-sky-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                      disabled={isSubmitting}
                    >
                      Add Client
                    </button>
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

export default ClientModal
