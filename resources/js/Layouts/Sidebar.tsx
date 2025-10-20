import PerfectScrollbar from 'react-perfect-scrollbar'
import { useStore, type ThemeState } from '@/Store/theme'
import NavLink from '@/Components/NavLink'
import logo from '../../assets/images/logo-reylosglass.png'
import UserIcon from '@/Components/Icons/UserIcon'
import ReferralIcon from '@/Components/Icons/ReferralIcon'
import SidebarLinkLabel from '@/Components/SidebarLinkLabel'
import DashboardIcon from '@/Components/Icons/DashboardIcon'
import CompanyIcon from '@/Components/Icons/CompanyIcon'
import { isAdmin, isAccountManager, isFrontdesk, isOwner, isSupervisor, isServiceManager, isInstaller, isPaymentCoordinator } from '@/Utils/user'
import { type Role, type Auth } from '@/types'
import WindowsIcon from '@/Components/Icons/WindowsIcon'
import PrintIcon from '@/Components/Icons/PrintIcon'
import CodeIcon from '@/Components/Icons/CodeIcon'
import CalendarIcon from '@/Components/Icons/CalendarIcon'
import FolderIcon from '@/Components/Icons/FolderIcon'
import RoundIcon from '@/Components/Icons/RoundIcon'
import BuildingIcon from '@/Components/Icons/BuildingIcon'

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
  const IS_SERVICE_MANAGER = isServiceManager(auth.user.roles.map((role: Role) => role.name))
  const IS_INSTALLER = isInstaller(auth.user.roles.map((role: Role) => role.name))
  const IS_PAYMENT_COORDINATOR = isPaymentCoordinator(auth.user.roles.map((role: Role) => role.name))

  return (
        <div className={`${themeState.semidark ? 'dark' : ''}`}>
            <nav
                className={`sidebar fixed min-h-screen h-full flex flex-col top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300 ${themeState.semidark ? 'text-white-dark' : ''}`}
            >
                <div className="bg-white dark:bg-black h-full flex flex-col">
                    <div className="flex justify-between items-center px-4 py-3 shrink-0">
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

                          {(IS_ADMIN || IS_FRONTDESK) && (
                              <>
                              <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Frontdesk</span>
                            </h2>

                            <li className="menu nav-item">
                                <NavLink href={route('frontdesk.index')} active={route().current('frontdesk.index')} className="group">
                                    <div className="flex items-center">
                                        <CodeIcon />
                                        <SidebarLinkLabel>Pipeline</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>

                            <li className="menu nav-item">
                                    <NavLink href={route('client.index')} active={route().current('client.index')} className="group">
                                        <div className="flex items-center">
                                            <ReferralIcon />
                                            <SidebarLinkLabel>Contact</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>

                                <li className="menu nav-item">
                                    <NavLink href={route('company_contact.index')} active={route().current('company_contact.index')} className="group">
                                        <div className="flex items-center">
                                            <BuildingIcon />
                                            <SidebarLinkLabel>Company</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                                </li>
                                 <li className="menu nav-item">
                                <NavLink href={route('source.index')} active={route().current('source.index')} className="group">
                                    <div className="flex items-center">
                                        <RoundIcon />
                                        <SidebarLinkLabel>Sources</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                              </>
                          )}

                           {(IS_ADMIN || IS_OWNER) && (
                            <>
                            <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Sales</span>
                            </h2>

                            <li className="menu nav-item">
                                <NavLink href={route('sales.index')} active={route().current('sales.index')} className="group">
                                    <div className="flex items-center">
                                        <CodeIcon />
                                        <SidebarLinkLabel>Sales Pipeline</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                            <li className="menu nav-item">
                                <NavLink href={route('sales.calendar')} active={route().current('sales.calendar')} className="group">
                                    <div className="flex items-center">
                                        <CalendarIcon />
                                        <SidebarLinkLabel>Sales Calendar</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                               </>
                           )}
                           {(IS_ADMIN ) && (
                            <>
                             <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Order Processing</span>
                            </h2>
                            </>
                           )}
                           {(IS_ADMIN) && (
                               <>
                             <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Order Storage</span>
                            </h2>
                               </>
                           )}
                           <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Operations</span>
                            </h2>
                            {(IS_ADMIN || IS_ACCOUNT_MANAGER || IS_SERVICE_MANAGER || IS_PAYMENT_COORDINATOR) && (
                            <>
                             <li className="menu nav-item">
                                <NavLink href={route('dashboard')} active={route().current('dashboard')} className="group">
                                    <div className="flex items-center">
                                        <DashboardIcon />
                                        <SidebarLinkLabel>Project Schedule Operations</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                            <li className="menu nav-item">
                                    <NavLink href={route('order.index')} active={route().current('order.index')} className="group">
                                        <div className="flex items-center">
                                            <FolderIcon />
                                            <SidebarLinkLabel>Order</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                            </li>
                            <li className="menu nav-item">
                              <NavLink href={route('installation_team.index')} active={route().current('installation_team.index') || route().current('installation_team.create') || route().current('installation_team.edit')} className="group">
                                  <div className="flex items-center">
                                    <ReferralIcon />
                                    <SidebarLinkLabel>Installation Team</SidebarLinkLabel>
                                  </div>
                              </NavLink>
                          </li>

                           </>
                            )}
                             {(IS_SUPERVISOR || IS_OWNER) && (
                              <>
                               <li className="menu nav-item">
                                <NavLink href={route('dashboard')} active={route().current('dashboard')} className="group">
                                    <div className="flex items-center">
                                        <DashboardIcon />
                                        <SidebarLinkLabel>Project Schedule Operations</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                              </li>
                              <li className="menu nav-item">
                                    <NavLink href={route('report.show-supervisor-report', { id: auth.user.id })} active={route().current('report.show-supervisor-report')} className="group">
                                        <div className="flex items-center">
                                            <ReferralIcon/>
                                            <SidebarLinkLabel>My Orders</SidebarLinkLabel>
                                        </div>
                                    </NavLink>
                              </li>
                              </>
                             ) }
                              { (IS_ADMIN || IS_SERVICE_MANAGER || IS_PAYMENT_COORDINATOR) && (
                                <>
                                <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                      <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                          <line x1="5" y1="12" x2="19" y2="12"></line>
                                      </svg>
                                      <span>Reports</span>
                                  </h2>
                                  <li className="menu nav-item">
                                          <NavLink href={route('report.supervisor')} active={route().current('report.supervisor')} className="group">
                                              <div className="flex items-center">
                                                  <ReferralIcon/>
                                                  <SidebarLinkLabel>Supervisors</SidebarLinkLabel>
                                              </div>
                                          </NavLink>
                                      </li>
                                      <li className="menu nav-item">
                                          <NavLink href={route('report.installer')} active={route().current('report.installer')} className="group">
                                              <div className="flex items-center">
                                                  <ReferralIcon/>
                                                  <SidebarLinkLabel>Installers</SidebarLinkLabel>
                                              </div>
                                          </NavLink>
                                      </li>
                                      <li className="menu nav-item">
                                          <NavLink href={route('biweekly.index')} active={route().current('biweekly.index')} className="group">
                                              <div className="flex items-center">
                                                  <CalendarIcon/>
                                                  <SidebarLinkLabel>Biweekly</SidebarLinkLabel>
                                              </div>
                                          </NavLink>
                                      </li>
                                      <li className="menu nav-item">
                                          <NavLink href={route('report.product-summary')} active={route().current('report.product-summary')} className="group">
                                              <div className="flex items-center">
                                                  <ReferralIcon/>
                                                  <SidebarLinkLabel>Product Summary</SidebarLinkLabel>
                                              </div>
                                          </NavLink>
                                      </li>
                                </>
                              )}
                            {(IS_ADMIN || IS_ACCOUNT_MANAGER) && (
                              <>
                                <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                    <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                    <span>ADMINISTRATION</span>
                                </h2>
                                        <li className="menu nav-item">
                                          <NavLink href={route('bigin.index')} active={route().current('bigin.index')} className="group">
                                                <div className="flex items-center">
                                                  <WindowsIcon />
                                                  <SidebarLinkLabel>Bigin Integration</SidebarLinkLabel>
                                                </div>
                                            </NavLink>
                                        </li>
                                        <li className="menu nav-item">
                                            <NavLink href={route('user.index')} active={route().current('user.index') || route().current('user.create') || route().current('user.edit')} className="group">
                                                <div className="flex items-center">
                                                  <UserIcon />
                                                  <SidebarLinkLabel>Users</SidebarLinkLabel>
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
