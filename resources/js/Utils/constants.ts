export type Locale = 'en' | 'es'
export type Theme = 'light' | 'dark' | 'system'
export type Menu = 'vertical' | 'collapsible-vertical' | 'horizontal'
export type Layout = 'full' | 'boxed-layout'
export type RtlClass = 'rtl' | 'ltr'
export type Animation = 'animate__fadeIn' | 'animate__fadeInDown' | 'animate__fadeInUp' | 'animate__fadeInLeft' | 'animate__fadeInRight' | 'animate__slideInDown' | 'animate__slideInLeft' | 'animate__slideInRight' | 'animate__zoomIn'
export type Navbar = 'navbar-sticky' | 'navbar-floating' | 'navbar-static'

export interface Status {
  id: string
  color: string
  label: string
}

export const STATUS: Status[] = [
  { id: 'estimate', color: 'bg-primary', label: 'ESTIMATE' }
]

export interface ThemeConfig {
  locale: Locale
  theme: Theme
  menu: Menu
  layout: Layout
  rtlClass: RtlClass
  animation: Animation
  navbar: Navbar
  semidark: boolean
  sidebar: boolean
  isDarkMode: boolean
}

export const THEME_CONFIG: ThemeConfig = {
  locale: 'en',
  theme: 'light',
  menu: 'vertical',
  layout: 'full',
  rtlClass: 'ltr',
  animation: 'animate__fadeIn',
  navbar: 'navbar-sticky',
  semidark: false,
  sidebar: false,
  isDarkMode: false
}

export const ROLES = {
  ADMIN: 'admin',
  CLIENT_ADMIN: 'client_admin',
  CLIENT: 'client'
}

export const PERSMISSION_USERS_LIST = 'users list'
export const PERSMISSION_USERS_CREATE = 'users create'
export const PERSMISSION_USERS_UPDATE = 'users update'
export const PERSMISSION_USERS_DELETE = 'users delete'
export const PERSMISSION_USERS_DETAILS = 'users details'

export const PERSMISSIONS = [
  {
    module: 'Users',
    permissions: [
      PERSMISSION_USERS_LIST,
      PERSMISSION_USERS_CREATE,
      PERSMISSION_USERS_UPDATE,
      PERSMISSION_USERS_DELETE,
      PERSMISSION_USERS_DETAILS
    ]
  }
]

export const PHONE_REG_EXP = /^((\\+[1-9]{1,4}[ \\-]*)|(\\([0-9]{2,3}\\)[ \\-]*)|([0-9]{2,4})[ \\-]*)*?[0-9]{3,4}?[ \\-]*[0-9]{3,4}?$/
export const COMPANY_NAME: string = import.meta.env.VITE_COMPANY_NAME
export const RECAPTCHA_SITE_KEY: string = import.meta.env.VITE_RECAPTCHA_SITE_KEY
export type PRODUCT_SYSTEM = 'FIXED WINDOWS' | 'SINGLE HUNT' | 'HORIZONTAL ROLLER'
export const PRODUCT_SYSTEMS = {
  FIXED_WINDOWS: 'FIXED WINDOWS',
  SINGLE_HUNT: 'SINGLE HUNT',
  HORIZONTAL_ROLLER: 'HORIZONTAL ROLLER'
}
