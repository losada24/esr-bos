import { type SyntheticEvent } from 'react'
import { useForm, router } from '@inertiajs/react'
import TextInput from '@/Components/TextInput'
import PrimaryButton from '@/Components/PrimaryButton'
import { type Status } from '@/types'

const OrderFilter = ({ statuses }: { statuses: Status[] }) => {
  const { data, setData } = useForm({
    text: '',
    status: ''
  })

  const reset = () => {
    setData({
      text: '',
      status: ''
    })

    router.get(route('order.index'), {
      text: ''
    }, {
      replace: true,
      preserveState: false
    })
  }

  const submit = (e: SyntheticEvent) => {
    e.preventDefault()
    let currentRoute = route().current()
    if (currentRoute === undefined) {
      currentRoute = 'order.index'
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
          <label htmlFor="text">Search</label>
          <TextInput
            id="text"
            name="text"
            value={data.text}
            className="form-input"
            onChange={(e) => {
              setData('text', e.target.value)
            }}
            type='text'
            placeholder='Search by Quote, Name or Project'
          />
        </div>
        <div className='mb-3 w-64'>
          <label htmlFor="role">Status</label>
          <select
            id="status"
            name="status"
            className="form-select"
            autoComplete="status"
            placeholder='Status'
            onChange={(e) => {
              setData('status', e.target.value)
            }}
          >
            <option value="">Select Status</option>
            {statuses.map((status, index) => (
              <option key={index} value={status.value}>{status.label.toUpperCase()}</option>
            ))}
          </select>
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

export default OrderFilter
