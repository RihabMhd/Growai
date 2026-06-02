import { useContext, useEffect, useState, useCallback } from "react";
import { AuthContext } from "../auth/AuthContext";
import { useLanguage } from "../context/LanguageContext";
import axios from "axios";

// ─── helpers ────────────────────────────────────────────────────────────────

const EMPTY_STATS = {
  total: 0, confirmed: 0, pending: 0, cancelled: 0, delivered: 0,
  revenue: 0, revenue_growth: 0, confirmation_rate: 0,
  avg_confirmation_time: null,
  products: 0, clients: 0, team_members: 0,
};

const fmt = (v) =>
  new Intl.NumberFormat("fr-MA", {
    style: "currency", currency: "MAD", maximumFractionDigits: 0,
  }).format(v ?? 0);

const fmtGrowth = (v) => `${v >= 0 ? "+" : ""}${v ?? 0}%`;

const fmtTime = (minutes) => {
  if (minutes === null || minutes === undefined) return "— Aucune donnée";
  if (minutes < 60) return `${Math.round(minutes)} min`;
  const h = Math.floor(minutes / 60);
  const m = Math.round(minutes % 60);
  return m > 0 ? `${h}h ${m}m` : `${h}h`;
};

const PERIOD_OPTIONS = [
  { label_key: "aujourd_hui",        fallback: "Aujourd'hui",        value: "today"        },
  { label_key: "hier",               fallback: "Hier",               value: "yesterday"    },
  { label_key: "les_7_derniers_jours", fallback: "7 derniers jours", value: "last_7_days"  },
  { label_key: "ce_mois",            fallback: "Ce mois",            value: "this_month"   },
];

// ─── sub-components ──────────────────────────────────────────────────────────

function KpiCard({ icon, iconClass, title, value, loading, style = {} }) {
  return (
    <div className="card">
      <div className="card-header">
        <div className={`card-icon ${iconClass}`} style={{ width: "32px", height: "32px" }}>
          {icon && <>{icon}</> || null}
        </div>
        <div style={{ textAlign: "right" }}>
          <div className="card-title" style={style.title}>{title}</div>
        </div>
      </div>
      <div className="card-value" style={style.value}>
        {loading ? "…" : value}
      </div>
    </div>
  );
}

