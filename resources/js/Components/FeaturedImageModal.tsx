import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type ModalProps } from '@/types'

const FeaturedImageModal = ({ showModal, onClose, selectedModalProps }: { showModal: boolean, onClose: CallableFunction, selectedModalProps: ModalProps | null }) => {
  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">{selectedModalProps?.title}</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className='flex justify-center'>
            <img
              src={ route('download.image-download', { id: selectedModalProps?.image ?? 0 })} alt={selectedModalProps?.title} />
          </div>
        </div>
    </Modal>
  )
}

export default FeaturedImageModal
