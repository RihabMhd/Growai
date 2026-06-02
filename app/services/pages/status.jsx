import { useState, useEffect } from "react";
import api from "../api/axios";

// ── Confirmation status meta ──────────────────────────────────────────────────
const STATUS_META = {
  pending:    { color: "#3b82f6", label: "Nouveau" },
  confirmed:  { color: "#22c55e", label: "Confirmé" },
  no_answer:  { color: "#f59e0b", label: "Pas de réponse" },
  callback:   { color: "#a855f7", label: "Rappel" },
  cancelled:  { color: "#ef4444", label: "Annulé" },
  duplicate:  { color: "#94a3b8", label: "Doublon" },
  wrong_num:  { color: "#f97316", label: "Mauvais numéro" },
};

// ── Company / delivery statuses ───────────────────────────────────────────────
const COMPANY_STATUSES = [
  { slug: "label_created",      label: "Label Created",       color: "#64748b", icon: "🏷️"  },
  { slug: "confirmed",          label: "Confirmed",           color: "#22c55e", icon: "✅"  },
  { slug: "ready_for_pickup",   label: "Ready for Pickup",    color: "#3b82f6", icon: "📦"  },
  { slug: "out_for_delivery",   label: "Out for Delivery",    color: "#f59e0b", icon: "🚚"  },
  { slug: "attempted_delivery", label: "Attempted Delivery",  color: "#ef4444", icon: "🔄"  },
  { slug: "picked_up",          label: "Picked Up",           color: "#8b5cf6", icon: "🤝"  },
  { slug: "delivered",          label: "Delivered",           color: "#10b981", icon: "🎉"  },
  { slug: "delayed",            label: "Delayed",             color: "#f97316", icon: "⏳"  },
  { slug: "returned",           label: "Returned",            color: "#dc2626", icon: "↩️"  },
];

// ── Icons ─────────────────────────────────────────────────────────────────────
const IconShield = () => (<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>);
const IconBuilding = () => (<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>);
const IconWhatsApp = () => (<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>);
const IconGlobe = () => (<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>);
const IconChevron = ({ up }) => (<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d={up ? "M18 15l-6-6-6 6" : "M6 9l6 6 6-6"}/></svg>);
const IconInfo = () => (<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>);
const IconSave = () => (<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>);

// ── Default templates ─────────────────────────────────────────────────────────
const DEFAULT_TEMPLATES = {
  pending: {
    FR: "🛍 Bonjour **{{customer_name}}** ✅ Votre commande **#{{order_id}}** est bien enregistrée chez **{{shop_name}}**.\n📦 Produit : {{product_name}}\n💰 Total : {{total}} DZD\n\n📞 Notre équipe vous appelle bientôt pour confirmer.",
    AR: "🛍 مرحبا **{{customer_name}}** ✅ تم تسجيل طلبك رقم **#{{order_id}}** في **{{shop_name}}**.\n📦 المنتج : {{product_name}}\n💰 المجموع : {{total}} دج\n\n📞 فريقنا سيتصل بك قريباً للتأكيد.",
    "FR/AR": "🛍 Bonjour **{{customer_name}}** ✅ Votre commande **#{{order_id}}** est bien en...",
    "Darija AR": "سلام **{{customer_name}}** ✅ طلبيتك رقم **#{{order_id}}** تسجلات عند **{{shop_name}}**...",
    "Darija FR": "Salam **{{customer_name}}** ✅ Talab dialek **#{{order_id}}** tsajjel 3and **{{shop_name}}**..."
  },
  confirmed: {
    FR: "✅ Bonjour **{{customer_name}}**, votre commande **#{{order_id}}** est confirmée ! 🎉\n📦 Elle sera livrée dans les prochains jours.",
    AR: "✅ مرحبا **{{customer_name}}**، تم تأكيد طلبك **#{{order_id}}** ! 🎉",
    "FR/AR": "✅ Commande confirmée / تم التأكيد",
    "Darija AR": "✅ Commande **#{{order_id}}** mta3k tconfirmat !",
    "Darija FR": "✅ Ta commande **#{{order_id}}** est confirmée !"
  },
  label_created: {
    FR: "🏷️ Bonjour **{{customer_name}}** 📦 L'étiquette de livraison de la commande **##{{order_id}}** a été créée.\n🛒 Produit : **{{product_name}}**\n🏭 Société : **{{shop_name}}**\n\n📦 Suivez votre livraison via le bouton ci-dessous.\n\n— Ma Boutique",
    AR: "🏷️ مرحبا **{{customer_name}}** 📦 تم إنشاء ماسق الحمن للطالب **##{{order_id}}**.\n🛒 المنتج : **{{product_name}}**\n🏭 الشركة : **{{shop_name}}**",
    "FR/AR": "🏷️ Bonjour **{{customer_name}}** 📦 L'étiquette de livraison de la commande **##{{order_id}}**...",
    "Darija AR": "سلام **{{customer_name}}** 📦 تصاوب الكولي ديال **{{order_id}}**. 🏭 **{{shop_name}}**",
    "Darija FR": "Salam **{{customer_name}}** 📦 Lcolis dyal talab **##{{order_id}}** tsawb. 🏭 **{{shop_name}}**"
  },
  out_for_delivery: {
    FR: "🚚 Bonjour **{{customer_name}}** ! Votre commande **#{{order_id}}** est en cours de livraison aujourd'hui. 📍 Le livreur arrivera bientôt.",
    AR: "🚚 مرحبا **{{customer_name}}** ! طلبك **#{{order_id}}** في طريقه إليك اليوم.",
    "FR/AR": "🚚 Commande en livraison / طلبك في الطريق",
    "Darija AR": "🚚 **{{customer_name}}** koltek ghadi ytsl liha **#{{order_id}}** lyoum !",
    "Darija FR": "🚚 Salam **{{customer_name}}** ! Colis **#{{order_id}}** f route lyoum !"
  },
  delivered: {
    FR: "🎉 Bonjour **{{customer_name}}** ! Votre commande **#{{order_id}}** a été livrée avec succès. Merci pour votre confiance !",
    AR: "🎉 مرحبا **{{customer_name}}** ! تم تسليم طلبك **#{{order_id}}** بنجاح. شكراً لثقتك !",
    "FR/AR": "🎉 Livraison réussie ! / تم التسليم بنجاح",
    "Darija AR": "🎉 **{{customer_name}}** koltek wsalt ! Shukran 3la thiqa diaalk.",
    "Darija FR": "🎉 Salam **{{customer_name}}** ! Colis **#{{order_id}}** wsl. Shukran !"
  },
};