function StatsBlock({ stats, loading }) {
  const confirmOther = stats.total - stats.confirmed;

  return (
    <>
      {/* Row 1 – Order KPIs */}
      <div className="dashboard-grid" style={{ gridTemplateColumns: "repeat(5, 1fr)" }}>

        <KpiCard loading={loading}
          iconClass="icon-primary"
          title="Total commandes"
          value={stats.total}
          icon={<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>}
        />
        <KpiCard loading={loading}
          iconClass="icon-success"
          title="Confirmées"
          value={stats.confirmed}
          icon={<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="20 6 9 17 4 12"/></svg>}
        />
        <KpiCard loading={loading}
          iconClass="icon-warning"
          title="En attente"
          value={stats.pending}
          icon={<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>}
        />
        <KpiCard loading={loading}
          iconClass="icon-danger"
          title="Annulées"
          value={stats.cancelled}
          icon={<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>}
        />
        <KpiCard loading={loading}
          iconClass="icon-primary"
          title="Taux de confirmation moyen"
          value={loading ? "…" : `${stats.confirmation_rate}%`}
          icon={<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 17"/><polyline points="17 6 23 6 23 12"/></svg>}
        />
        <KpiCard loading={loading}
          iconClass=""
          style={{ value: { fontSize: "1rem", color: "var(--text-muted)" }, title: { fontSize: "0.65rem" } }}
          title={<>Temps moyen<br />Confirmation</>}
          value={fmtTime(stats.avg_confirmation_time)}
          icon={<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>}
        />
      </div>

      {/* Row 2 – Revenue · Overview · Rate · (global: overview card) */}
      <div className="dashboard-grid-large" style={{ gridTemplateColumns: "repeat(3, 1fr)" }}>

        {/* Revenus */}
        <div className="card">
          <div className="card-header" style={{ marginBottom: "16px", flexDirection: "column", alignItems: "flex-start", gap: "6px" }}>
            <div style={{ display: "flex", alignItems: "center", gap: "8px", width: "100%" }}>
              <div className="card-icon icon-purple" style={{ width: "32px", height: "32px" }}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
              </div>
              <div style={{ flex: 1 }}>
                <div style={{ fontWeight: 700, color: "var(--text-main)", fontSize: "0.9rem" }}>Revenus</div>
                <div style={{ fontSize: "0.7rem", color: "var(--text-muted)" }}>Des commandes confirmées</div>
              </div>
              <div className={`badge ${stats.revenue_growth >= 0 ? "badge-success" : "badge-danger"}`}>
                {loading ? "…" : fmtGrowth(stats.revenue_growth)}
              </div>
            </div>
          </div>
          <div className="card-value" style={{ fontSize: "1.8rem" }}>
            {loading ? "…" : fmt(stats.revenue)}
          </div>
        </div>

        {/* Aperçu */}
        <div className="card">
          <div className="card-header" style={{ justifyContent: "flex-start", gap: "8px", marginBottom: "10px" }}>
            <div className="card-icon icon-primary" style={{ width: "32px", height: "32px" }}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/></svg>
            </div>
            <div style={{ fontWeight: 700, color: "var(--text-main)", fontSize: "0.9rem" }}>Aperçu</div>
          </div>
          <div className="data-list" style={{ marginTop: "6px" }}>
            <div className="data-item">
              <div className="data-item-label"><div className="data-item-dot dot-primary" />Produits</div>
              <div className="data-item-value">{loading ? "…" : stats.products ?? "—"}</div>
            </div>
            <div className="data-item">
              <div className="data-item-label"><div className="data-item-dot dot-success" />Clients</div>
              <div className="data-item-value">{loading ? "…" : stats.clients ?? "—"}</div>
            </div>
            <div className="data-item">
              <div className="data-item-label"><div className="data-item-dot dot-warning" />Membres d'équipe</div>
              <div className="data-item-value">{loading ? "…" : stats.team_members ?? "—"}</div>
            </div>
            <div className="data-item">
              <div className="data-item-label"><div className="data-item-dot dot-success" />Livrées</div>
              <div className="data-item-value">{loading ? "…" : stats.delivered}</div>
            </div>
          </div>
        </div>

        {/* Taux de confirmation */}
        <div className="card">
          <div className="card-header" style={{ justifyContent: "flex-start", gap: "8px", marginBottom: "10px" }}>
            <div className="card-icon icon-success" style={{ width: "32px", height: "32px" }}>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div style={{ fontWeight: 700, color: "var(--text-main)", fontSize: "0.9rem" }}>Taux de confirmation</div>
          </div>
          <div style={{ display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", marginTop: "8px" }}>
            <div style={{
              width: "100px", height: "100px", borderRadius: "50%",
              border: "6px solid var(--border-color)", borderTopColor: "var(--success)",
              display: "flex", alignItems: "center", justifyContent: "center",
            }}>
              <span style={{ fontSize: "1.6rem", fontWeight: "700" }}>
                {loading ? "…" : `${stats.confirmation_rate}%`}
              </span>
            </div>
            <div style={{ display: "flex", gap: "12px", marginTop: "10px", fontSize: "0.75rem", color: "var(--text-muted)" }}>
              <span style={{ display: "flex", alignItems: "center", gap: "4px" }}>
                <div className="data-item-dot dot-success" />{loading ? "…" : stats.confirmed} confirmées
              </span>
              <span style={{ display: "flex", alignItems: "center", gap: "4px" }}>
                <div className="data-item-dot" style={{ backgroundColor: "var(--border-color)", width: "5px", height: "5px" }} />
                {loading ? "…" : confirmOther} autres
              </span>
            </div>
          </div>
        </div>

      </div>
    </>
  );
}

// ─── main component ──────────────────────────────────────────────────────────

export default function Dashboard() {
  const { user }  = useContext(AuthContext);
  const { t }     = useLanguage();

  const [period,      setPeriod]      = useState("today");
  const [loading,     setLoading]     = useState(true);
  const [error,       setError]       = useState(null);
  const [activeShop,  setActiveShop]  = useState("global"); // "global" | shop.id

  const [global, setGlobal] = useState({ ...EMPTY_STATS });
  const [shops,  setShops]  = useState([]);                  // array of shop stat objects

  // ── fetch ─────────────────────────────────────────────────────────────────
  const fetchDashboard = useCallback(async (p) => {
    setLoading(true);
    setError(null);
    try {
      const { data } = await axios.get("/api/dashboard", { params: { period: p } });
      setGlobal(data.global ?? { ...EMPTY_STATS });
      setShops(data.shops  ?? []);
    } catch (err) {
      setError(err?.response?.data?.message ?? "Erreur de chargement");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { fetchDashboard(period); }, [period, fetchDashboard]);

  // ── derived active stats ──────────────────────────────────────────────────
  const activeStats =
    activeShop === "global"
      ? global
      : shops.find((s) => s.id === activeShop) ?? { ...EMPTY_STATS };

  // ── platform badge icon ────────────────────────────────────────────────────
  const PlatformDot = ({ platform }) => {
    const colors = { shopify: "#96bf48", manual: "#8950fc", woocommerce: "#7f54b3" };
    return (
      <span style={{
        display: "inline-block", width: "8px", height: "8px", borderRadius: "50%",
        backgroundColor: colors[platform] ?? "var(--text-muted)",
        marginRight: "6px", flexShrink: 0,
      }} />
    );
  };

  // ── render ────────────────────────────────────────────────────────────────
  return (
    <div>
      {/* ── Header ── */}
      <div className="page-header">
        <div>
          <h2 className="page-title">
            <svg style={{ width: "24px", height: "24px", color: "#8950fc" }} viewBox="0 0 24 24" fill="currentColor">
              <rect x="3"  y="3"  width="7" height="7" rx="1" />
              <rect x="14" y="3"  width="7" height="7" rx="1" />
              <rect x="14" y="14" width="7" height="7" rx="1" />
              <rect x="3"  y="14" width="7" height="7" rx="1" />
            </svg>
            {t("tableau_de_bord")}
          </h2>
          <p className="page-subtitle">{t("suivre_indicateurs")}</p>
        </div>

        <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
          {error && (
            <span style={{ fontSize: "0.75rem", color: "var(--danger)", display: "flex", alignItems: "center", gap: "4px" }}>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              {error}
            </span>
          )}
          <select
            className="select-input"
            value={period}
            onChange={(e) => setPeriod(e.target.value)}
          >
            {PERIOD_OPTIONS.map((o) => (
              <option key={o.value} value={o.value}>
                {t(o.label_key) || o.fallback}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* ── Shop tabs ── */}
      <div style={{
        display: "flex", alignItems: "center", gap: "6px",
        marginBottom: "20px", flexWrap: "wrap",
      }}>
        {/* Global tab */}
        <button
          onClick={() => setActiveShop("global")}
          style={{
            display: "flex", alignItems: "center", gap: "6px",
            padding: "6px 14px", borderRadius: "20px", cursor: "pointer",
            fontSize: "0.82rem", fontWeight: 600, transition: "all 0.15s",
            border: activeShop === "global"
              ? "2px solid #8950fc"
              : "2px solid var(--border-color)",
            background: activeShop === "global" ? "#f3eeff" : "var(--card-bg)",
            color: activeShop === "global" ? "#8950fc" : "var(--text-muted)",
          }}
        >
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <rect x="3" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
          </svg>
          Toutes les boutiques
          <span style={{
            fontSize: "0.7rem", padding: "1px 6px", borderRadius: "10px",
            background: activeShop === "global" ? "#8950fc" : "var(--border-color)",
            color: activeShop === "global" ? "#fff" : "var(--text-muted)",
          }}>
            {loading ? "…" : global.total}
          </span>
        </button>

        {/* One tab per shop */}
        {shops.map((shop) => {
          const isActive = activeShop === shop.id;
          return (
            <button
              key={shop.id}
              onClick={() => setActiveShop(shop.id)}
              style={{
                display: "flex", alignItems: "center", gap: "6px",
                padding: "6px 14px", borderRadius: "20px", cursor: "pointer",
                fontSize: "0.82rem", fontWeight: 600, transition: "all 0.15s",
                border: isActive
                  ? "2px solid var(--success)"
                  : "2px solid var(--border-color)",
                background: isActive ? "#edfff5" : "var(--card-bg)",
                color: isActive ? "var(--success)" : "var(--text-muted)",
              }}
            >
              <PlatformDot platform={shop.platform} />
              {shop.name}
              <span style={{
                fontSize: "0.7rem", padding: "1px 6px", borderRadius: "10px",
                background: isActive ? "var(--success)" : "var(--border-color)",
                color: isActive ? "#fff" : "var(--text-muted)",
              }}>
                {loading ? "…" : shop.total}
              </span>
              {!shop.is_active && (
                <span style={{ fontSize: "0.65rem", color: "var(--danger)", marginLeft: "2px" }}>
                  ● off
                </span>
              )}
            </button>
          );
        })}
      </div>

      {/* ── Active shop label (when not global) ── */}
      {activeShop !== "global" && (() => {
        const shop = shops.find((s) => s.id === activeShop);
        if (!shop) return null;
        return (
          <div style={{
            display: "flex", alignItems: "center", gap: "10px",
            marginBottom: "16px", padding: "10px 16px",
            background: "var(--card-bg)", borderRadius: "10px",
            border: "1px solid var(--border-color)", fontSize: "0.85rem",
          }}>
            <PlatformDot platform={shop.platform} />
            <strong>{shop.name}</strong>
            {shop.domain && (
              <span style={{ color: "var(--text-muted)" }}>— {shop.domain}</span>
            )}
            <span style={{
              marginLeft: "auto", fontSize: "0.72rem", padding: "2px 8px",
              borderRadius: "8px",
              background: shop.is_active ? "#edfff5" : "#fff0f0",
              color: shop.is_active ? "var(--success)" : "var(--danger)",
              fontWeight: 600,
            }}>
              {shop.is_active ? "Active" : "Inactive"}
            </span>
          </div>
        );
      })()}

      {/* ── Stats block for active selection ── */}
      <StatsBlock stats={activeStats} loading={loading} />
    </div>
  );
}
