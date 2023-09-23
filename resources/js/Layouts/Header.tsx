import logo from '../../assets/images/logo-reylosglass.png'
import Dropdown from '@/Components/Dropdown'
import { useStore, type ThemeState } from '@/Store/theme'
import NavLink from '@/Components/NavLink'
import { router } from '@inertiajs/react'
import SignOutIcon from '@/Components/Icons/SignOutIcon'
import { isAdmin } from '@/Utils/user'
import { type Role, type Auth } from '@/types'
import { COMPANY_NAME } from '@/Utils/constants'
import ProfileImage from '@/Components/ProfileImage'
import ProfileIcon from '@/Components/Icons/ProfileIcon'
import { ellipse } from '@/Utils/string'

const Header = ({ auth }: { auth: Auth }) => {
  const [themeState, toggleSidebar] = useStore((state: ThemeState) => [
    state.themeState,
    state.toggleSidebar
  ])

  const isRtl = themeState.rtlClass === 'rtl'
  return (
        <header className={`z-40 ${themeState.semidark && themeState.menu === 'horizontal' ? 'dark' : ''}`}>
            <div className="shadow-sm">
                <div className="relative bg-white flex w-full items-center px-5 py-2.5 dark:bg-black">
                    <div className="horizontal-logo flex lg:hidden justify-between items-center ltr:mr-2 rtl:ml-2">
                        <NavLink href="/" active={false} className="main-logo flex items-center shrink-0">
                            <img className="w-8 ltr:-ml-1 rtl:-mr-1 inline" src={logo} alt="logo" />
                            <span className="text-lg ltr:ml-1.5 rtl:mr-1.5  font-semibold  align-middle hidden md:inline dark:text-white-light transition-all duration-300">{COMPANY_NAME}</span>
                        </NavLink>
                        <button
                            type="button"
                            className="collapse-icon flex-none dark:text-[#d0d2d6] hover:text-primary dark:hover:text-primary flex lg:hidden ltr:ml-2 rtl:mr-2 p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:bg-white-light/90 dark:hover:bg-dark/60"
                            onClick={() => {
                              toggleSidebar()
                            }}
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20 7L4 7" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                                <path opacity="0.5" d="M20 12L4 12" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                                <path d="M20 17L4 17" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
                            </svg>
                        </button>
                    </div>
                    <div className="sm:flex-1 ltr:sm:ml-0 ltr:ml-auto sm:rtl:mr-0 rtl:mr-auto flex justify-end items-center space-x-1.5 lg:space-x-2 rtl:space-x-reverse dark:text-[#d0d2d6]">
                        <div className="dropdown shrink-0 flex">
                            <Dropdown
                                offset={[0, 8]}
                                placement={`${isRtl ? 'bottom-start' : 'bottom-end'}`}
                                btnClassName="relative group block"
                                button={<ProfileImage name={auth.user.name} />}
                            >
                                <ul className="text-dark dark:text-white-dark !py-0 w-[230px] font-semibold dark:text-white-light/90">
                                    <li>
                                        <div className="flex items-center px-4 py-4">
                                            <ProfileImage name={auth.user.name} />
                                            <div className="ltr:pl-4 rtl:pr-4 truncate">
                                                <h4 className="text-base">
                                                    {ellipse(auth.user.name, 10)}
                                                    <span className="text-xs bg-success-light rounded text-success px-1 ltr:ml-2 rtl:ml-2">
                                                      {isAdmin(auth.user.roles.map((role: Role) => role.name)) ? 'Admin' : 'User'}
                                                    </span>
                                                </h4>
                                                <button type="button" className="text-black/60 hover:text-primary dark:text-dark-light/60 dark:hover:text-white">
                                                    {auth.user.email}
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <NavLink href={route('profile.edit')} active={false} className="dark:hover:text-white">
                                            <ProfileIcon />
                                            Profile
                                        </NavLink>
                                    </li>
                                    <li className="border-t border-white-light dark:border-white-light/10">
                                        <NavLink href='#' onClick={() => { router.post('logout') }} active={false} className="text-danger !py-3">
                                            <SignOutIcon />
                                            Sign Out
                                        </NavLink>
                                    </li>
                                </ul>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </div>
        </header>
  )
}

export default Header
