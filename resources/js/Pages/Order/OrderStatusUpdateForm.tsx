import React, { useState } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type OrderStatusUpdate } from './OrderCommon'
import { type Status } from '@/types'
import ReactQuill from 'react-quill'
import { modules, textFormats } from '@/Utils/ReactQuillConfig'

const OrderStatusUpdateForm = ({ submitCount, errors, isCreate, statuses, setFieldValue }: {
  submitCount: number
  errors: FormikErrors<OrderStatusUpdate>
  isCreate: boolean
  setFieldValue: CallableFunction
  statuses: Status[] }) => {
  const [noteValue, setNoteValue] = useState('')
  return (
    <Form className='space-y-5'>
      <div className={submitCount ? (errors.status) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="status">Status</label>
        <Field
          id="status"
          name="status"
          className="form-select"
          autoComplete="status"
          placeholder='Status'
          as="select"
        >
          <option value="">Select Status</option>
          {statuses.map((status, index) => (
            <option key={index} value={status.value}>{status.label.toUpperCase()}</option>
          ))}
        </Field>
        {(submitCount && errors.status) ? <InputError message={errors.status} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="notes">Notes</label>
        <ReactQuill
          theme="snow"
          value={noteValue}
          defaultValue={noteValue}
          onChange={(note) => {
            setNoteValue(note)
            setFieldValue('notes', note)
          }}
          style={{ minHeight: '200px' }}
          modules={modules}
          formats={textFormats}
        />
        {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('order.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default OrderStatusUpdateForm
