import React from 'react'
import Dropdown from '@/Components/Dropdown'
import PrintIcon from '@/Components/Icons/PrintIcon'
import AngleIcon from '@/Components/Icons/AngleIcon'
import { Link } from '@inertiajs/react'
import { ESTIMATE_STATUS, SUB_DEALER_ESTIMATE } from '@/Utils/constants'
import PdfLinks from './PdfLinks'
import { type User } from '@/types'

const PrintEstimateOrderButton = ({ id, status }: { id: number, status?: string }) => {
  return (
    <div className='dropdown'>
      <Dropdown
        placement='bottom-start'
        btnClassName="btn btn-primary w-full gap-2 dropdown-toggle"
        button={
            <>
                <PrintIcon />
                Actions
                <span>
                  <AngleIcon />
                </span>
            </>
        }
      >
        <ul className="ltr:right-0 rtl:left-0 whitespace-nowrap">
          <PdfLinks id={id} />
          <li>
            <Link
              href={(status === ESTIMATE_STATUS || status === SUB_DEALER_ESTIMATE) ? route('estimate.show', id) : route('order.show', id)}
            >
              Go to Back
            </Link>
          </li>
        </ul>
      </Dropdown>
    </div>
  )
}

export default PrintEstimateOrderButton
