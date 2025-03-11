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
        {/* <div className ='flex mt-8'>
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
          </div> */}
          <div className={submitCount ? (errors.installer_payment_status) ? 'has-error' : 'has-success' : ''}>
                        <label htmlFor="installer_payment_status">Installer Payment Status</label>
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
