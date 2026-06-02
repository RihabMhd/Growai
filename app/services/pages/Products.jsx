import React, { useState, useEffect, useRef } from 'react';
import {
  Package, Plus, Search, LayoutGrid, ArrowDownNarrowWide,
  CheckCircle, FileText, Boxes, RefreshCw, Lock
} from 'lucide-react';
import api from '../api/axios';

// ─── Shopify SVG ─────────────────────────────────────────────────────────────
const ShopifyIcon = ({ size = 16, color = '#fff' }) => (
  <svg width={size} height={size} viewBox="0 0 109.5 124.5" fill={color} xmlns="http://www.w3.org/2000/svg">
    <path d="M74.7,14.8c0,0-1.4,0.4-3.7,1.1c-0.4-1.3-1-2.8-1.8-4.4c-2.6-5-6.5-7.7-11.1-7.7c-0.3,0-0.6,0-1,0
      c-0.1-0.2-0.3-0.3-0.4-0.5C54.9,1.2,52.6,0,49.9,0C44.6,0.2,39.4,4,35.3,11c-2.9,4.6-5.1,10.3-5.7,14.8
      c-5.8,1.8-9.9,3.1-10,3.1c-2.9,0.9-3,1-3.4,3.7C16.2,34.7,5,124.5,5,124.5l81,0V14.1C83,14.3,78.7,14.8,74.7,14.8z
      M62.5,18.3c-3.6,1.1-7.5,2.3-11.4,3.5c1.1-4.2,3.2-8.4,5.7-11.2C57.7,9.4,59.4,8,61.4,7.1C63.4,11.3,62.6,16.4,62.5,18.3z
      M58.2,3.9c1.5,0,2.8,0.3,3.9,1.1C59.9,5.9,57.8,7,55.9,9.1c-2.7,2.9-4.8,7.5-5.9,11.9c-3.3,1-6.5,2-9.5,2.9
      C42.5,16.2,49.9,4.2,58.2,3.9z M46.6,55.8c0.4,6.3,17,7.7,18,21.9c0.7,11.3-6,19-15.7,19.6c-11.6,0.7-18-6.1-18-6.1
      l2.5-10.5c0,0,6.4,4.8,11.5,4.5c3.3-0.2,4.5-2.9,4.4-4.9c-0.5-8.2-14-7.7-14.9-20.7c-0.8-11.1,6.6-22.3,22.7-23.3
      c6.2-0.4,9.4,1.2,9.4,1.2l-3.6,13.8c0,0-4.1-1.9-9-1.6C47.5,49.9,46.5,53.2,46.6,55.8z" />
  </svg>
);

// ─── Pen SVG ─────────────────────────────────────────────────────────────────
const PenIcon = ({ size = 13, color = '#fff' }) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill="none"
    stroke={color} strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
    <path d="M12 20h9"/>
    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
  </svg>
);

// ─── Stat Card ────────────────────────────────────────────────────────────────
const StatCard = ({ icon: Icon, iconColor, label, value }) => (
  <div className="stat-card">
    <div className="stat-card-header">
      <Icon size={16} color={iconColor} />
      <span className="stat-card-label">{label}</span>
    </div>
    <div className="stat-card-value">{value}</div>
  </div>
);

// ─── Inline Product Form ──────────────────────────────────────────────────────
const defaultVariant = () => ({ title: 'Default Title', price: '', compare_at_price: '', sku: '', stock: '', cost: '' });

