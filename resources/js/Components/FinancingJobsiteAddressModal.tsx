import { useEffect, useRef, useState } from 'react'
import { useJsApiLoader } from '@react-google-maps/api'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
const GOOGLE_MAPS_LIBRARIES: Array<'places'> = ['places']

export interface FinancingJobsiteAddressValues {
  sameAsDelivery: boolean
  address: string
}

interface FinancingJobsiteAddressModalProps {
  open: boolean
  deliveryAddress: string
  initialAddress?: string | null
  onClose: () => void
  onSubmit: (values: FinancingJobsiteAddressValues) => Promise<void>
}

const normalizeAddress = (address?: string | null): string => String(address ?? '').trim().toLowerCase()

export default function FinancingJobsiteAddressModal ({
  open,
  deliveryAddress,
  initialAddress,
  onClose,
  onSubmit
}: FinancingJobsiteAddressModalProps) {
  const normalizedDeliveryAddress = deliveryAddress.trim()
  const initialMatchesDelivery = normalizedDeliveryAddress !== '' &&
    normalizeAddress(initialAddress) === normalizeAddress(normalizedDeliveryAddress)
  const [sameAsDelivery, setSameAsDelivery] = useState(initialMatchesDelivery)
  const [address, setAddress] = useState(initialMatchesDelivery ? '' : String(initialAddress ?? '').trim())
  const [addressConfirmed, setAddressConfirmed] = useState(Boolean(initialAddress) && !initialMatchesDelivery)
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  const [checkingAddress, setCheckingAddress] = useState(false)
  const [predictions, setPredictions] = useState<google.maps.places.AutocompletePrediction[]>([])
  const addressInputRef = useRef<HTMLInputElement | null>(null)
  const autocompleteServiceRef = useRef<google.maps.places.AutocompleteService | null>(null)
  const geocoderRef = useRef<google.maps.Geocoder | null>(null)
  const predictionRequestRef = useRef(0)

  const { isLoaded, loadError } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: GOOGLE_MAPS_LIBRARIES
  })

  useEffect(() => {
    if (!open) return

    const matchesDelivery = normalizedDeliveryAddress !== '' &&
      normalizeAddress(initialAddress) === normalizeAddress(normalizedDeliveryAddress)
    setSameAsDelivery(matchesDelivery)
    setAddress(matchesDelivery ? '' : String(initialAddress ?? '').trim())
    setAddressConfirmed(Boolean(initialAddress) && !matchesDelivery)
    setError(null)
    setSaving(false)
    setCheckingAddress(false)
    setPredictions([])
  }, [initialAddress, normalizedDeliveryAddress, open])

  useEffect(() => {
    if (!isLoaded || typeof google === 'undefined') return

    geocoderRef.current = new google.maps.Geocoder()
    autocompleteServiceRef.current = new google.maps.places.AutocompleteService()

    return () => {
      geocoderRef.current = null
      autocompleteServiceRef.current = null
    }
  }, [isLoaded])

  useEffect(() => {
    const service = autocompleteServiceRef.current
    const input = address.trim()
    const requestId = ++predictionRequestRef.current

    if (!open || sameAsDelivery || !isLoaded || addressConfirmed || input.length < 2 || !service) {
      setPredictions([])
      return
    }

    const timeout = window.setTimeout(() => {
      service.getPlacePredictions({ input, types: ['address'] }, (results, status) => {
        if (requestId !== predictionRequestRef.current) return

        if (status !== google.maps.places.PlacesServiceStatus.OK || !results) {
          setPredictions([])
          return
        }

        setPredictions(results)
      })
    }, 250)

    return () => { window.clearTimeout(timeout) }
  }, [address, addressConfirmed, isLoaded, open, sameAsDelivery])

  const resolveAddressWithGoogle = async (value: string): Promise<string | null> => {
    const geocoder = geocoderRef.current
    const trimmedValue = value.trim()
    if (!geocoder || trimmedValue === '') return null

    setCheckingAddress(true)
    try {
      return await new Promise((resolve) => {
        geocoder.geocode({ address: trimmedValue }, (results, status) => {
          if (status !== 'OK' || !results?.[0]?.formatted_address) {
            resolve(null)
            return
          }

          resolve(results[0].formatted_address.trim())
        })
      })
    } finally {
      setCheckingAddress(false)
    }
  }

  const confirmTypedAddress = async () => {
    if (sameAsDelivery || address.trim() === '' || addressConfirmed) return

    const resolvedAddress = await resolveAddressWithGoogle(address)
    if (!resolvedAddress) {
      setError('Select a Google suggestion or enter an address Google can recognize.')
      return
    }

    setAddress(resolvedAddress)
    setAddressConfirmed(true)
    setPredictions([])
    setError(null)
  }

  const selectPrediction = async (prediction: google.maps.places.AutocompletePrediction) => {
    const geocoder = geocoderRef.current
    if (!geocoder) return

    setCheckingAddress(true)
    setError(null)
    try {
      const formattedAddress = await new Promise<string | null>((resolve) => {
        geocoder.geocode({ placeId: prediction.place_id }, (results, status) => {
          if (status !== 'OK' || !results?.[0]?.formatted_address) {
            resolve(null)
            return
          }

          resolve(results[0].formatted_address.trim())
        })
      })

      if (!formattedAddress) {
        setError('Google could not load that address. Please choose another suggestion.')
        return
      }

      setAddress(formattedAddress)
      setAddressConfirmed(true)
      setPredictions([])
      addressInputRef.current?.focus()
    } finally {
      setCheckingAddress(false)
    }
  }

  const submit = async () => {
    if (sameAsDelivery && normalizedDeliveryAddress === '') {
      setError('A delivery address is not available for this order.')
      return
    }

    let resolvedAddress = sameAsDelivery ? normalizedDeliveryAddress : address.trim()
    if (resolvedAddress === '') {
      setError('Jobsite Address is required.')
      return
    }

    if (!sameAsDelivery && !addressConfirmed) {
      const googleAddress = await resolveAddressWithGoogle(resolvedAddress)
      if (!googleAddress) {
        setError('Select a Google suggestion or enter an address Google can recognize.')
        return
      }

      resolvedAddress = googleAddress
      setAddress(googleAddress)
      setAddressConfirmed(true)
    }

    setSaving(true)
    setError(null)
    try {
      await onSubmit({
        sameAsDelivery,
        address: resolvedAddress
      })
    } catch (submissionError) {
      setError(submissionError instanceof Error ? submissionError.message : 'Unable to save the Jobsite Address.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal show={open} maxWidth="2xl" closeable={!saving} onClose={() => { if (!saving) onClose() }}>
      <div className="overflow-hidden rounded-2xl bg-white shadow-xl">
        <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4">
          <div>
            <h3 className="text-lg font-semibold text-slate-800">Financing Jobsite Address</h3>
            <p className="mt-1 text-sm text-slate-500">Required before moving this financed order to ACCOUNT RECEIPT.</p>
          </div>
          <button
            type="button"
            className="text-slate-400 transition hover:text-slate-600 disabled:opacity-60"
            onClick={() => { if (!saving) onClose() }}
            disabled={saving}
          >
            <CloseIcon />
            <span className="sr-only">Close</span>
          </button>
        </div>

        <div className="space-y-5 px-6 py-5">
          <div className="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
            <p className="text-xs font-semibold uppercase tracking-wide text-sky-600">Delivery Address</p>
            <p className="mt-1 text-sm font-medium text-slate-700">
              {normalizedDeliveryAddress || 'No delivery address is available.'}
            </p>
          </div>

          <label className={`flex items-start gap-3 rounded-xl border px-4 py-3 ${normalizedDeliveryAddress ? 'cursor-pointer border-slate-200' : 'cursor-not-allowed border-slate-100 opacity-60'}`}>
            <input
              type="checkbox"
              className="form-checkbox mt-0.5"
              checked={sameAsDelivery}
              disabled={!normalizedDeliveryAddress || saving}
              onChange={(event) => {
                setSameAsDelivery(event.target.checked)
                setError(null)
              }}
            />
            <span>
              <span className="block text-sm font-semibold text-slate-700">Jobsite is the same as the delivery address</span>
              <span className="mt-0.5 block text-xs text-slate-500">The delivery address shown above will be saved as the financing jobsite.</span>
            </span>
          </label>

          {!sameAsDelivery && (
            <div>
              <label htmlFor="financing-jobsite-address" className="text-sm font-semibold text-slate-700">Different Jobsite Address</label>
              <input
                ref={addressInputRef}
                id="financing-jobsite-address"
                type="text"
                className="form-input mt-2 w-full"
                value={address}
                disabled={saving || checkingAddress || !isLoaded}
                placeholder={isLoaded ? 'Start typing and select an address from Google' : 'Loading Google address search…'}
                onBlur={() => { void confirmTypedAddress() }}
                onChange={(event) => {
                  setAddress(event.target.value)
                  setAddressConfirmed(false)
                  setError(null)
                }}
              />
              {predictions.length > 0 && (
                <div className="mt-1 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg">
                  {predictions.map((prediction) => (
                    <button
                      key={prediction.place_id}
                      type="button"
                      className="block w-full border-b border-slate-100 px-3 py-2.5 text-left last:border-b-0 hover:bg-sky-50"
                      onMouseDown={(event) => { event.preventDefault() }}
                      onClick={() => { void selectPrediction(prediction) }}
                    >
                      <span className="block text-sm font-medium text-slate-700">
                        {prediction.structured_formatting.main_text}
                      </span>
                      <span className="block text-xs text-slate-500">
                        {prediction.structured_formatting.secondary_text}
                      </span>
                    </button>
                  ))}
                </div>
              )}
              <p className="mt-1 text-xs text-slate-500">
                Choose an address from the Google suggestions shown while you type.
              </p>
              {loadError && <p className="mt-2 text-sm text-rose-600">Google address search could not be loaded.</p>}
            </div>
          )}

          {error && <p className="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-600">{error}</p>}
        </div>

        <div className="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4">
          <button
            type="button"
            className="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 disabled:opacity-60"
            onClick={() => { if (!saving) onClose() }}
            disabled={saving}
          >
            Cancel
          </button>
          <button
            type="button"
            className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
            onClick={() => { void submit() }}
            disabled={saving || checkingAddress || (!sameAsDelivery && !isLoaded)}
          >
            {saving ? 'Saving…' : (checkingAddress ? 'Checking address…' : 'Save and Move to Account Receipt')}
          </button>
        </div>
      </div>
    </Modal>
  )
}
