export interface Tasks {
  id: number
  title: string
  // description: string
  date: string
  date_edited: string
  tags: string[]
  // status?: string
  // name: user
  // precio: number
}

export interface Pipelines {
  id: number
  title: string
  client_id: number
  tasks: Tasks[]
}
