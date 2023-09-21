import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { STATUS, type Status } from '@/Utils/constants'

const ReferredForm = ({ submitCount, errors, touched, values, defaultStatus, setFieldValue }) => {
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
      <div className={`mb-3 ${submitCount ? (errors.email) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="email">Email</label>
        <Field
          id="email"
          name="email"
          type="email"
          className="form-input"
          placeholder='Email'
        />
        {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.status ? 'has-error' : 'has-success') : ''}>
        <label htmlFor="status">Status</label>
        <Field as="select" name="status" className="form-select" onChange={(e) => {
          setFieldValue('status', e.target.value)
          if (e.target.value === defaultStatus) { setFieldValue('status_notes', '') }
        }}>
            <option value="">Select Status</option>
            {STATUS.map((status: Status) => {
              return (
                <option key={status.id} value={status.id}>{status.label}</option>
              )
            })}
        </Field>
        {(submitCount && errors.status) ? <InputError message={errors.status} className="mt-2" /> : ''}
      </div>
      {defaultStatus !== values.status && (
        <div className={`mb-3 ${submitCount ? (errors.status_notes) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="status_notes">Why status is updated?</label>
          <Field
            id="status_notes"
            name="status_notes"
            component="textarea"
            rows="5"
            className="form-textarea"
            placeholder='Update Status Notes'
          />
          <InputError message={errors.status_notes} className="mt-2" />
        </div>
      )}
      <div className={`mb-3 ${submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="phone">Phone</label>
        <Field
          id="phone"
          name="phone"
          type="text"
          className="form-input"
          placeholder='Phone Number'
        />
        <InputError message={errors.phone} className="mt-2" />
      </div>
      <div className={`mb-3 ${submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="notes">Notes</label>
        <Field
          id="notes"
          name="notes"
          component="textarea"
          rows="5"
          className="form-textarea"
          placeholder='Notes'
        />
        <InputError message={errors.notes} className="mt-2" />
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('referred.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          Save
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default ReferredForm
