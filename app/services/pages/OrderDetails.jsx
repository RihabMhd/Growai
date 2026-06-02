import React, { useState } from 'react';
import '../OrderDetails.css';

// Source icon: shows platform logo initial
const SourceIcon = ({ source }) => {
    const map = {
        shopify:   { label: 'S', color: '#95bf47' },
        facebook:  { label: 'f', color: '#1877f2' },
        tiktok:    { label: 'T', color: '#010101' },
        instagram: { label: 'ig', color: '#e1306c' },
        whatsapp:  { label: 'W', color: '#25d366' },
        manual:    { label: 'M', color: '#7239ea' },
    };
    const s = map[source?.toLowerCase()] || map.manual;
    return (
        <span className="source-icon" style={{ background: s.color }}>
            {s.label}
        </span>
    );
};

// Financial status badge
const FinancialBadge = ({ status }) => {
    const map = {
        unpaid:   { label: 'Unpaid',   cls: 'financial-unpaid' },
        pending:  { label: 'Pending',  cls: 'financial-pending' },
        paid:     { label: 'Paid',     cls: 'financial-paid' },
        refunded: { label: 'Refunded', cls: 'financial-refunded' },
    };
    const s = map[status?.toLowerCase()] || map.unpaid;
    return <span className={`financial-badge ${s.cls}`}>{s.label}</span>;
};

