import React from 'react'
import { CONFIG_XO } from '@/Utils/constants'
import { getNumberWithFraction } from '@/Utils/numbers'
// TODO: Fix this drawing
const HorizontallRollerOX = ({ width, height, widthtOfMovementPart }: {
  width: number
  height: number
  widthtOfMovementPart: number
}) => {
  return (
    <svg version="1.1" width="100%" height="100%" style={{ overflow: 'hidden' }} viewBox="-13 -13 76 76">
      <defs></defs>
      <g id="two-0" transform="matrix(1 0 0 1 0 0)" opacity="1">
        <text transform="matrix(1 0 0 1 44.96175 61)" id="two-147" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(widthtOfMovementPart)}"`}</text>
          <g id="two-148" transform="matrix(1 0 0 1 0 0)" opacity="1">
            <path transform="matrix(1 0 0 1 0 0)" id="two-144" d="M 29.9235 52 L 29.9235 60 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-145" d="M 60 52 L 60 60 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-146" d="M 27.9235 58 L 62 58 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          </g>
          <g id="two-160" transform="matrix(1 0 0 1 0 0)" opacity="1"><path transform="matrix(1 0 0 1 0.5 25)" id="two-137" d="M -0.5 -25 L 0.5 -25 L 0.5 25 L -0.5 25 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 1.375 25)" id="two-149" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 59.5 25)" id="two-138" d="M -0.5 -25 L 0.5 -25 L 0.5 25 L -0.5 25 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 58.458248 25)" id="two-139" d="M -0.54175 -21.571 L 0.54175 -21.571 L 0.54175 21.571 L -0.54175 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 57.374748 25)" id="two-140" d="M -0.54175 -21.571 L 0.54175 -21.571 L 0.54175 21.571 L -0.54175 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 56.458 25)" id="two-141" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-152" d="M 52.333 25 L 33.6735 25 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-153" d="M 33.6735 25 L 36.6735 22 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-154" d="M 33.6735 25 L 36.6735 28 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 30 1.714499)" id="two-142" d="M -29 -1.7145 L 29 -1.7145 L 29 1.7145 L -29 1.7145 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 30 48.285499)" id="two-143" d="M -29 -1.7145 L 29 -1.7145 L 29 1.7145 L -29 1.7145 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 14.45475 3.803999)" id="two-150" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 14.45475 46.195999)" id="two-151" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 43.37825 3.803999)" id="two-158" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 43.37825 46.195999)" id="two-159" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 28.9165 25)" id="two-155" d="M -1.007 -21.571 L 1.006999 -21.571 L 1.006999 21.571 L -1.007 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 27.5345 25)" id="two-156" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 30.2985 25)" id="two-157" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          </g>
          <text transform="matrix(1 0 0 1 30 -10)" id="two-164" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(width)}"`}</text>
          <g id="two-165" transform="matrix(1 0 0 1 0 0)" opacity="1"><path transform="matrix(1 0 0 1 0 0)" id="two-161" d="M 0 -2 L 0 -10 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-162" d="M 60 -2 L 60 -10 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-163" d="M -2 -8 L 62 -8 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          </g>
          <text transform="matrix(0 -1 1 0 -10 25)" id="two-169" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(height)}"`}</text>
          <g id="two-170" transform="matrix(1 0 0 1 0 0)" opacity="1"><path transform="matrix(1 0 0 1 0 0)" id="two-166" d="M -10 0 L -2 0 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
            <path transform="matrix(1 0 0 1 0 0)" id="two-167" d="M -10 50 L -2 50 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path><path transform="matrix(1 0 0 1 0 0)" id="two-168" d="M -8 -2 L -8 52 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          </g>
        </g>
      </svg>
  )
}

