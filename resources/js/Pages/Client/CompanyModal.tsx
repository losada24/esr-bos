import React, { useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type CompanyContact } from '@/types'
import { Field, Form, Formik, type FormikHelpers } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import Flatpickr from 'react-flatpickr'
import { companyContactSchema } from '../CompanyContact/CompanyContactCommon'
import { router } from '@inertiajs/react'
import { formatDateOnlyValue, toDateOnlyString } from '@/Utils/dateOnly'

const CompanyModal = ({
  showModal,
  onClose,
  addCompany
}: {
  showModal: boolean
  onClose: () => void
  addCompany: (company: CompanyContact) => void
}) => {
  const initialValues: CompanyContact = {
    id: 0,
    name: '',
    email: '',
    phone: '',
    website: '',
    billing_street: '',
    billing_city: '',
    billing_state: '',
    billing_code: '',
    bid_due_date: null
  }
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  const handleSubmit = async (
    values: CompanyContact,
    helpers: FormikHelpers<CompanyContact>
  ) => {
    try {
      const response = await fetch(route('company_contact.store'), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Content-Type': 'application/json',
          Accept: 'application/json'
        },
        body: JSON.stringify({
          ...values,
          from_modal: true,
          bid_due_date: toDateOnlyString(values.bid_due_date)
        })
      })

      if (!response.ok) {
        const errorData = await response.json()
        helpers.setErrors(errorData.errors || {})
        helpers.setSubmitting(false)
        return
      }

      const data = await response.json()

      if (data.company) {
        addCompany(data.company)
        onClose()
      }
    } catch (error) {
      console.error(error)
      helpers.setSubmitting(false)
    }
  }
  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={onClose}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Add Company</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose() }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className="h-[550px] overflow-y-scroll">
            <Formik<CompanyContact>
                initialValues={initialValues}
                validationSchema={companyContactSchema}
                onSubmit={handleSubmit}
              >
                {({ errors, submitCount, setFieldValue, values }) => (
                  <Form>
                    <div className='grid gap-4 grid-cols-3'>
                        <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="name">Name</label>
                          <Field
                            id="name"
                            name="name"
                            className="form-input"
                            autoComplete="name"
                            placeholder='Name'
                          />
                          {(submitCount && errors.name) ? <InputError message={errors.name} className="mt-2" /> : ''}
                        </div>
                        <div className={`mb-3 ${submitCount ? (errors.email) ? 'has-error' : 'has-success' : ''}`}>
                          <label htmlFor="email">Email</label>
                          <Field
                            id="email"
                            name="email"
                            type="email"
                            className="form-input"
                            autoComplete={false}
                            placeholder='Email'
                          />
                          {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
                        </div>
                        <div className={submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''}>
                          <label htmlFor="phone">Phone</label>
                          <Field
                            id="phone"
                            name="phone"
                            className="form-input"
                            autoComplete={false}
                            placeholder='Phone'
                          />
                          {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
                        </div>
                          <div className={`mb-3 ${submitCount ? (errors.website) ? 'has-error' : 'has-success' : ''}`}>
                          <label htmlFor="website">Website</label>
                          <Field
                            id="website"
                            name="website"
                            type="url"
                            className="form-input"
                            autoComplete={false}
                            placeholder='Website'
                          />
                          {(submitCount && errors.website) ? <InputError message={errors.website} className="mt-2" /> : ''}
                        </div>
                         <div className={`mb-3 ${submitCount ? (errors.billing_street) ? 'has-error' : 'has-success' : ''}`}>
                          <label htmlFor="billing_street">Billing Street</label>
                          <Field
                            id="billing_street"
                            name="billing_street"
                            className="form-input"
                            autoComplete={false}
                            placeholder='Billing Street'
                          />
                          {(submitCount && errors.billing_street) ? <InputError message={errors.billing_street} className="mt-2" /> : ''}
                        </div>
                     <div className={submitCount ? (errors.billing_city) ? 'has-error' : 'has-success' : ''}>
                      <label htmlFor="billing_city">Billing City</label>
                      <Field
                        id="billing_city"
                        name="billing_city"
                        className="form-input"
                        placeholder='Billing City'
                        autoComplete={false}
                      />
                      {(submitCount && errors.billing_city) ? <InputError message={errors.billing_city} className="mt-2" /> : ''}
                      </div>
                      <div className={submitCount ? (errors.billing_state) ? 'has-error' : 'has-success' : ''}>
                      <label htmlFor="billing_state">Billing State</label>
                      <Field
                        id="billing_state"
                        name="billing_state"
                        className="form-input"
                        placeholder='Billing State'
                        autoComplete={false}
                      />
                      {(submitCount && errors.billing_state) ? <InputError message={errors.billing_state} className="mt-2" /> : ''}
                      </div>
                      <div className={submitCount ? (errors.billing_code) ? 'has-error' : 'has-success' : ''}>
                      <label htmlFor="billing_code">Billing Code</label>
                      <Field
                        id="billing_code"
                        name="billing_code"
                        className="form-input"
                        placeholder='Billing Code'
                        autoComplete={false}
                      />
                      {(submitCount && errors.billing_code) ? <InputError message={errors.billing_code} className="mt-2" /> : ''}
                      </div>
                      <div className={submitCount ? (errors.bid_due_date) ? 'has-error' : 'has-success' : ''}>
                        <label htmlFor="bid_due_date">Bid Due Date</label>
                        <Flatpickr
                          options={{
                            mode: 'single',
                            dateFormat: 'Y-m-d',
                            position: 'auto right'
                          }}
                          name="bid_due_date"
                          value={values.bid_due_date ?? ''}
                          className="form-input"
                          onChange={([date]) => {
                            setFieldValue('bid_due_date', date ? formatDateOnlyValue(date) : null)
                          }}
                        />
                        {(submitCount && errors.bid_due_date) ? <InputError message={errors.bid_due_date.toString()} className="mt-2" /> : ''}
                      </div>
                      </div>
                      <div className="flex items-center justify-between mt-4">
                        <button className='btn btn-danger uppercase' onClick={ (e) => {
                          e.preventDefault()
                          onClose()
                        }}>Cancel</button>
                        <PrimaryButton className="btn btn-primary" type='submit'>
                          Add Company
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

export default CompanyModal
