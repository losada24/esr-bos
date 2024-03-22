import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type Mullion } from '@/types'
import FixedWindowsDrawing from './MullionDrawing'
import { type MullionProps } from './MullionCommon'

const MullionForm = ({ submitCount, errors, isCreate, frame_colors, estimate_id, values, external_products, setFieldValue }: {
  submitCount: number
  errors: FormikErrors<Mullion>
  isCreate: boolean
  frame_colors: string[]
  estimate_id: number
  values: Mullion
  external_products: MullionProps[]
  setFieldValue: (field: string, value: any, shouldValidate?: boolean | undefined) => void
}) => {
  const changeConfiguration = (value: string) => {
    setFieldValue('config', value)
    const selected = external_products.find((product) => product.configuration === value)
    if (selected) {
      setFieldValue('width', selected.width)
      setFieldValue('max_allowed_height', selected.height)
    }
  }

  return (
    <div className='grid gap-6 grid-cols-12'>
      <div className='col-span-6'>
        <Form className='space-y-5'>
          <fieldset>
            <legend className='font-semibold text-lg'>General Information</legend>
            <div className='grid gap-4 grid-cols-2'>
              <div className={submitCount ? (errors.mark) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="mark">Mark</label>
                <Field
                  id="mark"
                  name="mark"
                  className="form-input"
                  autoComplete="mark"
                  placeholder='Mark'
                />
                {(submitCount && errors.mark) ? <InputError message={errors.mark} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.qty) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="qty">Qty</label>
                <Field
                  id="qty"
                  name="qty"
                  className="form-input text-right"
                  autoComplete="qty"
                  placeholder='Qty'
                  type='number'
                />
                {(submitCount && errors.qty) ? <InputError message={errors.qty} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.config) ? 'has-error col-span-full' : 'has-success col-span-full' : 'col-span-full'}>
                <label htmlFor="configuration">Configuration</label>
                <Field
                  id="config"
                  name="config"
                  className="form-select"
                  autoComplete="config"
                  placeholder='Configuration'
                  onChange={(e: any) => { changeConfiguration(e.target.value) }}
                  as="select"
                >
                  <option value="">Select Configuration</option>
                  {external_products.map((external_product, index) => (
                    <option key={index} value={external_product.configuration}>{external_product.configuration}</option>
                  ))}
                </Field>
                {(submitCount && errors.config) ? <InputError message={errors.config} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.width) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="width">Width</label>
                <Field
                  id="width"
                  name="width"
                  className="form-input text-right disabled:pointer-events-none disabled:bg-[#eee]"
                  autoComplete="width"
                  placeholder='Width'
                  disabled={true}
                  type='number'
                />
                {(submitCount && errors.width) ? <InputError message={errors.width} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.height) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="height">Height</label>
                <Field
                  id="height"
                  name="height"
                  className="form-input text-right"
                  autoComplete="height"
                  placeholder='Height'
                  type='number'
                />
                {(submitCount && errors.height) ? <InputError message={errors.height} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.frame_color) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="frame_color">Frame Color</label>
                <Field
                  id="frame_color"
                  name="frame_color"
                  className="form-select"
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
              <div className={submitCount ? (errors.markup) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="markup">Markup</label>
                <div className="flex flex-1">
                  <Field
                    id="markup"
                    name="markup"
                    className="form-input text-right rounded-r-none"
                    autoComplete="markup"
                    placeholder='Markup'
                    type='number'
                  />
                  <div className="bg-[#eee] flex justify-center items-center px-3 font-semibold border border-[#e0e6ed] dark:border-[#17263c] dark:bg-[#1b2e4b] rounded-r-md">%</div>
                </div>
                {(submitCount && errors.markup) ? <InputError message={errors.markup} className="mt-2" /> : ''}
              </div>
            </div>
          </fieldset>
          <div className="flex items-center justify-between mt-4">
            <Link className='btn btn-danger uppercase' href={route('estimate.show', estimate_id)}>Cancel</Link>
            <PrimaryButton className="btn btn-primary" type='submit'>
              {isCreate ? 'Create' : 'Save'}
            </PrimaryButton>
          </div>
        </Form>
      </div>
      <div className='p-2 col-span-6 w-full'>
        <div className='h-full flex justify-center align-middle'>
          {values.width !== 0 && values.height !== 0
            ? <FixedWindowsDrawing width={values.width} height={values.height} />
            : ''
          }
        </div>
      </div>
    </div>
  )
}

export default MullionForm
