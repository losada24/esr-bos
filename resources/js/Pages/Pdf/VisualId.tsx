import { Image } from '@react-pdf/renderer'
import { createTw } from 'react-pdf-tailwind'

import image1 from '../../../assets/images/visual_ids/1.jpg'
import image2 from '../../../assets/images/visual_ids/2.jpg'
import image3 from '../../../assets/images/visual_ids/3.jpg'
import image4 from '../../../assets/images/visual_ids/4.jpg'
import image5 from '../../../assets/images/visual_ids/5.jpg'
import image6 from '../../../assets/images/visual_ids/6.jpg'
import image7 from '../../../assets/images/visual_ids/7.jpg'
import image8 from '../../../assets/images/visual_ids/8.jpg'
import image9 from '../../../assets/images/visual_ids/9.jpg'
import image10 from '../../../assets/images/visual_ids/10.jpg'
import image11 from '../../../assets/images/visual_ids/11.jpg'
import image12 from '../../../assets/images/visual_ids/12.jpg'
import image13 from '../../../assets/images/visual_ids/13.jpg'
import image14 from '../../../assets/images/visual_ids/14.jpg'
import image15 from '../../../assets/images/visual_ids/15.jpg'
import image16 from '../../../assets/images/visual_ids/16.jpg'
import image17 from '../../../assets/images/visual_ids/17.jpg'
import image18 from '../../../assets/images/visual_ids/18.jpg'
import image19 from '../../../assets/images/visual_ids/19.jpg'
import image20 from '../../../assets/images/visual_ids/20.jpg'
import image21 from '../../../assets/images/visual_ids/21.jpg'
import image22 from '../../../assets/images/visual_ids/22.jpg'
import image23 from '../../../assets/images/visual_ids/23.jpg'
import image24 from '../../../assets/images/visual_ids/24.jpg'
import image25 from '../../../assets/images/visual_ids/25.jpg'
import image26 from '../../../assets/images/visual_ids/26.jpg'
import image27 from '../../../assets/images/visual_ids/27.jpg'
import image28 from '../../../assets/images/visual_ids/28.jpg'
import image29 from '../../../assets/images/visual_ids/29.jpg'
import image30 from '../../../assets/images/visual_ids/30.jpg'
import image31 from '../../../assets/images/visual_ids/31.jpg'

const tw = createTw({
  theme: {
    extend: { }
  }
})

const images: string[] = [
  image1,
  image2,
  image3,
  image4,
  image5,
  image6,
  image7,
  image8,
  image9,
  image10,
  image11,
  image12,
  image13,
  image14,
  image15,
  image16,
  image17,
  image18,
  image19,
  image20,
  image21,
  image22,
  image23,
  image24,
  image25,
  image26,
  image27,
  image28,
  image29,
  image30,
  image31
]

const VisualId = ({ index }: { index: number }) => {
  return (
    <Image src={images[index]} style={tw('w-8 h-8')} />
  )
}

export default VisualId
