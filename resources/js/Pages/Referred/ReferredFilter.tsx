import { useState, useEffect, type SyntheticEvent } from 'react'
import { useForm, router } from '@inertiajs/react'
import Autocomplete, { type AutocompleteValue } from '@/Components/Autocomplete'
import TextInput from '@/Components/TextInput'
import SelectInput from '@/Components/SelectInput'
import PrimaryButton from '@/Components/PrimaryButton'
import { STATUS } from '@/Utils/constants'
import { isAdmin } from '@/Utils/user'
import { type Role, type Auth, type ListUsersItem } from '@/types'

const ReferredFilter = ({ userList, auth }: { userList: { data: ListUsersItem[] }, auth: Auth }) => {
  const [selectedUser, setSelectedUser] = useState<AutocompleteValue>({
    label: '',
    value: ''
  })
  const [users, setUsers] = useState<AutocompleteValue[]>([])
  const STATUS_OPTIONS = STATUS.map((status) => {
    return {
      label: status.label,
      value: status.id
    }
  })

  const { data, setData } = useForm({
    text: '',
    status: '',
    user_id: ''
  })

  const reset = () => {
    const emptyUser: AutocompleteValue = { label: '', value: '' }
    setSelectedUser({ ...emptyUser })

    setData({
      text: '',
      status: '',
      user_id: ''
    })

    router.get(route('referred.index'), {
      text: '',
      status: '',
      user_id: ''
    }, {
      replace: true,
      preserveState: false
    })
  }

  const handleStatusChange = (value: string) => {
    setData('status', value)
  }

  const handleSearch = (item: string) => {
    router.get('/referred', { user_term: item }, { preserveState: true, preserveScroll: true, replace: true })
  }

  const onAutocompleteChange = (item: AutocompleteValue) => {
    const selectedUser = { label: item.label, value: item.value }
    setSelectedUser({ ...selectedUser })
    setData('user_id', item.value)
  }

  const submit = (e: SyntheticEvent) => {
    e.preventDefault()
    let currentRoute = route().current()
    if (currentRoute === undefined) {
      currentRoute = 'referred.index'
    }

    router.get(route(currentRoute), data, {
      replace: true,
      preserveState: true
    })
  }

  useEffect(() => {
    const usersAutocomplete: AutocompleteValue[] = userList.data.map(data => {
      return {
        label: data.name,
        value: data.id.toString()
      }
    })

    setUsers(usersAutocomplete)
  }, [userList])

  return (
    <form onSubmit={submit}>
      <div className='flex flex-row gap-3 grow'>
        <div className='mb-3 w-64'>
        <label htmlFor="role">Search</label>

          <TextInput
            id="text"
            name="text"
            value={data.text}
            className="form-input"
            onChange={(e) => {
              setData('text', e.target.value)
            }}
            type='text'
            placeholder='Search by Email, Name or Phone'
          />
        </div>
        <div className='mb-3 w-40'>
        <label htmlFor="status">Status</label>

          <SelectInput
            id='status'
            name='status'
            value={data.status}
            className="form-select"
            options={[{ label: '', value: '' }, ...STATUS_OPTIONS]}
            handleChange={(e) => { handleStatusChange(e.target.value) }}
          />
        </div>
        {isAdmin(auth.user.roles.map((role: Role) => role.name)) && (
          <div className='mb-3 w-56'>
            <label htmlFor="user_id">User</label>

            <Autocomplete
              id='user_id'
              name='user_id'
              value={selectedUser}
              handleChange={onAutocompleteChange}
              data={users}
              handleSearch={(value) => { handleSearch(value) }}
            />
          </div>
        )}
        <div className="flex items-end justify-between w-44 pb-3">
          <PrimaryButton className="btn btn-primary">
            Filter
          </PrimaryButton>
          <button
            onClick={reset}
            className="btn btn-outline-primary"
            type="button"
          >
            Reset
          </button>
        </div>
      </div>
    </form>
  )
}

export default ReferredFilter
