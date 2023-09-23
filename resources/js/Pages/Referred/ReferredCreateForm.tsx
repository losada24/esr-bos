import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import EmailIcon from '@/Components/Icons/Auth/EmailIcon'
import UserIcon from '@/Components/Icons/UserIcon'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import MessageIcon from '@/Components/Icons/MessageIcon'

interface FormProps {
  submitCount: number
  errors: any
}

const ReferredCreateForm = ({ submitCount, errors }: FormProps) => {
  return (
    <Form className='space-y-5'>
      <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="name">Name</label>
        <div className="relative text-white-dark">
          <Field
            id="name"
            name="name"
            className="form-input ps-10 placeholder:text-white-dark"
            autoComplete="name"
            placeholder='Name'
          />
          <span className="absolute start-4 top-1/2 -translate-y-1/2">
            <UserIcon />
          </span>
        </div>
        {(submitCount && errors.name) ? <InputError message={errors.name} className="mt-2" /> : ''}
      </div>
      <div className={`mb-3 ${submitCount ? (errors.email) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="email">Email</label>
        <div className="relative text-white-dark">
          <Field
            id="email"
            name="email"
            type="email"
            className="form-input ps-10 placeholder:text-white-dark"
            placeholder='Email'
          />
          <span className="absolute start-4 top-1/2 -translate-y-1/2">
            <EmailIcon />
          </span>
        </div>
        {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
      </div>
      <div className={`mb-3 ${submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="phone">Phone Number</label>
        <div className="relative text-white-dark">
          <Field
            id="phone"
            name="phone"
            type="text"
            className="form-input ps-10 placeholder:text-white-dark"
            placeholder='Phone Number'
          />
          <span className="absolute start-4 top-1/2 -translate-y-1/2">
            <PhoneIcon />
          </span>
        </div>
        {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
      </div>
      <div className={`mb-3 ${submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="notes">Notes</label>
        <div className="relative text-white-dark">
          <Field
            id="notes"
            name="notes"
            component="textarea"
            rows="4"
            className="form-textarea resize-none ps-10 placeholder:text-white-dark"
            placeholder='Message'
          />
          <span className="absolute start-4 top-2.5">
            <MessageIcon />
          </span>
        </div>
        {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
      </div>
      <PrimaryButton
          className="btn btn-gradient border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)] w-full"
          type='submit'
      >
        Submit
      </PrimaryButton>
    </Form>
  )
}

export default ReferredCreateForm
