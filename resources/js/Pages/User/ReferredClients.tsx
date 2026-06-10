import { useState } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import Pagination from '@/Components/Pagination'
import { Head } from '@inertiajs/react'
import { type PageProps, type PaginatorLink } from '@/types'

type ReferredClient = {
  id: number
  name: string
  email?: string | null
  phone?: string | null
  source?: string | null
  created_at?: string | null
  company_contact?: {
    id: number
    name: string
  } | null
}

type ReferrerEntity = {
  id: number
  name: string
  email?: string | null
  phone?: string | null
}

type ReferralRecord = {
  id: number
  name?: string | null
  email?: string | null
  phone?: string | null
  type: string
  client_id?: number | null
  user_id?: number | null
  clients_count: number
  clients: ReferredClient[]
  referrer_user?: ReferrerEntity | null
  referrer_client?: ReferrerEntity | null
}

type ReferredClientsProps = PageProps & {
  referrals: {
    data: ReferralRecord[]
    links: PaginatorLink[]
  }
  can_view_all_referrals: boolean
}

const formatDate = (value?: string | null) => {
  if (!value) {
    return '-'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return date.toLocaleDateString()
}

const getReferrerKind = (referral: ReferralRecord) => {
  if (referral.referrer_user != null || referral.user_id != null) {
    return 'User'
  }

  if (referral.referrer_client != null || referral.client_id != null) {
    return 'Client'
  }

  return 'Manual'
}

export default function ReferredClients ({ auth, referrals, can_view_all_referrals }: ReferredClientsProps) {
  const pageTitle = can_view_all_referrals ? 'Referred Clients' : 'My Referred Clients'
  const groupedBySource = referrals.data.reduce<Record<string, ReferralRecord[]>>((groups, referral) => {
    const source = referral.type || 'Unknown'

    if (groups[source] == null) {
      groups[source] = []
    }

    groups[source].push(referral)

    return groups
  }, {})

  const sourceEntries = Object.entries(groupedBySource)
  const [openSources, setOpenSources] = useState<Record<string, boolean>>(() =>
    sourceEntries.reduce<Record<string, boolean>>((accumulator, [source], index) => {
      accumulator[source] = index === 0
      return accumulator
    }, {})
  )
  const [openReferrers, setOpenReferrers] = useState<Record<number, boolean>>({})

  const toggleSource = (source: string) => {
    setOpenSources((current) => ({
      ...current,
      [source]: !current[source]
    }))
  }

  const toggleReferrer = (referralId: number) => {
    setOpenReferrers((current) => ({
      ...current,
      [referralId]: !current[referralId]
    }))
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle={pageTitle}>
      <Head title={pageTitle} />

      <div className="space-y-6">
        <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
          {can_view_all_referrals
            ? 'This view is grouped by source, then by referrer, and then by referred clients.'
            : 'This view shows your referred clients grouped by source and referrer.'}
        </div>

        {sourceEntries.map(([source, sourceReferrals]) => (
          <section key={source} className="overflow-hidden rounded-xl border border-slate-200">
            <button
              type="button"
              className="flex w-full items-center justify-between gap-4 bg-slate-100 px-6 py-4 text-left"
              onClick={() => { toggleSource(source) }}
            >
              <div>
                <h2 className="text-lg font-semibold text-slate-900">{source}</h2>
                <p className="text-sm text-slate-600">
                  {sourceReferrals.length} referrer{sourceReferrals.length === 1 ? '' : 's'}
                </p>
              </div>
              <svg
                className={`h-5 w-5 text-slate-500 transition-transform ${openSources[source] ? 'rotate-180' : ''}`}
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
              >
                <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clipRule="evenodd" />
              </svg>
            </button>

            {openSources[source] && (
              <div className="space-y-4 px-4 py-4">
                {sourceReferrals.map((referral) => (
                  <article key={referral.id} className="overflow-hidden rounded-lg border border-slate-200">
                    <button
                      type="button"
                      className="flex w-full items-center justify-between gap-4 bg-slate-50 px-5 py-4 text-left"
                      onClick={() => { toggleReferrer(referral.id) }}
                    >
                      <div>
                        <h3 className="text-base font-semibold text-slate-900">{referral.name ?? 'Unnamed referrer'}</h3>
                        <p className="text-sm text-slate-600">
                          {[referral.email, referral.phone].filter(Boolean).join(' · ') || 'No contact info'}
                        </p>
                      </div>
                      <div className="flex items-center gap-4">
                        <div className="text-sm text-slate-600 md:text-right">
                          <div>{getReferrerKind(referral)}</div>
                          <div>{referral.clients_count} client{referral.clients_count === 1 ? '' : 's'}</div>
                        </div>
                        <svg
                          className={`h-5 w-5 text-slate-500 transition-transform ${openReferrers[referral.id] ? 'rotate-180' : ''}`}
                          viewBox="0 0 20 20"
                          fill="currentColor"
                          aria-hidden="true"
                        >
                          <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clipRule="evenodd" />
                        </svg>
                      </div>
                    </button>

                    {openReferrers[referral.id] && (
                      <div className="table-responsive">
                        <table className="w-full whitespace-nowrap">
                          <thead>
                            <tr className="font-bold text-left">
                              <th className="px-6 pt-5 pb-4">Client</th>
                              <th className="px-6 pt-5 pb-4">Contact</th>
                              <th className="px-6 pt-5 pb-4">Company</th>
                              <th className="px-6 pt-5 pb-4">Created</th>
                            </tr>
                          </thead>
                          <tbody>
                            {referral.clients.map((client) => (
                              <tr key={client.id} className="hover:bg-gray-100 focus-within:bg-gray-100">
                                <td className="border-t px-6 py-4 align-top">{client.name}</td>
                                <td className="border-t px-6 py-4 align-top">
                                  <div>{client.email ?? '-'}</div>
                                  <div>{client.phone ?? '-'}</div>
                                </td>
                                <td className="border-t px-6 py-4 align-top">{client.company_contact?.name ?? '-'}</td>
                                <td className="border-t px-6 py-4 align-top">{formatDate(client.created_at)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    )}
                  </article>
                ))}
              </div>
            )}
          </section>
        ))}

        {sourceEntries.length === 0 && (
          <div className="rounded-lg border border-dashed border-slate-300 px-6 py-8 text-center text-sm text-slate-500">
            No referred clients found.
          </div>
        )}
      </div>

      <Pagination links={referrals.links} />
    </AuthenticatedLayout>
  )
}
