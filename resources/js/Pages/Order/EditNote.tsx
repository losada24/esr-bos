import React, { useState } from 'react'
import ReactQuill from 'react-quill'
import 'react-quill/dist/quill.snow.css'
import { type OrderStatus } from '@/types'
import { Form, Formik, type FormikHelpers } from 'formik'
import { orderStatusUpdateSchema } from './OrderCommon'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { router } from '@inertiajs/react'
import { modules, textFormats } from '@/Utils/ReactQuillConfig'

const EditNote = ({ status, onComplete /* , setEditNote */ }: {
  status: OrderStatus
  onComplete: CallableFunction
}) => {
  const [noteValue, setNoteValue] = useState(status.notes)
  const initialValues: OrderStatus = {
    notes: status.notes,
    status: status.status,
    id: status.id,
    order_id: status.order_id,
    created_at: status.created_at,
    updated_at: status.updated_at,
    user: status.user
  }

  const handleSubmit = async (values: OrderStatus, helpers: FormikHelpers<OrderStatus>) => {
    router.post(route('order.notes.update'), {
      notes: noteValue,
      id: values.id
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        values.notes = noteValue
        onComplete(values)
      },
      onError: (errors: any) => {
        helpers.setErrors(errors)
      }
    })
  }

  return (
    <Formik<OrderStatus>
      initialValues={initialValues}
      validationSchema={orderStatusUpdateSchema}
      onSubmit={handleSubmit}
    >
      {({ errors, submitCount }) => (
        <Form>
          <div className={submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="glass_type">Notes</label>
              <ReactQuill
                theme="snow"
                value={noteValue}
                defaultValue={noteValue}
                onChange={setNoteValue}
                style={{ minHeight: '200px' }}
                modules={modules}
                formats={textFormats}
              />
              {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
              {(submitCount && errors.id) ? <InputError message={errors.id} className="mt-2" /> : ''}
          </div>
          <div className="flex items-center justify-between mt-4">
            <button className='btn btn-danger uppercase' onClick={() => { onComplete(null) }}>Cancel</button>
            <PrimaryButton className="btn btn-primary" type='submit'>
              Save
            </PrimaryButton>
          </div>
        </Form>
      )}
    </Formik>
  )
}

export default EditNote
