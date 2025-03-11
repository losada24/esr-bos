import { useState, useRef, useMemo, useEffect } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type Order, type InstallationPayment, type BiweeklyInstaller } from '@/types'
import PaymentInstallerTable from './PaymentInstallerTable'
import Select, { type SingleValue } from 'react-select'

const ReportInstallerPaymentForm = ({
  submitCount,
  errors,
  isCreate,
  installerId,
  setFieldValue,
  values,
  amount,
  order,
  payment,
  biweeklys,
  payment_status

}: {
  submitCount: number
  errors: FormikErrors<InstallationPayment>
  isCreate: boolean
  installerId: number
  setFieldValue: (field: string, value: any) => void
  values: InstallationPayment
  amount: number
  order: Order
  payment: InstallationPayment []
  biweeklys: BiweeklyInstaller []
  payment_status: string []
}) => {
  const extraWork = Number(values.extra_work) || 0
  const otherCost = Number(values.other_cost_installer) || 0
  const extraDiscount = Number(values.extra_discount) || 0
  const getBiweeklyLabel = (id: number) => {
    const biweekly = biweeklys.find((biweekly) => biweekly.id === id)
    if (!biweekly) {
      return ''
    }
    return `${biweekly.start_biweekly_period ? new Date(biweekly.start_biweekly_period).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', day: 'numeric' }) : 'Unknown'} to ${biweekly.end_biweekly_period ? new Date(biweekly.end_biweekly_period).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', day: 'numeric' }) : 'Unknown'}`
  }
  const loadPaymentData = (installationPayment: InstallationPayment) => {
    setFieldValue('id', installationPayment.id)
    setFieldValue('installer_payment', installationPayment.installer_payment)
    setFieldValue('percentage_payment', installationPayment.percentage_payment)
    setFieldValue('payment_date', installationPayment.payment_date)
    setFieldValue('extra_work', installationPayment.extra_work ?? 0.00)
    setFieldValue('extra_discount', installationPayment.extra_discount ?? 0.00)
    setFieldValue('other_cost_installer', installationPayment.other_cost_installer ?? 0.00)
    setFieldValue('payment_status', installationPayment.payment_status)
    setFieldValue('biweekly_id', installationPayment.biweekly_id)
    setFieldValue('responsible_extra_work', installationPayment.responsible_extra_work ?? '')
    setFieldValue('notes', installationPayment.notes ?? '')
  }

  useEffect(() => {
    if (values.percentage_payment) {
      const percentage = Number(values.percentage_payment) || 0
      const calculatedPayment = (amount * percentage) / 100
      setFieldValue('installer_payment', calculatedPayment.toFixed(2)) // Redondeamos a 2 decimales
    }
  }, [values.percentage_payment, amount, setFieldValue])
  return (
    <>
      <Form className='space-y-5'>
      <fieldset className='p-3 border rounded-xl mt-3'>
        <legend className='text-lg font-semibold'>Order data</legend>
        <div className='grid gap-4 grid-cols-3'>
       {/* <div className={submitCount ? (errors.biweekly_id) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="biweekly_id">Biweekly</label>
            <Select
              id='biweekly_id'
              placeholder="Biweekly"
              name='biweekly_id'
              value={values.biweekly_id ? { value: values.biweekly_id, label: getBiweeklyLabel(values.biweekly_id) } : null}
              onChange={(value) => { setFieldValue('biweekly_id', value?.value) }}
              options={biweeklys.map((biweekly) => {
                return {
                  label: `${biweekly.start_biweekly_period ? new Date(biweekly.start_biweekly_period).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', day: 'numeric' }) : 'Unknown'} to ${biweekly.end_biweekly_period ? new Date(biweekly.end_biweekly_period).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', day: 'numeric' }) : 'Unknown'}`,
                  value: biweekly.id
                }
              })}
            />
            {(submitCount && errors.biweekly_id) ? <InputError message={errors.biweekly_id} className="mt-2" /> : ''}
          </div> */}
          <div className={submitCount ? (errors.installer_payment) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="extra_work">Installer Payment</label>
            <Field
              id="installer_payment"
              name="installer_payment"
              className="form-input text-right"
              autoComplete="installer_payment"
              placeholder='installer_payment'
              type='number'
            />
            {(submitCount && errors.installer_payment) ? <InputError message={errors.installer_payment} className="mt-2" /> : ''}
          </div>
          <div className={submitCount ? (errors.percentage_payment) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="extra_discount">Perecentage Payment % </label>
            <Field
              id="percentage_payment"
              name="percentage_payment"
              className="form-input text-right"
              autoComplete="percentage_payment"
              placeholder='Perecentage Payment'
              type='number'
            />
            {(submitCount && errors.percentage_payment) ? <InputError message={errors.percentage_payment} className="mt-2" /> : ''}
          </div>
         <div className={submitCount ? (errors.payment_date) ? 'has-error' : 'has-success' : ''}>
           <label htmlFor="payment_date">Payment Date</label>
          <Flatpickr
            options={{
              mode: 'single',
              dateFormat: 'Y-m-d',
              position: 'auto right'
            }}
            name="payment_date"
            value={values.payment_date
              ? new Date(values.payment_date).toISOString().split('T')[0] // Convierte a UTC y formatea
              : undefined
            }
            className="form-input"
            onChange={([date]) => {
              setFieldValue('payment_date', date.toISOString().slice(0, 10))
            }}
          />
          {submitCount && errors.payment_date ? (<InputError message= {errors.payment_date} className="mt-2"/>) : null}
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
            // value={values.extra_work}
          />
          {(submitCount && errors.extra_work) ? <InputError message={errors.extra_work} className="mt-2" /> : ''}
        </div>
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
            <div className={submitCount ? (errors.payment_status) ? 'has-error' : 'has-success' : ''}>
             <label htmlFor="payment_status">Payment Status</label>
                <Field
                  id="payment_status"
                  name="payment_status"
                  className="form-select"
                  autoComplete="payment_status"
                  placeholder='Payment Status'
                  as="select"
                  onChange={(e: { target: { value: string } }) => {
                    setFieldValue('payment_status', e.target.value)
                  }}
                >
                <option value="">Payment Status</option>
                {payment_status.map((payment_statu, index) => (
                <option key={index} value={payment_statu}>{payment_statu}</option>
                ))}
              </Field>
              {(submitCount && errors.payment_status) ? <InputError message={errors.payment_status} className="mt-2" /> : ''}
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
            {values.id === 0 ? 'Create' : 'Save'}
          </PrimaryButton>
        </div>
        <PaymentInstallerTable
            values={values}
            amount={amount}
             order={order}
             payment={payment}
             loadPaymentData={loadPaymentData}
          />
      </fieldset>
      </Form>
    </>
  )
}

export default ReportInstallerPaymentForm
