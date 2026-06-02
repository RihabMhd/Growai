import { useState, useEffect, useContext, useRef } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { AuthContext } from "../auth/AuthContext";
import { useLanguage } from "../context/LanguageContext";
import api from "../api/axios";
import DatePicker from 'react-datepicker';
import "react-datepicker/dist/react-datepicker.css";
import '../CustomDatePicker.css';
import OrderDetails from './OrderDetails';

const CURRENCY = "MAD";

const ORDER_STATUSES = [
  { value: "nouveau",       label: "Nouveau",        color: "#7239ea", icon: "●" },
  { value: "confirmed",     label: "Confirmé",       color: "#50cd89", icon: "●" },
  { value: "no_response",   label: "Pas de réponse", color: "#00a3ff", icon: "◎" },
  { value: "rappel",        label: "Rappel",         color: "#9b6dff", icon: "☎" },
  { value: "cancelled",     label: "Annulé",         color: "#f1416c", icon: "⊗" },
  { value: "doublon",       label: "Doublon",        color: "#ffc700", icon: "◈" },
  { value: "wrong_number",  label: "Mauvais numéro", color: "#fd7e14", icon: "⚠" },
];

const FULFILLMENT_STATUSES = [
  { value: "unfulfilled",   label: "Unfulfilled",   color: "#ffc700" },
  { value: "fulfilled",     label: "Fulfilled",     color: "#50cd89" },
  { value: "in_transit",    label: "In Transit",    color: "#00a3ff" },
  { value: "delivered",     label: "Delivered",     color: "#50cd89" },
  { value: "delivery_failed", label: "Failed",      color: "#f1416c" },
];

const PER_PAGE_OPTIONS = [10, 25, 50, 100];

const getStatusMeta = (val) =>
  ORDER_STATUSES.find(s => s.value === val) || { label: val || "Pending", color: "#ffc700", icon: "●" };

const getFulfillmentMeta = (val) =>
  FULFILLMENT_STATUSES.find(s => s.value === val) || { label: val || "Unfulfilled", color: "#ffc700" };

const inputStyle = {
  width: "100%", padding: "11px 14px", borderRadius: "8px",
  background: "rgba(255,255,255,0.04)", border: "1px solid rgba(255,255,255,0.08)",
  color: "var(--text-main)", fontSize: "0.85rem", outline: "none",
  transition: "border-color 0.2s", boxSizing: "border-box",
};

const Icons = {
  total: (<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>),
  confirmed: (<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>),
  cancelled: (<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>),
  rate: (<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>),
};

