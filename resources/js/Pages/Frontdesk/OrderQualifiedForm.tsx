import { useState, useRef, useMemo, useEffect, useCallback, type FocusEvent } from 'react'
import { useJsApiLoader } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import {
  type User,
  type OptionType,
  type Attachment,
  type CompanyContact
} from '@/types'
import Select, { type SingleValue } from 'react-select'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type OrderFormValues } from './OrderCommon'
import PlusIcon from '@/Components/Icons/PlusIcon'
import { ORDER_TYPES, PAYMENT_METHODS, PRODUCT_LINES } from '@/Utils/constants'
import { formatDateOnlyValue } from '@/Utils/dateOnly'
import CompanyModal from './CompanyModal'
import { type Source } from '@/types/interfaces/order'
import ClientModal from './ClientModal'
import { type Client } from '../Client/ClientCommon'
import { NO_CLIENT_EMAIL_SELECTION, PRIMARY_CLIENT_EMAIL_SELECTION } from '@/Pages/Sales/ContractSignedModal'
import OrderGlobalSearch, { type OrderSearchResult } from '@/Components/OrderGlobalSearch'

const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY
const GOOGLE_MAPS_LIBRARIES: Array<'places'> = ['places']

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

type PaymentScheduleTemplateItem = { label: string, percentage: number }
type PaymentScheduleTemplates = Record<string, PaymentScheduleTemplateItem[]>
type CustomScheduleItem = { label: string, amount: string }
type ExternalEsrOrder = {
  name?: string | null
  order_number?: string | number | null
  project_amount?: string | number | null
  esr_express?: boolean
  esr_reylos_glass?: boolean
  esr_service?: boolean
  owner_id?: number | string | null
  account_manager_email?: string | null
  company_contact_id?: number | string | null
  company_email?: string | null
  company_phone?: string | null
}
type BosOrderPrefill = {
  name?: string | null
  product_line?: string | null
  service?: string | null
  project_amount?: string | number | null
  job_address?: string | null
  city?: string | null
  job_state?: string | null
  job_zip?: string | null
  method_of_payment?: string | null
  type_of_financing?: string | null
  down_payment?: string | number | null
  payment_schedule_type?: string | null
  client_id?: number | null
  company_contact_id?: number | null
  client_email_selection?: string | null
  owner_ids?: number[]
  company_pairs?: Array<{
    company_contact_id?: number | null
    client_id?: number | null
    source_id?: number | null
  }>
}
type ExternalOrderSearchStatus = 'idle' | 'loading' | 'found' | 'error'
const BOS_ORDER_MODULES = [
  { value: 'service_control', label: 'BOS Orders' }
]

const CUSTOM_SCHEDULE_TYPE = 'CUSTOMIZED'

