import React, { useState } from 'react'
import TextInput from './TextInput'
import CloseIcon from './Icons/CloseIcon'

const Autocomplete = ({ id, name, value, data, handleChange, handleSearch }) => {
  const [isOpen, setIsOpen] = useState(false)
  const [selectedItem, setSelectedItem] = useState(value)
  const handleSelect = (item) => {
    if (item.value === selectedItem.value) {
      const nullSelection = {
        label: null, value: null
      }
      setSelectedItem({ ...nullSelection })
    } else {
      setSelectedItem(item)
      handleChange({ ...item, name })
      setIsOpen(false)
    }
  }

  return (
    <div className="relative">
      <div
        className="form-input h-[38px]"
        onClick={() => { setIsOpen(!isOpen) }}
      >
        {selectedItem !== null && (
          <span>{selectedItem.label}</span>
        )}
        {isOpen && (
          <button onClick={() => { setIsOpen(!isOpen) }} className="text-white-dark hover:text-dark absolute top-2 right-2">
            <CloseIcon />
          </button>
        )}
      </div>
      {isOpen && (
        <ul className="absolute z-10 bg-white w-full h-40 overflow-scroll border rounded-md shadow-sm border-gray-300 mt-3 px-3">
          <li>
            <TextInput
              id={id}
              name={name}
              className='form-input my-3'
              onChange={(e) => handleSearch(e.target.value)}
            />
          </li>
          {data.map(item => {
            return <li
              key={item.value}
              className={`border-b p-3 cursor-pointer hover:bg-gray-300 ${item.value === selectedItem.value && 'bg-gray-400'}`}
              onClick={() => { handleSelect(item) }}>{item.label}</li>
          })}
        </ul>
      )}
    </div>
  )
}

export default Autocomplete
