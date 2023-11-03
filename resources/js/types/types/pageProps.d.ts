import { type User } from '@/types/interfaces/user'
import { type Flash } from '@/types/interfaces/flash'

export type PageProps<
  T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
  auth: {
    user: User
  }
  flash: Flash
}
