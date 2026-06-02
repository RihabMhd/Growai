import React, { useState } from 'react';
import { 
  Share2, 
  UserPlus, 
  Search, 
  UserX 
} from 'lucide-react';

export default function AffiliatesPage() {
  const [searchQuery, setSearchQuery] = useState('');

  return (
    <div className="main-content" style={{ padding: '28px 32px', background: 'var(--bg-app)' }}>
      
      {/* Top Header Layer */}
      <div className="page-header" style={{ marginBottom: '24px' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '4px' }}>
            <div className="products-title-icon" style={{ width: '32px', height: '32px' }}>
              <Share2 size={16} style={{ color: 'var(--purple)' }} />
            </div>
            <h1 className="products-title" style={{ margin: 0 }}>Affiliates</h1>
          </div>
          <p className="products-subtitle" style={{ margin: 0 }}>Manage affiliate accounts and track performance</p>
        </div>
        
        {/* Top-Right Invite Action Trigger */}
        <button 
          className="btn-primary-action" 
          style={{ 
            backgroundColor: 'var(--purple)', 
            borderRadius: '8px',
            padding: '9px 16px',
            fontSize: '13px',
            display: 'flex',
            alignItems: 'center',
            gap: '8px'
          }}
        >
          <UserPlus size={14} strokeWidth={2.5} />
          Invite Affiliate
        </button>
      </div>

      {/* Full Width Table Search Input Box Section */}
      <div className="search-filter-bar" style={{ marginBottom: '24px' }}>
        <div className="search-input-wrapper" style={{ width: '100%' }}>
          <Search className="search-icon" size={15} style={{ left: '14px' }} />
          <input
            type="text"
            className="search-input"
            placeholder="Search by name, email, or match value..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            style={{ 
              paddingLeft: '40px', 
              height: '42px', 
              borderRadius: '8px',
              fontSize: '13px'
            }}
          />
        </div>
      </div>

      {/* Styled Empty State Module Content */}
      <div className="empty-state-container" style={{ 
        background: 'var(--bg-card)', 
        border: '1px solid var(--border-color)', 
        borderRadius: '12px',
        padding: '100px 40px',
        marginTop: '16px'
      }}>
        {/* Gray Circle Avatar with Cross Slash Emblem Placeholder */}
        <div className="empty-state-icon" style={{ 
          width: '56px', 
          height: '56px', 
          borderRadius: '50%', 
          backgroundColor: 'var(--bg-app)',
          marginBottom: '16px',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center'
        }}>
          <UserX size={22} style={{ color: 'var(--text-muted)', opacity: 0.7 }} />
        </div>

        {/* Informative Missing Record Warning Message */}
        <p className="empty-state-text" style={{ 
          color: 'var(--text-muted)', 
          fontSize: '14px', 
          fontWeight: '400',
          margin: 0,
          letterSpacing: '0.1px'
        }}>
          No affiliates yet — invite one to start tracking attributed orders
        </p>
      </div>

    </div>
  );
}