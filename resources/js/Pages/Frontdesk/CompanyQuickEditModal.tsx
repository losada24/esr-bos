import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import InputError from '@/Components/InputError'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import { companyContactSchema, type CompanyContact } from '../CompanyContact/CompanyContactCommon'

type CompanyQuickEditModalProps = {
  open: boolean
  company: CompanyContact | null
  onClose: () => void
  onUpdated: (company: CompanyContact) => void
}

const normalizeValue = (value?: string | null) => {
  if (value == null) return null
  const trimmed = String(value).trim()
  return trimmed.length === 0 ? null : trimmed
}

export default function CompanyQuickEditModal ({
  open,
  company,
  onClose,
  onUpdated
}: CompanyQuickEditModalProps) {
  const initialValues: CompanyContact = {
    id: company?.id ?? 0,
    name: company?.name ?? '',
    email: company?.email ?? '',
    phone: company?.phone ?? '',
    website: company?.website ?? '',
    billing_street: company?.billing_street ?? '',
    billing_city: company?.billing_city ?? '',
    billing_state: company?.billing_state ?? '',
    billing_code: company?.billing_code ?? '',
    bid_due_date: company?.bid_due_date ?? null
  }

  const handleSubmit = async (
    values: CompanyContact,
    helpers: FormikHelpers<CompanyContact>
  ) => {
    if (!company?.id) {
      helpers.setSubmitting(false)
      return
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    try {
      const payload = {
        name: normalizeValue(values.name) ?? '',
        email: normalizeValue(values.email),
        phone: normalizeValue(values.phone),
        website: normalizeValue(values.website),
        billing_street: normalizeValue(values.billing_street),
        billing_city: normalizeValue(values.billing_city),
        billing_state: normalizeValue(values.billing_state),
        billing_code: normalizeValue(values.billing_code),
        from_modal: true
      }

      const response = await fetch(route('company_contact.update', company.id), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      })

      if (response.status === 422) {
        const data = await response.json().catch(() => null)
        const formattedErrors: Record<string, string> = {}
        Object.entries(data?.errors ?? {}).forEach(([field, messages]) => {
          if (Array.isArray(messages) && messages.length > 0) {
            formattedErrors[field] = messages[0]
          }
        })
        helpers.setErrors(formattedErrors)
        return
      }

      if (!response.ok) {
        throw new Error('Failed to update company.')
      }

      const data = await response.json().catch(() => null)
      if (data?.company) {
        onUpdated(data.company)
        onClose()
      }
    } catch (error) {
      helpers.setStatus('Unable to update company. Please try again.')
    } finally {
      helpers.setSubmitting(false)
    }
  }

  return (
    <Modal show={open} closeable onClose={onClose}>
      <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3">
        <div className="text-lg font-bold">Edit Company</div>
        <button type="button" className="text-slate-500 hover:text-slate-700" onClick={onClose}>
          <CloseIcon />
        </button>
      </div>
      <div className="p-5">
        <Formik<CompanyContact>
          initialValues={initialValues}
          validationSchema={companyContactSchema}
          enableReinitialize
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, isSubmitting, status }) => (
            <Form>
              <div className="grid gap-4 grid-cols-2">
                <div className={submitCount ? (errors.name ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="name">Name</label>
                  <Field id="name" name="name" className="form-input" placeholder="Name" />
                  {(submitCount && errors.name) ? <InputError message={errors.name} className="mt-2" /> : null}
                </div>
                <div className={submitCount ? (errors.email ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="email">Email</label>
                  <Field id="email" name="email" type="email" className="form-input" placeholder="Email" />
                  {(submitCount && errors.email) ? <InputError message={errors.email as string} className="mt-2" /> : null}
                </div>
                <div className={submitCount ? (errors.phone ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="phone">Phone</label>
                  <Field id="phone" name="phone" className="form-input" placeholder="Phone" />
                  {(submitCount && errors.phone) ? <InputError message={errors.phone as string} className="mt-2" /> : null}
                </div>
                <div className={submitCount ? (errors.website ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="website">Website</label>
                  <Field id="website" name="website" type="url" className="form-input" placeholder="Website" />
                  {(submitCount && errors.website) ? <InputError message={errors.website as string} className="mt-2" /> : null}
                </div>
                <div className={submitCount ? (errors.billing_street ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="billing_street">Billing Street</label>
                  <Field id="billing_street" name="billing_street" className="form-input" placeholder="Billing Street" />
                  {(submitCount && errors.billing_street) ? <InputError message={errors.billing_street as string} className="mt-2" /> : null}
                </div>
                <div className={submitCount ? (errors.billing_city ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="billing_city">Billing City</label>
                  <Field id="billing_city" name="billing_city" className="form-input" placeholder="Billing City" />
                  {(submitCount && errors.billing_city) ? <InputError message={errors.billing_city as string} className="mt-2" /> : null}
                </div>
                <div className={submitCount ? (errors.billing_state ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="billing_state">Billing State</label>
                  <Field id="billing_state" name="billing_state" className="form-input" placeholder="Billing State" />
                  {(submitCount && errors.billing_state) ? <InputError message={errors.billing_state as string} className="mt-2" /> : null}
                </div>
                <div className={submitCount ? (errors.billing_code ? 'has-error' : 'has-success') : ''}>
                  <label htmlFor="billing_code">Billing Code</label>
                  <Field id="billing_code" name="billing_code" className="form-input" placeholder="Billing Code" />
                  {(submitCount && errors.billing_code) ? <InputError message={errors.billing_code as string} className="mt-2" /> : null}
                </div>
              </div>

              {status && <div className="mt-3 text-sm text-red-600">{status}</div>}

              <div className="mt-5 flex items-center justify-between">
                <button type="button" className="btn btn-danger uppercase" onClick={onClose} disabled={isSubmitting}>
                  Cancel
                </button>
                <button type="submit" className="btn btn-primary" disabled={isSubmitting}>
                  {isSubmitting ? 'Saving...' : 'Save'}
                </button>
              </div>
            </Form>
          )}
        </Formik>
      </div>
    </Modal>
  )
}
