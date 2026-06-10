import React from 'react'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Pipelines, type CompanyContact } from '@/types'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import Flatpickr from 'react-flatpickr'
import { companyContactSchema } from '../CompanyContact/CompanyContactCommon'
import { type Tasks } from '@/types/interfaces/pipelines'
import { orderSchema, type OrderStatusUpdate } from './OrderCommon'
import { router } from '@inertiajs/react'

const LostRequestModal = ({
  lostTask,
  onClose,
  previousStatusId,
  setProjectList,
  updateOrderStatus,
  lostStatusId,
  showModal,
  lossReasonFrontdesk
}: {
  lostTask: Tasks | null
  onClose: () => void
  setProjectList: React.Dispatch<React.SetStateAction<Pipelines[]>>
  updateOrderStatus: (orderId: number, newStatus: string) => Promise<void>
  lostStatusId: string
  showModal: boolean
  previousStatusId: string | null
  lossReasonFrontdesk: string[]
}) => {
  const initialValues: OrderStatusUpdate = {
    id: 0,
    status: '',
    loss_reason_frontdesk: '',
    notes: ''
  }
  console.log('Loss Reason Frontdesk:', lostTask)
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  const handleSubmit = async (
    values: OrderStatusUpdate,
    helpers: FormikHelpers<OrderStatusUpdate>
  ) => {
    try {
      const response = await fetch(route('frontdesk.updateStatusLost', { order: lostTask?.id ?? 0 }), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json',
          Accept: 'application/json'
        },
        body: JSON.stringify({
          ...values,
          from_modal: true,
          id: lostTask?.id,
          status: lostStatusId,
          loss_reason_frontdesk: values.loss_reason_frontdesk,
          notes: values.notes
        })
      })

      if (!response.ok) {
        const errorData = await response.json()
        helpers.setErrors(errorData.errors || {})
        helpers.setSubmitting(false)
        return
      }
      await response.json()
      setTimeout(() => {
        router.visit(route('frontdesk.index'))
      }, 100)
    } catch (error) {
      console.error(error)
      helpers.setSubmitting(false)
    }
  }
  const handleClose = () => {
    if (lostTask && previousStatusId) {
      setProjectList(prev =>
        prev.map(pipeline => {
          if (pipeline.id.toString() === previousStatusId) {
            const taskExists = pipeline.tasks.some(t => t.id === lostTask.id)
            if (!taskExists) {
              const nextTotal = (pipeline.total_tasks ?? pipeline.tasks.length) + 1
              return { ...pipeline, tasks: [...pipeline.tasks, lostTask], total_tasks: nextTotal }
            }
          }
          return pipeline
        })
      )
    }
    onClose()
  }
  if (!showModal) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-slate-800">Loss Request</h3>
          <button
            type="button"
            className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            onClick={handleClose}
          >
            <CloseIcon />
          </button>
        </div>

        <Formik<OrderStatusUpdate>
          initialValues={initialValues}
          validationSchema={orderSchema}
          onSubmit={handleSubmit}
        >
          {({ errors, submitCount, setFieldValue }) => (
            <Form className="space-y-5">
              <div className={submitCount ? (errors.loss_reason_frontdesk ? 'has-error' : 'has-success') : ''}>
                <label htmlFor="loss_reason_frontdesk" className="mb-1 block text-sm font-medium text-slate-600">Loss Reason</label>
                <Field
                  id="loss_reason_frontdesk"
                  name="loss_reason_frontdesk"
                  className="form-select"
                  autoComplete="loss_reason_frontdesk"
                  placeholder='Select loss reason'
                  as="select"
                  onChange={(e: { target: { value: string } }) => {
                    setFieldValue('loss_reason_frontdesk', e.target.value)
                  }}
                >
                  <option value="">Loss Reason</option>
                  {lossReasonFrontdesk.map((loss, index) => (
                    <option key={index} value={loss}>{loss}</option>
                  ))}
                </Field>
                {(submitCount && errors.loss_reason_frontdesk) ? <InputError message={errors.loss_reason_frontdesk} className="mt-2" /> : null}
              </div>

              <div>
                <label htmlFor="notes" className="mb-1 block text-sm font-medium text-slate-600">Notes</label>
                <Field
                  id="notes"
                  name="notes"
                  component="textarea"
                  rows="4"
                  className="form-textarea resize-none placeholder:text-slate-400"
                  placeholder='Add more details about the loss'
                />
              </div>

              <div className="mt-6 flex items-center justify-end gap-3">
                <button
                  type="button"
                  onClick={handleClose}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                  Cancelar
                </button>
                <PrimaryButton
                  type='submit'
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                >
                  Save Changes
                </PrimaryButton>
              </div>
            </Form>
          )}
        </Formik>
      </div>
    </div>
  )
}

export default LostRequestModal
