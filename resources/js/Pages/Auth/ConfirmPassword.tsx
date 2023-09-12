import { useEffect, type FormEventHandler } from 'react'
import GuestLayout from '@/Layouts/GuestLayout'
import InputError from '@/Components/InputError'
import InputLabel from '@/Components/InputLabel'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import { Head, useForm } from '@inertiajs/react'

import featuredImage from '../../../assets/images/auth/reset-password.svg'
import PasswordIcon from '@/Components/Icons/Auth/PasswordIcon'

export default function ConfirmPassword () {
  const { data, setData, post, processing, errors, reset } = useForm({
    password: ''
  })

  useEffect(() => {
    return () => {
      reset('password')
    }
  }, [])

  const submit: FormEventHandler = e => {
    e.preventDefault()

    post(route('password.confirm'))
  }

  return (
    <GuestLayout
      featuredImage={featuredImage}
      formTitle='Confirm Password'
      formDescription='This is a secure area of the application. Please confirm your password
      before continuing.'
    >
      <Head title="Confirm Password" />

      <form onSubmit={submit}>
        <div>
          <InputLabel htmlFor="password" value="Password" />
          <div className="relative text-white-dark">
            <TextInput
              id="password"
              type="password"
              name="password"
              value={data.password}
              className="form-input ps-10 placeholder:text-white-dark"
              isFocused={true}
              placeholder="Enter Password"
              onChange={e => { setData('password', e.target.value) }}
            />
            <span className="absolute start-4 top-1/2 -translate-y-1/2">
              <PasswordIcon />
            </span>
          </div>
          <InputError message={errors.password} className="mt-2" />
        </div>
        <PrimaryButton
          className="btn btn-gradient !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]"
          disabled={processing}>
          Confirm
        </PrimaryButton>
      </form>
    </GuestLayout>
  )
}
