import Modal from '@/Components/Modal'
import InputError from '@/Components/InputError'
import { FormEvent } from 'react'

export interface RequestFormValues {
  client_name: string
  phone: string
  status: string
  source: string
  notes: string
}

export type RequestFormErrors = Partial<Record<keyof RequestFormValues, string[]>>

interface RequestEditModalProps {
  open: boolean
  saving: boolean
  values: RequestFormValues
  errors: RequestFormErrors
  statusOptions: string[]
  sourceOptions: string[]
  errorMessage?: string | null
  onClose: () => void
  onChange: (field: keyof RequestFormValues, value: RequestFormValues[keyof RequestFormValues]) => void
  onSubmit: (event: FormEvent<HTMLFormElement>) => void
}

export default function RequestEditModal ({
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
      <div className="mx-auto w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-slate-800">Edit Request Information</h3>
          <button
            type="button"
            className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            onClick={() => { if (!saving) onClose() }}
          >
            <span className="sr-only">Close</span>
            ×
          </button>
        </div>
        <form onSubmit={onSubmit} className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="request-client-name" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
              <input
                id="request-client-name"
                type="text"
                className="form-input"
                value={values.client_name}
                onChange={(event) => { onChange('client_name', event.target.value) }}
              />
              <InputError message={errors.client_name?.[0] ?? null} className="mt-1" />
            </div>
            <div>
              <label htmlFor="request-phone" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</label>
              <input
                id="request-phone"
                type="text"
                className="form-input"
                value={values.phone}
                onChange={(event) => { onChange('phone', event.target.value) }}
              />
              <InputError message={errors.phone?.[0] ?? null} className="mt-1" />
            </div>
            <div>
              <label htmlFor="request-status" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
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
              <InputError message={errors.status?.[0] ?? null} className="mt-1" />
            </div>
            <div>
              <label htmlFor="request-source" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Source</label>
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
              <InputError message={errors.source?.[0] ?? null} className="mt-1" />
            </div>
          </div>
          <div>
            <label htmlFor="request-notes" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</label>
            <textarea
              id="request-notes"
              className="form-textarea"
              rows={4}
              value={values.notes ?? ''}
              onChange={(event) => { onChange('notes', event.target.value) }}
            />
            <InputError message={errors.notes?.[0] ?? null} className="mt-1" />
          </div>
          {errorMessage && (
            <p className="text-sm text-rose-600">{errorMessage}</p>
          )}
          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={() => { if (!saving) onClose() }}
              className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
              disabled={saving}
            >
              {saving ? 'Saving...' : 'Save'}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  )
}
