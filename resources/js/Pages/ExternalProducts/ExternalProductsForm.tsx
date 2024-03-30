import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type ExternalProducts } from '@/types'
import { type FormikErrors } from 'formik'
import CodeIcon from '@/Components/Icons/CodeIcon'
import { useState } from 'react'
import ConfigurationCode from './ConfigurationCode'

const ExternalProductsForm = ({ submitCount, errors, externalProducts, isCreate, setFieldValue, defaultProduct }: {
  submitCount: number
  errors: FormikErrors<ExternalProducts>
  externalProducts: string[]
  defaultProduct?: string
  isCreate: boolean
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void
}) => {
  const [showCode, setShowCode] = useState<boolean>(false)
  const [product, setProduct] = useState<string>(defaultProduct ?? '')

  return (
    <Form className='space-y-5'>
      <div className={submitCount ? (errors.external_product ? 'has-error' : 'has-success') : ''}>
        <label htmlFor="external_product">Product</label>
        <Field as="select" name="external_product" className="form-select" onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
          setShowCode(false)
          setProduct(e.target.value)
          setFieldValue('external_product', e.target.value)
        }}>
            <option value="">Select Product</option>
            {externalProducts.map((externalProduct: string, index) => {
              return (
                <option key={`${index}_${externalProduct}`} value={externalProduct}>{externalProduct}</option>
              )
            })}
        </Field>
        {(submitCount && errors.external_product) ? <InputError message={errors.external_product} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.width) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="width">Width</label>
        <Field
          id="width"
          name="width"
          type="number"
          className="form-input text-right"
          autoComplete="width"
          placeholder='Width'
        />
        {(submitCount && errors.width) ? <InputError message={errors.width} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.height) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="height">Height</label>
        <Field
          id="height"
          name="height"
          type="number"
          className="form-input text-right"
          autoComplete="height"
          placeholder='Height'
        />
        {(submitCount && errors.height) ? <InputError message={errors.height} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.price) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="price">Price</label>
        <Field
          id="price"
          name="price"
          type="number"
          className="form-input text-right"
          autoComplete="price"
          placeholder='Price'
        />
        {(submitCount && errors.price) ? <InputError message={errors.price} className="mt-2" /> : ''}
      </div>
      <div className={submitCount ? (errors.extras) ? 'has-error' : 'has-success' : ''}>
        <label htmlFor="extras">Configurations <button onClick={() => { setShowCode(!showCode) }} type='button' className='flex font-semibold hover:text-gray-400d'><CodeIcon className='me-2'/> Code</button></label>
        <Field
          id="extras"
          name="extras"
          component="textarea"
          rows="4"
          className="form-textarea resize-y placeholder:text-white-dark"
          placeholder='Extras'
        />
        {(submitCount && errors.extras) ? <InputError message={errors.extras} className="mt-2" /> : ''}
        {showCode && product !== '' && (
          <ConfigurationCode product={product} />
        )}
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
        <Link className='btn btn-danger uppercase' href={route('external-products.index')}>Cancel</Link>
        <PrimaryButton className="btn btn-primary" type='submit'>
          {isCreate ? 'Create' : 'Save'}
        </PrimaryButton>
      </div>
    </Form>
  )
}

export default ExternalProductsForm
