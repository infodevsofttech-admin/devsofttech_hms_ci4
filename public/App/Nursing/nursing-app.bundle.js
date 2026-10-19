import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
const {
  useState,
  useEffect
} = React;
const DEVICE_AUTH_KEY = 'nursing_pwa_device_auth_v1';
const THIRTY_DAYS_MS = 30 * 24 * 60 * 60 * 1000;
const MobileAppsModal = ({
  show,
  onClose,
  currentApp
}) => {
  if (!show) return null;
  const navigateToApp = appKey => {
    if (appKey === currentApp) {
      onClose();
      return;
    }
    if (appKey === 'doctor') {
      window.location.href = '/app/doctor';
    } else if (appKey === 'nursing') {
      window.location.href = '/app/nursing';
    }
  };
  return /*#__PURE__*/_jsx("div", {
    className: "modal fade show d-block",
    style: {
      backgroundColor: 'rgba(15, 23, 42, 0.7)',
      zIndex: 1055,
      backdropFilter: 'blur(4px)'
    },
    tabIndex: "-1",
    children: /*#__PURE__*/_jsx("div", {
      className: "modal-dialog modal-dialog-centered px-2",
      children: /*#__PURE__*/_jsxs("div", {
        className: "modal-content border-0 shadow-lg rounded-4 overflow-hidden",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "modal-header text-white p-3",
          style: {
            background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2",
            children: [/*#__PURE__*/_jsx("img", {
              src: "/assets/img/logo.png",
              alt: "Hospital Logo",
              style: {
                height: '28px',
                width: 'auto',
                filter: 'brightness(0) invert(1)'
              },
              onError: e => {
                e.target.style.display = 'none';
              }
            }), /*#__PURE__*/_jsxs("h6", {
              className: "modal-title fw-bold mb-0 text-white",
              style: {
                fontSize: '15px'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-grid-fill me-1 text-primary"
              }), " HMS Mobile & PWA Apps Launcher"]
            })]
          }), /*#__PURE__*/_jsx("button", {
            type: "button",
            className: "btn-close btn-close-white",
            onClick: onClose
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "modal-body p-3 bg-light",
          children: [/*#__PURE__*/_jsx("label", {
            className: "form-label fw-bold small text-secondary mb-2",
            style: {
              letterSpacing: '0.5px'
            },
            children: "1. SELECT APPLICATION"
          }), /*#__PURE__*/_jsxs("div", {
            className: "row g-2 mb-3",
            children: [/*#__PURE__*/_jsx("div", {
              className: "col-4",
              children: /*#__PURE__*/_jsxs("div", {
                className: `card p-2 text-center h-100 ${currentApp === 'nursing' ? 'border-2 border-primary bg-primary-subtle shadow-sm' : 'border bg-white text-dark'}`,
                style: {
                  cursor: 'pointer',
                  borderRadius: '12px'
                },
                onClick: () => navigateToApp('nursing'),
                children: [/*#__PURE__*/_jsx("div", {
                  className: "my-1",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-heart-pulse-fill fs-3 text-primary"
                  })
                }), /*#__PURE__*/_jsx("div", {
                  className: "fw-bold small text-truncate",
                  style: {
                    fontSize: '12px'
                  },
                  children: "Nursing Care"
                }), currentApp === 'nursing' ? /*#__PURE__*/_jsx("span", {
                  className: "badge bg-primary mt-1",
                  style: {
                    fontSize: '9px'
                  },
                  children: "● Active App"
                }) : /*#__PURE__*/_jsx("span", {
                  className: "badge bg-info-subtle text-info-emphasis border border-info-subtle mt-1",
                  style: {
                    fontSize: '9px'
                  },
                  children: "Active PWA"
                })]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-4",
              children: /*#__PURE__*/_jsxs("div", {
                className: `card p-2 text-center h-100 ${currentApp === 'doctor' ? 'border-2 border-primary bg-primary-subtle shadow-sm' : 'border bg-white text-dark'}`,
                style: {
                  cursor: 'pointer',
                  borderRadius: '12px'
                },
                onClick: () => navigateToApp('doctor'),
                children: [/*#__PURE__*/_jsx("div", {
                  className: "my-1",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-person-badge-fill fs-3 text-primary"
                  })
                }), /*#__PURE__*/_jsx("div", {
                  className: "fw-bold small text-truncate",
                  style: {
                    fontSize: '12px'
                  },
                  children: "Doctor App"
                }), currentApp === 'doctor' ? /*#__PURE__*/_jsx("span", {
                  className: "badge bg-primary mt-1",
                  style: {
                    fontSize: '9px'
                  },
                  children: "● Active App"
                }) : /*#__PURE__*/_jsx("span", {
                  className: "badge bg-success mt-1",
                  style: {
                    fontSize: '9px'
                  },
                  children: "Active PWA"
                })]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-4",
              children: /*#__PURE__*/_jsxs("div", {
                className: "card p-2 text-center border bg-white text-muted opacity-75 h-100",
                style: {
                  borderRadius: '12px',
                  cursor: 'not-allowed'
                },
                children: [/*#__PURE__*/_jsx("div", {
                  className: "my-1",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-capsule fs-3 text-secondary"
                  })
                }), /*#__PURE__*/_jsx("div", {
                  className: "fw-bold small text-truncate",
                  style: {
                    fontSize: '12px'
                  },
                  children: "Pharmacy App"
                }), /*#__PURE__*/_jsx("span", {
                  className: "badge bg-secondary mt-1",
                  style: {
                    fontSize: '9px'
                  },
                  children: "Coming Soon"
                })]
              })
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "p-3 bg-white rounded-3 border text-center shadow-sm",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center justify-content-center gap-2 mb-2",
              children: [/*#__PURE__*/_jsx("img", {
                src: "/assets/img/logo.png",
                alt: "Logo",
                style: {
                  height: '28px'
                },
                onError: e => e.target.style.display = 'none'
              }), /*#__PURE__*/_jsxs("div", {
                className: "text-start",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-bold small text-dark",
                  children: "HMS Unified Mobile Portal"
                }), /*#__PURE__*/_jsxs("div", {
                  className: "small text-muted",
                  style: {
                    fontSize: '10px'
                  },
                  children: ["Domain: ", window.location.host]
                })]
              })]
            }), /*#__PURE__*/_jsx("p", {
              className: "small text-muted mb-2",
              style: {
                fontSize: '11px'
              },
              children: "Tap any active app card above to switch between modules in your mobile app."
            }), /*#__PURE__*/_jsxs("a", {
              href: "/app",
              className: "btn btn-outline-primary btn-sm w-100 fw-bold rounded-pill",
              style: {
                fontSize: '12px'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-house-door me-1"
              }), " Open Mobile App Hub"]
            })]
          })]
        })]
      })
    })
  });
};
function NursingApp() {
  const [showAppsModal, setShowAppsModal] = useState(false);
  const [mainTab, setMainTab] = useState('ipd'); // 'ipd' or 'opd'
  const [beds, setBeds] = useState([]);
  const [loading, setLoading] = useState(true);
  const [selectedWard, setSelectedWard] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [activeBed, setActiveBed] = useState(null);
  const [activeTab, setActiveTab] = useState('vitals');
  const [workspaceData, setWorkspaceData] = useState([]);
  const [nurses, setNurses] = useState([]);

  // OPD Patients State
  const [opdAppointments, setOpdAppointments] = useState([]);
  const [opdCounts, setOpdCounts] = useState({
    all: 0,
    waiting: 0,
    visited: 0,
    booked: 0,
    cancelled: 0,
    pending_vitals: 0,
    done_vitals: 0
  });
  const [opdFilter, setOpdFilter] = useState('all');
  const [opdDateMode, setOpdDateMode] = useState('today');
  const [doctors, setDoctors] = useState([]);
  const [selectedDoctorId, setSelectedDoctorId] = useState('0');
  const [vitalsFilter, setVitalsFilter] = useState('all'); // 'all', 'pending', 'done'

  // Modals & Action State
  const [activeOpdForVitals, setActiveOpdForVitals] = useState(null);
  const [opdVitalsForm, setOpdVitalsForm] = useState({
    temp: '',
    pulse: '',
    bp_systolic: '',
    bp_diastolic: '',
    spo2: '',
    weight: '',
    height: '',
    rr_min: ''
  });
  const [scanTarget, setScanTarget] = useState(null); // OPD or IPD object
  const [scanType, setScanType] = useState('opd'); // 'opd' or 'ipd'
  const [scanDocCategory, setScanDocCategory] = useState('Paper Document');
  const [scanImageBase64, setScanImageBase64] = useState('');
  const [scanImagePreview, setScanImagePreview] = useState('');

  // ABDM M2 & Patient Search / Barcode Scanner State
  const [showPatientSearchModal, setShowPatientSearchModal] = useState(false);
  const [searchModalTab, setSearchModalTab] = useState('scan'); // 'scan' or 'type'
  const [patientSearchQuery, setPatientSearchQuery] = useState('');
  const [patientSearchResults, setPatientSearchResults] = useState([]);
  const [searchingPatient, setSearchingPatient] = useState(false);
  const [scannerActive, setScannerActive] = useState(false);
  const [scannerError, setScannerError] = useState('');

  // ABDM M2 Wellness Vitals Modal State
  const [activeWellnessPatient, setActiveWellnessPatient] = useState(null);
  const [wellnessModalTab, setWellnessModalTab] = useState('vitals'); // 'vitals', 'sugar', 'activity', 'lifestyle', 'women'
  const [wellnessForm, setWellnessForm] = useState({
    temperature_f: '',
    pulse_rate: '',
    resp_rate: '',
    bp_systolic: '',
    bp_diastolic: '',
    spo2: '',
    weight_kg: '',
    height_cm: '',
    pain_score: '',
    waist_circumference_cm: '',
    hip_circumference_cm: '',
    waist_hip_ratio: '',
    sugar_random: '',
    sugar_fasting: '',
    sugar_pp: '',
    hba1c: '',
    hemoglobin: '',
    daily_steps: '',
    sleep_hours: '',
    exercise_min_per_day: '',
    diet_type: '',
    tobacco_status: '',
    alcohol_status: '',
    women_lmp: '',
    women_pregnancy_status: '',
    general_advice: '',
    diet_lifestyle_note: ''
  });

  // ABDM M2 Health Document Modal State
  const [activeDocPatient, setActiveDocPatient] = useState(null);
  const [patientDocForm, setPatientDocForm] = useState({
    document_type: 'Health Document',
    document_date: new Date().toISOString().slice(0, 10),
    remarks: ''
  });
  const [isSavingDoc, setIsSavingDoc] = useState(false);

  // Vitals History Modal State
  const [wellnessHistoryPatient, setWellnessHistoryPatient] = useState(null);
  const [wellnessHistoryList, setWellnessHistoryList] = useState([]);
  const [loadingHistory, setLoadingHistory] = useState(false);

  // Auth & Device Security State
  const PAIRED_NURSE_KEY = 'hms_nursing_paired_profile';
  const [authenticatedNurse, setAuthenticatedNurse] = useState(null);
  const [pairedNurse, setPairedNurse] = useState(() => {
    try {
      const item = localStorage.getItem(PAIRED_NURSE_KEY);
      return item ? JSON.parse(item) : null;
    } catch (e) {
      return null;
    }
  });
  const [staffIdInput, setStaffIdInput] = useState('');
  const [deviceCodeInput, setDeviceCodeInput] = useState('');
  const [pinInput, setPinInput] = useState('');
  const [pinError, setPinError] = useState('');
  const [showPairingForm, setShowPairingForm] = useState(false);
  const [isVerifying, setIsVerifying] = useState(false);
  const [isSettingNewPin, setIsSettingNewPin] = useState(false);
  const [newPin, setNewPin] = useState('');
  const [confirmPin, setConfirmPin] = useState('');

  // Workspace Forms (IPD)
  const [vitalsForm, setVitalsForm] = useState({
    temperature_f: '',
    pulse_rate: '',
    resp_rate: '',
    bp_systolic: '',
    bp_diastolic: '',
    spo2: '',
    weight_kg: '',
    general_note: ''
  });
  const [fluidForm, setFluidForm] = useState({
    fluid_direction: 'intake',
    fluid_route: 'IV (Intravenous)',
    fluid_amount_ml: '',
    general_note: ''
  });
  const [treatmentForm, setTreatmentForm] = useState({
    treatment_text: '',
    general_note: ''
  });
  const [showAllDoctorOrders, setShowAllDoctorOrders] = useState(false);
  const nurseFetch = async (url, options = {}) => {
    const headers = Object.assign({}, options.headers || {});
    const storedPairing = localStorage.getItem(PAIRED_NURSE_KEY);
    if (storedPairing) {
      try {
        const parsed = JSON.parse(storedPairing);
        if (parsed && parsed.device_token && !headers['X-Device-Token']) {
          headers['X-Device-Token'] = parsed.device_token;
        }
      } catch (e) {}
    }
    const fetchOptions = Object.assign({}, options, {
      credentials: 'same-origin',
      headers: headers
    });
    const res = await fetch(url, fetchOptions);
    if (res.status === 401) {
      setAuthenticatedNurse(null);
      localStorage.removeItem(DEVICE_AUTH_KEY);
    }
    return res;
  };
  const fetchBeds = async () => {
    try {
      setLoading(true);
      const res = await nurseFetch('/api/v1/nursing/beds');
      const json = await res.json();
      if (json.status === 1 && json.data) {
        setBeds(json.data.beds || []);
      }
    } catch (err) {
      console.error('Failed to load beds', err);
    } finally {
      setLoading(false);
    }
  };
  const fetchDoctors = async () => {
    try {
      const res = await nurseFetch('/api/v1/doctor/list');
      const json = await res.json();
      if (json.status === 1 && json.data) {
        setDoctors(json.data);
      }
    } catch (err) {
      console.error('Failed to load doctors', err);
    }
  };
  const fetchOpdAppointments = async (dateModeOverride, docIdOverride, vitalsOverride) => {
    try {
      const mode = dateModeOverride !== undefined ? dateModeOverride : opdDateMode;
      const docId = docIdOverride !== undefined ? docIdOverride : selectedDoctorId;
      const vFilter = vitalsOverride !== undefined ? vitalsOverride : vitalsFilter;
      const res = await nurseFetch(`/api/v1/nursing/opd/list?date_mode=${mode}&doctor_id=${docId}&vitals_status=${vFilter}`);
      const json = await res.json();
      if (json.status === 1) {
        setOpdAppointments(json.appointments || []);
        if (json.counts) {
          setOpdCounts(json.counts);
        }
      }
    } catch (err) {
      console.error('Failed to load OPD appointments', err);
    }
  };
  useEffect(() => {
    const savedAuth = localStorage.getItem(DEVICE_AUTH_KEY);
    let hasValidAuth = false;
    if (savedAuth) {
      try {
        const parsed = JSON.parse(savedAuth);
        if (parsed && parsed.nurse && parsed.expires_at > Date.now()) {
          setAuthenticatedNurse(parsed.nurse);
          hasValidAuth = true;
        } else {
          localStorage.removeItem(DEVICE_AUTH_KEY);
        }
      } catch (e) {}
    }
    if (hasValidAuth) {
      fetchBeds();
      fetchDoctors();
      fetchOpdAppointments('today', '0', 'all');
    }
  }, []);
  const handleVerifyPin = async () => {
    const isPairing = !pairedNurse || showPairingForm;
    const targetStaffId = isPairing ? staffIdInput.trim() : pairedNurse.staff_id || '';
    if (!targetStaffId) {
      setPinError('Please enter your Staff ID or Username.');
      return;
    }
    if (isPairing && (!deviceCodeInput || deviceCodeInput.trim().length < 4)) {
      setPinError('Please enter the 6-digit Device Security Code from Nursing Staff Master.');
      return;
    }
    if (!pinInput || pinInput.length < 4) {
      setPinError('Enter your 4 to 6 digit Security PIN or Password.');
      return;
    }
    setPinError('');
    setIsVerifying(true);
    try {
      const body = new URLSearchParams();
      body.append('staff_id', targetStaffId);
      body.append('pin', pinInput);
      if (isPairing) {
        body.append('device_code', deviceCodeInput.trim());
      } else if (pairedNurse && pairedNurse.device_token) {
        body.append('device_token', pairedNurse.device_token);
      }
      const res = await fetch('/api/v1/nursing/auth/verify-pin', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body.toString()
      });
      const json = await res.json();
      if (json.status === 1 && json.nurse) {
        setAuthenticatedNurse(json.nurse);
        const deviceAuthObj = {
          nurse: json.nurse,
          expires_at: Date.now() + THIRTY_DAYS_MS
        };
        localStorage.setItem(DEVICE_AUTH_KEY, JSON.stringify(deviceAuthObj));
        const pairedObj = {
          id: json.nurse.id,
          staff_id: json.nurse.nurse_code || targetStaffId,
          name: json.nurse.name,
          device_token: json.device_token || ''
        };
        localStorage.setItem(PAIRED_NURSE_KEY, JSON.stringify(pairedObj));
        setPairedNurse(pairedObj);
        setShowPairingForm(false);
        setPinInput('');
        setDeviceCodeInput('');
        fetchBeds();
        fetchDoctors();
        fetchOpdAppointments(opdDateMode, selectedDoctorId, vitalsFilter);
      } else if (json.status === 2) {
        setIsSettingNewPin(true);
      } else {
        if (json.need_device_code) {
          setShowPairingForm(true);
        }
        setPinError(json.message || 'Incorrect Security PIN or Device Code.');
      }
    } catch (err) {
      setPinError('Connection error verifying PIN.');
    } finally {
      setIsVerifying(false);
    }
  };
  const handleSaveNewPin = async () => {
    if (!newPin || newPin.length < 4) {
      setPinError('PIN must be 4 to 6 numeric digits.');
      return;
    }
    if (newPin !== confirmPin) {
      setPinError('New PIN and Confirm PIN do not match.');
      return;
    }
    try {
      const targetNurseId = pairedNurse ? pairedNurse.id : authenticatedNurse ? authenticatedNurse.id : 0;
      const body = new URLSearchParams();
      body.append('nurse_id', targetNurseId);
      body.append('staff_id', staffIdInput.trim() || (pairedNurse ? pairedNurse.staff_id : ''));
      body.append('new_pin', newPin);
      const res = await fetch('/api/v1/nursing/auth/set-pin', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body.toString()
      });
      const json = await res.json();
      if (json.status === 1) {
        alert('Security PIN set successfully! Enter your PIN to unlock.');
        setIsSettingNewPin(false);
        setNewPin('');
        setConfirmPin('');
        setPinInput('');
      } else {
        setPinError(json.message || 'Failed to update PIN');
      }
    } catch (err) {
      setPinError('Error setting PIN.');
    }
  };
  const handleLogout = () => {
    setAuthenticatedNurse(null);
    localStorage.removeItem(DEVICE_AUTH_KEY);
    setPinInput('');
    setActiveBed(null);
  };
  const handleUnpairDevice = () => {
    if (confirm('Unpair this device? You will need the Staff ID and Device Security Code to pair it again.')) {
      setAuthenticatedNurse(null);
      setPairedNurse(null);
      localStorage.removeItem(DEVICE_AUTH_KEY);
      localStorage.removeItem(PAIRED_NURSE_KEY);
      setShowPairingForm(true);
      setPinInput('');
      setStaffIdInput('');
      setDeviceCodeInput('');
      setPinError('');
    }
  };
  const openWorkspace = async bed => {
    if (!bed.ipd_id || bed.status !== 'occupied') return;
    setActiveBed(bed);
    try {
      const res = await fetch(`/api/v1/nursing/workspace/${bed.ipd_id}`);
      const json = await res.json();
      if (json.status === 1) {
        setWorkspaceData(json.entries || []);
      }
    } catch (err) {
      console.error('Failed to load workspace', err);
    }
  };
  const handleSaveIpdEntry = async (entryType, formData) => {
    if (!activeBed || !activeBed.ipd_id) return;
    try {
      const payload = {
        entry_type: entryType,
        recorded_by: authenticatedNurse ? authenticatedNurse.name : 'Staff Nurse',
        recorded_by_id: authenticatedNurse ? authenticatedNurse.id : 0,
        ...formData
      };
      const res = await fetch(`/api/v1/nursing/entry/save/${activeBed.ipd_id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 1) {
        alert(json.message || 'Saved successfully!');
        if (entryType === 'vitals') {
          setVitalsForm({
            temperature_f: '',
            pulse_rate: '',
            resp_rate: '',
            bp_systolic: '',
            bp_diastolic: '',
            spo2: '',
            weight_kg: '',
            general_note: ''
          });
        } else if (entryType === 'fluid') {
          setFluidForm({
            fluid_direction: 'intake',
            fluid_route: 'IV (Intravenous)',
            fluid_amount_ml: '',
            general_note: ''
          });
        } else if (entryType === 'treatment') {
          setTreatmentForm({
            treatment_text: '',
            general_note: ''
          });
        }
        openWorkspace(activeBed);
      } else {
        alert(json.message || 'Failed to save entry');
      }
    } catch (err) {
      alert('Error saving nursing entry');
    }
  };

  // Open OPD Vitals Modal
  const openOpdVitalsModal = app => {
    setActiveOpdForVitals(app);
    setOpdVitalsForm({
      temp: app.temp || '',
      pulse: app.pulse || '',
      bp_systolic: app.bp || '',
      bp_diastolic: app.diastolic || '',
      spo2: app.spo2 || '',
      weight: app.weight || '',
      height: app.height || '',
      rr_min: app.rr_min || ''
    });
  };
  const handleSaveOpdVitals = async () => {
    if (!activeOpdForVitals) return;
    try {
      const payload = {
        nurse_id: authenticatedNurse ? authenticatedNurse.id : 0,
        nurse_name: authenticatedNurse ? authenticatedNurse.name : 'Nurse',
        ...opdVitalsForm
      };
      const res = await fetch(`/api/v1/nursing/opd/vitals/save/${activeOpdForVitals.opd_id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 1) {
        alert(json.message || 'OPD Patient Vitals saved successfully!');
        setActiveOpdForVitals(null);
        fetchOpdAppointments(opdDateMode);
      } else {
        alert(json.message || 'Failed to save vitals');
      }
    } catch (err) {
      alert('Error saving OPD Vitals');
    }
  };

  // Open Document Scan Modal (OPD / IPD)
  const openScanModal = (targetObj, targetType) => {
    setScanTarget(targetObj);
    setScanType(targetType);
    setScanDocCategory('Paper Document');
    setScanImageBase64('');
    setScanImagePreview('');
  };
  const [cropSrc, setCropSrc] = useState('');
  const [cropper, setCropper] = useState(null);
  const imageRef = React.useRef(null);
  const handleScanImageCapture = e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = event => {
      setCropSrc(event.target.result);
    };
    reader.readAsDataURL(file);
    e.target.value = '';
  };
  useEffect(() => {
    if (cropSrc && imageRef.current) {
      if (cropper) {
        cropper.destroy();
      }
      const newCropper = new Cropper(imageRef.current, {
        viewMode: 1,
        autoCropArea: 0.9,
        responsive: true,
        restore: false,
        guides: true,
        center: true,
        highlight: true,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false
      });
      setCropper(newCropper);
    }
  }, [cropSrc]);
  const applyCrop = () => {
    if (cropper) {
      const croppedCanvas = cropper.getCroppedCanvas({
        maxWidth: 1600,
        maxHeight: 1600,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
      });
      if (croppedCanvas) {
        const croppedBase64 = croppedCanvas.toDataURL('image/jpeg', 0.85);
        setScanImageBase64(croppedBase64);
        setScanImagePreview(croppedBase64);
      }
      cropper.destroy();
      setCropper(null);
      setCropSrc('');
    }
  };
  const cancelCrop = () => {
    if (cropper) {
      cropper.destroy();
      setCropper(null);
    }
    setCropSrc('');
  };
  const handleSaveScanDocument = async () => {
    if (!scanTarget || !scanImageBase64) {
      alert('Please capture or select a document photo.');
      return;
    }
    try {
      const payload = {
        nurse_id: authenticatedNurse ? authenticatedNurse.id : 0,
        nurse_name: authenticatedNurse ? authenticatedNurse.name : 'Nurse',
        document_type: scanDocCategory,
        image_base64: scanImageBase64
      };
      const url = scanType === 'opd' ? `/api/v1/nursing/opd/scan/save/${scanTarget.opd_id}` : `/api/v1/nursing/ipd/scan/save/${scanTarget.ipd_id || scanTarget.id}`;
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 1) {
        alert(json.message || 'Scanned Document uploaded successfully!');
        setScanTarget(null);
        if (scanType === 'opd') fetchOpdAppointments(opdDateMode);
        if (scanType === 'ipd' && activeBed) openWorkspace(activeBed);
      } else {
        alert(json.message || 'Failed to save document scan');
      }
    } catch (err) {
      alert('Error saving document scan');
    }
  };

  // -----------------------------------------------------------------
  // ABDM M2 PATIENT SEARCH & BARCODE / QR SCANNER HANDLERS
  // -----------------------------------------------------------------
  let scannerInstance = null;
  const startBarcodeQrScanner = () => {
    if (typeof Html5Qrcode === 'undefined') {
      setScannerError('Scanner library is loading. Please try in a moment.');
      return;
    }
    setScannerError('');
    setScannerActive(true);
    setTimeout(() => {
      const readerElem = document.getElementById('patient-qr-reader');
      if (!readerElem) return;
      try {
        if (scannerInstance) {
          scannerInstance.stop().catch(() => {}).finally(() => {
            initHtml5QrCode(readerElem);
          });
        } else {
          initHtml5QrCode(readerElem);
        }
      } catch (e) {
        setScannerError('Could not access camera: ' + (e.message || e));
        setScannerActive(false);
      }
    }, 300);
  };
  const initHtml5QrCode = readerElem => {
    try {
      const html5QrCode = new Html5Qrcode("patient-qr-reader");
      scannerInstance = html5QrCode;
      const config = {
        fps: 12,
        qrbox: {
          width: 280,
          height: 180
        },
        aspectRatio: 1.333334,
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE, Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.CODE_39, Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8, Html5QrcodeSupportedFormats.DATA_MATRIX]
      };
      html5QrCode.start({
        facingMode: "environment"
      }, config, decodedText => {
        // On successful scan
        stopBarcodeQrScanner();
        setSearchModalTab('type');
        setPatientSearchQuery(decodedText);
        executePatientSearch(decodedText);
      }, errorMessage => {
        // ignore frame errors
      }).catch(err => {
        setScannerError('Camera permission required or camera busy: ' + (err.message || err));
        setScannerActive(false);
      });
    } catch (err) {
      setScannerError('Failed to initialize camera scanner: ' + (err.message || err));
      setScannerActive(false);
    }
  };
  const stopBarcodeQrScanner = () => {
    if (scannerInstance) {
      try {
        scannerInstance.stop().then(() => {
          scannerInstance.clear();
          scannerInstance = null;
          setScannerActive(false);
        }).catch(() => {
          scannerInstance = null;
          setScannerActive(false);
        });
      } catch (e) {
        scannerInstance = null;
        setScannerActive(false);
      }
    } else {
      setScannerActive(false);
    }
  };
  const openPatientSearch = (defaultTab = 'scan') => {
    setShowPatientSearchModal(true);
    setSearchModalTab(defaultTab);
    setPatientSearchResults([]);
    setPatientSearchQuery('');
    if (defaultTab === 'scan') {
      startBarcodeQrScanner();
    } else {
      stopBarcodeQrScanner();
    }
  };
  const closePatientSearch = () => {
    stopBarcodeQrScanner();
    setShowPatientSearchModal(false);
    setPatientSearchResults([]);
    setPatientSearchQuery('');
  };
  const executePatientSearch = async queryText => {
    const q = queryText !== undefined ? queryText : patientSearchQuery;
    if (!q || q.trim() === '') {
      setPatientSearchResults([]);
      return;
    }
    setSearchingPatient(true);
    try {
      const res = await fetch(`/api/v1/nursing/patient/search?q=${encodeURIComponent(q.trim())}`);
      const json = await res.json();
      if (json.status === 1) {
        setPatientSearchResults(json.data || []);
      } else {
        setPatientSearchResults([]);
      }
    } catch (err) {
      console.error('Patient search error', err);
    } finally {
      setSearchingPatient(false);
    }
  };

  // ABDM M2 Wellness Actions
  const openWellnessModal = patient => {
    setActiveWellnessPatient(patient);
    setWellnessModalTab('vitals');
    const opdVitals = patient.opd_today || {};
    setWellnessForm({
      temperature_f: opdVitals.temp || '',
      pulse_rate: opdVitals.pulse || '',
      resp_rate: opdVitals.rr_min || '',
      bp_systolic: opdVitals.bp || '',
      bp_diastolic: opdVitals.diastolic || '',
      spo2: opdVitals.spo2 || '',
      weight_kg: opdVitals.weight || '',
      height_cm: opdVitals.height || '',
      pain_score: '',
      waist_circumference_cm: opdVitals.waist || '',
      hip_circumference_cm: '',
      waist_hip_ratio: '',
      sugar_random: opdVitals.glucose || '',
      sugar_fasting: '',
      sugar_pp: '',
      hba1c: '',
      hemoglobin: '',
      daily_steps: '',
      sleep_hours: '',
      exercise_min_per_day: '',
      diet_type: '',
      tobacco_status: '',
      alcohol_status: '',
      women_lmp: '',
      women_pregnancy_status: '',
      general_advice: '',
      diet_lifestyle_note: ''
    });
    setShowPatientSearchModal(false);
    stopBarcodeQrScanner();
  };
  const handleSaveWellness = async () => {
    if (!activeWellnessPatient) return;
    try {
      const payload = {
        patient_id: activeWellnessPatient.id,
        recorded_by: authenticatedNurse ? authenticatedNurse.name : 'Staff Nurse',
        recorded_by_id: authenticatedNurse ? authenticatedNurse.id : 0,
        ...wellnessForm
      };
      const res = await fetch('/api/v1/nursing/patient/wellness/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 1) {
        alert('✅ ' + (json.message || 'ABDM M2 Wellness Vitals saved successfully!') + (json.bmi ? ` (BMI: ${json.bmi})` : ''));
        setActiveWellnessPatient(null);
        setWellnessForm({
          temperature_f: '',
          pulse_rate: '',
          resp_rate: '',
          bp_systolic: '',
          bp_diastolic: '',
          spo2: '',
          weight_kg: '',
          height_cm: '',
          pain_score: '',
          waist_circumference_cm: '',
          hip_circumference_cm: '',
          waist_hip_ratio: '',
          sugar_random: '',
          sugar_fasting: '',
          sugar_pp: '',
          hba1c: '',
          hemoglobin: '',
          daily_steps: '',
          sleep_hours: '',
          exercise_min_per_day: '',
          diet_type: '',
          tobacco_status: '',
          alcohol_status: '',
          women_lmp: '',
          women_pregnancy_status: '',
          general_advice: '',
          diet_lifestyle_note: ''
        });
      } else {
        alert(json.message || 'Failed to save wellness vitals');
      }
    } catch (err) {
      alert('Error saving wellness vitals');
    }
  };

  // ABDM M2 Health Document Actions
  const openDocModal = patient => {
    setActiveDocPatient(patient);
    setScanImageBase64('');
    setScanImagePreview('');
    setPatientDocForm({
      document_type: 'Health Document',
      document_date: new Date().toISOString().slice(0, 10),
      remarks: ''
    });
    setShowPatientSearchModal(false);
    stopBarcodeQrScanner();
  };
  const handleSavePatientDocument = async () => {
    if (!activeDocPatient || isSavingDoc) return;
    if (!scanImageBase64) {
      alert('Please capture or upload a document photo first.');
      return;
    }
    setIsSavingDoc(true);
    try {
      const payload = {
        patient_id: activeDocPatient.id,
        nurse_name: authenticatedNurse ? authenticatedNurse.name : 'Staff Nurse',
        nurse_id: authenticatedNurse ? authenticatedNurse.id : 0,
        image_base64: scanImageBase64,
        ...patientDocForm
      };
      const res = await fetch('/api/v1/nursing/patient/document/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 1) {
        alert('✅ ' + (json.message || 'ABDM M2 Health Document uploaded successfully!'));
        setActiveDocPatient(null);
        setScanImageBase64('');
        setScanImagePreview('');
      } else {
        alert(json.message || 'Failed to upload health document');
      }
    } catch (err) {
      alert('Error uploading health document');
    } finally {
      setIsSavingDoc(false);
    }
  };

  // Vitals History Actions
  const openWellnessHistory = async patient => {
    setWellnessHistoryPatient(patient);
    setLoadingHistory(true);
    try {
      const res = await fetch(`/api/v1/nursing/patient/wellness/history/${patient.id}`);
      const json = await res.json();
      if (json.status === 1) {
        setWellnessHistoryList(json.records || []);
      }
    } catch (err) {
      console.error('Failed to load history', err);
    } finally {
      setLoadingHistory(false);
    }
  };
  const getInitials = nameStr => {
    if (!nameStr) return 'N';
    const parts = nameStr.trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return nameStr.substring(0, 2).toUpperCase();
  };
  const renderNoteContent = entry => {
    const text = entry.treatment_text || entry.general_note || '';
    const match = text.match(/\[IMAGE_ATTACHMENT:(.*?)\]/);
    const cleanText = text.replace(/\[IMAGE_ATTACHMENT:.*?\]/, '').replace('[Doctor Note] ', '');
    return /*#__PURE__*/_jsxs("div", {
      children: [cleanText && /*#__PURE__*/_jsx("div", {
        className: "small fw-semibold",
        children: cleanText
      }), match && match[1] && /*#__PURE__*/_jsxs("div", {
        className: "mt-2",
        children: [/*#__PURE__*/_jsxs("span", {
          className: "badge bg-primary mb-1 d-inline-flex align-items-center gap-1",
          style: {
            fontSize: '10px'
          },
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-paperclip"
          }), " Attached Document / Photo"]
        }), /*#__PURE__*/_jsx("a", {
          href: match[1],
          target: "_blank",
          rel: "noreferrer",
          children: /*#__PURE__*/_jsx("img", {
            src: match[1],
            alt: "Attached Document",
            className: "img-fluid rounded-3 border shadow-sm d-block mt-1",
            style: {
              maxHeight: '220px',
              objectFit: 'cover'
            }
          })
        })]
      })]
    });
  };
  const displayTempF = tempC => {
    if (tempC === null || tempC === undefined || tempC === '') return '-';
    const val = parseFloat(tempC) * 9 / 5 + 32;
    return val.toFixed(1) + '°F';
  };
  const formatDateTime = dtStr => {
    if (!dtStr) return '-';
    try {
      const d = new Date(dtStr.replace(/-/g, '/'));
      if (isNaN(d.getTime())) return dtStr;
      return d.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      }) + ' ' + d.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      });
    } catch (e) {
      return dtStr;
    }
  };

  // Filtered lists: Only Admitted IPD Patients with Bed (Occupied Beds)
  const filteredBeds = beds.filter(bed => {
    const isOccupied = bed.status === 'occupied' || bed.patient_name && bed.patient_name.trim() !== '' || Boolean(bed.ipd_id);
    const matchesWard = selectedWard === 'all' || bed.ward_name === selectedWard;
    const q = searchQuery.toLowerCase();
    const matchesSearch = searchQuery === '' || (bed.bed_number || '').toLowerCase().includes(q) || (bed.patient_name || '').toLowerCase().includes(q) || (bed.uhid || '').toLowerCase().includes(q) || (bed.ipd_code || '').toLowerCase().includes(q) || (bed.doctor_name || '').toLowerCase().includes(q);
    return isOccupied && matchesWard && matchesSearch;
  });
  const filteredOpdAppointments = opdAppointments.filter(app => {
    const matchesFilter = opdFilter === 'all' || app.status_key === opdFilter;
    const q = searchQuery.toLowerCase();
    const name = (app.patient_display_name || '').toLowerCase();
    const uhid = (app.uhid || '').toLowerCase();
    const opdCode = (app.opd_code || '').toLowerCase();
    const docName = (app.doctor_name || '').toLowerCase();
    return matchesFilter && (searchQuery === '' || name.includes(q) || uhid.includes(q) || opdCode.includes(q) || docName.includes(q));
  });

  // -------------------------------------------------------------
  // LOCK SCREEN (DEVICE REGISTRATION & PIN AUTHENTICATION)
  // -------------------------------------------------------------
  if (!authenticatedNurse) {
    const isQuickUnlock = Boolean(pairedNurse && !showPairingForm);
    return /*#__PURE__*/_jsxs("div", {
      className: "lock-screen-overlay",
      children: [/*#__PURE__*/_jsx(MobileAppsModal, {
        show: showAppsModal,
        onClose: () => setShowAppsModal(false),
        currentApp: "nursing"
      }), /*#__PURE__*/_jsxs("div", {
        className: "lock-card",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "p-4 text-center text-white position-relative",
          style: {
            background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)'
          },
          children: [/*#__PURE__*/_jsx("button", {
            type: "button",
            className: "btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2 rounded-circle",
            style: {
              width: '32px',
              height: '32px',
              padding: 0
            },
            onClick: () => setShowAppsModal(true),
            title: "HMS Mobile & PWA Apps Launcher",
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-grid-3x3-gap-fill",
              style: {
                fontSize: '12px'
              }
            })
          }), /*#__PURE__*/_jsx("img", {
            src: "/assets/img/logo.png",
            alt: "Hospital Logo",
            className: "d-block mx-auto mb-2",
            style: {
              maxHeight: '40px',
              filter: 'brightness(0) invert(1)'
            },
            onError: e => {
              e.target.style.display = 'none';
            }
          }), /*#__PURE__*/_jsx("h5", {
            className: "fw-bold mb-1",
            children: "Nursing Care Mobile App"
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center justify-content-center gap-1 opacity-75 small",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-shield-check text-success"
            }), /*#__PURE__*/_jsx("span", {
              children: "Hospital Intranet & Device Protected"
            })]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "p-4",
          children: [pinError && /*#__PURE__*/_jsx("div", {
            className: "alert alert-danger py-2 px-3 small mb-3",
            children: pinError
          }), !isSettingNewPin ? isQuickUnlock ?
          /*#__PURE__*/
          /* -------------------------------------------------------------
             PAIRED DEVICE QUICK UNLOCK
             ------------------------------------------------------------- */
          _jsxs("div", {
            children: [/*#__PURE__*/_jsxs("div", {
              className: "text-center mb-4",
              children: [/*#__PURE__*/_jsx("div", {
                className: "avatar-circle mx-auto mb-2",
                style: {
                  width: '54px',
                  height: '54px',
                  fontSize: '18px'
                },
                children: getInitials(pairedNurse.name || pairedNurse.staff_id)
              }), /*#__PURE__*/_jsx("h6", {
                className: "fw-bold text-dark mb-0",
                children: pairedNurse.name || 'Staff Nurse'
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-secondary-subtle text-dark border font-monospace mt-1",
                children: pairedNurse.staff_id
              }), /*#__PURE__*/_jsxs("div", {
                className: "text-muted small mt-1",
                style: {
                  fontSize: '11px'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-phone-fill me-1 text-primary"
                }), " Trusted Hospital Device"]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-4",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label fw-bold small text-muted",
                children: "Enter PIN or Password to Unlock"
              }), /*#__PURE__*/_jsx("input", {
                type: "password",
                maxLength: "20",
                className: "form-control text-center fs-3 letter-spacing-2",
                placeholder: "••••",
                value: pinInput,
                autoFocus: true,
                onChange: e => setPinInput(e.target.value),
                onKeyUp: e => e.key === 'Enter' && handleVerifyPin()
              }), /*#__PURE__*/_jsx("small", {
                className: "text-muted text-center d-block mt-1",
                style: {
                  fontSize: '11px'
                },
                children: "30-day verified session active"
              })]
            }), /*#__PURE__*/_jsx("button", {
              className: "btn btn-success btn-lg w-100 py-3 fw-bold shadow-sm mb-2",
              onClick: handleVerifyPin,
              disabled: isVerifying,
              children: isVerifying ? /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "spinner-border spinner-border-sm me-2"
                }), "Verifying..."]
              }) : /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-unlock-fill me-2"
                }), " Unlock Nursing App"]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "text-center mt-3 pt-2 border-top",
              children: /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-link btn-sm text-secondary text-decoration-none p-0",
                onClick: () => {
                  setShowPairingForm(true);
                  setPinError('');
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-repeat me-1"
                }), " Switch Account / Re-Pair Device"]
              })
            })]
          }) :
          /*#__PURE__*/
          /* -------------------------------------------------------------
             FIRST-TIME DEVICE REGISTRATION / PAIRING FORM
             ------------------------------------------------------------- */
          _jsxs("div", {
            children: [/*#__PURE__*/_jsxs("div", {
              className: "alert alert-primary py-2 px-3 small d-flex align-items-center gap-2 mb-3",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-shield-lock-fill fs-5 text-primary"
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-bold",
                  children: "First-Time Device Pairing"
                }), /*#__PURE__*/_jsx("div", {
                  style: {
                    fontSize: '11px'
                  },
                  children: "Enter Staff ID and Device Security Code from Nursing Staff Master."
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsxs("label", {
                className: "form-label fw-bold small text-dark mb-1",
                children: ["Staff ID or Username ", /*#__PURE__*/_jsx("span", {
                  className: "text-danger",
                  children: "*"
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "input-group",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "input-group-text bg-light",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-person-badge"
                  })
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control",
                  placeholder: "e.g. NUR-637 or username",
                  value: staffIdInput,
                  onChange: e => setStaffIdInput(e.target.value)
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsxs("label", {
                className: "form-label fw-bold small text-dark mb-1",
                children: ["Device Security Code ", /*#__PURE__*/_jsx("span", {
                  className: "text-danger",
                  children: "*"
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "input-group",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "input-group-text bg-light",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-key-fill text-warning"
                  })
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  maxLength: "10",
                  className: "form-control font-monospace fw-bold",
                  placeholder: "6-digit code from Admin",
                  value: deviceCodeInput,
                  onChange: e => setDeviceCodeInput(e.target.value)
                })]
              }), /*#__PURE__*/_jsx("small", {
                className: "text-muted",
                style: {
                  fontSize: '11px'
                },
                children: "Displayed on HMS Admin > Nursing Staff Master"
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-4",
              children: [/*#__PURE__*/_jsxs("label", {
                className: "form-label fw-bold small text-dark mb-1",
                children: ["App PIN or Password ", /*#__PURE__*/_jsx("span", {
                  className: "text-danger",
                  children: "*"
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "input-group",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "input-group-text bg-light",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-lock-fill"
                  })
                }), /*#__PURE__*/_jsx("input", {
                  type: "password",
                  maxLength: "20",
                  className: "form-control",
                  placeholder: "••••",
                  value: pinInput,
                  onChange: e => setPinInput(e.target.value),
                  onKeyUp: e => e.key === 'Enter' && handleVerifyPin()
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              className: "btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm",
              onClick: handleVerifyPin,
              disabled: isVerifying,
              children: isVerifying ? /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "spinner-border spinner-border-sm me-2"
                }), "Pairing Device..."]
              }) : /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-shield-check me-2"
                }), " Register Device & Unlock"]
              })
            }), pairedNurse && /*#__PURE__*/_jsx("div", {
              className: "text-center mt-3 pt-2 border-top",
              children: /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-link btn-sm text-secondary text-decoration-none p-0",
                onClick: () => {
                  setShowPairingForm(false);
                  setPinError('');
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-left me-1"
                }), " Back to Quick Unlock"]
              })
            })]
          }) :
          /*#__PURE__*/
          /* -------------------------------------------------------------
             INITIAL PIN CREATION (IF NURSE DOES NOT HAVE A PIN YET)
             ------------------------------------------------------------- */
          _jsxs("div", {
            children: [/*#__PURE__*/_jsx("div", {
              className: "alert alert-info py-2 small mb-3",
              children: "Initial PIN Setup for Nurse Profile. Set a 4-6 digit Security PIN."
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label fw-bold small",
                children: "New Security PIN"
              }), /*#__PURE__*/_jsx("input", {
                type: "password",
                maxLength: "6",
                className: "form-control text-center fs-3",
                placeholder: "••••",
                value: newPin,
                onChange: e => setNewPin(e.target.value)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label fw-bold small",
                children: "Confirm New PIN"
              }), /*#__PURE__*/_jsx("input", {
                type: "password",
                maxLength: "6",
                className: "form-control text-center fs-3",
                placeholder: "••••",
                value: confirmPin,
                onChange: e => setConfirmPin(e.target.value)
              })]
            }), /*#__PURE__*/_jsxs("button", {
              className: "btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm",
              onClick: handleSaveNewPin,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-check-circle-fill me-2"
              }), " Save PIN & Unlock"]
            })]
          })]
        })]
      })]
    });
  }

  // -------------------------------------------------------------
  // UNLOCKED NURSING PWA APP
  // -------------------------------------------------------------
  return /*#__PURE__*/_jsxs("div", {
    children: [/*#__PURE__*/_jsx(MobileAppsModal, {
      show: showAppsModal,
      onClose: () => setShowAppsModal(false),
      currentApp: "nursing"
    }), /*#__PURE__*/_jsxs("header", {
      className: "nursing-header d-flex justify-content-between align-items-center",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "d-flex align-items-center gap-2",
        children: [/*#__PURE__*/_jsx("div", {
          className: "avatar-circle",
          children: getInitials(authenticatedNurse.name)
        }), /*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsx("h6", {
            className: "mb-0 fw-bold",
            children: authenticatedNurse.name
          }), /*#__PURE__*/_jsxs("small", {
            className: "text-light opacity-75",
            style: {
              fontSize: '11px'
            },
            children: [authenticatedNurse.designation || 'Staff Nurse', " ", /*#__PURE__*/_jsx("span", {
              className: "badge bg-success ms-1",
              style: {
                fontSize: '9px'
              },
              children: "● Active"
            })]
          })]
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "d-flex align-items-center gap-2",
        children: [/*#__PURE__*/_jsx("button", {
          className: "btn btn-sm btn-outline-light rounded-circle p-2",
          style: {
            width: '36px',
            height: '36px'
          },
          onClick: () => setShowAppsModal(true),
          title: "HMS Mobile & PWA Apps Launcher",
          children: /*#__PURE__*/_jsx("i", {
            className: "bi bi-grid-3x3-gap-fill"
          })
        }), /*#__PURE__*/_jsx("button", {
          className: "btn btn-sm btn-outline-light rounded-circle p-2",
          style: {
            width: '36px',
            height: '36px'
          },
          onClick: () => {
            fetchBeds();
            fetchOpdAppointments(opdDateMode, selectedDoctorId, vitalsFilter);
          },
          title: "Refresh",
          children: /*#__PURE__*/_jsx("i", {
            className: "bi bi-arrow-clockwise"
          })
        }), /*#__PURE__*/_jsx("button", {
          className: "btn btn-sm btn-outline-danger rounded-circle p-2",
          style: {
            width: '36px',
            height: '36px'
          },
          onClick: handleLogout,
          title: "Lock Access",
          children: /*#__PURE__*/_jsx("i", {
            className: "bi bi-lock-fill"
          })
        })]
      })]
    }), /*#__PURE__*/_jsxs("main", {
      className: "container-fluid py-3 px-2 px-md-3 content-with-bottom-dock",
      children: [!activeBed ? /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "segmented-tabs mb-3",
          children: [/*#__PURE__*/_jsxs("button", {
            className: `segmented-tab ${mainTab === 'ipd' ? 'active' : ''}`,
            onClick: () => {
              setMainTab('ipd');
              fetchBeds();
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-hospital-fill me-2"
            }), " Admitted IPDs (", beds.filter(b => b.status === 'occupied' || b.patient_name).length, ")"]
          }), /*#__PURE__*/_jsxs("button", {
            className: `segmented-tab ${mainTab === 'opd' ? 'active-blue' : ''}`,
            onClick: () => {
              setMainTab('opd');
              fetchOpdAppointments(opdDateMode, selectedDoctorId, vitalsFilter);
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-calendar-check-fill me-2"
            }), " OPD Patients (", opdAppointments.length, ")"]
          })]
        }), mainTab === 'ipd' ?
        /*#__PURE__*/
        /* -------------------------------------------------------------
           ADMITTED IPD PATIENTS & BEDS
           ------------------------------------------------------------- */
        _jsxs("div", {
          children: [/*#__PURE__*/_jsxs("div", {
            className: "row g-2 mb-3 align-items-center",
            children: [/*#__PURE__*/_jsx("div", {
              className: "col-12 col-md-6",
              children: /*#__PURE__*/_jsxs("div", {
                className: "input-group shadow-sm rounded-pill overflow-hidden bg-white border",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "input-group-text bg-white border-0 ps-3 text-secondary",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-search"
                  })
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control border-0 py-2 shadow-none",
                  placeholder: "Search Bed No, Patient Name, UHID...",
                  value: searchQuery,
                  onChange: e => setSearchQuery(e.target.value)
                })]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-12 col-md-6",
              children: /*#__PURE__*/_jsxs("select", {
                className: "form-select rounded-pill shadow-sm py-2",
                value: selectedWard,
                onChange: e => setSelectedWard(e.target.value),
                children: [/*#__PURE__*/_jsx("option", {
                  value: "all",
                  children: "All Wards / Units"
                }), Array.from(new Set(beds.map(b => b.ward_name))).filter(Boolean).map(w => /*#__PURE__*/_jsx("option", {
                  value: w,
                  children: w
                }, w))]
              })
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "row g-3",
            children: filteredBeds.length === 0 ? /*#__PURE__*/_jsxs("div", {
              className: "col-12 text-center text-muted py-5 bg-white rounded-4 border shadow-sm",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-hospital fs-1 text-secondary opacity-50 d-block mb-2"
              }), /*#__PURE__*/_jsx("h6", {
                className: "fw-bold text-slate-800 mb-1",
                children: "No Admitted Patients Found"
              }), /*#__PURE__*/_jsx("small", {
                className: "text-muted",
                children: "There are currently no admitted IPD patients in the selected ward."
              })]
            }) : filteredBeds.map(bed => /*#__PURE__*/_jsx("div", {
              className: "col-12 col-md-6 col-lg-4",
              children: /*#__PURE__*/_jsxs("div", {
                className: `bed-card p-3 bed-status-${bed.status}`,
                onClick: () => openWorkspace(bed),
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-start mb-2",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "fw-bold text-dark fs-5",
                    children: ["Bed ", bed.bed_number]
                  }), /*#__PURE__*/_jsx("span", {
                    className: `badge ${bed.status === 'occupied' ? 'bg-primary' : 'bg-success'} text-uppercase`,
                    style: {
                      fontSize: '9px'
                    },
                    children: bed.status
                  })]
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-muted d-block mb-2",
                  style: {
                    fontSize: '11px'
                  },
                  children: bed.ward_name || 'General Ward'
                }), bed.patient_name ? /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "fw-bold text-primary text-truncate",
                    style: {
                      fontSize: '15px'
                    },
                    children: bed.patient_name
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-muted d-block",
                    style: {
                      fontSize: '11px'
                    },
                    children: ["UHID: ", bed.uhid || 'N/A', " • IPD: ", bed.ipd_code || 'N/A']
                  }), /*#__PURE__*/_jsxs("span", {
                    className: "badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mt-2 d-inline-block",
                    style: {
                      fontSize: '11px'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-person-badge me-1"
                    }), " ", bed.doctor_name || 'Dr. Assigned']
                  })]
                }) : /*#__PURE__*/_jsx("div", {
                  className: "text-success small fw-semibold py-2",
                  children: "Available for Admission"
                }), /*#__PURE__*/_jsx("div", {
                  className: "mt-3 pt-2 border-top d-flex gap-1",
                  children: /*#__PURE__*/_jsxs("button", {
                    className: "btn btn-sm btn-primary w-100 py-2 fw-bold rounded-3 shadow-sm",
                    style: {
                      fontSize: '12px'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-heart-pulse me-1"
                    }), " Open Bedside Workspace"]
                  })
                })]
              })
            }, bed.id))
          })]
        }) :
        /*#__PURE__*/
        /* -------------------------------------------------------------
           OPD PATIENTS TAB (VITALS & DOCUMENT SCANNING)
           ------------------------------------------------------------- */
        _jsxs("div", {
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center justify-content-between mb-3 bg-white p-2 rounded-4 border shadow-sm flex-wrap gap-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-1 bg-light p-1 rounded-pill border",
              children: [/*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm rounded-pill px-3 py-1 fw-bold ${opdDateMode === 'today' ? 'btn-primary shadow-sm' : 'btn-light text-secondary'}`,
                onClick: () => {
                  setOpdDateMode('today');
                  fetchOpdAppointments('today', selectedDoctorId, vitalsFilter);
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-calendar-event me-1"
                }), " Today's OPDs (", opdCounts.today_total || 0, ")"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm rounded-pill px-3 py-1 fw-bold ${opdDateMode === 'all' ? 'btn-primary shadow-sm' : 'btn-light text-secondary'}`,
                onClick: () => {
                  setOpdDateMode('all');
                  fetchOpdAppointments('all', selectedDoctorId, vitalsFilter);
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-calendar3 me-1"
                }), " All Dates (", opdCounts.all_total || 0, ")"]
              })]
            }), /*#__PURE__*/_jsx("span", {
              className: "badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small",
              style: {
                fontSize: '11px'
              },
              children: opdDateMode === 'today' ? '● Current Date' : '● All Appointments'
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "bg-white p-3 mb-3 rounded-4 border shadow-sm",
            children: /*#__PURE__*/_jsxs("div", {
              className: "row g-2 align-items-center",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "col-12 col-md-6",
                children: [/*#__PURE__*/_jsxs("label", {
                  className: "form-label small fw-bold text-dark mb-1",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-person-badge text-primary me-1"
                  }), " Filter by Doctor"]
                }), /*#__PURE__*/_jsxs("select", {
                  className: "form-select form-select-sm rounded-pill fw-semibold",
                  value: selectedDoctorId,
                  onChange: e => {
                    const val = e.target.value;
                    setSelectedDoctorId(val);
                    fetchOpdAppointments(opdDateMode, val, vitalsFilter);
                  },
                  children: [/*#__PURE__*/_jsx("option", {
                    value: "0",
                    children: "-- All Hospital Doctors --"
                  }), doctors.map(d => /*#__PURE__*/_jsx("option", {
                    value: d.id,
                    children: d.name
                  }, d.id))]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-12 col-md-6",
                children: [/*#__PURE__*/_jsxs("label", {
                  className: "form-label small fw-bold text-dark mb-1",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-heart-pulse-fill text-danger me-1"
                  }), " Vitals Status Filter"]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex gap-1",
                  children: [/*#__PURE__*/_jsxs("button", {
                    className: `btn btn-sm flex-fill rounded-pill py-1 fw-bold ${vitalsFilter === 'all' ? 'btn-primary shadow-sm' : 'btn-light border text-secondary'}`,
                    onClick: () => {
                      setVitalsFilter('all');
                      fetchOpdAppointments(opdDateMode, selectedDoctorId, 'all');
                    },
                    children: ["All (", opdCounts.all, ")"]
                  }), /*#__PURE__*/_jsxs("button", {
                    className: `btn btn-sm flex-fill rounded-pill py-1 fw-bold ${vitalsFilter === 'pending' ? 'btn-warning text-dark shadow-sm' : 'btn-light border text-secondary'}`,
                    onClick: () => {
                      setVitalsFilter('pending');
                      fetchOpdAppointments(opdDateMode, selectedDoctorId, 'pending');
                    },
                    children: ["⚠️ Pending (", opdCounts.pending_vitals || 0, ")"]
                  }), /*#__PURE__*/_jsxs("button", {
                    className: `btn btn-sm flex-fill rounded-pill py-1 fw-bold ${vitalsFilter === 'done' ? 'btn-success text-white shadow-sm' : 'btn-light border text-secondary'}`,
                    onClick: () => {
                      setVitalsFilter('done');
                      fetchOpdAppointments(opdDateMode, selectedDoctorId, 'done');
                    },
                    children: ["✅ Filled (", opdCounts.done_vitals || 0, ")"]
                  })]
                })]
              })]
            })
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex gap-2 mb-3 overflow-auto no-scrollbar pb-1",
            children: [/*#__PURE__*/_jsx("button", {
              className: `btn btn-sm rounded-pill px-3 ${opdFilter === 'all' ? 'btn-primary fw-bold' : 'btn-white bg-white text-secondary border'}`,
              onClick: () => setOpdFilter('all'),
              children: "All Visits"
            }), /*#__PURE__*/_jsx("button", {
              className: `btn btn-sm rounded-pill px-3 ${opdFilter === 'waiting' ? 'btn-primary fw-bold' : 'btn-white bg-white text-secondary border'}`,
              onClick: () => setOpdFilter('waiting'),
              children: "Waiting"
            }), /*#__PURE__*/_jsx("button", {
              className: `btn btn-sm rounded-pill px-3 ${opdFilter === 'visited' ? 'btn-info text-dark fw-bold' : 'btn-white bg-white text-secondary border'}`,
              onClick: () => setOpdFilter('visited'),
              children: "Visited"
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "input-group mb-3 shadow-sm rounded-pill overflow-hidden bg-white border",
            children: [/*#__PURE__*/_jsx("span", {
              className: "input-group-text bg-white border-0 ps-3 text-secondary",
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-search"
              })
            }), /*#__PURE__*/_jsx("input", {
              type: "text",
              className: "form-control border-0 py-2 shadow-none",
              placeholder: "Search Patient Name, Doctor Name, UHID, OPD Code...",
              value: searchQuery,
              onChange: e => setSearchQuery(e.target.value)
            })]
          }), /*#__PURE__*/_jsx("div", {
            children: filteredOpdAppointments.length === 0 ? /*#__PURE__*/_jsxs("div", {
              className: "text-center text-muted py-5 bg-white rounded-4 border shadow-sm",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-person-x fs-1 text-secondary opacity-50 d-block mb-2"
              }), "No OPD appointments found for selected filter."]
            }) : filteredOpdAppointments.map(app => /*#__PURE__*/_jsxs("div", {
              className: "mobile-patient-card",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex align-items-center gap-2",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "queue-badge",
                    children: ["Q #", app.queue_no || app.opd_no || '1']
                  }), /*#__PURE__*/_jsxs("span", {
                    className: `status-pill ${app.status_key}`,
                    children: ["● ", app.status_label]
                  })]
                }), /*#__PURE__*/_jsx("code", {
                  className: "fw-bold text-dark fs-6",
                  children: app.opd_code
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-start gap-3 mb-3",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "patient-avatar-circle",
                  children: getInitials(app.patient_display_name)
                }), /*#__PURE__*/_jsxs("div", {
                  className: "flex-grow-1",
                  children: [/*#__PURE__*/_jsx("h5", {
                    className: "fw-bold text-slate-900 mb-1",
                    style: {
                      fontSize: '16px'
                    },
                    children: app.patient_display_name
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-muted d-block mb-2",
                    style: {
                      fontSize: '12px'
                    },
                    children: [app.age_display, " • ", app.gender_label, " | UHID: ", app.uhid]
                  }), /*#__PURE__*/_jsxs("span", {
                    className: "badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1",
                    style: {
                      fontSize: '11px'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-person-badge me-1"
                    }), app.doctor_name || 'Doctor']
                  })]
                })]
              }), app.has_vitals && /*#__PURE__*/_jsxs("div", {
                className: "alert alert-success py-1.5 px-3 mb-3 small rounded-3 d-flex align-items-center justify-content-between",
                style: {
                  fontSize: '12px'
                },
                children: [/*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-heart-pulse-fill text-danger me-1"
                  }), " ", /*#__PURE__*/_jsx("strong", {
                    children: "Vitals:"
                  }), " BP ", app.bp || '-', "/", app.diastolic || '-', " | Pulse ", app.pulse || '-', " | Temp ", app.temp || '-', "°F"]
                }), /*#__PURE__*/_jsx("span", {
                  className: "badge bg-success",
                  children: "Vitals ✓"
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex gap-2",
                children: [/*#__PURE__*/_jsxs("button", {
                  className: `btn ${app.has_vitals ? 'btn-success' : 'btn-outline-success'} w-50 py-2 fw-bold rounded-3 shadow-sm`,
                  onClick: () => openOpdVitalsModal(app),
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-heart-pulse me-1"
                  }), " ", app.has_vitals ? 'Vitals ✓' : 'Fill Vitals']
                }), /*#__PURE__*/_jsxs("button", {
                  className: "btn btn-outline-primary w-50 py-2 fw-bold rounded-3 shadow-sm",
                  onClick: () => openScanModal(app, 'opd'),
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-camera me-1"
                  }), " Scan / Photo"]
                })]
              })]
            }, app.opd_id))
          })]
        })]
      }) :
      /*#__PURE__*/
      /* -------------------------------------------------------------
         IPD BEDSIDE WORKSPACE FOR NURSING STAFF
         ------------------------------------------------------------- */
      _jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between align-items-center mb-3",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-sm btn-outline-dark rounded-pill px-3",
            onClick: () => setActiveBed(null),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-left me-1"
            }), " Back to Bed List"]
          }), /*#__PURE__*/_jsxs("button", {
            className: "btn btn-sm btn-outline-primary fw-bold rounded-pill px-3",
            onClick: () => openScanModal(activeBed, 'ipd'),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-camera me-1"
            }), " Scan Paper Doc"]
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "card shadow-sm mb-3 border-0 bg-primary text-white rounded-4",
          style: {
            background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)'
          },
          children: /*#__PURE__*/_jsxs("div", {
            className: "card-body p-3",
            children: [/*#__PURE__*/_jsx("h5", {
              className: "fw-bold mb-1",
              children: activeBed.patient_name
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex flex-wrap gap-2 opacity-90 small mt-2",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "badge bg-white text-dark",
                children: ["Bed ", activeBed.bed_number, " (", activeBed.ward_name, ")"]
              }), /*#__PURE__*/_jsxs("span", {
                className: "badge bg-white text-danger",
                children: ["UHID: ", activeBed.uhid]
              }), /*#__PURE__*/_jsxs("span", {
                className: "badge bg-white text-primary",
                children: ["IPD: ", activeBed.ipd_code]
              })]
            })]
          })
        }), /*#__PURE__*/_jsxs("ul", {
          className: "nav nav-pills mb-3 bg-white p-1 rounded-3 border shadow-sm",
          children: [/*#__PURE__*/_jsx("li", {
            className: "nav-item flex-fill text-center",
            children: /*#__PURE__*/_jsxs("button", {
              className: `nav-link w-100 fw-bold ${activeTab === 'vitals' ? 'active' : ''}`,
              onClick: () => setActiveTab('vitals'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-heart-pulse me-1"
              }), " Vitals (", workspaceData.filter(e => e.entry_type === 'vitals').length, ")"]
            })
          }), /*#__PURE__*/_jsx("li", {
            className: "nav-item flex-fill text-center",
            children: /*#__PURE__*/_jsxs("button", {
              className: `nav-link w-100 fw-bold ${activeTab === 'fluid' ? 'active' : ''}`,
              onClick: () => setActiveTab('fluid'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-droplet me-1"
              }), " Fluid I/O (", workspaceData.filter(e => e.entry_type === 'fluid').length, ")"]
            })
          }), /*#__PURE__*/_jsx("li", {
            className: "nav-item flex-fill text-center",
            children: /*#__PURE__*/_jsxs("button", {
              className: `nav-link w-100 fw-bold ${activeTab === 'treatment' ? 'active' : ''}`,
              onClick: () => setActiveTab('treatment'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-clipboard2-pulse me-1"
              }), " Treatment & Orders (", workspaceData.filter(e => e.entry_type === 'treatment').length, ")"]
            })
          })]
        }), activeTab === 'vitals' && /*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("div", {
            className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
            children: [/*#__PURE__*/_jsxs("h6", {
              className: "fw-bold mb-3 text-dark",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-heart-pulse-fill text-danger me-1"
              }), " Record Bedside Vitals"]
            }), /*#__PURE__*/_jsxs("div", {
              className: "row g-2 mb-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "col-6 col-md-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small mb-1 fw-bold",
                  children: "BP Sys/Dia (mmHg)"
                }), /*#__PURE__*/_jsxs("div", {
                  className: "input-group input-group-sm",
                  children: [/*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control text-center",
                    placeholder: "120",
                    value: vitalsForm.bp_systolic,
                    onChange: e => setVitalsForm({
                      ...vitalsForm,
                      bp_systolic: e.target.value
                    })
                  }), /*#__PURE__*/_jsx("span", {
                    className: "input-group-text",
                    children: "/"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control text-center",
                    placeholder: "80",
                    value: vitalsForm.bp_diastolic,
                    onChange: e => setVitalsForm({
                      ...vitalsForm,
                      bp_diastolic: e.target.value
                    })
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-6 col-md-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small mb-1 fw-bold",
                  children: "Pulse Rate (bpm)"
                }), /*#__PURE__*/_jsx("input", {
                  type: "number",
                  className: "form-control form-control-sm text-center",
                  placeholder: "72",
                  value: vitalsForm.pulse_rate,
                  onChange: e => setVitalsForm({
                    ...vitalsForm,
                    pulse_rate: e.target.value
                  })
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-6 col-md-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small mb-1 fw-bold",
                  children: "SpO2 %"
                }), /*#__PURE__*/_jsx("input", {
                  type: "number",
                  className: "form-control form-control-sm text-center",
                  placeholder: "98",
                  value: vitalsForm.spo2,
                  onChange: e => setVitalsForm({
                    ...vitalsForm,
                    spo2: e.target.value
                  })
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-6 col-md-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small mb-1 fw-bold",
                  children: "Temp °F"
                }), /*#__PURE__*/_jsx("input", {
                  type: "number",
                  step: "0.1",
                  className: "form-control form-control-sm text-center",
                  placeholder: "98.6",
                  value: vitalsForm.temperature_f,
                  onChange: e => setVitalsForm({
                    ...vitalsForm,
                    temperature_f: e.target.value
                  })
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-6 col-md-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small mb-1 fw-bold",
                  children: "Resp Rate (/min)"
                }), /*#__PURE__*/_jsx("input", {
                  type: "number",
                  className: "form-control form-control-sm text-center",
                  placeholder: "18",
                  value: vitalsForm.resp_rate,
                  onChange: e => setVitalsForm({
                    ...vitalsForm,
                    resp_rate: e.target.value
                  })
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-6 col-md-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small mb-1 fw-bold",
                  children: "Weight (kg)"
                }), /*#__PURE__*/_jsx("input", {
                  type: "number",
                  step: "0.1",
                  className: "form-control form-control-sm text-center",
                  placeholder: "70",
                  value: vitalsForm.weight_kg,
                  onChange: e => setVitalsForm({
                    ...vitalsForm,
                    weight_kg: e.target.value
                  })
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-12 col-md-6",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small mb-1 fw-bold",
                  children: "Nursing Remark / Observation"
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm",
                  placeholder: "e.g. Patient resting, vitals stable",
                  value: vitalsForm.general_note,
                  onChange: e => setVitalsForm({
                    ...vitalsForm,
                    general_note: e.target.value
                  })
                })]
              })]
            }), /*#__PURE__*/_jsxs("button", {
              className: "btn btn-success fw-bold py-2 w-100 rounded-3 shadow-sm",
              onClick: () => handleSaveIpdEntry('vitals', vitalsForm),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-check-circle-fill me-1"
              }), " Save Bedside Vitals"]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "card shadow-sm border-0 rounded-4 overflow-hidden mb-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header py-2 bg-white d-flex justify-content-between align-items-center border-bottom",
              children: [/*#__PURE__*/_jsxs("strong", {
                className: "text-dark",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history me-1 text-danger"
                }), " Vitals History Records"]
              }), /*#__PURE__*/_jsxs("span", {
                className: "badge bg-danger-subtle text-danger border border-danger-subtle",
                children: [workspaceData.filter(e => e.entry_type === 'vitals').length, " Records"]
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "table-responsive",
              children: /*#__PURE__*/_jsxs("table", {
                className: "table table-sm table-striped table-hover align-middle mb-0",
                style: {
                  fontSize: '12px'
                },
                children: [/*#__PURE__*/_jsx("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("th", {
                      children: "Date & Time"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "BP (mmHg)"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Pulse"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Temp"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Resp"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "SpO2"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Weight"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Staff"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Remarks"
                    })]
                  })
                }), /*#__PURE__*/_jsx("tbody", {
                  children: workspaceData.filter(e => e.entry_type === 'vitals').length === 0 ? /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsx("td", {
                      colSpan: "9",
                      className: "text-center text-muted py-4",
                      children: "No vitals records entered yet."
                    })
                  }) : workspaceData.filter(e => e.entry_type === 'vitals').map(v => /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("td", {
                      className: "fw-semibold text-nowrap",
                      children: formatDateTime(v.recorded_at)
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("span", {
                        className: "badge bg-light text-dark border",
                        children: v.bp_systolic ? `${v.bp_systolic}/${v.bp_diastolic}` : '-'
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      children: v.pulse_rate ? `${v.pulse_rate} bpm` : '-'
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("span", {
                        className: "badge bg-danger-subtle text-danger",
                        children: displayTempF(v.temperature_c)
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      children: v.resp_rate ? `${v.resp_rate}/min` : '-'
                    }), /*#__PURE__*/_jsx("td", {
                      children: v.spo2 ? `${v.spo2}%` : '-'
                    }), /*#__PURE__*/_jsx("td", {
                      children: v.weight_kg ? `${v.weight_kg} kg` : '-'
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        children: v.recorded_by || 'Staff'
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("small", {
                        children: v.general_note || '-'
                      })
                    })]
                  }, v.id))
                })]
              })
            })]
          })]
        }), activeTab === 'fluid' && (() => {
          const fluidList = workspaceData.filter(e => e.entry_type === 'fluid');
          const totalIn = fluidList.filter(e => (e.fluid_direction || '').toLowerCase() === 'intake').reduce((sum, e) => sum + (parseFloat(e.fluid_amount_ml) || 0), 0);
          const totalOut = fluidList.filter(e => (e.fluid_direction || '').toLowerCase() === 'output').reduce((sum, e) => sum + (parseFloat(e.fluid_amount_ml) || 0), 0);
          const netDiff = totalIn - totalOut;
          const intakeRoutes = ['IV (Intravenous)', 'Oral / Water / Juice', 'Ryles Tube / NG Feed', 'Normal Saline (NS)', 'Dextrose (DNS / RL)', 'Blood / Plasma', 'TPN', 'Other Intake'];
          const outputRoutes = ['Urine / Foley Catheter', 'Drain / Ryle Tube', 'Vomitus', 'Stool / Loose Motion', 'Chest Tube Drain', 'Wound Drain', 'Other Output'];
          return /*#__PURE__*/_jsxs("div", {
            children: [/*#__PURE__*/_jsxs("div", {
              className: "row g-2 mb-3",
              children: [/*#__PURE__*/_jsx("div", {
                className: "col-4",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "stat-card-intake text-center shadow-sm",
                  children: [/*#__PURE__*/_jsxs("small", {
                    className: "text-success fw-bold d-block mb-1",
                    style: {
                      fontSize: '11px'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-arrow-down-left-circle-fill me-1"
                    }), " Total Intake"]
                  }), /*#__PURE__*/_jsxs("h5", {
                    className: "fw-bold text-success mb-0",
                    children: [totalIn.toLocaleString(), " ", /*#__PURE__*/_jsx("span", {
                      style: {
                        fontSize: '11px'
                      },
                      children: "ml"
                    })]
                  })]
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "col-4",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "stat-card-output text-center shadow-sm",
                  children: [/*#__PURE__*/_jsxs("small", {
                    className: "text-warning fw-bold d-block mb-1",
                    style: {
                      fontSize: '11px'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-arrow-up-right-circle-fill me-1"
                    }), " Total Output"]
                  }), /*#__PURE__*/_jsxs("h5", {
                    className: "fw-bold text-warning mb-0",
                    children: [totalOut.toLocaleString(), " ", /*#__PURE__*/_jsx("span", {
                      style: {
                        fontSize: '11px'
                      },
                      children: "ml"
                    })]
                  })]
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "col-4",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "stat-card-balance text-center shadow-sm",
                  children: [/*#__PURE__*/_jsxs("small", {
                    className: "text-primary fw-bold d-block mb-1",
                    style: {
                      fontSize: '11px'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-calculator me-1"
                    }), " Difference (In-Out)"]
                  }), /*#__PURE__*/_jsxs("h5", {
                    className: `fw-bold mb-0 ${netDiff >= 0 ? 'text-primary' : 'text-danger'}`,
                    children: [netDiff > 0 ? `+${netDiff.toLocaleString()}` : netDiff.toLocaleString(), " ", /*#__PURE__*/_jsx("span", {
                      style: {
                        fontSize: '11px'
                      },
                      children: "ml"
                    })]
                  })]
                })
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "fw-bold mb-3 text-dark",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-droplet-fill text-primary me-1"
                }), " Add Fluid Intake / Output Entry"]
              }), /*#__PURE__*/_jsxs("div", {
                className: "mb-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small fw-bold mb-1",
                  children: "Direction"
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex gap-2",
                  children: [/*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: `btn btn-sm flex-fill py-2 fw-bold rounded-3 ${fluidForm.fluid_direction === 'intake' ? 'btn-success text-white shadow-sm' : 'btn-light border text-secondary'}`,
                    onClick: () => setFluidForm({
                      ...fluidForm,
                      fluid_direction: 'intake',
                      fluid_route: 'IV (Intravenous)'
                    }),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-arrow-down-left-circle me-1"
                    }), " Fluid Intake (In)"]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: `btn btn-sm flex-fill py-2 fw-bold rounded-3 ${fluidForm.fluid_direction === 'output' ? 'btn-warning text-dark shadow-sm' : 'btn-light border text-secondary'}`,
                    onClick: () => setFluidForm({
                      ...fluidForm,
                      fluid_direction: 'output',
                      fluid_route: 'Urine / Foley Catheter'
                    }),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-arrow-up-right-circle me-1"
                    }), " Fluid Output (Out)"]
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2 mb-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Route / Site"
                  }), /*#__PURE__*/_jsx("select", {
                    className: "form-select form-select-sm",
                    value: fluidForm.fluid_route,
                    onChange: e => setFluidForm({
                      ...fluidForm,
                      fluid_route: e.target.value
                    }),
                    children: (fluidForm.fluid_direction === 'intake' ? intakeRoutes : outputRoutes).map(r => /*#__PURE__*/_jsx("option", {
                      value: r,
                      children: r
                    }, r))
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-6",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label small fw-bold mb-1",
                    children: ["Amount (ml) ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center fw-bold fs-6",
                    placeholder: "e.g. 500",
                    value: fluidForm.fluid_amount_ml,
                    onChange: e => setFluidForm({
                      ...fluidForm,
                      fluid_amount_ml: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Observation / Note"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    placeholder: "e.g. Clear urine, Normal fluid color, IV site intact",
                    value: fluidForm.general_note,
                    onChange: e => setFluidForm({
                      ...fluidForm,
                      general_note: e.target.value
                    })
                  })]
                })]
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-primary fw-bold py-2 w-100 rounded-3 shadow-sm",
                onClick: () => {
                  if (!fluidForm.fluid_amount_ml) {
                    alert('Please enter fluid amount in ml.');
                    return;
                  }
                  handleSaveIpdEntry('fluid', fluidForm);
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-plus-circle-fill me-1"
                }), " Save Fluid Entry"]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "card shadow-sm border-0 rounded-4 overflow-hidden mb-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card-header py-2 bg-white d-flex justify-content-between align-items-center border-bottom",
                children: [/*#__PURE__*/_jsxs("strong", {
                  className: "text-dark",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-clock-history me-1 text-primary"
                  }), " Fluid Intake & Output Log"]
                }), /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-primary-subtle text-primary border border-primary-subtle",
                  children: [fluidList.length, " Records"]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "table-responsive",
                children: /*#__PURE__*/_jsxs("table", {
                  className: "table table-sm table-striped table-hover align-middle mb-0",
                  style: {
                    fontSize: '12px'
                  },
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-light",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        children: "Date & Time"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Direction"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Route"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Amount (ml)"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Observation / Note"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Staff"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: fluidList.length === 0 ? /*#__PURE__*/_jsx("tr", {
                      children: /*#__PURE__*/_jsx("td", {
                        colSpan: "6",
                        className: "text-center text-muted py-4",
                        children: "No fluid entries recorded yet."
                      })
                    }) : fluidList.map(fl => /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("td", {
                        className: "fw-semibold text-nowrap",
                        children: formatDateTime(fl.recorded_at)
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("span", {
                          className: `badge ${fl.fluid_direction === 'intake' ? 'bg-success' : 'bg-warning text-dark'} text-uppercase`,
                          children: fl.fluid_direction === 'intake' ? '↓ Intake' : '↑ Output'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        className: "fw-semibold",
                        children: fl.fluid_route || '-'
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "fw-bold",
                        children: [parseFloat(fl.fluid_amount_ml || 0).toLocaleString(), " ml"]
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("small", {
                          children: fl.general_note || '-'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("small", {
                          className: "text-muted",
                          children: fl.recorded_by || 'Staff'
                        })
                      })]
                    }, fl.id))
                  })]
                })
              })]
            })]
          });
        })(), activeTab === 'treatment' && (() => {
          const allTreatments = workspaceData.filter(e => e.entry_type === 'treatment');
          const doctorOrders = allTreatments.filter(e => e.recorded_by && e.recorded_by.toLowerCase().startsWith('dr.') || e.treatment_text && e.treatment_text.includes('[Doctor Note]'));
          const latestDocOrder = doctorOrders.length > 0 ? doctorOrders[0] : null;
          const nurseTreatments = allTreatments.filter(e => !(e.recorded_by && e.recorded_by.toLowerCase().startsWith('dr.')) && !(e.treatment_text && e.treatment_text.includes('[Doctor Note]')));
          return /*#__PURE__*/_jsxs("div", {
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card shadow-sm border-0 mb-3 rounded-4 overflow-hidden",
              style: {
                border: '1.5px solid #818cf8'
              },
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card-header py-2 text-white d-flex justify-content-between align-items-center",
                style: {
                  background: 'linear-gradient(135deg, #3730a3 0%, #4f46e5 100%)'
                },
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex align-items-center gap-2",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-file-earmark-medical-fill"
                  }), /*#__PURE__*/_jsx("strong", {
                    className: "small",
                    children: "Doctor's Prescribed Treatment & Orders"
                  })]
                }), /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-white text-dark small",
                  children: [doctorOrders.length, " Doctor Orders"]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "card-body p-3 bg-light",
                children: latestDocOrder ? /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-start mb-2",
                    children: [/*#__PURE__*/_jsxs("span", {
                      className: "badge bg-primary px-2 py-1",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-person-badge me-1"
                      }), " ", latestDocOrder.recorded_by]
                    }), /*#__PURE__*/_jsx("small", {
                      className: "text-muted fw-semibold",
                      children: formatDateTime(latestDocOrder.recorded_at)
                    })]
                  }), /*#__PURE__*/_jsx("div", {
                    className: "p-3 bg-white rounded-3 border mb-2 shadow-sm",
                    children: renderNoteContent(latestDocOrder)
                  }), /*#__PURE__*/_jsxs("button", {
                    className: "btn btn-sm btn-outline-primary fw-bold w-100 rounded-pill py-2",
                    onClick: () => {
                      const cleanText = (latestDocOrder.treatment_text || '').replace(/\[IMAGE_ATTACHMENT:.*?\]/, '').replace('[Doctor Note] ', '').trim();
                      setTreatmentForm({
                        ...treatmentForm,
                        treatment_text: `Administered as prescribed: ${cleanText}`
                      });
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-clipboard-check-fill me-1"
                    }), " Follow & Administer This Order"]
                  }), doctorOrders.length > 1 && /*#__PURE__*/_jsxs("div", {
                    className: "mt-3",
                    children: [/*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-sm btn-light border w-100 text-secondary",
                      onClick: () => setShowAllDoctorOrders(!showAllDoctorOrders),
                      children: [/*#__PURE__*/_jsx("i", {
                        className: `bi bi-chevron-${showAllDoctorOrders ? 'up' : 'down'} me-1`
                      }), showAllDoctorOrders ? 'Hide Previous Doctor Notes' : `View All ${doctorOrders.length} Doctor Notes`]
                    }), showAllDoctorOrders && /*#__PURE__*/_jsx("div", {
                      className: "mt-2 d-flex flex-column gap-2",
                      children: doctorOrders.slice(1).map(docOrd => /*#__PURE__*/_jsxs("div", {
                        className: "p-2.5 bg-white rounded-3 border",
                        children: [/*#__PURE__*/_jsxs("div", {
                          className: "d-flex justify-content-between align-items-center mb-1",
                          children: [/*#__PURE__*/_jsx("strong", {
                            className: "text-primary small",
                            children: docOrd.recorded_by
                          }), /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            style: {
                              fontSize: '11px'
                            },
                            children: formatDateTime(docOrd.recorded_at)
                          })]
                        }), renderNoteContent(docOrd)]
                      }, docOrd.id))
                    })]
                  })]
                }) : /*#__PURE__*/_jsxs("div", {
                  className: "text-center text-muted py-3",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-clipboard-x fs-2 text-secondary opacity-50 d-block mb-1"
                  }), /*#__PURE__*/_jsx("small", {
                    children: "No doctor orders or clinical prescriptions recorded yet."
                  })]
                })
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "fw-bold mb-3 text-dark",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-capsule text-success me-1"
                }), " Log Nursing Treatment Given / Medication Administered"]
              }), /*#__PURE__*/_jsxs("div", {
                className: "mb-3",
                children: [/*#__PURE__*/_jsxs("label", {
                  className: "form-label small fw-bold mb-1",
                  children: ["Treatment / Medication Administered ", /*#__PURE__*/_jsx("span", {
                    className: "text-danger",
                    children: "*"
                  })]
                }), /*#__PURE__*/_jsx("textarea", {
                  className: "form-control rounded-3",
                  rows: "3",
                  placeholder: "e.g. Inj. Ceftriaxone 1g IV given slowly, Tab Pantop 40mg given orally...",
                  value: treatmentForm.treatment_text,
                  onChange: e => setTreatmentForm({
                    ...treatmentForm,
                    treatment_text: e.target.value
                  })
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "mb-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small fw-bold mb-1",
                  children: "Observation / Patient Response"
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm",
                  placeholder: "e.g. Tolerated well, No adverse reaction, IV site patent",
                  value: treatmentForm.general_note,
                  onChange: e => setTreatmentForm({
                    ...treatmentForm,
                    general_note: e.target.value
                  })
                })]
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-success fw-bold py-2 w-100 rounded-3 shadow-sm",
                onClick: () => {
                  if (!treatmentForm.treatment_text.trim()) {
                    alert('Please enter treatment or medication administered.');
                    return;
                  }
                  handleSaveIpdEntry('treatment', treatmentForm);
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-send-check-fill me-1"
                }), " Log Treatment Administration"]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "card shadow-sm border-0 rounded-4 overflow-hidden mb-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card-header py-2 bg-white d-flex justify-content-between align-items-center border-bottom",
                children: [/*#__PURE__*/_jsxs("strong", {
                  className: "text-dark",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-clock-history me-1 text-success"
                  }), " Nursing Treatment Given History"]
                }), /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-success-subtle text-success border border-success-subtle",
                  children: [nurseTreatments.length, " Records"]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "table-responsive",
                children: /*#__PURE__*/_jsxs("table", {
                  className: "table table-sm table-striped table-hover align-middle mb-0",
                  style: {
                    fontSize: '12px'
                  },
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-light",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        style: {
                          width: '140px'
                        },
                        children: "Time Administered"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Treatment / Medication Given"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Observation & Remarks"
                      }), /*#__PURE__*/_jsx("th", {
                        style: {
                          width: '120px'
                        },
                        children: "Staff Nurse"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: nurseTreatments.length === 0 ? /*#__PURE__*/_jsx("tr", {
                      children: /*#__PURE__*/_jsx("td", {
                        colSpan: "4",
                        className: "text-center text-muted py-4",
                        children: "No nursing treatments logged yet."
                      })
                    }) : nurseTreatments.map(tr => /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("td", {
                        className: "fw-semibold text-nowrap",
                        children: formatDateTime(tr.recorded_at)
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("div", {
                          className: "fw-bold text-dark",
                          children: renderNoteContent(tr)
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("small", {
                          className: "text-muted",
                          children: tr.general_note || '-'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("span", {
                          className: "badge bg-secondary-subtle text-secondary border",
                          children: tr.recorded_by || 'Staff Nurse'
                        })
                      })]
                    }, tr.id))
                  })]
                })
              })]
            })]
          });
        })()]
      }), activeOpdForVitals && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        style: {
          backgroundColor: 'rgba(0,0,0,0.5)'
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content rounded-4 border-0 shadow-lg",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-success text-white",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "modal-title fw-bold",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-heart-pulse-fill me-2"
                }), " Fill OPD Patient Vitals"]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn-close btn-close-white",
                onClick: () => setActiveOpdForVitals(null)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "alert alert-light border py-2 mb-3 small",
                children: [/*#__PURE__*/_jsx("strong", {
                  children: activeOpdForVitals.patient_display_name
                }), " (", activeOpdForVitals.age_display, " • ", activeOpdForVitals.gender_label, ")", /*#__PURE__*/_jsx("br", {}), /*#__PURE__*/_jsxs("small", {
                  className: "text-muted",
                  children: ["UHID: ", activeOpdForVitals.uhid, " | OPD Code: ", activeOpdForVitals.opd_code]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "BP Sys/Dia"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      className: "form-control text-center",
                      placeholder: "120",
                      value: opdVitalsForm.bp_systolic,
                      onChange: e => setOpdVitalsForm({
                        ...opdVitalsForm,
                        bp_systolic: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "/"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "number",
                      className: "form-control text-center",
                      placeholder: "80",
                      value: opdVitalsForm.bp_diastolic,
                      onChange: e => setOpdVitalsForm({
                        ...opdVitalsForm,
                        bp_diastolic: e.target.value
                      })
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Pulse Rate (bpm)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center",
                    placeholder: "72",
                    value: opdVitalsForm.pulse,
                    onChange: e => setOpdVitalsForm({
                      ...opdVitalsForm,
                      pulse: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "SpO2 %"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center",
                    placeholder: "98",
                    value: opdVitalsForm.spo2,
                    onChange: e => setOpdVitalsForm({
                      ...opdVitalsForm,
                      spo2: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Temp °F"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.1",
                    className: "form-control form-control-sm text-center",
                    placeholder: "98.6",
                    value: opdVitalsForm.temp,
                    onChange: e => setOpdVitalsForm({
                      ...opdVitalsForm,
                      temp: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Weight (kg)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center",
                    placeholder: "70",
                    value: opdVitalsForm.weight,
                    onChange: e => setOpdVitalsForm({
                      ...opdVitalsForm,
                      weight: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Height (cm)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center",
                    placeholder: "165",
                    value: opdVitalsForm.height,
                    onChange: e => setOpdVitalsForm({
                      ...opdVitalsForm,
                      height: e.target.value
                    })
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer p-2 bg-light",
              children: [/*#__PURE__*/_jsx("button", {
                className: "btn btn-secondary btn-sm",
                onClick: () => setActiveOpdForVitals(null),
                children: "Cancel"
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-success btn-sm fw-bold px-3",
                onClick: handleSaveOpdVitals,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-check-circle-fill me-1"
                }), " Save Vitals for Doctor"]
              })]
            })]
          })
        })
      }), scanTarget && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        style: {
          backgroundColor: 'rgba(0,0,0,0.5)'
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content rounded-4 border-0 shadow-lg",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-primary text-white",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "modal-title fw-bold",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-camera-fill me-2"
                }), " Scan / Photo Paper Document"]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn-close btn-close-white",
                onClick: () => setScanTarget(null)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "mb-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small fw-bold",
                  children: "Document Type / Category"
                }), /*#__PURE__*/_jsxs("select", {
                  className: "form-select form-select-sm",
                  value: scanDocCategory,
                  onChange: e => setScanDocCategory(e.target.value),
                  children: [/*#__PURE__*/_jsx("option", {
                    value: "Paper Document",
                    children: "Paper Document (General)"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "Vitals Sheet",
                    children: "Vitals Sheet / Chart"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "Lab Test Report",
                    children: "Lab / Pathology Report"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "Handwritten Doctor Note",
                    children: "Handwritten Doctor Note"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "Consent Form",
                    children: "Signed Consent Form"
                  })]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "mb-3 text-center",
                children: /*#__PURE__*/_jsxs("label", {
                  className: "btn btn-primary btn-lg w-100 py-3 fw-bold rounded-3 shadow-sm",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-camera me-2"
                  }), " Take Photo / Choose File", /*#__PURE__*/_jsx("input", {
                    type: "file",
                    accept: "image/*",
                    capture: "environment",
                    className: "d-none",
                    onChange: handleScanImageCapture
                  })]
                })
              }), scanImagePreview && /*#__PURE__*/_jsxs("div", {
                className: "position-relative p-2 border rounded-3 bg-light text-center",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: "badge bg-success position-absolute top-0 start-0 m-2",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle me-1"
                  }), " Document Photo Captured"]
                }), /*#__PURE__*/_jsx("img", {
                  src: scanImagePreview,
                  alt: "Document Scan Preview",
                  className: "img-fluid rounded-3 border shadow-sm",
                  style: {
                    maxHeight: '220px',
                    objectFit: 'contain'
                  }
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer p-2 bg-light",
              children: [/*#__PURE__*/_jsx("button", {
                className: "btn btn-secondary btn-sm",
                onClick: () => setScanTarget(null),
                children: "Cancel"
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-success btn-sm fw-bold px-3",
                onClick: handleSaveScanDocument,
                disabled: !scanImageBase64,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-cloud-upload-fill me-1"
                }), " Save Document to Chart"]
              })]
            })]
          })
        })
      }), cropSrc && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        style: {
          backgroundColor: 'rgba(0,0,0,0.85)',
          zIndex: 10000
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered modal-lg",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content rounded-4 border-0 shadow-lg overflow-hidden",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-dark text-white py-2",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "modal-title fw-bold mb-0",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-crop me-2 text-success"
                }), " Crop Document Photo"]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn-close btn-close-white",
                onClick: cancelCrop
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "modal-body p-2 text-center bg-black",
              style: {
                maxHeight: '65vh',
                overflow: 'hidden'
              },
              children: /*#__PURE__*/_jsx("div", {
                style: {
                  maxHeight: '60vh',
                  width: '100%'
                },
                children: /*#__PURE__*/_jsx("img", {
                  ref: imageRef,
                  src: cropSrc,
                  alt: "Crop Target",
                  style: {
                    maxWidth: '100%',
                    display: 'block'
                  }
                })
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer p-2 bg-dark d-flex justify-content-between align-items-center flex-wrap gap-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex gap-1",
                children: [/*#__PURE__*/_jsxs("button", {
                  className: "btn btn-sm btn-outline-light px-2",
                  onClick: () => cropper && cropper.rotate(-90),
                  title: "Rotate Left 90°",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-arrow-counterclockwise"
                  }), " 90°"]
                }), /*#__PURE__*/_jsxs("button", {
                  className: "btn btn-sm btn-outline-light px-2",
                  onClick: () => cropper && cropper.rotate(90),
                  title: "Rotate Right 90°",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-arrow-clockwise"
                  }), " 90°"]
                }), /*#__PURE__*/_jsxs("button", {
                  className: "btn btn-sm btn-outline-light px-2",
                  onClick: () => cropper && cropper.reset(),
                  title: "Reset Crop Box",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-aspect-ratio"
                  }), " Reset"]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex gap-2",
                children: [/*#__PURE__*/_jsx("button", {
                  className: "btn btn-sm btn-secondary",
                  onClick: cancelCrop,
                  children: "Cancel"
                }), /*#__PURE__*/_jsxs("button", {
                  className: "btn btn-sm btn-success fw-bold px-3",
                  onClick: applyCrop,
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-lg me-1"
                  }), " Apply Crop & Save Photo"]
                })]
              })]
            })]
          })
        })
      }), /*#__PURE__*/_jsxs("div", {
        className: "bottom-dock-container",
        children: [/*#__PURE__*/_jsxs("button", {
          className: "bottom-dock-btn btn-scan",
          onClick: () => openPatientSearch('scan'),
          title: "Scan Barcode / QR or Search Patient",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-qr-code-scan fs-5"
          }), /*#__PURE__*/_jsx("span", {
            children: "Scan / Search"
          })]
        }), /*#__PURE__*/_jsxs("button", {
          className: "bottom-dock-btn btn-vitals",
          onClick: () => {
            if (activeBed) {
              openWellnessModal({
                id: activeBed.p_id || activeBed.patient_id,
                p_code: activeBed.uhid,
                fullName: activeBed.patient_name,
                abha_id: activeBed.abha_id,
                gender: activeBed.gender
              });
            } else {
              openPatientSearch('type');
            }
          },
          title: "Add ABDM M2 Wellness Vitals",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-heart-pulse-fill fs-5"
          }), /*#__PURE__*/_jsx("span", {
            children: "ABDM Vitals"
          })]
        }), /*#__PURE__*/_jsxs("button", {
          className: "bottom-dock-btn btn-doc",
          onClick: () => {
            if (activeBed) {
              openDocModal({
                id: activeBed.p_id || activeBed.patient_id,
                p_code: activeBed.uhid,
                fullName: activeBed.patient_name,
                abha_id: activeBed.abha_id
              });
            } else {
              openPatientSearch('type');
            }
          },
          title: "Add ABDM M2 Health Document (Scan/Photo)",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-file-earmark-medical-fill fs-5"
          }), /*#__PURE__*/_jsx("span", {
            children: "ABDM Health Doc"
          })]
        })]
      }), showPatientSearchModal && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        style: {
          backgroundColor: 'rgba(0,0,0,0.65)',
          zIndex: 1050
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered modal-lg",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content rounded-4 border-0 shadow-lg overflow-hidden",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-dark text-white py-2.5",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-upc-scan fs-5 text-primary"
                }), /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("h6", {
                    className: "modal-title fw-bold mb-0",
                    children: "Search Patient by Barcode, QR & Name"
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-white-50",
                    style: {
                      fontSize: '11px'
                    },
                    children: "Scan Hospital Card Barcode, ABHA QR, or search registered patients"
                  })]
                })]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn-close btn-close-white",
                onClick: closePatientSearch
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "segmented-tabs mb-3",
                children: [/*#__PURE__*/_jsxs("button", {
                  className: `segmented-tab ${searchModalTab === 'scan' ? 'active-blue' : ''}`,
                  onClick: () => {
                    setSearchModalTab('scan');
                    startBarcodeQrScanner();
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-camera-video-fill me-1"
                  }), " Camera Scanner (Barcode & QR)"]
                }), /*#__PURE__*/_jsxs("button", {
                  className: `segmented-tab ${searchModalTab === 'type' ? 'active-blue' : ''}`,
                  onClick: () => {
                    setSearchModalTab('type');
                    stopBarcodeQrScanner();
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-search me-1"
                  }), " Manual Search (UHID / Mobile / ABHA)"]
                })]
              }), searchModalTab === 'scan' && /*#__PURE__*/_jsxs("div", {
                className: "text-center mb-3",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "qr-scanner-wrapper mx-auto mb-2",
                  style: {
                    maxWidth: '420px'
                  },
                  children: /*#__PURE__*/_jsx("div", {
                    id: "patient-qr-reader",
                    style: {
                      width: '100%'
                    }
                  })
                }), scannerError ? /*#__PURE__*/_jsxs("div", {
                  className: "alert alert-warning py-2 px-3 small d-inline-block",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-exclamation-triangle-fill me-1"
                  }), " ", scannerError]
                }) : /*#__PURE__*/_jsxs("small", {
                  className: "text-muted d-block",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-info-circle me-1"
                  }), " Point camera at ", /*#__PURE__*/_jsx("strong", {
                    children: "Patient Card Barcode"
                  }), " or ", /*#__PURE__*/_jsx("strong", {
                    children: "ABHA QR Code"
                  }), " to scan instantly."]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "input-group mb-3",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "input-group-text bg-white border-end-0",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-search text-muted"
                  })
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control border-start-0 ps-0",
                  placeholder: "Search by UHID (P...), ABHA No, Mobile (98...), Name...",
                  value: patientSearchQuery,
                  onChange: e => {
                    setPatientSearchQuery(e.target.value);
                    executePatientSearch(e.target.value);
                  },
                  onKeyDown: e => {
                    if (e.key === 'Enter') executePatientSearch(patientSearchQuery);
                  }
                }), patientSearchQuery && /*#__PURE__*/_jsx("button", {
                  className: "btn btn-outline-secondary",
                  onClick: () => {
                    setPatientSearchQuery('');
                    setPatientSearchResults([]);
                  },
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-x"
                  })
                }), /*#__PURE__*/_jsx("button", {
                  className: "btn btn-primary fw-bold px-3",
                  onClick: () => executePatientSearch(patientSearchQuery),
                  children: searchingPatient ? /*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm"
                  }) : 'Search'
                })]
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center mb-2",
                  children: [/*#__PURE__*/_jsxs("strong", {
                    className: "small text-secondary text-uppercase",
                    children: ["Matched Patients (", patientSearchResults.length, ")"]
                  }), searchingPatient && /*#__PURE__*/_jsx("span", {
                    className: "badge bg-primary-subtle text-primary",
                    children: "Searching..."
                  })]
                }), patientSearchResults.length === 0 ? /*#__PURE__*/_jsxs("div", {
                  className: "text-center py-4 bg-light rounded-3 border",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-person-badge fs-2 text-muted opacity-50 d-block mb-1"
                  }), /*#__PURE__*/_jsx("p", {
                    className: "small text-muted mb-0",
                    children: patientSearchQuery ? 'No patients found matching this query.' : 'Type patient name, UHID, ABHA number or scan barcode above.'
                  })]
                }) : /*#__PURE__*/_jsx("div", {
                  className: "d-flex flex-column gap-2",
                  style: {
                    maxHeight: '40vh',
                    overflowY: 'auto'
                  },
                  children: patientSearchResults.map(pat => /*#__PURE__*/_jsx("div", {
                    className: "patient-search-result-card",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between align-items-start flex-wrap gap-2",
                      children: [/*#__PURE__*/_jsxs("div", {
                        children: [/*#__PURE__*/_jsxs("div", {
                          className: "d-flex align-items-center gap-2",
                          children: [/*#__PURE__*/_jsx("h6", {
                            className: "fw-bold mb-0 text-dark",
                            children: pat.fullName
                          }), /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-light text-dark border",
                            style: {
                              fontSize: '11px'
                            },
                            children: [pat.gender === '1' || pat.gender === 1 ? 'Male' : pat.gender === '2' || pat.gender === 2 ? 'Female' : 'Other', pat.dob ? ` • DOB: ${pat.dob}` : '']
                          })]
                        }), /*#__PURE__*/_jsxs("div", {
                          className: "d-flex flex-wrap gap-2 mt-1 small",
                          children: [/*#__PURE__*/_jsxs("span", {
                            className: "text-danger fw-semibold",
                            children: ["UHID: ", pat.p_code]
                          }), pat.mphone1 && /*#__PURE__*/_jsxs("span", {
                            className: "text-muted",
                            children: [/*#__PURE__*/_jsx("i", {
                              className: "bi bi-telephone me-1"
                            }), pat.mphone1]
                          }), pat.abha_id && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-success-subtle text-success border border-success-subtle",
                            children: [/*#__PURE__*/_jsx("i", {
                              className: "bi bi-shield-check me-1"
                            }), "ABHA: ", pat.abha_id]
                          }), pat.abha_address && /*#__PURE__*/_jsx("span", {
                            className: "badge bg-primary-subtle text-primary border border-primary-subtle",
                            children: pat.abha_address
                          })]
                        }), pat.ipd_admission && /*#__PURE__*/_jsx("div", {
                          className: "mt-1",
                          children: /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-primary",
                            children: [/*#__PURE__*/_jsx("i", {
                              className: "bi bi-hospital me-1"
                            }), " Admitted IPD: ", pat.ipd_admission.ipd_code, " (Bed ", pat.ipd_admission.bed_number || pat.ipd_admission.bed_no, ")"]
                          })
                        }), pat.opd_today && /*#__PURE__*/_jsx("div", {
                          className: "mt-1",
                          children: /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-success",
                            children: [/*#__PURE__*/_jsx("i", {
                              className: "bi bi-calendar-check me-1"
                            }), " OPD Today: Token #", pat.opd_today.token_no, " (", pat.opd_today.doctor_name || 'Doctor', ")"]
                          })
                        })]
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "d-flex flex-wrap gap-1.5 align-items-center",
                        children: [/*#__PURE__*/_jsxs("button", {
                          className: "btn btn-sm btn-success fw-bold rounded-3 px-2 py-1.5",
                          onClick: () => openWellnessModal(pat),
                          title: "Add ABDM M2 Wellness Vitals",
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-heart-pulse-fill me-1"
                          }), " Add Vitals"]
                        }), /*#__PURE__*/_jsxs("button", {
                          className: "btn btn-sm btn-primary fw-bold rounded-3 px-2 py-1.5",
                          onClick: () => openDocModal(pat),
                          title: "Add ABDM M2 Health Document",
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-file-earmark-medical-fill me-1"
                          }), " Add Doc"]
                        }), /*#__PURE__*/_jsx("button", {
                          className: "btn btn-sm btn-outline-secondary rounded-3 px-2 py-1.5",
                          onClick: () => openWellnessHistory(pat),
                          title: "View Vitals History",
                          children: /*#__PURE__*/_jsx("i", {
                            className: "bi bi-clock-history"
                          })
                        }), pat.ipd_admission && /*#__PURE__*/_jsxs("button", {
                          className: "btn btn-sm btn-dark rounded-3 px-2 py-1.5",
                          onClick: () => {
                            closePatientSearch();
                            openWorkspace({
                              ipd_id: pat.ipd_admission.ipd_id,
                              patient_name: pat.fullName,
                              uhid: pat.p_code,
                              ipd_code: pat.ipd_admission.ipd_code,
                              bed_number: pat.ipd_admission.bed_number,
                              ward_name: pat.ipd_admission.ward_name,
                              status: 'occupied'
                            });
                          },
                          title: "Go to IPD Bedside Care",
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-arrow-right-circle"
                          }), " IPD Bed"]
                        })]
                      })]
                    })
                  }, pat.id))
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer p-2 bg-light d-flex justify-content-between",
              children: [/*#__PURE__*/_jsxs("small", {
                className: "text-muted",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-shield-lock me-1"
                }), " ABDM M2 Standard Compliance"]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn btn-secondary btn-sm px-3",
                onClick: closePatientSearch,
                children: "Close"
              })]
            })]
          })
        })
      }), activeWellnessPatient && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        style: {
          backgroundColor: 'rgba(0,0,0,0.65)',
          zIndex: 1060
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered modal-lg",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content rounded-4 border-0 shadow-lg overflow-hidden",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-success text-white py-2.5",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-heart-pulse-fill fs-5"
                }), /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("h6", {
                    className: "modal-title fw-bold mb-0",
                    children: "Record ABDM M2 Wellness & Vitals"
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-white-50",
                    style: {
                      fontSize: '11px'
                    },
                    children: "NRCES FHIR R4 Compliant Comprehensive Health & Wellness Suite"
                  })]
                })]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn-close btn-close-white",
                onClick: () => setActiveWellnessPatient(null)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsx("div", {
                className: "p-2.5 rounded-3 bg-light border mb-3",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center flex-wrap gap-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsx("strong", {
                      className: "fs-6 text-dark me-2",
                      children: activeWellnessPatient.fullName || activeWellnessPatient.p_fname
                    }), /*#__PURE__*/_jsx("span", {
                      className: "badge bg-secondary me-1",
                      children: activeWellnessPatient.gender ? activeWellnessPatient.gender.toUpperCase() : 'PATIENT'
                    }), activeWellnessPatient.age && /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-light text-dark border me-1",
                      children: [activeWellnessPatient.age, " Yrs"]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "small text-muted mt-0.5",
                      children: ["UHID: ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger fw-bold",
                        children: activeWellnessPatient.p_code || activeWellnessPatient.uhid
                      }), activeWellnessPatient.mphone1 && ` • Phone: ${activeWellnessPatient.mphone1}`]
                    })]
                  }), /*#__PURE__*/_jsx("div", {
                    children: activeWellnessPatient.abha_id ? /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-success",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-shield-check me-1"
                      }), " ABHA: ", activeWellnessPatient.abha_id]
                    }) : /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-secondary-subtle text-secondary border",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-shield-exclamation me-1"
                      }), " ABHA Not Linked"]
                    })
                  })]
                })
              }), /*#__PURE__*/_jsxs("ul", {
                className: "nav nav-pills nav-fill bg-light p-1 rounded-3 mb-3 border small",
                children: [/*#__PURE__*/_jsx("li", {
                  className: "nav-item",
                  children: /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: `nav-link py-1.5 px-2 fw-semibold d-flex align-items-center justify-content-center gap-1 ${wellnessModalTab === 'vitals' ? 'active shadow-sm' : 'text-secondary'}`,
                    onClick: () => setWellnessModalTab('vitals'),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-heart-pulse-fill text-danger"
                    }), /*#__PURE__*/_jsx("span", {
                      children: "Vitals & Triage"
                    }), (wellnessForm.bp_systolic || wellnessForm.pulse_rate || wellnessForm.temperature_f) && /*#__PURE__*/_jsx("span", {
                      className: "badge rounded-pill bg-success p-1 ms-1",
                      style: {
                        fontSize: '7px'
                      },
                      children: "✓"
                    })]
                  })
                }), /*#__PURE__*/_jsx("li", {
                  className: "nav-item",
                  children: /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: `nav-link py-1.5 px-2 fw-semibold d-flex align-items-center justify-content-center gap-1 ${wellnessModalTab === 'sugar' ? 'active shadow-sm' : 'text-secondary'}`,
                    onClick: () => setWellnessModalTab('sugar'),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-droplet-half text-danger"
                    }), /*#__PURE__*/_jsx("span", {
                      children: "Sugar & Labs"
                    }), (wellnessForm.sugar_random || wellnessForm.sugar_fasting || wellnessForm.sugar_pp || wellnessForm.hba1c || wellnessForm.hemoglobin) && /*#__PURE__*/_jsx("span", {
                      className: "badge rounded-pill bg-success p-1 ms-1",
                      style: {
                        fontSize: '7px'
                      },
                      children: "✓"
                    })]
                  })
                }), /*#__PURE__*/_jsx("li", {
                  className: "nav-item",
                  children: /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: `nav-link py-1.5 px-2 fw-semibold d-flex align-items-center justify-content-center gap-1 ${wellnessModalTab === 'activity' ? 'active shadow-sm' : 'text-secondary'}`,
                    onClick: () => setWellnessModalTab('activity'),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-person-bounding-box text-primary"
                    }), /*#__PURE__*/_jsx("span", {
                      children: "Waist & Activity"
                    }), (wellnessForm.waist_circumference_cm || wellnessForm.hip_circumference_cm || wellnessForm.daily_steps || wellnessForm.sleep_hours) && /*#__PURE__*/_jsx("span", {
                      className: "badge rounded-pill bg-success p-1 ms-1",
                      style: {
                        fontSize: '7px'
                      },
                      children: "✓"
                    })]
                  })
                }), /*#__PURE__*/_jsx("li", {
                  className: "nav-item",
                  children: /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: `nav-link py-1.5 px-2 fw-semibold d-flex align-items-center justify-content-center gap-1 ${wellnessModalTab === 'lifestyle' ? 'active shadow-sm' : 'text-secondary'}`,
                    onClick: () => setWellnessModalTab('lifestyle'),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-apple text-success"
                    }), /*#__PURE__*/_jsx("span", {
                      children: "Lifestyle & Notes"
                    }), (wellnessForm.diet_type || wellnessForm.tobacco_status || wellnessForm.diet_lifestyle_note || wellnessForm.general_advice) && /*#__PURE__*/_jsx("span", {
                      className: "badge rounded-pill bg-success p-1 ms-1",
                      style: {
                        fontSize: '7px'
                      },
                      children: "✓"
                    })]
                  })
                }), String(activeWellnessPatient.gender || '').toLowerCase() !== 'male' && /*#__PURE__*/_jsx("li", {
                  className: "nav-item",
                  children: /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: `nav-link py-1.5 px-2 fw-semibold d-flex align-items-center justify-content-center gap-1 ${wellnessModalTab === 'women' ? 'active shadow-sm' : 'text-secondary'}`,
                    onClick: () => setWellnessModalTab('women'),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-gender-female text-warning"
                    }), /*#__PURE__*/_jsx("span", {
                      children: "Women's Health"
                    }), (wellnessForm.women_lmp || wellnessForm.women_pregnancy_status) && /*#__PURE__*/_jsx("span", {
                      className: "badge rounded-pill bg-success p-1 ms-1",
                      style: {
                        fontSize: '7px'
                      },
                      children: "✓"
                    })]
                  })
                })]
              }), wellnessModalTab === 'vitals' && /*#__PURE__*/_jsxs("div", {
                className: "row g-2.5 mb-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "BP Sys/Dia (mmHg)"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      className: "form-control text-center",
                      placeholder: "120",
                      value: wellnessForm.bp_systolic,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        bp_systolic: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text px-1",
                      children: "/"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "number",
                      className: "form-control text-center",
                      placeholder: "80",
                      value: wellnessForm.bp_diastolic,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        bp_diastolic: e.target.value
                      })
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Pulse Rate (bpm)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center",
                    placeholder: "72",
                    value: wellnessForm.pulse_rate,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      pulse_rate: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "SpO2 %"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center",
                    placeholder: "98",
                    value: wellnessForm.spo2,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      spo2: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Temp °F"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.1",
                    className: "form-control form-control-sm text-center",
                    placeholder: "98.6",
                    value: wellnessForm.temperature_f,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      temperature_f: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Resp Rate (/min)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm text-center",
                    placeholder: "18",
                    value: wellnessForm.resp_rate,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      resp_rate: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Height (cm)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.1",
                    className: "form-control form-control-sm text-center",
                    placeholder: "165",
                    value: wellnessForm.height_cm,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      height_cm: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Weight (kg)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.1",
                    className: "form-control form-control-sm text-center",
                    placeholder: "70",
                    value: wellnessForm.weight_kg,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      weight_kg: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Calculated BMI"
                  }), /*#__PURE__*/_jsx("div", {
                    className: "input-group input-group-sm",
                    children: /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm text-center bg-light fw-bold",
                      readOnly: true,
                      value: wellnessForm.height_cm && wellnessForm.weight_kg && parseFloat(wellnessForm.height_cm) > 0 ? (parseFloat(wellnessForm.weight_kg) / Math.pow(parseFloat(wellnessForm.height_cm) / 100, 2)).toFixed(2) : '-'
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12 mt-2",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label small mb-1 fw-bold d-flex justify-content-between",
                    children: [/*#__PURE__*/_jsxs("span", {
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-emoji-frown me-1 text-warning"
                      }), " Pain Severity Score (0 - 10 VRS)"]
                    }), /*#__PURE__*/_jsx("span", {
                      className: "text-muted fw-normal",
                      children: wellnessForm.pain_score === '' ? 'Not assessed' : wellnessForm.pain_score == 0 ? '0 - No Pain' : wellnessForm.pain_score <= 3 ? `${wellnessForm.pain_score} - Mild Pain` : wellnessForm.pain_score <= 6 ? `${wellnessForm.pain_score} - Moderate Pain` : `${wellnessForm.pain_score} - Severe Pain`
                    })]
                  }), /*#__PURE__*/_jsx("div", {
                    className: "d-flex gap-1 flex-wrap",
                    children: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(val => /*#__PURE__*/_jsx("button", {
                      type: "button",
                      className: `btn btn-sm flex-fill py-1 px-1.5 ${wellnessForm.pain_score === String(val) ? val === 0 ? 'btn-success text-white' : val <= 3 ? 'btn-info text-white' : val <= 6 ? 'btn-warning text-dark' : 'btn-danger text-white' : 'btn-outline-secondary'}`,
                      onClick: () => setWellnessForm({
                        ...wellnessForm,
                        pain_score: wellnessForm.pain_score === String(val) ? '' : String(val)
                      }),
                      children: val
                    }, val))
                  })]
                })]
              }), wellnessModalTab === 'sugar' && /*#__PURE__*/_jsxs("div", {
                className: "row g-2.5 mb-2",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "col-12",
                  children: /*#__PURE__*/_jsxs("div", {
                    className: "alert alert-info py-1.5 px-3 small d-flex align-items-center gap-2 mb-2",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-info-circle-fill fs-6 text-primary"
                    }), /*#__PURE__*/_jsxs("span", {
                      children: ["Point-of-care capillary blood sugar & POC lab values (NRCES ", /*#__PURE__*/_jsx("strong", {
                        children: "GeneralAssessment"
                      }), " slice)"]
                    })]
                  })
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Random Blood Sugar (RBS)"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "1",
                      className: "form-control text-center",
                      placeholder: "110",
                      value: wellnessForm.sugar_random,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        sugar_random: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "mg/dL"
                    })]
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Normal: < 140 mg/dL"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Fasting Blood Sugar (FBS)"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "1",
                      className: "form-control text-center",
                      placeholder: "95",
                      value: wellnessForm.sugar_fasting,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        sugar_fasting: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "mg/dL"
                    })]
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Normal: 70 - 100 mg/dL"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Post-Prandial (PPBS)"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "1",
                      className: "form-control text-center",
                      placeholder: "130",
                      value: wellnessForm.sugar_pp,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        sugar_pp: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "mg/dL"
                    })]
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Normal: < 140 mg/dL (2h post meal)"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "HbA1c (Glycated Hemoglobin)"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "0.1",
                      className: "form-control text-center",
                      placeholder: "5.6",
                      value: wellnessForm.hba1c,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        hba1c: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "%"
                    })]
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Normal: < 5.7% | Prediabetes: 5.7-6.4% | Diabetes: ≥ 6.5%"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "POC Hemoglobin (Hb)"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "0.1",
                      className: "form-control text-center",
                      placeholder: "13.5",
                      value: wellnessForm.hemoglobin,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        hemoglobin: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "g/dL"
                    })]
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Normal: 12.0 - 17.5 g/dL"
                  })]
                })]
              }), wellnessModalTab === 'activity' && /*#__PURE__*/_jsxs("div", {
                className: "row g-2.5 mb-2",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "col-12",
                  children: /*#__PURE__*/_jsxs("h6", {
                    className: "fw-bold small text-primary mb-1 border-bottom pb-1",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-rulers me-1"
                    }), " Body Circumferences (NRCES BodyMeasurement)"]
                  })
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Waist Circumference"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "0.1",
                      className: "form-control text-center",
                      placeholder: "85",
                      value: wellnessForm.waist_circumference_cm,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        waist_circumference_cm: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "cm"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Hip Circumference"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "0.1",
                      className: "form-control text-center",
                      placeholder: "95",
                      value: wellnessForm.hip_circumference_cm,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        hip_circumference_cm: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "cm"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Waist-to-Hip Ratio (WHR)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm text-center bg-light fw-bold",
                    readOnly: true,
                    value: wellnessForm.waist_circumference_cm && wellnessForm.hip_circumference_cm && parseFloat(wellnessForm.hip_circumference_cm) > 0 ? (parseFloat(wellnessForm.waist_circumference_cm) / parseFloat(wellnessForm.hip_circumference_cm)).toFixed(2) : '-'
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Normal: Men < 0.90 | Women < 0.85"
                  })]
                }), /*#__PURE__*/_jsx("div", {
                  className: "col-12 mt-2",
                  children: /*#__PURE__*/_jsxs("h6", {
                    className: "fw-bold small text-success mb-1 border-bottom pb-1",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-activity me-1"
                    }), " Physical Activity & Sleep (NRCES PhysicalActivity)"]
                  })
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Daily Steps (24h)"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "100",
                      className: "form-control text-center",
                      placeholder: "8000",
                      value: wellnessForm.daily_steps,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        daily_steps: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "steps"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Sleep Duration"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "0.5",
                      className: "form-control text-center",
                      placeholder: "7.5",
                      value: wellnessForm.sleep_hours,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        sleep_hours: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "hours"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Exercise Duration"
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "5",
                      className: "form-control text-center",
                      placeholder: "30",
                      value: wellnessForm.exercise_min_per_day,
                      onChange: e => setWellnessForm({
                        ...wellnessForm,
                        exercise_min_per_day: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("span", {
                      className: "input-group-text",
                      children: "min/day"
                    })]
                  })]
                })]
              }), wellnessModalTab === 'lifestyle' && /*#__PURE__*/_jsxs("div", {
                className: "row g-2.5 mb-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Diet Pattern (ABDM)"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: wellnessForm.diet_type,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      diet_type: e.target.value
                    }),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "",
                      children: "Select diet..."
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Balanced Diet",
                      children: "Balanced Diet"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Vegetarian",
                      children: "Vegetarian"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Lacto-Vegetarian",
                      children: "Lacto-Vegetarian"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Non-Vegetarian",
                      children: "Non-Vegetarian"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Vegan",
                      children: "Vegan"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Diabetic Diet",
                      children: "Diabetic Diet (Low GI)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Low Sodium / DASH Diet",
                      children: "Low Sodium / DASH Diet"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Renal Diet",
                      children: "Renal Diet"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Tobacco / Smoking"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: wellnessForm.tobacco_status,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      tobacco_status: e.target.value
                    }),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "",
                      children: "Select status..."
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Never Smoked",
                      children: "Never Smoked"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Former / Ex-Smoker",
                      children: "Former / Ex-Smoker"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Current Smoker (Occasional)",
                      children: "Current Smoker (Occasional)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Current Smoker (Daily)",
                      children: "Current Smoker (Daily)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Tobacco Chewer",
                      children: "Tobacco Chewer"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6 col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Alcohol Consumption"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: wellnessForm.alcohol_status,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      alcohol_status: e.target.value
                    }),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "",
                      children: "Select status..."
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Lifetime Non-Drinker",
                      children: "Lifetime Non-Drinker"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Former Drinker",
                      children: "Former Drinker"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Occasional / Social Drinker",
                      children: "Occasional / Social Drinker"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Regular Drinker",
                      children: "Regular Drinker"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Diet & Lifestyle Guidance (ABDM)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    placeholder: "e.g. Low sodium diet, 30 min daily brisk walking, adequate hydration",
                    value: wellnessForm.diet_lifestyle_note,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      diet_lifestyle_note: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "General Nursing Observations"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    placeholder: "e.g. Patient conscious, oriented to time/place, vitals within acceptable limits",
                    value: wellnessForm.general_advice,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      general_advice: e.target.value
                    })
                  })]
                })]
              }), wellnessModalTab === 'women' && /*#__PURE__*/_jsxs("div", {
                className: "row g-2.5 mb-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Last Menstrual Period (LMP Date)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "date",
                    className: "form-control form-control-sm",
                    value: wellnessForm.women_lmp,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      women_lmp: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small mb-1 fw-bold",
                    children: "Pregnancy / Reproductive Status"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: wellnessForm.women_pregnancy_status,
                    onChange: e => setWellnessForm({
                      ...wellnessForm,
                      women_pregnancy_status: e.target.value
                    }),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "",
                      children: "Select reproductive status..."
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Not Pregnant",
                      children: "Not Pregnant"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Pregnant - 1st Trimester (1 - 12 wks)",
                      children: "Pregnant - 1st Trimester (1 - 12 wks)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Pregnant - 2nd Trimester (13 - 26 wks)",
                      children: "Pregnant - 2nd Trimester (13 - 26 wks)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Pregnant - 3rd Trimester (27 - 40+ wks)",
                      children: "Pregnant - 3rd Trimester (27 - 40+ wks)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Postpartum (< 6 weeks post delivery)",
                      children: "Postpartum (< 6 weeks post delivery)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Lactating / Breastfeeding",
                      children: "Lactating / Breastfeeding"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Perimenopausal",
                      children: "Perimenopausal"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Postmenopausal",
                      children: "Postmenopausal"
                    })]
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer p-2 bg-light d-flex justify-content-between",
              children: [/*#__PURE__*/_jsxs("button", {
                className: "btn btn-outline-secondary btn-sm",
                onClick: () => openWellnessHistory(activeWellnessPatient),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history me-1"
                }), " Past History"]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex gap-2",
                children: [/*#__PURE__*/_jsx("button", {
                  className: "btn btn-secondary btn-sm",
                  onClick: () => setActiveWellnessPatient(null),
                  children: "Cancel"
                }), /*#__PURE__*/_jsxs("button", {
                  className: "btn btn-success btn-sm fw-bold px-3 shadow-sm",
                  onClick: handleSaveWellness,
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle-fill me-1"
                  }), " Save ABDM Wellness Vitals"]
                })]
              })]
            })]
          })
        })
      }), activeDocPatient && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        style: {
          backgroundColor: 'rgba(0,0,0,0.65)',
          zIndex: 1060
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered modal-lg",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content rounded-4 border-0 shadow-lg overflow-hidden",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-primary text-white py-2.5",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-medical-fill fs-5"
                }), /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("h6", {
                    className: "modal-title fw-bold mb-0",
                    children: "Add ABDM M2 Health Document"
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-white-50",
                    style: {
                      fontSize: '11px'
                    },
                    children: "Scan paper prescriptions, lab reports or clinical documents"
                  })]
                })]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn-close btn-close-white",
                onClick: () => setActiveDocPatient(null)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsx("div", {
                className: "p-2.5 rounded-3 bg-light border mb-3",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-start flex-wrap gap-1",
                  children: [/*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsx("strong", {
                      className: "fs-6 text-dark",
                      children: activeDocPatient.fullName || activeDocPatient.p_fname
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "small text-muted",
                      children: ["UHID: ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger fw-bold",
                        children: activeDocPatient.p_code || activeDocPatient.uhid
                      })]
                    })]
                  }), activeDocPatient.abha_id && /*#__PURE__*/_jsxs("span", {
                    className: "badge bg-success",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-shield-check me-1"
                    }), " ABHA: ", activeDocPatient.abha_id]
                  })]
                })
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2 mb-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Document Category (ABDM HI Type)"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: patientDocForm.document_type,
                    onChange: e => setPatientDocForm({
                      ...patientDocForm,
                      document_type: e.target.value
                    }),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "Prescription",
                      children: "Prescription Record (PrescriptionRecord)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Diagnostic Report / Lab",
                      children: "Diagnostic / Lab Report (DiagnosticReport)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Discharge Summary",
                      children: "Discharge Summary (DischargeSummary)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Scan / Radiology (X-Ray/CT/MRI)",
                      children: "Radiology / Scan (HealthDocumentRecord)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Paper Medical Record",
                      children: "Paper Medical Record (HealthDocumentRecord)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Immunization Record",
                      children: "Immunization Record (ImmunizationRecord)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Health Document",
                      children: "Other Health Document (HealthDocumentRecord)"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12 col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Document Date"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "date",
                    className: "form-control form-control-sm",
                    value: patientDocForm.document_date,
                    onChange: e => setPatientDocForm({
                      ...patientDocForm,
                      document_date: e.target.value
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Remarks / Document Title"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    placeholder: "e.g. Chest X-Ray Report, Blood CBC Test, Consultation Note",
                    value: patientDocForm.remarks,
                    onChange: e => setPatientDocForm({
                      ...patientDocForm,
                      remarks: e.target.value
                    })
                  })]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "mb-3 text-center",
                children: /*#__PURE__*/_jsxs("label", {
                  className: "btn btn-primary btn-lg w-100 py-3 fw-bold rounded-3 shadow-sm",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-camera me-2"
                  }), " Take Document Photo / Choose Image", /*#__PURE__*/_jsx("input", {
                    type: "file",
                    accept: "image/*",
                    capture: "environment",
                    className: "d-none",
                    onChange: handleScanImageCapture
                  })]
                })
              }), scanImagePreview && /*#__PURE__*/_jsxs("div", {
                className: "position-relative p-2 border rounded-3 bg-light text-center",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: "badge bg-success position-absolute top-0 start-0 m-2",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle me-1"
                  }), " Document Photo Captured"]
                }), /*#__PURE__*/_jsx("img", {
                  src: scanImagePreview,
                  alt: "Document Scan Preview",
                  className: "img-fluid rounded-3 border shadow-sm",
                  style: {
                    maxHeight: '200px',
                    objectFit: 'contain'
                  }
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer p-2 bg-light",
              children: [/*#__PURE__*/_jsx("button", {
                className: "btn btn-secondary btn-sm",
                onClick: () => setActiveDocPatient(null),
                disabled: isSavingDoc,
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                className: "btn btn-primary btn-sm fw-bold px-3",
                onClick: handleSavePatientDocument,
                disabled: !scanImageBase64 || isSavingDoc,
                children: isSavingDoc ? /*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm me-1",
                    role: "status",
                    "aria-hidden": "true"
                  }), "Uploading..."]
                }) : /*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-cloud-upload-fill me-1"
                  }), " Upload ABDM Health Document"]
                })
              })]
            })]
          })
        })
      }), wellnessHistoryPatient && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        style: {
          backgroundColor: 'rgba(0,0,0,0.65)',
          zIndex: 1070
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered modal-xl",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content rounded-4 border-0 shadow-lg overflow-hidden",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-dark text-white py-2.5",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history fs-5 text-warning"
                }), /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("h6", {
                    className: "modal-title fw-bold mb-0",
                    children: "Vitals & Wellness History"
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-white-50",
                    style: {
                      fontSize: '11px'
                    },
                    children: [wellnessHistoryPatient.fullName || wellnessHistoryPatient.p_fname, " (UHID: ", wellnessHistoryPatient.p_code || wellnessHistoryPatient.uhid, ")"]
                  })]
                })]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn-close btn-close-white",
                onClick: () => setWellnessHistoryPatient(null)
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "modal-body p-3",
              children: loadingHistory ? /*#__PURE__*/_jsxs("div", {
                className: "text-center py-4",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "spinner-border text-primary",
                  role: "status"
                }), /*#__PURE__*/_jsx("p", {
                  className: "small text-muted mt-2",
                  children: "Loading historical vitals..."
                })]
              }) : wellnessHistoryList.length === 0 ? /*#__PURE__*/_jsxs("div", {
                className: "text-center py-4 bg-light rounded-3",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-heart-pulse fs-2 text-muted opacity-50 d-block mb-1"
                }), /*#__PURE__*/_jsx("p", {
                  className: "small text-muted mb-0",
                  children: "No past vitals or wellness entries recorded yet for this patient."
                })]
              }) : /*#__PURE__*/_jsx("div", {
                className: "table-responsive",
                children: /*#__PURE__*/_jsxs("table", {
                  className: "table table-sm table-striped table-hover align-middle mb-0",
                  style: {
                    fontSize: '12px'
                  },
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-light",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        children: "Date & Time"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Vitals (BP / Pulse / SpO2 / Temp)"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Body / BMI"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Blood Glucose (mg/dL)"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "POC Labs"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Activity / Lifestyle"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Staff"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Advice / Remarks"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: wellnessHistoryList.map(rec => /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("td", {
                        className: "fw-semibold text-nowrap",
                        children: formatDateTime(rec.recorded_at)
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsxs("div", {
                          className: "d-flex flex-wrap gap-1 align-items-center",
                          children: [rec.bp_systolic && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-light text-dark border",
                            children: ["BP ", rec.bp_systolic, "/", rec.bp_diastolic]
                          }), rec.pulse_rate && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-light text-dark border",
                            children: [rec.pulse_rate, " bpm"]
                          }), rec.spo2 && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-info-subtle text-info border border-info-subtle",
                            children: [rec.spo2, "% O2"]
                          }), rec.temperature_f && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-danger-subtle text-danger border border-danger-subtle",
                            children: [rec.temperature_f, "°F"]
                          }), rec.pain_score !== null && rec.pain_score !== undefined && rec.pain_score !== '' && /*#__PURE__*/_jsxs("span", {
                            className: `badge ${rec.pain_score == 0 ? 'bg-success-subtle text-success' : rec.pain_score <= 3 ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning-emphasis'}`,
                            children: ["Pain: ", rec.pain_score, "/10"]
                          })]
                        })
                      }), /*#__PURE__*/_jsxs("td", {
                        children: [/*#__PURE__*/_jsxs("div", {
                          children: [rec.bmi && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-secondary-subtle text-secondary me-1",
                            children: ["BMI ", rec.bmi]
                          }), rec.weight_kg && /*#__PURE__*/_jsxs("small", {
                            className: "text-muted",
                            children: [rec.weight_kg, "kg "]
                          }), rec.height_cm && /*#__PURE__*/_jsxs("small", {
                            className: "text-muted",
                            children: [rec.height_cm, "cm"]
                          })]
                        }), (rec.waist_circumference_cm || rec.waist_hip_ratio) && /*#__PURE__*/_jsxs("small", {
                          className: "text-muted d-block",
                          style: {
                            fontSize: '11px'
                          },
                          children: [rec.waist_circumference_cm ? `Waist: ${rec.waist_circumference_cm}cm` : '', rec.waist_hip_ratio ? ` • WHR: ${rec.waist_hip_ratio}` : '']
                        })]
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsxs("div", {
                          className: "d-flex flex-wrap gap-1",
                          children: [rec.sugar_random && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-warning-subtle text-warning-emphasis border",
                            children: ["RBS: ", rec.sugar_random]
                          }), rec.sugar_fasting && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-primary-subtle text-primary border",
                            children: ["FBS: ", rec.sugar_fasting]
                          }), rec.sugar_pp && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-secondary-subtle text-secondary border",
                            children: ["PP: ", rec.sugar_pp]
                          }), !rec.sugar_random && !rec.sugar_fasting && !rec.sugar_pp && /*#__PURE__*/_jsx("span", {
                            className: "text-muted",
                            children: "-"
                          })]
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsxs("div", {
                          className: "d-flex flex-wrap gap-1",
                          children: [rec.hemoglobin && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-danger-subtle text-danger border",
                            children: ["Hb: ", rec.hemoglobin, " g/dL"]
                          }), rec.hba1c && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-success-subtle text-success border",
                            children: ["A1c: ", rec.hba1c, "%"]
                          }), !rec.hemoglobin && !rec.hba1c && /*#__PURE__*/_jsx("span", {
                            className: "text-muted",
                            children: "-"
                          })]
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsxs("div", {
                          className: "small",
                          children: [rec.diet_type && /*#__PURE__*/_jsx("span", {
                            className: "badge bg-light text-dark border me-1",
                            children: rec.diet_type
                          }), rec.daily_steps && /*#__PURE__*/_jsxs("span", {
                            className: "text-muted me-1",
                            children: [rec.daily_steps, " steps"]
                          }), rec.sleep_hours && /*#__PURE__*/_jsxs("span", {
                            className: "text-muted",
                            children: [rec.sleep_hours, "h sleep"]
                          }), !rec.diet_type && !rec.daily_steps && !rec.sleep_hours && /*#__PURE__*/_jsx("span", {
                            className: "text-muted",
                            children: "-"
                          })]
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("small", {
                          className: "text-muted",
                          children: rec.recorded_by || 'Staff'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("small", {
                          className: "text-truncate d-inline-block",
                          style: {
                            maxWidth: '180px'
                          },
                          title: rec.general_advice || rec.diet_lifestyle_note,
                          children: rec.general_advice || rec.diet_lifestyle_note || '-'
                        })
                      })]
                    }, rec.id))
                  })]
                })
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "modal-footer p-2 bg-light",
              children: /*#__PURE__*/_jsx("button", {
                className: "btn btn-secondary btn-sm px-3",
                onClick: () => setWellnessHistoryPatient(null),
                children: "Close"
              })
            })]
          })
        })
      })]
    })]
  });
}
const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(/*#__PURE__*/_jsx(NursingApp, {}));