import React, { useState } from 'react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type Company, type PaginatorLink, type ModalProps } from '@/types'
import Pagination from '@/Components/Pagination'
import CompanyFilter from './CompanyFilter'
import FeaturedImageModal from '@/Pages/RawMaterial/FeaturedImageModal'

type IndexCompanyProps = PageProps & {
  companies: {
    data: Company[]
    meta: {
      links: PaginatorLink[]
    }
  }
}

export default function Index ({ auth, companies }: IndexCompanyProps) {
  const [showModal, setShowModal] = useState(false)
  const [selectedModalProps, setSelectedModalProps] = useState<ModalProps | null>(null)
  const destroy = (id: number) => {
    if (confirm('Are you sure you want to delete this Company?')) {
      router.delete(route('company.destroy', id))
    }
  }

  return (
      <AuthenticatedLayout
          auth={auth}
          pageTitle='Companies'
          actions={
            <Link
              className="btn btn-primary"
              href={route('company.create')}
            >
              <span>Create Company</span>
            </Link>
          }
      >
        <Head title="Companies" />
        <CompanyFilter />

        <div className='table-responsive'>
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="font-bold text-left">
                <th className="px-6 pt-5 pb-4">Logo</th>
                <th className="px-6 pt-5 pb-4">Name</th>
                <th className="px-6 pt-5 pb-4">Phone</th>
                <th className="px-6 pt-5 pb-4">Email</th>
                <th className="px-6 pt-5 pb-4">City</th>
                <th className="px-6 pt-5 pb-4">State</th>
                <th className="px-6 pt-5 pb-4">Zip</th>
                <th className="px-6 pt-5 pb-4 w-14">Actions</th>
              </tr>
            </thead>
            <tbody>
              {companies.data.map(({ id, name, phone_number, email, city, state, zip, featured_image }) => {
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
                      {phone_number}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {email}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {city}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {state}
                    </td>
                    <td className="border-t px-6 py-4 align-top">
                      {zip}
                    </td>
                    <td className="border-t flex items-center px-6 py-4">
                        <Link
                          href={route('company.edit', id)}
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
              {companies.data.length === 0 && (
                <tr>
                  <td className="px-6 py-4 border-t" colSpan={3}>
                    No Companys found.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        <Pagination links={companies.meta.links} />
        <FeaturedImageModal showModal={showModal} onClose={setShowModal} selectedModalProps={selectedModalProps} />
      </AuthenticatedLayout>
  )
}
