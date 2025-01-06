import PerfectScrollbar from 'react-perfect-scrollbar'
import { useStore, type ThemeState } from '@/Store/theme'
import NavLink from '@/Components/NavLink'
import logo from '../../assets/images/logo-reylosglass.png'
import UserIcon from '@/Components/Icons/UserIcon'
import ReferralIcon from '@/Components/Icons/ReferralIcon'
import SidebarLinkLabel from '@/Components/SidebarLinkLabel'
import DashboardIcon from '@/Components/Icons/DashboardIcon'
import CompanyIcon from '@/Components/Icons/CompanyIcon'
import { isAdmin, isAccountManager, isFrontdesk, isOwner, isSupervisor } from '@/Utils/user'
import { type Role, type Auth } from '@/types'
import WindowsIcon from '@/Components/Icons/WindowsIcon'
import ProfileIcon from '@/Components/Icons/ProfileIcon'

const Sidebar = ({ auth }: { auth: Auth }) => {
  const [themeState, toggleSidebar] = useStore((state: ThemeState) => [
    state.themeState,
    state.toggleSidebar
  ])

  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const IS_FRONTDESK = isFrontdesk(auth.user.roles.map((role: Role) => role.name))
  const IS_OWNER = isOwner(auth.user.roles.map((role: Role) => role.name))
  const IS_SUPERVISOR = isSupervisor(auth.user.roles.map((role: Role) => role.name))

  return (
        <div className={`${themeState.semidark ? 'dark' : ''}`}>
            <nav
                className={`sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300 ${themeState.semidark ? 'text-white-dark' : ''}`}
            >
                <div className="bg-white dark:bg-black h-full">
                    <div className="flex justify-between items-center px-4 py-3">
                        <NavLink href={route('dashboard')} active={false} className="main-logo flex items-center shrink-0">
                            <img className="w-52 flex-none" src={logo} alt="logo" />
                        </NavLink>

                        <button
                            type="button"
                            className="collapse-icon w-8 h-8 rounded-full flex items-center hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light transition duration-300 rtl:rotate-180"
                            onClick={() => { toggleSidebar() }}
                        >
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" className="w-5 h-5 m-auto">
                                <path d="M13 19L7 12L13 5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        </button>
                    </div>
                    <PerfectScrollbar className="h-[calc(100vh-80px)] relative">
                        <ul className="relative font-semibold space-y-0.5 p-4 py-0">
                            <li className="menu nav-item">
                                <NavLink href={route('dashboard')} active={route().current('dashboard')} className="group">
                                    <div className="flex items-center">
                                        <DashboardIcon />
                                        <SidebarLinkLabel>Project Schedule Reylos</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                            {(IS_ADMIN || IS_ACCOUNT_MANAGER) && (
                              <>
                                <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                    <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>ADMINISTRATION</span>
                                </h2>

                                <li className="nav-item">
                                    <ul>
                                        <li className="nav-item">
                                          <NavLink href={route('bigin.index')} active={route().current('bigin.index')} className="group">
                                                <div className="flex items-center">
                                                  <WindowsIcon />
                                                  <SidebarLinkLabel>Bigin Integration</SidebarLinkLabel>
                                                </div>
                                            </NavLink>
                                        </li>
                                        <li className="nav-item">
                                            <NavLink href={route('user.index')} active={route().current('user.index') || route().current('user.create') || route().current('user.edit')} className="group">
                                                <div className="flex items-center">
                                                  <UserIcon />
                                                  <SidebarLinkLabel>Users</SidebarLinkLabel>
                                                </div>
                                            </NavLink>
                                        </li>
                                    </ul>
                                    <ul>
                                        <li className="nav-item">
                                            <NavLink href={route('installation_team.index')} active={route().current('installation_team.index') || route().current('installation_team.create') || route().current('installation_team.edit')} className="group">
                                                <div className="flex items-center">
                                                  <ReferralIcon />
                                                  <SidebarLinkLabel>Installation Team</SidebarLinkLabel>
                                                </div>
                                            </NavLink>
                                        </li>
                                    </ul>
                                </li>
                              </>
                            )}
                            <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Actions</span>
                            </h2>
                            {(IS_ADMIN || IS_ACCOUNT_MANAGER) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('order.index')} active={route().current('order.index')} className="group">
                                        <div className="flex items-center">
                                            <CompanyIcon />
                                            <SidebarLinkLabel>Order</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            )}
                            {(IS_ADMIN || IS_FRONTDESK || IS_ACCOUNT_MANAGER || IS_OWNER) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('client.index')} active={route().current('client.index')} className="group">
                                        <div className="flex items-center">
                                            <ReferralIcon />
                                            <SidebarLinkLabel>Client</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            )}

                          <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Reports</span>
                            </h2>
                            {(IS_ADMIN || IS_ACCOUNT_MANAGER) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('report.supervisor')} active={route().current('report.supervisor')} className="group">
                                        <div className="flex items-center">
                                            <ReferralIcon/>
                                            <SidebarLinkLabel>Supervisors</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            )}

                          {(IS_ADMIN || IS_ACCOUNT_MANAGER) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('report.installer')} active={route().current('report.installer')} className="group">
                                        <div className="flex items-center">
                                            <ReferralIcon/>
                                            <SidebarLinkLabel>Installers</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                          )}

                            {(IS_SUPERVISOR) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('report.show_supervisor', { id: auth.user.id })} active={route().current('report.show_supervisor')} className="group">
                                        <div className="flex items-center">
                                            <ReferralIcon/>
                                            <SidebarLinkLabel>My Orders</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            )}
                             {/* (IS_ADMIN || IS_FRONTDESK || IS_ACCOUNT_MANAGER || IS_OWNER) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('client.index')} active={route().current('client.index')} className="group">
                                        <div className="flex items-center">
                                            <WindowsIcon />
                                            <SidebarLinkLabel>Installers</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            ) */}
                        </ul>
                    </PerfectScrollbar>
                </div>
            </nav>
        </div>
  )
}

export default Sidebar
