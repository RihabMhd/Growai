export default function PlaceholderPage({ title }) {
  return (
    <div style={{ height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', color: 'var(--text-muted)' }}>
      <svg style={{ width: '64px', height: '64px', marginBottom: '20px', opacity: 0.2 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
        <line x1="3" y1="9" x2="21" y2="9"></line>
        <line x1="9" y1="21" x2="9" y2="9"></line>
      </svg>
      <h2 style={{ fontSize: '1.5rem', fontWeight: 600, color: 'var(--text-main)' }}>{title}</h2>
      <p style={{ marginTop: '10px' }}>This page is under construction.</p>
    </div>
  );
}
