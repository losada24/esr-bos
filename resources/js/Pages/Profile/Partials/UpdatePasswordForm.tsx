import { useRef, type FormEventHandler } from 'react'
import InputError from '@/Components/InputError'
import InputLabel from '@/Components/InputLabel'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import { useForm } from '@inertiajs/react'
import { Transition } from '@headlessui/react'

export default function UpdatePasswordForm ({
  className = ''
}: {
  className?: string
}) {
  const passwordInput = useRef<HTMLInputElement>()
  const currentPasswordInput = useRef<HTMLInputElement>()

  const { data, setData, errors, put, reset, processing, recentlySuccessful } =
    useForm({
      current_password: '',
      password: '',
      password_confirmation: ''
    })

  const updatePassword: FormEventHandler = e => {
    e.preventDefault()

    put(route('password.update'), {
      preserveScroll: true,
      onSuccess: () => { reset() },
      onError: errors => {
        if (errors.password !== null) {
          reset('password', 'password_confirmation')
          passwordInput.current?.focus()
        }

        if (errors.current_password !== null) {
          reset('current_password')
          currentPasswordInput.current?.focus()
        }
      }
    })
  }

  return (
    <section className={className}>
      <header>
        <h2 className="text-lg font-medium text-gray-900">Update Password</h2>

        <p className="mt-1 text-sm text-gray-600">
          Ensure your account is using a long, random password to stay secure.
        </p>
      </header>

      <form onSubmit={updatePassword} className="mt-6 space-y-6">
        <div>
          <label htmlFor='current_password'>Current Password</label>

          <TextInput
            id="current_password"
            ref={currentPasswordInput}
            value={data.current_password}
            onChange={e => { setData('current_password', e.target.value) }}
            type="password"
            className="form-input"
            autoComplete="current-password"
          />

          <InputError message={errors.current_password} className="mt-2" />
        </div>

        <div>
        <label htmlFor='password'>New Password</label>

          <TextInput
            id="password"
            ref={passwordInput}
            value={data.password}
            onChange={e => { setData('password', e.target.value) }}
            type="password"
            className="form-input"
            autoComplete="new-password"
          />

          <InputError message={errors.password} className="mt-2" />
        </div>

        <div>
        <label htmlFor='password_confirmation'>Confirm Password</label>

          <TextInput
            id="password_confirmation"
            value={data.password_confirmation}
            onChange={e => { setData('password_confirmation', e.target.value) }}
            type="password"
            className="form-input"
            autoComplete="new-password"
          />

          <InputError message={errors.password_confirmation} className="mt-2" />
        </div>

        <div className="flex items-center gap-4">
          <PrimaryButton className='btn btn-primary' disabled={processing}>Save</PrimaryButton>

          <Transition
            show={recentlySuccessful}
            enter="transition ease-in-out"
            enterFrom="opacity-0"
            leave="transition ease-in-out"
            leaveTo="opacity-0"
          >
            <p className="text-sm text-gray-600">Saved.</p>
          </Transition>
        </div>
      </form>
    </section>
  )
}
