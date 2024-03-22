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

const MullionSGV = ({ height, svgHeight, svgWidth }: {
  width: number
  height: number
  svgHeight: number
  svgWidth: number
}) => {
  return (
    <Svg height={svgHeight} width={svgWidth} viewBox='0 0 375 374.999991' preserveAspectRatio='xMidYMid meet'>
      <Defs>
        <ClipPath id="f5fc4a0b7d">
          <Path d="M 176.703125 37.535156 L 198.289062 37.535156 L 198.289062 337.464844 L 176.703125 337.464844 Z M 176.703125 37.535156 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="c38d865a7e">
          <Path d="M 151.136719 37.5 L 223.863281 37.5 L 223.863281 53.410156 L 151.136719 53.410156 Z M 151.136719 37.5 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="d0e913b599">
          <Path d="M 151.152344 37.5 L 223.847656 37.5 L 223.847656 53.25 L 151.152344 53.25 Z M 151.152344 37.5 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="37d31dacaa">
          <Path d="M 151.136719 321.589844 L 223.863281 321.589844 L 223.863281 337.5 L 151.136719 337.5 Z M 151.136719 321.589844 " clip-rule="nonzero"/>
        </ClipPath>
        <ClipPath id="49619e19e3">
          <Path d="M 151.152344 321.589844 L 223.847656 321.589844 L 223.847656 337.339844 L 151.152344 337.339844 Z M 151.152344 321.589844 " clip-rule="nonzero"/>
        </ClipPath>
      </Defs>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.000000000000008841, -0.000000000000008841, 0.75, 108.687074, 37.741323)" fill="none" stroke-linejoin="miter" d="M 0.000567783 0.501152 L 47.542238 0.501152 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(0.75, 0.000000000000008967, -0.000000000000008967, 0.75, 108.687074, 336.508629)" fill="none" stroke-linejoin="miter" d="M 0.000567783 0.498911 L 47.542238 0.498911 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="butt" transform="matrix(-0.00235603, -0.749996, 0.749996, -0.00235603, 111.502929, 337.158083)" fill="none" stroke-linejoin="miter" d="M 0.500884 0.49767 L 398.226826 0.502285 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.00235603, -0.749996, 0.749996, -0.00235603, 111.502929, 337.158083)" fill="none" stroke-linejoin="round" d="M 2.500394 -1.001264 L 0.500884 0.49767 L 2.501387 1.998754 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <Path stroke-linecap="round" transform="matrix(-0.00235603, -0.749996, 0.749996, -0.00235603, 111.502929, 337.158083)" fill="none" stroke-linejoin="round" d="M 396.226324 -0.998798 L 398.226826 0.502285 L 396.227316 2.00122 " stroke="#000000" strokeWidth="1" stroke-opacity="1" stroke-miterlimit="4"/>
      <G clip-path="url(#f5fc4a0b7d)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 176.704534, 37.534535)" fill="none" stroke-linejoin="miter" d="M -0.00187826 0.000828366 L 28.779375 0.000828366 L 28.779375 399.90713 L -0.00187826 399.90713 Z M -0.00187826 0.000828366 " stroke="#000000" strokeWidth="4" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#c38d865a7e)">
        <Path fill="#ffffff" d="M 151.136719 37.5 L 223.847656 37.5 L 223.847656 53.410156 L 151.136719 53.410156 Z M 151.136719 37.5 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#d0e913b599)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 151.151421, 37.499998)" fill="none" stroke-linejoin="miter" d="M 0.00123037 0.00000328333 L 96.928326 0.00000328333 L 96.928326 21.203131 L 0.00123037 21.203131 Z M 0.00123037 0.00000328333 " stroke="#000000" strokeWidth="4" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <G clip-path="url(#37d31dacaa)">
        <Path fill="#ffffff" d="M 151.136719 321.589844 L 223.847656 321.589844 L 223.847656 337.5 L 151.136719 337.5 Z M 151.136719 321.589844 " fill-opacity="1" fill-rule="nonzero"/>
      </G>
      <G clip-path="url(#49619e19e3)">
        <Path stroke-linecap="butt" transform="matrix(0.75, 0, 0, 0.75, 151.151421, 321.590897)" fill="none" stroke-linejoin="miter" d="M 0.00123037 -0.00140471 L 96.928326 -0.00140471 L 96.928326 21.201723 L 0.00123037 21.201723 Z M 0.00123037 -0.00140471 " stroke="#000000" strokeWidth="4" stroke-opacity="1" stroke-miterlimit="4"/>
      </G>
      <Text
        fill='#000000'
        stroke='transparent'
        strokeWidth='1' transform="matrix(0 -1 1 0 100 200)"
      >{getNumberWithFractionAndInches(height)}</Text>
    </Svg>
  )
}

export default MullionSGV
