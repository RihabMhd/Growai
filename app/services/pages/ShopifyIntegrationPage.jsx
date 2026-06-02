import React, { useState, useEffect, useCallback } from 'react';
import { CheckCircle2, XCircle, AlertCircle, Plus, LogOut, Loader2, Save, RefreshCw, Package, ShoppingCart, BarChart3, Webhook } from 'lucide-react';

// ---------------------------------------------------------------------------
// API helper
// ---------------------------------------------------------------------------
async function apiFetch(path, options = {}) {
  const token = localStorage.getItem('auth_token');
  const res = await fetch(`/api${path}`, {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
    },
    ...options,
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error || err.message || `HTTP ${res.status}`);
  }
  return res.json();
}

// ---------------------------------------------------------------------------
// Toast
// ---------------------------------------------------------------------------
function Toast({ message, type, onDismiss }) {
  useEffect(() => {
    const t = setTimeout(onDismiss, 4000);
    return () => clearTimeout(t);
  }, [onDismiss]);

  const colors = {
    success: { bg: '#EAF3DE', border: '#C0DD97', text: '#3B6D11', Icon: CheckCircle2 },
    error:   { bg: '#FCEBEB', border: '#F7C1C1', text: '#A32D2D', Icon: XCircle },
    info:    { bg: '#E6F1FB', border: '#B5D4F4', text: '#185FA5', Icon: AlertCircle },
  };
  const { bg, border, text, Icon } = colors[type] || colors.info;

  return (
    <div style={{
      position: 'fixed', bottom: 24, right: 24, zIndex: 1000,
      display: 'flex', alignItems: 'center', gap: 10,
      background: bg, border: `1px solid ${border}`, borderRadius: 10,
      padding: '12px 16px', boxShadow: '0 4px 16px rgba(0,0,0,0.10)',
      maxWidth: 340, fontSize: 13, color: text, fontWeight: 500,
      animation: 'slideIn 0.2s ease',
    }}>
      <Icon size={15} style={{ flexShrink: 0 }} />
      {message}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Shopify logo SVG
// ---------------------------------------------------------------------------
function ShopifyLogo({ size = 28 }) {
  return (
    <svg width={size} height={size} viewBox="0 0 109.5 124.5" fill="#95BF47" xmlns="http://www.w3.org/2000/svg">
      <path d="M74.7,14.8c0,0-1.4,0.4-3.7,1.1c-0.4-1.3-1-2.8-1.8-4.4c-2.6-5-6.5-7.7-11.1-7.7c-0.3,0-0.6,0-1,0
        c-0.1-0.2-0.3-0.3-0.4-0.5C54.9,1.2,52.6,0,49.9,0C44.6,0.2,39.4,4,35.3,11c-2.9,4.6-5.1,10.3-5.7,14.8
        c-5.8,1.8-9.9,3.1-10,3.1c-2.9,0.9-3,1-3.4,3.7C16.2,34.7,5,124.5,5,124.5l81,0V14.1C83,14.3,78.7,14.8,74.7,14.8z
        M62.5,18.3c-3.6,1.1-7.5,2.3-11.4,3.5c1.1-4.2,3.2-8.4,5.7-11.2C57.7,9.4,59.4,8,61.4,7.1C63.4,11.3,62.6,16.4,62.5,18.3z
        M58.2,3.9c1.5,0,2.8,0.3,3.9,1.1C59.9,5.9,57.8,7,55.9,9.1c-2.7,2.9-4.8,7.5-5.9,11.9c-3.3,1-6.5,2-9.5,2.9
        C42.5,16.2,49.9,4.2,58.2,3.9z M46.6,55.8c0.4,6.3,17,7.7,18,21.9c0.7,11.3-6,19-15.7,19.6c-11.6,0.7-18-6.1-18-6.1
        l2.5-10.5c0,0,6.4,4.8,11.5,4.5c3.3-0.2,4.5-2.9,4.4-4.9c-0.5-8.2-14-7.7-14.9-20.7c-0.8-11.1,6.6-22.3,22.7-23.3
        c6.2-0.4,9.4,1.2,9.4,1.2l-3.6,13.8c0,0-4.1-1.9-9-1.6C47.5,49.9,46.5,53.2,46.6,55.8z" />
    </svg>
  );
}

// ---------------------------------------------------------------------------
// Single store card
// ---------------------------------------------------------------------------
function StoreCard({ shop, onDisconnect, onSaveBoutiqueName }) {
  const [boutiqueName, setBoutiqueName] = useState(shop.boutique_name || '');
  const [saving, setSaving] = useState(false);

  const handleSave = async () => {
    setSaving(true);
    try {
      await onSaveBoutiqueName(shop.id, boutiqueName);
    } finally {
      setSaving(false);
    }
  };

  const installedDate = shop.created_at
    ? new Date(shop.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })
    : null;

  return (
    <div style={{
      border: '1px solid var(--border-color, #e5e7eb)',
      borderRadius: 12,
      padding: '18px 20px',
      background: 'var(--bg-card, #fff)',
      marginBottom: 12,
    }}>
      {/* Top row: logo + name + status + disconnect */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 10 }}>
        <div style={{
          width: 44, height: 44, borderRadius: 10, background: '#f6fef0',
          border: '1px solid #d5edba', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
        }}>
          <ShopifyLogo size={24} />
        </div>

        <div style={{ flex: 1, minWidth: 0 }}>
          <div style={{ fontWeight: 700, fontSize: 15, color: 'var(--text-main, #111)', marginBottom: 1 }}>
            {shop.name || shop.shopify_domain}
          </div>
          <div style={{ fontSize: 12, color: 'var(--text-muted, #6b7280)' }}>
            {shop.shopify_domain}
          </div>
        </div>

        {/* Active badge */}
        <span style={{
          display: 'inline-flex', alignItems: 'center', gap: 5,
          fontSize: 11, fontWeight: 600, color: '#3B6D11',
          background: '#EAF3DE', padding: '3px 10px', borderRadius: 12,
          border: '1px solid #C0DD97', flexShrink: 0,
        }}>
          <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#65a30d', display: 'inline-block' }} />
          Active
        </span>

        {/* Disconnect button */}
        <button
          onClick={() => onDisconnect(shop.id)}
          title="Disconnect store"
          style={{
            background: 'none', border: '1px solid var(--border-color, #e5e7eb)',
            borderRadius: 8, padding: '6px 8px', cursor: 'pointer',
            display: 'flex', alignItems: 'center', color: 'var(--text-muted, #6b7280)',
            transition: 'all 0.15s', flexShrink: 0,
          }}
          onMouseEnter={e => { e.currentTarget.style.borderColor = '#f7c1c1'; e.currentTarget.style.color = '#a32d2d'; }}
          onMouseLeave={e => { e.currentTarget.style.borderColor = 'var(--border-color, #e5e7eb)'; e.currentTarget.style.color = 'var(--text-muted, #6b7280)'; }}
        >
          <LogOut size={14} />
        </button>
      </div>

      {/* Meta row */}
      {(installedDate || shop.last_synced_at) && (
        <div style={{ display: 'flex', gap: 16, marginBottom: 14, fontSize: 11, color: 'var(--text-muted, #6b7280)' }}>
          {installedDate && (
            <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
              <span>🗓</span> Installed {installedDate}
            </span>
          )}
          {shop.last_synced_at && (
            <span style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
              <span>🔄</span> Auto-sync active
            </span>
          )}
        </div>
      )}

      {/* Boutique name field */}
      <div style={{ marginTop: 6 }}>
        <div style={{ fontSize: 11, color: 'var(--text-muted, #6b7280)', marginBottom: 6, display: 'flex', alignItems: 'center', gap: 6 }}>
          <span>🏷</span>
          <span>Boutique name for WhatsApp messages</span>
          <code style={{ fontSize: 10, background: 'rgba(0,0,0,0.06)', padding: '1px 5px', borderRadius: 4, color: 'var(--text-muted, #6b7280)' }}>
            {'{{boutique_name}}'}
          </code>
        </div>
        <div style={{ display: 'flex', gap: 8 }}>
          <input
            value={boutiqueName}
            onChange={e => setBoutiqueName(e.target.value)}
            placeholder="e.g. Ma Boutique"
            style={{
              flex: 1, padding: '8px 12px', fontSize: 13,
              border: '1px solid var(--border-color, #e5e7eb)', borderRadius: 8,
              background: 'var(--bg-app, #f9fafb)', color: 'var(--text-main, #111)',
              outline: 'none', transition: 'border-color 0.15s',
            }}
            onFocus={e => e.target.style.borderColor = '#65a30d'}
            onBlur={e => e.target.style.borderColor = 'var(--border-color, #e5e7eb)'}
          />
          <button
            onClick={handleSave}
            disabled={saving}
            style={{
              display: 'flex', alignItems: 'center', gap: 6,
              background: saving ? '#a3c76a' : '#65a30d', color: '#fff',
              border: 'none', borderRadius: 8, padding: '8px 16px',
              fontSize: 13, fontWeight: 600, cursor: saving ? 'not-allowed' : 'pointer',
              transition: 'background 0.15s', flexShrink: 0,
            }}
            onMouseEnter={e => !saving && (e.currentTarget.style.background = '#4d7c0f')}
            onMouseLeave={e => !saving && (e.currentTarget.style.background = '#65a30d')}
          >
            {saving ? <Loader2 size={13} style={{ animation: 'spin 1s linear infinite' }} /> : <Save size={13} />}
            Save
          </button>
        </div>
      </div>
    </div>
  );
}

