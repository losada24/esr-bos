import React from 'react'
import { Link } from '@inertiajs/react'
import { isSubDealer } from '@/Utils/user'
import { type User, type Role } from '@/types'

const PdfLinks = ({ id, user }: { id: number, user: User }) => {
  return (
    <>
      {!isSubDealer(user.roles.map((role: Role) => role.name)) && (
        <li>
          <Link
            href={route('pdf.report', id)}
          >
            Cost Report
          </Link>
        </li>
      )}
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
    </>
  )
}

export default PdfLinks