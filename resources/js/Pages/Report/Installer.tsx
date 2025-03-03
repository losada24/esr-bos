import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import { type PageProps, type User, type PaginatorLink, type Role, type InstallationTeam } from '@/types'
import Pagination from '@/Components/Pagination'
import SupervisorFilter from './SupervisorFilter'
import { isAdmin, getRoleName } from '@/Utils/user'
import EyeIcon from '@/Components/Icons/EyeIcon'
import ExportIcon from '@/Components/Icons/ExportIcon'
import InstallationTeamFilter from '../InstallationTeam/InstallationTeamFilter'
import PlusIcon from '@/Components/Icons/PlusIcon'

type IndexUserProps = PageProps & {
  installation_teams: {
    data: InstallationTeam[]
    meta: {
      links: PaginatorLink[]
    }
  }
}

export default function Installer ({ auth, installation_teams }: IndexUserProps) {
  // const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this User?')) {
      router.delete(route('user.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Installers'
      >
        <Head title="Installers" />
        <InstallationTeamFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
              <th className="px-6 pt-5 pb-4">Company Name</th>
                <th className="px-6 pt-5 pb-4">Installer Name</th>
                <th className="px-6 pt-5 pb-4">Email</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
            {installation_teams.data.map((installation_team) =>{
              return (
                  <tr
                  key={installation_team.id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                    {installation_team.company_name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {installation_team.user?.name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                    {installation_team.user?.email}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('report.show_installer', installation_team.user?.id)}
                          title='View Orders'
                        >
                          <EyeIcon/>
                        </Link>

                        <Link
                          href={route('report.show_biweekly', installation_team.user?.id)}
                          title='Add Biweeklies'
                        >
                          <PlusIcon/>
                        </Link>
                    </td>
                  </tr>
              )
            })}
              {installation_teams.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Users found.
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
