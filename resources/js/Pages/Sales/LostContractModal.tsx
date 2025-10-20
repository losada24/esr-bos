import InputError from '@/Components/InputError'
import CloseIcon from '@/Components/Icons/CloseIcon'
import PrimaryButton from '@/Components/PrimaryButton'
import { Field, Form, Formik } from 'formik'

interface LostContractFormValues {
  lossReason: string
  notes: string
}

export interface LostContractModalProps {
  open: boolean
  lossReasons: string[]
  loading?: boolean
  error?: string | null
  onSubmit: (values: { lossReason: string, notes: string }) => void
  onCancel: () => void
}

export default function LostContractModal ({
  open,
  lossReasons,
  loading = false,
  error,
  onSubmit,
  onCancel
}: LostContractModalProps) {
  if (!open) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-slate-800">Lost Contract</h3>
          <button
            type="button"
            className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            onClick={onCancel}
            disabled={loading}
          >
            <CloseIcon />
          </button>
        </div>

        <Formik<LostContractFormValues>
          initialValues={{ lossReason: '', notes: '' }}
          validate={(values) => {
            const issues: Partial<Record<keyof LostContractFormValues, string>> = {}
            if (!values.lossReason) {
              issues.lossReason = 'Select a loss reason.'
            }
            if (!values.notes || values.notes.trim() === '') {
              issues.notes = 'Notes are required.'
            }
            return issues
          }}
          onSubmit={(values) => {
            onSubmit({
              lossReason: values.lossReason,
              notes: values.notes.trim(),
            })
          }}
        >
          {({ errors, submitCount, handleChange, handleBlur, values }) => (
            <Form className="space-y-4">
              <div className={submitCount ? (errors.lossReason ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="lossReason">Loss Reason</label>
                <Field
                  as="select"
                  id="lossReason"
                  name="lossReason"
                  className="form-select"
                  value={values.lossReason}
                  onChange={handleChange}
                  onBlur={handleBlur}
                  disabled={loading}
                >
                  <option value="">Loss Reason</option>
                  {lossReasons.map((reason) => (
                    <option key={reason} value={reason}>{reason}</option>
                  ))}
                </Field>
                {submitCount && errors.lossReason
                  ? <InputError message={errors.lossReason} className="mt-2" />
                  : null}
              </div>

              <div className={submitCount ? (errors.notes ? 'has-error' : 'has-success') : ''}>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="notes">Notes</label>
                <Field
                  as="textarea"
                  id="notes"
                  name="notes"
                  rows={4}
                  className="form-textarea resize-none"
                  placeholder='Add context about the lost contract'
                  disabled={loading}
                />
                {submitCount && errors.notes
                  ? <InputError message={errors.notes} className="mt-2" />
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
                  onClick={onCancel}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-70"
                  disabled={loading}
                >
                  Cancel
                </button>
                <PrimaryButton
                  type='submit'
                   className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={loading}
                >
                  {loading ? 'Saving…' : 'Save Changes'}
                </PrimaryButton>
              </div>
            </Form>
          )}
        </Formik>
      </div>
    </div>
  )
}
