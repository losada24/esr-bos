import React, { useState } from 'react'
import Modal from '@/Components/Modal'
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
              return { ...pipeline, tasks: [...pipeline.tasks, lostTask] }
            }
          }
          return pipeline
        })
      )
    }
    onClose()
  }
  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={handleClose}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Loss Request</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={handleClose}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
            <div className="max-h-[400px] overflow-y-auto">
            <Formik<OrderStatusUpdate>
                initialValues={initialValues}
                validationSchema={orderSchema}
                onSubmit={handleSubmit}
              >
                {({ errors, submitCount, setFieldValue, values }) => (
                  <Form>
                    <div className='grid gap-4 grid-cols-3'>
                        <div className={submitCount ? (errors.loss_reason_frontdesk) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="loss_reason_frontdesk">Loss Reason</label>
                          <Field
                            id="loss_reason_frontdesk"
                            name="loss_reason_frontdesk"
                            className="form-select"
                            autoComplete="loss_reason_frontdesk"
                            placeholder='Source'
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
                          {(submitCount && errors.loss_reason_frontdesk) ? <InputError message={errors.loss_reason_frontdesk} className="mt-2" /> : ''}
                        </div>
                        <div className='col-span-3'>
                          <label htmlFor="notes"> Notes</label>
                          <Field
                            id="notes"
                            name="notes"
                            component="textarea"
                            rows="3"
                            className="form-textarea resize-none placeholder:text-white-dark"
                            placeholder='Notes'
                          />
                        </div>
                      </div>
                      <div className="flex items-center justify-between mt-4">
                        <button className='btn btn-danger uppercase' onClick={handleClose}>Cancel</button>
                        <PrimaryButton className="btn btn-primary" type='submit'>
                          Save Changes
                        </PrimaryButton>
                      </div>
                  </Form>
                )}
            </Formik>
          </div>
        </div>
    </Modal>
  )
}

export default LostRequestModal
