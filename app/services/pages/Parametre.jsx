import React, { useState, useEffect } from 'react';
import { 
  User, 
  Lock, 
  Briefcase, 
  Info, 
  Phone, 
  Mail, 
  Camera, 
  Check, 
  X, 
  ShieldAlert, 
  ShieldCheck, 
  Globe, 
  Copy, 
  ArrowRight,
  TrendingUp,
  Coins,
  AlertCircle,
  Tag,
  Shield
} from 'lucide-react';
import { getCurrentUser, updateProfile, updatePassword, toggle2FA } from '../services/authService';
import { getTeamData, saveTeamSettings } from '../services/teamService';
import api from '../api/axios';

const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState('Profile');
  
  // Loading & App State
  const [loading, setLoading] = useState(true);
  const [user, setUser] = useState(null);
  const [team, setTeam] = useState(null);
  
  // Profile form states
  const [name, setName] = useState('');
  const [whatsapp, setWhatsapp] = useState('');
  const [email, setEmail] = useState('');
  const [avatar, setAvatar] = useState('');

  // Security states
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);

  // Business settings states
  const [prefix, setPrefix] = useState('FM');
  const [country, setCountry] = useState('MA');
  const [exchangeRate, setExchangeRate] = useState('10');

  // Notification Banner State
  const [alert, setAlert] = useState(null); // { type: 'success' | 'error', text: '' }

  const showAlert = (type, text) => {
    setAlert({ type, text });
    setTimeout(() => {
      setAlert(null);
    }, 5000);
  };

  const loadSettingsData = async () => {
    try {
      setLoading(true);
      const userRes = await getCurrentUser();
      if (userRes && userRes.user) {
        const u = userRes.user;
        setUser(u);
        setName(u.name || '');
        setWhatsapp(u.whatsapp || '');
        setEmail(u.email || '');
        setAvatar(u.avatar || '');
        setTwoFactorEnabled(!!u.two_factor_enabled);
      }

      const teamRes = await getTeamData();
      if (teamRes && teamRes.team) {
        const t = teamRes.team;
        setTeam(t);
        setPrefix(t.order_prefix || 'ORD');
        setCountry(t.country || 'MA');
        setExchangeRate(t.exchange_rate || '10');
      }
    } catch (err) {
      console.error(err);
      showAlert('error', 'Erreur lors du chargement des paramètres.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadSettingsData();
  }, []);

  const handleSaveProfile = async (e) => {
    e.preventDefault();
    try {
      const res = await updateProfile({ name, whatsapp, avatar });
      setUser(res.user);
      showAlert('success', 'Votre profil a été mis à jour avec succès.');
    } catch (err) {
      console.error(err);
      showAlert('error', err.response?.data?.message || 'Erreur lors de la mise à jour du profil.');
    }
  };

  const handleSetPassword = async (e) => {
    e.preventDefault();
    if (!password) {
      showAlert('error', 'Veuillez saisir un mot de passe.');
      return;
    }
    if (password !== confirmPassword) {
      showAlert('error', 'Les mots de passe ne correspondent pas.');
      return;
    }
    if (password.length < 8) {
      showAlert('error', 'Le mot de passe doit contenir au moins 8 caractères.');
      return;
    }
    try {
      await updatePassword({ password, password_confirmation: confirmPassword });
      setPassword('');
      setConfirmPassword('');
      showAlert('success', 'Votre mot de passe a été modifié avec succès.');
    } catch (err) {
      console.error(err);
      showAlert('error', err.response?.data?.message || 'Erreur lors de la modification du mot de passe.');
    }
  };

  const handleToggle2FA = async () => {
    try {
      const res = await toggle2FA();
      setTwoFactorEnabled(res.two_factor_enabled);
      showAlert('success', res.message);
    } catch (err) {
      console.error(err);
      showAlert('error', 'Erreur lors de la modification de la double authentification.');
    }
  };

  const handleSavePrefix = async () => {
    if (!prefix) {
      showAlert('error', 'Le préfixe ne peut pas être vide.');
      return;
    }
    try {
      const res = await saveTeamSettings({ order_prefix: prefix });
      setTeam(res.team);
      showAlert('success', 'Préfixe d\'identification des commandes enregistré.');
    } catch (err) {
      console.error(err);
      showAlert('error', 'Erreur lors de la sauvegarde du préfixe.');
    }
  };

  const handleSaveMarket = async () => {
    try {
      const res = await saveTeamSettings({
        country,
        exchange_rate: parseFloat(exchangeRate) || 0
      });
      setTeam(res.team);
      showAlert('success', 'Paramètres de marché local enregistrés.');
    } catch (err) {
      console.error(err);
      showAlert('error', 'Erreur lors de la sauvegarde du marché local.');
    }
  };

  const handleAvatarUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) {
      showAlert('error', 'L\'image dépasse la taille maximale autorisée de 2 Mo.');
      return;
    }

    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await api.post('/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      if (response.data && response.data.url) {
        setAvatar(response.data.url);
        showAlert('success', 'Nouvelle photo de profil importée. Pensez à enregistrer vos modifications.');
      }
    } catch (err) {
      console.error(err);
      showAlert('error', 'Échec de l\'envoi de la photo de profil.');
    }
  };

  const styles = {
    wrapper: {
      padding: '28px 32px',
      fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
      backgroundColor: '#f9fafb',
      minHeight: '100vh',
    },
    pageTitle: {
      fontSize: '22px',
      fontWeight: '700',
      color: '#111827',
      margin: '0 0 4px 0',
    },
    pageSubtitle: {
      fontSize: '13px',
      color: '#6b7280',
      margin: '0 0 24px 0',
    },
    layout: {
      display: 'flex',
      gap: '24px',
      alignItems: 'flex-start',
    },
    leftPanel: {
      width: '200px',
      minWidth: '200px',
      backgroundColor: '#ffffff',
      borderRadius: '12px',
      border: '1px solid #e5e7eb',
      padding: '16px',
      flexShrink: 0,
    },
    userProfile: {
      display: 'flex',
      alignItems: 'center',
      gap: '10px',
      marginBottom: '16px',
      paddingBottom: '16px',
      borderBottom: '1px solid #f3f4f6',
    },
    avatar: {
      width: '38px',
      height: '38px',
      borderRadius: '50%',
      backgroundColor: '#f97316',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      color: '#ffffff',
      fontSize: '16px',
      fontWeight: '700',
      flexShrink: 0,
      objectFit: 'cover',
    },
    userName: {
      fontSize: '13px',
      fontWeight: '600',
      color: '#111827',
      margin: 0,
      whiteSpace: 'nowrap',
      overflow: 'hidden',
      textOverflow: 'ellipsis',
      maxWidth: '120px',
    },
    userRole: {
      fontSize: '11px',
      color: '#9ca3af',
      margin: '2px 0 0 0',
      textTransform: 'capitalize',
    },
    navMenu: {
      listStyle: 'none',
      padding: 0,
      margin: 0,
    },
    navItem: { marginBottom: '2px' },
    navLink: (isActive) => ({
      display: 'flex',
      alignItems: 'center',
      gap: '10px',
      padding: '9px 12px',
      borderRadius: '8px',
      fontSize: '13px',
      fontWeight: isActive ? '600' : '500',
      color: isActive ? '#7c3aed' : '#374151',
      backgroundColor: isActive ? '#f5f3ff' : 'transparent',
      cursor: 'pointer',
      border: isActive ? '1.5px solid #ede9fe' : '1.5px solid transparent',
      transition: 'all 0.15s',
    }),
    contentArea: {
      flex: 1,
      minWidth: 0,
    },
    card: {
      backgroundColor: '#ffffff',
      borderRadius: '12px',
      border: '1px solid #e5e7eb',
      padding: '28px',
      marginBottom: '20px',
    },
    cardHeader: {
      display: 'flex',
      alignItems: 'flex-start',
      gap: '14px',
      marginBottom: '24px',
      paddingBottom: '20px',
      borderBottom: '1px solid #f3f4f6',
    },
    cardIconWrap: {
      width: '36px',
      height: '36px',
      borderRadius: '8px',
      backgroundColor: '#f5f3ff',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      flexShrink: 0,
    },
    cardTitle: {
      fontSize: '15px',
      fontWeight: '600',
      color: '#111827',
      margin: '0 0 3px 0',
    },
    cardSubtitle: {
      fontSize: '13px',
      color: '#6b7280',
      margin: 0,
    },
    profileImageSection: {
      display: 'flex',
      alignItems: 'center',
      gap: '20px',
      marginBottom: '28px',
    },
    profileImageContainer: { position: 'relative' },
    profileImage: {
      width: '72px',
      height: '72px',
      borderRadius: '14px',
      backgroundColor: '#f97316',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      color: '#ffffff',
      fontSize: '32px',
      fontWeight: '700',
      objectFit: 'cover',
    },
    editBadge: {
      position: 'absolute',
      top: '-6px',
      right: '-6px',
      width: '20px',
      height: '20px',
      backgroundColor: '#ef4444',
      borderRadius: '50%',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      color: '#ffffff',
      fontSize: '10px',
      cursor: 'pointer',
    },
    changeImageButton: {
      display: 'flex',
      alignItems: 'center',
      gap: '8px',
      padding: '8px 14px',
      backgroundColor: '#f9fafb',
      border: '1px solid #e5e7eb',
      borderRadius: '8px',
      fontSize: '13px',
      fontWeight: '500',
      color: '#374151',
      cursor: 'pointer',
      marginBottom: '6px',
    },
    imageSizeNote: {
      fontSize: '12px',
      color: '#9ca3af',
      margin: 0,
    },
    formGrid: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: '20px',
      marginBottom: '20px',
    },
    formLabel: {
      display: 'flex',
      alignItems: 'center',
      gap: '6px',
      fontSize: '13px',
      fontWeight: '500',
      color: '#374151',
      marginBottom: '8px',
    },
    formInput: {
      width: '100%',
      padding: '10px 14px',
      border: '1px solid #e5e7eb',
      borderRadius: '8px',
      fontSize: '14px',
      color: '#111827',
      backgroundColor: '#f9fafb',
      outline: 'none',
      boxSizing: 'border-box',
    },
    formGroup: { marginBottom: '20px' },
    badge: {
      display: 'inline-block',
      padding: '2px 8px',
      backgroundColor: '#f5f3ff',
      color: '#7c3aed',
      borderRadius: '4px',
      fontSize: '11px',
      fontWeight: '600',
    },
    inactiveBadge: {
      display: 'inline-block',
      padding: '2px 8px',
      backgroundColor: '#f3f4f6',
      color: '#6b7280',
      borderRadius: '4px',
      fontSize: '11px',
      fontWeight: '600',
    },
    emailNote: {
      fontSize: '12px',
      color: '#9ca3af',
      marginTop: '6px',
    },
    emailLink: {
      color: '#7c3aed',
      textDecoration: 'underline',
      cursor: 'pointer',
    },
    saveButton: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '8px',
      padding: '10px 22px',
      backgroundColor: '#111827',
      color: '#ffffff',
      border: 'none',
      borderRadius: '24px',
      fontSize: '14px',
      fontWeight: '600',
      cursor: 'pointer',
    },
    setPasswordButton: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '8px',
      padding: '10px 22px',
      backgroundColor: '#111827',
      color: '#ffffff',
      border: 'none',
      borderRadius: '8px',
      fontSize: '14px',
      fontWeight: '600',
      cursor: 'pointer',
      marginTop: '8px',
    },
    enable2FAButton: {
      display: 'inline-flex',
      alignItems: 'center',
      gap: '8px',
      padding: '10px 20px',
      backgroundColor: '#111827',
      color: '#ffffff',
      border: 'none',
      borderRadius: '8px',
      fontSize: '13px',
      fontWeight: '600',
      cursor: 'pointer',
      flexShrink: 0,
    },
    twoFARow: {
      display: 'flex',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: '16px',
    },
    warningBox: {
      display: 'flex',
      alignItems: 'flex-start',
      gap: '10px',
      marginTop: '20px',
      padding: '14px',
      backgroundColor: '#fffbeb',
      borderRadius: '8px',
      border: '1px solid #fde68a',
    },
    warningBoxGreen: {
      display: 'flex',
      alignItems: 'flex-start',
      gap: '10px',
      marginTop: '20px',
      padding: '14px',
      backgroundColor: '#ecfdf5',
      borderRadius: '8px',
      border: '1px solid #a7f3d0',
    },
    warningText: {
      fontSize: '13px',
      fontWeight: '600',
      color: '#92400e',
      margin: '0 0 4px 0',
    },
    warningTextGreen: {
      fontSize: '13px',
      fontWeight: '600',
      color: '#065f46',
      margin: '0 0 4px 0',
    },
    warningSubtext: {
      fontSize: '12px',
      color: '#b45309',
      margin: 0,
    },
    warningSubtextGreen: {
      fontSize: '12px',
      color: '#047857',
      margin: 0,
    },
    prefixRow: {
      display: 'flex',
      alignItems: 'center',
    },
    prefixLabel: {
      padding: '10px 14px',
      backgroundColor: '#f3f4f6',
      border: '1px solid #e5e7eb',
      borderRight: 'none',
      borderRadius: '8px 0 0 8px',
      fontSize: '12px',
      fontWeight: '700',
      color: '#6b7280',
      letterSpacing: '0.05em',
      whiteSpace: 'nowrap',
    },
    prefixInput: {
      padding: '10px 14px',
      border: '1px solid #e5e7eb',
      borderRight: 'none',
      fontSize: '14px',
      fontWeight: '700',
      color: '#111827',
      backgroundColor: '#ffffff',
      outline: 'none',
      width: '90px',
    },
    prefixArrow: {
      padding: '10px 10px',
      border: '1px solid #e5e7eb',
      borderRight: 'none',
      fontSize: '13px',
      color: '#6b7280',
      backgroundColor: '#f9fafb',
      display: 'flex',
      alignItems: 'center',
    },
    prefixPreview: {
      padding: '10px 14px',
      border: '1px solid #e5e7eb',
      borderRight: 'none',
      fontSize: '13px',
      fontWeight: '600',
      color: '#6b7280',
      backgroundColor: '#f9fafb',
    },
    prefixSaveBtn: {
      display: 'flex',
      alignItems: 'center',
      gap: '6px',
      padding: '10px 18px',
      backgroundColor: '#111827',
      color: '#ffffff',
      border: 'none',
      borderRadius: '0 8px 8px 0',
      fontSize: '13px',
      fontWeight: '600',
      cursor: 'pointer',
    },
    localMarketGrid: {
      display: 'grid',
      gridTemplateColumns: '1fr 1fr',
      gap: '24px',
    },
    sectionLabel: {
      fontSize: '11px',
      fontWeight: '700',
      color: '#6b7280',
      letterSpacing: '0.08em',
      textTransform: 'uppercase',
      marginBottom: '10px',
      display: 'flex',
      alignItems: 'center',
      gap: '6px',
    },
    selectInput: {
      width: '100%',
      padding: '10px 14px',
      border: '1px solid #e5e7eb',
      borderRadius: '8px',
      fontSize: '14px',
      color: '#111827',
      backgroundColor: '#ffffff',
      outline: 'none',
      cursor: 'pointer',
    },
    exchangeRow: {
      display: 'flex',
      alignItems: 'center',
    },
    exchangePrefix: {
      padding: '10px 14px',
      border: '1px solid #e5e7eb',
      borderRight: 'none',
      borderRadius: '8px 0 0 8px',
      fontSize: '13px',
      color: '#6b7280',
      backgroundColor: '#f9fafb',
      whiteSpace: 'nowrap',
    },
    exchangeInput: {
      padding: '10px 14px',
      border: '1px solid #e5e7eb',
      borderRight: 'none',
      fontSize: '14px',
      fontWeight: '700',
      color: '#111827',
      backgroundColor: '#ffffff',
      outline: 'none',
      width: '70px',
    },
    exchangeSuffix: {
      padding: '10px 14px',
      border: '1px solid #e5e7eb',
      borderRight: 'none',
      fontSize: '13px',
      color: '#6b7280',
      backgroundColor: '#f9fafb',
    },
    exchangeSaveBtn: {
      display: 'flex',
      alignItems: 'center',
      gap: '6px',
      padding: '10px 18px',
      backgroundColor: '#111827',
      color: '#ffffff',
      border: 'none',
      borderRadius: '0 8px 8px 0',
      fontSize: '13px',
      fontWeight: '600',
      cursor: 'pointer',
    },
    exchangeNote: {
      marginTop: '10px',
      padding: '10px 14px',
      backgroundColor: '#fffbeb',
      borderRadius: '8px',
      fontSize: '13px',
      fontWeight: '500',
      color: '#92400e',
      display: 'flex',
      alignItems: 'center',
      gap: '8px',
    },
    usageNote: {
      fontSize: '12px',
      color: '#9ca3af',
      marginTop: '8px',
    },
    accountRow: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: '18px 0',
      borderBottom: '1px solid #f3f4f6',
    },
    accountRowLast: {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: '18px 0',
    },
    accountRowLabel: {
      fontSize: '14px',
      fontWeight: '600',
      color: '#111827',
      margin: '0 0 3px 0',
    },
    accountRowSub: {
      fontSize: '12px',
      color: '#9ca3af',
      margin: 0,
    },
    accountIDBox: {
      display: 'flex',
      alignItems: 'center',
      gap: '8px',
    },
    accountIDValue: {
      padding: '6px 12px',
      backgroundColor: '#f3f4f6',
      borderRadius: '6px',
      fontSize: '12px',
      fontFamily: 'monospace',
      color: '#374151',
      maxWidth: '180px',
      overflow: 'hidden',
      textOverflow: 'ellipsis',
      whiteSpace: 'nowrap',
    },
    copyBtn: {
      padding: '5px 8px',
      border: '1px solid #e5e7eb',
      borderRadius: '6px',
      background: '#fff',
      cursor: 'pointer',
      fontSize: '13px',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
    },
    roleBadge: {
      padding: '4px 12px',
      backgroundColor: '#f5f3ff',
      color: '#7c3aed',
      borderRadius: '6px',
      fontSize: '13px',
      fontWeight: '600',
      textTransform: 'capitalize',
    },
    activeBadge: {
      padding: '4px 12px',
      backgroundColor: '#d1fae5',
      color: '#065f46',
      borderRadius: '6px',
      fontSize: '13px',
      fontWeight: '600',
    },
    alertBanner: (type) => ({
      padding: '12px 16px',
      borderRadius: '8px',
      fontSize: '13px',
      fontWeight: '500',
      marginBottom: '20px',
      display: 'flex',
      alignItems: 'center',
      gap: '8px',
      backgroundColor: type === 'success' ? '#ecfdf5' : '#fef2f2',
      color: type === 'success' ? '#047857' : '#b91c1c',
      border: type === 'success' ? '1px solid #a7f3d0' : '1px solid #fca5a5',
    }),
    loadingContainer: {
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      minHeight: '200px',
      fontSize: '16px',
      color: '#6b7280',
    }
  };

  const navItems = [
    { name: 'Profile', icon: <User size={16} /> },
    { name: 'Security', icon: <Lock size={16} /> },
    ...(user?.role === 'admin' ? [{ name: 'Business', icon: <Briefcase size={16} /> }] : []),
    { name: 'Account', icon: <Info size={16} /> },
  ];

  const handleCopyAccountId = () => {
    if (user?.id) {
      navigator.clipboard.writeText(user.id);
      showAlert('success', 'ID de compte copié dans le presse-papiers.');
    }
  };

  const renderProfile = () => (
    <form onSubmit={handleSaveProfile} style={styles.card}>
      <div style={styles.cardHeader}>
        <div style={{ ...styles.cardIconWrap, backgroundColor: '#f5f3ff' }}>
          <User size={18} color="#7c3aed" />
        </div>
        <div>
          <h2 style={styles.cardTitle}>Profile Information</h2>
          <p style={styles.cardSubtitle}>Update your account details</p>
        </div>
      </div>

      <div style={styles.profileImageSection}>
        <div style={styles.profileImageContainer}>
          {avatar ? (
            <img src={avatar} alt="Avatar" style={styles.profileImage} />
          ) : (
            <div style={styles.profileImage}>{name ? name[0].toUpperCase() : 'U'}</div>
          )}
          {avatar && (
            <div style={styles.editBadge} onClick={() => setAvatar('')} title="Supprimer l'image">
              <X size={10} />
            </div>
          )}
        </div>
        <div>
          <button 
            type="button" 
            style={styles.changeImageButton}
            onClick={() => document.getElementById('avatar-file-input').click()}
          >
            <Camera size={14} /> Change Image
          </button>
          <input
            id="avatar-file-input"
            type="file"
            accept="image/*"
            style={{ display: 'none' }}
            onChange={handleAvatarUpload}
          />
          <p style={styles.imageSizeNote}>JPG, PNG or GIF. Max size 2MB</p>
        </div>
      </div>

      <div style={styles.formGrid}>
        <div>
          <label style={styles.formLabel}>Full Name</label>
          <input 
            type="text" 
            style={styles.formInput} 
            value={name} 
            onChange={(e) => setName(e.target.value)} 
            required 
          />
        </div>
        <div>
          <label style={styles.formLabel}>
            <Phone size={14} style={{ marginRight: '4px' }} /> WhatsApp
          </label>
          <input 
            type="text" 
            style={styles.formInput} 
            value={whatsapp} 
            onChange={(e) => setWhatsapp(e.target.value)} 
          />
        </div>
      </div>

      <div style={styles.formGroup}>
        <label style={styles.formLabel}>
          Email Address <span style={styles.badge}>Primary</span>
        </label>
        <input type="email" style={{ ...styles.formInput, opacity: 0.7 }} value={email} readOnly />
        <p style={styles.emailNote}>
          To change your email, contact{' '}
          <a href="mailto:contact@flash-manager.com" style={styles.emailLink}>
            contact@flash-manager.com
          </a>
        </p>
      </div>

      <button type="submit" style={styles.saveButton}>
        <Check size={16} /> Save Changes
      </button>
    </form>
  );

  const renderSecurity = () => (
    <>
      <form onSubmit={handleSetPassword} style={styles.card}>
        <div style={styles.cardHeader}>
          <div style={{ ...styles.cardIconWrap, backgroundColor: '#fef3c7' }}>
            <Lock size={18} color="#d97706" />
          </div>
          <div>
            <h2 style={styles.cardTitle}>Set Password</h2>
            <p style={styles.cardSubtitle}>Set Password</p>
          </div>
        </div>

        <div style={styles.formGrid}>
          <div>
            <label style={styles.formLabel}>Set Password</label>
            <input
              type="password"
              style={styles.formInput}
              value={password}
              onChange={e => setPassword(e.target.value)}
            />
            <p style={styles.imageSizeNote}>Minimum 8 characters</p>
          </div>
          <div>
            <label style={styles.formLabel}>Confirm Password</label>
            <input
              type="password"
              style={styles.formInput}
              value={confirmPassword}
              onChange={e => setConfirmPassword(e.target.value)}
            />
          </div>
        </div>

        <button type="submit" style={styles.setPasswordButton}>
          <Lock size={14} /> Set Password
        </button>
      </form>

      <div style={styles.card}>
        <div style={styles.twoFARow}>
          <div style={{ display: 'flex', alignItems: 'flex-start', gap: '14px' }}>
            <div style={{ ...styles.cardIconWrap, backgroundColor: '#f5f3ff' }}>
              <ShieldCheck size={18} color="#7c3aed" />
            </div>
            <div>
              <h2 style={{ ...styles.cardTitle, display: 'flex', alignItems: 'center', gap: '8px' }}>
                Two-Factor Authentication{' '}
                {twoFactorEnabled ? (
                  <span style={styles.activeBadge}>Active</span>
                ) : (
                  <span style={styles.inactiveBadge}>Inactive</span>
                )}
              </h2>
              <p style={styles.cardSubtitle}>Protect your account with Google Authenticator</p>
            </div>
          </div>
          <button style={styles.enable2FAButton} onClick={handleToggle2FA}>
            <Shield size={14} /> {twoFactorEnabled ? 'Disable 2FA' : 'Enable 2FA'}
          </button>
        </div>

        {twoFactorEnabled ? (
          <div style={styles.warningBoxGreen}>
            <ShieldCheck size={20} color="#047857" style={{ marginTop: '2px', flexShrink: 0 }} />
            <div>
              <p style={styles.warningTextGreen}>Your account is protected by 2FA</p>
              <p style={styles.warningSubtextGreen}>
                Two-factor authentication is active. Your account is secured.
              </p>
            </div>
          </div>
        ) : (
          <div style={styles.warningBox}>
            <ShieldAlert size={20} color="#b45309" style={{ marginTop: '2px', flexShrink: 0 }} />
            <div>
              <p style={styles.warningText}>Your account is not protected by 2FA</p>
              <p style={styles.warningSubtext}>
                Enable two-factor authentication to add an extra layer of security using Google Authenticator.
              </p>
            </div>
          </div>
        )}
      </div>
    </>
  );

  const renderBusiness = () => (
    <>
      <div style={styles.card}>
        <div style={styles.cardHeader}>
          <div style={{ ...styles.cardIconWrap, backgroundColor: '#ede9fe' }}>
            <Tag size={18} color="#7c3aed" />
          </div>
          <div>
            <h2 style={styles.cardTitle}>Order Identification</h2>
            <p style={styles.cardSubtitle}>Prefix used for manually created orders</p>
          </div>
        </div>

        <div style={styles.prefixRow}>
          <span style={styles.prefixLabel}>PREFIX</span>
          <input
            type="text"
            style={styles.prefixInput}
            value={prefix}
            onChange={e => setPrefix(e.target.value)}
          />
          <span style={styles.prefixArrow}><ArrowRight size={14} /></span>
          <span style={styles.prefixPreview}>{prefix}1001</span>
          <button style={styles.prefixSaveBtn} onClick={handleSavePrefix}>
            <Check size={14} /> Save
          </button>
        </div>
      </div>

      <div style={styles.card}>
        <div style={styles.cardHeader}>
          <div style={{ ...styles.cardIconWrap, backgroundColor: '#e0f2fe' }}>
            <Globe size={18} color="#0284c7" />
          </div>
          <div>
            <h2 style={styles.cardTitle}>Local Market</h2>
            <p style={styles.cardSubtitle}>Your operating country and its local currency rate</p>
          </div>
        </div>

        <div style={styles.localMarketGrid}>
          <div>
            <div style={styles.sectionLabel}>
              <Globe size={12} color="#3b82f6" /> Country
            </div>
            <select style={styles.selectInput} value={country} onChange={(e) => setCountry(e.target.value)}>
              <option value="MA">Morocco (MA)</option>
              <option value="FR">France (FR)</option>
              <option value="US">United States (US)</option>
              <option value="TN">Tunisia (TN)</option>
              <option value="DZ">Algeria (DZ)</option>
            </select>
            <p style={styles.usageNote}>Used to filter relevant delivery carriers and set the default currency.</p>
          </div>
          <div>
            <div style={styles.sectionLabel}>
              <TrendingUp size={12} color="#f59e0b" /> Exchange Rate
            </div>
            <div style={styles.exchangeRow}>
              <span style={styles.exchangePrefix}>$1 =</span>
              <input
                type="text"
                style={styles.exchangeInput}
                value={exchangeRate}
                onChange={e => setExchangeRate(e.target.value)}
              />
              <span style={styles.exchangeSuffix}>
                {country === 'MA' ? 'MAD' : country === 'FR' ? 'EUR' : country === 'TN' ? 'TND' : country === 'DZ' ? 'DZD' : 'USD'}
              </span>
              <button style={styles.exchangeSaveBtn} onClick={handleSaveMarket}>
                <Check size={14} /> Save
              </button>
            </div>
            <div style={styles.exchangeNote}>
              <Coins size={14} color="#b45309" />
              <span>$10 = {(parseFloat(exchangeRate) || 0) * 10} {country === 'MA' ? 'MAD' : country === 'FR' ? 'EUR' : country === 'TN' ? 'TND' : country === 'DZ' ? 'DZD' : 'USD'}</span>
            </div>
          </div>
        </div>
      </div>
    </>
  );

  const renderAccount = () => {
    const creationDate = user?.created_at 
      ? new Date(user.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
      : 'May 13, 2026';

    return (
      <div style={styles.card}>
        <div style={styles.cardHeader}>
          <div style={{ ...styles.cardIconWrap, backgroundColor: '#ede9fe' }}>
            <Info size={18} color="#7c3aed" />
          </div>
          <div>
            <h2 style={styles.cardTitle}>Account Information</h2>
            <p style={styles.cardSubtitle}>Your staff account details</p>
          </div>
        </div>

        <div style={styles.accountRow}>
          <div>
            <p style={styles.accountRowLabel}>Account ID</p>
            <p style={styles.accountRowSub}>Your unique identifier</p>
          </div>
          <div style={styles.accountIDBox}>
            <span style={styles.accountIDValue} title={user?.id || ''}>{user?.id || 'N/A'}</span>
            <button style={styles.copyBtn} title="Copy" onClick={handleCopyAccountId}>
              <Copy size={12} />
            </button>
          </div>
        </div>

        <div style={styles.accountRow}>
          <div>
            <p style={styles.accountRowLabel}>Role</p>
            <p style={styles.accountRowSub}>Your access level</p>
          </div>
          <span style={styles.roleBadge}>{user?.role === 'admin' ? 'admin' : 'agent'}</span>
        </div>

        <div style={styles.accountRow}>
          <div>
            <p style={styles.accountRowLabel}>Account Status</p>
            <p style={styles.accountRowSub}>Current account state</p>
          </div>
          <span style={styles.activeBadge}>{user?.is_active ? 'Active' : 'Inactive'}</span>
        </div>

        <div style={styles.accountRowLast}>
          <div>
            <p style={styles.accountRowLabel}>Member Since</p>
            <p style={styles.accountRowSub}>Account creation date</p>
          </div>
          <span style={{ fontSize: '14px', color: '#374151', fontWeight: '500' }}>{creationDate}</span>
        </div>
      </div>
    );
  };

  const tabContent = { 
    Profile: renderProfile, 
    Security: renderSecurity, 
    Business: renderBusiness, 
    Account: renderAccount 
  };

  if (loading) {
    return (
      <div style={styles.wrapper}>
        <h1 style={styles.pageTitle}>Settings</h1>
        <p style={styles.pageSubtitle}>Manage your account and preferences</p>
        <div style={styles.loadingContainer}>
          <span>Chargement des paramètres...</span>
        </div>
      </div>
    );
  }

  return (
    <div style={styles.wrapper}>
      <h1 style={styles.pageTitle}>Settings</h1>
      <p style={styles.pageSubtitle}>Manage your account and preferences</p>

      {alert && (
        <div style={styles.alertBanner(alert.type)}>
          {alert.type === 'success' ? (
            <Check size={16} style={{ flexShrink: 0 }} />
          ) : (
            <AlertCircle size={16} style={{ flexShrink: 0 }} />
          )}
          <span>{alert.text}</span>
        </div>
      )}

      <div style={styles.layout}>
        {/* Left panel: user card + nav */}
        <div style={styles.leftPanel}>
          <div style={styles.userProfile}>
            {avatar ? (
              <img src={avatar} alt="Avatar" style={styles.avatar} />
            ) : (
              <div style={styles.avatar}>{name ? name[0].toUpperCase() : 'U'}</div>
            )}
            <div>
              <p style={styles.userName} title={name}>{name || 'N/A'}</p>
              <p style={styles.userRole}>{user?.role === 'admin' ? 'admin' : 'agent'}</p>
            </div>
          </div>

          <ul style={styles.navMenu}>
            {navItems.map((item) => (
              <li key={item.name} style={styles.navItem}>
                <div
                  style={styles.navLink(activeTab === item.name)}
                  onClick={() => setActiveTab(item.name)}
                >
                  <span style={{ display: 'flex', alignItems: 'center' }}>{item.icon}</span>
                  <span>{item.name}</span>
                </div>
              </li>
            ))}
          </ul>
        </div>

        {/* Right content area */}
        <div style={styles.contentArea}>
          {tabContent[activeTab]?.()}
        </div>
      </div>
    </div>
  );
};

export default SettingsPage;