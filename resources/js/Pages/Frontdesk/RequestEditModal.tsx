import Modal from '@/Components/Modal'
import InputError from '@/Components/InputError'
import { PRODUCT_LINES } from '@/Utils/constants'
import { type Client } from '@/Pages/Client/ClientCommon'
import { type CompanyContact } from '@/types'
import { type Source } from '@/types/interfaces/order'
import PlusIcon from '@/Components/Icons/PlusIcon'
import CompanyModal from '@/Pages/Frontdesk/CompanyModal'
import ClientModal from '@/Pages/Frontdesk/ClientModal'
import Select from 'react-select'
import { type FormEvent, useEffect, useState } from 'react'

export interface RequestCommercialPair {
  company_id: number | null
  client_id: number | null
  source_id: number | null
}

export interface RequestFormValues {
  client_name: string
  phone: string
  status: string
  source: string
  notes: string
  product_line: string
  commercial_pairs: RequestCommercialPair[]
}

export type RequestFormErrors = Record<string, string[] | undefined>

interface RequestEditModalProps {
  open: boolean
  saving: boolean
  values: RequestFormValues
  errors: RequestFormErrors
  statusOptions: string[]
  sourceOptions: string[]
  isCommercial: boolean
  companies: CompanyContact[]
  clients: Client[]
  qualifiedSources: Source[]
  sourcesClients: string[]
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
  isCommercial,
  companies,
  clients,
  qualifiedSources,
  sourcesClients,
  errorMessage,
  onClose,
  onChange,
  onSubmit
}: RequestEditModalProps) {
  const [companyOptions, setCompanyOptions] = useState<CompanyContact[]>(companies)
  const [clientOptions, setClientOptions] = useState<Client[]>(clients)
  const [companyModalTargetIndex, setCompanyModalTargetIndex] = useState<number | null>(null)
  const [clientModalTargetIndex, setClientModalTargetIndex] = useState<number | null>(null)

  useEffect(() => {
    setCompanyOptions(companies)
  }, [companies])

  useEffect(() => {
    setClientOptions(clients)
  }, [clients])

  const updateCommercialPair = (index: number, field: keyof RequestCommercialPair, value: number | null) => {
    const pairs = values.commercial_pairs.map((pair, pairIndex) => (
      pairIndex === index
        ? { ...pair, [field]: value, ...(field === 'company_id' ? { client_id: null } : {}) }
        : pair
    ))
    onChange('commercial_pairs', pairs)
  }

  const clientBelongsToCompany = (client: Client, companyId: number | null) => {
    if (companyId == null) return false
    const companyIds = Array.isArray(client.company_contact_ids)
      ? client.company_contact_ids.map(Number)
      : (client.company_contact_id != null ? [Number(client.company_contact_id)] : [])

    return companyIds.includes(Number(companyId))
  }

  const handleCompanyCreated = (company: CompanyContact) => {
    setCompanyOptions((current) => (
      current.some((item) => item.id === company.id) ? current : [...current, company]
    ))
    if (companyModalTargetIndex != null) {
      updateCommercialPair(companyModalTargetIndex, 'company_id', company.id)
    }
    setCompanyModalTargetIndex(null)
  }

  const handleClientCreated = (client: Client) => {
    if (clientModalTargetIndex == null) return
    const companyId = values.commercial_pairs[clientModalTargetIndex]?.company_id ?? null
    const companyIds = Array.isArray(client.company_contact_ids)
      ? client.company_contact_ids.map(Number)
      : (client.company_contact_id != null ? [Number(client.company_contact_id)] : [])
    const clientWithCompany = {
      ...client,
      company_contact_id: companyId ?? client.company_contact_id,
      company_contact_ids: companyId != null && !companyIds.includes(companyId)
        ? [...companyIds, companyId]
        : companyIds
    }

    setClientOptions((current) => {
      const existingIndex = current.findIndex((item) => item.id === clientWithCompany.id)
      if (existingIndex === -1) return [...current, clientWithCompany]
      return current.map((item, index) => index === existingIndex ? clientWithCompany : item)
    })
    updateCommercialPair(clientModalTargetIndex, 'client_id', client.id)
    setClientModalTargetIndex(null)
  }

  return (
    <>
      <Modal show={open} maxWidth="5xl" closeable onClose={() => { if (!saving) onClose() }}>
        <div className="mx-auto max-h-[calc(100vh-3rem)] w-full overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
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
            <div>
              <label htmlFor="request-product-line" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Product Line</label>
              <select
                id="request-product-line"
                className="form-select"
                value={values.product_line}
                onChange={(event) => { onChange('product_line', event.target.value) }}
              >
                <option value="">Select product line</option>
                {PRODUCT_LINES.map((productLine) => (
                  <option key={productLine} value={productLine}>{productLine}</option>
                ))}
              </select>
              <InputError message={errors.product_line?.[0] ?? null} className="mt-1" />
            </div>
          </div>
          {isCommercial && (
            <div className="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div className="flex items-center justify-between gap-3">
                <div>
                  <p className="text-sm font-semibold text-slate-700">Companies</p>
                  <p className="text-xs text-slate-500">Associate up to 5 companies, contacts and sources.</p>
                </div>
                <button
                  type="button"
                  className="rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-300"
                  disabled={values.commercial_pairs.length >= 5}
                  onClick={() => {
                    onChange('commercial_pairs', [
                      ...values.commercial_pairs,
                      { company_id: null, client_id: null, source_id: null }
                    ])
                  }}
                >
                  Add Company
                </button>
              </div>
              {values.commercial_pairs.map((pair, index) => {
                const selectedCompany = pair.company_id
                  ? companyOptions.find((company) => Number(company.id) === Number(pair.company_id))
                  : null
                const availableClients = clientOptions.filter((client) => clientBelongsToCompany(client, pair.company_id))
                const selectedClient = pair.client_id
                  ? availableClients.find((client) => Number(client.id) === Number(pair.client_id))
                  : null
                const selectedSource = pair.source_id
                  ? qualifiedSources.find((source) => Number(source.id) === Number(pair.source_id))
                  : null

                return (
                  <div key={`request-commercial-pair-${index}`} className="rounded-lg border border-slate-200 bg-white p-3">
                    <div className="mb-2 flex items-center justify-between">
                      <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Company {index + 1}</span>
                      <button
                        type="button"
                        className="text-xs font-semibold text-rose-600"
                        onClick={() => {
                          onChange('commercial_pairs', values.commercial_pairs.filter((_, pairIndex) => pairIndex !== index))
                        }}
                      >
                        Remove
                      </button>
                    </div>
                    <div className="grid gap-3 md:grid-cols-3">
                      <div>
                        <label className="text-xs font-semibold uppercase text-slate-500">Company</label>
                        <div className="mt-1 flex items-center">
                          <div className="min-w-0 flex-1">
                            <Select
                              inputId={`request-company-${index}`}
                              placeholder="Company"
                              value={selectedCompany ? { value: selectedCompany.id, label: selectedCompany.name } : null}
                              options={companyOptions.map((company) => ({ value: company.id, label: company.name }))}
                              onChange={(option) => {
                                updateCommercialPair(index, 'company_id', option ? Number(option.value) : null)
                              }}
                              styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                            />
                          </div>
                          <button
                            type="button"
                            title="Create Company"
                            className="flex h-10 w-11 items-center justify-center rounded-r-md border border-l-0 border-[#e0e6ed] bg-[#2c7df6]"
                            onClick={() => { setCompanyModalTargetIndex(index) }}
                          >
                            <PlusIcon className="text-white" />
                          </button>
                        </div>
                        <InputError message={errors[`commercial_pairs.${index}.company_id`]?.[0] ?? null} className="mt-1" />
                      </div>
                      <div>
                        <label className="text-xs font-semibold uppercase text-slate-500">Contact Name</label>
                        <div className="mt-1 flex items-center">
                          <div className="min-w-0 flex-1">
                            <Select
                              inputId={`request-client-${index}`}
                              placeholder={pair.company_id ? 'Client' : 'Select company first'}
                              value={selectedClient ? { value: selectedClient.id, label: selectedClient.name } : null}
                              options={availableClients.map((client) => ({ value: client.id, label: client.name }))}
                              isDisabled={!pair.company_id}
                              onChange={(option) => {
                                updateCommercialPair(index, 'client_id', option ? Number(option.value) : null)
                              }}
                              styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                            />
                          </div>
                          <button
                            type="button"
                            title="Create Contact"
                            disabled={!pair.company_id}
                            className={`flex h-10 w-11 items-center justify-center rounded-r-md border border-l-0 border-[#e0e6ed] bg-[#2c7df6] ${!pair.company_id ? 'cursor-not-allowed opacity-50' : ''}`}
                            onClick={() => { setClientModalTargetIndex(index) }}
                          >
                            <PlusIcon className="text-white" />
                          </button>
                        </div>
                        <InputError message={errors[`commercial_pairs.${index}.client_id`]?.[0] ?? null} className="mt-1" />
                      </div>
                      <div>
                        <label className="text-xs font-semibold uppercase text-slate-500">Source</label>
                        <div className="mt-1">
                          <Select
                            inputId={`request-source-${index}`}
                            placeholder="Source"
                            value={selectedSource ? { value: selectedSource.id, label: selectedSource.name } : null}
                            options={qualifiedSources.map((source) => ({ value: source.id, label: source.name }))}
                            onChange={(option) => {
                              updateCommercialPair(index, 'source_id', option ? Number(option.value) : null)
                            }}
                            styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                          />
                        </div>
                        <InputError message={errors[`commercial_pairs.${index}.source_id`]?.[0] ?? null} className="mt-1" />
                      </div>
                    </div>
                  </div>
                )
              })}
              <InputError message={errors.commercial_pairs?.[0] ?? null} className="mt-1" />
            </div>
          )}
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
      <CompanyModal
        showModal={companyModalTargetIndex != null}
        onClose={() => { setCompanyModalTargetIndex(null) }}
        onConfirm={handleCompanyCreated}
      />
      <ClientModal
        showModal={clientModalTargetIndex != null}
        onClose={() => { setClientModalTargetIndex(null) }}
        onConfirm={handleClientCreated}
        sourcesClients={sourcesClients}
        orderType="COMMERCIAL"
      />
    </>
  )
}
