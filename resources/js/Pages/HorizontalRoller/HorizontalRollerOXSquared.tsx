import React from 'react'
import { getNumberWithFractionAndInches } from '@/Utils/numbers'
import {
  Svg,
  Defs,
  ClipPath,
  Path,
  G,
  Text
} from '@react-pdf/renderer'

const HorizontalRollerOXSquared = ({ width, height, svgHeight, svgWidth, widthtOfMovementPart }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
  widthtOfMovementPart: number
}) => {
  return (
    <Svg height={svgHeight} width={svgWidth} viewBox="0 0 375 374.999991" preserveAspectRatio="xMidYMid meet">
      <Defs>
        <ClipPath id="e2dd9f9bf3">
          <Path d="M 76.90625 66.34375 L 318.101562 66.34375 L 318.101562 307.539062 L 76.90625 307.539062 Z M 76.90625 66.34375 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="2488b09f20">
          <Path d="M 96.496094 85.941406 L 298.5 85.941406 L 298.5 287.941406 L 96.496094 287.941406 Z M 96.496094 85.941406 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="2001728f0f">
          <Path d="M 96.75 85.941406 L 298.5 85.941406 L 298.5 287.691406 L 96.75 287.691406 Z M 96.75 85.941406 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="c40c0d3342">
          <Path d="M 187.9375 85.941406 L 207.058594 85.941406 L 207.058594 287.941406 L 187.9375 287.941406 Z M 187.9375 85.941406 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="055bbe245f">
          <Path d="M 188.308594 85.976562 L 207.058594 85.976562 L 207.058594 287.691406 L 188.308594 287.691406 Z M 188.308594 85.976562 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="a9a4d2ce14">
          <Path d="M 209.144531 90.371094 L 294.410156 90.371094 L 294.410156 282.941406 L 209.144531 282.941406 Z M 209.144531 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="36e8f980fe">
          <Path d="M 209.144531 90.371094 L 294.410156 90.371094 L 294.410156 282.933594 L 209.144531 282.933594 Z M 209.144531 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="5a076af39b">
          <Path d="M 100.921875 90.371094 L 186.191406 90.371094 L 186.191406 282.941406 L 100.921875 282.941406 Z M 100.921875 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="dfd07ce827">
          <Path d="M 100.925781 90.371094 L 186.191406 90.371094 L 186.191406 282.933594 L 100.925781 282.933594 Z M 100.925781 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="d848b6113b">
          <Path d="M 238.0625 179.796875 L 265.8125 179.796875 L 265.8125 193.296875 L 238.0625 193.296875 Z M 238.0625 179.796875 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#e2dd9f9bf3)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 318.101439, 66.343671)" fill-opacity="1" fill="#d9d9d9" fill-rule="nonzero" stroke-linejoin="miter" d="M 0.000105542 -0.000164918 L 321.593877 -0.000164918 L 321.593877 321.593606 L 0.000105542 321.593606 Z M 0.000105542 -0.000164918 " stroke="#000000" strokeWidth="6" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#2488b09f20)">
        <Path fill="#ffffff" d="M 298.5 85.941406 L 298.5 287.941406 L 96.496094 287.941406 L 96.496094 85.941406 Z M 298.5 85.941406 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#2001728f0f)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 298.499399, 85.94086)" fill="none" stroke-linejoin="miter" d="M 0.000728391 -0.000800935 L 269.328888 -0.000800935 L 269.328888 269.327359 L 0.000728391 269.327359 Z M 0.000728391 -0.000800935 " stroke="#000000" strokeWidth="4" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.00000000000000538, -0.00000000000000538, 0.75, 37.500002, 66.343671)" fill="none" stroke-linejoin="miter" d="M -0.00000271667 0.500106 L 47.541667 0.500106 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.00000000000000538, -0.00000000000000538, 0.75, 37.500002, 305.72087)" fill="none" stroke-linejoin="miter" d="M -0.00000271667 0.502382 L 47.541667 0.502382 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 40.318617, 306.096719)" fill="none" stroke-linejoin="miter" d="M 0.49875 0.502261 L 318.670646 0.502261 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 40.318617, 306.096719)" fill="none" stroke-linejoin="round" d="M 2.498751 -0.997739 L 0.49875 0.502261 L 2.498751 2.002261 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 40.318617, 306.096719)" fill="none" stroke-linejoin="round" d="M 316.670646 -0.997739 L 318.670646 0.502261 L 316.670646 2.002261 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 207.060783, 311.29372)" fill="none" stroke-linejoin="miter" d="M -0.00100108 0.497711 L 47.540669 0.497711 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.749991, 0.00369937, -0.00369937, 0.749991, 206.472721, 342.708258)" fill="none" stroke-linejoin="miter" d="M 0.502392 0.5011 L 147.842717 0.498303 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.749991, 0.00369937, -0.00369937, 0.749991, 206.472721, 342.708258)" fill="none" stroke-linejoin="round" d="M 2.500229 -0.998356 L 0.502392 0.5011 L 2.499401 2.001685 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.749991, 0.00369937, -0.00369937, 0.749991, 206.472721, 342.708258)" fill="none" stroke-linejoin="round" d="M 145.845707 -1.002282 L 147.842717 0.498303 L 145.84488 1.997759 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 318.09848, 311.29372)" fill="none" stroke-linejoin="miter" d="M -0.00100108 0.501098 L 47.540669 0.501098 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 79.032326, 287.5)" fill="none" stroke-linejoin="miter" d="M -0.00143428 1.500547 L 24.154817 1.500547 " stroke="#000000" strokeWidth="3" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 79.032326, 84.5)" fill="none" stroke-linejoin="miter" d="M -0.00143428 1.499265 L 24.154817 1.499265 " stroke="#000000" strokeWidth="3" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 298, 84.5)" fill="none" stroke-linejoin="miter" d="M 0.00193318 1.499265 L 24.152976 1.499265 " stroke="#000000" strokeWidth="3" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 298, 287)" fill="none" stroke-linejoin="miter" d="M -0.00147737 1.500547 L 24.154774 1.500547 " stroke="#000000" strokeWidth="3" stroke-opacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#c40c0d3342)">
        <Path fill="#d9d9d9" d="M 207.058594 85.941406 L 207.058594 287.976562 L 187.9375 287.976562 L 187.9375 85.941406 Z M 207.058594 85.941406 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#055bbe245f)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 207.058013, 85.976918)" fill="none" stroke-linejoin="miter" d="M -0.000473406 -0.000773929 L 269.228728 -0.000773929 L 269.228728 25.483604 L -0.000473406 25.483604 Z M -0.000473406 -0.000773929 " stroke="#000000" strokeWidth="4" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#a9a4d2ce14)">
        <Path fill="#ffffff" d="M 294.410156 90.371094 L 294.410156 282.890625 L 209.144531 282.890625 L 209.144531 90.371094 Z M 294.410156 90.371094 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#36e8f980fe)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 294.410054, 90.372452)" fill="none" stroke-linejoin="miter" d="M -0.00181162 -0.000136377 L 256.748205 -0.000136377 L 256.748205 113.687371 L -0.00181162 113.687371 Z M -0.00181162 -0.000136377 " stroke="#000000" strokeWidth="2" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#5a076af39b)">
        <Path fill="#ffffff" d="M 186.191406 90.371094 L 186.191406 282.890625 L 100.921875 282.890625 L 100.921875 90.371094 Z M 186.191406 90.371094 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#dfd07ce827)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 186.189499, 90.372452)" fill="none" stroke-linejoin="miter" d="M -0.00181162 -0.00254344 L 256.748205 -0.00254344 L 256.748205 113.684964 L -0.00181162 113.684964 Z M -0.00181162 -0.00254344 " stroke="#000000" strokeWidth="2" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#d848b6113b)">
        <Path fill="#000000" d="M 243.925781 180.195312 L 238.445312 185.828125 C 238.0625 186.21875 238.0625 186.859375 238.445312 187.25 L 243.925781 192.894531 C 244.304688 193.285156 244.929688 193.285156 245.3125 192.894531 C 245.691406 192.503906 245.691406 191.863281 245.3125 191.472656 L 241.496094 187.554688 L 264.832031 187.554688 C 265.371094 187.554688 265.8125 187.097656 265.8125 186.546875 C 265.8125 185.992188 265.371094 185.539062 264.832031 185.539062 L 241.507812 185.539062 L 245.324219 181.617188 C 245.519531 181.417969 245.605469 181.164062 245.605469 180.902344 C 245.605469 180.636719 245.507812 180.382812 245.324219 180.183594 C 244.929688 179.804688 244.304688 179.804688 243.925781 180.195312 Z M 243.925781 180.195312 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 316.279496, 62.956263)" fill="none" stroke-linejoin="miter" d="M -0.00102424 0.502339 L 47.540646 0.502339 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 316.655053, 31.340506)" fill="none" stroke-linejoin="miter" d="M 0.498403 0.500883 L 318.680716 0.500882 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 316.655053, 31.340506)" fill="none" stroke-linejoin="round" d="M 2.498404 -0.999117 L 0.498403 0.500883 L 2.498404 2.000883 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 316.655053, 31.340506)" fill="none" stroke-linejoin="round" d="M 316.680716 -0.999118 L 318.680716 0.500882 L 316.680716 2.000882 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 76.902297, 62.956263)" fill="none" stroke-linejoin="miter" d="M -0.00102425 0.500062 L 47.540646 0.500062 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Text x="48%" y="25"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(width)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
        transform="matrix(0 -1 1 0 35 200)"
      >{getNumberWithFractionAndInches(height)}</Text>
      <Text x="62%" y="90%"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(widthtOfMovementPart)}</Text>
    </Svg>
  )
}

export default HorizontalRollerOXSquared
