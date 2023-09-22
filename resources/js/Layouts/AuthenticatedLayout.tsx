import { type PropsWithChildren, type ReactNode, Suspense } from 'react'
import { type Auth } from '@/types'
import { useStore, type ThemeState } from '@/Store/theme'
import Sidebar from './Sidebar'
import Header from './Header'
import Footer from './Footer'
import Portals from './Portals'
import FlashMessages from '@/Components/FlashMessages'

export default function AuthenticatedLayout ({
  auth,
  pageTitle,
  children,
  actions
}: PropsWithChildren<{ auth: Auth, pageTitle?: string, actions?: ReactNode }>) {
  const [themeState, toggleSidebar] = useStore((state: ThemeState) => [
    state.themeState,
    state.toggleSidebar
  ])

  return (
    <div className={`${(themeState.sidebar && 'toggle-sidebar') || ''} ${themeState.menu} ${themeState.layout} ${themeState.rtlClass} 
      main-section antialiased relative font-nunito text-sm font-normal`}>
      <div className="relative">
        {/* sidebar menu overlay */}
        <div className={`${(!themeState.sidebar && 'hidden') || ''} fixed inset-0 bg-[black]/60 z-50 lg:hidden`} onClick={() => { toggleSidebar() }}></div>
        {/* screen loader */}
        <div className={`${themeState.navbar} main-container text-black dark:text-white-dark min-h-screen`}>
          {/* BEGIN SIDEBAR */}
          <Sidebar auth={auth}/>
          {/* END SIDEBAR */}
          <div className="main-content flex flex-col min-h-screen">
            {/* BEGIN TOP NAVBAR */}
            <Header auth={auth}/>
            {/* END TOP NAVBAR */}
            {/* BEGIN CONTENT AREA */}
            <Suspense>
                <div className={`${themeState.animation} p-6 animate__animated`}>
                  <div>
                    <div className="panel">
                      <div className="flex flex-row items-center justify-between mb-5">
                        <div className="sm:mb-0 mb-4">
                          {pageTitle !== null && <div className="text-lg font-semibold ltr:sm:text-left rtl:sm:text-right text-center">{pageTitle}</div>}
                        </div>
                        {actions}
                      </div>
                      <FlashMessages />
                      {children}
                    </div>
                  </div>
                </div>
            </Suspense>
            {/* END CONTENT AREA */}
            {/* BEGIN FOOTER */}
            <Footer />
            {/* END FOOTER */}
            <Portals />
          </div>
        </div>
      </div>
    </div>
  )
}