// Format date nicely
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString([], {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const formatDateShort = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString([], {
        month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
};

const OrderDetails = ({ order, onClose }) => {
    const [status, setStatus] = useState(order?.status || 'pending');
    const [isEditingPrice, setIsEditingPrice] = useState(false);
    const [pricing, setPricing] = useState({
        subtotal: Number(order?.total_price) - Number(order?.shipping_price) + Number(order?.discount) || 0,
        shipping: Number(order?.shipping_price) || 0,
        discount: Number(order?.discount) || 0,
    });

    const currency = order?.currency || 'MAD';
    const total = pricing.subtotal + pricing.shipping - pricing.discount;

    const handlePriceChange = (e) => {
        const { name, value } = e.target;
        setPricing(prev => ({ ...prev, [name]: Number(value) }));
    };

    // Derive source from order_sources relation or source_channel fallback
    const sourceType = order?.source?.type || order?.source_channel || 'manual';

    // Build address string from client fields
    const client = order?.client || {};
    const addressParts = [
        client.address,
        client.city,
        client.province,
    ].filter(Boolean);
    const fullAddress = addressParts.join(', ') || '—';

    const shipment = order?.shipment;

    return (
        <div className="order-details-container">

            {/* ── HEADER ── */}
            <div className="od-header">
                <div className="od-header-left">
                    <SourceIcon source={sourceType} />
                    <div>
                        <h2>{order?.order_number || order?.id || '—'}</h2>
                        <span className="od-date">
                            {formatDate(order?.created_at)}
                            {sourceType && sourceType !== 'manual' && (
                                <> via <span style={{ textTransform: 'capitalize' }}>{sourceType}</span></>
                            )}
                        </span>
                    </div>
                </div>

                <div className="od-header-right" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                    <FinancialBadge status={order?.financial_status} />
                    <select
                        className={`status-select status-${status}`}
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        {['pending','confirmed','processing','shipped','delivered','cancelled','returned'].map(s => (
                            <option key={s} value={s}>
                                {s.charAt(0).toUpperCase() + s.slice(1)}
                            </option>
                        ))}
                    </select>
                    {onClose && (
                        <button className="close-modal-btn" onClick={onClose}>✕</button>
                    )}
                </div>
            </div>

            <div className="od-grid">

                {/* ── LEFT COLUMN ── */}
                <div className="od-main-col">

                    {/* Customer Details */}
                    <div className="od-card">
                        <h3 className="od-card-title">Customer Details</h3>
                        <div className="customer-info">
                            <div className="info-row">
                                <strong>Name</strong>
                                <span>{client.name || '—'}</span>
                            </div>
                            <div className="info-row">
                                <strong>Phone</strong>
                                <span className="highlight-text">{client.phone || '—'}</span>
                            </div>
                            {client.email && (
                                <div className="info-row">
                                    <strong>Email</strong>
                                    <span>{client.email}</span>
                                </div>
                            )}
                            <div className="info-row">
                                <strong>Address</strong>
                                <span>{fullAddress}</span>
                            </div>
                        </div>
                    </div>

                    {/* Products */}
                    <div className="od-card">
                        <h3 className="od-card-title">
                            Products ({order?.items?.length ?? 0})
                        </h3>
                        {order?.items?.length > 0 ? (
                            order.items.map((item, i) => (
                                <div className="product-item" key={i}>
                                    <div className="product-image-placeholder">IMG</div>
                                    <div className="product-details">
                                        <h4>{item.product_name}</h4>
                                        {item.variant_title && (
                                            <span className="product-variant">{item.variant_title}</span>
                                        )}
                                        {item.sku && (
                                            <span className="product-sku">SKU: {item.sku}</span>
                                        )}
                                    </div>
                                    <div className="product-price">{item.unit_price} {currency}</div>
                                    <div className="product-qty">× {item.quantity}</div>
                                    <div className="product-total">{item.total_price} {currency}</div>
                                </div>
                            ))
                        ) : (
                            <p className="note-text">No products.</p>
                        )}
                    </div>

                    {/* Pricing */}
                    <div className="od-card pricing-card">
                        <div className="pricing-header">
                            <h3 className="od-card-title">Pricing</h3>
                            <button
                                className="edit-btn"
                                onClick={() => setIsEditingPrice(!isEditingPrice)}
                            >
                                {isEditingPrice ? 'Save' : 'Edit'}
                            </button>
                        </div>

                        <div className="pricing-body">
                            {isEditingPrice ? (
                                <div className="pricing-edit-form">
                                    <div className="input-group">
                                        <label>Subtotal</label>
                                        <input type="number" name="subtotal" value={pricing.subtotal} onChange={handlePriceChange} />
                                    </div>
                                    <div className="input-group">
                                        <label>Shipping</label>
                                        <input type="number" name="shipping" value={pricing.shipping} onChange={handlePriceChange} />
                                    </div>
                                    <div className="input-group">
                                        <label>Discount</label>
                                        <input type="number" name="discount" value={pricing.discount} onChange={handlePriceChange} />
                                    </div>
                                </div>
                            ) : (
                                <div className="pricing-view">
                                    <div className="price-row">
                                        <span>Subtotal</span>
                                        <span>{pricing.subtotal} {currency}</span>
                                    </div>
                                    <div className="price-row">
                                        <span>Shipping</span>
                                        <span>{pricing.shipping} {currency}</span>
                                    </div>
                                    {pricing.discount > 0 && (
                                        <div className="price-row discount">
                                            <span>Discount</span>
                                            <span>−{pricing.discount} {currency}</span>
                                        </div>
                                    )}
                                </div>
                            )}
                            <div className="price-row total-row">
                                <span>Total</span>
                                <span>{total} {currency}</span>
                            </div>
                        </div>
                    </div>

                    {/* Shipment (if exists) */}
                    {shipment && (
                        <div className="od-card">
                            <h3 className="od-card-title">Shipment</h3>
                            <div className="customer-info">
                                {shipment.tracking_number && (
                                    <div className="info-row">
                                        <strong>Tracking</strong>
                                        <span className="highlight-text">{shipment.tracking_number}</span>
                                    </div>
                                )}
                                <div className="info-row">
                                    <strong>Status</strong>
                                    <span style={{ textTransform: 'capitalize' }}>{shipment.status?.replace('_', ' ') || '—'}</span>
                                </div>
                                {shipment.delivery_company && (
                                    <div className="info-row">
                                        <strong>Carrier</strong>
                                        <span>{shipment.delivery_company.name}</span>
                                    </div>
                                )}
                                {shipment.cod_amount > 0 && (
                                    <div className="info-row">
                                        <strong>COD</strong>
                                        <span>{shipment.cod_amount} {currency}</span>
                                    </div>
                                )}
                                {shipment.shipped_at && (
                                    <div className="info-row">
                                        <strong>Shipped</strong>
                                        <span>{formatDate(shipment.shipped_at)}</span>
                                    </div>
                                )}
                                {shipment.delivered_at && (
                                    <div className="info-row">
                                        <strong>Delivered</strong>
                                        <span>{formatDate(shipment.delivered_at)}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                </div>

                {/* ── RIGHT COLUMN ── */}
                <div className="od-side-col">

                    {/* Order Source */}
                    {order?.source && (
                        <div className="od-card">
                            <h3 className="od-card-title">Source</h3>
                            <div className="customer-info">
                                <div className="info-row">
                                    <strong>Channel</strong>
                                    <span style={{ textTransform: 'capitalize' }}>{order.source.type}</span>
                                </div>
                                {order.source.campaign_name && (
                                    <div className="info-row">
                                        <strong>Campaign</strong>
                                        <span>{order.source.campaign_name}</span>
                                    </div>
                                )}
                                {order.source.utm_source && (
                                    <div className="info-row">
                                        <strong>UTM source</strong>
                                        <span>{order.source.utm_source}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Assigned Agent */}
                    {order?.agent && (
                        <div className="od-card">
                            <h3 className="od-card-title">Assigned Agent</h3>
                            <div className="customer-info">
                                <div className="info-row">
                                    <strong>Name</strong>
                                    <span>{order.agent.name}</span>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Notes */}
                    <div className="od-card">
                        <h3 className="od-card-title">Notes</h3>
                        <p className="note-text">{order?.notes || 'No notes.'}</p>
                    </div>

                    {/* Action History */}
                    <div className="od-card">
                        <h3 className="od-card-title">Action History</h3>
                        <div className="timeline-container">
                            {order?.histories?.length > 0 ? (
                                order.histories.map((h, i) => (
                                    <div className="timeline-item" key={i}>
                                        <div className="timeline-dot"></div>
                                        <div className="timeline-content">
                                            <p className="timeline-description">{h.description}</p>
                                            <div className="timeline-meta">
                                                <span className="timeline-user">{h.user?.name || 'System'}</span>
                                                <span className="timeline-time">
                                                    {formatDateShort(h.created_at)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <span className="no-history">Aucun historique.</span>
                            )}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    );
};

export default OrderDetails;