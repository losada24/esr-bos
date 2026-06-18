import { ROLES } from './constants'

export const isAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ADMIN) !== undefined
}

export const isAccountManager = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ACCOUNT_MANAGER) !== undefined
}

export const isAccounting = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ACCOUNTING) !== undefined
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

export const isRemeasurer = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.REMEASURER) !== undefined
}

export const isSupervisor = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.SUPERVISOR) !== undefined &&
    roles.find((role) => role === ROLES.ACCOUNT_MANAGER || role === ROLES.SERVICE_MANAGER || role === ROLES.ADMIN) === undefined
}

export const isFrontdesk = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.FRONTDESK) !== undefined
}
export const isFrontdeskAdmin = (roles: string[]): boolean => {
  return roles.find((role) => {
    const normalized = String(role ?? '')
      .trim()
      .toLowerCase()
      .replace(/[\s-]+/g, '_')
      .replace(/[^a-z0-9_]/g, '')

    if (normalized === ROLES.FRONTDESK_ADMIN) {
      return true
    }

    const compact = normalized.replace(/_/g, '')
    const hasFrontChunk = compact.includes('front') || compact.includes('fron')
    const hasDeskChunk = compact.includes('desk') || compact.includes('destk')
    const hasAdminChunk = compact.includes('admin')

    return hasFrontChunk && hasDeskChunk && hasAdminChunk
  }) !== undefined
}
export const isServiceManager = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.SERVICE_MANAGER) !== undefined
}
export const isService = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.SERVICE) !== undefined
}
export const isPaymentCoordinator = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.PAYMENT_COORDINATOR) !== undefined
}
export const isFrontdeskEsr = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.FRONTDESK_ESR) !== undefined
}
export const can = (permissions: string[], permission: string): boolean => {
  return permissions.find((p) => p === permission) !== undefined
}

export const getRoleName = (roles: string[]): string => {
  if (isAdmin(roles)) {
    return 'Admin'
  } else if (isAccountManager(roles)) {
    return 'Account Manager'
  } else if (isAccounting(roles)) {
    return 'Accounting'
  } else if (isInstaller(roles)) {
    return 'Installer'
  } else if (isRemeasurer(roles)) {
    return 'Remeasurer'
  } else if (isOwner(roles)) {
    return 'Owner'
  } else if (isOwnerAdmin(roles)) {
    return 'Owner Admin'
  } else if (isServiceManager(roles)) {
    return 'Service Manager'
  } else if (isSupervisor(roles)) {
    return 'Supervisor'
  } else if (isService(roles)) {
    return 'Service'
  } else if (isFrontdesk(roles)) {
    return 'Frontdesk'
  } else if (isPaymentCoordinator(roles)) {
    return 'Payment Coordinator'
  } else if (isFrontdeskAdmin(roles)) {
    return 'Frontdesk Admin'
  } else if (isFrontdeskEsr(roles)) {
    return 'Frontdesk Esr'
  } else {
    return 'User'
  }
}
