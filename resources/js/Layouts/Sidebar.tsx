import PerfectScrollbar from 'react-perfect-scrollbar'
import { useStore, type ThemeState } from '@/Store/theme'
import NavLink from '@/Components/NavLink'
import logo from '../../assets/images/logo-reylosglass.png'
import UserIcon from '@/Components/Icons/UserIcon'
import ReferralIcon from '@/Components/Icons/ReferralIcon'
import MoneyBagIcon from '@/Components/Icons/MoneyBagIcon'
import SidebarLinkLabel from '@/Components/SidebarLinkLabel'
import DashboardIcon from '@/Components/Icons/DashboardIcon'
import BookIcon from '@/Components/Icons/BookIcon'
import MoneyIcon from '@/Components/Icons/MoneyIcon'
import CompanyIcon from '@/Components/Icons/CompanyIcon'
import { COMPANY_NAME } from '@/Utils/constants'
import { isAdmin, isClientAdmin, isClient, isAccounting, isProduction } from '@/Utils/user'
import { type Role, type Auth } from '@/types'

const Sidebar = ({ auth }: { auth: Auth }) => {
  const [themeState, toggleSidebar] = useStore((state: ThemeState) => [
    state.themeState,
    state.toggleSidebar
  ])

  return (
        <div className={`${themeState.semidark ? 'dark' : ''}`}>
            <nav
                className={`sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300 ${themeState.semidark ? 'text-white-dark' : ''}`}
            >
                <div className="bg-white dark:bg-black h-full">
                    <div className="flex justify-between items-center px-4 py-3">
                        <NavLink href={route('dashboard')} active={false} className="main-logo flex items-center shrink-0">
                            <img className="w-8 ml-[5px] flex-none" src={logo} alt="logo" />
                            <span className="text-lg ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle lg:inline dark:text-white-light">{COMPANY_NAME}</span>
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
                                        <SidebarLinkLabel>Dashboard</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                            {(isAdmin(auth.user.roles.map((role: Role) => role.name)) || isClientAdmin(auth.user.roles.map((role: Role) => role.name))) && (
                              <>
                                <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                    <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>ADMINISTRATION</span>
                                </h2>

                                <li className="nav-item">
                                    <ul>
                                        {isAdmin(auth.user.roles.map((role: Role) => role.name)) && (
                                          <>
                                            <li className="nav-item">
                                                <NavLink href={route('user.index')} active={route().current('user.index') || route().current('user.create') || route().current('user.edit')} className="group">
                                                    <div className="flex items-center">
                                                      <UserIcon />
                                                      <SidebarLinkLabel>Users</SidebarLinkLabel>
                                                    </div>
                                                </NavLink>
                                            </li>
                                            <li className="nav-item">
                                                <NavLink href={route('raw-material.index')} active={route().current('raw-material.index') || route().current('raw-material.create') || route().current('raw-material.edit')} className="group">
                                                    <div className="flex items-center">
                                                      <BookIcon />
                                                      <SidebarLinkLabel>Raw Materials</SidebarLinkLabel>
                                                    </div>
                                                </NavLink>
                                            </li>
                                          </>
                                        )}
                                        <li className="nav-item">
                                            <NavLink href={route('client.index')} active={route().current('client.index') || route().current('client.create') || route().current('client.edit')} className="group">
                                                <div className="flex items-center">
                                                  <ReferralIcon />
                                                  <SidebarLinkLabel>Clients</SidebarLinkLabel>
                                                </div>
                                            </NavLink>
                                        </li>
                                        <li className="nav-item">
                                            <NavLink href={route('company.index')} active={route().current('company.index') || route().current('company.create') || route().current('company.edit')} className="group">
                                                <div className="flex items-center">
                                                  <CompanyIcon />
                                                  <SidebarLinkLabel>Companies</SidebarLinkLabel>
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
                            {(isAdmin(auth.user.roles.map((role: Role) => role.name))) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('company.profile')} active={route().current('company.profile')} className="group">
                                        <div className="flex items-center">
                                            <CompanyIcon />
                                            <SidebarLinkLabel>Company</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            )}
                            {(isAdmin(auth.user.roles.map((role: Role) => role.name)) || isClientAdmin(auth.user.roles.map((role: Role) => role.name)) || isClient(auth.user.roles.map((role: Role) => role.name))) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('estimate.index')} active={route().current('estimate.index') || route().current('estimate.create') || route().current('estimate.edit') || route().current('estimate.show')} className="group">
                                        <div className="flex items-center">
                                            <MoneyBagIcon />
                                            <SidebarLinkLabel>Estimates</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            )}
                            {(isAdmin(auth.user.roles.map((role: Role) => role.name)) ||
                              isClientAdmin(auth.user.roles.map((role: Role) => role.name)) ||
                              isAccounting(auth.user.roles.map((role: Role) => role.name)) ||
                              isProduction(auth.user.roles.map((role: Role) => role.name))) && (
                              <>
                                <li className="menu nav-item">
                                    <NavLink href={route('order.index')} active={route().current('order.index') || route().current('order.show') || route().current('order.workOrder')} className="group">
                                        <div className="flex items-center">
                                            <MoneyIcon />
                                            <SidebarLinkLabel>Orders</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                              </>
                            )}
                        </ul>
                    </PerfectScrollbar>
                </div>
            </nav>
        </div>
  )
}

export default Sidebar
