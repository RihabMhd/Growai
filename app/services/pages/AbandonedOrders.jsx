import { useState, useEffect, useContext, useRef } from "react";
import { AuthContext } from "../auth/AuthContext";
import api from "../api/axios";

// ── helpers ───────────────────────────────────────────────────────────────────
const fmt = (n) => Number(n || 0).toFixed(2);

const LANGUAGES = [
  { value: "fr", label: "French (fr)" },
  { value: "ar", label: "Arabic (ar)" },
  { value: "en", label: "English (en)" },
];

const PLACEHOLDERS = ["{{customer_name}}", "{{recovery_url}}", "{{total}}", "{{currency}}", "{{shop_name}}"];

// ── Recovery Rules Panel ──────────────────────────────────────────────────────
function RecoveryRulesPanel({ onClose }) {
  const [stages, setStages] = useState([
    { id: 1, enabled: false, delay: 60,   language: "fr", template: "", params: ["{{customer_name}}", "{{recovery_url}}"], urlSuffix: "" },
    { id: 2, enabled: false, delay: 1440, language: "fr", template: "", params: ["{{customer_name}}", "{{recovery_url}}"], urlSuffix: "" },
    { id: 3, enabled: false, delay: 4320, language: "fr", template: "", params: ["{{customer_name}}", "{{recovery_url}}"], urlSuffix: "" },
  ]);
  const [saving, setSaving] = useState(false);
  const nextId = useRef(4);

  const updateStage = (id, key, val) =>
    setStages(prev => prev.map(s => s.id === id ? { ...s, [key]: val } : s));

  const updateParam = (id, idx, val) =>
    setStages(prev => prev.map(s => s.id === id ? { ...s, params: s.params.map((p, i) => i === idx ? val : p) } : s));

  const addParam = (id) =>
    setStages(prev => prev.map(s => s.id === id ? { ...s, params: [...s.params, ""] } : s));

  const removeParam = (id, idx) =>
    setStages(prev => prev.map(s => s.id === id ? { ...s, params: s.params.filter((_, i) => i !== idx) } : s));

  const addStage = () => {
    setStages(prev => [...prev, { id: nextId.current++, enabled: false, delay: 60, language: "fr", template: "", params: ["{{customer_name}}", "{{recovery_url}}"], urlSuffix: "" }]);
  };

  const removeStage = (id) => setStages(prev => prev.filter(s => s.id !== id));

  const handleSave = async () => {
    try {
      setSaving(true);
      await api.post("/recovery-rules", { stages });
      onClose();
    } catch (e) {
      console.error(e);
      alert("Erreur lors de la sauvegarde.");
    } finally {
      setSaving(false);
    }
  };

  const panelInput = {
    width: "100%", padding: "9px 12px", borderRadius: "7px",
    background: "#fff", border: "1px solid #e2e8f0",
    color: "#1a202c", fontSize: "0.83rem", outline: "none", boxSizing: "border-box",
  };

  return (
    <>
      {/* Backdrop */}
      <div onClick={onClose} style={{ position: "fixed", inset: 0, background: "rgba(0,0,0,0.35)", zIndex: 10000 }}/>

      {/* Slide-over panel */}
      <div style={{
        position: "fixed", top: 0, right: 0, height: "100vh", width: "100%", maxWidth: "500px",
        background: "#fff", zIndex: 10001, display: "flex", flexDirection: "column",
        boxShadow: "-8px 0 40px rgba(0,0,0,0.18)", animation: "slideIn 0.25s ease-out"
      }}>
        <style>{`
          @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
          .rr-input:focus { border-color: #7239ea !important; box-shadow: 0 0 0 2px rgba(114,57,234,0.12); }
          .rr-stage { border: 1px solid #e8eaf0; border-radius: 10px; padding: 18px; margin-bottom: 14px; background: #fafafa; }
          .rr-label { font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 6px; }
          .rr-param-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
          .rr-param-idx { font-size: 0.75rem; color: #94a3b8; font-weight: 600; min-width: 36px; }
          .rr-remove-btn:hover { background: #fee2e2 !important; color: #ef4444 !important; }
          .rr-add-param:hover { color: #7239ea; }
        `}</style>

        {/* Header */}
        <div style={{ display: "flex", alignItems: "flex-start", justifyContent: "space-between", padding: "20px 24px 16px", borderBottom: "1px solid #f0f0f5", flexShrink: 0 }}>
          <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
            <div style={{ width: "32px", height: "32px", background: "rgba(114,57,234,0.1)", borderRadius: "8px", display: "flex", alignItems: "center", justifyContent: "center" }}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#7239ea" strokeWidth="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            </div>
            <div>
              <h3 style={{ fontSize: "1rem", fontWeight: "700", color: "#1a202c", margin: 0 }}>Auto-recovery rules</h3>
              <p style={{ fontSize: "0.75rem", color: "#64748b", margin: "4px 0 0", lineHeight: "1.4", maxWidth: "340px" }}>
                Each enabled stage sends one WhatsApp message after the configured delay (counted from the abandoned-checkout time). The cron runs every 10 minutes.
              </p>
            </div>
          </div>
          <button onClick={onClose} style={{ width: "28px", height: "28px", borderRadius: "50%", background: "#f1f5f9", border: "none", color: "#64748b", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0, marginTop: "2px" }}>✕</button>
        </div>

        {/* Body */}
        <div style={{ flex: 1, overflowY: "auto", padding: "20px 24px" }}>
          {stages.map((stage, stageIdx) => (
            <div key={stage.id} className="rr-stage">
              {/* Stage header */}
              <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: "16px" }}>
                <label style={{ display: "flex", alignItems: "center", gap: "8px", cursor: "pointer" }}>
                  <input type="checkbox" checked={stage.enabled} onChange={e => updateStage(stage.id, "enabled", e.target.checked)}
                    style={{ width: "15px", height: "15px", accentColor: "#7239ea", cursor: "pointer" }}/>
                  <span style={{ fontWeight: "700", fontSize: "0.88rem", color: "#1a202c" }}>Stage {stageIdx + 1}</span>
                </label>
                <button onClick={() => removeStage(stage.id)} className="rr-remove-btn" style={{ display: "flex", alignItems: "center", gap: "4px", padding: "4px 8px", borderRadius: "6px", background: "transparent", border: "none", color: "#94a3b8", fontSize: "0.75rem", fontWeight: "600", cursor: "pointer" }}>
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                  Remove
                </button>
              </div>

              {/* Delay + Language */}
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "12px", marginBottom: "12px" }}>
                <div>
                  <label className="rr-label">Delay (minutes)</label>
                  <input className="rr-input" type="number" min="1" value={stage.delay}
                    onChange={e => updateStage(stage.id, "delay", e.target.value)}
                    style={panelInput}/>
                </div>
                <div>
                  <label className="rr-label">Language</label>
                  <select className="rr-input" value={stage.language}
                    onChange={e => updateStage(stage.id, "language", e.target.value)}
                    style={{ ...panelInput, appearance: "none", cursor: "pointer", backgroundImage: `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E")`, backgroundRepeat: "no-repeat", backgroundPosition: "right 10px center", paddingRight: "28px" }}>
                    {LANGUAGES.map(l => <option key={l.value} value={l.value}>{l.label}</option>)}
                  </select>
                </div>
              </div>

              {/* Template name */}
              <div style={{ marginBottom: "12px" }}>
                <label className="rr-label">Template name (must be approved in Meta)</label>
                <input className="rr-input" placeholder="e.g. abandoned_recovery_v1" value={stage.template}
                  onChange={e => updateStage(stage.id, "template", e.target.value)}
                  style={panelInput}/>
              </div>

              {/* Body params */}
              <div style={{ marginBottom: "12px" }}>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "8px" }}>
                  <label className="rr-label" style={{ marginBottom: 0 }}>Body parameters ({`{{1}}, {{2}} ...`})</label>
                  <button onClick={() => addParam(stage.id)} className="rr-add-param" style={{ fontSize: "0.75rem", fontWeight: "700", color: "#7239ea", background: "none", border: "none", cursor: "pointer" }}>+ add</button>
                </div>
                {stage.params.map((param, pIdx) => (
                  <div key={pIdx} className="rr-param-row">
                    <span className="rr-param-idx">{`{{${pIdx + 1}}}`}</span>
                    <input className="rr-input" value={param}
                      onChange={e => updateParam(stage.id, pIdx, e.target.value)}
                      style={{ ...panelInput, flex: 1 }}/>
                    <button onClick={() => removeParam(stage.id, pIdx)} className="rr-remove-btn" style={{ width: "26px", height: "26px", borderRadius: "5px", background: "transparent", border: "none", color: "#94a3b8", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/></svg>
                    </button>
                  </div>
                ))}
              </div>

              {/* URL suffix */}
              <div>
                <label className="rr-label">URL button dynamic suffix (optional)</label>
                <input className="rr-input" placeholder="e.g. checkouts/{{recovery_url}}" value={stage.urlSuffix}
                  onChange={e => updateStage(stage.id, "urlSuffix", e.target.value)}
                  style={panelInput}/>
              </div>
            </div>
          ))}

          {/* Add stage */}
          <button onClick={addStage} style={{ width: "100%", padding: "12px", borderRadius: "8px", border: "1.5px dashed #cbd5e1", background: "transparent", color: "#64748b", fontSize: "0.83rem", fontWeight: "600", cursor: "pointer", display: "flex", alignItems: "center", justifyContent: "center", gap: "6px", marginBottom: "16px", transition: "all 0.2s" }}
            onMouseEnter={e => { e.currentTarget.style.borderColor = "#7239ea"; e.currentTarget.style.color = "#7239ea"; }}
            onMouseLeave={e => { e.currentTarget.style.borderColor = "#cbd5e1"; e.currentTarget.style.color = "#64748b"; }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add stage
          </button>

          {/* Placeholders hint */}
          <div style={{ padding: "10px 14px", background: "#f8fafc", borderRadius: "8px", border: "1px solid #e2e8f0" }}>
            <span style={{ fontSize: "0.72rem", color: "#64748b" }}>
              <strong>Placeholders:</strong> {PLACEHOLDERS.join(", ")}
            </span>
          </div>
        </div>

        {/* Footer */}
        <div style={{ display: "flex", gap: "10px", padding: "16px 24px", borderTop: "1px solid #f0f0f5", flexShrink: 0 }}>
          <button onClick={onClose} style={{ flex: 1, padding: "10px", borderRadius: "8px", background: "#f1f5f9", border: "none", color: "#475569", fontSize: "0.85rem", fontWeight: "600", cursor: "pointer" }}>Cancel</button>
          <button onClick={handleSave} disabled={saving} style={{ flex: 2, padding: "10px", borderRadius: "8px", background: "#7239ea", border: "none", color: "white", fontSize: "0.85rem", fontWeight: "700", cursor: saving ? "not-allowed" : "pointer", opacity: saving ? 0.7 : 1, display: "flex", alignItems: "center", justifyContent: "center", gap: "7px" }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            {saving ? "Saving..." : "Save rules"}
          </button>
        </div>
      </div>
    </>
  );
}

