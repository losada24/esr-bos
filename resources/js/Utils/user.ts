import { ROLES } from './constants'

export const isAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ADMIN) !== undefined
}

export const isClientAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.CLIENT_ADMIN) !== undefined
}

export const isClient = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.CLIENT) !== undefined
}

export const can = (permissions: string[], permission: string): boolean => {
  return permissions.find((p) => p === permission) !== undefined
}

export const isProduction = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.PRODUCTION) !== undefined
}

export const isAccounting = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ACCOUNTING) !== undefined
}
