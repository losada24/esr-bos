import React from 'react'
import { getNumberWithFraction } from '@/Utils/numbers'
// TODO: Fix this drawing
const SingleHuntDrawing = ({ width, height, heightOfMovementPart }: {
  width: number
  height: number
  heightOfMovementPart: number
}) => {
  return (
    <svg version="1.1" width="100%" height="100%" style={{ overflow: 'hidden' }} viewBox="-13 -13 176 116">
      <defs></defs>
      <g id="two-0" transform="matrix(1 0 0 1 0 0)" opacity="1">
        <g id="two-189" transform="matrix(1 0 0 1 0 0)" opacity="1">
          <path transform="matrix(1 0 0 1 1.1875 50.215)" id="two-171" d="M -1.1875 -49.035 L 1.1875 -49.035 L 1.1875 49.035 L -1.1875 49.035 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 2.75 24.625749)" id="two-172" d="M -0.375 -23.44575 L 0.375 -23.44575 L 0.375 23.44575 L -0.375 23.44575 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 2.75 73.53125)" id="two-173" d="M -0.375 -23.44575 L 0.375 -23.44575 L 0.375 23.44575 L -0.375 23.44575 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 148.8125 50.215)" id="two-174" d="M -1.1875 -49.035 L 1.1875 -49.035 L 1.1875 49.035 L -1.1875 49.035 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 147.25 24.625749)" id="two-175" d="M -0.375 -23.44575 L 0.375 -23.44575 L 0.375 23.44575 L -0.375 23.44575 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 147.25 73.53125)" id="two-176" d="M -0.375 -23.44575 L 0.375 -23.44575 L 0.375 23.44575 L -0.375 23.44575 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 0.589999)" id="two-177" d="M -75 -0.59 L 75 -0.59 L 75 0.59 L -75 0.59 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 1.554999)" id="two-178" d="M -71.875 -0.375 L 71.875 -0.375 L 71.875 0.375 L -71.875 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 99.625)" id="two-179" d="M -75 -0.375 L 75 -0.375 L 75 0.375 L -75 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 98.681747)" id="two-180" d="M -72.625 -0.56825 L 72.625 -0.56825 L 72.625 0.56825 L -72.625 0.56825 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 97.545249)" id="two-181" d="M -72.625 -0.56825 L 72.625 -0.56825 L 72.625 0.56825 L -72.625 0.56825 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 96.601997)" id="two-182" d="M -71.875 -0.375 L 71.875 -0.375 L 71.875 0.375 L -71.875 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 49.078498)" id="two-183" d="M -72.625 -1.007 L 72.625 -1.007 L 72.625 1.006999 L -72.625 1.006999 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 47.696498)" id="two-184" d="M -71.875 -0.375 L 71.875 -0.375 L 71.875 0.375 L -71.875 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 75 50.460498)" id="two-185" d="M -71.875 -0.375 L 71.875 -0.375 L 71.875 0.375 L -71.875 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-186" d="M 75 63.53125 L 73 65.53125 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-187" d="M 75 63.53125 L 77 65.53125 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-188" d="M 75 63.53125 L 75 83.53125 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
        <text transform="matrix(1 0 0 1 75 -10)" id="two-193" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(width)}"`}</text>
        <g id="two-194" transform="matrix(1 0 0 1 0 0)" opacity="1">
          <path transform="matrix(1 0 0 1 0 0)" id="two-190" d="M 0 -2 L 0 -10 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-191" d="M 150 -2 L 150 -10 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-192" d="M -2 -8 L 152 -8 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
        <text transform="matrix(0 -1 1 0 -10 50)" id="two-198" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(height)}"`}</text>
        <g id="two-199" transform="matrix(1 0 0 1 0 0)" opacity="1">
          <path transform="matrix(1 0 0 1 0 0)" id="two-195" d="M -10 0 L -2 0 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-196" d="M -10 100 L -2 100 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-197" d="M -8 -2 L -8 102 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
        <text transform="matrix(0 -1 1 0 161 75.042747)" id="two-203" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(heightOfMovementPart)}"`}</text>
        <g id="two-204" transform="matrix(1 0 0 1 0 0)" opacity="1">
          <path transform="matrix(1 0 0 1 0 0)" id="two-200" d="M 160 50.085499 L 152 50.085499 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-201" d="M 160 100 L 152 100 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-202" d="M 158 48.085499 L 158 102 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
      </g>
    </svg>
  )
}

export default SingleHuntDrawing