// ── Main Component ────────────────────────────────────────────────────────────
export default function AbandonedOrders() {
  const { user } = useContext(AuthContext);

  const [orders, setOrders]       = useState([]);
  const [metrics, setMetrics]     = useState({ open: 0, open_value: 0, recovered: 0, recovered_value: 0, recovery_rate: 0, wa_sent: 0, wa_attempts: 0 });
  const [loading, setLoading]     = useState(true);
  const [showRules, setShowRules] = useState(false);
  const [syncing, setSyncing]     = useState(false);
  const [message, setMessage]     = useState(null);

  // Filters
  const [period, setPeriod]         = useState("30");
  const [statusFilter, setStatus]   = useState("all");
  const [hasPhone, setHasPhone]     = useState(false);
  const [search, setSearch]         = useState("");

  const fetchOrders = async () => {
    try {
      setLoading(true);
      const res = await api.get("/orders", {
        params: { type: "abandoned", search: search || undefined, status: statusFilter !== "all" ? statusFilter : undefined }
      });
      const data = Array.isArray(res.data?.orders) ? res.data.orders : [];
      setOrders(data);

      // Build metrics from orders
      const open      = data.filter(o => !["confirmed","delivered"].includes(o.status));
      const recovered = data.filter(o => ["confirmed","delivered"].includes(o.status));
      const openVal   = open.reduce((s, o) => s + parseFloat(o.total_price || 0), 0);
      const recVal    = recovered.reduce((s, o) => s + parseFloat(o.total_price || 0), 0);
      const rate      = data.length > 0 ? ((recovered.length / data.length) * 100).toFixed(1) : "0.0";
      setMetrics({ open: open.length, open_value: openVal, recovered: recovered.length, recovered_value: recVal, recovery_rate: rate, wa_sent: 0, wa_attempts: 0 });
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchOrders(); }, [search, statusFilter, period]);

  const handleSync = async () => {
    try {
      setSyncing(true);
      await api.post("/orders/sync-abandoned");
      await fetchOrders();
      showToast("Sync complete.");
    } catch {
      showToast("Sync failed.", "error");
    } finally {
      setSyncing(false);
    }
  };

  const showToast = (text, type = "success") => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 4000);
  };

  const handleUpdateStatus = async (orderId, newStatus) => {
    try {
      await api.put(`/orders/${orderId}`, { status: newStatus });
      setOrders(prev => prev.map(o => o.id === orderId ? { ...o, status: newStatus } : o));
      fetchOrders();
    } catch { alert("Erreur."); }
  };

  // Frontend filters
  const filtered = orders.filter(o => {
    if (hasPhone && !o.client?.phone) return false;
    if (search) {
      const q = search.toLowerCase();
      const name  = (o.client?.name || "").toLowerCase();
      const email = (o.client?.email || "").toLowerCase();
      const phone = (o.client?.phone || "").toLowerCase();
      if (!name.includes(q) && !email.includes(q) && !phone.includes(q)) return false;
    }
    if (statusFilter !== "all" && o.status !== statusFilter) return false;
    return true;
  });

  const statusBadge = (s) => {
    const map = {
      pending:   { bg: "rgba(255,199,0,0.12)",    text: "#d97706", label: "Pending" },
      confirmed: { bg: "rgba(80,205,137,0.12)",   text: "#059669", label: "Confirmed" },
      cancelled: { bg: "rgba(241,65,108,0.12)",   text: "#dc2626", label: "Cancelled" },
      recovered: { bg: "rgba(114,57,234,0.12)",   text: "#7239ea", label: "Recovered" },
    };
    const style = map[s] || { bg: "rgba(100,116,139,0.1)", text: "#64748b", label: s };
    return (
      <span style={{ display: "inline-block", padding: "3px 10px", borderRadius: "20px", background: style.bg, color: style.text, fontSize: "0.72rem", fontWeight: "700" }}>
        {style.label}
      </span>
    );
  };

  const timeAgo = (dateStr) => {
    const diff = Date.now() - new Date(dateStr).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    return `${Math.floor(h / 24)}d ago`;
  };

  // Light-theme card style (matches screenshot — white background)
  const metricCard = { background: "#fff", border: "1px solid #edf0f7", borderRadius: "12px", padding: "20px 22px", display: "flex", alignItems: "center", gap: "16px" };
  const metricIcon = { width: "42px", height: "42px", borderRadius: "10px", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 };

  return (
    <div style={{ color: "#1a202c", minHeight: "100%", paddingBottom: "40px", background: "var(--bg-app, #f8fafc)" }}>
      <style>{`
        .ab-row:hover { background: #f8faff !important; }
        .ab-action-btn:hover { background: #ede9fe !important; color: #7239ea !important; }
        .ab-filter-select { padding: 7px 28px 7px 10px; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; color: #374151; font-size: 0.8rem; font-weight: 500; appearance: none; cursor: pointer; outline: none; }
        .ab-filter-select:focus { border-color: #7239ea; }
      `}</style>

      {/* Toast */}
      {message && (
        <div style={{ position: "fixed", top: "20px", right: "20px", zIndex: 10002, background: message.type === "error" ? "#ef4444" : "#10b981", color: "white", padding: "11px 22px", borderRadius: "8px", fontWeight: "600", fontSize: "0.85rem", boxShadow: "0 4px 12px rgba(0,0,0,0.15)" }}>{message.text}</div>
      )}

      {/* ── Page Header ── */}
      <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", marginBottom: "22px" }}>
        <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1a202c" strokeWidth="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/><line x1="13" y1="9" x2="17" y2="13"/><line x1="17" y1="9" x2="13" y2="13"/></svg>
          <h2 style={{ fontSize: "1.3rem", fontWeight: "800", color: "#1a202c", margin: 0 }}>Abandoned</h2>
          <span style={{ fontSize: "0.75rem", color: "#94a3b8", fontWeight: "500" }}>{filtered.length} total</span>
        </div>
        <div style={{ display: "flex", gap: "10px" }}>
          <button onClick={() => setShowRules(true)} style={{ display: "flex", alignItems: "center", gap: "7px", padding: "8px 16px", borderRadius: "8px", background: "#fff", border: "1px solid #e2e8f0", color: "#374151", fontSize: "0.82rem", fontWeight: "600", cursor: "pointer", boxShadow: "0 1px 3px rgba(0,0,0,0.06)" }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            Recovery rules
          </button>
          <button onClick={handleSync} disabled={syncing} style={{ display: "flex", alignItems: "center", gap: "7px", padding: "8px 18px", borderRadius: "8px", background: "#7239ea", border: "none", color: "white", fontSize: "0.82rem", fontWeight: "700", cursor: syncing ? "not-allowed" : "pointer", opacity: syncing ? 0.8 : 1 }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" style={{ animation: syncing ? "spin 1s linear infinite" : "none" }}><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
            <style>{`@keyframes spin{to{transform:rotate(360deg)}}`}</style>
            {syncing ? "Syncing..." : "Sync now"}
          </button>
        </div>
      </div>

      {/* ── Metrics Cards ── */}
      <div style={{ display: "grid", gridTemplateColumns: "repeat(4,1fr)", gap: "14px", marginBottom: "22px" }}>
        {/* Open */}
        <div style={metricCard}>
          <div style={{ ...metricIcon, background: "#fff7ed" }}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f97316" strokeWidth="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
          </div>
          <div>
            <div style={{ fontSize: "0.65rem", fontWeight: "700", color: "#94a3b8", letterSpacing: "0.5px", textTransform: "uppercase", marginBottom: "4px" }}>Open (Last 30d)</div>
            <div style={{ fontSize: "1.4rem", fontWeight: "800", color: "#1a202c", lineHeight: 1 }}>{metrics.open}</div>
            <div style={{ fontSize: "0.72rem", color: "#94a3b8", marginTop: "3px" }}>${fmt(metrics.open_value)}</div>
          </div>
        </div>
        {/* Recovered */}
        <div style={metricCard}>
          <div style={{ ...metricIcon, background: "#f0fdf4" }}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" strokeWidth="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <div>
            <div style={{ fontSize: "0.65rem", fontWeight: "700", color: "#94a3b8", letterSpacing: "0.5px", textTransform: "uppercase", marginBottom: "4px" }}>Recovered</div>
            <div style={{ fontSize: "1.4rem", fontWeight: "800", color: "#1a202c", lineHeight: 1 }}>{metrics.recovered}</div>
            <div style={{ fontSize: "0.72rem", color: "#94a3b8", marginTop: "3px" }}>${fmt(metrics.recovered_value)}</div>
          </div>
        </div>
        {/* Recovery rate */}
        <div style={metricCard}>
          <div style={{ ...metricIcon, background: "#faf5ff" }}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#a855f7" strokeWidth="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </div>
          <div>
            <div style={{ fontSize: "0.65rem", fontWeight: "700", color: "#94a3b8", letterSpacing: "0.5px", textTransform: "uppercase", marginBottom: "4px" }}>Recovery Rate</div>
            <div style={{ fontSize: "1.4rem", fontWeight: "800", color: "#1a202c", lineHeight: 1 }}>{metrics.recovery_rate}%</div>
            <div style={{ fontSize: "0.72rem", color: "#94a3b8", marginTop: "3px" }}>{metrics.wa_attempts} of {metrics.wa_attempts} attempts</div>
          </div>
        </div>
        {/* WA sent */}
        <div style={metricCard}>
          <div style={{ ...metricIcon, background: "#eff6ff" }}>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" strokeWidth="2"><path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"/></svg>
          </div>
          <div>
            <div style={{ fontSize: "0.65rem", fontWeight: "700", color: "#94a3b8", letterSpacing: "0.5px", textTransform: "uppercase", marginBottom: "4px" }}>Recovery Sent</div>
            <div style={{ fontSize: "1.4rem", fontWeight: "800", color: "#1a202c", lineHeight: 1 }}>{metrics.wa_sent}</div>
            <div style={{ fontSize: "0.72rem", color: "#94a3b8", marginTop: "3px" }}>WA messages</div>
          </div>
        </div>
      </div>

      {/* ── Filter Bar ── */}
      <div style={{ display: "flex", alignItems: "center", gap: "10px", marginBottom: "16px", flexWrap: "wrap" }}>
        {/* Period */}
        <div style={{ position: "relative" }}>
          <select className="ab-filter-select" value={period} onChange={e => setPeriod(e.target.value)} style={{ paddingRight: "28px" }}>
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 90 days</option>
            <option value="all">All time</option>
          </select>
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" style={{ position: "absolute", right: "9px", top: "50%", transform: "translateY(-50%)", pointerEvents: "none" }}><path d="M6 9l6 6 6-6"/></svg>
        </div>

        {/* Status */}
        <div style={{ position: "relative" }}>
          <select className="ab-filter-select" value={statusFilter} onChange={e => setStatus(e.target.value)} style={{ paddingRight: "28px" }}>
            <option value="all">All statuses</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2.5" style={{ position: "absolute", right: "9px", top: "50%", transform: "translateY(-50%)", pointerEvents: "none" }}><path d="M6 9l6 6 6-6"/></svg>
        </div>

        {/* Has phone */}
        <label style={{ display: "flex", alignItems: "center", gap: "6px", cursor: "pointer", fontSize: "0.82rem", fontWeight: "500", color: "#374151" }}>
          <input type="checkbox" checked={hasPhone} onChange={e => setHasPhone(e.target.checked)}
            style={{ width: "14px", height: "14px", accentColor: "#7239ea", cursor: "pointer" }}/>
          Has phone
        </label>

        {/* Search */}
        <div style={{ flex: 1, minWidth: "200px", position: "relative" }}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" strokeWidth="2" style={{ position: "absolute", left: "10px", top: "50%", transform: "translateY(-50%)" }}><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search name, email, phone..."
            style={{ width: "100%", padding: "7px 12px 7px 30px", borderRadius: "7px", border: "1px solid #e2e8f0", background: "#fff", color: "#374151", fontSize: "0.8rem", outline: "none", boxSizing: "border-box" }}/>
        </div>
      </div>

      {/* ── Table ── */}
      <div style={{ background: "#fff", border: "1px solid #edf0f7", borderRadius: "12px", overflow: "hidden", boxShadow: "0 1px 4px rgba(0,0,0,0.04)" }}>
        <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "0.83rem" }}>
          <thead>
            <tr style={{ borderBottom: "1px solid #f0f4f8", background: "#fafbfc" }}>
              {["CUSTOMER", "ITEMS", "TOTAL", "ABANDONED", "RECOVERY", "STATUS", "ACTIONS"].map(h => (
                <th key={h} style={{ padding: "12px 18px", fontWeight: "700", color: "#94a3b8", fontSize: "0.68rem", letterSpacing: "0.6px", textAlign: "left" }}>{h}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr><td colSpan={7} style={{ textAlign: "center", padding: "60px 0" }}>
                <div style={{ display: "inline-block", width: "28px", height: "28px", border: "3px solid #e2e8f0", borderTop: "3px solid #7239ea", borderRadius: "50%", animation: "spin 1s linear infinite" }}/>
              </td></tr>
            ) : filtered.length === 0 ? (
              <tr><td colSpan={7}>
                <div style={{ display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", padding: "70px 20px" }}>
                  <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" strokeWidth="1.5" style={{ marginBottom: "14px" }}><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/><line x1="13" y1="9" x2="17" y2="13"/><line x1="17" y1="9" x2="13" y2="13"/></svg>
                  <p style={{ color: "#94a3b8", fontSize: "0.9rem", fontWeight: "500" }}>No abandoned checkouts in this period.</p>
                </div>
              </td></tr>
            ) : filtered.map(order => (
              <tr key={order.id} className="ab-row" style={{ borderBottom: "1px solid #f4f6f9" }}>
                {/* Customer */}
                <td style={{ padding: "14px 18px" }}>
                  <div style={{ fontWeight: "700", color: "#1a202c" }}>{order.client?.name || "—"}</div>
                  <div style={{ fontSize: "0.72rem", color: "#94a3b8", marginTop: "2px" }}>{order.client?.phone || order.client?.email || "—"}</div>
                </td>
                {/* Items */}
                <td style={{ padding: "14px 18px" }}>
                  {order.items?.length > 0 ? order.items.slice(0, 2).map((item, i) => (
                    <div key={i} style={{ fontSize: "0.78rem", color: "#374151", marginBottom: "2px" }}>
                      {item.product_name} <span style={{ color: "#7239ea", fontWeight: "700" }}>×{item.quantity}</span>
                    </div>
                  )) : <span style={{ color: "#94a3b8" }}>—</span>}
                  {order.items?.length > 2 && <div style={{ fontSize: "0.68rem", color: "#94a3b8" }}>+{order.items.length - 2} more</div>}
                </td>
                {/* Total */}
                <td style={{ padding: "14px 18px", fontWeight: "700", color: "#1a202c" }}>
                  {order.total_price} {order.currency || "MAD"}
                </td>
                {/* Abandoned time */}
                <td style={{ padding: "14px 18px", color: "#64748b", fontSize: "0.78rem" }}>
                  {order.abandoned_at ? timeAgo(order.abandoned_at) : timeAgo(order.created_at)}
                </td>
                {/* Recovery */}
                <td style={{ padding: "14px 18px" }}>
                  <span style={{ fontSize: "0.72rem", color: "#94a3b8", fontWeight: "500" }}>—</span>
                </td>
                {/* Status */}
                <td style={{ padding: "14px 18px" }}>{statusBadge(order.status)}</td>
                {/* Actions */}
                <td style={{ padding: "14px 18px" }}>
                  <div style={{ display: "flex", gap: "6px" }}>
                    {order.status === "pending" && (
                      <button onClick={() => handleUpdateStatus(order.id, "confirmed")} className="ab-action-btn" style={{ padding: "5px 10px", borderRadius: "6px", fontSize: "0.72rem", fontWeight: "700", background: "rgba(114,57,234,0.08)", color: "#7239ea", border: "none", cursor: "pointer", transition: "all 0.15s" }}>
                        Recover
                      </button>
                    )}
                    <button onClick={() => handleUpdateStatus(order.id, "cancelled")} style={{ padding: "5px 10px", borderRadius: "6px", fontSize: "0.72rem", fontWeight: "600", background: "#f8fafc", color: "#64748b", border: "1px solid #e2e8f0", cursor: "pointer" }}>
                      Dismiss
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Recovery Rules Panel */}
      {showRules && <RecoveryRulesPanel onClose={() => setShowRules(false)}/>}
    </div>
  );
}