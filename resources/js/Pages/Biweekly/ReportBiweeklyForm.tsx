import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import { Form, type FormikErrors } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type BiweeklyInstaller } from '@/types'


const ReportBiweeklyForm = ({
  submitCount,
  errors,
  isCreate,
  setFieldValue,
  values

}: {
  submitCount: number
  errors: FormikErrors<BiweeklyInstaller>
  isCreate: boolean
  setFieldValue: (field: string, value: any) => void
  values: BiweeklyInstaller
}) => {
  return (
    <>
      <Form className='space-y-5'>
        <div className='grid gap-4 grid-cols-3'>
          <div className={submitCount ? (errors.period) ? 'has-error' : 'has-success' : ''}>
                    <label htmlFor="period">Payment Date</label>
                   <Flatpickr
                     options={{
                       mode: 'range',
                       dateFormat: 'Y-m-d',
                       position: 'auto right'
                     }}
                     name="period"
                     value={[values.period[0], values.period[1]]}

                     className="form-input"
                     onChange={(dates: Date[]) => {
                       if (dates.length === 2) {
                         const [startDate, endDate] = dates
                         setFieldValue('period', [
                           startDate.toISOString().slice(0, 10),
                           endDate.toISOString().slice(0, 10)
                         ])
                       }
                     }}
                   />
                   {submitCount && errors.period ? (<InputError message= {errors.period.toString()} className="mt-2"/>) : null}
                 </div>
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

export default ReportBiweeklyForm
