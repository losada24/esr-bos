import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type RawMaterial, type PaginatorLink, type ModalProps } from '@/types'
import Pagination from '@/Components/Pagination'
import UserFilter from './UserFilter'
import { useState } from 'react'
import FeaturedImageModal from './FeaturedImageModal'

type IndexRawMaterialProps = PageProps & {
  rawMaterials: {
    data: RawMaterial[]
    meta: {
      links: PaginatorLink[]
    }
  }
}

export default function Index ({ auth, rawMaterials }: IndexRawMaterialProps) {
  const [showModal, setShowModal] = useState(false)
  const [selectedModalProps, setSelectedModalProps] = useState<ModalProps | null>(null)
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Raw Material?')) {
      router.delete(route('raw-material.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Raw Materials'
          actions={
            <Link
              className="btn btn-primary"
              href={route('raw-material.create')}
            >
              <span>Create Raw Material</span>
            </Link>
          }
      >
        <Head title="Raw Material" />
        <UserFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Featured Image</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Unit of Measurement</th>
                <th className="px-6 pt-5 pb-4">Qty</th>
                <th className="px-6 pt-5 pb-4">Cost per Unit</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {rawMaterials.data.map(({ id, featured_image, name, qty, unit_of_measurement, cost_per_unit }) => {
                return (
                  <tr
                    key={id}
                    className="hover:bg-gray-100 focus-within:bg-gray-100"
                  >
                    <td className="border-t px-6 py-4 align-top">
                      <button onClick={() => {
                        setSelectedModalProps({
                          title: name,
                          image: featured_image
                        })
                        setShowModal(true)
                      }}>
                        <img className='w-20 h-20 rounded-md overflow-hidden object-cover' src={featured_image} />
                      </button>
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {name}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {qty}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {unit_of_measurement}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      ${cost_per_unit}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('raw-material.edit', id)}
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
              {rawMaterials.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={6}>
                    No Raw Materials found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={rawMaterials.meta.links} />
        <FeaturedImageModal showModal={showModal} onClose={setShowModal} selectedModalProps={selectedModalProps} />
      </AuthenticatedLayout>
  )
}
