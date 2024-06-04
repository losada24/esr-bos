import React, { useState } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type EstimateCommentsUpdate } from './EstimateCommon'

const RequestCustomizationForm = ({ submitCount, errors, isCreate, setFieldValue, estimateId, attachment }: {
  submitCount: number
  errors: FormikErrors<EstimateCommentsUpdate>
  isCreate: boolean
  setFieldValue: CallableFunction
  estimateId: number
  attachment: string
}) => {
  return (
    <Form className='space-y-5'>
      <div className={submitCount ? (errors.comments) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="comments">Comments</label>
        <Field
          id="comments"
          name="comments"
          component="textarea"
          rows="4"
          className="form-textarea resize-none placeholder:text-white-dark"
          placeholder='Comments'
        />
        {(submitCount && errors.comments) ? <InputError message={errors.comments} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.attachment) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="attachment">Attachment</label>
        <input
          id="attachment"
          name="attachment"
          type="file"
          accept="image/*"
          className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
          placeholder="Attachment"
          onChange={(event: any) => {
            setFieldValue('attachment', event.currentTarget.files[0])
          }}
        />
        {(submitCount && errors.attachment) ? <InputError message={errors.attachment} className="mt-2" /> : ''}
        {attachment !== '' && (
          <a href={`/storage/${attachment}`} target="_blank" className="text-primary underline" rel="noreferrer">View Attachment</a>
        )}
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('estimate.show', estimateId)}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default RequestCustomizationForm
