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
export const MATERIAL_REVIEWED: string = 'material reviewed'
export const PRODUCTION_COMPLETED: string = 'production completed'
export const SCHEDULED_PRODUCTION: string = 'scheduled production'
export const PRODUCTION_IN_PROGRESS: string = 'production in progress'
export const PARTIAL_PRODUCTION_COMPLETED: string = 'partial production completed'
export const READY_FOR_DELIVERY: string = 'ready for delivery'
export const READY_FOR_PARTIAL_DELIVERY: string = 'ready for partial delivery'
export const READY_FOR_PICKUP: string = 'ready for pickup'
export const READY_FOR_PARTIAL_PICKUP: string = 'ready for partial pickup'
export const ORDER_COMPLETED: string = 'order completed'
export const DELIVERED: string = 'delivered'
export const PARTIAL_DELIVERED: string = 'partial delivered'
export const PICKED_UP: string = 'picked up'
export const PARTIAL_PICKED_UP: string = 'partial picked up'

export interface Status {
  id: string
  color: string
  label: string
  hex?: string
}

export const STATUS: Status[] = [
  {
    id: ESTIMATE_STATUS,
    color: 'bg-primary',
    label: ESTIMATE_STATUS.toUpperCase(),
    hex: '#FFC107'
  },
  {
    id: SUB_DEALER_ESTIMATE,
    color: 'bg-primary',
    label: 'SUB ESTIMATE',
    hex: '#007bff'
  },
  {
    id: ACCOUNTING_STATUS,
    color: 'bg-primary',
    label: ACCOUNTING_STATUS.toUpperCase(),
    hex: '#28a745'
  },
  {
    id: PRODUCTION_STATUS,
    color: 'bg-primary',
    label: PRODUCTION_STATUS.toUpperCase(),
    hex: '#dc3545'
  },
  {
    id: PRODUCTION_COMPLETED,
    color: 'bg-primary',
    label: PRODUCTION_COMPLETED.toUpperCase(),
    hex: '#dc3545'
  },
  {
    id: SCHEDULED_PRODUCTION,
    color: 'bg-primary',
    label: SCHEDULED_PRODUCTION.toUpperCase(),
    hex: '#4ade80'
  },
  {
    id: PRODUCTION_IN_PROGRESS,
    color: 'bg-primary',
    label: PRODUCTION_IN_PROGRESS.toUpperCase(),
    hex: '#9a3412'
  },
  {
    id: PARTIAL_PRODUCTION_COMPLETED,
    color: 'bg-primary',
    label: PARTIAL_PRODUCTION_COMPLETED.toUpperCase(),
    hex: '#c084fc'
  },
  {
    id: READY_FOR_DELIVERY,
    color: 'bg-primary',
    label: READY_FOR_DELIVERY.toUpperCase(),
    hex: '#e11d48'
  },
  {
    id: READY_FOR_PARTIAL_DELIVERY,
    color: 'bg-primary',
    label: READY_FOR_PARTIAL_DELIVERY.toUpperCase(),
    hex: '#881337'
  },
  {
    id: READY_FOR_PICKUP,
    color: 'bg-primary',
    label: READY_FOR_PICKUP.toUpperCase(),
    hex: '#9a3412'
  },
  {
    id: READY_FOR_PARTIAL_PICKUP,
    color: 'bg-primary',
    label: READY_FOR_PARTIAL_PICKUP.toUpperCase(),
    hex: '#b91c1c'
  },
  {
    id: ORDER_COMPLETED,
    color: 'bg-primary',
    label: ORDER_COMPLETED.toUpperCase(),
    hex: '#4d7c0f'
  },
  {
    id: DELIVERED,
    color: 'bg-primary',
    label: DELIVERED.toUpperCase(),
    hex: '#fbbf24'
  },
  {
    id: PARTIAL_DELIVERED,
    color: 'bg-primary',
    label: PARTIAL_DELIVERED.toUpperCase(),
    hex: '#f97316'
  },
  {
    id: PICKED_UP,
    color: 'bg-primary',
    label: PICKED_UP.toUpperCase(),
    hex: '#bef264'
  },
  {
    id: PARTIAL_PICKED_UP,
    color: 'bg-primary',
    label: PARTIAL_PICKED_UP.toUpperCase(),
    hex: '##3f3f46'
  }
]

