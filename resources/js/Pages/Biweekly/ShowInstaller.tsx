import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type User, type PageProps } from '@/types'
import 'flatpickr/dist/flatpickr.css'

interface BiweeklyInstallerHistory {
  installation_team_id: number
  biweekly_id: number
  data: JSON
  type_history: string
  installation_team: User
}

type IndexUserProps = PageProps & {
  biweeklies: BiweeklyInstallerHistory[]
  biweekly_id: number
  biweeklyTitle: string
}

export default function Index ({ auth, biweeklies, biweekly_id, biweeklyTitle }: IndexUserProps) {
  console.log(biweeklies)
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={'Biweekly Reports' + ' - ' + biweeklyTitle}
          actions={
                <div className='flex flex-row gap-2 items-right justify-end'>
                <button
                className="btn btn-primary"
                onClick={() => {
                  window.open(route('biweekly.show-pdf-biweekly-payment-resumen-general', { biweeklyId: biweekly_id }), '_blank')
                }
                }
                  >
                <span>Total Summary Report</span>
              </button>
              <button
                className="btn btn-primary"
                onClick={() => {
                  window.open(route('biweekly.show-pdf-biweekly-payment-extra-work', { biweeklyId: biweekly_id }), '_blank')
                }
                }
                  >
                <span>Extra Work Report</span>
              </button>

              </div>
          }
      >
        <Head title={'Biweekly periods' } />
            <div className='table-responsive'>
          <table className="table-auto w-full">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Installer Name</th>
              <th className="px-6 pt-5 pb-4">Payments</th>
              {/* <th className="px-6 pt-5 pb-4">Payments</th> */}
              <th className="px-6 pt-5 pb-4 ">Totals Summary</th>
              </tr>
            </thead>
            <tbody>
              {biweeklies.map((biweekly: BiweeklyInstallerHistory, index) => {
                return (
                  <tr key={biweekly.biweekly_id}>
                     <td className="px-6 py-4 border-t">
                     {biweekly.installation_team.name}
                    </td>
                    <td className="border-t px-6 py-4">
                    <div className="flex items-center space-x-4">
                      <button
                      className="btn btn-primary"
                      onClick={() => {
                        window.open(route('biweekly.show-pdf-biweekly', { biweeklyId: biweekly.biweekly_id, installerId: biweekly.installation_team_id }), '_blank')
                      }
                      }
                    >
                      <span>View PDF</span>
                    </button>
                    <button
                      className="btn btn-primary"
                      onClick={() => {
                        window.open(route('biweekly.export-biweekly-payment', { biweeklyId: biweekly.biweekly_id, installerId: biweekly.installation_team_id }), '_blank')
                      }
                      }
                    >
                      <span>Download Excel</span>
                    </button>
                    </div>
                    </td>
                    <td className="border-t px-6 py-4">
                    <div className="flex items-center space-x-4">
                      <button
                      className="btn btn-primary"
                      onClick={() => {
                        window.open(route('biweekly.show-pdf-biweekly-payment-resumen', { installerId: biweekly.installation_team_id, biweeklyId: biweekly.biweekly_id }), '_blank')
                      }
                      }
                    >
                    <span>View PDF</span>
                    </button>
                    </div>
                    </td>
                  </tr>
                )
              })}
              {biweeklies.length === 0 && (
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
