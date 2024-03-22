import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type ExternalProducts, type PaginatorLink, type ExternalProductsExtrasMullion } from '@/types'
import Pagination from '@/Components/Pagination'
import ExternalProductsFilter from './ExternalProductsFilter'
import { EXTERNAL_PRODUCT_MULLION } from '@/Utils/constants'
import Mullion from './Mullion'
import { formatPrice } from '@/Utils/price'

type IndexUserProps = PageProps & {
  externalProducts: {
    data: ExternalProducts[]
    links: PaginatorLink[]
  }
}

export default function Index ({ auth, externalProducts }: IndexUserProps) {
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this External Product?')) {
      router.delete(route('external-products.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='External Products'
          actions={
            <Link
              className="btn btn-primary"
              href={route('external-products.create')}
            >
              <span>Create External Products</span>
            </Link>
          }
      >
        <Head title="External Products" />
        <ExternalProductsFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Width</th>
                <th className="px-6 pt-5 pb-4">Heigth</th>
                <th className="px-6 pt-5 pb-4">Price</th>
                <th className="px-6 pt-5 pb-4">Extras</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {externalProducts.data.map(({ id, external_product, width, height, price, extras }) => {
                return (
                  <tr
                    key={id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      {external_product}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {width}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {height}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {formatPrice(price)}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {external_product === EXTERNAL_PRODUCT_MULLION && <Mullion extras={(extras as ExternalProductsExtrasMullion)} /> }
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('external-products.edit', id)}
                        >
                          <EditIcon />
                        </Link>
                        <button
                          onClick={() => { destroy(id) }}
                        >
                          <DeleteIcon />
                        </button>
                    </td>
                  </tr>
                )
              })}
              {externalProducts.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={6}>
                    No External Products found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={externalProducts.links} />
      </AuthenticatedLayout>
  )
}
