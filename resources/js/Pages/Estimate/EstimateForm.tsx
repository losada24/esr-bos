import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type Order, type Client } from '@/types'
import Select from 'react-select'

const EstimateForm = ({ submitCount, errors, isCreate, glass_colors, frame_colors, clients, setFieldValue, selectedClient }: {
  submitCount: number
  errors: FormikErrors<Order>
  isCreate: boolean
  featured_image?: string
  frame_colors: string[]
  glass_colors: string[]
  clients: Client[]
  setFieldValue: (field: string, value: any) => void
  selectedClient?: { label: string, value: number }
}) => {
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
      <div className={submitCount ? (errors.project_name) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="name">Project Name</label>
        <Field
          id="project_name"
          name="project_name"
          className="form-input"
          autoComplete="project_name"
          placeholder='Project Name'
        />
        {(submitCount && errors.project_name) ? <InputError message={errors.project_name} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.client_id) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="name">Client</label>
        <Select
          id='client_id'
          placeholder="Select client"
          name='client_id'
          defaultValue={selectedClient ?? null }
          onChange={(value) => { setFieldValue('client_id', value?.value) }}
          options={clients.map((client) => { return { label: client.name, value: client.id } })}
        />
        {(submitCount && errors.client_id) ? <InputError message={errors.client_id} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.frame_color) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="frame_color">Frame Color</label>
        <Field
          id="frame_color"
          name="frame_color"
          className="form-input"
          autoComplete="frame_color"
          placeholder='Frame Colors'
          as="select"
        >
          <option value="">Select Frame Color</option>
          {frame_colors.map((color, index) => (
            <option key={index} value={color}>{color}</option>
          ))}
        </Field>
        {(submitCount && errors.frame_color) ? <InputError message={errors.frame_color} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.glass_color) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="glass_colors">Glass Color</label>
        <Field
          id="glass_color"
          name="glass_color"
          className="form-input"
          autoComplete="glass_color"
          placeholder='Glass Color'
          as="select"
        >
          <option value="">Select Glass Color</option>
          {glass_colors.map((color, index) => (
            <option key={index} value={color}>{color}</option>
          ))}
        </Field>
        {(submitCount && errors.glass_color) ? <InputError message={errors.glass_color} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.markup) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="name">Markup(%)</label>
        <Field
          id="markup"
          name="markup"
          className="form-input text-right"
          autoComplete="markup"
          placeholder='Markup'
          type="number"
        />
        {(submitCount && errors.markup) ? <InputError message={errors.markup} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.notes) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="notes">Notes</label>
        <Field
          id="notes"
          name="notes"
          component="textarea"
          rows="4"
          className="form-textarea resize-none placeholder:text-white-dark"
          placeholder='Notes'
        />
        {(submitCount && errors.notes) ? <InputError message={errors.notes} className="mt-2" /> : ''}
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('raw-material.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default EstimateForm
