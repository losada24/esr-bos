import React, { useState } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link, usePage } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type Company } from './CompanyCommon'
import { type ModalProps, type Role, type PageProps } from '@/types'
import FeaturedImageModal from '@/Pages/RawMaterial/FeaturedImageModal'
import { isAdmin } from '@/Utils/user'

const CompanyForm = ({ submitCount, errors, isCreate, states, setFieldValue, featured_image, modalProps }: {
  submitCount: number
  errors: FormikErrors<Company>
  isCreate: boolean
  states: string[]
  modalProps: ModalProps | null
  featured_image?: string
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void }) => {
  const [showModal, setShowModal] = useState(false)
  const { auth } = usePage<PageProps>().props
  return (
    <Form className='space-y-5'>
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
      <div className={submitCount ? (errors.phone_number) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="phone">Phone</label>
        <Field
          id="phone_number"
          name="phone_number"
          className="form-input"
          autoComplete="phone_number"
          placeholder='Phone'
        />
        {(submitCount && errors.phone_number) ? <InputError message={errors.phone_number} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.address) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="address">Address</label>
        <Field
          id="address"
          name="address"
          component="textarea"
          rows="4"
          className="form-textarea resize-none placeholder:text-white-dark"
          placeholder='Address'
        />
        {(submitCount && errors.address) ? <InputError message={errors.address} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.city) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="city">City</label>
        <Field
          id="city"
          name="city"
          className="form-input"
          autoComplete="city"
          placeholder='City'
        />
        {(submitCount && errors.city) ? <InputError message={errors.city} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.state) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="state">State</label>
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
      <div className={submitCount ? (errors.zip) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="zip">Zip</label>
        <Field
          id="zip"
          name="zip"
          className="form-input"
          autoComplete="zip"
          placeholder='Zip'
        />
        {(submitCount && errors.zip) ? <InputError message={errors.zip} className="mt-2" /> : ''}
      </div>
      {isAdmin(auth.user.roles.map((role: Role) => role.name)) && (
        <div className='grid grid-cols-2 gap-4'>
          <div className={submitCount ? (errors.markup) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="markup">Markup</label>
            <div className='flex flex-1'>
              <Field
                id="markup"
                name="markup"
                className="form-input text-right rounded-r-none"
                autoComplete="markup"
                placeholder='markup'
                type='number'
              />
              <div className="bg-[#eee] flex justify-center items-center px-3 font-semibold border border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b] rounded-r-md">%</div>
            </div>
            {(submitCount && errors.markup) ? <InputError message={errors.markup} className="mt-2" /> : ''}
          </div>
          <div className={submitCount ? (errors.promotion) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="promotion">Promotion</label>
            <div className='flex flex-1'>
              <Field
                id="promotion"
                name="promotion"
                className="form-input text-right rounded-r-none"
                autoComplete="promotion"
                placeholder='Promotion'
                type='number'
              />
              <div className="bg-[#eee] flex justify-center items-center px-3 font-semibold border border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b] rounded-r-md">%</div>
            </div>
            {(submitCount && errors.promotion) ? <InputError message={errors.promotion} className="mt-2" /> : ''}
          </div>
          <div className={submitCount ? (errors.allow_credit_payment) ? 'has-error inline-flex' : 'has-success inline-flex' : 'inline-flex'}>
            <Field
              id="allow_credit_payment"
              name="allow_credit_payment"
              className="form-checkbox"
              type='checkbox'
            />
            <label htmlFor="allow_credit_payment">Allow Credit Payment</label>
            {(submitCount && errors.allow_credit_payment) ? <InputError message={errors.allow_credit_payment} className="mt-2" /> : ''}
          </div>
        </div>
      )}
      <div className={submitCount ? (errors.featured_image) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="featured_image">Feature Image</label>
        <input
          id="featured_image"
          name="featured_image"
          type="file"
          accept="image/*"
          className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
          placeholder="Qty"
          onChange={(event: any) => {
            setFieldValue('featured_image', event.currentTarget.files[0])
          }}
        />
        {(submitCount && errors.featured_image) ? <InputError message={errors.featured_image} className="mt-2" /> : ''}
        {featured_image && (
          <div className="mt-2">
            <button onClick={(e) => {
              e.preventDefault()
              setShowModal(true)
            }}>
             <img src={featured_image} className="w-20 h-20 object-cover rounded-md overflow-hidden" />
            </button>
          </div>
        )}
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('company.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
      {modalProps && <FeaturedImageModal showModal={showModal} onClose={setShowModal} selectedModalProps={modalProps} />}
    </Form>
  )
}

export default CompanyForm
