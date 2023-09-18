import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, Link } from '@inertiajs/react'
import InputLabel from '@/Components/InputLabel'
import PrimaryButton from '@/Components/PrimaryButton'
import TextInput from '@/Components/TextInput'
import InputError from '@/Components/InputError'
import Checkbox from '@/Components/Checkbox'
import Permissions from './Permissions'

export default function Create({ auth, defaultErrors }) {
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    permissions: [],
    is_admin: false
  })

  const onHandleChange = (event) => {
    setData(event.target.name, event.target.type === 'checkbox' ? event.target.checked : event.target.value);
  }

  const handleUpdateUserPermissions = (permission, isChecked) => {
    if (isChecked) {
      setData('permissions', [...data.permissions, permission])
    } else {
      setData('permissions', data.permissions.filter(selectedPermission => selectedPermission !== permission))
    }
  }

  const submit = (e) => {
    e.preventDefault()
    post(route('user.store'))
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          errors={defaultErrors}
          header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Create User</h2>}
      >
          <Head title="Create" />

          <div className="py-3">
              <div className="container mx-auto sm:px-6 lg:px-8">
                  <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <form onSubmit={submit} >
                      <div className='mb-3'>
                        <InputLabel forInput="name" value="Name" />

                        <TextInput
                          id="name"
                          name="name"
                          value={data.name}
                          className="mt-1 block w-full"
                          autoComplete="name"
                          isFocused={true}
                          handleChange={onHandleChange}
                        />

                        <InputError message={errors.name} className="mt-2" />
                      </div>
                      <div className='mb-3'>
                        <InputLabel forInput="name" value="Email" />

                        <TextInput
                          id="email"
                          name="email"
                          value={data.email}
                          type="email"
                          className="mt-1 block w-full"
                          isFocused={false}
                          handleChange={onHandleChange}
                        />

                        <InputError message={errors.email} className="mt-2" />
                      </div>
                      <div className='mb-3'>
                        <InputLabel forInput="name" value="Password" />

                        <TextInput
                          id="password"
                          name="password"
                          value={data.password}
                          type="password"
                          className="mt-1 block w-full"
                          isFocused={false}
                          handleChange={onHandleChange}
                        />

                        <InputError message={errors.password} className="mt-2" />
                      </div>
                      <div className='mb-3'>
                        <InputLabel forInput="name" value="Password Confirmation" />

                        <TextInput
                          id="password_confirmation"
                          name="password_confirmation"
                          value={data.password_confirmation}
                          type="password"
                          className="mt-1 block w-full"
                          isFocused={false}
                          handleChange={onHandleChange}
                        />

                        <InputError message={errors.password_confirmation} className="mt-2" />
                      </div>
                      <div className="block mb-3">
                          <label className="flex items-center">
                              <Checkbox 
                                name="is_admin"
                                value="is_admin" 
                                handleChange={onHandleChange}
                                isChecked={data.is_admin}
                              />
                              <span className="ml-2 text-sm text-gray-600">Is Admin</span>
                          </label>
                      </div>
                      {!data.is_admin && (
                        <div className='mb-3'>
                          <InputLabel forInput="name" value="Permissions" />

                          <Permissions
                            selectedPermissions={data.permissions}
                            handleUpdateUserPermissions={(permission, isChecked) => handleUpdateUserPermissions(permission, isChecked)}
                          />

                          <InputError message={errors.permissions} className="mt-2" />
                        </div>
                      )}

                      <div className="flex items-center justify-between mt-4">
                        <Link href={route('user.index')}>Cancel</Link>
                        <PrimaryButton className="ml-4" processing={processing}>
                          Create
                        </PrimaryButton>
                      </div>
                    </form>
                  </div>
              </div>
          </div>
      </AuthenticatedLayout>
  );
}
