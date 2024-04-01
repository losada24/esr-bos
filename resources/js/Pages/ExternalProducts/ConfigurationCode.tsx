import React from 'react'
import CodeHighlight from '@/Components/Highlight'
import { EXTERNAL_PRODUCT_CASEMENT, EXTERNAL_PRODUCT_MULLION } from '@/Utils/constants'

const ConfigurationCode = ({ product }: { product: string }) => {
  return (
    <CodeHighlight>
      <pre className='language-typescript'>
      {product === EXTERNAL_PRODUCT_CASEMENT && (
        <>
{`
  {
    "configuration": "MULTIPOINT",
    "screen": "NO",
    "muntins": "NO",
    "opening": "RIGHT OPENING (XR)",
    "frame_type": "FLANGE",
    "limit_device": "NO",
    "protective_film": "NO",
    "locking_mechanism": "MULTIPOINT LOCK",
    "glass_type": "3/16 HS CLEAR +0.09PVB t Clear +3/16 HS CLEAR (REGULAR)"
  }`
}
  </>

      )}
      {product === EXTERNAL_PRODUCT_MULLION && (
       <>
{`
  {
    "configuration": "1 x 3 x 1/8"
  }`
}
       </>
      )}
      </pre>
    </CodeHighlight>
  )
}

export default ConfigurationCode
