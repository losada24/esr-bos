export type Locale = 'en' | 'es'
export type Theme = 'light' | 'dark' | 'system'
export type Menu = 'vertical' | 'collapsible-vertical' | 'horizontal'
export type Layout = 'full' | 'boxed-layout'
export type RtlClass = 'rtl' | 'ltr'
export type Animation = 'animate__fadeIn' | 'animate__fadeInDown' | 'animate__fadeInUp' | 'animate__fadeInLeft' | 'animate__fadeInRight' | 'animate__slideInDown' | 'animate__slideInLeft' | 'animate__slideInRight' | 'animate__zoomIn'
export type Navbar = 'navbar-sticky' | 'navbar-floating' | 'navbar-static'

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
  ADMIN: 'admin'
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