const LANG_FLAGS = {
  FR:          { flag: "🇫🇷", label: "Français",   abbr: "FR"  },
  AR:          { flag: "🇩🇿", label: "العربية",     abbr: "AR"  },
  "FR/AR":     { flag: "🌍",  label: "FR/AR",       abbr: "FR/AR" },
  "Darija AR": { flag: "🇲🇦", label: "Darija AR",   abbr: "MA"  },
  "Darija FR": { flag: "🇲🇦", label: "Darija FR",   abbr: "MA"  },
};

// ── Toggle Switch ─────────────────────────────────────────────────────────────
function ToggleSwitch({ checked, onChange, size = "md" }) {
  const w = size === "sm" ? 36 : 44;
  const h = size === "sm" ? 20 : 24;
  const d = size === "sm" ? 13 : 16;
  const on = size === "sm" ? w - d - 3 : 22;
  return (
    <div onClick={onChange} style={{ width: `${w}px`, height: `${h}px`, borderRadius: `${h/2}px`, cursor: "pointer", background: checked ? "#22c55e" : "rgba(255,255,255,0.1)", position: "relative", transition: "background 0.25s", flexShrink: 0, border: checked ? "1px solid #16a34a" : "1px solid rgba(255,255,255,0.15)" }}>
      <div style={{ position: "absolute", top: "3px", left: checked ? `${on}px` : "3px", width: `${d}px`, height: `${d}px`, borderRadius: "50%", background: "#fff", transition: "left 0.25s", boxShadow: "0 1px 4px rgba(0,0,0,0.3)" }}/>
    </div>
  );
}

