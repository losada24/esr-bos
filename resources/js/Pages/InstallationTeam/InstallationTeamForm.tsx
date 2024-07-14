import React, { useState } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type OptionType, type TypeOfHousing, type User } from '@/types'
import Select, { type SingleValue, type MultiValue } from 'react-select'
import { type InstallationTeamFormValues } from './InstallationTeamCommon'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'

const InstallationTeamForm = ({
  submitCount,
  errors,
  isCreate,
  setFieldValue,
  users,
  type_of_housings,
  values
}: {
  submitCount: number
  errors: FormikErrors<InstallationTeamFormValues>
  isCreate: boolean
  type_of_housings: TypeOfHousing[]
  users: User[]
  values: InstallationTeamFormValues
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void }) => {
  const selectedUser: SingleValue<OptionType> = {
    value: values.user_id.value,
    label: values.user_id.label
  }

  const selectedTypeOfHousing: MultiValue<OptionType> = values.typeHousing?.map((typeHousing: TypeOfHousing) => {
    return {
      value: typeHousing.id,
      label: type_of_housings.find((type_of_housing: TypeOfHousing) => type_of_housing.id === typeHousing.id)?.name ?? ''
    }
  }) ?? []

  return (
    <Form className='space-y-5'>
      <div className={submitCount ? (errors.user_id) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="user_id">Installer</label>
        <Select
          id='user_id'
          placeholder="Select User"
          name='user_id'
          onChange={(value) => {
            setFieldValue('user_id', value)
          }}
          defaultValue={ selectedUser }
          isMulti={false}
          options={users.map((user: User) => { return { label: user.email, value: user.id } })}
        />
        {(submitCount && errors.user_id) ? <InputError message={errors.user_id} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.typeHousing) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="type_of_housings">Type of Housing</label>
        <Select
          id='typeHousing'
          placeholder="Select User"
          name='typeHousing'
          defaultValue={ selectedTypeOfHousing }
          onChange={(value) => {
            setFieldValue('typeHousing', value)
          }}
          isMulti={true}
          options={type_of_housings.map((type_of_housings: TypeOfHousing) => { return { label: type_of_housings.name, value: type_of_housings.id } })}
        />
        {(submitCount && errors.typeHousing) ? <InputError message={errors.typeHousing.toString()} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.number_of_member) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="number_of_member">Number of Member</label>
        <Field
          id="number_of_member"
          name="number_of_member"
          className="form-input text-right"
          autoComplete="number_of_member"
          placeholder='Number of Members'
          type='number'
        />
        {(submitCount && errors.number_of_member) ? <InputError message={errors.number_of_member} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.worker_compensation_expiration_date) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="worker_compensation_expiration_date">Worker Compensation Expiration Date</label>
        <Flatpickr
          options={{
            mode: 'single',
            dateFormat: 'Y-m-d',
            position: 'auto right'
          }}
          name="worker_compensation_expiration_date"
          value={values.worker_compensation_expiration_date}
          className="form-input"
          onChange={([date]) => {
            setFieldValue('worker_compensation_expiration_date', date.toISOString().slice(0, 10))
          }}
        />
        {(submitCount && errors.worker_compensation_expiration_date) ? <InputError message={errors.worker_compensation_expiration_date.toString()} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.liability_expiration_date) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="liability_expiration_date">Liability Expiration Date</label>
        <Flatpickr
          options={{
            mode: 'single',
            dateFormat: 'Y-m-d',
            position: 'auto right'
          }}
          name="liability_expiration_date"
          value={values.liability_expiration_date}
          className="form-input"
          onChange={([date]) => {
            setFieldValue('liability_expiration_date', date.toISOString().slice(0, 10))
          }}
        />
        {(submitCount && errors.liability_expiration_date) ? <InputError message={errors.liability_expiration_date.toString()} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.liability_expiration_attach) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="liability_expiration_attach">Liability Expiration File</label>
        <input
          id="liability_expiration_attach"
          name="liability_expiration_attach"
          type="file"
          accept="*"
          className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
          placeholder="Qty"
          onChange={(event: any) => {
            setFieldValue('liability_expiration_attach', event.currentTarget.files[0])
          }}
        />
        {(submitCount && errors.liability_expiration_attach) ? <InputError message={errors.liability_expiration_attach} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.worker_compensation_attach) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="worker_compensation_attach">Worker Compensation File</label>
        <input
          id="worker_compensation_attach"
          name="worker_compensation_attach"
          type="file"
          accept="*"
          className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
          onChange={(event: any) => {
            setFieldValue('worker_compensation_attach', event.currentTarget.files[0])
          }}
        />
        {(submitCount && errors.worker_compensation_attach) ? <InputError message={errors.worker_compensation_attach} className="mt-2" /> : ''}
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('installation_team.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default InstallationTeamForm
