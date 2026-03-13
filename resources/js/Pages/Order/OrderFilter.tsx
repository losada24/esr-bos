import { type SyntheticEvent } from 'react'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { useForm, router } from '@inertiajs/react'
import TextInput from '@/Components/TextInput'
import PrimaryButton from '@/Components/PrimaryButton'

const OrderFilter = ({ statuses }: { statuses: string[] }) => {
  const { data, setData } = useForm({
    text: '',
    status: '',
    is_supply: false,
    dates: [] as Date[] | []
  })

  const reset = () => {
    setData({
      text: '',
      status: '',
      is_supply: false,
      dates: []
    })

    router.get(route('order.index'), {
      text: '',
      status: '',
      is_supply: false,
      dates: []
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
            placeholder='Search by Order Number, Name or Address'
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
              <option key={index} value={status}>{status}</option>
            ))}
          </select>
        </div>
        <div className='mb-3 w-36 flex items-end'>
          <label htmlFor="is_supply" className='inline-flex items-center gap-2 cursor-pointer pb-2'>
            <input
              id="is_supply"
              name="is_supply"
              type="checkbox"
              checked={data.is_supply}
              onChange={(e) => {
                setData('is_supply', e.target.checked)
              }}
            />
            <span>SUPPLY</span>
          </label>
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
