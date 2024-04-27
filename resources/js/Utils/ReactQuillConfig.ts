export const textFormats = [
  'font',
  'header',
  'size',
  'bold',
  'italic',
  'underline',
  'strike',
  'blockquote',
  'background',
  'color',
  'script',
  'list',
  'bullet',
  'link',
  'image',
  'video',
  'clean',
  'code-block',
  'indent',
  'list',
  'align'
]

export const modules = {
  toolbar: [
    ['bold', 'italic', 'underline', 'strike'],
    [{ color: [] }],
    [{ align: [] }],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ script: 'sub' }, { script: 'super' }]
  ],
  clipboard: {
    matchVisual: false
  },
  syntax: false
}
