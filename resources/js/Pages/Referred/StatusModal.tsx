import { Fragment } from 'react'
import { Dialog, Transition } from '@headlessui/react'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { ReferralsStatusUpdate } from '@/types'
import Badge from '@/Components/Badge'
import StatusBadge from '@/Components/StatusBadge'

const StatusModal = ({ showModal, handleClose, statusHistory}: { showModal: boolean, handleClose: Function, statusHistory: ReferralsStatusUpdate[]}) => {
  return (
    <Transition appear show={showModal} as={Fragment}>
        <Dialog as="div" open={showModal} onClose={() => handleClose(false)}>
            <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0"
                enterTo="opacity-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
            >
                <div className="fixed inset-0" />
            </Transition.Child>
            <div className="fixed inset-0 bg-[black]/60 z-[999] overflow-y-auto">
                <div className="flex items-start justify-center min-h-screen px-4">
                    <Transition.Child
                        as={Fragment}
                        enter="ease-out duration-300"
                        enterFrom="opacity-0 scale-95"
                        enterTo="opacity-100 scale-100"
                        leave="ease-in duration-200"
                        leaveFrom="opacity-100 scale-100"
                        leaveTo="opacity-0 scale-95"
                    >
                        <Dialog.Panel as="div" className="panel border-0 p-0 rounded-lg overflow-hidden my-8 w-full max-w-lg text-black dark:text-white-dark">
                            <div className="flex bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-between px-5 py-3">
                                <div className="text-lg font-bold">Status Updates</div>
                                <button type="button" className="text-white-dark hover:text-dark" onClick={() => handleClose(false)}>
                                  <CloseIcon />
                                </button>
                            </div>
                            <div className="p-5">
                              <div className='grid grid-cols-1 gap-y-2.5'>
                                {statusHistory.map((history) => {
                                  return (
                                    <div key={history.id} className="bg-white dark:bg-[#1b2e4b] rounded-md border border-white-light dark:border-dark px-6 py-3.5">
                                      <div className="py-sm-2.5 sm:flex block ltr:md:text-left rtl:md:text-right text-center items-md-center">
                                        <div className="flex md:flex-row flex-col justify-between items-center flex-1 w-full">
                                          <div className="font-semibold md:my-0 my-3 w-full">
                                            <div className="text-dark dark:text-[#bfc9d4] text-base w-full flex justify-between">
                                              <span>{history.user.name}</span>
                                              <StatusBadge status={history.status} />
                                            </div>
                                            <div className="text-white-dark text-xs">{new Date(history.created_at).toLocaleDateString()}</div>
                                          </div>
                                        </div>
                                      </div>
                                      <div className="py-3">
                                        {history.notes}
                                      </div>
                                    </div>
                                  )
                                })}
                              </div>
                            </div>
                        </Dialog.Panel>
                    </Transition.Child>
                </div>
            </div>
        </Dialog>
    </Transition>
  )
}

export default StatusModal
