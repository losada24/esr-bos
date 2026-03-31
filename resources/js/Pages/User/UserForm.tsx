import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type Role, type ModalProps, type OptionType } from '@/types'
import { type FormikErrors } from 'formik'
import { type UserFormValues } from './UserCommon'
import FeaturedImageModal from '@/Components/FeaturedImageModal'
import { useState } from 'react'
import { capitalizeWords } from '@/Utils/string'
import Select from 'react-select'

const UserForm = ({ submitCount, errors, roles, statuses, ownerOptions, isCreate, /* companies, isAdmin, */ featured_image, setFieldValue, modalProps, values }: {
  submitCount: number
  errors: FormikErrors<UserFormValues>
  roles: Role[]
  statuses: OptionType[]
  ownerOptions: Array<{ id: number, name: string }>
  isCreate: boolean
  values: UserFormValues
  featured_image?: string
  modalProps: ModalProps | null
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void
}) => {
  const [showModal, setShowModal] = useState(false)
  const ownerRoleIds = new Set(
    roles
      .filter((role) => role.name === 'owner')
      .map((role) => role.id)
  )
  const hasOwnerRoleSelected = values.role.some((role) => ownerRoleIds.has(Number(role.value)))

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
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
            const formattedValue = capitalizeWords(e.target.value)
            setFieldValue('name', formattedValue)
          }}
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
      <div className={submitCount ? (errors.status ? 'has-error' : 'has-success') : ''}>
          <label htmlFor="status">Status</label>
          <Select
            id="status"
            placeholder="Select Status"
            name="status"
            options={statuses}
            value={statuses.find((status) => status.value === values.status) ?? null}
            onChange={(option) => {
              const selectedOption = option as OptionType | null
              setFieldValue('status', selectedOption?.value ?? '')
            }}
          />
          {(submitCount && errors.status) ? <InputError message={errors.status as string} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.role ? 'has-error' : 'has-success') : ''}>
          <label htmlFor="roles">Roles</label>
          <Select
            id="role"
            placeholder="Select Roles"
            name="role"
            defaultValue={ values.role || [] }
            isMulti={true}
            onChange={(value) => {
              setFieldValue('role', value)
            }}
            // options={roles.map((role) => ({ label: role.name, value: role.id }))} // Opciones de roles
            options={roles.map((roles: Role) => { return { label: roles.name, value: roles.id } })}
          />
          {(submitCount && errors.role) ? <InputError message={errors.role.toString()} className="mt-2" /> : ''}
      </div>
      {hasOwnerRoleSelected && (
        <div className={submitCount ? (errors.delegated_owner_ids ? 'has-error' : 'has-success') : ''}>
          <label htmlFor="delegated_owner_ids">Can Manage Orders From</label>
          <Select
            id="delegated_owner_ids"
            placeholder="Select owners"
            name="delegated_owner_ids"
            value={values.delegated_owner_ids}
            isMulti={true}
            onChange={(selected) => {
              setFieldValue('delegated_owner_ids', selected)
            }}
            options={ownerOptions.map((owner) => ({ label: owner.name, value: owner.id }))}
          />
          {(submitCount && errors.delegated_owner_ids) ? <InputError message={errors.delegated_owner_ids.toString()} className="mt-2" /> : ''}
        </div>
      )}
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
          placeholder="Featured Image"
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
             <img src={route('download.image-download', { id: values.id ?? 0 })} className="w-20 h-20 object-cover rounded-md overflow-hidden" />
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
