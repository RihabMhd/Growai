import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  Package, 
  Search, 
  Filter,
  ChevronDown,
  Loader2
} from 'lucide-react';
import { shipmentsService } from '../services/shipmentsService';
import { companiesService } from '../services/companiesService';
import ShipmentStatusBadge from '../components/ShipmentStatusBadge';

export default function ShipmentsPage() {
  const navigate = useNavigate();
  const [shipments, setShipments] = useState([]);
  const [companies, setCompanies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [carrierFilter, setCarrierFilter] = useState('');
  const [sortBy, setSortBy] = useState('created_at');

  useEffect(() => {
    fetchData();
  }, [statusFilter, carrierFilter, sortBy]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const [shipmentsRes, companiesRes] = await Promise.all([
        shipmentsService.getShipments({
          status: statusFilter || undefined,
          delivery_company_id: carrierFilter || undefined,
          sort: sortBy
        }),
        companiesService.getCompanies({ active: true })
      ]);

      setShipments(shipmentsRes.shipments || shipmentsRes);
      setCompanies(companiesRes.companies || companiesRes);
    } catch (error) {
      console.error('Failed to fetch shipments:', error);
    } finally {
      setLoading(false);
    }
  };

  const filteredShipments = shipments.filter(shipment =>
    !searchTerm ||
    shipment.tracking_number?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    shipment.order_id?.toString().includes(searchTerm) ||
    shipment.recipient_name?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const statuses = [
    { value: 'pending', label: 'En attente' },
    { value: 'picked_up', label: 'Collecté' },
    { value: 'in_transit', label: 'En transit' },
    { value: 'out_for_delivery', label: 'En livraison' },
    { value: 'delivered', label: 'Livré' },
    { value: 'returned', label: 'Retourné' },
    { value: 'failed', label: 'Échoué' }
  ];

  return (
    <div style={{ padding: '28px 32px', background: 'var(--bg-app)' }}>
      {/* Header */}
      <div style={{ marginBottom: '24px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '4px' }}>
          <div style={{ width: '32px', height: '32px', display: 'flex', alignItems: 'center' }}>
            <Package size={16} style={{ color: 'var(--text-main)' }} />
          </div>
          <h1 style={{ fontSize: '20px', fontWeight: '700', margin: 0, color: 'var(--text-main)' }}>
            Shipments
          </h1>
        </div>
        <p style={{ fontSize: '13px', color: 'var(--text-muted)', margin: 0 }}>
          Track and manage all shipments
        </p>
      </div>

      {/* Controls Row */}
      <div style={{
        display: 'grid',
        gridTemplateColumns: '1fr 180px 180px 180px',
        gap: '12px',
        marginBottom: '20px'
      }}>
        {/* Search */}
        <div style={{ position: 'relative' }}>
          <Search size={14} style={{
            position: 'absolute',
            left: '12px',
            top: '50%',
            transform: 'translateY(-50%)',
            color: 'var(--text-muted)'
          }} />
          <input
            type="text"
            placeholder="Search by tracking #, order #, or recipient..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            style={{
              width: '100%',
              padding: '10px 12px 10px 36px',
              border: '1px solid var(--border-color)',
              borderRadius: '8px',
              fontSize: '13px',
              backgroundColor: 'var(--bg-card)',
              color: 'var(--text-main)',
              boxSizing: 'border-box'
            }}
          />
        </div>

        {/* Status Filter */}
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          style={{
            padding: '10px 12px',
            border: '1px solid var(--border-color)',
            borderRadius: '8px',
            fontSize: '13px',
            backgroundColor: 'var(--bg-card)',
            color: 'var(--text-main)',
            cursor: 'pointer',
            appearance: 'none',
            backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E")`,
            backgroundRepeat: 'no-repeat',
            backgroundPosition: 'right 10px center',
            paddingRight: '28px'
          }}
        >
          <option value="">All Status</option>
          {statuses.map(status => (
            <option key={status.value} value={status.value}>
              {status.label}
            </option>
          ))}
        </select>

        {/* Carrier Filter */}
        <select
          value={carrierFilter}
          onChange={(e) => setCarrierFilter(e.target.value)}
          style={{
            padding: '10px 12px',
            border: '1px solid var(--border-color)',
            borderRadius: '8px',
            fontSize: '13px',
            backgroundColor: 'var(--bg-card)',
            color: 'var(--text-main)',
            cursor: 'pointer',
            appearance: 'none',
            backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E")`,
            backgroundRepeat: 'no-repeat',
            backgroundPosition: 'right 10px center',
            paddingRight: '28px'
          }}
        >
          <option value="">All Carriers</option>
          {companies.map(company => (
            <option key={company.id} value={company.id}>
              {company.name}
            </option>
          ))}
        </select>

        {/* Sort */}
        <select
          value={sortBy}
          onChange={(e) => setSortBy(e.target.value)}
          style={{
            padding: '10px 12px',
            border: '1px solid var(--border-color)',
            borderRadius: '8px',
            fontSize: '13px',
            backgroundColor: 'var(--bg-card)',
            color: 'var(--text-main)',
            cursor: 'pointer',
            appearance: 'none',
            backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E")`,
            backgroundRepeat: 'no-repeat',
            backgroundPosition: 'right 10px center',
            paddingRight: '28px'
          }}
        >
          <option value="created_at">Newest First</option>
          <option value="-created_at">Oldest First</option>
          <option value="status">Status</option>
        </select>
      </div>

      {/* Results Count */}
      <div style={{
        marginBottom: '16px',
        fontSize: '12px',
        color: 'var(--text-muted)',
        fontWeight: '500'
      }}>
        Showing {filteredShipments.length} of {shipments.length} shipments
      </div>

      {/* Table */}
      <div style={{
        backgroundColor: 'var(--bg-card)',
        border: '1px solid var(--border-color)',
        borderRadius: '12px',
        overflow: 'hidden'
      }}>
        {loading ? (
          <div style={{
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            padding: '60px 32px'
          }}>
            <Loader2 size={32} style={{ animation: 'spin 1s linear infinite', color: 'var(--text-muted)' }} />
          </div>
        ) : filteredShipments.length === 0 ? (
          <div style={{
            padding: '60px 32px',
            textAlign: 'center'
          }}>
            <Package size={32} style={{ color: 'var(--text-muted)', marginBottom: '12px', opacity: 0.5 }} />
            <p style={{ fontSize: '14px', color: 'var(--text-muted)', margin: 0 }}>
              No shipments found
            </p>
          </div>
        ) : (
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ borderBottom: '1px solid var(--border-color)', backgroundColor: 'var(--bg-app)' }}>
                <th style={{ padding: '14px 16px', textAlign: 'left', fontSize: '12px', fontWeight: '600', color: 'var(--text-muted)', textTransform: 'uppercase' }}>
                  Order #
                </th>
                <th style={{ padding: '14px 16px', textAlign: 'left', fontSize: '12px', fontWeight: '600', color: 'var(--text-muted)', textTransform: 'uppercase' }}>
                  Tracking #
                </th>
                <th style={{ padding: '14px 16px', textAlign: 'left', fontSize: '12px', fontWeight: '600', color: 'var(--text-muted)', textTransform: 'uppercase' }}>
                  Carrier
                </th>
                <th style={{ padding: '14px 16px', textAlign: 'left', fontSize: '12px', fontWeight: '600', color: 'var(--text-muted)', textTransform: 'uppercase' }}>
                  Status
                </th>
                <th style={{ padding: '14px 16px', textAlign: 'left', fontSize: '12px', fontWeight: '600', color: 'var(--text-muted)', textTransform: 'uppercase' }}>
                  Recipient
                </th>
                <th style={{ padding: '14px 16px', textAlign: 'left', fontSize: '12px', fontWeight: '600', color: 'var(--text-muted)', textTransform: 'uppercase' }}>
                  Created
                </th>
                <th style={{ padding: '14px 16px', textAlign: 'center', fontSize: '12px', fontWeight: '600', color: 'var(--text-muted)', textTransform: 'uppercase' }}>
                  Action
                </th>
              </tr>
            </thead>
            <tbody>
              {filteredShipments.map((shipment) => (
                <tr
                  key={shipment.id}
                  style={{
                    borderBottom: '1px solid var(--border-color)',
                    ':hover': { backgroundColor: 'var(--bg-app)' }
                  }}
                  onMouseEnter={(e) => e.currentTarget.style.backgroundColor = 'var(--bg-app)'}
                  onMouseLeave={(e) => e.currentTarget.style.backgroundColor = 'transparent'}
                >
                  <td style={{ padding: '14px 16px', fontSize: '13px', color: 'var(--text-main)', fontWeight: '500' }}>
                    #{shipment.order_id}
                  </td>
                  <td style={{ padding: '14px 16px', fontSize: '13px', color: '#3b82f6', fontWeight: '500', fontFamily: 'monospace' }}>
                    {shipment.tracking_number}
                  </td>
                  <td style={{ padding: '14px 16px', fontSize: '13px', color: 'var(--text-main)' }}>
                    {shipment.deliveryCompany?.name || 'Unknown'}
                  </td>
                  <td style={{ padding: '14px 16px' }}>
                    <ShipmentStatusBadge status={shipment.status} size="sm" />
                  </td>
                  <td style={{ padding: '14px 16px', fontSize: '13px', color: 'var(--text-main)' }}>
                    {shipment.recipient_name}
                  </td>
                  <td style={{ padding: '14px 16px', fontSize: '13px', color: 'var(--text-muted)' }}>
                    {new Date(shipment.created_at).toLocaleDateString()}
                  </td>
                  <td style={{ padding: '14px 16px', textAlign: 'center' }}>
                    <button
                      onClick={() => navigate(`/shipments/${shipment.id}`)}
                      style={{
                        padding: '6px 12px',
                        backgroundColor: 'var(--bg-app)',
                        color: 'var(--text-main)',
                        border: '1px solid var(--border-color)',
                        borderRadius: '6px',
                        fontSize: '12px',
                        fontWeight: '500',
                        cursor: 'pointer'
                      }}
                    >
                      View
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
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
