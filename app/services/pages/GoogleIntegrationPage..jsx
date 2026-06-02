import React from 'react';
import { 
  CheckCircle2, 
  Mail, 
  Calendar, 
  FileText, 
  ExternalLink 
} from 'lucide-react';

export default function GoogleIntegrationPage() {
  return (
    <div className="main-content" style={{ padding: '28px 32px', background: 'var(--bg-app)' }}>
      
      {/* Platform Header Section */}
      <div style={{ display: 'flex', alignItems: 'center', gap: '16px', marginBottom: '24px' }}>
        {/* Google Vector Icon Container */}
        <div style={{
          width: '54px',
          height: '54px',
          borderRadius: '12px',
          backgroundColor: '#ffffff',
          border: '1px solid var(--border-color)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          boxShadow: '0 2px 8px rgba(0,0,0,0.04)',
          flexShrink: 0
        }}>
          <svg width="28" height="28" viewBox="0 0 48 48">
            <path fill="#EA4335" d="M24 9.5c3.2 0 6.1 1.2 8.3 3.3l6.2-6.2C34.3 2.6 29.5 0 24 0 14.7 0 6.8 5.4 2.8 13.5l7.2 5.6C12.1 12.3 17.5 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.2-.4-4.7H24v9.1h12.9c-.6 3.1-2.4 5.7-5 7.4l7.1 5.5c4.1-3.8 6.5-9.4 6.5-17.3z"/>
            <path fill="#FBBC05" d="M10.1 28.5c-1.3-3.8-1.3-7.9 0-11.7l-7.2-5.6c-2.7 5.4-2.7 11.8 0 17.2l7.2-5.6z"/>
            <path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.1-5.5c-2.1 1.4-4.8 2.2-8.8 2.2-6.5 0-11.9-4.4-13.8-10.4l-7.2 5.6C6.8 42.6 14.7 48 24 48z"/>
          </svg>
        </div>

        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '4px' }}>
            <h1 className="products-title" style={{ margin: 0, fontSize: '22px', fontWeight: '700' }}>Google Account</h1>
            <span style={{
              fontSize: '11px',
              fontWeight: '600',
              color: 'var(--text-muted)',
              backgroundColor: 'rgba(0, 0, 0, 0.05)',
              padding: '3px 8px',
              borderRadius: '12px',
              border: '1px solid var(--border-color)',
              letterSpacing: '0.2px'
            }}>
              Not connected
            </span>
          </div>
          <p className="products-subtitle" style={{ margin: 0, color: 'var(--text-muted)' }}>
            Connect your Google account to access Sheets, Drive and more
          </p>
        </div>
      </div>

      {/* Main Connection Setup Card */}
      <div className="card" style={{ padding: '0px', borderRadius: '12px', overflow: 'hidden', marginBottom: '24px' }}>
        
        <div style={{ 
          padding: '20px 24px', 
          borderBottom: '1px solid var(--border-color)',
          display: 'flex',
          alignItems: 'center',
          gap: '12px'
        }}>
          <div style={{
            width: '32px',
            height: '32px',
            borderRadius: '8px',
            backgroundColor: '#f1f3f4',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
          }}>
            <Mail size={16} color="#5f6368" />
          </div>
          <div>
            <h3 style={{ margin: 0, fontSize: '14px', fontWeight: '600', color: 'var(--text-main)' }}>Connect Google Account</h3>
            <p style={{ margin: '2px 0 0 0', fontSize: '12px', color: 'var(--text-muted)' }}>Secure access to Google Services</p>
          </div>
        </div>

        <div style={{ padding: '24px' }}>
          <button
            style={{
              width: '100%',
              backgroundColor: '#ffffff',
              color: '#3c4043',
              border: '1px solid var(--border-color)',
              borderRadius: '8px',
              padding: '12px 20px',
              fontSize: '14px',
              fontWeight: '600',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '10px',
              cursor: 'pointer',
              transition: 'background-color 0.15s'
            }}
            onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#f8f9fa'}
            onMouseLeave={(e) => e.currentTarget.style.backgroundColor = '#ffffff'}
          >
            {/* Simple G-Icon */}
            <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Sign in with Google
          </button>
        </div>
      </div>

      {/* Integration Capabilities */}
      <div style={{ display: 'flex', flexWrap: 'wrap', gap: '12px' }}>
        {[
          { label: 'Google Sheets Sync', icon: <FileText size={13} /> },
          { label: 'Calendar Events', icon: <Calendar size={13} /> },
          { label: 'Docs Integration', icon: <ExternalLink size={13} /> }
        ].map((feature, idx) => (
          <div key={idx} style={{
            display: 'inline-flex',
            alignItems: 'center',
            gap: '8px',
            backgroundColor: 'var(--bg-card)',
            border: '1px solid var(--border-color)',
            padding: '8px 14px',
            borderRadius: '20px',
            fontSize: '12px',
            fontWeight: '500',
            color: 'var(--text-main)'
          }}>
            <CheckCircle2 size={13} style={{ color: 'var(--success)' }} />
            {feature.icon}
            {feature.label}
          </div>
        ))}
      </div>
    </div>
  );
}