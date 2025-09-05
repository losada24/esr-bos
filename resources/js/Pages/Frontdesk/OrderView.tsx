import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { type PageProps, type PaginatorLink } from '@/types'
import { useEffect, KeyboardEvent, useState } from 'react'
import { type OrderStatus } from '@/types/interfaces/order'
import TagPicker, { type TagItem } from '@/Components/TagPicker'
import UserIcon from '@/Components/Icons/UserIcon'
import { type Order } from './OrderCommon'
import LocationIcon from '@/Components/Icons/LocationIcon'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import StarIcon from '@/Components/Icons/StarIcon'
import MessageIcon from '@/Components/Icons/MessageIcon'
import EmailIcon from '@/Components/Icons/EmailIcon'
import ShareIcon from '@/Components/Icons/ShareIcon'
import CrownIcon from '@/Components/Icons/CrownIcon'
import DotsIcon from '@/Components/Icons/DotsIcon'

type IndexOrderProps = PageProps & {
  orderStatuses: OrderStatus[]
  order: Order
  tags: TagItem[]
  usedTags: TagItem[]
}

type TabKey = 'home' | 'profile' | 'contact'

export default function ShowStatusOrder ({ auth, orderStatuses, tags, order, usedTags }: IndexOrderProps) {
  const [tab, setTab] = useState<TabKey>('home')

  const tabs: Array<{ key: TabKey, label: string, Icon: React.FC<React.SVGProps<SVGSVGElement>> }> = [
    { key: 'home', label: 'Home', Icon: EmailIcon /* o Home de lucide */ },
    { key: 'profile', label: 'Profile', Icon: UserIcon /* o User de lucide */ },
    { key: 'contact', label: 'Contact', Icon: PhoneIcon /* o Phone de lucide */ }
  ];

  const onKeyDown = (e: KeyboardEvent<HTMLUListElement>) => {
    const idx = tabs.findIndex((t) => t.key === tab)
    if (e.key === 'ArrowRight') {
      setTab(tabs[(idx + 1) % tabs.length].key)
    } else if (e.key === 'ArrowLeft') {
      setTab(tabs[(idx - 1 + tabs.length) % tabs.length].key)
    }
  };

  useEffect(() => {
    /* fetch(route('order.status.filter', { })).then(async (response) => { return await response.json() }).then((data) => {
      setStatuses(data)
    }) */
  }, [])
  const { data, setData, processing, patch } = useForm<{ tags: TagItem[] }>({
    tags: tags ?? []
  })
  console.log(orderStatuses)

  function submit (e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    // ruta PATCH para actualizar solo tags del pedido
    patch(route('frontdesk.tags_update', order.id), { preserveScroll: true })
  }
  return (
       <AuthenticatedLayout
          auth={auth}
          pageTitle={`${order.name}${order.order_type ? ` (${order.order_type})` : ''}`}
      >
        <div className="mb-3 w-full">
        <div className="flex items-center gap-2">
          <CrownIcon />
          <span className="text-base font-medium text-gray-600">
            {order.user?.name}
          </span>
          <DotsIcon />
          <span className="text-base font-medium text-gray-600">
            {order.status}
          </span>
        </div>
      </div>

    <Head title="Order History" />

  <div className="h-px bg-gray-200 mb-2"></div>
  <div className="h-screen grid grid-cols-1 lg:grid-cols-[25%_75%]">
  <section className="p-4 overflow-auto space-y-8">
  <div >
    <h4 className="text-lg font-bold text-gray-800 mb-2">
      Related Contact</h4>
       <div className="space-y-6"> {/* 👈 controla el espacio vertical entre hijos */}
              {order.client?.name
                ? (
                <div className="flex items-center gap-2">
                  <UserIcon className="w-6 h-6 text-blue-600" />
                  <span className="text-base font-medium text-gray-600">
                    {order.client.name}
                  </span>
                </div>
                  )
                : (
                <p className="text-sm text-gray-500">No hay contacto relacionado</p>
                  )}

              {order.client?.phone
                ? (
                <div className="flex items-center gap-2">
                  <PhoneIcon />
                  <span className="text-base font-medium text-gray-600">
                    {order.client.phone}
                  </span>
                </div>
                  )
                : (
                <p className="text-sm text-gray-500">No hay contacto relacionado</p>
                  )}
                    {order.client?.email
                      ? (
                      <div className="flex items-center gap-2">
                        <EmailIcon />
                        <span className="text-base font-medium text-gray-600">
                          {order.client.email}
                        </span>
                      </div>
                        )
                      : (
                          <p className="text-sm text-gray-500">No Email Available</p>
                        )}
            </div>
    </div>
    <div>
        <form onSubmit={submit} className="space-y-3">
        <label className="text-lg font-bold text-gray-800 ">Tags</label>

        <div className="mt-1 flex items-end gap-2">
          <div className="flex-1">
            <TagPicker
              value={data.tags}
              onChange={(t) => { setData('tags', t) }}
              placeholder="Agregar tag"
              suggestions={usedTags} // <<<<< AQUÍ pasas las sugerencias
              // suggestionsTitle="Tags usados"
            />
          </div>

          <button
            type="submit"
            disabled={processing}
            aria-label="Guardar tags"
            title="Guardar tags"
            className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:opacity-50"
          >
                    {processing
                      ? (
                      // spinner
                      <svg viewBox="0 0 24 24" className="h-4 w-4 animate-spin" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" strokeOpacity="0.2" strokeWidth="3" />
                        <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" strokeWidth="3" />
                      </svg>
                        )
                      : (
                      // ícono de check
                      <svg viewBox="0 0 20 20" className="h-4 w-4" fill="currentColor" aria-hidden="true">
                        <path d="M8.5 13.5 4.9 10l1.2-1.2 2.4 2.3 5-5L15.7 7l-6 6.5z" />
                      </svg>
                        )}
            <span className="sr-only">Guardar</span>
          </button>
        </div>
      </form>
    </div>
    <div >
      <h4 className="text-lg font-bold text-gray-800 mb-2">
        Description</h4>
            {order.description
              ? (
              <div className="flex items-center gap-2">
                <span className="text-base font-medium text-gray-600">
                {order.description}
                </span>
              </div>
                )
              : (
              <p className="text-sm text-gray-500">No description available.</p>
                )}
      </div>
      <div>
      <h4 className="text-lg font-bold text-gray-800 mb-2">
        Job Site</h4>
            {order.job_address
              ? (
              <div className="flex items-center gap-2">
                <LocationIcon />
                <span className="text-base font-medium text-gray-600">
                {order.job_address}, {order.city}, {order.job_state} {order.job_zip}
                </span>
              </div>
                )
              : (
              <p className="text-sm text-gray-500">No Job Site available.</p>
                )}
      </div>
       <div>
      <h4 className="text-lg font-bold text-gray-800 mb-2">
        Sources</h4>
            {order.client?.source
              ? (
              <div className="flex items-center gap-2">
                <ShareIcon />
                <span className="text-base font-medium text-gray-600">
                {order.client.source}
                </span>
              </div>
                )
              : (
              <p className="text-sm text-gray-500">No Source Available.</p>
                )}
      </div>
      </section>
    <section className="p-4 overflow-auto border-t lg:border-t-0 lg:border-l">
      {/* Tabs */}
      <div>
        <ul
          role="tablist"
          aria-label="Tabs"
          className="flex flex-wrap mt-3 mb-5 border-b border-slate-200 dark:border-slate-700"
          onKeyDown={onKeyDown}
        >
          {tabs.map(({ key, label, Icon }) => {
            const active = tab === key
            return (
              <li key={key}>
                <button
                  id={`tab-${key}`}
                  role="tab"
                  aria-selected={active}
                  aria-controls={`panel-${key}`}
                  className={[
                    'p-5 py-3 -mb-px flex items-center border-b-2 transition-colors',
                    active
                      ? 'border-blue-600 text-blue-600'
                      : 'border-transparent hover:border-blue-600 hover:text-blue-600'
                  ].join(' ')}
                  onClick={() => { setTab(key) }}
                >
                  <Icon className="mr-2 h-4 w-4" aria-hidden="true" />
                  {label}
                </button>
              </li>
            );
          })}
        </ul>
      </div>

      {/* Panels */}
      <div className="flex-1 text-sm">
        {tab === 'home' && (
          <div id="panel-home" role="tabpanel" aria-labelledby="tab-home">
            <h4 className="font-semibold text-2xl mb-4">We move your world!</h4>
            <p className="mb-4">
              Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
              labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris
              nisi ut aliquip ex ea commodo consequat.
            </p>
            <p>
              Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
              labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris
              nisi ut aliquip ex ea commodo consequat.
            </p>
          </div>
        )}

        {tab === 'profile' && (
          <div id="panel-profile" role="tabpanel" aria-labelledby="tab-profile">
            <div className="flex items-start">
              <div className="w-20 h-20 mr-4 flex-none">
                <img
                  src="/assets/images/profile-34.jpeg"
                  alt="image"
                  className="w-20 h-20 m-0 rounded-full ring-2 ring-slate-200 dark:ring-slate-600 object-cover"
                />
              </div>
              <div className="flex-auto">
                <h5 className="text-xl font-medium mb-4">Media heading</h5>
                <p className="text-slate-500 dark:text-slate-400">
                  Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin.
                  Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac
                  nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
                </p>
              </div>
            </div>
          </div>
        )}

        {tab === 'contact' && (
          <div id="panel-contact" role="tabpanel" aria-labelledby="tab-contact">
            <p>
              Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut
              labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris
              nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit
              esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt
              in culpa qui officia deserunt mollit anim id est laborum.
            </p>
          </div>
        )}
      </div>
    </section>
</div>
      </AuthenticatedLayout>
  )
}
