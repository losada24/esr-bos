import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, router, Link } from '@inertiajs/react'
import { type PageProps, type Order, type Client, type Role, type Product } from '@/types'
import Panel from '@/Components/Panel'
import EditIcon from '@/Components/Icons/EditIcon'
import PlusIcon from '@/Components/Icons/PlusIcon'
import Dropdown from '@/Components/Dropdown'
import AngleIcon from '@/Components/Icons/AngleIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import PriceSummary from './PriceSummary'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { PRODUCT_SYSTEMS, ESTIMATE_STATUS, SUB_DEALER_ESTIMATE } from '@/Utils/constants'
import MoneyIcon from '@/Components/Icons/MoneyIcon'
import PrintEstimateButton from './PrintEstimateButton'
import { getNumberWithFraction } from '@/Utils/numbers'
import { isDealer, isSubDealer, isAdmin, isAccountManager } from '@/Utils/user'
import CopyIcon from '@/Components/Icons/CopyIcon'
import { formatPrice, getTotalPriceByRole, getUnitPriceByRole } from '@/Utils/price'

export default function Create ({ auth, estimate }: PageProps & {
  clients: Client[]
  estimate: Order
}) {
  const getUrlBySystem = (system: string, id: number) => {
    switch (system) {
      case PRODUCT_SYSTEMS.FIXED_WINDOWS:
        return route('fixed-windows.edit', id)
      case PRODUCT_SYSTEMS.SINGLE_HUNG:
        return route('single-hunt.edit', id)
      case PRODUCT_SYSTEMS.HORIZONTAL_ROLLER:
        return route('horizontal-roller.edit', id)
      default:
        return ''
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle={`Estimate ${estimate.name}`}
      >
          <Head title={`Estimate ${estimate.name}`} />
          <div className='grid gap-6 grid-cols-12'>
            <div className='col-span-2'>
              <Panel className='pb-0'>
                <div className='group relative flex items-center py-1.5'>
                  <div className="flex-1">Quote #</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">{createMarkWithLeadingZero(estimate.id, 6)}</div>
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
                  <div className="flex-1">External Purchase Id</div>
                  <div className="text-xs text-white-dark ltr:ml-auto rtl:mr-auto dark:text-gray-500">
                    {estimate.external_purchase_id ? estimate.external_purchase_id : 'No External Purchase Id'}
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
                  {(estimate.status === ESTIMATE_STATUS || estimate.status === SUB_DEALER_ESTIMATE) && (
                    <>
                      {((isSubDealer(auth.user.roles.map((role: Role) => role.name)) && estimate.status === SUB_DEALER_ESTIMATE) ||
                        ((isDealer(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name)) || isAdmin(auth.user.roles.map((role: Role) => role.name))) && estimate.status === ESTIMATE_STATUS)) && (
                        <>
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
                                        <button onClick={() => {
                                          router.get(route('single-hunt.create', estimate.id))
                                        }}>Single Hung</button>
                                    </li>
                                    <li>
                                        <button onClick={() => {
                                          router.get(route('horizontal-roller.create', estimate.id))
                                        }}>Horizontal Roller</button>
                                    </li>
                                </ul>
                            </Dropdown>
                          </div>
                          <Link href= { route('estimate.edit', estimate.id) } className="btn btn-success w-full gap-2">
                            <EditIcon />
                            Edit Estimate
                          </Link>
                        </>
                      )}
                      {isDealer(auth.user.roles.map((role: Role) => role.name)) && (estimate.products?.length ?? 0) > 0 && (
                        <Link href={ route('estimate.order', estimate.id) } className="btn btn-secondary w-full gap-2">
                          <MoneyIcon color='#fff' />
                          Crear Order
                        </Link>
                      )}
                    </>
                  )}
                  <PrintEstimateButton id={estimate.id} user={auth.user} />
                </div>
              </Panel>
            </div>
            <div className='col-span-10'>
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
                      {((isSubDealer(auth.user.roles.map((role: Role) => role.name)) && estimate.status === SUB_DEALER_ESTIMATE) ||
                      ((isDealer(auth.user.roles.map((role: Role) => role.name)) || isAdmin(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name))) && estimate.status === ESTIMATE_STATUS)) && (
                        <th className="px-6 pt-5 pb-4 w-14">Actions</th>
                      )}
                    </tr>
                  </thead>
                  <tbody>
                    {estimate.products?.map((product: Product) => {
                      const { id, system, line_item_name, qty, width, height, frame_color, glass_type } = product
                      return (
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
                            {getNumberWithFraction(width)} x {getNumberWithFraction(height)}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {frame_color}
                          </td>
                          <td className="border-t px-6 py-4 align-top">
                            {glass_type}
                          </td>
                          <td className="border-t px-6 py-4 align-top text-right">
                            {formatPrice(getUnitPriceByRole(product, auth.user.roles.map((role: Role) => role.name)))}
                          </td>
                          <td className="border-t px-6 py-4 align-top text-right">
                            {formatPrice(getTotalPriceByRole(product, auth.user.roles.map((role: Role) => role.name)))}
                          </td>
                          {((isSubDealer(auth.user.roles.map((role: Role) => role.name)) && estimate.status === SUB_DEALER_ESTIMATE) ||
                          ((isDealer(auth.user.roles.map((role: Role) => role.name)) || isAdmin(auth.user.roles.map((role: Role) => role.name)) || isAccountManager(auth.user.roles.map((role: Role) => role.name))) && estimate.status === ESTIMATE_STATUS)) && (
                            <td className="border-t flex items-center px-6 py-4">
                              <button
                                onClick={() => { router.post(route('product.duplicate', id)) }}
                                title='Duplicate Product'
                              >
                                <CopyIcon className='mr-2'/>
                              </button>
                              <button
                                onClick={() => { router.get(getUrlBySystem(system, id)) }}
                              >
                                <EditIcon />
                              </button>
                              <button
                                onClick={() => {
                                  if (confirm('Are you sure you want to delete this product?')) {
                                    router.delete(route('product.destroy', id))
                                  }
                                }}
                              >
                                <DeleteIcon />
                              </button>
                          </td>
                          )}
                        </tr>
                      )
                    })
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
              <PriceSummary estimate={estimate} roles={auth.user.roles.map((role: Role) => role.name)} />
            </div>
          </div>
      </AuthenticatedLayout>
  )
}