const ProductForm = ({ product, onSave, onCancel }) => {
  const isShopify = product?.source_type === 'shopify';
  const fileRef   = useRef();

  const initVariants = () => {
    if (product?.variants && Array.isArray(product.variants) && product.variants.length > 0) {
      return product.variants.map(v => ({
        title:            v.title            || 'Default Title',
        price:            v.price            != null ? String(v.price)            : '',
        compare_at_price: v.compare_at_price != null ? String(v.compare_at_price) : '',
        sku:              v.sku              || '',
        stock:            v.stock            != null ? String(v.stock)            : '',
        cost:             v.cost             != null ? String(v.cost)             : '',
      }));
    }
    return [defaultVariant()];
  };

  const [form, setForm] = useState({
    title:        product?.title        || '',
    vendor:       product?.vendor       || '',
    product_type: product?.product_type || '',
    handle:       product?.handle       || '',
    status:       product?.status       || 'active',
    tags:         Array.isArray(product?.tags) ? product.tags.join(', ') : (product?.tags || ''),
    description:  product?.description  || '',
    image:        product?.image        || '',
  });
  const [variants, setVariants]     = useState(initVariants);
  const [saving, setSaving]         = useState(false);
  const [imagePreview, setImagePreview] = useState(product?.image || '');

  const setField = (key, val) => setForm(f => ({ ...f, [key]: val }));

  const handleTitleChange = (val) => {
    setForm(f => ({
      ...f,
      title: val,
      handle: product ? f.handle : val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''),
    }));
  };

  const setVariantField = (idx, key, val) =>
    setVariants(vs => vs.map((v, i) => i === idx ? { ...v, [key]: val } : v));

  const addVariant = () => setVariants(vs => [...vs, defaultVariant()]);
  const removeVariant = (idx) => setVariants(vs => vs.filter((_, i) => i !== idx));

  const handleImageFile = (file) => {
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => { setImagePreview(e.target.result); setField('image', e.target.result); };
    reader.readAsDataURL(file);
  };

  const handleDrop = (e) => { e.preventDefault(); handleImageFile(e.dataTransfer.files[0]); };

  const handleSubmit = async () => {
    if (!form.title.trim()) return alert('Title is required');
    setSaving(true);
    try {
      const parsedVariants = variants.map(v => ({
        title:            v.title || 'Default Title',
        price:            parseFloat(v.price)            || 0,
        compare_at_price: v.compare_at_price ? parseFloat(v.compare_at_price) : null,
        sku:              v.sku || null,
        stock:            parseInt(v.stock)              || 0,
        cost:             v.cost ? parseFloat(v.cost)   : null,
      }));
      const payload = {
        ...form,
        tags:     form.tags ? form.tags.split(',').map(t => t.trim()).filter(Boolean) : [],
        variants: parsedVariants,
      };
      await onSave(payload);
    } finally {
      setSaving(false);
    }
  };

  const IS = { // input style
    width: '100%', padding: '9px 12px', fontSize: 13,
    border: '1px solid #e5e7eb', borderRadius: 8,
    background: '#fff', color: '#111827', outline: 'none',
    boxSizing: 'border-box', fontFamily: 'inherit', transition: 'border-color 0.15s',
  };
  const LS = { fontSize: 13, fontWeight: 500, color: '#374151', marginBottom: 5, display: 'block' }; // label
  const HS = { fontSize: 11, color: '#9ca3af', marginTop: 4 };                                       // hint
  const R2 = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14 };
  const R3 = { display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: 10 };
  const SH = { // section header
    fontSize: 11, fontWeight: 700, color: '#6b7280', textTransform: 'uppercase',
    letterSpacing: '0.07em', marginBottom: 12, paddingBottom: 8,
    borderBottom: '1px solid #f3f4f6',
  };
  const focusPurple = e => e.target.style.borderColor = '#7c3aed';
  const blurGray    = e => e.target.style.borderColor = '#e5e7eb';

  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 20 }}>

      {/* ── Shopify notice ── */}
      {isShopify && (
        <div style={{ background: '#eff6ff', border: '1px solid #bfdbfe', borderRadius: 8, padding: '10px 14px', display: 'flex', gap: 10, alignItems: 'flex-start' }}>
          <span style={{ color: '#3b82f6', flexShrink: 0, marginTop: 1 }}>ℹ️</span>
          <p style={{ margin: 0, fontSize: 12, color: '#1e40af', lineHeight: 1.5 }}>
            Edits here are stored locally in FlashManager and override Shopify's values for your team. The image is locked because Shopify is the source of truth for media.
          </p>
        </div>
      )}

      {/* ── Basic Information ── */}
      <div>
        <div style={SH}>Basic Information</div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>

          {/* Title */}
          <div>
            <label style={LS}>Title <span style={{ color: '#ef4444' }}>*</span></label>
            <input style={IS} placeholder="e.g., Premium black T-shirt"
              value={form.title} onChange={e => handleTitleChange(e.target.value)}
              onFocus={focusPurple} onBlur={blurGray} />
          </div>

          {/* Vendor + Product type */}
          <div style={R2}>
            <div>
              <label style={LS}>Vendor</label>
              <input style={IS} placeholder="e.g., Nike, Adidas"
                value={form.vendor} onChange={e => setField('vendor', e.target.value)}
                onFocus={focusPurple} onBlur={blurGray} />
              <span style={HS}>Brand or manufacturer name</span>
            </div>
            <div>
              <label style={LS}>Product type</label>
              <input style={IS} placeholder="e.g., T-Shirt, Shoes"
                value={form.product_type} onChange={e => setField('product_type', e.target.value)}
                onFocus={focusPurple} onBlur={blurGray} />
              <span style={HS}>Category or product family</span>
            </div>
          </div>

          {/* Handle (slug) */}
          <div>
            <label style={LS}>Handle (slug)</label>
            <input style={IS} placeholder="premium-black-tshirt"
              value={form.handle} onChange={e => setField('handle', e.target.value)}
              onFocus={focusPurple} onBlur={blurGray} />
            <span style={HS}>Unique URL-friendly identifier (auto-generated from title)</span>
          </div>

          {/* Status + Tags */}
          <div style={R2}>
            <div>
              <label style={LS}>Status</label>
              <select style={{ ...IS, cursor: 'pointer' }}
                value={form.status} onChange={e => setField('status', e.target.value)}>
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div>
              <label style={LS}>Tags</label>
              <input style={IS} placeholder="summer, men, cotton"
                value={form.tags} onChange={e => setField('tags', e.target.value)}
                onFocus={focusPurple} onBlur={blurGray} />
            </div>
          </div>

          {/* Description */}
          <div>
            <label style={LS}>Description</label>
            <textarea
              style={{ ...IS, minHeight: 80, resize: 'vertical', lineHeight: 1.5 }}
              placeholder="Describe your product..."
              value={form.description}
              onChange={e => setField('description', e.target.value)}
              onFocus={focusPurple} onBlur={blurGray}
            />
          </div>
        </div>
      </div>

      {/* ── Image ── */}
      <div>
        <div style={SH}>
          Image
          {isShopify && (
            <span style={{ marginLeft: 8, fontSize: 10, color: '#6b7280', fontWeight: 500, textTransform: 'none', letterSpacing: 0, display: 'inline-flex', alignItems: 'center', gap: 3 }}>
              <Lock size={10} /> Locked (Shopify image)
            </span>
          )}
        </div>

        {isShopify ? (
          imagePreview
            ? (
              <div style={{ position: 'relative', display: 'inline-block' }}>
                <img src={imagePreview} alt="product" style={{ width: 120, height: 120, objectFit: 'cover', borderRadius: 8, border: '1px solid #e5e7eb', display: 'block' }} />
                <div style={{ position: 'absolute', inset: 0, borderRadius: 8, background: 'rgba(0,0,0,0.38)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <Lock size={20} color="#fff" />
                </div>
              </div>
            ) : <div style={{ fontSize: 12, color: '#9ca3af' }}>No image from Shopify</div>
        ) : (
          imagePreview ? (
            <div style={{ display: 'flex', gap: 12, alignItems: 'flex-start' }}>
              <img src={imagePreview} alt="preview" style={{ width: 88, height: 88, objectFit: 'cover', borderRadius: 8, border: '1px solid #e5e7eb' }} />
              <button onClick={() => { setImagePreview(''); setField('image', ''); }}
                style={{ fontSize: 12, color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer', padding: '4px 0' }}>
                Remove
              </button>
            </div>
          ) : (
            <div onClick={() => fileRef.current?.click()} onDrop={handleDrop} onDragOver={e => e.preventDefault()}
              style={{ border: '2px dashed #e5e7eb', borderRadius: 10, padding: '28px 20px', textAlign: 'center', cursor: 'pointer', transition: 'border-color 0.15s' }}
              onMouseEnter={e => e.currentTarget.style.borderColor = '#7c3aed'}
              onMouseLeave={e => e.currentTarget.style.borderColor = '#e5e7eb'}>
              <div style={{ fontSize: 30, marginBottom: 8 }}>☁️</div>
              <div style={{ fontSize: 13, color: '#374151', fontWeight: 500 }}>Drop an image here or click to upload</div>
              <div style={{ fontSize: 11, color: '#9ca3af', marginTop: 4 }}>PNG · JPG · WEBP · GIF · AVIF — up to 10 MB</div>
              <input ref={fileRef} type="file" accept="image/*" style={{ display: 'none' }}
                onChange={e => handleImageFile(e.target.files[0])} />
            </div>
          )
        )}
      </div>

      {/* ── Pricing & Variants ── */}
      <div>
        <div style={{ ...SH, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <span>Pricing &amp; Variants</span>
          {!isShopify && (
            <button onClick={addVariant} style={{
              fontSize: 11, fontWeight: 600, color: '#7c3aed',
              background: '#f5f3ff', border: '1px solid #ddd6fe',
              borderRadius: 6, padding: '3px 10px', cursor: 'pointer',
              display: 'flex', alignItems: 'center', gap: 4,
            }}>
              <Plus size={11} /> Add variant
            </button>
          )}
        </div>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
          {variants.map((v, idx) => (
            <div key={idx} style={{ background: '#f9fafb', border: '1px solid #f3f4f6', borderRadius: 10, padding: '14px 16px' }}>
              {/* Variant header */}
              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <span style={{ fontSize: 12, fontWeight: 600, color: '#374151' }}>
                    {variants.length > 1 ? `Variant ${idx + 1}` : 'Default Variant'}
                  </span>
                  {variants.length > 1 && (
                    <input
                      style={{ ...IS, width: 160, padding: '5px 9px', fontSize: 12, background: '#fff' }}
                      placeholder="e.g., Red / Large"
                      value={v.title}
                      onChange={e => setVariantField(idx, 'title', e.target.value)}
                      onFocus={focusPurple} onBlur={blurGray}
                    />
                  )}
                </div>
                {variants.length > 1 && !isShopify && (
                  <button onClick={() => removeVariant(idx)}
                    style={{ fontSize: 11, color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer' }}>
                    Remove
                  </button>
                )}
              </div>

              {/* Price / Compare / SKU */}
              <div style={R3}>
                <div>
                  <label style={{ ...LS, fontSize: 12 }}>Price</label>
                  <div style={{ position: 'relative' }}>
                    <span style={{ position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', color: '#9ca3af', fontSize: 13 }}>$</span>
                    <input style={{ ...IS, paddingLeft: 22 }} type="number" min="0" step="0.01" placeholder="0.00"
                      value={v.price} onChange={e => setVariantField(idx, 'price', e.target.value)}
                      onFocus={focusPurple} onBlur={blurGray} />
                  </div>
                </div>
                <div>
                  <label style={{ ...LS, fontSize: 12 }}>Compare-at price</label>
                  <div style={{ position: 'relative' }}>
                    <span style={{ position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', color: '#9ca3af', fontSize: 13 }}>$</span>
                    <input style={{ ...IS, paddingLeft: 22 }} type="number" min="0" step="0.01" placeholder="0.00"
                      value={v.compare_at_price} onChange={e => setVariantField(idx, 'compare_at_price', e.target.value)}
                      onFocus={focusPurple} onBlur={blurGray} />
                  </div>
                </div>
                <div>
                  <label style={{ ...LS, fontSize: 12 }}>SKU</label>
                  <input style={IS} placeholder="SKU-001"
                    value={v.sku} onChange={e => setVariantField(idx, 'sku', e.target.value)}
                    onFocus={focusPurple} onBlur={blurGray} />
                </div>
              </div>

              {/* Stock / Cost */}
              <div style={{ ...R3, marginTop: 10 }}>
                <div>
                  <label style={{ ...LS, fontSize: 12 }}>Stock</label>
                  <input style={IS} type="number" min="0" placeholder="0"
                    value={v.stock} onChange={e => setVariantField(idx, 'stock', e.target.value)}
                    onFocus={focusPurple} onBlur={blurGray} />
                </div>
                <div>
                  <label style={{ ...LS, fontSize: 12 }}>Cost per item</label>
                  <div style={{ position: 'relative' }}>
                    <span style={{ position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', color: '#9ca3af', fontSize: 13 }}>$</span>
                    <input style={{ ...IS, paddingLeft: 22 }} type="number" min="0" step="0.01" placeholder="0.00"
                      value={v.cost} onChange={e => setVariantField(idx, 'cost', e.target.value)}
                      onFocus={focusPurple} onBlur={blurGray} />
                  </div>
                  <span style={HS}>Used for margin calculation</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'flex-end', paddingBottom: 2 }}>
                  {v.price && v.cost && parseFloat(v.price) > 0 ? (
                    <div style={{ fontSize: 12, color: '#6b7280' }}>
                      Margin:{' '}
                      <strong style={{ color: ((parseFloat(v.price) - parseFloat(v.cost)) / parseFloat(v.price) * 100) >= 30 ? '#16a34a' : '#f97316' }}>
                        {Math.round((parseFloat(v.price) - parseFloat(v.cost)) / parseFloat(v.price) * 100)}%
                      </strong>
                    </div>
                  ) : null}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* ── Actions ── */}
      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10, paddingTop: 6, borderTop: '1px solid #f3f4f6' }}>
        <button onClick={onCancel} style={{
          padding: '9px 20px', fontSize: 13, fontWeight: 500,
          background: 'none', border: '1px solid #e5e7eb', borderRadius: 8,
          color: '#374151', cursor: 'pointer',
        }}>Cancel</button>
        <button onClick={handleSubmit} disabled={saving} style={{
          padding: '9px 22px', fontSize: 13, fontWeight: 600,
          background: saving ? '#a78bfa' : '#7c3aed', color: '#fff',
          border: 'none', borderRadius: 8, cursor: saving ? 'not-allowed' : 'pointer',
          display: 'flex', alignItems: 'center', gap: 6, transition: 'background 0.15s',
        }}
          onMouseEnter={e => !saving && (e.currentTarget.style.background = '#6d28d9')}
          onMouseLeave={e => !saving && (e.currentTarget.style.background = '#7c3aed')}
        >
          {product
            ? <><PenIcon size={13} color="#fff" /> Save changes</>
            : <><Plus size={14} /> Create product</>
          }
        </button>
      </div>
    </div>
  );
};

// ─── Main Products page ───────────────────────────────────────────────────────
const Products = () => {
  const [products, setProducts]                 = useState([]);
  const [loading, setLoading]                   = useState(false);
  const [showProductModal, setShowProductModal] = useState(false);
  const [editingProduct, setEditingProduct]     = useState(null);
  const [pagination, setPagination]             = useState({ current_page: 1, last_page: 1, total: 0 });
  const [searchTerm, setSearchTerm]             = useState('');
  const [filterStatus, setFilterStatus]         = useState('');
  const [viewMode, setViewMode]                 = useState('grid');
  const [error, setError]                       = useState(null);
  const [shopConnected, setShopConnected]       = useState(false);
  const [syncing, setSyncing]                   = useState(false);

  // ─── Check shop connection ────────────────────────────────────────────────
  useEffect(() => {
    api.get('/shopify/status').then(({ data }) => {
      setShopConnected(!!data.connected);
    }).catch(() => setShopConnected(false));
  }, []);

  // ─── Fetch ────────────────────────────────────────────────────────────────
  const fetchProducts = async (page = 1) => {
    setLoading(true); setError(null);
    try {
      const params = {
        page, per_page: 15,
        ...(searchTerm   && { search: searchTerm }),
        ...(filterStatus && { status: filterStatus }),
      };
      const { data } = await api.get('/products', { params });
      setProducts(data.data);
      setPagination({ current_page: data.current_page, last_page: data.last_page, total: data.total });
    } catch (err) {
      setError('Failed to load products.');
    } finally {
      setLoading(false);
    }
  };

  // ─── Resync ───────────────────────────────────────────────────────────────
  const handleResync = async () => {
    setSyncing(true);
    try {
      await api.post('/shopify/sync-products');
      await fetchProducts(1);
    } catch { /* silent */ } finally {
      setSyncing(false);
    }
  };

  // ─── Save ─────────────────────────────────────────────────────────────────
  const saveProduct = async (productData) => {
    if (editingProduct) {
      await api.put(`/products/${editingProduct.id}`, productData);
    } else {
      await api.post('/products', productData);
    }
    setEditingProduct(null);
    await fetchProducts(pagination.current_page);
    setShowProductModal(false);
  };

  // ─── Delete ───────────────────────────────────────────────────────────────
  const deleteProduct = async (id) => {
    if (!window.confirm('Delete this product?')) return;
    try {
      await api.delete(`/products/${id}`);
      fetchProducts(pagination.current_page);
    } catch { alert('Failed to delete product'); }
  };

  useEffect(() => { fetchProducts(); }, []);
  useEffect(() => {
    const t = setTimeout(() => fetchProducts(1), 500);
    return () => clearTimeout(t);
  }, [searchTerm, filterStatus]);

  const activeCount    = products.filter(p => p.status === 'active').length;
  const draftCount     = products.filter(p => p.status === 'draft').length;
  const totalInventory = products.reduce((acc, p) => acc + (p.total_stock || 0), 0);

  const now      = new Date();
  const syncTime = `${String(now.getDate()).padStart(2,'0')}/${String(now.getMonth()+1).padStart(2,'0')}/${now.getFullYear()} ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;

  const openCreate = () => { setEditingProduct(null); setShowProductModal(true); };
  const openEdit   = (p) => { setEditingProduct(p);   setShowProductModal(true); };
  const closeModal = () => { setShowProductModal(false); setEditingProduct(null); };

  const isShopifyEdit = editingProduct?.source_type === 'shopify';

  return (
    <div className="products-page">
      <style>{`
        /* ─── Cards ─── */
        .products-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(188px, 1fr));
          gap: 14px;
        }
        .pcv2 {
          background: #fff;
          border: 1px solid #e5e7eb;
          border-radius: 14px;
          overflow: hidden;
          cursor: pointer;
          transition: box-shadow 0.18s, transform 0.18s;
        }
        .pcv2:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.10); transform: translateY(-2px); }
        .pcv2-img-wrap {
          position: relative; width: 100%; aspect-ratio: 1/1;
          background: #f3f4f6; overflow: hidden;
        }
        .pcv2-img { width:100%; height:100%; object-fit:cover; display:block; }
        .pcv2-img-ph {
          width:100%; height:100%;
          display:flex; align-items:center; justify-content:center; background:#f9fafb;
        }
        .pcv2-src {
          position:absolute; top:9px; left:9px;
          width:26px; height:26px; border-radius:8px;
          display:flex; align-items:center; justify-content:center; z-index:2;
        }
        .pcv2-src-shopify { background:#95bf47; }
        .pcv2-src-manual  { background:#7c3aed; }
        .pcv2-badge {
          position:absolute; top:9px; right:9px;
          font-size:10px; font-weight:700; letter-spacing:0.04em;
          padding:3px 9px; border-radius:20px; z-index:2;
        }
        .pcv2-badge-active   { background:#dcfce7; color:#166534; }
        .pcv2-badge-draft    { background:#fef3c7; color:#92400e; }
        .pcv2-badge-archived { background:#f3f4f6; color:#6b7280; }
        .pcv2-overlay {
          position:absolute; inset:0;
          display:flex; align-items:flex-end; justify-content:flex-end;
          padding:10px; opacity:0; transition:opacity 0.18s; z-index:3;
        }
        .pcv2:hover .pcv2-overlay { opacity:1; }
        .pcv2-edit-btn {
          width:30px; height:30px; border-radius:50%;
          background:rgba(255,255,255,0.92); border:1px solid #e5e7eb;
          display:flex; align-items:center; justify-content:center;
          cursor:pointer; color:#374151;
          box-shadow:0 2px 8px rgba(0,0,0,0.12); transition:background 0.15s;
        }
        .pcv2-edit-btn:hover { background:#fff; }
        .pcv2-info { padding:10px 12px 12px; }
        .pcv2-vendor { font-size:11px; color:#6b7280; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .pcv2-title  { font-size:13px; font-weight:700; color:#111827; margin-bottom:6px; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .pcv2-price-row { display:flex; align-items:baseline; gap:6px; flex-wrap:wrap; margin-bottom:4px; }
        .pcv2-price   { font-size:14px; font-weight:700; color:#111827; }
        .pcv2-compare { font-size:12px; color:#9ca3af; text-decoration:line-through; }
        .pcv2-stock   { font-size:11px; color:#16a34a; font-weight:600; margin-left:auto; }
        .pcv2-cost-row { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
        .pcv2-cost-lbl { font-size:11px; color:#9ca3af; }
        .pcv2-cost-val { color:#f97316; font-weight:600; }
        .pcv2-margin   { font-size:11px; font-weight:700; margin-left:auto; padding:2px 6px; border-radius:4px; }
        .pcv2-mg-pos   { color:#16a34a; background:#dcfce7; }
        .pcv2-mg-mid   { color:#92400e; background:#fef3c7; }
        .pcv2-mg-neg   { color:#991b1b; background:#fee2e2; }

        /* ─── Modal ─── */
        .pf-overlay {
          position:fixed; inset:0; background:rgba(0,0,0,0.45);
          display:flex; align-items:center; justify-content:center;
          z-index:1000; padding:16px;
        }
        .pf-modal {
          background:#fff; border-radius:18px;
          width:100%; max-width:560px;
          max-height:92vh; display:flex; flex-direction:column;
          box-shadow:0 20px 60px rgba(0,0,0,0.20);
          overflow:hidden;
        }
        .pf-header {
          display:flex; align-items:center; justify-content:space-between;
          padding:16px 20px 14px;
          border-bottom:1px solid #f3f4f6; flex-shrink:0;
        }
        .pf-header-left { display:flex; align-items:center; gap:10px; }
        .pf-icon {
          width:28px; height:28px; border-radius:50%;
          background:#7c3aed;
          display:flex; align-items:center; justify-content:center; color:#fff;
        }
        .pf-title { font-size:15px; font-weight:700; color:#111827; }
        .pf-source-chip {
          display:inline-flex; align-items:center; gap:5px;
          font-size:11px; font-weight:600;
          padding:3px 9px; border-radius:20px;
        }
        .pf-source-shopify { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
        .pf-source-manual  { background:#f5f3ff; color:#5b21b6; border:1px solid #ddd6fe; }
        .pf-close {
          width:28px; height:28px; border-radius:50%;
          border:1px solid #e5e7eb; background:#f9fafb;
          font-size:18px; line-height:1;
          display:flex; align-items:center; justify-content:center;
          cursor:pointer; color:#6b7280; transition:background 0.15s;
        }
        .pf-close:hover { background:#f3f4f6; }
        .pf-body { overflow-y:auto; padding:20px; flex:1; }
      `}</style>

      {/* ── Header ─────────────────────────────── */}
      <div className="products-header">
        <div>
          <div className="products-title-wrapper">
            <div className="products-title-icon"><Package size={18} /></div>
            <h1 className="products-title">Products</h1>
          </div>
          <p className="products-subtitle">
            Shopify product catalog
            <span className="products-sync-info">· Last sync {syncTime}</span>
          </p>
        </div>
        <div className="products-button-group">
          <button onClick={openCreate} className="btn-primary-action">
            <Plus size={14} /> New Product
          </button>
          {shopConnected ? (
            <button
              onClick={handleResync}
              disabled={syncing}
              className="btn-secondary-action"
              style={{ display:'flex', alignItems:'center', gap:6 }}
            >
              <RefreshCw size={14} style={{ animation: syncing ? 'spin 1s linear infinite' : 'none' }} />
              {syncing ? 'Syncing…' : 'Re-sync All'}
            </button>
          ) : (
            <button className="btn-secondary-action">
              <span style={{ fontWeight:700 }}>$</span> Connect your Shop
            </button>
          )}
        </div>
      </div>

      {/* ── Error ──────────────────────────────── */}
      {error && (
        <div className="error-banner">
          {error} <button onClick={() => fetchProducts()}>Retry</button>
        </div>
      )}

      {/* ── Stats ──────────────────────────────── */}
      {products.length > 0 && (
        <div className="stats-grid">
          <StatCard icon={Package}     iconColor="#60a5fa" label="TOTAL PRODUCTS"  value={pagination.total} />
          <StatCard icon={CheckCircle} iconColor="#4ade80" label="ACTIVE"          value={activeCount} />
          <StatCard icon={FileText}    iconColor="#fb923c" label="DRAFT"           value={draftCount} />
          <StatCard icon={Boxes}       iconColor="#a78bfa" label="TOTAL INVENTORY" value={totalInventory} />
        </div>
      )}

      {/* ── Search & Filter ────────────────────── */}
      {products.length > 0 && (
        <div className="search-filter-bar">
          <div className="search-input-wrapper">
            <Search size={14} className="search-icon" />
            <input
              type="text" placeholder="Search products..."
              value={searchTerm} onChange={e => setSearchTerm(e.target.value)}
              className="search-input"
            />
          </div>
          <select value={filterStatus} onChange={e => setFilterStatus(e.target.value)} className="filter-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="archived">Archived</option>
          </select>
          <button className={`filter-button ${viewMode === 'grid'  ? 'active' : ''}`} onClick={() => setViewMode('grid')}>
            <LayoutGrid size={15} />
          </button>
          <button className={`filter-button ${viewMode === 'table' ? 'active' : ''}`} onClick={() => setViewMode('table')}>
            <ArrowDownNarrowWide size={15} />
          </button>
        </div>
      )}

      {/* ── Content ────────────────────────────── */}
      {loading ? (
        <div className="loading-container"><div className="spinner-dark" /></div>
      ) : products.length === 0 ? (
        <div className="empty-state-container">
          <div className="empty-state-icon"><Package size={32} /></div>
          <h3 className="empty-state-title">Your catalog is empty</h3>
          <p className="empty-state-text">Create products manually or connect Shopify to import yours.</p>
          <div className="empty-state-actions">
            <button onClick={openCreate} className="btn-primary-action"><Plus size={14} /> New Product</button>
            {shopConnected
              ? <button onClick={handleResync} className="btn-secondary-action"><RefreshCw size={14} /> Re-sync All</button>
              : <button className="btn-secondary-action"><span>$</span> Connect your Shop</button>
            }
          </div>
        </div>
      ) : viewMode === 'grid' ? (
        <GridView products={products} onEdit={openEdit} onDelete={deleteProduct} />
      ) : (
        <TableView products={products} onEdit={openEdit} onDelete={deleteProduct} pagination={pagination} />
      )}

      {/* ── Pagination ─────────────────────────── */}
      {!loading && pagination.last_page > 1 && (
        <div className="pagination-bar">
          <button className="pagination-btn" disabled={pagination.current_page === 1}
            onClick={() => fetchProducts(pagination.current_page - 1)}>Previous</button>
          <span className="pagination-info">Page {pagination.current_page} of {pagination.last_page}</span>
          <button className="pagination-btn" disabled={pagination.current_page === pagination.last_page}
            onClick={() => fetchProducts(pagination.current_page + 1)}>Next</button>
        </div>
      )}

      {/* ── Modal ──────────────────────────────── */}
      {showProductModal && (
        <div className="pf-overlay" onClick={closeModal}>
          <div className="pf-modal" onClick={e => e.stopPropagation()}>

            {/* Header */}
            <div className="pf-header">
              <div className="pf-header-left">
                <span className="pf-icon">
                  {editingProduct
                    ? <PenIcon size={13} color="#fff" />
                    : <Plus size={13} />
                  }
                </span>
                <span className="pf-title">
                  {editingProduct ? 'Edit product' : 'New product'}
                </span>
                {editingProduct && (
                  <span className={`pf-source-chip ${isShopifyEdit ? 'pf-source-shopify' : 'pf-source-manual'}`}>
                    {isShopifyEdit
                      ? <><ShopifyIcon size={11} color="#166534" /> Shopify</>
                      : <><PenIcon size={11} color="#5b21b6" /> Manual</>
                    }
                  </span>
                )}
              </div>
              <button className="pf-close" onClick={closeModal}>×</button>
            </div>

            {/* Body */}
            <div className="pf-body">
              <ProductForm
                product={editingProduct}
                onSave={saveProduct}
                onCancel={closeModal}
              />
            </div>
          </div>
        </div>
      )}

      <style>{`@keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }`}</style>
    </div>
  );
};

// ─── Grid View ────────────────────────────────────────────────────────────────
const GridView = ({ products, onEdit, onDelete }) => (
  <div className="products-grid">
    {products.map(product => {
      const isShopify = product.source_type === 'shopify';
      const price     = product.min_price || product.variants?.[0]?.price || 0;
      const compareAt = product.variants?.[0]?.compare_at_price;
      const cost      = product.cost || 0;
      const margin    = price > 0 && cost > 0 ? Math.round(((price - cost) / price) * 100) : null;
      const stock     = product.total_stock || 0;
      const status    = product.status || 'draft';
      return (
        <div key={product.id} className="pcv2" onClick={() => onEdit(product)}>
          <div className="pcv2-img-wrap">
            {product.image
              ? <img src={product.image} alt={product.title} className="pcv2-img" />
              : <div className="pcv2-img-ph"><Package size={32} color="#d1d5db" /></div>
            }
            <div className={`pcv2-src ${isShopify ? 'pcv2-src-shopify' : 'pcv2-src-manual'}`}>
              {isShopify
                ? <ShopifyIcon size={13} color="#fff" />
                : <PenIcon size={13} color="#fff" />
              }
            </div>
            <span className={`pcv2-badge pcv2-badge-${status}`}>{status.toUpperCase()}</span>
            <div className="pcv2-overlay">
              <button className="pcv2-edit-btn" onClick={e => { e.stopPropagation(); onEdit(product); }}>
                <PenIcon size={13} color="#374151" />
              </button>
            </div>
          </div>
          <div className="pcv2-info">
            <div className="pcv2-vendor">{product.vendor || product.product_type || ''}</div>
            <div className="pcv2-title">{product.title}</div>
            <div className="pcv2-price-row">
              <span className="pcv2-price">${Number(price).toFixed(2)}</span>
              {compareAt && Number(compareAt) > Number(price) && (
                <span className="pcv2-compare">${Number(compareAt).toFixed(2)}</span>
              )}
              {stock > 0 && <span className="pcv2-stock">{stock} in stock</span>}
            </div>
            {cost > 0 && (
              <div className="pcv2-cost-row">
                <span className="pcv2-cost-lbl">Cost: <span className="pcv2-cost-val">${Number(cost).toFixed(2)}</span></span>
                {margin !== null && (
                  <span className={`pcv2-margin ${margin < 0 ? 'pcv2-mg-neg' : margin >= 30 ? 'pcv2-mg-pos' : 'pcv2-mg-mid'}`}>
                    {margin}% margin
                  </span>
                )}
              </div>
            )}
          </div>
        </div>
      );
    })}
  </div>
);

// ─── Table View ───────────────────────────────────────────────────────────────
const TableView = ({ products, onEdit, onDelete, pagination }) => (
  <div className="card">
    <div style={{ overflowX: 'auto' }}>
      <table className="products-table">
        <thead>
          <tr>{['PRODUCT','SOURCE','STATUS','TYPE','VENDOR','PRICE','COST','MARGIN','ACTIONS'].map(c => <th key={c}>{c}</th>)}</tr>
        </thead>
        <tbody>
          {products.map(product => {
            const price  = product.min_price || product.variants?.[0]?.price || 0;
            const cost   = product.cost || 0;
            const margin = price > 0 && cost > 0 ? Math.round(((price - cost) / price) * 100) : 0;
            return (
              <tr key={product.id}>
                <td>
                  <div className="product-cell">
                    {product.image
                      ? <img src={product.image} alt={product.title} className="product-image" />
                      : <div className="product-image-placeholder"><Package size={18} /></div>
                    }
                    <span className="product-name">{product.title}</span>
                  </div>
                </td>
                <td><span className="cell-text">{product.source_type || 'Manual'}</span></td>
                <td><span className={`status-badge status-${product.status || 'draft'}`}>{(product.status || 'draft').toUpperCase()}</span></td>
                <td className="cell-text">{product.product_type || '-'}</td>
                <td className="cell-text">{product.vendor || '-'}</td>
                <td className="cell-text-strong">${Number(price).toFixed(2)}</td>
                <td className="cell-text-warning">${Number(cost).toFixed(2)}</td>
                <td className={margin >= 30 ? 'cell-text-success' : 'cell-text-strong'}>{margin}%</td>
                <td>
                  <div className="table-actions">
                    <button onClick={() => onEdit(product)} className="btn-edit">Edit</button>
                    <button onClick={() => onDelete(product.id)} className="btn-delete">Delete</button>
                  </div>
                </td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
    <div className="table-footer">Showing {products.length} of {pagination.total} products</div>
  </div>
);

export default Products;