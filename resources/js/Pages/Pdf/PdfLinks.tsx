import React from 'react'
import { Link } from '@inertiajs/react'
import { isAdmin, isAccountManager, isAccounting } from '@/Utils/user'

const PdfLinks = ({ id, roles }: { id: number, roles?: string[] }) => {
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
      {(isAdmin(roles ?? []) || isAccounting(roles ?? []) || isAccountManager(roles ?? [])) && (
        <li>
          <Link
            href={route('pdf.production', id)}
          >
            Production Cost
          </Link>
        </li>
      )}
    </>
  )
}

export default PdfLinks
