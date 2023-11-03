import React, { useState } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type RawMaterial, type ModalProps } from '@/types'
import FeaturedImageModal from './FeaturedImageModal'

const RawMaterialForm = ({ submitCount, errors, isCreate, unit_of_measurement, setFieldValue, featured_image, modalProps }: {
  submitCount: number
  errors: FormikErrors<RawMaterial>
  isCreate: boolean
  unit_of_measurement: string[]
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void
  featured_image?: string
  modalProps: ModalProps | null
}) => {
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
      <div className={submitCount ? (errors.cost_per_unit) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="name">Cost per Unit</label>
        <Field
          id="cost_per_unit"
          name="cost_per_unit"
          type="number"
          className="form-input text-right"
          autoComplete="cost_per_unit"
          placeholder='Cost per Unit'
        />
        {(submitCount && errors.cost_per_unit) ? <InputError message={errors.cost_per_unit} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.qty) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="name">Qty</label>
        <Field
          id="qty"
          name="qty"
          type="number"
          className="form-input text-right"
          autoComplete="qty"
          placeholder="Qty"
        />
        {(submitCount && errors.qty) ? <InputError message={errors.qty} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.unit_of_measurement) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="state">Unit of Measurement</label>
        <Field
          id="unit_of_measurement"
          name="unit_of_measurement"
          className="form-input"
          autoComplete="unit_of_measurement"
          placeholder='Unit of Measurement'
          as="select"
        >
          <option value="">Select Unit of Measurement</option>
          {unit_of_measurement.map((unit, index) => (
            <option key={index} value={unit}>{unit}</option>
          ))}
        </Field>
        {(submitCount && errors.unit_of_measurement) ? <InputError message={errors.unit_of_measurement} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="notes">Notes</label>
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
        <Link className='btn btn-danger uppercase' href={route('raw-material.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
      {modalProps && <FeaturedImageModal showModal={showModal} onClose={setShowModal} selectedModalProps={modalProps} />}
    </Form>
  )
}

export default RawMaterialForm
