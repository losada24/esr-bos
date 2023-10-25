import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type Client } from './ClientCommon'

const ClientForm = ({ submitCount, errors, isCreate, states }: { submitCount: number, errors: FormikErrors<Client>, isCreate: boolean, states: string[] }) => {
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
      <div className={submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="phone">Phone</label>
        <Field
          id="phone"
          name="phone"
          className="form-input"
          autoComplete="phone"
          placeholder='Phone'
        />
        {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
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
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('client.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default ClientForm