function StatusDropdown({ orderId, currentStatus, onChange }) {
  const [open, setOpen] = useState(false);
  const ref = useRef(null);
  const meta = getStatusMeta(currentStatus);

  useEffect(() => {
    const h = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
    document.addEventListener("mousedown", h);
    return () => document.removeEventListener("mousedown", h);
  }, []);

  return (
    <div ref={ref} style={{ position: "relative", display: "inline-block" }}>
      <button
        onClick={(e) => { e.stopPropagation(); setOpen(v => !v); }}
        style={{
          display: "flex", alignItems: "center", gap: "6px", padding: "4px 10px",
          borderRadius: "20px", border: `1px solid ${meta.color}33`,
          background: `${meta.color}18`, color: meta.color,
          fontSize: "0.72rem", fontWeight: "700", cursor: "pointer", whiteSpace: "nowrap",
        }}
      >
        <span style={{ width: "6px", height: "6px", borderRadius: "50%", background: meta.color, flexShrink: 0 }} />
        {meta.label}
        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"
          style={{ transform: open ? "rotate(180deg)" : "none", transition: "transform 0.15s" }}>
          <path d="M6 9l6 6 6-6"/>
        </svg>
      </button>
      {open && (
        <div onClick={e => e.stopPropagation()} style={{
          position: "absolute", top: "calc(100% + 4px)", left: 0, zIndex: 9999,
          background: "#1a1a1f", border: "1px solid rgba(255,255,255,0.1)", borderRadius: "10px",
          padding: "6px", boxShadow: "0 12px 40px rgba(0,0,0,0.7)", minWidth: "180px",
        }}>
          {ORDER_STATUSES.map(s => (
            <div
              key={s.value}
              onClick={() => { onChange(orderId, s.value); setOpen(false); }}
              style={{
                display: "flex", alignItems: "center", justifyContent: "space-between",
                padding: "8px 12px", borderRadius: "7px", cursor: "pointer",
                background: s.value === currentStatus ? `${s.color}18` : "transparent",
                transition: "background 0.12s",
              }}
              className="sd-item"
            >
              <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                <span style={{ width: "8px", height: "8px", borderRadius: "50%", background: s.color, flexShrink: 0 }} />
                <span style={{ fontSize: "0.8rem", fontWeight: "600", color: "var(--text-main)" }}>{s.label}</span>
              </div>
              {s.value === currentStatus && (
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke={s.color} strokeWidth="3">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

// ── Fulfillment Badge ─────────────────────────────────────────────────────────
function FulfillmentBadge({ status }) {
  const meta = getFulfillmentMeta(status);
  return (
    <span style={{
      display: "inline-flex", alignItems: "center", gap: "5px",
      padding: "3px 9px", borderRadius: "20px",
      background: `${meta.color}15`, border: `1px solid ${meta.color}33`,
      fontSize: "0.7rem", fontWeight: "700", color: meta.color,
    }}>
      <span style={{ width: "5px", height: "5px", borderRadius: "50%", background: meta.color }} />
      {meta.label}
    </span>
  );
}

// ── Bulk Actions Menu ─────────────────────────────────────────────────────────
function BulkActionsMenu({ selectedIds, agents, onUpdateStatus, onAssign, onCreateParcel, onClear }) {
  const [open, setOpen] = useState(false);
  const [showStatusSub, setShowStatusSub] = useState(false);
  const [showAgentSub, setShowAgentSub] = useState(false);
  const ref = useRef(null);

  useEffect(() => {
    const h = (e) => { if (ref.current && !ref.current.contains(e.target)) { setOpen(false); setShowStatusSub(false); setShowAgentSub(false); } };
    document.addEventListener("mousedown", h);
    return () => document.removeEventListener("mousedown", h);
  }, []);

  if (selectedIds.length === 0) return null;

  return (
    <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
      <span style={{ fontSize: "0.78rem", fontWeight: "700", color: "var(--text-main)" }}>
        {selectedIds.length} selected
      </span>
      <div ref={ref} style={{ position: "relative" }}>
        <button
          onClick={() => setOpen(v => !v)}
          style={{
            display: "flex", alignItems: "center", gap: "6px", padding: "6px 14px",
            borderRadius: "8px", background: "var(--bg-card)", border: "1px solid var(--border-color)",
            color: "var(--text-main)", fontSize: "0.8rem", fontWeight: "700", cursor: "pointer",
          }}
        >
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>
          </svg>
          Actions
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </button>
        {open && (
          <div style={{
            position: "absolute", top: "calc(100% + 4px)", left: 0, zIndex: 9999,
            background: "#1a1a1f", border: "1px solid rgba(255,255,255,0.1)", borderRadius: "10px",
            padding: "6px", boxShadow: "0 12px 40px rgba(0,0,0,0.7)", minWidth: "190px",
          }}>
            {/* Create Parcel */}
            <div
              onClick={() => { onCreateParcel(selectedIds); setOpen(false); }}
              className="sd-item"
              style={{ display: "flex", alignItems: "center", gap: "10px", padding: "9px 12px", borderRadius: "7px", cursor: "pointer" }}
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7239ea" strokeWidth="2"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8l4 1 3 3v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
              <span style={{ fontSize: "0.82rem", fontWeight: "600" }}>Create Parcel</span>
            </div>

            {/* Update Status */}
            <div style={{ position: "relative" }}>
              <div
                onMouseEnter={() => { setShowStatusSub(true); setShowAgentSub(false); }}
                className="sd-item"
                style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "9px 12px", borderRadius: "7px", cursor: "pointer" }}
              >
                <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#50cd89" strokeWidth="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                  <span style={{ fontSize: "0.82rem", fontWeight: "600" }}>Update Status</span>
                </div>
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M9 6l6 6-6 6"/></svg>
              </div>
              {showStatusSub && (
                <div style={{
                  position: "absolute", left: "calc(100% + 4px)", top: 0, zIndex: 10000,
                  background: "#1a1a1f", border: "1px solid rgba(255,255,255,0.1)", borderRadius: "10px",
                  padding: "6px", boxShadow: "0 12px 40px rgba(0,0,0,0.7)", minWidth: "175px",
                }}>
                  {ORDER_STATUSES.map(s => (
                    <div
                      key={s.value}
                      onClick={() => { onUpdateStatus(selectedIds, s.value); setOpen(false); setShowStatusSub(false); }}
                      className="sd-item"
                      style={{ display: "flex", alignItems: "center", gap: "8px", padding: "8px 12px", borderRadius: "7px", cursor: "pointer" }}
                    >
                      <span style={{ width: "7px", height: "7px", borderRadius: "50%", background: s.color }} />
                      <span style={{ fontSize: "0.8rem", fontWeight: "600" }}>{s.label}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Assign To */}
            <div style={{ position: "relative" }}>
              <div
                onMouseEnter={() => { setShowAgentSub(true); setShowStatusSub(false); }}
                className="sd-item"
                style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "9px 12px", borderRadius: "7px", cursor: "pointer" }}
              >
                <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#00a3ff" strokeWidth="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                  <span style={{ fontSize: "0.82rem", fontWeight: "600" }}>Assign to</span>
                </div>
                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M9 6l6 6-6 6"/></svg>
              </div>
              {showAgentSub && (
                <div style={{
                  position: "absolute", left: "calc(100% + 4px)", top: 0, zIndex: 10000,
                  background: "#1a1a1f", border: "1px solid rgba(255,255,255,0.1)", borderRadius: "10px",
                  padding: "6px", boxShadow: "0 12px 40px rgba(0,0,0,0.7)", minWidth: "175px",
                }}>
                  <div
                    onClick={() => { onAssign(selectedIds, null); setOpen(false); setShowAgentSub(false); }}
                    className="sd-item"
                    style={{ display: "flex", alignItems: "center", gap: "8px", padding: "8px 12px", borderRadius: "7px", cursor: "pointer" }}
                  >
                    <span style={{ fontSize: "0.8rem", color: "var(--text-muted)" }}>— Unassign</span>
                  </div>
                  {agents.map(a => (
                    <div
                      key={a.id}
                      onClick={() => { onAssign(selectedIds, a.id); setOpen(false); setShowAgentSub(false); }}
                      className="sd-item"
                      style={{ display: "flex", alignItems: "center", gap: "8px", padding: "8px 12px", borderRadius: "7px", cursor: "pointer" }}
                    >
                      <div style={{ width: "22px", height: "22px", borderRadius: "50%", background: "rgba(114,57,234,0.2)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "0.65rem", fontWeight: "700", color: "#9b6dff" }}>
                        {a.name?.[0]?.toUpperCase()}
                      </div>
                      <span style={{ fontSize: "0.8rem", fontWeight: "600" }}>{a.name}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </div>
      <button onClick={onClear} style={{ background: "none", border: "none", color: "var(--text-muted)", cursor: "pointer", fontSize: "0.75rem", textDecoration: "underline" }}>
        Clear
      </button>
    </div>
  );
}

// ── Custom Filter Dropdown ────────────────────────────────────────────────────
function CustomSelect({ id, value, onChange, options, placeholder, openDropdown, setOpenDropdown }) {
  const isOpen = openDropdown === id;
  const selected = options.find(o => o.value === value);
  return (
    <div className="custom-select-wrapper" style={{ position: "relative" }}>
      <div onClick={() => setOpenDropdown(isOpen ? null : id)} style={{
        display: "flex", alignItems: "center", gap: "8px", padding: "7px 12px", borderRadius: "8px",
        background: "var(--bg-app)", border: isOpen ? "1px solid #7239ea" : "1px solid var(--border-color)",
        color: "var(--text-main)", fontSize: "0.78rem", cursor: "pointer", userSelect: "none",
        transition: "border-color 0.2s", minWidth: "120px",
      }}>
        {selected?.dot && <span style={{ width: "7px", height: "7px", borderRadius: "50%", background: selected.dot, flexShrink: 0 }} />}
        <span style={{ flex: 1, overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{selected?.label || placeholder}</span>
        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"
          style={{ transform: isOpen ? "rotate(180deg)" : "none", transition: "transform 0.2s", flexShrink: 0 }}>
          <path d="M6 9l6 6 6-6"/>
        </svg>
      </div>
      {isOpen && (
        <div style={{
          position: "absolute", top: "calc(100% + 6px)", left: 0, minWidth: "200px",
          background: "#18181b", border: "1px solid rgba(114,57,234,0.4)", borderRadius: "10px",
          padding: "6px", boxShadow: "0 12px 40px rgba(0,0,0,0.6)", zIndex: 9000, maxHeight: "280px", overflowY: "auto",
        }}>
          {options.map(opt => (
            <div key={opt.value} onClick={() => { onChange(opt.value); setOpenDropdown(null); }}
              className="cs-item-hover"
              style={{
                display: "flex", alignItems: "center", gap: "10px", padding: "8px 12px", borderRadius: "7px",
                cursor: "pointer", fontSize: "0.8rem",
                fontWeight: opt.value === value ? "700" : "500",
                background: opt.value === value ? "rgba(114,57,234,0.25)" : "transparent",
                color: opt.value === value ? "#c4a7ff" : "var(--text-main)",
              }}>
              {opt.dot && <span style={{ width: "7px", height: "7px", borderRadius: "50%", background: opt.dot, flexShrink: 0 }} />}
              <span>{opt.label}</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

// ── Product Picker ────────────────────────────────────────────────────────────
function ProductPicker({ products, onSelect, onClose }) {
  const [q, setQ] = useState("");
  const ref = useRef(null);
  const filtered = (Array.isArray(products) ? products : []).filter(p =>
    (p.title || p.name || "").toLowerCase().includes(q.toLowerCase())
  );
  useEffect(() => {
    const h = (e) => { if (ref.current && !ref.current.contains(e.target)) onClose(); };
    document.addEventListener("mousedown", h);
    return () => document.removeEventListener("mousedown", h);
  }, [onClose]);

  return (
    <div ref={ref} style={{
      position: "absolute", top: "calc(100% + 6px)", right: 0, width: "280px",
      background: "#1c1c1f", border: "1px solid rgba(114,57,234,0.35)", borderRadius: "10px",
      padding: "10px", boxShadow: "0 16px 40px rgba(0,0,0,0.7)", zIndex: 9999,
    }}>
      <input autoFocus placeholder="Search products..." value={q} onChange={e => setQ(e.target.value)}
        style={{ ...inputStyle, marginBottom: "8px", padding: "8px 12px", background: "rgba(255,255,255,0.06)" }} />
      <div style={{ maxHeight: "200px", overflowY: "auto", display: "flex", flexDirection: "column", gap: "2px" }}>
        {filtered.length === 0
          ? <p style={{ color: "var(--text-muted)", fontSize: "0.8rem", textAlign: "center", padding: "12px" }}>No products found</p>
          : filtered.map(p => {
            const variants = Array.isArray(p.variants) ? p.variants : (p.variants ? JSON.parse(p.variants) : []);
            const price = variants?.[0]?.price ?? p.cost ?? 0;
            return (
              <div key={p.id} onClick={() => onSelect(p)} className="cs-item-hover"
                style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "9px 12px", borderRadius: "7px", cursor: "pointer" }}>
                <div>
                  <div style={{ fontSize: "0.83rem", fontWeight: "600", color: "var(--text-main)" }}>{p.title || p.name}</div>
                  <div style={{ fontSize: "0.72rem", color: "var(--text-muted)", marginTop: "2px" }}>{price} MAD</div>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7239ea" strokeWidth="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </div>
            );
          })}
      </div>
    </div>
  );
}

// ── New Order Modal ───────────────────────────────────────────────────────────
function NewOrderModal({ products, isAbandonedPage, onClose, onSuccess }) {
  const empty = { customer_name: "", customer_phone: "", customer_email: "", source: "", province: "", city: "", street: "", notes: "", shipping_price: 0, items: [] };
  const [form, setForm] = useState(empty);
  const [showPicker, setShowPicker] = useState(false);
  const [saving, setSaving] = useState(false);
  const set = (k, v) => setForm(p => ({ ...p, [k]: v }));

  const addProduct = (product) => {
    const variants = Array.isArray(product.variants) ? product.variants : (product.variants ? JSON.parse(product.variants) : []);
    const price = parseFloat(variants?.[0]?.price ?? product.cost ?? 0);
    setForm(p => {
      const ex = p.items.find(i => i.product_id === product.id);
      if (ex) return { ...p, items: p.items.map(i => i.product_id === product.id ? { ...i, quantity: i.quantity + 1 } : i) };
      return { ...p, items: [...p.items, { product_id: product.id, product_name: product.title || product.name, price, quantity: 1 }] };
    });
    setShowPicker(false);
  };

  const removeItem = (pid) => setForm(p => ({ ...p, items: p.items.filter(i => i.product_id !== pid) }));
  const updateQty  = (pid, qty) => { const q = Math.max(1, parseInt(qty) || 1); setForm(p => ({ ...p, items: p.items.map(i => i.product_id === pid ? { ...i, quantity: q } : i) })); };
  const subtotal = form.items.reduce((s, i) => s + i.price * i.quantity, 0);
  const total = subtotal + parseFloat(form.shipping_price || 0);

  const handleSubmit = async () => {
    if (!form.customer_name.trim()) { alert("Le nom du client est requis."); return; }
    if (!form.customer_phone.trim()) { alert("Le téléphone est requis."); return; }
    if (form.items.length === 0) { alert("Ajoutez au moins un produit."); return; }
    try {
      setSaving(true);
      const res = await api.post("/orders", {
        customer_name: form.customer_name, customer_phone: form.customer_phone,
        customer_email: form.customer_email || null, source: form.source || null,
        province: form.province || null, city: form.city || null, street: form.street || null,
        notes: form.notes || null, shipping_price: parseFloat(form.shipping_price) || 0,
        is_abandoned: isAbandonedPage,
        items: form.items.map(i => ({ product_id: i.product_id, quantity: i.quantity })),
      });
      onSuccess(res.data.message || "Commande créée avec succès !");
    } catch (err) {
      console.error(err);
      const errors = err?.response?.data?.errors;
      const msg = errors ? Object.values(errors).flat().join("\n") : (err?.response?.data?.message || "Erreur lors de la création.");
      alert(msg);
    } finally {
      setSaving(false);
    }
  };

  const lbl = { fontSize: "0.67rem", fontWeight: "700", color: "var(--text-muted)", letterSpacing: "0.6px", textTransform: "uppercase", display: "block", marginBottom: "8px" };

  return (
    <div style={{ position: "fixed", inset: 0, background: "rgba(0,0,0,0.72)", display: "flex", alignItems: "center", justifyContent: "center", zIndex: 10000, padding: "20px" }}>
      <div style={{ background: "#18181b", border: "1px solid rgba(255,255,255,0.08)", borderRadius: "16px", width: "100%", maxWidth: "900px", maxHeight: "92vh", display: "flex", flexDirection: "column", boxShadow: "0 24px 60px rgba(0,0,0,0.7)", animation: "modalIn 0.2s ease-out", overflow: "hidden" }}>
        <style>{`@keyframes modalIn{from{opacity:0;transform:scale(0.97) translateY(8px)}to{opacity:1;transform:scale(1) translateY(0)}} .modal-input:focus{border-color:rgba(114,57,234,0.6)!important} .rm-btn:hover{background:rgba(241,65,108,0.12)!important;color:#f1416c!important} .qty-btn:hover{background:rgba(255,255,255,0.1)!important}`}</style>
        {/* Header */}
        <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "18px 24px", borderBottom: "1px solid rgba(255,255,255,0.07)", flexShrink: 0 }}>
          <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
            <div style={{ width: "32px", height: "32px", background: "rgba(114,57,234,0.15)", borderRadius: "8px", display: "flex", alignItems: "center", justifyContent: "center" }}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7239ea" strokeWidth="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            </div>
            <h3 style={{ fontSize: "1rem", fontWeight: "700", color: "var(--text-main)" }}>New Manual Order</h3>
          </div>
          <button onClick={onClose} style={{ width: "28px", height: "28px", borderRadius: "50%", background: "rgba(255,255,255,0.06)", border: "1px solid rgba(255,255,255,0.1)", color: "var(--text-muted)", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "1rem" }}>×</button>
        </div>
        {/* Body */}
        <div style={{ display: "flex", flex: 1, overflow: "hidden" }}>
          {/* LEFT */}
          <div style={{ flex: 1, padding: "24px", overflowY: "auto", borderRight: "1px solid rgba(255,255,255,0.06)" }}>
            <div style={{ marginBottom: "22px" }}>
              <label style={lbl}>Customer</label>
              <div style={{ display: "flex", flexDirection: "column", gap: "8px" }}>
                <input className="modal-input" placeholder="Full Name *" value={form.customer_name} onChange={e => set("customer_name", e.target.value)} style={inputStyle} />
                <input className="modal-input" placeholder="Phone *" value={form.customer_phone} onChange={e => set("customer_phone", e.target.value)} style={inputStyle} />
                <input className="modal-input" placeholder="Email (optional)" value={form.customer_email} onChange={e => set("customer_email", e.target.value)} style={inputStyle} />
              </div>
            </div>
            <div style={{ marginBottom: "22px" }}>
              <label style={lbl}>Order Source</label>
              <div style={{ position: "relative" }}>
                <select className="modal-input" value={form.source} onChange={e => set("source", e.target.value)}
                  style={{ ...inputStyle, paddingRight: "32px", appearance: "none", cursor: "pointer" }}>
                  <option value="">Select source...</option>
                  <option value="manual">Manual</option>
                  <option value="shopify">Shopify</option>
                  <option value="whatsapp">WhatsApp</option>
                  <option value="google_sheets">Google Sheets</option>
                </select>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" strokeWidth="2.5" style={{ position: "absolute", right: "12px", top: "50%", transform: "translateY(-50%)", pointerEvents: "none" }}><path d="M6 9l6 6 6-6"/></svg>
              </div>
            </div>
            <div style={{ marginBottom: "22px" }}>
              <label style={lbl}>Delivery Address</label>
              <div style={{ display: "flex", flexDirection: "column", gap: "8px" }}>
                <input className="modal-input" placeholder="Province..." value={form.province} onChange={e => set("province", e.target.value)} style={inputStyle} />
                <input className="modal-input" placeholder="City..." value={form.city} onChange={e => set("city", e.target.value)} style={inputStyle} />
                <input className="modal-input" placeholder="Street address (optional)" value={form.street} onChange={e => set("street", e.target.value)} style={inputStyle} />
              </div>
            </div>
            <div>
              <label style={lbl}>Note (optional)</label>
              <textarea className="modal-input" placeholder="Any notes..." value={form.notes} onChange={e => set("notes", e.target.value)}
                style={{ ...inputStyle, height: "80px", resize: "none", lineHeight: "1.5" }} />
            </div>
          </div>
          {/* RIGHT */}
          <div style={{ width: "340px", padding: "24px", display: "flex", flexDirection: "column", flexShrink: 0 }}>
            <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: "12px" }}>
              <label style={{ ...lbl, marginBottom: 0 }}>Products *</label>
              <div style={{ position: "relative" }}>
                <button onClick={() => setShowPicker(v => !v)} style={{ display: "flex", alignItems: "center", gap: "5px", padding: "5px 10px", borderRadius: "6px", background: "rgba(114,57,234,0.12)", border: "1px solid rgba(114,57,234,0.25)", color: "#9b6dff", fontSize: "0.75rem", fontWeight: "700", cursor: "pointer" }}>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  Add product
                </button>
                {showPicker && <ProductPicker products={products} onSelect={addProduct} onClose={() => setShowPicker(false)} />}
              </div>
            </div>
            <div style={{ flex: 1, border: "1.5px dashed rgba(255,255,255,0.1)", borderRadius: "10px", background: "rgba(255,255,255,0.02)", overflow: "hidden", display: "flex", flexDirection: "column", minHeight: "200px" }}>
              {form.items.length === 0 ? (
                <div style={{ flex: 1, display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", gap: "14px", padding: "20px" }}>
                  <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.12)" strokeWidth="1.5"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                  <button onClick={() => setShowPicker(true)} style={{ display: "flex", alignItems: "center", gap: "6px", padding: "8px 18px", borderRadius: "8px", background: "#7239ea", color: "white", fontSize: "0.8rem", fontWeight: "700", border: "none", cursor: "pointer" }}>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add product
                  </button>
                </div>
              ) : (
                <div style={{ overflowY: "auto", flex: 1 }}>
                  {form.items.map(item => (
                    <div key={item.product_id} style={{ display: "flex", alignItems: "center", gap: "8px", padding: "10px 12px", borderBottom: "1px solid rgba(255,255,255,0.05)" }}>
                      <div style={{ width: "30px", height: "30px", borderRadius: "7px", background: "rgba(114,57,234,0.12)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9b6dff" strokeWidth="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                      </div>
                      <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ fontSize: "0.78rem", fontWeight: "600", color: "var(--text-main)", whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>{item.product_name}</div>
                        <div style={{ fontSize: "0.68rem", color: "var(--text-muted)", marginTop: "1px" }}>{item.price} MAD</div>
                      </div>
                      <div style={{ display: "flex", alignItems: "center", gap: "3px", flexShrink: 0 }}>
                        <button className="qty-btn" onClick={() => updateQty(item.product_id, item.quantity - 1)} style={{ width: "20px", height: "20px", borderRadius: "4px", background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", color: "var(--text-main)", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center" }}>−</button>
                        <span style={{ width: "22px", textAlign: "center", fontSize: "0.8rem", fontWeight: "700" }}>{item.quantity}</span>
                        <button className="qty-btn" onClick={() => updateQty(item.product_id, item.quantity + 1)} style={{ width: "20px", height: "20px", borderRadius: "4px", background: "rgba(255,255,255,0.05)", border: "1px solid rgba(255,255,255,0.08)", color: "var(--text-main)", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center" }}>+</button>
                      </div>
                      <div style={{ fontSize: "0.78rem", fontWeight: "700", minWidth: "46px", textAlign: "right", flexShrink: 0 }}>{(item.price * item.quantity).toFixed(2)}</div>
                      <button className="rm-btn" onClick={() => removeItem(item.product_id)} style={{ width: "20px", height: "20px", borderRadius: "4px", background: "transparent", border: "none", color: "var(--text-muted)", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
            <div style={{ marginTop: "16px", display: "flex", flexDirection: "column", gap: "10px" }}>
              <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: "12px" }}>
                <span style={{ fontSize: "0.82rem", color: "var(--text-muted)" }}>Shipping</span>
                <div style={{ display: "flex", alignItems: "center", border: "1px solid rgba(255,255,255,0.1)", borderRadius: "7px", overflow: "hidden" }}>
                  <input type="number" min="0" step="0.01" value={form.shipping_price} onChange={e => set("shipping_price", e.target.value)}
                    style={{ width: "70px", padding: "6px 10px", background: "rgba(255,255,255,0.04)", border: "none", color: "var(--text-main)", fontSize: "0.82rem", outline: "none", textAlign: "right" }} />
                  <span style={{ padding: "6px 10px", background: "rgba(255,255,255,0.06)", fontSize: "0.75rem", fontWeight: "700", color: "var(--text-muted)", borderLeft: "1px solid rgba(255,255,255,0.08)" }}>MAD</span>
                </div>
              </div>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", paddingTop: "10px", borderTop: "1px solid rgba(255,255,255,0.07)" }}>
                <span style={{ fontSize: "0.85rem", fontWeight: "600", color: "var(--text-muted)" }}>Total</span>
                <span style={{ fontSize: "1.15rem", fontWeight: "800", color: "var(--text-main)" }}>{total.toFixed(2)} MAD</span>
              </div>
            </div>
          </div>
        </div>
        {/* Footer */}
        <div style={{ display: "flex", gap: "10px", padding: "16px 24px", borderTop: "1px solid rgba(255,255,255,0.07)", flexShrink: 0 }}>
          <button onClick={onClose} style={{ flex: 1, padding: "11px", borderRadius: "8px", background: "rgba(255,255,255,0.04)", border: "1px solid rgba(255,255,255,0.1)", color: "var(--text-main)", fontSize: "0.85rem", fontWeight: "600", cursor: "pointer" }}>Cancel</button>
          <button onClick={handleSubmit} disabled={saving} style={{ flex: 2, padding: "11px", borderRadius: "8px", background: "#7239ea", border: "none", color: "white", fontSize: "0.85rem", fontWeight: "700", cursor: saving ? "not-allowed" : "pointer", opacity: saving ? 0.7 : 1, display: "flex", alignItems: "center", justifyContent: "center", gap: "8px" }}>
            {saving ? "Creating..." : "Create Order"}
          </button>
        </div>
      </div>
    </div>
  );
}

// ── Main Component ────────────────────────────────────────────────────────────
export default function AdminOrders() {
  const { user } = useContext(AuthContext);
  const location = useLocation();
  const isAbandonedPage = location.pathname.includes("abandonnees");

  // Data
  const [orders,       setOrders]       = useState([]);
  const [metrics,      setMetrics]      = useState({ total_orders: 0, confirmed: 0, cancelled: 0, pending: 0, today_orders: 0, failed_delivery: 0, delivery_rate: "0%" });
  const [activeAgents, setActiveAgents] = useState([]);
  const [products,     setProducts]     = useState([]);
  const [companies,    setCompanies]    = useState([]);
  const [loading,      setLoading]      = useState(true);
  const [message,      setMessage]      = useState(null);

  // Pagination
  const [currentPage,  setCurrentPage]  = useState(1);
  const [perPage,      setPerPage]      = useState(50);
  const [totalOrders,  setTotalOrders]  = useState(0);
  const totalPages = Math.max(1, Math.ceil(totalOrders / perPage));

  // Filters
  const [search,               setSearch]               = useState("");
  const [statusFilter,         setStatusFilter]         = useState("all");
  const [fulfillmentFilter,    setFulfillmentFilter]    = useState("all");
  const [deliverCompaniesFilter, setDeliverCompaniesFilter] = useState("all");
  const [productFilter,        setProductFilter]        = useState("all");
  const [agentFilter,          setAgentFilter]          = useState("all");
  const [periodFilter,         setPeriodFilter]         = useState("all");
  const [dateRange,            setDateRange]            = useState([null, null]);
  const [startDate, endDate] = dateRange;
  const [openDropdown,         setOpenDropdown]         = useState(null);
  const [isMoreOpen,           setIsMoreOpen]           = useState(false);

  // Selection
  const [selectedIds,  setSelectedIds]  = useState([]);

  // Modals
  const [selectedOrder,       setSelectedOrder]       = useState(null);
  const [isCreateModalOpen,   setIsCreateModalOpen]   = useState(false);

  // ── Fetch ─────────────────────────────────────────────────────────────────
  const fetchOrders = async (page = currentPage) => {
    try {
      setLoading(true);
      const params = {
        type: isAbandonedPage ? "abandoned" : "all",
        search: search || undefined,
        status: statusFilter !== "all" ? statusFilter : undefined,
        page,
        per_page: perPage,
      };
      const res = await api.get("/orders", { params });
      setOrders(Array.isArray(res.data?.orders) ? res.data.orders : []);
      setTotalOrders(res.data?.total ?? res.data?.orders?.length ?? 0);
      setMetrics(res.data?.metrics || {});
      setActiveAgents(Array.isArray(res.data?.active_agents) ? res.data.active_agents : []);
      setSelectedIds([]);
    } catch (err) { console.error(err); } finally { setLoading(false); }
  };

  const fetchProducts = async () => {
    try {
      const res = await api.get("/products");
      const raw = res.data;
      setProducts(Array.isArray(raw) ? raw : Array.isArray(raw?.data) ? raw.data : []);
    } catch (err) { setProducts([]); }
  };

  const fetchCompanies = async () => {
    try {
      const res = await api.get("/companies");
      setCompanies(res.data.companies || res.data.data || []);
    } catch (err) { console.error(err); }
  };

  useEffect(() => { setCurrentPage(1); fetchOrders(1); }, [location.pathname, search, statusFilter, perPage]);
  useEffect(() => { fetchOrders(currentPage); }, [currentPage]);
  useEffect(() => { fetchProducts(); fetchCompanies(); }, []);
  useEffect(() => {
    const h = (e) => {
      if (!e.target.closest(".custom-select-wrapper")) setOpenDropdown(null);
      if (!e.target.closest(".more-dd")) setIsMoreOpen(false);
    };
    document.addEventListener("mousedown", h);
    return () => document.removeEventListener("mousedown", h);
  }, []);

  const showToast = (text, type = "success") => { setMessage({ text, type }); setTimeout(() => setMessage(null), 4000); };

  // ── Inline status update ──────────────────────────────────────────────────
  const handleUpdateStatus = async (orderId, newStatus) => {
    try {
      await api.put(`/orders/${orderId}`, { status: newStatus });
      setOrders(prev => prev.map(o => o.id === orderId ? { ...o, status: newStatus } : o));
      if (selectedOrder?.id === orderId) setSelectedOrder(prev => ({ ...prev, status: newStatus }));
    } catch (err) { console.error(err); showToast("Erreur de mise à jour.", "error"); }
  };

  // ── Inline agent assign ───────────────────────────────────────────────────
  const handleAssignAgent = async (orderId, agentId) => {
    try {
      const value = agentId === "" ? null : parseInt(agentId);
      const res = await api.post(`/orders/${orderId}/assign`, { assigned_to: value });
      setOrders(prev => prev.map(o => o.id === orderId ? { ...o, assigned_to: value, assignedAgent: res.data.order?.assignedAgent } : o));
    } catch (err) { console.error(err); showToast("Erreur d'attribution.", "error"); }
  };

  // ── Bulk actions ──────────────────────────────────────────────────────────
  const handleBulkUpdateStatus = async (ids, status) => {
    try {
      await api.put("/orders/bulk/status", { ids, status });
      setOrders(prev => prev.map(o => ids.includes(o.id) ? { ...o, status } : o));
      setSelectedIds([]);
      showToast(`${ids.length} orders updated to "${status}".`);
    } catch (err) { console.error(err); showToast("Bulk update failed.", "error"); }
  };

  const handleBulkAssign = async (ids, agentId) => {
    try {
      await api.put("/orders/bulk/assign", { ids, assigned_to: agentId });
      const agent = agentId ? activeAgents.find(a => a.id === agentId) : null;
      setOrders(prev => prev.map(o => ids.includes(o.id) ? { ...o, assigned_to: agentId, assignedAgent: agent } : o));
      setSelectedIds([]);
      showToast(`${ids.length} orders assigned.`);
    } catch (err) { console.error(err); showToast("Bulk assign failed.", "error"); }
  };

  const handleBulkCreateParcel = (ids) => {
    showToast(`Create parcel for ${ids.length} orders — connect a delivery company first.`, "info");
    setSelectedIds([]);
  };

  // ── Row selection ─────────────────────────────────────────────────────────
  const allChecked = orders.length > 0 && selectedIds.length === orders.length;
  const toggleAll = () => setSelectedIds(allChecked ? [] : orders.map(o => o.id));
  const toggleOne = (id) => setSelectedIds(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);

  // ── Client-side filters (period, product, agent, fulfillment, source) ─────
  const filteredOrders = orders.filter(order => {
    if (productFilter !== "all" && !order.items?.some(i => i.product_id === parseInt(productFilter))) return false;
    if (agentFilter !== "all" && String(order.assigned_to) !== agentFilter) return false;
    if (fulfillmentFilter !== "all") {
      const s = order.shipments?.[0]?.status || "unfulfilled";
      if (s.toLowerCase() !== fulfillmentFilter.toLowerCase()) return false;
    }
    if (periodFilter !== "all") {
      const d = new Date(order.created_at), today = new Date();
      if (periodFilter === "today") return d.toDateString() === today.toDateString();
      if (periodFilter === "yesterday") { const y = new Date(); y.setDate(today.getDate() - 1); return d.toDateString() === y.toDateString(); }
      if (periodFilter === "this_week") { const w = new Date(); w.setDate(today.getDate() - 7); return d >= w; }
    }
    if (startDate && endDate) {
      const od = new Date(order.created_at);
      if (od < startDate || od > endDate) return false;
    }
    return true;
  });

  return (
    <div style={{ color: "var(--text-main)", minHeight: "100%", paddingBottom: "40px" }}>
      <style>{`
        .cs-item-hover:hover{background:rgba(114,57,234,0.15)!important}
        .trow:hover{background:rgba(255,255,255,0.015)!important}
        .sd-item:hover{background:rgba(255,255,255,0.06)!important}
        .trow-selected{background:rgba(114,57,234,0.06)!important}
        @keyframes spin{to{transform:rotate(360deg)}}
      `}</style>

      {/* Toast */}
      {message && (
        <div style={{ position: "fixed", top: "20px", right: "20px", zIndex: 10001, background: message.type === "error" ? "#f1416c" : message.type === "info" ? "#00a3ff" : "#50cd89", color: "white", padding: "12px 20px", borderRadius: "8px", boxShadow: "0 4px 16px rgba(0,0,0,0.3)", fontWeight: "600", fontSize: "0.85rem" }}>
          {message.text}
        </div>
      )}

      {/* Page Header */}
      <div className="page-header" style={{ marginBottom: "12px" }}>
        <div>
          <h2 className="page-title" style={{ display: "flex", alignItems: "center", gap: "8px", fontSize: "1.05rem" }}>
            <svg style={{ width: "18px", height: "18px" }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            {isAbandonedPage ? "Abandoned Orders" : "Orders"}
          </h2>
          <p className="page-subtitle">Manage, assign, and track all orders</p>
        </div>
        <div style={{ display: "flex", gap: "8px", alignItems: "center" }}>
          {/* Period tabs */}
          <div style={{ display: "flex", background: "var(--border-color)", padding: "2px", borderRadius: "6px", gap: "1px" }}>
            {[["all","All"],["today","Today"],["yesterday","Yesterday"],["this_week","This week"]].map(([v,l]) => (
              <button key={v} onClick={() => setPeriodFilter(v)} style={{ padding: "5px 10px", borderRadius: "5px", fontSize: "0.7rem", fontWeight: "700", background: periodFilter === v ? "var(--bg-card)" : "transparent", color: periodFilter === v ? "var(--text-main)" : "var(--text-muted)", border: "none", cursor: "pointer" }}>{l}</button>
            ))}
            <div className="more-dd" style={{ position: "relative" }}>
              <button onClick={() => setIsMoreOpen(v => !v)} style={{ padding: "5px 10px", borderRadius: "5px", fontSize: "0.7rem", fontWeight: "700", background: "transparent", color: "var(--text-muted)", border: "none", cursor: "pointer", display: "flex", alignItems: "center", gap: "3px" }}>
                More <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M6 9l6 6 6-6"/></svg>
              </button>
              {isMoreOpen && (
                <div className="custom-more-dropdown">
                  <div className="more-options-section">
                    <span className="section-title">MORE</span>
                    {[["last7","Last 7 days"],["last30","Last 30 days"],["this_month","This month"]].map(([v,l]) => (
                      <div key={v} className="option-item" onClick={() => { setPeriodFilter(v); setIsMoreOpen(false); }}>{l}</div>
                    ))}
                  </div>
                  <div className="calendar-headers">
                    <div className="calendar-header-label">FROM</div>
                    <div className="calendar-header-label">TO</div>
                  </div>
                  <div className="calendar-picker-section">
                    <DatePicker selectsRange startDate={startDate} endDate={endDate} onChange={setDateRange} monthsShown={2} inline />
                  </div>
                  <div className="dropdown-footer">
                    <button onClick={() => setIsMoreOpen(false)} className="apply-btn">Apply</button>
                    <button onClick={() => { setDateRange([null, null]); }} className="clear-btn">Clear</button>
                  </div>
                </div>
              )}
            </div>
          </div>
          <button onClick={() => setIsCreateModalOpen(true)} style={{ display: "flex", alignItems: "center", gap: "6px", padding: "6px 14px", background: "var(--purple)", color: "white", borderRadius: "6px", fontSize: "0.8rem", fontWeight: "700", border: "none", cursor: "pointer" }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Order
          </button>
        </div>
      </div>

      {/* Metrics */}
      <div style={{ display: "grid", gridTemplateColumns: "repeat(5,1fr)", gap: "10px", marginBottom: "12px" }}>
        {[
          { label: "TOTAL ORDERS",   value: metrics.total_orders,     icon: Icons.total,     bg: "#e1e9ff", ic: "#6993ff", sub: `${metrics.today_orders || 0} today` },
          { label: "CONFIRMED",      value: metrics.confirmed,        icon: Icons.confirmed, bg: "#c9f7f5", ic: "#1bc5bd", sub: `${metrics.total_orders > 0 ? Math.round((metrics.confirmed/metrics.total_orders)*100) : 0}% rate` },
          { label: "CANCELLED",      value: metrics.cancelled,        icon: Icons.cancelled, bg: "#ffe2e5", ic: "#f64e60", sub: `${metrics.total_orders > 0 ? Math.round((metrics.cancelled/metrics.total_orders)*100) : 0}% rate` },
          { label: "FAILED DELIVERY",value: metrics.failed_delivery || 0, icon: Icons.cancelled, bg: "#ffe2e5", ic: "#f64e60", sub: "failed" },
          { label: "DELIVERY RATE",  value: metrics.delivery_rate || "0%", icon: Icons.rate, bg: "#e1e9ff", ic: "#6993ff", sub: "rate" },
        ].map((c, i) => (
          <div key={i} style={{ background: "var(--bg-card)", border: "1px solid var(--border-color)", borderRadius: "8px", padding: "12px" }}>
            <div style={{ display: "flex", alignItems: "center", gap: "10px", marginBottom: "8px" }}>
              <div style={{ width: "34px", height: "34px", borderRadius: "50%", background: c.bg, display: "flex", alignItems: "center", justifyContent: "center", color: c.ic }}>{c.icon}</div>
              <span style={{ fontSize: "0.6rem", fontWeight: "700", color: "var(--text-muted)", letterSpacing: "0.3px" }}>{c.label}</span>
            </div>
            <div style={{ fontSize: "1.5rem", fontWeight: "800" }}>{c.value}</div>
            <div style={{ fontSize: "0.65rem", color: "var(--text-muted)", marginTop: "3px" }}>{c.sub}</div>
          </div>
        ))}
      </div>

      {/* Filters row */}
      <div style={{ display: "flex", gap: "8px", marginBottom: "10px", flexWrap: "wrap", alignItems: "center" }}>
        <div style={{ flex: 1, minWidth: "180px", position: "relative" }}>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ position: "absolute", left: "10px", top: "50%", transform: "translateY(-50%)", color: "var(--text-muted)" }}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search orders..." style={{ width: "100%", padding: "7px 10px 7px 30px", borderRadius: "6px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.75rem", outline: "none", boxSizing: "border-box" }} />
        </div>
        <CustomSelect id="status" value={statusFilter} onChange={setStatusFilter} openDropdown={openDropdown} setOpenDropdown={setOpenDropdown} placeholder="All Status"
          options={[{ value: "all", label: "All Status" }, ...ORDER_STATUSES.map(s => ({ value: s.value, label: s.label, dot: s.color }))]} />
        <CustomSelect id="fulfillment" value={fulfillmentFilter} onChange={setFulfillmentFilter} openDropdown={openDropdown} setOpenDropdown={setOpenDropdown} placeholder="Fulfillment"
          options={[{ value: "all", label: "All Fulfillment" }, ...FULFILLMENT_STATUSES.map(s => ({ value: s.value, label: s.label, dot: s.color }))]} />
        <CustomSelect id="deliveryCompanies" value={deliverCompaniesFilter} onChange={setDeliverCompaniesFilter} openDropdown={openDropdown} setOpenDropdown={setOpenDropdown} placeholder="All Companies"
          options={[{ value: "all", label: "All Companies" }, ...companies.map(c => ({ value: String(c.id), label: c.name }))]} />
        <CustomSelect id="agent" value={agentFilter} onChange={setAgentFilter} openDropdown={openDropdown} setOpenDropdown={setOpenDropdown} placeholder="All Agents"
          options={[{ value: "all", label: "All Agents" }, ...activeAgents.map(a => ({ value: String(a.id), label: a.name }))]} />
        <CustomSelect id="product" value={productFilter} onChange={setProductFilter} openDropdown={openDropdown} setOpenDropdown={setOpenDropdown} placeholder="All Products"
          options={[{ value: "all", label: "All Products" }, ...products.map(p => ({ value: String(p.id), label: p.title || p.name }))]} />
      </div>

      {/* Table */}
      {loading ? (
        <div style={{ display: "flex", justifyContent: "center", padding: "60px 0" }}>
          <div style={{ border: "3px solid var(--border-color)", borderTop: "3px solid var(--purple)", borderRadius: "50%", width: "32px", height: "32px", animation: "spin 1s linear infinite" }} />
        </div>
      ) : filteredOrders.length === 0 ? (
        <div style={{ display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", padding: "80px 20px", background: "var(--bg-card)", border: "1px solid var(--border-color)", borderRadius: "12px" }}>
          <svg style={{ width: "56px", height: "56px", marginBottom: "16px", opacity: 0.1 }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
          <p style={{ color: "var(--text-muted)", fontWeight: "500" }}>No orders found</p>
        </div>
      ) : (
        <div style={{ background: "var(--bg-card)", border: "1px solid var(--border-color)", borderRadius: "8px", overflow: "hidden" }}>
          <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "0.78rem", textAlign: "left" }}>
            <thead>
              <tr style={{ borderBottom: "1px solid var(--border-color)", background: "rgba(255,255,255,0.02)" }}>
                {/* Checkbox + bulk actions */}
                <th style={{ padding: "10px 12px", width: "36px" }}>
                  <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                    <input type="checkbox" checked={allChecked} onChange={toggleAll}
                      style={{ width: "15px", height: "15px", accentColor: "#7239ea", cursor: "pointer" }} />
                    {selectedIds.length > 0 && (
                      <BulkActionsMenu
                        selectedIds={selectedIds}
                        agents={activeAgents}
                        onUpdateStatus={handleBulkUpdateStatus}
                        onAssign={handleBulkAssign}
                        onCreateParcel={handleBulkCreateParcel}
                        onClear={() => setSelectedIds([])}
                      />
                    )}
                  </div>
                </th>
                {["ORDER", "AGENT", "TRACKING", "CLIENT", "CITY", "STATUS", "FULFILLMENT", "TOTAL", "DATE"].map(h => (
                  <th key={h} style={{ padding: "10px 12px", fontWeight: "700", color: "var(--text-muted)", fontSize: "0.63rem", letterSpacing: "0.4px" }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filteredOrders.map(order => {
                const isSelected = selectedIds.includes(order.id);
                const fulfillmentStatus = order.shipments?.[0]?.status || "unfulfilled";
                const trackingNumber = order.shipments?.[0]?.tracking_number;
                const city = order.client?.city || order.shipments?.[0]?.city || "—";
                return (
                  <tr
                    key={order.id}
                    className={`trow${isSelected ? " trow-selected" : ""}`}
                    style={{ borderBottom: "1px solid var(--border-color)", cursor: "pointer" }}
                    onClick={() => setSelectedOrder(order)}
                  >
                    {/* Checkbox */}
                    <td style={{ padding: "10px 12px" }} onClick={e => e.stopPropagation()}>
                      <input type="checkbox" checked={isSelected} onChange={() => toggleOne(order.id)}
                        style={{ width: "15px", height: "15px", accentColor: "#7239ea", cursor: "pointer" }} />
                    </td>

                    {/* Order number */}
                    <td style={{ padding: "10px 12px", fontWeight: "700" }}>
                      <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
                        {order.shop?.favicon_url && (
                          <img src={order.shop.favicon_url} alt="" style={{ width: "16px", height: "16px", borderRadius: "3px" }} />
                        )}
                        <span style={{ color: "var(--purple)", textDecoration: "underline" }}>{order.order_number}</span>
                      </div>
                    </td>

                    {/* Agent */}
                    <td style={{ padding: "10px 12px" }} onClick={e => e.stopPropagation()}>
                      <select
                        value={order.assigned_to || ""}
                        onChange={e => handleAssignAgent(order.id, e.target.value)}
                        style={{ padding: "4px 8px", borderRadius: "6px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.75rem", outline: "none", cursor: "pointer", maxWidth: "110px" }}
                      >
                        <option value="">—</option>
                        {activeAgents.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}
                      </select>
                    </td>

                    {/* Tracking */}
                    <td style={{ padding: "10px 12px", color: "var(--text-muted)", fontSize: "0.73rem" }}>
                      {trackingNumber || "—"}
                    </td>

                    {/* Client */}
                    <td style={{ padding: "10px 12px" }}>
                      <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
                        {order.client?.phone && (
                          <a href={`https://wa.me/${order.client.phone}`} target="_blank" rel="noreferrer" onClick={e => e.stopPropagation()}
                            style={{ color: "#25D366", display: "flex", alignItems: "center", flexShrink: 0 }}>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="#25D366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                          </a>
                        )}
                        <div>
                          <div style={{ fontWeight: "700", fontSize: "0.78rem" }}>{order.client?.name || "—"}</div>
                          <div style={{ color: "var(--text-muted)", fontSize: "0.68rem" }}>{order.client?.phone || "—"}</div>
                        </div>
                      </div>
                    </td>

                    {/* City */}
                    <td style={{ padding: "10px 12px", color: "var(--text-muted)", fontSize: "0.75rem", maxWidth: "90px" }}>
                      <span style={{ display: "block", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{city}</span>
                    </td>

                    {/* Status inline dropdown */}
                    <td style={{ padding: "10px 12px" }} onClick={e => e.stopPropagation()}>
                      <StatusDropdown orderId={order.id} currentStatus={order.status} onChange={handleUpdateStatus} />
                    </td>

                    {/* Fulfillment badge */}
                    <td style={{ padding: "10px 12px" }}>
                      <FulfillmentBadge status={fulfillmentStatus} />
                    </td>

                    {/* Total */}
                    <td style={{ padding: "10px 12px", fontWeight: "700", whiteSpace: "nowrap" }}>
                      {parseFloat(order.total_price || 0).toFixed(2)} MAD
                    </td>

                    {/* Date */}
                    <td style={{ padding: "10px 12px", color: "var(--text-muted)", fontSize: "0.72rem", whiteSpace: "nowrap" }}>
                      {new Date(order.created_at).toLocaleDateString("fr-FR", { day: "2-digit", month: "short", year: "numeric" })}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {/* Pagination */}
          <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "10px 16px", borderTop: "1px solid var(--border-color)", background: "rgba(255,255,255,0.01)" }}>
            <div style={{ fontSize: "0.75rem", color: "var(--text-muted)" }}>
              Page {currentPage} of {totalPages} ({totalOrders} orders)
              <span style={{ marginLeft: "16px" }}>Per page:</span>
              <select
                value={perPage}
                onChange={e => { setPerPage(Number(e.target.value)); setCurrentPage(1); }}
                style={{ marginLeft: "6px", padding: "3px 6px", borderRadius: "5px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.75rem", cursor: "pointer", outline: "none" }}
              >
                {PER_PAGE_OPTIONS.map(n => <option key={n} value={n}>{n}</option>)}
              </select>
            </div>
            <div style={{ display: "flex", gap: "6px" }}>
              <button
                onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                disabled={currentPage === 1}
                style={{ padding: "5px 12px", borderRadius: "6px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: currentPage === 1 ? "var(--text-muted)" : "var(--text-main)", cursor: currentPage === 1 ? "not-allowed" : "pointer", fontSize: "0.78rem", fontWeight: "600" }}
              >
                Previous
              </button>
              <button
                onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                disabled={currentPage === totalPages}
                style={{ padding: "5px 12px", borderRadius: "6px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: currentPage === totalPages ? "var(--text-muted)" : "var(--text-main)", cursor: currentPage === totalPages ? "not-allowed" : "pointer", fontSize: "0.78rem", fontWeight: "600" }}
              >
                Next
              </button>
            </div>
          </div>
        </div>
      )}

      {/* New Order Modal */}
      {isCreateModalOpen && (
        <NewOrderModal
          products={products}
          isAbandonedPage={isAbandonedPage}
          onClose={() => setIsCreateModalOpen(false)}
          onSuccess={(msg) => { showToast(msg); setIsCreateModalOpen(false); fetchOrders(); }}
        />
      )}

      {/* Order Detail Drawer */}
      {selectedOrder && (
        <div className="order-modal-overlay" onClick={() => setSelectedOrder(null)}>
          <div className="order-modal-content" onClick={e => e.stopPropagation()}>
            <OrderDetails order={selectedOrder} onClose={() => setSelectedOrder(null)} />
          </div>
        </div>
      )}
    </div>
  );
}