import React, { useState, useEffect, useRef } from 'react';
import {
  Users,
  RefreshCw,
  Search,
  History,
  Crown,
  DollarSign,
  ArrowUpDown,
  Phone,
  MessageCircle,
  Loader2,
} from 'lucide-react';
import api from '../api/axios';

const TABS = [
  { id: 'recent',       label: 'Recent',       icon: <History size={13} /> },
  { id: 'most_orders',  label: 'Most Orders',  icon: <Crown size={13} /> },
  { id: 'top_spenders', label: 'Top Spenders', icon: <DollarSign size={13} /> },
  { id: 'name_az',      label: 'Name A-Z',     icon: <ArrowUpDown size={13} /> },
];

const DEFAULT_METRICS = { total_clients: 0, total_orders: 0, total_revenue: '0.00 MAD' };

export default function ClientsPage() {
  const [activeTab,   setActiveTab]   = useState('recent');
  const [searchQuery, setSearchQuery] = useState('');
  const [clients,     setClients]     = useState([]);
  const [metrics,     setMetrics]     = useState(DEFAULT_METRICS);
  const [loading,     setLoading]     = useState(true);
  const [syncing,     setSyncing]     = useState(false);
  const [error,       setError]       = useState(null);

  const searchTimer = useRef(null);

  // ── Core fetch ────────────────────────────────────────────────────────────
  const fetchClients = async (sort, search) => {
    try {
      setLoading(true);
      setError(null);

      const params = { sort };
      if (search && search.trim()) params.search = search.trim();

      const res = await api.get('/clients', { params });
      const body = res.data;

      // Accept both { success, data, metrics } and a bare array
      if (Array.isArray(body)) {
        setClients(body);
      } else if (Array.isArray(body?.data)) {
        setClients(body.data);
        if (body.metrics) setMetrics(body.metrics);
      } else {
        // Unexpected shape — log it so we can see in console
        console.warn('[ClientsPage] Unexpected API shape:', body);
        setClients([]);
      }
    } catch (err) {
      console.error('[ClientsPage] fetch error:', err);
      const serverMsg = err?.response?.data?.error || err?.response?.data?.message;
      setError(serverMsg || 'Failed to load clients.');
    } finally {
      setLoading(false);
    }
  };

  // ── On mount ──────────────────────────────────────────────────────────────
  useEffect(() => {
    fetchClients('recent', '');
  }, []);

  // ── Tab changes — immediate ───────────────────────────────────────────────
  useEffect(() => {
    fetchClients(activeTab, searchQuery);
  }, [activeTab]);

  // ── Search — debounced 350ms ──────────────────────────────────────────────
  useEffect(() => {
    clearTimeout(searchTimer.current);
    searchTimer.current = setTimeout(() => {
      fetchClients(activeTab, searchQuery);
    }, 350);
    return () => clearTimeout(searchTimer.current);
  }, [searchQuery]);

  const handleSync = async () => {
    setSyncing(true);
    await fetchClients(activeTab, searchQuery);
    setSyncing(false);
  };

  // ── Render ────────────────────────────────────────────────────────────────
  return (
    <div className="main-content" style={{ background: 'var(--bg-app)' }}>

      {/* Header */}
      <div className="page-header" style={{ marginBottom: '12px' }}>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '2px' }}>
            <Users size={16} style={{ color: '#2d2d2d' }} />
            <h1 style={{ margin: 0, fontSize: '1.05rem', fontWeight: '700' }}>Clients</h1>
          </div>
          <p style={{ margin: 0, fontSize: '0.75rem', color: 'var(--text-muted)' }}>
            Manage and view your registered consumer base
          </p>
        </div>

        <button
          onClick={handleSync}
          disabled={syncing || loading}
          className="btn-primary-action"
          style={{
            backgroundColor: 'var(--purple)', borderRadius: '6px',
            padding: '6px 12px', fontSize: '12px', border: 'none', color: 'white',
            opacity: syncing || loading ? 0.7 : 1,
            cursor: syncing || loading ? 'not-allowed' : 'pointer',
            display: 'flex', alignItems: 'center', gap: '6px',
          }}
        >
          {syncing
            ? <Loader2 size={11} style={{ animation: 'spin 1s linear infinite' }} />
            : <RefreshCw size={11} />}
          Sync from Orders
        </button>
      </div>

      {/* Metric Cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3,1fr)', gap: '10px', marginBottom: '12px' }}>
        {[
          { label: 'TOTAL CLIENTS', value: metrics.total_clients, bg: '#e1e9ff', border: '#6993ff' },
          { label: 'TOTAL ORDERS',  value: metrics.total_orders,  bg: '#c9f7f5', border: '#1bc5bd' },
          { label: 'TOTAL REVENUE', value: metrics.total_revenue, bg: '#fff4de', border: '#ffa800' },
        ].map((card) => (
          <div className="card" key={card.label}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
              <span style={{
                width: '28px', height: '28px', borderRadius: '50%',
                background: card.bg, border: `2px solid ${card.border}`, flexShrink: 0,
              }} />
              <span className="card-title">{card.label}</span>
            </div>
            <div className="card-value">{card.value}</div>
          </div>
        ))}
      </div>

      {/* Tabs */}
      <div style={{ display: 'flex', gap: '16px', borderBottom: '1px solid var(--border-color)', marginBottom: '12px' }}>
        {TABS.map((tab) => {
          const active = activeTab === tab.id;
          return (
            <button key={tab.id} onClick={() => setActiveTab(tab.id)} style={{
              display: 'flex', alignItems: 'center', gap: '6px',
              padding: '8px 0', background: 'none', border: 'none', cursor: 'pointer',
              fontSize: '12px', fontWeight: active ? '700' : '500',
              color: active ? 'var(--text-main)' : 'var(--text-muted)',
              borderBottom: active ? '2px solid var(--text-main)' : '2px solid transparent',
              marginBottom: '-1px', transition: 'all 0.15s',
            }}>
              {tab.icon} {tab.label}
            </button>
          );
        })}
      </div>

      {/* Search */}
      <div className="search-filter-bar" style={{ marginBottom: '10px' }}>
        <div className="search-input-wrapper">
          <Search className="search-icon" size={15} style={{ left: '12px' }} />
          <input
            type="text"
            className="search-input"
            placeholder="Search by name, phone, email or city..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            style={{ paddingLeft: '32px', height: '34px', borderRadius: '6px', fontSize: '13px' }}
          />
        </div>
      </div>

      {/* Table */}
      <div className="card" style={{ padding: 0, borderRadius: '8px', overflow: 'hidden' }}>
        <style>{`@keyframes spin { to { transform: rotate(360deg); } }`}</style>

        {loading ? (
          <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', padding: '60px 0', gap: '10px', color: 'var(--text-muted)', fontSize: '0.85rem' }}>
            <Loader2 size={20} style={{ animation: 'spin 1s linear infinite' }} />
            Loading clients...
          </div>

        ) : error ? (
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '60px 20px', color: '#f1416c', fontSize: '0.85rem', gap: '12px' }}>
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
              <circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <span>{error}</span>
            <button onClick={() => fetchClients(activeTab, searchQuery)} style={{
              padding: '6px 16px', borderRadius: '6px', background: 'rgba(241,65,108,0.1)',
              border: '1px solid rgba(241,65,108,0.3)', color: '#f1416c', fontSize: '0.8rem', cursor: 'pointer',
            }}>
              Retry
            </button>
          </div>

        ) : clients.length === 0 ? (
          <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '80px 20px', color: 'var(--text-muted)', gap: '12px' }}>
            <Users size={48} style={{ opacity: 0.12 }} />
            <p style={{ margin: 0, fontWeight: '500' }}>
              {searchQuery ? 'No clients match your search.' : 'No clients yet — they appear automatically when orders are created.'}
            </p>
          </div>

        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table className="products-table">
              <thead>
                <tr>
                  <th style={{ paddingLeft: '20px', width: '25%' }}>CLIENT</th>
                  <th style={{ width: '15%' }}>PHONE</th>
                  <th style={{ width: '20%' }}>LOCATION</th>
                  <th style={{ width: '10%', textAlign: 'center' }}>ORDERS</th>
                  <th style={{ width: '12%' }}>TOTAL SPENT</th>
                  <th style={{ width: '13%' }}>LAST ORDER</th>
                  <th style={{ width: '10%', textAlign: 'right', paddingRight: '20px' }}>ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                {clients.map((client) => (
                  <tr key={client.id}>
                    <td style={{ paddingLeft: '20px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <div style={{ width: '28px', height: '28px', borderRadius: '50%', backgroundColor: '#c1272d', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#006233" strokeWidth="3">
                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" />
                          </svg>
                        </div>
                        <div>
                          <div className="cell-text-strong" style={{ fontSize: '13px' }}>{client.name}</div>
                          <div className="cell-text" style={{ fontSize: '11px', color: 'var(--text-muted)', marginTop: '2px' }}>{client.email || '—'}</div>
                        </div>
                      </div>
                    </td>
                    <td><span className="cell-text">{client.phone || '—'}</span></td>
                    <td>
                      <span className="cell-text" style={{ display: 'block', maxWidth: '220px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {[client.address, client.city].filter(Boolean).join(', ') || '—'}
                      </span>
                    </td>
                    <td style={{ textAlign: 'center' }}>
                      <span style={{ backgroundColor: 'rgba(105,147,255,0.1)', color: 'var(--primary)', padding: '2px 8px', borderRadius: '12px', fontSize: '11px', fontWeight: '600' }}>
                        {client.orders}
                      </span>
                    </td>
                    <td><span className="cell-text-strong">{client.total_spent}</span></td>
                    <td><span className="cell-text">{client.last_order}</span></td>
                    <td style={{ paddingRight: '20px' }}>
                      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: '14px' }}>
                        {client.phone && (
                          <a href={`tel:${client.phone}`} style={{ color: 'var(--text-muted)', display: 'inline-flex', alignItems: 'center' }}>
                            <Phone size={14} />
                          </a>
                        )}
                        {client.phone && (
                          <a href={`https://wa.me/${client.phone}`} target="_blank" rel="noreferrer" style={{ color: '#25D366', display: 'inline-flex', alignItems: 'center' }}>
                            <MessageCircle size={14} fill="#25D366" stroke="none" />
                          </a>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}