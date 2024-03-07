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

const HorizontalRollerXOSquared = ({ width, height, svgHeight, svgWidth, widthtOfMovementPart }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
  widthtOfMovementPart: number
}) => {
  return (
    <Svg height={svgHeight} width={svgWidth} viewBox="0 0 375 374.999991" preserveAspectRatio="xMidYMid meet">
      <Defs>
        <ClipPath id="fe1c60ad76">
          <Path d="M 76.90625 66.34375 L 318.101562 66.34375 L 318.101562 307.539062 L 76.90625 307.539062 Z M 76.90625 66.34375 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="fbb594bd34">
          <Path d="M 96.496094 85.941406 L 298.5 85.941406 L 298.5 287.941406 L 96.496094 287.941406 Z M 96.496094 85.941406 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="d0a09ff19b">
          <Path d="M 96.75 85.941406 L 298.5 85.941406 L 298.5 287.691406 L 96.75 287.691406 Z M 96.75 85.941406 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="e6ff2ca2c4">
          <Path d="M 187.9375 85.941406 L 207.058594 85.941406 L 207.058594 287.941406 L 187.9375 287.941406 Z M 187.9375 85.941406 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="61f5c696fc">
          <Path d="M 188.308594 85.976562 L 207.058594 85.976562 L 207.058594 287.691406 L 188.308594 287.691406 Z M 188.308594 85.976562 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="d964eaf5de">
          <Path d="M 209.144531 90.371094 L 294.410156 90.371094 L 294.410156 282.941406 L 209.144531 282.941406 Z M 209.144531 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="2093563cf7">
          <Path d="M 209.144531 90.371094 L 294.410156 90.371094 L 294.410156 282.933594 L 209.144531 282.933594 Z M 209.144531 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="3e97f36a45">
          <Path d="M 100.921875 90.371094 L 186.191406 90.371094 L 186.191406 282.941406 L 100.921875 282.941406 Z M 100.921875 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="a603725a28">
          <Path d="M 100.925781 90.371094 L 186.191406 90.371094 L 186.191406 282.933594 L 100.925781 282.933594 Z M 100.925781 90.371094 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="0531b7a1e4">
          <Path d="M 130.285156 179.804688 L 158 179.804688 L 158 193.304688 L 130.285156 193.304688 Z M 130.285156 179.804688 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#fe1c60ad76)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 318.101439, 66.343671)" fill-opacity="1" fill="#d9d9d9" fill-rule="nonzero" stroke-linejoin="miter" d="M 0.000105542 -0.000164918 L 321.593877 -0.000164918 L 321.593877 321.593606 L 0.000105542 321.593606 Z M 0.000105542 -0.000164918 " stroke="#000000" strokeWidth="6" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#fbb594bd34)">
        <Path fill="#ffffff" d="M 298.5 85.941406 L 298.5 287.941406 L 96.496094 287.941406 L 96.496094 85.941406 Z M 298.5 85.941406 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#d0a09ff19b)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 298.499399, 85.94086)" fill="none" stroke-linejoin="miter" d="M 0.000728391 -0.000800935 L 269.328888 -0.000800935 L 269.328888 269.327359 L 0.000728391 269.327359 Z M 0.000728391 -0.000800935 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.00000000000000538, -0.00000000000000538, 0.75, 37.500002, 66.343671)" fill="none" stroke-linejoin="miter" d="M -0.00000271667 0.500106 L 47.541667 0.500106 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.00000000000000538, -0.00000000000000538, 0.75, 37.500002, 305.72087)" fill="none" stroke-linejoin="miter" d="M -0.00000271667 0.502382 L 47.541667 0.502382 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 40.318617, 306.096719)" fill="none" stroke-linejoin="miter" d="M 0.49875 0.502261 L 318.670646 0.502261 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 40.318617, 306.096719)" fill="none" stroke-linejoin="round" d="M 2.498751 -0.997739 L 0.49875 0.502261 L 2.498751 2.002261 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, -0.75, 0.75, 0, 40.318617, 306.096719)" fill="none" stroke-linejoin="round" d="M 316.670646 -0.997739 L 318.670646 0.502261 L 316.670646 2.002261 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 77.652297, 312.04372)" fill="none" stroke-linejoin="miter" d="M -0.00100101 0.499938 L 47.540669 0.499938 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.749991, 0.00369937, -0.00369937, 0.749991, 77.064213, 343.458258)" fill="none" stroke-linejoin="miter" d="M 0.500194 0.501111 L 147.845727 0.498288 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.749991, 0.00369937, -0.00369937, 0.749991, 77.064213, 343.458258)" fill="none" stroke-linejoin="round" d="M 2.49803 -0.998345 L 0.500194 0.501111 L 2.502411 2.00167 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.749991, 0.00369937, -0.00369937, 0.749991, 77.064213, 343.458258)" fill="none" stroke-linejoin="round" d="M 145.843509 -1.002271 L 147.845727 0.498288 L 145.842682 1.99777 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 188.689972, 312.04372)" fill="none" stroke-linejoin="miter" d="M -0.00100101 0.498088 L 47.540669 0.498088 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 79.032326, 287.5)" fill="none" stroke-linejoin="miter" d="M -0.00143428 1.500547 L 24.154817 1.500547 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 79.032326, 84.5)" fill="none" stroke-linejoin="miter" d="M -0.00143428 1.499265 L 24.154817 1.499265 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 298, 84.5)" fill="none" stroke-linejoin="miter" d="M 0.00193318 1.499265 L 24.152976 1.499265 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 298, 287)" fill="none" stroke-linejoin="miter" d="M -0.00147737 1.500547 L 24.154774 1.500547 " stroke="#000000" strokeWidth="3" strokeOpacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#e6ff2ca2c4)">
        <Path fill="#d9d9d9" d="M 207.058594 85.941406 L 207.058594 287.976562 L 187.9375 287.976562 L 187.9375 85.941406 Z M 207.058594 85.941406 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#61f5c696fc)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 207.058013, 85.976918)" fill="none" stroke-linejoin="miter" d="M -0.000473406 -0.000773929 L 269.228728 -0.000773929 L 269.228728 25.483604 L -0.000473406 25.483604 Z M -0.000473406 -0.000773929 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#d964eaf5de)">
        <Path fill="#ffffff" d="M 294.410156 90.371094 L 294.410156 282.890625 L 209.144531 282.890625 L 209.144531 90.371094 Z M 294.410156 90.371094 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#2093563cf7)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 295.5, 90.372452)" fill="none" stroke-linejoin="miter" d="M -0.00181162 -0.000136377 L 256.748205 -0.000136377 L 256.748205 113.687371 L -0.00181162 113.687371 Z M -0.00181162 -0.000136377 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#3e97f36a45)">
        <Path fill="#ffffff" d="M 186.191406 90.371094 L 186.191406 282.890625 L 100.921875 282.890625 L 100.921875 90.371094 Z M 186.191406 90.371094 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#a603725a28)">
        <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 185, 90.372452)" fill="none" stroke-linejoin="miter" d="M -0.00181162 -0.00254344 L 256.748205 -0.00254344 L 256.748205 113.684964 L -0.00181162 113.684964 Z M -0.00181162 -0.00254344 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#0531b7a1e4)">
        <Path fill="#000000" d="M 152.171875 192.902344 L 157.65625 187.269531 C 158.035156 186.878906 158.035156 186.238281 157.65625 185.847656 L 152.171875 180.203125 C 151.792969 179.8125 151.167969 179.8125 150.789062 180.203125 C 150.40625 180.59375 150.40625 181.234375 150.789062 181.625 L 154.601562 185.546875 L 131.265625 185.546875 C 130.726562 185.546875 130.285156 186 130.285156 186.554688 C 130.285156 187.105469 130.726562 187.5625 131.265625 187.5625 L 154.589844 187.5625 L 150.777344 191.480469 C 150.578125 191.679688 150.492188 191.933594 150.492188 192.199219 C 150.492188 192.460938 150.589844 192.714844 150.777344 192.914062 C 151.167969 193.292969 151.792969 193.292969 152.171875 192.902344 Z M 152.171875 192.902344 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0.000000001309, -0.75, 0.75, 0.000000001309, 316.279496, 62.956263)" fill="none" stroke-linejoin="miter" d="M -0.00102424 0.502339 L 47.540646 0.502339 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 316.655053, 31.340506)" fill="none" stroke-linejoin="miter" d="M 0.498403 0.500883 L 318.680716 0.500882 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 316.655053, 31.340506)" fill="none" stroke-linejoin="round" d="M 2.498404 -0.999117 L 0.498403 0.500883 L 2.498404 2.000883 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 316.655053, 31.340506)" fill="none" stroke-linejoin="round" d="M 316.680716 -0.999118 L 318.680716 0.500882 L 316.680716 2.000882 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 76.902297, 62.956263)" fill="none" stroke-linejoin="miter" d="M -0.00102425 0.500062 L 47.540646 0.500062 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
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
      <Text x="28%" y="90%"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(widthtOfMovementPart)}</Text>
    </Svg>
  )
}

export default HorizontalRollerXOSquared
