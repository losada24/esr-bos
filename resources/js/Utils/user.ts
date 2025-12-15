import { ROLES } from './constants'

export const isAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ADMIN) !== undefined
}

export const isAccountManager = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ACCOUNT_MANAGER) !== undefined
}

export const isOwner = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.OWNER) !== undefined
}
export const isOwnerAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.OWNER_ADMIN) !== undefined
}

export const isInstaller = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.INSTALLER) !== undefined
}

export const isSupervisor = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.SUPERVISOR) !== undefined
}

export const isFrontdesk = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.FRONTDESK) !== undefined
}
export const isFrontdeskAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.FRONTDESK_ADMIN) !== undefined
}
export const isServiceManager = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.SERVICE_MANAGER) !== undefined
}
export const isPaymentCoordinator = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.PAYMENT_COORDINATOR) !== undefined
}

export const can = (permissions: string[], permission: string): boolean => {
  return permissions.find((p) => p === permission) !== undefined
}

export const getRoleName = (roles: string[]): string => {
  if (isAdmin(roles)) {
    return 'Admin'
  } else if (isAccountManager(roles)) {
    return 'Account Manager'
  } else if (isInstaller(roles)) {
    return 'Installer'
  } else if (isSupervisor(roles)) {
    return 'Supervisor'
  } else if (isOwner(roles)) {
    return 'Owner'
  } else if (isOwnerAdmin(roles)) {
    return 'Owner Admin'
  } else if (isServiceManager(roles)) {
    return 'Service Manager'
  } else if (isFrontdesk(roles)) {
    return 'Frontdesk'
  } else if (isPaymentCoordinator(roles)) {
    return 'Payment Coordinator'
  } else if (isFrontdeskAdmin(roles)) {
    return 'Frontdesk Admin'
  } else {
    return 'User'
  }
}
