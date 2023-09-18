import { isAdmin, can } from '@/Utils/user'
import { type PropsWithChildren } from 'react'

type CanProps = PropsWithChildren<{
  auth: {
    user: {
      roles: string[]
      permissions: string[]
    }
  }
  permission: string
}>

const Can = ({ children, auth, permission }: CanProps) => {
  const { roles, permissions } = auth.user

  if (!isAdmin(roles) && !can(permissions, permission)) {
    return null
  }

  return children
}

export default Can
