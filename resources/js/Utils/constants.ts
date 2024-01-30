export type Locale = 'en' | 'es'
export type Theme = 'light' | 'dark' | 'system'
export type Menu = 'vertical' | 'collapsible-vertical' | 'horizontal'
export type Layout = 'full' | 'boxed-layout'
export type RtlClass = 'rtl' | 'ltr'
export type Animation = 'animate__fadeIn' | 'animate__fadeInDown' | 'animate__fadeInUp' | 'animate__fadeInLeft' | 'animate__fadeInRight' | 'animate__slideInDown' | 'animate__slideInLeft' | 'animate__slideInRight' | 'animate__zoomIn'
export type Navbar = 'navbar-sticky' | 'navbar-floating' | 'navbar-static'

export const ESTIMATE_STATUS: string = 'estimate'
export const SUB_DEALER_ESTIMATE: string = 'sub_dealer_estimate'
export const ACCOUNTING_STATUS: string = 'accounting'
export const PRODUCTION_STATUS: string = 'production'
export const PRODUCTION_COMPLETED: string = 'production completed'
export const READY_FOR_DELIVERY: string = 'ready for delivery'
export const PRODUCTION_IN_PROGRESS: string = 'production in progress'
export const SCHEDULED_PRODUCTION: string = 'scheduled production'

export interface Status {
  id: string
  color: string
  label: string
}

export const STATUS: Status[] = [
  { id: ESTIMATE_STATUS, color: 'bg-primary', label: ESTIMATE_STATUS.toUpperCase() }
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
  ACCOUNT_MANAGER: 'account_manager',
  DEALER: 'dealer',
  SUB_DEALER: 'sub_dealer',
  ACCOUNTING: 'accounting',
  PRODUCTION: 'production',
  SHIPPING: 'shipping'
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
export type PRODUCT_SYSTEM = 'FIXED WINDOWS' | 'SINGLE HUNG' | 'HORIZONTAL ROLLER'
export const PRODUCT_SYSTEMS = {
  FIXED_WINDOWS: 'FIXED WINDOWS',
  SINGLE_HUNG: 'SINGLE HUNG',
  HORIZONTAL_ROLLER: 'HORIZONTAL ROLLER'
}
export const EXPRESS_GLASS_TYPE: string = 'EXPRESS'
export const REGULAR_GLASS_TYPE: string = 'REGULAR'
export const RUSH_GLASS_TYPE: string = 'RUSH'

export const COLORS: string[] = [
  '#020617',
  '#64748b',
  '#450a0a',
  '#dc2626',
  '#fca5a5',
  '#fb923c',
  '#fbbf24',
  '#a3e635',
  '#16a34a',
  '#1e40af',
  '#93c5fd',
  '#6b21a8',
  '#4c0519'
]

export const SQUARE = 'SQUARE'
export const RECTANGLE = 'RECTANGLE'
export const CIRCLE = 'CIRCLE'
export const TRIANGLE = 'TRIANGLE'
export const TRAPEZOID = 'TRAPEZOID'
export const PARALLELOGRAM = 'PARALLELOGRAM'
// export const PENTAGON = 'PENTAGON'
// export const HEXAGON = 'HEXAGON'
// export const OCTAGON = 'OCTAGON'
// export const RIGHT_TRIANGLE = 'RIGHT_TRIANGLE'

export const SHAPES: string[] = [
  SQUARE,
  CIRCLE,
  TRAPEZOID,
  TRIANGLE,
  PARALLELOGRAM,
  RECTANGLE
  // RIGHT_TRIANGLE,
  // PENTAGON,
  // HEXAGON,
  // OCTAGON
]

export const FOOT: string = 'FOOT'
export const UNIT: string = 'UNIT'
export const SQFT: string = 'SQFT'
export const LENGTH_OF_BARS: number = 16
export const CONFIG_XO: string = 'XO'
export const NO_CERTIFICATION_STANDARD_MESSAGE: string = 'The windows does not comply with certification standards'
export const PAYMENT_METHODS = {
  CASH: 'CASH',
  CHECK: 'CHECK',
  CREDIT: 'CREDIT',
  BANK_TRANSFER: 'BANK TRANSFER'
}
export const ADDRESS_REQUIRED_AFTER_AMOUNT: number = import.meta.env.VITE_ADDRESS_REQUIRED_AFTER_AMOUNT
export const GLASS_TYPE = {
  REGULAR: REGULAR_GLASS_TYPE,
  EXPRESS: EXPRESS_GLASS_TYPE
}

export const PO_SCREEN: string = 'SC'
export const PO_GLASS: string = 'GL'
export const PO_BALANCE: string = 'BL'

export const PO_TITLES: Record<string, string> = {
  [PO_SCREEN]: 'PO SCREEN',
  [PO_GLASS]: 'PO GLASS',
  [PO_BALANCE]: 'PO BALANCE'
}

export const RUSH_GLASS_NEW_COLOR: string = 'OBSCURE/PRIVACY'
export const LEAD_TIME_BY_GLASS_TYPE: Record<string, string> = {
  [RUSH_GLASS_TYPE]: 'RUSH (2-3 WEEKS)',
  [EXPRESS_GLASS_TYPE]: 'EXPRESS (3-4 WEEKS)',
  [REGULAR_GLASS_TYPE]: 'REGULAR (6-8 WEEKS)'
}
export const CERTIFICATION_SQFT: number = 27.24
