import { ROLES } from './constants'

export const isAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ADMIN) !== undefined
}

export const isAccountManager = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ACCOUNT_MANAGER) !== undefined
}

export const isDealer = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.DEALER) !== undefined
}

export const isSubDealer = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.SUB_DEALER) !== undefined
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

export const isShipping = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.SHIPPING) !== undefined
}

export const isPlantManager = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.PLANT_MANAGER) !== undefined
}

export const getRoleName = (roles: string[]): string => {
  if (isAdmin(roles)) {
    return 'Admin'
  } else if (isAccountManager(roles)) {
    return 'Account Manager'
  } else if (isDealer(roles)) {
    return 'Dealer'
  } else if (isSubDealer(roles)) {
    return 'Sub Dealer'
  } else if (isProduction(roles)) {
    return 'Production'
  } else if (isAccounting(roles)) {
    return 'Accounting'
  } else if (isShipping(roles)) {
    return 'Shipping'
  } else if (isPlantManager(roles)) {
    return 'Plant Manager'
  } else {
    return 'User'
  }
}
