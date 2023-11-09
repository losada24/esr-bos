import React, { useState, useEffect } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type FixedWindows } from '@/types'
import FixedWindowsDrawing from './FixedWindowsDrawing'

const FixedWindowsForm = ({ submitCount, errors, isCreate, frame_colors, glass_colors, estimate_id, values }: {
  submitCount: number
  errors: FormikErrors<FixedWindows>
  isCreate: boolean
  frame_colors: string[]
  glass_colors: string[]
  estimate_id: number
  values: FixedWindows
}) => {
  // TODO: Check how fill the glassTypes
  const [glassTypes, setGlassTypes] = useState<string[]>([
    '3/16 HS + 0.090 SGP + 3/16 HS'
  ])
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
              <div className={submitCount ? (errors.width) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="width">Width</label>
                <Field
                  id="width"
                  name="width"
                  className="form-input text-right"
                  autoComplete="width"
                  placeholder='Width'
                  type='number'
                  min={10}
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
                  min={10}
                />
                {(submitCount && errors.height) ? <InputError message={errors.height} className="mt-2" /> : ''}
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
              <div className={submitCount ? (errors.markup) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="markup">Markup (%)</label>
                <Field
                  id="markup"
                  name="markup"
                  className="form-input text-right"
                  autoComplete="markup"
                  placeholder='Markup'
                  type='number'
                />
                {(submitCount && errors.markup) ? <InputError message={errors.markup} className="mt-2" /> : ''}
              </div>
            </div>
          </fieldset>
          <fieldset>
            <legend className='font-semibold text-lg'>Glass Info</legend>
            <div className='grid gap-4 grid-cols-2'>
              <div className={submitCount ? (errors.glass_color) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="glass_color">Glass Color</label>
                <Field
                  id="glass_color"
                  name="glass_color"
                  className="form-input"
                  autoComplete="glass_color"
                  placeholder='Glass Colors'
                  as="select"
                >
                  <option value="">Select Glass Color</option>
                  {glass_colors.map((color, index) => (
                    <option key={index} value={color}>{color}</option>
                  ))}
                </Field>
                {(submitCount && errors.glass_color) ? <InputError message={errors.glass_color} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.low_e) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="low_e">Glass Coating</label>
                <Field
                  id="low_e"
                  name="low_e"
                  className="form-input"
                  autoComplete="low_e"
                  placeholder='Low E'
                  as="select"
                >
                  {['None', 'SB70', 'N70/38'].map((type, index) => (
                    <option key={index} value={type}>{type}</option>
                  ))}
                </Field>
                {(submitCount && errors.low_e) ? <InputError message={errors.low_e} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.privacy) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="privacy">Privacy</label>
                <Field
                  id="privacy"
                  name="privacy"
                  className="form-input"
                  autoComplete="privacy"
                  placeholder='Privacy'
                  as="select"
                >
                  <option value="">Select Privacy Type</option>
                  {['Clear', 'White'].map((color, index) => (
                    <option key={index} value={color}>{color}</option>
                  ))}
                </Field>
                {(submitCount && errors.privacy) ? <InputError message={errors.privacy} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.glass_type) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="glass_type">Glass Type</label>
                <Field
                  id="glass_type"
                  name="glass_type"
                  className="form-input"
                  autoComplete="glass_type"
                  placeholder='glass_type'
                  as="select"
                >
                  <option value="">Select Glass Type</option>
                  {glassTypes.map((type, index) => (
                    <option key={index} value={type}>{type}</option>
                  ))}
                </Field>
                {(submitCount && errors.glass_type) ? <InputError message={errors.glass_type} className="mt-2" /> : ''}
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
      <div className='p-2 col-span-6 border border-dashed w-full'>
        <h3 className='text-lg font-semibold'>Preview</h3>
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

export default FixedWindowsForm
