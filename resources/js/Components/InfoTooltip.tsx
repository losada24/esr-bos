import React, { useEffect, useLayoutEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import InfoIcon from './Icons/InfoIcon'

export interface InfoTooltipProps {
  content: React.ReactNode
  side?: 'top' | 'right' | 'bottom' | 'left' // preferencia
  offset?: number
  className?: string
  width?: number // ancho máx. (no fijo)
}

export default function InfoTooltip ({
  content,
  side = 'top',
  offset = 10,
  width = 260,
  className = ''
}: InfoTooltipProps) {
  const [open, setOpen] = useState(false)
  const [coords, setCoords] = useState<{ top: number, left: number } | null>(null)
  const [realSide, setRealSide] = useState<typeof side>(side)
  const btnRef = useRef<HTMLButtonElement | null>(null)
  const tipRef = useRef<HTMLDivElement | null>(null)

  // calcular posición con auto-flip y clamp a viewport
  const computePosition = () => {
    if (!btnRef.current || !tipRef.current) return
    const br = btnRef.current.getBoundingClientRect()
    const tr = tipRef.current.getBoundingClientRect()
    const vw = window.innerWidth
    const vh = window.innerHeight

    let s: typeof side = side
    if (side === 'right' && br.right + tr.width + offset > vw) s = 'left'
    if (side === 'left' && br.left - tr.width - offset < 0) s = 'right'
    if (side === 'top' && br.top - tr.height - offset < 0) s = 'bottom'
    if (side === 'bottom' && br.bottom + tr.height + offset > vh) s = 'top'
    setRealSide(s)

    let top = 0
    let left = 0
    switch (s) {
      case 'top':
        top = br.top - tr.height - offset
        left = br.left + br.width / 2 - tr.width / 2
        break
      case 'right':
        top = br.top + br.height / 2 - tr.height / 2
        left = br.right + offset
        break
      case 'bottom':
        top = br.bottom + offset
        left = br.left + br.width / 2 - tr.width / 2
        break
      case 'left':
        top = br.top + br.height / 2 - tr.height / 2
        left = br.left - tr.width - offset
        break
    }

    const pad = 8 // margen a bordes
    left = Math.max(pad, Math.min(left, vw - tr.width - pad))
    top = Math.max(pad, Math.min(top, vh - tr.height - pad))
    setCoords({ top, left })
  }

  useLayoutEffect(() => { if (open) computePosition() }, [open, side, width])

  // reposicionar en scroll/resize (captura scroll de contenedores)
  useEffect(() => {
    if (!open) return
    const onScroll = () => { computePosition() }
    const onResize = () => { computePosition() }
    window.addEventListener('scroll', onScroll, true)
    window.addEventListener('resize', onResize)
    return () => {
      window.removeEventListener('scroll', onScroll, true)
      window.removeEventListener('resize', onResize)
    }
  }, [open])

  // cerrar con click afuera / ESC
  useEffect(() => {
    const onDocClick = (e: MouseEvent) => {
      if (!open) return
      const t = e.target as Node
      if (!btnRef.current?.contains(t) && !tipRef.current?.contains(t)) setOpen(false)
    }
    const onEsc = (e: KeyboardEvent) => { if (e.key === 'Escape') setOpen(false) }
    document.addEventListener('mousedown', onDocClick)
    document.addEventListener('keydown', onEsc)
    return () => {
      document.removeEventListener('mousedown', onDocClick)
      document.removeEventListener('keydown', onEsc)
    }
  }, [open])

  const tooltipBg = 'rgba(255, 255, 255, 0.96)'
  const tooltipBorder = 'rgba(148, 163, 184, 0.45)'

  // flecha según lado real
  const arrowStyle: React.CSSProperties = {
    position: 'absolute',
    width: 12,
    height: 12,
    background: tooltipBg,
    border: `1px solid ${tooltipBorder}`,
    boxShadow: '0 5px 18px rgba(15, 23, 42, 0.15)',
    transform: 'rotate(45deg)',
    ...(realSide === 'top' && { top: '100%', left: '50%', marginLeft: -6 }),
    ...(realSide === 'bottom' && { bottom: '100%', left: '50%', marginLeft: -6 }),
    ...(realSide === 'left' && { left: '100%', top: '50%', marginTop: -6 }),
    ...(realSide === 'right' && { right: '100%', top: '50%', marginTop: -6 })
  }

  return (
    <span style={{ position: 'relative', display: 'inline-flex', alignItems: 'center' }}>
      <button
        ref={btnRef}
        type="button"
        aria-label="Información"
        aria-expanded={open}
        onClick={() => { setOpen(v => !v) }}
        onMouseEnter={() => { setOpen(true) }}
        onMouseLeave={() => { setOpen(false) }}
        className={['inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-slate-100 text-slate-500 transition-all duration-200 hover:scale-105 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-500 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-sky-400', className].join(' ')}
        style={{ cursor: 'pointer' }}
      >
        <InfoIcon />
      </button>

      {/* Tooltip montado en el body para evitar clipping por overflow */}
      {open && createPortal(
        <div
          ref={tipRef}
          role="dialog"
          onMouseEnter={() => { setOpen(true) }}
          onMouseLeave={() => { setOpen(false) }}
          style={{
            position: 'fixed',
            top: coords?.top ?? -9999,
            left: coords?.left ?? -9999,
            zIndex: 9999,
            maxWidth: width,
            background: tooltipBg,
            color: '#0f172a',
            fontSize: 13,
            lineHeight: 1.6,
            padding: '12px 16px',
            borderRadius: 12,
            border: `1px solid ${tooltipBorder}`,
            boxShadow: '0 15px 45px rgba(15, 23, 42, 0.15)',
            wordBreak: 'break-word',
            whiteSpace: 'normal',
            backdropFilter: 'blur(4px)',
            transition: 'opacity 150ms ease, transform 150ms ease'
          }}
        >
          {content}
          <span aria-hidden="true" style={arrowStyle} />
        </div>,
        document.body
      )}
    </span>
  ) 
}
