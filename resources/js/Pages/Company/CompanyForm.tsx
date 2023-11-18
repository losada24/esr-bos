import React, { useState } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type Company } from './CompanyCommon'
import { type ModalProps } from '@/types'
import FeaturedImageModal from '@/Pages/RawMaterial/FeaturedImageModal'

const CompanyForm = ({ submitCount, errors, isCreate, states, setFieldValue, featured_image, modalProps }: {
  submitCount: number
  errors: FormikErrors<Company>
  isCreate: boolean
  states: string[]
  modalProps: ModalProps | null
  featured_image?: string
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void }) => {
  const [showModal, setShowModal] = useState(false)
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
          className="form-input"
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
