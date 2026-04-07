import { Field, Form } from 'formik'
import { useRef, useMemo, useState, type Dispatch, type SetStateAction } from 'react'
import { useJsApiLoader, StandaloneSearchBox } from '@react-google-maps/api'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type CompanyContact } from './CompanyContactCommon'
import ClientModal from './ClientModal'
import { type Client } from '@/Pages/Client/ClientCommon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'

const CompanyContactForm = ({ submitCount, errors, isCreate, setFieldValue, values, clients, setClients, sources }: {
  submitCount: number
  errors: FormikErrors<CompanyContact>
  setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void
  isCreate: boolean
  values: CompanyContact
  clients: Client[]
  sources: string[]
  setClients: Dispatch<SetStateAction<Client[]>>
}) => {
  const [showClientModal, setShowClientModal] = useState<boolean>(false)

  const addClient = (client: Client) => {
    setClients((prevClients) => {
      if (client.id && prevClients.some((item) => item.id === client.id)) {
        return prevClients
      }

      if (!client.id && client.phone && prevClients.some((item) => !item.id && item.phone === client.phone)) {
        return prevClients
      }

      return [...prevClients, client]
    })
  }
  const removeClient = (client: Client, index: number) => {
    if (client.id) {
      setClients(clients.filter((item) => item.id !== client.id))
      return
    }

    setClients(clients.filter((_, itemIndex) => itemIndex !== index))
  }
  return (
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
        <div className={`mb-3 ${submitCount ? (errors.email) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="email">Email</label>
          <Field
            id="email"
            name="email"
            type="email"
            className="form-input"
            autoComplete={false}
            placeholder='Email'
          />
          {(submitCount && errors.email) ? <InputError message={errors.email} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.phone) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="phone">Phone</label>
          <Field
            id="phone"
            name="phone"
            className="form-input"
            autoComplete={false}
            placeholder='Phone'
          />
          {(submitCount && errors.phone) ? <InputError message={errors.phone} className="mt-2" /> : ''}
        </div>
        <div className={submitCount ? (errors.website) ? 'has-error' : 'has-success' : ''}>
          <label htmlFor="phone">Website</label>
          <Field
            id="website"
            name="website"
            className="form-input"
            autoComplete={false}
            placeholder='Website'
          />
          {(submitCount && errors.website) ? <InputError message={errors.website} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.billing_street) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="billing_street">Billing Street</label>
          <Field
            id="billing_street"
            name="billing_street"
            className="form-input"
            autoComplete={false}
            placeholder='Billing Street'
          />
          {(submitCount && errors.billing_street) ? <InputError message={errors.billing_street} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.billing_city) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="billing_city">Billing City</label>
          <Field
            id="billing_city"
            name="billing_city"
            className="form-input"
            autoComplete={false}
            placeholder='Billing City'
          />
          {(submitCount && errors.billing_city) ? <InputError message={errors.billing_city} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.billing_state) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="billing_state">Billing State</label>
          <Field
            id="billing_state"
            name="billing_state"
            className="form-input"
            autoComplete={false}
            placeholder='Billing State'
          />
          {(submitCount && errors.billing_state) ? <InputError message={errors.billing_state} className="mt-2" /> : ''}
        </div>
        <div className={`mb-3 ${submitCount ? (errors.billing_code) ? 'has-error' : 'has-success' : ''}`}>
          <label htmlFor="billing_code">Billing Code</label>
          <Field
            id="billing_code"
            name="billing_code"
            className="form-input"
            autoComplete={false}
            placeholder='Billing Code'
          />
          </div>
          {(submitCount && errors.billing_code) ? <InputError message={errors.billing_code} className="mt-2" /> : ''}
          {/*
          <div className={submitCount ? (errors.bid_due_date) ? 'has-error' : 'has-success' : ''}>
            <label htmlFor="bid_due_date">Bid Due Date</label>
            <Flatpickr
              options={{
                mode: 'single',
                dateFormat: 'Y-m-d',
                position: 'auto right'
              }}
              name="bid_due_date"
              value={values.bid_due_date ?? ''}
              className="form-input"
              onChange={([date]) => {
                setFieldValue('bid_due_date', date.toISOString().slice(0, 10))
              }}
            />
            {(submitCount && errors.bid_due_date) ? <InputError message={errors.bid_due_date.toString()} className="mt-2" /> : ''}
          </div>
          */}
      </div>
      <div className='flex flex-col'>
        <div className='flex justify-end p-3'>
          <button type='button' className='btn btn-primary' onClick={() => { setShowClientModal(true) }}>Add Client</button>
        </div>
        <div className=''>
          <table className='table-auto w-full'>
            <thead>
              <tr>
                <th className='px-4 py-2'>Client Name</th>
                <th className='px-4 py-2'>Email</th>
                <th className='px-4 py-2'>Phone</th>
                <th className='px-4 py-2 text-right'>Actions</th>
              </tr>
            </thead>
            <tbody>
              {clients.map((client, index) => (
                <tr key={index}>
                  <td className='border px-4 py-2'>{client.name}</td>
                  <td className='border px-4 py-2'>{client.email}</td>
                  <td className='border px-4 py-2'>{client.phone}</td>
                  <td className='border px-4 py-2 text-right'>
                    <button
                      type="button"
                      className="text-white-dark hover:text-danger"
                      aria-label={`Remove ${client.name}`}
                      title={`Remove ${client.name}`}
                      onClick={() => { removeClient(client, index) }}
                    >
                      <DeleteIcon />
                    </button>
                  </td>
                </tr>
              ))}
              {clients.length === 0 && (
                <tr>
                  <td className='border px-4 py-2' colSpan={4}>No clients added yet.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
      <div className="flex items-center justify-between mt-4">
        <Link className='btn btn-danger uppercase' href={route('client.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
      <ClientModal
        showModal={showClientModal}
        onClose={(value: boolean) => {
          setShowClientModal(value)
        }}
        addClient={addClient }
        sources={sources}
        allowExistingSelection={!isCreate}
        selectedClients={clients}
      />
    </Form>
  )
}

export default CompanyContactForm
