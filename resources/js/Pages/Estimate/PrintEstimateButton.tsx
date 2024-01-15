import React from 'react'
import { Link } from '@inertiajs/react'
import PrintIcon from '@/Components/Icons/PrintIcon'
import Dropdown from '@/Components/Dropdown'
import AngleIcon from '@/Components/Icons/AngleIcon'

const PrintEstimateButton = ({ id }: { id: number }) => {
  return (
    <div className='dropdown'>
      <Dropdown
          placement='bottom-start'
          btnClassName="btn btn-info w-full gap-2 dropdown-toggle"
          button={
              <>
                  <PrintIcon />
                  Print
                  <span>
                    <AngleIcon />
                  </span>
              </>
          }
      >
          <ul className="w-full">
            <li>
              <Link
                href={route('pdf.report', id)}
              >
                Report
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
                Estimate with Total Prices
              </Link>
            </li>
          </ul>
      </Dropdown>
    </div>
  )
}

export default PrintEstimateButton