const buildCustomSchedule = (items?: Array<{ label?: string | null, amount?: number | string | null }>): CustomScheduleItem[] => {
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

const clientBelongsToCompany = (client: Client, companyId: number | null) => {
  if (!companyId) return false

  const companyIds = Array.isArray(client.company_contact_ids)
    ? client.company_contact_ids.map((id) => Number(id))
    : []

  if (companyIds.includes(Number(companyId))) {
    return true
  }

  return client.company_contact_id != null && Number(client.company_contact_id) === Number(companyId)
}

const withClientCompanyLink = (client: Client, companyId: number | null): Client => {
  if (!companyId) {
    return client
  }

  const nextCompanyIds = Array.isArray(client.company_contact_ids)
    ? [...client.company_contact_ids.map((id) => Number(id))]
    : []

  if (!nextCompanyIds.includes(Number(companyId))) {
    nextCompanyIds.push(Number(companyId))
  }

  return {
    ...client,
    company_contact_id: client.company_contact_id ?? Number(companyId),
    company_contact_ids: nextCompanyIds
  }
}

type ClientEmailOption = {
  value: string
  label: string
  is_primary?: boolean
}

const normalizeEmail = (email?: string | null) => {
  if (typeof email !== 'string') return null
  const normalized = email.trim()
  return normalized !== '' ? normalized : null
}

const pushClientEmailOption = (options: ClientEmailOption[], seen: Set<string>, email: string | null, label: string, isPrimary = false) => {
  const normalizedEmail = normalizeEmail(email)
  if (!normalizedEmail) return

  const key = normalizedEmail.toLowerCase()
  if (seen.has(key)) {
    const existing = options.find(option => option.value.toLowerCase() === key)
    if (existing && isPrimary) {
      existing.is_primary = true
      existing.label = `${label}: ${normalizedEmail}`
    }
    return
  }

  seen.add(key)
  options.push({
    value: normalizedEmail,
    label: `${label}: ${normalizedEmail}`,
    is_primary: isPrimary
  })
}

const buildClientEmailOptions = (
  client: Client | null,
  selectedCompany: CompanyContact | null,
  companies: CompanyContact[]
) => {
  const options: ClientEmailOption[] = []
  const seen = new Set<string>()

  pushClientEmailOption(options, seen, client?.email ?? null, 'Primary client email', true)
  pushClientEmailOption(options, seen, client?.secondary_email ?? null, 'Secondary client email')

  if (selectedCompany) {
    pushClientEmailOption(
      options,
      seen,
      selectedCompany.email ?? null,
      selectedCompany.name ? `Selected company email: ${selectedCompany.name}` : 'Selected company email'
    )
  }

  const clientCompanyIds = client?.company_contact_ids
  const linkedCompanyIds = Array.isArray(clientCompanyIds)
    ? clientCompanyIds.map((id) => Number(id))
    : (client?.company_contact_id != null ? [Number(client.company_contact_id)] : [])

  linkedCompanyIds.forEach((companyId) => {
    const linkedCompany = companies.find((item) => Number(item.id) === Number(companyId))
    if (!linkedCompany) return

    pushClientEmailOption(
      options,
      seen,
      linkedCompany.email ?? null,
      linkedCompany.name ? `Associated company email: ${linkedCompany.name}` : 'Associated company email'
    )
  })

  return options
}

const OrderQualifiedForm = ({
  submitCount,
  errors,
  isCreate,
  clients,
  setFieldValue,
  values,
  owners,
  attachments,
  status,
  sources,
  order_types,
  companies,
  sourcesClients,
  frame_colors,
  glass_colors,
  glass_types,
  glass_coatings,
  languages,
  methodsOfPayment = [],
  financingOptions = [],
  paymentScheduleTemplates = {},
  services = [],
  onCancel,
  submitLabel,
  showClientField = true,
  showNotesField = true,
  useModalLayout = false,
  showOwnerField = false,
  showInvoiceField = false,
  showPaymentInformationSection = false,
  showProjectAmountOnlySection = false,
  projectAmountReadOnly = false,
  appointmentDateReadOnly = false,
  esrMode = false,
  showCommercialSourceField = true,
  showAddCommercialCompanyButton = true,
  hideActions = false,
  showAttachmentsField = false,
  companyStoreRoute = 'company_contact.store',
  clientStoreRoute = 'client.store',
  serviceCreationMode = false
}: {
  submitCount: number
  errors: FormikErrors<OrderFormValues>
  isCreate: boolean
  clients: Client[]
  setFieldValue: (field: string, value: any) => void
  values: OrderFormValues
  owners: User[]
  attachments?: Attachment[]
  status: string[]
  sources: Source[]
  order_types: string[]
  companies: CompanyContact[]
  sourcesClients: string[]
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  glass_coatings: string[]
  languages: string[]
  methodsOfPayment?: string[]
  financingOptions?: string[]
  paymentScheduleTemplates?: PaymentScheduleTemplates
  services?: string[]
  onCancel?: () => void
  submitLabel?: string
  showClientField?: boolean
  showNotesField?: boolean
  useModalLayout?: boolean
  showOwnerField?: boolean
  showInvoiceField?: boolean
  showPaymentInformationSection?: boolean
  showProjectAmountOnlySection?: boolean
  projectAmountReadOnly?: boolean
  appointmentDateReadOnly?: boolean
  esrMode?: boolean
  showCommercialSourceField?: boolean
  showAddCommercialCompanyButton?: boolean
  hideActions?: boolean
  showAttachmentsField?: boolean
  companyStoreRoute?: string
  clientStoreRoute?: string
  serviceCreationMode?: boolean
}) => {
  const jobAddressInputRef = useRef<HTMLInputElement | null>(null)
  const autocompleteInstanceRef = useRef<google.maps.places.Autocomplete | null>(null)
  const autocompleteListenerRef = useRef<google.maps.MapsEventListener | null>(null)
  const geocoderRef = useRef<google.maps.Geocoder | null>(null)
  const menoLibraries = useMemo(() => GOOGLE_MAPS_LIBRARIES, [])
  const { isLoaded } = useJsApiLoader({
    id: 'google-map-script',
    googleMapsApiKey: GOOGLE_MAPS_API_KEY,
    libraries: menoLibraries
  })

  const [companiesList, setCompaniesList] = useState<CompanyContact[]>(companies)
  const [clientsList, setClientsList] = useState<Client[]>(clients)
  const [showCompanyModal, setShowCompanyModal] = useState<boolean>(false)
  const [showClientModal, setShowClientModal] = useState<boolean>(false)
  const [externalOrderSearch, setExternalOrderSearch] = useState<string>('')
  const [externalOrderSearchStatus, setExternalOrderSearchStatus] = useState<ExternalOrderSearchStatus>('idle')
  const [externalOrderSearchMessage, setExternalOrderSearchMessage] = useState<string>('')
  const [externalOwnerWarning, setExternalOwnerWarning] = useState<string>('')
  const [externalCompanyWarning, setExternalCompanyWarning] = useState<string>('')
  const [selectedBosOrder, setSelectedBosOrder] = useState<OrderSearchResult | null>(null)
  const externalOrderSearchRequestIdRef = useRef(0)

  useEffect(() => {
    if (!isLoaded) return
    geocoderRef.current = new google.maps.Geocoder()
  }, [isLoaded])

  const [modalTargetClientField, setModalTargetClientField] = useState<string | null>(null)
  const [companyModalTargetIndex, setCompanyModalTargetIndex] = useState<number | null>(null)
  const [clientModalTargetIndex, setClientModalTargetIndex] = useState<number | null>(null)

  const isCommercial = values.order_type === ORDER_TYPES.COMMERCIAL
  type CommercialPair = { companyId: number | null, clientId: number | null, sourceId: number | null }
  const buildCommercialPairsFromValues = useCallback((): CommercialPair[] => {
    if (!isCommercial) return []
    const pairs: CommercialPair[] = []
    if (values.company_contact_id) {
      pairs.push({
        companyId: Number(values.company_contact_id),
        clientId: values.client_id ? Number(values.client_id) : null,
        sourceId: values.company_source_id ? Number(values.company_source_id) : null
      })
    }
    if (values.associate_company_contact_id_1) {
      pairs.push({
        companyId: Number(values.associate_company_contact_id_1),
        clientId: values.associate_client_id_1 ? Number(values.associate_client_id_1) : null,
        sourceId: values.associate_source_id_1 ? Number(values.associate_source_id_1) : null
      })
    }
    if (values.associate_company_contact_id_2) {
      pairs.push({
        companyId: Number(values.associate_company_contact_id_2),
        clientId: values.associate_client_id_2 ? Number(values.associate_client_id_2) : null,
        sourceId: values.associate_source_id_2 ? Number(values.associate_source_id_2) : null
      })
    }
    if (values.associate_company_contact_id_3) {
      pairs.push({
        companyId: Number(values.associate_company_contact_id_3),
        clientId: values.associate_client_id_3 ? Number(values.associate_client_id_3) : null,
        sourceId: values.associate_source_id_3 ? Number(values.associate_source_id_3) : null
      })
    }
    if (values.associate_company_contact_id_4) {
      pairs.push({
        companyId: Number(values.associate_company_contact_id_4),
        clientId: values.associate_client_id_4 ? Number(values.associate_client_id_4) : null,
        sourceId: values.associate_source_id_4 ? Number(values.associate_source_id_4) : null
      })
    }
    return pairs.length > 0 ? pairs : [{ companyId: null, clientId: null, sourceId: null }]
  }, [
    isCommercial,
    values.associate_client_id_1,
    values.associate_client_id_2,
    values.associate_client_id_3,
    values.associate_client_id_4,
    values.associate_company_contact_id_1,
    values.associate_company_contact_id_2,
    values.associate_company_contact_id_3,
    values.associate_company_contact_id_4,
    values.associate_source_id_1,
    values.associate_source_id_2,
    values.associate_source_id_3,
    values.associate_source_id_4,
    values.client_id,
    values.company_contact_id,
    values.company_source_id
  ])
  const [commercialPairs, setCommercialPairs] = useState<CommercialPair[]>(buildCommercialPairsFromValues)

  useEffect(() => {
    if (!isCommercial) {
      setCommercialPairs([])
      return
    }
    setCommercialPairs(prev => (prev.length > 0 ? prev : buildCommercialPairsFromValues()))
  }, [isCommercial, buildCommercialPairsFromValues])

  useEffect(() => {
    if (!isCommercial) return
    const [primary, assoc1, assoc2, assoc3, assoc4] = commercialPairs
    setFieldValue('company_contact_id', primary?.companyId ?? null)
    setFieldValue('client_id', primary?.clientId ?? null)
    setFieldValue('company_source_id', primary?.sourceId ?? null)
    setFieldValue('associate_company_contact_id_1', assoc1?.companyId ?? null)
    setFieldValue('associate_client_id_1', assoc1?.clientId ?? null)
    setFieldValue('associate_source_id_1', assoc1?.sourceId ?? null)
    setFieldValue('associate_company_contact_id_2', assoc2?.companyId ?? null)
    setFieldValue('associate_client_id_2', assoc2?.clientId ?? null)
    setFieldValue('associate_source_id_2', assoc2?.sourceId ?? null)
    setFieldValue('associate_company_contact_id_3', assoc3?.companyId ?? null)
    setFieldValue('associate_client_id_3', assoc3?.clientId ?? null)
    setFieldValue('associate_source_id_3', assoc3?.sourceId ?? null)
    setFieldValue('associate_company_contact_id_4', assoc4?.companyId ?? null)
    setFieldValue('associate_client_id_4', assoc4?.clientId ?? null)
    setFieldValue('associate_source_id_4', assoc4?.sourceId ?? null)
  }, [commercialPairs, isCommercial, setFieldValue])

  const onCompanyCreated = (company: CompanyContact) => {
    setCompaniesList(prev =>
      prev.some(c => c.id === company.id) ? prev : [...prev, company]
    )
    if (companyModalTargetIndex != null) {
      setCommercialPairs(prev => {
        const next = [...prev]
        const current = next[companyModalTargetIndex]
        if (!current) return prev
        next[companyModalTargetIndex] = { ...current, companyId: company.id, clientId: null }
        return next
      })
    }
    setShowCompanyModal(false)
    setCompanyModalTargetIndex(null)
  }
  const companyOptions = companiesList.map(c => ({ value: c.id, label: c.name }))
  const sourceOptions = sources.map(source => ({ value: source.id, label: source.name }))

  const addCommercialPair = () => {
    setCommercialPairs(prev => {
      if (prev.length >= 5) return prev
      return [...prev, { companyId: null, clientId: null, sourceId: null }]
    })
  }

  const removeCommercialPair = (index: number) => {
    setCommercialPairs(prev => prev.filter((_, i) => i !== index))
  }

  const onClientCreated = (client: Client) => {
    if (isCommercial && clientModalTargetIndex != null) {
      const targetCompanyId = commercialPairs[clientModalTargetIndex]?.companyId ?? null
      const clientWithCompany = withClientCompanyLink(client, targetCompanyId)

      setClientsList(prev => {
        const existingIndex = prev.findIndex(c => c.id === clientWithCompany.id)
        if (existingIndex === -1) {
          return [...prev, clientWithCompany]
        }

        const next = [...prev]
        next[existingIndex] = withClientCompanyLink(next[existingIndex], targetCompanyId)
        return next
      })
      setCommercialPairs(prev => {
        const next = [...prev]
        const current = next[clientModalTargetIndex]
        if (!current) return prev
        next[clientModalTargetIndex] = { ...current, clientId: clientWithCompany.id }
        return next
      })
      setShowClientModal(false)
      setClientModalTargetIndex(null)
      return
    }

    const clientWithCompany = client

    setClientsList(prev =>
      prev.some(c => c.id === clientWithCompany.id) ? prev : [...prev, clientWithCompany]
    )
    console.log('modalTargetClientField', modalTargetClientField)
    console.log('client.id', clientWithCompany.id)
    console.log('setClientsList', setClientsList.toString())
    if (modalTargetClientField) setFieldValue(modalTargetClientField, clientWithCompany.id)
    setShowClientModal(false)
    setModalTargetClientField(null)
  }
  const clientsOptions = clientsList.map(c => ({ value: c.id, label: c.name }))

  const selectedClient = values.client_id
    ? clientsOptions.find(o => o.value === values.client_id) ?? null
    : null
  const primaryCommercialPair = commercialPairs[0] ?? null
  const emailContextClientId = isCommercial
    ? (primaryCommercialPair?.clientId ?? null)
    : (values.client_id ? Number(values.client_id) : null)
  const emailContextCompanyId = isCommercial
    ? (primaryCommercialPair?.companyId ?? null)
    : (values.company_contact_id ? Number(values.company_contact_id) : null)
  const emailContextClient = emailContextClientId != null
    ? clientsList.find((client) => Number(client.id) === Number(emailContextClientId)) ?? null
    : null
  const emailContextCompany = emailContextCompanyId != null
    ? companiesList.find((company) => Number(company.id) === Number(emailContextCompanyId)) ?? null
    : null
  const clientEmailOptions = useMemo(
    () => buildClientEmailOptions(emailContextClient, emailContextCompany, companiesList),
    [emailContextClient, emailContextCompany, companiesList]
  )
  const primaryClientEmailOption = clientEmailOptions.find((option) => option.is_primary)

  const ownerOptions = Array.isArray(owners) ? owners.map(owner => ({ value: owner.id, label: owner.name })) : []
  const ownerIds = Array.isArray(values.owner_ids) ? values.owner_ids : []
  const selectedOwners = ownerOptions.filter(option => ownerIds.includes(option.value))

  useEffect(() => {
    if (values.client_email_selection === NO_CLIENT_EMAIL_SELECTION) {
      return
    }

    if (values.client_email_selection === PRIMARY_CLIENT_EMAIL_SELECTION) {
      return
    }

    const normalizedSelection = String(values.client_email_selection ?? '').trim().toLowerCase()
    const isStillAvailable = clientEmailOptions.some((option) => option.value.trim().toLowerCase() === normalizedSelection)
    if (!isStillAvailable) {
      setFieldValue('client_email_selection', NO_CLIENT_EMAIL_SELECTION)
    }
  }, [clientEmailOptions, setFieldValue, values.client_email_selection])

  /* const selectedClient = values.client_id
    ? {
        value: values.client_id,
        label: clientsList.find(c => c.id === values.client_id)?.name ?? ''
      }
    : null */

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

      if (city) setFieldValue('city', city)
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
  const [isCreated] = useState<boolean>(true)
  const [showProductModal, setShowProductModal] = useState<boolean>(false)
  const [attachmentsArray, setAttachmentsList] = useState<Attachment[]>(attachments ?? [])
  useEffect(() => {
    setAttachmentsList(attachments ?? [])
  }, [attachments])
  const removeAttachmentProduct = (index: number) => {
    if (confirm('Are you sure you want to delete this attachment?')) {
      router.delete(route('order.drop_attachment', { id: attachmentsArray[index].id }), {
        onSuccess: () => {
          const attachmentsList = attachmentsArray.filter((_, i) => i !== index)
          setAttachmentsList(attachmentsList)
          setFieldValue('attachments', attachmentsList)
        }
      })
    }
  }
  const selectedStatus: SingleValue<OptionType> = {
    value: values.status ?? '',
    label: status.find((status) => status === values.status) ?? ''
  }
  const formatCurrency = (value?: number | string | null) => {
    const numeric = typeof value === 'number' ? value : Number(value)
    if (!Number.isFinite(numeric)) return '--'
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(numeric)
  }
  const projectAmountNumber = Number(values.project_amount ?? 0)
  const showProjectAmountField = esrMode || (!isCreate && (showPaymentInformationSection || showProjectAmountOnlySection))
  const isCashPaymentMethod = values.method_of_payment === PAYMENT_METHODS.CASH
  const isCashAndFinancedPaymentMethod = values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE
  const isFinancedPaymentMethod = values.method_of_payment === PAYMENT_METHODS.FINANCED
  const hasSchedulePaymentMethod = isCashPaymentMethod || isCashAndFinancedPaymentMethod
  const scheduleTemplates = paymentScheduleTemplates ?? {}
  const scheduleOptions = isCashAndFinancedPaymentMethod
    ? [CUSTOM_SCHEDULE_TYPE]
    : Object.keys(scheduleTemplates)
  const selectedScheduleItems = values.payment_schedule_type
    ? (scheduleTemplates[values.payment_schedule_type] ?? [])
    : []
  const customSchedule = buildCustomSchedule(values.custom_schedule)
  const hasProjectAmount = Number.isFinite(projectAmountNumber) && projectAmountNumber > 0
  const cashAmountValue = Number(String(values.down_payment ?? '').replace(/,/g, ''))
  const hasCashAmount = Number.isFinite(cashAmountValue) && cashAmountValue > 0
  const financedAmountValue = hasProjectAmount && hasCashAmount
    ? Math.max(projectAmountNumber - cashAmountValue, 0)
    : null
  const scheduleTargetAmount = isCashAndFinancedPaymentMethod ? cashAmountValue : projectAmountNumber
  const hasScheduleTargetAmount = Number.isFinite(scheduleTargetAmount) && scheduleTargetAmount > 0
  const hasRecordedSchedulePayments = Boolean(values.payment_schedule && (
    Number(values.payment_schedule.paid_amount ?? 0) > 0
    || (values.payment_schedule.installments ?? []).some((installment) => (
      Number(installment.paid_amount ?? 0) > 0
      || (installment.movements?.length ?? 0) > 0
      || String(installment.status ?? '').toUpperCase() !== 'PENDING'
    ))
  ))
  const isScheduleLockedByPayments = !isCreate && hasRecordedSchedulePayments
  const canEditScheduleInForm = hasSchedulePaymentMethod && (isCreate || !hasRecordedSchedulePayments)
  const shouldShowSchedulePreview = canEditScheduleInForm && Boolean(values.payment_schedule_type)
  const paymentScheduleTotalValue = Number(values.payment_schedule?.total_amount ?? Number.NaN)
  const effectiveScheduleTargetAmount = isScheduleLockedByPayments && Number.isFinite(paymentScheduleTotalValue)
    ? paymentScheduleTotalValue
    : scheduleTargetAmount
  const hasEffectiveScheduleTargetAmount = Number.isFinite(effectiveScheduleTargetAmount) && effectiveScheduleTargetAmount > 0
  const recordedSchedulePayments = (values.payment_schedule?.installments ?? []).filter((installment) => (
    Number(installment.paid_amount ?? 0) > 0
    || (installment.movements?.length ?? 0) > 0
  ))
  const buildSchedulePreview = (items: PaymentScheduleTemplateItem[]) => {
    if (!items.length) return []
    if (!hasEffectiveScheduleTargetAmount) {
      return items.map((item) => ({ ...item, amount: null }))
    }
    let runningTotal = 0
    return items.map((item, index) => {
      const amount = index === items.length - 1
        ? Math.round((effectiveScheduleTargetAmount - runningTotal) * 100) / 100
        : Math.round((effectiveScheduleTargetAmount * (item.percentage / 100)) * 100) / 100
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
  const customTotalMatches = hasScheduleTargetAmount && Math.abs(customScheduleTotal - scheduleTargetAmount) <= 0.01
  const customTotalClass = hasScheduleTargetAmount
    ? customTotalMatches
      ? 'text-emerald-600'
      : 'text-rose-600'
    : 'text-slate-400'
  const schedulePreviewItems = isCustomSchedule
    ? customScheduleItems.map((item) => ({
      label: item.label,
      percentage: hasEffectiveScheduleTargetAmount ? Math.round(((item.amount / effectiveScheduleTargetAmount) * 100) * 100) / 100 : Number.NaN,
      amount: item.amount
    }))
    : buildSchedulePreview(selectedScheduleItems)
  const isProjectAmountLocked = isScheduleLockedByPayments || projectAmountReadOnly
  const projectAmountLockedMessage = isScheduleLockedByPayments
    ? 'Locked because this order already has recorded payments.'
    : projectAmountReadOnly
      ? 'Locked for this role while payment method and schedule are already assigned before CONTRACT SIGNED BY CLIENT.'
      : null
  const canShowChangeOrderFields = showPaymentInformationSection && !isCreate && (values.has_contract_signed || esrMode)

  const firstClientIdForCompany = useCallback((companyId: number | null) => {
    if (companyId == null) return null

    return clientsList.find(client => clientBelongsToCompany(client, companyId))?.id ?? null
  }, [clientsList])

  const applyExternalOrder = useCallback((order: ExternalEsrOrder) => {
    setFieldValue('name', order.name ?? '')
    setFieldValue('order_number', order.order_number != null ? String(order.order_number) : '')
    setFieldValue('project_amount', order.project_amount ?? 0)
    setFieldValue('esr_express', Boolean(order.esr_express))
    setFieldValue('esr_reylos_glass', Boolean(order.esr_reylos_glass))
    setFieldValue('esr_service', serviceCreationMode ? true : Boolean(order.esr_service))

    if (order.owner_id != null && order.owner_id !== '') {
      setFieldValue('owner_ids', [Number(order.owner_id)])
    }

    if (order.company_contact_id != null && order.company_contact_id !== '') {
      const companyId = Number(order.company_contact_id)
      const clientId = firstClientIdForCompany(companyId)
      setCommercialPairs(prev => {
        const next = prev.length > 0 ? [...prev] : [{ companyId: null, clientId: null, sourceId: null }]
        next[0] = { ...next[0], companyId, clientId }
        return next
      })
      setFieldValue('company_contact_id', companyId)
      setFieldValue('client_id', clientId)
    }
  }, [firstClientIdForCompany, serviceCreationMode, setFieldValue])

  const applyBosOrderPrefill = useCallback((order: BosOrderPrefill) => {
    const prefillService = order.service && services.includes(order.service)
      ? order.service
      : (values.service ?? '')

    setFieldValue('name', order.name ?? '')
    if (!serviceCreationMode) {
      setFieldValue('product_line', order.product_line ?? values.product_line ?? '')
    }
    setFieldValue('service', prefillService)
    setFieldValue('project_amount', order.project_amount ?? 0)
    setFieldValue('job_address', order.job_address ?? '')
    setFieldValue('city', order.city ?? '')
    setFieldValue('job_state', order.job_state ?? '')
    setFieldValue('job_zip', order.job_zip ?? '')
    setFieldValue('method_of_payment', order.method_of_payment ?? values.method_of_payment ?? '')
    setFieldValue('type_of_financing', order.type_of_financing ?? null)
    setFieldValue('down_payment', order.down_payment ?? null)
    setFieldValue('payment_schedule_type', order.payment_schedule_type ?? null)
    setFieldValue('client_email_selection', order.client_email_selection ?? NO_CLIENT_EMAIL_SELECTION)

    if (Array.isArray(order.owner_ids) && order.owner_ids.length > 0) {
      setFieldValue('owner_ids', order.owner_ids.map(Number))
    }

    const pairs = Array.isArray(order.company_pairs) && order.company_pairs.length > 0
      ? order.company_pairs.map(pair => ({
        companyId: pair.company_contact_id != null ? Number(pair.company_contact_id) : null,
        clientId: pair.client_id != null ? Number(pair.client_id) : null,
        sourceId: pair.source_id != null ? Number(pair.source_id) : null
      }))
      : [{
          companyId: order.company_contact_id != null ? Number(order.company_contact_id) : null,
          clientId: order.client_id != null ? Number(order.client_id) : null,
          sourceId: null
        }]

    setCommercialPairs(pairs.length > 0 ? pairs : [{ companyId: null, clientId: null, sourceId: null }])
  }, [serviceCreationMode, services, setFieldValue, values.method_of_payment, values.product_line, values.service])

  const loadBosOrderPrefill = useCallback(async (orderId: number) => {
    try {
      const response = await fetch(route('esr-process.orders.prefill', { order: orderId }), {
        headers: {
          Accept: 'application/json'
        }
      })
      const payload = await response.json().catch(() => ({}))

      if (!response.ok) {
        throw new Error(payload?.message ?? 'Unable to load BOS order data.')
      }

      applyBosOrderPrefill(payload.order ?? {})
    } catch (error) {
      setExternalOrderSearchStatus('error')
      setExternalOrderSearchMessage(error instanceof Error ? error.message : 'Unable to load BOS order data.')
    }
  }, [applyBosOrderPrefill])

  const handleExternalOrderSearch = useCallback(async (searchOverride?: string) => {
    const search = (searchOverride ?? externalOrderSearch).trim()

    if (!search) {
      setExternalOrderSearchStatus('error')
      setExternalOrderSearchMessage('Enter an order number to search.')
      return
    }

    const requestId = externalOrderSearchRequestIdRef.current + 1
    externalOrderSearchRequestIdRef.current = requestId
    setExternalOrderSearchStatus('loading')
    setExternalOrderSearchMessage('')
    setExternalOwnerWarning('')
    setExternalCompanyWarning('')

    try {
      const params = new URLSearchParams({ search })
      if (serviceCreationMode) {
        params.set('service_only', '1')
      } else {
        params.set('sales_only', '1')
      }
      const response = await fetch(`${route('esr-process.orders.search-external')}?${params.toString()}`, {
        headers: {
          Accept: 'application/json'
        }
      })
      const payload = await response.json().catch(() => ({}))

      if (!response.ok) {
        throw new Error(payload?.message ?? 'Unable to search ESR order.')
      }

      if (requestId !== externalOrderSearchRequestIdRef.current) return

      const externalOrder = payload.order ?? {}
      applyExternalOrder(externalOrder)
      setExternalOrderSearchStatus('found')
      setExternalOrderSearchMessage('Order data loaded. You can still edit the fields before creating it.')
      setExternalOwnerWarning(
        externalOrder.account_manager_email && !externalOrder.owner_id
          ? `Owner does not exist in BOS for ${externalOrder.account_manager_email}.`
          : ''
      )
      setExternalCompanyWarning(
        (externalOrder.company_email || externalOrder.company_phone) && !externalOrder.company_contact_id
          ? `Company does not exist in BOS for ${externalOrder.company_email ?? externalOrder.company_phone}.`
          : ''
      )
    } catch (error) {
      if (requestId !== externalOrderSearchRequestIdRef.current) return

      setExternalOrderSearchStatus('error')
      setExternalOrderSearchMessage(error instanceof Error ? error.message : 'Unable to search ESR order.')
    }
  }, [applyExternalOrder, externalOrderSearch, serviceCreationMode])

  useEffect(() => {
    if (!esrMode || !isCreate || (serviceCreationMode && values.service_source !== 'ESR')) return

    const search = externalOrderSearch.trim()
    if (search.length === 0) {
      setExternalOrderSearchStatus('idle')
      setExternalOrderSearchMessage('')
      setExternalOwnerWarning('')
      setExternalCompanyWarning('')
      return
    }

    if (search.length < 3) return

    const timeout = window.setTimeout(() => {
      void handleExternalOrderSearch(search)
    }, 600)

    return () => {
      window.clearTimeout(timeout)
    }
  }, [esrMode, externalOrderSearch, handleExternalOrderSearch, isCreate, serviceCreationMode, values.service_source])

  useEffect(() => {
    if (!serviceCreationMode) return

    if (!values.esr_service) {
      setFieldValue('esr_service', true)
    }

    if (values.service_source !== 'ESW' && values.parent_order_id) {
      setFieldValue('parent_order_id', null)
      setSelectedBosOrder(null)
    }
  }, [serviceCreationMode, setFieldValue, values.esr_service, values.parent_order_id, values.service_source])

  console.log('frame_colors ->', frame_colors)
  return (
    <>
      {useModalLayout && (
        <style>{`
          .modal-order-form label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 0.25rem;
          }
          .modal-order-form legend {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0f172a;
          }
        `}
        </style>
      )}
      <Form className={`space-y-5 ${useModalLayout ? 'modal-order-form text-slate-700' : ''}`}>
        <fieldset className='p-3 border rounded-xl'>
          <legend className='text-lg font-semibold px-3'>Order Information</legend>
          <div className='grid gap-4 grid-cols-4'>
            {esrMode && isCreate && serviceCreationMode && (
              <div className="col-span-4 md:col-span-1">
                <label htmlFor="service_source">Service Origin</label>
                <Field
                  id="service_source"
                  name="service_source"
                  className="form-select"
                  as="select"
                  onChange={(event: React.ChangeEvent<HTMLSelectElement>) => {
                    const value = event.target.value
                    setFieldValue('service_source', value)
                    setFieldValue('product_line', value)
                    setExternalOrderSearch('')
                    setExternalOrderSearchStatus('idle')
                    setExternalOrderSearchMessage('')
                    setExternalOwnerWarning('')
                    setExternalCompanyWarning('')
                    if (value !== 'ESW') {
                      setFieldValue('parent_order_id', null)
                      setSelectedBosOrder(null)
                    }
                  }}
                >
                  <option value="ESR">ESR</option>
                  <option value="ESW">ESW</option>
                </Field>
              </div>
            )}
            {esrMode && isCreate && (!serviceCreationMode || values.service_source === 'ESR') && (
              <div className="col-span-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <label htmlFor="external_order_search" className="mb-2 block text-sm font-semibold text-slate-700">
                  {serviceCreationMode ? 'Search ESR Service' : 'Search ESR Order'}
                </label>
                <input
                  id="external_order_search"
                  type="text"
                  className="form-input"
                  value={externalOrderSearch}
                  placeholder="Order Number"
                  onChange={(event) => {
                    setExternalOrderSearch(event.target.value)
                    if (externalOrderSearchStatus !== 'idle') {
                      setExternalOrderSearchStatus('idle')
                      setExternalOrderSearchMessage('')
                    }
                  }}
                />
                {externalOrderSearchMessage && (
                  <p className={`mt-2 text-sm ${externalOrderSearchStatus === 'error' ? 'text-danger' : 'text-emerald-600'}`}>
                    {externalOrderSearchMessage}
                  </p>
                )}
              </div>
            )}
            {esrMode && isCreate && serviceCreationMode && values.service_source === 'ESW' && (
              <div className="col-span-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <label className="mb-2 block text-sm font-semibold text-slate-700">
                  Search BOS Order
                </label>
                <OrderGlobalSearch
                  origin="service_control"
                  modules={BOS_ORDER_MODULES}
                  defaultModule="service_control"
                  onSelectOrder={(orderId, order) => {
                    setFieldValue('parent_order_id', orderId)
                    setSelectedBosOrder(order ?? null)
                    void loadBosOrderPrefill(orderId)
                  }}
                />
                {selectedBosOrder && (
                  <div className="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-sm">
                    <div>
                      <p className="font-semibold text-slate-700">{selectedBosOrder.name ?? 'BOS Order'}</p>
                      <p className="text-xs text-slate-500">{selectedBosOrder.client ?? 'No client'} · {selectedBosOrder.status ?? 'No status'}</p>
                    </div>
                    <button
                      type="button"
                      className="btn btn-outline-secondary btn-sm"
                      onClick={() => {
                        setFieldValue('parent_order_id', null)
                        setSelectedBosOrder(null)
                      }}
                    >
                      Clear
                    </button>
                  </div>
                )}
              </div>
            )}
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
                // setFieldValue('status', '') // Reset status when order type changes
                setFieldValue('client_id', null) // Reset client when order type changes
                setFieldValue('company_contact_id', null) // Reset company contact when order type changes
                setFieldValue('company_source_id', null) // Reset company source when order type changes
                setFieldValue('associate_company_contact_id_1', null) // Reset associate company contact 1 when order type changes
                setFieldValue('associate_company_contact_id_2', null) // Reset associate company contact 2 when order type changes
                setFieldValue('associate_company_contact_id_3', null)
                setFieldValue('associate_company_contact_id_4', null)
                setFieldValue('associate_client_id_1', null) // Reset associate client 1 when order type changes
                setFieldValue('associate_client_id_2', null) // Reset associate client 2 when order type changes
                setFieldValue('associate_client_id_3', null)
                setFieldValue('associate_client_id_4', null)
                setFieldValue('associate_source_id_1', null) // Reset associate source 1 when order type changes
                setFieldValue('associate_source_id_2', null) // Reset associate source 2 when order type changes
                setFieldValue('associate_source_id_3', null)
                setFieldValue('associate_source_id_4', null)
                setFieldValue('client_email_selection', NO_CLIENT_EMAIL_SELECTION)
              }}
            >
              <option value="">Order Type</option>
              {order_types.map((order_type, index) => (
                <option key={index} value={order_type}>{order_type}</option>
              ))}
            </Field>
            {(submitCount && errors.order_type) ? <InputError message={errors.order_type} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.product_line) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="product_line">Product Line</label>
              <Field
                id="product_line"
                name="product_line"
                className="form-select"
                as="select"
                onChange={(event: React.ChangeEvent<HTMLSelectElement>) => {
                  setFieldValue('product_line', event.target.value)
                  setExternalOrderSearch('')
                  setExternalOrderSearchStatus('idle')
                  setExternalOrderSearchMessage('')
                }}
              >
                <option value="">Product Line</option>
                {PRODUCT_LINES.map((productLine) => (
                  <option key={productLine} value={productLine}>{productLine}</option>
                ))}
              </Field>
              {(submitCount && errors.product_line) ? <InputError message={errors.product_line} className="mt-2" /> : ''}
            </div>
            {esrMode && (
              <div className={submitCount ? ((errors as any).service ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="service">Service</label>
                <Field id="service" name="service" className="form-select" as="select">
                  <option value="">Service</option>
                  {services.map((service) => (
                    <option key={service} value={service}>{service}</option>
                  ))}
                </Field>
                {(submitCount && (errors as any).service) ? <InputError message={(errors as any).service} className="mt-2" /> : null}
              </div>
            )}
            {esrMode && (
              <div className="col-span-4">
                <label className="mb-2 block text-sm font-semibold text-slate-700">ESR Options</label>
                <div className="grid gap-2 sm:grid-cols-4">
                  {[
                    { field: 'esr_design', label: 'Design' },
                    { field: 'esr_express', label: 'EXPRESS' },
                    { field: 'esr_reylos_glass', label: 'Reylos Glass' },
                    { field: 'esr_service', label: 'Service' }
                  ].map((option) => {
                    const isServiceOption = serviceCreationMode && option.field === 'esr_service'
                    const checked = isServiceOption ? true : Boolean((values as any)[option.field])

                    return (
                      <label
                        key={option.field}
                        className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm font-semibold transition ${
                          isServiceOption
                            ? 'border-[#2c7df6] bg-blue-50 text-[#1f5fbf]'
                            : checked
                            ? 'border-[#2c7df6] bg-blue-50 text-[#1f5fbf]'
                            : 'border-[#e0e6ed] bg-white text-slate-600 hover:border-[#2c7df6]/60'
                        }`}
                      >
                        <input
                          type="checkbox"
                          className="form-checkbox"
                          checked={checked}
                          disabled={isServiceOption}
                          onChange={(event) => {
                            if (isServiceOption) {
                              setFieldValue(option.field, true)
                              return
                            }
                            setFieldValue(option.field, event.target.checked)
                          }}
                        />
                        <span>{option.label}</span>
                      </label>
                    )
                  })}
                </div>
              </div>
            )}
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
              {showInvoiceField && (
                <div className={submitCount ? (errors.invoice_number ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="invoice_number">Invoice Number</label>
                  <Field
                    id="invoice_number"
                    name="invoice_number"
                    type="text"
                    className="form-input"
                    autoComplete="invoice_number"
                    placeholder="Invoice Number"
                  />
                  {(submitCount && errors.invoice_number) ? <InputError message={errors.invoice_number as string} className="mt-2" /> : ''}
                </div>
              )}
              {esrMode && (
                <>
                  <div className={submitCount ? (errors.status ? 'has-error' : 'has-success') : ''}>
                    <label htmlFor="status">Status</label>
                    <Field id="status" name="status" className="form-select" as="select">
                      {isCreate
                        ? status.map((statusOption) => (
                          <option key={statusOption} value={statusOption}>{statusOption}</option>
                        ))
                        : <option value={values.status ?? ''}>{values.status ?? ''}</option>}
                    </Field>
                    {(submitCount && errors.status) ? <InputError message={errors.status} className="mt-2" /> : null}
                  </div>
                  <div className={submitCount ? (errors.order_number ? 'has-error' : 'has-success') : ''}>
                    <label htmlFor="order_number">Order Number</label>
                    <Field
                      id="order_number"
                      name="order_number"
                      className="form-input"
                      placeholder="Order Number"
                    />
                    {(submitCount && errors.order_number) ? <InputError message={errors.order_number} className="mt-2" /> : null}
                  </div>
                </>
              )}
              {showProjectAmountField && (
                <div className={`col-span-2 ${submitCount ? (errors.project_amount ? 'has-error' : 'has-success') : ''}`}>
                  <label htmlFor="project_amount" className="mb-1">Project Amount</label>
                  <div className="flex flex-wrap items-center gap-3 sm:flex-nowrap">
                    <Field
                      id="project_amount"
                      name="project_amount"
                      className="form-input min-w-[14rem] flex-1 text-right"
                      autoComplete="project_amount"
                      placeholder="Project Amount"
                      type="number"
                      disabled={isProjectAmountLocked}
                    />
                    {canShowChangeOrderFields && (
                      <div className="inline-flex shrink-0 items-center whitespace-nowrap">
                        <Field
                          id="change_order_enabled"
                          name="change_order_enabled"
                          className="form-checkbox"
                          type="checkbox"
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
                        <label htmlFor="change_order_enabled" className="mb-0 ml-2">Change Order</label>
                      </div>
                    )}
                  </div>
                  {(submitCount && errors.project_amount)
                    ? <InputError message={errors.project_amount} className="mt-2" />
                    : null}
                  {projectAmountLockedMessage && (
                    <div className="mt-1 text-xs text-slate-500">
                      {projectAmountLockedMessage}
                    </div>
                  )}
                  {(submitCount && errors.change_order_enabled) ? <div className="block"><InputError message={errors.change_order_enabled as string} className="mt-2" /></div> : ''}
                </div>
              )}
              {canShowChangeOrderFields && !showProjectAmountField && (
                <div className={submitCount ? (errors.change_order_enabled ? 'has-error inline-flex flex-col' : 'has-success inline-flex') : 'inline-flex items-end'}>
                  <div className="flex">
                    <Field
                      id="change_order_enabled"
                      name="change_order_enabled"
                      className="form-checkbox"
                      type="checkbox"
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
                  {(submitCount && errors.change_order_enabled) ? <div className="block"><InputError message={errors.change_order_enabled as string} className="mt-2" /></div> : ''}
                </div>
              )}
              {canShowChangeOrderFields && values.change_order_enabled && (
                <>
                  <div className={submitCount ? (errors.change_order_amount ? 'has-error' : 'has-success') : ''}>
                    <label htmlFor="change_order_amount">Change Order Price</label>
                    <Field
                      id="change_order_amount"
                      name="change_order_amount"
                      className="form-input text-right"
                      autoComplete="change_order_amount"
                      placeholder="Change Order Price"
                      type="number"
                    />
                    {(submitCount && errors.change_order_amount) ? <InputError message={errors.change_order_amount as string} className="mt-2" /> : ''}
                  </div>
                  <div className={submitCount ? (errors.change_order_note ? 'has-error' : 'has-success') : ''}>
                    <label htmlFor="change_order_note">Change Order Note</label>
                    <Field
                      id="change_order_note"
                      name="change_order_note"
                      className="form-input"
                      autoComplete="change_order_note"
                      placeholder="Change Order Note"
                      type="text"
                    />
                    {(submitCount && errors.change_order_note) ? <InputError message={errors.change_order_note as string} className="mt-2" /> : ''}
                  </div>
                </>
              )}
              <div className={`${useModalLayout ? 'col-span-2' : ''} ${submitCount ? (errors.job_address ? 'has-error' : 'has-success') : ''}`}>
                  <label htmlFor="job_address"> Job Address</label>
                    <Field
                      id="job_address"
                      name="job_address"
                      className="form-input placeholder:text-white-dark"
                      autoComplete="off"
                      placeholder="Address"
                      innerRef={jobAddressInputRef}
                      onBlur={(e: FocusEvent<HTMLInputElement>) => {
                        const value = e.target.value ?? ''
                        if (value.trim()) {
                          syncAddressFromString(value)
                        }
                      }}
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setFieldValue('job_address', e.target.value)
                      }}
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
                {showOwnerField && esrMode && (
                <div>
                  <label className="text-xs font-semibold uppercase tracking-wide text-slate-500">Owners</label>
                  <div className="mt-2 rounded-lg border border-slate-200 p-1">
                    <Select
                      isMulti
                      className="owners-select"
                      placeholder="Select owners"
                      value={selectedOwners}
                      onChange={(selection) => {
                        const values = Array.isArray(selection) ? selection.map(option => (option as any)?.value) : []
                        setFieldValue('owner_ids', values)
                      }}
                      options={ownerOptions}
                      styles={{ control: (base) => ({ ...base, minHeight: '40px', border: 'none', boxShadow: 'none' }) }}
                    />
                  </div>
                  {externalOwnerWarning && (
                    <div className="mt-2 rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-sm font-medium text-yellow-800">
                      {externalOwnerWarning}
                    </div>
                  )}
                  {(submitCount && errors.owner_ids) ? <InputError message={(errors.owner_ids as any) ?? null} className="mt-2" /> : null}
                </div>
              )}
                {showClientField && !isCommercial && (
                  <div className={submitCount ? (errors.client_id ? 'has-error' : 'has-success') : ''}>
                    <label htmlFor="client_id">Contact Name</label>
                    <div className="flex items-center">
                      <div className="flex-grow">
                        <Select
                          id="client_id"
                          inputId="client_id"
                          name="client_id"
                          placeholder="Client"
                          value={selectedClient}
                          isMulti={false}
                          onChange={(option) => {
                            const clientId = (option as any)?.value ?? null
                            setFieldValue('client_id', clientId)
                            if (clientId == null) {
                              setFieldValue('company_contact_id', null)
                              return
                            }
                            const c = clientsList.find(x => Number(x.id) === clientId)
                            const companyId = c?.company_contact_id != null ? Number(c.company_contact_id) : null
                            setFieldValue('company_contact_id', companyId)
                          }}
                          options={clientsOptions}
                          styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                        />
                      </div>
                      <button
                        type="button"
                        title="Create Client"
                        onClick={() => { setModalTargetClientField('client_id'); setShowClientModal(true) }}
                        className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                      >
                        <PlusIcon className="text-[#fff]" />
                      </button>
                    </div>
                    {(submitCount && errors.client_id) ? <InputError message={errors.client_id} className="mt-2" /> : null}
                  </div>
                )}
                  {!esrMode && <div className={submitCount ? (errors.schedule_appointment) ? 'has-error' : 'has-success' : ''}>
                           <label htmlFor="schedule_appointment">Appointment Date</label>
                           <Flatpickr
                             options={{
                               enableTime: true,
                               dateFormat: 'Y-m-d H:i',
                               clickOpens: !appointmentDateReadOnly
                             }}
                             name="schedule_appointment"
                             className="form-input"
                             value={values.schedule_appointment ?? ''}
                             disabled={appointmentDateReadOnly}
                             onChange={([date]) => {
                               if (appointmentDateReadOnly || !date) return
                               const year = date.getFullYear()
                               const month = String(date.getMonth() + 1).padStart(2, '0') // Meses empiezan en 0
                               const day = String(date.getDate()).padStart(2, '0')
                               const hours = String(date.getHours()).padStart(2, '0')
                               const minutes = String(date.getMinutes()).padStart(2, '0')
                               // Combinar en el formato deseado
                               const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}`
                               setFieldValue('schedule_appointment', formattedDate)
                             }}
                           />
                  {(submitCount && typeof errors.schedule_appointment === 'string') ? <InputError message={errors.schedule_appointment} className="mt-2" /> : ''}
                         </div>}
                  {isCommercial && !esrMode && (
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
                          setFieldValue('bid_due_date', date ? formatDateOnlyValue(date) : null)
                        }}
                      />
                      {(submitCount && errors.bid_due_date) ? <InputError message={errors.bid_due_date.toString()} className="mt-2" /> : ''}
                    </div>
                  )}
             {/* <div className={submitCount ? (errors.source) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="source">Clients Source</label>
              <Select
                id='source'
                placeholder="sources"
                name='source'
                defaultValue={selectedSourceClients}
                isMulti={false}
                onChange={(value) => { setFieldValue('source', value) }}
                options={sourcesClients.map((source) => { return { label: source, value: source } })}
              />
              {(submitCount && errors.source) ? <InputError message={errors.source} className="mt-2" /> : ''}
            </div> */}
               {(values.order_type === ORDER_TYPES.COMMERCIAL) && (
               <div className="col-span-4 space-y-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                      <div className="text-sm font-semibold text-slate-700">Companies</div>
                      <div className="text-xs text-slate-500">Add up to 5 companies and pick a contact per company.</div>
                    </div>
                    {showAddCommercialCompanyButton && (
                      <button
                        type="button"
                        className={`btn btn-primary ${commercialPairs.length >= 3 ? 'opacity-50 cursor-not-allowed' : ''}`}
                        onClick={addCommercialPair}
                        disabled={commercialPairs.length >= 3}
                      >
                        Add Company
                      </button>
                    )}
                  </div>

                  {commercialPairs.map((pair, index) => {
                    const rowCompany = pair.companyId
                      ? companyOptions.find(o => o.value === pair.companyId) ?? null
                      : null
                    const rowClients = pair.companyId
                      ? clientsList.filter(c => clientBelongsToCompany(c, pair.companyId))
                      : []
                    const rowClientOptions = rowClients.map(c => ({ value: c.id, label: c.name }))
                    const rowClient = pair.clientId
                      ? rowClientOptions.find(o => o.value === pair.clientId) ?? null
                      : null
                    const rowSource = pair.sourceId
                      ? sourceOptions.find(o => o.value === pair.sourceId) ?? null
                      : null
                    const isCompanySelected = pair.companyId != null
                    const companyError = index === 0
                      ? errors.company_contact_id
                      : (errors as any)[`associate_company_contact_id_${index}`]
                    const sourceError = index === 0
                      ? (errors as any).company_source_id
                      : (errors as any)[`associate_source_id_${index}`]
                    const clientError = index === 0
                      ? errors.client_id
                      : (errors as any)[`associate_client_id_${index}`]

                    return (
                      <div key={`commercial-pair-${index}`} className="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="mb-4 flex items-center justify-between">
                          <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Company {index + 1}</div>
                          {commercialPairs.length > 1 && (
                            <button
                              type="button"
                              className="text-xs font-semibold text-red-600 hover:text-red-700"
                              onClick={() => { removeCommercialPair(index) }}
                            >
                              Remove
                            </button>
                          )}
                        </div>

                        <div className="grid gap-4 md:grid-cols-3">
                          <div className={submitCount ? (companyError ? 'has-error' : 'has-success') : ''}>
                            <label htmlFor={`company_contact_id_${index}`}>Company</label>
                            <div className="flex items-center">
                              <div className="flex-grow">
                                <Select
                                  inputId={`company_contact_id_${index}`}
                                  name={`company_contact_id_${index}`}
                                  placeholder="Company"
                                  value={rowCompany}
                                  isMulti={false}
                                  onChange={(option) => {
                                    const companyId = option ? Number((option as any).value) : null
                                    const clientId = firstClientIdForCompany(companyId)
                                    setCommercialPairs(prev => prev.map((item, i) => (
                                      i === index ? { companyId, clientId, sourceId: null } : item
                                    )))
                                  }}
                                  options={companyOptions}
                                  styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                                />
                              </div>
                              <button
                                type="button"
                                title="Create Company"
                                onClick={() => { setCompanyModalTargetIndex(index); setShowCompanyModal(true) }}
                                className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                              >
                                <PlusIcon className="text-[#fff]" />
                              </button>
                            </div>
                            {(submitCount && companyError)
                              ? <InputError message={companyError as any} className="mt-2" />
                              : null}
                            {index === 0 && externalCompanyWarning && (
                              <div className="mt-2 rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-sm font-medium text-yellow-800">
                                {externalCompanyWarning}
                              </div>
                            )}
                          </div>

                          {showClientField && (
                            <div className={submitCount ? (clientError ? 'has-error' : 'has-success') : ''}>
                              <label htmlFor={`client_id_${index}`}>Contact Name</label>
                              <div className="flex items-center">
                                <div className="flex-grow">
                                  <Select
                                    id={`client_id_${index}`}
                                    inputId={`client_id_${index}`}
                                    name={`client_id_${index}`}
                                    placeholder={isCompanySelected ? 'Client' : 'Select company first'}
                                    value={rowClient}
                                    isMulti={false}
                                    onChange={(option) => {
                                      const clientId = option ? Number((option as any).value) : null
                                      setCommercialPairs(prev => prev.map((item, i) => (
                                        i === index ? { ...item, clientId } : item
                                      )))
                                    }}
                                    options={rowClientOptions}
                                    isDisabled={!isCompanySelected}
                                    styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                                  />
                                </div>
                                <button
                                  type="button"
                                  title="Create Client"
                                  onClick={() => { setClientModalTargetIndex(index); setShowClientModal(true) }}
                                  disabled={!isCompanySelected}
                                  className={`bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b] ${!isCompanySelected ? 'opacity-50 cursor-not-allowed' : ''}`}
                                >
                                  <PlusIcon className="text-[#fff]" />
                                </button>
                              </div>
                              {(submitCount && clientError)
                                ? <InputError message={clientError as any} className="mt-2" />
                                : null}
                            </div>
                          )}
                          {showCommercialSourceField && <div className={`${showClientField ? '' : 'md:col-span-2'} ${submitCount ? (sourceError ? 'has-error' : 'has-success') : ''}`}>
                            <label htmlFor={`source_id_${index}`}>Source</label>
                            <Select
                              id={`source_id_${index}`}
                              inputId={`source_id_${index}`}
                              name={`source_id_${index}`}
                              placeholder="Source"
                              value={rowSource}
                              isMulti={false}
                              onChange={(option) => {
                                const sourceId = option ? Number((option as any).value) : null
                                setCommercialPairs(prev => prev.map((item, i) => (
                                  i === index ? { ...item, sourceId } : item
                                )))
                              }}
                              options={sourceOptions}
                              styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                            />
                            {(submitCount && sourceError)
                              ? <InputError message={sourceError as any} className="mt-2" />
                              : null}
                          </div>}
                        </div>
                      </div>
                    )
                  })}
                </div>
               )}

               <div className={`col-span-4 md:col-span-2 ${submitCount ? (errors.client_email_selection ? 'has-error' : 'has-success') : ''}`}>
                 <label htmlFor="client_email_selection">Client Email Delivery</label>
                 <Field
                   as="select"
                   id="client_email_selection"
                   name="client_email_selection"
                   className="form-select"
                 >
                   <option value={PRIMARY_CLIENT_EMAIL_SELECTION}>
                     {primaryClientEmailOption
                       ? `Primary client email (${primaryClientEmailOption.value})`
                       : 'Primary client email'}
                   </option>
                   {clientEmailOptions
                     .filter((option) => !option.is_primary)
                     .map((option) => (
                       <option key={option.value} value={option.value}>
                         {option.label}
                       </option>
                     ))}
                   <option value={NO_CLIENT_EMAIL_SELECTION}>Do not send client emails</option>
                 </Field>
                 {(submitCount && errors.client_email_selection)
                   ? <InputError message={errors.client_email_selection} className="mt-2" />
                   : null}
                 <div className="mt-1 text-xs text-slate-500">
                   If no alternate address is selected, emails for this order go to the client&apos;s primary email.
                 </div>
               </div>

               {/* <div className={submitCount ? ((errors as any).associate_company_contact_id_1 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_company_contact_id_1">Other Company Associate 1</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_company_contact_id_1"
                        name="associate_company_contact_id_1"
                        placeholder="Company"
                        value={selectedAssociateCompany1}
                        isMulti={false}
                        onChange={(option) => {
                          const companyId = option ? Number((option as any).value) : null
                          setFieldValue('associate_company_contact_id_1', companyId)

                          if (companyId == null) {
                            setFieldValue('associate_client_id_1', null)
                            return
                          }

                          const matches = clientsList.filter(c => Number(c.company_contact_id) === companyId)
                          setFieldValue('associate_client_id_1', matches.length > 0 ? Number(matches[0].id) : null)
                        }}
                        options={companyOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Company"
                      onClick={() => { setModalTargetField('associate_company_contact_id_1'); setShowCompanyModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_company_contact_id_1)
                    ? <InputError message={(errors as any).associate_company_contact_id_1} className="mt-2" />
                    : null}
                </div>
                <div className={submitCount ? ((errors as any).associate_client_id_1 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_client_id_1">Other Contact Associate 1</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_client_id_1"
                        inputId="associate_client_id_1"
                        name="associate_client_id_1"
                        placeholder="Client"
                        value={selectedAssociateClient1}
                        isMulti={false}
                         onChange={(option) => {
                           setFieldValue('associate_client_id_1', (option as any)?.value ?? null)
                         }}
                        options={clientsOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Client"
                      onClick={() => { setModalTargetClientField('associate_client_id_1'); setShowClientModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_client_id_1)
                    ? <InputError message={(errors as any).associate_client_id_1} className="mt-2" />
                    : null}
                </div>
                  <div className={submitCount ? ((errors as any).associate_company_contact_id_2 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_company_contact_id_2">Other Company Associate 2</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_company_contact_id_2"
                        name="associate_company_contact_id_2"
                        placeholder="Company"
                        value={selectedAssociateCompany2}
                        isMulti={false}
                        onChange={(option) => {
                          const companyId = option ? Number((option as any).value) : null
                          setFieldValue('associate_company_contact_id_2', companyId)

                          if (companyId == null) {
                            setFieldValue('associate_client_id_2', null)
                            return
                          }

                          const matches = clientsList.filter(c => Number(c.company_contact_id) === companyId)
                          setFieldValue('associate_client_id_2', matches.length > 0 ? Number(matches[0].id) : null)
                        }}
                        options={companyOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Company"
                      onClick={() => { setModalTargetField('associate_company_contact_id_2'); setShowCompanyModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_company_contact_id_2)
                    ? <InputError message={(errors as any).associate_company_contact_id_2} className="mt-2" />
                    : null}
                </div>
                <div className={submitCount ? ((errors as any).associate_client_id_2 ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="associate_client_id_2">Other Contact Associate 2</label>
                  <div className="flex items-center">
                    <div className="flex-grow">
                      <Select
                        id="associate_client_id_2"
                        inputId="associate_client_id_2"
                        name="associate_client_id_2"
                        placeholder="Client"
                        value={selectedAssociateClient2}
                        isMulti={false}
                        onChange={(option) => {
                          setFieldValue('associate_client_id_2', (option as any)?.value ?? null)
                        }}
                        options={clientsOptions}
                        styles={{ control: (base) => ({ ...base, minHeight: '40px' }) }}
                      />
                    </div>
                    <button
                      type="button"
                      title="Create Client"
                      onClick={() => { setModalTargetClientField('associate_client_id_2'); setShowClientModal(true) }}
                      className="bg-[#2c7df6] w-[44px] h-[40px] flex justify-center items-center ltr:rounded-r-md rtl:rounded-l-md border ltr:border-l-0 rtl:border-r-0 border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b]"
                    >
                      <PlusIcon className="text-[#fff]" />
                    </button>
                  </div>
                  {(submitCount && (errors as any).associate_client_id_2)
                    ? <InputError message={(errors as any).associate_client_id_2} className="mt-2" />
                    : null}
                </div> */}
             {/* <div className={submitCount ? (errors.status) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="status">Status</label>
              <Select
                id='status'
                placeholder="status"
                name='status'
                defaultValue={selectedStatus}
                isMulti={false}
                onChange={(value) => { setFieldValue('status', value) }}
                options={status.map((status) => { return { label: status, value: status } })}
              />
              {(submitCount && errors.status) ? <InputError message={errors.status} className="mt-2" /> : ''}
            </div> */}
              {!esrMode && <div className='flex mt-8'>
                <Field
                  id="is_supply"
                  name="is_supply"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('is_supply', e.target.checked)
                  }}
                />
                <label htmlFor="is_supply" className='font-bold inline-flex'>Supply</label>
              </div>}
              {/* <div className='col-span-4'>
              <label htmlFor="description"> Description</label>
              <Field
                id="description"
                name="description"
                component="textarea"
                rows="3"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Description'
              />
            </div> */}
            {showNotesField && !esrMode && (
            <div className={`col-span-4 ${submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}`}>
              <label htmlFor="notes"> Notes</label>
              <Field
                id="notes"
                  name="notes"
                  component="textarea"
                  rows="3"
                  className="form-textarea resize-none placeholder:text-white-dark"
                  placeholder='Notes'
                />
                {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
              </div>
            )}
            {showAttachmentsField && !esrMode && (
              <div className={`col-span-4 ${submitCount ? (errors.attachments ? 'has-error' : 'has-success') : ''}`}>
                <label htmlFor="attachments">Attachments</label>
                <input
                  id="attachments"
                  name="attachments"
                  type="file"
                  multiple
                  className="form-input"
                  onChange={(event) => {
                    setFieldValue('attachments', Array.from(event.currentTarget.files ?? []))
                  }}
                />
                {(submitCount && typeof errors.attachments === 'string')
                  ? <InputError message={errors.attachments} className="mt-2" />
                  : null}
                {Array.isArray(values.attachments) && values.attachments.length > 0 && (
                  <div className="mt-2 space-y-1 text-xs text-slate-500">
                    {values.attachments.map((attachment: any, index: number) => (
                      <div key={`${attachment.name ?? attachment.filename ?? 'attachment'}-${index}`}>
                        {attachment.name ?? attachment.filename ?? `Attachment ${index + 1}`}
                      </div>
                    ))}
                  </div>
                )}
                {attachmentsArray.length > 0 && (
                  <div className="mt-3 overflow-hidden rounded-lg border border-slate-200">
                    {attachmentsArray.map((attachment, index) => (
                      <div key={attachment.id} className="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 text-sm last:border-b-0">
                        <Link href={route('download.file', { id: attachment.id })} target="_blank" className="truncate font-medium text-sky-700 hover:text-sky-900">
                          {attachment.filename}
                        </Link>
                        <button
                          type="button"
                          className="rounded-md p-1 text-rose-500 transition hover:bg-rose-50 hover:text-rose-700"
                          onClick={() => { removeAttachmentProduct(index) }}
                          title="Delete attachment"
                        >
                          <DeleteIcon />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
            </div>
            {showOwnerField && !esrMode && (
              <div className="col-span-4 md:col-span-2 mt-4 md:mt-0">
                <label className="text-xs font-semibold uppercase tracking-wide text-slate-500">Owners</label>
                <div className="mt-2 rounded-lg border border-slate-200 p-1">
                  <Select
                    isMulti
                    className="owners-select"
                    placeholder="Select owners"
                    value={selectedOwners}
                    onChange={(selection) => {
                      const values = Array.isArray(selection) ? selection.map(option => (option as any)?.value) : []
                      setFieldValue('owner_ids', values)
                    }}
                    options={ownerOptions}
                    styles={{ control: (base) => ({ ...base, minHeight: '40px', border: 'none', boxShadow: 'none' }) }}
                  />
                </div>
                {(submitCount && errors.owner_ids) ? <InputError message={(errors.owner_ids as any) ?? null} className="mt-2" /> : null}
              </div>
            )}
        </fieldset>
        {showPaymentInformationSection && (
          <fieldset className='p-3 border rounded-xl'>
            <legend className='text-lg font-semibold px-3'>Payment Information</legend>
            <div className='grid gap-4 grid-cols-3'>
              <div className={submitCount ? (errors.method_of_payment ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="method_of_payment">Project Payment Method</label>
                <Field
                  id="method_of_payment"
                  name="method_of_payment"
                  className="form-select"
                  autoComplete="method_of_payment"
                  as="select"
                  disabled={isScheduleLockedByPayments}
                  onChange={(e: { target: { value: string } }) => {
                    const nextMethod = e.target.value
                    const isCashAndFinanced = nextMethod === PAYMENT_METHODS.CASH_AND_FINANCE
                    const keepsFinancing = nextMethod === PAYMENT_METHODS.FINANCED || isCashAndFinanced
                    setFieldValue('method_of_payment', nextMethod)
                    if (!keepsFinancing) {
                      setFieldValue('type_of_financing', '')
                    }
                    if (!isCashAndFinanced) {
                      setFieldValue('down_payment', null)
                    }
                    if (isCashAndFinanced) {
                      setFieldValue('payment_schedule_type', CUSTOM_SCHEDULE_TYPE)
                      setFieldValue('custom_schedule', buildCustomSchedule(values.custom_schedule))
                    } else {
                      setFieldValue('payment_schedule_type', '')
                      setFieldValue('custom_schedule', buildCustomSchedule())
                    }
                  }}
                >
                  <option value="">Method of Payment</option>
                  {methodsOfPayment.map((method) => (
                    <option key={method} value={method}>{method}</option>
                  ))}
                </Field>
                {(submitCount && errors.method_of_payment) ? <InputError message={errors.method_of_payment} className="mt-2" /> : ''}
              </div>

              {hasSchedulePaymentMethod && (
                <div className={submitCount ? (errors.payment_schedule_type ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="payment_schedule_type">Payment Schedule</label>
                  <Field
                    id="payment_schedule_type"
                    name="payment_schedule_type"
                    className="form-select"
                    autoComplete="payment_schedule_type"
                    as="select"
                    disabled={isScheduleLockedByPayments || isCashAndFinancedPaymentMethod}
                    onChange={(e: { target: { value: string } }) => {
                      const nextType = e.target.value
                      setFieldValue('payment_schedule_type', nextType)
                      setFieldValue('custom_schedule', nextType === CUSTOM_SCHEDULE_TYPE ? buildCustomSchedule(values.custom_schedule) : buildCustomSchedule())
                    }}
                  >
                    <option value="">
                      {isCashAndFinancedPaymentMethod ? 'CUSTOMIZED' : 'Select Payment Schedule'}
                    </option>
                    {scheduleOptions.map((type) => (
                      <option key={type} value={type}>{type}</option>
                    ))}
                  </Field>
                  {(submitCount && errors.payment_schedule_type) ? <InputError message={errors.payment_schedule_type} className="mt-2" /> : ''}
                </div>
              )}

              {(isFinancedPaymentMethod || isCashAndFinancedPaymentMethod) && (
                <div className={submitCount ? (errors.type_of_financing ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="type_of_financing">Type Of Financing</label>
                  <Field
                    id="type_of_financing"
                    name="type_of_financing"
                    className="form-select"
                    autoComplete="type_of_financing"
                    as="select"
                    disabled={isScheduleLockedByPayments}
                  >
                    <option value="">Type Of Financing</option>
                    {financingOptions.map((financing) => (
                      <option key={financing} value={financing}>{financing}</option>
                    ))}
                  </Field>
                  {(submitCount && errors.type_of_financing) ? <InputError message={errors.type_of_financing} className="mt-2" /> : ''}
                </div>
              )}

              {isCashAndFinancedPaymentMethod && (
                <div className={submitCount ? (errors.down_payment ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="down_payment">Cash Amount</label>
                  <Field
                    id="down_payment"
                    name="down_payment"
                    className="form-input text-right"
                    autoComplete="down_payment"
                    placeholder='Cash Amount'
                    type='number'
                    disabled={isScheduleLockedByPayments}
                  />
                  {(submitCount && errors.down_payment) ? <InputError message={errors.down_payment} className="mt-2" /> : ''}
                </div>
              )}

              {isCashAndFinancedPaymentMethod && (
                <div>
                  <label htmlFor="amount_to_finance">Amount to Finance</label>
                  <input
                    id="amount_to_finance"
                    name="amount_to_finance"
                    className="form-input text-right bg-slate-100"
                    value={financedAmountValue != null ? String(financedAmountValue.toFixed(2)) : ''}
                    placeholder='Amount to Finance'
                    type='text'
                    readOnly
                  />
                </div>
              )}

              {canEditScheduleInForm && isCustomSchedule && (
                <div className="col-span-3 space-y-3 rounded-md border border-[#e0e6ed] bg-white p-3 dark:border-[#1b2e4b]">
                  <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-[#5c6370]">
                    <span>Custom schedule</span>
                    <span className={customTotalClass}>
                      Total: {formatCurrency(customScheduleTotal)}
                      {hasScheduleTargetAmount ? ` / ${formatCurrency(scheduleTargetAmount)}` : ''}
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
                          disabled={isScheduleLockedByPayments}
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
                          disabled={isScheduleLockedByPayments}
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
                  {schedulePreviewItems.length > 0
                    ? (
                      <div className="rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b]">
                        <div className="flex flex-wrap gap-6 px-3 py-2 text-sm text-[#5c6370]">
                          <div>
                            <span className="font-semibold text-[#1f2937]">Type:</span>{' '}
                            {values.payment_schedule_type || '--'}
                          </div>
                          <div>
                            <span className="font-semibold text-[#1f2937]">Total:</span>{' '}
                            {hasEffectiveScheduleTargetAmount ? formatCurrency(effectiveScheduleTargetAmount) : '--'}
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
                      )
                    : (
                      <div className="text-sm text-[#5c6370]">
                        No payment schedule template available for this selection.
                      </div>
                      )}
                </div>
              )}

              {isScheduleLockedByPayments && values.payment_schedule && (
                <div className="col-span-3">
                  <label className="mb-1 block text-sm font-medium text-[#5c6370]">
                    Recorded Payments
                  </label>
                  <div className="rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b]">
                    <div className="flex flex-wrap gap-6 px-3 py-2 text-sm text-[#5c6370]">
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
                      <div>
                        <span className="font-semibold text-[#1f2937]">Credit:</span>{' '}
                        {formatCurrency(values.payment_schedule.credit_amount ?? 0)}
                      </div>
                    </div>
                    {recordedSchedulePayments.length > 0
                      ? (
                        <div className="border-t border-[#e0e6ed] dark:border-[#1b2e4b]">
                          <div className="grid grid-cols-2 md:grid-cols-6 gap-3 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-[#5c6370]">
                            <span>Installment</span>
                            <span>Planned</span>
                            <span>Paid</span>
                            <span>Balance</span>
                            <span>Due Date</span>
                            <span>Status</span>
                          </div>
                          {recordedSchedulePayments.map((installment) => (
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
                        )
                      : (
                        <div className="border-t border-[#e0e6ed] px-3 py-2 text-sm text-[#5c6370] dark:border-[#1b2e4b]">
                          No recorded payment movements yet.
                        </div>
                        )}
                  </div>
                </div>
              )}
            </div>
          </fieldset>
        )}
        {esrMode && (showNotesField || showAttachmentsField) && (
          <fieldset className='p-3 border rounded-xl'>
            <legend className='text-lg font-semibold px-3'>Additional Information</legend>
            <div className='grid gap-4 grid-cols-4'>
              {showNotesField && (
                <div className={`col-span-4 ${submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}`}>
                  <label htmlFor="notes"> Notes</label>
                  <Field
                    id="notes"
                    name="notes"
                    component="textarea"
                    rows="3"
                    className="form-textarea resize-none placeholder:text-white-dark"
                    placeholder='Notes'
                  />
                  {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
                </div>
              )}
              {showAttachmentsField && (
                <div className={`col-span-4 ${submitCount ? (errors.attachments ? 'has-error' : 'has-success') : ''}`}>
                  <label htmlFor="attachments">Attachments</label>
                  <input
                    id="attachments"
                    name="attachments"
                    type="file"
                    multiple
                    className="form-input"
                    onChange={(event) => {
                      setFieldValue('attachments', Array.from(event.currentTarget.files ?? []))
                    }}
                  />
                  {(submitCount && typeof errors.attachments === 'string')
                    ? <InputError message={errors.attachments} className="mt-2" />
                    : null}
                  {Array.isArray(values.attachments) && values.attachments.length > 0 && (
                    <div className="mt-2 space-y-1 text-xs text-slate-500">
                      {values.attachments.map((attachment: any, index: number) => (
                        <div key={`${attachment.name ?? attachment.filename ?? 'attachment'}-${index}`}>
                          {attachment.name ?? attachment.filename ?? `Attachment ${index + 1}`}
                        </div>
                      ))}
                    </div>
                  )}
                  {attachmentsArray.length > 0 && (
                    <div className="mt-3 overflow-hidden rounded-lg border border-slate-200">
                      {attachmentsArray.map((attachment, index) => (
                        <div key={attachment.id} className="flex items-center justify-between gap-3 border-b border-slate-100 px-3 py-2 text-sm last:border-b-0">
                          <Link href={route('download.file', { id: attachment.id })} target="_blank" className="truncate font-medium text-sky-700 hover:text-sky-900">
                            {attachment.filename}
                          </Link>
                          <button
                            type="button"
                            className="rounded-md p-1 text-rose-500 transition hover:bg-rose-50 hover:text-rose-700"
                            onClick={() => { removeAttachmentProduct(index) }}
                            title="Delete attachment"
                          >
                            <DeleteIcon />
                          </button>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          </fieldset>
        )}
        {!esrMode && <fieldset className='p-3 border rounded-xl'>
        <legend className='text-lg font-semibold px-3'>Sales Information</legend>
        <div className='grid grid-cols-1 gap-4'>
        <fieldset className='p-3 border rounded-xl w-full'>
          <legend className='text-sm font-semibold px-3'>Type of Work and / or Service</legend>
          <div className="flex items-center gap-6 mt-4">
            <div className="flex items-center gap-2">
                <Field
                  id="sale"
                  name="sale"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('sale', e.target.checked)
                  }}
                />
                <label htmlFor="sale" className='font-bold inline-flex'>Sale</label>
              </div>
             <div className="flex items-center gap-2">
                <Field
                  id="installation"
                  name="installation"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('installation', e.target.checked)
                  }}
                />
                <label htmlFor="installation" className='font-bold inline-flex'>Installation</label>
            </div>
            <div className="flex items-center gap-2">
                <Field
                  id="permit"
                  name="permit"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('permit', e.target.checked)
                  }}
                />
                <label htmlFor="permit" className='font-bold inline-flex'>Permit</label>
            </div>
             <div className={submitCount ? (errors.hoa ? 'has-error' : 'has-success') : ''}>
            <div className="flex items-center gap-2 mt-4 md:mt-0">
              <Field
                id="hoa"
                name="hoa"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('hoa', e.target.checked)
                }}
              />
              <label htmlFor="hoa" className='font-bold inline-flex'>HOA</label>
            </div>
            {(submitCount && errors.hoa && typeof errors.hoa === 'string')
              ? <InputError message={errors.hoa} className="mt-2" />
              : null}
          </div>
           {/* <div className="flex items-center gap-2">
                <Field
                  id="replacement"
                  name="replacement"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('replacement', e.target.checked)
                  }}
                />
              <label htmlFor="replacement" className='font-bold inline-flex'>Replacement</label>
          </div> */}
          {/* <div className="flex items-center gap-2">
                <Field
                  id="new_construction"
                  name="new_construction"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('new_construction', e.target.checked)
                  }}
                />
              <label htmlFor="new_construction" className='font-bold inline-flex'>New Construction</label>
          </div> */}
          <div className="flex items-center gap-2">
                <Field
                  id="financing"
                  name="financing"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('financing', e.target.checked)
                  }}
                />
              <label htmlFor="financing" className='font-bold inline-flex'>Financing</label>
          </div>
          </div>
        </fieldset>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
        </div>
       <fieldset className="p-3 border rounded-xl w-full col-start-1 mt-4">
          <legend className="text-sm font-semibold px-3"> Project Specifications</legend>
          <div className="grid grid-cols-6 gap-6 mt-4">
            <div className={submitCount ? (errors.frame_color) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="frame_color">Frame Color</label>
            <Field
              id="frame_color"
              name="frame_color"
              className="form-select"
              autoComplete="frame_color"
              placeholder='frame_color'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('frame_color', e.target.value)
              }}
            >
              <option value="">Frame Color</option>
              {frame_colors?.map((frame_color, index) => (
                <option key={index} value={frame_color}>{frame_color}</option>
              ))}
            </Field>
            {(submitCount && errors.frame_color) ? <InputError message={errors.frame_color} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.glass_color) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="glass_color">Glass Color</label>
            <Field
              id="glass_color"
              name="glass_color"
              className="form-select"
              autoComplete="glass_color"
              placeholder='glass_color'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('glass_color', e.target.value)
              }}
            >
              <option value="">Glass Color</option>
              {glass_colors?.map((glass_color, index) => (
                <option key={index} value={glass_color}>{glass_color}</option>
              ))}
            </Field>
            {(submitCount && errors.glass_color) ? <InputError message={errors.glass_color} className="mt-2" /> : ''}
            </div>

            
          <div className={submitCount ? (errors.language ? 'has-error' : 'has-success') : ''}>
            <label htmlFor="language">Language</label>
            <Field
              id="language"
              name="language"
              className="form-select"
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('language', e.target.value)
              }}
            >
              <option value="">Select Language</option>
              {languages.map((language) => (
                <option key={language} value={language}>
                  {language}
                </option>
              ))}
            </Field>
            {(submitCount && errors.language && typeof errors.language === 'string')
              ? <InputError message={errors.language} className="mt-2" />
              : null}
          </div>
          {/* <div className={submitCount ? (errors.glass_color) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="glass_type">Glass Type</label>
            <Field
              id="glass_type"
              name="glass_type"
              className="form-select"
              autoComplete="glass_type"
              placeholder='glass_type'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('glass_type', e.target.value)
              }}
            >
              <option value="">Glass Type</option>
              {glass_types?.map((glass_type, index) => (
                <option key={index} value={glass_type}>{glass_type}</option>
              ))}
            </Field>
            {(submitCount && errors.glass_type) ? <InputError message={errors.glass_type} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.glass_coating) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="glass_coating">Glass Coating</label>
            <Field
              id="glass_coating"
              name="glass_coating"
              className="form-select"
              autoComplete="glass_coating"
              placeholder='glass_coating'
              as="select"
              onChange={(e: { target: { value: string } }) => {
                setFieldValue('glass_coating', e.target.value)
              }}
            >
              <option value="">Glass Coating</option>
              {glass_coatings?.map((glass_coating, index) => (
                <option key={index} value={glass_coating}>{glass_coating}</option>
              ))}
            </Field>
            {(submitCount && errors.glass_coating) ? <InputError message={errors.glass_coating} className="mt-2" /> : ''}
            </div> */}
             <div className={submitCount ? (errors.door_quantity) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="door_quantity">Door Quantity</label>
              <Field
                id="door_quantity"
                name="door_quantity"
                className="form-input text-right"
                autoComplete="door_quantity"
                placeholder='Door Quantity'
                type='number'
              />
              {(submitCount && errors.door_quantity) ? <InputError message={errors.door_quantity} className="mt-2" /> : ''}
            </div>
             <div className={submitCount ? (errors.window_quantity) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="window_quantity">Window Quantity</label>
                <Field
                  id="window_quantity"
                  name="window_quantity"
                  className="form-input text-right"
                  autoComplete="window_quantity"
                  placeholder='Window Quantity'
                  type='number'
                />
                {(submitCount && errors.window_quantity) ? <InputError message={errors.window_quantity} className="mt-2" /> : ''}
              </div>
             {/* <div className="flex items-center gap-2">
                <Field
                  id="screen"
                  name="screen"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('screen', e.target.checked)
                  }}
                />
              <label htmlFor="screen" className='font-bold inline-flex'>Screen</label>
            </div>
            <div className="flex items-center gap-2">
                <Field
                  id="door_design"
                  name="door_design"
                  className="form-checkbox"
                  type='checkbox'
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                    setFieldValue('door_design', e.target.checked)
                  }}
                />
              <label htmlFor="door_design" className='font-bold inline-flex'>Door Design</label>
            </div>
            <div className="flex items-center gap-2">
              <Field
                id="mountin"
                name="mountin"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('mountin', e.target.checked)
                }}
              />
            <label htmlFor="mountin" className='font-bold inline-flex'>Mounting</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="bar"
                name="bar"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('bar', e.target.checked)
                }}
              />
            <label htmlFor="bar" className='font-bold inline-flex'>Bar</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="shutter_hole"
                name="shutter_hole"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('shutter_hole', e.target.checked)
                }}
              />
            <label htmlFor="shutter_hole" className='font-bold inline-flex'>Shutter Hole</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="floor_cutting"
                name="floor_cutting"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('floor_cutting', e.target.checked)
                }}
              />
            <label htmlFor="floor_cutting" className='font-bold inline-flex'>Floor Cutting</label>
          </div>
          <div className="flex items-center gap-2">
              <Field
                id="interior_finish"
                name="interior_finish"
                className="form-checkbox"
                type='checkbox'
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setFieldValue('interior_finish', e.target.checked)
                }}
              />
            <label htmlFor="interior_finish" className='font-bold inline-flex'>Interior Finish</label>
          </div> */}
            </div>
        </fieldset>
        </div>
        </fieldset>}
        {!hideActions && (useModalLayout ? (
          <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
            <button
              type="button"
              onClick={() => { if (onCancel) onCancel() }}
              className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="rounded-lg bg-sky-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
            >
              {submitLabel ?? (isCreate ? 'Create' : 'Save')}
            </button>
          </div>
        ) : (
          <div className="flex items-center justify-between mt-4">
            {onCancel
              ? (
                <button
                  type="button"
                  className="btn btn-danger uppercase"
                  onClick={onCancel}
                >
                  Cancel
                </button>
                )
              : (
                <Link className='btn btn-danger uppercase' href={route('frontdesk.index')}>Cancel</Link>
                )}
            <PrimaryButton className="btn btn-primary" type='submit'>
              {submitLabel ?? (isCreate ? 'Create' : 'Save')}
            </PrimaryButton>
          </div>
        ))}
        <CompanyModal
          showModal={showCompanyModal}
          onClose={() => { setShowCompanyModal(false); setCompanyModalTargetIndex(null) }}
          onConfirm={onCompanyCreated}
          storeRoute={companyStoreRoute}
        />
        <ClientModal
          showModal={showClientModal}
          // onClose={() => { setShowClientModal(false) }}
           onClose={() => { setShowClientModal(false); setModalTargetClientField(null); setClientModalTargetIndex(null) }}
           onConfirm={onClientCreated}
          // addClient={addClient}
          sourcesClients={sourcesClients}
          orderType={values.order_type}
          storeRoute={clientStoreRoute}
      />
      </Form>
    </>
  )
}

export default OrderQualifiedForm
