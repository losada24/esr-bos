import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Product } from '@/types'

const RequestCustomizationModal = ({ showModal, onClose, product }: {
  showModal: boolean
  onClose: CallableFunction
  product: Product | null
}) => {
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
          {product?.comments !== '' && (
            <div className='px-1 py-3 font-semibold text-white-dark'>
              <p>{product?.comments}</p>
            </div>
          )}
          {product?.attachment !== '' && (
            <a href={`/storage/${product?.attachment}`} target="_blank" className="text-primary underline" rel="noreferrer">View Attachment</a>
          )}
        </div>
    </Modal>
  )
}

export default RequestCustomizationModal
