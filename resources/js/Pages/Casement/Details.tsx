import InputError from '@/Components/InputError'
import { CASEMENT_MULTIPOINT_CONFIG } from '@/Utils/constants'
import { type Casement } from '@/types'
import { Field, type FormikErrors } from 'formik'

const Details = ({ submitCount, errors, muntin_patterns, muntin_styles, values }: {
  submitCount: number
  errors: FormikErrors<Casement>
  muntin_patterns: string[]
  muntin_styles: string[]
  values: Casement
}) => {
  return (
    <fieldset>
      <legend className='font-semibold text-lg'>Details</legend>
      <div className='grid gap-4 grid-cols-2'>
        <div className={submitCount ? (errors.muntin_panels) ? 'has-error inline-flex' : 'has-success inline-flex' : 'inline-flex'}>
          <Field
            id="muntin_panels"
            name="muntin_panels"
            className="form-checkbox"
            type='checkbox'
          />
          <label htmlFor="muntin_panels">Muntin Panels</label>
          {(submitCount && errors.muntin_panels) ? <InputError message={errors.muntin_panels} className="mt-2" /> : ''}
        </div>
        {values.muntin_panels && (
          <>
            <div className='flex gap-x-3'>
              <div className={submitCount ? (errors.panel_a) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex'}>
                <div className='flex'>
                  <Field
                    id="panel_a"
                    name="panel_a"
                    className="form-checkbox"
                    type='checkbox'
                  />
                  <label htmlFor="panel_a">Panel A</label>
                </div>
                {(submitCount && errors.panel_a) ? <div className='block'><InputError message={errors.panel_a} className="mt-2" /></div> : ''}
              </div>
              <div className={submitCount ? (errors.panel_b) ? 'has-error inline-flex flex-col' : 'has-success inline-flex' : 'inline-flex'}>
                <div className='flex'>
                  <Field
                    id="panel_b"
                    name="panel_b"
                    className="form-checkbox"
                    disabled={values.config === CASEMENT_MULTIPOINT_CONFIG}
                    type='checkbox'
                  />
                  <label htmlFor="panel_b">Panel B</label>
                </div>
                {(submitCount && errors.panel_b) ? <div className='block'><InputError message={errors.panel_b} className="mt-2" /></div> : ''}
              </div>
            </div>
            <div className={submitCount ? (errors.muntin_pattern) ? 'has-error col-span-2' : 'has-success col-span-2' : 'col-span-2'}>
              <label htmlFor="muntin_pattern">Muntin Pattern</label>
              <Field
                id="muntin_pattern"
                name="muntin_pattern"
                className="form-select"
                autoComplete="muntin_pattern"
                placeholder='Muntin Pattern'
                as="select"
              >
                <option value="">Select Muntin Pattern</option>
                {muntin_patterns.map((pattern, index) => (
                  <option key={index} value={pattern}>{pattern}</option>
                ))}
              </Field>
              {(submitCount && errors.muntin_pattern) ? <InputError message={errors.muntin_pattern} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.muntin_interior_style) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="muntin_interior_style">Muntin Interior Style</label>
              <Field
                id="muntin_interior_style"
                name="muntin_interior_style"
                className="form-select"
                autoComplete="muntin_interior_style"
                placeholder='Muntin Interior Style'
                as="select"
              >
                <option value="">Select Interior Style</option>
                {muntin_styles.map((style, index) => (
                  <option key={index} value={style}>{style}</option>
                ))}
              </Field>
              {(submitCount && errors.muntin_interior_style) ? <InputError message={errors.muntin_interior_style} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.muntin_exterior_style) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="muntin_exterior_style">Muntin Exterior Style</label>
              <Field
                id="muntin_exterior_style"
                name="muntin_exterior_style"
                className="form-select"
                autoComplete="muntin_exterior_style"
                placeholder='Muntin Exterior Style'
                as="select"
              >
                <option value="">Select Exterior Style</option>
                {muntin_styles.map((style, index) => (
                  <option key={index} value={style}>{style}</option>
                ))}
              </Field>
              {(submitCount && errors.muntin_exterior_style) ? <InputError message={errors.muntin_exterior_style} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.vertical_lines) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="vertical_lines">Vertical Lites</label>
              <Field
                id="vertical_lines"
                name="vertical_lines"
                className="form-input text-right"
                autoComplete="vertical_lines"
                placeholder='Vertical Lines'
                type='number'
              />
              {(submitCount && errors.vertical_lines) ? <InputError message={errors.vertical_lines} className="mt-2" /> : ''}
            </div>
            <div className={submitCount ? (errors.horizontal_lines) ? 'has-error' : 'has-success' : ''}>
              <label htmlFor="horizontal_lines">Horizontal Lites</label>
              <Field
                id="horizontal_lines"
                name="horizontal_lines"
                className="form-input text-right"
                autoComplete="horizontal_lines"
                placeholder='Horizontal Lines'
                type='number'
              />
              {(submitCount && errors.horizontal_lines) ? <InputError message={errors.horizontal_lines} className="mt-2" /> : ''}
            </div>
          </>
        )}
      </div>
    </fieldset>
  )
}

export default Details
