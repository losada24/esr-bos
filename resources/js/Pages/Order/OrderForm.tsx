import { useState, useEffect, useRef, useCallback, type FocusEvent } from 'react'
import { useJsApiLoader } from '@react-google-maps/api'
import type { Library } from '@googlemaps/js-api-loader'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import {
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
  type ProductCost,
  type OrderProduct,
  type OptionType,
  type Attachment
} from '@/types'
import Select, { type SingleValue } from 'react-select'
import { getOrderProducts, getValueIdNotNull, type OrderFormValues } from './OrderCommon'
import ProductModal from './ProductModal'
import ProductTable from './ProductTable'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import ExportIcon from '@/Components/Icons/ExportIcon'
import { PAYMENT_METHODS, SERVICES, STOREFRONT_CATEGORY } from '@/Utils/constants'
import { capitalizeWords } from '@/Utils/string'
import { getProductExtraWorkPrice, getProductPrice, getProductPriceWithExtraWorks } from '@/Utils/price'
import { type OrderColor } from '@/types/interfaces/order'
import OrderNotesForOrder from '@/Components/OrderNotesForOrder'

const ATTACHMENT_ROLE_OPTIONS = [
  { key: 'supervisor', label: 'Supervisor' },
  { key: 'service_manager', label: 'Service Mgr' },
  { key: 'installer', label: 'Installer' },
  { key: 'account_manager', label: 'Account Mgr' }
] as const

type AttachmentRoleKey = typeof ATTACHMENT_ROLE_OPTIONS[number]['key']
type AttachmentRoleTargets = Record<AttachmentRoleKey, number[]>

const buildEmptyAttachmentRoleTargets = (): AttachmentRoleTargets => ({
  supervisor: [],
  service_manager: [],
  installer: [],
  account_manager: []
})

const normalizeAttachmentRoleTargets = (
  incomingTargets: Record<string, unknown> | undefined,
  validAttachmentIds: number[]
): AttachmentRoleTargets => {
  const validAttachmentIdSet = new Set(validAttachmentIds)
  const initialTargets = buildEmptyAttachmentRoleTargets()

  ATTACHMENT_ROLE_OPTIONS.forEach((roleOption) => {
    const rawRoleTargets = incomingTargets?.[roleOption.key]
    if (!Array.isArray(rawRoleTargets)) {
      return
    }

    initialTargets[roleOption.key] = Array.from(
      new Set(
        rawRoleTargets
          .map((value) => Number(value))
          .filter((value) => Number.isInteger(value) && validAttachmentIdSet.has(value))
      )
    )
  })

  return initialTargets
}

const removeAttachmentFromRoleTargets = (
  currentTargets: AttachmentRoleTargets,
  attachmentId: number
): AttachmentRoleTargets => {
  const nextTargets = { ...currentTargets }
  ATTACHMENT_ROLE_OPTIONS.forEach((roleOption) => {
    nextTargets[roleOption.key] = nextTargets[roleOption.key].filter((id) => id !== attachmentId)
  })
  return nextTargets
}

type ClientSearchResult = {
  id: number
  name: string
  phone: string
  email?: string | null
  vip_clients?: boolean | number
  vip_notes?: string | null
  company_contact?: { id: number, name: string } | null
}

type PaymentScheduleTemplateItem = { label: string, percentage: number }
type PaymentScheduleTemplates = Record<string, PaymentScheduleTemplateItem[]>
type CustomScheduleItem = { label: string, amount: string }

const CUSTOM_SCHEDULE_TYPE = 'CUSTOMIZED'
const REPLANNED_REASON_OPTIONS = ['CLIENT', 'PERMIT', 'MATERIALS'] as const

const getStatusValue = (statusValue: unknown): string => {
  if (typeof statusValue === 'string') return statusValue
  if (statusValue && typeof statusValue === 'object' && 'value' in statusValue) {
    const value = (statusValue as { value?: unknown }).value
    return typeof value === 'string' ? value : ''
  }
  return ''
}

const buildCustomSchedule = (items?: Array<{ label?: string | null, amount?: number | string | null }>) => {
  const normalized = Array.isArray(items)
    ? items.map((item) => ({
      label: item?.label ?? '',
      amount: item?.amount != null ? String(item.amount) : ''
    }))
    : []

  while (normalized.length < 6) {
    normalized.push({ label: '', amount: '' })
  }

  return normalized.slice(0, 6)
}

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
const GOOGLE_MAPS_LIBRARIES: Library[] = ['places']

const parseAddressComponents = (components: google.maps.GeocoderAddressComponent[] = []) => {
  const find = (type: string) =>
    components.find((component) => component.types.includes(type)) ?? null

  const streetNumber = find('street_number')?.long_name ?? ''
  const route = find('route')?.long_name ?? ''
  const subpremise = find('subpremise')?.long_name ?? ''
  const city =
    find('locality')?.long_name ??
    find('postal_town')?.long_name ??
    find('administrative_area_level_2')?.long_name ??
    ''
  const stateComponent = find('administrative_area_level_1')
  const stateLong = stateComponent?.long_name ?? ''
  const stateShort = stateComponent?.short_name ?? ''
  const postalCode = find('postal_code')?.long_name ?? ''

  return {
    streetNumber,
    route,
    subpremise,
    city,
    stateLong,
    stateShort,
    postalCode
  }
}

const buildJobAddress = (streetNumber: string, route: string, subpremise: string) => {
  const base = [streetNumber, route].filter(Boolean).join(' ').trim()
  if (!base) return ''
  if (!subpremise) return base
  return `${base} ${subpremise}`.trim()
}

