import { useEffect, type FormEventHandler } from 'react'
import GuestLayout from '@/Layouts/GuestLayout'
import InputError from '@/Components/InputError'
import InputLabel from '@/Components/InputLabel'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import { Head, Link, useForm } from '@inertiajs/react'

import featuredImage from '../../../assets/images/auth/register.svg'
import SocialNetworkSignUp from './SocialNetworkSignUp'
import UserIcon from '@/Components/Icons/Auth/UserIcon'
import EmailIcon from '@/Components/Icons/Auth/EmailIcon'
import PasswordIcon from '@/Components/Icons/Auth/PasswordIcon'

export default function Register () {
  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    email: '',
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

    post(route('register'))
  }

  return (
    <GuestLayout
      featuredImage={featuredImage}
      formTitle='Sign Up'
      formDescription='Enter your email and password to register'
    >
      <Head title="Register" />
      <form className='space-y-5 dark:text-white' onSubmit={submit}>
        <div>
          <InputLabel htmlFor="name" value="Name" />
          <div className="relative text-white-dark">
            <TextInput
              id="name"
              name="name"
              value={data.name}
              className="form-input ps-10 placeholder:text-white-dark"
              autoComplete="name"
              isFocused={true}
              onChange={e => { setData('name', e.target.value) }}
              placeholder='Enter Name'
              required
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <UserIcon />
            </span>
          </div>
          <InputError message={errors.name} className="mt-2" />
        </div>

        <div className="mt-4">
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
              required
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
              onChange={e => { setData('password', e.target.value) }}
              placeholder='Enter Password'
              required
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
          <div className='relative text-white-dark'>
            <TextInput
              id="password_confirmation"
              type="password"
              name="password_confirmation"
              value={data.password_confirmation}
              className="form-input ps-10 placeholder:text-white-dark"
              autoComplete="new-password"
              onChange={e => { setData('password_confirmation', e.target.value) }}
              placeholder='Password Confirmation'
              required
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <PasswordIcon />
            </span>
          </div>

          <InputError message={errors.password_confirmation} className="mt-2" />
        </div>
        <div className="flex items-center justify-end mt-4">
          <Link
            href={route('login')}
            className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Already registered?
          </Link>

          <PrimaryButton
            className="btn btn-gradient border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)] ml-4"
            disabled={processing}
          >
            Register
          </PrimaryButton>
        </div>
      </form>
      <SocialNetworkSignUp isLogin={false} />
    </GuestLayout>
  )
}
