import { useNavigate } from "react-router-dom";
import { useLanguage } from "../context/LanguageContext";

export default function Help() {
  const navigate = useNavigate();
  const { t } = useLanguage();

  return (
    <div style={{ padding: "10px 0" }}>
      {/* Page Header */}
      <div className="page-header" style={{ marginBottom: "30px" }}>
        <div>
          <h2 className="page-title" style={{ display: "flex", alignItems: "center", gap: "10px" }}>
            <span style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "35px", height: "35px", borderRadius: "8px", background: "rgba(137, 80, 252, 0.1)", color: "var(--purple)" }}>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10" />
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                <line x1="12" y1="17" x2="12.01" y2="17" />
              </svg>
            </span>
            {t("help_title")}
          </h2>
          <p className="page-subtitle">{t("help_intro")}</p>
        </div>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(400px, 1fr))", gap: "25px", marginBottom: "30px" }}>
        {/* Quotas Card */}
        <div className="card" style={{ padding: "25px", display: "flex", flexDirection: "column", gap: "15px", border: "1px solid var(--border-color)", borderRadius: "12px", background: "var(--card-bg)" }}>
          <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
            <div style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "45px", height: "45px", borderRadius: "10px", background: "rgba(137, 80, 252, 0.08)", color: "var(--purple)" }}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                <line x1="9" y1="3" x2="9" y2="21" />
                <line x1="15" y1="3" x2="15" y2="21" />
                <line x1="3" y1="9" x2="21" y2="9" />
                <line x1="3" y1="15" x2="21" y2="15" />
              </svg>
            </div>
            <h3 style={{ fontSize: "1.2rem", fontWeight: 700, color: "var(--text-main)", margin: 0 }}>
              {t("help_quotas_title")}
            </h3>
          </div>
          
          <p style={{ fontSize: "0.95rem", color: "var(--text-muted)", lineHeight: "1.6", margin: 0 }}>
            {t("help_quotas_text")}
          </p>
        </div>

        {/* Commissions Card */}
        <div className="card" style={{ padding: "25px", display: "flex", flexDirection: "column", gap: "15px", border: "1px solid var(--border-color)", borderRadius: "12px", background: "var(--card-bg)" }}>
          <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
            <div style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "45px", height: "45px", borderRadius: "10px", background: "rgba(27, 197, 189, 0.08)", color: "#1bc5bd" }}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <line x1="12" y1="1" x2="12" y2="23" />
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
              </svg>
            </div>
            <h3 style={{ fontSize: "1.2rem", fontWeight: 700, color: "var(--text-main)", margin: 0 }}>
              {t("help_commissions_title")}
            </h3>
          </div>

          <div style={{ display: "flex", flexDirection: "column", gap: "12px" }}>
            <p style={{ fontSize: "0.95rem", color: "var(--text-muted)", lineHeight: "1.6", margin: 0 }}>
              {t("help_commissions_text")}
            </p>

            <ul style={{ paddingLeft: "20px", paddingRight: "20px", margin: 0, display: "flex", flexDirection: "column", gap: "10px" }}>
              <li style={{ fontSize: "0.9rem", color: "var(--text-main)", lineHeight: "1.5" }}>
                <strong style={{ color: "var(--purple)" }}>• </strong>{t("help_commission_type")}
              </li>
              <li style={{ fontSize: "0.9rem", color: "var(--text-main)", lineHeight: "1.5" }}>
                <strong style={{ color: "var(--purple)" }}>• </strong>{t("help_commission_mode")}
              </li>
              <li style={{ fontSize: "0.9rem", color: "var(--text-main)", lineHeight: "1.5" }}>
                <strong style={{ color: "var(--purple)" }}>• </strong>{t("help_commission_amount")}
              </li>
            </ul>
          </div>
        </div>
      </div>

      {/* Bottom Action Footer */}
      <div className="card" style={{ padding: "30px", border: "1px dashed var(--purple)", borderRadius: "12px", background: "rgba(137, 80, 252, 0.02)", display: "flex", flexWrap: "wrap", alignItems: "center", justifyContent: "space-between", gap: "20px" }}>
        <div style={{ flex: "1 1 500px" }}>
          <p style={{ fontSize: "1rem", color: "var(--text-main)", fontWeight: 500, margin: 0, lineHeight: "1.5" }}>
            {t("help_conclusion")}
          </p>
        </div>
        <button
          onClick={() => navigate("/team")}
          style={{
            backgroundColor: "var(--purple)",
            color: "white",
            padding: "12px 24px",
            borderRadius: "8px",
            fontWeight: 600,
            fontSize: "0.9rem",
            display: "flex",
            alignItems: "center",
            gap: "10px",
            boxShadow: "0 4px 15px rgba(137, 80, 252, 0.2)",
            transition: "all 0.2s",
            cursor: "pointer",
            border: "none"
          }}
          className="btn-purple-hover"
        >
          {t("equipe")}
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <line x1="7" y1="17" x2="17" y2="7" />
            <polyline points="7 7 17 7 17 17" />
          </svg>
        </button>
      </div>
    </div>
  );
}
