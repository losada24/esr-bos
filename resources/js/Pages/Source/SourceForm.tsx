import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Field, Form, type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type BiweeklyInstaller } from '@/types'
import { type Source } from '@/types/interfaces/order'


const SourceForm = ({
  submitCount,
  errors,
  isCreate,
  setFieldValue,
  values

}: {
  submitCount: number
  errors: FormikErrors<Source>
  isCreate: boolean
  setFieldValue: (field: string, value: any) => void
  values: Source
}) => {
  return (
    <>
      <Form className='space-y-5'>
        <div className='grid gap-4 grid-cols-3'>
         <div className={submitCount ? (errors.name) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="name">Name</label>
          <Field
            id="name"
            name="name"
            className="form-input"
            autoComplete={false}
            placeholder='Name'
          />
          {(submitCount && errors.name) ? <InputError message={errors.name} className="mt-2" /> : ''}
          </div>
        </div>
        <div className='col-span-4'>
            <label htmlFor="description">Description</label>
            <Field
              id="description"
              name="description"
              component="textarea"
              rows="4"
              className="form-textarea resize-none placeholder:text-white-dark"
              placeholder='Description'
            />
      </div>
        <div className="flex items-center justify-between mt-4">
          <PrimaryButton className="btn btn-primary" type='submit'>
            {isCreate ? 'Create' : 'Save'}
          </PrimaryButton>
        </div>
      </Form>
    </>
  )
}

export default SourceForm
