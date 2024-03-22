import React from 'react'
import Dropdown from '@/Components/Dropdown'
import PrintIcon from '@/Components/Icons/PrintIcon'
import AngleIcon from '@/Components/Icons/AngleIcon'
import { Link } from '@inertiajs/react'

const PrintButton = ({ id }: { id: number }) => {
  return (
    <div className='dropdown'>
      <Dropdown
        placement='bottom-start'
        btnClassName="btn btn-primary w-full gap-2 dropdown-toggle"
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
        <ul className="ltr:right-0 rtl:left-0 whitespace-nowrap">
          <li>
            <Link
              href={route('pdf.work.order', id)}
            >
              Work Order
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.cutting.list', id)}
            >
              Cutting List
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.material.consumption', id)}
            >
              Material Consumption
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.material.release', id)}
            >
              Material Release
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.po.screen', id)}
            >
              PO Screens
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.po.glass', id)}
            >
              PO Glasses
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.po.balance', id)}
            >
              PO Balances
            </Link>
          </li>
          <li>
            <Link
              href={route('pdf.po.external.products', id)}
            >
              PO External Products
            </Link>
          </li>
          <li>
            <button
              onClick={() => {
                const url = route('labels.labelsByPieces', id)
                window.open(url, '_blank')
              }}
            >
              Cutting List CSV
            </button>
          </li>
          <li>
            <button
              onClick={() => {
                const url = route('labels.productLabels', id)
                window.open(url, '_blank')
              }}
            >
              Order Info CSV
            </button>
          </li>
        </ul>
      </Dropdown>
    </div>
  )
}

export default PrintButton
