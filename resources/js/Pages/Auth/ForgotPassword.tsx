import GuestLayout from '@/Layouts/GuestLayout'
import InputError from '@/Components/InputError'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import { Head, useForm } from '@inertiajs/react'
import { type FormEventHandler } from 'react'

import featuredImage from '../../../assets/images/auth/reset-password.svg'
import EmailIcon from '@/Components/Icons/Auth/EmailIcon'

export default function ForgotPassword ({ status }: { status?: string }) {
  const { data, setData, post, processing, errors } = useForm({
    email: ''
  })

  const submit: FormEventHandler = e => {
    e.preventDefault()

    post(route('password.email'))
  }

  return (
    <GuestLayout
      featuredImage={featuredImage}
      formTitle='Password reset'
      formDescription='Forgot your password? No problem. Just let us know your email address
      and we will email you a password reset link that will allow you to
      choose a new one.'
    >
      <Head title="Forgot Password" />

      {status !== undefined && (
        <div className="mb-4 font-medium text-sm text-green-600">{status}</div>
      )}

      <form onSubmit={submit}>
        <div>
          <label htmlFor="Email">Email</label>
          <div className='relative text-white-dark'>
            <TextInput
              id="email"
              type="email"
              name="email"
              value={data.email}
              className="form-input ps-10 placeholder:text-white-dark"
              isFocused={true}
              placeholder='Enter Email'
              onChange={e => { setData('email', e.target.value) }}
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <EmailIcon />
            </span>
          </div>
          <InputError message={errors.email} className="mt-2" />
        </div>
        <PrimaryButton
          className="btn btn-gradient !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]"
          disabled={processing}
        >
          Email Password Reset Link
        </PrimaryButton>
      </form>
    </GuestLayout>
  )
}
