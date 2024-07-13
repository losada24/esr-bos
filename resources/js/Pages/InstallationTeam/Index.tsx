import React, { useState } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type PaginatorLink, type InstallationTeam } from '@/types'
import Pagination from '@/Components/Pagination'
import InstallationTeamFilter from './InstallationTeamFilter'

type IndexCompanyProps = PageProps & {
  installation_teams: {
    data: InstallationTeam[]
    meta: {
      links: PaginatorLink[]
    }
  }
}

export default function Index ({ auth, installation_teams }: IndexCompanyProps) {
  console.log(installation_teams)
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Team?')) {
      router.delete(route('installation_team.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Installation Teams'
          actions={
            <Link
              className="btn btn-primary"
              href={route('installation_team.create')}
            >
              <span>Create Installation Team</span>
            </Link>
          }
      >
        <Head title="Installation Teams" />
        <InstallationTeamFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">User</th>
                <th className="px-6 pt-5 pb-4 text-right">Number of Member</th>
                <th className="px-6 pt-5 pb-4">Type Of Housing</th>
                <th className="px-6 pt-5 pb-4">Worker Compensation Expiration</th>
                <th className="px-6 pt-5 pb-4">Liability Expiration</th>
                <th className="px-6 pt-5 pb-4">Attachments</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {installation_teams.data.map((installation_team) => {
                return (
                  <tr
                    key={installation_team.id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {installation_team.user?.email}
                    </td>
                    <td className="border-t px-6 py-4 align-top  text-right">
                      {installation_team.number_of_member}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {installation_team.typeHousing?.map((typeHousing) => {
                        return (
                          <span key={typeHousing.id} className="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">
                            {typeHousing.name}
                          </span>
                        )
                      })}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {installation_team.worker_compensation_expiration_date.toString()}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {installation_team.liability_expiration_date.toString()}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {installation_team.attachments.map((attachment) => {
                        return (
                          <div key={attachment.id}>
                            <a href={attachment.file_path} target="_blank" className="text-blue-500 hover:underline" rel="noreferrer">
                              {attachment.file_type === 'worker_compensation_attach' ? 'Worker Compensation' : 'Liability Expiration'}
                            </a>
                          </div>
                        )
                      })}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('installation_team.edit', installation_team.id)}
                        >
                          <EditIcon />
                        </Link>
                        <button
                          onClick={() => { destroy(installation_team.id) }}
                        >
                          <DeleteIcon />
                        </button>
                    </td>
                  </tr>
                )
              })}
              {installation_teams.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Installation teams found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={installation_teams.meta.links} />
      </AuthenticatedLayout>
  )
}
