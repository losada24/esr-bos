import { PERSMISSIONS, PERSMISSION_ORDER_ADD_PRODUCTS } from '@/Utils/constants'
import Checkbox from '@/Components/Checkbox'
import InputLabel from '@/Components/InputLabel'

const Permissions = ({ selectedPermissions = [], handleUpdateUserPermissions }) => {

  const handleChange = (event) => {
    handleUpdateUserPermissions(event.target.value, event.target.checked)
  }

  return (
    <>
      <table className="w-full whitespace-nowrap">
        <thead>
          <tr className="font-bold text-left">
            <th className="pt-5 pb-4">Module</th>
            <th className="pt-5 pb-4">List</th>
            <th className="pt-5 pb-4">Create</th>
            <th className="pt-5 pb-4">Update</th>
            <th className="pt-5 pb-4">Delete</th>
            <th className="pt-5 pb-4">Details</th>
          </tr>
        </thead>
        <tbody>
          {PERSMISSIONS.map((permission, index) => {
            return (
              <tr key={`permissions_${index}`} className="hover:bg-gray-100 focus-within:bg-gray-100">
                <td className="border-t px-3 py-4 align-text-top">{permission.module}</td>
                <td className="border-t px-3 py-4 align-text-top">
                  <Checkbox 
                    id={`list_${index}`}
                    name={`list_${index}`}
                    value={permission.permissions.list}
                    handleChange={(e) => handleChange(e)}
                    isChecked={selectedPermissions.find(selectedPermission => selectedPermission === permission.permissions.list)}
                  />
                </td>
                <td className="border-t px-3 py-4 align-text-top">
                  <Checkbox 
                    id={`create_${index}`}
                    name={`create_${index}`}
                    value={permission.permissions.create}
                    handleChange={(e) => handleChange(e)}
                    isChecked={selectedPermissions.find(selectedPermission => selectedPermission === permission.permissions.create)}
                  />
                </td>
                <td className="border-t px-3 py-4 align-text-top">
                  <Checkbox 
                    id={`update_${index}`}
                    name={`update_${index}`}
                    value={permission.permissions.update}
                    handleChange={(e) => handleChange(e)}
                    isChecked={selectedPermissions.find(selectedPermission => selectedPermission === permission.permissions.update)}
                  />
                </td>
                <td className="border-t px-3 py-4 align-text-top">
                  <Checkbox 
                    id={`delete_${index}`}
                    name={`delete_${index}`}
                    value={permission.permissions.delete}
                    handleChange={(e) => handleChange(e)}
                    isChecked={selectedPermissions.find(selectedPermission => selectedPermission === permission.permissions.delete)}
                  />
                </td>
                <td className="border-t px-3 py-4 align-text-top">
                  <Checkbox 
                    id={`details_${index}`}
                    name={`details_${index}`}
                    value={permission.permissions.details}
                    handleChange={(e) => handleChange(e)}
                    isChecked={selectedPermissions.find(selectedPermission => selectedPermission === permission.permissions.details)}
                  />
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
      <InputLabel forInput="name" value="Special Permissions" />
      <div className="block mb-3">
          <label className="flex items-center">
              <Checkbox 
                id={PERSMISSION_ORDER_ADD_PRODUCTS}
                name="add_products_to_order"
                value={PERSMISSION_ORDER_ADD_PRODUCTS}
                handleChange={(e) => handleChange(e)}
                isChecked={selectedPermissions.find(selectedPermission => selectedPermission === PERSMISSION_ORDER_ADD_PRODUCTS)}
              />
              <span className="ml-2 text-sm text-gray-600">Add products to order</span>
          </label>
      </div>
    </>
  )
}

export default Permissions
