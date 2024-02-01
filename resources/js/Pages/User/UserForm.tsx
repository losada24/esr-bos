import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type Role, type Company, type ModalProps } from '@/types'
import { type FormikErrors } from 'formik'
import { type User } from './UserCommon'
import FeaturedImageModal from '../RawMaterial/FeaturedImageModal'
import { useState } from 'react'

const UserForm = ({ submitCount, errors, roles, isCreate, companies, isAdmin, featured_image, setFieldValue, modalProps }: {
  submitCount: number
  errors: FormikErrors<User>
  roles: Role[]
  isCreate: boolean
  companies: Company[]
  isAdmin: boolean
  featured_image?: string
  modalProps: ModalProps | null
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void
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
      <div className={submitCount ? (errors.role ? 'has-error' : 'has-success') : ''}>
        <label htmlFor="role">Role</label>
        <Field as="select" name="role" className="form-select">
            <option value="">Select Role</option>
            {roles.map((role: Role) => {
              return (
                <option key={role.id} value={role.id}>{role.name}</option>
              )
            })}
        </Field>
        {(submitCount && errors.role) ? <InputError message={errors.role} className="mt-2" /> : ''}
      </div>
      {isAdmin && (
        <div className={submitCount ? (errors.company_id ? 'has-error' : 'has-success') : ''}>
          <label htmlFor="company_id">Company</label>
          <Field as="select" name="company_id" className="form-select">
              <option value="">Select Company</option>
              {companies.map((company: Company) => {
                return (
                  <option key={company.id} value={company.id}>{company.name}</option>
                )
              })}
          </Field>
          {(submitCount && errors.company_id) ? <InputError message={errors.company_id} className="mt-2" /> : ''}
        </div>
      )}
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
            min='0'
          />
          <div className="bg-[#eee] flex justify-center items-center px-3 font-semibold border border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b] rounded-r-md">%</div>
        </div>
        {(submitCount && errors.markup) ? <InputError message={errors.markup} className="mt-2" /> : ''}
      </div>
      <div className={`mb-3 ${submitCount ? (errors.password) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="password">Password</label>
        <Field
          id="password"
          name="password"
          type="password"
          className="form-input"
          placeholder='Password'
        />
        {(submitCount && errors.password) ? <InputError message={errors.password} className="mt-2" /> : ''}
      </div>
      <div className={`mb-3 ${submitCount ? (errors.password_confirmation) ? 'has-error' : 'has-success' : ''}`}>
        <label htmlFor="password_confirmation">Password Confirmation</label>
        <Field
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          className="form-input"
          placeholder='Password Confirmation'
        />
        <InputError message={errors.password_confirmation} className="mt-2" />
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
        <Link className='btn btn-danger uppercase' href={route('user.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
      {modalProps && <FeaturedImageModal showModal={showModal} onClose={setShowModal} selectedModalProps={modalProps} />}
    </Form>
  )
}

export default UserForm
