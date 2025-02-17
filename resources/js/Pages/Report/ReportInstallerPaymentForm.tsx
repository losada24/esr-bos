import { useState, useRef, useMemo, useEffect } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, router } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type Order, type InstallationPayment } from '@/types'
import PaymentInstallerTable from './PaymentInstallerTable'

const ReportInstallerPaymentForm = ({
  submitCount,
  errors,
  isCreate,
  installerId,
  setFieldValue,
  values,
  amount,
  order,
  payment

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
}) => {
  const extraWork = Number(order.payment_extra_fields?.extra_work) || 0
  const otherCost = Number(order.payment_extra_fields?.other_cost_installer) || 0
  const extraDiscount = Number(order.payment_extra_fields?.extra_discount) || 0
  const totalAmount = (amount + extraWork + otherCost) - extraDiscount

  console.log(errors.payment_date, typeof errors.payment_date)

  useEffect(() => {
    if (values.percentage_payment) {
      const percentage = Number(values.percentage_payment) || 0
      const calculatedPayment = (totalAmount * percentage) / 100
      setFieldValue('installer_payment', calculatedPayment.toFixed(2)) // Redondeamos a 2 decimales
    }
  }, [values.percentage_payment, totalAmount, setFieldValue])
  return (
    <>
      <Form className='space-y-5'>
      <fieldset className='p-3 border rounded-xl mt-3'>
        <legend className='text-lg font-semibold'>Order data</legend>
        <div className='grid gap-4 grid-cols-3'>
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
            <label htmlFor="extra_discount">Perecentage Payment % {amount} {order.payment_extra_fields?.extra_work}</label>
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
                             // value={values.payment_date?.toString()}
                             className="form-input"
                             onChange={([date]) => {
                               setFieldValue('payment_date', date.toISOString().slice(0, 10))
                             }}
                           />
                          {submitCount && errors.payment_date ? (<InputError message= {errors.payment_date} className="mt-2"/>) : null}
                         </div>
        </div>
        <div className="flex items-center justify-between mt-4">
          <PrimaryButton className="btn btn-primary" type='submit'>
            {isCreate ? 'Create' : 'Save'}
          </PrimaryButton>
        </div>
        <PaymentInstallerTable
            values={values}
            amount={amount}
             order={order}
             payment={payment}
          />
      </fieldset>
      </Form>
    </>
  )
}

export default ReportInstallerPaymentForm
