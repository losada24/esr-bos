import { create } from 'zustand'
import { THEME_CONFIG, type ThemeConfig, type Theme, type Layout, type RtlClass, type Animation, type Navbar } from '@/Utils/constants'

const initialState: ThemeConfig = {
  theme: THEME_CONFIG.theme,
  menu: THEME_CONFIG.menu,
  layout: THEME_CONFIG.layout,
  rtlClass: THEME_CONFIG.rtlClass,
  animation: THEME_CONFIG.animation,
  navbar: THEME_CONFIG.navbar,
  locale: THEME_CONFIG.locale,
  isDarkMode: false,
  sidebar: THEME_CONFIG.sidebar,
  semidark: THEME_CONFIG.semidark
}

export interface ThemeState {
  themeState: ThemeConfig
  toogleMenu?: (payload: Theme) => void
  toogleLayout?: (payload: Layout) => void
  toogleRtl?: (payload: RtlClass) => void
  toogleAnimation?: (payload: Animation) => void
  toggleNavbar?: (payload: Navbar) => void
  toggleSemidark?: () => void
  toggleSidebar: () => void
}

export const useStore = create<ThemeState>((set) => ({
  themeState: initialState,
  toggleSidebar: () => {
    set((state) => {
      state.themeState.sidebar = !state.themeState.sidebar
      return { ...state }
    })
  }
}))