// ---------------------------------------------------------------------------
// Feature badges row
// ---------------------------------------------------------------------------
function FeatureBadges() {
  const features = [
    { label: 'Order Sync',          icon: <ShoppingCart size={12} />, color: '#65a30d' },
    { label: 'Product Import',      icon: <Package size={12} />,      color: '#65a30d' },
    { label: 'Inventory Tracking',  icon: <BarChart3 size={12} />,    color: '#65a30d' },
    { label: 'Webhooks',            icon: <Webhook size={12} />,      color: '#65a30d' },
  ];

  return (
    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10, marginTop: 16 }}>
      {features.map(({ label, icon, color }) => (
        <div key={label} style={{
          display: 'inline-flex', alignItems: 'center', gap: 6,
          background: 'var(--bg-card, #fff)', border: '1px solid var(--border-color, #e5e7eb)',
          padding: '6px 12px', borderRadius: 20, fontSize: 12, fontWeight: 500,
          color: 'var(--text-main, #111)',
        }}>
          <CheckCircle2 size={12} color={color} />
          <span style={{ color: 'var(--text-muted, #6b7280)', display: 'flex', alignItems: 'center' }}>{icon}</span>
          {label}
        </div>
      ))}
    </div>
  );
}

// ---------------------------------------------------------------------------
// Main page
// ---------------------------------------------------------------------------
export default function ShopifyIntegrationPage() {
  const [shops, setShops]               = useState([]);
  const [loading, setLoading]           = useState(true);
  const [toast, setToast]               = useState(null);
  const [anyConnected, setAnyConnected] = useState(false);
  const [showAddPanel, setShowAddPanel] = useState(false);
  const [storeHandle, setStoreHandle]   = useState('');

  const showToast = (message, type = 'info') => setToast({ message, type });

  // Load all connected shops
  const loadShops = useCallback(async () => {
    setLoading(true);
    try {
      // GET /api/shopify/shops  →  { shops: [...] }
      // Falls back gracefully: if endpoint returns single status, wrap it
      const data = await apiFetch('/shopify/shops');
      const list = Array.isArray(data.shops) ? data.shops
                 : Array.isArray(data)        ? data
                 : data.connected             ? [data] : [];
      setShops(list);
      setAnyConnected(list.length > 0);
    } catch {
      // Try legacy single-shop status endpoint
      try {
        const data = await apiFetch('/shopify/status');
        if (data.connected) {
          setShops([{
            id: data.shop_id,
            name: data.domain,
            shopify_domain: data.domain,
            last_synced_at: data.last_synced_at,
            boutique_name: '',
          }]);
          setAnyConnected(true);
        } else {
          setShops([]);
          setAnyConnected(false);
        }
      } catch {
        setShops([]);
        setAnyConnected(false);
      }
    } finally {
      setLoading(false);
    }
  }, []);

  // Check URL params on mount (OAuth callback)
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('connected') === '1') {
      showToast('Shopify store connected successfully!', 'success');
      window.history.replaceState({}, '', window.location.pathname);
    }
    if (params.get('error') === '1') {
      showToast('Shopify connection failed. Please try again.', 'error');
      window.history.replaceState({}, '', window.location.pathname);
    }
  }, []);

  useEffect(() => { loadShops(); }, [loadShops]);

  const handleConnectStore = () => {
    const handle = storeHandle.trim().replace(/\.myshopify\.com.*$/, '');
    if (!handle) {
      showToast('Please enter a store handle.', 'error');
      return;
    }
    window.location.href = `/api/auth/shopify/redirect?shop=${encodeURIComponent(handle)}.myshopify.com`;
  };

  const handleDisconnect = async (shopId) => {
    if (!window.confirm('Disconnect this Shopify store?')) return;
    try {
      await apiFetch(`/shopify/shops/${shopId}`, { method: 'DELETE' });
      showToast('Store disconnected.', 'success');
      loadShops();
    } catch (err) {
      showToast(err.message || 'Failed to disconnect.', 'error');
    }
  };

  const handleSaveBoutiqueName = async (shopId, name) => {
    try {
      await apiFetch(`/shopify/shops/${shopId}`, {
        method: 'PATCH',
        body: JSON.stringify({ boutique_name: name }),
      });
      showToast('Boutique name saved!', 'success');
      setShops(prev => prev.map(s => s.id === shopId ? { ...s, boutique_name: name } : s));
    } catch (err) {
      showToast(err.message || 'Failed to save.', 'error');
    }
  };

  return (
    <div className="main-content" style={{ padding: '28px 32px', background: 'var(--bg-app, #f9fafb)', maxWidth: 680 }}>
      <style>{`
        @keyframes spin    { from { transform: rotate(0deg);   } to { transform: rotate(360deg); } }
        @keyframes slideIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
      `}</style>

      {/* ── Header ─────────────────────────────────────────────── */}
      <div style={{ display: 'flex', alignItems: 'center', gap: 14, marginBottom: 24 }}>
        <div style={{
          width: 52, height: 52, borderRadius: 12, background: '#fff',
          border: '1px solid var(--border-color, #e5e7eb)',
          display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0,
          boxShadow: '0 2px 8px rgba(0,0,0,0.04)',
        }}>
          <ShopifyLogo size={28} />
        </div>
        <div>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 2 }}>
            <h1 style={{ margin: 0, fontSize: 20, fontWeight: 700, color: 'var(--text-main, #111)' }}>Shopify</h1>
            {!loading && anyConnected && (
              <span style={{
                display: 'inline-flex', alignItems: 'center', gap: 5,
                fontSize: 11, fontWeight: 600, color: '#3B6D11',
                background: '#EAF3DE', padding: '3px 10px', borderRadius: 12,
                border: '1px solid #C0DD97',
              }}>
                <span style={{ width: 6, height: 6, borderRadius: '50%', background: '#65a30d', display: 'inline-block' }} />
                Connected
              </span>
            )}
          </div>
          <p style={{ margin: 0, fontSize: 12, color: 'var(--text-muted, #6b7280)' }}>
            Sync orders, products &amp; inventory from your Shopify store
          </p>
        </div>
      </div>

      {/* ── Store list ─────────────────────────────────────────── */}
      {loading ? (
        <div style={{ textAlign: 'center', padding: 48, color: 'var(--text-muted, #6b7280)' }}>
          <Loader2 size={22} style={{ animation: 'spin 1s linear infinite', marginBottom: 8 }} />
          <div style={{ fontSize: 13 }}>Loading…</div>
        </div>
      ) : (
        <>
          {shops.map(shop => (
            <StoreCard
              key={shop.id}
              shop={shop}
              onDisconnect={handleDisconnect}
              onSaveBoutiqueName={handleSaveBoutiqueName}
            />
          ))}

          {/* No stores yet → single connect prompt */}
          {shops.length === 0 && (
            <div style={{
              border: '2px dashed var(--border-color, #e5e7eb)', borderRadius: 12,
              padding: '32px 24px', textAlign: 'center', marginBottom: 12,
            }}>
              <ShopifyLogo size={36} />
              <div style={{ fontWeight: 600, fontSize: 15, color: 'var(--text-main, #111)', margin: '12px 0 4px' }}>
                No Shopify store connected
              </div>
              <div style={{ fontSize: 12, color: 'var(--text-muted, #6b7280)', marginBottom: 20 }}>
                Connect your store via secure OAuth — no API keys needed.
              </div>

              {!showAddPanel ? (
                <button
                  onClick={() => setShowAddPanel(true)}
                  style={{
                    display: 'inline-flex', alignItems: 'center', gap: 8,
                    background: '#65a30d', color: '#fff', border: 'none',
                    borderRadius: 8, padding: '11px 22px', fontSize: 14, fontWeight: 600,
                    cursor: 'pointer', transition: 'background 0.15s',
                  }}
                  onMouseEnter={e => e.currentTarget.style.background = '#4d7c0f'}
                  onMouseLeave={e => e.currentTarget.style.background = '#65a30d'}
                >
                  <Plus size={15} /> Connect Shopify
                </button>
              ) : (
                <div style={{
                  border: '1px solid #c0dd97', borderRadius: 10,
                  padding: '14px 16px', background: '#f9fef4',
                  textAlign: 'left', marginTop: 8,
                  animation: 'slideIn 0.18s ease',
                }}>
                  <div style={{ fontSize: 12, color: 'var(--text-muted, #6b7280)', marginBottom: 8 }}>
                    Enter your Shopify store handle to connect.
                  </div>
                  <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    <input
                      value={storeHandle}
                      onChange={e => setStoreHandle(e.target.value)}
                      onKeyDown={e => e.key === 'Enter' && handleConnectStore()}
                      placeholder="my-store"
                      autoFocus
                      style={{
                        flex: 1, padding: '9px 12px', fontSize: 13,
                        border: '1px solid var(--border-color, #e5e7eb)', borderRadius: 8,
                        background: '#fff', color: 'var(--text-main, #111)',
                        outline: 'none',
                      }}
                      onFocus={e => e.target.style.borderColor = '#65a30d'}
                      onBlur={e => e.target.style.borderColor = 'var(--border-color, #e5e7eb)'}
                    />
                    <span style={{ fontSize: 13, color: 'var(--text-muted, #6b7280)', whiteSpace: 'nowrap' }}>
                      .myshopify.com
                    </span>
                    <button
                      onClick={handleConnectStore}
                      style={{
                        background: '#65a30d', color: '#fff', border: 'none',
                        borderRadius: 8, padding: '9px 18px', fontSize: 13, fontWeight: 600,
                        cursor: 'pointer', whiteSpace: 'nowrap',
                      }}
                    >
                      Connect
                    </button>
                    <button
                      onClick={() => { setShowAddPanel(false); setStoreHandle(''); }}
                      style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--text-muted, #6b7280)', display: 'flex' }}
                    >
                      <XCircle size={16} />
                    </button>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* "Add another store" toggle + inline panel */}
          {shops.length > 0 && (
            <>
              {/* Collapsed: dashed button */}
              {!showAddPanel && (
                <button
                  onClick={() => setShowAddPanel(true)}
                  style={{
                    width: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                    background: 'none', border: '2px dashed var(--border-color, #e5e7eb)',
                    borderRadius: 12, padding: '14px 20px', fontSize: 13, fontWeight: 500,
                    color: 'var(--text-muted, #6b7280)', cursor: 'pointer',
                    transition: 'all 0.15s', marginBottom: 12,
                  }}
                  onMouseEnter={e => { e.currentTarget.style.borderColor = '#65a30d'; e.currentTarget.style.color = '#65a30d'; }}
                  onMouseLeave={e => { e.currentTarget.style.borderColor = 'var(--border-color, #e5e7eb)'; e.currentTarget.style.color = 'var(--text-muted, #6b7280)'; }}
                >
                  <span style={{
                    width: 22, height: 22, borderRadius: '50%',
                    border: '1.5px solid currentColor',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                  }}>
                    <Plus size={12} />
                  </span>
                  Add another store
                </button>
              )}

              {/* Expanded: handle input panel */}
              {showAddPanel && (
                <div style={{
                  border: '2px dashed #c0dd97', borderRadius: 12,
                  padding: '18px 20px', marginBottom: 12,
                  background: '#f9fef4',
                  animation: 'slideIn 0.18s ease',
                }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 }}>
                    <span style={{ fontSize: 13, color: 'var(--text-muted, #6b7280)', fontWeight: 500 }}>
                      Enter your Shopify store handle to connect a specific store.
                    </span>
                    <button
                      onClick={() => { setShowAddPanel(false); setStoreHandle(''); }}
                      style={{
                        background: 'none', border: 'none', cursor: 'pointer',
                        color: 'var(--text-muted, #6b7280)', display: 'flex', alignItems: 'center',
                        padding: 4, borderRadius: 6,
                      }}
                      title="Cancel"
                    >
                      <XCircle size={16} />
                    </button>
                  </div>
                  <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    <input
                      value={storeHandle}
                      onChange={e => setStoreHandle(e.target.value)}
                      onKeyDown={e => e.key === 'Enter' && handleConnectStore()}
                      placeholder="my-store"
                      autoFocus
                      style={{
                        flex: 1, padding: '9px 12px', fontSize: 13,
                        border: '1px solid var(--border-color, #e5e7eb)', borderRadius: 8,
                        background: '#fff', color: 'var(--text-main, #111)',
                        outline: 'none', transition: 'border-color 0.15s',
                      }}
                      onFocus={e => e.target.style.borderColor = '#65a30d'}
                      onBlur={e => e.target.style.borderColor = 'var(--border-color, #e5e7eb)'}
                    />
                    <span style={{ fontSize: 13, color: 'var(--text-muted, #6b7280)', whiteSpace: 'nowrap', userSelect: 'none' }}>
                      .myshopify.com
                    </span>
                    <button
                      onClick={handleConnectStore}
                      style={{
                        display: 'flex', alignItems: 'center', gap: 6,
                        background: '#65a30d', color: '#fff', border: 'none',
                        borderRadius: 8, padding: '9px 18px', fontSize: 13, fontWeight: 600,
                        cursor: 'pointer', transition: 'background 0.15s', whiteSpace: 'nowrap',
                      }}
                      onMouseEnter={e => e.currentTarget.style.background = '#4d7c0f'}
                      onMouseLeave={e => e.currentTarget.style.background = '#65a30d'}
                    >
                      Connect
                    </button>
                  </div>
                </div>
              )}
            </>
          )}
        </>
      )}

      {/* ── Feature badges ─────────────────────────────────────── */}
      <FeatureBadges />

      {/* Toast */}
      {toast && <Toast {...toast} onDismiss={() => setToast(null)} />}
    </div>
  );
}