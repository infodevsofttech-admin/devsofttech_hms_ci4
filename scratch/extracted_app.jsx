
const { useState, useEffect, useRef } = React;

const API_BASE = '/api/v1/medical-store/';

// Detect store slug from URL (e.g. /MedicalStore/storeA or /app/medical-store/storeB)
function getInitialStoreSlug() {
    if (window.INITIAL_STORE_SLUG) {
        return window.INITIAL_STORE_SLUG;
    }
    const path = window.location.pathname;
    const match = path.match(/(?:MedicalStore|app\/medical-store)\/([a-zA-Z0-9_\-]+)/i);
    return match ? match[1] : null;
}

// =========================================================================
// ROOT ERROR BOUNDARY
// =========================================================================
class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }
    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }
    componentDidCatch(error, errorInfo) {
        console.error("Medical Store UI Error:", error, errorInfo);
    }
    render() {
        if (this.state.hasError) {
            return (
                <div className="d-flex flex-column align-items-center justify-content-center vh-100 bg-slate-900 text-white p-4 text-center" style={{ background: '#0f172a' }}>
                    <div className="bg-danger text-white rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style={{ width: 64, height: 64 }}>
                        <i className="bi bi-exclamation-octagon fs-2"></i>
                    </div>
                    <h4 className="fw-bold">Counter Desk Interruption</h4>
                    <p className="text-secondary mb-3" style={{ maxWidth: 500 }}>
                        {this.state.error?.message || 'An unexpected error occurred while rendering the counter desk.'}
                    </p>
                    <button className="btn btn-outline-light btn-sm" onClick={() => window.location.reload()}>
                        <i className="bi bi-arrow-clockwise me-1"></i> Reload Counter Desk
                    </button>
                </div>
            );
        }
        return this.props.children;
    }
}

