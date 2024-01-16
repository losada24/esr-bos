import React from 'react'
import Dropdown from '@/Components/Dropdown'
import PrintIcon from '@/Components/Icons/PrintIcon'
import AngleIcon from '@/Components/Icons/AngleIcon'
import { Link } from '@inertiajs/react'
import { ESTIMATE_STATUS } from '@/Utils/constants'

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
          <li>
            <Link
              href={route('pdf.report', id)}
            >
              Cost Report
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.estimate.with.prices', id)}
            >
              Estimate with Prices
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.estimate.without.prices', id)}
            >
              Estimate without Prices
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.estimate.with.total.prices', id)}
            >
              Estimate only Total Prices
            </Link>
          </li>
          <li>
            <Link
              href={status === ESTIMATE_STATUS ? route('estimate.show', id) : route('order.show', id)}
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
