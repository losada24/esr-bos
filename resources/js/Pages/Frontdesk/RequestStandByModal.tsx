import React, { useEffect, useState } from 'react'
import CloseIcon from '@/Components/Icons/CloseIcon'
import PrimaryButton from '@/Components/PrimaryButton'
import InputError from '@/Components/InputError'
import { router } from '@inertiajs/react'
import { type Tasks } from '@/types/interfaces/pipelines'
import { type Pipelines } from '@/types'

interface RequestStandByModalProps {
  task: Tasks | null
  showModal: boolean
  previousStatusId: string | null
  onClose: () => void
  setProjectList: React.Dispatch<React.SetStateAction<Pipelines[]>>
}

const RequestStandByModal = ({
  task,
  showModal,
  previousStatusId,
  onClose,
  setProjectList
}: RequestStandByModalProps) => {
  const [note, setNote] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (showModal) {
      setNote('')
      setError(null)
    }
  }, [showModal, task?.id])

  const restoreTask = () => {
    if (task && previousStatusId) {
      setProjectList(prev =>
        prev.map(pipeline => {
          if (pipeline.id.toString() === previousStatusId) {
            const exists = pipeline.tasks.some(t => t.id === task.id)
            if (!exists) {
              const nextTotal = (pipeline.total_tasks ?? pipeline.tasks.length) + 1
              return { ...pipeline, tasks: [...pipeline.tasks, task], total_tasks: nextTotal }
            }
          }
          return pipeline
        })
      )
    }
  }

  const handleClose = () => {
    restoreTask()
    onClose()
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault()
    if (!task?.id) {
      setError('No order selected.')
      return
    }
    if (!note.trim()) {
      setError('The note is required.')
      return
    }
    setSubmitting(true)
    setError(null)

    try {
      const response = await fetch(route('frontdesk.updateStatusStandBy', { order: task.id }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ note })
      })

      if (!response.ok) {
        const payload = await response.json().catch(() => null)
        if (payload?.errors?.note?.length) {
          setError(payload.errors.note[0])
        } else {
          setError(payload?.message ?? 'No se pudo guardar la nota.')
        }
        return
      }

      await response.json().catch(() => null)
      router.visit(route('frontdesk.index'))
    } catch (err: any) {
      setError(err?.message ?? 'No se pudo guardar la nota.')
    } finally {
      setSubmitting(false)
    }
  }

  if (!showModal) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold text-slate-800">Request Stand By</h3>
          <button
            type="button"
            className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
            onClick={handleClose}
          >
            <CloseIcon />
          </button>
        </div>

        <form className="space-y-4" onSubmit={handleSubmit}>
          <div>
            <label htmlFor="standby-note" className="mb-1 block text-sm font-medium text-slate-600">
              Note <span className="text-red-500">*</span>
            </label>
            <textarea
              id="standby-note"
              className="form-textarea w-full resize-none placeholder:text-slate-400"
              rows={4}
              value={note}
              onChange={(event) => {
                setNote(event.target.value)
                if (error) setError(null)
              }}
              placeholder="Describe why the order is going to Request Stand By"
              required
            />
            {error && <InputError message={error} className="mt-2" />}
          </div>

          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={handleClose}
              className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
              disabled={submitting}
            >
              Cancel
            </button>
            <PrimaryButton
              type="submit"
              disabled={submitting}
              className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
            >
              {submitting ? 'Saving...' : 'Save Note'}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  )
}

export default RequestStandByModal
