import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
const {
  useState,
  useEffect
} = React;
const DOC_DEVICE_AUTH_KEY = 'doctor_pwa_device_auth_v1';
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
function DoctorApp() {
  const [showAppsModal, setShowAppsModal] = useState(false);
  const [doctors, setDoctors] = useState([]);
  const [myIpdPatients, setMyIpdPatients] = useState([]);
  const [allIpdPatients, setAllIpdPatients] = useState([]);
  const [ipdViewMode, setIpdViewMode] = useState('mine'); // 'mine' or 'all'

  const [opdAppointments, setOpdAppointments] = useState([]);
  const [opdCounts, setOpdCounts] = useState({
    all: 0,
    waiting: 0,
    visited: 0,
    booked: 0,
    cancelled: 0
  });
  const [opdFilter, setOpdFilter] = useState('all');
  const [opdDateMode, setOpdDateMode] = useState('today'); // 'today' or 'all'

  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [mainTab, setMainTab] = useState('opd');

  // Active Workspace state
  const [activePatient, setActivePatient] = useState(null);
  const [activeOpd, setActiveOpd] = useState(null);
  const [workspaceEntries, setWorkspaceEntries] = useState([]);
  const [newTreatmentNote, setNewTreatmentNote] = useState('');
  const [noteImageBase64, setNoteImageBase64] = useState('');
  const [noteImagePreview, setNoteImagePreview] = useState('');
  const [installPrompt, setInstallPrompt] = useState(null);

  // Auth & Device Security State
  const PAIRED_DOC_KEY = 'hms_doctor_paired_profile';
  const [authenticatedDoctor, setAuthenticatedDoctor] = useState(null);
  const [pairedDoctor, setPairedDoctor] = useState(() => {
    try {
      const item = localStorage.getItem(PAIRED_DOC_KEY);
      return item ? JSON.parse(item) : null;
    } catch (e) {
      return null;
    }
  });
  const [docIdInput, setDocIdInput] = useState('');
  const [deviceCodeInput, setDeviceCodeInput] = useState('');
  const [pinInput, setPinInput] = useState('');
  const [pinError, setPinError] = useState('');
  const [showPairingForm, setShowPairingForm] = useState(false);
  const [isVerifying, setIsVerifying] = useState(false);
  const [isSettingNewPin, setIsSettingNewPin] = useState(false);
  const [newPin, setNewPin] = useState('');
  const [confirmPin, setConfirmPin] = useState('');

  // OPD Prescription Consultation Controls State
  const [prescForm, setPrescForm] = useState({
    chief_complaints: '',
    finding_examinations: '',
    provisional_diagnosis: '',
    investigation: '',
    advice: '',
    next_visit: '',
    temp: '',
    pulse: '',
    bp_systolic: '',
    bp_diastolic: '',
    spo2: '',
    weight: '',
    height: '',
    rr_min: ''
  });

  // Medicines Repeater Array
  const [medicines, setMedicines] = useState([{
    drug_name: '',
    dosage: '1 Tab',
    frequency: '1-0-1',
    duration: '5 Days',
    instructions: 'After Food'
  }]);

  // Quick Chips presets for Mobile Typing
  const COMPLAINT_CHIPS = ['Fever', 'Cough', 'Swelling', 'Joint Pain', 'Headache', 'Back Ache', 'Nausea / Vomiting', 'Weakness', 'Chest Pain', 'Shortness of Breath'];
  const EXAMINATION_CHIPS = ['Pallor (+)', 'Icterus (-)', 'Cyanosis (-)', 'Clubbing (-)', 'Edema (-)', 'B/L Chest Clear', 'S1 S2 Heard'];
  const DIAGNOSIS_CHIPS = ['Acute Rheumatic Fever', 'Viral Infection', 'Essential Hypertension', 'Type 2 Diabetes Mellitus', 'GERD', 'Acute Bronchitis', 'Osteoarthritis'];
  const DRUG_CHIPS = ['Paracetamol 500mg', 'Amoxicillin 500mg', 'Pantoprazole 40mg', 'Cetirizine 10mg', 'Ibuprofen 400mg', 'Azithromycin 500mg', 'Multivitamin'];
  const LAB_CHIPS = ['CBC', 'Serum Creatinine', 'LFT', 'KFT', 'Chest X-Ray PA', 'ECG', 'Urine RE', 'HbA1c', 'USG Abdomen'];
  const fetchIpdPatients = async docId => {
    try {
      setLoading(true);
      const res = await fetch(`/api/v1/doctor/ipd/list/${docId || 0}`);
      const json = await res.json();
      if (json.status === 1) {
        setAllIpdPatients(json.all_patients || json.patients || []);
        setMyIpdPatients(json.patients || []);
      }
    } catch (err) {
      console.error('Failed to load IPD patients', err);
    } finally {
      setLoading(false);
    }
  };
  const fetchOpdAppointments = async (docId, dateModeOverride) => {
    try {
      const mode = dateModeOverride || opdDateMode;
      const res = await fetch(`/api/v1/doctor/opd/list/${docId || 0}?date_mode=${mode}`);
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
    const savedAuth = localStorage.getItem(DOC_DEVICE_AUTH_KEY);
    if (savedAuth) {
      try {
        const parsed = JSON.parse(savedAuth);
        if (parsed && parsed.doctor && parsed.expires_at > Date.now()) {
          setAuthenticatedDoctor(parsed.doctor);
          fetchIpdPatients(parsed.doctor.id);
          fetchOpdAppointments(parsed.doctor.id, opdDateMode);
        } else {
          localStorage.removeItem(DOC_DEVICE_AUTH_KEY);
        }
      } catch (e) {}
    }
    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      setInstallPrompt(e);
    });
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/App/Doctor/sw.js').then(() => console.log('Doctor PWA Service Worker Registered')).catch(err => console.error('SW failed', err));
    }
  }, []);
  const handleVerifyPin = async () => {
    const isPairing = !pairedDoctor || showPairingForm;
    const targetDocId = isPairing ? docIdInput.trim() : pairedDoctor.doc_id || '';
    if (!targetDocId) {
      setPinError('Please enter your Doctor ID or Username.');
      return;
    }
    if (isPairing && (!deviceCodeInput || deviceCodeInput.trim().length < 4)) {
      setPinError('Please enter the 6-digit Device Security Code from Doctor Master.');
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
      body.append('doctor_id', targetDocId);
      body.append('pin', pinInput);
      if (isPairing) {
        body.append('device_code', deviceCodeInput.trim());
      } else if (pairedDoctor && pairedDoctor.device_token) {
        body.append('device_token', pairedDoctor.device_token);
      }
      const res = await fetch('/api/v1/doctor/auth/verify-pin', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: body.toString()
      });
      const json = await res.json();
      if (json.status === 1 && json.doctor) {
        setAuthenticatedDoctor(json.doctor);
        const deviceAuthObj = {
          doctor: json.doctor,
          expires_at: Date.now() + THIRTY_DAYS_MS
        };
        localStorage.setItem(DOC_DEVICE_AUTH_KEY, JSON.stringify(deviceAuthObj));
        const pairedObj = {
          id: json.doctor.id,
          doc_id: json.doctor.id ? 'DOC-' + json.doctor.id : targetDocId,
          name: json.doctor.name,
          device_token: json.device_token || ''
        };
        localStorage.setItem(PAIRED_DOC_KEY, JSON.stringify(pairedObj));
        setPairedDoctor(pairedObj);
        setShowPairingForm(false);
        setPinInput('');
        setDeviceCodeInput('');
        fetchIpdPatients(json.doctor.id);
        fetchOpdAppointments(json.doctor.id, opdDateMode);
      } else if (json.status === 2) {
        setIsSettingNewPin(true);
      } else {
        if (json.need_device_code) {
          setShowPairingForm(true);
        }
        setPinError(json.message || 'Invalid Security PIN or Device Code');
      }
    } catch (err) {
      setPinError('Connection error verifying credentials.');
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
      const targetDocId = pairedDoctor ? pairedDoctor.id : authenticatedDoctor ? authenticatedDoctor.id : 0;
      const body = new URLSearchParams();
      body.append('doctor_id', targetDocId);
      body.append('new_pin', newPin);
      const res = await fetch('/api/v1/doctor/auth/set-pin', {
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
    setAuthenticatedDoctor(null);
    localStorage.removeItem(DOC_DEVICE_AUTH_KEY);
    setPinInput('');
    setActivePatient(null);
    setActiveOpd(null);
  };
  const handleUnpairDevice = () => {
    if (confirm('Unpair this device? You will need the Doctor ID and Device Security Code to pair it again.')) {
      setAuthenticatedDoctor(null);
      setPairedDoctor(null);
      localStorage.removeItem(DOC_DEVICE_AUTH_KEY);
      localStorage.removeItem(PAIRED_DOC_KEY);
      setShowPairingForm(true);
      setPinInput('');
      setDocIdInput('');
      setDeviceCodeInput('');
      setPinError('');
    }
  };
  const openIpdWorkspace = async patient => {
    setActivePatient(patient);
    clearCapturedImage();
    try {
      const res = await fetch(`/api/v1/doctor/ipd/workspace/${patient.ipd_id}`);
      const json = await res.json();
      if (json.status === 1) {
        setWorkspaceEntries(json.entries || []);
      }
    } catch (err) {
      console.error('Failed to load IPD workspace', err);
    }
  };
  const [cropSrc, setCropSrc] = useState('');
  const [cropper, setCropper] = useState(null);
  const imageRef = React.useRef(null);
  const handleImageCapture = e => {
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
        setNoteImageBase64(croppedBase64);
        setNoteImagePreview(croppedBase64);
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
  const clearCapturedImage = () => {
    setNoteImageBase64('');
    setNoteImagePreview('');
  };
  const handleSaveTreatmentNote = async () => {
    if (!activePatient || !newTreatmentNote.trim() && !noteImageBase64) {
      alert('Please enter clinical observations or take a picture of your handwritten note.');
      return;
    }
    try {
      const payload = {
        doctor_id: authenticatedDoctor ? authenticatedDoctor.id : 0,
        doctor_name: authenticatedDoctor ? authenticatedDoctor.name : 'Doctor',
        treatment_text: newTreatmentNote,
        image_base64: noteImageBase64 || ''
      };
      const res = await fetch(`/api/v1/doctor/ipd/treatment/save/${activePatient.ipd_id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 1) {
        alert(json.message || 'Clinical note / handwritten note saved successfully!');
        setNewTreatmentNote('');
        clearCapturedImage();
        openIpdWorkspace(activePatient);
      } else {
        alert(json.message || 'Failed to save treatment note');
      }
    } catch (err) {
      alert('Error saving treatment note');
    }
  };
  const openOpdConsultation = async app => {
    setActiveOpd(app);
    clearCapturedImage();
    try {
      const res = await fetch(`/api/v1/doctor/opd/prescription/${app.opd_id}`);
      const json = await res.json();
      if (json.status === 1 && json.prescription) {
        const pr = json.prescription;
        setPrescForm({
          chief_complaints: pr.chief_complaints || pr.complaints || '',
          finding_examinations: pr.finding_examinations || pr.Finding_Examinations || '',
          provisional_diagnosis: pr.provisional_diagnosis || pr.diagnosis || '',
          investigation: pr.investigation || '',
          advice: pr.advice || '',
          next_visit: pr.next_visit || '',
          temp: pr.temp || '',
          pulse: pr.pulse || '',
          bp_systolic: pr.bp || '',
          bp_diastolic: pr.diastolic || '',
          spo2: pr.spo2 || '',
          weight: pr.weight || '',
          height: pr.height || '',
          rr_min: pr.rr_min || ''
        });
        if (json.medicines && json.medicines.length > 0) {
          setMedicines(json.medicines);
        } else if (pr.json_medicines) {
          try {
            const parsedMeds = JSON.parse(pr.json_medicines);
            if (Array.isArray(parsedMeds) && parsedMeds.length > 0) {
              setMedicines(parsedMeds);
            }
          } catch (e) {}
        }
      } else {
        setPrescForm({
          chief_complaints: '',
          finding_examinations: '',
          provisional_diagnosis: '',
          investigation: '',
          advice: '',
          next_visit: '',
          temp: '',
          pulse: '',
          bp_systolic: '',
          bp_diastolic: '',
          spo2: '',
          weight: '',
          height: '',
          rr_min: ''
        });
        setMedicines([{
          drug_name: '',
          dosage: '1 Tab',
          frequency: '1-0-1',
          duration: '5 Days',
          instructions: 'After Food'
        }]);
      }
    } catch (err) {
      console.error('Failed to load OPD prescription', err);
    }
  };
  const handleSaveOpdPrescription = async () => {
    if (!activeOpd) return;
    try {
      const payload = {
        doctor_id: authenticatedDoctor ? authenticatedDoctor.id : 0,
        ...prescForm,
        medicines: medicines,
        image_base64: noteImageBase64 || ''
      };
      const res = await fetch(`/api/v1/doctor/opd/prescription/save/${activeOpd.opd_id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json();
      if (json.status === 1) {
        alert(json.message || 'Prescription saved & OPD Visit completed!');
        setActiveOpd(null);
        clearCapturedImage();
        fetchOpdAppointments(authenticatedDoctor ? authenticatedDoctor.id : 0, opdDateMode);
      } else {
        alert(json.message || 'Failed to save prescription');
      }
    } catch (err) {
      alert('Error saving OPD consultation');
    }
  };
  const addMedicineRow = (drugName = '') => {
    setMedicines([...medicines, {
      drug_name: drugName,
      dosage: '1 Tab',
      frequency: '1-0-1',
      duration: '5 Days',
      instructions: 'After Food'
    }]);
  };
  const removeMedicineRow = idx => {
    if (medicines.length === 1) return;
    setMedicines(medicines.filter((_, i) => i !== idx));
  };
  const updateMedicineRow = (idx, field, val) => {
    const updated = [...medicines];
    updated[idx][field] = val;
    setMedicines(updated);
  };
  const appendChipText = (field, text) => {
    const current = prescForm[field];
    if (!current) {
      setPrescForm({
        ...prescForm,
        [field]: text
      });
    } else if (!current.includes(text)) {
      setPrescForm({
        ...prescForm,
        [field]: `${current}, ${text}`
      });
    }
  };
  const filteredOpdAppointments = opdAppointments.filter(app => {
    const matchesFilter = opdFilter === 'all' || app.status_key === opdFilter;
    const q = searchQuery.toLowerCase();
    const name = (app.patient_display_name || '').toLowerCase();
    const uhid = (app.uhid || '').toLowerCase();
    const opdCode = (app.opd_code || '').toLowerCase();
    const matchesSearch = searchQuery === '' || name.includes(q) || uhid.includes(q) || opdCode.includes(q);
    return matchesFilter && matchesSearch;
  });
  const activeIpdSource = ipdViewMode === 'mine' ? myIpdPatients : allIpdPatients;
  const filteredIpdPatients = activeIpdSource.filter(pat => {
    const q = searchQuery.toLowerCase();
    const name = (pat.patient_display_name || pat.fname || '').toLowerCase();
    const uhid = (pat.uhid || '').toLowerCase();
    const ipdCode = (pat.ipd_code || '').toLowerCase();
    const docName = (pat.assigned_doctors || pat.r_doc_name || '').toLowerCase();
    return searchQuery === '' || name.includes(q) || uhid.includes(q) || ipdCode.includes(q) || docName.includes(q);
  });
  const getInitials = nameStr => {
    if (!nameStr) return 'P';
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
          }), " Doctor Handwritten Note Photo"]
        }), /*#__PURE__*/_jsx("a", {
          href: match[1],
          target: "_blank",
          rel: "noreferrer",
          children: /*#__PURE__*/_jsx("img", {
            src: match[1],
            alt: "Doctor Handwritten Note",
            className: "img-fluid rounded-3 border shadow-sm d-block mt-1",
            style: {
              maxHeight: '240px',
              objectFit: 'cover'
            }
          })
        })]
      })]
    });
  };

  // -------------------------------------------------------------
  // RENDER: LOCK SCREEN (DEVICE REGISTRATION & PIN AUTHENTICATION)
  // -------------------------------------------------------------
  if (!authenticatedDoctor) {
    const isQuickUnlock = Boolean(pairedDoctor && !showPairingForm);
    return /*#__PURE__*/_jsxs("div", {
      className: "lock-screen-overlay",
      children: [/*#__PURE__*/_jsx(MobileAppsModal, {
        show: showAppsModal,
        onClose: () => setShowAppsModal(false),
        currentApp: "doctor"
      }), /*#__PURE__*/_jsxs("div", {
        className: "lock-card",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "p-4 text-center bg-primary text-white position-relative",
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
            children: "Doctor EMR Mobile Portal"
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
                children: getInitials(pairedDoctor.name || pairedDoctor.doc_id)
              }), /*#__PURE__*/_jsx("h6", {
                className: "fw-bold text-dark mb-0",
                children: pairedDoctor.name || 'Doctor'
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-secondary-subtle text-dark border font-monospace mt-1",
                children: pairedDoctor.doc_id
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
              className: "btn btn-primary btn-lg w-100 py-3 fw-bold shadow-sm mb-2",
              onClick: handleVerifyPin,
              disabled: isVerifying,
              children: isVerifying ? /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "spinner-border spinner-border-sm me-2"
                }), "Verifying..."]
              }) : /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-unlock-fill me-2"
                }), " Unlock Doctor Portal"]
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
                }), " Switch Doctor / Re-Pair Device"]
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
                  children: "Enter Doctor ID and Device Security Code from Doctor Master."
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsxs("label", {
                className: "form-label fw-bold small text-dark mb-1",
                children: ["Doctor ID or Username ", /*#__PURE__*/_jsx("span", {
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
                  placeholder: "e.g. DOC-123 or doctor username",
                  value: docIdInput,
                  onChange: e => setDocIdInput(e.target.value)
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
                children: "Displayed on HMS Admin > Doctors"
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
            }), pairedDoctor && /*#__PURE__*/_jsx("div", {
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
             INITIAL PIN CREATION (IF DOCTOR DOES NOT HAVE A PIN YET)
             ------------------------------------------------------------- */
          _jsxs("div", {
            children: [/*#__PURE__*/_jsx("div", {
              className: "alert alert-info py-2 small mb-3",
              children: "Initial PIN Setup for Doctor Profile. Set a 4-6 digit Security PIN."
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
              className: "btn btn-success btn-lg w-100 py-3 fw-bold shadow-sm",
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
  // RENDER: UNLOCKED DOCTOR PWA APP
  // -------------------------------------------------------------
  return /*#__PURE__*/_jsxs("div", {
    children: [/*#__PURE__*/_jsx(MobileAppsModal, {
      show: showAppsModal,
      onClose: () => setShowAppsModal(false),
      currentApp: "doctor"
    }), /*#__PURE__*/_jsxs("header", {
      className: "doctor-header d-flex justify-content-between align-items-center",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "d-flex align-items-center gap-2",
        children: [/*#__PURE__*/_jsx("div", {
          className: "avatar-circle",
          children: getInitials(authenticatedDoctor.name)
        }), /*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsx("h6", {
            className: "mb-0 fw-bold",
            children: authenticatedDoctor.name
          }), /*#__PURE__*/_jsxs("small", {
            className: "text-light opacity-75",
            style: {
              fontSize: '11px'
            },
            children: ["Doctor EMR ", /*#__PURE__*/_jsx("span", {
              className: "badge bg-success ms-1",
              style: {
                fontSize: '9px'
              },
              children: "● Active Session"
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
            fetchOpdAppointments(authenticatedDoctor.id, opdDateMode);
            fetchIpdPatients(authenticatedDoctor.id);
          },
          title: "Refresh Queue",
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
          title: "Lock Device Access",
          children: /*#__PURE__*/_jsx("i", {
            className: "bi bi-lock-fill"
          })
        })]
      })]
    }), /*#__PURE__*/_jsxs("main", {
      className: "container-fluid py-3 px-2 px-md-3",
      children: [!activeOpd && !activePatient ? /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "segmented-tabs mb-3",
          children: [/*#__PURE__*/_jsxs("button", {
            className: `segmented-tab ${mainTab === 'opd' ? 'active' : ''}`,
            onClick: () => setMainTab('opd'),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-calendar-check-fill me-2"
            }), " OPD Appointments (", opdAppointments.length, ")"]
          }), /*#__PURE__*/_jsxs("button", {
            className: `segmented-tab ${mainTab === 'ipd' ? 'active' : ''}`,
            onClick: () => setMainTab('ipd'),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-hospital-fill me-2"
            }), " Admitted IPDs (", activeIpdSource.length, ")"]
          })]
        }), mainTab === 'opd' ? /*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center justify-content-between mb-3 bg-white p-2 rounded-4 border shadow-sm flex-wrap gap-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-1 bg-light p-1 rounded-pill border",
              children: [/*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm rounded-pill px-3 py-1 fw-bold ${opdDateMode === 'today' ? 'btn-primary shadow-sm' : 'btn-light text-secondary'}`,
                onClick: () => {
                  setOpdDateMode('today');
                  fetchOpdAppointments(authenticatedDoctor.id, 'today');
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-calendar-event me-1"
                }), " Today's OPDs (", opdCounts.today_total || 0, ")"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm rounded-pill px-3 py-1 fw-bold ${opdDateMode === 'all' ? 'btn-primary shadow-sm' : 'btn-light text-secondary'}`,
                onClick: () => {
                  setOpdDateMode('all');
                  fetchOpdAppointments(authenticatedDoctor.id, 'all');
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
              children: opdDateMode === 'today' ? '● Current Date Only' : '● All Appointments'
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex gap-2 mb-3 overflow-auto no-scrollbar pb-1",
            children: [/*#__PURE__*/_jsxs("button", {
              className: `btn btn-sm rounded-pill text-nowrap px-3 ${opdFilter === 'all' ? 'btn-primary fw-bold shadow-sm' : 'btn-white bg-white text-secondary border'}`,
              onClick: () => setOpdFilter('all'),
              children: ["All ", /*#__PURE__*/_jsx("span", {
                className: `badge ms-1 ${opdFilter === 'all' ? 'bg-white text-primary' : 'bg-light text-dark'}`,
                children: opdCounts.all
              })]
            }), /*#__PURE__*/_jsxs("button", {
              className: `btn btn-sm rounded-pill text-nowrap px-3 ${opdFilter === 'waiting' ? 'btn-primary fw-bold shadow-sm' : 'btn-white bg-white text-secondary border'}`,
              onClick: () => setOpdFilter('waiting'),
              children: ["Waiting ", /*#__PURE__*/_jsx("span", {
                className: `badge ms-1 ${opdFilter === 'waiting' ? 'bg-white text-primary' : 'bg-light text-dark'}`,
                children: opdCounts.waiting
              })]
            }), /*#__PURE__*/_jsxs("button", {
              className: `btn btn-sm rounded-pill text-nowrap px-3 ${opdFilter === 'visited' ? 'btn-info text-dark fw-bold shadow-sm' : 'btn-white bg-white text-secondary border'}`,
              onClick: () => setOpdFilter('visited'),
              children: ["Visited ", /*#__PURE__*/_jsx("span", {
                className: `badge ms-1 ${opdFilter === 'visited' ? 'bg-white text-dark' : 'bg-light text-dark'}`,
                children: opdCounts.visited
              })]
            }), /*#__PURE__*/_jsxs("button", {
              className: `btn btn-sm rounded-pill text-nowrap px-3 ${opdFilter === 'booked' ? 'btn-secondary fw-bold shadow-sm' : 'btn-white bg-white text-secondary border'}`,
              onClick: () => setOpdFilter('booked'),
              children: ["Booked ", /*#__PURE__*/_jsx("span", {
                className: `badge ms-1 ${opdFilter === 'booked' ? 'bg-white text-secondary' : 'bg-light text-dark'}`,
                children: opdCounts.booked
              })]
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
              placeholder: "Search Patient Name, UHID, OPD Code...",
              value: searchQuery,
              onChange: e => setSearchQuery(e.target.value)
            }), searchQuery && /*#__PURE__*/_jsx("button", {
              className: "btn bg-white border-0 pe-3 text-secondary",
              onClick: () => setSearchQuery(''),
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-x-circle-fill"
              })
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-block d-md-none",
            children: filteredOpdAppointments.length === 0 ? /*#__PURE__*/_jsxs("div", {
              className: "text-center text-muted py-5 bg-white rounded-4 border shadow-sm",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-person-x fs-1 text-secondary opacity-50 d-block mb-2"
              }), opdDateMode === 'today' ? "No OPD appointments registered for today's date." : "No OPD appointments found for selected filter."]
            }) : filteredOpdAppointments.map(app => {
              const statusClass = app.status_key === 'waiting' ? 'waiting' : app.status_key === 'visited' ? 'visited' : 'booked';
              return /*#__PURE__*/_jsxs("div", {
                className: `mobile-patient-card ${statusClass}`,
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex align-items-center gap-2",
                    children: [/*#__PURE__*/_jsxs("span", {
                      className: "queue-badge",
                      children: ["Q #", app.queue_no || app.opd_no || '1']
                    }), /*#__PURE__*/_jsxs("span", {
                      className: `status-pill ${statusClass}`,
                      children: ["● ", app.status_label]
                    })]
                  }), /*#__PURE__*/_jsx("code", {
                    className: "fw-bold text-slate-800 fs-6",
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
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex flex-wrap gap-1 align-items-center mt-1",
                      children: [/*#__PURE__*/_jsxs("span", {
                        className: "badge bg-slate-100 text-dark border px-2 py-1",
                        style: {
                          fontSize: '11px'
                        },
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-person-fill text-primary me-1"
                        }), app.age_display || '47 Year', " • ", app.gender_label]
                      }), /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-rose-50 text-danger border border-danger-subtle px-2 py-1",
                        style: {
                          fontSize: '11px'
                        },
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-card-text me-1"
                        }), app.uhid || '-']
                      }), /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-emerald-50 text-success border border-success-subtle px-2 py-1",
                        style: {
                          fontSize: '11px'
                        },
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-wallet2 me-1"
                        }), app.opd_type || 'Cash']
                      })]
                    })]
                  })]
                }), /*#__PURE__*/_jsx("div", {
                  children: /*#__PURE__*/_jsxs("button", {
                    className: "btn btn-action-primary w-100 py-2.5",
                    onClick: () => openOpdConsultation(app),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-file-earmark-medical-fill me-2"
                    }), " Consult Patient"]
                  })
                })]
              }, app.opd_id);
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "card shadow-sm border-0 d-none d-md-block rounded-4 overflow-hidden",
            children: /*#__PURE__*/_jsx("div", {
              className: "card-body p-0 table-responsive",
              children: /*#__PURE__*/_jsxs("table", {
                className: "table table-hover align-middle mb-0",
                children: [/*#__PURE__*/_jsx("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("th", {
                      children: "Status"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "OPD No."
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Current Patient"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Q No."
                    }), /*#__PURE__*/_jsx("th", {
                      children: "UHID"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "OPD Type"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-center",
                      children: "Action"
                    })]
                  })
                }), /*#__PURE__*/_jsx("tbody", {
                  children: filteredOpdAppointments.length === 0 ? /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsx("td", {
                      colSpan: "7",
                      className: "text-center text-muted py-4",
                      children: "No OPD appointments found for selected filter."
                    })
                  }) : filteredOpdAppointments.map(app => {
                    const statusBadgeClass = app.status_key === 'waiting' ? 'bg-primary' : app.status_key === 'visited' ? 'bg-info text-dark' : app.status_key === 'booked' ? 'bg-secondary' : 'bg-danger';
                    return /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("span", {
                          className: `badge ${statusBadgeClass} text-uppercase px-2 py-1`,
                          children: app.status_label
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("strong", {
                          className: "text-dark",
                          children: app.opd_code
                        })
                      }), /*#__PURE__*/_jsxs("td", {
                        children: [/*#__PURE__*/_jsx("div", {
                          className: "fw-bold text-primary",
                          children: app.patient_display_name
                        }), /*#__PURE__*/_jsxs("small", {
                          className: "text-muted",
                          children: ["(", app.age_display || '47 Year', " / ", app.gender_label, ")"]
                        })]
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("span", {
                          className: "badge bg-dark text-white fs-6",
                          children: app.queue_no || app.opd_no || '1'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("code", {
                          children: app.uhid || '-'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("small", {
                          className: "text-muted",
                          children: app.opd_type || 'Cash'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        className: "text-center",
                        children: /*#__PURE__*/_jsxs("button", {
                          className: "btn btn-sm btn-action-primary px-3",
                          onClick: () => openOpdConsultation(app),
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-file-earmark-medical me-1"
                          }), " Consult"]
                        })
                      })]
                    }, app.opd_id);
                  })
                })]
              })
            })
          })]
        }) :
        /*#__PURE__*/
        /* -------------------------------------------------------------
           ADMITTED IPD PATIENTS LIST (ASSIGNED TO DOCTOR / ALL ADMISSIONS)
           ------------------------------------------------------------- */
        _jsxs("div", {
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center justify-content-between mb-3 bg-white p-2 rounded-4 border shadow-sm flex-wrap gap-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-1 bg-light p-1 rounded-pill border",
              children: [/*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm rounded-pill px-3 py-1 fw-bold ${ipdViewMode === 'mine' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary'}`,
                onClick: () => setIpdViewMode('mine'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-person-check-fill me-1"
                }), " My Assigned Patients (", myIpdPatients.length, ")"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm rounded-pill px-3 py-1 fw-bold ${ipdViewMode === 'all' ? 'btn-success text-white shadow-sm' : 'btn-light text-secondary'}`,
                onClick: () => setIpdViewMode('all'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-hospital-fill me-1"
                }), " All Hospital Admissions (", allIpdPatients.length, ")"]
              })]
            }), /*#__PURE__*/_jsx("span", {
              className: "badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small",
              style: {
                fontSize: '11px'
              },
              children: ipdViewMode === 'mine' ? '● Doctor Assignments' : '● Hospital Wide IPDs'
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
              placeholder: "Search Admitted Patient Name, UHID, IPD Code...",
              value: searchQuery,
              onChange: e => setSearchQuery(e.target.value)
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-block d-md-none",
            children: filteredIpdPatients.length === 0 ? /*#__PURE__*/_jsxs("div", {
              className: "text-center text-muted py-5 bg-white rounded-4 border shadow-sm",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-hospital fs-1 d-block text-secondary opacity-50 mb-2"
              }), "No Admitted IPD Patients found."]
            }) : filteredIpdPatients.map(pat => /*#__PURE__*/_jsxs("div", {
              className: "mobile-patient-card",
              style: {
                borderLeft: '6px solid #16a34a'
              },
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold",
                  style: {
                    fontSize: '11px'
                  },
                  children: "● Current Admission"
                }), /*#__PURE__*/_jsx("code", {
                  className: "fw-bold text-dark fs-6",
                  children: pat.ipd_code
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-start gap-3 mb-3",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "patient-avatar-circle",
                  style: {
                    background: '#f0fdf4',
                    color: '#16a34a',
                    borderColor: '#bbf7d0'
                  },
                  children: getInitials(pat.patient_display_name)
                }), /*#__PURE__*/_jsxs("div", {
                  className: "flex-grow-1",
                  children: [/*#__PURE__*/_jsx("h5", {
                    className: "fw-bold text-slate-900 mb-1",
                    style: {
                      fontSize: '16px'
                    },
                    children: pat.patient_display_name
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted d-block mb-2",
                    style: {
                      fontSize: '12px'
                    },
                    children: pat.rname ? `S/D/W of ${pat.rname}` : `(${pat.age_display} • ${pat.gender_label})`
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex flex-wrap gap-1",
                    children: [/*#__PURE__*/_jsxs("span", {
                      className: "badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1",
                      style: {
                        fontSize: '11px'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-door-closed me-1"
                      }), pat.bed_label]
                    }), /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-rose-50 text-danger border border-danger-subtle px-2 py-1",
                      style: {
                        fontSize: '11px'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-card-text me-1"
                      }), pat.uhid]
                    }), /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-light text-dark border px-2 py-1",
                      style: {
                        fontSize: '11px'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-person-badge me-1"
                      }), pat.assigned_doctors || pat.r_doc_name || `Dr. ${authenticatedDoctor.name}`]
                    })]
                  })]
                })]
              }), /*#__PURE__*/_jsx("div", {
                children: /*#__PURE__*/_jsxs("button", {
                  className: "btn btn-action-success w-100 py-2.5",
                  onClick: () => openIpdWorkspace(pat),
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-heart-pulse-fill me-2"
                  }), " Open Bedside Workspace"]
                })
              })]
            }, pat.ipd_id))
          }), /*#__PURE__*/_jsx("div", {
            className: "card shadow-sm border-0 d-none d-md-block rounded-4 overflow-hidden",
            children: /*#__PURE__*/_jsx("div", {
              className: "card-body p-0 table-responsive",
              children: /*#__PURE__*/_jsxs("table", {
                className: "table table-hover align-middle mb-0",
                children: [/*#__PURE__*/_jsx("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("th", {
                      children: "IPD Code"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Name / Patient Code"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Bed No. [Type]"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Register Date"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Dr. Name"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-center",
                      children: "Action"
                    })]
                  })
                }), /*#__PURE__*/_jsx("tbody", {
                  children: filteredIpdPatients.length === 0 ? /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsxs("td", {
                      colSpan: "6",
                      className: "text-center text-muted py-4",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-hospital fs-1 d-block text-secondary mb-2"
                      }), "No Admitted IPD Patients found."]
                    })
                  }) : filteredIpdPatients.map(pat => /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsx("strong", {
                        className: "text-dark d-block",
                        children: pat.ipd_code
                      }), /*#__PURE__*/_jsx("span", {
                        className: "badge bg-success",
                        style: {
                          fontSize: '9px'
                        },
                        children: "CURRENT ADMISSION"
                      })]
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsxs("small", {
                        className: "text-danger fw-bold d-block",
                        children: ["[", pat.uhid, "]"]
                      }), /*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-primary",
                        children: pat.patient_display_name
                      }), /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        children: pat.rname ? `S/D/W of ${pat.rname}` : `(${pat.age_display} / ${pat.gender_label})`
                      })]
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("span", {
                        className: "badge bg-primary-subtle text-primary border",
                        children: pat.bed_label
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("small", {
                        className: "text-dark fw-semibold",
                        children: pat.register_date || 'Today'
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("small", {
                        className: "text-dark fw-semibold",
                        children: pat.assigned_doctors || pat.r_doc_name || `Dr. ${authenticatedDoctor.name}`
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      className: "text-center",
                      children: /*#__PURE__*/_jsxs("button", {
                        className: "btn btn-sm btn-action-success px-3",
                        onClick: () => openIpdWorkspace(pat),
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-heart-pulse-fill me-1"
                        }), " Bedside Workspace"]
                      })
                    })]
                  }, pat.ipd_id))
                })]
              })
            })
          })]
        })]
      }) : activePatient ?
      /*#__PURE__*/
      /* -------------------------------------------------------------
         IPD BEDSIDE WORKSPACE & CLINICAL ROUNDS
         ------------------------------------------------------------- */
      _jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between align-items-center mb-3",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-sm btn-outline-dark rounded-pill px-3",
            onClick: () => setActivePatient(null),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-left me-1"
            }), " Back to Admitted IPDs"]
          }), /*#__PURE__*/_jsx("span", {
            className: "badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fw-bold",
            children: "● CURRENT ADMISSION"
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
              children: activePatient.patient_display_name
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex flex-wrap gap-2 opacity-90 small mt-2",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "badge bg-white text-dark",
                children: [activePatient.age_display, " • ", activePatient.gender_label]
              }), /*#__PURE__*/_jsxs("span", {
                className: "badge bg-white text-danger",
                children: ["UHID: ", activePatient.uhid]
              }), /*#__PURE__*/_jsxs("span", {
                className: "badge bg-white text-primary",
                children: ["IPD: ", activePatient.ipd_code]
              }), /*#__PURE__*/_jsxs("span", {
                className: "badge bg-white text-success",
                children: ["Bed: ", activePatient.bed_label]
              })]
            })]
          })
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-2",
            children: [/*#__PURE__*/_jsxs("label", {
              className: "form-label fw-bold small text-dark mb-0",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-pencil-square text-primary me-1"
              }), " Write Clinical Round Note or Attach Photo"]
            }), /*#__PURE__*/_jsxs("label", {
              className: "btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold",
              style: {
                fontSize: '11px'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-camera-fill me-1"
              }), " Take Photo / Attach", /*#__PURE__*/_jsx("input", {
                type: "file",
                accept: "image/*",
                capture: "environment",
                className: "d-none",
                onChange: handleImageCapture
              })]
            })]
          }), noteImagePreview && /*#__PURE__*/_jsxs("div", {
            className: "position-relative mb-2 p-2 border rounded-3 bg-light text-center",
            children: [/*#__PURE__*/_jsxs("span", {
              className: "badge bg-primary position-absolute top-0 start-0 m-2",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-camera me-1"
              }), " Handwritten Note Attached"]
            }), /*#__PURE__*/_jsx("img", {
              src: noteImagePreview,
              alt: "Handwritten Note Preview",
              className: "img-fluid rounded-3 border shadow-sm",
              style: {
                maxHeight: '180px',
                objectFit: 'contain'
              }
            }), /*#__PURE__*/_jsx("button", {
              className: "btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle p-1",
              style: {
                width: '28px',
                height: '28px'
              },
              onClick: clearCapturedImage,
              title: "Remove Photo",
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-x-lg"
              })
            })]
          }), /*#__PURE__*/_jsx("textarea", {
            className: "form-control mb-2 rounded-3",
            rows: "3",
            placeholder: "Enter clinical observations, daily round progress note, or bedside treatment instructions...",
            value: newTreatmentNote,
            onChange: e => setNewTreatmentNote(e.target.value)
          }), /*#__PURE__*/_jsxs("button", {
            className: "btn btn-action-primary btn-sm fw-bold align-self-end px-4",
            onClick: handleSaveTreatmentNote,
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-send-fill me-1"
            }), " Save Round Note & Share with Nursing"]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 rounded-4",
          children: [/*#__PURE__*/_jsxs("h6", {
            className: "fw-bold mb-3",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-journal-medical me-1"
            }), " Bedside Clinical Log History"]
          }), workspaceEntries.length === 0 ? /*#__PURE__*/_jsx("p", {
            className: "text-muted small",
            children: "No bedside log entries recorded yet."
          }) : workspaceEntries.map(e => /*#__PURE__*/_jsxs("div", {
            className: "p-3 border rounded-3 bg-light mb-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center mb-1",
              children: [/*#__PURE__*/_jsx("span", {
                className: "badge bg-secondary",
                style: {
                  fontSize: '10px'
                },
                children: e.entry_type
              }), /*#__PURE__*/_jsxs("small", {
                className: "text-muted",
                style: {
                  fontSize: '10px'
                },
                children: [e.recorded_at, " - by ", e.recorded_by]
              })]
            }), renderNoteContent(e)]
          }, e.id))]
        })]
      }) : activeOpd ?
      /*#__PURE__*/
      /* -------------------------------------------------------------
         OPD CONSULTATION & PRESCRIPTION FORM
         ------------------------------------------------------------- */
      _jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between align-items-center mb-3",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-sm btn-outline-dark rounded-pill px-3",
            onClick: () => setActiveOpd(null),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-left me-1"
            }), " Back to Appointments"]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex gap-2",
            children: [/*#__PURE__*/_jsxs("button", {
              className: "btn btn-sm btn-outline-secondary fw-bold rounded-pill px-3",
              onClick: () => window.open(`/Opd/opd_lettre_pdf/${activeOpd.opd_id}?layout=content_only`, '_blank'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-printer me-1"
              }), " Print PDF"]
            }), /*#__PURE__*/_jsxs("button", {
              className: "btn btn-sm btn-action-success fw-bold rounded-pill px-3",
              onClick: handleSaveOpdPrescription,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-check-circle-fill me-1"
              }), " Save & Mark Visited"]
            })]
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "card shadow-sm mb-3 border-0 rounded-4 text-white",
          style: {
            background: 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)'
          },
          children: /*#__PURE__*/_jsx("div", {
            className: "card-body p-3",
            children: /*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-start",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h5", {
                  className: "fw-bold mb-1",
                  children: activeOpd.patient_display_name
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex flex-wrap gap-1 opacity-90 small mt-2",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "badge bg-white text-dark",
                    children: [activeOpd.age_display || '47 Year', " • ", activeOpd.gender_label]
                  }), /*#__PURE__*/_jsxs("span", {
                    className: "badge bg-white text-danger",
                    children: ["UHID: ", activeOpd.uhid]
                  }), /*#__PURE__*/_jsxs("span", {
                    className: "badge bg-white text-primary",
                    children: ["OPD: ", activeOpd.opd_code]
                  })]
                })]
              }), /*#__PURE__*/_jsxs("span", {
                className: "queue-badge fs-6",
                children: ["Q #", activeOpd.queue_no || '1']
              })]
            })
          })
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4 bg-primary-subtle border-primary-subtle",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "fw-bold mb-0 text-primary",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-camera-fill me-1"
                }), " Attach Photo of Handwritten Rx Note"]
              }), /*#__PURE__*/_jsx("small", {
                className: "text-muted d-block",
                style: {
                  fontSize: '11px'
                },
                children: "Snap a picture of your paper prescription to send to Pharmacy / Patient"
              })]
            }), /*#__PURE__*/_jsxs("label", {
              className: "btn btn-sm btn-primary rounded-pill px-3 py-1.5 fw-bold",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-camera me-1"
              }), " Snap Photo", /*#__PURE__*/_jsx("input", {
                type: "file",
                accept: "image/*",
                capture: "environment",
                className: "d-none",
                onChange: handleImageCapture
              })]
            })]
          }), noteImagePreview && /*#__PURE__*/_jsxs("div", {
            className: "position-relative mt-2 p-2 border rounded-3 bg-white text-center",
            children: [/*#__PURE__*/_jsxs("span", {
              className: "badge bg-success position-absolute top-0 start-0 m-2",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-check-circle me-1"
              }), " Rx Photo Attached"]
            }), /*#__PURE__*/_jsx("img", {
              src: noteImagePreview,
              alt: "Handwritten Rx Preview",
              className: "img-fluid rounded-3 border shadow-sm",
              style: {
                maxHeight: '200px',
                objectFit: 'contain'
              }
            }), /*#__PURE__*/_jsx("button", {
              className: "btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle p-1",
              style: {
                width: '28px',
                height: '28px'
              },
              onClick: clearCapturedImage,
              title: "Remove Photo",
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-x-lg"
              })
            })]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
          children: [/*#__PURE__*/_jsxs("h6", {
            className: "fw-bold mb-2 text-danger small",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-heart-pulse-fill me-1"
            }), " Patient Vitals (BP, Pulse, Temp, SpO2, Weight)"]
          }), /*#__PURE__*/_jsxs("div", {
            className: "row g-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "col-4 col-md-2",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small mb-1",
                style: {
                  fontSize: '11px'
                },
                children: "BP Sys/Dia"
              }), /*#__PURE__*/_jsxs("div", {
                className: "input-group input-group-sm",
                children: [/*#__PURE__*/_jsx("input", {
                  type: "number",
                  className: "form-control px-1 text-center",
                  placeholder: "120",
                  value: prescForm.bp_systolic,
                  onChange: e => setPrescForm({
                    ...prescForm,
                    bp_systolic: e.target.value
                  })
                }), /*#__PURE__*/_jsx("span", {
                  className: "input-group-text px-1",
                  children: "/"
                }), /*#__PURE__*/_jsx("input", {
                  type: "number",
                  className: "form-control px-1 text-center",
                  placeholder: "80",
                  value: prescForm.bp_diastolic,
                  onChange: e => setPrescForm({
                    ...prescForm,
                    bp_diastolic: e.target.value
                  })
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "col-4 col-md-2",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small mb-1",
                style: {
                  fontSize: '11px'
                },
                children: "Pulse"
              }), /*#__PURE__*/_jsx("input", {
                type: "number",
                className: "form-control form-control-sm text-center",
                placeholder: "72",
                value: prescForm.pulse,
                onChange: e => setPrescForm({
                  ...prescForm,
                  pulse: e.target.value
                })
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "col-4 col-md-2",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small mb-1",
                style: {
                  fontSize: '11px'
                },
                children: "SpO2 %"
              }), /*#__PURE__*/_jsx("input", {
                type: "number",
                className: "form-control form-control-sm text-center",
                placeholder: "98",
                value: prescForm.spo2,
                onChange: e => setPrescForm({
                  ...prescForm,
                  spo2: e.target.value
                })
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "col-4 col-md-2",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small mb-1",
                style: {
                  fontSize: '11px'
                },
                children: "Temp °F"
              }), /*#__PURE__*/_jsx("input", {
                type: "number",
                step: "0.1",
                className: "form-control form-control-sm text-center",
                placeholder: "98.6",
                value: prescForm.temp,
                onChange: e => setPrescForm({
                  ...prescForm,
                  temp: e.target.value
                })
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "col-4 col-md-2",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small mb-1",
                style: {
                  fontSize: '11px'
                },
                children: "Weight (kg)"
              }), /*#__PURE__*/_jsx("input", {
                type: "number",
                className: "form-control form-control-sm text-center",
                placeholder: "70",
                value: prescForm.weight,
                onChange: e => setPrescForm({
                  ...prescForm,
                  weight: e.target.value
                })
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "col-4 col-md-2",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small mb-1",
                style: {
                  fontSize: '11px'
                },
                children: "Height (cm)"
              }), /*#__PURE__*/_jsx("input", {
                type: "number",
                className: "form-control form-control-sm text-center",
                placeholder: "165",
                value: prescForm.height,
                onChange: e => setPrescForm({
                  ...prescForm,
                  height: e.target.value
                })
              })]
            })]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
          children: [/*#__PURE__*/_jsxs("label", {
            className: "form-label fw-bold small text-dark mb-1",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-chat-square-text text-primary me-1"
            }), " Chief Complaints"]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-flex gap-1 flex-wrap mb-2",
            children: COMPLAINT_CHIPS.map(c => /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "chip-btn",
              onClick: () => appendChipText('chief_complaints', c),
              children: ["+ ", c]
            }, c))
          }), /*#__PURE__*/_jsx("textarea", {
            className: "form-control form-control-sm rounded-3",
            rows: "2",
            placeholder: "Enter chief complaints...",
            value: prescForm.chief_complaints,
            onChange: e => setPrescForm({
              ...prescForm,
              chief_complaints: e.target.value
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
          children: [/*#__PURE__*/_jsxs("label", {
            className: "form-label fw-bold small text-dark mb-1",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-clipboard2-pulse text-info me-1"
            }), " Clinical Examination & Findings"]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-flex gap-1 flex-wrap mb-2",
            children: EXAMINATION_CHIPS.map(e => /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "chip-btn",
              onClick: () => appendChipText('finding_examinations', e),
              children: ["+ ", e]
            }, e))
          }), /*#__PURE__*/_jsx("textarea", {
            className: "form-control form-control-sm rounded-3",
            rows: "2",
            placeholder: "Enter clinical examination & findings...",
            value: prescForm.finding_examinations,
            onChange: e => setPrescForm({
              ...prescForm,
              finding_examinations: e.target.value
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
          children: [/*#__PURE__*/_jsxs("label", {
            className: "form-label fw-bold small text-dark mb-1",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-stethoscope text-primary me-1"
            }), " Provisional Diagnosis"]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-flex gap-1 flex-wrap mb-2",
            children: DIAGNOSIS_CHIPS.map(d => /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "chip-btn",
              onClick: () => appendChipText('provisional_diagnosis', d),
              children: ["+ ", d]
            }, d))
          }), /*#__PURE__*/_jsx("textarea", {
            className: "form-control form-control-sm rounded-3",
            rows: "2",
            placeholder: "Enter diagnosis...",
            value: prescForm.provisional_diagnosis,
            onChange: e => setPrescForm({
              ...prescForm,
              provisional_diagnosis: e.target.value
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-2",
            children: [/*#__PURE__*/_jsxs("label", {
              className: "form-label fw-bold small text-dark mb-0",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-capsule text-success me-1"
              }), " Prescribed Medicines (Rx)"]
            }), /*#__PURE__*/_jsx("button", {
              className: "btn btn-sm btn-outline-success py-0 px-2 fw-bold rounded-pill",
              style: {
                fontSize: '11px'
              },
              onClick: () => addMedicineRow(),
              children: "+ Add Drug"
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-flex gap-1 flex-wrap mb-2",
            children: DRUG_CHIPS.map(drug => /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "chip-btn",
              onClick: () => addMedicineRow(drug),
              children: ["+ ", drug]
            }, drug))
          }), medicines.map((med, idx) => /*#__PURE__*/_jsxs("div", {
            className: "p-2 border rounded-3 bg-light mb-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center mb-1",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "badge bg-secondary",
                style: {
                  fontSize: '10px'
                },
                children: ["Item #", idx + 1]
              }), medicines.length > 1 && /*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-link text-danger p-0",
                onClick: () => removeMedicineRow(idx),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-trash"
                }), " Remove"]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "row g-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "col-12 col-md-4",
                children: /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm",
                  placeholder: "Medicine / Drug Name",
                  value: med.drug_name,
                  onChange: e => updateMedicineRow(idx, 'drug_name', e.target.value)
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "col-4 col-md-2",
                children: /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm",
                  placeholder: "Dosage (1 Tab)",
                  value: med.dosage,
                  onChange: e => updateMedicineRow(idx, 'dosage', e.target.value)
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "col-4 col-md-3",
                children: /*#__PURE__*/_jsxs("select", {
                  className: "form-select form-select-sm",
                  value: med.frequency,
                  onChange: e => updateMedicineRow(idx, 'frequency', e.target.value),
                  children: [/*#__PURE__*/_jsx("option", {
                    value: "1-0-1",
                    children: "1-0-1 (BD)"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "1-1-1",
                    children: "1-1-1 (TID)"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "1-0-0",
                    children: "1-0-0 (OD Morning)"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "0-0-1",
                    children: "0-0-1 (OD Night)"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "SOS",
                    children: "SOS (When needed)"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "STAT",
                    children: "STAT (Immediately)"
                  })]
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "col-4 col-md-3",
                children: /*#__PURE__*/_jsxs("select", {
                  className: "form-select form-select-sm",
                  value: med.duration,
                  onChange: e => updateMedicineRow(idx, 'duration', e.target.value),
                  children: [/*#__PURE__*/_jsx("option", {
                    value: "3 Days",
                    children: "3 Days"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "5 Days",
                    children: "5 Days"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "7 Days",
                    children: "7 Days"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "15 Days",
                    children: "15 Days"
                  }), /*#__PURE__*/_jsx("option", {
                    value: "1 Month",
                    children: "1 Month"
                  })]
                })
              })]
            })]
          }, idx))]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card p-3 shadow-sm border-0 mb-3 rounded-4",
          children: [/*#__PURE__*/_jsxs("label", {
            className: "form-label fw-bold small text-dark mb-1",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-file-earmark-medical text-warning me-1"
            }), " Lab & Pathology Orders"]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-flex gap-1 flex-wrap mb-2",
            children: LAB_CHIPS.map(lab => /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "chip-btn",
              onClick: () => appendChipText('investigation', lab),
              children: ["+ ", lab]
            }, lab))
          }), /*#__PURE__*/_jsx("textarea", {
            className: "form-control form-control-sm mb-3 rounded-3",
            rows: "2",
            placeholder: "Lab / Pathology / Radiology tests...",
            value: prescForm.investigation,
            onChange: e => setPrescForm({
              ...prescForm,
              investigation: e.target.value
            })
          }), /*#__PURE__*/_jsxs("label", {
            className: "form-label fw-bold small text-dark mb-1",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-card-text me-1"
            }), " General Advice & Instructions"]
          }), /*#__PURE__*/_jsx("textarea", {
            className: "form-control form-control-sm mb-3 rounded-3",
            rows: "2",
            placeholder: "Doctor's advice to patient...",
            value: prescForm.advice,
            onChange: e => setPrescForm({
              ...prescForm,
              advice: e.target.value
            })
          }), /*#__PURE__*/_jsxs("label", {
            className: "form-label fw-bold small text-dark mb-1",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-calendar-event me-1"
            }), " Follow-up Next Visit"]
          }), /*#__PURE__*/_jsx("div", {
            className: "d-flex gap-1 mb-2",
            children: ['After 3 Days', 'After 7 Days', 'After 15 Days', 'After 1 Month'].map(d => /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "chip-btn",
              onClick: () => setPrescForm({
                ...prescForm,
                next_visit: d
              }),
              children: d
            }, d))
          }), /*#__PURE__*/_jsx("input", {
            type: "text",
            className: "form-control form-control-sm rounded-3",
            placeholder: "Follow-up advice",
            value: prescForm.next_visit,
            onChange: e => setPrescForm({
              ...prescForm,
              next_visit: e.target.value
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex gap-2 mb-4",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-outline-secondary btn-lg w-50 fw-bold py-2.5 rounded-3 shadow-sm",
            onClick: () => window.open(`/Opd/opd_lettre_pdf/${activeOpd.opd_id}?layout=content_only`, '_blank'),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-printer me-1"
            }), " Print PDF"]
          }), /*#__PURE__*/_jsxs("button", {
            className: "btn btn-action-success btn-lg w-50 fw-bold py-2.5 rounded-3 shadow-sm",
            onClick: handleSaveOpdPrescription,
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-check-circle-fill me-1"
            }), " Save & Complete Visit"]
          })]
        })]
      }) : null, cropSrc && /*#__PURE__*/_jsx("div", {
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
                  className: "bi bi-crop me-2 text-primary"
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
      })]
    })]
  });
}
const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(/*#__PURE__*/_jsx(DoctorApp, {}));