const HorizontallRollerXO = ({ width, height, widthtOfMovementPart }: {
  width: number
  height: number
  widthtOfMovementPart: number
}) => {
  return (
    <svg version="1.1" width="100%" height="100%" style={{ overflow: 'hidden' }} viewBox="-13 -13 76 76">
      <defs></defs>
      <g id="two-0" transform="matrix(1 0 0 1 0 0)" opacity="1">
        <text transform="matrix(1 0 0 1 15.038249 61)" id="two-246" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(widthtOfMovementPart)}"`}</text>
        <g id="two-247" transform="matrix(1 0 0 1 0 0)" opacity="1"><path transform="matrix(1 0 0 1 0 0)" id="two-243" d="M 0 52 L 0 60 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-244" d="M 30.076499 52 L 30.076499 60 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-245" d="M -2 58 L 32.076499 58 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
        <g id="two-262" transform="matrix(1 0 0 1 0 0)" opacity="1">
          <path transform="matrix(1 0 0 1 0.5 25)" id="two-239" d="M -0.5 -25 L 0.5 -25 L 0.5 25 L -0.5 25 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 3.542 25)" id="two-249" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 1.541749 25)" id="two-252" d="M -0.54175 -21.571 L 0.54175 -21.571 L 0.54175 21.571 L -0.54175 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 2.62525 25)" id="two-253" d="M -0.54175 -21.571 L 0.54175 -21.571 L 0.54175 21.571 L -0.54175 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-254" d="M 6.917 25 L 26.3265 25 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-255" d="M 26.3265 25 L 23.3265 22 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-256" d="M 26.3265 25 L 23.3265 28 " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 59.5 25)" id="two-240" d="M -0.5 -25 L 0.5 -25 L 0.5 25 L -0.5 25 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 58.625 25)" id="two-248" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 30 1.714499)" id="two-241" d="M -29 -1.7145 L 29 -1.7145 L 29 1.7145 L -29 1.7145 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 30 48.285499)" id="two-242" d="M -29 -1.7145 L 29 -1.7145 L 29 1.7145 L -29 1.7145 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 16.621749 3.803999)" id="two-250" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 16.621749 46.195999)" id="two-251" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 45.545249 3.803999)" id="two-260" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 45.545249 46.195999)" id="two-261" d="M -12.70475 -0.375 L 12.704749 -0.375 L 12.704749 0.375 L -12.70475 0.375 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 31.083499 25)" id="two-257" d="M -1.007 -21.571 L 1.006999 -21.571 L 1.006999 21.571 L -1.007 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 29.701499 25)" id="two-258" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 32.465499 25)" id="two-259" d="M -0.375 -21.571 L 0.375 -21.571 L 0.375 21.571 L -0.375 21.571 Z " fill="#fff" stroke="#000" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
        <text transform="matrix(1 0 0 1 30 -10)" id="two-266" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(width)}"`}</text>
        <g id="two-267" transform="matrix(1 0 0 1 0 0)" opacity="1">
          <path transform="matrix(1 0 0 1 0 0)" id="two-263" d="M 0 -2 L 0 -10 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-264" d="M 60 -2 L 60 -10 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-265" d="M -2 -8 L 62 -8 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
        <text transform="matrix(0 -1 1 0 -10 25)" id="two-271" fontFamily="sans-serif" fontSize="5" textAnchor="middle" dominantBaseline="middle" alignmentBaseline="middle" fontStyle="normal" fontWeight="500" textDecoration="none" fill="#616A72" stroke="transparent" strokeWidth="1" opacity="1" visibility="visible">{`${getNumberWithFraction(height)}"`}</text>
        <g id="two-272" transform="matrix(1 0 0 1 0 0)" opacity="1">
          <path transform="matrix(1 0 0 1 0 0)" id="two-268" d="M -10 0 L -2 0 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-269" d="M -10 50 L -2 50 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
          <path transform="matrix(1 0 0 1 0 0)" id="two-270" d="M -8 -2 L -8 52 " fill="#fff" stroke="#616A72" strokeWidth="0.3" strokeOpacity="1" fillOpacity="1" visibility="visible" strokeLinecap="butt" strokeLinejoin="miter" strokeMiterlimit="4"></path>
        </g>
      </g>
    </svg>
  )
}

const HorizontalRollerDrawing = ({ width, height, widthtOfMovementPart, config }: {
  width: number
  height: number
  widthtOfMovementPart: number
  config: string
}) => {
  if (config === '') {
    return null
  }

  return config === CONFIG_XO
    ? <HorizontallRollerXO width={width} height={height} widthtOfMovementPart={widthtOfMovementPart} />
    : <HorizontallRollerOX width={width} height={height} widthtOfMovementPart={widthtOfMovementPart} />
}

export default HorizontalRollerDrawing
