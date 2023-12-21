import React from 'react'
import PrintLayout from '@/Layouts/PrintLayout'
import { Head } from '@inertiajs/react'
import { Page, Text, View, Image } from '@react-pdf/renderer'
import { type PageProps } from '@/types'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { createTw } from 'react-pdf-tailwind'

type IndexOrderProps = PageProps & {
  data: []
}

const tw = createTw({
  theme: {
    extend: {
      colors: {
        custom: '#bada55'
      }
    }
  }
})

const WorkOrder = ({ data, auth }: IndexOrderProps) => {
  console.log(data)
  return (
    <AuthenticatedLayout
          auth={auth}
          pageTitle='Work Order'
      >
        <Head title="Work Order" />
        <PrintLayout>
          <Page size="A4" style={tw('p-12')}>
            <View style={tw('p-20 bg-gray-100')}>
              <Text style={tw('text-custom text-3xl')}>Section #1</Text>
            </View>
            <View style={tw('mt-12 px-8 rotate-2')}>
              <Text style={tw('text-amber-600 text-2xl')}>Section #2</Text>
            </View>
          </Page>
        </PrintLayout>
    </AuthenticatedLayout>
  )
}

export default WorkOrder
