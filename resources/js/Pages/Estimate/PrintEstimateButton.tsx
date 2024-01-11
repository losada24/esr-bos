import React from 'react'
import { router } from '@inertiajs/react'
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
                  <button onClick={() => {
                    router.get(route('pdf.report', id))
                  }}>Report</button>
              </li>
              <li>
                  <button onClick={() => {
                    router.get(route('pdf.estimate', id))
                  }}>Estimate</button>
              </li>
          </ul>
      </Dropdown>
    </div>
  )
}

export default PrintEstimateButton