function App() {
    const [stores, setStores] = useState([]);
    const [activeStoreId, setActiveStoreId] = useState(null);
    const [activeTab, setActiveTab] = useState('pos'); // pos, inventory, purchase, transfer, ledgers, settings
    const [notification, setNotification] = useState(null);
    const [isLoadingStores, setIsLoadingStores] = useState(true);

    // Machine Authorization State
    const [isDeviceAuthorized, setIsDeviceAuthorized] = useState(false);
    const [deviceToken, setDeviceToken] = useState('');
    const [machineName, setMachineName] = useState('Counter PC ' + (navigator.platform || 'Desktop'));
    const [secKeyInput, setSecKeyInput] = useState('');
    const [verifyMethod, setVerifyMethod] = useState('key'); // 'key' or 'otp'
    const [isVerifying, setIsVerifying] = useState(false);

    const storeSlugFromUrl = getInitialStoreSlug();

    // Active Store Object
    const activeStore = stores.find(s => s.store_id == activeStoreId) || stores[0] || null;

    useEffect(() => {
        fetchStores();
    }, []);

    useEffect(() => {
        if (activeStore) {
            checkMachineAuthorization(activeStore.store_id);
        }
    }, [activeStoreId, stores]);

    const fetchStores = async () => {
        setIsLoadingStores(true);
        try {
            const url = storeSlugFromUrl ? `${API_BASE}stores?slug=${encodeURIComponent(storeSlugFromUrl)}` : `${API_BASE}stores`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.status && data.stores && data.stores.length > 0) {
                setStores(data.stores);
                // If slug matched specific store, select it (case-insensitive)
                if (storeSlugFromUrl) {
                    const matched = data.stores.find(s => 
                        (s.store_slug && s.store_slug.toLowerCase() === storeSlugFromUrl.toLowerCase()) || 
                        (s.store_code && s.store_code.toLowerCase() === storeSlugFromUrl.toLowerCase())
                    );
                    if (matched) {
                        setActiveStoreId(matched.store_id);
                        return;
                    }
                }
                if (!activeStoreId) {
                    setActiveStoreId(data.stores[0].store_id);
                }
            }
        } catch (e) {
            console.error('Failed to load stores:', e);
        } finally {
            setIsLoadingStores(false);
        }
    };

    const checkMachineAuthorization = async (storeId) => {
        const savedToken = localStorage.getItem('mst_device_token_' + storeId);
        if (!savedToken) {
            setIsDeviceAuthorized(false);
            return;
        }

        try {
            const res = await fetch(API_BASE + 'device/check', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ store_id: storeId, device_token: savedToken })
            });
            const data = await res.json();
            if (data.status && data.authorized) {
                setIsDeviceAuthorized(true);
                setDeviceToken(savedToken);
                if (data.machine_name) setMachineName(data.machine_name);
            } else {
                localStorage.removeItem('mst_device_token_' + storeId);
                setIsDeviceAuthorized(false);
            }
        } catch (e) {
            console.error('Device authorization check error:', e);
        }
    };

    const submitDeviceVerification = async (e) => {
        e.preventDefault();
        if (!activeStore || !secKeyInput) return;

        setIsVerifying(true);
        try {
            const payload = {
                store_id: activeStore.store_id,
                store_slug: activeStore.store_slug,
                machine_name: machineName,
                security_key: verifyMethod === 'key' ? secKeyInput : '',
                otp: verifyMethod === 'otp' ? secKeyInput : ''
            };

            const res = await fetch(API_BASE + 'device/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status && data.device_token) {
                localStorage.setItem('mst_device_token_' + activeStore.store_id, data.device_token);
                setDeviceToken(data.device_token);
                setIsDeviceAuthorized(true);
                notify(data.message, 'success');
            } else {
                alert(data.message || 'Verification failed. Please check with HMS Admin.');
            }
        } catch (e) {
            alert('Verification request failed: ' + e.message);
        } finally {
            setIsVerifying(false);
        }
    };

    const notify = (msg, type = 'success') => {
        setNotification({ msg, type });
        setTimeout(() => setNotification(null), 4000);
    };

    if (isLoadingStores && !activeStore) {
        return (
            <div className="d-flex flex-column align-items-center justify-content-center vh-100 bg-slate-900 text-white" style={{ background: '#0f172a' }}>
                <div className="spinner-border text-info mb-3" style={{ width: '3rem', height: '3rem' }} role="status">
                    <span className="visually-hidden">Loading Store Counter...</span>
                </div>
                <h5 className="fw-bold tracking-wide">HMS Multi-Building Medical Store</h5>
                <p className="text-secondary small">Synchronizing pharmacy counters, terminal licenses & ABDM...</p>
            </div>
        );
    }

    if (!activeStore) {
        return (
            <div className="d-flex flex-column align-items-center justify-content-center vh-100 bg-slate-900 text-white p-4 text-center" style={{ background: '#0f172a' }}>
                <div className="bg-danger text-white rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center" style={{ width: 64, height: 64 }}>
                    <i className="bi bi-exclamation-triangle fs-2"></i>
                </div>
                <h4 className="fw-bold">Medical Store Not Found</h4>
                <p className="text-secondary mb-3" style={{ maxWidth: 480 }}>
                    Could not locate any active pharmacy store counter for <code>/{storeSlugFromUrl || 'default'}</code>.
                    Please verify store configuration in HMS Admin.
                </p>
                <div className="d-flex gap-2">
                    <a href="/MedicalStore/storeA" className="btn btn-outline-light btn-sm">
                        <i className="bi bi-shop me-1"></i> Try Main Store (storeA)
                    </a>
                    <a href="/app/admin/medical-stores" className="btn btn-primary-custom btn-sm">
                        <i className="bi bi-gear me-1"></i> Open HMS Store Settings
                    </a>
                </div>
            </div>
        );
    }

    return (
        <div>
            {/* Machine Lock Screen Modal if PC is not verified */}
            {!isDeviceAuthorized && activeStore && (
                <div className="machine-lock-backdrop">
                    <div className="card shadow-lg border-0" style={{ maxWidth: 460, width: '100%', borderRadius: 16 }}>
                        <div className="card-header bg-dark text-white p-3 text-center border-0" style={{ borderTopLeftRadius: 16, borderTopRightRadius: 16 }}>
                            <i className="bi bi-shield-lock-fill text-warning fs-1 d-block mb-1"></i>
                            <h5 className="fw-bold mb-0">Terminal Machine Verification</h5>
                            <small className="text-slate-300" style={{ color: '#94a3b8' }}>Counter Authorization Required</small>
                        </div>
                        <div className="card-body p-4">
                            <div className="text-center mb-3">
                                <h6 className="fw-bold text-dark">{activeStore.store_name}</h6>
                                <small className="text-muted d-block">
                                    <i className="bi bi-geo-alt me-1"></i>{activeStore.building_name || 'Main Hospital'} • {activeStore.floor_no || 'Ground Floor'}
                                </small>
                                <span className="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mt-1">
                                    Link: MedicalStore/{activeStore.store_slug}
                                </span>
                            </div>

                            <p className="small text-muted text-center mb-3">
                                This computer terminal is accessing the counter for the first time. Please enter the <strong>Security Key</strong> or <strong>6-digit OTP</strong> provided by HMS Admin.
                            </p>

                            <form onSubmit={submitDeviceVerification}>
                                <div className="mb-3">
                                    <label className="form-label small fw-bold text-dark mb-1">Computer / Machine Name</label>
                                    <input 
                                        type="text" 
                                        className="form-control form-control-sm" 
                                        value={machineName} 
                                        onChange={(e) => setMachineName(e.target.value)}
                                        required 
                                    />
                                </div>

                                <div className="mb-2">
                                    <div className="btn-group btn-group-sm w-100 mb-2">
                                        <button 
                                            type="button" 
                                            className={`btn ${verifyMethod === 'key' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setVerifyMethod('key')}
                                        >
                                            <i className="bi bi-key-fill me-1"></i> Admin Security Key
                                        </button>
                                        <button 
                                            type="button" 
                                            className={`btn ${verifyMethod === 'otp' ? 'btn-primary' : 'btn-outline-secondary'}`}
                                            onClick={() => setVerifyMethod('otp')}
                                        >
                                            <i className="bi bi-123 me-1"></i> 6-Digit Counter OTP
                                        </button>
                                    </div>

                                    <input 
                                        type={verifyMethod === 'otp' ? 'number' : 'text'}
                                        className="form-control text-center fw-bold fs-5 letter-spacing-1" 
                                        placeholder={verifyMethod === 'key' ? 'e.g. HMS-XXXXXX-XXX' : 'Enter 6-digit OTP'}
                                        value={secKeyInput}
                                        onChange={(e) => setSecKeyInput(e.target.value)}
                                        required 
                                    />
                                </div>

                                <button 
                                    type="submit" 
                                    className="btn btn-success-custom w-100 py-2.5 mt-3 fw-bold"
                                    disabled={isVerifying}
                                >
                                    {isVerifying ? (
                                        <span><i className="bi bi-hourglass-split me-1"></i> Verifying Terminal...</span>
                                    ) : (
                                        <span><i className="bi bi-shield-check me-1"></i> Verify Machine &amp; Unlock Counter</span>
                                    )}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            {/* Top Store Header */}
            <header className="navbar-store">
                <div className="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div className="d-flex align-items-center gap-3">
                        <div className="bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center" style={{ width: 40, height: 40 }}>
                            <i className="bi bi-capsule fs-5"></i>
                        </div>
                        <div>
                            <div className="d-flex align-items-center gap-2">
                                <h6 className="mb-0 fw-bold text-white fs-5">{activeStore ? activeStore.store_name : 'Medical Store Counter'}</h6>
                                {activeStore && (
                                    <span className="badge bg-info-subtle text-info border border-info px-2 py-0.5 rounded-pill" style={{ fontSize: '0.65rem' }}>
                                        /{activeStore.store_slug || activeStore.store_code}
                                    </span>
                                )}
                            </div>
                            <small className="text-slate-400" style={{ color: '#94a3b8' }}>
                                <i className="bi bi-geo-alt me-1"></i>
                                {activeStore ? `${activeStore.building_name || 'Main Building'} • ${activeStore.floor_no || 'Ground Floor'}` : 'Counter'}
                            </small>
                        </div>
                    </div>

                    {/* Active Store Statutory License Badges */}
                    {activeStore && (
                        <div className="d-none d-md-flex align-items-center gap-2">
                            {activeStore.abdm_hfr_id && (
                                <span className="badge bg-success-subtle text-success border border-success px-2 py-1" title="ABDM Health Facility Registry">
                                    <i className="bi bi-shield-fill-check me-1"></i> HFR: {activeStore.abdm_hfr_id}
                                </span>
                            )}
                            <span className="badge-license" title="Retail Allopathic Drug License">
                                DL 20B: {activeStore.drug_license_no_20b || 'N/A'}
                            </span>
                            <span className="badge-license" title="Schedule C/C1 Drug License">
                                DL 21B: {activeStore.drug_license_no_21b || 'N/A'}
                            </span>
                            <span className="badge-gst" title="GSTIN">
                                GSTIN: {activeStore.gstin || 'N/A'}
                            </span>
                            {isDeviceAuthorized && (
                                <span className="badge bg-success text-white rounded-pill px-2.5 py-1" style={{ fontSize: '0.7rem' }}>
                                    <i className="bi bi-shield-check me-1"></i> Terminal Verified
                                </span>
                            )}
                        </div>
                    )}

                    {/* Store Selector & Exit */}
                    <div className="d-flex align-items-center gap-2">
                        <select 
                            className="form-select form-select-sm bg-dark text-white border-secondary" 
                            style={{ minWidth: 200 }}
                            value={activeStoreId || ''} 
                            onChange={(e) => setActiveStoreId(Number(e.target.value))}
                        >
                            {stores.map(s => (
                                <option key={s.store_id} value={s.store_id}>
                                    {s.store_name} ({s.store_slug})
                                </option>
                            ))}
                        </select>
                        <a href="/app" className="btn btn-sm btn-outline-light" title="Return to App Hub">
                            <i className="bi bi-grid-fill"></i>
                        </a>
                    </div>
                </div>
            </header>

            {/* Notification Alert Banner */}
            {notification && (
                <div className={`alert alert-${notification.type === 'error' ? 'danger' : 'success'} alert-dismissible fade show m-2 p-2 px-3`} role="alert">
                    <i className={`bi bi-${notification.type === 'error' ? 'exclamation-triangle' : 'check-circle'}-fill me-2`}></i>
                    {notification.msg}
                    <button type="button" className="btn-close p-2" onClick={() => setNotification(null)}></button>
                </div>
            )}

            {/* Main Navigation Tabs */}
            <nav className="nav-tabs-custom d-flex flex-wrap">
                <button className={`nav-link ${activeTab === 'pos' ? 'active' : ''}`} onClick={() => setActiveTab('pos')}>
                    <i className="bi bi-cart3 me-1"></i> POS Counter Desk
                </button>
                <button className={`nav-link ${activeTab === 'inventory' ? 'active' : ''}`} onClick={() => setActiveTab('inventory')}>
                    <i className="bi bi-boxes me-1"></i> Live Stock &amp; Batches
                </button>
                <button className={`nav-link ${activeTab === 'purchase' ? 'active' : ''}`} onClick={() => setActiveTab('purchase')}>
                    <i className="bi bi-box-arrow-in-down me-1"></i> Purchases &amp; Marg Import
                </button>
                <button className={`nav-link ${activeTab === 'transfer' ? 'active' : ''}`} onClick={() => setActiveTab('transfer')}>
                    <i className="bi bi-arrow-left-right me-1"></i> Inter-Store Indents
                </button>
                <button className={`nav-link ${activeTab === 'ledgers' ? 'active' : ''}`} onClick={() => setActiveTab('ledgers')}>
                    <i className="bi bi-journal-text me-1"></i> Ledgers, Daybook &amp; GST
                </button>
                <button className={`nav-link ${activeTab === 'settings' ? 'active' : ''}`} onClick={() => setActiveTab('settings')}>
                    <i className="bi bi-gear me-1"></i> Store &amp; License Setup
                </button>
            </nav>

            {/* Main View Container */}
            <main className="container-fluid py-3">
                {activeTab === 'pos' && <PosCounter store={activeStore} notify={notify} />}
                {activeTab === 'inventory' && <InventoryView store={activeStore} notify={notify} />}
                {activeTab === 'purchase' && <PurchaseView store={activeStore} notify={notify} />}
                {activeTab === 'transfer' && <TransferView store={activeStore} stores={stores} notify={notify} />}
                {activeTab === 'ledgers' && <LedgersView store={activeStore} notify={notify} />}
                {activeTab === 'settings' && <SettingsView store={activeStore} onStoreSaved={fetchStores} notify={notify} />}
            </main>
        </div>
    );
}

// =========================================================================
// 1. POS BILLING & DISPENSING COUNTER (MULTI-PRINT: A4, A5, 80mm THERMAL)
// =========================================================================
function PosCounter({ store, notify }) {
    if (!store) {
        return (
            <div className="text-center py-5 text-muted">
                <div className="spinner-border spinner-border-sm text-primary me-2"></div>
                Loading counter desk...
            </div>
        );
    }

    const [patientQuery, setPatientQuery] = useState('');
    const [patientResults, setPatientResults] = useState([]);
    const [selectedPatient, setSelectedPatient] = useState(null);
    const [patientType, setPatientType] = useState('Walk-in'); // Walk-in, OPD, IPD

    const [walkinAbhaId, setWalkinAbhaId] = useState('');
    const [walkinAbhaAddress, setWalkinAbhaAddress] = useState('');
    const [showFhirModal, setShowFhirModal] = useState(false);
    const [fhirBundleData, setFhirBundleData] = useState(null);
    const [isLoadingFhir, setIsLoadingFhir] = useState(false);
    const [copiedFhir, setCopiedFhir] = useState(false);

    const [doctorName, setDoctorName] = useState('');
    const [doctorRegNo, setDoctorRegNo] = useState('');

    const [itemQuery, setItemQuery] = useState('');
    const [itemResults, setItemResults] = useState([]);
    const [isSearchingItems, setIsSearchingItems] = useState(false);

    const [cart, setCart] = useState([]);
    const [wholeDiscountType, setWholeDiscountType] = useState('pct'); // 'pct' or 'val'
    const [wholeDiscountVal, setWholeDiscountVal] = useState(0);
    const [paymentMode, setPaymentMode] = useState('Cash'); // Cash, UPI, Card, Mixed, IPD_Credit
    const [paymentRef, setPaymentRef] = useState('');

    const [lastInvoice, setLastInvoice] = useState(null);
    const [printFormat, setPrintFormat] = useState('thermal'); // 'thermal', 'a5', 'a4'
    const [isProcessing, setIsProcessing] = useState(false);

    // Search patient
    const searchPatients = async (val) => {
        setPatientQuery(val);
        if (val.length < 2) {
            setPatientResults([]);
            return;
        }
        try {
            const res = await fetch(API_BASE + `patient/search?q=${encodeURIComponent(val)}`);
            const data = await res.json();
            if (data.status) {
                setPatientResults(data.patients || []);
            }
        } catch (e) {
            console.error(e);
        }
    };

    const selectPatient = (pt) => {
        setSelectedPatient(pt);
        setPatientResults([]);
        setPatientQuery(pt.full_name + ' (' + pt.uhid + ')');
        setWalkinAbhaId(pt.abha_id || '');
        setWalkinAbhaAddress(pt.abha_address || '');

        if (pt.has_active_ipd && pt.active_ipd) {
            setPatientType('IPD');
            setDoctorName(pt.active_ipd.doctor_name || '');
            setPrintFormat('a4'); // Default IPD bills to A4
        } else if (pt.has_active_opd && pt.active_opd) {
            setPatientType('OPD');
            setDoctorName(pt.active_opd.doctor_name || '');
            setPrintFormat('a5'); // Default OPD bills to A5
        } else {
            setPatientType('Walk-in');
            setPrintFormat('thermal'); // Default walkin to Thermal
        }
    };

    const resetPatient = () => {
        setSelectedPatient(null);
        setPatientQuery('');
        setPatientType('Walk-in');
        setDoctorName('');
        setDoctorRegNo('');
        setWalkinAbhaId('');
        setWalkinAbhaAddress('');
        setPrintFormat('thermal');
    };

    const viewAbdmBundle = async (saleId) => {
        setIsLoadingFhir(true);
        setShowFhirModal(true);
        setCopiedFhir(false);
        try {
            const res = await fetch(API_BASE + `abdm/bundle/${saleId}`);
            const data = await res.json();
            if (data.status) {
                setFhirBundleData(data);
            } else {
                notify(data.message || 'Unable to retrieve ABDM FHIR bundle', 'error');
                setShowFhirModal(false);
            }
        } catch (e) {
            notify('FHIR Fetch error: ' + e.message, 'error');
            setShowFhirModal(false);
        } finally {
            setIsLoadingFhir(false);
        }
    };

    const copyFhirToClipboard = () => {
        if (!fhirBundleData?.bundle) return;
        navigator.clipboard.writeText(JSON.stringify(fhirBundleData.bundle, null, 2));
        setCopiedFhir(true);
        setTimeout(() => setCopiedFhir(false), 2500);
    };

    // 1-Click Load Doctor Rx
    const loadDoctorPrescription = async () => {
        if (!selectedPatient) return;
        const encType = selectedPatient.has_active_ipd ? 'ipd' : (selectedPatient.has_active_opd ? 'opd' : null);
        const encId = selectedPatient.has_active_ipd ? selectedPatient.active_ipd.ipd_id : (selectedPatient.has_active_opd ? selectedPatient.active_opd.opd_id : null);

        if (!encType || !encId) {
            notify('No active OPD or IPD prescription found for this patient.', 'error');
            return;
        }

        try {
            const res = await fetch(API_BASE + `patient/prescription/${encType}/${encId}`);
            const data = await res.json();
            if (data.status && data.medicines && data.medicines.length > 0) {
                let addedCount = 0;
                for (const med of data.medicines) {
                    const sRes = await fetch(API_BASE + `items/search?store_id=${store.store_id}&q=${encodeURIComponent(med.medicine_name)}`);
                    const sData = await sRes.json();
                    if (sData.status && sData.items && sData.items.length > 0) {
                        const matchedItem = sData.items[0];
                        if (matchedItem.fefo_batch) {
                            addItemToCart(matchedItem, matchedItem.fefo_batch, med.prescribed_qty || 10);
                            addedCount++;
                        }
                    }
                }
                notify(`Imported ${addedCount} prescribed medicines into cart!`, 'success');
            } else {
                notify('No electronic medicines found for this visit.', 'error');
            }
        } catch (e) {
            notify('Failed to load prescription: ' + e.message, 'error');
        }
    };

    // Search Items in Store
    const searchItems = async (val) => {
        setItemQuery(val);
        if (val.length < 2) {
            setItemResults([]);
            return;
        }
        setIsSearchingItems(true);
        try {
            const res = await fetch(API_BASE + `items/search?store_id=${store.store_id}&q=${encodeURIComponent(val)}`);
            const data = await res.json();
            if (data.status) {
                setItemResults(data.items || []);
            }
        } catch (e) {
            console.error(e);
        } finally {
            setIsSearchingItems(false);
        }
    };

    const addItemToCart = (item, batch, qty = 1) => {
        if (!batch) {
            notify('No active batch available for ' + item.item_name, 'error');
            return;
        }
        if (batch.is_expired) {
            notify(`STATUTORY BLOCK: Batch ${batch.batch_no} is expired! Cannot be sold.`, 'error');
            return;
        }

        const existingIdx = cart.findIndex(c => c.item_id === item.item_id && c.batch_id === batch.batch_id);
        if (existingIdx >= 0) {
            const updated = [...cart];
            updated[existingIdx].qty += qty;
            setCart(updated);
        } else {
            setCart([...cart, {
                item_id: item.item_id,
                item_name: item.item_name,
                generic_name: item.generic_name,
                unit_pack: item.unit_pack,
                drug_schedule: item.drug_schedule,
                hsn_code: item.hsn_code || '3004',
                gst_rate: batch.gst_rate || 12.0,
                batch_id: batch.batch_id,
                batch_no: batch.batch_no,
                expiry_display: batch.expiry_display,
                expiry_date: batch.expiry_date,
                current_qty: batch.current_qty,
                unit_mrp: batch.mrp,
                discount_type: 'pct',
                discount_val: 0,
                qty: qty
            }]);
        }
        setItemResults([]);
        setItemQuery('');

        // Auto-suggest format based on item count
        if (cart.length + 1 > 5 && printFormat === 'thermal') {
            setPrintFormat('a5');
        }
    };

    const getItemDiscount = (it) => {
        const lineGross = (Number(it.qty) || 0) * (Number(it.unit_mrp) || 0);
        const val = Number(it.discount_val) || 0;
        if (val <= 0) return 0;
        if (it.discount_type === 'val') {
            return Math.min(lineGross, val);
        } else {
            const pct = Math.min(100, Math.max(0, val));
            return (lineGross * pct) / 100;
        }
    };

    const toggleItemDiscType = (idx) => {
        const updated = [...cart];
        const it = updated[idx];
        const lineGross = (Number(it.qty) || 0) * (Number(it.unit_mrp) || 0);
        const currentDisc = getItemDiscount(it);
        if (it.discount_type === 'val') {
            it.discount_type = 'pct';
            it.discount_val = lineGross > 0 ? Number(((currentDisc / lineGross) * 100).toFixed(1)) : 0;
        } else {
            it.discount_type = 'val';
            it.discount_val = Math.round(currentDisc);
        }
        setCart(updated);
    };

    const updateCartItem = (idx, field, val) => {
        const updated = [...cart];
        if (field === 'discount_type') {
            updated[idx][field] = val;
        } else {
            updated[idx][field] = Number(val);
        }
        setCart(updated);
    };

    const removeCartItem = (idx) => {
        setCart(cart.filter((_, i) => i !== idx));
    };

    // Calculate totals with whole bill discount
    const grossTotal = cart.reduce((acc, it) => acc + ((Number(it.qty) || 0) * (Number(it.unit_mrp) || 0)), 0);
    const itemDiscountTotal = cart.reduce((acc, it) => acc + getItemDiscount(it), 0);
    const balanceAfterItemDisc = Math.max(0, grossTotal - itemDiscountTotal);

    let wholeDiscountAmt = 0;
    const numWholeDisc = Number(wholeDiscountVal) || 0;
    if (numWholeDisc > 0) {
        if (wholeDiscountType === 'val') {
            wholeDiscountAmt = Math.min(balanceAfterItemDisc, numWholeDisc);
        } else {
            const pct = Math.min(100, Math.max(0, numWholeDisc));
            wholeDiscountAmt = (balanceAfterItemDisc * pct) / 100;
        }
    }

    const discountTotal = itemDiscountTotal + wholeDiscountAmt;
    const netBeforeRound = Math.max(0, grossTotal - discountTotal);
    const netPayable = Math.round(netBeforeRound);
    const roundOff = (netPayable - netBeforeRound).toFixed(2);

    // Save and print sale
    const processSale = async () => {
        if (cart.length === 0) {
            notify('Cart is empty!', 'error');
            return;
        }

        if (paymentMode === 'IPD_Credit' && (!selectedPatient || !selectedPatient.has_active_ipd)) {
            notify('IPD Credit mode is only allowed for admitted IPD patients.', 'error');
            return;
        }

        setIsProcessing(true);
        const payload = {
            store_id: store.store_id,
            patient_type: patientType,
            uhid: selectedPatient ? selectedPatient.uhid : null,
            patient_id: selectedPatient ? selectedPatient.patient_id : null,
            opd_id: selectedPatient && selectedPatient.active_opd ? selectedPatient.active_opd.opd_id : null,
            ipd_id: selectedPatient && selectedPatient.active_ipd ? selectedPatient.active_ipd.ipd_id : null,
            patient_name: selectedPatient ? selectedPatient.full_name : (patientQuery || 'Walk-in Customer'),
            patient_mobile: selectedPatient ? selectedPatient.mobile : '',
            patient_address: selectedPatient ? selectedPatient.address : '',
            abha_id: selectedPatient?.abha_id || walkinAbhaId || null,
            abha_address: selectedPatient?.abha_address || walkinAbhaAddress || null,
            age: selectedPatient ? selectedPatient.age : '',
            gender: selectedPatient ? selectedPatient.gender : '',
            doctor_name: doctorName || (selectedPatient?.active_opd?.doctor_name || selectedPatient?.active_ipd?.doctor_name || 'Dr. Consultant'),
            doctor_reg_no: doctorRegNo,
            payment_mode: paymentMode,
            payment_reference: paymentRef,
            whole_discount_type: wholeDiscountType,
            whole_discount_val: Number(wholeDiscountVal) || 0,
            items: cart.map(it => {
                const lineGross = (Number(it.qty) || 0) * (Number(it.unit_mrp) || 0);
                const lineDisc = getItemDiscount(it);
                const discPct = lineGross > 0 ? Number(((lineDisc / lineGross) * 100).toFixed(2)) : 0;
                return {
                    item_id: it.item_id,
                    batch_id: it.batch_id,
                    qty: Number(it.qty) || 1,
                    discount_type: it.discount_type || 'pct',
                    discount_val: Number(it.discount_val) || 0,
                    discount_pct: discPct,
                    discount_amount: lineDisc
                };
            })
        };

        try {
            const res = await fetch(API_BASE + 'sales/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status) {
                notify(`Invoice ${data.invoice_no} generated successfully! Amount: ₹${data.net_amount}`, 'success');
                fetchInvoiceDetails(data.sale_id);
                setCart([]);
                setWholeDiscountVal(0);
                resetPatient();
            } else {
                notify(data.message || 'Failed to complete sale.', 'error');
            }
        } catch (e) {
            notify('Sale error: ' + e.message, 'error');
        } finally {
            setIsProcessing(false);
        }
    };

    const fetchInvoiceDetails = async (saleId) => {
        try {
            const res = await fetch(API_BASE + `sales/invoice/${saleId}`);
            const data = await res.json();
            if (data.status) {
                setLastInvoice(data);
            }
        } catch (e) {
            console.error(e);
        }
    };

    return (
        <div className="row g-3">
            {/* Left Column: Patient Selector & Cart Grid */}
            <div className="col-lg-8">
                {/* Patient Information Panel */}
                <div className="counter-card position-relative">
                    <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div className="d-flex align-items-center gap-2">
                            <span className="fw-bold text-dark fs-6"><i className="bi bi-person-fill text-primary me-1"></i> Patient &amp; Prescription</span>
                            <span className={`badge ${patientType === 'IPD' ? 'bg-danger' : (patientType === 'OPD' ? 'bg-primary' : 'bg-secondary')}`}>
                                {patientType}
                            </span>
                        </div>
                        {selectedPatient && (
                            <div className="d-flex gap-2">
                                {(selectedPatient.has_active_opd || selectedPatient.has_active_ipd) && (
                                    <button className="btn btn-sm btn-outline-success py-1 px-2.5" onClick={loadDoctorPrescription}>
                                        <i className="bi bi-file-earmark-medical me-1"></i> 1-Click Load Doctor Rx
                                    </button>
                                )}
                                <button className="btn btn-sm btn-outline-secondary py-1 px-2" onClick={resetPatient}>
                                    <i className="bi bi-x-circle me-1"></i> Clear
                                </button>
                            </div>
                        )}
                    </div>

                    <div className="row g-2">
                        <div className="col-md-7 position-relative">
                            <div className="input-group input-group-sm">
                                <span className="input-group-text bg-light"><i className="bi bi-search"></i></span>
                                <input 
                                    type="text" 
                                    className="form-control" 
                                    placeholder="Search by UHID, Mobile, Name, OPD No, IPD No..." 
                                    value={patientQuery}
                                    onChange={(e) => searchPatients(e.target.value)}
                                />
                            </div>

                            {/* Patient Search Results Dropdown */}
                            {patientResults.length > 0 && (
                                <div className="search-results-dropdown">
                                    {patientResults.map(p => (
                                        <div key={p.patient_id} className="search-item-row d-flex justify-content-between align-items-center" onClick={() => selectPatient(p)}>
                                            <div>
                                                <div className="fw-bold text-dark">
                                                    {p.full_name} <small className="text-primary">({p.uhid})</small>
                                                    {(p.abha_id || p.abha_address) && (
                                                        <span className="badge bg-success-subtle text-success border border-success ms-2" style={{ fontSize: '0.65rem' }}>
                                                            <i className="bi bi-shield-fill-check me-1"></i>ABHA: {p.abha_id || p.abha_address}
                                                        </span>
                                                    )}
                                                </div>
                                                <small className="text-muted"><i className="bi bi-telephone me-1"></i>{p.mobile || 'No Mobile'} • {p.gender} • {p.age}</small>
                                            </div>
                                            <div className="text-end">
                                                {p.has_active_ipd && <span className="badge bg-danger d-block mb-1">IPD Admitted: {p.active_ipd.bed_info?.bed_no || 'Bed'}</span>}
                                                {p.has_active_opd && <span className="badge bg-info d-block">OPD Visit Today</span>}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="col-md-5">
                            <input 
                                type="text" 
                                className="form-control form-control-sm" 
                                placeholder="Consultant Doctor Name" 
                                value={doctorName}
                                onChange={(e) => setDoctorName(e.target.value)}
                            />
                        </div>
                    </div>

                    {/* ABDM ABHA ID & Address Quick-Link Row */}
                    <div className="row g-2 mt-1">
                        <div className="col-md-6">
                            <div className="input-group input-group-sm">
                                <span className="input-group-text bg-light text-success fw-bold" style={{ fontSize: '0.72rem' }}>
                                    <i className="bi bi-shield-check me-1"></i> ABHA No
                                </span>
                                <input 
                                    type="text" 
                                    className="form-control form-control-sm" 
                                    placeholder="14-Digit ABHA (e.g. 12-3456-7890-1234)" 
                                    value={walkinAbhaId} 
                                    onChange={(e) => setWalkinAbhaId(e.target.value)} 
                                />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="input-group input-group-sm">
                                <span className="input-group-text bg-light text-success fw-bold" style={{ fontSize: '0.72rem' }}>
                                    <i className="bi bi-at me-1"></i> ABHA Address
                                </span>
                                <input 
                                    type="text" 
                                    className="form-control form-control-sm" 
                                    placeholder="username@abdm" 
                                    value={walkinAbhaAddress} 
                                    onChange={(e) => setWalkinAbhaAddress(e.target.value)} 
                                />
                            </div>
                        </div>
                    </div>

                    {/* Patient Context Tags */}
                    {selectedPatient && (
                        <div className="mt-2 p-2 bg-light rounded-2 d-flex flex-wrap gap-3 align-items-center" style={{ fontSize: '0.8rem' }}>
                            <span><strong>Name:</strong> {selectedPatient.full_name}</span>
                            <span><strong>UHID:</strong> <code className="text-primary">{selectedPatient.uhid}</code></span>
                            <span><strong>Gender/Age:</strong> {selectedPatient.gender}, {selectedPatient.age}</span>
                            {(selectedPatient.abha_id || selectedPatient.abha_address) && (
                                <span className="badge bg-success text-white py-1 px-2" style={{ fontSize: '0.75rem' }}>
                                    <i className="bi bi-shield-fill-check me-1"></i> ABDM Linked: {selectedPatient.abha_id || ''} {selectedPatient.abha_address ? `(${selectedPatient.abha_address})` : ''}
                                </span>
                            )}
                            {selectedPatient.has_active_ipd && (
                                <span className="text-danger fw-bold">
                                    <i className="bi bi-hospital me-1"></i>
                                    {selectedPatient.active_ipd.bed_info?.ward_name || 'Ward'} / {selectedPatient.active_ipd.bed_info?.bed_no || 'Bed'} (Dr. {selectedPatient.active_ipd.doctor_name})
                                </span>
                            )}
                            {selectedPatient.has_active_opd && !selectedPatient.has_active_ipd && (
                                <span className="text-primary fw-bold">
                                    <i className="bi bi-stethoscope me-1"></i>
                                    OPD Dr. {selectedPatient.active_opd.doctor_name}
                                </span>
                            )}
                        </div>
                    )}
                </div>

                {/* Medicine Search & Barcode Scanner Line */}
                <div className="counter-card position-relative">
                    <div className="d-flex align-items-center gap-2 mb-2">
                        <i className="bi bi-upc-scan text-primary fs-5"></i>
                        <span className="fw-bold fs-6">Medicine Lookup &amp; Barcode Dispenser</span>
                    </div>

                    <div className="input-group">
                        <span className="input-group-text bg-light"><i className="bi bi-capsule"></i></span>
                        <input 
                            type="text" 
                            className="form-control" 
                            placeholder="Type Medicine Name, Generic Molecule, or Scan Box Barcode..." 
                            value={itemQuery}
                            onChange={(e) => searchItems(e.target.value)}
                        />
                        {isSearchingItems && <span className="input-group-text bg-light"><i className="bi bi-hourglass-split"></i></span>}
                    </div>

                    {/* Item & FEFO Batch Dropdown */}
                    {itemResults.length > 0 && (
                        <div className="search-results-dropdown">
                            {itemResults.map(it => (
                                <div key={it.item_id} className="search-item-row">
                                    <div className="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <span className="fw-bold text-dark fs-6">{it.item_name}</span>
                                            <span className="badge bg-secondary ms-2" style={{ fontSize: '0.65rem' }}>{it.unit_pack}</span>
                                            {it.drug_schedule && it.drug_schedule !== 'OTC' && (
                                                <span className="badge bg-warning-subtle text-warning border border-warning ms-1" style={{ fontSize: '0.65rem' }}>
                                                    {it.drug_schedule}
                                                </span>
                                            )}
                                        </div>
                                        <div>
                                            <span className="text-muted me-2" style={{ fontSize: '0.75rem' }}>Total Stock: {it.total_stock}</span>
                                        </div>
                                    </div>
                                    <small className="text-muted d-block mb-1"><i className="bi bi-diagram-3 me-1"></i>{it.generic_name} • HSN: {it.hsn_code}</small>
                                    
                                    {/* Batches Table */}
                                    {it.batches && it.batches.length > 0 ? (
                                        <div className="d-flex flex-wrap gap-2 mt-1">
                                            {it.batches.map(b => (
                                                <button 
                                                    key={b.batch_id}
                                                    className={`btn btn-sm ${b.is_expired ? 'btn-outline-danger disabled' : 'btn-outline-dark'} d-flex align-items-center gap-1 py-0.5 px-2`}
                                                    style={{ fontSize: '0.75rem' }}
                                                    onClick={() => addItemToCart(it, b, 1)}
                                                    disabled={b.is_expired}
                                                >
                                                    <strong>{b.batch_no}</strong>
                                                    <span>(Exp: {b.expiry_display})</span>
                                                    <span className="text-success fw-bold">₹{b.mrp}</span>
                                                    <span className="badge bg-primary text-white">{b.current_qty} left</span>
                                                    {it.fefo_batch && it.fefo_batch.batch_id === b.batch_id && (
                                                        <span className="badge-fefo ms-1">FEFO</span>
                                                    )}
                                                    {b.is_near_expiry && (
                                                        <span className="badge-near-expiry ms-1">Near Exp</span>
                                                    )}
                                                    {b.is_expired && (
                                                        <span className="badge-expired ms-1">EXPIRED</span>
                                                    )}
                                                </button>
                                            ))}
                                        </div>
                                    ) : (
                                        <span className="text-danger fw-bold" style={{ fontSize: '0.75rem' }}>Out of Stock in this Counter</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Billing Cart Table */}
                <div className="counter-card p-0 overflow-hidden">
                    <div className="p-3 pb-2 d-flex justify-content-between align-items-center border-bottom">
                        <h6 className="mb-0 fw-bold"><i className="bi bi-cart-check-fill text-success me-1"></i> Dispensing Cart ({cart.length} items)</h6>
                        {cart.length > 0 && (
                            <button className="btn btn-sm btn-link text-danger text-decoration-none p-0" onClick={() => setCart([])}>
                                Clear Cart
                            </button>
                        )}
                    </div>

                    <div className="table-responsive" style={{ maxHeight: 380 }}>
                        <table className="table table-pos mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Medicine Name</th>
                                    <th>Batch / Exp</th>
                                    <th style={{ width: 85 }}>Qty</th>
                                    <th>MRP (₹)</th>
                                    <th style={{ width: 125 }}>Disc (% / ₹)</th>
                                    <th>GST%</th>
                                    <th className="text-end">Total (₹)</th>
                                    <th style={{ width: 40 }}></th>
                                </tr>
                            </thead>
                            <tbody>
                                {cart.length === 0 ? (
                                    <tr>
                                        <td colSpan="9" className="text-center py-4 text-muted">
                                            <i className="bi bi-basket3 fs-3 d-block mb-1"></i>
                                            Scan barcode or search medicine to add items to bill.
                                        </td>
                                    </tr>
                                ) : (
                                    cart.map((it, idx) => {
                                        const lineGross = (Number(it.qty) || 0) * (Number(it.unit_mrp) || 0);
                                        const lineDisc = getItemDiscount(it);
                                        const lineNet = Math.max(0, lineGross - lineDisc).toFixed(2);
                                        return (
                                            <tr key={idx}>
                                                <td>{idx + 1}</td>
                                                <td>
                                                    <div className="fw-bold">{it.item_name}</div>
                                                    <small className="text-muted">{it.generic_name} • HSN: {it.hsn_code}</small>
                                                </td>
                                                <td>
                                                    <span className="badge bg-light text-dark border">{it.batch_no}</span>
                                                    <div className="text-muted" style={{ fontSize: '0.7rem' }}>Exp: {it.expiry_display}</div>
                                                </td>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        className="form-control form-control-sm text-center fw-bold" 
                                                        min="1" 
                                                        max={it.current_qty}
                                                        value={it.qty} 
                                                        onChange={(e) => updateCartItem(idx, 'qty', e.target.value)}
                                                    />
                                                </td>
                                                <td className="fw-semibold">₹{it.unit_mrp}</td>
                                                <td>
                                                    <div className="input-group input-group-sm" style={{ width: 115 }}>
                                                        <input 
                                                            type="number" 
                                                            className="form-control form-control-sm text-center px-1 fw-semibold" 
                                                            min="0" 
                                                            max={it.discount_type === 'pct' ? 100 : lineGross}
                                                            step={it.discount_type === 'pct' ? '0.5' : '1'}
                                                            value={it.discount_val ?? ''} 
                                                            placeholder="0"
                                                            onChange={(e) => updateCartItem(idx, 'discount_val', e.target.value)}
                                                        />
                                                        <button 
                                                            type="button" 
                                                            className={`btn btn-sm ${it.discount_type === 'pct' ? 'btn-primary' : 'btn-warning text-dark'} px-2 py-0 fw-bold`}
                                                            title={`Click to switch between % and ₹ (Currently: ${it.discount_type === 'pct' ? 'Percentage' : 'Value in Rupees'})`}
                                                            onClick={() => toggleItemDiscType(idx)}
                                                        >
                                                            {it.discount_type === 'pct' ? '%' : '₹'}
                                                        </button>
                                                    </div>
                                                    {lineDisc > 0 && (
                                                        <div className="text-muted text-center mt-0.5" style={{ fontSize: '0.68rem' }}>
                                                            {it.discount_type === 'pct' 
                                                                ? `-₹${lineDisc.toFixed(2)}` 
                                                                : `${(lineGross > 0 ? ((lineDisc / lineGross) * 100).toFixed(1) : 0)}% off`}
                                                        </div>
                                                    )}
                                                </td>
                                                <td>{it.gst_rate}%</td>
                                                <td className="text-end fw-bold text-dark">₹{lineNet}</td>
                                                <td>
                                                    <button className="btn btn-sm btn-outline-danger py-0 px-1" onClick={() => removeCartItem(idx)}>
                                                        <i className="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Right Column: Checkout, Totals & Multi-Format Printing */}
            <div className="col-lg-4">
                <div className="pos-summary-card">
                    <h5 className="fw-bold text-white mb-3 d-flex justify-content-between align-items-center">
                        <span>Checkout Desk</span>
                        <span className="fs-6 text-primary">{store?.store_code || ''}</span>
                    </h5>

                    {/* Whole Bill Discount Controller */}
                    <div className="p-2.5 rounded-3 mb-3" style={{ background: 'rgba(255, 255, 255, 0.08)', border: '1px solid rgba(255, 255, 255, 0.15)' }}>
                        <div className="d-flex justify-content-between align-items-center mb-1.5">
                            <label className="text-white small fw-bold mb-0 d-flex align-items-center gap-1">
                                <i className="bi bi-tag-fill text-warning"></i> Whole Bill Discount
                            </label>
                            <div className="btn-group btn-group-sm">
                                <button 
                                    type="button" 
                                    className={`btn btn-xs py-0.5 px-2 ${wholeDiscountType === 'pct' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`}
                                    style={{ fontSize: '0.72rem' }}
                                    onClick={() => setWholeDiscountType('pct')}
                                >
                                    % Percent
                                </button>
                                <button 
                                    type="button" 
                                    className={`btn btn-xs py-0.5 px-2 ${wholeDiscountType === 'val' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`}
                                    style={{ fontSize: '0.72rem' }}
                                    onClick={() => setWholeDiscountType('val')}
                                >
                                    ₹ Fixed Value
                                </button>
                            </div>
                        </div>

                        <div className="input-group input-group-sm mb-2">
                            <span className="input-group-text bg-dark border-secondary text-warning fw-bold">
                                {wholeDiscountType === 'pct' ? '%' : '₹'}
                            </span>
                            <input 
                                type="number" 
                                className="form-control form-control-sm bg-dark text-white border-secondary fw-bold" 
                                min="0"
                                max={wholeDiscountType === 'pct' ? 100 : balanceAfterItemDisc}
                                step={wholeDiscountType === 'pct' ? '0.5' : '1'}
                                placeholder={wholeDiscountType === 'pct' ? 'Enter % (e.g. 10 for 10%)' : 'Enter ₹ (e.g. 50 for ₹50 off)'}
                                value={wholeDiscountVal || ''} 
                                onChange={(e) => setWholeDiscountVal(e.target.value)}
                            />
                            {Number(wholeDiscountVal) > 0 && (
                                <button 
                                    type="button" 
                                    className="btn btn-sm btn-outline-danger" 
                                    title="Reset Whole Bill Discount"
                                    onClick={() => setWholeDiscountVal(0)}
                                >
                                    <i className="bi bi-x-lg"></i>
                                </button>
                            )}
                        </div>

                        {/* Quick Preset Buttons */}
                        <div className="d-flex flex-wrap gap-1 align-items-center">
                            <small className="text-slate-400 me-1" style={{ fontSize: '0.7rem', color: '#94a3b8' }}>Presets:</small>
                            {wholeDiscountType === 'pct' ? (
                                [5, 10, 15, 20].map(p => (
                                    <button 
                                        key={p} 
                                        type="button" 
                                        className={`badge border ${Number(wholeDiscountVal) === p ? 'bg-warning text-dark border-warning' : 'bg-transparent text-white border-secondary'}`}
                                        style={{ cursor: 'pointer', padding: '4px 8px', fontSize: '0.7rem' }}
                                        onClick={() => setWholeDiscountVal(p)}
                                    >
                                        {p}%
                                    </button>
                                ))
                            ) : (
                                [20, 50, 100, 200].map(v => (
                                    <button 
                                        key={v} 
                                        type="button" 
                                        className={`badge border ${Number(wholeDiscountVal) === v ? 'bg-warning text-dark border-warning' : 'bg-transparent text-white border-secondary'}`}
                                        style={{ cursor: 'pointer', padding: '4px 8px', fontSize: '0.7rem' }}
                                        onClick={() => setWholeDiscountVal(v)}
                                    >
                                        ₹{v}
                                    </button>
                                ))
                            )}
                            {Number(wholeDiscountVal) > 0 && (
                                <button 
                                    type="button" 
                                    className="badge bg-danger-subtle text-danger border border-danger ms-auto"
                                    style={{ cursor: 'pointer', padding: '4px 6px', fontSize: '0.7rem' }}
                                    onClick={() => setWholeDiscountVal(0)}
                                >
                                    Clear
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Breakdown */}
                    <div className="d-flex justify-content-between mb-1.5 text-slate-300" style={{ color: '#cbd5e1' }}>
                        <span>Gross Items Total:</span>
                        <span className="fw-bold">₹{grossTotal.toFixed(2)}</span>
                    </div>

                    {itemDiscountTotal > 0 && (
                        <div className="d-flex justify-content-between mb-1 text-slate-300" style={{ fontSize: '0.84rem', color: '#94a3b8' }}>
                            <span>Item-Level Discounts:</span>
                            <span className="text-danger">-₹{itemDiscountTotal.toFixed(2)}</span>
                        </div>
                    )}

                    {wholeDiscountAmt > 0 && (
                        <div className="d-flex justify-content-between mb-1 text-slate-300" style={{ fontSize: '0.84rem', color: '#94a3b8' }}>
                            <span>Whole Bill Discount ({wholeDiscountType === 'pct' ? `${wholeDiscountVal}%` : `₹${wholeDiscountVal}`}):</span>
                            <span className="text-danger">-₹{wholeDiscountAmt.toFixed(2)}</span>
                        </div>
                    )}

                    <div className="d-flex justify-content-between mb-2 text-slate-300" style={{ color: '#cbd5e1' }}>
                        <span>Total Discounts:</span>
                        <span className="text-danger fw-bold">-₹{discountTotal.toFixed(2)}</span>
                    </div>
                    <div className="d-flex justify-content-between mb-2 text-slate-300" style={{ color: '#cbd5e1' }}>
                        <span>Round Off:</span>
                        <span>₹{roundOff}</span>
                    </div>
                    <hr style={{ borderColor: 'rgba(255,255,255,0.15)' }} />

                    <div className="d-flex justify-content-between align-items-center mb-4">
                        <span className="fs-5 fw-bold text-white">Net Payable:</span>
                        <span className="fs-3 fw-bolder text-warning">₹{netPayable.toLocaleString('en-IN')}</span>
                    </div>

                    {/* Payment Mode Selection */}
                    <label className="form-label text-white fw-bold mb-2">Payment Method</label>
                    <div className="row g-2 mb-3">
                        <div className="col-6">
                            <button 
                                className={`btn btn-sm w-100 ${paymentMode === 'Cash' ? 'btn-primary-custom' : 'btn-outline-light'}`}
                                onClick={() => setPaymentMode('Cash')}
                            >
                                <i className="bi bi-cash-stack me-1"></i> Cash
                            </button>
                        </div>
                        <div className="col-6">
                            <button 
                                className={`btn btn-sm w-100 ${paymentMode === 'UPI' ? 'btn-primary-custom' : 'btn-outline-light'}`}
                                onClick={() => setPaymentMode('UPI')}
                            >
                                <i className="bi bi-qr-code-scan me-1"></i> UPI / QR
                            </button>
                        </div>
                        <div className="col-6">
                            <button 
                                className={`btn btn-sm w-100 ${paymentMode === 'Card' ? 'btn-primary-custom' : 'btn-outline-light'}`}
                                onClick={() => setPaymentMode('Card')}
                            >
                                <i className="bi bi-credit-card me-1"></i> Debit/Credit
                            </button>
                        </div>
                        <div className="col-6">
                            <button 
                                className={`btn btn-sm w-100 ${paymentMode === 'IPD_Credit' ? 'btn-danger' : 'btn-outline-light'}`}
                                onClick={() => setPaymentMode('IPD_Credit')}
                                title="Charge to IPD Patient Bill"
                            >
                                <i className="bi bi-hospital me-1"></i> IPD Bill Post
                            </button>
                        </div>
                    </div>

                    {paymentMode === 'IPD_Credit' && (
                        <div className="alert alert-warning py-2 mb-3" style={{ fontSize: '0.78rem' }}>
                            <i className="bi bi-info-circle me-1"></i>
                            Medicines will be charged directly to the patient's IPD Hospital Account and settled at discharge.
                        </div>
                    )}

                    {paymentMode === 'UPI' && store?.upi_id && (
                        <div className="bg-dark p-2 rounded-2 text-center mb-3">
                            <small className="text-muted d-block">Store UPI VPA: <span className="text-warning fw-bold">{store?.upi_id}</span></small>
                        </div>
                    )}

                    {/* Print Format Selector */}
                    <div className="mb-3">
                        <label className="form-label text-white small fw-bold mb-1">Invoice Print Format</label>
                        <div className="btn-group btn-group-sm w-100">
                            <button 
                                type="button" 
                                className={`btn ${printFormat === 'thermal' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`}
                                onClick={() => setPrintFormat('thermal')}
                            >
                                Thermal 80mm (1-5 Items)
                            </button>
                            <button 
                                type="button" 
                                className={`btn ${printFormat === 'a5' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`}
                                onClick={() => setPrintFormat('a5')}
                            >
                                A5 Invoice (OPD)
                            </button>
                            <button 
                                type="button" 
                                className={`btn ${printFormat === 'a4' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`}
                                onClick={() => setPrintFormat('a4')}
                            >
                                A4 Tax Bill (IPD)
                            </button>
                        </div>
                    </div>

                    <button 
                        className="btn btn-success-custom w-100 py-2.5 fs-6"
                        onClick={processSale}
                        disabled={isProcessing || cart.length === 0}
                    >
                        {isProcessing ? (
                            <span><i className="bi bi-hourglass-split me-2"></i> Generating Bill...</span>
                        ) : (
                            <span><i className="bi bi-printer me-2"></i> Complete Sale &amp; Print ({printFormat.toUpperCase()})</span>
                        )}
                    </button>
                </div>

                {/* Print Preview Component for Thermal 80mm, A5, and A4 */}
                {lastInvoice && (
                    <div className="counter-card mt-3">
                        <div className="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div className="d-flex flex-wrap align-items-center gap-2">
                                <span className="fw-bold text-success"><i className="bi bi-check-circle me-1"></i> Bill: {lastInvoice.sale.invoice_no}</span>
                                {lastInvoice.sale.abdm_care_context_ref && (
                                    <span className="badge bg-success-subtle text-success border border-success" title="Ayushman Bharat Digital Mission Care Context Reference">
                                        <i className="bi bi-shield-fill-check me-1"></i> {lastInvoice.sale.abdm_care_context_ref}
                                    </span>
                                )}
                            </div>
                            <div className="d-flex gap-1">
                                <button 
                                    className="btn btn-sm btn-outline-success py-0 px-2" 
                                    onClick={() => viewAbdmBundle(lastInvoice.sale.sale_id)} 
                                    title="View official ABDM FHIR R4 MedicationDispense JSON Document Bundle"
                                >
                                    <i className="bi bi-filetype-json me-1"></i> FHIR R4 Bundle
                                </button>
                                <button className={`btn btn-sm ${printFormat === 'thermal' ? 'btn-dark' : 'btn-outline-secondary'} py-0 px-2`} onClick={() => setPrintFormat('thermal')}>80mm</button>
                                <button className={`btn btn-sm ${printFormat === 'a5' ? 'btn-dark' : 'btn-outline-secondary'} py-0 px-2`} onClick={() => setPrintFormat('a5')}>A5</button>
                                <button className={`btn btn-sm ${printFormat === 'a4' ? 'btn-dark' : 'btn-outline-secondary'} py-0 px-2`} onClick={() => setPrintFormat('a4')}>A4</button>
                                <button className="btn btn-sm btn-primary py-0 px-2" onClick={() => window.print()} title="Print Bill">
                                    <i className="bi bi-printer"></i>
                                </button>
                            </div>
                        </div>

                        <div id="printable-container">
                            {/* Format 1: 80mm Thermal Slip (Single or 1-5 Items) */}
                            {printFormat === 'thermal' && (
                                <div className="print-thermal-80mm">
                                    <div className="text-center fw-bold fs-6">{lastInvoice.sale.store_name}</div>
                                    <div className="text-center">{lastInvoice.sale.building_name}, {lastInvoice.sale.floor_no}</div>
                                    {lastInvoice.sale.abdm_hfr_id && (
                                        <div className="text-center" style={{ fontSize: '9px' }}>HFR ID: {lastInvoice.sale.abdm_hfr_id}</div>
                                    )}
                                    <div className="text-center">DL 20B: {lastInvoice.sale.drug_license_no_20b}</div>
                                    <div className="text-center">DL 21B: {lastInvoice.sale.drug_license_no_21b}</div>
                                    <div className="text-center">GSTIN: {lastInvoice.sale.gstin}</div>
                                    <hr style={{ margin: '4px 0' }} />
                                    <div>Bill No: <strong>{lastInvoice.sale.invoice_no}</strong></div>
                                    <div>Date: {lastInvoice.sale.sale_date}</div>
                                    <div>Patient: {lastInvoice.sale.patient_name} {lastInvoice.sale.uhid ? `(${lastInvoice.sale.uhid})` : ''}</div>
                                    {(lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && (
                                        <div style={{ fontSize: '9px' }}>ABHA: {lastInvoice.sale.abha_id || ''} {lastInvoice.sale.abha_address ? `(${lastInvoice.sale.abha_address})` : ''}</div>
                                    )}
                                    {lastInvoice.sale.abdm_care_context_ref && (
                                        <div style={{ fontSize: '8px' }}>Care Context: {lastInvoice.sale.abdm_care_context_ref}</div>
                                    )}
                                    <div>Doctor: {lastInvoice.sale.doctor_name}</div>
                                    <hr style={{ margin: '4px 0' }} />
                                    <table style={{ width: '100%', fontSize: '10px' }}>
                                        <thead>
                                            <tr>
                                                <th style={{ textAlign: 'left' }}>Item</th>
                                                <th style={{ textAlign: 'center' }}>Batch</th>
                                                <th style={{ textAlign: 'center' }}>Qty</th>
                                                <th style={{ textAlign: 'right' }}>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {lastInvoice.items.map((it, i) => (
                                                <tr key={i}>
                                                    <td>{it.item_name}</td>
                                                    <td style={{ textAlign: 'center' }}>{it.batch_no}</td>
                                                    <td style={{ textAlign: 'center' }}>{it.qty}</td>
                                                    <td style={{ textAlign: 'right' }}>₹{it.total_amount}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    <hr style={{ margin: '4px 0' }} />
                                    <div className="d-flex justify-content-between" style={{ fontSize: '10px' }}>
                                        <span>Gross Total:</span>
                                        <span>₹{lastInvoice.sale.gross_amount}</span>
                                    </div>
                                    {Number(lastInvoice.sale.discount_amount) > 0 && (
                                        <div className="d-flex justify-content-between text-danger" style={{ fontSize: '10px' }}>
                                            <span>Total Discount:</span>
                                            <span>-₹{lastInvoice.sale.discount_amount}</span>
                                        </div>
                                    )}
                                    <div className="d-flex justify-content-between fw-bold" style={{ fontSize: '12px' }}>
                                        <span>NET AMOUNT:</span>
                                        <span>₹{lastInvoice.sale.net_amount}</span>
                                    </div>
                                    <div style={{ fontSize: '9px', marginTop: 4 }}>Mode: {lastInvoice.sale.payment_mode}</div>
                                    <div style={{ fontSize: '9px', marginTop: 4, textAlign: 'center' }}>
                                        R.Ph: {lastInvoice.sale.registered_pharmacist_name || 'Pharmacist'} {lastInvoice.sale.pharmacist_hpr_id ? `(HPR: ${lastInvoice.sale.pharmacist_hpr_id})` : ''}
                                    </div>
                                    <div style={{ fontSize: '8px', textAlign: 'center', marginTop: 4 }}>
                                        {lastInvoice.sale.terms_conditions || 'Thank you! Get well soon.'}
                                    </div>
                                </div>
                            )}

                            {/* Format 2: A5 Half-Page Invoice (Normal OPD/Walk-in) */}
                            {printFormat === 'a5' && (
                                <div className="print-a5-invoice">
                                    <div className="d-flex justify-content-between align-items-start border-bottom pb-2 mb-2">
                                        <div>
                                            <h6 className="fw-bold mb-0 text-primary">{lastInvoice.sale.store_name}</h6>
                                            <div style={{ fontSize: '10px' }}>{lastInvoice.sale.building_name}, {lastInvoice.sale.floor_no}, {lastInvoice.sale.store_address}</div>
                                            <div style={{ fontSize: '10px' }}>
                                                <strong>DL 20B:</strong> {lastInvoice.sale.drug_license_no_20b} | <strong>DL 21B:</strong> {lastInvoice.sale.drug_license_no_21b}
                                                {lastInvoice.sale.abdm_hfr_id && <span> | <strong>HFR ID:</strong> {lastInvoice.sale.abdm_hfr_id}</span>}
                                            </div>
                                            <div style={{ fontSize: '10px' }}><strong>GSTIN:</strong> {lastInvoice.sale.gstin} | <strong>Phone:</strong> {lastInvoice.sale.contact_phone}</div>
                                        </div>
                                        <div className="text-end">
                                            <span className="badge bg-dark text-white mb-1">RETAIL TAX INVOICE</span>
                                            <div className="fw-bold" style={{ fontSize: '12px' }}>{lastInvoice.sale.invoice_no}</div>
                                            <div className="text-muted" style={{ fontSize: '10px' }}>{lastInvoice.sale.sale_date}</div>
                                        </div>
                                    </div>

                                    <div className="row g-1 mb-2 p-1.5 bg-light rounded" style={{ fontSize: '10px' }}>
                                        <div className="col-6"><strong>Patient:</strong> {lastInvoice.sale.patient_name} {lastInvoice.sale.uhid ? `(${lastInvoice.sale.uhid})` : ''}</div>
                                        <div className="col-6"><strong>Prescribed By:</strong> {lastInvoice.sale.doctor_name}</div>
                                        <div className="col-6"><strong>Age/Gender:</strong> {lastInvoice.sale.age || 'N/A'} / {lastInvoice.sale.gender || 'N/A'}</div>
                                        <div className="col-6"><strong>Payment Mode:</strong> {lastInvoice.sale.payment_mode}</div>
                                        {(lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && (
                                            <div className="col-12 text-success">
                                                <strong>ABHA:</strong> {lastInvoice.sale.abha_id || 'N/A'} ({lastInvoice.sale.abha_address || 'N/A'})
                                                {lastInvoice.sale.abdm_care_context_ref && <span> | <strong>Care Context:</strong> {lastInvoice.sale.abdm_care_context_ref}</span>}
                                            </div>
                                        )}
                                    </div>

                                    <table className="table table-bordered table-sm mb-2" style={{ fontSize: '10px' }}>
                                        <thead className="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Medicine Name</th>
                                                <th>HSN</th>
                                                <th>Batch</th>
                                                <th>Exp</th>
                                                <th>Qty</th>
                                                <th>MRP (₹)</th>
                                                <th className="text-end">Amount (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {lastInvoice.items.map((it, i) => (
                                                <tr key={i}>
                                                    <td>{i + 1}</td>
                                                    <td className="fw-bold">
                                                        {it.item_name}
                                                        {it.snomed_ct_code && <div className="text-muted" style={{ fontSize: '8px' }}>SNOMED: {it.snomed_ct_code}</div>}
                                                    </td>
                                                    <td>{it.hsn_code}</td>
                                                    <td>{it.batch_no}</td>
                                                    <td>{it.expiry_date.substring(0,7)}</td>
                                                    <td>{it.qty}</td>
                                                    <td>₹{it.unit_mrp}</td>
                                                    <td className="text-end fw-bold">₹{it.total_amount}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>

                                    <div className="d-flex justify-content-between align-items-center border-top pt-2">
                                        <div style={{ fontSize: '9px' }}>
                                            R.Ph: {lastInvoice.sale.registered_pharmacist_name || 'Pharmacist'} ({lastInvoice.sale.pharmacist_reg_no})
                                            {lastInvoice.sale.pharmacist_hpr_id && <span> | HPR ID: {lastInvoice.sale.pharmacist_hpr_id}</span>}
                                        </div>
                                        <div className="text-end">
                                            <div style={{ fontSize: '10px' }} className="text-muted">
                                                Gross: ₹{lastInvoice.sale.gross_amount}
                                                {Number(lastInvoice.sale.discount_amount) > 0 && (
                                                    <span className="text-danger ms-2">Disc: -₹{lastInvoice.sale.discount_amount}</span>
                                                )}
                                            </div>
                                            <span className="fs-6 fw-bold text-primary">TOTAL: ₹{lastInvoice.sale.net_amount}</span>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Format 3: A4 Comprehensive Hospital Tax Invoice (IPD & Large Bills) */}
                            {printFormat === 'a4' && (
                                <div className="print-a4-invoice">
                                    <div className="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                                        <div>
                                            <h4 className="fw-bold text-primary mb-1">{lastInvoice.sale.store_name}</h4>
                                            <div className="text-muted">{lastInvoice.sale.building_name}, {lastInvoice.sale.floor_no}, {lastInvoice.sale.store_address}</div>
                                            <div><strong>Drug License 20-B:</strong> {lastInvoice.sale.drug_license_no_20b} | <strong>21-B:</strong> {lastInvoice.sale.drug_license_no_21b} | <strong>FSSAI:</strong> {lastInvoice.sale.fssai_no || 'N/A'}</div>
                                            <div>
                                                <strong>GSTIN:</strong> {lastInvoice.sale.gstin} | <strong>State:</strong> {lastInvoice.sale.state_name} (Code: {lastInvoice.sale.state_code})
                                                {lastInvoice.sale.abdm_hfr_id && <span> | <strong>ABDM HFR Facility ID:</strong> {lastInvoice.sale.abdm_hfr_id}</span>}
                                            </div>
                                        </div>
                                        <div className="text-end">
                                            <span className="badge bg-primary fs-6 px-3 py-1.5 mb-1">HOSPITAL GST TAX INVOICE</span>
                                            <h5 className="fw-bold mb-0 text-dark">{lastInvoice.sale.invoice_no}</h5>
                                            <div className="text-muted small">Date &amp; Time: {lastInvoice.sale.sale_date}</div>
                                        </div>
                                    </div>

                                    {/* Patient & Hospital Admission Info */}
                                    <div className="row g-2 mb-3 p-2 bg-light rounded border">
                                        <div className="col-4"><strong>Patient Name:</strong> {lastInvoice.sale.patient_name}</div>
                                        <div className="col-4"><strong>UHID No:</strong> <code className="text-primary fw-bold">{lastInvoice.sale.uhid || 'Walk-in'}</code></div>
                                        <div className="col-4"><strong>Phone:</strong> {lastInvoice.sale.patient_mobile || 'N/A'}</div>
                                        <div className="col-4"><strong>Age / Gender:</strong> {lastInvoice.sale.age || 'N/A'} / {lastInvoice.sale.gender || 'N/A'}</div>
                                        <div className="col-4"><strong>Consultant Doctor:</strong> {lastInvoice.sale.doctor_name}</div>
                                        <div className="col-4"><strong>Doctor Reg No:</strong> {lastInvoice.sale.doctor_reg_no || 'N/A'}</div>
                                        {(lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && (
                                            <div className="col-12 bg-success-subtle p-1.5 rounded text-success fw-bold border border-success mt-1">
                                                <i className="bi bi-shield-fill-check me-1"></i> ABDM Linked: ABHA Number: {lastInvoice.sale.abha_id || 'N/A'} | ABHA Address: {lastInvoice.sale.abha_address || 'N/A'} | Care Context: {lastInvoice.sale.abdm_care_context_ref || 'N/A'}
                                            </div>
                                        )}
                                        {lastInvoice.sale.ipd_id && (
                                            <div className="col-12 text-danger fw-bold border-top pt-1 mt-1">
                                                <i className="bi bi-hospital me-1"></i> IPD Admission #{lastInvoice.sale.ipd_id} • Ward: {lastInvoice.sale.ward_name || 'General'} • Bed: {lastInvoice.sale.bed_no || 'Bed'} (Charged to IPD Running Bill)
                                            </div>
                                        )}
                                    </div>

                                    {/* Items Table */}
                                    <table className="table table-bordered mb-3">
                                        <thead className="table-light">
                                            <tr>
                                                <th style={{ width: 40 }}>#</th>
                                                <th>Medicine &amp; Formulation</th>
                                                <th>HSN</th>
                                                <th>Batch No</th>
                                                <th>Exp Date</th>
                                                <th style={{ width: 60 }}>Qty</th>
                                                <th>MRP (₹)</th>
                                                <th>Taxable (₹)</th>
                                                <th>CGST (₹)</th>
                                                <th>SGST (₹)</th>
                                                <th className="text-end">Total (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {lastInvoice.items.map((it, i) => (
                                                <tr key={i}>
                                                    <td>{i + 1}</td>
                                                    <td className="fw-bold">
                                                        {it.item_name}
                                                        {it.snomed_ct_code && (
                                                            <div className="text-muted fw-normal" style={{ fontSize: '9px' }}>
                                                                SNOMED-CT: <code>{it.snomed_ct_code}</code> ({it.snomed_display || ''})
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td>{it.hsn_code}</td>
                                                    <td>{it.batch_no}</td>
                                                    <td>{it.expiry_date}</td>
                                                    <td>{it.qty}</td>
                                                    <td>₹{it.unit_mrp}</td>
                                                    <td>₹{it.taxable_value}</td>
                                                    <td>₹{it.cgst_amount}</td>
                                                    <td>₹{it.sgst_amount}</td>
                                                    <td className="text-end fw-bold">₹{it.total_amount}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>

                                    {/* HSN Summary Table */}
                                    {lastInvoice.hsn_summary && lastInvoice.hsn_summary.length > 0 && (
                                        <div className="mb-3">
                                            <label className="form-label small fw-bold text-muted mb-1">GST Tax Breakdown</label>
                                            <table className="table table-sm table-bordered text-center" style={{ fontSize: '10px' }}>
                                                <thead className="table-light">
                                                    <tr>
                                                        <th>HSN Code</th>
                                                        <th>GST Rate</th>
                                                        <th>Taxable Value (₹)</th>
                                                        <th>CGST (₹)</th>
                                                        <th>SGST (₹)</th>
                                                        <th>Total Tax (₹)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {lastInvoice.hsn_summary.map((h, idx) => (
                                                        <tr key={idx}>
                                                            <td>{h.hsn_code}</td>
                                                            <td>{h.gst_rate}%</td>
                                                            <td>₹{h.taxable_value.toFixed(2)}</td>
                                                            <td>₹{h.cgst_amount.toFixed(2)}</td>
                                                            <td>₹{h.sgst_amount.toFixed(2)}</td>
                                                            <td className="fw-bold">₹{h.total_tax.toFixed(2)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}

                                    {/* Summary Totals & Signatures */}
                                    <div className="row g-3 border-top pt-3">
                                        <div className="col-7">
                                            <small className="text-muted d-block">
                                                <strong>Registered Pharmacist:</strong> {lastInvoice.sale.registered_pharmacist_name || 'Pharmacist'} (Reg No: {lastInvoice.sale.pharmacist_reg_no || 'N/A'})
                                                {lastInvoice.sale.pharmacist_hpr_id && <span> | <strong>HPR ID:</strong> {lastInvoice.sale.pharmacist_hpr_id}</span>}
                                            </small>
                                            <small className="text-muted d-block mt-1">
                                                {lastInvoice.sale.terms_conditions || '1. Goods once sold are returnable only as per Drug Rules.\n2. Store medicines below 25°C.'}
                                            </small>
                                        </div>
                                        <div className="col-5">
                                            <div className="d-flex justify-content-between mb-1">
                                                <span>Gross Items Total:</span>
                                                <span>₹{lastInvoice.sale.gross_amount}</span>
                                            </div>
                                            {Number(lastInvoice.sale.discount_amount) > 0 && (
                                                <div className="d-flex justify-content-between mb-1 text-danger">
                                                    <span>Total Discounts Allowed:</span>
                                                    <span>-₹{lastInvoice.sale.discount_amount}</span>
                                                </div>
                                            )}
                                            <div className="d-flex justify-content-between mb-1">
                                                <span>Taxable Amount:</span>
                                                <span>₹{lastInvoice.sale.taxable_amount}</span>
                                            </div>
                                            <div className="d-flex justify-content-between mb-1">
                                                <span>CGST Amount:</span>
                                                <span>₹{lastInvoice.sale.cgst_amount}</span>
                                            </div>
                                            <div className="d-flex justify-content-between mb-1">
                                                <span>SGST Amount:</span>
                                                <span>₹{lastInvoice.sale.sgst_amount}</span>
                                            </div>
                                            <div className="d-flex justify-content-between mb-1">
                                                <span>Round Off:</span>
                                                <span>₹{lastInvoice.sale.round_off}</span>
                                            </div>
                                            <div className="d-flex justify-content-between fs-5 fw-bolder border-top pt-2 text-primary">
                                                <span>GRAND TOTAL:</span>
                                                <span>₹{lastInvoice.sale.net_amount}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* ABDM FHIR R4 Bundle Modal */}
                {showFhirModal && (
                    <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(15, 23, 42, 0.75)' }} tabIndex="-1">
                        <div className="modal-dialog modal-lg modal-dialog-scrollable">
                            <div className="modal-content border-0 shadow-lg">
                                <div className="modal-header bg-dark text-white py-2 px-3">
                                    <div className="d-flex align-items-center gap-2">
                                        <i className="bi bi-shield-fill-check text-success fs-5"></i>
                                        <div>
                                            <h6 className="modal-title mb-0 fw-bold">ABDM NRCES FHIR R4 MedicationDispense Document</h6>
                                            <small className="text-secondary" style={{ fontSize: '0.75rem' }}>
                                                Profile: MedicationDispenseDocument | LOINC: 60590-7
                                            </small>
                                        </div>
                                    </div>
                                    <button type="button" className="btn-close btn-close-white" onClick={() => setShowFhirModal(false)}></button>
                                </div>
                                <div className="modal-body p-3 bg-light">
                                    {isLoadingFhir ? (
                                        <div className="text-center py-5">
                                            <div className="spinner-border text-primary mb-2" role="status"></div>
                                            <div className="text-muted">Retrieving &amp; Validating ABDM FHIR R4 Bundle...</div>
                                        </div>
                                    ) : fhirBundleData ? (
                                        <div>
                                            <div className="row g-2 mb-3">
                                                <div className="col-md-4">
                                                    <div className="bg-white p-2 rounded border">
                                                        <small className="text-muted d-block">Care Context Reference</small>
                                                        <code className="text-primary fw-bold">{fhirBundleData.care_context_ref}</code>
                                                    </div>
                                                </div>
                                                <div className="col-md-4">
                                                    <div className="bg-white p-2 rounded border">
                                                        <small className="text-muted d-block">ABHA Number / Address</small>
                                                        <strong className="text-success">{fhirBundleData.abha_id || fhirBundleData.abha_address || 'Not ABHA Linked'}</strong>
                                                    </div>
                                                </div>
                                                <div className="col-md-4">
                                                    <div className="bg-white p-2 rounded border">
                                                        <small className="text-muted d-block">Gateway Sync Status</small>
                                                        <span className={`badge ${fhirBundleData.sync_status === 'SYNCED' ? 'bg-success' : 'bg-warning text-dark'}`}>
                                                            {fhirBundleData.sync_status || 'QUEUED_FOR_BRIDGE'}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="d-flex justify-content-between align-items-center mb-1">
                                                <span className="small fw-bold text-dark">NRCES FHIR R4 Document Bundle (JSON):</span>
                                                <button className="btn btn-sm btn-outline-secondary py-0 px-2" onClick={copyFhirToClipboard}>
                                                    <i className={`bi ${copiedFhir ? 'bi-check-lg text-success' : 'bi-clipboard'} me-1`}></i>
                                                    {copiedFhir ? 'Copied!' : 'Copy JSON'}
                                                </button>
                                            </div>
                                            <pre className="bg-dark text-light p-3 rounded-2" style={{ maxHeight: 360, fontSize: '0.75rem', overflow: 'auto' }}>
                                                {JSON.stringify(fhirBundleData.bundle, null, 2)}
                                            </pre>
                                        </div>
                                    ) : (
                                        <div className="alert alert-warning mb-0">No FHIR bundle data found.</div>
                                    )}
                                </div>
                                <div className="modal-footer py-1 px-3 bg-white">
                                    <small className="text-muted me-auto" style={{ fontSize: '0.75rem' }}>
                                        Includes SNOMED-CT clinical codes, HFR Facility ID, and Practitioner HPR ID.
                                    </small>
                                    <button type="button" className="btn btn-sm btn-secondary" onClick={() => setShowFhirModal(false)}>Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// =========================================================================
// 2. LIVE INVENTORY & BATCH MATRIX VIEW
// =========================================================================
function InventoryView({ store, notify }) {
    if (!store) return null;
    const [stock, setStock] = useState([]);
    const [summary, setSummary] = useState({});
    const [filter, setFilter] = useState('all');
    const [searchTerm, setSearchTerm] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (store?.store_id) {
            loadStock();
        }
    }, [store?.store_id, filter]);

    const loadStock = async () => {
        setLoading(true);
        try {
            const res = await fetch(API_BASE + `stock/list?store_id=${store.store_id}&filter=${filter}`);
            const data = await res.json();
            if (data.status) {
                setStock(data.stock || []);
                setSummary(data.summary || {});
            }
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    const filteredStock = stock.filter(s => 
        s.item_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (s.generic_name && s.generic_name.toLowerCase().includes(searchTerm.toLowerCase())) ||
        s.batch_no.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div>
            {/* KPI Summary Cards */}
            <div className="row g-3 mb-3">
                <div className="col-md-3 col-sm-6">
                    <div className="counter-card p-3 border-start border-4 border-primary">
                        <small className="text-muted fw-bold text-uppercase">Total Batches in Store</small>
                        <h4 className="fw-bolder text-dark mt-1 mb-0">{summary.total_rows || 0}</h4>
                    </div>
                </div>
                <div className="col-md-3 col-sm-6">
                    <div className="counter-card p-3 border-start border-4 border-success">
                        <small className="text-muted fw-bold text-uppercase">Stock Cost Valuation (PTR)</small>
                        <h4 className="fw-bolder text-success mt-1 mb-0">₹{(summary.total_cost_valuation || 0).toLocaleString('en-IN')}</h4>
                    </div>
                </div>
                <div className="col-md-3 col-sm-6">
                    <div className="counter-card p-3 border-start border-4 border-warning">
                        <small className="text-muted fw-bold text-uppercase">Stock Retail Valuation (MRP)</small>
                        <h4 className="fw-bolder text-dark mt-1 mb-0">₹{(summary.total_mrp_valuation || 0).toLocaleString('en-IN')}</h4>
                    </div>
                </div>
                <div className="col-md-3 col-sm-6">
                    <div className="counter-card p-3 border-start border-4 border-info">
                        <small className="text-muted fw-bold text-uppercase">Counter / URL</small>
                        <h6 className="fw-bolder text-primary mt-1 mb-0 text-truncate">MedicalStore/{store.store_slug}</h6>
                    </div>
                </div>
            </div>

            {/* Filter Tabs & Search Bar */}
            <div className="counter-card p-3 mb-3">
                <div className="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div className="btn-group btn-group-sm">
                        <button className={`btn ${filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setFilter('all')}>
                            All Stock
                        </button>
                        <button className={`btn ${filter === 'low' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setFilter('low')}>
                            <i className="bi bi-arrow-down-circle me-1"></i> Low Stock
                        </button>
                        <button className={`btn ${filter === 'near_expiry' ? 'btn-warning' : 'btn-outline-secondary'}`} onClick={() => setFilter('near_expiry')}>
                            <i className="bi bi-clock-history me-1"></i> Near Expiry (&lt;90 Days)
                        </button>
                        <button className={`btn ${filter === 'expired' ? 'btn-danger' : 'btn-outline-secondary'}`} onClick={() => setFilter('expired')}>
                            <i className="bi bi-shield-x me-1"></i> Expired Stock
                        </button>
                    </div>

                    <div style={{ maxWidth: 300 }} className="w-100">
                        <input 
                            type="text" 
                            className="form-control form-control-sm" 
                            placeholder="Search item, molecule, batch..." 
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            {/* Stock Table */}
            <div className="counter-card p-0 overflow-hidden">
                <div className="table-responsive">
                    <table className="table table-pos table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item &amp; Generic Molecule</th>
                                <th>Category</th>
                                <th>Batch No</th>
                                <th>Expiry</th>
                                <th>Current Qty</th>
                                <th>PTR (₹)</th>
                                <th>MRP (₹)</th>
                                <th>Cost Value (₹)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan="10" className="text-center py-4 text-muted">
                                        <i className="bi bi-hourglass-split fs-4 d-block mb-1"></i> Loading inventory...
                                    </td>
                                </tr>
                            ) : filteredStock.length === 0 ? (
                                <tr>
                                    <td colSpan="10" className="text-center py-4 text-muted">No stock records found.</td>
                                </tr>
                            ) : (
                                filteredStock.map((s, idx) => (
                                    <tr key={s.stock_id}>
                                        <td>{idx + 1}</td>
                                        <td>
                                            <div className="fw-bold">{s.item_name}</div>
                                            <small className="text-muted">{s.generic_name} • HSN: {s.hsn_code}</small>
                                        </td>
                                        <td><span className="badge bg-secondary-subtle text-secondary">{s.category}</span></td>
                                        <td><span className="badge bg-light text-dark border fw-bold">{s.batch_no}</span></td>
                                        <td>{s.expiry_display}</td>
                                        <td>
                                            <span className={`fw-bold fs-6 ${s.current_qty <= s.min_reorder_level ? 'text-danger' : 'text-success'}`}>
                                                {s.current_qty}
                                            </span>
                                        </td>
                                        <td>₹{s.ptr}</td>
                                        <td className="fw-semibold">₹{s.mrp}</td>
                                        <td className="fw-bold">₹{s.cost_valuation}</td>
                                        <td>
                                            {s.is_expired ? (
                                                <span className="badge-expired">EXPIRED</span>
                                            ) : s.is_near_expiry ? (
                                                <span className="badge-near-expiry">Near Expiry</span>
                                            ) : (
                                                <span className="badge-fefo">Good Stock</span>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

// =========================================================================
// 3. INWARD PURCHASES, SUPPLIERS & MARG ERP IMPORT
// =========================================================================
function PurchaseView({ store, notify }) {
    if (!store) return null;
    const [subTab, setSubTab] = useState('inward'); // inward, marg, suppliers
    const [suppliers, setSuppliers] = useState([]);
    const [selectedSupplierId, setSelectedSupplierId] = useState('');
    const [invoiceNo, setInvoiceNo] = useState('');
    const [invoiceDate, setInvoiceDate] = useState(new Date().toISOString().split('T')[0]);

    const [rows, setRows] = useState([
        { item_name: '', generic_name: '', batch_no: '', expiry_date: '', qty_packs: 1, free_qty_packs: 0, units_per_pack: 10, mrp: 0, ptr: 0, discount_pct: 0, gst_rate: 12 }
    ]);

    useEffect(() => {
        fetchSuppliers();
    }, []);

    const fetchSuppliers = async () => {
        try {
            const res = await fetch(API_BASE + 'suppliers');
            const data = await res.json();
            if (data.status) setSuppliers(data.suppliers || []);
        } catch (e) {
            console.error(e);
        }
    };

    const addRow = () => {
        setRows([...rows, { item_name: '', generic_name: '', batch_no: '', expiry_date: '', qty_packs: 1, free_qty_packs: 0, units_per_pack: 10, mrp: 0, ptr: 0, discount_pct: 0, gst_rate: 12 }]);
    };

    const updateRow = (idx, field, val) => {
        const updated = [...rows];
        updated[idx][field] = val;
        setRows(updated);
    };

    const removeRow = (idx) => {
        setRows(rows.filter((_, i) => i !== idx));
    };

    const savePurchaseBill = async () => {
        if (!selectedSupplierId || !invoiceNo) {
            notify('Please select supplier and enter invoice number.', 'error');
            return;
        }

        const validItems = rows.filter(r => r.item_name && r.batch_no && r.expiry_date && Number(r.qty_packs) > 0);
        if (validItems.length === 0) {
            notify('Please fill at least one valid item line.', 'error');
            return;
        }

        try {
            const itemsPayload = [];
            for (const r of validItems) {
                const searchRes = await fetch(API_BASE + `items/search?store_id=${store.store_id}&q=${encodeURIComponent(r.item_name)}`);
                const sData = await searchRes.json();
                let itemId = 1;
                if (sData.status && sData.items && sData.items.length > 0) {
                    itemId = sData.items[0].item_id;
                }
                itemsPayload.push({
                    item_id: itemId,
                    batch_no: r.batch_no,
                    expiry_date: r.expiry_date,
                    qty_packs: Number(r.qty_packs),
                    free_qty_packs: Number(r.free_qty_packs || 0),
                    units_per_pack: Number(r.units_per_pack || 10),
                    mrp: Number(r.mrp),
                    ptr: Number(r.ptr),
                    discount_pct: Number(r.discount_pct || 0),
                    gst_rate: Number(r.gst_rate || 12)
                });
            }

            const res = await fetch(API_BASE + 'purchase/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    store_id: store.store_id,
                    supplier_id: Number(selectedSupplierId),
                    supplier_invoice_no: invoiceNo,
                    invoice_date: invoiceDate,
                    items: itemsPayload
                })
            });
            const data = await res.json();
            if (data.status) {
                notify('Purchase inward recorded! Supplier Ledger & Stock updated.', 'success');
                setInvoiceNo('');
                setRows([{ item_name: '', generic_name: '', batch_no: '', expiry_date: '', qty_packs: 1, free_qty_packs: 0, units_per_pack: 10, mrp: 0, ptr: 0, discount_pct: 0, gst_rate: 12 }]);
            } else {
                notify(data.message || 'Failed to save purchase bill.', 'error');
            }
        } catch (e) {
            notify('Purchase bill error: ' + e.message, 'error');
        }
    };

    return (
        <div>
            <div className="d-flex gap-2 mb-3">
                <button className={`btn btn-sm ${subTab === 'inward' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSubTab('inward')}>
                    <i className="bi bi-box-arrow-in-down me-1"></i> Inward Purchase Bill
                </button>
                <button className={`btn btn-sm ${subTab === 'marg' ? 'btn-success' : 'btn-outline-secondary'}`} onClick={() => setSubTab('marg')}>
                    <i className="bi bi-file-earmark-spreadsheet me-1"></i> Marg ERP / Excel Import
                </button>
                <button className={`btn btn-sm ${subTab === 'suppliers' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setSubTab('suppliers')}>
                    <i className="bi bi-truck me-1"></i> Supplier Directory
                </button>
            </div>

            {subTab === 'inward' && (
                <div className="counter-card">
                    <h5 className="fw-bold mb-3"><i className="bi bi-receipt text-primary me-1"></i> Distributor Purchase Inward (Stock Entry)</h5>
                    <div className="row g-2 mb-3">
                        <div className="col-md-4">
                            <label className="form-label small fw-bold">Supplier / Distributor</label>
                            <select className="form-select form-select-sm" value={selectedSupplierId} onChange={(e) => setSelectedSupplierId(e.target.value)}>
                                <option value="">-- Select Supplier --</option>
                                {suppliers.map(s => (
                                    <option key={s.supplier_id} value={s.supplier_id}>
                                        {s.supplier_name} (GST: {s.gstin || 'N/A'})
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="col-md-4">
                            <label className="form-label small fw-bold">Supplier Bill / Invoice No.</label>
                            <input type="text" className="form-control form-control-sm" placeholder="e.g. APX-9941" value={invoiceNo} onChange={(e) => setInvoiceNo(e.target.value)} />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label small fw-bold">Invoice Date</label>
                            <input type="date" className="form-control form-control-sm" value={invoiceDate} onChange={(e) => setInvoiceDate(e.target.value)} />
                        </div>
                    </div>

                    <div className="table-responsive">
                        <table className="table table-pos table-bordered">
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Batch No</th>
                                    <th>Expiry</th>
                                    <th style={{ width: 70 }}>Packs</th>
                                    <th style={{ width: 65 }}>Free</th>
                                    <th style={{ width: 80 }}>Units/Pack</th>
                                    <th style={{ width: 85 }}>PTR (₹)</th>
                                    <th style={{ width: 85 }}>MRP (₹)</th>
                                    <th style={{ width: 65 }}>GST%</th>
                                    <th style={{ width: 40 }}></th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((r, i) => (
                                    <tr key={i}>
                                        <td>
                                            <input type="text" className="form-control form-control-sm" placeholder="e.g. Augmentin 625" value={r.item_name} onChange={(e) => updateRow(i, 'item_name', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="text" className="form-control form-control-sm" placeholder="Batch" value={r.batch_no} onChange={(e) => updateRow(i, 'batch_no', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="date" className="form-control form-control-sm" value={r.expiry_date} onChange={(e) => updateRow(i, 'expiry_date', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm" min="1" value={r.qty_packs} onChange={(e) => updateRow(i, 'qty_packs', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm" min="0" value={r.free_qty_packs} onChange={(e) => updateRow(i, 'free_qty_packs', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm" min="1" value={r.units_per_pack} onChange={(e) => updateRow(i, 'units_per_pack', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm" placeholder="PTR" value={r.ptr} onChange={(e) => updateRow(i, 'ptr', e.target.value)} />
                                        </td>
                                        <td>
                                            <input type="number" className="form-control form-control-sm" placeholder="MRP" value={r.mrp} onChange={(e) => updateRow(i, 'mrp', e.target.value)} />
                                        </td>
                                        <td>
                                            <select className="form-select form-select-sm" value={r.gst_rate} onChange={(e) => updateRow(i, 'gst_rate', e.target.value)}>
                                                <option value="0">0%</option>
                                                <option value="5">5%</option>
                                                <option value="12">12%</option>
                                                <option value="18">18%</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button className="btn btn-sm btn-outline-danger py-0 px-1" onClick={() => removeRow(i)}>
                                                <i className="bi bi-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="d-flex justify-content-between mt-2">
                        <button className="btn btn-sm btn-outline-primary" onClick={addRow}>
                            <i className="bi bi-plus-circle me-1"></i> Add Another Item
                        </button>
                        <button className="btn btn-success-custom" onClick={savePurchaseBill}>
                            <i className="bi bi-check-circle me-1"></i> Record Inward Bill &amp; Update Inventory
                        </button>
                    </div>
                </div>
            )}

            {subTab === 'marg' && (
                <div className="counter-card">
                    <h5 className="fw-bold text-success mb-2"><i className="bi bi-file-earmark-spreadsheet me-1"></i> Marg ERP / Excel Inventory Importer</h5>
                    <p className="text-muted small mb-3">
                        Upload your stock export from <strong>Marg Pharmacy Software</strong> or Microsoft Excel to seamlessly import medicines, batch numbers, MRP, PTR, HSN codes, and opening stock into <strong>{store.store_name}</strong>.
                    </p>

                    <div className="p-4 bg-light rounded-3 border text-center mb-3">
                        <i className="bi bi-cloud-arrow-up fs-1 text-success d-block mb-2"></i>
                        <h6 className="fw-bold">Upload Marg CSV / Excel File</h6>
                        <p className="small text-muted mb-3">Ensure file is exported in CSV format from Marg ERP.</p>
                        <input type="file" id="marg_file_input" accept=".csv, .txt" className="form-control form-control-sm w-50 mx-auto mb-3" />
                        <button 
                            className="btn btn-success-custom"
                            onClick={async () => {
                                const fileInput = document.getElementById('marg_file_input');
                                if (!fileInput.files || fileInput.files.length === 0) {
                                    alert('Please select a CSV file first.');
                                    return;
                                }
                                const formData = new FormData();
                                formData.append('store_id', store.store_id);
                                formData.append('import_file', fileInput.files[0]);

                                try {
                                    const res = await fetch(API_BASE + 'stock/import-marg', {
                                        method: 'POST',
                                        body: formData
                                    });
                                    const data = await res.json();
                                    if (data.ok) {
                                        alert(data.message);
                                        fileInput.value = '';
                                    } else {
                                        alert(data.error || 'Import failed.');
                                    }
                                } catch (e) {
                                    alert('Import error: ' + e.message);
                                }
                            }}
                        >
                            <i className="bi bi-upload me-1"></i> Import Inventory into {store.store_name}
                        </button>
                    </div>
                </div>
            )}

            {subTab === 'suppliers' && (
                <div className="counter-card">
                    <h5 className="fw-bold mb-3"><i className="bi bi-truck text-primary me-1"></i> Approved Drug Distributors &amp; Suppliers</h5>
                    <div className="table-responsive">
                        <table className="table table-pos mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Supplier Name</th>
                                    <th>DL Form 20B / 21B</th>
                                    <th>GSTIN</th>
                                    <th>Phone / Contact</th>
                                    <th>Credit Terms</th>
                                </tr>
                            </thead>
                            <tbody>
                                {suppliers.map((s, i) => (
                                    <tr key={s.supplier_id}>
                                        <td>{i + 1}</td>
                                        <td className="fw-bold">{s.supplier_name}</td>
                                        <td>{s.dl_no_20b || 'N/A'} / {s.dl_no_21b || 'N/A'}</td>
                                        <td><code>{s.gstin}</code></td>
                                        <td>{s.phone} ({s.contact_person})</td>
                                        <td>{s.credit_days} Days</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
}

// =========================================================================
// 4. INTER-STORE INDENTS & TRANSFERS
// =========================================================================
function TransferView({ store, stores, notify }) {
    if (!store) return null;
    const [toStoreId, setToStoreId] = useState('');
    const [remarks, setRemarks] = useState('');
    const [transferItems, setTransferItems] = useState([
        { item_name: '', qty: 10 }
    ]);

    const addTransferRow = () => {
        setTransferItems([...transferItems, { item_name: '', qty: 10 }]);
    };

    const submitIndent = async () => {
        if (!toStoreId) {
            notify('Please select destination store counter.', 'error');
            return;
        }

        try {
            const res = await fetch(API_BASE + 'transfer/request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    from_store_id: store.store_id,
                    to_store_id: Number(toStoreId),
                    remarks: remarks,
                    items: transferItems.map(it => ({ item_id: 1, qty: Number(it.qty) }))
                })
            });
            const data = await res.json();
            if (data.status) {
                notify(`Store Indent ${data.transfer_no} submitted to Central Pharmacy!`, 'success');
                setRemarks('');
            } else {
                notify(data.message || 'Failed to submit indent.', 'error');
            }
        } catch (e) {
            notify('Indent error: ' + e.message, 'error');
        }
    };

    return (
        <div className="counter-card">
            <h5 className="fw-bold mb-3"><i className="bi bi-arrow-left-right text-primary me-1"></i> Multi-Building Store Requisition (Indent)</h5>
            <p className="text-muted small">
                Building counters can requisition medicines from the Main Hospital Central Store. Once dispatched and physically verified, stock transfers automatically.
            </p>

            <div className="row g-2 mb-3">
                <div className="col-md-6">
                    <label className="form-label small fw-bold">Source Store (From)</label>
                    <input type="text" className="form-control form-control-sm" value={store.store_name} disabled />
                </div>
                <div className="col-md-6">
                    <label className="form-label small fw-bold">Target Counter / Building (To)</label>
                    <select className="form-select form-select-sm" value={toStoreId} onChange={(e) => setToStoreId(e.target.value)}>
                        <option value="">-- Select Destination Store --</option>
                        {stores.filter(s => s.store_id !== store.store_id).map(s => (
                            <option key={s.store_id} value={s.store_id}>
                                {s.store_name} ({s.store_slug})
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            <div className="mb-3">
                <label className="form-label small fw-bold">Requested Medicines</label>
                {transferItems.map((ti, i) => (
                    <div key={i} className="row g-2 mb-2">
                        <div className="col-8">
                            <input 
                                type="text" 
                                className="form-control form-control-sm" 
                                placeholder="Medicine Name" 
                                value={ti.item_name} 
                                onChange={(e) => {
                                    const updated = [...transferItems];
                                    updated[i].item_name = e.target.value;
                                    setTransferItems(updated);
                                }} 
                            />
                        </div>
                        <div className="col-4">
                            <input 
                                type="number" 
                                className="form-control form-control-sm" 
                                placeholder="Quantity Required" 
                                min="1" 
                                value={ti.qty} 
                                onChange={(e) => {
                                    const updated = [...transferItems];
                                    updated[i].qty = e.target.value;
                                    setTransferItems(updated);
                                }} 
                            />
                        </div>
                    </div>
                ))}
                <button className="btn btn-sm btn-outline-primary mt-1" onClick={addTransferRow}>
                    <i className="bi bi-plus-circle me-1"></i> Add Another Medicine
                </button>
            </div>

            <button className="btn btn-primary-custom" onClick={submitIndent}>
                <i className="bi bi-send me-1"></i> Submit Store Indent Requisition
            </button>
        </div>
    );
}

// =========================================================================
// 5. ACCOUNTING, LEDGERS, DAYBOOK & GST REPORTS
// =========================================================================
function LedgersView({ store, notify }) {
    if (!store) return null;
    const [accTab, setAccTab] = useState('daybook');
    const [daybookData, setDaybookData] = useState(null);
    const [gstData, setGstData] = useState(null);
    const [h1Records, setH1Records] = useState([]);

    useEffect(() => {
        if (!store?.store_id) return;
        if (accTab === 'daybook') loadDaybook();
        if (accTab === 'gst') loadGst();
        if (accTab === 'schedule_h1') loadScheduleH1();
    }, [accTab, store?.store_id]);

    const loadDaybook = async () => {
        try {
            const res = await fetch(API_BASE + `accounting/daybook?store_id=${store.store_id}`);
            const data = await res.json();
            if (data.status) setDaybookData(data);
        } catch (e) {
            console.error(e);
        }
    };

    const loadGst = async () => {
        try {
            const res = await fetch(API_BASE + `accounting/gst-report?store_id=${store.store_id}`);
            const data = await res.json();
            if (data.status) setGstData(data);
        } catch (e) {
            console.error(e);
        }
    };

    const loadScheduleH1 = async () => {
        try {
            const res = await fetch(API_BASE + `compliance/schedule-h1-register?store_id=${store.store_id}`);
            const data = await res.json();
            if (data.status) setH1Records(data.records || []);
        } catch (e) {
            console.error(e);
        }
    };

    return (
        <div>
            <div className="d-flex flex-wrap gap-2 mb-3">
                <button className={`btn btn-sm ${accTab === 'daybook' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setAccTab('daybook')}>
                    <i className="bi bi-journal-check me-1"></i> Daily Cash Book / Day Book
                </button>
                <button className={`btn btn-sm ${accTab === 'gst' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setAccTab('gst')}>
                    <i className="bi bi-file-earmark-spreadsheet me-1"></i> GST Returns (GSTR-1 &amp; 2)
                </button>
                <button className={`btn btn-sm ${accTab === 'schedule_h1' ? 'btn-primary' : 'btn-outline-secondary'}`} onClick={() => setAccTab('schedule_h1')}>
                    <i className="bi bi-shield-check me-1"></i> Statutory Schedule H1 Register
                </button>
            </div>

            {accTab === 'daybook' && daybookData && (
                <div>
                    <div className="row g-3 mb-3">
                        <div className="col-md-3 col-sm-6">
                            <div className="counter-card p-3 border-start border-4 border-success">
                                <small className="text-muted fw-bold">TOTAL TODAY SALES</small>
                                <h4 className="fw-bolder text-dark mt-1">₹{daybookData.summary.total_sales.toLocaleString('en-IN')}</h4>
                                <small className="text-muted">{daybookData.summary.total_invoices} Bills</small>
                            </div>
                        </div>
                        <div className="col-md-3 col-sm-6">
                            <div className="counter-card p-3 border-start border-4 border-primary">
                                <small className="text-muted fw-bold">CASH IN DRAWER</small>
                                <h4 className="fw-bolder text-primary mt-1">₹{daybookData.summary.cash_in_drawer.toLocaleString('en-IN')}</h4>
                            </div>
                        </div>
                        <div className="col-md-3 col-sm-6">
                            <div className="counter-card p-3 border-start border-4 border-warning">
                                <small className="text-muted fw-bold">UPI / QR COLLECTIONS</small>
                                <h4 className="fw-bolder text-warning mt-1">₹{daybookData.summary.upi_collection.toLocaleString('en-IN')}</h4>
                            </div>
                        </div>
                        <div className="col-md-3 col-sm-6">
                            <div className="counter-card p-3 border-start border-4 border-danger">
                                <small className="text-muted fw-bold">IPD CREDIT BILLED</small>
                                <h4 className="fw-bolder text-danger mt-1">₹{daybookData.summary.ipd_credit.toLocaleString('en-IN')}</h4>
                            </div>
                        </div>
                    </div>

                    <div className="counter-card p-0 overflow-hidden">
                        <div className="p-3 border-bottom">
                            <h6 className="mb-0 fw-bold">Today's Transactions Journal</h6>
                        </div>
                        <div className="table-responsive">
                            <table className="table table-pos mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Time</th>
                                        <th>Patient (UHID)</th>
                                        <th>Doctor</th>
                                        <th>Mode</th>
                                        <th>CGST (₹)</th>
                                        <th>SGST (₹)</th>
                                        <th className="text-end">Net Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {daybookData.transactions.map((t) => (
                                        <tr key={t.sale_id}>
                                            <td className="fw-bold">{t.invoice_no}</td>
                                            <td>{t.sale_date.split(' ')[1]}</td>
                                            <td>{t.patient_name} {t.uhid ? `(${t.uhid})` : ''}</td>
                                            <td>{t.doctor_name}</td>
                                            <td><span className="badge bg-secondary">{t.payment_mode}</span></td>
                                            <td>₹{t.cgst_amount}</td>
                                            <td>₹{t.sgst_amount}</td>
                                            <td className="text-end fw-bold text-success">₹{t.net_amount}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}

            {accTab === 'gst' && gstData && (
                <div className="counter-card mb-3">
                    <h6 className="fw-bold mb-3"><i className="bi bi-file-earmark-spreadsheet text-primary me-1"></i> GSTR-1 Outward Sales Summary (HSN-wise)</h6>
                    <div className="table-responsive">
                        <table className="table table-pos mb-0">
                            <thead>
                                <tr>
                                    <th>HSN Code</th>
                                    <th>GST Slab</th>
                                    <th>Taxable Value (₹)</th>
                                    <th>CGST (₹)</th>
                                    <th>SGST (₹)</th>
                                    <th>IGST (₹)</th>
                                    <th className="text-end">Total Invoice Value (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {gstData.gstr1_sales.map((g, i) => (
                                    <tr key={i}>
                                        <td><code>{g.hsn_code}</code></td>
                                        <td><span className="badge bg-primary">{g.gst_rate}%</span></td>
                                        <td>₹{Number(g.taxable_val).toFixed(2)}</td>
                                        <td>₹{Number(g.cgst_val).toFixed(2)}</td>
                                        <td>₹{Number(g.sgst_val).toFixed(2)}</td>
                                        <td>₹{Number(g.igst_val).toFixed(2)}</td>
                                        <td className="text-end fw-bold">₹{Number(g.total_val).toFixed(2)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {accTab === 'schedule_h1' && (
                <div className="counter-card">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 className="fw-bold mb-0"><i className="bi bi-shield-check text-success me-1"></i> Statutory Schedule H1 Drug Register</h6>
                            <small className="text-muted">Mandatory under Indian Drugs &amp; Cosmetics Rules (4th Amendment 2013). Retain records for 3 years.</small>
                        </div>
                        <button className="btn btn-sm btn-outline-dark" onClick={() => window.print()}>
                            <i className="bi bi-printer me-1"></i> Export / Print Register
                        </button>
                    </div>

                    <div className="table-responsive">
                        <table className="table table-pos table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bill No</th>
                                    <th>Patient Name &amp; Address</th>
                                    <th>Prescribing Doctor &amp; Reg No</th>
                                    <th>Medicine Name</th>
                                    <th>Batch No</th>
                                    <th>Qty Supplied</th>
                                </tr>
                            </thead>
                            <tbody>
                                {h1Records.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="text-center py-4 text-muted">No Schedule H1 medicines dispensed in this period.</td>
                                    </tr>
                                ) : (
                                    h1Records.map((h, i) => (
                                        <tr key={i}>
                                            <td>{h.sale_date.split(' ')[0]}</td>
                                            <td className="fw-bold">{h.invoice_no}</td>
                                            <td>{h.patient_name} {h.patient_address ? `(${h.patient_address})` : ''}</td>
                                            <td>{h.doctor_name} {h.doctor_reg_no ? `(Reg: ${h.doctor_reg_no})` : ''}</td>
                                            <td className="fw-bold text-danger">{h.item_name}</td>
                                            <td><code>{h.batch_no}</code></td>
                                            <td className="fw-bold">{h.qty}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
}

// =========================================================================
// 6. STORE & STATUTORY LICENSING SETUP
// =========================================================================
function SettingsView({ store, onStoreSaved, notify }) {
    const [formData, setFormData] = useState({
        store_id: store ? store.store_id : 0,
        store_code: store ? store.store_code : '',
        store_slug: store ? store.store_slug : '',
        store_name: store ? store.store_name : '',
        building_name: store ? store.building_name : '',
        floor_no: store ? store.floor_no : '',
        room_no: store ? store.room_no : '',
        drug_license_no_20b: store ? store.drug_license_no_20b : '',
        drug_license_no_21b: store ? store.drug_license_no_21b : '',
        drug_license_no_20f_x: store ? store.drug_license_no_20f_x : '',
        gstin: store ? store.gstin : '',
        pan_no: store ? store.pan_no : '',
        fssai_no: store ? store.fssai_no : '',
        state_code: store ? store.state_code : '07',
        state_name: store ? store.state_name : 'Delhi',
        registered_pharmacist_name: store ? store.registered_pharmacist_name : '',
        pharmacist_reg_no: store ? store.pharmacist_reg_no : '',
        pharmacist_hpr_id: store ? (store.pharmacist_hpr_id || '') : '',
        abdm_hfr_id: store ? (store.abdm_hfr_id || '') : '',
        abdm_hip_id: store ? (store.abdm_hip_id || '') : '',
        invoice_prefix: store ? store.invoice_prefix : 'INV/',
        next_invoice_no: store ? store.next_invoice_no : 1001,
        upi_id: store ? store.upi_id : '',
        terms_conditions: store ? store.terms_conditions : ''
    });

    const handleChange = (field, val) => {
        setFormData({ ...formData, [field]: val });
    };

    const handleSave = async (e) => {
        e.preventDefault();
        try {
            const res = await fetch(API_BASE + 'stores/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const data = await res.json();
            if (data.status) {
                notify('Store & statutory licensing updated successfully!', 'success');
                onStoreSaved();
            } else {
                notify(data.message || 'Failed to update store.', 'error');
            }
        } catch (e) {
            notify('Save error: ' + e.message, 'error');
        }
    };

    return (
        <div className="counter-card">
            <h5 className="fw-bold mb-3"><i className="bi bi-building-gear text-primary me-1"></i> Hospital Pharmacy Store &amp; Indian Licensing Configuration</h5>
            <form onSubmit={handleSave}>
                <div className="row g-3 mb-3">
                    <div className="col-md-3">
                        <label className="form-label small fw-bold">Store Code</label>
                        <input type="text" className="form-control form-control-sm" required value={formData.store_code} onChange={(e) => handleChange('store_code', e.target.value)} />
                    </div>
                    <div className="col-md-3">
                        <label className="form-label small fw-bold">URL Slug (Direct Link)</label>
                        <input type="text" className="form-control form-control-sm" required value={formData.store_slug} onChange={(e) => handleChange('store_slug', e.target.value)} />
                    </div>
                    <div className="col-md-6">
                        <label className="form-label small fw-bold">Store / Pharmacy Name</label>
                        <input type="text" className="form-control form-control-sm" required value={formData.store_name} onChange={(e) => handleChange('store_name', e.target.value)} />
                    </div>

                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Building Name</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. OPD Block B" value={formData.building_name} onChange={(e) => handleChange('building_name', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Floor</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. 1st Floor" value={formData.floor_no} onChange={(e) => handleChange('floor_no', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Room / Counter No</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. Counter 2" value={formData.room_no} onChange={(e) => handleChange('room_no', e.target.value)} />
                    </div>
                </div>

                <h6 className="fw-bold text-primary mt-4 mb-2"><i className="bi bi-shield-check me-1"></i> Indian Drug &amp; Tax Statutory Licenses</h6>
                <div className="row g-3 mb-3">
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Drug License Form 20-B (Allopathic)</label>
                        <input type="text" className="form-control form-control-sm" placeholder="DL-20B-DEL-XXXXX" value={formData.drug_license_no_20b} onChange={(e) => handleChange('drug_license_no_20b', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Drug License Form 21-B (Schedule C/C1)</label>
                        <input type="text" className="form-control form-control-sm" placeholder="DL-21B-DEL-XXXXX" value={formData.drug_license_no_21b} onChange={(e) => handleChange('drug_license_no_21b', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Drug License Form 20-F (Schedule X)</label>
                        <input type="text" className="form-control form-control-sm" placeholder="DL-20F-DEL-XXXXX" value={formData.drug_license_no_20f_x} onChange={(e) => handleChange('drug_license_no_20f_x', e.target.value)} />
                    </div>

                    <div className="col-md-4">
                        <label className="form-label small fw-bold">GSTIN (15-Digit)</label>
                        <input type="text" className="form-control form-control-sm text-uppercase" placeholder="07AAAAA0000A1Z5" value={formData.gstin} onChange={(e) => handleChange('gstin', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">PAN Number</label>
                        <input type="text" className="form-control form-control-sm text-uppercase" placeholder="AAAAA0000A" value={formData.pan_no} onChange={(e) => handleChange('pan_no', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">FSSAI License No</label>
                        <input type="text" className="form-control form-control-sm" placeholder="100XXXXXXXXXX" value={formData.fssai_no} onChange={(e) => handleChange('fssai_no', e.target.value)} />
                    </div>
                </div>

                <h6 className="fw-bold text-primary mt-4 mb-2"><i className="bi bi-person-badge me-1"></i> Pharmacist &amp; POS Bill Configuration</h6>
                <div className="row g-3 mb-3">
                    <div className="col-md-6">
                        <label className="form-label small fw-bold">Registered Pharmacist Name</label>
                        <input type="text" className="form-control form-control-sm" placeholder="Pharmacist Name" value={formData.registered_pharmacist_name} onChange={(e) => handleChange('registered_pharmacist_name', e.target.value)} />
                    </div>
                    <div className="col-md-6">
                        <label className="form-label small fw-bold">State Pharmacy Council Reg No</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. DPC-Reg-45892" value={formData.pharmacist_reg_no} onChange={(e) => handleChange('pharmacist_reg_no', e.target.value)} />
                    </div>

                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Bill Series Prefix</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. OPD1/26-27/" value={formData.invoice_prefix} onChange={(e) => handleChange('invoice_prefix', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Next Sequence Number</label>
                        <input type="number" className="form-control form-control-sm" value={formData.next_invoice_no} onChange={(e) => handleChange('next_invoice_no', e.target.value)} />
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Store UPI ID (For Dynamic QR)</label>
                        <input type="text" className="form-control form-control-sm" placeholder="hospital@upi" value={formData.upi_id} onChange={(e) => handleChange('upi_id', e.target.value)} />
                    </div>
                </div>

                <h6 className="fw-bold text-primary mt-4 mb-2"><i className="bi bi-shield-fill-check me-1"></i> Ayushman Bharat Digital Mission (ABDM) Registry Configuration</h6>
                <div className="row g-3 mb-3">
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">ABDM Health Facility Registry (HFR ID)</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. IN0710001234" value={formData.abdm_hfr_id} onChange={(e) => handleChange('abdm_hfr_id', e.target.value)} />
                        <small className="text-muted" style={{ fontSize: '0.7rem' }}>Facility ID registered in National Health Authority HFR</small>
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Health Information Provider (HIP ID)</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. IN0710001234-HIP" value={formData.abdm_hip_id} onChange={(e) => handleChange('abdm_hip_id', e.target.value)} />
                        <small className="text-muted" style={{ fontSize: '0.7rem' }}>Identifier configured in ABDM Gateway Bridge</small>
                    </div>
                    <div className="col-md-4">
                        <label className="form-label small fw-bold">Pharmacist HPR ID (Healthcare Professional)</label>
                        <input type="text" className="form-control form-control-sm" placeholder="e.g. 91-8822-4411-9901" value={formData.pharmacist_hpr_id} onChange={(e) => handleChange('pharmacist_hpr_id', e.target.value)} />
                        <small className="text-muted" style={{ fontSize: '0.7rem' }}>National Pharmacist ID from HPR Portal</small>
                    </div>
                </div>

                <div className="mb-3">
                    <label className="form-label small fw-bold">Terms &amp; Conditions (Printed on Invoice Footer)</label>
                    <textarea className="form-control form-control-sm" rows="2" value={formData.terms_conditions} onChange={(e) => handleChange('terms_conditions', e.target.value)}></textarea>
                </div>

                <button type="submit" className="btn btn-primary-custom">
                    <i className="bi bi-save me-1"></i> Save Store &amp; Licensing Details
                </button>
            </form>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
    <ErrorBoundary>
        <App />
    </ErrorBoundary>
);
