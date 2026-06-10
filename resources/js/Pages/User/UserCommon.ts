import * as Yup from 'yup'
import { type PageProps, type Role, type OptionType } from '@/types'
import { isValidFileSize, isValidFileType } from '@/Utils/fileValidation'
import { type MultiValue } from 'react-select'


export const userSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  password: Yup.string().required('Password is required'),
  password_confirmation: Yup.string().oneOf([Yup.ref('password'), null], 'Passwords must match').required('Password confirmation is required'),
  status: Yup.string().required('Status is required'),
  // role: Yup.number().required('Role is required'),
  // role: Yup.array().of(Yup.number()).min(1, 'At least one role is required').required('Role is required'), // Cambié a array
  featured_image: Yup.mixed()
    /* .when('id', {
      is: (id: number) => id === 0,
      then: Yup.mixed().required('Featured image is required'),
      otherwise: Yup.mixed().nullable()
    }) */
    .nullable()
    .test('is-valid-type', 'Not a valid image type', value => isValidFileType(value?.name, 'image'))
    .test('is-valid-size', 'Max allowed size is 500KB', value => isValidFileSize(value?.size ?? 0))

})

export const userUpdateSchema = Yup.object({
  name: Yup.string().required('Name is required'),
  email: Yup.string().email('Invalid email address').required('Email is required'),
  password: Yup.string().nullable(),
  password_confirmation: Yup.string().oneOf([Yup.ref('password'), null], 'Passwords must match').nullable(),
  status: Yup.string().required('Status is required'),
  // role: Yup.number().required('Role is required'),
  // role: Yup.array().of(Yup.number()).min(1, 'At least one role is required').required('Role is required'), // Cambié a array
  markup: Yup.number().nullable().integer().min(0).max(100),
  featured_image: Yup.mixed()
    .when('id', {
      is: (id: number) => id === 0,
      then: Yup.mixed().required('Featured image is required'),
      otherwise: Yup.mixed().nullable()
    })
    .test('is-valid-type', 'Not a valid image type', value => isValidFileType(value?.name, 'image'))
    .test('is-valid-size', 'Max allowed size is 500KB', value => isValidFileSize(value?.size ?? 0))
})

export interface User {
  id?: number
  name: string
  email: string
  password: string
  password_confirmation: string
  role: Role[]
  delegated_owner_ids?: number[]
  featured_image?: string
  phone: string
  status: string
}

interface UserResource {
  data: User
}

export type UserPageProps = PageProps & {
  roles: Role[]
  statuses: OptionType[]
  owner_options: Array<{ id: number, name: string }>
  // companies: Company[]
  user?: UserResource
}

export type UserFormValues = Omit<User, 'role' | 'delegated_owner_ids'> & {
  role: MultiValue <OptionType>
  delegated_owner_ids: MultiValue<OptionType>
}
