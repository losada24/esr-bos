import PerfectScrollbar from 'react-perfect-scrollbar'
// import { useDispatch, useSelector } from 'react-redux'
// import { NavLink, useLocation } from 'react-router-dom'
// import { toggleSidebar } from '../../store/themeConfigSlice'
import AnimateHeight from 'react-animate-height'
// import { type IRootState } from '../../store'
import { useState, useEffect } from 'react'
import { useStore, type ThemeState } from '@/Store/theme'
import NavLink from '@/Components/NavLink'
import logo from '../../assets/images/logo.svg'
import UserIcon from '@/Components/Icons/UserIcon'
import SidebarLinkLabel from '@/Components/SidebarLinkLabel'
import ReferralIcon from '@/Components/Icons/ReferralIcon'
import DashboardIcon from '@/Components/Icons/DashboardIcon'

const Sidebar = () => {
  const [themeState, toggleSidebar] = useStore((state: ThemeState) => [
    state.themeState,
    state.toggleSidebar
  ])
  const [currentMenu, setCurrentMenu] = useState<string>('')
  const [errorSubMenu, setErrorSubMenu] = useState(false)
  // const themeConfig = useSelector((state: IRootState) => state.themeConfig)
  // const semidark = useSelector((state: IRootState) => state.themeConfig.semidark)
  // const location = useLocation()
  // const dispatch = useDispatch()
  const toggleMenu = (value: string) => {
    setCurrentMenu((oldValue) => {
      return oldValue === value ? '' : value
    })
  }

  /* useEffect(() => {
    const selector = document.querySelector('.sidebar ul a[href="' + window.location.pathname + '"]')
    if (selector) {
      selector.classList.add('active')
      const ul: any = selector.closest('ul.sub-menu')
      if (ul) {
        let ele: any = ul.closest('li.menu').querySelectorAll('.nav-link') || []
        if (ele.length) {
          ele = ele[0]
          setTimeout(() => {
            ele.click()
          })
        }
      }
    }
  }, [])

  useEffect(() => {
    if (window.innerWidth < 1024 && themeState.sidebar) {
      toggleSidebar()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location]) */

  return (
        <div className={`${themeState.semidark ? 'dark' : ''}`}>
            <nav
                className={`sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300 ${themeState.semidark ? 'text-white-dark' : ''}`}
            >
                <div className="bg-white dark:bg-black h-full">
                    <div className="flex justify-between items-center px-4 py-3">
                        <NavLink href="/" active={false} className="main-logo flex items-center shrink-0">
                            <img className="w-8 ml-[5px] flex-none" src={logo} alt="logo" />
                            <span className="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle lg:inline dark:text-white-light">{'VRISTO'}</span>
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
                                <NavLink href="/" active={false} className="group">
                                    <div className="flex items-center">
                                        <DashboardIcon />
                                        <SidebarLinkLabel>Dashboard</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                            {/* <li className="menu nav-item">
                                <button type="button" className={`${currentMenu === 'dashboard' ? 'active' : ''} nav-link group w-full`} onClick={() => { toggleMenu('dashboard') }}>
                                    <div className="flex items-center">
                                        <DashboardIcon />
                                        <SidebarLinkLabel>Dashboard</SidebarLinkLabel>
                                    </div>

                                    <div className={currentMenu === 'dashboard' ? 'rotate-90' : 'rtl:rotate-180'}>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9 5L15 12L9 19" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
                                        </svg>
                                    </div>
                                </button>

                                <AnimateHeight duration={300} height={currentMenu === 'dashboard' ? 'auto' : 0}>
                                    <ul className="sub-menu text-gray-500">
                                        <li>
                                            <NavLink href="/" active={false}>Sales</NavLink>
                                        </li>
                                        <li>
                                            <NavLink href="/analytics" active={false}>Analytics</NavLink>
                                        </li>
                                    </ul>
                                </AnimateHeight>
                            </li> */}

                            <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>ADMINISTRATION</span>
                            </h2>

                            <li className="nav-item">
                                <ul>
                                    <li className="nav-item">
                                        <NavLink href="/user" active={false} className="group">
                                            <div className="flex items-center">
                                              <UserIcon />
                                              <SidebarLinkLabel>Users</SidebarLinkLabel>
                                            </div>
                                        </NavLink>
                                    </li>
                                </ul>
                            </li>

                            <h2 className="py-3 px-7 flex items-center uppercase font-extrabold bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] -mx-4 mb-1">
                                <svg className="w-4 h-5 flex-none hidden" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5" fill="none" strokeLinecap="round" strokeLinejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Actions</span>
                            </h2>

                            <li className="menu nav-item">
                                <NavLink href="/referred" active={false} className="group">
                                    <div className="flex items-center">
                                        <ReferralIcon />
                                        <SidebarLinkLabel>Referrals</SidebarLinkLabel>
                                    </div>
                                </NavLink>
                            </li>
                        </ul>
                    </PerfectScrollbar>
                </div>
            </nav>
        </div>
  )
}

export default Sidebar
