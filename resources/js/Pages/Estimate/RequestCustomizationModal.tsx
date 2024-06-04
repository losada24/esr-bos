import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Product } from '@/types'
import { router } from '@inertiajs/react'
import { type FormikHelpers, Formik } from 'formik'
import { estimateCommentsSchema, type EstimateCommentsUpdate } from './EstimateCommon'
import RequestCustomizationForm from './RequestCustomizationForm'

const RequestCustomizationModal = ({ showModal, onClose, product }: {
  showModal: boolean
  onClose: CallableFunction
  product: Product | null
}) => {
  const handleSubmit = async (values: any, helpers: FormikHelpers<EstimateCommentsUpdate>) => {
    router.post(route('estimate.customization.update', product?.id), {
      _method: 'PUT',
      ...values
    }, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      },
      onSuccess: () => {
        onClose(false)
      }
    })
  }

  const initialValues: EstimateCommentsUpdate = {
    id: product?.id ?? 0,
    attachment: product?.attachment ?? '',
    comments: product?.comments ?? ''
  }

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Request Customization</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className='flex flex-col justify-start'>
            <Formik<EstimateCommentsUpdate>
              initialValues={initialValues}
              validationSchema={estimateCommentsSchema}
              onSubmit={handleSubmit}
            >
              {({ errors, submitCount, setFieldValue }) => (
                <RequestCustomizationForm
                  submitCount={submitCount}
                  errors={errors}
                  isCreate={false}
                  setFieldValue={setFieldValue}
                  estimateId={product?.order_id ?? 0}
                  attachment={product?.attachment ?? ''}
                />
              )}
            </Formik>
          </div>
        </div>
    </Modal>
  )
}

export default RequestCustomizationModal
