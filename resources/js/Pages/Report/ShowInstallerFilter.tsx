import { type SyntheticEvent } from 'react'
import { useForm, router } from '@inertiajs/react'
import TextInput from '@/Components/TextInput'
import PrimaryButton from '@/Components/PrimaryButton'
import InputLabel from '@/Components/InputLabel'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'

interface ShowInstallerFilterProps {
  id: string// El id del supervisor, debe ser un string
  statuses: string[] // Lista de estados
}

const ShowInstallerFilter: React.FC<ShowInstallerFilterProps> = ({ id, statuses }) => {
  
  const { data, setData } = useForm({
    status: '',
    name: '',
    start_date: '',
    end_date: '',
    payment_date: ''
  })

  const reset = () => {
    setData({
      status: '',
      name: '',
      start_date: '',
      end_date: '',
      payment_date: ''
    })

    // Recargar la página sin filtros
    router.get(route('report.show_installer', { id }), {
      status: '',
      name: '',
      start_date: '',
      end_date: '',
      payment_date: ''
    }, {
      replace: true,
      preserveState: true
    })
  }

  const submit = (e: SyntheticEvent) => {
    e.preventDefault()
    let currentRoute = route().current()
    // console.log(currentRoute)
    if (currentRoute === undefined) {
      currentRoute = 'report.show_installer'
    }
    // console.log(data)
    router.get(route(currentRoute, { id }), data, {
      // replace: true,
      // preserveState: true
    })
  }

  return (
    <form onSubmit={submit}>
      <div className="flex gap-3">
       {/* <div className="mb-3 w-64">
          <label htmlFor="name">Name</label>
          <TextInput
            id="name"
            name="name"
            value={data.name}
            className="form-input"
            onChange={(e) => { setData('name', e.target.value) }}
            type="text"
            placeholder="Search by Name"
          />
        </div> */}
        <div className="mb-3 w-64">
          <label htmlFor="status">Name</label>
          <TextInput
            id="name"
            name="name"
            value={data.name}
            className="form-input"
            onChange={(e) => { setData('name', e.target.value) }}
            type="text"
            placeholder="Search by Name"
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
        <div className='mb-3 w-64'>
          <label htmlFor='payment_date'>Payment Date</label>
          <Flatpickr
            options={{
              mode: 'single',
              dateFormat: 'Y-m-d',
              position: 'auto right'
            }}
            name="payment_date"
            value={data.payment_date}
            className="form-input"
            onChange={([date]) => {
              setData('payment_date', date.toISOString().slice(0, 10))
            }}
          />

        </div>
        <div className='mb-3 w-64'>
          <label htmlFor='start_date'>Start Date</label>
          <Flatpickr
            options={{
              mode: 'single',
              dateFormat: 'Y-m-d',
              position: 'auto right'
            }}
            name="start_date"
            value={data.start_date}
            className="form-input"
            onChange={([date]) => {
              setData('start_date', date.toISOString().slice(0, 10))
            }}
          />

        </div>
        <div className='mb-3 w-64'>
          <label htmlFor='end_date' >End Date</label>
          <Flatpickr
            options={{
              mode: 'single',
              dateFormat: 'Y-m-d',
              position: 'auto right'
            }}
            name="end_date"
            value={data.end_date}
            className="form-input"
            onChange={([date]) => {
              setData('end_date', date.toISOString().slice(0, 10))
            }}
          />
        </div>
        <div className="flex items-end justify-between w-44 pb-3">
          <PrimaryButton className="btn btn-primary">Filter</PrimaryButton>
          <button type="button" onClick={reset} className="btn btn-outline-primary ml-2">
            Reset
          </button>
        </div>
      </div>
    </form>
  )
}

export default ShowInstallerFilter
