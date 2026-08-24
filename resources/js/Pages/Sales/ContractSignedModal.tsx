import InputError from '@/Components/InputError'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { Formik, Form } from 'formik'
import { PAYMENT_METHODS, PRODUCT_LINES } from '@/Utils/constants'

interface CustomScheduleItem {
  label: string
  amount: string
}

interface ScheduleTemplateItem {
  label: string
  percentage: number
}

export interface ClientEmailOption {
  value: string
  label: string
  is_primary?: boolean
}

export const PRIMARY_CLIENT_EMAIL_SELECTION = '__PRIMARY__'
export const NO_CLIENT_EMAIL_SELECTION = '__NONE__'

interface ContractSignedFormValues {
  projectName: string
  orderNumber: string
  productLine: string
  esrCost: string
  projectAmount: string
  downPayment: string
  jobAddress: string
  city: string
  jobState: string
  jobZip: string
  methodOfPayment: string
  typeOfFinancing: string
  clientEmailSelection: string
  orderCompanyContactId: string
  nameCheck: boolean
  addressCheck: boolean
  amountCheck: boolean
  emailCheck: boolean
  cityPermits: boolean
  associationPermits: boolean
  pendingFinancingOrDeposit: string
  pendingHoaApproval: string
  attachments: File[]
  paymentScheduleType: string
  customSchedule: CustomScheduleItem[]
}

interface ContractSignedConfirmation {
  message: string
  userEmail?: string
  userRoles?: string[]
}

type CustomSchedulePayload = { label: string, amount: number }

type ContractSignedSubmitValues = {
  projectName: string
  orderNumber: string
  productLine: string
  esrCost: string
  projectAmount: string
  downPayment: string
  jobAddress: string
  city: string
  jobState: string
  jobZip: string
  methodOfPayment: string
  typeOfFinancing: string
  clientEmailSelection: string
  orderCompanyContactId?: number | null
  attachments: File[]
  nameCheck: boolean
  addressCheck: boolean
  amountCheck: boolean
  emailCheck: boolean
  cityPermits: boolean
  associationPermits: boolean
  pendingFinancingOrDeposit: boolean
  pendingHoaApproval: boolean
  paymentScheduleType: string
  customSchedule: CustomSchedulePayload[]
}

export interface ContractSignedModalProps {
  open: boolean
  taskTitle: string
  initialProjectName: string
  initialOrderNumber?: string
  initialProductLine: string
  initialEsrCost?: string
  requireProductLine?: boolean
  initialProjectAmount: string
  initialDownPayment: string
  initialJobAddress: string
  initialCity: string
  initialJobState: string
  initialJobZip: string
  initialMethodOfPayment: string
  initialTypeOfFinancing: string
  initialClientEmailSelection: string
  clientEmailOptions?: ClientEmailOption[]
  initialNameCheck: boolean
  initialAddressCheck: boolean
  initialAmountCheck: boolean
  initialEmailCheck: boolean
  initialCityPermits: boolean
  initialAssociationPermits: boolean
  initialPendingFinancingOrDeposit?: boolean | null
  initialPendingHoaApproval?: boolean | null
  orderType?: string | null
  companyOptions?: Array<{ id: number, label: string, client_email?: string | null, clientEmailOptions?: ClientEmailOption[] }>
  initialOrderCompanyContactId?: number | null
  initialPaymentScheduleType?: string
  initialCustomSchedule?: CustomScheduleItem[]
  paymentMethods: string[]
  financingOptions: string[]
  paymentScheduleTemplates: Record<string, ScheduleTemplateItem[]>
  loading?: boolean
  error?: string | null
  confirmation?: ContractSignedConfirmation | null
  onConfirmCustomerRole?: () => void
  onDismissConfirmation?: () => void
  onSubmit: (values: ContractSignedSubmitValues) => void | Promise<void>
  onCancel: () => void
}

