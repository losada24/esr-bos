import React, { useState, useEffect, type MouseEventHandler } from 'react'
import { usePage } from '@inertiajs/react'
import { type PageProps } from '@/types'
import IconSuccess from './Icons/SuccessIcon'
import CloseIcon from './Icons/CloseIcon'

const ButtonClose = ({ className, onClick }: { className?: string, onClick: MouseEventHandler<HTMLButtonElement> }) => {
  return (
    <button
      onClick={onClick}
      type="button"
      className={className}
    >
      <CloseIcon />
    </button>
  )
}

const FlashMessages = () => {
  const [visible, setVisible] = useState(true)
  const { flash, errors } = usePage<PageProps>().props
  const numOfErrors = Object.keys(errors).length

  useEffect(() => {
    setVisible(true)
  }, [flash, errors])

  return (
    <>
      {flash.success !== null && visible && (
        <div className="mb-4 flex items-center justify-between rounded bg-success-light p-3.5 text-danger dark:bg-success-dark-light">
          <span className="ltr:pr-2 rtl:pl-2">{flash.success}</span>
          <ButtonClose onClick={() => { setVisible(false) }} className='ltr:ml-auto rtl:mr-auto hover:opacity-80' />
        </div>
      )}
      {(flash.error !== null || numOfErrors > 0) && visible && (
        <div className="mb-4 flex items-center justify-between rounded bg-danger-light p-3.5 text-danger dark:bg-danger-dark-light">
          <span className="ltr:pr-2 rtl:pl-2">
            {flash.error !== null && flash.error}
            {numOfErrors === 1 && 'There is one form error'}
            {numOfErrors > 1 && `There are ${numOfErrors} form errors.`}
          </span>
          <ButtonClose onClick={() => { setVisible(false) }} className='ltr:ml-auto rtl:mr-auto hover:opacity-80' />
        </div>
      )}
    </>
  )
}

export default FlashMessages
