import React, { useState, useEffect } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type FixedWindows } from '@/types'
import FixedWindowsDrawing from './FixedWindowsDrawing'
import { EXPRESS_GLASS_TYPE, NO_CERTIFICATION_STANDARD_MESSAGE, RUSH_GLASS_TYPE, RUSH_GLASS_NEW_COLOR, CERTIFICATION_SQFT } from '@/Utils/constants'

const FixedWindowsForm = ({ submitCount, errors, isCreate, frame_colors, glass_colors, estimate_id, values }: {
  submitCount: number
  errors: FormikErrors<FixedWindows>
  isCreate: boolean
  frame_colors: string[]
  glass_colors: string[]
  estimate_id: number
  values: FixedWindows
}) => {
  const [glassTypes, setGlassTypes] = useState<string[]>([])
  const [colors, setColors] = useState<string[]>(glass_colors)
  const LOW_E_OPTIONS: string[] = values.order_glass_type === EXPRESS_GLASS_TYPE ? ['NONE', 'LOW E Q366'] : ['NONE', 'LOW E SB70']
  useEffect(() => {
    if (values.order_glass_type === RUSH_GLASS_TYPE) {
      const glass = `3/16 HS ${values.glass_color} (${values.order_glass_type})`
      setGlassTypes([glass])
      setColors([...glass_colors])
    } else {
      setColors(glass_colors.filter((color) => color !== RUSH_GLASS_NEW_COLOR))
      if (values.glass_color !== '' && values.low_e !== '' && values.privacy !== '') {
        const firstGlass = `3/16 HS ${values.glass_color} ${values.glass_color === 'CLEAR' && values.low_e !== 'NONE' ? values.low_e : ''}`
        const interlayer = `+0.09PVB t ${values.privacy}`
        const lastGlass = `+3/16 HS CLEAR ${values.glass_color !== 'CLEAR' && values.low_e !== 'NONE' ? values.low_e : ''}`
        setGlassTypes([`${firstGlass} ${interlayer} ${lastGlass}(${values.order_glass_type})`])
      } else {
        setGlassTypes([])
      }
    }
  }, [values])

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
                />
                {(submitCount && errors.width) ? <InputError message={errors.width} className="mt-2" /> : ''}
                {(values.width * values.height / 144) > CERTIFICATION_SQFT && (
                  <div className="flex items-center p-3.5 rounded text-warning bg-danger-light dark:bg-danger-dark-light mt-3">
                    <span className="ltr:pr-2 rtl:pl-2">
                      <strong className="ltr:mr-1 rtl:ml-1">Warning!</strong>
                      {NO_CERTIFICATION_STANDARD_MESSAGE}
                    </span>
                  </div>
                )}
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
          <fieldset>
            <legend className='font-semibold text-lg'>Glass Info</legend>
            <div className='grid gap-4 grid-cols-2'>
              <div className={submitCount ? (errors.glass_color) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="glass_color">Glass Color</label>
                <Field
                  id="glass_color"
                  name="glass_color"
                  className="form-select"
                  autoComplete="glass_color"
                  placeholder='Glass Colors'
                  as="select"
                >
                  <option value="">Select Glass Color</option>
                  {colors.map((color, index) => (
                    <option key={index} value={color}>{color}</option>
                  ))}
                </Field>
                {(submitCount && errors.glass_color) ? <InputError message={errors.glass_color} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.low_e) ? `has-error ${values.order_glass_type === RUSH_GLASS_TYPE ? 'hidden' : ''}` : `has-success ${values.order_glass_type === RUSH_GLASS_TYPE ? 'hidden' : ''}` : `${values.order_glass_type === RUSH_GLASS_TYPE ? 'hidden' : ''}`}>
                <label htmlFor="low_e">Glass Coating</label>
                <Field
                  id="low_e"
                  name="low_e"
                  className="form-select"
                  autoComplete="low_e"
                  placeholder='Low E'
                  as="select"
                >
                  {LOW_E_OPTIONS.map((type, index) => (
                    <option key={index} value={type}>{type}</option>
                  ))}
                </Field>
                {(submitCount && errors.low_e) ? <InputError message={errors.low_e} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.privacy) ? `has-error ${values.order_glass_type === RUSH_GLASS_TYPE ? 'hidden' : ''}` : `has-success ${values.order_glass_type === RUSH_GLASS_TYPE ? 'hidden' : ''}` : `${values.order_glass_type === RUSH_GLASS_TYPE ? 'hidden' : ''}`}>
                <label htmlFor="privacy">Privacy</label>
                <Field
                  id="privacy"
                  name="privacy"
                  className="form-select"
                  autoComplete="privacy"
                  placeholder='Privacy'
                  as="select"
                >
                  <option value="">Select Privacy Type</option>
                  {['Clear', 'White Interlayer'].map((color, index) => (
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
                  className="form-select"
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