const OrderForm = ({
  submitCount,
  errors,
  isCreate,
  // clients,
  setFieldValue,
  values,
  owners,
  type_of_works,
  types_of_housing,
  installation_teams,
  supervisors,
  methods_of_payment,
  services,
  order_types,
  payment_schedule_types,
  payment_schedule_templates,
  travel_costs,
  duration_of_works,
  products_config,
  type_of_products,
  product_category,
  product_costs,
  frame_colors,
  attachments,
  status,
  type_of_financing,
  statusPaymentInstaller,
  extraWorks,
  order_colors,
  showWorkTeamNotes = true
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
  order_types: string[]
  payment_schedule_types: string[]
  payment_schedule_templates?: PaymentScheduleTemplates
  travel_costs: TravelCost[]
  duration_of_works: DurationOfWork[]
  products_config: ProductConfig[]
  type_of_products: TypeOfProduct[]
  product_category: ProductCategory[]
  product_costs: ProductCost[]
  frame_colors: string[]
  order_colors: OrderColor[]
  attachments?: Attachment[]
  status: string[]
  statusPaymentInstaller: string
  type_of_financing: string[]
  extraWorks: Array<{ id: number, name: string }>
  showWorkTeamNotes?: boolean
}) => {
  const jobAddressInputRef = useRef<HTMLInputElement | null>(null)
  const autocompleteInstanceRef = useRef<google.maps.places.Autocomplete | null>(null)
  const autocompleteListenerRef = useRef<google.maps.MapsEventListener | null>(null)
  const geocoderRef = useRef<google.maps.Geocoder | null>(null)
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: GOOGLE_MAPS_LIBRARIES
  })

  useEffect(() => {
    if (!isLoaded) return
    geocoderRef.current = new google.maps.Geocoder()
  }, [isLoaded])

  const syncAddressFromString = useCallback((address: string) => {
    const trimmedAddress = address.trim()
    setFieldValue('job_address', trimmedAddress)
    if (!trimmedAddress || !geocoderRef.current) return

    geocoderRef.current.geocode({ address: trimmedAddress }, (results, status) => {
      if (status !== 'OK' || !results || results.length === 0) return
      const components = results[0].address_components ?? []
      const {
        streetNumber,
        route,
        subpremise,
        city,
        stateLong,
        stateShort,
        postalCode
      } = parseAddressComponents(components)

      const jobAddress = buildJobAddress(streetNumber, route, subpremise)
      if (jobAddress) {
        setFieldValue('job_address', jobAddress)
      }

      if (city) {
        setFieldValue('city', city)
      }
      if (stateShort || stateLong) setFieldValue('job_state', stateShort || stateLong)
      if (postalCode) setFieldValue('job_zip', postalCode)
    })
  }, [setFieldValue])

  const handlePlaceResult = useCallback((placeResult?: google.maps.places.PlaceResult | null) => {
    const inputValue = jobAddressInputRef.current?.value ?? ''
    const formattedAddress =
      placeResult?.formatted_address ??
      (placeResult as { formattedAddress?: string } | undefined)?.formattedAddress ??
      inputValue
    const components =
      placeResult?.address_components ??
      (placeResult as { addressComponents?: google.maps.GeocoderAddressComponent[] } | undefined)?.addressComponents ??
      []

    const {
      streetNumber,
      route,
      subpremise,
      city,
      stateLong,
      stateShort,
      postalCode
    } = parseAddressComponents(components)

    const jobAddress = buildJobAddress(streetNumber, route, subpremise) || formattedAddress
    if (jobAddress) {
      setFieldValue('job_address', jobAddress)
    } else if (formattedAddress) {
      setFieldValue('job_address', formattedAddress)
    }

    if (city) {
      setFieldValue('city', city)
    }
    if (stateShort || stateLong) setFieldValue('job_state', stateShort || stateLong)
    if (postalCode) setFieldValue('job_zip', postalCode)

    if ((!city || !(stateShort || stateLong) || !postalCode) && (jobAddress || formattedAddress)) {
      syncAddressFromString(jobAddress || formattedAddress)
    }
  }, [setFieldValue, syncAddressFromString])

  useEffect(() => {
    if (!isLoaded || typeof google === 'undefined' || !google.maps?.places || !jobAddressInputRef.current) return

    const options: google.maps.places.AutocompleteOptions = {
      fields: ['address_components', 'formatted_address'],
      types: ['address']
    }

    const autocomplete = new google.maps.places.Autocomplete(jobAddressInputRef.current, options)
    autocompleteInstanceRef.current = autocomplete

    const listener = autocomplete.addListener('place_changed', () => {
      handlePlaceResult(autocomplete.getPlace())
    })
    autocompleteListenerRef.current = listener

    return () => {
      if (autocompleteListenerRef.current) {
        google.maps.event.removeListener(autocompleteListenerRef.current)
        autocompleteListenerRef.current = null
      }
      autocompleteInstanceRef.current = null
    }
  }, [isLoaded, handlePlaceResult])

  const [orderProducts, setOrderProducts] = useState<OrderProduct[]>(
    values.order_products?.map((orderProduct) => {
      return getOrderProducts(orderProduct)
    }) ?? []
  )
  const [isCreated] = useState<boolean>(true)
  const [showProductModal, setShowProductModal] = useState<boolean>(false)
  const [attachmentsArray, setAttachmentsList] = useState<Attachment[]>(attachments ?? [])
  const [attachmentRoleTargets, setAttachmentRoleTargets] = useState<AttachmentRoleTargets>(() => {
    const validAttachmentIds = (attachments ?? [])
      .map((attachment) => Number(attachment.id))
      .filter((attachmentId) => Number.isInteger(attachmentId) && attachmentId > 0)

    return normalizeAttachmentRoleTargets(values.attachment_role_targets as Record<string, unknown> | undefined, validAttachmentIds)
  })
  const [clientSearchTerm, setClientSearchTerm] = useState<string>('')
  const [clientSearchResults, setClientSearchResults] = useState<ClientSearchResult[]>([])
  const [clientSearchLoading, setClientSearchLoading] = useState<boolean>(false)
  const [clientSearchError, setClientSearchError] = useState<string>('')
  const [selectedClientId, setSelectedClientId] = useState<number | null>(
    isCreate && Number(values.client_id) > 0 ? Number(values.client_id) : null
  )
  const [isClientReassigning, setIsClientReassigning] = useState<boolean>(false)
  const clientSnapshotRef = useRef({
    client_id: Number(values.client_id ?? 0),
    client_name: values.client_name ?? '',
    phone: values.phone ?? '',
    email: values.email ?? '',
    vip_clients: Boolean(values.vip_clients),
    vip_notes: values.vip_notes ?? '',
    client_company_name: values.client_company_name ?? ''
  })
  const isClientLocked = selectedClientId !== null
  const canSearchClient = isCreate ? !isClientLocked : isClientReassigning
  const canEditClientLookupFields = canSearchClient
  const suppressNextSearchRef = useRef<boolean>(false)
  const suppressNextPhoneSearchRef = useRef<boolean>(false)
  const [phoneSearchTerm, setPhoneSearchTerm] = useState<string>(values.phone ?? '')
  const [phoneSearchResults, setPhoneSearchResults] = useState<ClientSearchResult[]>([])
  const [phoneSearchLoading, setPhoneSearchLoading] = useState<boolean>(false)
  const [phoneSearchError, setPhoneSearchError] = useState<string>('')
  const [phoneExists, setPhoneExists] = useState<boolean>(false)
  const allowsAttachmentRoleSelection = values.service !== SERVICES.PICKUP && values.service !== SERVICES.DELIVERY_ONLY

  const syncAttachmentRoleTargets = useCallback((nextTargets: AttachmentRoleTargets) => {
    setAttachmentRoleTargets(nextTargets)
    setFieldValue('attachment_role_targets', nextTargets)
  }, [setFieldValue])

  useEffect(() => {
    const validAttachmentIds = attachmentsArray
      .map((attachment) => Number(attachment.id))
      .filter((attachmentId) => Number.isInteger(attachmentId) && attachmentId > 0)

    const normalizedTargets = normalizeAttachmentRoleTargets(
      values.attachment_role_targets as Record<string, unknown> | undefined,
      validAttachmentIds
    )
    setAttachmentRoleTargets(normalizedTargets)
    setFieldValue('attachment_role_targets', normalizedTargets)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [values.id])

  const toggleAttachmentRoleTarget = (role: AttachmentRoleKey, attachmentId: number, isChecked: boolean) => {
    const currentRoleTargets = attachmentRoleTargets[role] ?? []
    let nextTargets: AttachmentRoleTargets
    if (isChecked) {
      if (currentRoleTargets.includes(attachmentId)) {
        return
      }
      nextTargets = {
        ...attachmentRoleTargets,
        [role]: [...currentRoleTargets, attachmentId]
      }
    } else {
      nextTargets = {
        ...attachmentRoleTargets,
        [role]: currentRoleTargets.filter((id) => id !== attachmentId)
      }
    }

    syncAttachmentRoleTargets(nextTargets)
  }

  const removeAttachmentProduct = (index: number) => {
    if (confirm('Are you sure you want to delete this attachment?')) {
      router.delete(route('order.drop_attachment', { id: attachmentsArray[index].id }), {
        onSuccess: () => {
          const attachmentsList = attachmentsArray.filter((_, i) => i !== index)
          setAttachmentsList(attachmentsList)
          const pendingFiles = Array.isArray(values.attachments)
            ? values.attachments.filter((item: any) => item instanceof File)
            : []
          setFieldValue('attachments', pendingFiles)
          const removedAttachmentId = Number(attachmentsArray[index]?.id ?? 0)
          if (Number.isInteger(removedAttachmentId) && removedAttachmentId > 0) {
            const nextTargets = removeAttachmentFromRoleTargets(attachmentRoleTargets, removedAttachmentId)
            syncAttachmentRoleTargets(nextTargets)
          }
        }
      })
    }
  }

  const updateOrderProduct = (index: number) => {
    console.log('updateOrderProduct', index)
  }
  // console.log(values)

  const runClientSearch = async (term: string) => {
    const normalized = term.trim()
    if (normalized.length < 2) {
      setClientSearchError('Enter at least 2 characters.')
      setClientSearchResults([])
      return
    }

    setClientSearchError('')
    setClientSearchLoading(true)

    try {
      const response = await fetch(`${route('client.search')}?q=${encodeURIComponent(normalized)}`)
      if (!response.ok) {
        throw new Error('Search failed')
      }
      const data = await response.json()
      setClientSearchResults(Array.isArray(data.data) ? data.data : [])
    } catch (error) {
      setClientSearchError('Search failed. Try again.')
      setClientSearchResults([])
    } finally {
      setClientSearchLoading(false)
    }
  }

  const runPhoneSearch = async (term: string) => {
    const digits = term.replace(/\D/g, '')
    if (digits.length < 7) {
      setPhoneSearchResults([])
      setPhoneSearchError('')
      return
    }

    setPhoneSearchError('')
    setPhoneSearchLoading(true)

    try {
      const response = await fetch(`${route('client.search')}?q=${encodeURIComponent(digits)}`)
      if (!response.ok) {
        throw new Error('Search failed')
      }
      const data = await response.json()
      setPhoneSearchResults(Array.isArray(data.data) ? data.data : [])
    } catch (error) {
      setPhoneSearchError('Search failed. Try again.')
      setPhoneSearchResults([])
    } finally {
      setPhoneSearchLoading(false)
    }
  }

  const checkPhoneExists = async (term: string) => {
    const digits = term.replace(/\D/g, '')
    if (digits.length !== 10) {
      setPhoneExists(false)
      return
    }

    try {
      const response = await fetch(`${route('client.phone_exists')}?phone=${encodeURIComponent(digits)}`)
      if (!response.ok) {
        return
      }
      const data = await response.json()
      setPhoneExists(Boolean(data.exists))
    } catch (error) {
      // ignore
    }
  }

  useEffect(() => {
    if (!canSearchClient) {
      setClientSearchResults([])
      setClientSearchError('')
      return
    }
    if (suppressNextSearchRef.current) {
      suppressNextSearchRef.current = false
      return
    }
    const term = clientSearchTerm.trim()
    if (term.length < 2) {
      setClientSearchResults([])
      setClientSearchError('')
      return
    }

    const timeoutId = window.setTimeout(() => {
      runClientSearch(term)
    }, 300)

    return () => {
      window.clearTimeout(timeoutId)
    }
  }, [clientSearchTerm, canSearchClient])

  useEffect(() => {
    if (!canSearchClient) {
      setPhoneSearchResults([])
      setPhoneSearchError('')
      setPhoneExists(false)
      return
    }
    if (suppressNextPhoneSearchRef.current) {
      suppressNextPhoneSearchRef.current = false
      return
    }
    const term = phoneSearchTerm.trim()
    if (term.length === 0) {
      setPhoneSearchResults([])
      setPhoneSearchError('')
      setPhoneExists(false)
      return
    }

    const timeoutId = window.setTimeout(() => {
      runPhoneSearch(term)
      checkPhoneExists(term)
    }, 300)

    return () => {
      window.clearTimeout(timeoutId)
    }
  }, [phoneSearchTerm, canSearchClient])

  const handleSelectClient = (client: ClientSearchResult) => {
    suppressNextSearchRef.current = true
    suppressNextPhoneSearchRef.current = true
    setSelectedClientId(client.id)
    setFieldValue('client_id', client.id)
    setFieldValue('client_name', client.name ?? '')
    setFieldValue('phone', client.phone ?? '')
    setFieldValue('email', client.email ?? '')
    setFieldValue('vip_clients', !!client.vip_clients)
    setFieldValue('vip_notes', client.vip_notes ?? '')
    setFieldValue('client_company_name', client.company_contact?.name ?? '')
    setClientSearchResults([])
    setClientSearchTerm(client.name ?? '')
    setPhoneSearchResults([])
    setPhoneSearchTerm(client.phone ?? '')
    setPhoneExists(false)
    if (!isCreate) {
      setIsClientReassigning(false)
    }
  }

  const clearSelectedClient = () => {
    setSelectedClientId(null)
    setFieldValue('client_id', 0)
    setFieldValue('client_name', '')
    setFieldValue('phone', '')
    setFieldValue('email', '')
    setFieldValue('vip_clients', false)
    setFieldValue('vip_notes', '')
    setFieldValue('client_company_name', '')
    setClientSearchResults([])
    setClientSearchTerm('')
    setClientSearchError('')
    setPhoneSearchResults([])
    setPhoneSearchTerm('')
    setPhoneSearchError('')
    setPhoneExists(false)
  }

  const startClientReassignment = () => {
    clientSnapshotRef.current = {
      client_id: Number(values.client_id ?? 0),
      client_name: values.client_name ?? '',
      phone: values.phone ?? '',
      email: values.email ?? '',
      vip_clients: Boolean(values.vip_clients),
      vip_notes: values.vip_notes ?? '',
      client_company_name: values.client_company_name ?? ''
    }
    clearSelectedClient()
    setIsClientReassigning(true)
  }

  const cancelClientReassignment = () => {
    const snapshot = clientSnapshotRef.current
    setFieldValue('client_id', snapshot.client_id)
    setFieldValue('client_name', snapshot.client_name)
    setFieldValue('phone', snapshot.phone)
    setFieldValue('email', snapshot.email)
    setFieldValue('vip_clients', snapshot.vip_clients)
    setFieldValue('vip_notes', snapshot.vip_notes)
    setFieldValue('client_company_name', snapshot.client_company_name)
    setSelectedClientId(snapshot.client_id > 0 ? snapshot.client_id : null)
    setClientSearchResults([])
    setClientSearchTerm('')
    setClientSearchError('')
    setPhoneSearchResults([])
    setPhoneSearchTerm(snapshot.phone ?? '')
    setPhoneSearchError('')
    setPhoneExists(false)
    setIsClientReassigning(false)
  }

  const addOrderProduct = (orderProduct: OrderProduct) => {
    const orderProductsList = [...orderProducts, orderProduct]
    setOrderProducts(orderProductsList)
    setFieldValue('orderProducts', orderProductsList)
  }

  const selectDeliveryAndInstallationDate = async (payment_factory_date: string, cityPermits: boolean) => {
    console.log(values.travel_cost_id)
    let travel_cost_id = 0
    if (
      values.travel_cost_id &&
                typeof values.travel_cost_id === 'object' &&
                'value' in values.travel_cost_id
    ) {
      travel_cost_id = (values.travel_cost_id as any).value
    } else if (typeof values.travel_cost_id === 'number') {
      travel_cost_id = values.travel_cost_id
    }
    // const travel_cost_id = 'value' in ((values.travel_cost_id) as any) ? (values.travel_cost_id as any).value : 0
    const response = await fetch(
      `/order/get_delivery_and_installation_date/${payment_factory_date}/${values.type_of_housing_id}/${travel_cost_id}/${values.service}/${cityPermits}`)
    const data = await response.json()

    setFieldValue('eta_date', data.estimate_eta_date)
    setFieldValue('delivery_date', data.estimate_delivery_date)
    setFieldValue('installation_date', data.estimate_installation_date)
    const duration_of_work_value = getValueIdNotNull(values.duration_of_work_id)
    if (duration_of_work_value !== '') {
      const duration_of_work = duration_of_works.find((duration_of_work) => duration_of_work.id === duration_of_work_value)
      if (duration_of_work) {
        const installation_end_date1 = new Date(data.estimate_installation_date)
        // console.log(installation_end_date1.getDate())
        installation_end_date1.setDate(installation_end_date1.getDate() + duration_of_work.number_of_day)
        const year = installation_end_date1.getFullYear()
        const month = String(installation_end_date1.getMonth() + 1).padStart(2, '0') // Agregar cero inicial si es necesario
        const day = String(installation_end_date1.getDate()).padStart(2, '0')// Agregar cero inicial si es necesario
        // Formato yyyy-mm-dd
        const fechaFormateada = `${year}-${month}-${day}`
        setFieldValue('installation_end_date', fechaFormateada)
      }
    }
  }

  const selectDeliveryAndPickupDate = async (payment_factory_date: string) => {
    const response = await fetch(
      `/order/get_delivery_and_pickup_date/${payment_factory_date}`)
    const data = await response.json()

    setFieldValue('eta_date', data.estimate_eta_date)
    setFieldValue('delivery_date', data.estimate_delivery_date)
  }

  const removeOrderProduct = (index: number) => {
    const orderProductList = orderProducts.filter((_, i) => i !== index)
    setOrderProducts(orderProductList)
    setFieldValue('orderProducts', orderProductList)
  }

  const selectedSupervisor: SingleValue<OptionType> = {
    value: values.supervisor_id ?? 0,
    label: supervisors.find((supervisor) => supervisor.id === values.supervisor_id)?.name ?? ''
  }

  const selectedStatus: SingleValue<OptionType> = {
    value: values.status ?? '',
    label: status.find((status) => status === values.status) ?? ''
  }
  const selectedStatusValue = getStatusValue(values.status)
  const selectedReplannedReasons = Array.isArray(values.replanned_reasons) ? values.replanned_reasons : []

  const toggleReplannedReason = (reason: string, checked: boolean) => {
    const nextReasons = checked
      ? Array.from(new Set([...selectedReplannedReasons, reason]))
      : selectedReplannedReasons.filter((item) => item !== reason)

    setFieldValue('replanned_reasons', nextReasons)
  }

  const selectedTravelCost: SingleValue<OptionType> = {
    value: values.travel_cost_id ?? 0,
    label: travel_costs.find((travel_cost) => travel_cost.id === values.travel_cost_id)?.name ?? ''
  }

  const selectedDurationOfWork: SingleValue<OptionType> = {
    value: values.duration_of_work_id ?? 0,
    label: duration_of_works.find((duration_of_work) => duration_of_work.id === values.duration_of_work_id)?.name ?? ''
  }

  const formatCurrency = (value?: number | string | null) => {
    const numeric = typeof value === 'number' ? value : Number(value)
    if (!Number.isFinite(numeric)) return '--'
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(numeric)
  }

  const scheduleTemplates = payment_schedule_templates ?? {}
  const selectedScheduleItems = values.payment_schedule_type
    ? (scheduleTemplates[values.payment_schedule_type] ?? [])
    : []
  const customSchedule: CustomScheduleItem[] = buildCustomSchedule(values.custom_schedule)
  const projectAmountValue = Number(String(values.project_amount ?? '').replace(/,/g, ''))
  const hasProjectAmount = Number.isFinite(projectAmountValue) && projectAmountValue > 0
  const buildSchedulePreview = (items: PaymentScheduleTemplateItem[]) => {
    if (!items.length) return []
    if (!hasProjectAmount) {
      return items.map((item) => ({ ...item, amount: null }))
    }
    let runningTotal = 0
    return items.map((item, index) => {
      const amount = index === items.length - 1
        ? Math.round((projectAmountValue - runningTotal) * 100) / 100
        : Math.round((projectAmountValue * (item.percentage / 100)) * 100) / 100
      runningTotal += amount
      return { ...item, amount }
    })
  }
  const isCustomSchedule = values.payment_schedule_type === CUSTOM_SCHEDULE_TYPE
  const customScheduleItems = customSchedule
    .map((item) => ({
      label: String(item.label ?? '').trim(),
      amount: Number(String(item.amount ?? '').replace(/,/g, ''))
    }))
    .filter((item) => item.label !== '' && Number.isFinite(item.amount))
  const customScheduleTotal = customSchedule.reduce((total, item) => {
    const value = Number(String(item.amount ?? '').replace(/,/g, ''))
    return Number.isFinite(value) ? total + value : total
  }, 0)
  const customTotalMatches = hasProjectAmount && Math.abs(customScheduleTotal - projectAmountValue) <= 0.01
  const customTotalClass = hasProjectAmount
    ? customTotalMatches
      ? 'text-emerald-600'
      : 'text-rose-600'
    : 'text-slate-400'
  const schedulePreviewItems = isCustomSchedule
    ? customScheduleItems.map((item) => ({
      label: item.label,
      percentage: hasProjectAmount ? Math.round(((item.amount / projectAmountValue) * 100) * 100) / 100 : Number.NaN,
      amount: item.amount
    }))
    : buildSchedulePreview(selectedScheduleItems)
  const hasRecordedSchedulePayments = Boolean(values.payment_schedule && (
    Number(values.payment_schedule.paid_amount ?? 0) > 0
    || (values.payment_schedule.installments ?? []).some((installment) => (
      Number(installment.paid_amount ?? 0) > 0
      || (installment.movements?.length ?? 0) > 0
      || String(installment.status ?? '').toUpperCase() !== 'PENDING'
    ))
  ))
  const canEditScheduleInForm = values.method_of_payment === PAYMENT_METHODS.CASH && (isCreate || !hasRecordedSchedulePayments)
  const shouldShowSchedulePreview = canEditScheduleInForm
    && values.payment_schedule_type
  const isProjectAmountLocked = !isCreate && Boolean(values.has_contract_signed)

  //console.log('selectedStatus', selectedStatus)
  return (
    <>
      <Form className='space-y-5'>
        {Number(values.is_supply) === 1 && (
          <div className="px-3 py-2 rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-800 font-semibold tracking-wide">
            SUPPLY
          </div>
        )}
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Client Information</legend>
          <div className='grid gap-4 grid-cols-3'>
            <div className={`${submitCount ? (errors.last_name) ? 'has-error' : 'has-success' : ''} relative`}>
              <div className='flex items-center justify-between'>
                <label htmlFor="client_name">Name</label>
                {!isCreate && (
                  isClientReassigning
                    ? (
                      <button
                        type="button"
                        className="text-xs font-semibold text-[#dc2626] hover:text-[#b91c1c]"
                        onClick={cancelClientReassignment}
                      >
                        Cancel
                      </button>
                      )
                    : (
                      <button
                        type="button"
                        className="text-xs font-semibold text-[#2563eb] hover:text-[#1d4ed8]"
                        onClick={startClientReassignment}
                      >
                        Change client
                      </button>
                      )
                )}
              </div>
              <div className='flex'>
                <Field
                  id="client_name"
                  name="client_name"
                  className="form-input"
                  autoComplete="client_name"
                  placeholder='Name'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    const formattedValue = capitalizeWords(e.target.value)
                    setFieldValue('client_name', formattedValue)
                    if (canSearchClient) {
                      setClientSearchTerm(formattedValue)
                    }
                  }}
                  readOnly={!canEditClientLookupFields}
                />
                {isCreate && isClientLocked && (
                  <button
                    type="button"
                    className="ml-2 px-2 text-[#5c6370] hover:text-[#111]"
                    onClick={clearSelectedClient}
                    aria-label="Clear client"
                  >
                    ×
                  </button>
                )}
              </div>
              {canSearchClient && (
                <div className="text-xs text-[#5c6370] mt-1">Type to search by name or phone.</div>
              )}
              {!isCreate && isClientReassigning && (
                <div className="text-xs text-[#5c6370] mt-1">If no match appears, keep the data and save to create a new client.</div>
              )}
              {!isCreate && !isClientReassigning && (
                <div className="text-xs text-[#5c6370] mt-1">Client data is locked here. Manage it from Clients.</div>
              )}
              {(submitCount && errors.client_name) ? <InputError message={errors.client_name} className="mt-2" /> : ''}
              {canSearchClient && clientSearchError && (
                <div className="text-danger text-sm mt-1">{clientSearchError}</div>
              )}
              {canSearchClient && clientSearchLoading && (
                <div className="text-sm mt-1">Searching...</div>
              )}
              {canSearchClient && clientSearchResults.length > 0 && (
                <div className="absolute z-50 mt-2 w-full border border-[#e0e6ed] rounded-md divide-y bg-white shadow-lg dark:border-[#17263c] dark:bg-[#1b2e4b]">
                  {clientSearchResults.map((client) => (
                    <button
                      key={client.id}
                      type="button"
                      className="w-full text-left px-3 py-2 hover:bg-[#f5f7fb]"
                      onClick={() => { handleSelectClient(client) }}
                    >
                      <div className="font-semibold">{client.name}</div>
                      <div className="text-xs text-[#5c6370]">
                        {client.phone}
                        {client.email ? ` • ${client.email}` : ''}
                        {client.vip_clients ? ' • VIP' : ''}
                      </div>
                    </button>
                  ))}
                </div>
              )}
              {isCreate && isClientLocked && (
                <div className="text-xs text-[#5c6370] mt-1">Client selected. Fields locked.</div>
              )}
            </div>
            <div className={`${submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''} relative`}>
              <label htmlFor="phone">Phone</label>
              <Field
                id="phone"
                name="phone"
                className="form-input"
                autoComplete="phone"
                placeholder='Phone'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('phone', e.target.value)
                  if (canSearchClient) {
                    setPhoneSearchTerm(e.target.value)
                  }
                }}
                readOnly={!canEditClientLookupFields}
              />
              {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
              {canSearchClient && phoneExists && (
                <div className="text-danger text-sm mt-1">Phone already exists. Select a client from the list.</div>
              )}
              {canSearchClient && phoneSearchError && (
                <div className="text-danger text-sm mt-1">{phoneSearchError}</div>
              )}
              {canSearchClient && phoneSearchLoading && (
                <div className="text-sm mt-1">Searching...</div>
              )}
              {canSearchClient && phoneSearchResults.length > 0 && (
                <div className="absolute z-50 mt-2 w-full border border-[#e0e6ed] rounded-md divide-y bg-white shadow-lg dark:border-[#17263c] dark:bg-[#1b2e4b]">
                  {phoneSearchResults.map((client) => (
                    <button
                      key={client.id}
                      type="button"
                      className="w-full text-left px-3 py-2 hover:bg-[#f5f7fb]"
                      onClick={() => { handleSelectClient(client) }}
                    >
                      <div className="font-semibold">{client.name}</div>
                      <div className="text-xs text-[#5c6370]">
                        {client.phone}
                        {client.email ? ` • ${client.email}` : ''}
                        {client.vip_clients ? ' • VIP' : ''}
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </div>
            <div className={submitCount ? (errors.email) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="email">Email</label>
              <Field
                id="email"
                name="email"
                className="form-input"
                autoComplete="email"
                placeholder='Email'
                readOnly={!canEditClientLookupFields}
              />
              {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
            </div>
            {values.order_type === 'COMMERCIAL' && (
              <div className='col-span-3'>
                <label htmlFor="client_company_name">Company</label>
                <div
                  id="client_company_name"
                  className="form-input bg-[#f5f7fb] text-[#5c6370]"
                >
                  {values.client_company_name?.trim() || '—'}
                </div>
              </div>
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
                    disabled={!canEditClientLookupFields}
                  />
                  <label htmlFor="vip_clients" className='font-bold inline-flex'>VIP</label>
                </div>
                <div className=' flex mt-8'>
                  <Field
                    id="do_not_send_email"
                    name="do_not_send_email"
                    className="form-checkbox"
                    type='checkbox'
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                      setFieldValue('do_not_send_email', e.target.checked)
                    } }
                  />
                  <label htmlFor="do_not_send_email" className='font-bold inline-flex' >Do Not Send Email</label>
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
                readOnly={!canEditClientLookupFields}
              />
            </div>
            )}
          </div>
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Order Information</legend>
              <div className='flex items-center mb-4'>
                <strong className='mr-2'>STATUS PAYMENT INSTALLER:</strong>
                <span
                  className={`px-2 py-1 rounded ${
                    statusPaymentInstaller === 'PARTIALLY PAID' || statusPaymentInstaller === 'FULLY PAID'
                      ? 'shadow-md shadow-red-500 text-red-700'
                      : statusPaymentInstaller === 'OPEN'
                      ? 'shadow-md shadow-green-500 text-green-700'
                      : ''
                  }`}
                >
                  {statusPaymentInstaller}
                </span>
              </div>
          <div className='grid gap-4 grid-cols-4'>
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
                {order_types
                  .filter((order_type) => order_type !== 'SUPPLY')
                  .map((order_type, index) => (
                    <option key={index} value={order_type}>{order_type}</option>
                  ))}
              </Field>
              {(submitCount && errors.order_type) ? <InputError message={errors.order_type} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="name">Name</label>
              <Field
                id="name"
                name="name"
                className="form-input"
                autoComplete="name"
                placeholder='Name'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  const formattedValue = capitalizeWords(e.target.value)
                  setFieldValue('name', formattedValue)
                }}
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
            <div className={submitCount ? (errors.invoice_number) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="invoice_number">Invoice Number</label>
              <Field
                id="invoice_number"
                name="invoice_number"
                className="form-input"
                autoComplete="invoice_number"
                placeholder='Invoice Number'
              />
              {(submitCount && errors.invoice_number) ? <InputError message={errors.invoice_number} className="mt-2" /> : ''}
            </div>
          { /* <div className={submitCount ? (errors.job_address) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="job_address">Job Address</label>
              <Field
                id="job_address"
                name="job_address"
                className="form-input"
                autoComplete="job_address"
                placeholder='Job Address'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  const formattedValue = capitalizeWords(e.target.value)
                  setFieldValue('job_address', formattedValue)
                }}
              />
              {(submitCount && errors.job_address) ? <InputError message={errors.job_address} className="mt-2" /> : ''}
            </div> */}
            <div className={submitCount ? (errors.job_address ? 'has-error' : 'has-success') : ''}>
              <label htmlFor="job_address"> Job Address</label>
              <Field
                id="job_address"
                name="job_address"
                className="form-input placeholder:text-white-dark"
                autoComplete="off"
                placeholder="Address"
                innerRef={jobAddressInputRef}
                onBlur={(e: FocusEvent<HTMLInputElement>) => {
                  syncAddressFromString(e.target.value)
                }}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFieldValue('job_address', e.target.value)}
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
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('city', e.target.value)
                }}
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

            <div className={submitCount ? (errors.owners) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="owners">Owner</label>
              <Select
                id='owners'
                placeholder="Select Owners"
                name='owners'
                defaultValue={ values.owners.map((owner) => { return { label: owner.name, value: owner.id } }) }
                onChange={(value) => {
                  setFieldValue('owners', value)
                }}
                isMulti={true}
                options={owners.map((owner) => { return { label: owner.name, value: owner.id } })}
              />
              {(submitCount && errors.owners) ? <InputError message={errors.owners.toString()} className="mt-2" /> : ''}
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
                onChange={(e: { target: { value: string } }) => {
                  const nextService = e.target.value
                  setFieldValue('service', nextService)
                  if (nextService !== SERVICES.DELIVERY_AND_INSTALLATION) {
                    setFieldValue('city_permits', false)
                    setFieldValue('cost_city_fee', 0)
                    setFieldValue('association_permits', false)
                    setFieldValue('equipment_rental', false)
                  }
                  setFieldValue('type_of_work_id', 0)
                  setFieldValue('type_of_housing_id', 0)
                  setFieldValue('travel_cost_id', 0)
                  setFieldValue('duration_of_work_id', 0)
                  setFieldValue('installation_date', null)
                  setFieldValue('installation_end_date', null)
                }}
              >
                <option value="">Service</option>
                {services.map((service, index) => (
                  <option key={index} value={service}>{service}</option>
                ))}
              </Field>
              {(submitCount && errors.service) ? <InputError message={errors.service} className="mt-2" /> : ''}
            </div>
            { (values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
             <div className={submitCount ? (errors.owners) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="frame_color">Frame Colors</label>
              <Select
                id='frame_color'
                placeholder="Select Colors"
                name='frame_color'
                defaultValue={ values.order_colors?.map((order_color) => { return { label: order_color.name, value: order_color.id } }) }
                onChange={(value) => {
                  setFieldValue('frame_color', value)
                }}
                isMulti={true}
                options={frame_colors.map((color, index) => ({ label: color, value: index }))}
              />
              {(submitCount && errors.frame_color) ? <InputError message={errors.frame_color?.toString()} className="mt-2" /> : ''}
            </div>
            ) }
            {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
            <>
            <div className={submitCount ? (errors.type_of_work_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="type_of_work">Type of Work</label>
              <Field
                id="type_of_work_id"
                name="type_of_work_id"
                className="form-select"
                autoComplete="type_of_work_id"
                placeholder='Type of Work'
                as="select"
                onChange={(e: { target: { value: string } }) => {
                  const type_of_work_id = parseInt(e.target.value)
                  setFieldValue('type_of_work_id', type_of_work_id)

                  // TODO: RECALCULATE PRICES
                  // console.log(orderProducts)
                  if (type_of_work_id !== 0 && orderProducts.length > 0) {
                    const recalculateOrderProducts = orderProducts.map((orderProduct) => {
                      const product = {
                        ...orderProduct,
                        type_of_work_id
                      }

                      const unit_price = getProductPrice(product, product_costs)
                      const unit_price_with_extrawork = getProductPriceWithExtraWorks(product, product_costs)
                      product.extra_work_price = getProductExtraWorkPrice(product) ?? 0
                      product.unit_price = unit_price

                      if (product.type_of_product_id !== STOREFRONT_CATEGORY) {
                        product.total_price = unit_price * product.qty
                      } else {
                        product.total_price = unit_price
                      }

                      product.unit_price_with_extraworks = unit_price_with_extrawork
                      product.total_price_with_extraworks = unit_price_with_extrawork + product.total_price

                      return product
                    })

                    setOrderProducts(recalculateOrderProducts)
                    setFieldValue('orderProducts', recalculateOrderProducts)
                    // console.log(recalculateOrderProducts)
                  }
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
                onChange={(e: { target: { value: string } }) => {
                  const type_of_housing_id = parseInt(e.target.value)
                  setFieldValue('type_of_housing_id', type_of_housing_id)
                }}
              >
                <option value="">Type of Housing</option>
                {types_of_housing.map((type_of_housing, index) => (
                  <option key={index} value={type_of_housing.id}>{type_of_housing.name}</option>
                ))}
              </Field>
              {(submitCount && errors.type_of_housing_id) ? <InputError message={errors.type_of_housing_id} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.travel_cost_id) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="travel_cost_id">County</label>
              <Select
                id='travel_cost_id'
                placeholder="Travel Cost"
                name='travel_cost_id'
                defaultValue={ selectedTravelCost }
                onChange={(value) => { setFieldValue('travel_cost_id', value) }}
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
                defaultValue={ selectedDurationOfWork }
                onChange={(value) => {
                  setFieldValue('duration_of_work_id', value)
                  if (values.installation_date) {
                    const duration_of_work_value = getValueIdNotNull(value)
                    // console.log(duration_of_work_value)
                    const duration_of_work = duration_of_works.find((duration_of_work) => duration_of_work.id === duration_of_work_value)
                    const installation_end_date = new Date(values.installation_date)
                    if (duration_of_work) {
                      installation_end_date.setDate(installation_end_date.getDate() + duration_of_work?.number_of_day ?? 0)
                      const year = installation_end_date.getFullYear()
                      const month = String(installation_end_date.getMonth() + 1).padStart(2, '0') // Agregar cero inicial si es necesario
                      const day = String(installation_end_date.getDate()).padStart(2, '0')// Agregar cero inicial si es necesario
                      // Formato yyyy-mm-dd
                      const fechaFormateada = `${year}-${month}-${day}`
                      setFieldValue('installation_end_date', fechaFormateada)
                    }
                  }
                }}
                options={duration_of_works.map((duration_of_work) => { return { label: duration_of_work.name, value: duration_of_work.id } })}
              />
              {(submitCount && errors.duration_of_work_id) ? <InputError message={errors.duration_of_work_id.toString()} className="mt-2" /> : ''}
            </div>
            </>
            )}
            <div className={submitCount ? (errors.additional_travel_costs) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="additional_travel_costs">Other Cost</label>
              <Field
                id="additional_travel_costs"
                name="additional_travel_costs"
                className="form-input text-right"
                autoComplete="additional_travel_costs"
                placeholder='Other Cost'
                type='number'
              />
              {(submitCount && errors.additional_travel_costs) ? <InputError message={errors.additional_travel_costs} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.entry_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="entry_date">Entry Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="entry_date"
                value={values.entry_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('entry_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.entry_date) ? <InputError message={errors.entry_date?.toString()} className="mt-2" /> : ''}
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
                value={values.contract_signing_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('contract_signing_date', date.toISOString().slice(0, 10))
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
                disabled={(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (values.type_of_work_id === 0 || values.type_of_housing_id === 0 || values.travel_cost_id === 0 || ((values.duration_of_work_id) as any).value === 0)}
                name="payment_factory_date"
                value={values.payment_factory_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  const payment_factory_date = date.toISOString().slice(0, 10)
                  if (values.service === SERVICES.DELIVERY_AND_INSTALLATION) {
                    selectDeliveryAndInstallationDate(payment_factory_date, values.city_permits)
                  } else {
                    selectDeliveryAndPickupDate(payment_factory_date)
                  }
                  setFieldValue('payment_factory_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.payment_factory_date) ? <InputError message={errors.payment_factory_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.eta_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="eta_date">Eta Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="eta_date"
                value={values.eta_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('eta_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.eta_date) ? <InputError message={errors.eta_date?.toString()} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.delivery_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="delivery_date">Delivery/Pickup Date</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="delivery_date"
                value={values.delivery_date?.toString()}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('delivery_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.delivery_date) ? <InputError message={errors.delivery_date?.toString()} className="mt-2" /> : ''}
            </div>
            {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
              <>
                <div className={submitCount ? (errors.installation_date) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="installation_date">Installation Date</label>
                  <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}
                    name="installation_date"
                    value={values.installation_date?.toString()}
                    className="form-input"
                    onChange={([date]) => {
                      setFieldValue('installation_date', date.toISOString().slice(0, 10))
                      const duration_of_work_value = getValueIdNotNull(values.duration_of_work_id)
                      if (duration_of_work_value !== '') {
                        const duration_of_work = duration_of_works.find((duration_of_work) => duration_of_work.id === duration_of_work_value)
                        if (duration_of_work) {
                          const installation_end_date = new Date(date)
                          installation_end_date.setDate(installation_end_date.getDate() + duration_of_work.number_of_day - 1)
                          const year = installation_end_date.getFullYear()
                          const month = String(installation_end_date.getMonth() + 1).padStart(2, '0') // Agregar cero inicial si es necesario
                          const day = String(installation_end_date.getDate()).padStart(2, '0')// Agregar cero inicial si es necesario
                          // Formato yyyy-mm-dd
                          const fechaFormateada = `${year}-${month}-${day}`
                          setFieldValue('installation_end_date', fechaFormateada)
                        }
                      }
                    }}
                  />
                  {(submitCount && errors.installation_date) ? <InputError message={errors.installation_date?.toString()} className="mt-2" /> : ''}
                </div>
                <div className={submitCount ? (errors.installation_end_date) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="installation_end_date">Installation End Date</label>
                  <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}
                    name="installation_end_date"
                    value={values.installation_end_date?.toString()}
                    className="form-input"
                    onChange={([date]) => {
                      setFieldValue('installation_end_date', date.toISOString().slice(0, 10))
                    }}
                  />
                  {(submitCount && errors.installation_end_date) ? <InputError message={errors.installation_end_date?.toString()} className="mt-2" /> : ''}
                </div>
              </>
            )}
             {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
            <>
            <div className={submitCount ? (errors.city_permits) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                <div className='flex'>
                <Field
                      id="city_permits"
                      name="city_permits"
                      className="form-checkbox"
                      type="checkbox"
                      onChange={async (e: React.ChangeEvent<HTMLInputElement>) => {
                        const isChecked = e.target.checked
                        console.log('Checkbox changed to:', isChecked)
                        setFieldValue('city_permits', isChecked)
                        setFieldValue('cost_city_fee', 0)

                        // Obtener el valor de `payment_factory_date`
                        const paymentFactoryDate =
                          typeof values.payment_factory_date === 'string'
                            ? values.payment_factory_date
                            : values.payment_factory_date?.toISOString().slice(0, 10) ?? ''

                        // Pasar el valor actualizado directamente a la función
                        await selectDeliveryAndInstallationDate(paymentFactoryDate, isChecked)
                      }}
                    />
                 { /* <Field
                    id="city_permits"
                    name="city_permits"
                    className="form-checkbox"
                    type='checkbox'
                    onChange={(e: any) => {
                      setFieldValue('city_permits', e.target.checked)
                      setFieldValue('cost_city_fee', 0)
                      const payment_factory_date = typeof values.payment_factory_date === 'string' ? values.payment_factory_date : values.payment_factory_date?.toISOString().slice(0, 10) ?? ''
                      selectDeliveryAndInstallationDate(payment_factory_date)
                    }}
                  /> */ }
                  <label htmlFor="city_permits">City Permits</label>
                </div>
                {(submitCount && errors.city_permits) ? <div className='block'><InputError message={errors.city_permits} className="mt-2" /></div> : ''}
            </div>
            {(values.city_permits) && (
              <>
                <div className={submitCount ? (errors.cost_city_fee) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="cost_city_fee">City Fee Cost</label>
                  <Field
                    id="cost_city_fee"
                    name="cost_city_fee"
                    className="form-input text-right"
                    autoComplete="cost_city_fee"
                    placeholder='City Fee Cost'
                    type='number'
                  />
                  {(submitCount && errors.cost_city_fee) ? <InputError message={errors.cost_city_fee} className="mt-2" /> : ''}
                </div>
              </>
            )}
            <div className={submitCount ? (errors.association_permits) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                <div className='flex'>
                  <Field
                    id="association_permits"
                    name="association_permits"
                    className="form-checkbox"
                    type='checkbox'
                    onChange={(e: any) => {
                      setFieldValue('association_permits', e.target.checked)
                    }}
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
                    onChange={(e: any) => {
                      setFieldValue('equipment_rental', e.target.checked)
                    }}
                  />
                  <label htmlFor="equipment_rental">Equipment Rental</label>
                </div>
                {(submitCount && errors.equipment_rental) ? <div className='block'><InputError message={errors.equipment_rental} className="mt-2" /></div> : ''}
            </div>
            </>)}
              <div className={submitCount ? (errors.cost_delivery) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="cost_delivery"> Client Pending Payment</label>
                <Field
                  id="cost_delivery"
                  name="cost_delivery"
                  className="form-input text-right"
                  autoComplete="cost_delivery"
                  placeholder='Delivery Cost'
                  type='number'
                />
                {(submitCount && errors.cost_delivery) ? <InputError message={errors.cost_delivery} className="mt-2" /> : ''}
              </div>
            {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
              <>
                <div className={submitCount ? (errors.installation_teams) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="installationTeams">Installation Team</label>
                  <Select
                    id='installation_teams'
                    placeholder="Installation Team"
                    name='installation_teams'
                    defaultValue={ values.installation_teams.map((installation_team) => { return { label: installation_team.user?.name, value: installation_team.id } }) }
                    isMulti={true}
                    onChange={(value) => { setFieldValue('installation_teams', value) }}
                    options={installation_teams
                      .filter((team_member) =>
                        team_member.user?.status === 'ACTIVE' &&
                        team_member.type_housing?.some((type_of_housing) => type_of_housing.id === values.type_of_housing_id)
                      )
                      .map((installation_team) => { return { label: installation_team.user?.name, value: installation_team.id } })}
                  />
                  {(submitCount && errors.installation_teams) ? <InputError message={errors.installation_teams.toString()} className="mt-2" /> : ''}
                </div>
                <div className={submitCount ? (errors.supervisor_id) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="supervisor_id">Supervisor</label>
                  <Select
                    id='supervisor_id'
                    placeholder="Supervisor"
                    name='supervisor_id'
                    defaultValue={ selectedSupervisor }
                    onChange={(value) => { setFieldValue('supervisor_id', value) }}
                    options={supervisors.map((supervisor) => { return { label: supervisor.name, value: supervisor.id } })}
                  />
                  {(submitCount && errors.supervisor_id) ? <InputError message={errors.supervisor_id} className="mt-2" /> : ''}
                </div>
              </>
            )}
            <div className={submitCount ? (errors.installation_teams) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="status">Status</label>
              <Select
                id='status'
                placeholder="status"
                name='status'
                defaultValue={selectedStatus}
                isMulti={false}
                onChange={(value) => {
                  setFieldValue('status', value)
                  if ((value?.value ?? '') !== 'REPLANNED') {
                    setFieldValue('replanned_reasons', [])
                  }
                }}
                options={status.map((status) => { return { label: status, value: status } })}
              />
              {(submitCount && errors.status) ? <InputError message={errors.status} className="mt-2" /> : ''}
            </div>
            {selectedStatusValue === 'REPLANNED' && (
              <div className={submitCount ? (errors.replanned_reasons ? 'has-error' : 'has-success') : ''}>
                <label>Replanned Reasons</label>
                <div className='mt-2 flex flex-wrap gap-4'>
                  {REPLANNED_REASON_OPTIONS.map((reason) => (
                    <label key={reason} className='inline-flex items-center gap-2'>
                      <input
                        type='checkbox'
                        className='form-checkbox'
                        checked={selectedReplannedReasons.includes(reason)}
                        onChange={(e) => { toggleReplannedReason(reason, e.target.checked) }}
                      />
                      <span>{capitalizeWords(reason.toLowerCase())}</span>
                    </label>
                  ))}
                </div>
                {(submitCount && errors.replanned_reasons) ? <InputError message={Array.isArray(errors.replanned_reasons) ? errors.replanned_reasons.join(', ') : String(errors.replanned_reasons)} className="mt-2" /> : ''}
              </div>
            )}
           {(values.service === SERVICES.DELIVERY_AND_INSTALLATION) && (
              <>
               <div className={submitCount ? (errors.hide_on_weekends) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                    <div className='flex'>
                      <Field
                        id="hide_on_weekends"
                        name="hide_on_weekends"
                        className="form-checkbox"
                        type='checkbox'
                        onChange={(e: any) => {
                          setFieldValue('hide_on_weekends', e.target.checked)
                          // setFieldValue('initial_payment_percentage', 0)
                        }}
                      />
                      <label htmlFor="hide_on_weekends">Hide On Weekends</label>
                    </div>
                    {(submitCount && errors.hide_on_weekends) ? <div className='block'><InputError message={errors.hide_on_weekends} className="mt-2" /></div> : ''}
                </div>

                <div className={submitCount ? (errors.is_new_travel_cost) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
                    <div className='flex'>
                      <Field
                        id="is_new_travel_cost"
                        name="is_new_travel_cost"
                        className="form-checkbox"
                        type='checkbox'
                        onChange={(e: any) => {
                          setFieldValue('is_new_travel_cost', e.target.checked)
                          setFieldValue('new_travel_cost', 0)
                          // setFieldValue('initial_payment_percentage', 0)
                        }}
                      />
                      <label htmlFor="is_new_travel_cost">New Travel Cost</label>
                    </div>
                    {(submitCount && errors.is_new_travel_cost) ? <div className='block'><InputError message={errors.is_new_travel_cost} className="mt-2" /></div> : ''}
                </div>
                {(values.is_new_travel_cost) && (
                        <div className={submitCount ? (errors.new_travel_cost) ? 'has-error' : 'has-success' : ''}>
                        <label htmlFor="new_travel_cost">New Travel Cost</label>
                        <Field
                          id="new_travel_cost"
                          name="new_travel_cost"
                          className="form-input text-right"
                          autoComplete="new_travel_cost"
                          placeholder='New Travel Cost'
                          type='number'
                        />
                        {(submitCount && errors.new_travel_cost) ? <InputError message={errors.new_travel_cost} className="mt-2" /> : ''}
                </div>)}
               {/* <div className={submitCount ? (errors.initial_payment_percentage) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="initial_payment_percentage">Initial Payment Percentage</label>
                  <Field
                    id="initial_payment_percentage"
                    name="initial_payment_percentage"
                    className="form-input text-right"
                    autoComplete="initial_payment_percentage"
                    placeholder='Initial Payment Percentage'
                    type='number'
                  />
                  {(submitCount && errors.initial_payment_percentage) ? <InputError message={errors.initial_payment_percentage} className="mt-2" /> : ''}
                </div> */}
              </>
           )}
            { ((values.status && values.status === 'MATERIALS RECEIVED') || (((values.status as unknown) as { value: string })?.value === 'MATERIALS RECEIVED')) && (
            <div className={submitCount ? (errors.material_received_date) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="material_received_date">Materials Received Date:</label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                name="material_received_date"
                value={values.material_received_date?.toString()}
                className="form-input"
                onChange={([date]) => {
                  setFieldValue('material_received_date', date.toISOString().slice(0, 10))
                }}
              />
              {(submitCount && errors.material_received_date) ? <InputError message={errors.material_received_date?.toString()} className="mt-2" /> : ''}
            </div>
            ) }
          </div>
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Payment Information</legend>
          <div className='grid gap-4 grid-cols-3'>
            <div className={submitCount ? (errors.method_of_payment) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="method_of_payment">Project Payment Method</label>
              <Field
                id="method_of_payment"
                name="method_of_payment"
                className="form-select"
                autoComplete="method_of_payment"
                placeholder='Method of Payment'
                as="select"
                onChange={(e: { target: { value: string } }) => {
                  setFieldValue('method_of_payment', e.target.value)
                  setFieldValue('cost_delivery', 0)
                  setFieldValue('type_of_financing', '')
                  setFieldValue('payment_schedule_type', '')
                  setFieldValue('custom_schedule', buildCustomSchedule())
                }}
              >
                <option value="">Method of Payment</option>
                {methods_of_payment.map((method_of_payment, index) => (
                  <option key={index} value={method_of_payment}>{method_of_payment}</option>
                ))}
              </Field>
              {(submitCount && errors.method_of_payment) ? <InputError message={errors.method_of_payment} className="mt-2" /> : ''}
            </div>
            {canEditScheduleInForm && (
              <div className={submitCount ? (errors.payment_schedule_type) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="payment_schedule_type">Payment Schedule</label>
                <Field
                  id="payment_schedule_type"
                  name="payment_schedule_type"
                  className="form-select"
                  autoComplete="payment_schedule_type"
                  placeholder="Payment Schedule"
                  as="select"
                  onChange={(e: { target: { value: string } }) => {
                    const nextType = e.target.value
                    setFieldValue('payment_schedule_type', nextType)
                    setFieldValue('custom_schedule', nextType === CUSTOM_SCHEDULE_TYPE ? buildCustomSchedule(values.custom_schedule) : buildCustomSchedule())
                  }}
                >
                  <option value="">Select Payment Schedule</option>
                  {payment_schedule_types.map((type, index) => (
                    <option key={index} value={type}>{type}</option>
                  ))}
                </Field>
                {(submitCount && errors.payment_schedule_type) ? <InputError message={errors.payment_schedule_type} className="mt-2" /> : ''}
              </div>
            )}
            <div className={submitCount ? (errors.project_amount) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="project_amount">Project Amount</label>
              <Field
                id="project_amount"
                name="project_amount"
                className="form-input text-right"
                autoComplete="project_amount"
                placeholder='Project Amount'
                type='number'
                disabled={isProjectAmountLocked}
              />
              {(submitCount && errors.project_amount) ? <InputError message={errors.project_amount} className="mt-2" /> : ''}
              {isProjectAmountLocked && (
                <div className="mt-1 text-xs text-slate-500">
                  Locked after CONTRACT SIGNED BY CLIENT. Use Change Order for amount changes.
                </div>
              )}
            </div>
            {canEditScheduleInForm && isCustomSchedule && (
              <div className="col-span-3 space-y-3 rounded-md border border-[#e0e6ed] bg-white p-3 dark:border-[#1b2e4b]">
                <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-[#5c6370]">
                  <span>Custom schedule</span>
                  <span className={customTotalClass}>
                    Total: {formatCurrency(customScheduleTotal)}
                    {hasProjectAmount ? ` / ${formatCurrency(projectAmountValue)}` : ''}
                  </span>
                </div>
                {customSchedule.map((item, index) => (
                  <div key={`custom-schedule-${index}`} className="grid gap-3 md:grid-cols-3">
                    <div className="md:col-span-2">
                      <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#5c6370]">Label</label>
                      <input
                        name={`custom_schedule[${index}].label`}
                        type="text"
                        value={item.label}
                        onChange={(event) => { setFieldValue(`custom_schedule[${index}].label`, event.target.value) }}
                        className="form-input"
                        placeholder={`Payment ${index + 1}`}
                      />
                    </div>
                    <div>
                      <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-[#5c6370]">Amount</label>
                      <input
                        name={`custom_schedule[${index}].amount`}
                        type="number"
                        step="0.01"
                        value={item.amount}
                        onChange={(event) => { setFieldValue(`custom_schedule[${index}].amount`, event.target.value) }}
                        className="form-input text-right"
                        placeholder="0.00"
                      />
                    </div>
                  </div>
                ))}
                {submitCount && typeof errors.custom_schedule === 'string'
                  ? <InputError message={errors.custom_schedule} className="mt-2" />
                  : null}
              </div>
            )}
            {shouldShowSchedulePreview && (
              <div className="col-span-3">
                <label className="mb-1 block text-sm font-medium text-[#5c6370]">
                  Payment Schedule Preview
                </label>
                {schedulePreviewItems.length > 0 ? (
                  <div className="rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b]">
                    <div className="flex flex-wrap gap-6 px-3 py-2 text-sm text-[#5c6370]">
                      <div>
                        <span className="font-semibold text-[#1f2937]">Type:</span>{' '}
                        {values.payment_schedule_type || '--'}
                      </div>
                      <div>
                        <span className="font-semibold text-[#1f2937]">Total:</span>{' '}
                        {hasProjectAmount ? formatCurrency(projectAmountValue) : '--'}
                      </div>
                    </div>
                    <div className="border-t border-[#e0e6ed] dark:border-[#1b2e4b]">
                      {schedulePreviewItems.map((item, index) => (
                        <div
                          key={`${item.label}-${index}`}
                          className="flex flex-wrap items-center gap-4 px-3 py-2 text-sm text-[#5c6370]"
                        >
                          <span className="font-semibold text-[#1f2937]">{item.label}</span>
                          <span>{Number.isFinite(Number(item.percentage)) ? `${item.percentage}%` : '--'}</span>
                          <span>{item.amount != null ? formatCurrency(item.amount) : '--'}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                ) : (
                  <div className="text-sm text-[#5c6370]">
                    No payment schedule template available for this selection.
                  </div>
                )}
              </div>
            )}
            {(values.method_of_payment === PAYMENT_METHODS.FINANCED || values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE) && (
              <div className={submitCount ? (errors.type_of_financing) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="method_of_payment">Type Of Financing</label>
                <Field
                  id="type_of_financing"
                  name="type_of_financing"
                  className="form-select"
                  autoComplete="type_of_financing"
                  placeholder='Type Of Financing'
                  as="select"
                  onChange={(e: { target: { value: string } }) => {
                    setFieldValue('type_of_financing', e.target.value)
                  }}
                >
                  <option value="">Type Of Financing</option>
                  {type_of_financing.map((financing, index) => (
                    <option key={index} value={financing}>{financing}</option>
                  ))}
                </Field>
                {(submitCount && errors.type_of_financing) ? <InputError message={errors.type_of_financing} className="mt-2" /> : ''}
              </div>
            )}
            {values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE && (
              <div className={submitCount ? (errors.down_payment) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="down_payment">Down Payment</label>
                <Field
                  id="down_payment"
                  name="down_payment"
                  className="form-input text-right"
                  autoComplete="down_payment"
                  placeholder='Down Payment'
                  type='number'
                />
                {(submitCount && errors.down_payment) ? <InputError message={errors.down_payment} className="mt-2" /> : ''}
              </div>
            )}
            <div className={submitCount ? (errors.change_order_enabled) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex items-end'}>
              <div className='flex'>
                <Field
                  id="change_order_enabled"
                  name="change_order_enabled"
                  className="form-checkbox"
                  type='checkbox'
                  checked={Boolean(values.change_order_enabled)}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    const enabled = e.target.checked
                    setFieldValue('change_order_enabled', enabled)
                    if (!enabled) {
                      setFieldValue('change_order_amount', null)
                      setFieldValue('change_order_note', '')
                    }
                  }}
                />
                <label htmlFor="change_order_enabled" className="ml-2">Change Order</label>
              </div>
              {(submitCount && errors.change_order_enabled) ? <div className='block'><InputError message={errors.change_order_enabled} className="mt-2" /></div> : ''}
            </div>
            {values.change_order_enabled && (
              <>
                <div className={submitCount ? (errors.change_order_amount) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="change_order_amount">Change Order Price</label>
                  <Field
                    id="change_order_amount"
                    name="change_order_amount"
                    className="form-input text-right"
                    autoComplete="change_order_amount"
                    placeholder='Change Order Price'
                    type='number'
                  />
                  {(submitCount && errors.change_order_amount) ? <InputError message={errors.change_order_amount} className="mt-2" /> : ''}
                </div>
                <div className={submitCount ? (errors.change_order_note) ? 'has-error' : 'has-success' : ''}>
                  <label htmlFor="change_order_note">Change Order Note</label>
                  <Field
                    id="change_order_note"
                    name="change_order_note"
                    className="form-input"
                    autoComplete="change_order_note"
                    placeholder='Change Order Note'
                    type='text'
                  />
                  {(submitCount && errors.change_order_note) ? <InputError message={errors.change_order_note} className="mt-2" /> : ''}
                </div>
              </>
            )}
            {!isCreate && values.method_of_payment === PAYMENT_METHODS.CASH && !canEditScheduleInForm && (
              <div className="col-span-3">
                <label className="mb-1 block text-sm font-medium text-[#5c6370]" htmlFor="payment_schedule">
                  Payment Schedule
                </label>
                <div className="mb-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                  Schedule locked: this order already has recorded payments.
                </div>
                {values.payment_schedule ? (
                  <div className="rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b]">
                    <div className="flex flex-wrap gap-6 px-3 py-2 text-sm text-[#5c6370]">
                      <div>
                        <span className="font-semibold text-[#1f2937]">Type:</span>{' '}
                        {values.payment_schedule.schedule_type ?? '--'}
                      </div>
                      <div>
                        <span className="font-semibold text-[#1f2937]">Planned:</span>{' '}
                        {formatCurrency(values.payment_schedule.total_amount)}
                      </div>
                      <div>
                        <span className="font-semibold text-[#1f2937]">Paid:</span>{' '}
                        {formatCurrency(values.payment_schedule.paid_amount ?? 0)}
                      </div>
                      <div>
                        <span className="font-semibold text-[#1f2937]">Balance:</span>{' '}
                        {formatCurrency(values.payment_schedule.remaining_amount ?? 0)}
                      </div>
                    </div>
                    {values.payment_schedule.installments && values.payment_schedule.installments.length > 0 ? (
                      <div className="border-t border-[#e0e6ed] dark:border-[#1b2e4b]">
                        <div className="grid grid-cols-2 md:grid-cols-6 gap-3 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-[#5c6370]">
                          <span>Installment</span>
                          <span>Planned</span>
                          <span>Paid</span>
                          <span>Balance</span>
                          <span>Due Date</span>
                          <span>Status</span>
                        </div>
                        {values.payment_schedule.installments.map((installment) => (
                          <div
                            key={installment.id}
                            className="grid grid-cols-2 md:grid-cols-6 gap-3 px-3 py-2 text-sm text-[#5c6370] border-t border-[#e0e6ed] dark:border-[#1b2e4b]"
                          >
                            <span className="font-semibold text-[#1f2937]">
                              {installment.label}
                              <span className="ml-2 text-xs font-normal text-[#5c6370]">{installment.percentage}%</span>
                            </span>
                            <span>{formatCurrency(installment.amount)}</span>
                            <span>{formatCurrency(installment.paid_amount ?? 0)}</span>
                            <span>{formatCurrency(installment.balance ?? 0)}</span>
                            <span>{installment.due_date ?? '-'}</span>
                            <span className="text-xs uppercase tracking-wide">{installment.status}</span>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="px-3 py-2 text-sm text-[#5c6370]">
                        No payment installments recorded for this order.
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="text-sm text-[#5c6370]">
                    No payment schedule has been created for this order. (Likely an older order.)
                  </div>
                )}
              </div>
            )}
          </div>
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Installer Notes</legend>
          <div className='grid gap-4 grid-cols-4'>
            <div className='col-span-4'>
              <label htmlFor="notes">Installer Notes</label>
              <Field
                id="notes"
                name="notes"
                component="textarea"
                rows="4"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
              />
            </div>
            {showWorkTeamNotes && (
              <div className='col-span-4'>
                <label htmlFor="work_team_notes">Work Team Notes</label>
                <Field
                  id="work_team_notes"
                  name="work_team_notes"
                  component="textarea"
                  rows="4"
                  className="form-textarea resize-none placeholder:text-white-dark"
                  placeholder='Work Team Notes'
                />
              </div>
            )}
            <div className='col-span-4'>
              <label htmlFor="attachments">Attachments</label>
              <input
                id="attachments"
                name="attachments"
                type="file"
                accept="*"
                className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
                placeholder="Qty"
                multiple={true}
                onChange={(event: any) => {
                  const files = Array.from(event.currentTarget.files ?? [])
                  setFieldValue('attachments', files)
                }}
              />
              {attachments !== undefined && attachments.length > 0 && (
                <div className="flex flex-col rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b] mt-3">
                  <table className='w-full whitespace-nowrap'>
                    <thead>
                      <tr>
                        <th className='border-b px-4 py-2 text-left'>File</th>
                        {allowsAttachmentRoleSelection && ATTACHMENT_ROLE_OPTIONS.map((roleOption) => (
                          <th key={roleOption.key} className='border-b px-3 py-2 text-center text-xs'>{roleOption.label}</th>
                        ))}
                        <th className='border-b px-4 py-2 text-right'>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {attachmentsArray.map((attachment, index) => {
                        const attachmentId = Number(attachment.id)
                        return (
                          <tr key={index} className='border-b border-[#e0e6ed] dark:border-[#1b2e4b] hover:bg-[#eee] dark:hover:bg-[#eee]/10'>
                            <td className='px-4 py-2.5'>{attachment.filename}</td>
                            {allowsAttachmentRoleSelection && ATTACHMENT_ROLE_OPTIONS.map((roleOption) => {
                              const roleTargets = attachmentRoleTargets[roleOption.key] ?? []
                              const isChecked = Number.isInteger(attachmentId) && roleTargets.includes(attachmentId)

                              return (
                                <td key={`${attachment.id}-${roleOption.key}`} className='px-3 py-2.5 text-center'>
                                  <input
                                    type='checkbox'
                                    checked={isChecked}
                                    disabled={!Number.isInteger(attachmentId) || attachmentId <= 0}
                                    onChange={(event) => {
                                      toggleAttachmentRoleTarget(roleOption.key, attachmentId, event.currentTarget.checked)
                                    }}
                                  />
                                </td>
                              )
                            })}
                            <td className='px-4 py-2.5 text-right'>
                              <div className='flex flex-row gap-2 justify-end'>
                                {Number.isInteger(attachmentId) && attachmentId > 0 && (
                                  <a href={route('download.file', { id: attachmentId })} target='_blank' rel='noreferrer' title='Open Attachment'>
                                    <ExportIcon />
                                  </a>
                                )}
                                <button
                                  onClick={(e) => {
                                    e.preventDefault()
                                    removeAttachmentProduct(index)
                                  }}
                                  title='Delete Attachment'
                                >
                                  <DeleteIcon />
                                </button>
                              </div>
                            </td>
                          </tr>
                        )
                      })}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Product Information</legend>
          {((values.service === SERVICES.DELIVERY_AND_INSTALLATION && values.type_of_work_id !== 0) || (values.service === SERVICES.DELIVERY_ONLY || values.service === SERVICES.PICKUP || values.service === SERVICES.SERVICE)) && (
            <div className='flex items-center justify-end'>
              <button onClick={(e) => {
                e.preventDefault()
                setShowProductModal(true)
              }} className="btn btn-primary">Add Product</button>
            </div>
          )}
          <ProductTable
            orderProducts={orderProducts}
            type_of_products={type_of_products}
            product_category={product_category}
            products_config={products_config}
            service={values.service}
            values= {values}
            travel_costs={travel_costs}
            type_of_works={type_of_works}
            removeOrderProduct={(index: number) => { removeOrderProduct(index) }}
            updateOrderProduct={(index: number) => { updateOrderProduct(index) }}
            extraWorks={extraWorks}
            product_costs={product_costs}
          />
        </fieldset>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Work Team Notes (All Notes)</legend>
          <OrderNotesForOrder orderId={values.id || null} canCreate={values.id !== 0} />
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
        typeOfWork={values.type_of_work_id}
        listTypeOfWork={type_of_works}
        productCosts={product_costs}
        service={values.service}
        onClose={() => {
          setShowProductModal(false)
        }}
        isCreated={isCreated}
        addOrderProduct={(product: OrderProduct) => { addOrderProduct(product) }}
      />
    </>
  )
}

export default OrderForm
