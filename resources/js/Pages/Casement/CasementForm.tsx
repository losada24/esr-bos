import React, { useState, useEffect } from 'react'
import { Field, Form } from 'formik'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Link } from '@inertiajs/react'
import { type FormikErrors } from 'formik'
import { type Casement } from '@/types'
import { EXPRESS_GLASS_TYPE, RUSH_GLASS_TYPE, CASEMENT_MULTIPOINT_CONFIG } from '@/Utils/constants'
import Details from './Details'
import { type CasementProps } from './CasementCommon'
import CasementDrawing from './CasementDrawing'

const CasementForm = ({ submitCount, errors, isCreate, frame_colors, glass_colors, estimate_id, values, muntin_patterns, external_products, muntin_styles, setFieldValue, opening }: {
  submitCount: number
  errors: FormikErrors<Casement>
  isCreate: boolean
  frame_colors: string[]
  glass_colors: string[]
  muntin_patterns: string[]
  muntin_styles: string[]
  estimate_id: number
  external_products: CasementProps[]
  values: Casement
  opening: string[]
  setFieldValue: (field: string, value: any, shouldValidate?: boolean) => void
}) => {
  const [glassTypes, setGlassTypes] = useState<string[]>([])
  // const [openingEnabled, setOpeningEnabled] = useState<boolean>(values.config === CASEMENT_MULTIPOINT_CONFIG)
  const [openingOptions, setOpeningOptions] = useState<string[]>(values.config === CASEMENT_MULTIPOINT_CONFIG ? opening : ['NONE'])
  const LOW_E_OPTIONS: string[] = values.order_glass_type === EXPRESS_GLASS_TYPE ? ['NONE', 'LOW E Q366'] : ['NONE'] // 'LOW E SB70'
  useEffect(() => {
    if (values.glass_color !== '' && values.low_e !== '' && values.privacy !== '') {
      const firstGlass = `1/8 HS ${values.glass_color}${values.glass_color === 'CLEAR' && values.low_e !== 'NONE' ? ` ${values.low_e}` : ''}`
      const interlayer = `+0.09PVB s ${values.privacy}`
      const lastGlass = `+1/8 HS CLEAR${values.glass_color !== 'CLEAR' && values.low_e !== 'NONE' ? ` ${values.low_e}` : ''}`
      setGlassTypes([`${firstGlass} ${interlayer} ${lastGlass} (${values.order_glass_type})`])
    } else {
      setGlassTypes([])
    }
  }, [values])

  const changeConfiguration = (configuration: string) => {
    setFieldValue('config', configuration)
    if (configuration === CASEMENT_MULTIPOINT_CONFIG) {
      setOpeningOptions(opening)
      setFieldValue('panel_b', false)
    } else {
      setOpeningOptions(['NONE'])
      setFieldValue('opening', configuration)
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
                  min='1'
                />
                {(submitCount && errors.qty) ? <InputError message={errors.qty} className="mt-2" /> : ''}
              </div>
              <div className={submitCount ? (errors.config) ? 'has-error' : 'has-success' : ''}>
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
              <div className={submitCount ? (errors.opening) ? 'has-error' : 'has-success' : ''}>
                <label htmlFor="opening">Opening</label>
                <Field
                  id="opening"
                  name="opening"
                  className="form-select"
                  autoComplete="opening"
                  placeholder='Opening'
                  as="select"
                >
                  <option value="">Select Opening</option>
                  {openingOptions.map((opening, index) => (
                    <option key={index} value={opening}>{opening}</option>
                  ))}
                </Field>
                {(submitCount && errors.opening) ? <InputError message={errors.opening} className="mt-2" /> : ''}
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
              <div className={submitCount ? (errors.screen) ? 'has-error inline-flex' : 'has-success inline-flex' : 'inline-flex'}>
                <Field
                  id="screen"
                  name="screen"
                  className="form-checkbox"
                  type='checkbox'
                />
                <label htmlFor="screen">Screen</label>
                {(submitCount && errors.screen) ? <InputError message={errors.screen} className="mt-2" /> : ''}
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
                  {glass_colors.map((color, index) => (
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
          <Details
            submitCount={submitCount}
            errors={errors}
            muntin_patterns={muntin_patterns}
            muntin_styles={muntin_styles}
            values={values}
          />
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
          {values.width !== 0 && values.height !== 0 && values.config !== ''
            ? <CasementDrawing width={values.width} height={values.height} configuration={values.config} />
            : ''
          }
        </div>
      </div>
    </div>
  )
}

export default CasementForm
