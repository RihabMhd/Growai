import { useState, useEffect, useContext } from "react";
import { useLocation } from "react-router-dom";
import { AuthContext } from "../auth/AuthContext";
import { useLanguage } from "../context/LanguageContext";
import api from "../api/axios";

// ─── Icons ───────────────────────────────────────────────────────────────────
const Icons = {
  total: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
      <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
    </svg>
  ),
  confirmed: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
  ),
  cancelled: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
    </svg>
  ),
  pending: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
    </svg>
  ),
  rate: (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
    </svg>
  )
};

const getStatusStyle = (s) => {
  switch (s) {
    case "confirmed": case "delivered": return { bg: "rgba(80,205,137,0.1)",  text: "#50cd89" };
    case "cancelled": case "returned":  return { bg: "rgba(241,65,108,0.1)",  text: "#f1416c" };
    case "processing": case "shipped":  return { bg: "rgba(0,163,255,0.1)",   text: "#00a3ff" };
    default:                            return { bg: "rgba(255,199,0,0.1)",    text: "#ffc700" };
  }
};

// ─── Main Component ───────────────────────────────────────────────────────────
export default function AgentOrders() {
  const { user } = useContext(AuthContext);
  const { t } = useLanguage();
  const location = useLocation();

  const isAbandonedPage = location.pathname.includes("abandonnees");
  const currencySymbol = "DA";

  const agentProducts = user?.products || [];

  // ── State ──────────────────────────────────────────────────────────────────
  const [orders, setOrders] = useState([]);
  const [metrics, setMetrics] = useState({ total_orders: 0, confirmed: 0, cancelled: 0, pending: 0, confirmation_rate: "0%" });
  const [loading, setLoading] = useState(true);
  const [message, setMessage] = useState(null);

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [productFilter, setProductFilter] = useState("all");
  const [periodFilter, setPeriodFilter] = useState("all");

  const [selectedOrder, setSelectedOrder] = useState(null);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [isCreating, setIsCreating] = useState(false);
  const [newOrder, setNewOrder] = useState({
    customer_name: "",
    customer_phone: "",
    customer_email: "",
    province: "",
    city: "",
    street: "",
    shipping_price: 0,
    notes: "",
    product_id: "",
    quantity: 1
  });

  // Update states on detailed sheet
  const [editNote, setEditNote] = useState("");
  const [isUpdating, setIsUpdating] = useState(false);

  // ── Data Fetching ──────────────────────────────────────────────────────────
  const fetchOrders = async () => {
    try {
      setLoading(true);
      const params = {
        type: isAbandonedPage ? "abandoned" : "all",
        search: search || undefined,
        status: statusFilter !== "all" ? statusFilter : undefined
      };
      const res = await api.get("/orders", { params });
      setOrders(Array.isArray(res.data?.orders) ? res.data.orders : []);
      setMetrics(res.data?.metrics || { total_orders: 0, confirmed: 0, cancelled: 0, pending: 0, confirmation_rate: "0%" });
    } catch (err) {
      console.error("Error fetching agent orders:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();
  }, [location.pathname, search, statusFilter]);

  // ── Actions ────────────────────────────────────────────────────────────────
  const handleCreateOrder = async (e) => {
    e.preventDefault();
    if (!newOrder.customer_name?.trim()) { alert("Le nom du client est requis."); return; }
    if (!newOrder.customer_phone?.trim()) { alert("Le téléphone est requis."); return; }
    if (!newOrder.product_id) { alert("Veuillez choisir un produit."); return; }
    try {
      setIsCreating(true);
      const res = await api.post("/orders", {
        customer_name: newOrder.customer_name,
        customer_phone: newOrder.customer_phone,
        customer_email: newOrder.customer_email || null,
        province: newOrder.province || null,
        city: newOrder.city || null,
        street: newOrder.street || null,
        shipping_price: parseFloat(newOrder.shipping_price) || 0,
        notes: newOrder.notes || null,
        is_abandoned: isAbandonedPage,
        items: [{ product_id: parseInt(newOrder.product_id), quantity: parseInt(newOrder.quantity) }]
      });
      showToast(res.data.message || "Commande créée.", "success");
      setIsCreateModalOpen(false);
      setNewOrder({
        customer_name: "",
        customer_phone: "",
        customer_email: "",
        province: "",
        city: "",
        street: "",
        shipping_price: 0,
        notes: "",
        product_id: "",
        quantity: 1
      });
      fetchOrders();
    } catch (err) {
      console.error(err);
      alert("Erreur lors de la création de la commande.");
    } finally {
      setIsCreating(false);
    }
  };

  const handleUpdateNotes = async (orderId) => {
    try {
      setIsUpdating(true);
      const res = await api.put(`/orders/${orderId}`, { notes: editNote });
      showToast("Notes mises à jour avec succès.", "success");
      setOrders(prev => prev.map(o => o.id === orderId ? { ...o, notes: editNote } : o));
      setSelectedOrder(res.data.order);
      fetchOrders();
    } catch (err) {
      console.error(err);
      alert("Erreur lors de la mise à jour des notes.");
    } finally {
      setIsUpdating(false);
    }
  };

  const handleAgentStatusUpdate = async (orderId, newStatus) => {
    try {
      setIsUpdating(true);
      const res = await api.put(`/orders/${orderId}`, { status: newStatus });
      showToast(`Statut changé à ${newStatus}.`, "success");
      setOrders(prev => prev.map(o => o.id === orderId ? { ...o, status: newStatus } : o));
      setSelectedOrder(res.data.order);
      fetchOrders();
    } catch (err) {
      console.error(err);
      alert("Erreur lors de la mise à jour du statut.");
    } finally {
      setIsUpdating(false);
    }
  };

  const openOrderPanel = (order) => {
    setSelectedOrder(order);
    setEditNote(order.notes || "");
  };

  const showToast = (text, type = "success") => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 4000);
  };

  // ── Frontend Filters ───────────────────────────────────────────────────────
  const filteredOrders = orders.filter(order => {
    if (productFilter !== "all" && !order.items?.some(i => i.product_id === parseInt(productFilter))) return false;
    if (periodFilter !== "all") {
      const orderDate = new Date(order.created_at);
      const today = new Date();
      if (periodFilter === "today") return orderDate.toDateString() === today.toDateString();
      if (periodFilter === "yesterday") {
        const y = new Date(); y.setDate(today.getDate() - 1);
        return orderDate.toDateString() === y.toDateString();
      }
      if (periodFilter === "this_week") {
        const w = new Date(); w.setDate(today.getDate() - 7);
        return orderDate >= w;
      }
    }
    return true;
  });

  return (
    <div style={{ color: "var(--text-main)", minHeight: "100%", paddingBottom: "40px" }}>
      
      {/* Toast Alert */}
      {message && (
        <div style={{
          position: "fixed", top: "20px", right: "20px", zIndex: 10000,
          background: message.type === "success" ? "var(--success)" : "var(--danger)",
          color: "white", padding: "12px 24px", borderRadius: "8px",
          boxShadow: "0 4px 12px rgba(0,0,0,0.2)", fontWeight: "600"
        }}>{message.text}</div>
      )}

      {/* ── Page Header ── */}
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "20px" }}>
        <div>
          <h2 style={{ display: "flex", alignItems: "center", gap: "10px", fontSize: "1.4rem", fontWeight: "700", margin: 0 }}>
            <svg style={{ width: "22px", height: "22px", color: "var(--purple)" }} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            {isAbandonedPage ? "Mes Commandes Abandonnées" : "Mes Commandes Assignées"}
          </h2>
          <p style={{ color: "var(--text-muted)", fontSize: "0.78rem", margin: "4px 0 0 0" }}>Traitez vos fiches clients et suivez vos confirmations</p>
        </div>

        <button onClick={() => setIsCreateModalOpen(true)} style={{
          display: "flex", alignItems: "center", gap: "8px", padding: "8px 16px",
          background: "var(--purple)", color: "white", borderRadius: "8px",
          fontSize: "0.82rem", fontWeight: "700", border: "none", cursor: "pointer"
        }}>
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Créer Commande
        </button>
      </div>

      {/* ── Metrics Cards ── */}
      <div style={{ display: "grid", gridTemplateColumns: "repeat(5, 1fr)", gap: "16px", marginBottom: "24px" }}>
        {[
          { label: "TOTAL ASSIGNÉES", value: metrics.total_orders, icon: Icons.total, color: "#7239ea", sub: "toutes périodes" },
          { label: "CONFIRMÉES", value: metrics.confirmed, icon: Icons.confirmed, color: "#50cd89", sub: `${metrics.total_orders > 0 ? Math.round((metrics.confirmed/metrics.total_orders)*100) : 0}% taux` },
          { label: "ANNULÉES", value: metrics.cancelled, icon: Icons.cancelled, color: "#f1416c", sub: `${metrics.total_orders > 0 ? Math.round((metrics.cancelled/metrics.total_orders)*100) : 0}% taux` },
          { label: "EN ATTENTE", value: metrics.pending, icon: Icons.pending, color: "#ffc700", sub: "action requise" },
          { label: "TAUX VALIDATION", value: metrics.confirmation_rate, icon: Icons.rate, color: "#00a3ff", sub: "votre objectif" },
        ].map((card, i) => (
          <div key={i} style={{ background: "var(--bg-card)", border: "1px solid var(--border-color)", borderRadius: "12px", padding: "16px", display: "flex", flexDirection: "column", gap: "6px" }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <span style={{ fontSize: "0.62rem", fontWeight: "700", color: "var(--text-muted)", letterSpacing: "0.3px" }}>{card.label}</span>
              <span style={{ color: card.color }}>{card.icon}</span>
            </div>
            <div style={{ fontSize: "1.5rem", fontWeight: "800", color: "var(--text-main)" }}>{card.value}</div>
            <div style={{ fontSize: "0.7rem", color: "var(--text-muted)" }}>{card.sub}</div>
          </div>
        ))}
      </div>

      {/* ── Filters ── */}
      <div style={{ display: "flex", gap: "12px", marginBottom: "20px" }}>
        <div style={{ flex: 1, position: "relative" }}>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ position: "absolute", left: "12px", top: "50%", transform: "translateY(-50%)", color: "var(--text-muted)" }}>
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Chercher par nom, téléphone, numéro..."
            style={{ width: "100%", padding: "8px 12px 8px 36px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.8rem", outline: "none" }} />
        </div>

        <select value={statusFilter} onChange={e => setStatusFilter(e.target.value)}
          style={{ padding: "8px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.8rem", outline: "none", cursor: "pointer" }}>
          <option value="all">Tous les Statuts</option>
          <option value="pending">En attente</option>
          <option value="confirmed">Confirmé</option>
          <option value="cancelled">Annulé</option>
          <option value="processing">En traitement</option>
        </select>

        <select value={periodFilter} onChange={e => setPeriodFilter(e.target.value)}
          style={{ padding: "8px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.8rem", outline: "none", cursor: "pointer" }}>
          <option value="all">Toutes périodes</option>
          <option value="today">Aujourd'hui</option>
          <option value="yesterday">Hier</option>
          <option value="this_week">7 derniers jours</option>
        </select>
      </div>

      {/* ── Table / Grid ── */}
      {loading ? (
        <div style={{ display: "flex", justifyContent: "center", padding: "40px 0" }}>
          <div style={{ border: "3px solid var(--border-color)", borderTop: "3px solid var(--purple)", borderRadius: "50%", width: "28px", height: "28px", animation: "spin 1s linear infinite" }} />
        </div>
      ) : filteredOrders.length === 0 ? (
        <div style={{ padding: "60px", textCenter: "center", background: "var(--bg-card)", borderRadius: "12px", border: "1px solid var(--border-color)", display: "flex", flexDirection: "column", alignItems: "center" }}>
          <p style={{ color: "var(--text-muted)", margin: 0, fontSize: "0.85rem" }}>Aucune commande affectée trouvée.</p>
        </div>
      ) : (
        <div style={{ background: "var(--bg-card)", border: "1px solid var(--border-color)", borderRadius: "12px", overflow: "hidden" }}>
          <table style={{ width: "100%", borderCollapse: "collapse", fontSize: "0.82rem", textAlign: "left" }}>
            <thead>
              <tr style={{ borderBottom: "1px solid var(--border-color)", background: "rgba(255,255,255,0.01)" }}>
                {["COMMANDE", "CLIENT / CODE", "PRODUIT / QTE", "TOTAL", "STATUT", "DATE"].map(h => (
                  <th key={h} style={{ padding: "12px 16px", fontWeight: "600", color: "var(--text-muted)", fontSize: "0.68rem", letterSpacing: "0.3px" }}>{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filteredOrders.map(order => {
                const statusStyle = getStatusStyle(order.status);
                return (
                  <tr key={order.id} style={{ borderBottom: "1px solid var(--border-color)" }}>
                    <td style={{ padding: "12px 16px", fontWeight: "700" }}>
                      <span onClick={() => openOrderPanel(order)} style={{ color: "var(--purple)", cursor: "pointer", textDecoration: "underline" }}>{order.order_number}</span>
                    </td>
                    <td style={{ padding: "12px 16px" }}>
                      <div style={{ fontWeight: "600" }}>{order.client?.name || order.customer_name || "—"}</div>
                      <div style={{ color: "var(--text-muted)", fontSize: "0.7rem", marginTop: "2px" }}>{order.client?.phone || order.customer_phone || "—"}</div>
                    </td>
                    <td style={{ padding: "12px 16px" }}>
                      {order.items?.map((item, idx) => (
                        <div key={idx}>
                          {item.product_name} <span style={{ color: "var(--purple)", fontWeight: "700" }}>x{item.quantity}</span>
                        </div>
                      ))}
                    </td>
                    <td style={{ padding: "12px 16px", fontWeight: "700" }}>{order.total_price} {order.currency || currencySymbol}</td>
                    <td style={{ padding: "12px 16px" }}>
                      <span style={{ display: "inline-block", padding: "4px 8px", borderRadius: "5px", background: statusStyle.bg, color: statusStyle.text, fontSize: "0.68rem", fontWeight: "700" }}>
                        {order.status.toUpperCase()}
                      </span>
                    </td>
                    <td style={{ padding: "12px 16px", color: "var(--text-muted)" }}>{new Date(order.created_at).toLocaleDateString()}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* ── CREATE ORDER MODAL (Matches Modern Sidebar Slide/Panel View System Layouts) ── */}
      {isCreateModalOpen && (
        <div style={{
          position: "fixed", top: 0, left: 0, right: 0, bottom: 0,
          background: "rgba(0, 0, 0, 0.7)", display: "flex", justifyContent: "center",
          alignItems: "center", zIndex: 9999, padding: "20px", backdropFilter: "blur(4px)"
        }} onClick={() => setIsCreateModalOpen(false)}>
          <div style={{
            background: "var(--bg-card)", border: "1px solid var(--border-color)",
            borderRadius: "12px", width: "100%", maxWidth: "460px",
            boxShadow: "0 24px 60px rgba(0,0,0,0.8)", overflow: "hidden",
            display: "flex", flexDirection: "column", animation: "modalAppear 0.25s ease"
          }} onClick={e => e.stopPropagation()}>
            <style>{`@keyframes modalAppear { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }`}</style>
            
            {/* Header */}
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "16px 20px", borderBottom: "1px solid var(--border-color)", background: "rgba(255,255,255,0.01)" }}>
              <div>
                <h3 style={{ fontSize: "1rem", fontWeight: "700", color: "var(--text-main)", margin: 0, display: "flex", alignItems: "center", gap: "8px" }}>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" strokeWidth="2.5"><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                  Create New Order
                </h3>
                <p style={{ fontSize: "0.72rem", color: "var(--text-muted)", margin: "2px 0 0 0" }}>Manually insert order particulars</p>
              </div>
              <button onClick={() => setIsCreateModalOpen(false)} style={{ background: "none", border: "none", color: "var(--text-muted)", cursor: "pointer", padding: "4px", display: "flex", alignItems: "center" }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" /></svg>
              </button>
            </div>

            {/* Body / Form */}
            <form onSubmit={handleCreateOrder} style={{ padding: "20px", display: "flex", flexDirection: "column", gap: "16px", maxHeight: "65vh", overflowY: "auto" }}>
              
              {/* Customer Info */}
              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Client Name *</label>
                <input 
                  type="text" 
                  required
                  placeholder="John Doe"
                  value={newOrder.customer_name} 
                  onChange={e => setNewOrder(p => ({ ...p, customer_name: e.target.value }))}
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                />
              </div>

              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Client Phone *</label>
                <input 
                  type="text" 
                  required
                  placeholder="+213..."
                  value={newOrder.customer_phone} 
                  onChange={e => setNewOrder(p => ({ ...p, customer_phone: e.target.value }))}
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                />
              </div>

              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Client Email</label>
                <input 
                  type="email" 
                  placeholder="john@example.com"
                  value={newOrder.customer_email} 
                  onChange={e => setNewOrder(p => ({ ...p, customer_email: e.target.value }))}
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                />
              </div>

              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "10px" }}>
                <div>
                  <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Province</label>
                  <input 
                    type="text" 
                    placeholder="Province"
                    value={newOrder.province} 
                    onChange={e => setNewOrder(p => ({ ...p, province: e.target.value }))}
                    style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                  />
                </div>
                <div>
                  <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>City</label>
                  <input 
                    type="text" 
                    placeholder="City"
                    value={newOrder.city} 
                    onChange={e => setNewOrder(p => ({ ...p, city: e.target.value }))}
                    style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                  />
                </div>
              </div>

              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Street Address</label>
                <input 
                  type="text" 
                  placeholder="Street"
                  value={newOrder.street} 
                  onChange={e => setNewOrder(p => ({ ...p, street: e.target.value }))}
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                />
              </div>

              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Shipping Price</label>
                <input 
                  type="number" 
                  min="0"
                  step="0.01"
                  placeholder="0.00"
                  value={newOrder.shipping_price} 
                  onChange={e => setNewOrder(p => ({ ...p, shipping_price: e.target.value }))}
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                />
              </div>

              {/* Product Selection */}
              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Product *</label>
                <select 
                  value={newOrder.product_id} 
                  onChange={e => setNewOrder(p => ({ ...p, product_id: e.target.value }))}
                  required
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                >
                  <option value="">Select a product...</option>
                  {agentProducts.map(p => (
                    <option key={p.id} value={p.id}>{p.name}</option>
                  ))}
                </select>
              </div>

              {/* Quantity */}
              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Quantity</label>
                <input 
                  type="number" 
                  min="1" 
                  value={newOrder.quantity} 
                  onChange={e => setNewOrder(p => ({ ...p, quantity: Math.max(1, parseInt(e.target.value) || 1) }))}
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none" }}
                />
              </div>

              {/* Internal Notes */}
              <div>
                <label style={{ display: "block", fontSize: "0.72rem", fontWeight: "700", color: "var(--text-muted)", textTransform: "uppercase", marginBottom: "6px", letterSpacing: "0.5px" }}>Internal Notes</label>
                <textarea 
                  placeholder="Enter additional details or specifications..." 
                  value={newOrder.notes} 
                  onChange={e => setNewOrder(p => ({ ...p, notes: e.target.value }))}
                  rows="4"
                  style={{ width: "100%", padding: "10px 12px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", outline: "none", resize: "none", fontFamily: "inherit" }}
                />
              </div>

              {/* Actions Footer Container */}
              <div style={{ display: "flex", justifyContent: "flex-end", gap: "10px", marginTop: "8px", borderTop: "1px solid var(--border-color)", paddingTop: "14px" }}>
                <button type="button" onClick={() => setIsCreateModalOpen(false)} style={{ padding: "8px 16px", borderRadius: "8px", background: "rgba(255,255,255,0.05)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.82rem", fontWeight: "600", cursor: "pointer" }}>
                  Cancel
                </button>
                <button type="submit" disabled={isCreating} style={{ padding: "8px 18px", borderRadius: "8px", background: "var(--purple)", border: "none", color: "white", fontSize: "0.82rem", fontWeight: "700", cursor: "pointer", display: "flex", alignItems: "center", gap: "6px" }}>
                  {isCreating ? (
                    <>
                      <div style={{ width: "12px", height: "12px", border: "2px solid rgba(255,255,255,0.3)", borderTopColor: "white", borderRadius: "50%", animation: "spin 0.6s linear infinite" }} />
                      Saving...
                    </>
                  ) : "Save Order"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ── Details Panel ── */}
      {selectedOrder && (
        <div style={{ position: "fixed", top: 0, right: 0, bottom: 0, width: "400px", background: "var(--bg-card)", borderLeft: "1px solid var(--border-color)", boxShadow: "-10px 0 35px rgba(0,0,0,0.5)", zIndex: 9500, display: "flex", flexDirection: "column", padding: "20px", gap: "20px" }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
            <div>
              <h3 style={{ fontSize: "1rem", fontWeight: "800", color: "var(--text-main)", margin: 0 }}>Commande {selectedOrder.order_number}</h3>
              <span style={{ fontSize: "0.7rem", color: "var(--text-muted)" }}>Créée le {new Date(selectedOrder.created_at).toLocaleString()}</span>
            </div>
            <button onClick={() => setSelectedOrder(null)} style={{ background: "none", border: "none", color: "var(--text-muted)", cursor: "pointer", fontSize: "1.1rem" }}>✕</button>
          </div>

          <div style={{ display: "flex", flexDirection: "column", gap: "18px", flex: 1, overflowY: "auto" }}>
            {/* Info Client */}
            <div>
              <strong style={{ fontSize: "0.7rem", color: "var(--text-muted)", textTransform: "uppercase", display: "block", marginBottom: "8px" }}>Client Info</strong>
              <div style={{ background: "var(--bg-app)", padding: "10px", borderRadius: "8px", border: "1px solid var(--border-color)" }}>
                <div style={{ fontWeight: "600", fontSize: "0.85rem" }}>{selectedOrder.client?.name || selectedOrder.customer_name || "—"}</div>
                <div style={{ color: "var(--text-muted)", fontSize: "0.78rem", marginTop: "2px" }}>{selectedOrder.client?.phone || selectedOrder.customer_phone || "—"}</div>
              </div>
            </div>

            {/* Articles */}
            <div>
              <strong style={{ fontSize: "0.7rem", color: "var(--text-muted)", textTransform: "uppercase", display: "block", marginBottom: "8px" }}>Articles</strong>
              <div style={{ display: "flex", flexDirection: "column", gap: "6px" }}>
                {selectedOrder.items?.map((item, i) => (
                  <div key={i} style={{ display: "flex", justifyContent: "space-between", fontSize: "0.8rem", background: "rgba(255,255,255,0.01)", padding: "6px 10px", borderRadius: "6px" }}>
                    <span>{item.product_name} <span style={{ color: "var(--purple)", fontWeight: "700" }}>x{item.quantity}</span></span>
                    <strong style={{ color: "var(--purple)" }}>{selectedOrder.total_price} {selectedOrder.currency || currencySymbol}</strong>
                  </div>
                ))}
              </div>
            </div>

            {/* Notes / Edit */}
            <div>
              <strong style={{ fontSize: "0.7rem", color: "var(--text-muted)", textTransform: "uppercase", display: "block", marginBottom: "8px" }}>Notes / Suivi Agent</strong>
              <textarea value={editNote} onChange={e => setEditNote(e.target.value)} rows="3" placeholder="Ajouter des détails sur l'appel ou la livraison..."
                style={{ width: "100%", padding: "8px 10px", borderRadius: "8px", background: "var(--bg-app)", border: "1px solid var(--border-color)", color: "var(--text-main)", fontSize: "0.78rem", outline: "none", resize: "none" }} />
              <button onClick={() => handleUpdateNotes(selectedOrder.id)} disabled={isUpdating}
                style={{ marginTop: "6px", width: "100%", padding: "6px", background: "rgba(114,57,234,0.15)", border: "1px solid var(--purple)", color: "#c4a7ff", borderRadius: "6px", fontSize: "0.75rem", fontWeight: "700", cursor: "pointer" }}>
                {isUpdating ? "Mise à jour..." : "Sauvegarder les Notes"}
              </button>
            </div>

            {/* Change Status Controls */}
            <div>
              <strong style={{ fontSize: "0.7rem", color: "var(--text-muted)", textTransform: "uppercase", display: "block", marginBottom: "8px" }}>Changer le Statut</strong>
              {selectedOrder.status === "pending" ? (
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "8px" }}>
                  <button onClick={() => handleAgentStatusUpdate(selectedOrder.id, "confirmed")} disabled={isUpdating}
                    style={{ padding: "8px", background: "#50cd89", border: "none", color: "white", borderRadius: "6px", fontSize: "0.75rem", fontWeight: "700", cursor: "pointer" }}>
                    Confirmer
                  </button>
                  <button onClick={() => handleAgentStatusUpdate(selectedOrder.id, "cancelled")} disabled={isUpdating}
                    style={{ padding: "8px", background: "#f1416c", border: "none", color: "white", borderRadius: "6px", fontSize: "0.75rem", fontWeight: "700", cursor: "pointer" }}>
                    Annuler
                  </button>
                </div>
              ) : (
                <div style={{ background: "rgba(255,255,255,0.02)", padding: "10px", borderRadius: "6px", border: "1px solid var(--border-color)", fontSize: "0.75rem", color: "var(--text-muted)", textCenter: "center" }}>
                  Statut figé sur <strong style={{ color: getStatusStyle(selectedOrder.status).text }}>{selectedOrder.status.toUpperCase()}</strong> — only pending orders can be updated.
                </div>
              )}
            </div>

            {/* History */}
            <div>
              <strong style={{ fontSize: "0.7rem", color: "var(--text-muted)", textTransform: "uppercase", display: "block", marginBottom: "10px" }}>Action History</strong>
              <div style={{ display: "flex", flexDirection: "column", gap: "8px", maxHeight: "140px", overflowY: "auto" }}>
                {selectedOrder.histories?.length > 0 ? selectedOrder.histories.map((h, i) => (
                  <div key={i} style={{ fontSize: "0.72rem", borderLeft: "2px solid var(--purple)", paddingLeft: "10px" }}>
                    <div style={{ display: "flex", justifyContent: "space-between", color: "var(--text-muted)", marginBottom: "2px" }}>
                      <span>{h.user?.name || "System"}</span>
                      <span>{new Date(h.created_at).toLocaleString()}</span>
                    </div>
                    <p style={{ color: "var(--text-main)", fontWeight: "500" }}>{h.description}</p>
                  </div>
                )) : <span style={{ color: "var(--text-muted)", fontStyle: "italic", fontSize: "0.77rem" }}>Aucun historique.</span>}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
