import { useState, useRef, useMemo } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type BiweeklyInstaller } from '@/types'

const ReportBiweeklyForm = ({
  submitCount,
  errors,
  isCreate,
  installerId,
  method_payment,
  setFieldValue,
  values

}: {
  submitCount: number
  errors: FormikErrors<BiweeklyInstaller>
  isCreate: boolean
  installerId: number
  method_payment: string []
  setFieldValue: (field: string, value: any) => void
  values: BiweeklyInstaller
}) => {
  return (
    <>
      <Form className='space-y-5'>
      <fieldset className='p-3 border rounded-xl mt-3'>
        <legend className='text-lg font-semibold'>Biweekly data</legend>
        <div className='grid gap-4 grid-cols-3'>
          <div className={submitCount ? (errors.period) ? 'has-error' : 'has-success' : ''}>
                    <label htmlFor="period">Payment Date</label>
                   <Flatpickr
                     options={{
                       mode: 'range',
                       dateFormat: 'Y-m-d',
                       position: 'auto right'
                     }}
                     name="period"
                     value={[values.period[0], values.period[1]]}

                     className="form-input"
                     onChange={(dates: Date[]) => {
                       if (dates.length === 2) {
                         const [startDate, endDate] = dates
                         setFieldValue('period', [
                           startDate.toISOString().slice(0, 10),
                           endDate.toISOString().slice(0, 10)
                         ])
                       }
                     }}
                   />
                   {submitCount && errors.period ? (<InputError message= {errors.period.toString()} className="mt-2"/>) : null}
                 </div>
          <div className={submitCount ? (errors.payment_method) ? 'has-error' : 'has-success' : ''}>
                        <label htmlFor="installer_payment_status">Installer Payment Method</label>
                        <Field
                          id="payment_method"
                          name="payment_method"
                          className="form-select"
                          autoComplete="payment_method"
                          placeholder='Installer Payment Status'
                          as="select"
                          onChange={(e: { target: { value: string } }) => {
                            setFieldValue('payment_method', e.target.value)
                          }}
                        >
                          <option value="">Installer Payment Method</option>
                          {method_payment.map((method_payment, index) => (
                            <option key={index} value={method_payment}>{method_payment}</option>
                          ))}
                        </Field>
                        {(submitCount && errors.payment_method) ? <InputError message={errors.payment_method} className="mt-2" /> : ''}
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

export default ReportBiweeklyForm
