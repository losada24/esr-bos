const InfoIcon = () => {
  return (
    <svg
      width="18"
      height="18"
      viewBox="0 0 18 18"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <defs>
        <linearGradient id="infoGradient" x1="3" y1="3" x2="15" y2="15" gradientUnits="userSpaceOnUse">
          <stop stopColor="#38bdf8" />
          <stop offset="1" stopColor="#6366f1" />
        </linearGradient>
      </defs>
      <circle cx="9" cy="9" r="8" fill="url(#infoGradient)" opacity="0.2" />
      <circle
        cx="9"
        cy="9"
        r="6.75"
        stroke="url(#infoGradient)"
        strokeWidth="1.5"
      />
      <path
        d="M9 12.5V8.25"
        stroke="url(#infoGradient)"
        strokeWidth="1.5"
        strokeLinecap="round"
      />
      <circle cx="9" cy="5.75" r="0.75" fill="url(#infoGradient)" />
    </svg>
  )
}

export default InfoIcon
