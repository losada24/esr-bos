import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type PaymentInfo, type Order } from '@/types'
import { PAYMENT_METHODS, ADDRESS_REQUIRED_AFTER_AMOUNT } from '@/Utils/constants'
import Alert from '@/Components/Alert'
import { getSubtotal } from '@/Utils/price'
import PaymentSummary from './PaymentSummary'

const PaymentForm = ({ submitCount, errors, estimate, values, states }: {
  submitCount: number
  errors: FormikErrors<PaymentInfo>
  estimate: Order
  values: PaymentInfo
  states: string[]
}) => {
  const subtotal = getSubtotal(estimate) ?? 0
  return (
    <Form className='w-full'>
      <div className='grid grid-cols-12 gap-4 mb-4'>
        <div className='space-y-5 col-span-4'>
          <div className=''>
            <label className="inline-flex" htmlFor='cashPaymentMethod'>
                <Field
                  id="cashPaymentMethod"
                  type="radio"
                  className="form-checkbox rounded-full"
                  name='method'
                  value={PAYMENT_METHODS.CASH}
                />
                <span>Cash/Zelle</span>
            </label>
            {values.method === PAYMENT_METHODS.CASH && (
              <Alert className="relative flex items-center border p-3.5 rounded text-dark bg-dark-light border-dark dark:bg-dark-dark-light dark:text-white-light dark:border-white-light/20">
                <p>Pay with cash in Store or Zelle transfer. Your products will be available at the time the payment is made.</p>
                <p><strong>Zelle Number:</strong> 786-620-6183</p>
              </Alert>
            )}
          </div>
          <div className=''>
            <label className="inline-flex" htmlFor='checkPaymentMethod'>
                <Field
                  id="checkPaymentMethod"
                  type="radio"
                  className="form-checkbox rounded-full"
                  name='method'
                  value={PAYMENT_METHODS.CHECK}
                />
                <span>Check Payment</span>
            </label>
            {values.method === PAYMENT_METHODS.CHECK && (
              <Alert className="relative flex items-center border p-3.5 rounded text-dark bg-dark-light border-dark dark:bg-dark-dark-light dark:text-white-light dark:border-white-light/20">
                <p>Please send a check to Reylos Glazing, 12400 SW 134th Court Suite 12 Miami, FL 33186 or Send a legible photo on both sides to the phone number (786) 337-1431. Your order will not be available until the funds have cleared in our account. Estimated time of 3 to 5 days.</p>
              </Alert>
            )}
          </div>
          <div className=''>
            <label className="inline-flex" htmlFor='bankPaymentMethod'>
                <Field
                  id="bankPaymentMethod"
                  type="radio"
                  className="form-checkbox rounded-full"
                  name='method'
                  value={PAYMENT_METHODS.BANK_TRANSFER}
                />
                <span>Direct Bank Transfer</span>
            </label>
            {values.method === PAYMENT_METHODS.BANK_TRANSFER && (
              <Alert className="relative flex items-center border p-3.5 rounded text-dark bg-dark-light border-dark dark:bg-dark-dark-light dark:text-white-light dark:border-white-light/20">
                <p>Make your payment directly into our bank account. Please use your Order ID as the payment reference. Your order will not be available until the funds have cleared in our account. Estimated time of 3 to 5 days.</p>
                <p><strong>Bank name:</strong> Truist Bank</p>
                <p><strong>Account number:</strong> 1100028830279</p>
                <p><strong>Routing number:</strong> 263191387</p>
              </Alert>
            )}
          </div>
          {estimate?.user?.company?.allow_credit_payment && (
            <div className=''>
              <label className="inline-flex" htmlFor='creditPaymentMethod'>
                  <Field
                    id="creditPaymentMethod"
                    type="radio"
                    className="form-checkbox rounded-full"
                    name='method'
                    value={PAYMENT_METHODS.CREDIT}
                  />
                  <span>RG Impact System Credit</span>
              </label>
            </div>
          )}
          <div>
            {(submitCount && errors.method) ? <InputError message={errors.method} className="mt-2 mb-2" /> : ''}
          </div>
        </div>
        {(values.method === PAYMENT_METHODS.CREDIT || subtotal >= ADDRESS_REQUIRED_AFTER_AMOUNT) && (
          <div className='space-y-5 col-span-8'>
            <div className={submitCount ? (errors.street_address) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="name">Street Address</label>
              <Field
                id="street_address"
                name="street_address"
                className="form-input"
                placeholder='Street Address'
              />
              {(submitCount && errors.street_address) ? <InputError message={errors.street_address} className="mt-2" /> : ''}
            </div>
            <div className='grid grid-cols-3 gap-4'>
              <div className={submitCount ? (errors.city) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="name">City</label>
                <Field
                  id="city"
                  name="city"
                  className="form-input"
                  placeholder='City'
                />
                {(submitCount && errors.city) ? <InputError message={errors.city} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.state) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="name">State</label>
                <Field
                  id="state"
                  name="state"
                  className="form-select"
                  autoComplete="state"
                  placeholder='State'
                  as="select"
                >
                  <option value="">Select State</option>
                  {states.map((state, index) => (
                    <option key={index} value={state}>{state}</option>
                  ))}
                </Field>
                {(submitCount && errors.state) ? <InputError message={errors.state} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.zip_code) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="name">Zip Code</label>
                <Field
                  id="zip_code"
                  name="zip_code"
                  className="form-input"
                  placeholder='Zip Code'
                />
                {(submitCount && errors.zip_code) ? <InputError message={errors.zip_code} className="mt-2" /> : ''}
              </div>
            </div>
            <div className={submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="name">Notes</label>
              <Field
                id="notes"
                name="notes"
                component="textarea"
                rows="4"
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
              />
              {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
            </div>
          </div>
        )}
      </div>
      <PaymentSummary estimate={estimate} />
      <div className={submitCount ? (errors.terms_and_conditions_agreed) ? 'has-error inline-flex' : 'has-success inline-flex' : 'inline-flex'}>
        <Field
          id="terms_and_conditions_agreed"
          name="terms_and_conditions_agreed"
          className="form-checkbox"
          type='checkbox'
        />
        <label htmlFor="terms_and_conditions_agreed">I have read and agree to the website <a href='/files/RG Impact System Manufacturing Terms & Conditions PDF.pdf' className='text-primary'>terms and conditions</a></label>
      </div>
      {(submitCount && errors.terms_and_conditions_agreed)
        ? <div><InputError message={errors.terms_and_conditions_agreed} className="mt-2" /></div>
        : ''}
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('estimate.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          Save
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default PaymentForm
