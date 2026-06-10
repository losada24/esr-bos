import { useEffect, useState } from 'react'
import { Field } from 'formik'
import InputError from '@/Components/InputError'
import { SOURCES } from '@/Utils/constants'

type ReferralValues = {
  source?: string | { value?: string | null } | null
  refer_name?: string
  refer_phone?: string
  refer_email?: string
  referral_id?: number | null
  referrer_client_id?: number | null
  referrer_user_id?: number | null
}

type SearchResult = {
  id: number
  name: string
  phone?: string | null
  email?: string | null
}

const ReferralFields = ({
  values,
  errors,
  submitCount,
  setFieldValue
}: {
  values: ReferralValues
  errors: Record<string, any>
  submitCount: number
  setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void
}) => {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<SearchResult[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const sourceValue = typeof values.source === 'string'
    ? values.source
    : values.source?.value ?? ''

  const isCustomerReferral = sourceValue === SOURCES.EXTERNAL_REFERAL
  const isEmployerReferral = sourceValue === SOURCES.INTERNAL_REFERAL
  const isManualDirectoryReferral = sourceValue === SOURCES.ESW_REFER || sourceValue === SOURCES.ESR_REFER
  const isReferralSource = isCustomerReferral || isEmployerReferral || isManualDirectoryReferral

  const hasExistingSelection = Number(values.referrer_client_id ?? 0) > 0
    || Number(values.referrer_user_id ?? 0) > 0
    || Number(values.referral_id ?? 0) > 0

  useEffect(() => {
    setQuery('')
    setResults([])
    setIsLoading(false)
  }, [sourceValue])

  useEffect(() => {
    if (!isReferralSource || hasExistingSelection) {
      setResults([])
      return
    }

    const normalized = query.trim()
    if (normalized.length < 2) {
      setResults([])
      return
    }

    const controller = new AbortController()
    const searchRoute = isCustomerReferral
        ? `${route('client.search')}?q=${encodeURIComponent(normalized)}`
      : isEmployerReferral
        ? `${route('user.referrers.search')}?q=${encodeURIComponent(normalized)}`
        : `${route('referral.search')}?q=${encodeURIComponent(normalized)}&type=${encodeURIComponent(sourceValue)}`

    const run = async () => {
      setIsLoading(true)
      try {
        const response = await fetch(searchRoute, {
          headers: { Accept: 'application/json' },
          signal: controller.signal
        })

        if (!response.ok) {
          setResults([])
          return
        }

        const data = await response.json()
        setResults(Array.isArray(data?.data) ? data.data : [])
      } catch (error) {
        if (!(error instanceof DOMException && error.name === 'AbortError')) {
          console.error(error)
        }
      } finally {
        setIsLoading(false)
      }
    }

    void run()

    return () => {
      controller.abort()
    }
  }, [query, sourceValue, isReferralSource, hasExistingSelection, isCustomerReferral, isEmployerReferral, isManualDirectoryReferral])

  if (!isReferralSource) {
    return null
  }

  const selectExisting = (selectedId: number) => {
    const selected = results.find(item => item.id === selectedId)
    if (!selected) {
      return
    }

    setQuery(selected.name ?? '')
    setResults([])
    setFieldValue('refer_name', selected.name ?? '')
    setFieldValue('refer_phone', selected.phone ?? '')
    setFieldValue('refer_email', selected.email ?? '')

    if (isCustomerReferral) {
      setFieldValue('referrer_client_id', selected.id)
      setFieldValue('referrer_user_id', null)
      setFieldValue('referral_id', null)
      return
    }

    if (isEmployerReferral) {
      setFieldValue('referrer_user_id', selected.id)
      setFieldValue('referrer_client_id', null)
      setFieldValue('referral_id', null)
      return
    }

    setFieldValue('referral_id', selected.id)
    setFieldValue('referrer_client_id', null)
    setFieldValue('referrer_user_id', null)
  }

  const clearSelection = () => {
    setQuery('')
    setResults([])
    setFieldValue('referral_id', null)
    setFieldValue('referrer_client_id', null)
    setFieldValue('referrer_user_id', null)
  }

  const lookupLabel = isCustomerReferral
    ? 'Find Customer Referral'
    : isEmployerReferral
      ? 'Find Employer Referral'
      : 'Find Existing Referral'

  const queryPlaceholder = isCustomerReferral
    ? 'Search client by name, email or phone'
    : isEmployerReferral
      ? 'Search active user by name, email or phone'
      : 'Search referral by name, email or phone'

  const selectedLabel = [values.refer_name, values.refer_phone, values.refer_email]
    .filter(Boolean)
    .join(' · ')

  const selectionError = errors.referral_id ?? errors.referrer_client_id ?? errors.referrer_user_id
  const showEmptyState = !isLoading && query.trim().length >= 2 && results.length === 0

  return (
    <div className="col-span-full space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
      <div>
        <label htmlFor="referral-search" className="text-xs font-semibold uppercase tracking-wide text-slate-500">{lookupLabel}</label>
        {!hasExistingSelection && (
          <>
            <input
              id="referral-search"
              className="form-input mt-2"
              value={query}
              onChange={(event) => { setQuery(event.target.value) }}
              placeholder={queryPlaceholder}
            />
            {isLoading && (
              <p className="mt-2 text-xs text-slate-500">Searching...</p>
            )}
            {results.length > 0 && (
              <div className="mt-2 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                {results.map((result) => (
                  <button
                    key={result.id}
                    type="button"
                    className="flex w-full items-start justify-between gap-3 border-b border-slate-100 px-3 py-3 text-left last:border-b-0 hover:bg-slate-50"
                    onClick={() => { selectExisting(result.id) }}
                  >
                    <span className="min-w-0">
                      <span className="block truncate text-sm font-semibold text-slate-800">{result.name}</span>
                      <span className="mt-1 block truncate text-xs text-slate-500">
                        {[result.phone, result.email].filter(Boolean).join(' · ') || 'No contact info'}
                      </span>
                    </span>
                    <span className="shrink-0 text-xs font-semibold uppercase tracking-wide text-sky-600">Use</span>
                  </button>
                ))}
              </div>
            )}
            {showEmptyState && (
              <p className="mt-2 text-xs text-slate-500">
                No matches found. Enter the referral details below.
              </p>
            )}
          </>
        )}
        {hasExistingSelection && (
          <div className="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
            <p className="text-sm font-medium text-emerald-800">
              {selectedLabel || 'Existing referral selected'}
            </p>
            <button
              type="button"
              className="mt-2 text-xs font-semibold text-emerald-700 underline"
              onClick={clearSelection}
            >
              Use manual referral instead
            </button>
          </div>
        )}
        <InputError message={submitCount && selectionError ? selectionError : null} className="mt-2" />
      </div>

      {!hasExistingSelection && (
        <div className="grid gap-4 md:grid-cols-3">
          <div>
            <label htmlFor="refer_name" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Referral Name</label>
            <Field
              id="refer_name"
              name="refer_name"
              className="form-input mt-2"
              autoComplete="off"
              placeholder="Referral Name"
            />
            <InputError message={submitCount && errors.refer_name ? errors.refer_name : null} className="mt-2" />
          </div>
          <div>
            <label htmlFor="refer_phone" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Referral Phone</label>
            <Field
              id="refer_phone"
              name="refer_phone"
              className="form-input mt-2"
              autoComplete="off"
              placeholder="Referral Phone"
            />
            <InputError message={submitCount && errors.refer_phone ? errors.refer_phone : null} className="mt-2" />
          </div>
          <div>
            <label htmlFor="refer_email" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Referral Email</label>
            <Field
              id="refer_email"
              name="refer_email"
              type="email"
              className="form-input mt-2"
              autoComplete="off"
              placeholder="Referral Email"
            />
            <InputError message={submitCount && errors.refer_email ? errors.refer_email : null} className="mt-2" />
          </div>
        </div>
      )}
    </div>
  )
}

export default ReferralFields
