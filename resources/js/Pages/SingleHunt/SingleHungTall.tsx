import React from 'react'
import { getNumberWithFractionAndInches } from '@/Utils/numbers'
import {
  Svg,
  Defs,
  ClipPath,
  Path,
  G,
  Rect,
  Text
} from '@react-pdf/renderer'

const SingleHungTall = ({ width, height, svgHeight, svgWidth, heightOfMovementPart }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
  heightOfMovementPart: number
}) => {
  return (
    <Svg width={svgWidth} viewBox="0 0 375 374.999991" height={svgHeight} preserveAspectRatio="xMidYMid meet">
      <Defs>
        <ClipPath id="a874ab2270">
          <Path d="M 97.496094 73.902344 L 280.027344 73.902344 L 280.027344 351.75 L 97.496094 351.75 Z M 97.496094 73.902344 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="0b96ee2dc5">
          <Path d="M 97.496094 73.902344 L 279.746094 73.902344 L 279.746094 351.402344 L 97.496094 351.402344 Z M 97.496094 73.902344 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="57ea64792a">
          <Path d="M 112.324219 96.476562 L 265.195312 96.476562 L 265.195312 329.175781 L 112.324219 329.175781 Z M 112.324219 96.476562 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="be6afd3dbb">
          <Path d="M 112.328125 96.476562 L 265.191406 96.476562 L 265.191406 328.976562 L 112.328125 328.976562 Z M 112.328125 96.476562 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="982041f33a">
          <Path d="M 112.5625 203.890625 L 265.0625 203.890625 L 265.0625 223.011719 L 112.5625 223.011719 Z M 112.5625 203.890625 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="4156ba9635">
          <Path d="M 112.589844 203.890625 L 264.8125 203.890625 L 264.8125 222.640625 L 112.589844 222.640625 Z M 112.589844 203.890625 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="58b55b5a52">
          <Path d="M 117.101562 101.1875 L 260.324219 101.1875 L 260.324219 201.445312 L 117.101562 201.445312 Z M 117.101562 101.1875 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="34fdea42c6">
          <Path d="M 117.105469 101.1875 L 260.316406 101.1875 L 260.316406 201.441406 L 117.105469 201.441406 Z M 117.105469 101.1875 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="02086e91d3">
          <Path d="M 117.050781 224.511719 L 260.269531 224.511719 L 260.269531 324.773438 L 117.050781 324.773438 Z M 117.050781 224.511719 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="ea865583c1">
          <Path d="M 117.050781 224.511719 L 260.265625 224.511719 L 260.265625 324.765625 L 117.050781 324.765625 Z M 117.050781 224.511719 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="e632ab6423">
          <Path d="M 180.855469 261 L 194.355469 261 L 194.355469 288.671875 L 180.855469 288.671875 Z M 180.855469 261 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <G clip-path="url(#a874ab2270)">
        <Path fill="#d9d9d9" d="M 97.496094 73.902344 L 280.027344 73.902344 L 280.027344 351.980469 L 97.496094 351.980469 Z M 97.496094 73.902344 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#0b96ee2dc5)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 97.496585, 73.901678)" fill="none" stroke-linejoin="miter" d="M -0.000655397 0.000887204 L 243.369137 0.000887204 L 243.369137 370.454013 L -0.000655397 370.454013 Z M -0.000655397 0.000887204 " stroke="#000000" strokeWidth="6" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#57ea64792a)">
        <Path fill="#ffffff" d="M 112.324219 96.476562 L 265.195312 96.476562 L 265.195312 329.367188 L 112.324219 329.367188 Z M 112.324219 96.476562 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#be6afd3dbb)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 112.328736, 96.476845)" fill="none" stroke-linejoin="miter" d="M -0.000814032 -0.000376486 L 203.816908 -0.000376486 L 203.816908 310.249644 L -0.000814032 310.249644 Z M -0.000814032 -0.000376486 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 110.5, 97)" fill="none" stroke-linejoin="miter" d="M 0.00124664 1.999818 L 27.824165 1.999818 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 263.9, 97)" fill="none" stroke-linejoin="miter" d="M 0.00248369 1.999211 L 27.820194 1.999211 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 110.5, 350)" fill="none" stroke-linejoin="miter" d="M -0.0000580495 1.998482 L 27.82286 1.998482 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, -0.75, 0.75, 0, 263.9, 350)" fill="none" stroke-linejoin="miter" d="M -0.0000580495 1.999211 L 27.82286 1.999211 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#982041f33a)">
        <Path fill="#d9d9d9" d="M 112.5625 203.890625 L 265.078125 203.890625 L 265.078125 223.011719 L 112.5625 223.011719 Z M 112.5625 203.890625 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#4156ba9635)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 112.588797, 203.892042)" fill="none" stroke-linejoin="miter" d="M 0.00139552 -0.00188908 L 203.25663 -0.00188908 L 203.25663 25.482489 L 0.00139552 25.482489 Z M 0.00139552 -0.00188908 " stroke="#000000" strokeWidth="4" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#58b55b5a52)">
        <Path fill="#ffffff" d="M 117.101562 101.1875 L 260.441406 101.1875 L 260.441406 201.445312 L 117.101562 201.445312 Z M 117.101562 101.1875 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#34fdea42c6)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 117.104901, 100)" fill="none" stroke-linejoin="miter" d="M 0.000757253 0.000121926 L 190.948686 0.000121926 L 190.948686 133.672006 L 0.000757253 133.672006 Z M 0.000757253 0.000121926 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <Path stroke-linecap="butt" transform="matrix(-0.75, -0.000000001309, 0.000000001309, -0.75, 93.73688, 74.710586)" fill="none" stroke-linejoin="miter" d="M -0.00186841 0.499532 L 47.539801 0.499532 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 62.121161, 74.333652)" fill="none" stroke-linejoin="miter" d="M 0.497839 0.50009 L 368.82078 0.50009 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, 0.75, -0.75, 0, 62.121161, 74.333652)" fill="none" stroke-linejoin="round" d="M 2.497839 -0.99991 L 0.497839 0.50009 L 2.497839 2.00009 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0, 0.75, -0.75, 0, 62.121161, 74.333652)" fill="none" stroke-linejoin="round" d="M 366.82078 -0.99991 L 368.82078 0.50009 L 366.82078 2.00009 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.75, 0, 0, -0.75, 93.73688, 351.691062)" fill="none" stroke-linejoin="miter" d="M -0.00186841 0.499541 L 47.539801 0.499541 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.00000000000000538, -0.00000000000000538, 0.75, 283.777428, 350.8844)" fill="none" stroke-linejoin="miter" d="M -0.000112687 0.497883 L 47.541557 0.497883 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.0032138, -0.749993, 0.749993, 0.0032138, 315.193109, 351.506929)" fill="none" stroke-linejoin="miter" d="M 0.500972 0.500795 L 170.257753 0.502544 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.0032138, -0.749993, 0.749993, 0.0032138, 315.193109, 351.506929)" fill="none" stroke-linejoin="round" d="M 2.499757 -1.002575 L 0.500972 0.500795 L 2.502196 1.997442 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.0032138, -0.749993, 0.749993, 0.0032138, 315.193109, 351.506929)" fill="none" stroke-linejoin="round" d="M 168.256507 -0.999311 L 170.257753 0.502544 L 168.258946 2.000706 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 283.777428, 223.069497)" fill="none" stroke-linejoin="miter" d="M -0.000112687 0.501087 L 47.541557 0.501087 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 280.02356, 35.24939)" fill="none" stroke-linejoin="miter" d="M 0.000813646 0.500163 L 47.542483 0.500163 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0, 0.75, -0.75, 0, 98.240741, 35.24939)" fill="none" stroke-linejoin="miter" d="M 0.000813646 0.498072 L 47.542483 0.498072 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.74999, -0.0038722, 0.0038722, 0.74999, 97.69956, 38.064394)" fill="none" stroke-linejoin="miter" d="M 0.502178 0.500073 L 242.104389 0.49745 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.74999, -0.0038722, 0.0038722, 0.74999, 97.69956, 38.064394)" fill="none" stroke-linejoin="round" d="M 2.499533 -1.000051 L 0.502178 0.500073 L 2.499669 1.99999 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(0.74999, -0.0038722, 0.0038722, 0.74999, 97.69956, 38.064394)" fill="none" stroke-linejoin="round" d="M 240.106898 -1.002466 L 242.104389 0.49745 L 240.107034 1.997574 " stroke="#000000" strokeWidth="1" strokeOpacity="1" stroke-miterlimit="4"/>    
      <G clip-path="url(#02086e91d3)">
        <Path fill="#ffffff" d="M 117.050781 224.511719 L 260.390625 224.511719 L 260.390625 324.773438 L 117.050781 324.773438 Z M 117.050781 224.511719 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#ea865583c1)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 117.052293, 226)" fill="none" stroke-linejoin="miter" d="M -0.00201542 -0.00151161 L 190.951122 -0.00151161 L 190.951122 133.670372 L -0.00201542 133.670372 Z M -0.00201542 -0.00151161 " stroke="#000000" strokeWidth="2" strokeOpacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#e632ab6423)">
        <Path fill="#000000" d="M 193.953125 266.78125 L 188.324219 261.300781 C 187.933594 260.921875 187.289062 260.921875 186.898438 261.300781 L 181.253906 266.78125 C 180.863281 267.164062 180.863281 267.789062 181.253906 268.167969 C 181.644531 268.546875 182.289062 268.546875 182.679688 268.167969 L 186.597656 264.355469 L 186.597656 287.691406 C 186.597656 288.230469 187.050781 288.671875 187.605469 288.671875 C 188.160156 288.671875 188.613281 288.230469 188.613281 287.691406 L 188.613281 264.367188 L 192.53125 268.179688 C 192.734375 268.378906 192.984375 268.464844 193.25 268.464844 C 193.515625 268.464844 193.765625 268.363281 193.96875 268.179688 C 194.34375 267.789062 194.34375 267.164062 193.953125 266.78125 Z M 193.953125 266.78125 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <Text x="48%" y="30"
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
      >{getNumberWithFractionAndInches(width)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1'
        transform="matrix(0 -1 1 0 45 220)"
      >{getNumberWithFractionAndInches(height)}</Text>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1' transform="matrix(0 -1 1 0 340 320)"
      >{getNumberWithFractionAndInches(heightOfMovementPart)}</Text>
    </Svg>
  )
}

export default SingleHungTall
