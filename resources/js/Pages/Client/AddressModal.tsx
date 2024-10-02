import React, { useState } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { Field, Form, Formik } from 'formik'

import PrimaryButton from '@/Components/PrimaryButton'

const AddressModal = ({
  showModal,
  onClose,
  address,
  currentAddress,
  setModalAddress
}: {
  showModal: boolean
  onClose: CallableFunction
  address: string[]
  currentAddress: string
  setModalAddress: CallableFunction
}) => {
  const initialValue = {
    address: currentAddress
  }
  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Confirm Address</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className="h-[550px] overflow-y-scroll">
            <Formik
              initialValues={initialValue}
              onSubmit={(values: any) => {
                setModalAddress(values.address)
                onClose()
              }}
            >
              {() => (
                <Form>
                  <p>We found the same client registered with a different address.</p>
                  <p>Please choose the best option.</p>
                    {address.map((address, index) => {
                      return (
                        <div key={index} className='grid gap-4 grid-cols-3 border border-gray-200 p-4'>
                          <div className='col-span-3 flex'>
                            <Field
                              type="radio"
                              name="address"
                              value={address}
                              className="form-radio"
                            />
                            <label htmlFor="address">{address}</label>
                          </div>
                        </div>
                      )
                    })}
                    <p>Keep the currently selected address</p>
                    <div className='grid gap-4 grid-cols-3 border border-gray-200 p-4'>
                      <div className='col-span-3 flex'>
                        <Field
                          type="radio"
                          name="address"
                          value={currentAddress}
                          className="form-radio"
                        />
                        <label htmlFor="address">{currentAddress}</label>
                      </div>
                    </div>
                    <div className="flex items-center justify-between mt-4">
                      <button className='btn btn-danger uppercase' onClick={ (e) => {
                        e.preventDefault()
                        onClose(false)
                      }}>Cancel</button>
                      <PrimaryButton className="btn btn-primary" type='submit'>
                        Save
                      </PrimaryButton>
                    </div>
                </Form>
              )}
            </Formik>
          </div>
        </div>
    </Modal>
  )
}

export default AddressModal
