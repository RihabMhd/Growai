import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Package,
  Copy,
  RefreshCw,
  Trash2,
  Loader2,
  AlertCircle,
  CheckCircle2,
  ArrowLeft
} from 'lucide-react';
import { shipmentsService } from '../services/shipmentsService';
import ShipmentStatusBadge from '../components/ShipmentStatusBadge';
import TrackingTimeline from '../components/TrackingTimeline';

export default function ShipmentDetailsPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [shipment, setShipment] = useState(null);
  const [tracking, setTracking] = useState(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [canceling, setCanceling] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [copied, setCopied] = useState(false);

  useEffect(() => {
    fetchShipmentDetails();
  }, [id]);

  const fetchShipmentDetails = async () => {
    try {
      setLoading(true);
      const data = await shipmentsService.getShipment(id);
      setShipment(data.shipment || data);
      setError(null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load shipment');
    } finally {
      setLoading(false);
    }
  };

  const handleRefreshTracking = async () => {
    setRefreshing(true);
    try {
      const data = await shipmentsService.getTracking(id);
      setTracking(data.tracking || data);
      setSuccess('Tracking updated');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to refresh tracking');
    } finally {
      setRefreshing(false);
    }
  };

  const handleCancelShipment = async () => {
    if (!window.confirm('Cancel this shipment? This action cannot be undone.')) return;

    setCanceling(true);
    try {
      await shipmentsService.cancelShipment(id);
      setSuccess('Shipment cancelled successfully');
      setTimeout(() => navigate('/shipments'), 2000);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to cancel shipment');
    } finally {
      setCanceling(false);
    }
  };

  const handleCopyTracking = () => {
    if (shipment?.tracking_number) {
      navigator.clipboard.writeText(shipment.tracking_number);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  if (loading) {
    return (
      <div style={{ padding: '32px', display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '400px' }}>
        <Loader2 size={32} style={{ animation: 'spin 1s linear infinite', color: 'var(--text-muted)' }} />
      </div>
    );
  }

  if (!shipment) {
    return (
      <div style={{ padding: '32px', textAlign: 'center' }}>
        <p style={{ color: 'var(--text-muted)', fontSize: '14px' }}>Shipment not found</p>
      </div>
    );
  }

  const canCancel = ['pending', 'picked_up', 'in_transit'].includes(shipment.status);
  const shippedAt = shipment.shipped_at ? new Date(shipment.shipped_at) : null;
  const deliveredAt = shipment.delivered_at ? new Date(shipment.delivered_at) : null;

  return (
    <div style={{ padding: '28px 32px', background: 'var(--bg-app)' }}>
      {/* Back Button */}
      <button
        onClick={() => navigate(-1)}
        style={{
          display: 'flex',
          alignItems: 'center',
          gap: '6px',
          background: 'none',
          border: 'none',
          cursor: 'pointer',
          color: '#3b82f6',
          fontSize: '13px',
          fontWeight: '600',
          marginBottom: '20px',
          padding: 0
        }}
      >
        <ArrowLeft size={14} />
        Back
      </button>

      {/* Alerts */}
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

      {success && (
        <div style={{
          marginBottom: '20px',
          padding: '12px 16px',
          backgroundColor: '#ecfdf5',
          border: '1px solid #d1fae5',
          borderRadius: '8px',
          display: 'flex',
          gap: '8px',
          alignItems: 'flex-start'
        }}>
          <CheckCircle2 size={18} style={{ color: '#10b981', flexShrink: 0, marginTop: '2px' }} />
          <span style={{ fontSize: '13px', color: '#065f46' }}>{success}</span>
        </div>
      )}

      {/* Header */}
      <div style={{
        backgroundColor: 'var(--bg-card)',
        border: '1px solid var(--border-color)',
        borderRadius: '12px',
        padding: '24px',
        marginBottom: '24px',
        display: 'grid',
        gridTemplateColumns: '1fr 1fr',
        gap: '24px'
      }}>
        <div>
          <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 8px 0', textTransform: 'uppercase' }}>
            Order Number
          </p>
          <h2 style={{ fontSize: '20px', fontWeight: '700', color: 'var(--text-main)', margin: 0, cursor: 'pointer' }}>
            #{shipment.order_id}
          </h2>
        </div>

        <div>
          <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 8px 0', textTransform: 'uppercase' }}>
            Tracking Number
          </p>
          <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
            <span style={{ fontSize: '18px', fontWeight: '700', color: 'var(--text-main)', fontFamily: 'monospace' }}>
              {shipment.tracking_number}
            </span>
            <button
              onClick={handleCopyTracking}
              style={{
                background: 'none',
                border: 'none',
                padding: '4px',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                gap: '4px',
                fontSize: '12px',
                color: '#3b82f6',
                fontWeight: '600'
              }}
            >
              <Copy size={14} />
              {copied ? 'Copied!' : 'Copy'}
            </button>
          </div>
        </div>
      </div>

      {/* Main Content Grid */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '24px', marginBottom: '24px' }}>
        {/* Section 1: Shipment Details */}
        <div style={{
          backgroundColor: 'var(--bg-card)',
          border: '1px solid var(--border-color)',
          borderRadius: '12px',
          padding: '24px'
        }}>
          <h2 style={{ fontSize: '16px', fontWeight: '700', margin: '0 0 16px 0', color: 'var(--text-main)' }}>
            Shipment Details
          </h2>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Status
              </p>
              <ShipmentStatusBadge status={shipment.status} size="md" />
            </div>

            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Carrier
              </p>
              <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                {shipment.deliveryCompany?.name || 'Unknown'}
              </p>
            </div>

            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Recipient
              </p>
              <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                {shipment.recipient_name}
              </p>
            </div>

            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Phone
              </p>
              <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                {shipment.recipient_phone}
              </p>
            </div>

            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                COD Amount
              </p>
              <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0, fontWeight: '600' }}>
                {shipment.cod_amount} DH
              </p>
            </div>

            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Created
              </p>
              <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                {new Date(shipment.created_at).toLocaleDateString()} {new Date(shipment.created_at).toLocaleTimeString()}
              </p>
            </div>
          </div>
        </div>

        {/* Section 2: Address */}
        <div style={{
          backgroundColor: 'var(--bg-card)',
          border: '1px solid var(--border-color)',
          borderRadius: '12px',
          padding: '24px'
        }}>
          <h2 style={{ fontSize: '16px', fontWeight: '700', margin: '0 0 16px 0', color: 'var(--text-main)' }}>
            Delivery Address
          </h2>

          <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
            <div>
              <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                Address
              </p>
              <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                {shipment.address}
              </p>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  City
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {shipment.city}
                </p>
              </div>

              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Region
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {shipment.region}
                </p>
              </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '12px' }}>
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Country
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {shipment.country}
                </p>
              </div>

              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Weight
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {shipment.weight ? `${shipment.weight} kg` : 'N/A'}
                </p>
              </div>
            </div>

            {shippedAt && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Picked Up
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {shippedAt.toLocaleDateString()} {shippedAt.toLocaleTimeString()}
                </p>
              </div>
            )}

            {deliveredAt && (
              <div>
                <p style={{ fontSize: '11px', fontWeight: '600', color: 'var(--text-muted)', margin: '0 0 4px 0', textTransform: 'uppercase' }}>
                  Delivered
                </p>
                <p style={{ fontSize: '14px', color: 'var(--text-main)', margin: 0 }}>
                  {deliveredAt.toLocaleDateString()} {deliveredAt.toLocaleTimeString()}
                </p>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Tracking Timeline */}
      <div style={{
        backgroundColor: 'var(--bg-card)',
        border: '1px solid var(--border-color)',
        borderRadius: '12px',
        padding: '24px',
        marginBottom: '24px'
      }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
          <h2 style={{ fontSize: '16px', fontWeight: '700', margin: 0, color: 'var(--text-main)' }}>
            Tracking Timeline
          </h2>
          <button
            onClick={handleRefreshTracking}
            disabled={refreshing}
            style={{
              padding: '8px 12px',
              backgroundColor: 'var(--bg-app)',
              color: 'var(--text-main)',
              border: '1px solid var(--border-color)',
              borderRadius: '6px',
              fontSize: '12px',
              fontWeight: '600',
              cursor: refreshing ? 'not-allowed' : 'pointer',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              opacity: refreshing ? 0.6 : 1
            }}
          >
            {refreshing ? <Loader2 size={12} style={{ animation: 'spin 1s linear infinite' }} /> : <RefreshCw size={12} />}
            Refresh
          </button>
        </div>

        <TrackingTimeline events={tracking?.events || [
          {
            status: shipment.status,
            timestamp: shipment.updated_at,
            notes: shipment.delivery_notes
          }
        ]} />
      </div>

      {/* Actions */}
      <div style={{ display: 'flex', gap: '12px' }}>
        {canCancel && (
          <button
            onClick={handleCancelShipment}
            disabled={canceling}
            style={{
              padding: '10px 16px',
              backgroundColor: '#fef2f2',
              color: '#ef4444',
              border: '1px solid #fee2e2',
              borderRadius: '8px',
              fontSize: '13px',
              fontWeight: '600',
              cursor: canceling ? 'not-allowed' : 'pointer',
              display: 'flex',
              alignItems: 'center',
              gap: '6px',
              opacity: canceling ? 0.6 : 1
            }}
          >
            {canceling ? <Loader2 size={14} style={{ animation: 'spin 1s linear infinite' }} /> : <Trash2 size={14} />}
            Cancel Shipment
          </button>
        )}
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
