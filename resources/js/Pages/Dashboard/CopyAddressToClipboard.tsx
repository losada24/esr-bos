import CopyIcon from '@/Components/Icons/CopyIcon'
import Tippy from '@tippyjs/react'
import 'tippy.js/dist/tippy.css'
import Swal from 'sweetalert2'
import withReactContent from 'sweetalert2-react-content'

const CopyAddressToClipboard = ({ link }: { link: string }) => {
  return (
    <div className="mb-5">
        <label htmlFor="addonsRight">My referred address</label>
        <div className="flex">
            <input id="addonsRight" type="text" value={link} className="form-input ltr:rounded-r-none rtl:rounded-l-none" readOnly={true} />
            <Tippy content="Copy Link to Clipboard">
                <button
                  type="button"
                  className="btn btn-primary ltr:rounded-l-none rtl:rounded-r-none"
                  onClick={() => {
                    navigator.clipboard.writeText(link)
                    const MySwal = withReactContent(Swal)
                    MySwal.fire({
                      title: 'Link successfully copied to clipboard!',
                      toast: true,
                      position: 'bottom-start',
                      showConfirmButton: false,
                      timer: 3000,
                      showCloseButton: true
                    })
                  }}>
                  <CopyIcon className='h-5 w-5' />
                </button>
            </Tippy>
        </div>
    </div>
  )
}

export default CopyAddressToClipboard
