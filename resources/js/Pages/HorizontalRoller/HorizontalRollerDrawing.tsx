import React from 'react'
import { CONFIG_OX, CONFIG_XO } from '@/Utils/constants'
import { getNumberWithFraction } from '@/Utils/numbers'
import {
  PDFViewer,
  Document,
  Page,
  StyleSheet
} from '@react-pdf/renderer'
import HorizontalRollerXOSquared from './HorizontalRollerXOSquared'
import HorizontalRollerOXSquared from './HorizontalRollerOXSquared'

const styles = StyleSheet.create({
  page: { padding: 20 },
  viewer: {
    width: 500,
    height: 500
  }
})

const HorizontalRollerDrawing = ({ width, height, widthtOfMovementPart, config }: {
  width: number
  height: number
  widthtOfMovementPart: number
  config: string
}) => {
  if (config === '') {
    return null
  }

  return (
    <PDFViewer style={styles.viewer} showToolbar={false}>
      <Document>
        <Page style={styles.page} size={{ width: 550, height: 550 }}>
          {config === CONFIG_XO && <HorizontalRollerXOSquared width={width} height={height} svgHeight={500} svgWidth={500} widthtOfMovementPart={widthtOfMovementPart} /> }
          {config === CONFIG_OX && <HorizontalRollerOXSquared width={width} height={height} svgHeight={500} svgWidth={500} widthtOfMovementPart={widthtOfMovementPart} /> }
        </Page>
      </Document>
    </PDFViewer>
  )
}

export default HorizontalRollerDrawing
