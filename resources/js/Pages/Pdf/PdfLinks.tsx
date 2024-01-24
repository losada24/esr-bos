import React from 'react'
import { Link } from '@inertiajs/react'

const PdfLinks = ({ id }: { id: number }) => {
  return (
    <>
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
          href={route('pdf.estimate.with.total.prices', id)}
        >
          Estimate only Total Prices
        </Link>
      </li>
      <li>
        <Link
          href={route('pdf.estimate.without.prices', id)}
        >
          Estimate without Prices
        </Link>
      </li>
    </>
  )
}

export default PdfLinks
