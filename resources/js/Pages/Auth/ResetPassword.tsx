import { useEffect, type FormEventHandler } from 'react'
import GuestLayout from '@/Layouts/GuestLayout'
import InputError from '@/Components/InputError'
import InputLabel from '@/Components/InputLabel'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import { Head, useForm } from '@inertiajs/react'

import featuredImage from '../../../assets/images/auth/reset-password.svg'
import EmailIcon from '@/Components/Icons/Auth/EmailIcon'
import PasswordIcon from '@/Components/Icons/Auth/PasswordIcon'

export default function ResetPassword ({
  token,
  email
}: {
  token: string
  email: string
}) {
  const { data, setData, post, processing, errors, reset } = useForm({
    token,
    email,
    password: '',
    password_confirmation: ''
  })

  useEffect(() => {
    return () => {
      reset('password', 'password_confirmation')
    }
  }, [])

  const submit: FormEventHandler = e => {
    e.preventDefault()

    post(route('password.store'))
  }

  return (
    <GuestLayout
      featuredImage={featuredImage}
      formTitle='Reset Password'
      formDescription=''
    >
      <Head title="Reset Password" />

      <form onSubmit={submit}>
        <div>
          <InputLabel htmlFor="email" value="Email" />
          <div className='relative text-white-dark'>
            <TextInput
              id="email"
              type="email"
              name="email"
              value={data.email}
              className="form-input ps-10 placeholder:text-white-dark"
              autoComplete="username"
              placeholder='Enter Email'
              onChange={e => { setData('email', e.target.value) }}
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <EmailIcon />
            </span>
          </div>
          <InputError message={errors.email} className="mt-2" />
        </div>

        <div className="mt-4">
          <InputLabel htmlFor="password" value="Password" />
          <div className="relative text-white-dark">
            <TextInput
              id="password"
              type="password"
              name="password"
              value={data.password}
              className="form-input ps-10 placeholder:text-white-dark"
              autoComplete="new-password"
              isFocused={true}
              placeholder='Enter Password'
              onChange={e => { setData('password', e.target.value) }}
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <PasswordIcon />
            </span>
          </div>

          <InputError message={errors.password} className="mt-2" />
        </div>

        <div className="mt-4">
          <InputLabel
            htmlFor="password_confirmation"
            value="Confirm Password"
          />
          <div className="relative text-white-dark">
            <TextInput
              type="password"
              name="password_confirmation"
              value={data.password_confirmation}
              className="form-input ps-10 placeholder:text-white-dark"
              autoComplete="new-password"
              placeholder='Password Confirmation'
              onChange={e => { setData('password_confirmation', e.target.value) }}
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <PasswordIcon />
            </span>
          </div>

          <InputError message={errors.password_confirmation} className="mt-2" />
        </div>

        <PrimaryButton
          className="btn btn-gradient !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]"
          disabled={processing}
        >
          Reset Password
        </PrimaryButton>
      </form>
    </GuestLayout>
  )
}
