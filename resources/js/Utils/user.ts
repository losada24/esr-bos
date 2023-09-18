import { ROLES } from './constants'

export const isAdmin = (roles: string[]): boolean => {
  return roles.find((role) => role === ROLES.ADMIN) !== undefined
}

export const can = (permissions: string[], permission: string): boolean => {
  return permissions.find((p) => p === permission) !== undefined
}
