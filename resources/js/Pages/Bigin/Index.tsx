import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head } from '@inertiajs/react'
import { type PageProps } from '@/types'

type IndexReferredProps = PageProps & {
  bigin: {
    client_id: string
    redirect_uri: string
  }
  token: boolean
  expire_in: string
}

export default function Dashboard ({ auth, bigin, token, expire_in }: IndexReferredProps) {
  const createToken = () => {
    if (confirm('Are you sure you want to create a new token?')) {
      const scopes: string = 'ZohoBigin.modules.ALL'
      const url: string = `https://accounts.zoho.com/oauth/v2/auth?scope=${scopes}&client_id=${bigin.client_id}&response_type=code&access_type=offline&redirect_uri=${bigin.redirect_uri}`
      window.location.href = url
    }
  }

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle='Bigin Integration'
    >
      <Head title="Bigin Integration" />
      <div className="mb-5">
        {token
          ? (
            <div className="flex items-center p-3.5 rounded text-info bg-info-light dark:bg-info-dark-light mb-4">
              <span className="ltr:pr-2 rtl:pl-2"><strong className="ltr:mr-1 rtl:ml-1">Info!</strong>
                Token is created. If you want create a new one click the button below. <br />
                <strong>Token expire in: {expire_in}</strong>
              </span>
            </div>
            )
          : (
            <div className="flex items-center p-3.5 rounded text-warning bg-warning-light dark:bg-warning-dark-light mb-4">
              <span className="ltr:pr-2 rtl:pl-2">
                <strong className="ltr:mr-1 rtl:ml-1">Warning!</strong>
                Token is not created yet. Please click the button below to create a new token.
              </span>
            </div>
            )}
        <button type="button" className="btn btn-primary" onClick={() => { createToken() }}>
          {token ? 'Refresh token' : 'Create token'}
        </button>
      </div>
    </AuthenticatedLayout>
  )
}
