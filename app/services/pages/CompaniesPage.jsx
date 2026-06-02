import React, { useState } from 'react';
import { 
  Building2, 
  Search, 
  Plus, 
  Layers 
} from 'lucide-react';
import ConnectCompanyModal from '../components/ConnectCompanyModal';

const DELIVERY_COMPANIES = [
  { id: 'ameex', name: 'AMEEX', logoColor: '#e11d48', textColor: '#ffffff', initial: 'A' },
  { id: 'cathedis', name: 'CATHEDIS', logoColor: '#b91c1c', textColor: '#ffffff', initial: 'C' },
  { id: 'chrono-diali', name: 'CHRONO DIALI', logoColor: '#0284c7', textColor: '#ffffff', initial: 'CD' },
  { id: 'sendit', name: 'SENDIT', logoColor: '#4f46e5', textColor: '#ffffff', initial: 'S' },
  { id: 'ozon-express', name: 'OZON EXPRESS', logoColor: '#eab308', textColor: '#000000', initial: 'OE' }
];

export default function CompaniesPage() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCompany, setSelectedCompany] = useState(null);
  const [showConnectModal, setShowConnectModal] = useState(false);

  const filteredCompanies = DELIVERY_COMPANIES.filter(company =>
    company.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleConnectClick = (company) => {
    setSelectedCompany(company);
    setShowConnectModal(true);
  };

  const handleCloseModal = () => {
    setShowConnectModal(false);
    setSelectedCompany(null);
  };

  const handleConnectSuccess = () => {
    // TODO: Refresh companies list or show toast
    console.log('Company connected successfully');
  };

  return (
    <div className="main-content" style={{ padding: '28px 32px', background: 'var(--bg-app)' }}>
      
      {/* Top Header Layer */}
      <div className="page-header" style={{ marginBottom: '24px' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '4px' }}>
            <div className="products-title-icon" style={{ width: '32px', height: '32px' }}>
              <Building2 size={16} style={{ color: 'var(--text-main)' }} />
            </div>
            <h1 className="products-title" style={{ margin: 0 }}>Delivery Companies</h1>
          </div>
          <p className="products-subtitle" style={{ margin: 0 }}>Connect your delivery partners to start shipping</p>
          <p className="products-subtitle" style={{ margin: '4px 0 0 0', fontSize: '11px', color: 'var(--text-muted)' }}>
            Showing carriers for your market: Morocco (change country under Settings → Business)
          </p>
        </div>
      </div>

      {/* Mini Controls Row */}
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
        {/* Category Pill Tag */}
        <div style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: '6px',
          backgroundColor: 'var(--bg-card)',
          border: '1px solid var(--border-color)',
          padding: '6px 12px',
          borderRadius: '8px',
          fontSize: '12px',
          fontWeight: '500',
          color: 'var(--text-main)'
        }}>
          <Layers size={13} style={{ color: 'var(--text-muted)' }} />
          <span>Available</span>
          <span style={{ 
            fontSize: '11px', 
            color: 'var(--text-muted)', 
            marginLeft: '4px',
            backgroundColor: 'var(--bg-app)',
            padding: '1px 6px',
            borderRadius: '4px'
          }}>{filteredCompanies.length} companies</span>
        </div>

        {/* Floating Right Search field */}
        <div className="search-input-wrapper" style={{ width: '260px', flex: 'none' }}>
          <Search className="search-icon" size={14} style={{ left: '12px' }} />
          <input
            type="text"
            className="search-input"
            placeholder="Search companies..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={{ paddingLeft: '34px', height: '34px', borderRadius: '8px' }}
          />
        </div>
      </div>

      {/* Grid Layout matching the Product Grid viewports */}
      <div className="products-grid" style={{ 
        display: 'grid', 
        gridTemplateColumns: 'repeat(4, 1fr)', 
        gap: '20px' 
      }}>
        {filteredCompanies.map((company) => (
          <div 
            key={company.id} 
            className="product-card" 
            style={{ 
              padding: '24px 20px', 
              display: 'flex', 
              flexDirection: 'column', 
              alignItems: 'center', 
              justifyContent: 'center',
              borderRadius: '12px',
              textAlign: 'center'
            }}
          >
            {/* Minimalistic Vector Logo Box replacement */}
            <div style={{
              width: '80px',
              height: '80px',
              borderRadius: '16px',
              backgroundColor: company.logoColor,
              color: company.textColor,
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              fontSize: '22px',
              fontWeight: '700',
              marginBottom: '20px',
              boxShadow: '0 4px 10px rgba(0,0,0,0.05)',
              letterSpacing: '0.5px'
            }}>
              {company.initial}
            </div>

            {/* Carrier Identity Title */}
            <h3 style={{ 
              fontSize: '14px', 
              fontWeight: '700', 
              color: 'var(--text-main)', 
              margin: '0 0 20px 0',
              letterSpacing: '0.3px'
            }}>
              {company.name}
            </h3>

            {/* Solid Action Execution Bar Button */}
            <button
              onClick={() => handleConnectClick(company)}
              style={{
                width: '100%',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                gap: '6px',
                backgroundColor: '#18181b',
                color: '#ffffff',
                border: 'none',
                borderRadius: '8px',
                padding: '10px 14px',
                fontSize: '13px',
                fontWeight: '600',
                cursor: 'pointer',
                transition: 'background-color 0.15s'
              }}
              onMouseEnter={(e) => e.currentTarget.style.backgroundColor = '#27272a'}
              onMouseLeave={(e) => e.currentTarget.style.backgroundColor = '#18181b'}
            >
              <Plus size={14} strokeWidth={2.5} />
              Connect
            </button>
          </div>
        ))}
      </div>

      {/* Connect Modal */}
      {showConnectModal && selectedCompany && (
        <ConnectCompanyModal
          company={selectedCompany}
          onClose={handleCloseModal}
          onSuccess={handleConnectSuccess}
        />
      )}
    </div>
  );
}