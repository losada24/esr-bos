import GuestLayout from '@/Layouts/GuestLayout'
import PrimaryButton from '@/Components/PrimaryButton'
import { Head, Link, useForm } from '@inertiajs/react'
import { type FormEventHandler } from 'react'

import featuredImage from '../../../assets/images/auth/email-verification.svg'

export default function VerifyEmail ({ status }: { status?: string }) {
  const { post, processing } = useForm({})

  const submit: FormEventHandler = e => {
    e.preventDefault()

    post(route('verification.send'))
  }

  return (
    <GuestLayout
      featuredImage={featuredImage}
      formTitle='Email Verification'
      formDescription='Thanks for signing up! Before getting started, could you verify your
      email address by clicking on the link we just emailed to you? If you
      didn&apos;t receive the email, we will gladly send you another.'
    >
      <Head title="Email Verification" />

      {status === 'verification-link-sent' && (
        <div className="mb-4 font-medium text-sm text-green-600">
          A new verification link has been sent to the email address you
          provided during registration.
        </div>
      )}

      <form onSubmit={submit}>
        <div className="flex items-center justify-end mt-4">
        <Link
            href={route('logout')}
            method="post"
            as="button"
            className="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Log Out
          </Link>
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