// ── Phone Preview ─────────────────────────────────────────────────────────────
function PhonePreview({ message, customerName = "Ahmed Benali" }) {
  const rendered = (message || "")
    .replace(/{{customer_name}}/g, customerName)
    .replace(/{{order_id}}/g, "1042")
    .replace(/{{shop_name}}/g, "Ma Boutique")
    .replace(/{{product_name}}/g, "T-Shirt Rouge")
    .replace(/{{total}}/g, "3 500");
  const lines = rendered.split("\n").filter(Boolean);
  return (
    <div style={{ width: "220px", background: "#1a1a2e", borderRadius: "32px", overflow: "hidden", border: "6px solid #0d0d1a", boxShadow: "0 24px 60px rgba(0,0,0,0.6)", flexShrink: 0 }}>
      <div style={{ background: "#075e54", padding: "10px 16px 8px", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
        <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
          <div style={{ width: "32px", height: "32px", borderRadius: "50%", background: "rgba(255,255,255,0.2)", display:"flex",alignItems:"center",justifyContent:"center" }}><span style={{fontSize:"14px"}}>👤</span></div>
          <div>
            <div style={{ fontSize: "0.78rem", fontWeight: "700", color: "#fff" }}>{customerName}</div>
            <div style={{ fontSize: "0.6rem", color: "rgba(255,255,255,0.7)" }}>online</div>
          </div>
        </div>
        <div style={{ display: "flex", gap: "10px" }}>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.8)" strokeWidth="2"><path d="M15 10l4.553-2.069A1 1 0 0 1 21 8.87v6.26a1 1 0 0 1-1.447.899L15 14"/><rect x="1" y="6" width="14" height="12" rx="2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.8)" strokeWidth="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.82 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
      </div>
      <div style={{ background: "#0b141a", backgroundImage: "url(\"data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.015'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")", padding: "12px 8px", minHeight: "200px" }}>
        <div style={{ textAlign: "center", fontSize: "0.6rem", color: "rgba(255,255,255,0.35)", background: "rgba(255,255,255,0.05)", borderRadius: "8px", padding: "3px 8px", display: "inline-block", margin: "0 auto 10px" }}>Today</div>
        <div style={{ background: "#d9fdd3", borderRadius: "8px 8px 2px 8px", padding: "8px 10px", maxWidth: "90%", marginLeft: "auto", boxShadow: "0 1px 2px rgba(0,0,0,0.2)" }}>
          {lines.map((line, i) => {
            const bold = line.replace(/\*\*(.*?)\*\*/g, (_, t) => `<strong>${t}</strong>`);
            return <div key={i} style={{ fontSize: "0.62rem", color: "#111", lineHeight: "1.45", marginBottom: i < lines.length - 1 ? "3px" : 0 }} dangerouslySetInnerHTML={{ __html: bold }}/>;
          })}
          <div style={{ fontSize: "0.5rem", color: "rgba(0,0,0,0.4)", textAlign: "right", marginTop: "4px" }}>10:31 AM ✓✓</div>
        </div>
        {/* Track button */}
        <div style={{ textAlign: "center", marginTop: "10px" }}>
          <div style={{ display: "inline-flex", alignItems: "center", gap: "5px", background: "#d9fdd3", borderRadius: "6px", padding: "5px 12px", fontSize: "0.58rem", color: "#075e54", fontWeight: "700", border: "1px solid rgba(7,94,84,0.2)" }}>
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#075e54" strokeWidth="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Suivre la commande
          </div>
        </div>
      </div>
      <div style={{ background: "#1f2c34", padding: "8px 10px", display: "flex", alignItems: "center", gap: "8px" }}>
        <div style={{ flex: 1, background: "#2a3942", borderRadius: "20px", padding: "6px 12px", fontSize: "0.6rem", color: "rgba(255,255,255,0.3)" }}>Type a message</div>
        <div style={{ width: "28px", height: "28px", borderRadius: "50%", background: "#00a884", display: "flex", alignItems: "center", justifyContent: "center" }}>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="white"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
        </div>
      </div>
    </div>
  );
}

// ── Language Card ─────────────────────────────────────────────────────────────
function LangCard({ lang, message, selected, onSelect }) {
  const meta = LANG_FLAGS[lang] || { flag: "🌐", label: lang, abbr: lang.substring(0,2).toUpperCase() };
  const preview = (message || "").substring(0, 55).replace(/\n/g, " ") + ((message || "").length > 55 ? "..." : "");
  return (
    <div onClick={onSelect} style={{ background: selected ? "rgba(34,197,94,0.08)" : "var(--bg-app,#111)", border: selected ? "1px solid rgba(34,197,94,0.4)" : "1px solid var(--border-color,#2a2a2e)", borderRadius: "12px", padding: "14px 16px", cursor: "pointer", transition: "all 0.15s", position: "relative", overflow: "hidden" }}>
      <div style={{ position: "absolute", top: "10px", right: "-18px", background: "linear-gradient(135deg,#7239ea,#a855f7)", color: "#fff", fontSize: "0.5rem", fontWeight: "800", padding: "3px 28px", transform: "rotate(45deg)", letterSpacing: "0.05em" }}>UTILITY</div>
      <div style={{ display: "flex", alignItems: "flex-start", gap: "12px" }}>
        {selected ? (
          <div style={{ width: "36px", height: "36px", borderRadius: "50%", background: "rgba(34,197,94,0.2)", border: "2px solid #22c55e", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
        ) : (
          <div style={{ width: "36px", height: "36px", borderRadius: "50%", background: "rgba(255,255,255,0.06)", border: "1px solid rgba(255,255,255,0.1)", display: "flex", alignItems: "center", justifyContent: "center", flexShrink: 0 }}>
            <span style={{ fontSize: lang === "FR" || lang === "AR" ? "1rem" : "0.6rem", fontWeight: "700", color: "var(--text-muted,#888)" }}>{lang === "FR" ? "🇫🇷" : lang === "AR" ? "🇩🇿" : meta.abbr}</span>
          </div>
        )}
        <div style={{ flex: 1, minWidth: 0 }}>
          <div style={{ fontSize: "0.82rem", fontWeight: "700", color: "var(--text-main,#fff)", marginBottom: "3px" }}>{meta.label}</div>
          <div style={{ fontSize: "0.71rem", color: "var(--text-muted,#888)", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{preview || <em>No template set</em>}</div>
        </div>
      </div>
    </div>
  );
}

// ── Company Status Tab ────────────────────────────────────────────────────────
function CompanyStatusTab({ savedMsg, setSavedMsg }) {
  const LANGS = ["FR", "AR", "FR/AR", "Darija AR", "Darija FR"];

  // per-status enabled toggle
  const [enabled, setEnabled] = useState(() => Object.fromEntries(COMPANY_STATUSES.map(s => [s.slug, true])));
  const [selectedSlug, setSelectedSlug] = useState(COMPANY_STATUSES[0].slug);
  const [selectedLang, setSelectedLang] = useState("FR");
  const [templates, setTemplates] = useState(() => {
    const t = {};
    COMPANY_STATUSES.forEach(s => { t[s.slug] = DEFAULT_TEMPLATES[s.slug] || {}; });
    return t;
  });
  const [autoSend, setAutoSend] = useState(() => Object.fromEntries(COMPANY_STATUSES.map(s => [s.slug, false])));
  const [saving, setSaving] = useState(false);

  const selected = COMPANY_STATUSES.find(s => s.slug === selectedSlug);
  const currentTemplate = templates[selectedSlug]?.[selectedLang] || "";
  const currentAutoSend = autoSend[selectedSlug] || false;

  const handleTemplateChange = (val) => {
    setTemplates(prev => ({ ...prev, [selectedSlug]: { ...(prev[selectedSlug] || {}), [selectedLang]: val } }));
  };

  const handleToggleAutoSend = async () => {
    const next = !currentAutoSend;
    setAutoSend(prev => ({ ...prev, [selectedSlug]: next }));
    try { await api.post(`/company-statuses/${selectedSlug}/auto-send`, { auto_send: next }); } catch {}
  };

  const handleToggleEnabled = async (slug, val) => {
    setEnabled(prev => ({ ...prev, [slug]: val }));
    try { await api.post(`/company-statuses/${slug}/toggle`, { enabled: val }); } catch {}
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await api.put(`/company-statuses/${selectedSlug}`, { auto_send: currentAutoSend, templates: templates[selectedSlug] });
      setSavedMsg(true);
      setTimeout(() => setSavedMsg(false), 2500);
    } catch { setSavedMsg(true); setTimeout(() => setSavedMsg(false), 2500); }
    finally { setSaving(false); }
  };

  return (
    <div style={{ display: "grid", gridTemplateColumns: "220px 1fr 240px", gap: "16px", alignItems: "start" }}>

      {/* LEFT: Company status list with toggles */}
      <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", overflow: "hidden" }}>
        <div style={{ padding: "14px 16px", borderBottom: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <span style={{ fontSize: "0.65rem", fontWeight: "800", letterSpacing: "0.08em", color: "var(--text-muted,#888)", textTransform: "uppercase" }}>Company Statuses</span>
          <div style={{ width: "20px", height: "20px", borderRadius: "50%", background: "rgba(255,255,255,0.06)", display:"flex",alignItems:"center",justifyContent:"center", cursor:"pointer", color:"var(--text-muted)" }}><IconInfo/></div>
        </div>
        <div style={{ padding: "6px" }}>
          {COMPANY_STATUSES.map(s => {
            const isSelected = selectedSlug === s.slug;
            return (
              <div key={s.slug} onClick={() => { setSelectedSlug(s.slug); setSelectedLang("FR"); }}
                style={{ display: "flex", alignItems: "center", gap: "10px", padding: "10px 12px", borderRadius: "9px", cursor: "pointer", background: isSelected ? "rgba(114,57,234,0.15)" : "transparent", border: isSelected ? "1px solid rgba(114,57,234,0.3)" : "1px solid transparent", transition: "all 0.15s", marginBottom: "2px" }}>
                {/* Status icon + color dot */}
                <div style={{ width: "28px", height: "28px", borderRadius: "8px", background: `${s.color}22`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "0.85rem", flexShrink: 0 }}>{s.icon}</div>
                {/* Label */}
                <span style={{ fontSize: "0.82rem", fontWeight: isSelected ? "700" : "500", color: isSelected ? "#c4a7ff" : "var(--text-main,#fff)", flex: 1 }}>{s.label}</span>
                {/* Toggle — stop propagation so clicking toggle doesn't select row */}
                <div onClick={e => { e.stopPropagation(); handleToggleEnabled(s.slug, !enabled[s.slug]); }}>
                  <ToggleSwitch checked={enabled[s.slug]} onChange={() => {}} size="sm"/>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* CENTER: WhatsApp template editor */}
      <div style={{ display: "flex", flexDirection: "column", gap: "12px" }}>

        {/* Status name bar */}
        {selected && (
          <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", padding: "14px 18px", display: "flex", alignItems: "center", gap: "14px" }}>
            <div style={{ width: "38px", height: "38px", borderRadius: "50%", background: `${selected.color}22`, border: `2px solid ${selected.color}`, display: "flex", alignItems: "center", justifyContent: "center", fontSize: "1.1rem", flexShrink: 0 }}>{selected.icon}</div>
            <span style={{ flex: 1, fontSize: "1.1rem", fontWeight: "700", color: "var(--text-main,#fff)" }}>{selected.label}</span>
            <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
              <span style={{ fontSize: "0.72rem", color: "var(--text-muted,#888)" }}>Enabled</span>
              <ToggleSwitch checked={enabled[selectedSlug]} onChange={() => handleToggleEnabled(selectedSlug, !enabled[selectedSlug])}/>
            </div>
          </div>
        )}

        {/* WhatsApp card */}
        <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", overflow: "hidden" }}>
          {/* Card header with Auto-send toggle */}
          <div style={{ padding: "16px 20px", borderBottom: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
            <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
              <div style={{ width: "32px", height: "32px", borderRadius: "50%", background: "rgba(34,197,94,0.15)", display: "flex", alignItems: "center", justifyContent: "center", color: "#22c55e" }}><IconWhatsApp/></div>
              <div>
                <div style={{ fontWeight: "700", fontSize: "0.92rem" }}>WhatsApp Template</div>
                <div style={{ fontSize: "0.7rem", color: "var(--text-muted,#888)" }}>Auto-sent when order moves to this status</div>
              </div>
            </div>
            <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
              <span style={{ fontSize: "0.72rem", fontWeight: "600", color: currentAutoSend ? "#22c55e" : "var(--text-muted,#888)" }}>Auto-send {currentAutoSend ? "ON" : "OFF"}</span>
              <ToggleSwitch checked={currentAutoSend} onChange={handleToggleAutoSend}/>
            </div>
          </div>

          {/* Language cards */}
          <div style={{ padding: "16px", display: "flex", flexDirection: "column", gap: "10px" }}>
            {LANGS.map(lang => (
              <LangCard key={lang} lang={lang} message={templates[selectedSlug]?.[lang] || ""} selected={selectedLang === lang} onSelect={() => setSelectedLang(lang)}/>
            ))}
          </div>
        </div>

        {/* Template textarea editor */}
        {selected && (
          <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", overflow: "hidden" }}>
            <div style={{ padding: "14px 18px", borderBottom: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                {selectedLang === "FR" ? <span style={{fontSize:"1rem"}}>🇫🇷</span> : selectedLang === "AR" ? <span style={{fontSize:"1rem"}}>🇩🇿</span> : <IconGlobe/>}
                <span style={{ fontWeight: "700", fontSize: "0.85rem" }}>{LANG_FLAGS[selectedLang]?.label || selectedLang}</span>
              </div>
              <div style={{ fontSize: "0.7rem", color: "var(--text-muted,#888)", display: "flex", gap: "8px" }}>
                {["{{customer_name}}", "{{order_id}}", "{{shop_name}}", "{{total}}"].map(v => (
                  <code key={v} style={{ background: "rgba(114,57,234,0.15)", color: "#c4a7ff", borderRadius: "4px", padding: "2px 6px", fontSize: "0.65rem", cursor: "pointer" }}
                    onClick={() => handleTemplateChange(currentTemplate + v)}>{v}</code>
                ))}
              </div>
            </div>
            <textarea value={currentTemplate} onChange={e => handleTemplateChange(e.target.value)}
              placeholder={`Write your WhatsApp message in ${selectedLang}...\nUse {{customer_name}}, {{order_id}}, {{shop_name}}, {{total}}`}
              style={{ width: "100%", minHeight: "140px", background: "transparent", border: "none", outline: "none", color: "var(--text-main,#fff)", fontSize: "0.85rem", lineHeight: "1.6", padding: "16px 18px", resize: "vertical", boxSizing: "border-box", fontFamily: "inherit" }}/>
            <div style={{ padding: "10px 18px", borderTop: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <span style={{ fontSize: "0.7rem", color: "var(--text-muted,#888)" }}>{currentTemplate.length} chars</span>
              <button onClick={handleSave} disabled={saving} style={{ display: "flex", alignItems: "center", gap: "6px", padding: "7px 16px", borderRadius: "8px", background: saving ? "rgba(114,57,234,0.4)" : "#7239ea", color: "#fff", border: "none", fontSize: "0.78rem", fontWeight: "700", cursor: saving ? "not-allowed" : "pointer" }}>
                <IconSave/>{saving ? "Saving..." : "Save Changes"}
              </button>
            </div>
          </div>
        )}
      </div>

      {/* RIGHT: Phone preview */}
      <div style={{ position: "sticky", top: "20px", display: "flex", flexDirection: "column", alignItems: "center", gap: "12px" }}>
        <div style={{ fontSize: "0.65rem", fontWeight: "700", letterSpacing: "0.08em", color: "var(--text-muted,#888)", textTransform: "uppercase" }}>Preview</div>
        <PhonePreview message={currentTemplate}/>
      </div>
    </div>
  );
}

// ── Main Component ────────────────────────────────────────────────────────────
export default function Status() {
  const [activeTab, setActiveTab] = useState("confirmation");
  const [statuses, setStatuses] = useState([]);
  const [selectedStatus, setSelectedStatus] = useState(null);
  const [selectedLang, setSelectedLang] = useState("FR");
  const [templates, setTemplates] = useState({});
  const [autoSend, setAutoSend] = useState({});
  const [saving, setSaving] = useState(false);
  const [savedMsg, setSavedMsg] = useState(false);
  const [loading, setLoading] = useState(true);
  const LANGS = ["FR", "AR", "FR/AR", "Darija AR", "Darija FR"];

  useEffect(() => {
    const load = async () => {
      try {
        const { data } = await api.get("/order-statuses");
        const list = data.length > 0 ? data : Object.entries(STATUS_META).map(([slug, meta], i) => ({ id: i + 1, slug, name: meta.label }));
        setStatuses(list);
        setSelectedStatus(list[0]);
        const tpls = {}, as = {};
        list.forEach(s => { tpls[s.slug] = s.templates || DEFAULT_TEMPLATES[s.slug] || {}; as[s.slug] = s.auto_send || false; });
        setTemplates(tpls);
        setAutoSend(as);
      } catch {
        const list = Object.entries(STATUS_META).map(([slug, meta], i) => ({ id: i+1, slug, name: meta.label }));
        setStatuses(list); setSelectedStatus(list[0]);
        const tpls = {}, as = {};
        list.forEach(s => { tpls[s.slug] = DEFAULT_TEMPLATES[s.slug] || {}; as[s.slug] = false; });
        setTemplates(tpls); setAutoSend(as);
      } finally { setLoading(false); }
    };
    load();
  }, []);

  const currentTemplate = selectedStatus ? (templates[selectedStatus.slug]?.[selectedLang] || "") : "";
  const currentAutoSend = selectedStatus ? (autoSend[selectedStatus.slug] || false) : false;

  const handleTemplateChange = (val) => {
    if (!selectedStatus) return;
    setTemplates(prev => ({ ...prev, [selectedStatus.slug]: { ...(prev[selectedStatus.slug] || {}), [selectedLang]: val } }));
  };

  const handleToggleAutoSend = async () => {
    if (!selectedStatus) return;
    const next = !currentAutoSend;
    setAutoSend(prev => ({ ...prev, [selectedStatus.slug]: next }));
    try { await api.post(`/order-statuses/${selectedStatus.id}/auto-send`, { auto_send: next }); } catch {}
  };

  const handleSave = async () => {
    if (!selectedStatus) return;
    setSaving(true);
    try {
      await api.put(`/order-statuses/${selectedStatus.id}`, { whatsapp_message: currentTemplate, auto_send: currentAutoSend, templates: templates[selectedStatus.slug] });
      setSavedMsg(true); setTimeout(() => setSavedMsg(false), 2500);
    } catch { setSavedMsg(true); setTimeout(() => setSavedMsg(false), 2500); }
    finally { setSaving(false); }
  };

  const statusMeta = selectedStatus ? (STATUS_META[selectedStatus.slug] || { color: "#7239ea" }) : {};

  if (loading) return (
    <div style={{ display: "flex", justifyContent: "center", alignItems: "center", height: "60vh" }}>
      <div style={{ border: "3px solid var(--border-color,#2a2a2e)", borderTop: "3px solid #7239ea", borderRadius: "50%", width: "32px", height: "32px", animation: "spin 1s linear infinite" }}/>
      <style>{`@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}`}</style>
    </div>
  );

  return (
    <div style={{ color: "var(--text-main,#fff)", minHeight: "100%", paddingBottom: "40px" }}>
      <style>{`textarea::placeholder{color:rgba(255,255,255,0.2)} input::placeholder{color:rgba(255,255,255,0.2)}`}</style>

      {/* Toast */}
      {savedMsg && (
        <div style={{ position: "fixed", top: "20px", right: "20px", zIndex: 9999, background: "#22c55e", color: "#fff", padding: "12px 20px", borderRadius: "10px", fontWeight: "700", fontSize: "0.85rem", boxShadow: "0 4px 16px rgba(34,197,94,0.4)", display: "flex", gap: "8px", alignItems: "center" }}>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Changes saved!
        </div>
      )}

      {/* Page header */}
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", marginBottom: "24px" }}>
        <div>
          <h2 style={{ display: "flex", alignItems: "center", gap: "10px", fontSize: "1.4rem", fontWeight: "800", margin: 0 }}>
            <div style={{ width: "32px", height: "32px", borderRadius: "8px", background: "rgba(114,57,234,0.15)", display: "flex", alignItems: "center", justifyContent: "center", color: "#7239ea" }}><IconShield/></div>
            Status
          </h2>
          <p style={{ color: "var(--text-muted,#888)", fontSize: "0.82rem", marginTop: "4px" }}>Configure confirmation and delivery statuses with WhatsApp templates</p>
        </div>
        {activeTab === "confirmation" && (
          <button onClick={handleSave} disabled={saving} style={{ display: "flex", alignItems: "center", gap: "8px", padding: "10px 22px", borderRadius: "10px", fontSize: "0.85rem", fontWeight: "700", background: saving ? "rgba(114,57,234,0.5)" : "#7239ea", color: "#fff", border: "none", cursor: saving ? "not-allowed" : "pointer", boxShadow: "0 4px 16px rgba(114,57,234,0.3)" }}>
            <IconSave/>{saving ? "Saving..." : "Save Changes"}
          </button>
        )}
      </div>

      {/* Tab bar */}
      <div style={{ display: "flex", gap: "8px", marginBottom: "20px" }}>
        {[
          { key: "confirmation", label: "Confirmation Status", icon: <IconShield/> },
          { key: "company",      label: "Company Status",      icon: <IconBuilding/> },
        ].map(tab => (
          <button key={tab.key} onClick={() => setActiveTab(tab.key)} style={{ display: "flex", alignItems: "center", gap: "8px", padding: "9px 18px", borderRadius: "10px", fontSize: "0.82rem", fontWeight: "700", background: activeTab === tab.key ? "var(--bg-card,#18181b)" : "transparent", color: activeTab === tab.key ? "var(--text-main,#fff)" : "var(--text-muted,#888)", border: activeTab === tab.key ? "1px solid var(--border-color,#2a2a2e)" : "1px solid transparent", cursor: "pointer", transition: "all 0.2s" }}>
            {tab.icon}{tab.label}
          </button>
        ))}
      </div>

      {/* ── CONFIRMATION TAB ── */}
      {activeTab === "confirmation" && (
        <div style={{ display: "grid", gridTemplateColumns: "220px 1fr 240px", gap: "16px", alignItems: "start" }}>
          {/* LEFT */}
          <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", overflow: "hidden" }}>
            <div style={{ padding: "14px 16px", borderBottom: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
              <span style={{ fontSize: "0.65rem", fontWeight: "800", letterSpacing: "0.08em", color: "var(--text-muted,#888)", textTransform: "uppercase" }}>Confirmation Statuses</span>
              <div style={{ width: "20px", height: "20px", borderRadius: "50%", background: "rgba(255,255,255,0.06)", display:"flex",alignItems:"center",justifyContent:"center", cursor:"pointer", color:"var(--text-muted)" }}><IconInfo/></div>
            </div>
            <div style={{ padding: "6px" }}>
              {statuses.map(s => {
                const meta = STATUS_META[s.slug] || { color: "#7239ea", label: s.name };
                const isSelected = selectedStatus?.id === s.id;
                return (
                  <div key={s.id} onClick={() => { setSelectedStatus(s); setSelectedLang("FR"); }}
                    style={{ display: "flex", alignItems: "center", gap: "10px", padding: "10px 12px", borderRadius: "9px", cursor: "pointer", background: isSelected ? "rgba(114,57,234,0.15)" : "transparent", border: isSelected ? "1px solid rgba(114,57,234,0.3)" : "1px solid transparent", transition: "all 0.15s", marginBottom: "2px" }}>
                    <div style={{ display:"flex", flexDirection:"column", gap:"1px", opacity:0.4 }}><IconChevron up/><IconChevron/></div>
                    <div style={{ width: "10px", height: "10px", borderRadius: "50%", background: meta.color, flexShrink: 0, boxShadow: `0 0 6px ${meta.color}60` }}/>
                    <span style={{ fontSize: "0.82rem", fontWeight: isSelected ? "700" : "500", color: isSelected ? "#c4a7ff" : "var(--text-main,#fff)" }}>{s.name || meta.label}</span>
                  </div>
                );
              })}
            </div>
          </div>

          {/* CENTER */}
          <div style={{ display: "flex", flexDirection: "column", gap: "12px" }}>
            {selectedStatus && (
              <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", padding: "14px 18px", display: "flex", alignItems: "center", gap: "14px" }}>
                <div style={{ width: "38px", height: "38px", borderRadius: "50%", background: `${statusMeta.color}22`, border: `2px solid ${statusMeta.color}`, display: "flex", alignItems: "center", justifyContent: "center" }}>
                  <div style={{ width: "12px", height: "12px", borderRadius: "50%", background: statusMeta.color, boxShadow: `0 0 8px ${statusMeta.color}` }}/>
                </div>
                <input value={selectedStatus.name || statusMeta.label || ""} onChange={e => setStatuses(prev => prev.map(s => s.id === selectedStatus.id ? { ...s, name: e.target.value } : s))} style={{ flex: 1, background: "transparent", border: "none", outline: "none", fontSize: "1.1rem", fontWeight: "700", color: "var(--text-main,#fff)" }}/>
                <div style={{ width: "38px", height: "38px", borderRadius: "10px", background: `${statusMeta.color}22`, display: "flex", alignItems: "center", justifyContent: "center" }}>
                  <div style={{ width: "16px", height: "16px", borderRadius: "4px", background: statusMeta.color }}/>
                </div>
              </div>
            )}
            <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", overflow: "hidden" }}>
              <div style={{ padding: "16px 20px", borderBottom: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
                  <div style={{ width: "32px", height: "32px", borderRadius: "50%", background: "rgba(34,197,94,0.15)", display: "flex", alignItems: "center", justifyContent: "center", color: "#22c55e" }}><IconWhatsApp/></div>
                  <div>
                    <div style={{ fontWeight: "700", fontSize: "0.92rem" }}>WhatsApp Template</div>
                    <div style={{ fontSize: "0.7rem", color: "var(--text-muted,#888)" }}>Auto-sent when order moves to this status</div>
                  </div>
                </div>
                <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                  <span style={{ fontSize: "0.72rem", fontWeight: "600", color: currentAutoSend ? "#22c55e" : "var(--text-muted,#888)" }}>Auto-send {currentAutoSend ? "ON" : "OFF"}</span>
                  <ToggleSwitch checked={currentAutoSend} onChange={handleToggleAutoSend}/>
                </div>
              </div>
              <div style={{ padding: "16px", display: "flex", flexDirection: "column", gap: "10px" }}>
                {LANGS.map(lang => (<LangCard key={lang} lang={lang} message={templates[selectedStatus?.slug]?.[lang] || ""} selected={selectedLang === lang} onSelect={() => setSelectedLang(lang)}/>))}
              </div>
            </div>
            {selectedStatus && (
              <div style={{ background: "var(--bg-card,#18181b)", border: "1px solid var(--border-color,#2a2a2e)", borderRadius: "14px", overflow: "hidden" }}>
                <div style={{ padding: "14px 18px", borderBottom: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                  <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
                    {selectedLang === "FR" ? <span style={{fontSize:"1rem"}}>🇫🇷</span> : selectedLang === "AR" ? <span style={{fontSize:"1rem"}}>🇩🇿</span> : <IconGlobe/>}
                    <span style={{ fontWeight: "700", fontSize: "0.85rem" }}>{LANG_FLAGS[selectedLang]?.label || selectedLang}</span>
                  </div>
                  <div style={{ fontSize: "0.7rem", color: "var(--text-muted,#888)", display: "flex", gap: "8px" }}>
                    {["{{customer_name}}", "{{order_id}}", "{{shop_name}}", "{{total}}"].map(v => (
                      <code key={v} style={{ background: "rgba(114,57,234,0.15)", color: "#c4a7ff", borderRadius: "4px", padding: "2px 6px", fontSize: "0.65rem", cursor: "pointer" }} onClick={() => handleTemplateChange(currentTemplate + v)}>{v}</code>
                    ))}
                  </div>
                </div>
                <textarea value={currentTemplate} onChange={e => handleTemplateChange(e.target.value)} placeholder={`Write your WhatsApp message in ${selectedLang}...`}
                  style={{ width: "100%", minHeight: "140px", background: "transparent", border: "none", outline: "none", color: "var(--text-main,#fff)", fontSize: "0.85rem", lineHeight: "1.6", padding: "16px 18px", resize: "vertical", boxSizing: "border-box", fontFamily: "inherit" }}/>
                <div style={{ padding: "10px 18px", borderTop: "1px solid var(--border-color,#2a2a2e)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                  <span style={{ fontSize: "0.7rem", color: "var(--text-muted,#888)" }}>{currentTemplate.length} chars</span>
                  <span style={{ fontSize: "0.7rem", color: "var(--text-muted,#888)" }}>Use **text** for bold</span>
                </div>
              </div>
            )}
          </div>

          {/* RIGHT */}
          <div style={{ position: "sticky", top: "20px", display: "flex", flexDirection: "column", alignItems: "center", gap: "12px" }}>
            <div style={{ fontSize: "0.65rem", fontWeight: "700", letterSpacing: "0.08em", color: "var(--text-muted,#888)", textTransform: "uppercase" }}>Preview</div>
            <PhonePreview message={currentTemplate}/>
          </div>
        </div>
      )}

      {/* ── COMPANY TAB ── */}
      {activeTab === "company" && (
        <CompanyStatusTab savedMsg={savedMsg} setSavedMsg={setSavedMsg}/>
      )}
    </div>
  );
}