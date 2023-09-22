import { useEffect, type FormEventHandler } from 'react'
import Checkbox from '@/Components/Checkbox'
import GuestLayout from '@/Layouts/GuestLayout'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import { Head, Link, useForm } from '@inertiajs/react'

import featuredImage from '../../../assets/images/auth/login.svg'
import EmailIcon from '@/Components/Icons/Auth/EmailIcon'
import PasswordIcon from '@/Components/Icons/Auth/PasswordIcon'

export default function Login ({
  canResetPassword
}: {
  status?: string
  canResetPassword: boolean
}) {
  const { data, setData, post, processing, errors, reset } = useForm({
    email: '',
    password: '',
    remember: false
  })

  useEffect(() => {
    return () => {
      reset('password')
    }
  }, [])

  const submit: FormEventHandler = e => {
    e.preventDefault()

    post(route('login'))
  }

  return (
    <GuestLayout
      featuredImage={featuredImage}
      formTitle='Sign in'
      formDescription='Enter your email and password to login'
    >
      <Head title="Log in" />
      <form className="space-y-5 dark:text-white" onSubmit={submit}>
        <div>
          <label htmlFor="Email">Email</label>
          <div className="relative text-white-dark">
            <TextInput
              id="Email"
              type="email"
              name="email"
              value={data.email}
              placeholder="Enter Email"
              className="form-input ps-10 placeholder:text-white-dark"
              autoComplete="username"
              isFocused={true}
              onChange={e => { setData('email', e.target.value) }}
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <EmailIcon />
            </span>
          </div>

          <InputError message={errors.email} className="mt-2" />
        </div>
        <div>
          <label htmlFor="Password">Password</label>
          <div className="relative text-white-dark">
            <TextInput
              id="password"
              type="password"
              name="password"
              placeholder="Enter Password"
              value={data.password}
              className="form-input ps-10 placeholder:text-white-dark"
              autoComplete="current-password"
              onChange={e => { setData('password', e.target.value) }}
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <PasswordIcon />
            </span>
          </div>
          <InputError message={errors.password} className="mt-2" />
        </div>
        <div>
          <label className="flex cursor-pointer items-center">
            <Checkbox
              name="remember"
              checked={data.remember}
              className="form-checkbox bg-white dark:bg-black"
              onChange={e => { setData('remember', e.target.checked) }}
            />
            <span className="text-white-dark">Remember me</span>
          </label>
        </div>
        <div className="flex items-center justify-end mt-4">
          {canResetPassword && (
            <Link
              href={route('password.request')}
              className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Forgot your password?
            </Link>
          )}

          <PrimaryButton
            className="btn btn-gradient border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)] ml-4"
            disabled={processing}
          >
            Log in
          </PrimaryButton>
        </div>
      </form>
    </GuestLayout>
  )
}
