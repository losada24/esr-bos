import React from 'react'
import {
  Document,
  Page,
  StyleSheet,
  PDFViewer
} from '@react-pdf/renderer'
import { getNumberWithFraction } from '@/Utils/numbers'
import SingleHungSquared from './SingleHungSquared'
import SingleHungTall from './SingleHungTall'
import SingleHungWider from './SingleHungWider'

const styles = StyleSheet.create({
  page: { padding: 20 },
  viewer: {
    width: 500,
    height: 500
  }
})

const SingleHuntDrawing = ({ width, height, heightOfMovementPart }: {
  width: number
  height: number
  heightOfMovementPart: number
}) => {
  return (
    <PDFViewer style={styles.viewer} showToolbar={false}>
      <Document>
        <Page style={styles.page} size={{ width: 550, height: 550 }}>
          {height > width && <SingleHungTall width={width} height={height} svgHeight={500} svgWidth={500} heightOfMovementPart={heightOfMovementPart} /> }
          {width > height && <SingleHungWider width={width} height={height} svgHeight={500} svgWidth={500} heightOfMovementPart={heightOfMovementPart} /> }
          {getNumberWithFraction(height) === getNumberWithFraction(width) && <SingleHungSquared width={width} height={height} svgHeight={500} svgWidth={500} heightOfMovementPart={heightOfMovementPart} />}
        </Page>
      </Document>
    </PDFViewer>
  )
}

export default SingleHuntDrawing
