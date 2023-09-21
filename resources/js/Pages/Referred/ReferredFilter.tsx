import { useState, useEffect } from 'react'
import { usePage, useForm, router } from '@inertiajs/react'
import Autocomplete from '@/Components/Autocomplete'
import TextInput from '@/Components/TextInput'
import SelectInput from '@/Components/SelectInput'
import PrimaryButton from '@/Components/PrimaryButton'
import { STATUS } from '@/Utils/constants'
import { isAdmin } from '@/Utils/user'
import { type Role } from '@/types'

const ReferredFilter = () => {
  const { users: userList, auth } = usePage().props
  const [selectedUser, setSelectedUser] = useState({
    label: null,
    value: null
  })
  const [users, setUsers] = useState([])
  const STATUS_OPTIONS = STATUS.map((status) => {
    return {
      label: status.label,
      value: status.id
    }
  })

  const { data, setData, processing } = useForm({
    text: '',
    status: '',
    user_id: ''
  })

  const reset = () => {
    const emptyUser = { label: null, value: null }
    setSelectedUser({ ...emptyUser })

    setData({
      text: '',
      status: '',
      user_id: ''
    })

    router.get(route(route().current()), {
      text: '',
      status: '',
      user_id: ''
    }, {
      replace: true,
      preserveState: false
    })
  }

  const onHandleChange = (event) => {
    setData(event.target.name, event.target.type === 'checkbox' ? event.target.checked : event.target.value)
  }

  const handleStatusChange = (value: string) => {
    setData('status', value)
  }

  const handleSearch = (item) => {
    router.get('/referred', { user_term: item }, { preserveState: true, preserveScroll: true, replace: true })
  }

  const onAutocompleteChange = (item) => {
    const selectedUser = { label: item.label, value: item.value }
    setSelectedUser({ ...selectedUser })
    setData(item.name, item.value)
  }

  const submit = (e) => {
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
    const usersAutocomplete = userList.data.map(data => {
      return {
        label: data.name,
        value: data.id
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
            onChange={onHandleChange}
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
            isMultiple={false}
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
