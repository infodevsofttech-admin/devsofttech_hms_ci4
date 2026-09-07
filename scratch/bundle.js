const { useState, useEffect, useRef } = React;
const API_BASE = "/api/v1/medical-store/";
function getInitialStoreSlug() {
  if (window.INITIAL_STORE_SLUG) {
    return window.INITIAL_STORE_SLUG;
  }
  const path = window.location.pathname;
  const match = path.match(/(?:MedicalStore|app\/medical-store)\/([a-zA-Z0-9_\-]+)/i);
  return match ? match[1] : null;
}
function App() {
  const [stores, setStores] = useState([]);
  const [activeStoreId, setActiveStoreId] = useState(null);
  const [activeTab, setActiveTab] = useState("pos");
  const [notification, setNotification] = useState(null);
  const [isDeviceAuthorized, setIsDeviceAuthorized] = useState(false);
  const [deviceToken, setDeviceToken] = useState("");
  const [machineName, setMachineName] = useState("Counter PC " + (navigator.platform || "Desktop"));
  const [secKeyInput, setSecKeyInput] = useState("");
  const [verifyMethod, setVerifyMethod] = useState("key");
  const [isVerifying, setIsVerifying] = useState(false);
  const storeSlugFromUrl = getInitialStoreSlug();
  const activeStore = stores.find((s) => s.store_id == activeStoreId) || stores[0] || null;
  useEffect(() => {
    fetchStores();
  }, []);
  useEffect(() => {
    if (activeStore) {
      checkMachineAuthorization(activeStore.store_id);
    }
  }, [activeStoreId, stores]);
  const fetchStores = async () => {
    try {
      const url = storeSlugFromUrl ? `${API_BASE}stores?slug=${encodeURIComponent(storeSlugFromUrl)}` : `${API_BASE}stores`;
      const res = await fetch(url);
      const data = await res.json();
      if (data.status && data.stores && data.stores.length > 0) {
        setStores(data.stores);
        if (storeSlugFromUrl) {
          const matched = data.stores.find((s) => s.store_slug === storeSlugFromUrl || s.store_code === storeSlugFromUrl);
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
      console.error("Failed to load stores:", e);
    }
  };
  const checkMachineAuthorization = async (storeId) => {
    const savedToken = localStorage.getItem("mst_device_token_" + storeId);
    if (!savedToken) {
      setIsDeviceAuthorized(false);
      return;
    }
    try {
      const res = await fetch(API_BASE + "device/check", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ store_id: storeId, device_token: savedToken })
      });
      const data = await res.json();
      if (data.status && data.authorized) {
        setIsDeviceAuthorized(true);
        setDeviceToken(savedToken);
        if (data.machine_name) setMachineName(data.machine_name);
      } else {
        localStorage.removeItem("mst_device_token_" + storeId);
        setIsDeviceAuthorized(false);
      }
    } catch (e) {
      console.error("Device authorization check error:", e);
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
        security_key: verifyMethod === "key" ? secKeyInput : "",
        otp: verifyMethod === "otp" ? secKeyInput : ""
      };
      const res = await fetch(API_BASE + "device/verify", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status && data.device_token) {
        localStorage.setItem("mst_device_token_" + activeStore.store_id, data.device_token);
        setDeviceToken(data.device_token);
        setIsDeviceAuthorized(true);
        notify(data.message, "success");
      } else {
        alert(data.message || "Verification failed. Please check with HMS Admin.");
      }
    } catch (e2) {
      alert("Verification request failed: " + e2.message);
    } finally {
      setIsVerifying(false);
    }
  };
  const notify = (msg, type = "success") => {
    setNotification({ msg, type });
    setTimeout(() => setNotification(null), 4e3);
  };
  return /* @__PURE__ */ React.createElement("div", null, !isDeviceAuthorized && activeStore && /* @__PURE__ */ React.createElement("div", { className: "machine-lock-backdrop" }, /* @__PURE__ */ React.createElement("div", { className: "card shadow-lg border-0", style: { maxWidth: 460, width: "100%", borderRadius: 16 } }, /* @__PURE__ */ React.createElement("div", { className: "card-header bg-dark text-white p-3 text-center border-0", style: { borderTopLeftRadius: 16, borderTopRightRadius: 16 } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-lock-fill text-warning fs-1 d-block mb-1" }), /* @__PURE__ */ React.createElement("h5", { className: "fw-bold mb-0" }, "Terminal Machine Verification"), /* @__PURE__ */ React.createElement("small", { className: "text-slate-300", style: { color: "#94a3b8" } }, "Counter Authorization Required")), /* @__PURE__ */ React.createElement("div", { className: "card-body p-4" }, /* @__PURE__ */ React.createElement("div", { className: "text-center mb-3" }, /* @__PURE__ */ React.createElement("h6", { className: "fw-bold text-dark" }, activeStore.store_name), /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-geo-alt me-1" }), activeStore.building_name || "Main Hospital", " \u2022 ", activeStore.floor_no || "Ground Floor"), /* @__PURE__ */ React.createElement("span", { className: "badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mt-1" }, "Link: MedicalStore/", activeStore.store_slug)), /* @__PURE__ */ React.createElement("p", { className: "small text-muted text-center mb-3" }, "This computer terminal is accessing the counter for the first time. Please enter the ", /* @__PURE__ */ React.createElement("strong", null, "Security Key"), " or ", /* @__PURE__ */ React.createElement("strong", null, "6-digit OTP"), " provided by HMS Admin."), /* @__PURE__ */ React.createElement("form", { onSubmit: submitDeviceVerification }, /* @__PURE__ */ React.createElement("div", { className: "mb-3" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold text-dark mb-1" }, "Computer / Machine Name"), /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control form-control-sm",
      value: machineName,
      onChange: (e) => setMachineName(e.target.value),
      required: true
    }
  )), /* @__PURE__ */ React.createElement("div", { className: "mb-2" }, /* @__PURE__ */ React.createElement("div", { className: "btn-group btn-group-sm w-100 mb-2" }, /* @__PURE__ */ React.createElement(
    "button",
    {
      type: "button",
      className: `btn ${verifyMethod === "key" ? "btn-primary" : "btn-outline-secondary"}`,
      onClick: () => setVerifyMethod("key")
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-key-fill me-1" }),
    " Admin Security Key"
  ), /* @__PURE__ */ React.createElement(
    "button",
    {
      type: "button",
      className: `btn ${verifyMethod === "otp" ? "btn-primary" : "btn-outline-secondary"}`,
      onClick: () => setVerifyMethod("otp")
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-123 me-1" }),
    " 6-Digit Counter OTP"
  )), /* @__PURE__ */ React.createElement(
    "input",
    {
      type: verifyMethod === "otp" ? "number" : "text",
      className: "form-control text-center fw-bold fs-5 letter-spacing-1",
      placeholder: verifyMethod === "key" ? "e.g. HMS-XXXXXX-XXX" : "Enter 6-digit OTP",
      value: secKeyInput,
      onChange: (e) => setSecKeyInput(e.target.value),
      required: true
    }
  )), /* @__PURE__ */ React.createElement(
    "button",
    {
      type: "submit",
      className: "btn btn-success-custom w-100 py-2.5 mt-3 fw-bold",
      disabled: isVerifying
    },
    isVerifying ? /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("i", { className: "bi bi-hourglass-split me-1" }), " Verifying Terminal...") : /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-check me-1" }), " Verify Machine & Unlock Counter")
  ))))), /* @__PURE__ */ React.createElement("header", { className: "navbar-store" }, /* @__PURE__ */ React.createElement("div", { className: "container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex align-items-center gap-3" }, /* @__PURE__ */ React.createElement("div", { className: "bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center", style: { width: 40, height: 40 } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-capsule fs-5" })), /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "d-flex align-items-center gap-2" }, /* @__PURE__ */ React.createElement("h6", { className: "mb-0 fw-bold text-white fs-5" }, activeStore ? activeStore.store_name : "Medical Store Counter"), activeStore && /* @__PURE__ */ React.createElement("span", { className: "badge bg-info-subtle text-info border border-info px-2 py-0.5 rounded-pill", style: { fontSize: "0.65rem" } }, "/", activeStore.store_slug || activeStore.store_code)), /* @__PURE__ */ React.createElement("small", { className: "text-slate-400", style: { color: "#94a3b8" } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-geo-alt me-1" }), activeStore ? `${activeStore.building_name || "Main Building"} \u2022 ${activeStore.floor_no || "Ground Floor"}` : "Counter"))), activeStore && /* @__PURE__ */ React.createElement("div", { className: "d-none d-md-flex align-items-center gap-2" }, activeStore.abdm_hfr_id && /* @__PURE__ */ React.createElement("span", { className: "badge bg-success-subtle text-success border border-success px-2 py-1", title: "ABDM Health Facility Registry" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-fill-check me-1" }), " HFR: ", activeStore.abdm_hfr_id), /* @__PURE__ */ React.createElement("span", { className: "badge-license", title: "Retail Allopathic Drug License" }, "DL 20B: ", activeStore.drug_license_no_20b || "N/A"), /* @__PURE__ */ React.createElement("span", { className: "badge-license", title: "Schedule C/C1 Drug License" }, "DL 21B: ", activeStore.drug_license_no_21b || "N/A"), /* @__PURE__ */ React.createElement("span", { className: "badge-gst", title: "GSTIN" }, "GSTIN: ", activeStore.gstin || "N/A"), isDeviceAuthorized && /* @__PURE__ */ React.createElement("span", { className: "badge bg-success text-white rounded-pill px-2.5 py-1", style: { fontSize: "0.7rem" } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-check me-1" }), " Terminal Verified")), /* @__PURE__ */ React.createElement("div", { className: "d-flex align-items-center gap-2" }, /* @__PURE__ */ React.createElement(
    "select",
    {
      className: "form-select form-select-sm bg-dark text-white border-secondary",
      style: { minWidth: 200 },
      value: activeStoreId || "",
      onChange: (e) => setActiveStoreId(Number(e.target.value))
    },
    stores.map((s) => /* @__PURE__ */ React.createElement("option", { key: s.store_id, value: s.store_id }, s.store_name, " (", s.store_slug, ")"))
  ), /* @__PURE__ */ React.createElement("a", { href: "/app", className: "btn btn-sm btn-outline-light", title: "Return to App Hub" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-grid-fill" }))))), notification && /* @__PURE__ */ React.createElement("div", { className: `alert alert-${notification.type === "error" ? "danger" : "success"} alert-dismissible fade show m-2 p-2 px-3`, role: "alert" }, /* @__PURE__ */ React.createElement("i", { className: `bi bi-${notification.type === "error" ? "exclamation-triangle" : "check-circle"}-fill me-2` }), notification.msg, /* @__PURE__ */ React.createElement("button", { type: "button", className: "btn-close p-2", onClick: () => setNotification(null) })), /* @__PURE__ */ React.createElement("nav", { className: "nav-tabs-custom d-flex flex-wrap" }, /* @__PURE__ */ React.createElement("button", { className: `nav-link ${activeTab === "pos" ? "active" : ""}`, onClick: () => setActiveTab("pos") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-cart3 me-1" }), " POS Counter Desk"), /* @__PURE__ */ React.createElement("button", { className: `nav-link ${activeTab === "inventory" ? "active" : ""}`, onClick: () => setActiveTab("inventory") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-boxes me-1" }), " Live Stock & Batches"), /* @__PURE__ */ React.createElement("button", { className: `nav-link ${activeTab === "purchase" ? "active" : ""}`, onClick: () => setActiveTab("purchase") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-box-arrow-in-down me-1" }), " Purchases & Marg Import"), /* @__PURE__ */ React.createElement("button", { className: `nav-link ${activeTab === "transfer" ? "active" : ""}`, onClick: () => setActiveTab("transfer") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-arrow-left-right me-1" }), " Inter-Store Indents"), /* @__PURE__ */ React.createElement("button", { className: `nav-link ${activeTab === "ledgers" ? "active" : ""}`, onClick: () => setActiveTab("ledgers") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-journal-text me-1" }), " Ledgers, Daybook & GST"), /* @__PURE__ */ React.createElement("button", { className: `nav-link ${activeTab === "settings" ? "active" : ""}`, onClick: () => setActiveTab("settings") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-gear me-1" }), " Store & License Setup")), /* @__PURE__ */ React.createElement("main", { className: "container-fluid py-3" }, activeTab === "pos" && /* @__PURE__ */ React.createElement(PosCounter, { store: activeStore, notify }), activeTab === "inventory" && /* @__PURE__ */ React.createElement(InventoryView, { store: activeStore, notify }), activeTab === "purchase" && /* @__PURE__ */ React.createElement(PurchaseView, { store: activeStore, notify }), activeTab === "transfer" && /* @__PURE__ */ React.createElement(TransferView, { store: activeStore, stores, notify }), activeTab === "ledgers" && /* @__PURE__ */ React.createElement(LedgersView, { store: activeStore, notify }), activeTab === "settings" && /* @__PURE__ */ React.createElement(SettingsView, { store: activeStore, onStoreSaved: fetchStores, notify })));
}
function PosCounter({ store, notify }) {
  const [patientQuery, setPatientQuery] = useState("");
  const [patientResults, setPatientResults] = useState([]);
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [patientType, setPatientType] = useState("Walk-in");
  const [walkinAbhaId, setWalkinAbhaId] = useState("");
  const [walkinAbhaAddress, setWalkinAbhaAddress] = useState("");
  const [showFhirModal, setShowFhirModal] = useState(false);
  const [fhirBundleData, setFhirBundleData] = useState(null);
  const [isLoadingFhir, setIsLoadingFhir] = useState(false);
  const [copiedFhir, setCopiedFhir] = useState(false);
  const [doctorName, setDoctorName] = useState("");
  const [doctorRegNo, setDoctorRegNo] = useState("");
  const [itemQuery, setItemQuery] = useState("");
  const [itemResults, setItemResults] = useState([]);
  const [isSearchingItems, setIsSearchingItems] = useState(false);
  const [cart, setCart] = useState([]);
  const [paymentMode, setPaymentMode] = useState("Cash");
  const [paymentRef, setPaymentRef] = useState("");
  const [lastInvoice, setLastInvoice] = useState(null);
  const [printFormat, setPrintFormat] = useState("thermal");
  const [isProcessing, setIsProcessing] = useState(false);
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
    setPatientQuery(pt.full_name + " (" + pt.uhid + ")");
    setWalkinAbhaId(pt.abha_id || "");
    setWalkinAbhaAddress(pt.abha_address || "");
    if (pt.has_active_ipd && pt.active_ipd) {
      setPatientType("IPD");
      setDoctorName(pt.active_ipd.doctor_name || "");
      setPrintFormat("a4");
    } else if (pt.has_active_opd && pt.active_opd) {
      setPatientType("OPD");
      setDoctorName(pt.active_opd.doctor_name || "");
      setPrintFormat("a5");
    } else {
      setPatientType("Walk-in");
      setPrintFormat("thermal");
    }
  };
  const resetPatient = () => {
    setSelectedPatient(null);
    setPatientQuery("");
    setPatientType("Walk-in");
    setDoctorName("");
    setDoctorRegNo("");
    setWalkinAbhaId("");
    setWalkinAbhaAddress("");
    setPrintFormat("thermal");
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
        notify(data.message || "Unable to retrieve ABDM FHIR bundle", "error");
        setShowFhirModal(false);
      }
    } catch (e) {
      notify("FHIR Fetch error: " + e.message, "error");
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
  const loadDoctorPrescription = async () => {
    if (!selectedPatient) return;
    const encType = selectedPatient.has_active_ipd ? "ipd" : selectedPatient.has_active_opd ? "opd" : null;
    const encId = selectedPatient.has_active_ipd ? selectedPatient.active_ipd.ipd_id : selectedPatient.has_active_opd ? selectedPatient.active_opd.opd_id : null;
    if (!encType || !encId) {
      notify("No active OPD or IPD prescription found for this patient.", "error");
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
        notify(`Imported ${addedCount} prescribed medicines into cart!`, "success");
      } else {
        notify("No electronic medicines found for this visit.", "error");
      }
    } catch (e) {
      notify("Failed to load prescription: " + e.message, "error");
    }
  };
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
      notify("No active batch available for " + item.item_name, "error");
      return;
    }
    if (batch.is_expired) {
      notify(`STATUTORY BLOCK: Batch ${batch.batch_no} is expired! Cannot be sold.`, "error");
      return;
    }
    const existingIdx = cart.findIndex((c) => c.item_id === item.item_id && c.batch_id === batch.batch_id);
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
        hsn_code: item.hsn_code || "3004",
        gst_rate: batch.gst_rate || 12,
        batch_id: batch.batch_id,
        batch_no: batch.batch_no,
        expiry_display: batch.expiry_display,
        expiry_date: batch.expiry_date,
        current_qty: batch.current_qty,
        unit_mrp: batch.mrp,
        discount_pct: 0,
        qty
      }]);
    }
    setItemResults([]);
    setItemQuery("");
    if (cart.length + 1 > 5 && printFormat === "thermal") {
      setPrintFormat("a5");
    }
  };
  const updateCartItem = (idx, field, val) => {
    const updated = [...cart];
    updated[idx][field] = Number(val);
    setCart(updated);
  };
  const removeCartItem = (idx) => {
    setCart(cart.filter((_, i) => i !== idx));
  };
  const grossTotal = cart.reduce((acc, it) => acc + it.qty * it.unit_mrp, 0);
  const discountTotal = cart.reduce((acc, it) => acc + it.qty * it.unit_mrp * (it.discount_pct || 0) / 100, 0);
  const netBeforeRound = grossTotal - discountTotal;
  const netPayable = Math.round(netBeforeRound);
  const roundOff = (netPayable - netBeforeRound).toFixed(2);
  const processSale = async () => {
    if (cart.length === 0) {
      notify("Cart is empty!", "error");
      return;
    }
    if (paymentMode === "IPD_Credit" && (!selectedPatient || !selectedPatient.has_active_ipd)) {
      notify("IPD Credit mode is only allowed for admitted IPD patients.", "error");
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
      patient_name: selectedPatient ? selectedPatient.full_name : patientQuery || "Walk-in Customer",
      patient_mobile: selectedPatient ? selectedPatient.mobile : "",
      patient_address: selectedPatient ? selectedPatient.address : "",
      abha_id: selectedPatient?.abha_id || walkinAbhaId || null,
      abha_address: selectedPatient?.abha_address || walkinAbhaAddress || null,
      age: selectedPatient ? selectedPatient.age : "",
      gender: selectedPatient ? selectedPatient.gender : "",
      doctor_name: doctorName || (selectedPatient?.active_opd?.doctor_name || selectedPatient?.active_ipd?.doctor_name || "Dr. Consultant"),
      doctor_reg_no: doctorRegNo,
      payment_mode: paymentMode,
      payment_reference: paymentRef,
      items: cart.map((it) => ({
        item_id: it.item_id,
        batch_id: it.batch_id,
        qty: it.qty,
        discount_pct: it.discount_pct || 0
      }))
    };
    try {
      const res = await fetch(API_BASE + "sales/save", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status) {
        notify(`Invoice ${data.invoice_no} generated successfully! Amount: \u20B9${data.net_amount}`, "success");
        fetchInvoiceDetails(data.sale_id);
        setCart([]);
        resetPatient();
      } else {
        notify(data.message || "Failed to complete sale.", "error");
      }
    } catch (e) {
      notify("Sale error: " + e.message, "error");
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
  return /* @__PURE__ */ React.createElement("div", { className: "row g-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-lg-8" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card position-relative" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex align-items-center gap-2" }, /* @__PURE__ */ React.createElement("span", { className: "fw-bold text-dark fs-6" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-person-fill text-primary me-1" }), " Patient & Prescription"), /* @__PURE__ */ React.createElement("span", { className: `badge ${patientType === "IPD" ? "bg-danger" : patientType === "OPD" ? "bg-primary" : "bg-secondary"}` }, patientType)), selectedPatient && /* @__PURE__ */ React.createElement("div", { className: "d-flex gap-2" }, (selectedPatient.has_active_opd || selectedPatient.has_active_ipd) && /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-success py-1 px-2.5", onClick: loadDoctorPrescription }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-file-earmark-medical me-1" }), " 1-Click Load Doctor Rx"), /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-secondary py-1 px-2", onClick: resetPatient }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-x-circle me-1" }), " Clear"))), /* @__PURE__ */ React.createElement("div", { className: "row g-2" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-7 position-relative" }, /* @__PURE__ */ React.createElement("div", { className: "input-group input-group-sm" }, /* @__PURE__ */ React.createElement("span", { className: "input-group-text bg-light" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-search" })), /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control",
      placeholder: "Search by UHID, Mobile, Name, OPD No, IPD No...",
      value: patientQuery,
      onChange: (e) => searchPatients(e.target.value)
    }
  )), patientResults.length > 0 && /* @__PURE__ */ React.createElement("div", { className: "search-results-dropdown" }, patientResults.map((p) => /* @__PURE__ */ React.createElement("div", { key: p.patient_id, className: "search-item-row d-flex justify-content-between align-items-center", onClick: () => selectPatient(p) }, /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "fw-bold text-dark" }, p.full_name, " ", /* @__PURE__ */ React.createElement("small", { className: "text-primary" }, "(", p.uhid, ")"), (p.abha_id || p.abha_address) && /* @__PURE__ */ React.createElement("span", { className: "badge bg-success-subtle text-success border border-success ms-2", style: { fontSize: "0.65rem" } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-fill-check me-1" }), "ABHA: ", p.abha_id || p.abha_address)), /* @__PURE__ */ React.createElement("small", { className: "text-muted" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-telephone me-1" }), p.mobile || "No Mobile", " \u2022 ", p.gender, " \u2022 ", p.age)), /* @__PURE__ */ React.createElement("div", { className: "text-end" }, p.has_active_ipd && /* @__PURE__ */ React.createElement("span", { className: "badge bg-danger d-block mb-1" }, "IPD Admitted: ", p.active_ipd.bed_info?.bed_no || "Bed"), p.has_active_opd && /* @__PURE__ */ React.createElement("span", { className: "badge bg-info d-block" }, "OPD Visit Today")))))), /* @__PURE__ */ React.createElement("div", { className: "col-md-5" }, /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control form-control-sm",
      placeholder: "Consultant Doctor Name",
      value: doctorName,
      onChange: (e) => setDoctorName(e.target.value)
    }
  ))), /* @__PURE__ */ React.createElement("div", { className: "row g-2 mt-1" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-6" }, /* @__PURE__ */ React.createElement("div", { className: "input-group input-group-sm" }, /* @__PURE__ */ React.createElement("span", { className: "input-group-text bg-light text-success fw-bold", style: { fontSize: "0.72rem" } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-check me-1" }), " ABHA No"), /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control form-control-sm",
      placeholder: "14-Digit ABHA (e.g. 12-3456-7890-1234)",
      value: walkinAbhaId,
      onChange: (e) => setWalkinAbhaId(e.target.value)
    }
  ))), /* @__PURE__ */ React.createElement("div", { className: "col-md-6" }, /* @__PURE__ */ React.createElement("div", { className: "input-group input-group-sm" }, /* @__PURE__ */ React.createElement("span", { className: "input-group-text bg-light text-success fw-bold", style: { fontSize: "0.72rem" } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-at me-1" }), " ABHA Address"), /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control form-control-sm",
      placeholder: "username@abdm",
      value: walkinAbhaAddress,
      onChange: (e) => setWalkinAbhaAddress(e.target.value)
    }
  )))), selectedPatient && /* @__PURE__ */ React.createElement("div", { className: "mt-2 p-2 bg-light rounded-2 d-flex flex-wrap gap-3 align-items-center", style: { fontSize: "0.8rem" } }, /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("strong", null, "Name:"), " ", selectedPatient.full_name), /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("strong", null, "UHID:"), " ", /* @__PURE__ */ React.createElement("code", { className: "text-primary" }, selectedPatient.uhid)), /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("strong", null, "Gender/Age:"), " ", selectedPatient.gender, ", ", selectedPatient.age), (selectedPatient.abha_id || selectedPatient.abha_address) && /* @__PURE__ */ React.createElement("span", { className: "badge bg-success text-white py-1 px-2", style: { fontSize: "0.75rem" } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-fill-check me-1" }), " ABDM Linked: ", selectedPatient.abha_id || "", " ", selectedPatient.abha_address ? `(${selectedPatient.abha_address})` : ""), selectedPatient.has_active_ipd && /* @__PURE__ */ React.createElement("span", { className: "text-danger fw-bold" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-hospital me-1" }), selectedPatient.active_ipd.bed_info?.ward_name || "Ward", " / ", selectedPatient.active_ipd.bed_info?.bed_no || "Bed", " (Dr. ", selectedPatient.active_ipd.doctor_name, ")"), selectedPatient.has_active_opd && !selectedPatient.has_active_ipd && /* @__PURE__ */ React.createElement("span", { className: "text-primary fw-bold" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-stethoscope me-1" }), "OPD Dr. ", selectedPatient.active_opd.doctor_name))), /* @__PURE__ */ React.createElement("div", { className: "counter-card position-relative" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex align-items-center gap-2 mb-2" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-upc-scan text-primary fs-5" }), /* @__PURE__ */ React.createElement("span", { className: "fw-bold fs-6" }, "Medicine Lookup & Barcode Dispenser")), /* @__PURE__ */ React.createElement("div", { className: "input-group" }, /* @__PURE__ */ React.createElement("span", { className: "input-group-text bg-light" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-capsule" })), /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control",
      placeholder: "Type Medicine Name, Generic Molecule, or Scan Box Barcode...",
      value: itemQuery,
      onChange: (e) => searchItems(e.target.value)
    }
  ), isSearchingItems && /* @__PURE__ */ React.createElement("span", { className: "input-group-text bg-light" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-hourglass-split" }))), itemResults.length > 0 && /* @__PURE__ */ React.createElement("div", { className: "search-results-dropdown" }, itemResults.map((it) => /* @__PURE__ */ React.createElement("div", { key: it.item_id, className: "search-item-row" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between align-items-center mb-1" }, /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("span", { className: "fw-bold text-dark fs-6" }, it.item_name), /* @__PURE__ */ React.createElement("span", { className: "badge bg-secondary ms-2", style: { fontSize: "0.65rem" } }, it.unit_pack), it.drug_schedule && it.drug_schedule !== "OTC" && /* @__PURE__ */ React.createElement("span", { className: "badge bg-warning-subtle text-warning border border-warning ms-1", style: { fontSize: "0.65rem" } }, it.drug_schedule)), /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("span", { className: "text-muted me-2", style: { fontSize: "0.75rem" } }, "Total Stock: ", it.total_stock))), /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block mb-1" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-diagram-3 me-1" }), it.generic_name, " \u2022 HSN: ", it.hsn_code), it.batches && it.batches.length > 0 ? /* @__PURE__ */ React.createElement("div", { className: "d-flex flex-wrap gap-2 mt-1" }, it.batches.map((b) => /* @__PURE__ */ React.createElement(
    "button",
    {
      key: b.batch_id,
      className: `btn btn-sm ${b.is_expired ? "btn-outline-danger disabled" : "btn-outline-dark"} d-flex align-items-center gap-1 py-0.5 px-2`,
      style: { fontSize: "0.75rem" },
      onClick: () => addItemToCart(it, b, 1),
      disabled: b.is_expired
    },
    /* @__PURE__ */ React.createElement("strong", null, b.batch_no),
    /* @__PURE__ */ React.createElement("span", null, "(Exp: ", b.expiry_display, ")"),
    /* @__PURE__ */ React.createElement("span", { className: "text-success fw-bold" }, "\u20B9", b.mrp),
    /* @__PURE__ */ React.createElement("span", { className: "badge bg-primary text-white" }, b.current_qty, " left"),
    it.fefo_batch && it.fefo_batch.batch_id === b.batch_id && /* @__PURE__ */ React.createElement("span", { className: "badge-fefo ms-1" }, "FEFO"),
    b.is_near_expiry && /* @__PURE__ */ React.createElement("span", { className: "badge-near-expiry ms-1" }, "Near Exp"),
    b.is_expired && /* @__PURE__ */ React.createElement("span", { className: "badge-expired ms-1" }, "EXPIRED")
  ))) : /* @__PURE__ */ React.createElement("span", { className: "text-danger fw-bold", style: { fontSize: "0.75rem" } }, "Out of Stock in this Counter"))))), /* @__PURE__ */ React.createElement("div", { className: "counter-card p-0 overflow-hidden" }, /* @__PURE__ */ React.createElement("div", { className: "p-3 pb-2 d-flex justify-content-between align-items-center border-bottom" }, /* @__PURE__ */ React.createElement("h6", { className: "mb-0 fw-bold" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-cart-check-fill text-success me-1" }), " Dispensing Cart (", cart.length, " items)"), cart.length > 0 && /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-link text-danger text-decoration-none p-0", onClick: () => setCart([]) }, "Clear Cart")), /* @__PURE__ */ React.createElement("div", { className: "table-responsive", style: { maxHeight: 380 } }, /* @__PURE__ */ React.createElement("table", { className: "table table-pos mb-0" }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "#"), /* @__PURE__ */ React.createElement("th", null, "Medicine Name"), /* @__PURE__ */ React.createElement("th", null, "Batch / Exp"), /* @__PURE__ */ React.createElement("th", { style: { width: 85 } }, "Qty"), /* @__PURE__ */ React.createElement("th", null, "MRP (\u20B9)"), /* @__PURE__ */ React.createElement("th", { style: { width: 80 } }, "Disc%"), /* @__PURE__ */ React.createElement("th", null, "GST%"), /* @__PURE__ */ React.createElement("th", { className: "text-end" }, "Total (\u20B9)"), /* @__PURE__ */ React.createElement("th", { style: { width: 40 } }))), /* @__PURE__ */ React.createElement("tbody", null, cart.length === 0 ? /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("td", { colSpan: "9", className: "text-center py-4 text-muted" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-basket3 fs-3 d-block mb-1" }), "Scan barcode or search medicine to add items to bill.")) : cart.map((it, idx) => {
    const lineGross = it.qty * it.unit_mrp;
    const lineDisc = lineGross * (it.discount_pct || 0) / 100;
    const lineNet = (lineGross - lineDisc).toFixed(2);
    return /* @__PURE__ */ React.createElement("tr", { key: idx }, /* @__PURE__ */ React.createElement("td", null, idx + 1), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("div", { className: "fw-bold" }, it.item_name), /* @__PURE__ */ React.createElement("small", { className: "text-muted" }, it.generic_name, " \u2022 HSN: ", it.hsn_code)), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("span", { className: "badge bg-light text-dark border" }, it.batch_no), /* @__PURE__ */ React.createElement("div", { className: "text-muted", style: { fontSize: "0.7rem" } }, "Exp: ", it.expiry_display)), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement(
      "input",
      {
        type: "number",
        className: "form-control form-control-sm text-center fw-bold",
        min: "1",
        max: it.current_qty,
        value: it.qty,
        onChange: (e) => updateCartItem(idx, "qty", e.target.value)
      }
    )), /* @__PURE__ */ React.createElement("td", { className: "fw-semibold" }, "\u20B9", it.unit_mrp), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement(
      "input",
      {
        type: "number",
        className: "form-control form-control-sm text-center",
        min: "0",
        max: "100",
        value: it.discount_pct,
        onChange: (e) => updateCartItem(idx, "discount_pct", e.target.value)
      }
    )), /* @__PURE__ */ React.createElement("td", null, it.gst_rate, "%"), /* @__PURE__ */ React.createElement("td", { className: "text-end fw-bold text-dark" }, "\u20B9", lineNet), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-danger py-0 px-1", onClick: () => removeCartItem(idx) }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-trash" }))));
  })))))), /* @__PURE__ */ React.createElement("div", { className: "col-lg-4" }, /* @__PURE__ */ React.createElement("div", { className: "pos-summary-card" }, /* @__PURE__ */ React.createElement("h5", { className: "fw-bold text-white mb-3 d-flex justify-content-between align-items-center" }, /* @__PURE__ */ React.createElement("span", null, "Checkout Desk"), /* @__PURE__ */ React.createElement("span", { className: "fs-6 text-primary" }, store.store_code)), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mb-2 text-slate-300", style: { color: "#cbd5e1" } }, /* @__PURE__ */ React.createElement("span", null, "Gross Items Total:"), /* @__PURE__ */ React.createElement("span", { className: "fw-bold" }, "\u20B9", grossTotal.toFixed(2))), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mb-2 text-slate-300", style: { color: "#cbd5e1" } }, /* @__PURE__ */ React.createElement("span", null, "Total Discounts:"), /* @__PURE__ */ React.createElement("span", { className: "text-danger fw-bold" }, "-\u20B9", discountTotal.toFixed(2))), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mb-2 text-slate-300", style: { color: "#cbd5e1" } }, /* @__PURE__ */ React.createElement("span", null, "Round Off:"), /* @__PURE__ */ React.createElement("span", null, "\u20B9", roundOff)), /* @__PURE__ */ React.createElement("hr", { style: { borderColor: "rgba(255,255,255,0.15)" } }), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between align-items-center mb-4" }, /* @__PURE__ */ React.createElement("span", { className: "fs-5 fw-bold text-white" }, "Net Payable:"), /* @__PURE__ */ React.createElement("span", { className: "fs-3 fw-bolder text-warning" }, "\u20B9", netPayable.toLocaleString("en-IN"))), /* @__PURE__ */ React.createElement("label", { className: "form-label text-white fw-bold mb-2" }, "Payment Method"), /* @__PURE__ */ React.createElement("div", { className: "row g-2 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement(
    "button",
    {
      className: `btn btn-sm w-100 ${paymentMode === "Cash" ? "btn-primary-custom" : "btn-outline-light"}`,
      onClick: () => setPaymentMode("Cash")
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-cash-stack me-1" }),
    " Cash"
  )), /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement(
    "button",
    {
      className: `btn btn-sm w-100 ${paymentMode === "UPI" ? "btn-primary-custom" : "btn-outline-light"}`,
      onClick: () => setPaymentMode("UPI")
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-qr-code-scan me-1" }),
    " UPI / QR"
  )), /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement(
    "button",
    {
      className: `btn btn-sm w-100 ${paymentMode === "Card" ? "btn-primary-custom" : "btn-outline-light"}`,
      onClick: () => setPaymentMode("Card")
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-credit-card me-1" }),
    " Debit/Credit"
  )), /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement(
    "button",
    {
      className: `btn btn-sm w-100 ${paymentMode === "IPD_Credit" ? "btn-danger" : "btn-outline-light"}`,
      onClick: () => setPaymentMode("IPD_Credit"),
      title: "Charge to IPD Patient Bill"
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-hospital me-1" }),
    " IPD Bill Post"
  ))), paymentMode === "IPD_Credit" && /* @__PURE__ */ React.createElement("div", { className: "alert alert-warning py-2 mb-3", style: { fontSize: "0.78rem" } }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-info-circle me-1" }), "Medicines will be charged directly to the patient's IPD Hospital Account and settled at discharge."), paymentMode === "UPI" && store.upi_id && /* @__PURE__ */ React.createElement("div", { className: "bg-dark p-2 rounded-2 text-center mb-3" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block" }, "Store UPI VPA: ", /* @__PURE__ */ React.createElement("span", { className: "text-warning fw-bold" }, store.upi_id))), /* @__PURE__ */ React.createElement("div", { className: "mb-3" }, /* @__PURE__ */ React.createElement("label", { className: "form-label text-white small fw-bold mb-1" }, "Invoice Print Format"), /* @__PURE__ */ React.createElement("div", { className: "btn-group btn-group-sm w-100" }, /* @__PURE__ */ React.createElement(
    "button",
    {
      type: "button",
      className: `btn ${printFormat === "thermal" ? "btn-warning text-dark fw-bold" : "btn-outline-light"}`,
      onClick: () => setPrintFormat("thermal")
    },
    "Thermal 80mm (1-5 Items)"
  ), /* @__PURE__ */ React.createElement(
    "button",
    {
      type: "button",
      className: `btn ${printFormat === "a5" ? "btn-warning text-dark fw-bold" : "btn-outline-light"}`,
      onClick: () => setPrintFormat("a5")
    },
    "A5 Invoice (OPD)"
  ), /* @__PURE__ */ React.createElement(
    "button",
    {
      type: "button",
      className: `btn ${printFormat === "a4" ? "btn-warning text-dark fw-bold" : "btn-outline-light"}`,
      onClick: () => setPrintFormat("a4")
    },
    "A4 Tax Bill (IPD)"
  ))), /* @__PURE__ */ React.createElement(
    "button",
    {
      className: "btn btn-success-custom w-100 py-2.5 fs-6",
      onClick: processSale,
      disabled: isProcessing || cart.length === 0
    },
    isProcessing ? /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("i", { className: "bi bi-hourglass-split me-2" }), " Generating Bill...") : /* @__PURE__ */ React.createElement("span", null, /* @__PURE__ */ React.createElement("i", { className: "bi bi-printer me-2" }), " Complete Sale & Print (", printFormat.toUpperCase(), ")")
  )), lastInvoice && /* @__PURE__ */ React.createElement("div", { className: "counter-card mt-3" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex flex-wrap align-items-center gap-2" }, /* @__PURE__ */ React.createElement("span", { className: "fw-bold text-success" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-check-circle me-1" }), " Bill: ", lastInvoice.sale.invoice_no), lastInvoice.sale.abdm_care_context_ref && /* @__PURE__ */ React.createElement("span", { className: "badge bg-success-subtle text-success border border-success", title: "Ayushman Bharat Digital Mission Care Context Reference" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-fill-check me-1" }), " ", lastInvoice.sale.abdm_care_context_ref)), /* @__PURE__ */ React.createElement("div", { className: "d-flex gap-1" }, /* @__PURE__ */ React.createElement(
    "button",
    {
      className: "btn btn-sm btn-outline-success py-0 px-2",
      onClick: () => viewAbdmBundle(lastInvoice.sale.sale_id),
      title: "View official ABDM FHIR R4 MedicationDispense JSON Document Bundle"
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-filetype-json me-1" }),
    " FHIR R4 Bundle"
  ), /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${printFormat === "thermal" ? "btn-dark" : "btn-outline-secondary"} py-0 px-2`, onClick: () => setPrintFormat("thermal") }, "80mm"), /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${printFormat === "a5" ? "btn-dark" : "btn-outline-secondary"} py-0 px-2`, onClick: () => setPrintFormat("a5") }, "A5"), /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${printFormat === "a4" ? "btn-dark" : "btn-outline-secondary"} py-0 px-2`, onClick: () => setPrintFormat("a4") }, "A4"), /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-primary py-0 px-2", onClick: () => window.print(), title: "Print Bill" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-printer" })))), /* @__PURE__ */ React.createElement("div", { id: "printable-container" }, printFormat === "thermal" && /* @__PURE__ */ React.createElement("div", { className: "print-thermal-80mm" }, /* @__PURE__ */ React.createElement("div", { className: "text-center fw-bold fs-6" }, lastInvoice.sale.store_name), /* @__PURE__ */ React.createElement("div", { className: "text-center" }, lastInvoice.sale.building_name, ", ", lastInvoice.sale.floor_no), lastInvoice.sale.abdm_hfr_id && /* @__PURE__ */ React.createElement("div", { className: "text-center", style: { fontSize: "9px" } }, "HFR ID: ", lastInvoice.sale.abdm_hfr_id), /* @__PURE__ */ React.createElement("div", { className: "text-center" }, "DL 20B: ", lastInvoice.sale.drug_license_no_20b), /* @__PURE__ */ React.createElement("div", { className: "text-center" }, "DL 21B: ", lastInvoice.sale.drug_license_no_21b), /* @__PURE__ */ React.createElement("div", { className: "text-center" }, "GSTIN: ", lastInvoice.sale.gstin), /* @__PURE__ */ React.createElement("hr", { style: { margin: "4px 0" } }), /* @__PURE__ */ React.createElement("div", null, "Bill No: ", /* @__PURE__ */ React.createElement("strong", null, lastInvoice.sale.invoice_no)), /* @__PURE__ */ React.createElement("div", null, "Date: ", lastInvoice.sale.sale_date), /* @__PURE__ */ React.createElement("div", null, "Patient: ", lastInvoice.sale.patient_name, " ", lastInvoice.sale.uhid ? `(${lastInvoice.sale.uhid})` : ""), (lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && /* @__PURE__ */ React.createElement("div", { style: { fontSize: "9px" } }, "ABHA: ", lastInvoice.sale.abha_id || "", " ", lastInvoice.sale.abha_address ? `(${lastInvoice.sale.abha_address})` : ""), lastInvoice.sale.abdm_care_context_ref && /* @__PURE__ */ React.createElement("div", { style: { fontSize: "8px" } }, "Care Context: ", lastInvoice.sale.abdm_care_context_ref), /* @__PURE__ */ React.createElement("div", null, "Doctor: ", lastInvoice.sale.doctor_name), /* @__PURE__ */ React.createElement("hr", { style: { margin: "4px 0" } }), /* @__PURE__ */ React.createElement("table", { style: { width: "100%", fontSize: "10px" } }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", { style: { textAlign: "left" } }, "Item"), /* @__PURE__ */ React.createElement("th", { style: { textAlign: "center" } }, "Batch"), /* @__PURE__ */ React.createElement("th", { style: { textAlign: "center" } }, "Qty"), /* @__PURE__ */ React.createElement("th", { style: { textAlign: "right" } }, "Total"))), /* @__PURE__ */ React.createElement("tbody", null, lastInvoice.items.map((it, i) => /* @__PURE__ */ React.createElement("tr", { key: i }, /* @__PURE__ */ React.createElement("td", null, it.item_name), /* @__PURE__ */ React.createElement("td", { style: { textAlign: "center" } }, it.batch_no), /* @__PURE__ */ React.createElement("td", { style: { textAlign: "center" } }, it.qty), /* @__PURE__ */ React.createElement("td", { style: { textAlign: "right" } }, "\u20B9", it.total_amount))))), /* @__PURE__ */ React.createElement("hr", { style: { margin: "4px 0" } }), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between fw-bold" }, /* @__PURE__ */ React.createElement("span", null, "NET AMOUNT:"), /* @__PURE__ */ React.createElement("span", null, "\u20B9", lastInvoice.sale.net_amount)), /* @__PURE__ */ React.createElement("div", { style: { fontSize: "9px", marginTop: 4 } }, "Mode: ", lastInvoice.sale.payment_mode), /* @__PURE__ */ React.createElement("div", { style: { fontSize: "9px", marginTop: 4, textAlign: "center" } }, "R.Ph: ", lastInvoice.sale.registered_pharmacist_name || "Pharmacist", " ", lastInvoice.sale.pharmacist_hpr_id ? `(HPR: ${lastInvoice.sale.pharmacist_hpr_id})` : ""), /* @__PURE__ */ React.createElement("div", { style: { fontSize: "8px", textAlign: "center", marginTop: 4 } }, lastInvoice.sale.terms_conditions || "Thank you! Get well soon.")), printFormat === "a5" && /* @__PURE__ */ React.createElement("div", { className: "print-a5-invoice" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between align-items-start border-bottom pb-2 mb-2" }, /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("h6", { className: "fw-bold mb-0 text-primary" }, lastInvoice.sale.store_name), /* @__PURE__ */ React.createElement("div", { style: { fontSize: "10px" } }, lastInvoice.sale.building_name, ", ", lastInvoice.sale.floor_no, ", ", lastInvoice.sale.store_address), /* @__PURE__ */ React.createElement("div", { style: { fontSize: "10px" } }, /* @__PURE__ */ React.createElement("strong", null, "DL 20B:"), " ", lastInvoice.sale.drug_license_no_20b, " | ", /* @__PURE__ */ React.createElement("strong", null, "DL 21B:"), " ", lastInvoice.sale.drug_license_no_21b, lastInvoice.sale.abdm_hfr_id && /* @__PURE__ */ React.createElement("span", null, " | ", /* @__PURE__ */ React.createElement("strong", null, "HFR ID:"), " ", lastInvoice.sale.abdm_hfr_id)), /* @__PURE__ */ React.createElement("div", { style: { fontSize: "10px" } }, /* @__PURE__ */ React.createElement("strong", null, "GSTIN:"), " ", lastInvoice.sale.gstin, " | ", /* @__PURE__ */ React.createElement("strong", null, "Phone:"), " ", lastInvoice.sale.contact_phone)), /* @__PURE__ */ React.createElement("div", { className: "text-end" }, /* @__PURE__ */ React.createElement("span", { className: "badge bg-dark text-white mb-1" }, "RETAIL TAX INVOICE"), /* @__PURE__ */ React.createElement("div", { className: "fw-bold", style: { fontSize: "12px" } }, lastInvoice.sale.invoice_no), /* @__PURE__ */ React.createElement("div", { className: "text-muted", style: { fontSize: "10px" } }, lastInvoice.sale.sale_date))), /* @__PURE__ */ React.createElement("div", { className: "row g-1 mb-2 p-1.5 bg-light rounded", style: { fontSize: "10px" } }, /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement("strong", null, "Patient:"), " ", lastInvoice.sale.patient_name, " ", lastInvoice.sale.uhid ? `(${lastInvoice.sale.uhid})` : ""), /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement("strong", null, "Prescribed By:"), " ", lastInvoice.sale.doctor_name), /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement("strong", null, "Age/Gender:"), " ", lastInvoice.sale.age || "N/A", " / ", lastInvoice.sale.gender || "N/A"), /* @__PURE__ */ React.createElement("div", { className: "col-6" }, /* @__PURE__ */ React.createElement("strong", null, "Payment Mode:"), " ", lastInvoice.sale.payment_mode), (lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && /* @__PURE__ */ React.createElement("div", { className: "col-12 text-success" }, /* @__PURE__ */ React.createElement("strong", null, "ABHA:"), " ", lastInvoice.sale.abha_id || "N/A", " (", lastInvoice.sale.abha_address || "N/A", ")", lastInvoice.sale.abdm_care_context_ref && /* @__PURE__ */ React.createElement("span", null, " | ", /* @__PURE__ */ React.createElement("strong", null, "Care Context:"), " ", lastInvoice.sale.abdm_care_context_ref))), /* @__PURE__ */ React.createElement("table", { className: "table table-bordered table-sm mb-2", style: { fontSize: "10px" } }, /* @__PURE__ */ React.createElement("thead", { className: "table-light" }, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "#"), /* @__PURE__ */ React.createElement("th", null, "Medicine Name"), /* @__PURE__ */ React.createElement("th", null, "HSN"), /* @__PURE__ */ React.createElement("th", null, "Batch"), /* @__PURE__ */ React.createElement("th", null, "Exp"), /* @__PURE__ */ React.createElement("th", null, "Qty"), /* @__PURE__ */ React.createElement("th", null, "MRP (\u20B9)"), /* @__PURE__ */ React.createElement("th", { className: "text-end" }, "Amount (\u20B9)"))), /* @__PURE__ */ React.createElement("tbody", null, lastInvoice.items.map((it, i) => /* @__PURE__ */ React.createElement("tr", { key: i }, /* @__PURE__ */ React.createElement("td", null, i + 1), /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, it.item_name, it.snomed_ct_code && /* @__PURE__ */ React.createElement("div", { className: "text-muted", style: { fontSize: "8px" } }, "SNOMED: ", it.snomed_ct_code)), /* @__PURE__ */ React.createElement("td", null, it.hsn_code), /* @__PURE__ */ React.createElement("td", null, it.batch_no), /* @__PURE__ */ React.createElement("td", null, it.expiry_date.substring(0, 7)), /* @__PURE__ */ React.createElement("td", null, it.qty), /* @__PURE__ */ React.createElement("td", null, "\u20B9", it.unit_mrp), /* @__PURE__ */ React.createElement("td", { className: "text-end fw-bold" }, "\u20B9", it.total_amount))))), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between align-items-center border-top pt-2" }, /* @__PURE__ */ React.createElement("div", { style: { fontSize: "9px" } }, "R.Ph: ", lastInvoice.sale.registered_pharmacist_name || "Pharmacist", " (", lastInvoice.sale.pharmacist_reg_no, ")", lastInvoice.sale.pharmacist_hpr_id && /* @__PURE__ */ React.createElement("span", null, " | HPR ID: ", lastInvoice.sale.pharmacist_hpr_id)), /* @__PURE__ */ React.createElement("div", { className: "text-end" }, /* @__PURE__ */ React.createElement("span", { className: "fs-6 fw-bold" }, "TOTAL: \u20B9", lastInvoice.sale.net_amount)))), printFormat === "a4" && /* @__PURE__ */ React.createElement("div", { className: "print-a4-invoice" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between align-items-center border-bottom pb-3 mb-3" }, /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("h4", { className: "fw-bold text-primary mb-1" }, lastInvoice.sale.store_name), /* @__PURE__ */ React.createElement("div", { className: "text-muted" }, lastInvoice.sale.building_name, ", ", lastInvoice.sale.floor_no, ", ", lastInvoice.sale.store_address), /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("strong", null, "Drug License 20-B:"), " ", lastInvoice.sale.drug_license_no_20b, " | ", /* @__PURE__ */ React.createElement("strong", null, "21-B:"), " ", lastInvoice.sale.drug_license_no_21b, " | ", /* @__PURE__ */ React.createElement("strong", null, "FSSAI:"), " ", lastInvoice.sale.fssai_no || "N/A"), /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("strong", null, "GSTIN:"), " ", lastInvoice.sale.gstin, " | ", /* @__PURE__ */ React.createElement("strong", null, "State:"), " ", lastInvoice.sale.state_name, " (Code: ", lastInvoice.sale.state_code, ")", lastInvoice.sale.abdm_hfr_id && /* @__PURE__ */ React.createElement("span", null, " | ", /* @__PURE__ */ React.createElement("strong", null, "ABDM HFR Facility ID:"), " ", lastInvoice.sale.abdm_hfr_id))), /* @__PURE__ */ React.createElement("div", { className: "text-end" }, /* @__PURE__ */ React.createElement("span", { className: "badge bg-primary fs-6 px-3 py-1.5 mb-1" }, "HOSPITAL GST TAX INVOICE"), /* @__PURE__ */ React.createElement("h5", { className: "fw-bold mb-0 text-dark" }, lastInvoice.sale.invoice_no), /* @__PURE__ */ React.createElement("div", { className: "text-muted small" }, "Date & Time: ", lastInvoice.sale.sale_date))), /* @__PURE__ */ React.createElement("div", { className: "row g-2 mb-3 p-2 bg-light rounded border" }, /* @__PURE__ */ React.createElement("div", { className: "col-4" }, /* @__PURE__ */ React.createElement("strong", null, "Patient Name:"), " ", lastInvoice.sale.patient_name), /* @__PURE__ */ React.createElement("div", { className: "col-4" }, /* @__PURE__ */ React.createElement("strong", null, "UHID No:"), " ", /* @__PURE__ */ React.createElement("code", { className: "text-primary fw-bold" }, lastInvoice.sale.uhid || "Walk-in")), /* @__PURE__ */ React.createElement("div", { className: "col-4" }, /* @__PURE__ */ React.createElement("strong", null, "Phone:"), " ", lastInvoice.sale.patient_mobile || "N/A"), /* @__PURE__ */ React.createElement("div", { className: "col-4" }, /* @__PURE__ */ React.createElement("strong", null, "Age / Gender:"), " ", lastInvoice.sale.age || "N/A", " / ", lastInvoice.sale.gender || "N/A"), /* @__PURE__ */ React.createElement("div", { className: "col-4" }, /* @__PURE__ */ React.createElement("strong", null, "Consultant Doctor:"), " ", lastInvoice.sale.doctor_name), /* @__PURE__ */ React.createElement("div", { className: "col-4" }, /* @__PURE__ */ React.createElement("strong", null, "Doctor Reg No:"), " ", lastInvoice.sale.doctor_reg_no || "N/A"), (lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && /* @__PURE__ */ React.createElement("div", { className: "col-12 bg-success-subtle p-1.5 rounded text-success fw-bold border border-success mt-1" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-fill-check me-1" }), " ABDM Linked: ABHA Number: ", lastInvoice.sale.abha_id || "N/A", " | ABHA Address: ", lastInvoice.sale.abha_address || "N/A", " | Care Context: ", lastInvoice.sale.abdm_care_context_ref || "N/A"), lastInvoice.sale.ipd_id && /* @__PURE__ */ React.createElement("div", { className: "col-12 text-danger fw-bold border-top pt-1 mt-1" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-hospital me-1" }), " IPD Admission #", lastInvoice.sale.ipd_id, " \u2022 Ward: ", lastInvoice.sale.ward_name || "General", " \u2022 Bed: ", lastInvoice.sale.bed_no || "Bed", " (Charged to IPD Running Bill)")), /* @__PURE__ */ React.createElement("table", { className: "table table-bordered mb-3" }, /* @__PURE__ */ React.createElement("thead", { className: "table-light" }, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", { style: { width: 40 } }, "#"), /* @__PURE__ */ React.createElement("th", null, "Medicine & Formulation"), /* @__PURE__ */ React.createElement("th", null, "HSN"), /* @__PURE__ */ React.createElement("th", null, "Batch No"), /* @__PURE__ */ React.createElement("th", null, "Exp Date"), /* @__PURE__ */ React.createElement("th", { style: { width: 60 } }, "Qty"), /* @__PURE__ */ React.createElement("th", null, "MRP (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "Taxable (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "CGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "SGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", { className: "text-end" }, "Total (\u20B9)"))), /* @__PURE__ */ React.createElement("tbody", null, lastInvoice.items.map((it, i) => /* @__PURE__ */ React.createElement("tr", { key: i }, /* @__PURE__ */ React.createElement("td", null, i + 1), /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, it.item_name, it.snomed_ct_code && /* @__PURE__ */ React.createElement("div", { className: "text-muted fw-normal", style: { fontSize: "9px" } }, "SNOMED-CT: ", /* @__PURE__ */ React.createElement("code", null, it.snomed_ct_code), " (", it.snomed_display || "", ")")), /* @__PURE__ */ React.createElement("td", null, it.hsn_code), /* @__PURE__ */ React.createElement("td", null, it.batch_no), /* @__PURE__ */ React.createElement("td", null, it.expiry_date), /* @__PURE__ */ React.createElement("td", null, it.qty), /* @__PURE__ */ React.createElement("td", null, "\u20B9", it.unit_mrp), /* @__PURE__ */ React.createElement("td", null, "\u20B9", it.taxable_value), /* @__PURE__ */ React.createElement("td", null, "\u20B9", it.cgst_amount), /* @__PURE__ */ React.createElement("td", null, "\u20B9", it.sgst_amount), /* @__PURE__ */ React.createElement("td", { className: "text-end fw-bold" }, "\u20B9", it.total_amount))))), lastInvoice.hsn_summary && lastInvoice.hsn_summary.length > 0 && /* @__PURE__ */ React.createElement("div", { className: "mb-3" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold text-muted mb-1" }, "GST Tax Breakdown"), /* @__PURE__ */ React.createElement("table", { className: "table table-sm table-bordered text-center", style: { fontSize: "10px" } }, /* @__PURE__ */ React.createElement("thead", { className: "table-light" }, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "HSN Code"), /* @__PURE__ */ React.createElement("th", null, "GST Rate"), /* @__PURE__ */ React.createElement("th", null, "Taxable Value (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "CGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "SGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "Total Tax (\u20B9)"))), /* @__PURE__ */ React.createElement("tbody", null, lastInvoice.hsn_summary.map((h, idx) => /* @__PURE__ */ React.createElement("tr", { key: idx }, /* @__PURE__ */ React.createElement("td", null, h.hsn_code), /* @__PURE__ */ React.createElement("td", null, h.gst_rate, "%"), /* @__PURE__ */ React.createElement("td", null, "\u20B9", h.taxable_value.toFixed(2)), /* @__PURE__ */ React.createElement("td", null, "\u20B9", h.cgst_amount.toFixed(2)), /* @__PURE__ */ React.createElement("td", null, "\u20B9", h.sgst_amount.toFixed(2)), /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, "\u20B9", h.total_tax.toFixed(2))))))), /* @__PURE__ */ React.createElement("div", { className: "row g-3 border-top pt-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-7" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block" }, /* @__PURE__ */ React.createElement("strong", null, "Registered Pharmacist:"), " ", lastInvoice.sale.registered_pharmacist_name || "Pharmacist", " (Reg No: ", lastInvoice.sale.pharmacist_reg_no || "N/A", ")", lastInvoice.sale.pharmacist_hpr_id && /* @__PURE__ */ React.createElement("span", null, " | ", /* @__PURE__ */ React.createElement("strong", null, "HPR ID:"), " ", lastInvoice.sale.pharmacist_hpr_id)), /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block mt-1" }, lastInvoice.sale.terms_conditions || "1. Goods once sold are returnable only as per Drug Rules.\n2. Store medicines below 25\xB0C.")), /* @__PURE__ */ React.createElement("div", { className: "col-5" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mb-1" }, /* @__PURE__ */ React.createElement("span", null, "Taxable Amount:"), /* @__PURE__ */ React.createElement("span", null, "\u20B9", lastInvoice.sale.taxable_amount)), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mb-1" }, /* @__PURE__ */ React.createElement("span", null, "CGST Amount:"), /* @__PURE__ */ React.createElement("span", null, "\u20B9", lastInvoice.sale.cgst_amount)), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mb-1" }, /* @__PURE__ */ React.createElement("span", null, "SGST Amount:"), /* @__PURE__ */ React.createElement("span", null, "\u20B9", lastInvoice.sale.sgst_amount)), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mb-1" }, /* @__PURE__ */ React.createElement("span", null, "Round Off:"), /* @__PURE__ */ React.createElement("span", null, "\u20B9", lastInvoice.sale.round_off)), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between fs-5 fw-bolder border-top pt-2 text-primary" }, /* @__PURE__ */ React.createElement("span", null, "GRAND TOTAL:"), /* @__PURE__ */ React.createElement("span", null, "\u20B9", lastInvoice.sale.net_amount))))))), showFhirModal && /* @__PURE__ */ React.createElement("div", { className: "modal fade show d-block", style: { backgroundColor: "rgba(15, 23, 42, 0.75)" }, tabIndex: "-1" }, /* @__PURE__ */ React.createElement("div", { className: "modal-dialog modal-lg modal-dialog-scrollable" }, /* @__PURE__ */ React.createElement("div", { className: "modal-content border-0 shadow-lg" }, /* @__PURE__ */ React.createElement("div", { className: "modal-header bg-dark text-white py-2 px-3" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex align-items-center gap-2" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-fill-check text-success fs-5" }), /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("h6", { className: "modal-title mb-0 fw-bold" }, "ABDM NRCES FHIR R4 MedicationDispense Document"), /* @__PURE__ */ React.createElement("small", { className: "text-secondary", style: { fontSize: "0.75rem" } }, "Profile: MedicationDispenseDocument | LOINC: 60590-7"))), /* @__PURE__ */ React.createElement("button", { type: "button", className: "btn-close btn-close-white", onClick: () => setShowFhirModal(false) })), /* @__PURE__ */ React.createElement("div", { className: "modal-body p-3 bg-light" }, isLoadingFhir ? /* @__PURE__ */ React.createElement("div", { className: "text-center py-5" }, /* @__PURE__ */ React.createElement("div", { className: "spinner-border text-primary mb-2", role: "status" }), /* @__PURE__ */ React.createElement("div", { className: "text-muted" }, "Retrieving & Validating ABDM FHIR R4 Bundle...")) : fhirBundleData ? /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "row g-2 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("div", { className: "bg-white p-2 rounded border" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block" }, "Care Context Reference"), /* @__PURE__ */ React.createElement("code", { className: "text-primary fw-bold" }, fhirBundleData.care_context_ref))), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("div", { className: "bg-white p-2 rounded border" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block" }, "ABHA Number / Address"), /* @__PURE__ */ React.createElement("strong", { className: "text-success" }, fhirBundleData.abha_id || fhirBundleData.abha_address || "Not ABHA Linked"))), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("div", { className: "bg-white p-2 rounded border" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted d-block" }, "Gateway Sync Status"), /* @__PURE__ */ React.createElement("span", { className: `badge ${fhirBundleData.sync_status === "SYNCED" ? "bg-success" : "bg-warning text-dark"}` }, fhirBundleData.sync_status || "QUEUED_FOR_BRIDGE")))), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between align-items-center mb-1" }, /* @__PURE__ */ React.createElement("span", { className: "small fw-bold text-dark" }, "NRCES FHIR R4 Document Bundle (JSON):"), /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-secondary py-0 px-2", onClick: copyFhirToClipboard }, /* @__PURE__ */ React.createElement("i", { className: `bi ${copiedFhir ? "bi-check-lg text-success" : "bi-clipboard"} me-1` }), copiedFhir ? "Copied!" : "Copy JSON")), /* @__PURE__ */ React.createElement("pre", { className: "bg-dark text-light p-3 rounded-2", style: { maxHeight: 360, fontSize: "0.75rem", overflow: "auto" } }, JSON.stringify(fhirBundleData.bundle, null, 2))) : /* @__PURE__ */ React.createElement("div", { className: "alert alert-warning mb-0" }, "No FHIR bundle data found.")), /* @__PURE__ */ React.createElement("div", { className: "modal-footer py-1 px-3 bg-white" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted me-auto", style: { fontSize: "0.75rem" } }, "Includes SNOMED-CT clinical codes, HFR Facility ID, and Practitioner HPR ID."), /* @__PURE__ */ React.createElement("button", { type: "button", className: "btn btn-sm btn-secondary", onClick: () => setShowFhirModal(false) }, "Close")))))));
}
function InventoryView({ store, notify }) {
  const [stock, setStock] = useState([]);
  const [summary, setSummary] = useState({});
  const [filter, setFilter] = useState("all");
  const [searchTerm, setSearchTerm] = useState("");
  const [loading, setLoading] = useState(false);
  useEffect(() => {
    loadStock();
  }, [store.store_id, filter]);
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
  const filteredStock = stock.filter(
    (s) => s.item_name.toLowerCase().includes(searchTerm.toLowerCase()) || s.generic_name && s.generic_name.toLowerCase().includes(searchTerm.toLowerCase()) || s.batch_no.toLowerCase().includes(searchTerm.toLowerCase())
  );
  return /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "row g-3 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-primary" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold text-uppercase" }, "Total Batches in Store"), /* @__PURE__ */ React.createElement("h4", { className: "fw-bolder text-dark mt-1 mb-0" }, summary.total_rows || 0))), /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-success" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold text-uppercase" }, "Stock Cost Valuation (PTR)"), /* @__PURE__ */ React.createElement("h4", { className: "fw-bolder text-success mt-1 mb-0" }, "\u20B9", (summary.total_cost_valuation || 0).toLocaleString("en-IN")))), /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-warning" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold text-uppercase" }, "Stock Retail Valuation (MRP)"), /* @__PURE__ */ React.createElement("h4", { className: "fw-bolder text-dark mt-1 mb-0" }, "\u20B9", (summary.total_mrp_valuation || 0).toLocaleString("en-IN")))), /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-info" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold text-uppercase" }, "Counter / URL"), /* @__PURE__ */ React.createElement("h6", { className: "fw-bolder text-primary mt-1 mb-0 text-truncate" }, "MedicalStore/", store.store_slug)))), /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex flex-wrap justify-content-between align-items-center gap-2" }, /* @__PURE__ */ React.createElement("div", { className: "btn-group btn-group-sm" }, /* @__PURE__ */ React.createElement("button", { className: `btn ${filter === "all" ? "btn-primary" : "btn-outline-secondary"}`, onClick: () => setFilter("all") }, "All Stock"), /* @__PURE__ */ React.createElement("button", { className: `btn ${filter === "low" ? "btn-primary" : "btn-outline-secondary"}`, onClick: () => setFilter("low") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-arrow-down-circle me-1" }), " Low Stock"), /* @__PURE__ */ React.createElement("button", { className: `btn ${filter === "near_expiry" ? "btn-warning" : "btn-outline-secondary"}`, onClick: () => setFilter("near_expiry") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-clock-history me-1" }), " Near Expiry (<90 Days)"), /* @__PURE__ */ React.createElement("button", { className: `btn ${filter === "expired" ? "btn-danger" : "btn-outline-secondary"}`, onClick: () => setFilter("expired") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-x me-1" }), " Expired Stock")), /* @__PURE__ */ React.createElement("div", { style: { maxWidth: 300 }, className: "w-100" }, /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control form-control-sm",
      placeholder: "Search item, molecule, batch...",
      value: searchTerm,
      onChange: (e) => setSearchTerm(e.target.value)
    }
  )))), /* @__PURE__ */ React.createElement("div", { className: "counter-card p-0 overflow-hidden" }, /* @__PURE__ */ React.createElement("div", { className: "table-responsive" }, /* @__PURE__ */ React.createElement("table", { className: "table table-pos table-hover mb-0" }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "#"), /* @__PURE__ */ React.createElement("th", null, "Item & Generic Molecule"), /* @__PURE__ */ React.createElement("th", null, "Category"), /* @__PURE__ */ React.createElement("th", null, "Batch No"), /* @__PURE__ */ React.createElement("th", null, "Expiry"), /* @__PURE__ */ React.createElement("th", null, "Current Qty"), /* @__PURE__ */ React.createElement("th", null, "PTR (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "MRP (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "Cost Value (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "Status"))), /* @__PURE__ */ React.createElement("tbody", null, loading ? /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("td", { colSpan: "10", className: "text-center py-4 text-muted" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-hourglass-split fs-4 d-block mb-1" }), " Loading inventory...")) : filteredStock.length === 0 ? /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("td", { colSpan: "10", className: "text-center py-4 text-muted" }, "No stock records found.")) : filteredStock.map((s, idx) => /* @__PURE__ */ React.createElement("tr", { key: s.stock_id }, /* @__PURE__ */ React.createElement("td", null, idx + 1), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("div", { className: "fw-bold" }, s.item_name), /* @__PURE__ */ React.createElement("small", { className: "text-muted" }, s.generic_name, " \u2022 HSN: ", s.hsn_code)), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("span", { className: "badge bg-secondary-subtle text-secondary" }, s.category)), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("span", { className: "badge bg-light text-dark border fw-bold" }, s.batch_no)), /* @__PURE__ */ React.createElement("td", null, s.expiry_display), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("span", { className: `fw-bold fs-6 ${s.current_qty <= s.min_reorder_level ? "text-danger" : "text-success"}` }, s.current_qty)), /* @__PURE__ */ React.createElement("td", null, "\u20B9", s.ptr), /* @__PURE__ */ React.createElement("td", { className: "fw-semibold" }, "\u20B9", s.mrp), /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, "\u20B9", s.cost_valuation), /* @__PURE__ */ React.createElement("td", null, s.is_expired ? /* @__PURE__ */ React.createElement("span", { className: "badge-expired" }, "EXPIRED") : s.is_near_expiry ? /* @__PURE__ */ React.createElement("span", { className: "badge-near-expiry" }, "Near Expiry") : /* @__PURE__ */ React.createElement("span", { className: "badge-fefo" }, "Good Stock")))))))));
}
function PurchaseView({ store, notify }) {
  const [subTab, setSubTab] = useState("inward");
  const [suppliers, setSuppliers] = useState([]);
  const [selectedSupplierId, setSelectedSupplierId] = useState("");
  const [invoiceNo, setInvoiceNo] = useState("");
  const [invoiceDate, setInvoiceDate] = useState((/* @__PURE__ */ new Date()).toISOString().split("T")[0]);
  const [rows, setRows] = useState([
    { item_name: "", generic_name: "", batch_no: "", expiry_date: "", qty_packs: 1, free_qty_packs: 0, units_per_pack: 10, mrp: 0, ptr: 0, discount_pct: 0, gst_rate: 12 }
  ]);
  useEffect(() => {
    fetchSuppliers();
  }, []);
  const fetchSuppliers = async () => {
    try {
      const res = await fetch(API_BASE + "suppliers");
      const data = await res.json();
      if (data.status) setSuppliers(data.suppliers || []);
    } catch (e) {
      console.error(e);
    }
  };
  const addRow = () => {
    setRows([...rows, { item_name: "", generic_name: "", batch_no: "", expiry_date: "", qty_packs: 1, free_qty_packs: 0, units_per_pack: 10, mrp: 0, ptr: 0, discount_pct: 0, gst_rate: 12 }]);
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
      notify("Please select supplier and enter invoice number.", "error");
      return;
    }
    const validItems = rows.filter((r) => r.item_name && r.batch_no && r.expiry_date && Number(r.qty_packs) > 0);
    if (validItems.length === 0) {
      notify("Please fill at least one valid item line.", "error");
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
      const res = await fetch(API_BASE + "purchase/save", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
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
        notify("Purchase inward recorded! Supplier Ledger & Stock updated.", "success");
        setInvoiceNo("");
        setRows([{ item_name: "", generic_name: "", batch_no: "", expiry_date: "", qty_packs: 1, free_qty_packs: 0, units_per_pack: 10, mrp: 0, ptr: 0, discount_pct: 0, gst_rate: 12 }]);
      } else {
        notify(data.message || "Failed to save purchase bill.", "error");
      }
    } catch (e) {
      notify("Purchase bill error: " + e.message, "error");
    }
  };
  return /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "d-flex gap-2 mb-3" }, /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${subTab === "inward" ? "btn-primary" : "btn-outline-secondary"}`, onClick: () => setSubTab("inward") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-box-arrow-in-down me-1" }), " Inward Purchase Bill"), /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${subTab === "marg" ? "btn-success" : "btn-outline-secondary"}`, onClick: () => setSubTab("marg") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-file-earmark-spreadsheet me-1" }), " Marg ERP / Excel Import"), /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${subTab === "suppliers" ? "btn-primary" : "btn-outline-secondary"}`, onClick: () => setSubTab("suppliers") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-truck me-1" }), " Supplier Directory")), subTab === "inward" && /* @__PURE__ */ React.createElement("div", { className: "counter-card" }, /* @__PURE__ */ React.createElement("h5", { className: "fw-bold mb-3" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-receipt text-primary me-1" }), " Distributor Purchase Inward (Stock Entry)"), /* @__PURE__ */ React.createElement("div", { className: "row g-2 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Supplier / Distributor"), /* @__PURE__ */ React.createElement("select", { className: "form-select form-select-sm", value: selectedSupplierId, onChange: (e) => setSelectedSupplierId(e.target.value) }, /* @__PURE__ */ React.createElement("option", { value: "" }, "-- Select Supplier --"), suppliers.map((s) => /* @__PURE__ */ React.createElement("option", { key: s.supplier_id, value: s.supplier_id }, s.supplier_name, " (GST: ", s.gstin || "N/A", ")")))), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Supplier Bill / Invoice No."), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. APX-9941", value: invoiceNo, onChange: (e) => setInvoiceNo(e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Invoice Date"), /* @__PURE__ */ React.createElement("input", { type: "date", className: "form-control form-control-sm", value: invoiceDate, onChange: (e) => setInvoiceDate(e.target.value) }))), /* @__PURE__ */ React.createElement("div", { className: "table-responsive" }, /* @__PURE__ */ React.createElement("table", { className: "table table-pos table-bordered" }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "Medicine Name"), /* @__PURE__ */ React.createElement("th", null, "Batch No"), /* @__PURE__ */ React.createElement("th", null, "Expiry"), /* @__PURE__ */ React.createElement("th", { style: { width: 70 } }, "Packs"), /* @__PURE__ */ React.createElement("th", { style: { width: 65 } }, "Free"), /* @__PURE__ */ React.createElement("th", { style: { width: 80 } }, "Units/Pack"), /* @__PURE__ */ React.createElement("th", { style: { width: 85 } }, "PTR (\u20B9)"), /* @__PURE__ */ React.createElement("th", { style: { width: 85 } }, "MRP (\u20B9)"), /* @__PURE__ */ React.createElement("th", { style: { width: 65 } }, "GST%"), /* @__PURE__ */ React.createElement("th", { style: { width: 40 } }))), /* @__PURE__ */ React.createElement("tbody", null, rows.map((r, i) => /* @__PURE__ */ React.createElement("tr", { key: i }, /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. Augmentin 625", value: r.item_name, onChange: (e) => updateRow(i, "item_name", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "Batch", value: r.batch_no, onChange: (e) => updateRow(i, "batch_no", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "date", className: "form-control form-control-sm", value: r.expiry_date, onChange: (e) => updateRow(i, "expiry_date", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "number", className: "form-control form-control-sm", min: "1", value: r.qty_packs, onChange: (e) => updateRow(i, "qty_packs", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "number", className: "form-control form-control-sm", min: "0", value: r.free_qty_packs, onChange: (e) => updateRow(i, "free_qty_packs", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "number", className: "form-control form-control-sm", min: "1", value: r.units_per_pack, onChange: (e) => updateRow(i, "units_per_pack", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "number", className: "form-control form-control-sm", placeholder: "PTR", value: r.ptr, onChange: (e) => updateRow(i, "ptr", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("input", { type: "number", className: "form-control form-control-sm", placeholder: "MRP", value: r.mrp, onChange: (e) => updateRow(i, "mrp", e.target.value) })), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("select", { className: "form-select form-select-sm", value: r.gst_rate, onChange: (e) => updateRow(i, "gst_rate", e.target.value) }, /* @__PURE__ */ React.createElement("option", { value: "0" }, "0%"), /* @__PURE__ */ React.createElement("option", { value: "5" }, "5%"), /* @__PURE__ */ React.createElement("option", { value: "12" }, "12%"), /* @__PURE__ */ React.createElement("option", { value: "18" }, "18%"))), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-danger py-0 px-1", onClick: () => removeRow(i) }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-x" })))))))), /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between mt-2" }, /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-primary", onClick: addRow }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-plus-circle me-1" }), " Add Another Item"), /* @__PURE__ */ React.createElement("button", { className: "btn btn-success-custom", onClick: savePurchaseBill }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-check-circle me-1" }), " Record Inward Bill & Update Inventory"))), subTab === "marg" && /* @__PURE__ */ React.createElement("div", { className: "counter-card" }, /* @__PURE__ */ React.createElement("h5", { className: "fw-bold text-success mb-2" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-file-earmark-spreadsheet me-1" }), " Marg ERP / Excel Inventory Importer"), /* @__PURE__ */ React.createElement("p", { className: "text-muted small mb-3" }, "Upload your stock export from ", /* @__PURE__ */ React.createElement("strong", null, "Marg Pharmacy Software"), " or Microsoft Excel to seamlessly import medicines, batch numbers, MRP, PTR, HSN codes, and opening stock into ", /* @__PURE__ */ React.createElement("strong", null, store.store_name), "."), /* @__PURE__ */ React.createElement("div", { className: "p-4 bg-light rounded-3 border text-center mb-3" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-cloud-arrow-up fs-1 text-success d-block mb-2" }), /* @__PURE__ */ React.createElement("h6", { className: "fw-bold" }, "Upload Marg CSV / Excel File"), /* @__PURE__ */ React.createElement("p", { className: "small text-muted mb-3" }, "Ensure file is exported in CSV format from Marg ERP."), /* @__PURE__ */ React.createElement("input", { type: "file", id: "marg_file_input", accept: ".csv, .txt", className: "form-control form-control-sm w-50 mx-auto mb-3" }), /* @__PURE__ */ React.createElement(
    "button",
    {
      className: "btn btn-success-custom",
      onClick: async () => {
        const fileInput = document.getElementById("marg_file_input");
        if (!fileInput.files || fileInput.files.length === 0) {
          alert("Please select a CSV file first.");
          return;
        }
        const formData = new FormData();
        formData.append("store_id", store.store_id);
        formData.append("import_file", fileInput.files[0]);
        try {
          const res = await fetch(API_BASE + "stock/import-marg", {
            method: "POST",
            body: formData
          });
          const data = await res.json();
          if (data.ok) {
            alert(data.message);
            fileInput.value = "";
          } else {
            alert(data.error || "Import failed.");
          }
        } catch (e) {
          alert("Import error: " + e.message);
        }
      }
    },
    /* @__PURE__ */ React.createElement("i", { className: "bi bi-upload me-1" }),
    " Import Inventory into ",
    store.store_name
  ))), subTab === "suppliers" && /* @__PURE__ */ React.createElement("div", { className: "counter-card" }, /* @__PURE__ */ React.createElement("h5", { className: "fw-bold mb-3" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-truck text-primary me-1" }), " Approved Drug Distributors & Suppliers"), /* @__PURE__ */ React.createElement("div", { className: "table-responsive" }, /* @__PURE__ */ React.createElement("table", { className: "table table-pos mb-0" }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "#"), /* @__PURE__ */ React.createElement("th", null, "Supplier Name"), /* @__PURE__ */ React.createElement("th", null, "DL Form 20B / 21B"), /* @__PURE__ */ React.createElement("th", null, "GSTIN"), /* @__PURE__ */ React.createElement("th", null, "Phone / Contact"), /* @__PURE__ */ React.createElement("th", null, "Credit Terms"))), /* @__PURE__ */ React.createElement("tbody", null, suppliers.map((s, i) => /* @__PURE__ */ React.createElement("tr", { key: s.supplier_id }, /* @__PURE__ */ React.createElement("td", null, i + 1), /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, s.supplier_name), /* @__PURE__ */ React.createElement("td", null, s.dl_no_20b || "N/A", " / ", s.dl_no_21b || "N/A"), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("code", null, s.gstin)), /* @__PURE__ */ React.createElement("td", null, s.phone, " (", s.contact_person, ")"), /* @__PURE__ */ React.createElement("td", null, s.credit_days, " Days"))))))));
}
function TransferView({ store, stores, notify }) {
  const [toStoreId, setToStoreId] = useState("");
  const [remarks, setRemarks] = useState("");
  const [transferItems, setTransferItems] = useState([
    { item_name: "", qty: 10 }
  ]);
  const addTransferRow = () => {
    setTransferItems([...transferItems, { item_name: "", qty: 10 }]);
  };
  const submitIndent = async () => {
    if (!toStoreId) {
      notify("Please select destination store counter.", "error");
      return;
    }
    try {
      const res = await fetch(API_BASE + "transfer/request", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          from_store_id: store.store_id,
          to_store_id: Number(toStoreId),
          remarks,
          items: transferItems.map((it) => ({ item_id: 1, qty: Number(it.qty) }))
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(`Store Indent ${data.transfer_no} submitted to Central Pharmacy!`, "success");
        setRemarks("");
      } else {
        notify(data.message || "Failed to submit indent.", "error");
      }
    } catch (e) {
      notify("Indent error: " + e.message, "error");
    }
  };
  return /* @__PURE__ */ React.createElement("div", { className: "counter-card" }, /* @__PURE__ */ React.createElement("h5", { className: "fw-bold mb-3" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-arrow-left-right text-primary me-1" }), " Multi-Building Store Requisition (Indent)"), /* @__PURE__ */ React.createElement("p", { className: "text-muted small" }, "Building counters can requisition medicines from the Main Hospital Central Store. Once dispatched and physically verified, stock transfers automatically."), /* @__PURE__ */ React.createElement("div", { className: "row g-2 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-6" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Source Store (From)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", value: store.store_name, disabled: true })), /* @__PURE__ */ React.createElement("div", { className: "col-md-6" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Target Counter / Building (To)"), /* @__PURE__ */ React.createElement("select", { className: "form-select form-select-sm", value: toStoreId, onChange: (e) => setToStoreId(e.target.value) }, /* @__PURE__ */ React.createElement("option", { value: "" }, "-- Select Destination Store --"), stores.filter((s) => s.store_id !== store.store_id).map((s) => /* @__PURE__ */ React.createElement("option", { key: s.store_id, value: s.store_id }, s.store_name, " (", s.store_slug, ")"))))), /* @__PURE__ */ React.createElement("div", { className: "mb-3" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Requested Medicines"), transferItems.map((ti, i) => /* @__PURE__ */ React.createElement("div", { key: i, className: "row g-2 mb-2" }, /* @__PURE__ */ React.createElement("div", { className: "col-8" }, /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "text",
      className: "form-control form-control-sm",
      placeholder: "Medicine Name",
      value: ti.item_name,
      onChange: (e) => {
        const updated = [...transferItems];
        updated[i].item_name = e.target.value;
        setTransferItems(updated);
      }
    }
  )), /* @__PURE__ */ React.createElement("div", { className: "col-4" }, /* @__PURE__ */ React.createElement(
    "input",
    {
      type: "number",
      className: "form-control form-control-sm",
      placeholder: "Quantity Required",
      min: "1",
      value: ti.qty,
      onChange: (e) => {
        const updated = [...transferItems];
        updated[i].qty = e.target.value;
        setTransferItems(updated);
      }
    }
  )))), /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-primary mt-1", onClick: addTransferRow }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-plus-circle me-1" }), " Add Another Medicine")), /* @__PURE__ */ React.createElement("button", { className: "btn btn-primary-custom", onClick: submitIndent }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-send me-1" }), " Submit Store Indent Requisition"));
}
function LedgersView({ store, notify }) {
  const [accTab, setAccTab] = useState("daybook");
  const [daybookData, setDaybookData] = useState(null);
  const [gstData, setGstData] = useState(null);
  const [h1Records, setH1Records] = useState([]);
  useEffect(() => {
    if (accTab === "daybook") loadDaybook();
    if (accTab === "gst") loadGst();
    if (accTab === "schedule_h1") loadScheduleH1();
  }, [accTab, store.store_id]);
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
  return /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "d-flex flex-wrap gap-2 mb-3" }, /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${accTab === "daybook" ? "btn-primary" : "btn-outline-secondary"}`, onClick: () => setAccTab("daybook") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-journal-check me-1" }), " Daily Cash Book / Day Book"), /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${accTab === "gst" ? "btn-primary" : "btn-outline-secondary"}`, onClick: () => setAccTab("gst") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-file-earmark-spreadsheet me-1" }), " GST Returns (GSTR-1 & 2)"), /* @__PURE__ */ React.createElement("button", { className: `btn btn-sm ${accTab === "schedule_h1" ? "btn-primary" : "btn-outline-secondary"}`, onClick: () => setAccTab("schedule_h1") }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-check me-1" }), " Statutory Schedule H1 Register")), accTab === "daybook" && daybookData && /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("div", { className: "row g-3 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-success" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold" }, "TOTAL TODAY SALES"), /* @__PURE__ */ React.createElement("h4", { className: "fw-bolder text-dark mt-1" }, "\u20B9", daybookData.summary.total_sales.toLocaleString("en-IN")), /* @__PURE__ */ React.createElement("small", { className: "text-muted" }, daybookData.summary.total_invoices, " Bills"))), /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-primary" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold" }, "CASH IN DRAWER"), /* @__PURE__ */ React.createElement("h4", { className: "fw-bolder text-primary mt-1" }, "\u20B9", daybookData.summary.cash_in_drawer.toLocaleString("en-IN")))), /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-warning" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold" }, "UPI / QR COLLECTIONS"), /* @__PURE__ */ React.createElement("h4", { className: "fw-bolder text-warning mt-1" }, "\u20B9", daybookData.summary.upi_collection.toLocaleString("en-IN")))), /* @__PURE__ */ React.createElement("div", { className: "col-md-3 col-sm-6" }, /* @__PURE__ */ React.createElement("div", { className: "counter-card p-3 border-start border-4 border-danger" }, /* @__PURE__ */ React.createElement("small", { className: "text-muted fw-bold" }, "IPD CREDIT BILLED"), /* @__PURE__ */ React.createElement("h4", { className: "fw-bolder text-danger mt-1" }, "\u20B9", daybookData.summary.ipd_credit.toLocaleString("en-IN"))))), /* @__PURE__ */ React.createElement("div", { className: "counter-card p-0 overflow-hidden" }, /* @__PURE__ */ React.createElement("div", { className: "p-3 border-bottom" }, /* @__PURE__ */ React.createElement("h6", { className: "mb-0 fw-bold" }, "Today's Transactions Journal")), /* @__PURE__ */ React.createElement("div", { className: "table-responsive" }, /* @__PURE__ */ React.createElement("table", { className: "table table-pos mb-0" }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "Invoice No"), /* @__PURE__ */ React.createElement("th", null, "Time"), /* @__PURE__ */ React.createElement("th", null, "Patient (UHID)"), /* @__PURE__ */ React.createElement("th", null, "Doctor"), /* @__PURE__ */ React.createElement("th", null, "Mode"), /* @__PURE__ */ React.createElement("th", null, "CGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "SGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", { className: "text-end" }, "Net Amount"))), /* @__PURE__ */ React.createElement("tbody", null, daybookData.transactions.map((t) => /* @__PURE__ */ React.createElement("tr", { key: t.sale_id }, /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, t.invoice_no), /* @__PURE__ */ React.createElement("td", null, t.sale_date.split(" ")[1]), /* @__PURE__ */ React.createElement("td", null, t.patient_name, " ", t.uhid ? `(${t.uhid})` : ""), /* @__PURE__ */ React.createElement("td", null, t.doctor_name), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("span", { className: "badge bg-secondary" }, t.payment_mode)), /* @__PURE__ */ React.createElement("td", null, "\u20B9", t.cgst_amount), /* @__PURE__ */ React.createElement("td", null, "\u20B9", t.sgst_amount), /* @__PURE__ */ React.createElement("td", { className: "text-end fw-bold text-success" }, "\u20B9", t.net_amount)))))))), accTab === "gst" && gstData && /* @__PURE__ */ React.createElement("div", { className: "counter-card mb-3" }, /* @__PURE__ */ React.createElement("h6", { className: "fw-bold mb-3" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-file-earmark-spreadsheet text-primary me-1" }), " GSTR-1 Outward Sales Summary (HSN-wise)"), /* @__PURE__ */ React.createElement("div", { className: "table-responsive" }, /* @__PURE__ */ React.createElement("table", { className: "table table-pos mb-0" }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "HSN Code"), /* @__PURE__ */ React.createElement("th", null, "GST Slab"), /* @__PURE__ */ React.createElement("th", null, "Taxable Value (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "CGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "SGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", null, "IGST (\u20B9)"), /* @__PURE__ */ React.createElement("th", { className: "text-end" }, "Total Invoice Value (\u20B9)"))), /* @__PURE__ */ React.createElement("tbody", null, gstData.gstr1_sales.map((g, i) => /* @__PURE__ */ React.createElement("tr", { key: i }, /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("code", null, g.hsn_code)), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("span", { className: "badge bg-primary" }, g.gst_rate, "%")), /* @__PURE__ */ React.createElement("td", null, "\u20B9", Number(g.taxable_val).toFixed(2)), /* @__PURE__ */ React.createElement("td", null, "\u20B9", Number(g.cgst_val).toFixed(2)), /* @__PURE__ */ React.createElement("td", null, "\u20B9", Number(g.sgst_val).toFixed(2)), /* @__PURE__ */ React.createElement("td", null, "\u20B9", Number(g.igst_val).toFixed(2)), /* @__PURE__ */ React.createElement("td", { className: "text-end fw-bold" }, "\u20B9", Number(g.total_val).toFixed(2)))))))), accTab === "schedule_h1" && /* @__PURE__ */ React.createElement("div", { className: "counter-card" }, /* @__PURE__ */ React.createElement("div", { className: "d-flex justify-content-between align-items-center mb-3" }, /* @__PURE__ */ React.createElement("div", null, /* @__PURE__ */ React.createElement("h6", { className: "fw-bold mb-0" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-check text-success me-1" }), " Statutory Schedule H1 Drug Register"), /* @__PURE__ */ React.createElement("small", { className: "text-muted" }, "Mandatory under Indian Drugs & Cosmetics Rules (4th Amendment 2013). Retain records for 3 years.")), /* @__PURE__ */ React.createElement("button", { className: "btn btn-sm btn-outline-dark", onClick: () => window.print() }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-printer me-1" }), " Export / Print Register")), /* @__PURE__ */ React.createElement("div", { className: "table-responsive" }, /* @__PURE__ */ React.createElement("table", { className: "table table-pos table-bordered mb-0" }, /* @__PURE__ */ React.createElement("thead", null, /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("th", null, "Date"), /* @__PURE__ */ React.createElement("th", null, "Bill No"), /* @__PURE__ */ React.createElement("th", null, "Patient Name & Address"), /* @__PURE__ */ React.createElement("th", null, "Prescribing Doctor & Reg No"), /* @__PURE__ */ React.createElement("th", null, "Medicine Name"), /* @__PURE__ */ React.createElement("th", null, "Batch No"), /* @__PURE__ */ React.createElement("th", null, "Qty Supplied"))), /* @__PURE__ */ React.createElement("tbody", null, h1Records.length === 0 ? /* @__PURE__ */ React.createElement("tr", null, /* @__PURE__ */ React.createElement("td", { colSpan: "7", className: "text-center py-4 text-muted" }, "No Schedule H1 medicines dispensed in this period.")) : h1Records.map((h, i) => /* @__PURE__ */ React.createElement("tr", { key: i }, /* @__PURE__ */ React.createElement("td", null, h.sale_date.split(" ")[0]), /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, h.invoice_no), /* @__PURE__ */ React.createElement("td", null, h.patient_name, " ", h.patient_address ? `(${h.patient_address})` : ""), /* @__PURE__ */ React.createElement("td", null, h.doctor_name, " ", h.doctor_reg_no ? `(Reg: ${h.doctor_reg_no})` : ""), /* @__PURE__ */ React.createElement("td", { className: "fw-bold text-danger" }, h.item_name), /* @__PURE__ */ React.createElement("td", null, /* @__PURE__ */ React.createElement("code", null, h.batch_no)), /* @__PURE__ */ React.createElement("td", { className: "fw-bold" }, h.qty))))))));
}
function SettingsView({ store, onStoreSaved, notify }) {
  const [formData, setFormData] = useState({
    store_id: store ? store.store_id : 0,
    store_code: store ? store.store_code : "",
    store_slug: store ? store.store_slug : "",
    store_name: store ? store.store_name : "",
    building_name: store ? store.building_name : "",
    floor_no: store ? store.floor_no : "",
    room_no: store ? store.room_no : "",
    drug_license_no_20b: store ? store.drug_license_no_20b : "",
    drug_license_no_21b: store ? store.drug_license_no_21b : "",
    drug_license_no_20f_x: store ? store.drug_license_no_20f_x : "",
    gstin: store ? store.gstin : "",
    pan_no: store ? store.pan_no : "",
    fssai_no: store ? store.fssai_no : "",
    state_code: store ? store.state_code : "07",
    state_name: store ? store.state_name : "Delhi",
    registered_pharmacist_name: store ? store.registered_pharmacist_name : "",
    pharmacist_reg_no: store ? store.pharmacist_reg_no : "",
    pharmacist_hpr_id: store ? store.pharmacist_hpr_id || "" : "",
    abdm_hfr_id: store ? store.abdm_hfr_id || "" : "",
    abdm_hip_id: store ? store.abdm_hip_id || "" : "",
    invoice_prefix: store ? store.invoice_prefix : "INV/",
    next_invoice_no: store ? store.next_invoice_no : 1001,
    upi_id: store ? store.upi_id : "",
    terms_conditions: store ? store.terms_conditions : ""
  });
  const handleChange = (field, val) => {
    setFormData({ ...formData, [field]: val });
  };
  const handleSave = async (e) => {
    e.preventDefault();
    try {
      const res = await fetch(API_BASE + "stores/save", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formData)
      });
      const data = await res.json();
      if (data.status) {
        notify("Store & statutory licensing updated successfully!", "success");
        onStoreSaved();
      } else {
        notify(data.message || "Failed to update store.", "error");
      }
    } catch (e2) {
      notify("Save error: " + e2.message, "error");
    }
  };
  return /* @__PURE__ */ React.createElement("div", { className: "counter-card" }, /* @__PURE__ */ React.createElement("h5", { className: "fw-bold mb-3" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-building-gear text-primary me-1" }), " Hospital Pharmacy Store & Indian Licensing Configuration"), /* @__PURE__ */ React.createElement("form", { onSubmit: handleSave }, /* @__PURE__ */ React.createElement("div", { className: "row g-3 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-3" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Store Code"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", required: true, value: formData.store_code, onChange: (e) => handleChange("store_code", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-3" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "URL Slug (Direct Link)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", required: true, value: formData.store_slug, onChange: (e) => handleChange("store_slug", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-6" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Store / Pharmacy Name"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", required: true, value: formData.store_name, onChange: (e) => handleChange("store_name", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Building Name"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. OPD Block B", value: formData.building_name, onChange: (e) => handleChange("building_name", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Floor"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. 1st Floor", value: formData.floor_no, onChange: (e) => handleChange("floor_no", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Room / Counter No"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. Counter 2", value: formData.room_no, onChange: (e) => handleChange("room_no", e.target.value) }))), /* @__PURE__ */ React.createElement("h6", { className: "fw-bold text-primary mt-4 mb-2" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-check me-1" }), " Indian Drug & Tax Statutory Licenses"), /* @__PURE__ */ React.createElement("div", { className: "row g-3 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Drug License Form 20-B (Allopathic)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "DL-20B-DEL-XXXXX", value: formData.drug_license_no_20b, onChange: (e) => handleChange("drug_license_no_20b", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Drug License Form 21-B (Schedule C/C1)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "DL-21B-DEL-XXXXX", value: formData.drug_license_no_21b, onChange: (e) => handleChange("drug_license_no_21b", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Drug License Form 20-F (Schedule X)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "DL-20F-DEL-XXXXX", value: formData.drug_license_no_20f_x, onChange: (e) => handleChange("drug_license_no_20f_x", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "GSTIN (15-Digit)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm text-uppercase", placeholder: "07AAAAA0000A1Z5", value: formData.gstin, onChange: (e) => handleChange("gstin", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "PAN Number"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm text-uppercase", placeholder: "AAAAA0000A", value: formData.pan_no, onChange: (e) => handleChange("pan_no", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "FSSAI License No"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "100XXXXXXXXXX", value: formData.fssai_no, onChange: (e) => handleChange("fssai_no", e.target.value) }))), /* @__PURE__ */ React.createElement("h6", { className: "fw-bold text-primary mt-4 mb-2" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-person-badge me-1" }), " Pharmacist & POS Bill Configuration"), /* @__PURE__ */ React.createElement("div", { className: "row g-3 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-6" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Registered Pharmacist Name"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "Pharmacist Name", value: formData.registered_pharmacist_name, onChange: (e) => handleChange("registered_pharmacist_name", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-6" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "State Pharmacy Council Reg No"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. DPC-Reg-45892", value: formData.pharmacist_reg_no, onChange: (e) => handleChange("pharmacist_reg_no", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Bill Series Prefix"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. OPD1/26-27/", value: formData.invoice_prefix, onChange: (e) => handleChange("invoice_prefix", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Next Sequence Number"), /* @__PURE__ */ React.createElement("input", { type: "number", className: "form-control form-control-sm", value: formData.next_invoice_no, onChange: (e) => handleChange("next_invoice_no", e.target.value) })), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Store UPI ID (For Dynamic QR)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "hospital@upi", value: formData.upi_id, onChange: (e) => handleChange("upi_id", e.target.value) }))), /* @__PURE__ */ React.createElement("h6", { className: "fw-bold text-primary mt-4 mb-2" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-shield-fill-check me-1" }), " Ayushman Bharat Digital Mission (ABDM) Registry Configuration"), /* @__PURE__ */ React.createElement("div", { className: "row g-3 mb-3" }, /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "ABDM Health Facility Registry (HFR ID)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. IN0710001234", value: formData.abdm_hfr_id, onChange: (e) => handleChange("abdm_hfr_id", e.target.value) }), /* @__PURE__ */ React.createElement("small", { className: "text-muted", style: { fontSize: "0.7rem" } }, "Facility ID registered in National Health Authority HFR")), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Health Information Provider (HIP ID)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. IN0710001234-HIP", value: formData.abdm_hip_id, onChange: (e) => handleChange("abdm_hip_id", e.target.value) }), /* @__PURE__ */ React.createElement("small", { className: "text-muted", style: { fontSize: "0.7rem" } }, "Identifier configured in ABDM Gateway Bridge")), /* @__PURE__ */ React.createElement("div", { className: "col-md-4" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Pharmacist HPR ID (Healthcare Professional)"), /* @__PURE__ */ React.createElement("input", { type: "text", className: "form-control form-control-sm", placeholder: "e.g. 91-8822-4411-9901", value: formData.pharmacist_hpr_id, onChange: (e) => handleChange("pharmacist_hpr_id", e.target.value) }), /* @__PURE__ */ React.createElement("small", { className: "text-muted", style: { fontSize: "0.7rem" } }, "National Pharmacist ID from HPR Portal"))), /* @__PURE__ */ React.createElement("div", { className: "mb-3" }, /* @__PURE__ */ React.createElement("label", { className: "form-label small fw-bold" }, "Terms & Conditions (Printed on Invoice Footer)"), /* @__PURE__ */ React.createElement("textarea", { className: "form-control form-control-sm", rows: "2", value: formData.terms_conditions, onChange: (e) => handleChange("terms_conditions", e.target.value) })), /* @__PURE__ */ React.createElement("button", { type: "submit", className: "btn btn-primary-custom" }, /* @__PURE__ */ React.createElement("i", { className: "bi bi-save me-1" }), " Save Store & Licensing Details")));
}
const root = ReactDOM.createRoot(document.getElementById("root"));
root.render(/* @__PURE__ */ React.createElement(App, null));
