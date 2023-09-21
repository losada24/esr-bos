import { useForm, router } from '@inertiajs/react'
import TextInput from '@/Components/TextInput'
import PrimaryButton from '@/Components/PrimaryButton'

const UserFilter = () => {
  const { data, setData, processing } = useForm({
    text: ''
  })

  const reset = () => {
    setData({
      text: ''
    })

    router.get(route(route().current()), {
      text: ''
    }, {
      replace: true,
      preserveState: false
    })
  }

  const onHandleChange = (event) => {
    setData(event.target.name, event.target.type === 'checkbox' ? event.target.checked : event.target.value)
  }

  const submit = (e) => {
    e.preventDefault()
    let currentRoute = route().current()
    if (currentRoute === undefined) {
      currentRoute = 'user.index'
    }

    router.get(route(currentRoute), data, {
      replace: true,
      preserveState: true
    })
  }

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
            placeholder='Search by Name or Email'
          />
        </div>
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

export default UserFilter
