import Modal from '@/Components/Modal'
import InputError from '@/Components/InputError'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { FormEvent, type ReactNode } from 'react'

export interface ContactFormValues {
  client_name: string
  email: string
  secondary_email: string
  phone: string
  phone_ext: string
  other_phone: string
  source: string
  vip_clients: boolean
  vip_notes: string
}

export interface RequestFormValues {
  client_name: string
  phone: string
  status: string
  source: string
  notes: string
}

export interface FormErrors {
  [key: string]: string[] | undefined
}

interface ContactEditModalProps {
  open: boolean
  saving: boolean
  canEditRequest: boolean
  values: ContactFormValues
  errors: FormErrors
  sourceOptions: string[]
  errorMessage?: string | null
  onClose: () => void
  onEditRequest: () => void
  onChange: (field: keyof ContactFormValues, value: ContactFormValues[keyof ContactFormValues]) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

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

export function ContactEditModal ({
  open,
  saving,
  canEditRequest,
  values,
  errors,
  sourceOptions,
  errorMessage,
  onClose,
  onEditRequest,
  onChange,
  onSubmit
}: ContactEditModalProps) {
  return (
    <Modal show={open} closeable onClose={() => { if (!saving) onClose() }}>
      <div className="mx-auto w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl">
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <h3 className="text-lg font-semibold text-slate-800 dark:text-white">Edit Contact</h3>
          <button
            type="button"
            className="text-slate-400 transition hover:text-slate-600"
            onClick={() => { if (!saving) onClose() }}
          >
            <CloseIcon />
            <span className="sr-only">Close</span>
          </button>
        </div>
        <div className="p-5">
          <div className="max-h-[70vh] overflow-y-auto pr-1">
            <form onSubmit={onSubmit} className="space-y-5">
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <SectionField label="Name" htmlFor="contact-name">
                    <input
                      id="contact-name"
                      type="text"
                      className="form-input"
                      value={values.client_name}
                      onChange={(event) => { onChange('client_name', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.client_name?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Phone" htmlFor="contact-phone">
                    <input
                      id="contact-phone"
                      type="text"
                      className="form-input"
                      value={values.phone}
                      onChange={(event) => { onChange('phone', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.phone?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Ext" htmlFor="contact-phone-ext">
                    <input
                      id="contact-phone-ext"
                      type="text"
                      className="form-input"
                      value={values.phone_ext}
                      onChange={(event) => { onChange('phone_ext', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.phone_ext?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Email" htmlFor="contact-email">
                    <input
                      id="contact-email"
                      type="email"
                      className="form-input"
                      value={values.email}
                      onChange={(event) => { onChange('email', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.email?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Secondary Email" htmlFor="contact-secondary-email">
                    <input
                      id="contact-secondary-email"
                      type="email"
                      className="form-input"
                      value={values.secondary_email}
                      onChange={(event) => { onChange('secondary_email', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.secondary_email?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Other Phone" htmlFor="contact-other-phone">
                    <input
                      id="contact-other-phone"
                      type="text"
                      className="form-input"
                      value={values.other_phone}
                      onChange={(event) => { onChange('other_phone', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.other_phone?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Source" htmlFor="contact-source">
                    <select
                      id="contact-source"
                      className="form-select"
                      value={values.source ?? ''}
                      onChange={(event) => { onChange('source', event.target.value) }}
                    >
                      <option value="">Select source</option>
                      {sourceOptions.map((sourceOption) => (
                        <option key={sourceOption} value={sourceOption}>{sourceOption}</option>
                      ))}
                    </select>
                  </SectionField>
                  <InputError message={errors.source?.[0] ?? null} className="mt-1" />
                </div>
              </div>

              <div className="space-y-3 rounded-xl bg-slate-50 p-4">
                <div className="flex items-center justify-between">
                  <p className="text-sm font-semibold text-slate-700">VIP Client</p>
                  <label className="inline-flex items-center gap-2 text-sm font-medium text-slate-600">
                    <input
                      id="contact-vip"
                      type="checkbox"
                      className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                      checked={values.vip_clients}
                      onChange={(event) => { onChange('vip_clients', event.target.checked) }}
                    />
                    Enable
                  </label>
                </div>
                <InputError message={errors.vip_clients?.[0] ?? null} className="mt-1" />
                <div>
                  <label htmlFor="contact-vip-notes" className="text-xs font-semibold uppercase tracking-wide text-slate-500">VIP Notes</label>
                  <textarea
                    id="contact-vip-notes"
                    className="form-textarea mt-2"
                    rows={3}
                    value={values.vip_notes ?? ''}
                    onChange={(event) => { onChange('vip_notes', event.target.value) }}
                  />
                  <InputError message={errors.vip_notes?.[0] ?? null} className="mt-1" />
                </div>
              </div>

              {errorMessage && (
                <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-600">{errorMessage}</div>
              )}

              <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <button
                  type="button"
                  onClick={() => { if (!saving) onClose() }}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                  Cancel
                </button>
                <div className="flex items-center gap-2">
                  {canEditRequest && (
                    <button
                      type="button"
                      onClick={() => {
                        if (saving) return
                        onClose()
                        onEditRequest()
                      }}
                      className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                    >
                      Edit Request
                    </button>
                  )}
                  <button
                    type="submit"
                    className="rounded-lg bg-sky-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                    disabled={saving}
                  >
                    {saving ? 'Saving...' : 'Save'}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Modal>
  )
}

interface RequestEditModalProps {
  open: boolean
  saving: boolean
  values: RequestFormValues
  errors: FormErrors
  statusOptions: string[]
  sourceOptions: string[]
  errorMessage?: string | null
  onClose: () => void
  onChange: (field: keyof RequestFormValues, value: string) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

export function RequestEditModal ({
  open,
  saving,
  values,
  errors,
  statusOptions,
  sourceOptions,
  errorMessage,
  onClose,
  onChange,
  onSubmit
}: RequestEditModalProps) {
  return (
    <Modal show={open} closeable onClose={() => { if (!saving) onClose() }}>
      <div className="mx-auto w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-xl">
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <h3 className="text-lg font-semibold text-slate-800 dark:text-white">Edit Request Information</h3>
          <button
            type="button"
            className="text-slate-400 transition hover:text-slate-600"
            onClick={() => { if (!saving) onClose() }}
          >
            <CloseIcon />
            <span className="sr-only">Close</span>
          </button>
        </div>
        <div className="p-5">
          <div className="max-h-[70vh] overflow-y-auto pr-1">
            <form onSubmit={onSubmit} className="space-y-5">
              <div className="grid gap-4 md:grid-cols-2">
                <div>
                  <SectionField label="Name" htmlFor="request-client-name">
                    <input
                      id="request-client-name"
                      type="text"
                      className="form-input"
                      value={values.client_name}
                      onChange={(event) => { onChange('client_name', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.client_name?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Phone" htmlFor="request-phone">
                    <input
                      id="request-phone"
                      type="text"
                      className="form-input"
                      value={values.phone}
                      onChange={(event) => { onChange('phone', event.target.value) }}
                    />
                  </SectionField>
                  <InputError message={errors.phone?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Status" htmlFor="request-status">
                    <select
                      id="request-status"
                      className="form-select"
                      value={values.status}
                      onChange={(event) => { onChange('status', event.target.value) }}
                    >
                      <option value="">Select status</option>
                      {statusOptions.map((statusOption) => (
                        <option key={statusOption} value={statusOption}>{statusOption}</option>
                      ))}
                    </select>
                  </SectionField>
                  <InputError message={errors.status?.[0] ?? null} className="mt-1" />
                </div>
                <div>
                  <SectionField label="Source" htmlFor="request-source">
                    <select
                      id="request-source"
                      className="form-select"
                      value={values.source ?? ''}
                      onChange={(event) => { onChange('source', event.target.value) }}
                    >
                      <option value="">Select source</option>
                      {sourceOptions.map((sourceOption) => (
                        <option key={sourceOption} value={sourceOption}>{sourceOption}</option>
                      ))}
                    </select>
                  </SectionField>
                  <InputError message={errors.source?.[0] ?? null} className="mt-1" />
                </div>
              </div>
              <div>
                <label htmlFor="request-notes" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
                <textarea
                  id="request-notes"
                  className="form-textarea mt-2"
                  rows={4}
                  value={values.notes ?? ''}
                  onChange={(event) => { onChange('notes', event.target.value) }}
                />
                <InputError message={errors.notes?.[0] ?? null} className="mt-1" />
              </div>

              {errorMessage && (
                <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-600">{errorMessage}</div>
              )}

              <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <button
                  type="button"
                  onClick={() => { if (!saving) onClose() }}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="rounded-lg bg-sky-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={saving}
                >
                  {saving ? 'Saving...' : 'Save'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Modal>
  )
}
