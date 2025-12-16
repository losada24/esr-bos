import InputError from '@/Components/InputError'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { Formik, Form } from 'formik'
import { PAYMENT_METHODS } from '@/Utils/constants'

interface ContractSignedFormValues {
  projectName: string
  projectAmount: string
  downPayment: string
  jobAddress: string
  city: string
  jobState: string
  jobZip: string
  methodOfPayment: string
  typeOfFinancing: string
  contactEmail: string
  nameCheck: boolean
  addressCheck: boolean
  amountCheck: boolean
  emailCheck: boolean
  attachments: File[]
}

export interface ContractSignedModalProps {
  open: boolean
  taskTitle: string
  initialProjectName: string
  initialProjectAmount: string
  initialDownPayment: string
  initialJobAddress: string
  initialCity: string
  initialJobState: string
  initialJobZip: string
  initialMethodOfPayment: string
  initialTypeOfFinancing: string
  initialContactEmail: string
  initialNameCheck: boolean
  initialAddressCheck: boolean
  initialAmountCheck: boolean
  initialEmailCheck: boolean
  paymentMethods: string[]
  financingOptions: string[]
  loading?: boolean
  error?: string | null
  onSubmit: (values: { projectName: string, projectAmount: string, downPayment: string, jobAddress: string, city: string, jobState: string, jobZip: string, methodOfPayment: string, typeOfFinancing: string, contactEmail: string, attachments: File[], nameCheck: boolean, addressCheck: boolean, amountCheck: boolean, emailCheck: boolean }) => void | Promise<void>
  onCancel: () => void
}

export default function ContractSignedModal ({
  open,
  taskTitle,
  initialProjectName,
  initialProjectAmount,
  initialDownPayment,
  initialJobAddress,
  initialCity,
  initialJobState,
  initialJobZip,
  initialMethodOfPayment,
  initialTypeOfFinancing,
  initialContactEmail,
  initialNameCheck,
  initialAddressCheck,
  initialAmountCheck,
  initialEmailCheck,
  paymentMethods,
  financingOptions,
  loading = false,
  error,
  onSubmit,
  onCancel
}: ContractSignedModalProps) {
  if (!open) return null

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
        <Formik<ContractSignedFormValues>
          enableReinitialize
          initialValues={{
            projectName: initialProjectName ?? '',
            projectAmount: initialProjectAmount ?? '',
            downPayment: initialDownPayment ?? '',
            jobAddress: initialJobAddress ?? '',
            city: initialCity ?? '',
            jobState: initialJobState ?? '',
            jobZip: initialJobZip ?? '',
            methodOfPayment: initialMethodOfPayment ?? '',
            typeOfFinancing: initialTypeOfFinancing ?? '',
            contactEmail: initialContactEmail ?? '',
            nameCheck: initialNameCheck ?? true,
            addressCheck: initialAddressCheck ?? true,
            amountCheck: initialAmountCheck ?? true,
            emailCheck: initialEmailCheck ?? true,
            attachments: [],
          }}
          validate={(values) => {
            const issues: Partial<Record<keyof ContractSignedFormValues, string>> = {}

            const requiresFinancing = [PAYMENT_METHODS.FINANCED, PAYMENT_METHODS.CASH_AND_FINANCE]
              .includes(values.methodOfPayment)

            if (!values.projectName || values.projectName.trim() === '') {
              issues.projectName = 'Project name is required.'
            }

            if (!values.projectAmount) {
              issues.projectAmount = 'Project amount is required.'
            } else if (Number.isNaN(Number(values.projectAmount))) {
              issues.projectAmount = 'Enter a valid number.'
            }

            const trimmedEmail = values.contactEmail?.trim() ?? ''
            if (!trimmedEmail) {
              issues.contactEmail = 'Contact email is required.'
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
              issues.contactEmail = 'Enter a valid email address.'
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

            if (values.downPayment && Number.isNaN(Number(values.downPayment))) {
              issues.downPayment = 'Enter a valid number.'
            }

            if (requiresFinancing && !values.typeOfFinancing) {
              issues.typeOfFinancing = 'Select a financing type.'
            }

            if (!values.attachments || values.attachments.length === 0) {
              issues.attachments = 'At least one attachment is required.'
            }

            const verifiedAll = values.nameCheck && values.addressCheck && values.amountCheck && values.emailCheck
            if (!verifiedAll) {
              issues.nameCheck = 'Please confirm the order details before saving.'
            }

            return issues
          }}
          onSubmit={(values) => {
            onSubmit({
              projectName: values.projectName.trim(),
              projectAmount: values.projectAmount,
              downPayment: values.downPayment,
              jobAddress: values.jobAddress.trim(),
              city: values.city.trim(),
              jobState: values.jobState.trim(),
              jobZip: values.jobZip.trim(),
              methodOfPayment: values.methodOfPayment,
              typeOfFinancing: values.typeOfFinancing,
              contactEmail: values.contactEmail.trim(),
              nameCheck: values.nameCheck,
              addressCheck: values.addressCheck,
              amountCheck: values.amountCheck,
              emailCheck: values.emailCheck,
              attachments: values.attachments ?? [],
            })
          }}
        >
          {({ values, errors, submitCount, handleChange, handleBlur, setFieldValue }) => {
            const financingRequired = [PAYMENT_METHODS.FINANCED, PAYMENT_METHODS.CASH_AND_FINANCE]
            const shouldShowFinancing = financingRequired.includes(values.methodOfPayment)
            const attachmentsErrorMessage = typeof errors.attachments === 'string'
              ? errors.attachments
              : Array.isArray(errors.attachments)
                ? errors.attachments
                  .map((item) => typeof item === 'string' ? item : '')
                  .filter(Boolean)
                  .join(', ') || undefined
                : undefined

            return (
            <Form className="mt-4 space-y-4" encType="multipart/form-data">
              <fieldset className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <legend className="px-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Review the contract information</legend>
                <div className="mt-3 space-y-4">
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

                  <div className={submitCount ? (errors.contactEmail ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600">Contact Email</label>
                    <input
                      name="contactEmail"
                      type="email"
                      value={values.contactEmail}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      placeholder="name@example.com"
                      disabled={loading}
                    />
                    {submitCount && errors.contactEmail
                      ? <InputError message={errors.contactEmail} className="mt-2" />
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
                        handleChange(event)
                        const value = event.target.value
                        if (!financingRequired.includes(value)) {
                          setFieldValue('typeOfFinancing', '')
                        }
                      }}
                      onBlur={handleBlur}
                      className="form-select w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      disabled={loading}
                    >
                      <option value="">Method of Payment</option>
                      {paymentMethods.map((method) => (
                        <option key={method} value={method}>{method}</option>
                      ))}
                    </select>
                    {submitCount && errors.methodOfPayment
                      ? <InputError message={errors.methodOfPayment} className="mt-2" />
                      : null}
                  </div>

                  <div className={submitCount ? (errors.downPayment ? 'has-error' : 'has-success') : ''}>
                    <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="downPayment">Down Payment</label>
                    <input
                      id="downPayment"
                      name="downPayment"
                      type="number"
                      step="0.01"
                      value={values.downPayment}
                      onChange={handleChange}
                      onBlur={handleBlur}
                      className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                      placeholder="Enter down payment"
                      disabled={loading}
                    />
                    {submitCount && errors.downPayment
                      ? <InputError message={errors.downPayment} className="mt-2" />
                      : null}
                  </div>

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
                </div>
                {submitCount && errors.nameCheck && (
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
            )}
          }
        </Formik>
        </div>
      </div>
    </div>
  )
}
