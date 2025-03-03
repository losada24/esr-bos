import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type Role, type InstallationTeam, type PageProps, type User, type PaymentExtraFields, type InstallationPayment } from '@/types'
import { formatPrice } from '@/Utils/price'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { isAccountManager, isAdmin, isSupervisor } from '@/Utils/user'
import { useState } from 'react'
import ShowInstallerFilter from './ShowInstallerFilter'
import Supervisor from './Supervisor'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import ExportIcon from '@/Components/Icons/ExportIcon'

interface BiweeklyInstaller {
  id: number
  start_biweekly_period: string
  end_biweekly_period: string
  payment_method: string

}

type IndexUserProps = PageProps & {
  biweeklys: BiweeklyInstaller[]
  installer: User
  statuses: string[]
}

export default function ShowBiweekly ({ auth, installer, biweeklys, statuses }: IndexUserProps) {
  // console.log(biweeklys)
  // console.log(orders)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Biweekly periods of installer ${installer.name}`}
          actions={
            <Link
              className="btn btn-primary"
              href={route('report.create_biweekly', { installation_team: installer.id })}
            >
              <span>Create Biweekly</span>
            </Link>
          }
      >
        <Head title={`Biweekly periods of installer ${installer.name}`} />
            <div className='table-responsive'>
          <table className="table-auto w-full">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Start Biweekly Date</th>
              <th className="px-6 pt-5 pb-4">Year</th>
              <th className="px-6 pt-5 pb-4">Payment Method</th>
              <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {biweeklys.map((biweekly: BiweeklyInstaller, index) => {
                const start_biweekly_period = new Date(biweekly.start_biweekly_period).toLocaleDateString('en-US', {
                  timeZone: 'UTC', // ✅ Corrige el desfase
                  day: '2-digit',
                  month: 'long'
                })
                const end_biweekly_period = new Date(biweekly.end_biweekly_period).toLocaleDateString('en-US', {
                  timeZone: 'UTC', // ✅ Corrige el desfase
                  day: '2-digit',
                  month: 'long'
                })
                const year = new Date(biweekly.start_biweekly_period).getFullYear()
                return (
                  <tr key={biweekly.id}>
                     <td className="px-6 py-4 border-t">
                     {start_biweekly_period} to {end_biweekly_period}
                    </td>
                    <td className="px-6 py-4 border-t ">
                    {year}
                    </td>
                    <td className="px-6 py-4 border-t ">
                      {biweekly.payment_method}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                      <Link
                        href={route('report.edit_biweekly', { id: biweekly.id, installation_team: installer.id })}
                        title='Edit Biweekly'
                         className='mr-2'
                      >
                        <EditIcon />
                      </Link>
                      <a href={route('report.excel-installer', biweekly.id)} target='_blank' rel="noreferrer" title='Download Payment Report'>
                          <ExportIcon/>
                        </a>
                    </td>
                  </tr>
                )
              })}
              {biweeklys.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Biweeklies found.
                  </td>
                </tr>
              )}
            </tbody>
            <tfoot>
            </tfoot>
          </table>
        </div>
      </AuthenticatedLayout>
  )
}
