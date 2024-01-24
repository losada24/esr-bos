import React from 'react'
import PrintIcon from '@/Components/Icons/PrintIcon'
import Dropdown from '@/Components/Dropdown'
import AngleIcon from '@/Components/Icons/AngleIcon'
import PdfLinks from '@/Pages/Pdf/PdfLinks'
import { type User } from '@/types'

const PrintEstimateButton = ({ id, user }: { id: number, user: User }) => {
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
            <PdfLinks id={id} />
          </ul>
      </Dropdown>
    </div>
  )
}

export default PrintEstimateButton
