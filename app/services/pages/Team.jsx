import { useState, useEffect, useContext } from "react";
import { AuthContext } from "../auth/AuthContext";
import { useLanguage } from "../context/LanguageContext";
import { 
  getTeamData, 
  addTeamMember, 
  updateTeamMember, 
  saveTeamSettings, 
  deleteTeamMember,
  impersonateUser
} from "../services/teamService";

export default function Team() {
  const { login } = useContext(AuthContext);
  const { t } = useLanguage();

  const [members, setMembers] = useState([]);
  const [allProducts, setAllProducts] = useState([]);
  const [teamSettings, setTeamSettings] = useState({
    dispatch_auto: false,
    inactive_strategy: "do_nothing",
    commission_currency: "DZ DA"
  });

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [successMsg, setSuccessMsg] = useState(null);

  // Invitation Modal State
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [newEmail, setNewEmail] = useState("");
  const [newRole, setNewRole] = useState("agent"); // 'agent' or 'admin'
  const [isCreating, setIsCreating] = useState(false);

  // Edit Modal State
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [editingMember, setEditingMember] = useState(null);
  const [editName, setEditName] = useState("");
  const [editRole, setEditRole] = useState("agent");
  const [isUpdating, setIsUpdating] = useState(false);

  // Product Assignment Modal State
  const [isProductModalOpen, setIsProductModalOpen] = useState(false);
  const [productMember, setProductMember] = useState(null);
  const [selectedProductIds, setSelectedProductIds] = useState([]);
  const [productSearch, setProductSearch] = useState("");
  const [isSavingProducts, setIsSavingProducts] = useState(false);

  // Custom Currency Dropdown State
  const [isCurrencyDropdownOpen, setIsCurrencyDropdownOpen] = useState(false);

  const currencies = [
    { code: "DZ", symbol: "DA", label: "Dinar algérien" },
    { code: "MA", symbol: "MAD", label: "Dirham marocain" },
    { code: "TN", symbol: "TND", label: "Dinar tunisien" },
    { code: "EU", symbol: "EUR", label: "Euro" },
    { code: "US", symbol: "USD", label: "US Dollar" },
    { code: "GB", symbol: "GBP", label: "British Pound" },
    { code: "SA", symbol: "SAR", label: "Riyal saoudien" },
    { code: "AE", symbol: "AED", label: "Dirham des Émirats" },
    { code: "QA", symbol: "QAR", label: "Riyal qatari" },
    { code: "OM", symbol: "OMR", label: "Rial omanais" },
    { code: "BH", symbol: "BHD", label: "Dinar bahreïni" },
    { code: "KW", symbol: "KWD", label: "Dinar koweïtien" }
  ];

  const triggerOptions = [
    { value: "none", label: "Aucun" },
    { value: "confirmed", label: "Sur commande confirmée" },
    { value: "delivered", label: "Sur commande livrée" }
  ];

  // Fetch Team & Members & Products
  const fetchData = async () => {
    try {
      setLoading(true);
      const data = await getTeamData();
      setMembers(data.members || []);
      setAllProducts(data.products || []);
      if (data.team) {
        setTeamSettings({
          dispatch_auto: !!data.team.dispatch_auto,
          inactive_strategy: data.team.inactive_strategy || "do_nothing",
          commission_currency: data.team.commission_currency || "DZ DA"
        });
      }
      setError(null);
    } catch (err) {
      console.error(err);
      setError("Erreur lors de la récupération des données de l'équipe.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
  }, []);

  // Show Toast Success Msg
  const triggerToast = (msg) => {
    setSuccessMsg(msg);
    setTimeout(() => {
      setSuccessMsg(null);
    }, 4000);
  };

  // Add Member
  const handleAddMember = async (e) => {
    e.preventDefault();
    if (!newEmail) return;
    try {
      setIsCreating(true);
      const response = await addTeamMember({
        email: newEmail,
        role: newRole
      });
      setIsAddModalOpen(false);
      setNewEmail("");
      setNewRole("agent");
      triggerToast(response.message || "Membre ajouté avec succès !");
      fetchData();
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || "Erreur lors de l'ajout du membre.");
    } finally {
      setIsCreating(false);
    }
  };

  // Open Edit Modal
  const openEditModal = (member) => {
    setEditingMember(member);
    setEditName(member.name);
    setEditRole(member.role === 'staff' ? 'agent' : 'admin');
    setIsEditModalOpen(true);
  };

  // Save Edit Member
  const handleEditMemberSubmit = async (e) => {
    e.preventDefault();
    if (!editingMember) return;
    try {
      setIsUpdating(true);
      const response = await updateTeamMember(editingMember.id, {
        name: editName,
        role: editRole
      });
      setIsEditModalOpen(false);
      triggerToast(response.message || "Membre mis à jour avec succès !");
      fetchData();
    } catch (err) {
      console.error(err);
      alert("Erreur lors de la mise à jour.");
    } finally {
      setIsUpdating(false);
    }
  };

  // Impersonate Member (Login as him)
  const handleImpersonate = async (member) => {
    if (!confirm(`Voulez-vous vraiment simuler une connexion en tant que ${member.name} ?`)) return;
    try {
      const response = await impersonateUser(member.id);
      await login(response.token);
      triggerToast(`Connecté en tant que ${member.name} !`);
      window.location.href = "/dashboard";
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || "Erreur d'impersonation.");
    }
  };

  // Open Product Assignment Modal
  const openProductModal = (member) => {
    setProductMember(member);
    const assignedIds = (member.products || []).map(p => p.id);
    setSelectedProductIds(assignedIds);
    setProductSearch("");
    setIsProductModalOpen(true);
  };

  // Toggle single product selection
  const handleToggleProductSelection = (productId) => {
    if (selectedProductIds.includes(productId)) {
      setSelectedProductIds(selectedProductIds.filter(id => id !== productId));
    } else {
      setSelectedProductIds([...selectedProductIds, productId]);
    }
  };

  // Quick Select All / Deselect All for visible (filtered) products
  const getFilteredProducts = () => {
    return allProducts.filter(p => 
      p.name.toLowerCase().includes(productSearch.toLowerCase()) || 
      (p.sku && p.sku.toLowerCase().includes(productSearch.toLowerCase()))
    );
  };

  const handleSelectAllProducts = () => {
    const filteredIds = getFilteredProducts().map(p => p.id);
    const combined = Array.from(new Set([...selectedProductIds, ...filteredIds]));
    setSelectedProductIds(combined);
  };

  const handleDeselectAllProducts = () => {
    const filteredIds = getFilteredProducts().map(p => p.id);
    setSelectedProductIds(selectedProductIds.filter(id => !filteredIds.includes(id)));
  };

  // Save Product Assignment
  const handleSaveProductAssignment = async () => {
    if (!productMember) return;
    try {
      setIsSavingProducts(true);
      await updateTeamMember(productMember.id, {
        product_ids: selectedProductIds
      });
      setIsProductModalOpen(false);
      triggerToast(`Produits assignés à ${productMember.name} !`);
      fetchData();
    } catch (err) {
      console.error(err);
      alert("Erreur lors de l'assignation des produits.");
    } finally {
      setIsSavingProducts(false);
    }
  };

  // Toggle active/inactive state of a member
  const handleToggleActive = async (member) => {
    try {
      const updated = await updateTeamMember(member.id, {
        is_active: !member.is_active
      });
      setMembers(members.map(m => m.id === member.id ? updated.member : m));
      triggerToast(`${member.name} est maintenant ${!member.is_active ? 'actif' : 'inactif'}.`);
    } catch (err) {
      console.error(err);
    }
  };

  // Delete member
  const handleDeleteMember = async (id) => {
    if (!confirm("Voulez-vous vraiment supprimer ce membre de votre équipe ?")) return;
    try {
      await deleteTeamMember(id);
      setMembers(members.filter(m => m.id !== id));
      triggerToast("Membre supprimé de l'équipe.");
    } catch (err) {
      console.error(err);
      alert(err.response?.data?.message || "Erreur lors de la suppression.");
    }
  };

  // Dispatch Quota handlers
  const handleQuotaChange = (memberId, amount) => {
    setMembers(members.map(m => {
      if (m.id === memberId) {
        const next = Math.max(0, m.quota + amount);
        return { ...m, quota: next };
      }
      return m;
    }));
  };

  const handleToggleDispatchActive = (memberId) => {
    setMembers(members.map(m => {
      if (m.id === memberId) {
        return { ...m, is_dispatch_active: !m.is_dispatch_active };
      }
      return m;
    }));
  };

  const handleSaveQuotas = async () => {
    try {
      await Promise.all(
        members.map(m => updateTeamMember(m.id, {
          quota: m.quota,
          is_dispatch_active: m.is_dispatch_active
        }))
      );
      triggerToast("Quotas enregistrés avec succès !");
    } catch (err) {
      console.error(err);
      alert("Erreur lors de l'enregistrement des quotas.");
    }
  };

  // Inactive strategy change
  const handleStrategyChange = async (strategy) => {
    try {
      const nextSettings = { ...teamSettings, inactive_strategy: strategy };
      setTeamSettings(nextSettings);
      await saveTeamSettings(nextSettings);
      triggerToast("Stratégie d'inactivité mise à jour !");
    } catch (err) {
      console.error(err);
    }
  };

  // Auto dispatch toggle
  const handleToggleAutoDispatch = async () => {
    try {
      const nextSettings = { ...teamSettings, dispatch_auto: !teamSettings.dispatch_auto };
      setTeamSettings(nextSettings);
      await saveTeamSettings(nextSettings);
      triggerToast(`Dispatch auto ${!teamSettings.dispatch_auto ? 'activé' : 'désactivé'}.`);
    } catch (err) {
      console.error(err);
    }
  };

  // Commissions handlers
  const handleCommissionTriggerChange = (memberId, trigger) => {
    setMembers(members.map(m => {
      if (m.id === memberId) {
        return { ...m, commission_trigger: trigger };
      }
      return m;
    }));
  };

  const handleCommissionAmountChange = (memberId, amount) => {
    setMembers(members.map(m => {
      if (m.id === memberId) {
        const next = Math.max(0, parseFloat(m.commission_amount) + amount);
        return { ...m, commission_amount: next };
      }
      return m;
    }));
  };

  const handleCommissionTypeChange = (memberId, type) => {
    setMembers(members.map(m => {
      if (m.id === memberId) {
        return { ...m, commission_type: type };
      }
      return m;
    }));
  };

  const handleSelectCurrency = async (curr) => {
    const nextSettings = { ...teamSettings, commission_currency: `${curr.code} ${curr.symbol}` };
    setTeamSettings(nextSettings);
    setIsCurrencyDropdownOpen(false);
    try {
      await saveTeamSettings(nextSettings);
      triggerToast(`Devise modifiée pour ${curr.symbol} !`);
    } catch (err) {
      console.error(err);
    }
  };

  const handleSaveCommissions = async () => {
    try {
      await Promise.all(
        members.map(m => updateTeamMember(m.id, {
          commission_trigger: m.commission_trigger,
          commission_amount: m.commission_amount,
          commission_type: m.commission_type
        }))
      );
      await saveTeamSettings(teamSettings);
      triggerToast("Commissions enregistrées avec succès !");
    } catch (err) {
      console.error(err);
      alert("Erreur lors de l'enregistrement des commissions.");
    }
  };

  // Calculate dynamic quota percentages
  const activeDispatchMembers = members.filter(m => m.role === "staff" && m.is_dispatch_active);
  const totalQuota = activeDispatchMembers.reduce((sum, m) => sum + (parseInt(m.quota) || 0), 0);

  const getQuotaPercentage = (quota, isDispatchActive, role) => {
    if (role !== "staff" || !isDispatchActive || totalQuota === 0) return 0;
    return Math.round((quota / totalQuota) * 100);
  };

  if (loading && members.length === 0) {
    return (
      <div style={{ height: "100%", display: "flex", justifyContent: "center", alignItems: "center", color: "var(--text-muted)" }}>
        <div style={{ textAlign: "center" }}>
          <div className="spinner" style={{ margin: "0 auto 15px" }}></div>
          <p>Chargement de votre équipe...</p>
        </div>
      </div>
    );
  }

  // Get display symbol for commissions list
  const currentCurrencySymbol = teamSettings.commission_currency.split(" ")[1] || "DA";

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: "25px", paddingBottom: "40px" }}>
      
      {/* Success Toast */}
      {successMsg && (
        <div style={{
          position: "fixed",
          top: "20px",
          right: "20px",
          backgroundColor: "#1bc5bd",
          color: "white",
          padding: "12px 24px",
          borderRadius: "8px",
          boxShadow: "0 10px 30px rgba(0,0,0,0.2)",
          zIndex: 10000,
          fontWeight: 600,
          display: "flex",
          alignItems: "center",
          gap: "10px",
          animation: "popupFadeIn 0.2s ease-out"
        }}>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          {successMsg}
        </div>
      )}

      {/* Page Header */}
      <div className="page-header" style={{ marginBottom: "5px" }}>
        <div>
          <h1 className="page-title" style={{ fontSize: "1.6rem", display: "flex", alignItems: "center", gap: "10px" }}>
            <span style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "35px", height: "35px", borderRadius: "8px", background: "rgba(137, 80, 252, 0.1)", color: "var(--purple)" }}>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </span>
            {t("equipe")}
          </h1>
          <p className="page-subtitle">{members.filter(m => m.is_active).length} {t("actif").toLowerCase()}(s)</p>
        </div>
        <button 
          onClick={() => setIsAddModalOpen(true)}
          style={{
            backgroundColor: "var(--purple)",
            color: "white",
            padding: "10px 18px",
            borderRadius: "8px",
            fontWeight: 600,
            fontSize: "0.85rem",
            display: "flex",
            alignItems: "center",
            gap: "8px",
            boxShadow: "0 4px 15px rgba(137, 80, 252, 0.2)",
            transition: "all 0.2s",
            cursor: "pointer"
          }}
          className="btn-purple-hover"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
          {t("ajouter_membre")}
        </button>
      </div>

      {/* Members Grid Layout */}
      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(320px, 1fr))", gap: "20px" }}>
        {members.map(member => (
          <div key={member.id} className="card" style={{ display: "flex", flexDirection: "column", gap: "15px", padding: "18px", position: "relative" }}>
            
            {/* Action buttons row top right: Impersonate, Edit, Delete */}
            <div style={{
              position: "absolute",
              top: "15px",
              right: "15px",
              display: "flex",
              alignItems: "center",
              gap: "8px"
            }}>
              
              {/* Impersonate Login-as (🚪➡️) */}
              <button 
                onClick={() => handleImpersonate(member)}
                style={{
                  background: "none",
                  border: "1px solid var(--border-color)",
                  color: "var(--text-main)",
                  cursor: "pointer",
                  padding: "5px 6px",
                  borderRadius: "6px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  backgroundColor: "rgba(255,255,255,0.02)"
                }}
                className="btn-action-hover"
                title="Se connecter en tant que ce membre"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3"/></svg>
              </button>

              {/* Edit Member pencil (✏️) */}
              <button 
                onClick={() => openEditModal(member)}
                style={{
                  background: "none",
                  border: "1px solid var(--border-color)",
                  color: "var(--text-main)",
                  cursor: "pointer",
                  padding: "5px 6px",
                  borderRadius: "6px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  backgroundColor: "rgba(255,255,255,0.02)"
                }}
                className="btn-action-hover"
                title="Modifier le membre"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              </button>

              {/* Delete Member trash (🗑️) */}
              <button 
                onClick={() => handleDeleteMember(member.id)}
                style={{
                  background: "none",
                  border: "1px solid var(--border-color)",
                  color: "var(--text-muted)",
                  cursor: "pointer",
                  padding: "5px 6px",
                  borderRadius: "6px",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  backgroundColor: "rgba(255,255,255,0.02)"
                }}
                className="btn-action-hover"
                title="Supprimer ce membre"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
              </button>

            </div>

            {/* Profile Avatar & Details */}
            <div style={{ display: "flex", alignItems: "center", gap: "15px" }}>
              <img 
                src={member.avatar_url} 
                alt={member.name}
                style={{
                  width: "48px",
                  height: "48px",
                  borderRadius: "50%",
                  backgroundColor: "var(--bg-app)",
                  border: "2px solid var(--border-color)",
                  objectFit: "cover"
                }}
              />
              <div>
                <h3 style={{ fontSize: "1rem", fontWeight: 600, color: "var(--text-main)", marginBottom: "2px" }}>
                  {member.name}
                </h3>
                <p style={{ fontSize: "0.8rem", color: "var(--text-muted)" }}>
                  {member.email}
                </p>
              </div>
            </div>

            {/* Clickable Product Assignment Card Area */}
            <div 
              onClick={() => openProductModal(member)}
              style={{
                backgroundColor: "var(--bg-app)",
                border: "1px dashed var(--border-color)",
                borderRadius: "8px",
                padding: "10px 12px",
                display: "flex",
                alignItems: "center",
                justifyContent: "between",
                cursor: "pointer",
                transition: "border-color 0.2s"
              }}
              className="assigned-products-area-hover"
            >
              <div style={{ display: "flex", alignItems: "center", gap: "10px", flex: 1 }}>
                <span style={{ color: "var(--text-muted)" }}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </span>
                {member.products && member.products.length > 0 ? (
                  <div style={{ display: "flex", flexDirection: "column" }}>
                    <span style={{ fontSize: "0.75rem", fontWeight: 700, color: "var(--purple)" }}>
                      {member.products.length} produit(s) assigné(s)
                    </span>
                    <span style={{ fontSize: "0.7rem", color: "var(--text-muted)" }}>
                      {member.products.slice(0, 2).map(p => p.name).join(", ")}
                      {member.products.length > 2 && " ..."}
                    </span>
                  </div>
                ) : (
                  <div style={{ display: "flex", flexDirection: "column" }}>
                    <span style={{ fontSize: "0.75rem", fontWeight: 500, color: "var(--text-main)" }}>Aucun produit assigné</span>
                    <span style={{ fontSize: "0.7rem", color: "var(--text-muted)" }}>Ce membre verra toutes les commandes</span>
                  </div>
                )}
              </div>
              <span style={{ color: "var(--text-muted)", fontSize: "1rem" }}>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="9 18 15 12 9 6"/></svg>
              </span>
            </div>

            {/* Wallet Balance Display */}
            {member.role === "staff" && (
              <div style={{
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                padding: "8px 12px",
                borderRadius: "8px",
                backgroundColor: "rgba(80, 205, 137, 0.08)",
                border: "1px solid rgba(80, 205, 137, 0.15)",
                color: "#50cd89",
                fontSize: "0.8rem",
                fontWeight: "600"
              }}>
                <div style={{ display: "flex", alignItems: "center", gap: "6px" }}>
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="12" y1="10" x2="12" y2="10"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                  <span>Portefeuille</span>
                </div>
                <span>{member.wallet_balance ? parseFloat(member.wallet_balance).toFixed(2) : "0.00"} {currentCurrencySymbol}</span>
              </div>
            )}

            {/* Badges footer */}
            <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", borderTop: "1px solid var(--border-color)", paddingTop: "12px", marginTop: "5px" }}>
              <div style={{ display: "flex", gap: "8px" }}>
                <span style={{
                  fontSize: "0.7rem",
                  fontWeight: 600,
                  textTransform: "uppercase",
                  padding: "4px 8px",
                  borderRadius: "4px",
                  backgroundColor: member.role === "admin" ? "rgba(113, 113, 122, 0.15)" : "rgba(137, 80, 252, 0.15)",
                  color: member.role === "admin" ? "var(--text-muted)" : "var(--purple)"
                }}>
                  {member.role === "admin" ? "Admin" : "Agent"}
                </span>

                <button 
                  onClick={() => handleToggleActive(member)}
                  style={{
                    fontSize: "0.7rem",
                    fontWeight: 600,
                    padding: "4px 8px",
                    borderRadius: "4px",
                    backgroundColor: member.is_active ? "rgba(27, 197, 189, 0.15)" : "rgba(246, 78, 96, 0.15)",
                    color: member.is_active ? "var(--success)" : "var(--danger)",
                    display: "flex",
                    alignItems: "center",
                    gap: "5px",
                    cursor: "pointer"
                  }}
                >
                  <span style={{
                    width: "6px",
                    height: "6px",
                    borderRadius: "50%",
                    backgroundColor: member.is_active ? "var(--success)" : "var(--danger)"
                  }}></span>
                  {member.is_active ? "Actif" : "Inactif"}
                </button>
              </div>
              
              <span style={{ fontSize: "0.75rem", color: "var(--text-muted)" }}>
                Jamais
              </span>
            </div>

          </div>
        ))}
      </div>

      {/* Order Dispatch Section */}
      <div className="card" style={{ display: "flex", flexDirection: "column", gap: "20px", padding: "24px" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <div>
            <h2 style={{ fontSize: "1.15rem", fontWeight: 600, color: "var(--text-main)", display: "flex", alignItems: "center", gap: "8px" }}>
              <span style={{ color: "var(--warning)" }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
              </span>
              Dispatch des commandes
            </h2>
            <p style={{ fontSize: "0.8rem", color: "var(--text-muted)", marginTop: "4px" }}>
              Définissez les quotas pour distribuer automatiquement les commandes
            </p>
          </div>
          
          <div style={{ display: "flex", alignItems: "center", gap: "15px" }}>
            <button 
              onClick={handleSaveQuotas}
              style={{
                backgroundColor: "transparent",
                border: "1px solid var(--border-color)",
                color: "var(--text-main)",
                padding: "8px 16px",
                borderRadius: "6px",
                fontWeight: 600,
                fontSize: "0.8rem",
                cursor: "pointer"
              }}
            >
              Enregistrer les quotas
            </button>

            <div style={{ display: "flex", alignItems: "center", gap: "8px" }}>
              <span style={{ fontSize: "0.8rem", fontWeight: 500, color: "var(--text-muted)" }}>Dispatch auto</span>
              <button 
                onClick={handleToggleAutoDispatch}
                style={{
                  width: "40px",
                  height: "22px",
                  borderRadius: "15px",
                  backgroundColor: teamSettings.dispatch_auto ? "var(--success)" : "var(--border-color)",
                  position: "relative",
                  cursor: "pointer",
                  transition: "background-color 0.2s"
                }}
              >
                <div style={{
                  width: "16px",
                  height: "16px",
                  borderRadius: "50%",
                  backgroundColor: "white",
                  position: "absolute",
                  top: "3px",
                  left: teamSettings.dispatch_auto ? "21px" : "3px",
                  transition: "left 0.2s"
                }}></div>
              </button>
            </div>
          </div>
        </div>

        {/* Quota members list */}
        <div style={{ display: "flex", flexDirection: "column", gap: "2px", borderTop: "1px solid var(--border-color)", paddingTop: "10px" }}>
          {members.filter(m => m.role === "staff").map(member => {
            const pct = getQuotaPercentage(member.quota, member.is_dispatch_active, member.role);
            return (
              <div key={member.id} style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "12px 10px", borderRadius: "8px", borderBottom: "1px solid var(--border-color)" }}>
                <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
                  <img 
                    src={member.avatar_url} 
                    alt={member.name}
                    style={{ width: "32px", height: "32px", borderRadius: "50%" }}
                  />
                  <div>
                    <span style={{ fontSize: "0.85rem", fontWeight: 600, color: "var(--text-main)" }}>{member.name}</span>
                    <span style={{ fontSize: "0.75rem", color: "var(--text-muted)", marginLeft: "10px" }}>0 assigné</span>
                  </div>
                </div>

                <div style={{ display: "flex", alignItems: "center", gap: "25px" }}>
                  
                  {/* Quota Clicker */}
                  <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                    <span style={{ fontSize: "0.75rem", textTransform: "uppercase", fontWeight: 700, color: "var(--text-muted)" }}>Quota</span>
                    <div style={{ display: "flex", alignItems: "center", gap: "3px", backgroundColor: "var(--bg-app)", border: "1px solid var(--border-color)", borderRadius: "6px", padding: "2px" }}>
                      <button 
                        onClick={() => handleQuotaChange(member.id, -1)}
                        style={{ width: "24px", height: "24px", borderRadius: "4px", backgroundColor: "transparent", color: "var(--text-main)", fontSize: "1rem", display: "flex", alignItems: "center", justifyContent: "center" }}
                      >-</button>
                      <span style={{ width: "30px", textAlign: "center", fontSize: "0.85rem", fontWeight: 600 }}>{member.quota}</span>
                      <button 
                        onClick={() => handleQuotaChange(member.id, 1)}
                        style={{ width: "24px", height: "24px", borderRadius: "4px", backgroundColor: "transparent", color: "var(--text-main)", fontSize: "1rem", display: "flex", alignItems: "center", justifyContent: "center" }}
                      >+</button>
                    </div>
                  </div>

                  {/* Percentage quota */}
                  <span style={{ fontSize: "0.85rem", fontWeight: 700, color: member.is_dispatch_active ? "var(--warning)" : "var(--text-muted)", width: "45px", textAlign: "right" }}>
                    {pct}%
                  </span>

                  {/* Dispatch toggle per member */}
                  <button 
                    onClick={() => handleToggleDispatchActive(member.id)}
                    style={{
                      width: "35px",
                      height: "18px",
                      borderRadius: "10px",
                      backgroundColor: member.is_dispatch_active ? "var(--success)" : "var(--border-color)",
                      position: "relative",
                      cursor: "pointer",
                      transition: "background-color 0.2s"
                    }}
                  >
                    <div style={{
                      width: "12px",
                      height: "12px",
                      borderRadius: "50%",
                      backgroundColor: "white",
                      position: "absolute",
                      top: "3px",
                      left: member.is_dispatch_active ? "20px" : "3px",
                      transition: "left 0.2s"
                    }}></div>
                  </button>

                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Inactive Agent strategy card selector */}
      <div className="card" style={{ display: "flex", flexDirection: "column", gap: "15px", padding: "24px" }}>
        <div>
          <h2 style={{ fontSize: "1.1rem", fontWeight: 600, color: "var(--text-main)", display: "flex", alignItems: "center", gap: "8px" }}>
            <span style={{ color: "var(--danger)" }}>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </span>
            Si un agent est inactif
          </h2>
          <p style={{ fontSize: "0.8rem", color: "var(--text-muted)", marginTop: "4px" }}>
            Choisissez ce qui arrive aux commandes assignées lorsqu'un agent n'est pas connecté
          </p>
        </div>

        {/* 3 Choice Cards */}
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: "15px", marginTop: "10px" }}>
          
          <div 
            onClick={() => handleStrategyChange("do_nothing")}
            style={{
              padding: "20px 15px",
              borderRadius: "10px",
              border: `1.5px solid ${teamSettings.inactive_strategy === "do_nothing" ? "var(--purple)" : "var(--border-color)"}`,
              backgroundColor: teamSettings.inactive_strategy === "do_nothing" ? "rgba(137, 80, 252, 0.05)" : "transparent",
              cursor: "pointer",
              display: "flex",
              flexDirection: "column",
              gap: "10px",
              transition: "all 0.2s"
            }}
          >
            <span style={{ color: teamSettings.inactive_strategy === "do_nothing" ? "var(--purple)" : "var(--text-muted)" }}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </span>
            <div>
              <h3 style={{ fontSize: "0.85rem", fontWeight: 700, color: "var(--text-main)" }}>Ne rien faire</h3>
              <p style={{ fontSize: "0.75rem", color: "var(--text-muted)", marginTop: "4px" }}>
                Les commandes restent assignées à l'agent inactif
              </p>
            </div>
          </div>

          <div 
            onClick={() => handleStrategyChange("transfer")}
            style={{
              padding: "20px 15px",
              borderRadius: "10px",
              border: `1.5px solid ${teamSettings.inactive_strategy === "transfer" ? "var(--purple)" : "var(--border-color)"}`,
              backgroundColor: teamSettings.inactive_strategy === "transfer" ? "rgba(137, 80, 252, 0.05)" : "transparent",
              cursor: "pointer",
              display: "flex",
              flexDirection: "column",
              gap: "10px",
              transition: "all 0.2s"
            }}
          >
            <span style={{ color: teamSettings.inactive_strategy === "transfer" ? "var(--purple)" : "var(--text-muted)" }}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <div>
              <h3 style={{ fontSize: "0.85rem", fontWeight: 700, color: "var(--text-main)" }}>Transférer à un ou plusieurs agents</h3>
              <p style={{ fontSize: "0.75rem", color: "var(--text-muted)", marginTop: "4px" }}>
                Les commandes vont à un ou plusieurs agents choisis
              </p>
            </div>
          </div>

          <div 
            onClick={() => handleStrategyChange("redistribute")}
            style={{
              padding: "20px 15px",
              borderRadius: "10px",
              border: `1.5px solid ${teamSettings.inactive_strategy === "redistribute" ? "var(--purple)" : "var(--border-color)"}`,
              backgroundColor: teamSettings.inactive_strategy === "redistribute" ? "rgba(137, 80, 252, 0.05)" : "transparent",
              cursor: "pointer",
              display: "flex",
              flexDirection: "column",
              gap: "10px",
              transition: "all 0.2s"
            }}
          >
            <span style={{ color: teamSettings.inactive_strategy === "redistribute" ? "var(--purple)" : "var(--text-muted)" }}>
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            </span>
            <div>
              <h3 style={{ fontSize: "0.85rem", fontWeight: 700, color: "var(--text-main)" }}>Redistribuer par quota</h3>
              <p style={{ fontSize: "0.75rem", color: "var(--text-muted)", marginTop: "4px" }}>
                Les commandes sont distribuées entre les agents actifs par quota
              </p>
            </div>
          </div>

        </div>
      </div>

      {/* Commissions settings card */}
      <div className="card" style={{ display: "flex", flexDirection: "column", gap: "20px", padding: "24px" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center" }}>
          <div>
            <h2 style={{ fontSize: "1.15rem", fontWeight: 600, color: "var(--text-main)", display: "flex", alignItems: "center", gap: "8px" }}>
              <span style={{ color: "var(--success)" }}>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </span>
              Commissions
            </h2>
            <p style={{ fontSize: "0.8rem", color: "var(--text-muted)", marginTop: "4px" }}>
              Définissez la commission par commande pour chaque membre
            </p>
          </div>

          <div style={{ display: "flex", alignItems: "center", gap: "12px", position: "relative" }}>
            
            {/* Custom Currency Selector Dropdown matching Image 3 perfectly */}
            <div style={{ position: "relative" }}>
              <button 
                onClick={() => setIsCurrencyDropdownOpen(!isCurrencyDropdownOpen)}
                style={{
                  backgroundColor: "var(--bg-app)",
                  border: "1px solid var(--border-color)",
                  color: "var(--text-main)",
                  borderRadius: "6px",
                  padding: "8px 14px",
                  fontSize: "0.8rem",
                  fontWeight: 600,
                  display: "flex",
                  alignItems: "center",
                  gap: "8px",
                  cursor: "pointer"
                }}
              >
                {/* Find current details */}
                {(() => {
                  const [code, sym] = teamSettings.commission_currency.split(" ");
                  return (
                    <>
                      <span style={{ color: "var(--purple)", fontWeight: 700 }}>{code} {sym}</span>
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M6 9l6 6 6-6"/></svg>
                    </>
                  );
                })()}
              </button>

              {isCurrencyDropdownOpen && (
                <>
                  <div 
                    style={{ position: "fixed", top: 0, left: 0, width: "100%", height: "100%", zIndex: 999 }}
                    onClick={() => setIsCurrencyDropdownOpen(false)}
                  ></div>
                  
                  <div style={{
                    position: "absolute",
                    bottom: "38px",
                    right: 0,
                    width: "240px",
                    maxHeight: "220px",
                    backgroundColor: "#18181b",
                    border: "1px solid #27272a",
                    borderRadius: "8px",
                    boxShadow: "0 10px 25px rgba(0,0,0,0.5)",
                    zIndex: 1000,
                    overflowY: "auto",
                    padding: "4px",
                    animation: "popupFadeIn 0.15s ease"
                  }} className="custom-scrollbar">
                    {currencies.map(curr => {
                      const isSelected = teamSettings.commission_currency === `${curr.code} ${curr.symbol}`;
                      return (
                        <div 
                          key={curr.code}
                          onClick={() => handleSelectCurrency(curr)}
                          style={{
                            padding: "8px 12px",
                            borderRadius: "6px",
                            display: "flex",
                            justifyContent: "space-between",
                            alignItems: "center",
                            cursor: "pointer",
                            backgroundColor: isSelected ? "rgba(137, 80, 252, 0.15)" : "transparent",
                            transition: "background-color 0.15s"
                          }}
                          className="currency-item-hover"
                        >
                          <span style={{ fontSize: "0.8rem", fontWeight: 700, color: isSelected ? "var(--purple)" : "white" }}>
                            {curr.code} <span style={{ color: isSelected ? "var(--purple)" : "var(--text-muted)", marginLeft: "4px" }}>{curr.symbol}</span>
                          </span>
                          <span style={{ fontSize: "0.75rem", color: "#71717a", textAlign: "right" }}>
                            {curr.label}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                </>
              )}
            </div>

            <button 
              onClick={handleSaveCommissions}
              style={{
                backgroundColor: "transparent",
                border: "1px solid var(--border-color)",
                color: "var(--text-main)",
                padding: "8px 16px",
                borderRadius: "6px",
                fontWeight: 600,
                fontSize: "0.8rem",
                cursor: "pointer"
              }}
            >
              Enregistrer les commissions
            </button>
          </div>
        </div>

        {/* Commissions Members List */}
        <div style={{ display: "flex", flexDirection: "column", gap: "2px", borderTop: "1px solid var(--border-color)", paddingTop: "10px" }}>
          {members.filter(m => m.role === "staff").map(member => (
            <div key={member.id} style={{ display: "flex", alignItems: "center", justifyContent: "space-between", padding: "12px 10px", borderBottom: "1px solid var(--border-color)" }}>
              <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
                <img 
                  src={member.avatar_url} 
                  alt={member.name}
                  style={{ width: "32px", height: "32px", borderRadius: "50%" }}
                />
                <div>
                  <span style={{ fontSize: "0.85rem", fontWeight: 600, color: "var(--text-main)" }}>{member.name}</span>
                  <span style={{ fontSize: "0.75rem", color: "var(--success)", fontWeight: 700, marginLeft: "10px" }}>
                    0 {currentCurrencySymbol}
                  </span>
                </div>
              </div>

              <div style={{ display: "flex", alignItems: "center", gap: "15px" }}>
                
                {/* Commission Trigger Dropdown */}
                <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                  <span style={{ fontSize: "0.75rem", fontWeight: 700, color: "var(--text-muted)", textTransform: "uppercase" }}>Déclencheur</span>
                  <select 
                    value={member.commission_trigger || "none"}
                    onChange={(e) => handleCommissionTriggerChange(member.id, e.target.value)}
                    style={{
                      backgroundColor: "var(--bg-app)",
                      border: "1px solid var(--border-color)",
                      color: "var(--text-main)",
                      borderRadius: "6px",
                      padding: "6px 12px",
                      fontSize: "0.8rem"
                    }}
                  >
                    {triggerOptions.map(opt => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>

                {/* Amount input controller */}
                <div style={{ display: "flex", alignItems: "center", gap: "10px" }}>
                  <span style={{ fontSize: "0.75rem", fontWeight: 700, color: "var(--text-muted)", textTransform: "uppercase" }}>Montant</span>
                  <div style={{ display: "flex", alignItems: "center", gap: "3px", backgroundColor: "var(--bg-app)", border: "1px solid var(--border-color)", borderRadius: "6px", padding: "2px" }}>
                    <button 
                      onClick={() => handleCommissionAmountChange(member.id, -5)}
                      style={{ width: "24px", height: "24px", borderRadius: "4px", backgroundColor: "transparent", color: "var(--text-main)", fontSize: "1rem", display: "flex", alignItems: "center", justifyContent: "center" }}
                    >-</button>
                    <span style={{ width: "40px", textAlign: "center", fontSize: "0.85rem", fontWeight: 600 }}>{parseFloat(member.commission_amount || 0)}</span>
                    <button 
                      onClick={() => handleCommissionAmountChange(member.id, 5)}
                      style={{ width: "24px", height: "24px", borderRadius: "4px", backgroundColor: "transparent", color: "var(--text-main)", fontSize: "1rem", display: "flex", alignItems: "center", justifyContent: "center" }}
                    >+</button>
                  </div>
                </div>

                {/* Amount Type Selector */}
                <div style={{ display: "flex", border: "1px solid var(--border-color)", borderRadius: "6px", overflow: "hidden" }}>
                  <button 
                    onClick={() => handleCommissionTypeChange(member.id, "fixed")}
                    style={{
                      backgroundColor: member.commission_type === "fixed" ? "var(--purple)" : "var(--bg-app)",
                      color: member.commission_type === "fixed" ? "white" : "var(--text-main)",
                      padding: "6px 12px",
                      fontSize: "0.8rem",
                      fontWeight: 600,
                      cursor: "pointer"
                    }}
                  >
                    {currentCurrencySymbol}
                  </button>
                  <button 
                    onClick={() => handleCommissionTypeChange(member.id, "percent")}
                    style={{
                      backgroundColor: member.commission_type === "percent" ? "var(--purple)" : "var(--bg-app)",
                      color: member.commission_type === "percent" ? "white" : "var(--text-main)",
                      padding: "6px 12px",
                      fontSize: "0.8rem",
                      fontWeight: 600,
                      cursor: "pointer"
                    }}
                  >
                    %
                  </button>
                </div>

              </div>
            </div>
          ))}
        </div>
      </div>

      {/* 1. Interactive invitation Modal */}
      {isAddModalOpen && (
        <div style={{
          position: "fixed", top: 0, left: 0, width: "100%", height: "100%",
          backgroundColor: "rgba(0,0,0,0.6)", backdropFilter: "blur(4px)",
          display: "flex", justifyContent: "center", alignItems: "center", zIndex: 100000,
          animation: "popupFadeIn 0.2s ease"
        }} onClick={() => setIsAddModalOpen(false)}>
          <div 
            style={{
              width: "480px", backgroundColor: "#18181b", borderRadius: "14px",
              border: "1px solid #27272a", boxShadow: "0 25px 50px -12px rgba(0, 0, 0, 0.5)",
              overflow: "hidden", animation: "popupFadeIn 0.15s ease-out"
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", padding: "20px 24px" }}>
              <div style={{ display: "flex", gap: "15px" }}>
                <span style={{
                  display: "flex", alignItems: "center", justifyContent: "center",
                  width: "40px", height: "40px", borderRadius: "10px",
                  background: "rgba(137, 80, 252, 0.1)", color: "var(--purple)"
                }}>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
                </span>
                <div>
                  <h3 style={{ fontSize: "1.2rem", fontWeight: 700, color: "white" }}>Ajouter un membre</h3>
                  <p style={{ fontSize: "0.8rem", color: "var(--text-muted)", marginTop: "2px" }}>Ajouter quelqu'un à votre équipe</p>
                </div>
              </div>
              <button onClick={() => setIsAddModalOpen(false)} style={{ width: "24px", height: "24px", borderRadius: "50%", backgroundColor: "rgba(255,255,255,0.08)", color: "#a1a1aa", display: "flex", alignItems: "center", justifyContent: "center", cursor: "pointer" }}>&times;</button>
            </div>
            <hr style={{ border: 0, borderTop: "1px solid #27272a" }} />
            <form onSubmit={handleAddMember} style={{ padding: "20px 24px", display: "flex", flexDirection: "column", gap: "20px" }}>
              <div style={{ display: "flex", flexDirection: "column", gap: "8px" }}>
                <label style={{ fontSize: "0.85rem", fontWeight: 600, color: "#d4d4d8" }}>Email</label>
                <div style={{ position: "relative" }}>
                  <input 
                    type="email" placeholder="rihabmahdi18@gmail.com" value={newEmail}
                    onChange={(e) => setNewEmail(e.target.value)} required
                    style={{ width: "100%", padding: "12px 40px 12px 14px", backgroundColor: "rgba(255,255,255,0.03)", border: "1px solid #3f3f46", borderRadius: "8px", color: "white", fontSize: "0.9rem", outline: "none" }}
                  />
                  <div style={{ position: "absolute", right: "14px", top: "50%", transform: "translateY(-50%)", display: "flex", alignItems: "center", justifyContent: "center", width: "18px", height: "18px", backgroundColor: "white", borderRadius: "50%" }}>
                    <svg width="10" height="10" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
                  </div>
                </div>
                <div style={{ display: "flex", alignItems: "center", gap: "6px", fontSize: "0.75rem", color: "#a1a1aa", marginTop: "4px" }}>
                  <svg width="12" height="12" viewBox="0 0 24 24"><path fill="#ea4335" d="M21.35 11.1H12v2.7h5.3c-.2 1.2-.9 2.1-2 2.8v2.3h3.2c1.9-1.8 3-4.4 3-7.5 0-.6-.05-1.1-.15-1.6z"/><path fill="#34a853" d="M12 21c2.4 0 4.5-.8 6-2.2l-3.2-2.3c-.9.6-2 .9-3.2.9-2.5 0-4.6-1.7-5.3-4H2.9v2.5C4.4 18.8 8 21 12 21z"/><path fill="#fbbc05" d="M6.7 13.4c-.2-.6-.3-1.3-.3-2s.1-1.4.3-2V6.9H2.9C2.1 8.5 1.7 10.2 1.7 12s.4 3.5 1.2 5.1l3.8-3.7z"/><path fill="#4285f4" d="M12 5.9c1.3 0 2.5.5 3.4 1.3l2.6-2.6C16.4 3.2 14.4 2.5 12 2.5 8 2.5 4.4 4.7 2.9 7.8l3.8 3.7c.7-2.4 2.8-4.1 5.3-4.1z"/></svg>
                  Le membre recevra une invitation par email pour se connecter via Google
                </div>
              </div>
              <div style={{ display: "flex", flexDirection: "column", gap: "10px" }}>
                <label style={{ fontSize: "0.85rem", fontWeight: 600, color: "#d4d4d8" }}>Rôle</label>
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "15px" }}>
                  <div onClick={() => setNewRole("agent")} style={{ padding: "16px", borderRadius: "10px", border: `2px solid ${newRole === "agent" ? "var(--purple)" : "#27272a"}`, backgroundColor: newRole === "agent" ? "rgba(137, 80, 252, 0.05)" : "rgba(255,255,255,0.01)", cursor: "pointer", display: "flex", flexDirection: "column", gap: "10px", transition: "all 0.2s" }}>
                    <span style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "30px", height: "30px", borderRadius: "50%", background: newRole === "agent" ? "rgba(137, 80, 252, 0.15)" : "rgba(255,255,255,0.05)", color: newRole === "agent" ? "var(--purple)" : "#71717a" }}><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <div>
                      <h4 style={{ fontSize: "0.85rem", fontWeight: 700, color: newRole === "agent" ? "var(--purple)" : "white" }}>Agent</h4>
                      <p style={{ fontSize: "0.75rem", color: "#a1a1aa", marginTop: "3px", lineHeight: "1.2" }}>Peut voir et gérer les commandes</p>
                    </div>
                  </div>
                  <div onClick={() => setNewRole("admin")} style={{ padding: "16px", borderRadius: "10px", border: `2px solid ${newRole === "admin" ? "var(--purple)" : "#27272a"}`, backgroundColor: newRole === "admin" ? "rgba(137, 80, 252, 0.05)" : "rgba(255,255,255,0.01)", cursor: "pointer", display: "flex", flexDirection: "column", gap: "10px", transition: "all 0.2s" }}>
                    <span style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "30px", height: "30px", borderRadius: "50%", background: newRole === "admin" ? "rgba(137, 80, 252, 0.15)" : "rgba(255,255,255,0.05)", color: newRole === "admin" ? "var(--purple)" : "#71717a" }}><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                    <div>
                      <h4 style={{ fontSize: "0.85rem", fontWeight: 700, color: newRole === "admin" ? "var(--purple)" : "white" }}>Admin</h4>
                      <p style={{ fontSize: "0.75rem", color: "#a1a1aa", marginTop: "3px", lineHeight: "1.2" }}>Accès complet à tout</p>
                    </div>
                  </div>
                </div>
              </div>
              <hr style={{ border: 0, borderTop: "1px solid #27272a", margin: "5px 0" }} />
              <div style={{ display: "flex", gap: "15px", justifyContent: "flex-end" }}>
                <button type="button" onClick={() => setIsAddModalOpen(false)} style={{ backgroundColor: "transparent", border: "1px solid #3f3f46", color: "white", padding: "10px 20px", borderRadius: "8px", fontWeight: 600, fontSize: "0.85rem", cursor: "pointer" }}>Annuler</button>
                <button type="submit" disabled={isCreating} style={{ backgroundColor: "var(--purple)", color: "white", padding: "10px 24px", borderRadius: "8px", fontWeight: 600, fontSize: "0.85rem", display: "flex", alignItems: "center", gap: "8px", cursor: "pointer" }}>
                  {isCreating ? <span className="spinner" style={{ width: "12px", height: "12px", border: "2px solid white", borderTopColor: "transparent" }}></span> : <><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>Créer</>}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 2. Interactive Member Edit Modal (✏️) */}
      {isEditModalOpen && (
        <div style={{
          position: "fixed", top: 0, left: 0, width: "100%", height: "100%",
          backgroundColor: "rgba(0,0,0,0.6)", backdropFilter: "blur(4px)",
          display: "flex", justifyContent: "center", alignItems: "center", zIndex: 100000,
          animation: "popupFadeIn 0.2s ease"
        }} onClick={() => setIsEditModalOpen(false)}>
          <div 
            style={{
              width: "480px", backgroundColor: "#18181b", borderRadius: "14px",
              border: "1px solid #27272a", boxShadow: "0 25px 50px -12px rgba(0, 0, 0, 0.5)",
              overflow: "hidden", animation: "popupFadeIn 0.15s ease-out"
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", padding: "20px 24px" }}>
              <div style={{ display: "flex", gap: "15px" }}>
                <span style={{
                  display: "flex", alignItems: "center", justifyContent: "center",
                  width: "40px", height: "40px", borderRadius: "10px",
                  background: "rgba(137, 80, 252, 0.1)", color: "var(--purple)"
                }}>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </span>
                <div>
                  <h3 style={{ fontSize: "1.2rem", fontWeight: 700, color: "white" }}>Modifier le membre</h3>
                  <p style={{ fontSize: "0.8rem", color: "var(--text-muted)", marginTop: "2px" }}>{editingMember?.email}</p>
                </div>
              </div>
              <button onClick={() => setIsEditModalOpen(false)} style={{ width: "24px", height: "24px", borderRadius: "50%", backgroundColor: "rgba(255,255,255,0.08)", color: "#a1a1aa", display: "flex", alignItems: "center", justifyContent: "center", cursor: "pointer" }}>&times;</button>
            </div>
            <hr style={{ border: 0, borderTop: "1px solid #27272a" }} />
            <form onSubmit={handleEditMemberSubmit} style={{ padding: "20px 24px", display: "flex", flexDirection: "column", gap: "20px" }}>
              <div style={{ display: "flex", flexDirection: "column", gap: "8px" }}>
                <label style={{ fontSize: "0.85rem", fontWeight: 600, color: "#d4d4d8" }}>Nom</label>
                <input 
                  type="text" value={editName} onChange={(e) => setEditName(e.target.value)} required
                  style={{ width: "100%", padding: "12px 14px", backgroundColor: "rgba(255,255,255,0.03)", border: "1px solid #3f3f46", borderRadius: "8px", color: "white", fontSize: "0.9rem", outline: "none" }}
                />
              </div>
              <div style={{ display: "flex", flexDirection: "column", gap: "10px" }}>
                <label style={{ fontSize: "0.85rem", fontWeight: 600, color: "#d4d4d8" }}>Rôle</label>
                <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "15px" }}>
                  <div onClick={() => setEditRole("agent")} style={{ padding: "16px", borderRadius: "10px", border: `2px solid ${editRole === "agent" ? "var(--purple)" : "#27272a"}`, backgroundColor: editRole === "agent" ? "rgba(137, 80, 252, 0.05)" : "rgba(255,255,255,0.01)", cursor: "pointer", display: "flex", flexDirection: "column", gap: "10px", transition: "all 0.2s" }}>
                    <span style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "30px", height: "30px", borderRadius: "50%", background: editRole === "agent" ? "rgba(137, 80, 252, 0.15)" : "rgba(255,255,255,0.05)", color: editRole === "agent" ? "var(--purple)" : "#71717a" }}><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <div>
                      <h4 style={{ fontSize: "0.85rem", fontWeight: 700, color: editRole === "agent" ? "var(--purple)" : "white" }}>Agent</h4>
                      <p style={{ fontSize: "0.75rem", color: "#a1a1aa", marginTop: "3px", lineHeight: "1.2" }}>Peut voir et gérer les commandes</p>
                    </div>
                  </div>
                  <div onClick={() => setEditRole("admin")} style={{ padding: "16px", borderRadius: "10px", border: `2px solid ${editRole === "admin" ? "var(--purple)" : "#27272a"}`, backgroundColor: editRole === "admin" ? "rgba(137, 80, 252, 0.05)" : "rgba(255,255,255,0.01)", cursor: "pointer", display: "flex", flexDirection: "column", gap: "10px", transition: "all 0.2s" }}>
                    <span style={{ display: "flex", alignItems: "center", justifyContent: "center", width: "30px", height: "30px", borderRadius: "50%", background: editRole === "admin" ? "rgba(137, 80, 252, 0.15)" : "rgba(255,255,255,0.05)", color: editRole === "admin" ? "var(--purple)" : "#71717a" }}><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                    <div>
                      <h4 style={{ fontSize: "0.85rem", fontWeight: 700, color: editRole === "admin" ? "var(--purple)" : "white" }}>Admin</h4>
                      <p style={{ fontSize: "0.75rem", color: "#a1a1aa", marginTop: "3px", lineHeight: "1.2" }}>Accès complet à tout</p>
                    </div>
                  </div>
                </div>
              </div>
              <hr style={{ border: 0, borderTop: "1px solid #27272a", margin: "5px 0" }} />
              <div style={{ display: "flex", gap: "15px", justifyContent: "flex-end" }}>
                <button type="button" onClick={() => setIsEditModalOpen(false)} style={{ backgroundColor: "transparent", border: "1px solid #3f3f46", color: "white", padding: "10px 20px", borderRadius: "8px", fontWeight: 600, fontSize: "0.85rem", cursor: "pointer" }}>Annuler</button>
                <button type="submit" disabled={isUpdating} style={{ backgroundColor: "var(--purple)", color: "white", padding: "10px 24px", borderRadius: "8px", fontWeight: 600, fontSize: "0.85rem", display: "flex", alignItems: "center", gap: "8px", cursor: "pointer" }}>
                  {isUpdating ? <span className="spinner" style={{ width: "12px", height: "12px", border: "2px solid white", borderTopColor: "transparent" }}></span> : <>Enregistrer</>}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* 3. Product Assignment Modal matching Image 2 perfectly */}
      {isProductModalOpen && (
        <div style={{
          position: "fixed", top: 0, left: 0, width: "100%", height: "100%",
          backgroundColor: "rgba(0,0,0,0.6)", backdropFilter: "blur(4px)",
          display: "flex", justifyContent: "center", alignItems: "center", zIndex: 100000,
          animation: "popupFadeIn 0.2s ease"
        }} onClick={() => setIsProductModalOpen(false)}>
          
          <div 
            style={{
              width: "500px", backgroundColor: "#18181b", borderRadius: "14px",
              border: "1px solid #27272a", boxShadow: "0 25px 50px -12px rgba(0, 0, 0, 0.5)",
              overflow: "hidden", animation: "popupFadeIn 0.15s ease-out"
            }}
            onClick={(e) => e.stopPropagation()}
          >
            
            {/* Modal Header */}
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", padding: "20px 24px" }}>
              <div style={{ display: "flex", gap: "15px" }}>
                <span style={{
                  display: "flex", alignItems: "center", justifyContent: "center",
                  width: "40px", height: "40px", borderRadius: "10px",
                  background: "rgba(137, 80, 252, 0.1)", color: "var(--purple)"
                }}>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </span>
                <div>
                  <h3 style={{ fontSize: "1.2rem", fontWeight: 700, color: "white" }}>Assigner des produits</h3>
                  <p style={{ fontSize: "0.8rem", color: "var(--text-muted)", marginTop: "2px" }}>{productMember?.name}</p>
                </div>
              </div>
              
              <button 
                onClick={() => setIsProductModalOpen(false)}
                style={{
                  width: "24px", height: "24px", borderRadius: "50%",
                  backgroundColor: "rgba(255,255,255,0.08)", color: "#a1a1aa",
                  display: "flex", alignItems: "center", justifyContent: "center",
                  cursor: "pointer"
                }}
              >
                &times;
              </button>
            </div>

            <hr style={{ border: 0, borderTop: "1px solid #27272a" }} />

            {/* Modal Content */}
            <div style={{ padding: "20px 24px", display: "flex", flexDirection: "column", gap: "15px" }}>
              
              {/* Product Search Bar */}
              <div style={{ position: "relative" }}>
                <input 
                  type="text"
                  placeholder="Rechercher des produits..."
                  value={productSearch}
                  onChange={(e) => setProductSearch(e.target.value)}
                  style={{
                    width: "100%",
                    padding: "12px 14px 12px 40px",
                    backgroundColor: "rgba(255,255,255,0.03)",
                    border: "1px solid #3f3f46",
                    borderRadius: "8px",
                    color: "white",
                    fontSize: "0.9rem",
                    outline: "none"
                  }}
                />
                <span style={{
                  position: "absolute",
                  left: "14px",
                  top: "50%",
                  transform: "translateY(-50%)",
                  color: "#a1a1aa"
                }}>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
              </div>

              {/* Stats & Actions */}
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", fontSize: "0.8rem" }}>
                <span style={{ color: "#a1a1aa", fontWeight: 500 }}>
                  {selectedProductIds.length} sélectionné(s)
                </span>
                
                <div style={{ display: "flex", gap: "15px" }}>
                  <button 
                    onClick={handleSelectAllProducts}
                    style={{ background: "none", border: 0, color: "var(--purple)", fontWeight: 600, cursor: "pointer" }}
                  >
                    Tout sélectionner
                  </button>
                  <button 
                    onClick={handleDeselectAllProducts}
                    style={{ background: "none", border: 0, color: "var(--text-muted)", fontWeight: 600, cursor: "pointer" }}
                  >
                    Tout désélectionner
                  </button>
                </div>
              </div>

              {/* Products List Scrollable area */}
              <div style={{
                maxHeight: "220px",
                overflowY: "auto",
                border: "1px solid #27272a",
                borderRadius: "8px",
                padding: "6px"
              }} className="custom-scrollbar">
                
                {getFilteredProducts().length === 0 ? (
                  <div style={{ padding: "40px 10px", textAlign: "center", color: "var(--text-muted)", fontSize: "0.85rem" }}>
                    Aucun produit trouvé
                  </div>
                ) : (
                  getFilteredProducts().map(prod => {
                    const isChecked = selectedProductIds.includes(prod.id);
                    return (
                      <div 
                        key={prod.id}
                        onClick={() => handleToggleProductSelection(prod.id)}
                        style={{
                          display: "flex",
                          alignItems: "center",
                          justifyContent: "space-between",
                          padding: "10px 12px",
                          borderRadius: "6px",
                          cursor: "pointer",
                          backgroundColor: isChecked ? "rgba(137, 80, 252, 0.05)" : "transparent",
                          transition: "background-color 0.15s",
                          marginBottom: "4px"
                        }}
                        className="product-item-hover"
                      >
                        <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
                          
                          {/* Checked box */}
                          <div style={{
                            width: "18px", height: "18px",
                            borderRadius: "4px",
                            border: `2px solid ${isChecked ? "var(--purple)" : "#3f3f46"}`,
                            backgroundColor: isChecked ? "var(--purple)" : "transparent",
                            display: "flex", alignItems: "center", justifyContent: "center",
                            transition: "all 0.15s"
                          }}>
                            {isChecked && (
                              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" strokeWidth="3"><polyline points="20 6 9 17 4 12"/></svg>
                            )}
                          </div>

                          <div style={{ display: "flex", flexDirection: "column" }}>
                            <span style={{ fontSize: "0.85rem", fontWeight: 600, color: "white" }}>
                              {prod.name}
                            </span>
                            <span style={{ fontSize: "0.75rem", color: "var(--text-muted)" }}>
                              SKU: {prod.sku || "N/A"}
                            </span>
                          </div>
                        </div>

                        <span style={{ fontSize: "0.85rem", fontWeight: 700, color: "white" }}>
                          {prod.price} $
                        </span>

                      </div>
                    );
                  })
                )}

              </div>

              <hr style={{ border: 0, borderTop: "1px solid #27272a", margin: "5px 0" }} />

              {/* Action Buttons */}
              <div style={{ display: "flex", gap: "15px", justifyContent: "flex-end" }}>
                <button 
                  type="button" 
                  onClick={() => setIsProductModalOpen(false)}
                  style={{
                    backgroundColor: "transparent",
                    border: "1px solid #3f3f46",
                    color: "white",
                    padding: "10px 20px",
                    borderRadius: "8px",
                    fontWeight: 600,
                    fontSize: "0.85rem",
                    cursor: "pointer"
                  }}
                >
                  Annuler
                </button>
                <button 
                  onClick={handleSaveProductAssignment}
                  disabled={isSavingProducts}
                  style={{
                    backgroundColor: "var(--purple)",
                    color: "white",
                    padding: "10px 24px",
                    borderRadius: "8px",
                    fontWeight: 600,
                    fontSize: "0.85rem",
                    display: "flex",
                    alignItems: "center",
                    gap: "8px",
                    cursor: "pointer"
                  }}
                >
                  {isSavingProducts ? (
                    <span className="spinner" style={{ width: "12px", height: "12px", border: "2px solid white", borderTopColor: "transparent" }}></span>
                  ) : (
                    <>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                      Enregistrer
                    </>
                  )}
                </button>
              </div>

            </div>

          </div>

        </div>
      )}

    </div>
  );
}
