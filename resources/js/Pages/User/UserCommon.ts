import * as Yup from 'yup'
import { type PageProps, type Role, type Company } from '@/types'

export const userSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  password: Yup.string().required('Password is required'),
  password_confirmation: Yup.string().oneOf([Yup.ref('password'), null], 'Passwords must match').required('Password confirmation is required'),
  role: Yup.number().required('Role is required'),
  markup: Yup.number().nullable().integer().min(0).max(100)
})

export const userUpdateSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  password: Yup.string().nullable(),
  password_confirmation: Yup.string().oneOf([Yup.ref('password'), null], 'Passwords must match').nullable(),
  role: Yup.number().required('Role is required'),
  markup: Yup.number().nullable().integer().min(0).max(100)
})

export interface User {
  id?: number
  name: string
  email: string
  password: string
  password_confirmation: string
  role: number
  company_id: number
  markup?: number
}

interface UserResource {
  data: User
}

export type UserPageProps = PageProps & {
  roles: Role[]
  companies: Company[]
  user?: UserResource
}
