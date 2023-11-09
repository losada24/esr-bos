import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router } from '@inertiajs/react'
import { type PageProps, type Order, type Client } from '@/types'
import Panel from '@/Components/Panel'
import EditIcon from '@/Components/Icons/EditIcon'
import PrintIcon from '@/Components/Icons/PrintIcon'
import PlusIcon from '@/Components/Icons/PlusIcon'
import Dropdown from '@/Components/Dropdown'
import AngleIcon from '@/Components/Icons/AngleIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import PriceSummary from './PriceSummary'

export default function Create ({ auth, estimate }: PageProps & {
  clients: Client[]
  estimate: Order
}) {
  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Estimate ${estimate.name}`}
      >
          <Head title={`Estimate ${estimate.name}`} />
          <div className='grid gap-6 grid-cols-12'>
            <div className='col-span-4'>
              <Panel className='pb-0'>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Quote #</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">{estimate.id}</div>
                </div>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Client</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">{estimate.client?.name}</div>
                </div>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Project</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">
                    {estimate.project_name ? estimate.project_name : 'No Project'}
                  </div>
                </div>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Markup</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">
                    {estimate.markup} %
                  </div>
                </div>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Created At</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">
                    {estimate.created_at ? estimate.created_at.toString() : 'No Date'}
                  </div>
                </div>
                <div className="flex flex-col gap-y-2 border-t border-white-light dark:border-white/10 py-2">
                    <button onClick={() => { route('estimate.edit', estimate.id) }} className="btn btn-success w-full gap-2">
                      <EditIcon />
                      Edit Estimate
                    </button>
                    <button type="button" className="btn btn-info w-full gap-2">
                        <PrintIcon />
                        Print Estimate
                    </button>
                    <div className='dropdown'>
                      <Dropdown
                          placement='bottom-start'
                          btnClassName="btn btn-primary w-full gap-2 dropdown-toggle"
                          button={
                              <>
                                  <PlusIcon />
                                  Add Products
                                  <span>
                                    <AngleIcon />
                                  </span>
                              </>
                          }
                      >
                          <ul className="w-full">
                              <li>
                                  <button onClick={() => {
                                    router.get(route('fixed-windows.create', estimate.id))
                                  }}>Fixed Windows</button>
                              </li>
                              <li>
                                  <button type="button">Single Hunt</button>
                              </li>
                              <li>
                                  <button type="button">Horizontal Roller</button>
                              </li>
                          </ul>
                      </Dropdown>
                    </div>
                </div>
              </Panel>
            </div>
            <div className='col-span-8'>
              <div className='table-responsive'>
                <table className="w-full whitespace-nowrap">
                  <thead>
                    <tr className="font-bold text-left">
                      <th className="px-6 pt-5 pb-4">System</th>
                      <th className="px-6 pt-5 pb-4">Mark</th>
                      <th className="px-6 pt-5 pb-4 text-right">Qty</th>
                      <th className="px-6 pt-5 pb-4">Size</th>
                      <th className="px-6 pt-5 pb-4">Frame Color</th>
                      <th className="px-6 pt-5 pb-4">Glass</th>
                      <th className="px-6 pt-5 pb-4 text-right">Price</th>
                      <th className="px-6 pt-5 pb-4 text-right">Amount</th>
                      <th className="px-6 pt-5 pb-4 w-14">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {estimate.products?.map(({ id, system, line_item_name, qty, width, height, frame_color, glass_type, glass_color, low_e, privacy, unit_price, total_price }) => (
                        <tr
                          key={id}
                          className="hover:bg-gray-100 focus-within:bg-gray-100"
                        >
                          <td className="border-t px-6 py-4 align-top">
                            {system}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {line_item_name}
                          </td>
                          <td className="border-t px-6 py-4 align-top text-right">
                            {qty}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {width} x {height}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {frame_color}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            Glass Details
                          </td>
                          <td className="border-t px-6 py-4 align-top text-right">
                            ${unit_price}
                          </td>
                          <td className="border-t px-6 py-4 align-top text-right">
                            ${total_price}
                          </td>
                          <td className="border-t flex items-center px-6 py-4">
                              <button
                                onClick={() => { route('product.edit', id) }}
                              >
                                <EditIcon />
                              </button>
                              <button
                                onClick={() => { route('product.destroy', id) }}
                              >
                                <DeleteIcon />
                              </button>
                          </td>
                        </tr>
                    ))
                    }
                    {estimate.products?.length === 0 && (
                      <tr>
                        <td className="px-6 py-4 border-t" colSpan={9}>
                          No Products found.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
              <PriceSummary estimate={estimate} />
            </div>
          </div>
      </AuthenticatedLayout>
  )
}
