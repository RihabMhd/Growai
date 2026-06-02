import React, { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { 
  Building2, 
  Phone, 
  Globe, 
  RefreshCw, 
  Power, 
  AlertCircle,
  CheckCircle2,
  Loader2
} from 'lucide-react';
import { companiesService } from '../services/companiesService';
import CompanyConnectionStatus from '../components/CompanyConnectionStatus';

export default function CompanyDetailsPage() {
  const { id } = useParams();
  const [company, setCompany] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [testingConnection, setTestingConnection] = useState(false);
  const [testResult, setTestResult] = useState(null);
  const [disabling, setDisabling] = useState(false);
  const [enablingWebhook, setEnablingWebhook] = useState(false);

  useEffect(() => {
    fetchCompanyDetails();
  }, [id]);

  const fetchCompanyDetails = async () => {
    try {
      setLoading(true);
      const data = await companiesService.getCompany(id);
      setCompany(data.company || data);
      setError(null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load company details');
    } finally {
      setLoading(false);
    }
  };

  const handleTestConnection = async () => {
    setTestingConnection(true);
    try {
      await companiesService.testConnection(id);
      setTestResult({ success: true, message: 'Connection successful ✓' });
    } catch (err) {
      setTestResult({ 
        success: false, 
        message: err.response?.data?.message || 'Connection failed' 
      });
    } finally {
      setTestingConnection(false);
    }
  };

  const handleDisconnect = async () => {
    if (!window.confirm('Are you sure you want to disconnect this company?')) return;
    
    setDisabling(true);
    try {
      await companiesService.disconnectCompany(id);
      setCompany(prev => ({ ...prev, is_connected: false }));
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to disconnect');
    } finally {
      setDisabling(false);
    }
  };

  const handleEnableUpdates = async () => {
    setEnablingWebhook(true);
    try {
      await companiesService.enableUpdates(id);
      setCompany(prev => ({ 
        ...prev, 
        webhook_enabled: true,
        webhook_registered_at: new Date().toISOString()
      }));
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to enable updates');
    } finally {
      setEnablingWebhook(false);
    }
  };

  const handleDisableUpdates = async () => {
    if (!window.confirm('Disable automatic order updates?')) return;

    setDisabling(true);
    try {
      await companiesService.disableUpdates(id);
      setCompany(prev => ({ ...prev, webhook_enabled: false }));
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to disable updates');
    } finally {
      setDisabling(false);
    }
  };

  if (loading) {
    return (
      <div style={{ padding: '32px', display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '400px' }}>
        <Loader2 size={32} style={{ animation: 'spin 1s linear infinite', color: 'var(--text-muted)' }} />
      </div>
    );
  }

  if (!company) {
    return (
      <div style={{ padding: '32px', textAlign: 'center' }}>
        <p style={{ color: 'var(--text-muted)', fontSize: '14px' }}>Company not found</p>
      </div>
    );
  }

  const webhookUrl = `https://yourdomain.com/api/shipments/webhook/${id}`;
  const lastConnectionTest = company.last_connection_test ? new Date(company.last_connection_test) : null;
  const webhookRegisteredAt = company.webhook_registered_at ? new Date(company.webhook_registered_at) : null;

  return (
    <div style={{ padding: '28px 32px', background: 'var(--bg-app)' }}>
      {/* Header */}
      <div style={{ marginBottom: '28px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '8px' }}>
          <Building2 size={28} style={{ color: 'var(--text-main)' }} />
          <h1 style={{ fontSize: '24px', fontWeight: '700', margin: 0, color: 'var(--text-main)' }}>
            {company.name}
          </h1>
        </div>
        <p style={{ fontSize: '14px', color: 'var(--text-muted)', margin: 0 }}>
          Manage delivery company settings and credentials
        </p>
      </div>

      {error && (
        <div style={{
          marginBottom: '20px',
          padding: '12px 16px',
          backgroundColor: '#fef2f2',
          border: '1px solid #fee2e2',
          borderRadius: '8px',
          display: 'flex',
          gap: '8px',
          alignItems: 'flex-start'
        }}>
          <AlertCircle size={18} style={{ color: '#ef4444', flexShrink: 0, marginTop: '2px' }} />
          <span style={{ fontSize: '13px', color: '#7f1d1d' }}>{error}</span>
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px' }}>
        {/* Section 1: Company Information */}
        <div style={{
          backgroundColor: 'var(--bg-card)',
          border: '1px solid var(--border-color)',
          borderRadius: '12px',
          padding: '24px'
        }}>
          <h2 style={{ fontSize: '16px', fontWeight: '700', margin: '0 0 16px 0', color: 'var(--text-main)' }}>
            Company Information
          </h2>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Company Name
              </p>
              <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                {company.name}
              </p>
            </div>

            {company.phone && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Phone Number
                </p>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Phone size={14} style={{ color: 'var(--text-muted)' }} />
                  <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                    {company.phone}
                  </p>
                </div>
              </div>
            )}

            {company.api_url && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  API URL
                </p>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  <Globe size={14} style={{ color: 'var(--text-muted)' }} />
                  <a href={company.api_url} target="_blank" rel="noopener noreferrer" style={{
                    fontSize: '13px',
                    color: '#3b82f6',
                    textDecoration: 'none',
                    wordBreak: 'break-all'
                  }}>
                    {company.api_url}
                  </a>
                </div>
              </div>
            )}

            {company.updated_at && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Last Updated
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {new Date(company.updated_at).toLocaleDateString()} {new Date(company.updated_at).toLocaleTimeString()}
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Section 2: Connection Status */}
        <div style={{
          backgroundColor: 'var(--bg-card)',
          border: '1px solid var(--border-color)',
          borderRadius: '12px',
          padding: '24px'
        }}>
          <h2 style={{ fontSize: '16px', fontWeight: '700', margin: '0 0 16px 0', color: 'var(--text-main)' }}>
            Connection Status
          </h2>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <CompanyConnectionStatus 
              isConnected={company.is_connected}
              webhookEnabled={company.webhook_enabled}
            />

            {company.is_connected && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  API Key
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0, fontFamily: 'monospace' }}>
                  •••••••••••
                </p>
              </div>
            )}

            {lastConnectionTest && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Last Connection Test
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {lastConnectionTest.toLocaleDateString()} {lastConnectionTest.toLocaleTimeString()}
                </p>
              </div>
            )}

            <div style={{ display: 'flex', gap: '8px', marginTop: '12px' }}>
              <button
                onClick={handleTestConnection}
                disabled={testingConnection}
                style={{
                  flex: 1,
                  padding: '10px 14px',
                  backgroundColor: 'var(--bg-app)',
                  color: 'var(--text-main)',
                  border: '1px solid var(--border-color)',
                  borderRadius: '8px',
                  fontSize: '13px',
                  fontWeight: '600',
                  cursor: testingConnection ? 'not-allowed' : 'pointer',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: '6px',
                  opacity: testingConnection ? 0.6 : 1
                }}
              >
                {testingConnection ? <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} /> : <RefreshCw size={14} />}
                Test Connection
              </button>

              {company.is_connected && (
                <button
                  onClick={handleDisconnect}
                  disabled={disabling}
                  style={{
                    flex: 1,
                    padding: '10px 14px',
                    backgroundColor: '#fef2f2',
                    color: '#ef4444',
                    border: '1px solid #fee2e2',
                    borderRadius: '8px',
                    fontSize: '13px',
                    fontWeight: '600',
                    cursor: disabling ? 'not-allowed' : 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    gap: '6px',
                    opacity: disabling ? 0.6 : 1
                  }}
                >
                  {disabling ? <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} /> : <Power size={14} />}
                  Disconnect
                </button>
              )}
            </div>

            {testResult && (
              <div style={{
                padding: '10px 12px',
                backgroundColor: testResult.success ? '#ecfdf5' : '#fef2f2',
                border: `1px solid ${testResult.success ? '#d1fae5' : '#fee2e2'}`,
                borderRadius: '6px',
                display: 'flex',
                gap: '8px',
                alignItems: 'flex-start'
              }}>
                {testResult.success ? (
                  <CheckCircle2 size={14} style={{ color: '#10b981', flexShrink: 0 }} />
                ) : (
                  <AlertCircle size={14} style={{ color: '#ef4444', flexShrink: 0 }} />
                )}
                <span style={{
                  fontSize: '12px',
                  color: testResult.success ? '#065f46' : '#7f1d1d'
                }}>
                  {testResult.message}
                </span>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Section 3: Orders Updates (Webhooks) */}
      <div style={{
        marginTop: '24px',
        backgroundColor: 'var(--bg-card)',
        border: '1px solid var(--border-color)',
        borderRadius: '12px',
        padding: '24px'
      }}>
        <h2 style={{ fontSize: '16px', fontWeight: '700', margin: '0 0 16px 0', color: 'var(--text-main)' }}>
          Order Updates (Webhooks)
        </h2>

        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px' }}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Webhook Status
              </p>
              <div style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '6px',
                backgroundColor: company.webhook_enabled ? '#ecfdf5' : '#f3f4f6',
                color: company.webhook_enabled ? '#065f46' : '#374151',
                padding: '6px 12px',
                borderRadius: '6px',
                fontSize: '13px',
                fontWeight: '600'
              }}>
                {company.webhook_enabled ? <CheckCircle2 size={14} /> : <AlertCircle size={14} />}
                {company.webhook_enabled ? 'Enabled' : 'Disabled'}
              </div>
            </div>

            {webhookRegisteredAt && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Registered At
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {webhookRegisteredAt.toLocaleDateString()} {webhookRegisteredAt.toLocaleTimeString()}
                </p>
              </div>
            )}
          </div>

          <div>
            <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
              Webhook URL
            </p>
            <div style={{
              padding: '12px',
              backgroundColor: 'var(--bg-app)',
              border: '1px solid var(--border-color)',
              borderRadius: '6px',
              fontSize: '11px',
              fontFamily: 'monospace',
              color: 'var(--text-main)',
              wordBreak: 'break-all',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              gap: '8px'
            }}>
              <span>{webhookUrl}</span>
              <button
                onClick={() => {
                  navigator.clipboard.writeText(webhookUrl);
                }}
                style={{
                  background: 'none',
                  border: '1px solid var(--border-color)',
                  padding: '4px 8px',
                  borderRadius: '4px',
                  cursor: 'pointer',
                  fontSize: '10px',
                  color: 'var(--text-main)',
                  whiteSpace: 'nowrap'
                }}
              >
                Copy
              </button>
            </div>
          </div>
        </div>

        <div style={{ display: 'flex', gap: '8px', marginTop: '16px' }}>
          {!company.webhook_enabled && (
            <button
              onClick={handleEnableUpdates}
              disabled={enablingWebhook || !company.is_connected}
              style={{
                padding: '10px 14px',
                backgroundColor: '#10b981',
                color: '#ffffff',
                border: 'none',
                borderRadius: '8px',
                fontSize: '13px',
                fontWeight: '600',
                cursor: enablingWebhook || !company.is_connected ? 'not-allowed' : 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                opacity: enablingWebhook || !company.is_connected ? 0.6 : 1
              }}
            >
              {enablingWebhook ? <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} /> : <CheckCircle2 size={14} />}
              Enable Updates
            </button>
          )}

          {company.webhook_enabled && (
            <button
              onClick={handleDisableUpdates}
              disabled={disabling}
              style={{
                padding: '10px 14px',
                backgroundColor: '#fef2f2',
                color: '#ef4444',
                border: '1px solid #fee2e2',
                borderRadius: '8px',
                fontSize: '13px',
                fontWeight: '600',
                cursor: disabling ? 'not-allowed' : 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '6px',
                opacity: disabling ? 0.6 : 1
              }}
            >
              {disabling ? <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} /> : <Power size={14} />}
              Disable Updates
            </button>
          )}
        </div>
      </div>

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
}