export const SERVICES = {
  DELIVERY_AND_INSTALLATION: 'DELIVERY AND INSTALLATION',
  DELIVERY_ONLY: 'DELIVERY ONLY',
  PICKUP: 'PICKUP',
  SERVICE: 'SERVICE'

}

export const SOURCES = {
  EXTERNAL_REFERAL: 'EXTERNAL REFERAL',
  INTERNAL_REFERAL: 'INTERNAL REFERAL'

}
export const CONTACT_TYPES = {
  RESIDENTIAL_CONTACT: 'RESIDENTIAL CONTACT',
  COMMERCIAL_CONTACT: 'COMMERCIAL CONTACT'

}
export const ORDER_TYPES = {
  RESIDENTIAL: 'RESIDENTIAL',
  COMMERCIAL: 'COMMERCIAL',
  SUPPLY: 'SUPPLY'
}

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
  INSTALLER: 'installer',
  SUPERVISOR: 'supervisor',
  OWNER: 'owner',
  FRONTDESK: 'frontdesk',
  SERVICE_MANAGER: 'service_manager',
  PAYMENT_COORDINATOR: 'payment_coordinator',
  OWNER_ADMIN: 'owner_admin',
  FRONTDESK_ADMIN: 'frontdesk_admin'

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
export const EXTERNAL_PRODUCTS = {
  MULLION: 'MULLION',
  CASEMENT: 'CASEMENT'
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
  '#134e4a'
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
export const CONFIG_OX: string = 'OX'
export const NO_CERTIFICATION_STANDARD_MESSAGE: string = 'The windows does not comply with certification standards'
export const PAYMENT_METHODS = {
  CASH: 'CASH',
  CASH_AND_FINANCE: 'CASH AND FINANCED',
  FINANCED: 'FINANCED'
}
export const ADDRESS_REQUIRED_AFTER_AMOUNT: number = import.meta.env.VITE_ADDRESS_REQUIRED_AFTER_AMOUNT
export const GLASS_TYPE = {
  REGULAR: REGULAR_GLASS_TYPE,
  EXPRESS: EXPRESS_GLASS_TYPE,
  RUSH: RUSH_GLASS_TYPE
}

export const PO_SCREEN: string = 'SC'
export const PO_GLASS: string = 'GL'
export const PO_BALANCE: string = 'BL'
export const PO_EXTERNAL_PRODUCTS: string = 'EP'

export const PO_TITLES: Record<string, string> = {
  [PO_SCREEN]: 'PO SCREEN',
  [PO_GLASS]: 'PO GLASS',
  [PO_BALANCE]: 'PO BALANCE',
  [PO_EXTERNAL_PRODUCTS]: 'EXTERNAL PRODUCTS'
}

export const RUSH_GLASS_NEW_COLOR: string = 'OBSCURE/PRIVACY'
export const LEAD_TIME_BY_GLASS_TYPE: Record<string, string> = {
  [RUSH_GLASS_TYPE]: 'RUSH (2-3 WEEKS)',
  [EXPRESS_GLASS_TYPE]: 'EXPRESS (3-4 WEEKS)',
  [REGULAR_GLASS_TYPE]: 'REGULAR (6-8 WEEKS)'
}
export const CERTIFICATION_SQFT: number = 27.24
export const EXTERNAL_PRODUCT_MULLION = 'MULLION'
export const EXTERNAL_PRODUCT_CASEMENT = 'CASEMENT'
export const CASEMENT_MULTIPOINT_CONFIG = 'MULTIPOINT'
export const SWEEP_LOCK = 'SWEEP LOCK'
export const STOREFRONT_CATEGORY = 3
export const PIVOT_CONFIG = 30
