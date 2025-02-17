import { useState, useRef, useMemo } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type PaymentExtraFields } from '@/types'

const ReportInstallerForm = ({
  submitCount,
  errors,
  isCreate,
  installerId,
  installer_payment_status,
  setFieldValue,
  values

}: {
  submitCount: number
  errors: FormikErrors<PaymentExtraFields>
  isCreate: boolean
  installerId: number
  installer_payment_status: string []
  setFieldValue: (field: string, value: any) => void
  values: PaymentExtraFields
}) => {
  return (
    <>
      <Form className='space-y-5'>
      <fieldset className='p-3 border rounded-xl mt-3'>
        <legend className='text-lg font-semibold'>Order data</legend>
        <div className='grid gap-4 grid-cols-3'>
          <div className={submitCount ? (errors.responsible_extra_work) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="last_name">Responsible Extra Work</label>
              <Field
                id="responsible_extra_work"
                name="responsible_extra_work"
                className="form-input rounded-r-none"
                autoComplete="responsible_extra_work"
              />
              {(submitCount && errors.responsible_extra_work) ? <InputError message={errors.responsible_extra_work} className="mt-2" /> : ''}
          </div>
          <div className={submitCount ? (errors.documents_submitted) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="documents_submitted">Documents Submited</label>
              <Field
                id="documents_submitted"
                name="documents_submitted"
                className="form-input rounded-r-none"
                autoComplete="documents_submited"
              />
              {(submitCount && errors.documents_submitted) ? <InputError message={errors.documents_submitted} className="mt-2" /> : ''}
          </div>
          <div className ='flex mt-8'>
            <Field
              id="collected_payment"
              name="collected_payment"
              className="form-checkbox"
              type='checkbox'
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                setFieldValue('collected_payment', e.target.checked)
              } }
            />
            <label htmlFor="collected_payment" className='font-bold inline-flex'>Collect Payment</label>
          </div>
          <div className={submitCount ? (errors.extra_work) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="extra_work">Extra Work Cost</label>
            <Field
              id="extra_work"
              name="extra_work"
              className="form-input text-right"
              autoComplete="extra_work"
              placeholder='Extra Work Cost'
              type='number'
            />
            {(submitCount && errors.extra_work) ? <InputError message={errors.extra_work} className="mt-2" /> : ''}
          </div>
          <div className={submitCount ? (errors.extra_discount) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="extra_discount">Discount(-)</label>
            <Field
              id="extra_discount"
              name="extra_discount"
              className="form-input text-right"
              autoComplete="extra_discount"
              placeholder='Extra Discount'
              type='number'
            />
            {(submitCount && errors.extra_discount) ? <InputError message={errors.extra_discount} className="mt-2" /> : ''}
          </div>
          <div className={submitCount ? (errors.other_cost_installer) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="other_cost_installer">Other Cost Installtion(+)</label>
            <Field
              id="other_cost_installer"
              name="other_cost_installer"
              className="form-input text-right"
              autoComplete="other_cost_installer"
              placeholder='Extra Discount'
              type='number'
            />
            {(submitCount && errors.other_cost_installer) ? <InputError message={errors.other_cost_installer} className="mt-2" /> : ''}
          </div>
          <div className={submitCount ? (errors.installer_payment_status) ? 'has-error' : 'has-success' : ''}>
                        <label htmlFor="method_of_payment">Installer Payment Status</label>
                        <Field
                          id="installer_payment_status"
                          name="installer_payment_status"
                          className="form-select"
                          autoComplete="installer_payment_status"
                          placeholder='Installer Payment Status'
                          as="select"
                          onChange={(e: { target: { value: string } }) => {
                            setFieldValue('installer_payment_status', e.target.value)
                          }}
                        >
                          <option value="">Installer Payment Status</option>
                          {installer_payment_status.map((installer_payment_status, index) => (
                            <option key={index} value={installer_payment_status}>{installer_payment_status}</option>
                          ))}
                        </Field>
                        {(submitCount && errors.installer_payment_status) ? <InputError message={errors.installer_payment_status} className="mt-2" /> : ''}
                      </div>
          <div className='col-span-3'>
          <div className={submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="notes">Notes</label>
              <Field
                id="notes"
                name="notes"
                component="textarea"
                rows="6"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
              />
              {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
          </div>
          </div>
        </div>
        <div className="flex items-center justify-between mt-4">
          <PrimaryButton className="btn btn-primary" type='submit'>
            {isCreate ? 'Create' : 'Save'}
          </PrimaryButton>
        </div>
      </fieldset>
      </Form>
    </>
  )
}

export default ReportInstallerForm