export default function ContractSignedModal ({
  open,
  taskTitle,
  initialProjectName,
  initialOrderNumber = '',
  initialProductLine,
  initialEsrCost = '',
  requireProductLine = false,
  initialProjectAmount,
  initialDownPayment,
  initialJobAddress,
  initialCity,
  initialJobState,
  initialJobZip,
  initialMethodOfPayment,
  initialTypeOfFinancing,
  initialClientEmailSelection,
  clientEmailOptions = [],
  initialNameCheck,
  initialAddressCheck,
  initialAmountCheck,
  initialEmailCheck,
  initialCityPermits,
  initialAssociationPermits,
  initialPendingFinancingOrDeposit = null,
  initialPendingHoaApproval = null,
  orderType,
  companyOptions = [],
  initialOrderCompanyContactId = null,
  initialPaymentScheduleType,
  initialCustomSchedule,
  paymentMethods,
  financingOptions,
  paymentScheduleTemplates,
  loading = false,
  error,
  confirmation,
  onConfirmCustomerRole,
  onDismissConfirmation,
  onSubmit,
  onCancel
}: ContractSignedModalProps) {
  if (!open) return null

  const CUSTOM_SCHEDULE_TYPE = 'CUSTOMIZED'
  const buildCustomSchedule = (items?: CustomScheduleItem[]) => {
    const normalized = Array.isArray(items)
      ? items.map((item) => ({
        label: item.label ?? '',
        amount: item.amount != null ? String(item.amount) : ''
      }))
      : []
    while (normalized.length < 6) {
      normalized.push({ label: '', amount: '' })
    }
    return normalized.slice(0, 6)
  }

  const isCommercial = orderType?.toLowerCase() === 'commercial'
  const requiresCompanySelection = isCommercial && companyOptions.length > 1
  const hiddenPaymentMethods = new Set(['CHECK', 'ZELLE', 'AIA'])
  const booleanToRequiredSelection = (value?: boolean | null) => {
    if (value === true) return '1'
    if (value === false) return '0'
    return ''
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[90vh]">
        <div className="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-slate-100">
          <div>
            <h3 className="text-lg font-semibold text-slate-800">Contract Signed Details</h3>
            <p className="mt-2 text-sm text-slate-600">
              Review the contract information for <strong>{taskTitle}</strong>.
            </p>
          </div>
          <button
            type="button"
            onClick={onCancel}
            className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            disabled={loading}
          >
            <CloseIcon />
          </button>
        </div>
        <div className="flex-1 overflow-y-auto px-6 pb-6">
        {confirmation && (
          <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p className="font-semibold">Attention required</p>
            <p className="mt-1">{confirmation.message}</p>
            {confirmation.userEmail && (
              <p className="mt-2"><strong>Email:</strong> {confirmation.userEmail}</p>
            )}
            {confirmation.userRoles && confirmation.userRoles.length > 0 && (
              <p className="mt-1"><strong>Roles:</strong> {confirmation.userRoles.join(', ')}</p>
            )}
            <div className="mt-4 flex flex-wrap gap-3">
              <button
                type="button"
                className="rounded-lg border border-amber-300 px-4 py-2 text-amber-900 transition hover:bg-amber-100"
                onClick={onDismissConfirmation}
                disabled={loading}
              >
                Cancel
              </button>
              <button
                type="button"
                className="rounded-lg bg-amber-600 px-4 py-2 text-white transition hover:bg-amber-700"
                onClick={onConfirmCustomerRole}
                disabled={loading}
              >
                Convert to customer and send email
              </button>
            </div>
          </div>
        )}
        <Formik<ContractSignedFormValues>
          enableReinitialize
          initialValues={{
            projectName: initialProjectName ?? '',
            orderNumber: initialOrderNumber ?? '',
            productLine: initialProductLine ?? '',
            esrCost: initialEsrCost ?? '',
            projectAmount: initialProjectAmount ?? '',
            downPayment: initialDownPayment ?? '',
            jobAddress: initialJobAddress ?? '',
            city: initialCity ?? '',
            jobState: initialJobState ?? '',
            jobZip: initialJobZip ?? '',
            methodOfPayment: initialMethodOfPayment ?? '',
            typeOfFinancing: initialTypeOfFinancing ?? '',
            clientEmailSelection: initialClientEmailSelection ?? PRIMARY_CLIENT_EMAIL_SELECTION,
            orderCompanyContactId: initialOrderCompanyContactId ? String(initialOrderCompanyContactId) : (companyOptions.length === 1 ? String(companyOptions[0].id) : ''),
            nameCheck: initialNameCheck ?? true,
            addressCheck: initialAddressCheck ?? true,
            amountCheck: initialAmountCheck ?? true,
            emailCheck: initialEmailCheck ?? true,
            cityPermits: initialCityPermits ?? false,
            associationPermits: initialAssociationPermits ?? false,
            pendingFinancingOrDeposit: booleanToRequiredSelection(initialPendingFinancingOrDeposit),
            pendingHoaApproval: booleanToRequiredSelection(initialPendingHoaApproval),
            attachments: [],
            paymentScheduleType: initialMethodOfPayment === PAYMENT_METHODS.CASH_AND_FINANCE
              ? CUSTOM_SCHEDULE_TYPE
              : (initialPaymentScheduleType ?? ''),
            customSchedule: buildCustomSchedule(initialCustomSchedule)
          }}
          validate={(values) => {
            const issues: Partial<Record<keyof ContractSignedFormValues, string>> = {}

            const isCash = values.methodOfPayment === PAYMENT_METHODS.CASH
            const isCashAndFinance = values.methodOfPayment === PAYMENT_METHODS.CASH_AND_FINANCE
            const requiresFinancing = [PAYMENT_METHODS.FINANCED, PAYMENT_METHODS.CASH_AND_FINANCE]
              .includes(values.methodOfPayment)
            const requiresSchedule = isCash || isCashAndFinance
            const projectAmountValue = Number(String(values.projectAmount ?? '').replace(/,/g, ''))
            const hasValidProjectAmount = Number.isFinite(projectAmountValue) && projectAmountValue > 0
            const cashAmountValue = Number(String(values.downPayment ?? '').replace(/,/g, ''))
            const selectedCompanyOption = companyOptions.find((option) => String(option.id) === String(values.orderCompanyContactId))
            const activeEmailOptions = (selectedCompanyOption?.clientEmailOptions ?? clientEmailOptions) ?? []
            const primaryEmailOption = activeEmailOptions.find((option) => option.is_primary)
            const allowedEmailValues = new Set(activeEmailOptions.map((option) => option.value.trim().toLowerCase()))

            if (!values.projectName || values.projectName.trim() === '') {
              issues.projectName = 'Project name is required.'
            }
            if (requireProductLine && !values.productLine) {
              issues.productLine = 'Product Line is required.'
            }
            if (values.productLine === 'MIXED' && String(values.esrCost ?? '').trim() === '') {
              issues.esrCost = 'ESR Cost is required.'
            }

            if (!values.projectAmount) {
              issues.projectAmount = 'Project amount is required.'
            } else if (Number.isNaN(Number(values.projectAmount))) {
              issues.projectAmount = 'Enter a valid number.'
            }

            const normalizedClientEmailSelection = values.clientEmailSelection?.trim() ?? ''
            if (!normalizedClientEmailSelection) {
              issues.clientEmailSelection = 'Select how client emails should be handled for this order.'
            } else if (normalizedClientEmailSelection === PRIMARY_CLIENT_EMAIL_SELECTION) {
              if (!primaryEmailOption?.value) {
                issues.clientEmailSelection = 'Primary client email is not available. Select another associated email or choose not to send client emails.'
              }
            } else if (normalizedClientEmailSelection !== NO_CLIENT_EMAIL_SELECTION && !allowedEmailValues.has(normalizedClientEmailSelection.toLowerCase())) {
              issues.clientEmailSelection = 'Select one of the available client emails.'
            }

            if (values.jobAddress && values.jobAddress.length > 255) {
              issues.jobAddress = 'Job address is too long.'
            }

            if (values.city && values.city.length > 255) {
              issues.city = 'City is too long.'
            }

            if (values.jobState && values.jobState.length > 255) {
              issues.jobState = 'State is too long.'
            }

            if (values.jobZip && values.jobZip.length > 50) {
              issues.jobZip = 'Zip code is too long.'
            }

            if (!values.methodOfPayment) {
              issues.methodOfPayment = 'Select a payment method.'
            }

            if (isCashAndFinance) {
              const normalizedCashAmount = String(values.downPayment ?? '').trim()
              if (normalizedCashAmount === '') {
                issues.downPayment = 'Cash amount is required.'
              } else if (Number.isNaN(cashAmountValue)) {
                issues.downPayment = 'Enter a valid number.'
              } else if (cashAmountValue <= 0) {
                issues.downPayment = 'Cash amount must be greater than 0.'
              } else if (hasValidProjectAmount && cashAmountValue >= projectAmountValue) {
                issues.downPayment = 'Cash amount must be less than project amount.'
              }
            }

            if (requiresFinancing && !values.typeOfFinancing) {
              issues.typeOfFinancing = 'Select a financing type.'
            }

            if (requiresSchedule && !values.paymentScheduleType) {
              issues.paymentScheduleType = 'Select a payment schedule.'
            }

            if (isCashAndFinance && values.paymentScheduleType !== CUSTOM_SCHEDULE_TYPE) {
              issues.paymentScheduleType = 'Cash and financed requires CUSTOMIZED payment schedule.'
            }

            if (requiresSchedule && values.paymentScheduleType === CUSTOM_SCHEDULE_TYPE) {
              const customItems = values.customSchedule
                .map((item) => {
                  const labelValue = String(item.label ?? '').trim()
                  const amountValue = String(item.amount ?? '').trim()
                  return { label: labelValue, amount: amountValue }
                })
                .filter((item) => item.label !== '' || item.amount !== '')

              if (customItems.length === 0) {
                issues.customSchedule = 'Add at least one custom payment.'
              } else {
                let total = 0
                for (const item of customItems) {
                  if (!item.label) {
                    issues.customSchedule = 'Each custom payment needs a label.'
                    break
                  }
                  const normalizedAmount = item.amount.replace(/,/g, '')
                  if (normalizedAmount === '' || Number.isNaN(Number(normalizedAmount))) {
                    issues.customSchedule = 'Each custom payment needs a valid amount.'
                    break
                  }
                  const amountValue = Number(normalizedAmount)
                  if (amountValue <= 0) {
                    issues.customSchedule = 'Amounts must be greater than 0.'
                    break
                  }
                  total += amountValue
                }

                const targetTotal = isCashAndFinance ? cashAmountValue : projectAmountValue
                const targetLabel = isCashAndFinance ? 'cash amount' : 'project amount'
                if (!issues.customSchedule && Number.isFinite(targetTotal) && targetTotal > 0) {
                  if (Math.abs(total - targetTotal) > 0.01) {
                    issues.customSchedule = `Custom payments must total the ${targetLabel}.`
                  }
                }
              }
            }

            if (!values.attachments || values.attachments.length === 0) {
              issues.attachments = 'At least one attachment is required.'
            }

            if (values.pendingFinancingOrDeposit === '') {
              issues.pendingFinancingOrDeposit = 'Select Yes or No.'
            }

            if (values.associationPermits && values.pendingHoaApproval === '') {
              issues.pendingHoaApproval = 'Select Yes or No.'
            }

            if (requiresCompanySelection && !values.orderCompanyContactId) {
              issues.orderCompanyContactId = 'Select the company for this contract.'
            }

            const verifiedAll = values.nameCheck && values.addressCheck && values.amountCheck && values.emailCheck
            if (!verifiedAll) {
              issues.nameCheck = 'Please confirm the order details before saving.'
            }

            return issues
          }}
          onSubmit={(values) => {
            const customSchedule = values.paymentScheduleType === CUSTOM_SCHEDULE_TYPE
              ? values.customSchedule
                .map((item) => ({
                  label: String(item.label ?? '').trim(),
                  amount: Number(String(item.amount ?? '').replace(/,/g, ''))
                }))
                .filter((item) => item.label !== '' && Number.isFinite(item.amount))
              : []

            onSubmit({
              projectName: values.projectName.trim(),
              orderNumber: values.orderNumber.trim(),
              productLine: values.productLine,
              esrCost: values.esrCost,
              projectAmount: values.projectAmount,
              downPayment: values.downPayment,
              jobAddress: values.jobAddress.trim(),
              city: values.city.trim(),
              jobState: values.jobState.trim(),
              jobZip: values.jobZip.trim(),
              methodOfPayment: values.methodOfPayment,
              typeOfFinancing: values.typeOfFinancing,
              clientEmailSelection: values.clientEmailSelection.trim(),
              orderCompanyContactId: values.orderCompanyContactId ? Number(values.orderCompanyContactId) : null,
              nameCheck: values.nameCheck,
              addressCheck: values.addressCheck,
              amountCheck: values.amountCheck,
              emailCheck: values.emailCheck,
              cityPermits: values.cityPermits,
              associationPermits: values.associationPermits,
              pendingFinancingOrDeposit: values.pendingFinancingOrDeposit === '1',
              pendingHoaApproval: values.associationPermits ? values.pendingHoaApproval === '1' : false,
              attachments: values.attachments ?? [],
              paymentScheduleType: values.paymentScheduleType,
              customSchedule
            })
          }}
        >
          {({ values, errors, submitCount, handleChange, handleBlur, setFieldValue }) => {
            const isCash = values.methodOfPayment === PAYMENT_METHODS.CASH
            const isCashAndFinance = values.methodOfPayment === PAYMENT_METHODS.CASH_AND_FINANCE
            const isFinanced = values.methodOfPayment === PAYMENT_METHODS.FINANCED
            const shouldShowFinancing = isFinanced || isCashAndFinance
            const shouldShowCashAmount = isCashAndFinance
            const shouldShowPaymentSchedule = isCash || isCashAndFinance
            const availablePaymentMethods = paymentMethods.filter((method) => (
              !hiddenPaymentMethods.has(String(method).trim().toUpperCase())
            ))
            const attachmentsErrorMessage = typeof errors.attachments === 'string'
              ? errors.attachments
              : Array.isArray(errors.attachments)
                ? errors.attachments
                  .map((item) => typeof item === 'string' ? item : '')
                  .filter(Boolean)
                  .join(', ') || undefined
                : undefined
            const scheduleTemplates = paymentScheduleTemplates ?? {}
            const scheduleOptions = isCashAndFinance
              ? [CUSTOM_SCHEDULE_TYPE]
              : Object.keys(scheduleTemplates)
            const isCustomSchedule = values.paymentScheduleType === CUSTOM_SCHEDULE_TYPE
            const projectAmountValue = Number((values.projectAmount ?? '').toString().replace(/,/g, ''))
            const hasProjectAmount = Number.isFinite(projectAmountValue) && projectAmountValue > 0
            const cashAmountValue = Number((values.downPayment ?? '').toString().replace(/,/g, ''))
            const hasCashAmount = Number.isFinite(cashAmountValue) && cashAmountValue > 0
            const financedAmountValue = hasProjectAmount && hasCashAmount
              ? Math.max(projectAmountValue - cashAmountValue, 0)
              : null
            const scheduleTargetAmount = isCashAndFinance ? cashAmountValue : projectAmountValue
            const hasScheduleTargetAmount = Number.isFinite(scheduleTargetAmount) && scheduleTargetAmount > 0
            const formatCurrency = (value: number) =>
              new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)

            const buildPreviewItems = (items: Array<{ label: string, percentage: number }>) => {
              if (!items.length) return []
              if (!hasScheduleTargetAmount) {
                return items.map((item) => ({ ...item, amount: null }))
              }
              let runningTotal = 0
              return items.map((item, index) => {
                const amount = index === items.length - 1
                  ? Math.round((scheduleTargetAmount - runningTotal) * 100) / 100
                  : Math.round((scheduleTargetAmount * (item.percentage / 100)) * 100) / 100
                runningTotal += amount
                return { ...item, amount }
              })
            }

            const selectedTemplateItems = scheduleTemplates[values.paymentScheduleType] ?? []
            const customScheduleItems = values.customSchedule
              .map((item) => ({
                label: String(item.label ?? '').trim(),
                amount: Number(String(item.amount ?? '').replace(/,/g, ''))
              }))
              .filter((item) => item.label !== '' && Number.isFinite(item.amount))
            const customScheduleTotal = values.customSchedule.reduce((total, item) => {
              const value = Number(String(item.amount ?? '').replace(/,/g, ''))
              return Number.isFinite(value) ? total + value : total
            }, 0)
            const customTotalMatches = hasScheduleTargetAmount && Math.abs(customScheduleTotal - scheduleTargetAmount) <= 0.01
            const customTotalClass = hasScheduleTargetAmount
              ? customTotalMatches
                ? 'text-emerald-600'
                : 'text-rose-600'
              : 'text-slate-400'
            const previewItems = isCustomSchedule
              ? customScheduleItems.map((item) => ({
                label: item.label,
                percentage: hasScheduleTargetAmount
                  ? Math.round(((item.amount / scheduleTargetAmount) * 100) * 100) / 100
                  : null,
                amount: Number.isFinite(item.amount) ? item.amount : null
              }))
              : buildPreviewItems(selectedTemplateItems)

            const selectedCompanyOption = companyOptions.find((option) => String(option.id) === String(values.orderCompanyContactId))
            const activeEmailOptions = (selectedCompanyOption?.clientEmailOptions ?? clientEmailOptions) ?? []
            const primaryEmailOption = activeEmailOptions.find((option) => option.is_primary)
            const alternateEmailOptions = activeEmailOptions.filter((option) => !option.is_primary)
            const selectedAlternateEmail = alternateEmailOptions.find((option) => option.value === values.clientEmailSelection)
            const recipientPreview = values.clientEmailSelection === NO_CLIENT_EMAIL_SELECTION
              ? 'Client emails disabled for this order.'
              : values.clientEmailSelection === PRIMARY_CLIENT_EMAIL_SELECTION
                ? (primaryEmailOption?.value ?? 'Primary client email is not available.')
                : (selectedAlternateEmail?.value ?? values.clientEmailSelection)
            const renderYesNoSegment = (
              name: 'pendingFinancingOrDeposit' | 'pendingHoaApproval',
              value: string
            ) => (
              <div className="grid w-28 grid-cols-2 gap-px rounded-md border border-slate-200 bg-slate-100 p-px">
                {[
                  { label: 'Yes', value: '1' },
                  { label: 'No', value: '0' }
                ].map((option) => {
                  const selected = value === option.value

                  return (
                    <button
                      key={`${name}-${option.value}`}
                      type="button"
                      onClick={() => { setFieldValue(name, option.value) }}
                      className={[
                        'rounded px-1.5 py-1 text-[11px] font-semibold leading-4 transition focus:outline-none focus:ring-2 focus:ring-sky-100 disabled:cursor-not-allowed disabled:opacity-70',
                        selected
                          ? 'bg-sky-600 text-white shadow-sm ring-1 ring-sky-600'
                          : 'bg-transparent text-slate-600 hover:bg-white hover:text-slate-800'
                      ].filter(Boolean).join(' ')}
                      disabled={loading}
                      aria-pressed={selected}
                    >
                      {option.label}
                    </button>
                  )
                })}
              </div>
            )

            return (
            <Form className="mt-4 space-y-4" encType="multipart/form-data">
              <fieldset className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <legend className="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Review the contract information</legend>
                <div className="mt-3 space-y-4">
                  {requiresCompanySelection && (
                    <div className={submitCount ? (errors.orderCompanyContactId ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600">Select Company</label>
                      <select
                        name="orderCompanyContactId"
                        value={values.orderCompanyContactId}
                        onChange={(event) => {
                          handleChange(event)
                          const nextId = event.target.value
                          const nextOption = companyOptions.find((option) => String(option.id) === String(nextId))
                          const nextEmailOptions = (nextOption?.clientEmailOptions ?? clientEmailOptions) ?? []
                          const nextAllowedValues = new Set(nextEmailOptions.map((option) => option.value.trim().toLowerCase()))
                          const currentSelection = values.clientEmailSelection?.trim() ?? ''
                          const isReservedSelection = currentSelection === PRIMARY_CLIENT_EMAIL_SELECTION || currentSelection === NO_CLIENT_EMAIL_SELECTION

                          if (!isReservedSelection && !nextAllowedValues.has(currentSelection.toLowerCase())) {
                            setFieldValue('clientEmailSelection', PRIMARY_CLIENT_EMAIL_SELECTION)
                          }
                        }}
                        onBlur={handleBlur}
                        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        disabled={loading}
                      >
                        <option value="">Select company</option>
                        {companyOptions.map((option) => (
                          <option key={option.id} value={option.id}>{option.label}</option>
                        ))}
                      </select>
                      {submitCount && errors.orderCompanyContactId
                        ? <InputError message={errors.orderCompanyContactId as string} className="mt-2" />
                        : null}
                    </div>
                  )}
                  <div className={submitCount ? (errors.projectName ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600">Project Name</label>
                    <input
                      name="projectName"
                      type="text"
                      value={values.projectName}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      placeholder="Enter project name"
                      disabled={loading}
                    />
                    {submitCount && errors.projectName
                      ? <InputError message={errors.projectName} className="mt-2" />
                      : null}
                  </div>

                  {requireProductLine && <div className={submitCount ? (errors.productLine ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600">Product Line</label>
                    <select
                      name="productLine"
                      value={values.productLine}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      disabled={loading}
                    >
                      <option value="">Select Product Line</option>
                      {PRODUCT_LINES.map((line) => <option key={line} value={line}>{line}</option>)}
                    </select>
                    {submitCount && errors.productLine ? <InputError message={errors.productLine} className="mt-2" /> : null}
                  </div>}
                  {values.productLine === 'MIXED' && <div className={submitCount ? (errors.esrCost ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600">ESR Cost</label>
                    <input name="esrCost" type="number" min="0" step="0.01" value={values.esrCost} onChange={handleChange} onBlur={handleBlur} className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100" placeholder="Enter ESR cost" disabled={loading} />
                    {submitCount && errors.esrCost ? <InputError message={errors.esrCost} className="mt-2" /> : null}
                  </div>}

                  <div className={`grid gap-4 md:grid-cols-2 ${submitCount ? (errors.projectAmount ? 'has-error' : 'has-success') : ''}`}>
                    <div className={submitCount ? (errors.projectAmount ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600">Project Amount</label>
                      <input
                        name="projectAmount"
                        type="number"
                        step="0.01"
                        value={values.projectAmount}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        placeholder="Enter project amount"
                        disabled={loading}
                      />
                      {submitCount && errors.projectAmount
                        ? <InputError message={errors.projectAmount} className="mt-2" />
                        : null}
                    </div>
                  </div>

                  <div className={submitCount ? (errors.clientEmailSelection ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600">Client Email Delivery</label>
                    <select
                      name="clientEmailSelection"
                      value={values.clientEmailSelection}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      disabled={loading}
                    >
                      <option value={PRIMARY_CLIENT_EMAIL_SELECTION}>
                        {primaryEmailOption?.label ?? 'Primary client email not available'}
                      </option>
                      {alternateEmailOptions.map((option) => (
                        <option key={option.value} value={option.value}>{option.label}</option>
                      ))}
                      <option value={NO_CLIENT_EMAIL_SELECTION}>Do not send client emails</option>
                    </select>
                    <p className="mt-2 text-xs text-slate-500">{recipientPreview}</p>
                    {submitCount && errors.clientEmailSelection
                      ? <InputError message={errors.clientEmailSelection} className="mt-2" />
                      : null}
                  </div>

                  <div className={submitCount ? (errors.jobAddress ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600">Job Address</label>
                    <textarea
                      name="jobAddress"
                      rows={3}
                      value={values.jobAddress}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      placeholder="Enter job address"
                      disabled={loading}
                    />
                    {submitCount && errors.jobAddress
                      ? <InputError message={errors.jobAddress} className="mt-2" />
                      : null}
                  </div>

                  <div className="grid gap-4 md:grid-cols-3">
                    <div className={submitCount ? (errors.city ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600">City</label>
                      <input
                        name="city"
                        type="text"
                        value={values.city}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        placeholder="City"
                        disabled={loading}
                      />
                      {submitCount && errors.city
                        ? <InputError message={errors.city} className="mt-2" />
                        : null}
                    </div>

                    <div className={submitCount ? (errors.jobState ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600">State</label>
                      <input
                        name="jobState"
                        type="text"
                        value={values.jobState}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        placeholder="State"
                        disabled={loading}
                      />
                      {submitCount && errors.jobState
                        ? <InputError message={errors.jobState} className="mt-2" />
                        : null}
                    </div>

                    <div className={submitCount ? (errors.jobZip ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600">Zip</label>
                      <input
                        name="jobZip"
                        type="text"
                        value={values.jobZip}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        placeholder="Zip"
                        disabled={loading}
                      />
                      {submitCount && errors.jobZip
                        ? <InputError message={errors.jobZip} className="mt-2" />
                        : null}
                    </div>
                  </div>
                </div>
              </fieldset>

              <fieldset className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <legend className="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Information</legend>
                <div className="mt-3 space-y-4 md:space-y-0 md:grid md:grid-cols-2 md:gap-4">
                  <div className={submitCount ? (errors.methodOfPayment ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="methodOfPayment">Project Payment Method</label>
                    <select
                      id="methodOfPayment"
                      name="methodOfPayment"
                      value={values.methodOfPayment}
                      onChange={(event) => {
                        const previousMethod = values.methodOfPayment
                        handleChange(event)
                        const value = event.target.value
                        const isCashValue = value === PAYMENT_METHODS.CASH
                        const isCashAndFinanceValue = value === PAYMENT_METHODS.CASH_AND_FINANCE
                        const isFinancedValue = value === PAYMENT_METHODS.FINANCED
                        if (!isFinancedValue && !isCashAndFinanceValue) {
                          setFieldValue('typeOfFinancing', '')
                        }
                        if (!isCashAndFinanceValue) {
                          setFieldValue('downPayment', '')
                        }
                        if (isCashAndFinanceValue) {
                          setFieldValue('paymentScheduleType', CUSTOM_SCHEDULE_TYPE)
                        } else if (isCashValue && previousMethod !== PAYMENT_METHODS.CASH) {
                          setFieldValue('paymentScheduleType', '')
                          setFieldValue('customSchedule', buildCustomSchedule())
                        } else if (!isCashValue) {
                          setFieldValue('paymentScheduleType', '')
                          setFieldValue('customSchedule', buildCustomSchedule())
                        }
                      }}
                      onBlur={handleBlur}
                      className="form-select w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      disabled={loading}
                    >
                      <option value="">Method of Payment</option>
                      {availablePaymentMethods.map((method) => (
                        <option key={method} value={method}>{method}</option>
                      ))}
                    </select>
                    {submitCount && errors.methodOfPayment
                      ? <InputError message={errors.methodOfPayment} className="mt-2" />
                      : null}
                  </div>

                  {shouldShowCashAmount && (
                    <div className={submitCount ? (errors.downPayment ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="downPayment">Cash Amount</label>
                      <input
                        id="downPayment"
                        name="downPayment"
                        type="number"
                        step="0.01"
                        value={values.downPayment}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        placeholder="Enter cash amount"
                        disabled={loading}
                      />
                      {submitCount && errors.downPayment
                        ? <InputError message={errors.downPayment} className="mt-2" />
                        : null}
                    </div>
                  )}

                  {isCashAndFinance && (
                    <div>
                      <label className="mb-1 block text-sm font-medium text-slate-600">Amount to Finance</label>
                      <div className="w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-700">
                        {financedAmountValue != null ? formatCurrency(financedAmountValue) : 'Enter project and cash amounts'}
                      </div>
                    </div>
                  )}

                  {shouldShowFinancing && (
                    <div className={submitCount ? (errors.typeOfFinancing ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="typeOfFinancing">Type of Financing</label>
                      <select
                        id="typeOfFinancing"
                        name="typeOfFinancing"
                        value={values.typeOfFinancing}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        className="form-select w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        disabled={loading}
                      >
                        <option value="">Type of Financing</option>
                        {financingOptions.map((option) => (
                          <option key={option} value={option}>{option}</option>
                        ))}
                      </select>
                      {submitCount && errors.typeOfFinancing
                        ? <InputError message={errors.typeOfFinancing} className="mt-2" />
                        : null}
                    </div>
                  )}
                </div>
                {shouldShowPaymentSchedule && (
                  <div className="mt-4 space-y-4">
                    <div className={submitCount ? (errors.paymentScheduleType ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="paymentScheduleType">Payment Schedule</label>
                      <select
                        id="paymentScheduleType"
                        name="paymentScheduleType"
                        value={values.paymentScheduleType}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        className="form-select w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                        disabled={loading || isCashAndFinance}
                      >
                        <option value="">
                          {isCashAndFinance ? 'CUSTOMIZED' : 'Select a payment schedule'}
                        </option>
                        {scheduleOptions.map((schedule) => (
                          <option key={schedule} value={schedule}>{schedule}</option>
                        ))}
                      </select>
                      {submitCount && errors.paymentScheduleType
                        ? <InputError message={errors.paymentScheduleType} className="mt-2" />
                        : null}
                    </div>

                    {isCustomSchedule && (
                      <div className="space-y-3 rounded-lg border border-slate-200 bg-white p-3">
                        <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-400">
                          <span>Custom schedule</span>
                          <span className={customTotalClass}>
                            Total: {formatCurrency(customScheduleTotal)}
                            {hasScheduleTargetAmount ? ` / ${formatCurrency(scheduleTargetAmount)}` : ''}
                          </span>
                        </div>
                        {values.customSchedule.map((item, index) => (
                          <div key={`custom-schedule-${index}`} className="grid gap-3 md:grid-cols-3">
                            <div className="md:col-span-2">
                              <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Label</label>
                              <input
                                name={`customSchedule[${index}].label`}
                                type="text"
                                value={item.label}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                placeholder={`Payment ${index + 1}`}
                                disabled={loading}
                              />
                            </div>
                            <div>
                              <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</label>
                              <input
                                name={`customSchedule[${index}].amount`}
                                type="number"
                                step="0.01"
                                value={item.amount}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                placeholder="0.00"
                                disabled={loading}
                              />
                            </div>
                          </div>
                        ))}
                        {submitCount && errors.customSchedule && typeof errors.customSchedule === 'string'
                          ? <InputError message={errors.customSchedule} className="mt-2" />
                          : null}
                      </div>
                    )}

                    {values.paymentScheduleType && previewItems.length > 0 && (
                      <div className="rounded-lg border border-slate-200 bg-white p-3">
                        <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-400">
                          <span>Schedule preview</span>
                          <span>{hasScheduleTargetAmount ? formatCurrency(scheduleTargetAmount) : (isCashAndFinance ? 'Enter cash amount' : 'Enter project amount')}</span>
                        </div>
                        <div className="mt-3 space-y-2 text-sm text-slate-600">
                          {previewItems.map((item, index) => (
                            <div key={`${item.label}-${index}`} className="flex items-center justify-between gap-3">
                              <span className="font-medium text-slate-700">{item.label}</span>
                              <span>{item.percentage != null ? `${item.percentage}%` : '--'}</span>
                              <span className="text-slate-500">
                                {item.amount != null ? formatCurrency(item.amount) : '--'}
                              </span>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </fieldset>

              <fieldset className="rounded-lg border border-slate-200 bg-white/70 p-3">
                <legend className="px-1 text-xs font-semibold uppercase text-slate-500">Verifications</legend>
                <div className="mt-2 grid grid-cols-2 gap-3 text-sm text-slate-600">
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      name="nameCheck"
                      checked={values.nameCheck}
                      onChange={(event) => { setFieldValue('nameCheck', event.target.checked) }}
                      className="form-checkbox h-4 w-4"
                      disabled={loading}
                    />
                    <span>Name</span>
                  </label>
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      name="addressCheck"
                      checked={values.addressCheck}
                      onChange={(event) => { setFieldValue('addressCheck', event.target.checked) }}
                      className="form-checkbox h-4 w-4"
                      disabled={loading}
                    />
                    <span>Address</span>
                  </label>
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      name="amountCheck"
                      checked={values.amountCheck}
                      onChange={(event) => { setFieldValue('amountCheck', event.target.checked) }}
                      className="form-checkbox h-4 w-4"
                      disabled={loading}
                    />
                    <span>Amount</span>
                  </label>
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      name="emailCheck"
                      checked={values.emailCheck}
                      onChange={(event) => { setFieldValue('emailCheck', event.target.checked) }}
                      className="form-checkbox h-4 w-4"
                      disabled={loading}
                    />
                    <span>Email</span>
                  </label>
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      name="cityPermits"
                      checked={values.cityPermits}
                      onChange={(event) => { setFieldValue('cityPermits', event.target.checked) }}
                      className="form-checkbox h-4 w-4"
                      disabled={loading}
                    />
                    <span>Included City Permits</span>
                  </label>
                  <label className="inline-flex items-center gap-2">
                    <input
                      type="checkbox"
                      name="associationPermits"
                      checked={values.associationPermits}
                      onChange={(event) => {
                        setFieldValue('associationPermits', event.target.checked)
                        if (!event.target.checked) {
                          setFieldValue('pendingHoaApproval', '')
                        }
                      }}
                      className="form-checkbox h-4 w-4"
                      disabled={loading}
                    />
                    <span>HOA</span>
                  </label>
                </div>
                <div className="mt-2.5 grid gap-2 md:grid-cols-2">
                  <div className={submitCount ? (errors.pendingFinancingOrDeposit ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-[11px] font-semibold uppercase leading-4 text-slate-500">
                      Pending financing or deposit
                    </label>
                    {renderYesNoSegment('pendingFinancingOrDeposit', values.pendingFinancingOrDeposit)}
                    {submitCount && errors.pendingFinancingOrDeposit
                      ? <InputError message={errors.pendingFinancingOrDeposit} className="mt-2" />
                      : null}
                  </div>
                  {values.associationPermits && (
                    <div className={submitCount ? (errors.pendingHoaApproval ? 'has-error' : 'has-success') : ''}>
                      <label className="mb-1 block text-[11px] font-semibold uppercase leading-4 text-slate-500">
                        Pending HOA approval
                      </label>
                      {renderYesNoSegment('pendingHoaApproval', values.pendingHoaApproval)}
                      {submitCount && errors.pendingHoaApproval
                        ? <InputError message={errors.pendingHoaApproval} className="mt-2" />
                        : null}
                    </div>
                  )}
                </div>
                {submitCount > 0 && errors.nameCheck && (
                  <InputError message={errors.nameCheck} className="mt-2" />
                )}
              </fieldset>

              <div className={submitCount ? (errors.attachments ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600">Attachments</label>
                <input
                  name="attachments"
                  type="file"
                  multiple
                  onChange={(event) => {
                    const files = Array.from(event.currentTarget.files ?? [])
                    setFieldValue('attachments', files)
                  }}
                  className="block w-full cursor-pointer rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-sky-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed"
                  disabled={loading}
                />
                {values.attachments?.length > 0 && (
                  <ul className="mt-2 space-y-1 text-xs text-slate-500">
                    {values.attachments.map((file, index) => (
                      <li key={`${file.name}-${index}`}>{file.name}</li>
                    ))}
                  </ul>
                )}
                {submitCount && errors.attachments
                  ? <InputError message={attachmentsErrorMessage ?? 'Please verify the attachments.'} className="mt-2" />
                  : null}
              </div>

              {error && (
                <div className="rounded-lg bg-rose-100 px-3 py-2 text-sm text-rose-700">
                  {error}
                </div>
              )}

              <div className="mt-6 flex items-center justify-end gap-3">
                <button
                  type="button"
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-70"
                  onClick={onCancel}
                  disabled={loading}
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={loading}
                >
                  {loading ? 'Saving…' : 'Confirm'}
                </button>
              </div>
            </Form>
            )
          }}
        </Formik>
        </div>
      </div>
    </div>
  )
}
