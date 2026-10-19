import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
const {
  useState,
  useEffect,
  useRef,
  useMemo,
  useCallback
} = React;
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
    this.state = {
      hasError: false,
      error: null
    };
  }
  static getDerivedStateFromError(error) {
    return {
      hasError: true,
      error
    };
  }
  componentDidCatch(error, errorInfo) {
    console.error("Medical Store UI Error:", error, errorInfo);
  }
  render() {
    if (this.state.hasError) {
      return /*#__PURE__*/_jsxs("div", {
        className: "d-flex flex-column align-items-center justify-content-center vh-100 bg-slate-900 text-white p-4 text-center",
        style: {
          background: '#0f172a'
        },
        children: [/*#__PURE__*/_jsx("div", {
          className: "bg-danger text-white rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center",
          style: {
            width: 64,
            height: 64
          },
          children: /*#__PURE__*/_jsx("i", {
            className: "bi bi-exclamation-octagon fs-2"
          })
        }), /*#__PURE__*/_jsx("h4", {
          className: "fw-bold",
          children: "Counter Desk Interruption"
        }), /*#__PURE__*/_jsx("p", {
          className: "text-secondary mb-3",
          style: {
            maxWidth: 500
          },
          children: this.state.error?.message || 'An unexpected error occurred while rendering the counter desk.'
        }), /*#__PURE__*/_jsxs("button", {
          className: "btn btn-outline-light btn-sm",
          onClick: () => window.location.reload(),
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-arrow-clockwise me-1"
          }), " Reload Counter Desk"]
        })]
      });
    }
    return this.props.children;
  }
}
function App() {
  const [stores, setStores] = useState([]);
  const [activeStoreId, setActiveStoreId] = useState(null);
  const [activeTab, setActiveTab] = useState('pos'); // pos, invoices, inventory, purchase, transfer, ledgers
  const [editInvoiceForPos, setEditInvoiceForPos] = useState(null);
  const [notification, setNotification] = useState(null);
  const [isLoadingStores, setIsLoadingStores] = useState(true);

  // Mobile Companion Mode & PWA Installation State
  const [isMobileMode, setIsMobileMode] = useState(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('mode') === 'mobile' || window.innerWidth <= 768 && !urlParams.get('desktop');
  });
  const [showQrModal, setShowQrModal] = useState(false);
  const [serverNetworkInfo, setServerNetworkInfo] = useState(null);
  const [selectedLanIp, setSelectedLanIp] = useState('');
  const [pwaInstallPrompt, setPwaInstallPrompt] = useState(null);

  // Active Store Operator / Staff Session State
  const [activeOperator, setActiveOperator] = useState(() => {
    try {
      const saved = localStorage.getItem('mst_active_operator');
      return saved ? JSON.parse(saved) : null;
    } catch (e) {
      return null;
    }
  });
  const [storeUsers, setStoreUsers] = useState([]);
  const [showOperatorModal, setShowOperatorModal] = useState(false);
  const [selectedUserToSwitch, setSelectedUserToSwitch] = useState(null);
  const [operatorPinInput, setOperatorPinInput] = useState('');
  const [pinError, setPinError] = useState('');
  const [isSwitchingOperator, setIsSwitchingOperator] = useState(false);

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
      loadStoreUsers(activeStore.store_id);
      loadServerNetworkInfo();
    }
    const handleInstallPrompt = e => {
      e.preventDefault();
      setPwaInstallPrompt(e);
    };
    window.addEventListener('beforeinstallprompt', handleInstallPrompt);
    return () => window.removeEventListener('beforeinstallprompt', handleInstallPrompt);
  }, [activeStoreId, stores]);
  const loadServerNetworkInfo = async () => {
    try {
      const res = await fetch(`${API_BASE}server-info`);
      const data = await res.json();
      if (data.status) {
        setServerNetworkInfo(data);
        setSelectedLanIp(data.primary_ip || window.location.hostname);
      }
    } catch (e) {
      console.error("Failed to load server network info:", e);
    }
  };
  const triggerPwaInstall = async () => {
    if (!pwaInstallPrompt) {
      notify('To install on mobile, tap your browser menu (⋮ or Share) and select "Add to Home screen".', 'info');
      return;
    }
    pwaInstallPrompt.prompt();
    const choice = await pwaInstallPrompt.userChoice;
    if (choice.outcome === 'accepted') {
      notify('Pharmacy Application installed successfully!', 'success');
      setPwaInstallPrompt(null);
    }
  };
  const loadStoreUsers = async storeId => {
    if (!storeId) return;
    try {
      const res = await fetch(`${API_BASE}users?store_id=${storeId}`);
      const data = await res.json();
      if (data.status && Array.isArray(data.users)) {
        setStoreUsers(data.users);
        if (!activeOperator || activeOperator.store_id != storeId && activeOperator.store_id != 0) {
          const def = data.users.find(u => u.is_default == 1) || data.users[0];
          if (def) {
            setActiveOperator(def);
            localStorage.setItem('mst_active_operator', JSON.stringify(def));
          }
        }
      }
    } catch (e) {
      console.error("Failed to load store staff:", e);
    }
  };
  const handleSwitchOperator = async () => {
    if (!selectedUserToSwitch) return;
    if (!operatorPinInput.trim()) {
      setPinError('Please enter 4-digit PIN');
      return;
    }
    setIsSwitchingOperator(true);
    setPinError('');
    try {
      const res = await fetch(`${API_BASE}users/switch-operator`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: activeStore?.store_id || 1,
          user_id: selectedUserToSwitch.user_id,
          pin_code: operatorPinInput.trim()
        })
      });
      const data = await res.json();
      if (data.status && data.operator) {
        setActiveOperator(data.operator);
        localStorage.setItem('mst_active_operator', JSON.stringify(data.operator));
        notify(`Switched operator to ${data.operator.full_name} (${data.operator.user_role})`, 'success');
        setShowOperatorModal(false);
        setSelectedUserToSwitch(null);
        setOperatorPinInput('');
      } else {
        setPinError(data.message || 'Incorrect PIN code');
      }
    } catch (e) {
      setPinError('Network error while verifying PIN');
    } finally {
      setIsSwitchingOperator(false);
    }
  };
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
          const matched = data.stores.find(s => s.store_slug && s.store_slug.toLowerCase() === storeSlugFromUrl.toLowerCase() || s.store_code && s.store_code.toLowerCase() === storeSlugFromUrl.toLowerCase());
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
  const checkMachineAuthorization = async storeId => {
    const savedToken = localStorage.getItem('mst_device_token_' + storeId);
    if (!savedToken) {
      setIsDeviceAuthorized(false);
      return;
    }
    try {
      const res = await fetch(API_BASE + 'device/check', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: storeId,
          device_token: savedToken
        })
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
  const submitDeviceVerification = async e => {
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
        headers: {
          'Content-Type': 'application/json'
        },
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
    setNotification({
      msg,
      type
    });
    setTimeout(() => setNotification(null), 4000);
  };
  if (isLoadingStores && !activeStore) {
    return /*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-column align-items-center justify-content-center vh-100 bg-slate-900 text-white",
      style: {
        background: '#0f172a'
      },
      children: [/*#__PURE__*/_jsx("div", {
        className: "spinner-border text-info mb-3",
        style: {
          width: '3rem',
          height: '3rem'
        },
        role: "status",
        children: /*#__PURE__*/_jsx("span", {
          className: "visually-hidden",
          children: "Loading Store Counter..."
        })
      }), /*#__PURE__*/_jsx("h5", {
        className: "fw-bold tracking-wide",
        children: "HMS Multi-Building Medical Store"
      }), /*#__PURE__*/_jsx("p", {
        className: "text-secondary small",
        children: "Synchronizing pharmacy counters, terminal licenses & ABDM..."
      })]
    });
  }
  if (!activeStore) {
    return /*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-column align-items-center justify-content-center vh-100 bg-slate-900 text-white p-4 text-center",
      style: {
        background: '#0f172a'
      },
      children: [/*#__PURE__*/_jsx("div", {
        className: "bg-danger text-white rounded-circle p-3 mb-3 d-flex align-items-center justify-content-center",
        style: {
          width: 64,
          height: 64
        },
        children: /*#__PURE__*/_jsx("i", {
          className: "bi bi-exclamation-triangle fs-2"
        })
      }), /*#__PURE__*/_jsx("h4", {
        className: "fw-bold",
        children: "Medical Store Not Found"
      }), /*#__PURE__*/_jsxs("p", {
        className: "text-secondary mb-3",
        style: {
          maxWidth: 480
        },
        children: ["Could not locate any active pharmacy store counter for ", /*#__PURE__*/_jsxs("code", {
          children: ["/", storeSlugFromUrl || 'default']
        }), ". Please verify store configuration in HMS Admin."]
      }), /*#__PURE__*/_jsxs("div", {
        className: "d-flex gap-2",
        children: [/*#__PURE__*/_jsxs("a", {
          href: "/MedicalStore/storeA",
          className: "btn btn-outline-light btn-sm",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-shop me-1"
          }), " Try Main Store (storeA)"]
        }), /*#__PURE__*/_jsxs("a", {
          href: "/app/admin/medical-stores",
          className: "btn btn-primary-custom btn-sm",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-gear me-1"
          }), " Open HMS Store Settings"]
        })]
      })]
    });
  }
  return /*#__PURE__*/_jsxs("div", {
    children: [!isDeviceAuthorized && activeStore && /*#__PURE__*/_jsx("div", {
      className: "machine-lock-backdrop",
      children: /*#__PURE__*/_jsxs("div", {
        className: "card shadow-lg border-0",
        style: {
          maxWidth: 460,
          width: '100%',
          borderRadius: 16
        },
        children: [/*#__PURE__*/_jsxs("div", {
          className: "card-header bg-dark text-white p-3 text-center border-0",
          style: {
            borderTopLeftRadius: 16,
            borderTopRightRadius: 16
          },
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-shield-lock-fill text-warning fs-1 d-block mb-1"
          }), /*#__PURE__*/_jsx("h5", {
            className: "fw-bold mb-0",
            children: "Terminal Machine Verification"
          }), /*#__PURE__*/_jsx("small", {
            className: "text-slate-300",
            style: {
              color: '#94a3b8'
            },
            children: "Counter Authorization Required"
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card-body p-4",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "text-center mb-3",
            children: [/*#__PURE__*/_jsx("h6", {
              className: "fw-bold text-dark",
              children: activeStore.store_name
            }), /*#__PURE__*/_jsxs("small", {
              className: "text-muted d-block",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-geo-alt me-1"
              }), activeStore.building_name || 'Main Hospital', " • ", activeStore.floor_no || 'Ground Floor']
            }), /*#__PURE__*/_jsxs("span", {
              className: "badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mt-1",
              children: ["Link: MedicalStore/", activeStore.store_slug]
            })]
          }), /*#__PURE__*/_jsxs("p", {
            className: "small text-muted text-center mb-3",
            children: ["This computer terminal is accessing the counter for the first time. Please enter the ", /*#__PURE__*/_jsx("strong", {
              children: "Security Key"
            }), " or ", /*#__PURE__*/_jsx("strong", {
              children: "6-digit OTP"
            }), " provided by HMS Admin."]
          }), /*#__PURE__*/_jsxs("form", {
            onSubmit: submitDeviceVerification,
            children: [/*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small fw-bold text-dark mb-1",
                children: "Computer / Machine Name"
              }), /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm",
                value: machineName,
                onChange: e => setMachineName(e.target.value),
                required: true
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "btn-group btn-group-sm w-100 mb-2",
                children: [/*#__PURE__*/_jsxs("button", {
                  type: "button",
                  className: `btn ${verifyMethod === 'key' ? 'btn-primary' : 'btn-outline-secondary'}`,
                  onClick: () => setVerifyMethod('key'),
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-key-fill me-1"
                  }), " Admin Security Key"]
                }), /*#__PURE__*/_jsxs("button", {
                  type: "button",
                  className: `btn ${verifyMethod === 'otp' ? 'btn-primary' : 'btn-outline-secondary'}`,
                  onClick: () => setVerifyMethod('otp'),
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-123 me-1"
                  }), " 6-Digit Counter OTP"]
                })]
              }), /*#__PURE__*/_jsx("input", {
                type: verifyMethod === 'otp' ? 'number' : 'text',
                className: "form-control text-center fw-bold fs-5 letter-spacing-1",
                placeholder: verifyMethod === 'key' ? 'e.g. HMS-XXXXXX-XXX' : 'Enter 6-digit OTP',
                value: secKeyInput,
                onChange: e => setSecKeyInput(e.target.value),
                required: true
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "submit",
              className: "btn btn-success-custom w-100 py-2.5 mt-3 fw-bold",
              disabled: isVerifying,
              children: isVerifying ? /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-hourglass-split me-1"
                }), " Verifying Terminal..."]
              }) : /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-shield-check me-1"
                }), " Verify Machine & Unlock Counter"]
              })
            })]
          })]
        })]
      })
    }), /*#__PURE__*/_jsx("header", {
      className: "navbar-store",
      children: /*#__PURE__*/_jsxs("div", {
        className: "container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center gap-3",
          children: [/*#__PURE__*/_jsx("div", {
            className: "bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center",
            style: {
              width: 40,
              height: 40
            },
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-capsule fs-5"
            })
          }), /*#__PURE__*/_jsxs("div", {
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("h6", {
                className: "mb-0 fw-bold text-white fs-5",
                children: activeStore ? activeStore.store_name : 'Medical Store Counter'
              }), activeStore && /*#__PURE__*/_jsxs("span", {
                className: "badge bg-info-subtle text-info border border-info px-2 py-0.5 rounded-pill",
                style: {
                  fontSize: '0.65rem'
                },
                children: ["/", activeStore.store_slug || activeStore.store_code]
              })]
            }), /*#__PURE__*/_jsxs("small", {
              className: "text-slate-400",
              style: {
                color: '#94a3b8'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-geo-alt me-1"
              }), activeStore ? `${activeStore.building_name || 'Main Building'} • ${activeStore.floor_no || 'Ground Floor'}` : 'Counter']
            })]
          })]
        }), activeStore && /*#__PURE__*/_jsxs("div", {
          className: "d-none d-md-flex align-items-center gap-2",
          children: [activeStore.abdm_hfr_id && /*#__PURE__*/_jsxs("span", {
            className: "badge bg-success-subtle text-success border border-success px-2 py-1",
            title: "ABDM Health Facility Registry",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-shield-fill-check me-1"
            }), " HFR: ", activeStore.abdm_hfr_id]
          }), /*#__PURE__*/_jsxs("span", {
            className: "badge-license",
            title: "Retail Allopathic Drug License",
            children: ["DL 20B: ", activeStore.drug_license_no_20b || 'N/A']
          }), /*#__PURE__*/_jsxs("span", {
            className: "badge-license",
            title: "Schedule C/C1 Drug License",
            children: ["DL 21B: ", activeStore.drug_license_no_21b || 'N/A']
          }), /*#__PURE__*/_jsxs("span", {
            className: "badge-gst",
            title: "GSTIN",
            children: ["GSTIN: ", activeStore.gstin || 'N/A']
          }), isDeviceAuthorized && /*#__PURE__*/_jsxs("span", {
            className: "badge bg-success text-white rounded-pill px-2.5 py-1",
            style: {
              fontSize: '0.7rem'
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-shield-check me-1"
            }), " Terminal Verified"]
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "d-flex align-items-center",
          children: /*#__PURE__*/_jsxs("button", {
            type: "button",
            className: "btn btn-sm text-white d-flex align-items-center gap-2 px-3 py-1.5 rounded-pill shadow-sm",
            style: {
              background: 'linear-gradient(135deg, rgba(14, 165, 233, 0.25), rgba(99, 102, 241, 0.25))',
              border: '1px solid rgba(56, 189, 248, 0.5)',
              backdropFilter: 'blur(4px)'
            },
            onClick: () => {
              setSelectedUserToSwitch(activeOperator || storeUsers[0] || null);
              setOperatorPinInput('');
              setPinError('');
              setShowOperatorModal(true);
            },
            title: "Active Medical Store Operator / Cashier (Click to switch)",
            children: [/*#__PURE__*/_jsx("div", {
              className: "rounded-circle bg-info text-dark d-flex align-items-center justify-content-center fw-bold",
              style: {
                width: 26,
                height: 26,
                fontSize: '0.75rem'
              },
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-person-badge-fill"
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "text-start lh-1",
              style: {
                fontSize: '0.78rem'
              },
              children: [/*#__PURE__*/_jsx("div", {
                className: "fw-bold text-white text-truncate",
                style: {
                  maxWidth: 130
                },
                children: activeOperator ? activeOperator.full_name : 'Select Staff'
              }), /*#__PURE__*/_jsx("span", {
                className: "badge p-0 text-cyan-300",
                style: {
                  fontSize: '0.65rem',
                  color: '#7dd3fc'
                },
                children: activeOperator ? activeOperator.user_role.toUpperCase() : 'COUNTER'
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "badge bg-dark bg-opacity-50 text-white-50 px-1.5 py-0.5 rounded small",
              style: {
                fontSize: '0.65rem'
              },
              children: ["Switch ", /*#__PURE__*/_jsx("i", {
                className: "bi bi-key-fill text-warning ms-0.5"
              })]
            })]
          })
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center gap-2",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill border border-secondary shadow-xs text-white",
            style: {
              background: '#1e293b',
              fontSize: '0.78rem'
            },
            title: "Active counter store is locked for billing integrity",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-hospital text-info"
            }), /*#__PURE__*/_jsx("span", {
              className: "fw-bold",
              children: activeStore ? activeStore.store_name : 'Counter'
            }), /*#__PURE__*/_jsxs("span", {
              className: "badge bg-secondary-subtle text-secondary py-0.5 px-1.5 rounded",
              style: {
                fontSize: '0.62rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-lock-fill me-0.5 text-warning"
              }), " LOCKED"]
            })]
          }), /*#__PURE__*/_jsxs("button", {
            type: "button",
            className: "btn btn-sm text-white rounded-pill px-2.5 py-1 d-flex align-items-center gap-1.5 shadow-sm",
            style: {
              background: 'rgba(14, 165, 233, 0.2)',
              border: '1px solid #0ea5e9'
            },
            onClick: () => setShowQrModal(true),
            title: "Scan QR Code to open Mobile Rack Collector & Stock Auditor on phone",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-qr-code text-cyan-300"
            }), /*#__PURE__*/_jsx("span", {
              className: "d-none d-lg-inline small fw-semibold",
              children: "Mobile App QR"
            })]
          }), /*#__PURE__*/_jsxs("button", {
            type: "button",
            className: `btn btn-sm rounded-pill px-2.5 py-1 d-flex align-items-center gap-1.5 ${isMobileMode ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`,
            onClick: () => setIsMobileMode(!isMobileMode),
            title: "Toggle Mobile Companion View",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-phone"
            }), /*#__PURE__*/_jsx("span", {
              className: "small fw-semibold",
              children: isMobileMode ? 'Desktop' : 'Mobile'
            })]
          }), /*#__PURE__*/_jsx("a", {
            href: "/app",
            className: "btn btn-sm btn-outline-light",
            title: "Return to App Hub",
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-grid-fill"
            })
          })]
        })]
      })
    }), notification && /*#__PURE__*/_jsxs("div", {
      className: `position-fixed shadow-lg alert alert-${notification.type === 'error' ? 'danger' : notification.type === 'warning' ? 'warning' : 'success'} d-flex align-items-center gap-2 alert-dismissible fade show m-0 py-2.5 px-3`,
      style: {
        top: '1.25rem',
        right: '1.5rem',
        zIndex: 999999,
        minWidth: '320px',
        maxWidth: '480px',
        borderWidth: '1.5px',
        borderRadius: '10px'
      },
      role: "alert",
      children: [/*#__PURE__*/_jsx("i", {
        className: `bi bi-${notification.type === 'error' ? 'exclamation-triangle-fill' : notification.type === 'warning' ? 'exclamation-circle-fill' : 'check-circle-fill'} fs-5 me-1`
      }), /*#__PURE__*/_jsx("div", {
        className: "flex-grow-1 fw-semibold small pe-2",
        children: notification.msg
      }), /*#__PURE__*/_jsx("button", {
        type: "button",
        className: "btn-close p-2",
        onClick: () => setNotification(null)
      })]
    }), /*#__PURE__*/_jsxs("nav", {
      className: "nav-tabs-custom d-flex flex-wrap",
      children: [/*#__PURE__*/_jsxs("button", {
        className: `nav-link ${activeTab === 'pos' ? 'active' : ''}`,
        onClick: () => setActiveTab('pos'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-cart3 me-1"
        }), " POS Counter Desk"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `nav-link ${activeTab === 'invoices' ? 'active' : ''}`,
        onClick: () => setActiveTab('invoices'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-receipt me-1"
        }), " All Invoices & Sales"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `nav-link ${activeTab === 'inventory' ? 'active' : ''}`,
        onClick: () => setActiveTab('inventory'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-boxes me-1"
        }), " Live Stock & Batches"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `nav-link ${activeTab === 'purchase' ? 'active' : ''}`,
        onClick: () => setActiveTab('purchase'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-box-arrow-in-down me-1"
        }), " Purchases & Stock Import"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `nav-link ${activeTab === 'transfer' ? 'active' : ''}`,
        onClick: () => setActiveTab('transfer'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-arrow-left-right me-1"
        }), " Inter-Store Indents"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `nav-link ${activeTab === 'ledgers' ? 'active' : ''}`,
        onClick: () => setActiveTab('ledgers'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-journal-text me-1"
        }), " Ledgers, Daybook & GST"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `nav-link ${activeTab === 'staff' ? 'active' : ''}`,
        onClick: () => setActiveTab('staff'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-people-fill me-1"
        }), " Staff & Roles"]
      })]
    }), isMobileMode ? /*#__PURE__*/_jsx(MobileAppView, {
      store: activeStore,
      stores: stores,
      notify: notify,
      activeOperator: activeOperator,
      onSwitchOperator: () => setShowOperatorModal(true),
      onExitMobile: () => setIsMobileMode(false),
      triggerPwaInstall: triggerPwaInstall
    }) : /*#__PURE__*/_jsxs("main", {
      className: "container-fluid py-3",
      children: [activeTab === 'pos' && /*#__PURE__*/_jsx(PosCounter, {
        store: activeStore,
        notify: notify,
        activeOperator: activeOperator,
        onSwitchOperator: () => setShowOperatorModal(true),
        initialEditInvoice: editInvoiceForPos,
        onClearEditInvoice: () => setEditInvoiceForPos(null)
      }), activeTab === 'invoices' && /*#__PURE__*/_jsx(InvoicesView, {
        store: activeStore,
        notify: notify,
        activeOperator: activeOperator,
        onEditInPos: invData => {
          setEditInvoiceForPos(invData);
          setActiveTab('pos');
        }
      }), activeTab === 'inventory' && /*#__PURE__*/_jsx(InventoryView, {
        store: activeStore,
        notify: notify,
        activeOperator: activeOperator
      }), activeTab === 'purchase' && /*#__PURE__*/_jsx(PurchaseView, {
        store: activeStore,
        notify: notify,
        activeOperator: activeOperator
      }), activeTab === 'transfer' && /*#__PURE__*/_jsx(TransferView, {
        store: activeStore,
        stores: stores,
        notify: notify,
        activeOperator: activeOperator
      }), activeTab === 'ledgers' && /*#__PURE__*/_jsx(LedgersView, {
        store: activeStore,
        notify: notify,
        activeOperator: activeOperator
      }), activeTab === 'staff' && /*#__PURE__*/_jsx(StaffManagementView, {
        store: activeStore,
        notify: notify,
        activeOperator: activeOperator,
        onOperatorUpdated: () => loadStoreUsers(activeStore?.store_id),
        onSelectOperator: u => {
          setSelectedUserToSwitch(u);
          setOperatorPinInput('');
          setPinError('');
          setShowOperatorModal(true);
        }
      })]
    }), showQrModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.85)',
        zIndex: 10700
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-dialog-centered",
        style: {
          maxWidth: 520
        },
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-2xl rounded-4 overflow-hidden",
          style: {
            background: '#0f172a',
            color: '#f8fafc',
            border: '1px solid #334155'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header border-bottom border-secondary py-3 px-4",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "p-2 rounded-3 bg-info bg-opacity-25 text-info",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-qr-code-scan fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title fw-bold text-white mb-0",
                  children: "Mobile App QR & Installation"
                }), /*#__PURE__*/_jsxs("small", {
                  className: "text-slate-400",
                  style: {
                    color: '#94a3b8'
                  },
                  children: [activeStore?.store_name, " • Rack Collector & Stock Auditor"]
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowQrModal(false)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-body p-4 text-center",
            children: [serverNetworkInfo && serverNetworkInfo.detected_ips && serverNetworkInfo.detected_ips.length > 1 && /*#__PURE__*/_jsxs("div", {
              className: "mb-3 text-start",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label text-slate-300 small fw-bold text-uppercase",
                style: {
                  color: '#cbd5e1'
                },
                children: "Hospital WiFi / LAN IP"
              }), /*#__PURE__*/_jsxs("select", {
                className: "form-select form-select-sm bg-dark text-white border-secondary",
                value: selectedLanIp,
                onChange: e => setSelectedLanIp(e.target.value),
                children: [serverNetworkInfo.detected_ips.map(ip => /*#__PURE__*/_jsxs("option", {
                  value: ip,
                  children: [ip, " (WiFi / LAN Network)"]
                }, ip)), /*#__PURE__*/_jsxs("option", {
                  value: window.location.hostname,
                  children: [window.location.hostname, " (Current Browser Host)"]
                })]
              })]
            }), (() => {
              const portStr = serverNetworkInfo?.port ? ':' + serverNetworkInfo.port : window.location.port ? ':' + window.location.port : '';
              const hostIp = selectedLanIp || serverNetworkInfo?.primary_ip || window.location.hostname;
              const targetUrl = `http://${hostIp}${portStr}/MedicalStore/${activeStore?.store_slug || 'storea'}?mode=mobile`;
              const qrSrc = `https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=8&data=${encodeURIComponent(targetUrl)}`;
              return /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "p-3 bg-white rounded-4 shadow d-inline-block mb-3",
                  children: /*#__PURE__*/_jsx("img", {
                    src: qrSrc,
                    alt: "Scan to open Mobile Pharmacy App",
                    style: {
                      width: 220,
                      height: 220,
                      display: 'block'
                    }
                  })
                }), /*#__PURE__*/_jsxs("div", {
                  className: "p-2.5 rounded-3 mb-3 text-start small border border-secondary",
                  style: {
                    background: '#1e293b'
                  },
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "text-slate-400 small mb-1",
                    style: {
                      color: '#94a3b8'
                    },
                    children: "Mobile Application URL:"
                  }), /*#__PURE__*/_jsx("div", {
                    className: "fw-mono text-cyan-300 text-truncate fw-bold",
                    style: {
                      color: '#67e8f9'
                    },
                    children: targetUrl
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2 text-start small mb-3",
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "col-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "p-2 rounded-3 border border-secondary text-center h-100",
                      style: {
                        background: '#1e293b'
                      },
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-info mb-1",
                        children: "1. Scan QR"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-slate-400",
                        style: {
                          fontSize: '0.72rem',
                          color: '#94a3b8'
                        },
                        children: "Open camera app on your smartphone"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "p-2 rounded-3 border border-secondary text-center h-100",
                      style: {
                        background: '#1e293b'
                      },
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-info mb-1",
                        children: "2. Rack Drafts"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-slate-400",
                        style: {
                          fontSize: '0.72rem',
                          color: '#94a3b8'
                        },
                        children: "Collect items & audit shelf stock"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "p-2 rounded-3 border border-secondary text-center h-100",
                      style: {
                        background: '#1e293b'
                      },
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-success mb-1",
                        children: "3. Install App"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-slate-400",
                        style: {
                          fontSize: '0.72rem',
                          color: '#94a3b8'
                        },
                        children: "Tap \"Add to Home Screen\" to install"
                      })]
                    })
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex flex-wrap align-items-center justify-content-center gap-2",
                  children: [/*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-sm btn-outline-info d-flex align-items-center gap-1.5",
                    onClick: () => {
                      navigator.clipboard.writeText(targetUrl);
                      notify('Mobile app URL copied to clipboard!', 'success');
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-copy"
                    }), " Copy Link"]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-sm btn-outline-success d-flex align-items-center gap-1.5",
                    onClick: triggerPwaInstall,
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-phone-fill"
                    }), " 📲 Install App"]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-sm btn-primary d-flex align-items-center gap-1.5 fw-bold",
                    onClick: () => {
                      setIsMobileMode(true);
                      setShowQrModal(false);
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-arrow-right-circle"
                    }), " Open Mobile Mode"]
                  })]
                })]
              });
            })()]
          })]
        })
      })
    }), showOperatorModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)',
        zIndex: 10600
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-dialog-centered",
        style: {
          maxWidth: 500
        },
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-2xl rounded-4 overflow-hidden",
          style: {
            background: '#0f172a',
            color: '#f8fafc',
            border: '1px solid #334155'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header border-bottom border-secondary py-3 px-4",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "p-2 rounded-3 bg-primary bg-opacity-25 text-primary",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-person-badge-fill fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title fw-bold text-white mb-0",
                  children: "Switch Store Operator / Auditor"
                }), /*#__PURE__*/_jsxs("small", {
                  className: "text-slate-400",
                  style: {
                    color: '#94a3b8'
                  },
                  children: [activeStore?.store_name, " • Counter Session"]
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowOperatorModal(false)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-body p-4",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label text-slate-300 small fw-bold text-uppercase",
              style: {
                color: '#cbd5e1'
              },
              children: "1. Select Staff Member"
            }), /*#__PURE__*/_jsx("div", {
              className: "d-flex flex-column gap-2 mb-3",
              style: {
                maxHeight: 220,
                overflowY: 'auto'
              },
              children: storeUsers.map(u => {
                const isSelected = selectedUserToSwitch?.user_id == u.user_id;
                const isCurrent = activeOperator?.user_id == u.user_id;
                return /*#__PURE__*/_jsxs("div", {
                  onClick: () => {
                    setSelectedUserToSwitch(u);
                    setPinError('');
                    setOperatorPinInput('');
                  },
                  className: `p-2.5 rounded-3 d-flex align-items-center justify-content-between cursor-pointer transition-all ${isSelected ? 'border border-primary' : 'border border-secondary'}`,
                  style: {
                    background: isSelected ? 'rgba(14, 165, 233, 0.15)' : '#1e293b',
                    cursor: 'pointer'
                  },
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex align-items-center gap-2.5",
                    children: [/*#__PURE__*/_jsx("div", {
                      className: "rounded-circle bg-dark text-cyan-400 border border-slate-700 d-flex align-items-center justify-content-center fw-bold",
                      style: {
                        width: 34,
                        height: 34,
                        fontSize: '0.85rem'
                      },
                      children: u.full_name ? u.full_name[0] : 'U'
                    }), /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsxs("div", {
                        className: "fw-bold text-white small d-flex align-items-center gap-1.5",
                        children: [u.full_name, isCurrent && /*#__PURE__*/_jsx("span", {
                          className: "badge bg-success",
                          style: {
                            fontSize: '0.6rem'
                          },
                          children: "CURRENT"
                        }), u.is_default == 1 && /*#__PURE__*/_jsx("span", {
                          className: "badge bg-info-subtle text-info",
                          style: {
                            fontSize: '0.6rem'
                          },
                          children: "DEFAULT"
                        })]
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "text-slate-400",
                        style: {
                          fontSize: '0.72rem',
                          color: '#94a3b8'
                        },
                        children: ["@", u.username, " • ", /*#__PURE__*/_jsx("span", {
                          className: "text-capitalize text-info",
                          children: u.user_role.replace('_', ' ')
                        }), u.pharmacist_reg_no && ` • Reg: ${u.pharmacist_reg_no}`]
                      })]
                    })]
                  }), /*#__PURE__*/_jsx("i", {
                    className: `bi bi-${isSelected ? 'check-circle-fill text-primary' : 'circle text-secondary'} fs-5`
                  })]
                }, u.user_id);
              })
            }), selectedUserToSwitch && /*#__PURE__*/_jsxs("div", {
              className: "mt-3 p-3 rounded-3",
              style: {
                background: '#1e293b',
                border: '1px solid #334155'
              },
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center justify-content-between mb-2",
                children: [/*#__PURE__*/_jsxs("label", {
                  className: "form-label text-slate-300 small fw-bold text-uppercase mb-0",
                  style: {
                    color: '#cbd5e1'
                  },
                  children: ["2. Enter 4-Digit Security PIN for ", selectedUserToSwitch.full_name]
                }), /*#__PURE__*/_jsx("span", {
                  className: "badge bg-dark text-slate-400 small",
                  style: {
                    fontSize: '0.65rem'
                  },
                  children: "Default: 1234/1111"
                })]
              }), /*#__PURE__*/_jsx("input", {
                type: "password",
                maxLength: "6",
                className: "form-control form-control-lg text-center fw-bold fs-4 tracking-widest bg-dark text-white border-secondary",
                placeholder: "••••",
                value: operatorPinInput,
                onChange: e => {
                  setOperatorPinInput(e.target.value);
                  setPinError('');
                },
                onKeyDown: e => {
                  if (e.key === 'Enter') handleSwitchOperator();
                },
                autoFocus: true
              }), pinError && /*#__PURE__*/_jsxs("div", {
                className: "text-danger small mt-2 d-flex align-items-center gap-1",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-exclamation-circle-fill"
                }), " ", pinError]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-footer border-top border-secondary py-2 px-4 d-flex justify-content-between",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsx("div", {
              children: /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-info d-flex align-items-center gap-1.5",
                onClick: () => {
                  setShowOperatorModal(false);
                  setShowQrModal(true);
                },
                title: "Scan QR code to open installable mobile application on smartphone",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-qr-code-scan"
                }), /*#__PURE__*/_jsx("span", {
                  children: "📱 Mobile App QR Code"
                })]
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-2",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-outline-secondary",
                onClick: () => setShowOperatorModal(false),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-primary px-3 fw-bold d-flex align-items-center gap-1.5",
                onClick: handleSwitchOperator,
                disabled: isSwitchingOperator || !selectedUserToSwitch || !operatorPinInput,
                children: isSwitchingOperator ? /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm"
                  }), " Verifying..."]
                }) : /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-box-arrow-in-right"
                  }), " Authenticate & Switch"]
                })
              })]
            })]
          })]
        })
      })
    })]
  });
}

// =========================================================================
// 1. POS BILLING & DISPENSING COUNTER (MULTI-PRINT: A4, A5, 80mm THERMAL)
// =========================================================================
function PosCounter({
  store,
  notify,
  initialEditInvoice,
  onClearEditInvoice,
  activeOperator,
  onSwitchOperator
}) {
  if (!store) {
    return /*#__PURE__*/_jsxs("div", {
      className: "text-center py-5 text-muted",
      children: [/*#__PURE__*/_jsx("div", {
        className: "spinner-border spinner-border-sm text-primary me-2"
      }), "Loading counter desk..."]
    });
  }
  const [patientQuery, setPatientQuery] = useState('');
  const [patientResults, setPatientResults] = useState([]);
  const [selectedPatient, setSelectedPatient] = useState(null);
  const [patientType, setPatientType] = useState('Walk-in'); // Walk-in, OPD, IPD

  const [walkinAbhaId, setWalkinAbhaId] = useState('');
  const [walkinAbhaAddress, setWalkinAbhaAddress] = useState('');
  const [walkinMobile, setWalkinMobile] = useState('');
  const [walkinUhid, setWalkinUhid] = useState('');
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
  const [paymentMode, setPaymentMode] = useState('Cash'); // Cash, UPI, Card, Mixed, IPD_Credit, Staff_Credit
  const [draftBillsList, setDraftBillsList] = useState([]);
  const [showDraftModal, setShowDraftModal] = useState(false);
  const [draftModalTab, setDraftModalTab] = useState('DRAFT'); // 'DRAFT' or 'CONVERTED'
  const [loadedDraft, setLoadedDraft] = useState(null);
  const [creditAccounts, setCreditAccounts] = useState([]);
  const [selectedCreditAccount, setSelectedCreditAccount] = useState(null);
  const loadDraftBills = async statusOverride => {
    if (!store?.store_id) return;
    try {
      const st = statusOverride !== undefined ? statusOverride : 'DRAFT';
      const res = await fetch(`${API_BASE}drafts/list?store_id=${store.store_id}&status=${st}`);
      const data = await res.json();
      if (data.status) {
        setDraftBillsList(data.drafts || []);
      }
    } catch (e) {
      console.error('Error fetching drafts:', e);
    }
  };
  const loadCreditAccountsList = async () => {
    if (!store?.store_id) return;
    try {
      const res = await fetch(`${API_BASE}credit/accounts?store_id=${store.store_id}`);
      const data = await res.json();
      if (data.status) {
        setCreditAccounts(data.accounts || []);
      }
    } catch (e) {
      console.error('Error fetching credit accounts:', e);
    }
  };
  useEffect(() => {
    if (store?.store_id) {
      loadDraftBills();
      loadCreditAccountsList();
      const timer = setInterval(loadDraftBills, 25000);
      return () => clearInterval(timer);
    }
  }, [store?.store_id]);
  const handleLoadDraftBillIntoCart = draft => {
    try {
      const rawItems = typeof draft.items_json === 'string' ? JSON.parse(draft.items_json) : draft.items || draft.items_json;
      if (!Array.isArray(rawItems) || rawItems.length === 0) {
        notify('Draft contains no items.', 'warning');
        return;
      }
      const formattedCart = rawItems.map((it, idx) => {
        const upp = Number(it.units_per_pack) > 0 ? Number(it.units_per_pack) : 1;
        const sellUnit = it.sell_unit || (upp > 1 ? 'Tablet' : 'Unit');
        const q = Math.max(1, Number(it.qty) || 1);

        // Resiliently resolve strip MRP (unit_mrp)
        let unitMrp = Number(it.unit_mrp ?? it.mrp ?? 0);
        if (unitMrp <= 0) {
          if (Number(it.effective_rate) > 0) {
            unitMrp = sellUnit === 'Tablet' ? Number((Number(it.effective_rate) * upp).toFixed(2)) : Number(it.effective_rate);
          } else if (Number(it.line_gross) > 0) {
            unitMrp = sellUnit === 'Tablet' ? Number(it.line_gross) : Number((Number(it.line_gross) / q).toFixed(2));
          }
        }
        const stripQty = sellUnit === 'Strip' ? q : Number(it.strip_qty) || 0;
        const looseQty = sellUnit === 'Tablet' || sellUnit === 'Unit' ? q : Number(it.loose_qty) || 0;
        return {
          ...it,
          cart_item_id: Date.now() + '_' + idx + '_' + Math.random().toString(36).substr(2, 4),
          item_id: it.item_id,
          item_name: it.item_name,
          generic_name: it.generic_name || '',
          unit_pack: it.unit_pack || (upp > 1 ? `Pack: ${upp} Tabs` : 'Unit'),
          units_per_pack: upp,
          drug_schedule: it.drug_schedule || '',
          hsn_code: it.hsn_code || '3004',
          gst_rate: it.gst_rate !== undefined && it.gst_rate !== null ? Number(it.gst_rate) : 12.0,
          batch_id: it.batch_id,
          batch_no: it.batch_no,
          expiry_display: it.expiry_display || (it.expiry_date ? it.expiry_date.substring(0, 7) : ''),
          expiry_date: it.expiry_date || '',
          current_qty: Number(it.current_qty) || 9999,
          unit_mrp: unitMrp,
          mrp: unitMrp,
          sell_unit: sellUnit,
          strip_qty: stripQty,
          loose_qty: looseQty,
          qty: q,
          discount_type: it.discount_type || 'pct',
          discount_val: Number(it.discount_val) || 0
        };
      });
      setCart(formattedCart);
      setLoadedDraft(draft);
      if (draft.patient_name) setPatientQuery(draft.patient_name);
      if (draft.uhid) setWalkinUhid(draft.uhid);
      if (draft.patient_mobile) setWalkinMobile(draft.patient_mobile);
      if (draft.doctor_name) setDoctorName(draft.doctor_name);
      setShowDraftModal(false);
      notify(`Draft [${draft.draft_code}] with ${rawItems.length} items loaded into billing cart!`, 'success');
    } catch (err) {
      notify('Failed to load draft items: ' + err.message, 'error');
    }
  };
  const handleCancelDraft = async draftId => {
    if (!confirm('Discard this draft bill?')) return;
    try {
      const res = await fetch(`${API_BASE}drafts/cancel`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          draft_id: draftId
        })
      });
      const data = await res.json();
      if (data.status) {
        notify('Draft discarded.', 'info');
        loadDraftBills();
      }
    } catch (e) {
      notify('Error cancelling draft: ' + e.message, 'error');
    }
  };
  const [paymentRef, setPaymentRef] = useState('');
  const [splitCash, setSplitCash] = useState('');
  const [splitUpi, setSplitUpi] = useState('');
  const [splitCard, setSplitCard] = useState('');
  const [upiRefNo, setUpiRefNo] = useState('');
  const [bankName, setBankName] = useState('');
  const [cardRefNo, setCardRefNo] = useState('');
  const [lastInvoice, setLastInvoice] = useState(null);
  const [printFormat, setPrintFormat] = useState('thermal'); // 'thermal', 'a5', 'a4'
  const [isProcessing, setIsProcessing] = useState(false);
  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [isPrintOpen, setIsPrintOpen] = useState(false);

  // Patient History & Old Records State (Right-Side Panel)
  const [patientHistory, setPatientHistory] = useState(null);
  const [isLoadingHistory, setIsLoadingHistory] = useState(false);
  const [showHistoryPanel, setShowHistoryPanel] = useState(true);
  const [historyActiveTab, setHistoryActiveTab] = useState('last_given'); // 'last_given', 'all_bills', 'prescriptions', 'summary'
  const [expandedBillId, setExpandedBillId] = useState(null);

  // POS Counter Persistent Draft Auto-Save & Recovery
  const draftKey = `hms_pharmacy_pos_draft_${store?.store_id || 1}`;
  const [draftStatus, setDraftStatus] = useState('idle'); // 'idle', 'saved', 'restored'
  const [draftSavedAt, setDraftSavedAt] = useState(null);
  const [isRestoredBannerVisible, setIsRestoredBannerVisible] = useState(false);
  const [editingInvoiceBanner, setEditingInvoiceBanner] = useState(null);
  const isRestoringRef = useRef(false);

  // 1. Handle initialEditInvoice (from Invoices tab "Edit in POS")
  useEffect(() => {
    if (!initialEditInvoice || !initialEditInvoice.sale) return;
    try {
      isRestoringRef.current = true;
      const sale = initialEditInvoice.sale;
      const pt = {
        id: sale.patient_id || 0,
        patient_id: sale.patient_id || 0,
        uhid: sale.uhid || '',
        name: sale.patient_name || 'Walk-in Customer',
        patient_name: sale.patient_name || 'Walk-in Customer',
        mobile: sale.patient_mobile || '',
        patient_mobile: sale.patient_mobile || '',
        patient_type: sale.patient_type || 'Walk-in',
        address: sale.patient_address || '',
        age: sale.age || '',
        gender: sale.gender || ''
      };
      setSelectedPatient(pt);
      setPatientType(sale.patient_type || 'Walk-in');
      setDoctorName(sale.doctor_name || '');
      setDoctorRegNo(sale.doctor_reg_no || '');
      if (sale.payment_mode) setPaymentMode(sale.payment_mode);
      if (sale.bill_discount_type) setWholeDiscountType(sale.bill_discount_type);
      if (sale.bill_discount_val) setWholeDiscountVal(Number(sale.bill_discount_val) || 0);
      if (sale.patient_id || sale.uhid) {
        fetchPatientHistory(sale.patient_id, sale.uhid, sale.patient_mobile);
        setShowHistoryPanel(true);
      }
      const restoredItems = (initialEditInvoice.items || []).filter(it => (it.item_type || 'SALE') === 'SALE').map(it => {
        const unitsPerPack = Math.max(1, Number(it.units_per_pack) || 1);
        const sellUnit = it.sell_unit || 'Strip';
        const qty = Number(it.qty) || 1;
        return {
          item_id: Number(it.item_id),
          item_name: it.item_name || 'Medicine',
          generic_name: it.generic_name || '',
          drug_schedule: it.drug_schedule || '',
          unit_pack: it.unit_pack || '',
          units_per_pack: unitsPerPack,
          batch_id: Number(it.batch_id),
          batch_no: it.batch_no || '',
          expiry_date: it.expiry_date || '',
          qty: qty,
          strip_qty: sellUnit === 'Strip' ? qty : 0,
          loose_qty: sellUnit === 'Tablet' ? qty : 0,
          sell_unit: sellUnit,
          mrp: Number(it.unit_mrp) || Number(it.unit_price) || 0,
          rate: Number(it.unit_price) || 0,
          gst_rate: Number(it.gst_rate) || 0,
          discount_pct: Number(it.discount_pct) || 0,
          current_qty: 999
        };
      });
      setCart(restoredItems);
      setEditingInvoiceBanner(sale.invoice_no);
      setIsRestoredBannerVisible(false);
      notify(`Loaded invoice ${sale.invoice_no} into POS counter for editing.`, 'success');
      setTimeout(() => {
        isRestoringRef.current = false;
      }, 400);
    } catch (e) {
      console.error('Error loading edit invoice:', e);
      isRestoringRef.current = false;
    }
  }, [initialEditInvoice]);

  // 2. Draft Auto-Recovery on Mount (Persistent across computer restart / crash / reload)
  useEffect(() => {
    if (initialEditInvoice) return;
    try {
      const rawDraft = localStorage.getItem(draftKey);
      if (rawDraft) {
        const draft = JSON.parse(rawDraft);
        const hasItems = draft.cart && draft.cart.length > 0;
        const hasPatient = !!draft.selectedPatient;
        const hasReturns = draft.returnCart && draft.returnCart.length > 0;
        if (hasItems || hasPatient || hasReturns) {
          isRestoringRef.current = true;
          if (draft.selectedPatient) {
            setSelectedPatient(draft.selectedPatient);
            if (draft.selectedPatient.patient_id || draft.selectedPatient.uhid) {
              fetchPatientHistory(draft.selectedPatient.patient_id, draft.selectedPatient.uhid, draft.selectedPatient.mobile || draft.selectedPatient.patient_mobile);
              setShowHistoryPanel(true);
            }
          }
          if (draft.patientType) setPatientType(draft.patientType);
          if (draft.doctorName) setDoctorName(draft.doctorName);
          if (draft.doctorRegNo) setDoctorRegNo(draft.doctorRegNo);
          if (draft.walkinAbhaId) setWalkinAbhaId(draft.walkinAbhaId);
          if (draft.walkinAbhaAddress) setWalkinAbhaAddress(draft.walkinAbhaAddress);
          if (hasItems) setCart(draft.cart);
          if (hasReturns) setReturnCart(draft.returnCart);
          if (draft.wholeDiscountType) setWholeDiscountType(draft.wholeDiscountType);
          if (draft.wholeDiscountVal) setWholeDiscountVal(draft.wholeDiscountVal);
          if (draft.paymentMode) setPaymentMode(draft.paymentMode);
          if (draft.splitCash) setSplitCash(draft.splitCash);
          if (draft.splitUpi) setSplitUpi(draft.splitUpi);
          if (draft.splitCard) setSplitCard(draft.splitCard);
          if (draft.editingInvoiceBanner) setEditingInvoiceBanner(draft.editingInvoiceBanner);
          setDraftStatus('restored');
          setDraftSavedAt(draft.timestamp ? new Date(draft.timestamp) : new Date());
          setIsRestoredBannerVisible(true);
          setTimeout(() => {
            isRestoringRef.current = false;
          }, 400);
        }
      }
    } catch (e) {
      console.error('Error recovering draft:', e);
      isRestoringRef.current = false;
    }
  }, [store?.store_id]);

  // 3. Debounced Auto-Save to localStorage on state change
  useEffect(() => {
    if (isRestoringRef.current) return;
    const hasData = cart && cart.length > 0 || selectedPatient || returnCart && returnCart.length > 0 || doctorName && doctorName.trim().length > 0;
    if (!hasData) {
      if (!editingInvoiceBanner) {
        try {
          localStorage.removeItem(draftKey);
        } catch (e) {}
        setDraftStatus('idle');
      }
      return;
    }
    const timer = setTimeout(() => {
      try {
        const draftData = {
          timestamp: Date.now(),
          selectedPatient,
          patientType,
          doctorName,
          doctorRegNo,
          walkinAbhaId,
          walkinAbhaAddress,
          cart,
          returnCart,
          wholeDiscountType,
          wholeDiscountVal,
          paymentMode,
          splitCash,
          splitUpi,
          splitCard,
          editingInvoiceBanner
        };
        localStorage.setItem(draftKey, JSON.stringify(draftData));
        setDraftStatus('saved');
        setDraftSavedAt(new Date());
      } catch (e) {
        console.error('Draft auto-save error:', e);
      }
    }, 300);
    return () => clearTimeout(timer);
  }, [cart, returnCart, selectedPatient, patientType, doctorName, doctorRegNo, walkinAbhaId, walkinAbhaAddress, wholeDiscountType, wholeDiscountVal, paymentMode, splitCash, splitUpi, splitCard, draftKey, editingInvoiceBanner]);
  const clearDraftAndReset = () => {
    try {
      localStorage.removeItem(draftKey);
    } catch (e) {}
    setCart([]);
    setReturnCart([]);
    resetPatient();
    setLoadedDraft(null);
    setDoctorName('');
    setDoctorRegNo('');
    setWholeDiscountVal(0);
    setEditingInvoiceBanner(null);
    if (onClearEditInvoice) onClearEditInvoice();
    setIsRestoredBannerVisible(false);
    setDraftStatus('idle');
    notify('Draft cleared. Counter reset to new blank bill.', 'info');
  };

  // Automatically preload latest completed sale for this store so "Last Bill" is immediately available
  useEffect(() => {
    if (store && store.store_id && !lastInvoice) {
      fetch(API_BASE + `sales/recent?store_id=${store.store_id}&limit=1`).then(r => r.json()).then(d => {
        if (d.status && d.sales && d.sales.length > 0) {
          const latestSaleId = d.sales[0].sale_id;
          fetch(API_BASE + `sales/invoice/${latestSaleId}`).then(ir => ir.json()).then(id => {
            if (id.status) setLastInvoice(id);
          }).catch(() => {});
        }
      }).catch(() => {});
    }
  }, [store?.store_id]);

  // B2C Sales Return & Same-Invoice Exchange State
  const [returnCart, setReturnCart] = useState([]);
  const [showReturnModal, setShowReturnModal] = useState(false);
  const [returnSearchQuery, setReturnSearchQuery] = useState('');
  const [returnSearchResults, setReturnSearchResults] = useState([]);
  const [isSearchingReturn, setIsSearchingReturn] = useState(false);
  const [selectedReturnInvoice, setSelectedReturnInvoice] = useState(null);
  const [returnDraftItems, setReturnDraftItems] = useState({});
  const [refundMode, setRefundMode] = useState('Cash'); // 'Cash', 'UPI'
  const [refundRefNo, setRefundRefNo] = useState('');
  const returnSearchInputRef = useRef(null);
  const openReturnModal = () => {
    setShowReturnModal(true);
    setTimeout(() => {
      if (returnSearchInputRef.current) {
        returnSearchInputRef.current.focus();
        returnSearchInputRef.current.select();
      }
    }, 120);
  };
  const searchReturnInvoices = async (query = returnSearchQuery) => {
    if (!query || query.trim().length < 2) {
      notify('Please enter at least 2 characters to search bills.', 'error');
      return;
    }
    setIsSearchingReturn(true);
    setSelectedReturnInvoice(null);
    try {
      const res = await fetch(API_BASE + `returns/lookup-invoice?store_id=${store.store_id}&q=${encodeURIComponent(query.trim())}`);
      const data = await res.json();
      if (data.status) {
        setReturnSearchResults(data.results || []);
        if (data.results && data.results.length === 1) {
          selectReturnInvoice(data.results[0]);
        } else if (!data.results || data.results.length === 0) {
          notify('No invoices found matching "' + query.trim() + '"', 'error');
        }
      } else {
        notify(data.message || 'Error looking up invoices', 'error');
      }
    } catch (e) {
      notify('Invoice search error: ' + e.message, 'error');
    } finally {
      setIsSearchingReturn(false);
    }
  };
  const selectReturnInvoice = resObj => {
    setSelectedReturnInvoice(resObj);
    const drafts = {};
    if (resObj && resObj.items) {
      resObj.items.forEach(it => {
        if (it.remaining_units > 0) {
          drafts[it.sale_item_id] = {
            return_qty: 0,
            return_unit: it.units_per_pack > 1 && it.remaining_strips > 0 ? 'Strip' : 'Tab',
            condition: 'GOOD',
            reason: 'EXCESS_UNUSED'
          };
        }
      });
    }
    setReturnDraftItems(drafts);
  };
  const updateReturnDraft = (saleItemId, field, val) => {
    setReturnDraftItems(prev => ({
      ...prev,
      [saleItemId]: {
        ...prev[saleItemId],
        [field]: val
      }
    }));
  };
  const addSelectedReturnsToBill = () => {
    if (!selectedReturnInvoice) return;
    const newReturns = [];
    let hasAnyQty = false;
    selectedReturnInvoice.items.forEach(it => {
      const draft = returnDraftItems[it.sale_item_id];
      if (draft && Number(draft.return_qty) > 0) {
        hasAnyQty = true;
        const qty = Number(draft.return_qty);
        const upp = Number(it.units_per_pack) || 1;
        const totalUnits = draft.return_unit === 'Strip' ? qty * upp : qty;
        if (totalUnits > it.remaining_units) {
          notify(`Cannot return more than available (${it.remaining_units} tabs) for ${it.item_name}!`, 'error');
          return;
        }
        const lineTotal = Number((totalUnits * it.effective_unit_price).toFixed(2));
        const itemData = {
          ref_sale_item_id: it.sale_item_id,
          ref_sale_id: selectedReturnInvoice.sale.sale_id,
          ref_invoice_no: selectedReturnInvoice.sale.invoice_no,
          item_id: it.item_id,
          item_name: it.item_name,
          generic_name: it.generic_name,
          batch_id: it.batch_id,
          batch_no: it.batch_no,
          expiry_date: it.expiry_date,
          units_per_pack: upp,
          return_qty: qty,
          return_unit: draft.return_unit,
          return_total_units: totalUnits,
          unit_price: it.effective_unit_price,
          unit_mrp: it.unit_mrp,
          gst_rate: it.gst_rate,
          condition: draft.condition || 'GOOD',
          reason: draft.reason || 'EXCESS_UNUSED',
          line_total: lineTotal
        };
        const existingIdx = returnCart.findIndex(rc => rc.ref_sale_item_id === it.sale_item_id);
        if (existingIdx >= 0) {
          returnCart[existingIdx] = itemData;
        } else {
          newReturns.push(itemData);
        }
      }
    });
    if (!hasAnyQty) {
      notify('Please enter quantity > 0 for at least one item to return.', 'error');
      return;
    }
    setReturnCart([...returnCart, ...newReturns]);

    // If patient context is currently blank, copy patient from original invoice
    if (!selectedPatient && selectedReturnInvoice.sale) {
      const s = selectedReturnInvoice.sale;
      if (s.patient_name || s.uhid) {
        setPatientQuery(s.patient_name + (s.uhid ? ` (${s.uhid})` : ''));
        if (s.doctor_name) setDoctorName(s.doctor_name);
      }
    }
    setShowReturnModal(false);
    notify('Added return medicines to current invoice! Adjustments reflected in totals.', 'success');
  };

  // Keyboard Navigation & Focus Refs
  const medicineSearchRef = useRef(null);
  const patientSearchRef = useRef(null);
  const wholeDiscountRef = useRef(null);
  const [selectedBatchIndex, setSelectedBatchIndex] = useState(0);
  const [selectedPatientIndex, setSelectedPatientIndex] = useState(0);

  // Flatten non-expired available batches for keyboard arrow navigation
  const selectableBatches = [];
  itemResults.forEach(it => {
    if (it.batches && it.batches.length > 0) {
      it.batches.forEach(b => {
        if (!b.is_expired && Number(b.current_qty) > 0) {
          selectableBatches.push({
            item: it,
            batch: b
          });
        }
      });
    }
  });
  useEffect(() => {
    setSelectedBatchIndex(0);
  }, [itemResults]);
  useEffect(() => {
    setSelectedPatientIndex(0);
  }, [patientResults]);
  const focusMedicineSearch = () => {
    if (medicineSearchRef.current) {
      medicineSearchRef.current.focus();
      medicineSearchRef.current.select();
    }
  };

  // Auto-focus on initial mount
  useEffect(() => {
    const timer = setTimeout(() => {
      focusMedicineSearch();
    }, 150);
    return () => clearTimeout(timer);
  }, []);

  // Search patient
  const searchPatients = async val => {
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
  const handlePatientSearchKeyDown = e => {
    if (patientResults.length > 0) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setSelectedPatientIndex(prev => (prev + 1) % patientResults.length);
        return;
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        setSelectedPatientIndex(prev => (prev - 1 + patientResults.length) % patientResults.length);
        return;
      }
      if (e.key === 'Enter') {
        e.preventDefault();
        const chosen = patientResults[selectedPatientIndex >= 0 && selectedPatientIndex < patientResults.length ? selectedPatientIndex : 0];
        if (chosen) {
          selectPatient(chosen);
          setTimeout(() => focusMedicineSearch(), 50);
        }
        return;
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        setPatientResults([]);
        return;
      }
    } else if (e.key === 'Enter') {
      e.preventDefault();
      focusMedicineSearch();
    }
  };
  const handleMedicineSearchKeyDown = e => {
    if (itemResults.length > 0 && selectableBatches.length > 0) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setSelectedBatchIndex(prev => (prev + 1) % selectableBatches.length);
        return;
      }
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        setSelectedBatchIndex(prev => (prev - 1 + selectableBatches.length) % selectableBatches.length);
        return;
      }
      if (e.key === 'Enter') {
        e.preventDefault();
        const chosen = selectableBatches[selectedBatchIndex >= 0 && selectedBatchIndex < selectableBatches.length ? selectedBatchIndex : 0];
        if (chosen) {
          addItemToCart(chosen.item, chosen.batch, 1);
        }
        return;
      }
      if (e.key === 'Escape') {
        e.preventDefault();
        setItemResults([]);
        setItemQuery('');
        return;
      }
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (itemResults.length === 1 && itemResults[0].fefo_batch) {
        addItemToCart(itemResults[0], itemResults[0].fefo_batch, 1);
      }
    }
  };
  const selectPatient = pt => {
    setSelectedPatient(pt);
    setPatientResults([]);
    setPatientQuery(pt.full_name + ' (' + pt.uhid + ')');
    setWalkinAbhaId(pt.abha_id || '');
    setWalkinAbhaAddress(pt.abha_address || '');
    setWalkinMobile(pt.mobile || '');
    setWalkinUhid(pt.uhid || '');
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

    // Fetch patient pharmacy history & old records on the right side
    fetchPatientHistory(pt.patient_id, pt.uhid, pt.mobile);
    setShowHistoryPanel(true);
  };
  const resetPatient = () => {
    setSelectedPatient(null);
    setPatientHistory(null);
    setPatientQuery('');
    setPatientType('Walk-in');
    setDoctorName('');
    setDoctorRegNo('');
    setWalkinAbhaId('');
    setWalkinAbhaAddress('');
    setWalkinMobile('');
    setWalkinUhid('');
    setPrintFormat('thermal');
  };
  const viewAbdmBundle = async saleId => {
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
    const encType = selectedPatient.has_active_ipd ? 'ipd' : selectedPatient.has_active_opd ? 'opd' : null;
    const encId = selectedPatient.has_active_ipd ? selectedPatient.active_ipd.ipd_id : selectedPatient.has_active_opd ? selectedPatient.active_opd.opd_id : null;
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
  const searchItems = async val => {
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
  const getItemLineInfo = it => {
    const upp = Number(it.units_per_pack) > 0 ? Number(it.units_per_pack) : 1;
    const stripMrp = Number(it.unit_mrp) || 0;
    const perUnitMrp = upp > 0 ? stripMrp / upp : stripMrp;
    const sellUnit = it.sell_unit || (upp > 1 ? 'Strip' : 'Unit');
    let totalUnits = 0;
    let lineGross = 0;
    let unitLabel = '';
    if (upp <= 1) {
      totalUnits = Math.max(1, Number(it.qty) || 1);
      lineGross = totalUnits * stripMrp;
      unitLabel = it.unit_pack || 'Unit';
    } else if (sellUnit === 'Tablet' || sellUnit === 'Unit') {
      totalUnits = Math.max(1, Number(it.loose_qty ?? it.qty) || 1);
      lineGross = totalUnits * perUnitMrp;
      unitLabel = 'Tablet';
    } else if (sellUnit === 'Combo') {
      const sq = Math.max(0, Number(it.strip_qty) || 0);
      const lq = Math.max(0, Number(it.loose_qty) || 0);
      totalUnits = sq * upp + lq;
      if (totalUnits <= 0) totalUnits = 1;
      lineGross = sq * stripMrp + lq * perUnitMrp;
      unitLabel = 'Combo';
    } else {
      // 'Strip'
      const sq = Math.max(1, Number(it.strip_qty ?? it.qty) || 1);
      totalUnits = sq * upp;
      lineGross = sq * stripMrp;
      unitLabel = 'Strip';
    }
    lineGross = Number(lineGross.toFixed(2));
    const effectiveUnitPrice = sellUnit === 'Tablet' || sellUnit === 'Unit' ? Number(perUnitMrp.toFixed(2)) : stripMrp;
    return {
      upp,
      stripMrp,
      perUnitMrp: Number(perUnitMrp.toFixed(2)),
      sellUnit,
      totalUnits,
      lineGross,
      unitLabel,
      effectiveUnitPrice
    };
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
    const upp = Number(item.units_per_pack) > 0 ? Number(item.units_per_pack) : 1;
    const existingIdx = cart.findIndex(c => c.item_id === item.item_id && c.batch_id === batch.batch_id);
    const targetIdx = existingIdx >= 0 ? existingIdx : cart.length;
    if (existingIdx >= 0) {
      const updated = [...cart];
      if (updated[existingIdx].sell_unit === 'Tablet') {
        updated[existingIdx].loose_qty = (Number(updated[existingIdx].loose_qty) || 0) + qty;
        updated[existingIdx].qty = updated[existingIdx].loose_qty;
      } else {
        updated[existingIdx].strip_qty = (Number(updated[existingIdx].strip_qty) || 0) + qty;
        updated[existingIdx].qty = updated[existingIdx].strip_qty;
      }
      setCart(updated);
    } else {
      setCart([...cart, {
        item_id: item.item_id,
        item_name: item.item_name,
        generic_name: item.generic_name,
        unit_pack: item.unit_pack,
        units_per_pack: upp,
        drug_schedule: item.drug_schedule,
        hsn_code: item.hsn_code || '3004',
        gst_rate: batch.gst_rate || 12.0,
        batch_id: batch.batch_id,
        batch_no: batch.batch_no,
        expiry_display: batch.expiry_display,
        expiry_date: batch.expiry_date,
        current_qty: batch.current_qty,
        unit_mrp: batch.mrp,
        sell_unit: upp > 1 ? 'Tablet' : 'Unit',
        strip_qty: 0,
        loose_qty: qty,
        discount_type: 'pct',
        discount_val: 0,
        qty: qty
      }]);
    }
    setItemResults([]);
    setItemQuery('');
    setSelectedBatchIndex(0);

    // Auto-suggest format based on item count
    if (cart.length + 1 > 5 && printFormat === 'thermal') {
      setPrintFormat('a5');
    }

    // Auto-focus quantity input of the newly added/updated cart line (default to loose)
    setTimeout(() => {
      const row = existingIdx >= 0 ? cart[existingIdx] : null;
      const sellUnit = row ? row.sell_unit : upp > 1 ? 'Tablet' : 'Unit';
      let el = null;
      if (sellUnit === 'Strip') {
        el = document.getElementById(`cart-qty-strip-${targetIdx}`);
      } else {
        el = document.getElementById(`cart-qty-loose-${targetIdx}`) || document.getElementById(`cart-qty-strip-${targetIdx}`);
      }
      if (el) {
        el.focus();
        el.select();
      }
    }, 50);
  };
  const getItemDiscount = it => {
    const info = getItemLineInfo(it);
    const lineGross = info.lineGross;
    const val = Number(it.discount_val) || 0;
    if (val <= 0) return 0;
    if (it.discount_type === 'val') {
      return Math.min(lineGross, val);
    } else {
      const pct = Math.min(100, Math.max(0, val));
      return Number((lineGross * pct / 100).toFixed(2));
    }
  };
  const toggleItemDiscType = idx => {
    const updated = [...cart];
    const it = updated[idx];
    const info = getItemLineInfo(it);
    const lineGross = info.lineGross;
    const currentDisc = getItemDiscount(it);
    if (it.discount_type === 'val') {
      it.discount_type = 'pct';
      it.discount_val = lineGross > 0 ? Number((currentDisc / lineGross * 100).toFixed(1)) : 0;
    } else {
      it.discount_type = 'val';
      it.discount_val = Math.round(currentDisc);
    }
    setCart(updated);
  };
  const switchSellUnit = (idx, newUnit) => {
    const updated = [...cart];
    const it = updated[idx];
    const upp = it.units_per_pack || 1;
    it.sell_unit = newUnit;
    if (newUnit === 'Tablet') {
      if (!it.loose_qty || Number(it.loose_qty) <= 0) {
        it.loose_qty = Number(it.strip_qty) > 0 ? Number(it.strip_qty) * upp : it.qty || 1;
      }
      it.qty = it.loose_qty;
      setCart(updated);
      setTimeout(() => {
        const el = document.getElementById(`cart-qty-loose-${idx}`);
        if (el) {
          el.focus();
          el.select();
        }
      }, 30);
    } else if (newUnit === 'Strip') {
      if (!it.strip_qty || Number(it.strip_qty) <= 0) {
        it.strip_qty = Number(it.loose_qty) > 0 ? Math.max(1, Math.round(Number(it.loose_qty) / upp)) : 1;
      }
      it.qty = it.strip_qty;
      setCart(updated);
      setTimeout(() => {
        const el = document.getElementById(`cart-qty-strip-${idx}`);
        if (el) {
          el.focus();
          el.select();
        }
      }, 30);
    } else if (newUnit === 'Combo') {
      if (it.strip_qty === undefined || Number(it.strip_qty) <= 0) it.strip_qty = 1;
      if (it.loose_qty === undefined) it.loose_qty = 0;
      setCart(updated);
      setTimeout(() => {
        const el = document.getElementById(`cart-qty-strip-${idx}`);
        if (el) {
          el.focus();
          el.select();
        }
      }, 30);
    }
  };
  const updateCartItem = (idx, field, val) => {
    const updated = [...cart];
    updated[idx][field] = val;
    setCart(updated);
  };
  const removeCartItem = idx => {
    setCart(cart.filter((_, i) => i !== idx));
  };
  const fetchPatientHistory = async (patientId, uhid, mobile) => {
    if (!patientId && !uhid && !mobile) {
      setPatientHistory(null);
      return;
    }
    setIsLoadingHistory(true);
    try {
      const p = new URLSearchParams();
      if (patientId) p.append('patient_id', patientId);
      if (uhid) p.append('uhid', uhid);
      if (mobile) p.append('mobile', mobile);
      if (store?.store_id) p.append('store_id', store.store_id);
      const res = await fetch(API_BASE + `patient/history?${p.toString()}`);
      const data = await res.json();
      if (data.status) {
        setPatientHistory(data);
        if (data.invoices && data.invoices.length > 0) {
          setExpandedBillId(data.invoices[0].sale.sale_id);
        }
      } else {
        setPatientHistory(null);
      }
    } catch (e) {
      console.error('Error fetching patient history:', e);
      setPatientHistory(null);
    } finally {
      setIsLoadingHistory(false);
    }
  };
  const reDispenseMedicine = async (itemObj, defaultQty = 1) => {
    try {
      const searchTerm = itemObj.item_name || itemObj.med_name;
      if (!searchTerm) return;
      notify(`Finding current stock for "${searchTerm}"...`, 'info');
      const res = await fetch(API_BASE + `items/search?store_id=${store.store_id}&q=${encodeURIComponent(searchTerm)}`);
      const data = await res.json();
      if (data.status && data.items && data.items.length > 0) {
        let matched = null;
        if (itemObj.item_id) {
          matched = data.items.find(it => Number(it.item_id) === Number(itemObj.item_id));
        }
        if (!matched) matched = data.items[0];
        if (matched && matched.fefo_batch) {
          const qtyToAdd = defaultQty || (itemObj.qty ? Number(itemObj.qty) : 1);
          addItemToCart(matched, matched.fefo_batch, qtyToAdd);
          notify(`Added "${matched.item_name}" (Batch ${matched.fefo_batch.batch_no}) to cart!`, 'success');
        } else if (matched) {
          notify(`"${matched.item_name}" is currently OUT OF STOCK in this counter!`, 'error');
        }
      } else {
        notify(`Medicine "${searchTerm}" not found in current inventory.`, 'error');
      }
    } catch (e) {
      notify('Failed to add medicine: ' + e.message, 'error');
    }
  };
  const initiateReturnFromHistory = (saleObj, itemObj) => {
    if (!saleObj || !itemObj) return;
    const inv = patientHistory?.invoices?.find(inv => Number(inv.sale.sale_id) === Number(saleObj.sale_id));
    if (inv) {
      selectReturnInvoice(inv);
      const upp = Number(itemObj.units_per_pack) || 1;
      const defUnit = upp > 1 && itemObj.remaining_strips > 0 ? 'Strip' : 'Tab';
      const defQty = defUnit === 'Strip' ? Math.max(1, itemObj.remaining_strips) : Math.max(1, itemObj.remaining_units);
      setReturnDraftItems(prev => ({
        ...prev,
        [itemObj.sale_item_id]: {
          return_qty: defQty,
          return_unit: defUnit,
          condition: 'GOOD',
          reason: 'EXCESS_UNUSED'
        }
      }));
      openReturnModal();
      notify(`Loaded bill ${saleObj.invoice_no} for return of ${itemObj.item_name}`, 'info');
    } else {
      setReturnSearchQuery(saleObj.invoice_no);
      searchReturnInvoices(saleObj.invoice_no);
      openReturnModal();
    }
  };
  const viewPastBill = saleId => {
    fetch(API_BASE + `sales/invoice/${saleId}`).then(r => r.json()).then(data => {
      if (data.status) {
        setLastInvoice(data);
        setIsPrintOpen(true);
      } else {
        notify('Unable to load invoice', 'error');
      }
    }).catch(err => notify('Error loading bill: ' + err.message, 'error'));
  };

  // Calculate totals with whole bill discount and return items
  const grossTotal = cart.reduce((acc, it) => acc + getItemLineInfo(it).lineGross, 0);
  const itemDiscountTotal = cart.reduce((acc, it) => acc + getItemDiscount(it), 0);
  const balanceAfterItemDisc = Math.max(0, grossTotal - itemDiscountTotal);
  let wholeDiscountAmt = 0;
  const numWholeDisc = Number(wholeDiscountVal) || 0;
  if (numWholeDisc > 0) {
    if (wholeDiscountType === 'val') {
      wholeDiscountAmt = Math.min(balanceAfterItemDisc, numWholeDisc);
    } else {
      const pct = Math.min(100, Math.max(0, numWholeDisc));
      wholeDiscountAmt = balanceAfterItemDisc * pct / 100;
    }
  }
  const discountTotal = itemDiscountTotal + wholeDiscountAmt;
  const netSalesBeforeReturn = Math.max(0, grossTotal - discountTotal);
  const returnTotal = returnCart.reduce((acc, it) => acc + (Number(it.line_total) || 0), 0);
  const netDifference = netSalesBeforeReturn - returnTotal;
  let netPayable = 0;
  let refundPayable = 0;
  let roundOff = '0.00';
  if (netDifference >= 0) {
    netPayable = Math.round(netDifference);
    roundOff = (netPayable - netDifference).toFixed(2);
    refundPayable = 0;
  } else {
    refundPayable = Math.round(Math.abs(netDifference));
    roundOff = (refundPayable - Math.abs(netDifference)).toFixed(2);
    netPayable = 0;
  }
  const splitCashNum = Number(splitCash) || 0;
  const splitUpiNum = Number(splitUpi) || 0;
  const splitCardNum = Number(splitCard) || 0;
  const totalSplitAllocated = splitCashNum + splitUpiNum + splitCardNum;
  const splitRemaining = Number((netPayable - totalSplitAllocated).toFixed(2));

  // Save and print sale or return/exchange bill
  const processSale = async () => {
    if (cart.length === 0 && returnCart.length === 0) {
      notify('Cart has no items to bill or return!', 'error');
      return;
    }
    if (paymentMode === 'Staff_Credit' && !selectedCreditAccount) {
      notify('Please select a Staff / Customer credit account to bill.', 'error');
      return;
    }
    if (paymentMode === 'IPD_Credit' && (!selectedPatient || !selectedPatient.has_active_ipd)) {
      notify('IPD Credit mode is only allowed for admitted IPD patients.', 'error');
      return;
    }
    let finalCash = 0;
    let finalUpi = 0;
    let finalCard = 0;
    let finalCredit = 0;
    if (refundPayable > 0) {
      // Patient receives a refund; no positive payment collected
    } else {
      if (paymentMode === 'Cash') {
        finalCash = netPayable;
      } else if (paymentMode === 'UPI') {
        finalUpi = netPayable;
      } else if (paymentMode === 'Card') {
        finalCard = netPayable;
      } else if (paymentMode === 'IPD_Credit') {
        finalCredit = netPayable;
      } else if (paymentMode === 'Staff_Credit') {
        finalCredit = netPayable;
      } else if (paymentMode === 'Mixed') {
        finalCash = splitCashNum;
        finalUpi = splitUpiNum;
        finalCard = splitCardNum;
        if (Math.abs(splitRemaining) > 0.01) {
          notify(`Split payment total (₹${totalSplitAllocated.toFixed(2)}) does not match Net Payable (₹${netPayable})! Difference: ₹${splitRemaining.toFixed(2)}`, 'error');
          return;
        }
      }
    }
    setIsProcessing(true);
    const payload = {
      store_id: store.store_id,
      draft_id: loadedDraft ? loadedDraft.draft_id : null,
      draft_code: loadedDraft ? loadedDraft.draft_code : null,
      patient_type: patientType,
      uhid: selectedPatient ? selectedPatient.uhid : walkinUhid || null,
      patient_id: selectedPatient ? selectedPatient.patient_id : null,
      opd_id: selectedPatient && selectedPatient.active_opd ? selectedPatient.active_opd.opd_id : null,
      ipd_id: selectedPatient && selectedPatient.active_ipd ? selectedPatient.active_ipd.ipd_id : null,
      patient_name: selectedPatient ? selectedPatient.full_name : patientQuery || 'Walk-in Customer',
      patient_mobile: selectedPatient ? selectedPatient.mobile : walkinMobile || '',
      patient_address: selectedPatient ? selectedPatient.address : '',
      abha_id: selectedPatient?.abha_id || walkinAbhaId || null,
      abha_address: selectedPatient?.abha_address || walkinAbhaAddress || null,
      age: selectedPatient ? selectedPatient.age : '',
      gender: selectedPatient ? selectedPatient.gender : '',
      doctor_name: doctorName || selectedPatient?.active_opd?.doctor_name || selectedPatient?.active_ipd?.doctor_name || 'Dr. Consultant',
      doctor_reg_no: doctorRegNo,
      payment_mode: refundPayable > 0 ? refundMode === 'Cash' ? 'Cash' : 'UPI' : paymentMode,
      credit_account_id: paymentMode === 'Staff_Credit' && selectedCreditAccount ? selectedCreditAccount.account_id : null,
      credit_person_name: paymentMode === 'Staff_Credit' && selectedCreditAccount ? selectedCreditAccount.account_name : null,
      cash_paid: finalCash,
      upi_paid: finalUpi,
      card_paid: finalCard,
      credit_amount: finalCredit,
      refund_amount: refundPayable,
      refund_mode: refundPayable > 0 ? refundMode : '',
      refund_ref_no: refundPayable > 0 ? refundRefNo : '',
      payment_reference: paymentRef || upiRefNo || cardRefNo,
      bank_name: bankName,
      upi_ref_no: upiRefNo,
      card_ref_no: cardRefNo,
      whole_discount_type: wholeDiscountType,
      whole_discount_val: Number(wholeDiscountVal) || 0,
      biller_name: activeOperator?.full_name || 'Store Staff',
      operator_name: activeOperator?.full_name || 'Store Staff',
      user_id: activeOperator?.user_id || 1,
      items: cart.map(it => {
        const info = getItemLineInfo(it);
        const lineGross = info.lineGross;
        const lineDisc = getItemDiscount(it);
        const discPct = lineGross > 0 ? Number((lineDisc / lineGross * 100).toFixed(2)) : 0;
        return {
          item_id: it.item_id,
          batch_id: it.batch_id,
          qty: Number(it.qty) || 1,
          sell_unit: info.sellUnit,
          units_per_pack: info.upp,
          strip_qty: Number(it.strip_qty) || 0,
          loose_qty: Number(it.loose_qty) || 0,
          total_units: info.totalUnits,
          effective_rate: info.effectiveUnitPrice,
          line_gross: lineGross,
          discount_type: it.discount_type || 'pct',
          discount_val: Number(it.discount_val) || 0,
          discount_pct: discPct,
          discount_amount: lineDisc
        };
      }),
      return_items: returnCart.map(rc => ({
        ref_sale_item_id: rc.ref_sale_item_id,
        ref_sale_id: rc.ref_sale_id,
        ref_invoice_no: rc.ref_invoice_no,
        item_id: rc.item_id,
        batch_id: rc.batch_id,
        return_qty: rc.return_qty,
        return_unit: rc.return_unit,
        units_per_pack: rc.units_per_pack,
        return_total_units: rc.return_total_units,
        unit_price: rc.unit_price,
        return_amount: rc.line_total,
        condition: rc.condition || 'GOOD',
        reason: rc.reason || 'EXCESS_UNUSED'
      }))
    };
    try {
      const res = await fetch(API_BASE + 'sales/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status) {
        const msg = data.refund_amount > 0 ? `Return Bill ${data.invoice_no} processed! Refund: ₹${data.refund_amount} (${data.refund_mode})` : `Invoice ${data.invoice_no} generated successfully! Net Amount: ₹${data.net_amount}`;
        notify(msg, 'success');
        fetchInvoiceDetails(data.sale_id);
        setIsCheckoutOpen(false);
        setCart([]);
        setReturnCart([]);
        setWholeDiscountVal(0);
        setSplitCash('');
        setSplitUpi('');
        setSplitCard('');
        setUpiRefNo('');
        setBankName('');
        setCardRefNo('');
        setPaymentRef('');
        resetPatient();
        setLoadedDraft(null);
        loadDraftBills('DRAFT');
        try {
          localStorage.removeItem(draftKey);
        } catch (e) {}
        setDraftStatus('idle');
        setEditingInvoiceBanner(null);
        if (onClearEditInvoice) onClearEditInvoice();
      } else {
        notify(data.message || 'Failed to complete transaction.', 'error');
      }
    } catch (e) {
      notify('Sale error: ' + e.message, 'error');
    } finally {
      setIsProcessing(false);
    }
  };
  const fetchInvoiceDetails = async saleId => {
    try {
      const res = await fetch(API_BASE + `sales/invoice/${saleId}`);
      const data = await res.json();
      if (data.status) {
        setLastInvoice(data);
        setIsPrintOpen(true);
      }
    } catch (e) {
      console.error(e);
    }
  };
  const generateThermalReceiptText = inv => {
    if (!inv || !inv.sale) return '';
    const sale = inv.sale;
    const items = inv.items || [];
    const returnItems = inv.return_items || [];
    const LINE_WIDTH = 48; // Standard 80mm thermal receipt line length (Font A: 48 chars)

    const center = str => {
      str = (str || '').trim();
      if (str.length >= LINE_WIDTH) return str.substring(0, LINE_WIDTH);
      const pad = Math.floor((LINE_WIDTH - str.length) / 2);
      return ' '.repeat(pad) + str;
    };
    const padLine = (left, right) => {
      left = left || '';
      right = right || '';
      const space = LINE_WIDTH - left.length - right.length;
      if (space <= 0) return left + ' ' + right;
      return left + ' '.repeat(space) + right;
    };
    const divider = '='.repeat(LINE_WIDTH);
    const dashDivider = '-'.repeat(LINE_WIDTH);
    let lines = [];
    lines.push(divider);
    lines.push(center(sale.store_name || 'HOSPITAL PHARMACY'));
    if (sale.building_name || sale.floor_no) {
      lines.push(center(`${sale.building_name || ''}, ${sale.floor_no || ''}`));
    }
    if (sale.store_address) {
      lines.push(center(sale.store_address));
    }
    if (sale.contact_phone) {
      lines.push(center(`Phone: ${sale.contact_phone}`));
    }
    if (sale.drug_license_no_20b || sale.drug_license_no_21b) {
      lines.push(center(`DL 20B: ${sale.drug_license_no_20b || 'N/A'} | 21B: ${sale.drug_license_no_21b || 'N/A'}`));
    }
    if (sale.gstin) {
      lines.push(center(`GSTIN: ${sale.gstin}${sale.state_code ? ` (State: ${sale.state_code})` : ''}`));
    }
    if (sale.fssai_no) {
      lines.push(center(`FSSAI: ${sale.fssai_no}`));
    }
    if (sale.abdm_hfr_id) {
      lines.push(center(`HFR ID: ${sale.abdm_hfr_id}`));
    }
    lines.push(divider);
    lines.push(center('RETAIL TAX INVOICE'));
    lines.push(padLine(`Bill No: ${sale.invoice_no}`, `Date: ${sale.sale_date ? sale.sale_date.substring(0, 16) : ''}`));
    lines.push(padLine(`Patient: ${sale.patient_name || 'Walk-in'}`, `UHID: ${sale.uhid || 'Walk-in'}`));
    if (sale.patient_mobile) {
      lines.push(padLine(`Mobile : ${sale.patient_mobile}`, `Age/Sex: ${sale.age || 'N/A'}/${sale.gender || 'N/A'}`));
    }
    if (sale.doctor_name) {
      lines.push(`Doctor : ${sale.doctor_name}`);
    }
    if (sale.abha_id || sale.abha_address) {
      lines.push(`ABHA   : ${sale.abha_id || ''} ${sale.abha_address ? `(${sale.abha_address})` : ''}`);
    }
    if (sale.abdm_care_context_ref) {
      lines.push(`ABDM Ref: ${sale.abdm_care_context_ref}`);
    }
    if (sale.ipd_id) {
      lines.push(`IPD #  : ${sale.ipd_id} (Ward: ${sale.ward_name || ''} Bed: ${sale.bed_no || ''})`);
    }
    lines.push(dashDivider);

    // Header: Item (22), Batch (9), Qty (7), Total (10)
    lines.push('Item'.padEnd(22) + 'Batch'.padEnd(9) + 'Qty'.padStart(7) + 'Total'.padStart(10));
    lines.push(dashDivider);
    if (items.length === 0) {
      lines.push(center('- Return / Refund Only -'));
    } else {
      items.forEach(it => {
        const name = (it.item_name || '').substring(0, 21).padEnd(22);
        const batch = (it.batch_no || '').substring(0, 8).padEnd(9);
        const qtyStr = (it.sell_unit === 'Tablet' ? `${it.total_units}T` : it.sell_unit === 'Combo' ? `${it.qty}S+${it.loose_qty}T` : `${it.qty}`).substring(0, 6).padStart(7);
        const amtStr = Number(it.total_amount || 0).toFixed(2).padStart(10);
        lines.push(name + batch + qtyStr + amtStr);
        let sub = `  Exp:${(it.expiry_date || '').substring(0, 7)} HSN:${it.hsn_code || '3004'} GST:${it.gst_rate}%`;
        if (it.sell_unit === 'Tablet') {
          sub += ` @Rs.${it.unit_price}/tab`;
        } else {
          sub += ` @Rs.${it.unit_mrp}`;
        }
        lines.push(sub.substring(0, LINE_WIDTH));
      });
    }
    if (returnItems.length > 0) {
      lines.push(dashDivider);
      lines.push(center('-- RETURNED / EXCHANGED --'));
      if (inv.credit_note) {
        lines.push(center(`Credit Note: ${inv.credit_note.credit_note_no}`));
      }
      returnItems.forEach(it => {
        const name = ('*' + (it.item_name || '')).substring(0, 21).padEnd(22);
        const batch = (it.batch_no || '').substring(0, 8).padEnd(9);
        const qtyStr = ('-' + (it.sell_unit === 'Tablet' ? `${it.total_units}T` : `${it.qty}`)).substring(0, 6).padStart(7);
        const amtStr = ('-' + Math.abs(Number(it.total_amount || 0)).toFixed(2)).padStart(10);
        lines.push(name + batch + qtyStr + amtStr);
      });
    }
    lines.push(dashDivider);
    lines.push(padLine('Gross Total:', `Rs. ${Number(sale.gross_amount || 0).toFixed(2)}`));
    if (Number(sale.discount_amount) > 0) {
      lines.push(padLine('Discounts Allowed:', `-Rs. ${Number(sale.discount_amount).toFixed(2)}`));
    }
    if (Number(sale.return_amount) > 0) {
      lines.push(padLine('Less Return Credit:', `-Rs. ${Number(sale.return_amount).toFixed(2)}`));
    }
    lines.push(padLine('Taxable Amount:', `Rs. ${Number(sale.taxable_amount || 0).toFixed(2)}`));
    lines.push(padLine('CGST Amount:', `Rs. ${Number(sale.cgst_amount || 0).toFixed(2)}`));
    lines.push(padLine('SGST Amount:', `Rs. ${Number(sale.sgst_amount || 0).toFixed(2)}`));
    if (Number(sale.round_off) !== 0) {
      lines.push(padLine('Round Off:', `Rs. ${Number(sale.round_off || 0).toFixed(2)}`));
    }
    lines.push(divider);
    lines.push(padLine('NET PAYABLE:', `Rs. ${Number(sale.net_amount || 0).toFixed(2)}`));
    lines.push(divider);
    if (Number(sale.refund_amount) > 0) {
      lines.push(padLine('REFUND PAID:', `Rs. ${Number(sale.refund_amount).toFixed(2)}`));
      lines.push(padLine('Refund Mode:', `${sale.refund_mode} ${sale.refund_ref_no || ''}`));
      lines.push(dashDivider);
    }
    lines.push(`Settlement: ${sale.payment_mode}`);
    if (Number(sale.cash_paid) > 0) lines.push(`  Cash Paid : Rs. ${Number(sale.cash_paid).toFixed(2)}`);
    if (Number(sale.upi_paid) > 0) lines.push(`  UPI/Bank  : Rs. ${Number(sale.upi_paid).toFixed(2)} ${sale.upi_ref_no ? `[UTR:${sale.upi_ref_no}]` : ''}`);
    if (Number(sale.card_paid) > 0) lines.push(`  Card Paid : Rs. ${Number(sale.card_paid).toFixed(2)} ${sale.card_ref_no ? `[Ref:${sale.card_ref_no}]` : ''}`);
    if (Number(sale.credit_amount) > 0) lines.push(`  IPD Credit: Rs. ${Number(sale.credit_amount).toFixed(2)}`);
    if (sale.is_bank_reconciled == 1) lines.push('  Status    : Bank Statement Audited');else lines.push('  Status    : Bank Statement Pending Audit');
    lines.push(dashDivider);
    if (sale.registered_pharmacist_name) {
      lines.push(`R.Ph: ${sale.registered_pharmacist_name} (${sale.pharmacist_reg_no || 'N/A'})`);
    }
    lines.push(center(sale.terms_conditions || 'Thank you! Get well soon.'));
    lines.push(divider);
    return lines.join('\n');
  };
  const copyThermalReceiptText = () => {
    if (!lastInvoice) return;
    const txt = generateThermalReceiptText(lastInvoice);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(txt).then(() => {
        notify('Thermal receipt text copied to clipboard!', 'success');
      }).catch(() => {
        fallbackCopyText(txt);
      });
    } else {
      fallbackCopyText(txt);
    }
  };
  const fallbackCopyText = txt => {
    const ta = document.createElement('textarea');
    ta.value = txt;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand('copy');
      notify('Thermal receipt text copied to clipboard!', 'success');
    } catch (err) {
      notify('Unable to copy text.', 'error');
    }
    document.body.removeChild(ta);
  };
  const printThermalReceipt = () => {
    if (!lastInvoice) return;
    const txt = generateThermalReceiptText(lastInvoice);
    const win = window.open('', '_blank', 'width=420,height=650');
    if (!win) {
      notify('Pop-up blocked. Please allow pop-ups to print thermal receipt.', 'warning');
      return;
    }
    try {
      win.document.title = 'Thermal Receipt - ' + (lastInvoice.sale ? lastInvoice.sale.invoice_no : '');
      const style = win.document.createElement('style');
      style.textContent = '@page { size: 80mm auto; margin: 0; } html, body { margin: 0; padding: 0; background: #fff; } body { padding: 4mm 2mm; font-family: Courier, monospace; font-size: 11px; line-height: 1.25; color: #000; } pre { margin: 0; font-family: inherit; font-size: inherit; white-space: pre-wrap; word-break: break-all; }';
      win.document.head.appendChild(style);
      const pre = win.document.createElement('pre');
      pre.textContent = txt;
      win.document.body.appendChild(pre);
      setTimeout(() => {
        try {
          win.focus();
          win.print();
          setTimeout(() => {
            try {
              win.close();
            } catch (err) {}
          }, 1000);
        } catch (err) {}
      }, 250);
    } catch (e) {
      console.error('Print thermal error:', e);
    }
  };
  const printPdf = format => {
    if (!lastInvoice || !lastInvoice.sale) return;
    const saleId = lastInvoice.sale.sale_id;
    const url = `/api/v1/medical-store/sales/invoice-pdf/${saleId}?format=${format || 'a4'}`;
    let iframe = document.getElementById('pdf-print-iframe');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'pdf-print-iframe';
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = '0';
      document.body.appendChild(iframe);
    }
    iframe.src = url;
    iframe.onload = function () {
      try {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
      } catch (e) {
        window.open(url, '_blank');
      }
    };
  };
  const downloadPdf = format => {
    if (!lastInvoice || !lastInvoice.sale) return;
    const saleId = lastInvoice.sale.sale_id;
    window.open(`/api/v1/medical-store/sales/invoice-pdf/${saleId}?format=${format || 'a4'}&download=1`, '_blank');
  };

  // Global Keyboard Shortcuts (Desktop Pharmacy Standard)
  useEffect(() => {
    const handleGlobalKeyDown = e => {
      // Dismiss Modals or Drawers on Escape
      if (showFhirModal || showReturnModal || isCheckoutOpen || isPrintOpen) {
        if (e.key === 'Escape') {
          e.preventDefault();
          if (showFhirModal) setShowFhirModal(false);else if (showReturnModal) setShowReturnModal(false);else if (isCheckoutOpen) setIsCheckoutOpen(false);else if (isPrintOpen) setIsPrintOpen(false);
        }
        return;
      }

      // F2: Jump to Medicine Search / Barcode scanner
      if (e.key === 'F2') {
        e.preventDefault();
        focusMedicineSearch();
        return;
      }

      // F4: Jump to Patient Search
      if (e.key === 'F4') {
        e.preventDefault();
        if (patientSearchRef.current) {
          patientSearchRef.current.focus();
          patientSearchRef.current.select();
        }
        return;
      }

      // F6 or Alt+R: Jump to Sales Return & Same-Invoice Exchange Desk
      if (e.key === 'F6' || e.altKey && (e.key === 'r' || e.key === 'R')) {
        e.preventDefault();
        openReturnModal();
        return;
      }

      // F7: Jump to Whole Bill Discount (opens Checkout Drawer if closed)
      if (e.key === 'F7') {
        e.preventDefault();
        setIsCheckoutOpen(true);
        setTimeout(() => {
          if (wholeDiscountRef.current) {
            wholeDiscountRef.current.focus();
            wholeDiscountRef.current.select();
          }
        }, 150);
        return;
      }

      // F8: Open Checkout Drawer or Cycle Payment Mode
      if (e.key === 'F8') {
        e.preventDefault();
        if (!isCheckoutOpen) {
          setIsCheckoutOpen(true);
        } else {
          const modes = ['Cash', 'UPI', 'Card', 'Mixed'];
          if (selectedPatient?.has_active_ipd) modes.push('IPD_Credit');
          const curIdx = modes.indexOf(paymentMode);
          const nextMode = modes[(curIdx + 1) % modes.length];
          setPaymentMode(nextMode);
        }
        return;
      }

      // F9 or Ctrl+Enter or Alt+S: Open Checkout or Complete Sale & Print Bill
      if (e.key === 'F9' || e.ctrlKey && e.key === 'Enter' || e.altKey && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
        if (!isCheckoutOpen) {
          setIsCheckoutOpen(true);
        } else {
          if (!isProcessing && (cart.length > 0 || returnCart.length > 0)) {
            processSale();
          }
        }
        return;
      }
    };
    window.addEventListener('keydown', handleGlobalKeyDown);
    return () => window.removeEventListener('keydown', handleGlobalKeyDown);
  }, [showFhirModal, showReturnModal, isCheckoutOpen, isPrintOpen, paymentMode, selectedPatient, isProcessing, cart, returnCart, splitRemaining, splitCashNum, splitUpiNum, splitCardNum, refundPayable, refundMode]);
  return /*#__PURE__*/_jsxs("div", {
    className: "pos-counter-wrapper position-relative",
    children: [/*#__PURE__*/_jsxs("div", {
      className: "row g-3",
      children: [/*#__PURE__*/_jsxs("div", {
        className: selectedPatient && showHistoryPanel ? "col-12 col-lg-8 col-xl-8" : "col-12",
        children: [isRestoredBannerVisible && /*#__PURE__*/_jsxs("div", {
          className: "alert alert-info py-2 px-3 d-flex flex-wrap justify-content-between align-items-center mb-2 shadow-sm border-info rounded-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-clockwise text-info fs-5"
            }), /*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsx("strong", {
                className: "text-dark",
                children: "Draft Auto-Restored:"
              }), /*#__PURE__*/_jsxs("span", {
                className: "text-secondary ms-1",
                children: ["Recovered ", /*#__PURE__*/_jsxs("strong", {
                  children: [cart.length, " medicine(s)"]
                }), " ", selectedPatient ? /*#__PURE__*/_jsxs(_Fragment, {
                  children: ["for ", /*#__PURE__*/_jsx("strong", {
                    children: selectedPatient.name || selectedPatient.patient_name
                  })]
                }) : '', " from your previous session ", draftSavedAt ? `(${draftSavedAt.toLocaleTimeString()})` : '', "."]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex gap-2 mt-1 mt-md-0",
            children: [/*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-primary py-0.5 px-2.5",
              onClick: () => setIsRestoredBannerVisible(false),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-check-lg me-1"
              }), " Continue Working"]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-danger py-0.5 px-2.5",
              onClick: clearDraftAndReset,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-trash me-1"
              }), " Discard Draft"]
            })]
          })]
        }), editingInvoiceBanner && /*#__PURE__*/_jsxs("div", {
          className: "alert alert-warning py-2 px-3 d-flex flex-wrap justify-content-between align-items-center mb-2 shadow-sm border-warning rounded-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-pencil-square text-warning-emphasis fs-5"
            }), /*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsxs("strong", {
                className: "text-dark",
                children: ["Editing Invoice #", editingInvoiceBanner, ":"]
              }), /*#__PURE__*/_jsx("span", {
                className: "text-secondary ms-1",
                children: "Patient and medicines loaded into POS Counter. You can adjust items, quantities, or re-dispense."
              })]
            })]
          }), /*#__PURE__*/_jsxs("button", {
            type: "button",
            className: "btn btn-sm btn-outline-dark py-0.5 px-2.5 mt-1 mt-md-0",
            onClick: clearDraftAndReset,
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-x-circle me-1"
            }), " Cancel Edit & New Bill"]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "counter-card position-relative",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "fw-bold text-dark fs-6",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-person-fill text-primary me-1"
                }), " Patient & Prescription"]
              }), /*#__PURE__*/_jsx("span", {
                className: `badge ${patientType === 'IPD' ? 'bg-danger' : patientType === 'OPD' ? 'bg-primary' : 'bg-secondary'}`,
                children: patientType
              }), draftStatus === 'saved' && /*#__PURE__*/_jsxs("span", {
                className: "badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5 d-inline-flex align-items-center gap-1 shadow-sm",
                style: {
                  fontSize: '0.72rem'
                },
                title: draftSavedAt ? `Auto-saved at ${draftSavedAt.toLocaleTimeString()}` : 'Auto-saved in local draft',
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-cloud-check-fill"
                }), " Draft Auto-Saved (", cart.length, " items)"]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-1.5 flex-wrap",
              children: [/*#__PURE__*/_jsxs("button", {
                type: "button",
                className: `btn btn-sm ${draftBillsList.length > 0 ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'} py-0.5 px-2.5 d-inline-flex align-items-center gap-1 shadow-sm`,
                onClick: () => {
                  loadDraftBills();
                  setShowDraftModal(true);
                },
                title: "Draft bills collected from racks via Mobile App",
                style: {
                  fontSize: "0.75rem"
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-phone"
                }), /*#__PURE__*/_jsx("span", {
                  children: "Mobile Drafts"
                }), draftBillsList.length > 0 && /*#__PURE__*/_jsx("span", {
                  className: "badge bg-danger text-white rounded-pill ms-1",
                  style: {
                    fontSize: '0.65rem'
                  },
                  children: draftBillsList.length
                })]
              }), (cart.length > 0 || selectedPatient) && /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-secondary py-0.5 px-2 d-inline-flex align-items-center gap-1 shadow-sm",
                onClick: clearDraftAndReset,
                title: "Discard draft and start new blank bill",
                style: {
                  fontSize: "0.75rem"
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-plus-circle"
                }), " New Bill"]
              }), lastInvoice && /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-success py-0.5 px-2.5 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm",
                onClick: () => setIsPrintOpen(true),
                title: "View & Print Last Bill",
                style: {
                  fontSize: "0.75rem"
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-printer"
                }), " Last Bill: ", /*#__PURE__*/_jsx("strong", {
                  children: lastInvoice.sale.invoice_no
                })]
              })]
            }), selectedPatient && /*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-1.5 flex-wrap",
              children: [/*#__PURE__*/_jsxs("button", {
                type: "button",
                className: `btn btn-sm ${showHistoryPanel ? 'btn-primary text-white' : 'btn-outline-primary'} py-1 px-2.5 d-inline-flex align-items-center gap-1 shadow-sm`,
                onClick: () => setShowHistoryPanel(!showHistoryPanel),
                title: "Toggle Patient Old Records & Past Dispensed Medicines (Right-Side)",
                style: {
                  fontSize: "0.75rem"
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history"
                }), /*#__PURE__*/_jsx("span", {
                  children: "Old Records"
                }), patientHistory && patientHistory.total_sales > 0 && /*#__PURE__*/_jsx("span", {
                  className: `badge ${showHistoryPanel ? 'bg-white text-primary' : 'bg-primary text-white'} ms-1`,
                  style: {
                    fontSize: "0.65rem"
                  },
                  children: patientHistory.total_sales
                })]
              }), (selectedPatient.has_active_opd || selectedPatient.has_active_ipd) && /*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-outline-success py-1 px-2.5",
                onClick: loadDoctorPrescription,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-medical me-1"
                }), " 1-Click Load Doctor Rx"]
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-outline-secondary py-1 px-2",
                onClick: resetPatient,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-x-circle me-1"
                }), " Clear"]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "row g-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "col-md-7 position-relative",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "input-group input-group-sm",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: "input-group-text bg-light text-primary fw-bold",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: [/*#__PURE__*/_jsx("kbd", {
                    className: "me-1",
                    style: {
                      background: '#0f172a',
                      color: '#38bdf8',
                      fontWeight: 800,
                      padding: '1px 5px',
                      borderRadius: '3px'
                    },
                    children: "F4"
                  }), /*#__PURE__*/_jsx("i", {
                    className: "bi bi-search"
                  })]
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  id: "patient-search-input",
                  ref: patientSearchRef,
                  className: "form-control",
                  placeholder: "Search by UHID, Mobile, Name, OPD No, IPD No... (F4)",
                  value: patientQuery,
                  onChange: e => searchPatients(e.target.value),
                  onKeyDown: handlePatientSearchKeyDown,
                  autoComplete: "off"
                })]
              }), patientResults.length > 0 && /*#__PURE__*/_jsx("div", {
                className: "search-results-dropdown",
                children: patientResults.map((p, pIdx) => {
                  const isHighlighted = pIdx === selectedPatientIndex;
                  return /*#__PURE__*/_jsxs("div", {
                    className: `search-item-row d-flex justify-content-between align-items-center ${isHighlighted ? 'bg-primary-subtle border-primary' : ''}`,
                    onClick: () => {
                      selectPatient(p);
                      setTimeout(() => focusMedicineSearch(), 50);
                    },
                    style: {
                      cursor: 'pointer'
                    },
                    children: [/*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsxs("div", {
                        className: "fw-bold text-dark",
                        children: [p.full_name, " ", /*#__PURE__*/_jsxs("small", {
                          className: "text-primary",
                          children: ["(", p.uhid, ")"]
                        }), isHighlighted && /*#__PURE__*/_jsx("span", {
                          className: "badge bg-primary text-white ms-2",
                          style: {
                            fontSize: '0.65rem'
                          },
                          children: "↵ Enter"
                        }), (p.abha_id || p.abha_address) && /*#__PURE__*/_jsxs("span", {
                          className: "badge bg-success-subtle text-success border border-success ms-2",
                          style: {
                            fontSize: '0.65rem'
                          },
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-shield-fill-check me-1"
                          }), "ABHA: ", p.abha_id || p.abha_address]
                        })]
                      }), /*#__PURE__*/_jsxs("small", {
                        className: "text-muted",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-telephone me-1"
                        }), p.mobile || 'No Mobile', " • ", p.gender, " • ", p.age]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-end",
                      children: [p.has_active_ipd && /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-danger d-block mb-1",
                        children: ["IPD Admitted: ", p.active_ipd.bed_info?.bed_no || 'Bed']
                      }), p.has_active_opd && /*#__PURE__*/_jsx("span", {
                        className: "badge bg-info d-block",
                        children: "OPD Visit Today"
                      })]
                    })]
                  }, p.patient_id);
                })
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "col-md-5",
              children: /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm",
                placeholder: "Consultant Doctor Name",
                value: doctorName,
                onChange: e => setDoctorName(e.target.value)
              })
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "row g-2 mt-1",
            children: [/*#__PURE__*/_jsx("div", {
              className: "col-md-6",
              children: /*#__PURE__*/_jsxs("div", {
                className: "input-group input-group-sm",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: "input-group-text bg-light text-success fw-bold",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-shield-check me-1"
                  }), " ABHA No"]
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm",
                  placeholder: "14-Digit ABHA (e.g. 12-3456-7890-1234)",
                  value: walkinAbhaId,
                  onChange: e => setWalkinAbhaId(e.target.value)
                })]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-md-6",
              children: /*#__PURE__*/_jsxs("div", {
                className: "input-group input-group-sm",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: "input-group-text bg-light text-success fw-bold",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-at me-1"
                  }), " ABHA Address"]
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm",
                  placeholder: "username@abdm",
                  value: walkinAbhaAddress,
                  onChange: e => setWalkinAbhaAddress(e.target.value)
                })]
              })
            })]
          }), selectedPatient && /*#__PURE__*/_jsxs("div", {
            className: "mt-2 p-2 bg-light rounded-2 d-flex flex-wrap gap-3 align-items-center",
            style: {
              fontSize: '0.8rem'
            },
            children: [/*#__PURE__*/_jsxs("span", {
              children: [/*#__PURE__*/_jsx("strong", {
                children: "Name:"
              }), " ", selectedPatient.full_name]
            }), /*#__PURE__*/_jsxs("span", {
              children: [/*#__PURE__*/_jsx("strong", {
                children: "UHID:"
              }), " ", /*#__PURE__*/_jsx("code", {
                className: "text-primary",
                children: selectedPatient.uhid
              })]
            }), /*#__PURE__*/_jsxs("span", {
              children: [/*#__PURE__*/_jsx("strong", {
                children: "Gender/Age:"
              }), " ", selectedPatient.gender, ", ", selectedPatient.age]
            }), (selectedPatient.abha_id || selectedPatient.abha_address) && /*#__PURE__*/_jsxs("span", {
              className: "badge bg-success text-white py-1 px-2",
              style: {
                fontSize: '0.75rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-shield-fill-check me-1"
              }), " ABDM Linked: ", selectedPatient.abha_id || '', " ", selectedPatient.abha_address ? `(${selectedPatient.abha_address})` : '']
            }), selectedPatient.has_active_ipd && /*#__PURE__*/_jsxs("span", {
              className: "text-danger fw-bold",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-hospital me-1"
              }), selectedPatient.active_ipd.bed_info?.ward_name || 'Ward', " / ", selectedPatient.active_ipd.bed_info?.bed_no || 'Bed', " (Dr. ", selectedPatient.active_ipd.doctor_name, ")"]
            }), selectedPatient.has_active_opd && !selectedPatient.has_active_ipd && /*#__PURE__*/_jsxs("span", {
              className: "text-primary fw-bold",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-stethoscope me-1"
              }), "OPD Dr. ", selectedPatient.active_opd.doctor_name]
            })]
          }), !selectedPatient && (walkinMobile || walkinUhid) && /*#__PURE__*/_jsxs("div", {
            className: "mt-2 p-2 bg-light rounded-2 d-flex flex-wrap gap-3 align-items-center",
            style: {
              fontSize: '0.8rem'
            },
            children: [/*#__PURE__*/_jsxs("span", {
              children: [/*#__PURE__*/_jsx("strong", {
                children: "Customer:"
              }), " ", patientQuery || 'Walk-in']
            }), walkinMobile && /*#__PURE__*/_jsxs("span", {
              children: [/*#__PURE__*/_jsx("strong", {
                children: "Mobile:"
              }), " ", /*#__PURE__*/_jsx("i", {
                className: "bi bi-telephone me-1 text-primary"
              }), walkinMobile]
            }), walkinUhid && /*#__PURE__*/_jsxs("span", {
              children: [/*#__PURE__*/_jsx("strong", {
                children: "UHID:"
              }), " ", /*#__PURE__*/_jsx("code", {
                className: "text-primary",
                children: walkinUhid
              })]
            })]
          }), loadedDraft && /*#__PURE__*/_jsxs("div", {
            className: "mt-2 p-2 rounded-2 d-flex flex-wrap justify-content-between align-items-center gap-2",
            style: {
              background: '#fef3c7',
              border: '1px solid #f59e0b',
              fontSize: '0.8rem'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex flex-wrap align-items-center gap-2",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "badge bg-warning text-dark fw-bold",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-phone me-1"
                }), " Draft: ", loadedDraft.draft_code]
              }), /*#__PURE__*/_jsxs("span", {
                className: "text-dark",
                children: ["Collector: ", /*#__PURE__*/_jsx("strong", {
                  children: loadedDraft.collected_by_name || 'Staff'
                }), loadedDraft.ward_name ? ` • Ward: ${loadedDraft.ward_name} (${loadedDraft.bed_no || ''})` : '']
              }), /*#__PURE__*/_jsxs("span", {
                className: "badge bg-light text-secondary border",
                style: {
                  fontSize: '0.7rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-right-circle me-1 text-success"
                }), "Auto-converts to invoice on checkout"]
              })]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-dark py-0 px-2",
              onClick: () => {
                setLoadedDraft(null);
                notify(`Draft [${loadedDraft.draft_code}] unlinked. Cart items kept.`, 'info');
              },
              title: "Unlink this draft without clearing the cart",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-link-45deg me-1"
              }), " Unlink Draft"]
            })]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "counter-card position-relative",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex flex-wrap align-items-center gap-1.5 mb-2.5 py-1.5 px-2.5 rounded-3 shadow-sm",
            style: {
              background: '#f8fafc',
              border: '1px solid #cbd5e1'
            },
            children: [/*#__PURE__*/_jsxs("span", {
              className: "fw-bold d-flex align-items-center me-1",
              style: {
                color: '#0f172a',
                fontSize: '0.74rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-keyboard-fill text-primary me-1 fs-6"
              }), /*#__PURE__*/_jsx("span", {
                children: "Keys:"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0f172a',
                  color: '#38bdf8',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "F2"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                style: {
                  color: '#0f172a'
                },
                children: "Search"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0f172a',
                  color: '#38bdf8',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "↑ / ↓"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                style: {
                  color: '#0f172a'
                },
                children: "Select"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0f172a',
                  color: '#38bdf8',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "↵ Enter"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                style: {
                  color: '#0f172a'
                },
                children: "Batch"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border border-primary shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0891b2',
                  color: '#ffffff',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "↵ on Qty"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold text-primary",
                children: "Next Item"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0f172a',
                  color: '#10b981',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "Alt+T"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                style: {
                  color: '#0f172a'
                },
                children: "Loose"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0f172a',
                  color: '#38bdf8',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "Alt+S"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                style: {
                  color: '#0f172a'
                },
                children: "Strip"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0f172a',
                  color: '#f59e0b',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "F4"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                style: {
                  color: '#0f172a'
                },
                children: "Patient"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2 py-0.5 bg-white rounded border shadow-xs",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#0f172a',
                  color: '#a855f7',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "F8"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                style: {
                  color: '#0f172a'
                },
                children: "Payment"
              })]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-xs py-0.5 px-2 bg-white rounded border border-danger shadow-xs d-inline-flex align-items-center gap-1",
              onClick: openReturnModal,
              style: {
                fontSize: '0.72rem'
              },
              title: "Accept patient returns or exchange on current bill (Alt+R / F6)",
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#e11d48',
                  color: '#ffffff',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "Alt+R / F6"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold text-danger",
                children: "Return / Exch"
              })]
            }), /*#__PURE__*/_jsxs("span", {
              className: "d-inline-flex align-items-center gap-1 px-2.5 py-0.5 rounded text-white shadow-sm",
              style: {
                background: '#059669',
                fontSize: '0.72rem',
                border: '1px solid #047857'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#ffffff',
                  color: '#065f46',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px',
                  fontSize: '0.68rem'
                },
                children: "F9 / Ctrl+↵"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold",
                children: "Bill & Print"
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2 mb-2",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-upc-scan text-primary fs-5"
            }), /*#__PURE__*/_jsx("span", {
              className: "fw-bold fs-6",
              children: "Medicine Lookup & Barcode Dispenser"
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "input-group",
            children: [/*#__PURE__*/_jsxs("span", {
              className: "input-group-text bg-light text-primary fw-bold",
              style: {
                fontSize: '0.75rem'
              },
              children: [/*#__PURE__*/_jsx("kbd", {
                className: "me-1",
                style: {
                  background: '#0f172a',
                  color: '#38bdf8',
                  fontWeight: 800,
                  padding: '1px 5px',
                  borderRadius: '3px'
                },
                children: "F2"
              }), /*#__PURE__*/_jsx("i", {
                className: "bi bi-capsule"
              })]
            }), /*#__PURE__*/_jsx("input", {
              type: "text",
              id: "item-search-input",
              ref: medicineSearchRef,
              className: "form-control fw-semibold",
              placeholder: "Type Medicine Name, Generic Molecule, or Scan Box Barcode... (Press F2 anytime)",
              value: itemQuery,
              onChange: e => searchItems(e.target.value),
              onKeyDown: handleMedicineSearchKeyDown,
              autoComplete: "off"
            }), isSearchingItems && /*#__PURE__*/_jsx("span", {
              className: "input-group-text bg-light",
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-hourglass-split"
              })
            })]
          }), itemResults.length > 0 && /*#__PURE__*/_jsx("div", {
            className: "search-results-dropdown",
            children: itemResults.map(it => /*#__PURE__*/_jsxs("div", {
              className: "search-item-row",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between align-items-center mb-1",
                children: [/*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "fw-bold text-dark fs-6",
                    children: it.item_name
                  }), /*#__PURE__*/_jsx("span", {
                    className: "badge bg-secondary ms-2",
                    style: {
                      fontSize: '0.65rem'
                    },
                    children: it.unit_pack
                  }), it.drug_schedule && it.drug_schedule !== 'OTC' && /*#__PURE__*/_jsx("span", {
                    className: "badge bg-warning-subtle text-warning border border-warning ms-1",
                    style: {
                      fontSize: '0.65rem'
                    },
                    children: it.drug_schedule
                  })]
                }), /*#__PURE__*/_jsx("div", {
                  children: /*#__PURE__*/_jsxs("span", {
                    className: "text-muted me-2",
                    style: {
                      fontSize: '0.75rem'
                    },
                    children: ["Total Stock: ", it.total_stock]
                  })
                })]
              }), /*#__PURE__*/_jsxs("small", {
                className: "text-muted d-block mb-1",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-diagram-3 me-1"
                }), it.generic_name, " • HSN: ", it.hsn_code]
              }), it.batches && it.batches.length > 0 ? /*#__PURE__*/_jsx("div", {
                className: "d-flex flex-wrap gap-2 mt-1",
                children: it.batches.map(b => {
                  const isHighlighted = selectableBatches[selectedBatchIndex] && selectableBatches[selectedBatchIndex].batch.batch_id === b.batch_id && selectableBatches[selectedBatchIndex].item.item_id === it.item_id;
                  return /*#__PURE__*/_jsxs("button", {
                    className: `btn btn-sm ${isHighlighted ? 'btn-warning text-dark border-2 shadow-sm fw-bold' : b.is_expired ? 'btn-outline-danger disabled' : 'btn-outline-dark'} d-flex align-items-center gap-1 py-0.5 px-2`,
                    style: {
                      fontSize: '0.75rem',
                      transform: isHighlighted ? 'scale(1.04)' : 'scale(1)',
                      transition: 'transform 0.1s'
                    },
                    onClick: () => addItemToCart(it, b, 1),
                    disabled: b.is_expired,
                    title: isHighlighted ? 'Press Enter to select this batch' : `Select batch ${b.batch_no}`,
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: b.batch_no
                    }), /*#__PURE__*/_jsxs("span", {
                      children: ["(Exp: ", b.expiry_display, ")"]
                    }), /*#__PURE__*/_jsxs("span", {
                      className: "text-success fw-bold",
                      children: ["₹", b.mrp]
                    }), /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-primary text-white",
                      children: [b.current_qty, " left"]
                    }), it.fefo_batch && it.fefo_batch.batch_id === b.batch_id && /*#__PURE__*/_jsx("span", {
                      className: "badge-fefo ms-1",
                      children: "FEFO"
                    }), b.is_near_expiry && /*#__PURE__*/_jsx("span", {
                      className: "badge-near-expiry ms-1",
                      children: "Near Exp"
                    }), b.is_expired && /*#__PURE__*/_jsx("span", {
                      className: "badge-expired ms-1",
                      children: "EXPIRED"
                    }), isHighlighted && /*#__PURE__*/_jsx("span", {
                      className: "badge bg-dark text-warning ms-1",
                      style: {
                        fontSize: '0.62rem'
                      },
                      children: "↵ Enter"
                    })]
                  }, b.batch_id);
                })
              }) : /*#__PURE__*/_jsx("span", {
                className: "text-danger fw-bold",
                style: {
                  fontSize: '0.75rem'
                },
                children: "Out of Stock in this Counter"
              })]
            }, it.item_id))
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "counter-card p-0 overflow-hidden",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "p-3 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "mb-0 fw-bold",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-cart-check-fill text-success me-1"
                }), " Dispensing Cart (", cart.length, " new)"]
              }), returnCart.length > 0 && /*#__PURE__*/_jsxs("span", {
                className: "badge bg-danger-subtle text-danger border border-danger",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-return-left me-1"
                }), returnCart.length, " return/exchange"]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-2 align-items-center",
              children: [(cart.length > 0 || returnCart.length > 0) && /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-success py-0.5 px-2.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm",
                onClick: () => setIsCheckoutOpen(true),
                style: {
                  fontSize: '0.75rem',
                  background: '#10b981',
                  borderColor: '#059669'
                },
                title: "Open Checkout Desk to finalize bill (F8 / F9)",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-wallet2"
                }), " Checkout ", refundPayable > 0 ? `(Refund ₹${refundPayable})` : `(₹${netPayable})`, " ", /*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-right"
                })]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-danger py-0.5 px-2.5 fw-semibold d-inline-flex align-items-center gap-1",
                onClick: openReturnModal,
                style: {
                  fontSize: '0.75rem'
                },
                title: "Add patient return or item exchange (Alt+R / F6)",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-return-left"
                }), " Add Return / Exch"]
              }), (cart.length > 0 || returnCart.length > 0) && /*#__PURE__*/_jsx("button", {
                className: "btn btn-sm btn-link text-muted text-decoration-none p-0",
                onClick: () => {
                  setCart([]);
                  setReturnCart([]);
                },
                children: "Clear All"
              })]
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "table-responsive",
            style: {
              maxHeight: 380
            },
            children: /*#__PURE__*/_jsxs("table", {
              className: "table table-pos mb-0",
              children: [/*#__PURE__*/_jsx("thead", {
                children: /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsx("th", {
                    children: "#"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Medicine Name"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Batch / Exp"
                  }), /*#__PURE__*/_jsx("th", {
                    style: {
                      width: 155
                    },
                    children: "Qty & Unit"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Rate (₹)"
                  }), /*#__PURE__*/_jsx("th", {
                    style: {
                      width: 125
                    },
                    children: "Disc (% / ₹)"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "GST%"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    children: "Total (₹)"
                  }), /*#__PURE__*/_jsx("th", {
                    style: {
                      width: 40
                    }
                  })]
                })
              }), /*#__PURE__*/_jsx("tbody", {
                children: cart.length === 0 && returnCart.length === 0 ? /*#__PURE__*/_jsx("tr", {
                  children: /*#__PURE__*/_jsxs("td", {
                    colSpan: "9",
                    className: "text-center py-4 text-muted",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-basket3 fs-3 d-block mb-1"
                    }), "Scan barcode or search medicine to add items, or click ", /*#__PURE__*/_jsx("strong", {
                      className: "text-danger",
                      children: "Add Return / Exch"
                    }), " (Alt+R / F6)."]
                  })
                }) : cart.map((it, idx) => {
                  const info = getItemLineInfo(it);
                  const lineGross = info.lineGross;
                  const lineDisc = getItemDiscount(it);
                  const lineNet = Math.max(0, lineGross - lineDisc).toFixed(2);
                  const upp = info.upp;
                  return /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("td", {
                      children: idx + 1
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold",
                        children: it.item_name
                      }), /*#__PURE__*/_jsxs("small", {
                        className: "text-muted",
                        children: [it.generic_name, " • HSN: ", it.hsn_code]
                      }), upp > 1 && /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-secondary-subtle text-secondary border ms-1",
                        style: {
                          fontSize: '0.62rem'
                        },
                        children: ["Pack: ", it.unit_pack || `${upp} Tabs`]
                      })]
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsx("span", {
                        className: "badge bg-light text-dark border",
                        children: it.batch_no
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.7rem'
                        },
                        children: ["Exp: ", it.expiry_display]
                      })]
                    }), /*#__PURE__*/_jsx("td", {
                      children: upp > 1 ? /*#__PURE__*/_jsxs("div", {
                        style: {
                          maxWidth: 155
                        },
                        children: [it.sell_unit === 'Combo' ? /*#__PURE__*/_jsxs("div", {
                          className: "input-group input-group-sm",
                          children: [/*#__PURE__*/_jsxs("select", {
                            className: "form-select form-select-sm py-0 px-1 fw-bold bg-warning-subtle text-dark border-warning",
                            style: {
                              maxWidth: 64,
                              fontSize: '0.72rem'
                            },
                            value: it.sell_unit,
                            onChange: e => switchSellUnit(idx, e.target.value),
                            title: "Unit Type: Combo (Strips + Loose Tablets)",
                            children: [/*#__PURE__*/_jsx("option", {
                              value: "Tablet",
                              children: "Tab"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "Strip",
                              children: "Strip"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "Combo",
                              children: "Str+Tab"
                            })]
                          }), /*#__PURE__*/_jsx("input", {
                            type: "number",
                            id: `cart-qty-strip-${idx}`,
                            className: "form-control form-control-sm text-center fw-bold px-1",
                            min: "0",
                            placeholder: "Str",
                            title: "Whole Strips",
                            value: it.strip_qty ?? 1,
                            onChange: e => updateCartItem(idx, 'strip_qty', Number(e.target.value)),
                            onKeyDown: e => {
                              if (e.key === 'Enter') {
                                e.preventDefault();
                                focusMedicineSearch();
                              } else if (e.key === 'Delete' && e.shiftKey) {
                                e.preventDefault();
                                removeCartItem(idx);
                                focusMedicineSearch();
                              }
                            }
                          }), /*#__PURE__*/_jsx("input", {
                            type: "number",
                            id: `cart-qty-loose-${idx}`,
                            className: "form-control form-control-sm text-center fw-bold text-success border-success px-1",
                            min: "0",
                            placeholder: "Tab",
                            title: "Loose Tablets",
                            value: it.loose_qty ?? 0,
                            onChange: e => updateCartItem(idx, 'loose_qty', Number(e.target.value)),
                            onKeyDown: e => {
                              if (e.key === 'Enter') {
                                e.preventDefault();
                                focusMedicineSearch();
                              } else if (e.key === 'Delete' && e.shiftKey) {
                                e.preventDefault();
                                removeCartItem(idx);
                                focusMedicineSearch();
                              }
                            }
                          })]
                        }) : /*#__PURE__*/_jsxs("div", {
                          className: "input-group input-group-sm",
                          children: [/*#__PURE__*/_jsxs("select", {
                            className: `form-select form-select-sm py-0 px-1 fw-bold ${it.sell_unit === 'Strip' ? 'bg-primary-subtle text-primary border-primary' : 'bg-success-subtle text-success border-success'}`,
                            style: {
                              maxWidth: 65,
                              fontSize: '0.72rem'
                            },
                            value: it.sell_unit || 'Tablet',
                            onChange: e => switchSellUnit(idx, e.target.value),
                            title: "Select Unit Type before quantity",
                            children: [/*#__PURE__*/_jsx("option", {
                              value: "Tablet",
                              children: "Tab"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "Strip",
                              children: "Strip"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "Combo",
                              children: "Str+Tab"
                            })]
                          }), /*#__PURE__*/_jsx("input", {
                            type: "number",
                            id: it.sell_unit === 'Strip' ? `cart-qty-strip-${idx}` : `cart-qty-loose-${idx}`,
                            className: `form-control form-control-sm text-center fw-bold ${it.sell_unit === 'Strip' ? 'text-primary' : 'text-success border-success'}`,
                            min: "1",
                            max: it.current_qty,
                            value: it.sell_unit === 'Strip' ? it.strip_qty ?? it.qty ?? 1 : it.loose_qty ?? it.qty ?? 1,
                            onChange: e => {
                              const val = Number(e.target.value);
                              const updated = [...cart];
                              if (it.sell_unit === 'Strip') {
                                updated[idx].strip_qty = val;
                              } else {
                                updated[idx].loose_qty = val;
                              }
                              updated[idx].qty = val;
                              setCart(updated);
                            },
                            onKeyDown: e => {
                              if (e.key === 'Enter') {
                                e.preventDefault();
                                focusMedicineSearch();
                              } else if (e.altKey && (e.key === 's' || e.key === 'S')) {
                                e.preventDefault();
                                switchSellUnit(idx, 'Strip');
                              } else if (e.altKey && (e.key === 't' || e.key === 'T')) {
                                e.preventDefault();
                                switchSellUnit(idx, 'Tablet');
                              } else if (e.altKey && (e.key === 'c' || e.key === 'C')) {
                                e.preventDefault();
                                switchSellUnit(idx, 'Combo');
                              } else if (e.key === 'Delete' && e.shiftKey) {
                                e.preventDefault();
                                removeCartItem(idx);
                                focusMedicineSearch();
                              }
                            }
                          })]
                        }), /*#__PURE__*/_jsx("div", {
                          className: "text-muted text-center mt-0.5",
                          style: {
                            fontSize: '0.64rem'
                          },
                          children: it.sell_unit === 'Tablet' ? `= ${info.totalUnits} Tab (₹${info.perUnitMrp}/tab)` : it.sell_unit === 'Combo' ? `= ${info.totalUnits} Tab total` : `= ${info.totalUnits} Tab (${upp}/str)`
                        })]
                      }) : /*#__PURE__*/_jsxs("div", {
                        style: {
                          maxWidth: 120
                        },
                        children: [/*#__PURE__*/_jsxs("div", {
                          className: "input-group input-group-sm",
                          children: [/*#__PURE__*/_jsx("span", {
                            className: "input-group-text bg-light text-muted px-1.5 py-0 fw-semibold",
                            style: {
                              fontSize: '0.68rem',
                              maxWidth: 50
                            },
                            children: it.unit_pack || 'Unit'
                          }), /*#__PURE__*/_jsx("input", {
                            type: "number",
                            id: `cart-qty-strip-${idx}`,
                            className: "form-control form-control-sm text-center fw-bold",
                            min: "1",
                            max: it.current_qty,
                            value: it.qty,
                            onChange: e => updateCartItem(idx, 'qty', Number(e.target.value)),
                            onKeyDown: e => {
                              if (e.key === 'Enter') {
                                e.preventDefault();
                                focusMedicineSearch();
                              } else if (e.key === 'Delete' && e.shiftKey) {
                                e.preventDefault();
                                removeCartItem(idx);
                                focusMedicineSearch();
                              }
                            }
                          })]
                        }), /*#__PURE__*/_jsxs("div", {
                          className: "text-muted text-center mt-0.5",
                          style: {
                            fontSize: '0.64rem'
                          },
                          children: ["₹", it.unit_mrp, " each"]
                        })]
                      })
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-semibold",
                        children: it.sell_unit === 'Tablet' ? /*#__PURE__*/_jsxs("span", {
                          children: ["₹", info.perUnitMrp, " ", /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/tab"
                          })]
                        }) : it.sell_unit === 'Combo' ? /*#__PURE__*/_jsxs("span", {
                          children: ["₹", it.unit_mrp, " ", /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/str"
                          })]
                        }) : /*#__PURE__*/_jsxs("span", {
                          children: ["₹", it.unit_mrp, " ", upp > 1 ? /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/strip"
                          }) : '']
                        })
                      }), upp > 1 && /*#__PURE__*/_jsx("small", {
                        className: "text-muted d-block",
                        style: {
                          fontSize: '0.68rem'
                        },
                        children: it.sell_unit === 'Tablet' ? `Strip: ₹${it.unit_mrp}` : `Tab: ₹${info.perUnitMrp}`
                      })]
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsxs("div", {
                        className: "input-group input-group-sm",
                        style: {
                          width: 115
                        },
                        children: [/*#__PURE__*/_jsx("input", {
                          type: "number",
                          id: `cart-disc-${idx}`,
                          className: "form-control form-control-sm text-center px-1 fw-semibold",
                          min: "0",
                          max: it.discount_type === 'pct' ? 100 : lineGross,
                          step: it.discount_type === 'pct' ? '0.5' : '1',
                          value: it.discount_val ?? '',
                          placeholder: "0",
                          onChange: e => updateCartItem(idx, 'discount_val', e.target.value),
                          onKeyDown: e => {
                            if (e.key === 'Enter') {
                              e.preventDefault();
                              focusMedicineSearch();
                            }
                          }
                        }), /*#__PURE__*/_jsx("button", {
                          type: "button",
                          className: `btn btn-sm ${it.discount_type === 'pct' ? 'btn-primary' : 'btn-warning text-dark'} px-2 py-0 fw-bold`,
                          title: `Click to switch between % and ₹ (Currently: ${it.discount_type === 'pct' ? 'Percentage' : 'Value in Rupees'})`,
                          onClick: () => toggleItemDiscType(idx),
                          children: it.discount_type === 'pct' ? '%' : '₹'
                        })]
                      }), lineDisc > 0 && /*#__PURE__*/_jsx("div", {
                        className: "text-muted text-center mt-0.5",
                        style: {
                          fontSize: '0.68rem'
                        },
                        children: it.discount_type === 'pct' ? `-₹${lineDisc.toFixed(2)}` : `${lineGross > 0 ? (lineDisc / lineGross * 100).toFixed(1) : 0}% off`
                      })]
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [it.gst_rate, "%"]
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "text-end fw-bold text-dark",
                      children: ["₹", lineNet]
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("button", {
                        className: "btn btn-sm btn-outline-danger py-0 px-1",
                        onClick: () => removeCartItem(idx),
                        children: /*#__PURE__*/_jsx("i", {
                          className: "bi bi-trash"
                        })
                      })
                    })]
                  }, `sale-${idx}`);
                }).concat(returnCart.map((rc, rIdx) => /*#__PURE__*/_jsxs("tr", {
                  style: {
                    backgroundColor: 'rgba(244, 63, 94, 0.05)'
                  },
                  children: [/*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: "badge bg-danger text-white",
                      children: "RET"
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "fw-bold text-danger",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-arrow-return-left me-1"
                      }), rc.item_name]
                    }), /*#__PURE__*/_jsxs("small", {
                      className: "text-muted",
                      children: ["Bill: ", /*#__PURE__*/_jsx("code", {
                        children: rc.ref_invoice_no
                      }), " • ", rc.reason ? rc.reason.replace(/_/g, ' ') : 'Return']
                    })]
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "badge bg-light text-dark border",
                      children: rc.batch_no
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-muted",
                      style: {
                        fontSize: '0.7rem'
                      },
                      children: ["Exp: ", rc.expiry_date ? rc.expiry_date.substring(0, 7) : '']
                    })]
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsxs("span", {
                      className: "badge bg-danger-subtle text-danger border border-danger fw-bold",
                      children: ["- ", rc.return_qty, " ", rc.return_unit]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-muted mt-0.5",
                      style: {
                        fontSize: '0.64rem'
                      },
                      children: ["(", rc.return_total_units, " Tabs • ", rc.condition === 'GOOD' ? 'Restocking' : 'Discard', ")"]
                    })]
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "fw-semibold text-danger",
                      children: ["₹", rc.unit_price, " ", /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        children: "/tab"
                      })]
                    }), rc.units_per_pack > 1 && /*#__PURE__*/_jsxs("small", {
                      className: "text-muted d-block",
                      style: {
                        fontSize: '0.68rem'
                      },
                      children: ["MRP: ₹", rc.unit_mrp]
                    })]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: "badge bg-secondary-subtle text-secondary",
                      style: {
                        fontSize: '0.7rem'
                      },
                      children: rc.condition === 'GOOD' ? 'Restock' : 'Scrap'
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [rc.gst_rate || 12, "%"]
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end fw-bold text-danger",
                    children: ["-₹", Number(rc.line_total).toFixed(2)]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("button", {
                      className: "btn btn-sm btn-outline-danger py-0 px-1",
                      onClick: () => setReturnCart(returnCart.filter((_, i) => i !== rIdx)),
                      title: "Remove from returns",
                      children: /*#__PURE__*/_jsx("i", {
                        className: "bi bi-trash"
                      })
                    })
                  })]
                }, `ret-${rIdx}`)))
              })]
            })
          }), /*#__PURE__*/_jsxs("div", {
            className: "p-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-3",
            children: [/*#__PURE__*/_jsx("div", {
              className: "d-flex flex-wrap align-items-center gap-3",
              children: /*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: "badge bg-secondary-subtle text-secondary border px-2.5 py-1.5 fw-semibold",
                  style: {
                    fontSize: '0.8rem'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-cart-check me-1"
                  }), " ", cart.length + returnCart.length, " Line Items"]
                }), /*#__PURE__*/_jsxs("span", {
                  className: "text-muted small",
                  children: ["Gross: ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-dark",
                    children: ["₹", grossTotal.toFixed(2)]
                  })]
                }), itemDiscountTotal > 0 && /*#__PURE__*/_jsxs("span", {
                  className: "text-muted small",
                  children: ["Item Disc: ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-danger",
                    children: ["-₹", itemDiscountTotal.toFixed(2)]
                  })]
                }), wholeDiscountAmt > 0 && /*#__PURE__*/_jsxs("span", {
                  className: "text-muted small",
                  children: ["Bill Disc: ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-danger",
                    children: ["-₹", wholeDiscountAmt.toFixed(2)]
                  })]
                }), returnTotal > 0 && /*#__PURE__*/_jsxs("span", {
                  className: "text-muted small",
                  children: ["Return Credit: ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-danger",
                    children: ["-₹", returnTotal.toFixed(2)]
                  })]
                })]
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "text-end",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "text-muted small text-uppercase fw-semibold",
                  style: {
                    fontSize: '0.68rem',
                    letterSpacing: '0.5px'
                  },
                  children: refundPayable > 0 ? 'Customer Refund' : 'Net Amount'
                }), /*#__PURE__*/_jsxs("div", {
                  className: `fs-4 fw-bolder ${refundPayable > 0 ? 'text-danger' : 'text-success'}`,
                  style: {
                    lineHeight: 1.1
                  },
                  children: ["₹", refundPayable > 0 ? refundPayable.toFixed(2) : netPayable.toFixed(2)]
                })]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                id: "btn-pos-checkout-trigger",
                className: "btn btn-success btn-lg px-4 py-2.5 fw-bold shadow d-flex align-items-center gap-2",
                disabled: cart.length === 0 && returnCart.length === 0,
                onClick: () => setIsCheckoutOpen(true),
                style: {
                  background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                  border: 'none',
                  borderRadius: '10px',
                  fontSize: '1rem',
                  letterSpacing: '0.3px',
                  transition: 'transform 0.15s ease, box-shadow 0.15s ease'
                },
                title: "Open Checkout Desk to review totals, select payment method and complete sale (F8 / F9)",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-wallet2 fs-5"
                }), /*#__PURE__*/_jsx("span", {
                  children: "Checkout"
                }), /*#__PURE__*/_jsx("span", {
                  className: "badge bg-white text-success ms-1 px-2 py-0.5",
                  style: {
                    fontSize: '0.72rem',
                    fontWeight: 800
                  },
                  children: "F8 / F9"
                }), /*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-right fs-5"
                })]
              })]
            })]
          })]
        })]
      }), selectedPatient && showHistoryPanel && /*#__PURE__*/_jsx("div", {
        className: "col-12 col-lg-4 col-xl-4",
        children: /*#__PURE__*/_jsxs("div", {
          className: "patient-history-sidebar position-sticky shadow-sm",
          style: {
            top: '15px',
            maxHeight: 'calc(100vh - 100px)',
            display: 'flex',
            flexDirection: 'column'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "patient-history-header",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center mb-1.5",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history text-info fs-5"
                }), /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("h6", {
                    className: "mb-0 fw-bold text-white",
                    style: {
                      fontSize: '0.9rem'
                    },
                    children: "Patient Pharmacy History"
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-secondary",
                    style: {
                      fontSize: '0.72rem'
                    },
                    children: ["UHID: ", /*#__PURE__*/_jsx("span", {
                      className: "text-warning fw-bold",
                      children: selectedPatient.uhid
                    })]
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-1",
                children: [/*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn btn-xs btn-outline-light py-0.5 px-2",
                  onClick: () => fetchPatientHistory(selectedPatient.patient_id, selectedPatient.uhid, selectedPatient.mobile),
                  disabled: isLoadingHistory,
                  title: "Refresh Patient History",
                  style: {
                    fontSize: '0.7rem'
                  },
                  children: /*#__PURE__*/_jsx("i", {
                    className: `bi bi-arrow-clockwise ${isLoadingHistory ? 'spin' : ''}`
                  })
                }), /*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn btn-xs btn-outline-light py-0.5 px-2",
                  onClick: () => setShowHistoryPanel(false),
                  title: "Hide sidebar (Click 'Old Records' in patient bar to re-open)",
                  style: {
                    fontSize: '0.7rem'
                  },
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-chevron-bar-right"
                  })
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex flex-wrap align-items-center justify-content-between gap-1 pt-1.5 border-top border-secondary",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsxs("span", {
                className: "text-light",
                children: [/*#__PURE__*/_jsx("strong", {
                  children: selectedPatient.full_name
                }), " • ", selectedPatient.gender, ", ", selectedPatient.age]
              }), patientHistory && /*#__PURE__*/_jsxs("span", {
                className: "badge bg-primary-subtle text-primary border border-primary px-1.5 py-0.5",
                children: [patientHistory.total_sales || 0, " Bills • ₹", patientHistory.total_spend || 0]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "patient-history-tabs",
            children: [/*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `patient-history-tab-btn ${historyActiveTab === 'last_given' ? 'active' : ''}`,
              onClick: () => setHistoryActiveTab('last_given'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-capsule text-success"
              }), /*#__PURE__*/_jsx("span", {
                children: "Last Given"
              }), patientHistory?.last_dispensed_items?.length > 0 && /*#__PURE__*/_jsx("span", {
                className: "badge bg-success text-white rounded-pill px-1",
                style: {
                  fontSize: '0.62rem'
                },
                children: patientHistory.last_dispensed_items.length
              })]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `patient-history-tab-btn ${historyActiveTab === 'all_bills' ? 'active' : ''}`,
              onClick: () => setHistoryActiveTab('all_bills'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-receipt text-primary"
              }), /*#__PURE__*/_jsx("span", {
                children: "Past Bills"
              }), patientHistory?.invoices?.length > 0 && /*#__PURE__*/_jsx("span", {
                className: "badge bg-primary text-white rounded-pill px-1",
                style: {
                  fontSize: '0.62rem'
                },
                children: patientHistory.invoices.length
              })]
            }), patientHistory?.doctor_prescriptions?.length > 0 && /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `patient-history-tab-btn ${historyActiveTab === 'prescriptions' ? 'active' : ''}`,
              onClick: () => setHistoryActiveTab('prescriptions'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-stethoscope text-danger"
              }), /*#__PURE__*/_jsx("span", {
                children: "Doctor Rx"
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-danger text-white rounded-pill px-1",
                style: {
                  fontSize: '0.62rem'
                },
                children: patientHistory.doctor_prescriptions.length
              })]
            }), patientHistory?.unique_medicines?.length > 0 && /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `patient-history-tab-btn ${historyActiveTab === 'summary' ? 'active' : ''}`,
              onClick: () => setHistoryActiveTab('summary'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-journal-medical text-warning"
              }), /*#__PURE__*/_jsx("span", {
                children: "All Meds"
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-secondary text-white rounded-pill px-1",
                style: {
                  fontSize: '0.62rem'
                },
                children: patientHistory.unique_medicines.length
              })]
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "patient-history-body",
            children: isLoadingHistory ? /*#__PURE__*/_jsxs("div", {
              className: "text-center py-5 text-muted",
              children: [/*#__PURE__*/_jsx("div", {
                className: "spinner-border spinner-border-sm text-primary me-2"
              }), "Loading patient records..."]
            }) : !patientHistory || patientHistory.total_sales === 0 && (!patientHistory.doctor_prescriptions || patientHistory.doctor_prescriptions.length === 0) ? /*#__PURE__*/_jsxs("div", {
              className: "text-center py-4 px-2 text-muted",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-folder-x fs-1 text-secondary opacity-50 d-block mb-2"
              }), /*#__PURE__*/_jsx("h6", {
                className: "fw-semibold text-dark",
                children: "No Previous Pharmacy Records"
              }), /*#__PURE__*/_jsx("small", {
                children: "This patient has no recorded medicine dispenses or bills in the pharmacy database yet."
              })]
            }) : /*#__PURE__*/_jsxs(_Fragment, {
              children: [historyActiveTab === 'last_given' && /*#__PURE__*/_jsx("div", {
                children: patientHistory.last_sale ? /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "p-2.5 mb-2 rounded-3 border",
                    style: {
                      background: '#f0fdf4',
                      borderColor: '#bbf7d0'
                    },
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between align-items-center mb-1",
                      children: [/*#__PURE__*/_jsxs("span", {
                        className: "fw-bold text-success",
                        style: {
                          fontSize: '0.78rem'
                        },
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-check-circle-fill me-1"
                        }), " Last Dispensed Visit"]
                      }), /*#__PURE__*/_jsx("span", {
                        className: "badge bg-dark text-white",
                        style: {
                          fontSize: '0.68rem'
                        },
                        children: patientHistory.last_sale.invoice_no
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-muted",
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsxs("div", {
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-calendar-event me-1"
                        }), " ", patientHistory.last_sale.sale_date ? patientHistory.last_sale.sale_date.substring(0, 16) : 'N/A']
                      }), patientHistory.last_sale.doctor_name && /*#__PURE__*/_jsxs("div", {
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-person-badge me-1"
                        }), " Prescribed by: ", /*#__PURE__*/_jsx("strong", {
                          children: patientHistory.last_sale.doctor_name
                        })]
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "d-flex justify-content-between align-items-center mt-1.5 pt-1 border-top border-success-subtle",
                        children: [/*#__PURE__*/_jsxs("span", {
                          children: ["Paid: ", /*#__PURE__*/_jsxs("strong", {
                            children: ["₹", patientHistory.last_sale.net_amount]
                          }), " (", patientHistory.last_sale.payment_mode, ")"]
                        }), /*#__PURE__*/_jsxs("button", {
                          type: "button",
                          className: "btn btn-xs btn-outline-success py-0 px-2 fw-bold",
                          onClick: () => viewPastBill(patientHistory.last_sale.sale_id),
                          style: {
                            fontSize: '0.68rem'
                          },
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-printer me-1"
                          }), " View Bill"]
                        })]
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "fw-bold text-dark mb-1.5 d-flex justify-content-between align-items-center",
                    style: {
                      fontSize: '0.75rem'
                    },
                    children: [/*#__PURE__*/_jsxs("span", {
                      children: ["Medicines Given (", patientHistory.last_dispensed_items.length, "):"]
                    }), /*#__PURE__*/_jsxs("small", {
                      className: "text-muted",
                      children: ["Click ", /*#__PURE__*/_jsx("strong", {
                        children: "+"
                      }), " to re-dispense"]
                    })]
                  }), patientHistory.last_dispensed_items.map((it, mIdx) => /*#__PURE__*/_jsxs("div", {
                    className: "history-med-card last-given",
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between align-items-start mb-1",
                      children: [/*#__PURE__*/_jsxs("div", {
                        children: [/*#__PURE__*/_jsx("div", {
                          className: "fw-bold text-dark",
                          style: {
                            fontSize: '0.82rem'
                          },
                          children: it.item_name
                        }), /*#__PURE__*/_jsx("small", {
                          className: "text-muted d-block",
                          style: {
                            fontSize: '0.68rem'
                          },
                          children: it.generic_name
                        })]
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "text-end",
                        children: [/*#__PURE__*/_jsxs("span", {
                          className: "fw-bold text-dark",
                          style: {
                            fontSize: '0.82rem'
                          },
                          children: ["₹", it.total_amount]
                        }), /*#__PURE__*/_jsxs("small", {
                          className: "text-muted d-block",
                          style: {
                            fontSize: '0.68rem'
                          },
                          children: ["₹", it.unit_mrp, "/", it.sell_unit]
                        })]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex flex-wrap align-items-center gap-1 mb-2",
                      style: {
                        fontSize: '0.68rem'
                      },
                      children: [/*#__PURE__*/_jsxs("span", {
                        className: "badge bg-light text-dark border",
                        children: ["Batch: ", it.batch_no]
                      }), /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-light text-secondary border",
                        children: ["Exp: ", it.expiry_date]
                      }), /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-primary text-white",
                        children: ["Qty: ", it.qty, " ", it.sell_unit]
                      }), it.remaining_units > 0 ? /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-success-subtle text-success border border-success",
                        children: ["Returnable: ", it.remaining_strips > 0 ? `${it.remaining_strips} Str ` : '', it.remaining_loose > 0 ? `${it.remaining_loose} Tab` : '']
                      }) : /*#__PURE__*/_jsx("span", {
                        className: "badge bg-secondary-subtle text-muted border",
                        children: "Fully Returned"
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex gap-1.5 pt-1.5 border-top",
                      children: [/*#__PURE__*/_jsxs("button", {
                        type: "button",
                        className: "btn btn-xs btn-success py-1 px-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-xs flex-fill justify-content-center",
                        onClick: () => reDispenseMedicine(it, it.qty),
                        style: {
                          fontSize: '0.72rem',
                          background: '#059669',
                          borderColor: '#047857'
                        },
                        title: "Add this medicine to active dispensing cart with current FEFO batch",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-plus-circle-fill"
                        }), /*#__PURE__*/_jsx("span", {
                          children: "Dispense Again"
                        })]
                      }), it.remaining_units > 0 && /*#__PURE__*/_jsxs("button", {
                        type: "button",
                        className: "btn btn-xs btn-outline-danger py-1 px-2 fw-semibold d-inline-flex align-items-center gap-1 shadow-xs",
                        onClick: () => initiateReturnFromHistory(patientHistory.last_sale, it),
                        style: {
                          fontSize: '0.72rem'
                        },
                        title: "Return or exchange this dispensed item into current bill",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-arrow-return-left"
                        }), /*#__PURE__*/_jsx("span", {
                          children: "Return"
                        })]
                      })]
                    })]
                  }, `last-med-${mIdx}`))]
                }) : /*#__PURE__*/_jsxs("div", {
                  className: "text-center py-4 text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-basket text-secondary fs-3 d-block mb-1"
                  }), "No pharmacy sales recorded yet for this patient."]
                })
              }), historyActiveTab === 'all_bills' && /*#__PURE__*/_jsx("div", {
                children: patientHistory.invoices?.map((inv, invIdx) => {
                  const s = inv.sale;
                  const isExp = expandedBillId === s.sale_id;
                  return /*#__PURE__*/_jsxs("div", {
                    className: "card mb-2 border shadow-xs",
                    style: {
                      borderRadius: '8px'
                    },
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "card-header py-2 px-2.5 bg-white d-flex justify-content-between align-items-center",
                      style: {
                        cursor: 'pointer',
                        borderBottom: isExp ? '1px solid #e2e8f0' : 'none'
                      },
                      onClick: () => setExpandedBillId(isExp ? null : s.sale_id),
                      children: [/*#__PURE__*/_jsxs("div", {
                        children: [/*#__PURE__*/_jsxs("div", {
                          className: "fw-bold text-dark d-flex align-items-center gap-1.5",
                          style: {
                            fontSize: '0.78rem'
                          },
                          children: [/*#__PURE__*/_jsx("i", {
                            className: `bi bi-chevron-${isExp ? 'down' : 'right'} text-primary`,
                            style: {
                              fontSize: '0.7rem'
                            }
                          }), /*#__PURE__*/_jsx("span", {
                            children: s.invoice_no
                          }), /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-light text-secondary border",
                            style: {
                              fontSize: '0.65rem'
                            },
                            children: [inv.items.length, " items"]
                          })]
                        }), /*#__PURE__*/_jsxs("small", {
                          className: "text-muted",
                          style: {
                            fontSize: '0.68rem'
                          },
                          children: [s.sale_date ? s.sale_date.substring(0, 10) : '', " • Dr. ", s.doctor_name || 'Consultant']
                        })]
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "text-end",
                        children: [/*#__PURE__*/_jsxs("span", {
                          className: "fw-bold text-success d-block",
                          style: {
                            fontSize: '0.82rem'
                          },
                          children: ["₹", s.net_amount]
                        }), /*#__PURE__*/_jsx("span", {
                          className: "badge bg-secondary-subtle text-secondary",
                          style: {
                            fontSize: '0.62rem'
                          },
                          children: s.payment_mode
                        })]
                      })]
                    }), isExp && /*#__PURE__*/_jsxs("div", {
                      className: "card-body p-2 bg-light",
                      children: [/*#__PURE__*/_jsxs("div", {
                        className: "d-flex justify-content-between align-items-center mb-1.5",
                        children: [/*#__PURE__*/_jsx("span", {
                          className: "text-muted fw-semibold",
                          style: {
                            fontSize: '0.7rem'
                          },
                          children: "Items Dispensed:"
                        }), /*#__PURE__*/_jsxs("div", {
                          className: "d-flex gap-1",
                          children: [/*#__PURE__*/_jsxs("button", {
                            type: "button",
                            className: "btn btn-xs btn-outline-dark py-0 px-1.5",
                            onClick: e => {
                              e.stopPropagation();
                              viewPastBill(s.sale_id);
                            },
                            style: {
                              fontSize: '0.65rem'
                            },
                            children: [/*#__PURE__*/_jsx("i", {
                              className: "bi bi-printer me-1"
                            }), " Reprint"]
                          }), /*#__PURE__*/_jsxs("button", {
                            type: "button",
                            className: "btn btn-xs btn-outline-danger py-0 px-1.5",
                            onClick: e => {
                              e.stopPropagation();
                              selectReturnInvoice(inv);
                              openReturnModal();
                            },
                            style: {
                              fontSize: '0.65rem'
                            },
                            children: [/*#__PURE__*/_jsx("i", {
                              className: "bi bi-arrow-return-left me-1"
                            }), " Return All"]
                          })]
                        })]
                      }), inv.items.map((it, itIdx) => /*#__PURE__*/_jsxs("div", {
                        className: "p-1.5 mb-1 bg-white rounded border d-flex justify-content-between align-items-center",
                        style: {
                          fontSize: '0.72rem'
                        },
                        children: [/*#__PURE__*/_jsxs("div", {
                          children: [/*#__PURE__*/_jsx("div", {
                            className: "fw-bold text-dark",
                            children: it.item_name
                          }), /*#__PURE__*/_jsxs("div", {
                            className: "text-muted",
                            style: {
                              fontSize: '0.65rem'
                            },
                            children: ["Batch: ", it.batch_no, " • ", it.qty, " ", it.sell_unit, " @ ₹", it.unit_mrp]
                          })]
                        }), /*#__PURE__*/_jsxs("div", {
                          className: "d-flex align-items-center gap-1",
                          children: [/*#__PURE__*/_jsx("button", {
                            type: "button",
                            className: "btn btn-xs btn-outline-success py-0 px-1",
                            onClick: () => reDispenseMedicine(it, it.qty),
                            title: "Dispense again",
                            style: {
                              fontSize: '0.68rem'
                            },
                            children: /*#__PURE__*/_jsx("i", {
                              className: "bi bi-plus"
                            })
                          }), it.remaining_units > 0 && /*#__PURE__*/_jsx("button", {
                            type: "button",
                            className: "btn btn-xs btn-outline-danger py-0 px-1",
                            onClick: () => initiateReturnFromHistory(s, it),
                            title: "Return this item",
                            style: {
                              fontSize: '0.68rem'
                            },
                            children: /*#__PURE__*/_jsx("i", {
                              className: "bi bi-arrow-return-left"
                            })
                          })]
                        })]
                      }, `inv-item-${itIdx}`))]
                    })]
                  }, `inv-${s.sale_id}`);
                })
              }), historyActiveTab === 'prescriptions' && /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "text-muted mb-2 fw-semibold",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-stethoscope text-primary me-1"
                  }), " Doctor Prescriptions from OPD Visits:"]
                }), patientHistory.doctor_prescriptions?.map((dp, dpIdx) => /*#__PURE__*/_jsxs("div", {
                  className: "history-med-card mb-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-start mb-1",
                    children: [/*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-dark",
                        style: {
                          fontSize: '0.82rem'
                        },
                        children: dp.med_name
                      }), /*#__PURE__*/_jsx("span", {
                        className: "badge bg-secondary-subtle text-secondary border",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: dp.med_type || 'Medicine'
                      })]
                    }), /*#__PURE__*/_jsx("small", {
                      className: "text-muted",
                      style: {
                        fontSize: '0.68rem'
                      },
                      children: dp.date_opd_visit
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-muted mb-1.5",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: [dp.doc_name && /*#__PURE__*/_jsxs("div", {
                      children: ["Doc: ", /*#__PURE__*/_jsxs("strong", {
                        children: ["Dr. ", dp.doc_name]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      children: ["Dose: ", dp.dosage_freq_str || dp.dosage || '1 Tab', " • Duration: ", dp.no_of_days || '5 Days']
                    }), dp.remark && /*#__PURE__*/_jsxs("div", {
                      children: ["Note: ", dp.remark]
                    })]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-xs btn-primary w-100 py-1 fw-semibold d-flex align-items-center justify-content-center gap-1 shadow-xs",
                    onClick: () => reDispenseMedicine({
                      item_name: dp.med_name,
                      qty: 10
                    }),
                    style: {
                      fontSize: '0.72rem'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-plus-circle"
                    }), " Dispense Prescribed Medicine"]
                  })]
                }, `doc-rx-${dpIdx}`))]
              }), historyActiveTab === 'summary' && /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "text-muted mb-2 fw-semibold",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: "Distinct Medicines Dispensed to Patient:"
                }), patientHistory.unique_medicines?.map((um, uIdx) => /*#__PURE__*/_jsxs("div", {
                  className: "history-med-card mb-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-start mb-1",
                    children: [/*#__PURE__*/_jsx("div", {
                      className: "fw-bold text-dark",
                      style: {
                        fontSize: '0.8rem'
                      },
                      children: um.item_name
                    }), /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-primary-subtle text-primary border",
                      style: {
                        fontSize: '0.65rem'
                      },
                      children: [um.times_dispensed, "x dispensed"]
                    })]
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-muted d-block mb-1.5",
                    style: {
                      fontSize: '0.68rem'
                    },
                    children: ["Last on ", um.last_dispensed ? um.last_dispensed.substring(0, 10) : '', " (Batch: ", um.last_batch_no, ")"]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-xs btn-outline-success w-100 py-0.5 fw-semibold d-flex align-items-center justify-content-center gap-1",
                    onClick: () => reDispenseMedicine(um, um.last_qty || 1),
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-plus-circle"
                    }), " Dispense Again"]
                  })]
                }, `uniq-med-${uIdx}`))]
              })]
            })
          })]
        })
      })]
    }), /*#__PURE__*/_jsx("div", {
      className: `checkout-drawer-overlay ${isCheckoutOpen ? 'open' : ''}`,
      onClick: () => setIsCheckoutOpen(false)
    }), /*#__PURE__*/_jsxs("div", {
      className: `checkout-drawer ${isCheckoutOpen ? 'open' : ''}`,
      id: "pos-checkout-drawer",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "checkout-drawer-header",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center gap-2",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-cash-coin text-warning fs-5"
          }), /*#__PURE__*/_jsxs("div", {
            children: [/*#__PURE__*/_jsx("h6", {
              className: "mb-0 fw-bold text-white",
              children: "Checkout Desk"
            }), /*#__PURE__*/_jsxs("small", {
              className: "text-secondary",
              style: {
                fontSize: '0.72rem'
              },
              children: ["Counter: ", /*#__PURE__*/_jsx("span", {
                className: "text-info fw-bold",
                children: store?.store_code || ''
              }), " • ", store?.store_name]
            })]
          })]
        }), /*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-sm btn-outline-light rounded-pill px-3 py-1 d-flex align-items-center gap-1.5",
          onClick: () => setIsCheckoutOpen(false),
          title: "Close Checkout Drawer (Esc)",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-x-lg"
          }), /*#__PURE__*/_jsx("span", {
            children: "Close"
          }), /*#__PURE__*/_jsx("kbd", {
            className: "ms-1",
            style: {
              background: '#334155',
              color: '#f8fafc',
              fontSize: '0.65rem',
              padding: '1px 4px',
              borderRadius: '3px'
            },
            children: "Esc"
          })]
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "checkout-drawer-body",
        children: [/*#__PURE__*/_jsxs("h5", {
          className: "fw-bold text-white mb-3 d-flex justify-content-between align-items-center",
          children: [/*#__PURE__*/_jsx("span", {
            children: "Checkout Desk"
          }), /*#__PURE__*/_jsx("span", {
            className: "fs-6 text-primary",
            children: store?.store_code || ''
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "p-2.5 rounded-3 mb-3",
          style: {
            background: 'rgba(255, 255, 255, 0.08)',
            border: '1px solid rgba(255, 255, 255, 0.15)'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-1.5",
            children: [/*#__PURE__*/_jsxs("label", {
              className: "text-white small fw-bold mb-0 d-flex align-items-center gap-1",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-tag-fill text-warning"
              }), " Whole Bill Discount"]
            }), /*#__PURE__*/_jsxs("div", {
              className: "btn-group btn-group-sm",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: `btn btn-xs py-0.5 px-2 ${wholeDiscountType === 'pct' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setWholeDiscountType('pct'),
                children: "% Percent"
              }), /*#__PURE__*/_jsx("button", {
                type: "button",
                className: `btn btn-xs py-0.5 px-2 ${wholeDiscountType === 'val' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setWholeDiscountType('val'),
                children: "₹ Fixed Value"
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "input-group input-group-sm mb-2",
            children: [/*#__PURE__*/_jsx("span", {
              className: "input-group-text bg-dark border-secondary text-warning fw-bold",
              children: wholeDiscountType === 'pct' ? '%' : '₹'
            }), /*#__PURE__*/_jsx("input", {
              type: "number",
              id: "whole-discount-input",
              ref: wholeDiscountRef,
              className: "form-control form-control-sm bg-dark text-white border-secondary fw-bold",
              min: "0",
              max: wholeDiscountType === 'pct' ? 100 : balanceAfterItemDisc,
              step: wholeDiscountType === 'pct' ? '0.5' : '1',
              placeholder: wholeDiscountType === 'pct' ? 'Enter % (F7)' : 'Enter ₹ (F7)',
              value: wholeDiscountVal || '',
              onChange: e => setWholeDiscountVal(e.target.value),
              onKeyDown: e => {
                if (e.key === 'Enter') {
                  e.preventDefault();
                  if (!isProcessing && cart.length > 0) {
                    processSale();
                  }
                }
              }
            }), Number(wholeDiscountVal) > 0 && /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-outline-danger",
              title: "Reset Whole Bill Discount",
              onClick: () => setWholeDiscountVal(0),
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-x-lg"
              })
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex flex-wrap gap-1 align-items-center",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-slate-400 me-1",
              style: {
                fontSize: '0.7rem',
                color: '#94a3b8'
              },
              children: "Presets:"
            }), wholeDiscountType === 'pct' ? [5, 10, 15, 20].map(p => /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `badge border ${Number(wholeDiscountVal) === p ? 'bg-warning text-dark border-warning' : 'bg-transparent text-white border-secondary'}`,
              style: {
                cursor: 'pointer',
                padding: '4px 8px',
                fontSize: '0.7rem'
              },
              onClick: () => setWholeDiscountVal(p),
              children: [p, "%"]
            }, p)) : [20, 50, 100, 200].map(v => /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `badge border ${Number(wholeDiscountVal) === v ? 'bg-warning text-dark border-warning' : 'bg-transparent text-white border-secondary'}`,
              style: {
                cursor: 'pointer',
                padding: '4px 8px',
                fontSize: '0.7rem'
              },
              onClick: () => setWholeDiscountVal(v),
              children: ["₹", v]
            }, v)), Number(wholeDiscountVal) > 0 && /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "badge bg-danger-subtle text-danger border border-danger ms-auto",
              style: {
                cursor: 'pointer',
                padding: '4px 6px',
                fontSize: '0.7rem'
              },
              onClick: () => setWholeDiscountVal(0),
              children: "Clear"
            })]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between mb-1.5 text-slate-300",
          style: {
            color: '#cbd5e1'
          },
          children: [/*#__PURE__*/_jsx("span", {
            children: "Gross Items Total:"
          }), /*#__PURE__*/_jsxs("span", {
            className: "fw-bold",
            children: ["₹", grossTotal.toFixed(2)]
          })]
        }), itemDiscountTotal > 0 && /*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between mb-1 text-slate-300",
          style: {
            fontSize: '0.84rem',
            color: '#94a3b8'
          },
          children: [/*#__PURE__*/_jsx("span", {
            children: "Item-Level Discounts:"
          }), /*#__PURE__*/_jsxs("span", {
            className: "text-danger",
            children: ["-₹", itemDiscountTotal.toFixed(2)]
          })]
        }), wholeDiscountAmt > 0 && /*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between mb-1 text-slate-300",
          style: {
            fontSize: '0.84rem',
            color: '#94a3b8'
          },
          children: [/*#__PURE__*/_jsxs("span", {
            children: ["Whole Bill Discount (", wholeDiscountType === 'pct' ? `${wholeDiscountVal}%` : `₹${wholeDiscountVal}`, "):"]
          }), /*#__PURE__*/_jsxs("span", {
            className: "text-danger",
            children: ["-₹", wholeDiscountAmt.toFixed(2)]
          })]
        }), discountTotal > 0 && /*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between mb-2 text-slate-300",
          style: {
            color: '#cbd5e1'
          },
          children: [/*#__PURE__*/_jsx("span", {
            children: "Total Discounts:"
          }), /*#__PURE__*/_jsxs("span", {
            className: "text-danger fw-bold",
            children: ["-₹", discountTotal.toFixed(2)]
          })]
        }), returnTotal > 0 && /*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between mb-2 text-danger fw-bold py-1 px-2 rounded",
          style: {
            background: 'rgba(244, 63, 94, 0.15)',
            border: '1px solid rgba(244, 63, 94, 0.3)'
          },
          children: [/*#__PURE__*/_jsxs("span", {
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-return-left me-1"
            }), " Less Return Credit:"]
          }), /*#__PURE__*/_jsxs("span", {
            children: ["-₹", returnTotal.toFixed(2)]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between mb-2 text-slate-300",
          style: {
            color: '#cbd5e1'
          },
          children: [/*#__PURE__*/_jsx("span", {
            children: "Round Off:"
          }), /*#__PURE__*/_jsxs("span", {
            children: ["₹", roundOff]
          })]
        }), /*#__PURE__*/_jsx("hr", {
          style: {
            borderColor: 'rgba(255,255,255,0.15)'
          }
        }), refundPayable > 0 ? /*#__PURE__*/_jsxs("div", {
          className: "p-3 rounded-3 mb-3 border border-warning",
          style: {
            background: 'rgba(245, 158, 11, 0.15)'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("span", {
              className: "fs-6 fw-bold text-white",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-wallet2 text-warning me-1"
              }), " REFUND TO PATIENT:"]
            }), /*#__PURE__*/_jsxs("span", {
              className: "fs-3 fw-bolder text-warning",
              children: ["₹", refundPayable.toLocaleString('en-IN')]
            })]
          }), /*#__PURE__*/_jsx("small", {
            className: "text-white-50 d-block mt-1",
            style: {
              fontSize: '0.72rem'
            },
            children: "Return credit exceeds new items. Refund this net difference from counter cash drawer or UPI."
          }), /*#__PURE__*/_jsxs("div", {
            className: "mt-2.5",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label text-white small fw-bold mb-1",
              children: "Refund Outflow Method"
            }), /*#__PURE__*/_jsxs("div", {
              className: "btn-group btn-group-sm w-100 mb-2",
              children: [/*#__PURE__*/_jsxs("button", {
                type: "button",
                className: `btn ${refundMode === 'Cash' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`,
                onClick: () => setRefundMode('Cash'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-cash-stack me-1"
                }), " Cash Refund"]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: `btn ${refundMode === 'UPI' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`,
                onClick: () => setRefundMode('UPI'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-qr-code me-1"
                }), " UPI / Bank Refund"]
              })]
            }), refundMode === 'UPI' && /*#__PURE__*/_jsx("input", {
              type: "text",
              className: "form-control form-control-sm bg-dark text-white border-warning",
              placeholder: "Patient UPI VPA or Bank Reference #",
              value: refundRefNo,
              onChange: e => setRefundRefNo(e.target.value)
            })]
          })]
        }) : /*#__PURE__*/_jsxs("div", {
          className: "d-flex justify-content-between align-items-center mb-4",
          children: [/*#__PURE__*/_jsx("span", {
            className: "fs-5 fw-bold text-white",
            children: "Net Payable:"
          }), /*#__PURE__*/_jsxs("span", {
            className: "fs-3 fw-bolder text-warning",
            children: ["₹", netPayable.toLocaleString('en-IN')]
          })]
        }), refundPayable <= 0 && /*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-2",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label text-white fw-bold mb-0",
              children: "Payment Method"
            }), paymentMode === 'Mixed' && /*#__PURE__*/_jsxs("span", {
              className: "badge bg-warning text-dark",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-pie-chart me-1"
              }), " Partial / Split Mode"]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "row g-2 mb-3",
            children: [/*#__PURE__*/_jsx("div", {
              className: "col-4",
              children: /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm w-100 ${paymentMode === 'Cash' ? 'btn-primary-custom' : 'btn-outline-light'}`,
                onClick: () => setPaymentMode('Cash'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-cash-stack me-1"
                }), " Cash"]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-4",
              children: /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm w-100 ${paymentMode === 'UPI' ? 'btn-primary-custom' : 'btn-outline-light'}`,
                onClick: () => setPaymentMode('UPI'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-qr-code-scan me-1"
                }), " UPI / QR"]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-4",
              children: /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm w-100 ${paymentMode === 'Mixed' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`,
                onClick: () => {
                  setPaymentMode('Mixed');
                  if (!splitCash && !splitUpi) {
                    setSplitCash('');
                    setSplitUpi('');
                  }
                },
                title: "Partial payment by Cash and UPI/Bank",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-pie-chart-fill me-1"
                }), " Split / Mixed"]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm w-100 ${paymentMode === 'Card' ? 'btn-primary-custom' : 'btn-outline-light'}`,
                onClick: () => setPaymentMode('Card'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-credit-card me-1"
                }), " Card Swipe"]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsxs("button", {
                className: `btn btn-sm w-100 ${paymentMode === 'IPD_Credit' ? 'btn-danger' : 'btn-outline-light'}`,
                onClick: () => setPaymentMode('IPD_Credit'),
                title: "Charge to IPD Patient Bill",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-hospital me-1"
                }), " IPD Bill Post"]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-12 mt-1",
              children: /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: `btn btn-sm w-100 ${paymentMode === 'Staff_Credit' ? 'btn-info text-dark fw-bold' : 'btn-outline-info'}`,
                onClick: () => setPaymentMode('Staff_Credit'),
                title: "Charge to Staff / Doctor / Customer Credit Ledger",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-person-lines-fill me-1"
                }), " Staff / Doctor / Customer Credit A/c"]
              })
            })]
          }), paymentMode === 'Staff_Credit' && /*#__PURE__*/_jsxs("div", {
            className: "card bg-dark border-info mb-3 p-2.5 rounded-3 text-white",
            style: {
              fontSize: '0.82rem'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-secondary",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "fw-bold text-info",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-person-lines-fill me-1"
                }), " Staff / Customer Credit Account"]
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-info text-dark",
                children: "Ledger Debit"
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-2",
              children: [/*#__PURE__*/_jsxs("label", {
                className: "form-label text-white-50 small mb-1",
                children: ["Select Credit Account ", /*#__PURE__*/_jsx("span", {
                  className: "text-danger",
                  children: "*"
                })]
              }), /*#__PURE__*/_jsxs("select", {
                className: "form-select form-select-sm",
                value: selectedCreditAccount ? selectedCreditAccount.account_id : '',
                onChange: e => {
                  const acc = creditAccounts.find(a => String(a.account_id) === e.target.value);
                  setSelectedCreditAccount(acc || null);
                },
                children: [/*#__PURE__*/_jsx("option", {
                  value: "",
                  children: "-- Choose Account --"
                }), creditAccounts.filter(a => a.is_active == 1).map(a => /*#__PURE__*/_jsxs("option", {
                  value: a.account_id,
                  children: [a.account_name, " (", a.account_type.toUpperCase(), ") - Dues: ₹", Number(a.current_balance || 0).toFixed(2)]
                }, a.account_id))]
              })]
            }), selectedCreditAccount && /*#__PURE__*/_jsxs("div", {
              className: "p-2 bg-secondary bg-opacity-25 rounded border border-secondary mb-1",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-white-50",
                  children: "Dept / ID:"
                }), /*#__PURE__*/_jsx("span", {
                  className: "fw-bold",
                  children: selectedCreditAccount.department || selectedCreditAccount.staff_id || 'N/A'
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-white-50",
                  children: "Current Outstanding:"
                }), /*#__PURE__*/_jsxs("span", {
                  className: `fw-bold ${Number(selectedCreditAccount.current_balance) > 0 ? 'text-warning' : 'text-success'}`,
                  children: ["₹", Number(selectedCreditAccount.current_balance || 0).toFixed(2)]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-white-50",
                  children: "Credit Limit:"
                }), /*#__PURE__*/_jsxs("span", {
                  className: "fw-bold",
                  children: ["₹", Number(selectedCreditAccount.credit_limit || 0).toFixed(2)]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between border-top border-secondary pt-1 mt-1",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-white-50",
                  children: "Balance after this bill:"
                }), /*#__PURE__*/_jsxs("span", {
                  className: "fw-bold text-info",
                  children: ["₹", (Number(selectedCreditAccount.current_balance || 0) + netPayable).toFixed(2)]
                })]
              })]
            })]
          }), paymentMode === 'IPD_Credit' && /*#__PURE__*/_jsxs("div", {
            className: "alert alert-warning py-2 mb-3",
            style: {
              fontSize: '0.78rem'
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-info-circle me-1"
            }), "Medicines will be charged directly to the patient's IPD Hospital Account and settled at discharge."]
          }), paymentMode === 'Mixed' && /*#__PURE__*/_jsxs("div", {
            className: "card bg-dark border-warning mb-3 p-2.5 rounded-3 text-white",
            style: {
              fontSize: '0.82rem'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom border-secondary",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "fw-bold text-warning",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-receipt-cutoff me-1"
                }), " Split Payment Allocation"]
              }), /*#__PURE__*/_jsxs("span", {
                className: "fw-bold",
                children: ["Total: ₹", netPayable]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "row g-2 mb-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "col-6",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label text-white-50 mb-1",
                  style: {
                    fontSize: '0.75rem'
                  },
                  children: "Cash Amount (₹)"
                }), /*#__PURE__*/_jsxs("div", {
                  className: "input-group input-group-sm",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "input-group-text bg-secondary border-0 text-white",
                    children: "₹"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm bg-dark text-white border-secondary",
                    placeholder: "e.g. 35",
                    value: splitCash,
                    onChange: e => setSplitCash(e.target.value),
                    step: "any"
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-6",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label text-white-50 mb-1",
                  style: {
                    fontSize: '0.75rem'
                  },
                  children: "UPI / Bank (₹)"
                }), /*#__PURE__*/_jsxs("div", {
                  className: "input-group input-group-sm",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "input-group-text bg-secondary border-0 text-white",
                    children: "₹"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm bg-dark text-white border-secondary",
                    placeholder: "e.g. 100",
                    value: splitUpi,
                    onChange: e => setSplitUpi(e.target.value),
                    step: "any"
                  })]
                })]
              })]
            }), Number(splitUpi) > 0 && /*#__PURE__*/_jsx("div", {
              className: "p-2 rounded bg-black bg-opacity-25 border border-secondary mb-2",
              children: /*#__PURE__*/_jsxs("div", {
                className: "row g-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-7",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label text-warning mb-0",
                    style: {
                      fontSize: '0.72rem'
                    },
                    children: ["UPI UTR / Trans. Ref # ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm bg-dark text-white border-warning",
                    placeholder: "e.g. 425109876543 (12-digit)",
                    value: upiRefNo,
                    onChange: e => setUpiRefNo(e.target.value)
                  }), /*#__PURE__*/_jsx("span", {
                    style: {
                      fontSize: '0.65rem'
                    },
                    className: "text-white-50",
                    children: "Used by Accountant for statement audit"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-5",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label text-white-50 mb-0",
                    style: {
                      fontSize: '0.72rem'
                    },
                    children: "Bank / QR"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm bg-dark text-white border-secondary",
                    placeholder: "e.g. SBI, HDFC",
                    list: "commonBankList",
                    value: bankName,
                    onChange: e => setBankName(e.target.value)
                  }), /*#__PURE__*/_jsxs("datalist", {
                    id: "commonBankList",
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "SBI"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "HDFC Bank"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "ICICI Bank"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Axis Bank"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Punjab National Bank"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Bank of Baroda"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Google Pay / PhonePe"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Paytm"
                    })]
                  })]
                })]
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between align-items-center",
                children: [/*#__PURE__*/_jsx("span", {
                  style: {
                    fontSize: '0.75rem'
                  },
                  className: "text-white-50",
                  children: "Card Swiped (if any):"
                }), /*#__PURE__*/_jsxs("div", {
                  className: "input-group input-group-sm",
                  style: {
                    width: 140
                  },
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "input-group-text bg-secondary border-0 text-white",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: "₹"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    className: "form-control form-control-sm bg-dark text-white border-secondary",
                    placeholder: "0",
                    value: splitCard,
                    onChange: e => setSplitCard(e.target.value),
                    step: "any"
                  })]
                })]
              }), Number(splitCard) > 0 && /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm bg-dark text-white border-secondary mt-1",
                placeholder: "Card Slip Auth / Ref No",
                value: cardRefNo,
                onChange: e => setCardRefNo(e.target.value)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "p-2 rounded bg-black bg-opacity-40 border border-secondary",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between mb-1",
                style: {
                  fontSize: '0.75rem'
                },
                children: [/*#__PURE__*/_jsxs("span", {
                  children: ["Allocated: ", /*#__PURE__*/_jsxs("strong", {
                    children: ["₹", totalSplitAllocated.toFixed(2)]
                  })]
                }), /*#__PURE__*/_jsxs("span", {
                  children: ["Bill Net: ", /*#__PURE__*/_jsxs("strong", {
                    children: ["₹", netPayable]
                  })]
                })]
              }), Math.abs(splitRemaining) <= 0.01 ? /*#__PURE__*/_jsxs("div", {
                className: "text-success fw-bold d-flex align-items-center gap-1",
                style: {
                  fontSize: '0.78rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-check-circle-fill"
                }), " Balanced! Ready to print bill."]
              }) : splitRemaining > 0 ? /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "text-warning fw-bold mb-1.5",
                  style: {
                    fontSize: '0.78rem'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-exclamation-circle me-1"
                  }), " Balance Remaining: ₹", splitRemaining.toFixed(2)]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex gap-1",
                  children: [/*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-xs btn-outline-warning py-0",
                    style: {
                      fontSize: '0.7rem'
                    },
                    onClick: () => setSplitUpi(String(((Number(splitUpi) || 0) + splitRemaining).toFixed(2))),
                    children: ["+ ₹", splitRemaining.toFixed(2), " to UPI"]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-xs btn-outline-info py-0",
                    style: {
                      fontSize: '0.7rem'
                    },
                    onClick: () => setSplitCash(String(((Number(splitCash) || 0) + splitRemaining).toFixed(2))),
                    children: ["+ ₹", splitRemaining.toFixed(2), " to Cash"]
                  })]
                })]
              }) : /*#__PURE__*/_jsxs("div", {
                className: "text-danger fw-bold",
                style: {
                  fontSize: '0.78rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-x-circle-fill me-1"
                }), " Overallocated by ₹", Math.abs(splitRemaining).toFixed(2)]
              })]
            })]
          }), paymentMode === 'UPI' && /*#__PURE__*/_jsxs("div", {
            className: "bg-dark p-2.5 rounded-3 mb-3 border border-secondary",
            children: [store?.upi_id && /*#__PURE__*/_jsx("div", {
              className: "text-center mb-2",
              children: /*#__PURE__*/_jsxs("small", {
                className: "text-muted d-block",
                style: {
                  fontSize: '0.75rem'
                },
                children: ["Store UPI VPA: ", /*#__PURE__*/_jsx("span", {
                  className: "text-warning fw-bold",
                  children: store?.upi_id
                })]
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "row g-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "col-7",
                children: [/*#__PURE__*/_jsxs("label", {
                  className: "form-label text-white-50 mb-1",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: ["UPI UTR / Ref No ", /*#__PURE__*/_jsx("span", {
                    className: "text-danger",
                    children: "*"
                  })]
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm bg-dark text-white border-warning",
                  placeholder: "12-digit UTR No",
                  value: upiRefNo,
                  onChange: e => setUpiRefNo(e.target.value)
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-5",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label text-white-50 mb-1",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: "Bank Name"
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm bg-dark text-white border-secondary",
                  placeholder: "e.g. SBI, HDFC",
                  value: bankName,
                  onChange: e => setBankName(e.target.value)
                })]
              })]
            }), /*#__PURE__*/_jsxs("small", {
              className: "text-white-50 d-block mt-1",
              style: {
                fontSize: '0.68rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-shield-check me-1 text-success"
              }), " Accountant will match this UTR against the hospital bank statement."]
            })]
          }), paymentMode === 'Card' && /*#__PURE__*/_jsx("div", {
            className: "bg-dark p-2.5 rounded-3 mb-3 border border-secondary",
            children: /*#__PURE__*/_jsxs("div", {
              className: "row g-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "col-7",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label text-white-50 mb-1",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: "Card Slip / Auth Ref No"
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm bg-dark text-white border-secondary",
                  placeholder: "POS Machine Auth Code",
                  value: cardRefNo,
                  onChange: e => setCardRefNo(e.target.value)
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-5",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label text-white-50 mb-1",
                  style: {
                    fontSize: '0.72rem'
                  },
                  children: "Bank / Terminal"
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm bg-dark text-white border-secondary",
                  placeholder: "e.g. HDFC EDC",
                  value: bankName,
                  onChange: e => setBankName(e.target.value)
                })]
              })]
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "mb-3",
          children: [/*#__PURE__*/_jsx("label", {
            className: "form-label text-white small fw-bold mb-1",
            children: "Invoice Print Format"
          }), /*#__PURE__*/_jsxs("div", {
            className: "btn-group btn-group-sm w-100",
            children: [/*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `btn ${printFormat === 'thermal' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`,
              onClick: () => setPrintFormat('thermal'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-receipt me-1"
              }), " Thermal (Text)"]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `btn ${printFormat === 'a5' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`,
              onClick: () => setPrintFormat('a5'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-file-earmark-pdf me-1"
              }), " A5 Landscape (PDF)"]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: `btn ${printFormat === 'a4' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light'}`,
              onClick: () => setPrintFormat('a4'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-file-earmark-pdf me-1"
              }), " A4 (PDF)"]
            })]
          })]
        }), /*#__PURE__*/_jsx("button", {
          className: `btn ${refundPayable > 0 ? 'btn-warning text-dark fw-bold' : 'btn-success-custom'} w-100 py-2.5 fs-6`,
          onClick: processSale,
          disabled: isProcessing || cart.length === 0 && returnCart.length === 0 || refundPayable === 0 && paymentMode === 'Mixed' && Math.abs(splitRemaining) > 0.01,
          children: isProcessing ? /*#__PURE__*/_jsxs("span", {
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-hourglass-split me-2"
            }), " Generating Bill & Credit Note..."]
          }) : refundPayable === 0 && paymentMode === 'Mixed' && Math.abs(splitRemaining) > 0.01 ? /*#__PURE__*/_jsxs("span", {
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-exclamation-triangle me-2"
            }), " Allocate Balance (₹", splitRemaining > 0 ? splitRemaining.toFixed(2) + ' left' : Math.abs(splitRemaining).toFixed(2) + ' over', ")"]
          }) : refundPayable > 0 ? /*#__PURE__*/_jsxs("span", {
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-return-left me-2"
            }), " Process Return & Refund ₹", refundPayable]
          }) : returnCart.length > 0 ? /*#__PURE__*/_jsxs("span", {
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-left-right me-2"
            }), " Complete Exchange Bill & Print (", printFormat === 'a5' ? 'A5 LANDSCAPE' : printFormat.toUpperCase(), ")"]
          }) : /*#__PURE__*/_jsxs("span", {
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-printer me-2"
            }), " Complete Sale & Print (", printFormat === 'a5' ? 'A5 LANDSCAPE' : printFormat.toUpperCase(), ")"]
          })
        })]
      })]
    }), /*#__PURE__*/_jsx("div", {
      className: `print-drawer-overlay ${isPrintOpen ? 'open' : ''}`,
      onClick: () => setIsPrintOpen(false)
    }), /*#__PURE__*/_jsx("div", {
      className: `print-drawer ${isPrintOpen ? 'open' : ''}`,
      id: "pos-print-drawer",
      children: lastInvoice && /*#__PURE__*/_jsxs(_Fragment, {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "print-drawer-header",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-receipt text-warning fs-5"
            }), /*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsx("h6", {
                className: "mb-0 fw-bold text-white",
                children: "Invoice & Print Desk"
              }), /*#__PURE__*/_jsxs("small", {
                className: "text-secondary",
                style: {
                  fontSize: '0.72rem'
                },
                children: ["Bill: ", /*#__PURE__*/_jsx("span", {
                  className: "text-info fw-bold",
                  children: lastInvoice.sale.invoice_no
                }), lastInvoice.sale.abdm_care_context_ref && /*#__PURE__*/_jsxs("span", {
                  className: "ms-2 text-success",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-shield-fill-check me-0.5"
                  }), " ABDM Linked"]
                })]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-1.5 flex-wrap",
            children: [/*#__PURE__*/_jsxs("button", {
              className: "btn btn-xs btn-outline-success text-white py-1 px-2",
              onClick: () => viewAbdmBundle(lastInvoice.sale.sale_id),
              title: "View official ABDM FHIR R4 MedicationDispense JSON Document Bundle",
              style: {
                fontSize: '0.72rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-filetype-json me-1"
              }), " FHIR R4"]
            }), /*#__PURE__*/_jsxs("div", {
              className: "btn-group btn-group-sm",
              children: [/*#__PURE__*/_jsxs("button", {
                className: `btn btn-xs py-1 px-2 ${printFormat === 'thermal' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setPrintFormat('thermal'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-receipt me-1"
                }), " Thermal (Text)"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn btn-xs py-1 px-2 ${printFormat === 'a5' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setPrintFormat('a5'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-pdf me-1"
                }), " A5 Landscape (PDF)"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn btn-xs py-1 px-2 ${printFormat === 'a4' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setPrintFormat('a4'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-pdf me-1"
                }), " A4 (PDF)"]
              })]
            }), printFormat === 'thermal' ? /*#__PURE__*/_jsxs("button", {
              className: "btn btn-sm btn-warning text-dark fw-bold py-1 px-2.5 ms-0.5",
              onClick: printThermalReceipt,
              title: "Print on 80mm Thermal Receipt Printer",
              style: {
                fontSize: '0.75rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-printer-fill me-1"
              }), " Print Thermal"]
            }) : /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-outline-light py-1 px-2 text-white",
                onClick: () => downloadPdf(printFormat),
                title: `Download ${printFormat === 'a5' ? 'A5 Landscape' : printFormat.toUpperCase()} PDF Document`,
                style: {
                  fontSize: '0.75rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-download me-1 text-info"
                }), " PDF"]
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-primary py-1 px-2.5 ms-0.5 fw-bold",
                onClick: () => printPdf(printFormat),
                title: `Print Vector-Clean ${printFormat === 'a5' ? 'A5 Landscape' : printFormat.toUpperCase()} PDF`,
                style: {
                  fontSize: '0.75rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-printer me-1"
                }), " Print PDF (", printFormat === 'a5' ? 'A5 LANDSCAPE' : printFormat.toUpperCase(), ")"]
              })]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-light rounded-pill px-2.5 py-1 ms-1 d-flex align-items-center gap-1",
              onClick: () => setIsPrintOpen(false),
              title: "Close Print Drawer (Esc)",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-x-lg"
              }), /*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#334155',
                  color: '#f8fafc',
                  fontSize: '0.62rem',
                  padding: '1px 3px',
                  borderRadius: '3px'
                },
                children: "Esc"
              })]
            })]
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "print-drawer-body",
          children: /*#__PURE__*/_jsx("div", {
            className: "print-paper-card",
            children: /*#__PURE__*/_jsxs("div", {
              id: "printable-container",
              children: [printFormat === 'thermal' && /*#__PURE__*/_jsxs("div", {
                className: "p-2 text-center",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center mb-2 mx-auto",
                  style: {
                    maxWidth: '360px'
                  },
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "badge bg-dark text-warning border border-secondary",
                    style: {
                      fontSize: '0.72rem'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-receipt me-1"
                    }), " 80mm Thermal Receipt (Plain Text)"]
                  }), /*#__PURE__*/_jsx("div", {
                    children: /*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-xs btn-warning text-dark fw-bold py-0.5 px-2.5",
                      onClick: printThermalReceipt,
                      title: "Print Thermal 80mm Receipt",
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-printer-fill me-1"
                      }), " Print Thermal"]
                    })
                  })]
                }), /*#__PURE__*/_jsx("pre", {
                  id: "thermal-receipt-preview",
                  style: {
                    fontFamily: "'Courier New', Courier, monospace",
                    fontSize: '11px',
                    lineHeight: '1.28',
                    backgroundColor: '#ffffff',
                    color: '#000000',
                    padding: '16px',
                    border: '1px solid #cbd5e1',
                    borderRadius: '6px',
                    boxShadow: '0 4px 15px rgba(0,0,0,0.06)',
                    width: '360px',
                    maxWidth: '100%',
                    margin: '0 auto',
                    textAlign: 'left',
                    whiteSpace: 'pre-wrap',
                    wordBreak: 'break-all'
                  },
                  children: generateThermalReceiptText(lastInvoice)
                })]
              }), printFormat === 'a5' && /*#__PURE__*/_jsxs("div", {
                className: "print-a5-invoice",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center bg-primary-subtle border border-primary-subtle rounded px-2.5 py-1.5 mb-2.5 no-print",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex align-items-center gap-2",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-file-earmark-pdf-fill text-danger fs-5"
                    }), /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-dark",
                        style: {
                          fontSize: '0.78rem'
                        },
                        children: "A5 Landscape Tax Invoice (PDF Ready)"
                      }), /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.68rem'
                        },
                        children: "Direct server vector PDF (Landscape: 210 × 148 mm) • Zero clipped columns"
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex gap-1.5",
                    children: [/*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-outline-primary py-1 px-2",
                      onClick: () => downloadPdf('a5'),
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-download me-1"
                      }), " Download PDF"]
                    }), /*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-primary py-1 px-2.5 fw-bold",
                      onClick: () => printPdf('a5'),
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-printer me-1"
                      }), " Print PDF (A5 Landscape)"]
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-start border-bottom pb-2 mb-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsx("h6", {
                      className: "fw-bold mb-0 text-primary",
                      children: lastInvoice.sale.store_name
                    }), /*#__PURE__*/_jsxs("div", {
                      style: {
                        fontSize: '10px'
                      },
                      children: [lastInvoice.sale.building_name, ", ", lastInvoice.sale.floor_no, ", ", lastInvoice.sale.store_address]
                    }), /*#__PURE__*/_jsxs("div", {
                      style: {
                        fontSize: '10px'
                      },
                      children: [/*#__PURE__*/_jsx("strong", {
                        children: "DL 20B:"
                      }), " ", lastInvoice.sale.drug_license_no_20b, " | ", /*#__PURE__*/_jsx("strong", {
                        children: "DL 21B:"
                      }), " ", lastInvoice.sale.drug_license_no_21b, lastInvoice.sale.abdm_hfr_id && /*#__PURE__*/_jsxs("span", {
                        children: [" | ", /*#__PURE__*/_jsx("strong", {
                          children: "HFR ID:"
                        }), " ", lastInvoice.sale.abdm_hfr_id]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      style: {
                        fontSize: '10px'
                      },
                      children: [/*#__PURE__*/_jsx("strong", {
                        children: "GSTIN:"
                      }), " ", lastInvoice.sale.gstin, " | ", /*#__PURE__*/_jsx("strong", {
                        children: "Phone:"
                      }), " ", lastInvoice.sale.contact_phone]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-end",
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "badge bg-dark text-white mb-1",
                      children: "RETAIL TAX INVOICE"
                    }), /*#__PURE__*/_jsx("div", {
                      className: "fw-bold",
                      style: {
                        fontSize: '12px'
                      },
                      children: lastInvoice.sale.invoice_no
                    }), /*#__PURE__*/_jsx("div", {
                      className: "text-muted",
                      style: {
                        fontSize: '10px'
                      },
                      children: lastInvoice.sale.sale_date
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-1 mb-2 p-1.5 bg-light rounded",
                  style: {
                    fontSize: '10px'
                  },
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-6",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Patient:"
                    }), " ", lastInvoice.sale.patient_name, " ", lastInvoice.sale.uhid ? `(${lastInvoice.sale.uhid})` : '']
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-6",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Prescribed By:"
                    }), " ", lastInvoice.sale.doctor_name]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-6",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Age/Gender:"
                    }), " ", lastInvoice.sale.age || 'N/A', " / ", lastInvoice.sale.gender || 'N/A']
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-6",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Payment Mode:"
                    }), " ", lastInvoice.sale.payment_mode]
                  }), (lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && /*#__PURE__*/_jsxs("div", {
                    className: "col-12 text-success fw-bold",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "ABHA:"
                    }), " ", lastInvoice.sale.abha_id || 'N/A', " (", lastInvoice.sale.abha_address || 'N/A', ")", lastInvoice.sale.abdm_care_context_ref && /*#__PURE__*/_jsxs("span", {
                      children: [" | ", /*#__PURE__*/_jsx("strong", {
                        children: "Care Context:"
                      }), " ", lastInvoice.sale.abdm_care_context_ref]
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("table", {
                  className: "table table-bordered table-sm mb-2",
                  style: {
                    fontSize: '10px'
                  },
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-light",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        children: "#"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Medicine Name"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "HSN"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Batch"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Exp"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Qty & Unit"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Unit MRP (₹)"
                      }), /*#__PURE__*/_jsx("th", {
                        className: "text-end",
                        children: "Amount (₹)"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: lastInvoice.items.length === 0 ? /*#__PURE__*/_jsx("tr", {
                      children: /*#__PURE__*/_jsx("td", {
                        colSpan: 8,
                        className: "text-center text-muted py-2",
                        children: "No medicines dispensed on this bill (Return / Refund Only)"
                      })
                    }) : lastInvoice.items.map((it, i) => /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("td", {
                        children: i + 1
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "fw-bold",
                        children: [it.item_name, it.snomed_ct_code && /*#__PURE__*/_jsxs("div", {
                          className: "text-muted",
                          style: {
                            fontSize: '8px'
                          },
                          children: ["SNOMED: ", it.snomed_ct_code]
                        }), Number(it.units_per_pack) > 1 && /*#__PURE__*/_jsxs("div", {
                          className: "text-muted fw-normal",
                          style: {
                            fontSize: '8px'
                          },
                          children: ["Pack: 1 Strip = ", it.units_per_pack, " Tabs"]
                        })]
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.hsn_code
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.batch_no
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.expiry_date.substring(0, 7)
                      }), /*#__PURE__*/_jsxs("td", {
                        children: [/*#__PURE__*/_jsx("span", {
                          className: "fw-bold",
                          children: it.sell_unit === 'Tablet' ? `${it.total_units} Tab` : it.sell_unit === 'Combo' ? `${it.qty} Str + ${it.loose_qty} Tab` : Number(it.units_per_pack) > 1 ? `${it.qty} Strip` : `${it.qty} ${it.unit_pack || 'Unit'}`
                        }), Number(it.units_per_pack) > 1 && it.sell_unit !== 'Tablet' && /*#__PURE__*/_jsxs("div", {
                          className: "text-muted",
                          style: {
                            fontSize: '8px'
                          },
                          children: ["(", it.total_units, " Tabs)"]
                        })]
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.sell_unit === 'Tablet' ? /*#__PURE__*/_jsxs("span", {
                          children: ["₹", it.unit_price, " ", /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/tab"
                          })]
                        }) : /*#__PURE__*/_jsxs("span", {
                          children: ["₹", it.unit_mrp, " ", Number(it.units_per_pack) > 1 ? /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/strip"
                          }) : '']
                        })
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "text-end fw-bold",
                        children: ["₹", it.total_amount]
                      })]
                    }, i))
                  })]
                }), lastInvoice.return_items && lastInvoice.return_items.length > 0 && /*#__PURE__*/_jsxs("div", {
                  className: "mb-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-center bg-danger-subtle px-2 py-1 rounded-top border border-bottom-0 border-danger-subtle text-danger",
                    style: {
                      fontSize: '10px'
                    },
                    children: [/*#__PURE__*/_jsxs("span", {
                      className: "fw-bold",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-arrow-return-left me-1"
                      }), " RETURNED / EXCHANGED MEDICINES"]
                    }), lastInvoice.credit_note && /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-danger",
                      children: ["Credit Note: ", lastInvoice.credit_note.credit_note_no]
                    })]
                  }), /*#__PURE__*/_jsxs("table", {
                    className: "table table-bordered table-sm mb-0 text-danger border-danger-subtle",
                    style: {
                      fontSize: '9px'
                    },
                    children: [/*#__PURE__*/_jsx("thead", {
                      className: "table-danger",
                      children: /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("th", {
                          children: "#"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Medicine Name"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Batch"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Original Bill"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Condition"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Returned Qty"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Credit Rate (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          className: "text-end",
                          children: "Credit Total (₹)"
                        })]
                      })
                    }), /*#__PURE__*/_jsx("tbody", {
                      children: lastInvoice.return_items.map((it, i) => /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("td", {
                          children: i + 1
                        }), /*#__PURE__*/_jsx("td", {
                          className: "fw-bold text-decoration-line-through",
                          children: it.item_name
                        }), /*#__PURE__*/_jsx("td", {
                          children: it.batch_no
                        }), /*#__PURE__*/_jsx("td", {
                          children: /*#__PURE__*/_jsx("code", {
                            children: it.ref_invoice_no || 'N/A'
                          })
                        }), /*#__PURE__*/_jsx("td", {
                          children: /*#__PURE__*/_jsx("span", {
                            className: "badge bg-secondary",
                            children: it.return_condition
                          })
                        }), /*#__PURE__*/_jsxs("td", {
                          className: "fw-bold",
                          children: ["-", it.sell_unit === 'Tablet' ? `${it.total_units} Tab` : `${it.qty} ${it.unit_pack || 'Unit'}`]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["₹", it.unit_price]
                        }), /*#__PURE__*/_jsxs("td", {
                          className: "text-end fw-bold",
                          children: ["-₹", Math.abs(Number(it.total_amount)).toFixed(2)]
                        })]
                      }, `a5-ret-${i}`))
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center border-top pt-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    style: {
                      fontSize: '9px'
                    },
                    children: ["R.Ph: ", lastInvoice.sale.registered_pharmacist_name || 'Pharmacist', " (", lastInvoice.sale.pharmacist_reg_no, ")", lastInvoice.sale.pharmacist_hpr_id && /*#__PURE__*/_jsxs("span", {
                      children: [" | HPR ID: ", lastInvoice.sale.pharmacist_hpr_id]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-end",
                    children: [/*#__PURE__*/_jsxs("div", {
                      style: {
                        fontSize: '10px'
                      },
                      className: "text-muted",
                      children: ["Gross: ₹", lastInvoice.sale.gross_amount, Number(lastInvoice.sale.discount_amount) > 0 && /*#__PURE__*/_jsxs("span", {
                        className: "text-danger ms-2",
                        children: ["Disc: -₹", lastInvoice.sale.discount_amount]
                      }), Number(lastInvoice.sale.return_amount) > 0 && /*#__PURE__*/_jsxs("span", {
                        className: "text-danger fw-bold ms-2",
                        children: ["Return Credit: -₹", lastInvoice.sale.return_amount]
                      })]
                    }), /*#__PURE__*/_jsx("span", {
                      className: "fs-6 fw-bold text-primary",
                      children: Number(lastInvoice.sale.refund_amount) > 0 ? `NET REFUND: ₹${lastInvoice.sale.refund_amount}` : `TOTAL: ₹${lastInvoice.sale.net_amount}`
                    }), Number(lastInvoice.sale.refund_amount) > 0 && /*#__PURE__*/_jsxs("div", {
                      className: "text-danger fw-bold small",
                      children: ["Refund Paid via ", lastInvoice.sale.refund_mode, " ", lastInvoice.sale.refund_ref_no ? `[Ref: ${lastInvoice.sale.refund_ref_no}]` : '']
                    }), /*#__PURE__*/_jsxs("div", {
                      style: {
                        fontSize: '9px'
                      },
                      className: "text-muted mt-0.5",
                      children: [/*#__PURE__*/_jsxs("span", {
                        children: ["Settlement: ", /*#__PURE__*/_jsx("strong", {
                          children: lastInvoice.sale.payment_mode
                        })]
                      }), Number(lastInvoice.sale.cash_paid) > 0 && /*#__PURE__*/_jsxs("span", {
                        className: "ms-1.5",
                        children: ["Cash: ₹", lastInvoice.sale.cash_paid]
                      }), Number(lastInvoice.sale.upi_paid) > 0 && /*#__PURE__*/_jsxs("span", {
                        className: "ms-1.5",
                        children: ["UPI: ₹", lastInvoice.sale.upi_paid, " ", lastInvoice.sale.upi_ref_no ? `(UTR: ${lastInvoice.sale.upi_ref_no})` : '']
                      }), Number(lastInvoice.sale.card_paid) > 0 && /*#__PURE__*/_jsxs("span", {
                        className: "ms-1.5",
                        children: ["Card: ₹", lastInvoice.sale.card_paid]
                      }), lastInvoice.sale.is_bank_reconciled == 1 && /*#__PURE__*/_jsx("span", {
                        className: "ms-1.5 text-success fw-bold",
                        children: "✓ Audited"
                      })]
                    })]
                  })]
                })]
              }), printFormat === 'a4' && /*#__PURE__*/_jsxs("div", {
                className: "print-a4-invoice",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center bg-primary-subtle border border-primary-subtle rounded px-3 py-1.5 mb-3 no-print",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex align-items-center gap-2",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-file-earmark-pdf-fill text-danger fs-5"
                    }), /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-dark",
                        style: {
                          fontSize: '0.8rem'
                        },
                        children: "A4 Hospital GST Tax Invoice (PDF Ready)"
                      }), /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.68rem'
                        },
                        children: "Server-rendered vector PDF • Zero clipped columns"
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex gap-1.5",
                    children: [/*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-outline-primary py-1 px-2.5",
                      onClick: () => downloadPdf('a4'),
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-download me-1"
                      }), " Download PDF"]
                    }), /*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-primary py-1 px-2.5 fw-bold",
                      onClick: () => printPdf('a4'),
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-printer me-1"
                      }), " Print PDF"]
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center border-bottom pb-3 mb-3",
                  children: [/*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsx("h4", {
                      className: "fw-bold text-primary mb-1",
                      children: lastInvoice.sale.store_name
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-muted",
                      children: [lastInvoice.sale.building_name, ", ", lastInvoice.sale.floor_no, ", ", lastInvoice.sale.store_address]
                    }), /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("strong", {
                        children: "Drug License 20-B:"
                      }), " ", lastInvoice.sale.drug_license_no_20b, " | ", /*#__PURE__*/_jsx("strong", {
                        children: "21-B:"
                      }), " ", lastInvoice.sale.drug_license_no_21b, " | ", /*#__PURE__*/_jsx("strong", {
                        children: "FSSAI:"
                      }), " ", lastInvoice.sale.fssai_no || 'N/A']
                    }), /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("strong", {
                        children: "GSTIN:"
                      }), " ", lastInvoice.sale.gstin, " | ", /*#__PURE__*/_jsx("strong", {
                        children: "State:"
                      }), " ", lastInvoice.sale.state_name, " (Code: ", lastInvoice.sale.state_code, ")", lastInvoice.sale.abdm_hfr_id && /*#__PURE__*/_jsxs("span", {
                        children: [" | ", /*#__PURE__*/_jsx("strong", {
                          children: "ABDM HFR Facility ID:"
                        }), " ", lastInvoice.sale.abdm_hfr_id]
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-end",
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "badge bg-primary fs-6 px-3 py-1.5 mb-1",
                      children: "HOSPITAL GST TAX INVOICE"
                    }), /*#__PURE__*/_jsx("h5", {
                      className: "fw-bold mb-0 text-dark",
                      children: lastInvoice.sale.invoice_no
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-muted small",
                      children: ["Date & Time: ", lastInvoice.sale.sale_date]
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2 mb-3 p-2 bg-light rounded border",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-4",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Patient Name:"
                    }), " ", lastInvoice.sale.patient_name]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-4",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "UHID No:"
                    }), " ", /*#__PURE__*/_jsx("code", {
                      className: "text-primary fw-bold",
                      children: lastInvoice.sale.uhid || 'Walk-in'
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-4",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Phone:"
                    }), " ", lastInvoice.sale.patient_mobile || 'N/A']
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-4",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Age / Gender:"
                    }), " ", lastInvoice.sale.age || 'N/A', " / ", lastInvoice.sale.gender || 'N/A']
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-4",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Consultant Doctor:"
                    }), " ", lastInvoice.sale.doctor_name]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-4",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Doctor Reg No:"
                    }), " ", lastInvoice.sale.doctor_reg_no || 'N/A']
                  }), (lastInvoice.sale.abha_id || lastInvoice.sale.abha_address) && /*#__PURE__*/_jsxs("div", {
                    className: "col-12 bg-success-subtle p-1.5 rounded text-success fw-bold border border-success mt-1",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-shield-fill-check me-1"
                    }), " ABDM Linked: ABHA Number: ", lastInvoice.sale.abha_id || 'N/A', " | ABHA Address: ", lastInvoice.sale.abha_address || 'N/A', " | Care Context: ", lastInvoice.sale.abdm_care_context_ref || 'N/A']
                  }), lastInvoice.sale.ipd_id && /*#__PURE__*/_jsxs("div", {
                    className: "col-12 text-danger fw-bold border-top pt-1 mt-1",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-hospital me-1"
                    }), " IPD Admission #", lastInvoice.sale.ipd_id, " • Ward: ", lastInvoice.sale.ward_name || 'General', " • Bed: ", lastInvoice.sale.bed_no || 'Bed', " (Charged to IPD Running Bill)"]
                  })]
                }), /*#__PURE__*/_jsxs("table", {
                  className: "table table-bordered mb-3",
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-light",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        style: {
                          width: 40
                        },
                        children: "#"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Medicine & Formulation"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "HSN"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Batch No"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Exp Date"
                      }), /*#__PURE__*/_jsx("th", {
                        style: {
                          width: 105
                        },
                        children: "Qty & Unit"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Rate (₹)"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Taxable (₹)"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "CGST (₹)"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "SGST (₹)"
                      }), /*#__PURE__*/_jsx("th", {
                        className: "text-end",
                        children: "Total (₹)"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: lastInvoice.items.length === 0 ? /*#__PURE__*/_jsx("tr", {
                      children: /*#__PURE__*/_jsx("td", {
                        colSpan: 11,
                        className: "text-center text-muted py-3",
                        children: "No medicines dispensed on this bill (Pure Sales Return & Customer Refund)"
                      })
                    }) : lastInvoice.items.map((it, i) => /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("td", {
                        children: i + 1
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "fw-bold",
                        children: [it.item_name, it.snomed_ct_code && /*#__PURE__*/_jsxs("div", {
                          className: "text-muted fw-normal",
                          style: {
                            fontSize: '9px'
                          },
                          children: ["SNOMED-CT: ", /*#__PURE__*/_jsx("code", {
                            children: it.snomed_ct_code
                          }), " (", it.snomed_display || '', ")"]
                        }), Number(it.units_per_pack) > 1 && /*#__PURE__*/_jsxs("div", {
                          className: "text-muted fw-normal",
                          style: {
                            fontSize: '9px'
                          },
                          children: ["Packing: 1 Strip of ", it.units_per_pack, " Tablets"]
                        })]
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.hsn_code
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.batch_no
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.expiry_date
                      }), /*#__PURE__*/_jsxs("td", {
                        children: [/*#__PURE__*/_jsx("span", {
                          className: "fw-bold",
                          children: it.sell_unit === 'Tablet' ? `${it.total_units} Tab` : it.sell_unit === 'Combo' ? `${it.qty} Str + ${it.loose_qty} Tab` : Number(it.units_per_pack) > 1 ? `${it.qty} Strip` : `${it.qty} ${it.unit_pack || 'Unit'}`
                        }), Number(it.units_per_pack) > 1 && it.sell_unit !== 'Tablet' && /*#__PURE__*/_jsxs("div", {
                          className: "text-muted",
                          style: {
                            fontSize: '9px'
                          },
                          children: ["(", it.total_units, " Tablets)"]
                        })]
                      }), /*#__PURE__*/_jsx("td", {
                        children: it.sell_unit === 'Tablet' ? /*#__PURE__*/_jsxs("span", {
                          children: ["₹", it.unit_price, " ", /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/tab"
                          })]
                        }) : /*#__PURE__*/_jsxs("span", {
                          children: ["₹", it.unit_mrp, " ", Number(it.units_per_pack) > 1 ? /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/strip"
                          }) : '']
                        })
                      }), /*#__PURE__*/_jsxs("td", {
                        children: ["₹", it.taxable_value]
                      }), /*#__PURE__*/_jsxs("td", {
                        children: ["₹", it.cgst_amount]
                      }), /*#__PURE__*/_jsxs("td", {
                        children: ["₹", it.sgst_amount]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "text-end fw-bold",
                        children: ["₹", it.total_amount]
                      })]
                    }, i))
                  })]
                }), lastInvoice.return_items && lastInvoice.return_items.length > 0 && /*#__PURE__*/_jsxs("div", {
                  className: "mb-3",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-center bg-danger-subtle p-2 rounded-top border border-bottom-0 border-danger text-danger",
                    children: [/*#__PURE__*/_jsxs("span", {
                      className: "fw-bold",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-arrow-return-left me-1"
                      }), " GST CREDIT NOTE / RETURNED & EXCHANGED MEDICINES"]
                    }), lastInvoice.credit_note && /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-danger fs-7",
                      children: ["Credit Note No: ", lastInvoice.credit_note.credit_note_no, " (", lastInvoice.credit_note.return_date, ")"]
                    })]
                  }), /*#__PURE__*/_jsxs("table", {
                    className: "table table-bordered mb-0 text-danger border-danger",
                    children: [/*#__PURE__*/_jsx("thead", {
                      className: "table-danger",
                      children: /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("th", {
                          style: {
                            width: 40
                          },
                          children: "#"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Returned Medicine & Formulation"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "HSN"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Batch No"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Original Invoice"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Condition / Reason"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 105
                          },
                          children: "Qty Returned"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Credit Rate (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Taxable (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "CGST (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "SGST (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          className: "text-end",
                          children: "Credit Total (₹)"
                        })]
                      })
                    }), /*#__PURE__*/_jsx("tbody", {
                      children: lastInvoice.return_items.map((it, i) => /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("td", {
                          children: i + 1
                        }), /*#__PURE__*/_jsx("td", {
                          className: "fw-bold text-decoration-line-through",
                          children: it.item_name
                        }), /*#__PURE__*/_jsx("td", {
                          children: it.hsn_code
                        }), /*#__PURE__*/_jsx("td", {
                          children: it.batch_no
                        }), /*#__PURE__*/_jsx("td", {
                          children: /*#__PURE__*/_jsx("code", {
                            children: it.ref_invoice_no || 'N/A'
                          })
                        }), /*#__PURE__*/_jsxs("td", {
                          children: [/*#__PURE__*/_jsx("span", {
                            className: "badge bg-secondary me-1",
                            children: it.return_condition
                          }), /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: it.return_reason
                          })]
                        }), /*#__PURE__*/_jsx("td", {
                          children: /*#__PURE__*/_jsxs("span", {
                            className: "fw-bold",
                            children: ["-", it.sell_unit === 'Tablet' ? `${it.total_units} Tab` : `${it.qty} ${it.unit_pack || 'Unit'}`]
                          })
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["₹", it.unit_price]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["-₹", Math.abs(Number(it.taxable_value)).toFixed(2)]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["-₹", Math.abs(Number(it.cgst_amount)).toFixed(2)]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["-₹", Math.abs(Number(it.sgst_amount)).toFixed(2)]
                        }), /*#__PURE__*/_jsxs("td", {
                          className: "text-end fw-bold",
                          children: ["-₹", Math.abs(Number(it.total_amount)).toFixed(2)]
                        })]
                      }, `a4-ret-${i}`))
                    })]
                  })]
                }), lastInvoice.hsn_summary && lastInvoice.hsn_summary.length > 0 && /*#__PURE__*/_jsxs("div", {
                  className: "mb-3",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "GST Tax Breakdown"
                  }), /*#__PURE__*/_jsxs("table", {
                    className: "table table-sm table-bordered text-center",
                    style: {
                      fontSize: '10px'
                    },
                    children: [/*#__PURE__*/_jsx("thead", {
                      className: "table-light",
                      children: /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("th", {
                          children: "HSN Code"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "GST Rate"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Taxable Value (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "CGST (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "SGST (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          children: "Total Tax (₹)"
                        })]
                      })
                    }), /*#__PURE__*/_jsx("tbody", {
                      children: lastInvoice.hsn_summary.map((h, idx) => /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("td", {
                          children: h.hsn_code
                        }), /*#__PURE__*/_jsxs("td", {
                          children: [h.gst_rate, "%"]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["₹", h.taxable_value.toFixed(2)]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["₹", h.cgst_amount.toFixed(2)]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["₹", h.sgst_amount.toFixed(2)]
                        }), /*#__PURE__*/_jsxs("td", {
                          className: "fw-bold",
                          children: ["₹", h.total_tax.toFixed(2)]
                        })]
                      }, idx))
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-3 border-top pt-3",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-7",
                    children: [/*#__PURE__*/_jsxs("small", {
                      className: "text-muted d-block",
                      children: [/*#__PURE__*/_jsx("strong", {
                        children: "Registered Pharmacist:"
                      }), " ", lastInvoice.sale.registered_pharmacist_name || 'Pharmacist', " (Reg No: ", lastInvoice.sale.pharmacist_reg_no || 'N/A', ")", lastInvoice.sale.pharmacist_hpr_id && /*#__PURE__*/_jsxs("span", {
                        children: [" | ", /*#__PURE__*/_jsx("strong", {
                          children: "HPR ID:"
                        }), " ", lastInvoice.sale.pharmacist_hpr_id]
                      })]
                    }), /*#__PURE__*/_jsx("small", {
                      className: "text-muted d-block mt-1",
                      children: lastInvoice.sale.terms_conditions || '1. Goods once sold are returnable only as per Drug Rules.\n2. Store medicines below 25°C.'
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "mt-2 p-2 bg-light rounded border",
                      style: {
                        fontSize: '11px'
                      },
                      children: [/*#__PURE__*/_jsxs("div", {
                        className: "fw-bold text-dark mb-1",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-wallet2 me-1"
                        }), " Payment Settlement:"]
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "d-flex flex-wrap gap-2 align-items-center mb-1",
                        children: [/*#__PURE__*/_jsx("span", {
                          className: "badge bg-secondary",
                          children: lastInvoice.sale.payment_mode
                        }), Number(lastInvoice.sale.cash_paid) > 0 && /*#__PURE__*/_jsxs("span", {
                          children: [/*#__PURE__*/_jsx("strong", {
                            children: "Cash:"
                          }), " ₹", lastInvoice.sale.cash_paid]
                        }), Number(lastInvoice.sale.upi_paid) > 0 && /*#__PURE__*/_jsxs("span", {
                          children: [/*#__PURE__*/_jsx("strong", {
                            children: "UPI / Bank:"
                          }), " ₹", lastInvoice.sale.upi_paid, lastInvoice.sale.upi_ref_no && /*#__PURE__*/_jsxs("code", {
                            className: "text-primary ms-1",
                            children: ["[UTR: ", lastInvoice.sale.upi_ref_no, "]"]
                          }), lastInvoice.sale.bank_name && /*#__PURE__*/_jsxs("span", {
                            className: "text-muted ms-1",
                            children: ["(", lastInvoice.sale.bank_name, ")"]
                          })]
                        }), Number(lastInvoice.sale.card_paid) > 0 && /*#__PURE__*/_jsxs("span", {
                          children: [/*#__PURE__*/_jsx("strong", {
                            children: "Card:"
                          }), " ₹", lastInvoice.sale.card_paid, " ", lastInvoice.sale.card_ref_no ? `[Ref: ${lastInvoice.sale.card_ref_no}]` : '']
                        }), Number(lastInvoice.sale.credit_amount) > 0 && /*#__PURE__*/_jsxs("span", {
                          children: [/*#__PURE__*/_jsx("strong", {
                            children: "Hospital Credit:"
                          }), " ₹", lastInvoice.sale.credit_amount]
                        })]
                      }), Number(lastInvoice.sale.refund_amount) > 0 && /*#__PURE__*/_jsx("div", {
                        className: "mb-1",
                        children: /*#__PURE__*/_jsxs("span", {
                          className: "badge bg-danger-subtle text-danger border border-danger",
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-cash-stack me-1"
                          }), " Refund Issued: ₹", lastInvoice.sale.refund_amount, " via ", lastInvoice.sale.refund_mode, " ", lastInvoice.sale.refund_ref_no ? `[Ref: ${lastInvoice.sale.refund_ref_no}]` : '']
                        })
                      }), /*#__PURE__*/_jsx("div", {
                        children: lastInvoice.sale.is_bank_reconciled == 1 ? /*#__PURE__*/_jsxs("span", {
                          className: "badge bg-success-subtle text-success border border-success",
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-patch-check-fill me-1"
                          }), " Bank Statement Reconciled & Audited"]
                        }) : /*#__PURE__*/_jsxs("span", {
                          className: "badge bg-warning-subtle text-dark border border-warning",
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-clock-history me-1"
                          }), " Bank Statement: Pending Audit"]
                        })
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-5",
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between mb-1",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "Gross Items Total:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["₹", lastInvoice.sale.gross_amount]
                      })]
                    }), Number(lastInvoice.sale.discount_amount) > 0 && /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between mb-1 text-danger",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "Total Discounts Allowed:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["-₹", lastInvoice.sale.discount_amount]
                      })]
                    }), Number(lastInvoice.sale.return_amount) > 0 && /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between mb-1 text-danger fw-bold",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "Less Return / Exchange Credit:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["-₹", lastInvoice.sale.return_amount]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between mb-1",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "Taxable Amount:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["₹", lastInvoice.sale.taxable_amount]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between mb-1",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "CGST Amount:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["₹", lastInvoice.sale.cgst_amount]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between mb-1",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "SGST Amount:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["₹", lastInvoice.sale.sgst_amount]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between mb-1",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "Round Off:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["₹", lastInvoice.sale.round_off]
                      })]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between fs-5 fw-bolder border-top pt-2 text-primary",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "GRAND TOTAL / NET PAYABLE:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["₹", lastInvoice.sale.net_amount]
                      })]
                    }), Number(lastInvoice.sale.refund_amount) > 0 && /*#__PURE__*/_jsxs("div", {
                      className: "d-flex justify-content-between fs-6 fw-bold text-danger border-top border-bottom py-1 mt-2",
                      children: [/*#__PURE__*/_jsx("span", {
                        children: "REFUND PAID TO CUSTOMER:"
                      }), /*#__PURE__*/_jsxs("span", {
                        children: ["₹", lastInvoice.sale.refund_amount, " (", lastInvoice.sale.refund_mode, lastInvoice.sale.refund_ref_no ? ` - ${lastInvoice.sale.refund_ref_no}` : '', ")"]
                      })]
                    })]
                  })]
                })]
              })]
            })
          })
        }), /*#__PURE__*/_jsxs("div", {
          className: "p-2.5 bg-white border-top d-flex justify-content-between align-items-center flex-shrink-0 px-3",
          children: [/*#__PURE__*/_jsxs("span", {
            className: "text-muted small",
            children: ["Format: ", /*#__PURE__*/_jsx("strong", {
              className: "text-dark text-uppercase",
              children: printFormat
            }), " • Bill No: ", /*#__PURE__*/_jsx("strong", {
              children: lastInvoice.sale.invoice_no
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex gap-2 flex-wrap align-items-center",
            children: [/*#__PURE__*/_jsx("button", {
              className: "btn btn-sm btn-outline-secondary px-3",
              onClick: () => setIsPrintOpen(false),
              children: "Done / Close (Esc)"
            }), printFormat === 'thermal' ? /*#__PURE__*/_jsxs("button", {
              className: "btn btn-sm btn-warning text-dark px-3 fw-bold",
              onClick: printThermalReceipt,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-printer-fill me-1"
              }), " Print Thermal Receipt"]
            }) : /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-outline-primary px-3",
                onClick: () => downloadPdf(printFormat),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-download me-1"
                }), " Download PDF"]
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-primary px-3 fw-bold",
                onClick: () => printPdf(printFormat),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-printer me-1"
                }), " Print PDF (", printFormat === 'a5' ? 'A5 LANDSCAPE' : printFormat.toUpperCase(), ")"]
              })]
            })]
          })]
        })]
      })
    }), showFhirModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-lg modal-dialog-scrollable",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2 px-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-shield-fill-check text-success fs-5"
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: "ABDM NRCES FHIR R4 MedicationDispense Document"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-secondary",
                  style: {
                    fontSize: '0.75rem'
                  },
                  children: "Profile: MedicationDispenseDocument | LOINC: 60590-7"
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowFhirModal(false)
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "modal-body p-3 bg-light",
            children: isLoadingFhir ? /*#__PURE__*/_jsxs("div", {
              className: "text-center py-5",
              children: [/*#__PURE__*/_jsx("div", {
                className: "spinner-border text-primary mb-2",
                role: "status"
              }), /*#__PURE__*/_jsx("div", {
                className: "text-muted",
                children: "Retrieving & Validating ABDM FHIR R4 Bundle..."
              })]
            }) : fhirBundleData ? /*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsxs("div", {
                className: "row g-2 mb-3",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "col-md-4",
                  children: /*#__PURE__*/_jsxs("div", {
                    className: "bg-white p-2 rounded border",
                    children: [/*#__PURE__*/_jsx("small", {
                      className: "text-muted d-block",
                      children: "Care Context Reference"
                    }), /*#__PURE__*/_jsx("code", {
                      className: "text-primary fw-bold",
                      children: fhirBundleData.care_context_ref
                    })]
                  })
                }), /*#__PURE__*/_jsx("div", {
                  className: "col-md-4",
                  children: /*#__PURE__*/_jsxs("div", {
                    className: "bg-white p-2 rounded border",
                    children: [/*#__PURE__*/_jsx("small", {
                      className: "text-muted d-block",
                      children: "ABHA Number / Address"
                    }), /*#__PURE__*/_jsx("strong", {
                      className: "text-success",
                      children: fhirBundleData.abha_id || fhirBundleData.abha_address || 'Not ABHA Linked'
                    })]
                  })
                }), /*#__PURE__*/_jsx("div", {
                  className: "col-md-4",
                  children: /*#__PURE__*/_jsxs("div", {
                    className: "bg-white p-2 rounded border",
                    children: [/*#__PURE__*/_jsx("small", {
                      className: "text-muted d-block",
                      children: "Gateway Sync Status"
                    }), /*#__PURE__*/_jsx("span", {
                      className: `badge ${fhirBundleData.sync_status === 'SYNCED' ? 'bg-success' : 'bg-warning text-dark'}`,
                      children: fhirBundleData.sync_status || 'QUEUED_FOR_BRIDGE'
                    })]
                  })
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex justify-content-between align-items-center mb-1",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "small fw-bold text-dark",
                  children: "NRCES FHIR R4 Document Bundle (JSON):"
                }), /*#__PURE__*/_jsxs("button", {
                  className: "btn btn-sm btn-outline-secondary py-0 px-2",
                  onClick: copyFhirToClipboard,
                  children: [/*#__PURE__*/_jsx("i", {
                    className: `bi ${copiedFhir ? 'bi-check-lg text-success' : 'bi-clipboard'} me-1`
                  }), copiedFhir ? 'Copied!' : 'Copy JSON']
                })]
              }), /*#__PURE__*/_jsx("pre", {
                className: "bg-dark text-light p-3 rounded-2",
                style: {
                  maxHeight: 360,
                  fontSize: '0.75rem',
                  overflow: 'auto'
                },
                children: JSON.stringify(fhirBundleData.bundle, null, 2)
              })]
            }) : /*#__PURE__*/_jsx("div", {
              className: "alert alert-warning mb-0",
              children: "No FHIR bundle data found."
            })
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-footer py-1 px-3 bg-white",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted me-auto",
              style: {
                fontSize: '0.75rem'
              },
              children: "Includes SNOMED-CT clinical codes, HFR Facility ID, and Practitioner HPR ID."
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-secondary",
              onClick: () => setShowFhirModal(false),
              children: "Close"
            })]
          })]
        })
      })
    }), showReturnModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-xl modal-dialog-scrollable",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2 px-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-arrow-left-right text-warning fs-5"
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: "Sales Return & Same-Invoice Exchange Desk"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-secondary",
                  style: {
                    fontSize: '0.75rem'
                  },
                  children: "B2C Retail Returns • Stock Replenishment • GST Credit Note • Fast POS Standard (F6 / Alt+R)"
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowReturnModal(false)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-body p-3 bg-light",
            children: [/*#__PURE__*/_jsx("div", {
              className: "card shadow-sm border-0 mb-3",
              children: /*#__PURE__*/_jsxs("div", {
                className: "card-body p-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small fw-bold text-muted mb-1",
                  children: "Lookup Original Tax Invoice / Patient UHID / Phone:"
                }), /*#__PURE__*/_jsxs("div", {
                  className: "input-group",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "input-group-text bg-white",
                    children: /*#__PURE__*/_jsx("i", {
                      className: "bi bi-search"
                    })
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    ref: returnSearchInputRef,
                    className: "form-control",
                    placeholder: "Enter Invoice No (e.g. MAIN/26-27/08176), UHID, Patient Name, or Mobile...",
                    value: returnSearchQuery,
                    onChange: e => setReturnSearchQuery(e.target.value),
                    onKeyDown: e => {
                      if (e.key === 'Enter') {
                        e.preventDefault();
                        searchReturnInvoices();
                      }
                    }
                  }), /*#__PURE__*/_jsx("button", {
                    className: "btn btn-primary px-3",
                    type: "button",
                    disabled: isSearchingReturn,
                    onClick: () => searchReturnInvoices(),
                    children: isSearchingReturn ? /*#__PURE__*/_jsxs(_Fragment, {
                      children: [/*#__PURE__*/_jsx("span", {
                        className: "spinner-border spinner-border-sm me-1"
                      }), "Searching..."]
                    }) : /*#__PURE__*/_jsxs(_Fragment, {
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-arrow-repeat me-1"
                      }), " Search Bill"]
                    })
                  })]
                })]
              })
            }), !selectedReturnInvoice && returnSearchResults.length > 1 && /*#__PURE__*/_jsxs("div", {
              className: "card shadow-sm border-0 mb-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card-header bg-white py-2 fw-bold text-dark small",
                children: ["Matching Invoices (", returnSearchResults.length, " found) - Select an invoice to process returns:"]
              }), /*#__PURE__*/_jsx("div", {
                className: "table-responsive",
                style: {
                  maxHeight: '220px'
                },
                children: /*#__PURE__*/_jsxs("table", {
                  className: "table table-hover table-sm align-middle mb-0",
                  style: {
                    fontSize: '0.85rem'
                  },
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-light",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        children: "Bill No"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Date"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Patient"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Doctor"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Original Net"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Returnable Items"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Action"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: returnSearchResults.map(resObj => {
                      const returnableCount = (resObj.items || []).filter(it => it.remaining_units > 0).length;
                      return /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("td", {
                          className: "fw-bold text-primary",
                          children: resObj.sale.invoice_no
                        }), /*#__PURE__*/_jsx("td", {
                          className: "text-muted",
                          children: resObj.sale.sale_date
                        }), /*#__PURE__*/_jsxs("td", {
                          children: [/*#__PURE__*/_jsx("strong", {
                            children: resObj.sale.patient_name
                          }), resObj.sale.uhid && /*#__PURE__*/_jsxs("span", {
                            className: "text-muted ms-1",
                            children: ["(", resObj.sale.uhid, ")"]
                          })]
                        }), /*#__PURE__*/_jsx("td", {
                          children: resObj.sale.doctor_name || 'N/A'
                        }), /*#__PURE__*/_jsxs("td", {
                          className: "fw-bold",
                          children: ["₹", resObj.sale.net_amount]
                        }), /*#__PURE__*/_jsx("td", {
                          children: /*#__PURE__*/_jsxs("span", {
                            className: `badge ${returnableCount > 0 ? 'bg-success-subtle text-success' : 'bg-secondary'}`,
                            children: [returnableCount, " items eligible"]
                          })
                        }), /*#__PURE__*/_jsx("td", {
                          children: /*#__PURE__*/_jsxs("button", {
                            type: "button",
                            className: "btn btn-sm btn-outline-primary py-0 px-2",
                            onClick: () => selectReturnInvoice(resObj),
                            children: ["Select Bill ", /*#__PURE__*/_jsx("i", {
                              className: "bi bi-chevron-right ms-1"
                            })]
                          })
                        })]
                      }, resObj.sale.sale_id);
                    })
                  })]
                })
              })]
            }), selectedReturnInvoice && /*#__PURE__*/_jsxs("div", {
              className: "card shadow-sm border-0 mb-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card-header bg-white py-2 d-flex justify-content-between align-items-center",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex align-items-center gap-2",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "badge bg-primary fs-7",
                    children: selectedReturnInvoice.sale.invoice_no
                  }), /*#__PURE__*/_jsxs("span", {
                    className: "text-muted small",
                    children: ["Date: ", selectedReturnInvoice.sale.sale_date]
                  }), selectedReturnInvoice.sale.uhid && /*#__PURE__*/_jsxs("span", {
                    className: "badge bg-light text-dark border",
                    children: ["UHID: ", selectedReturnInvoice.sale.uhid]
                  })]
                }), returnSearchResults.length > 1 && /*#__PURE__*/_jsxs("button", {
                  type: "button",
                  className: "btn btn-sm btn-outline-secondary py-0",
                  onClick: () => setSelectedReturnInvoice(null),
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-arrow-left me-1"
                  }), " Change Bill"]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "card-body p-2 bg-light-subtle",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "row g-2 small",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Patient:"
                    }), " ", selectedReturnInvoice.sale.patient_name]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Phone:"
                    }), " ", selectedReturnInvoice.sale.patient_mobile || 'N/A']
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Doctor:"
                    }), " ", selectedReturnInvoice.sale.doctor_name || 'N/A']
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Original Net Paid:"
                    }), " ₹", selectedReturnInvoice.sale.net_amount]
                  })]
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "table-responsive",
                children: /*#__PURE__*/_jsxs("table", {
                  className: "table table-bordered table-sm align-middle mb-0",
                  style: {
                    fontSize: '0.85rem'
                  },
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-light",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        children: "#"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Medicine Name"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Batch / Exp"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Sold / Returned"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Eligible Units"
                      }), /*#__PURE__*/_jsx("th", {
                        style: {
                          width: 150
                        },
                        children: "Return Qty & Unit"
                      }), /*#__PURE__*/_jsx("th", {
                        style: {
                          width: 140
                        },
                        children: "Condition"
                      }), /*#__PURE__*/_jsx("th", {
                        style: {
                          width: 150
                        },
                        children: "Reason"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Credit Rate"
                      }), /*#__PURE__*/_jsx("th", {
                        className: "text-end",
                        style: {
                          width: 110
                        },
                        children: "Line Credit"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: selectedReturnInvoice.items.map((it, idx) => {
                      const draft = returnDraftItems[it.sale_item_id] || {
                        return_qty: 0,
                        return_unit: it.units_per_pack > 1 && it.remaining_strips > 0 ? 'Strip' : 'Tab',
                        condition: 'GOOD',
                        reason: 'EXCESS_UNUSED'
                      };
                      const isEligible = it.remaining_units > 0;
                      const qty = Number(draft.return_qty) || 0;
                      const upp = Number(it.units_per_pack) || 1;
                      const totalReturnUnits = draft.return_unit === 'Strip' ? qty * upp : qty;
                      const lineCredit = Number((totalReturnUnits * it.effective_unit_price).toFixed(2));
                      const isOverLimit = totalReturnUnits > it.remaining_units;
                      return /*#__PURE__*/_jsxs("tr", {
                        className: !isEligible ? 'opacity-50 table-light' : qty > 0 ? 'table-warning' : '',
                        children: [/*#__PURE__*/_jsx("td", {
                          children: idx + 1
                        }), /*#__PURE__*/_jsxs("td", {
                          children: [/*#__PURE__*/_jsx("strong", {
                            className: "text-dark",
                            children: it.item_name
                          }), it.generic_name && /*#__PURE__*/_jsx("div", {
                            className: "text-muted",
                            style: {
                              fontSize: '0.75rem'
                            },
                            children: it.generic_name
                          }), upp > 1 && /*#__PURE__*/_jsxs("div", {
                            className: "text-muted",
                            style: {
                              fontSize: '0.72rem'
                            },
                            children: ["1 Strip = ", upp, " Tabs"]
                          })]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: [/*#__PURE__*/_jsx("span", {
                            className: "badge bg-secondary-subtle text-dark border",
                            children: it.batch_no
                          }), /*#__PURE__*/_jsxs("div", {
                            className: "text-muted",
                            style: {
                              fontSize: '0.72rem'
                            },
                            children: ["Exp: ", it.expiry_date ? it.expiry_date.substring(0, 7) : 'N/A']
                          })]
                        }), /*#__PURE__*/_jsxs("td", {
                          children: [/*#__PURE__*/_jsxs("div", {
                            children: ["Sold: ", it.total_units, " tab"]
                          }), Number(it.already_returned_units) > 0 && /*#__PURE__*/_jsxs("div", {
                            className: "text-danger",
                            style: {
                              fontSize: '0.75rem'
                            },
                            children: ["Returned: ", it.already_returned_units, " tab"]
                          })]
                        }), /*#__PURE__*/_jsx("td", {
                          children: isEligible ? /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-success-subtle text-success border border-success",
                            children: [it.remaining_units, " Tabs ", upp > 1 ? `(${it.remaining_strips} Str + ${it.remaining_loose} Tab)` : '']
                          }) : /*#__PURE__*/_jsx("span", {
                            className: "badge bg-secondary",
                            children: "Fully Returned"
                          })
                        }), /*#__PURE__*/_jsxs("td", {
                          children: [isEligible ? /*#__PURE__*/_jsxs("div", {
                            className: "input-group input-group-sm",
                            children: [/*#__PURE__*/_jsx("input", {
                              type: "number",
                              min: "0",
                              max: draft.return_unit === 'Strip' ? it.remaining_strips : it.remaining_units,
                              className: `form-control ${isOverLimit ? 'is-invalid' : ''}`,
                              value: draft.return_qty,
                              onChange: e => updateReturnDraft(it.sale_item_id, 'return_qty', e.target.value)
                            }), upp > 1 && /*#__PURE__*/_jsxs("select", {
                              className: "form-select",
                              style: {
                                maxWidth: 80
                              },
                              value: draft.return_unit,
                              onChange: e => updateReturnDraft(it.sale_item_id, 'return_unit', e.target.value),
                              children: [/*#__PURE__*/_jsx("option", {
                                value: "Tab",
                                children: "Tab"
                              }), /*#__PURE__*/_jsx("option", {
                                value: "Strip",
                                children: "Strip"
                              })]
                            })]
                          }) : /*#__PURE__*/_jsx("span", {
                            className: "text-muted small",
                            children: "-"
                          }), isOverLimit && /*#__PURE__*/_jsxs("small", {
                            className: "text-danger d-block",
                            children: ["Max ", it.remaining_units, " tabs"]
                          })]
                        }), /*#__PURE__*/_jsx("td", {
                          children: isEligible && qty > 0 ? /*#__PURE__*/_jsxs("select", {
                            className: "form-select form-select-sm",
                            value: draft.condition,
                            onChange: e => updateReturnDraft(it.sale_item_id, 'condition', e.target.value),
                            children: [/*#__PURE__*/_jsx("option", {
                              value: "GOOD",
                              children: "Good (Restock)"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "DAMAGED",
                              children: "Damaged / Broken"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "EXPIRED",
                              children: "Expired / Defective"
                            })]
                          }) : /*#__PURE__*/_jsx("span", {
                            className: "text-muted small",
                            children: "-"
                          })
                        }), /*#__PURE__*/_jsx("td", {
                          children: isEligible && qty > 0 ? /*#__PURE__*/_jsxs("select", {
                            className: "form-select form-select-sm",
                            value: draft.reason,
                            onChange: e => updateReturnDraft(it.sale_item_id, 'reason', e.target.value),
                            children: [/*#__PURE__*/_jsx("option", {
                              value: "EXCESS_UNUSED",
                              children: "Excess / Unused"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "DOCTOR_CHANGED_RX",
                              children: "Doctor Changed Rx"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "PATIENT_DISCHARGED",
                              children: "Patient Discharged"
                            }), /*#__PURE__*/_jsx("option", {
                              value: "WRONG_MEDICINE",
                              children: "Wrong Dispense"
                            })]
                          }) : /*#__PURE__*/_jsx("span", {
                            className: "text-muted small",
                            children: "-"
                          })
                        }), /*#__PURE__*/_jsxs("td", {
                          children: ["₹", it.effective_unit_price, " ", /*#__PURE__*/_jsx("small", {
                            className: "text-muted",
                            children: "/tab"
                          }), upp > 1 && /*#__PURE__*/_jsxs("div", {
                            className: "text-muted",
                            style: {
                              fontSize: '0.72rem'
                            },
                            children: ["₹", (it.effective_unit_price * upp).toFixed(2), "/str"]
                          })]
                        }), /*#__PURE__*/_jsx("td", {
                          className: "text-end fw-bold",
                          children: qty > 0 ? /*#__PURE__*/_jsxs("span", {
                            className: "text-danger",
                            children: ["-₹", lineCredit.toFixed(2)]
                          }) : /*#__PURE__*/_jsx("span", {
                            className: "text-muted",
                            children: "₹0.00"
                          })
                        })]
                      }, it.sale_item_id);
                    })
                  })]
                })
              })]
            }), !selectedReturnInvoice && returnSearchResults.length === 0 && /*#__PURE__*/_jsxs("div", {
              className: "text-center py-5 text-muted bg-white rounded border",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-receipt-cutoff fs-1 text-secondary mb-2 d-block"
              }), /*#__PURE__*/_jsx("div", {
                className: "fw-bold",
                children: "No Invoice Selected"
              }), /*#__PURE__*/_jsx("div", {
                className: "small",
                children: "Search by Bill Number, Patient UHID, Name, or Mobile to load returnable items."
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-footer py-2 px-3 bg-white d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsx("div", {
              children: selectedReturnInvoice && /*#__PURE__*/_jsx("div", {
                className: "d-flex align-items-center gap-3",
                children: (() => {
                  let totalCredit = 0;
                  let totalItemsCount = 0;
                  selectedReturnInvoice.items.forEach(it => {
                    const draft = returnDraftItems[it.sale_item_id];
                    if (draft && Number(draft.return_qty) > 0) {
                      const qty = Number(draft.return_qty);
                      const upp = Number(it.units_per_pack) || 1;
                      const tu = draft.return_unit === 'Strip' ? qty * upp : qty;
                      totalCredit += tu * it.effective_unit_price;
                      totalItemsCount++;
                    }
                  });
                  return /*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsxs("span", {
                      className: "text-muted small me-2",
                      children: ["Selected Items: ", /*#__PURE__*/_jsx("strong", {
                        children: totalItemsCount
                      })]
                    }), /*#__PURE__*/_jsxs("span", {
                      className: "text-danger fw-bold fs-6",
                      children: ["Total Return Credit: -₹", totalCredit.toFixed(2)]
                    })]
                  });
                })()
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-2",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-secondary btn-sm",
                onClick: () => setShowReturnModal(false),
                children: "Cancel"
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-danger btn-sm px-3 fw-bold",
                disabled: !selectedReturnInvoice,
                onClick: addSelectedReturnsToBill,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-cart-plus me-1"
                }), " Add to Current Bill (Exchange / Net-Off)"]
              })]
            })]
          })]
        })
      })
    }), showDraftModal && /*#__PURE__*/_jsx("div", {
      className: "modal show d-block",
      tabIndex: "-1",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-dialog-centered modal-lg",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content shadow-lg border-0",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-phone-vibrate text-warning fs-5"
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title fw-bold mb-0",
                  children: "Mobile App Rack Drafts Queue"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-muted",
                  children: "Draft bills collected from pharmacy shelves via smartphone"
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowDraftModal(false)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-body p-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "btn-group btn-group-sm",
                children: [/*#__PURE__*/_jsxs("button", {
                  type: "button",
                  className: `btn btn-sm ${draftModalTab === 'DRAFT' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'}`,
                  onClick: () => {
                    setDraftModalTab('DRAFT');
                    loadDraftBills('DRAFT');
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-hourglass-split me-1"
                  }), " Pending Queue"]
                }), /*#__PURE__*/_jsxs("button", {
                  type: "button",
                  className: `btn btn-sm ${draftModalTab === 'CONVERTED' ? 'btn-success text-white fw-bold' : 'btn-outline-secondary'}`,
                  onClick: () => {
                    setDraftModalTab('CONVERTED');
                    loadDraftBills('CONVERTED');
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check2-all me-1"
                  }), " Billed / Converted"]
                })]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-primary py-0.5 px-2",
                onClick: () => loadDraftBills(draftModalTab),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-clockwise me-1"
                }), " Refresh"]
              })]
            }), draftBillsList.length === 0 ? /*#__PURE__*/_jsxs("div", {
              className: "text-center py-5 text-muted",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-basket3 fs-1 d-block mb-2 text-secondary"
              }), /*#__PURE__*/_jsx("h5", {
                children: draftModalTab === 'CONVERTED' ? 'No Converted Draft Bills' : 'No Pending Draft Bills'
              }), /*#__PURE__*/_jsx("p", {
                className: "small mb-0",
                children: draftModalTab === 'CONVERTED' ? 'Draft bills will appear here once converted into pharmacy sales invoices.' : 'Medicines added by rack collectors from the mobile app will automatically show up here.'
              })]
            }) : /*#__PURE__*/_jsx("div", {
              className: "table-responsive",
              children: /*#__PURE__*/_jsxs("table", {
                className: "table table-pos table-hover mb-0",
                children: [/*#__PURE__*/_jsx("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("th", {
                      children: "Draft Code"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Patient / Ward"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Items"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Est. Amount"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Collector"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Time"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-end",
                      children: "Action"
                    })]
                  })
                }), /*#__PURE__*/_jsx("tbody", {
                  children: draftBillsList.map(d => /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("span", {
                        className: `badge ${d.status === 'CONVERTED' ? 'bg-secondary' : 'bg-warning text-dark'} fw-bold`,
                        children: d.draft_code
                      })
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold",
                        children: d.patient_name || 'Walk-in'
                      }), /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        children: d.ward_name ? `${d.ward_name} / Bed ${d.bed_no}` : d.patient_mobile || 'No bed info'
                      })]
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-primary rounded-pill",
                        children: [d.total_items, " items"]
                      })
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "fw-bold text-success",
                      children: ["₹", Number(d.estimated_amount || 0).toFixed(2)]
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsxs("small", {
                        className: "text-secondary",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-person me-1"
                        }), d.collected_by_name || 'Staff']
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      children: /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        children: d.created_at?.split(' ')[1] || d.created_at
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      className: "text-end",
                      children: d.status === 'CONVERTED' ? /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-success-subtle text-success border border-success fw-bold px-2 py-1",
                        style: {
                          fontSize: '0.75rem'
                        },
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-check-circle-fill me-1"
                        }), " Billed: ", d.converted_invoice_no || 'INV']
                      }) : /*#__PURE__*/_jsxs(_Fragment, {
                        children: [/*#__PURE__*/_jsxs("button", {
                          type: "button",
                          className: "btn btn-sm btn-success fw-bold px-2.5 py-1 me-1 shadow-sm",
                          onClick: () => handleLoadDraftBillIntoCart(d),
                          children: [/*#__PURE__*/_jsx("i", {
                            className: "bi bi-cart-plus me-1"
                          }), " Load Bill"]
                        }), /*#__PURE__*/_jsx("button", {
                          type: "button",
                          className: "btn btn-sm btn-outline-danger py-1 px-1.5",
                          onClick: () => handleCancelDraft(d.draft_id),
                          title: "Discard draft",
                          children: /*#__PURE__*/_jsx("i", {
                            className: "bi bi-trash"
                          })
                        })]
                      })
                    })]
                  }, d.draft_id))
                })]
              })
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-footer py-2 px-3 bg-light d-flex justify-content-between",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted",
              children: draftModalTab === 'DRAFT' ? `${draftBillsList.length} draft bill(s) pending` : `${draftBillsList.length} converted draft bill(s)`
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-secondary",
              onClick: () => setShowDraftModal(false),
              children: "Close"
            })]
          })]
        })
      })
    })]
  });
}

// =========================================================================
// 1.5. INVOICES & SALES REGISTER (ALL INVOICES, SEARCHABLE, EDIT & REPRINT)
// =========================================================================
function InvoicesView({
  store,
  notify,
  onEditInPos
}) {
  if (!store) {
    return /*#__PURE__*/_jsxs("div", {
      className: "text-center py-5 text-muted",
      children: [/*#__PURE__*/_jsx("div", {
        className: "spinner-border spinner-border-sm text-primary me-2"
      }), "Loading invoices register..."]
    });
  }
  const [invoices, setInvoices] = useState([]);
  const [stats, setStats] = useState({
    today_count: 0,
    today_amount: 0,
    total_count: 0,
    total_amount: 0,
    total_returns: 0
  });
  const [pagination, setPagination] = useState({
    current_page: 1,
    per_page: 25,
    total_records: 0,
    total_pages: 1
  });
  const [isLoading, setIsLoading] = useState(false);

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [datePreset, setDatePreset] = useState('all'); // 'today', 'yesterday', 'last7', 'month', 'all'
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [paymentMode, setPaymentMode] = useState('ALL');
  const [page, setPage] = useState(1);
  const [limit, setLimit] = useState(25);

  // Selected invoice for View/Print Drawer
  const [viewInvoice, setViewInvoice] = useState(null);
  const [isPrintOpen, setIsPrintOpen] = useState(false);
  const [printFormat, setPrintFormat] = useState('thermal'); // 'thermal', 'a5', 'a4'
  const [isLoadingView, setIsLoadingView] = useState(false);

  // Quick Edit Details Modal state
  const [editModalSale, setEditModalSale] = useState(null);
  const [editForm, setEditForm] = useState({
    patient_name: '',
    patient_mobile: '',
    patient_address: '',
    age: '',
    gender: '',
    doctor_name: '',
    doctor_reg_no: '',
    payment_mode: 'Cash',
    payment_reference: '',
    bank_name: '',
    upi_ref_no: '',
    card_ref_no: '',
    remarks: ''
  });
  const [isUpdating, setIsUpdating] = useState(false);
  const loadInvoices = async (targetPage = page, customSearch = searchQuery) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        store_id: store.store_id || 0,
        search: customSearch !== undefined ? customSearch : searchQuery,
        from_date: fromDate || '',
        to_date: toDate || '',
        payment_mode: paymentMode || 'ALL',
        page: targetPage,
        limit: limit
      });
      const res = await fetch(API_BASE + 'sales/list?' + params.toString());
      const data = await res.json();
      if (data.status) {
        setInvoices(data.sales || []);
        if (data.stats) setStats(data.stats);
        if (data.pagination) setPagination(data.pagination);
        setPage(targetPage);
      } else {
        notify(data.message || 'Failed to load invoices.', 'error');
      }
    } catch (e) {
      notify('Error fetching sales list: ' + e.message, 'error');
    } finally {
      setIsLoading(false);
    }
  };
  useEffect(() => {
    loadInvoices(1);
  }, [store?.store_id, fromDate, toDate, paymentMode, limit]);
  const handleDatePreset = preset => {
    setDatePreset(preset);
    const today = new Date();
    const formatDate = d => d.toISOString().split('T')[0];
    if (preset === 'today') {
      const todayStr = formatDate(today);
      setFromDate(todayStr);
      setToDate(todayStr);
    } else if (preset === 'yesterday') {
      const yest = new Date(today);
      yest.setDate(yest.getDate() - 1);
      const yestStr = formatDate(yest);
      setFromDate(yestStr);
      setToDate(yestStr);
    } else if (preset === 'last7') {
      const past7 = new Date(today);
      past7.setDate(past7.getDate() - 7);
      setFromDate(formatDate(past7));
      setToDate(formatDate(today));
    } else if (preset === 'month') {
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
      setFromDate(formatDate(firstDay));
      setToDate(formatDate(today));
    } else if (preset === 'all') {
      setFromDate('');
      setToDate('');
    }
  };
  const handleSearchSubmit = e => {
    if (e) e.preventDefault();
    loadInvoices(1, searchQuery);
  };
  const clearSearch = () => {
    setSearchQuery('');
    loadInvoices(1, '');
  };

  // Open View / Print Drawer
  const openPrintDrawer = async saleId => {
    setIsLoadingView(true);
    try {
      const res = await fetch(API_BASE + 'sales/invoice/' + saleId);
      const data = await res.json();
      if (data.status) {
        setViewInvoice(data);
        setIsPrintOpen(true);
      } else {
        notify(data.message || 'Invoice details not found.', 'error');
      }
    } catch (e) {
      notify('Error loading invoice: ' + e.message, 'error');
    } finally {
      setIsLoadingView(false);
    }
  };

  // Open Edit Details Modal
  const openEditModal = sale => {
    setEditModalSale(sale);
    setEditForm({
      patient_name: sale.patient_name || '',
      patient_mobile: sale.patient_mobile || '',
      patient_address: sale.patient_address || '',
      age: sale.age || '',
      gender: sale.gender || '',
      doctor_name: sale.doctor_name || '',
      doctor_reg_no: sale.doctor_reg_no || '',
      payment_mode: sale.payment_mode || 'Cash',
      payment_reference: sale.payment_reference || '',
      bank_name: sale.bank_name || '',
      upi_ref_no: sale.upi_ref_no || '',
      card_ref_no: sale.card_ref_no || '',
      remarks: sale.reconciled_notes || ''
    });
  };
  const handleSaveEdit = async e => {
    e.preventDefault();
    if (!editModalSale) return;
    setIsUpdating(true);
    try {
      const res = await fetch(API_BASE + 'sales/update', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          sale_id: editModalSale.sale_id,
          ...editForm
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(`Invoice {editModalSale.invoice_no} updated successfully!`, 'success');
        setEditModalSale(null);
        loadInvoices(page);
      } else {
        notify(data.message || 'Failed to update invoice.', 'error');
      }
    } catch (err) {
      notify('Error saving invoice update: ' + err.message, 'error');
    } finally {
      setIsUpdating(false);
    }
  };

  // Load into POS Counter Desk for direct re-billing / line items editing
  const handleEditInPos = async saleId => {
    setIsLoadingView(true);
    try {
      const res = await fetch(API_BASE + 'sales/invoice/' + saleId);
      const data = await res.json();
      if (data.status && data.sale) {
        if (onEditInPos) {
          onEditInPos(data);
        }
      } else {
        notify(data.message || 'Failed to load invoice for POS edit.', 'error');
      }
    } catch (e) {
      notify('Error: ' + e.message, 'error');
    } finally {
      setIsLoadingView(false);
    }
  };
  const printThermalReceipt = () => {
    if (!viewInvoice || !viewInvoice.sale) return;
    const txt = generateThermalReceiptText(viewInvoice);
    const win = window.open('', '_blank', 'width=420,height=650');
    if (!win) {
      notify('Pop-up blocked. Please allow pop-ups to print thermal receipt.', 'warning');
      return;
    }
    try {
      win.document.title = 'Thermal Receipt - ' + (viewInvoice.sale ? viewInvoice.sale.invoice_no : '');
      const style = win.document.createElement('style');
      style.textContent = '@page { size: 80mm auto; margin: 0; } html, body { margin: 0; padding: 0; background: #fff; } body { padding: 4mm 2mm; font-family: Courier, monospace; font-size: 11px; line-height: 1.25; color: #000; } pre { margin: 0; font-family: inherit; font-size: inherit; white-space: pre-wrap; word-break: break-all; }';
      win.document.head.appendChild(style);
      const pre = win.document.createElement('pre');
      pre.textContent = txt;
      win.document.body.appendChild(pre);
      setTimeout(() => {
        try {
          win.focus();
          win.print();
        } catch (pe) {
          console.error('Print trigger error', pe);
        }
      }, 350);
    } catch (e) {
      console.error('Error writing thermal window:', e);
    }
  };
  const printPdf = (format = 'a5') => {
    if (!viewInvoice || !viewInvoice.sale) return;
    const saleId = viewInvoice.sale.sale_id;
    const url = `{API_BASE}sales/invoice-pdf/{saleId}?format={format}&view=1`;
    const printWindow = window.open(url, '_blank');
    if (!printWindow) {
      notify('Popup was blocked by browser. Please allow popups for PDF printing.', 'error');
    }
  };
  const downloadPdf = (format = 'a5') => {
    if (!viewInvoice || !viewInvoice.sale) return;
    const saleId = viewInvoice.sale.sale_id;
    const url = `{API_BASE}sales/invoice-pdf/{saleId}?format={format}&download=1`;
    const a = document.createElement('a');
    a.href = url;
    a.download = `Invoice_{viewInvoice.sale.invoice_no.replace(/[^a-zA-Z0-9_-]/g, '_')}_{format.toUpperCase()}.pdf`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  };
  const generateThermalReceiptText = inv => {
    if (!inv || !inv.sale) return '';
    const sale = inv.sale;
    const items = inv.items || [];
    const returnItems = inv.return_items || [];
    const LINE_WIDTH = 48;
    const padR = (str, len) => String(str || '').slice(0, len).padEnd(len, ' ');
    const padL = (str, len) => String(str || '').slice(0, len).padStart(len, ' ');
    const center = str => {
      const s = String(str || '').trim();
      if (s.length >= LINE_WIDTH) return s.slice(0, LINE_WIDTH);
      const pad = Math.floor((LINE_WIDTH - s.length) / 2);
      return ' '.repeat(pad) + s;
    };
    const rule = (char = '=') => char.repeat(LINE_WIDTH);
    let lines = [];
    lines.push(center(inv.store?.store_name || 'HOSPITAL PHARMACY'));
    lines.push(center(`{inv.store?.building_name || 'Main Hospital'} - {inv.store?.floor_no || 'Ground Floor'}`));
    if (inv.store?.phone) lines.push(center(`Ph: {inv.store.phone}`));
    if (inv.store?.gstin) lines.push(center(`GSTIN: {inv.store.gstin}`));
    if (inv.store?.drug_license_no_20b) lines.push(center(`DL: {inv.store.drug_license_no_20b} / {inv.store?.drug_license_no_21b || ''}`));
    lines.push(rule('='));
    lines.push(center('TAX INVOICE / CASH MEMO'));
    lines.push(rule('-'));
    lines.push(padR(`Inv No : {sale.invoice_no}`, 28) + padL(`Date: {String(sale.sale_date).slice(0, 10)}`, 20));
    lines.push(padR(`Patient: {sale.patient_name || 'Walk-in Customer'}`, 32) + padL(sale.uhid ? `UHID: {sale.uhid}` : '', 16));
    if (sale.doctor_name) lines.push(`Doctor : {sale.doctor_name}`);
    lines.push(rule('-'));
    lines.push(padR('ITEM', 22) + padR('BATCH', 8) + padL('QTY', 5) + padL('RATE', 6) + padL('TOTAL', 7));
    lines.push(rule('-'));
    items.forEach(it => {
      const name = (it.item_name || 'Item').slice(0, 21);
      const batch = (it.batch_no || '').slice(0, 7);
      const qty = String(it.qty || 1);
      const rate = Number(it.unit_price || it.effective_rate || 0).toFixed(2);
      const total = Number(it.total_amount || 0).toFixed(2);
      lines.push(padR(name, 22) + padR(batch, 8) + padL(qty, 5) + padL(rate, 6) + padL(total, 7));
    });
    if (returnItems.length > 0) {
      lines.push(rule('-'));
      lines.push(center('RETURNED / EXCHANGED ITEMS'));
      returnItems.forEach(it => {
        const name = ('RET: ' + (it.item_name || 'Item')).slice(0, 21);
        const batch = (it.batch_no || '').slice(0, 7);
        const qty = String(it.qty || 1);
        const rate = Number(it.unit_price || 0).toFixed(2);
        const total = '-' + Number(it.total_amount || 0).toFixed(2);
        lines.push(padR(name, 22) + padR(batch, 8) + padL(qty, 5) + padL(rate, 6) + padL(total, 7));
      });
    }
    lines.push(rule('='));
    lines.push(padR('Gross Total:', 36) + padL(`Rs. {Number(sale.gross_amount).toFixed(2)}`, 12));
    if (Number(sale.discount_amount) > 0) {
      lines.push(padR('Discount Allowed:', 36) + padL(`-Rs. {Number(sale.discount_amount).toFixed(2)}`, 12));
    }
    if (Number(sale.return_amount) > 0) {
      lines.push(padR('Less Return Value:', 36) + padL(`-Rs. {Number(sale.return_amount).toFixed(2)}`, 12));
    }
    lines.push(padR('NET AMOUNT PAYABLE:', 34) + padL(`Rs. {Number(sale.net_amount).toFixed(2)}`, 14));
    lines.push(rule('-'));
    lines.push(padR(`Paid by: {sale.payment_mode || 'Cash'}`, 30) + padL(`Status: PAID`, 18));
    lines.push(rule('='));
    lines.push(center('Thank you for your visit! Get well soon!'));
    lines.push(center('Medicines cannot be returned without original bill.'));
    return lines.join('n');
  };
  return /*#__PURE__*/_jsxs("div", {
    className: "invoices-view-wrapper",
    children: [/*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3",
      children: [/*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("h5", {
          className: "mb-0 fw-bold text-dark d-flex align-items-center gap-2",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-receipt-cutoff text-primary fs-4"
          }), "Invoices & Sales Register", /*#__PURE__*/_jsxs("span", {
            className: "badge bg-primary-subtle text-primary border border-primary-subtle fs-6 rounded-pill px-2.5 py-0.5",
            children: [pagination.total_records || 0, " Bills"]
          })]
        }), /*#__PURE__*/_jsxs("small", {
          className: "text-secondary",
          children: ["Showing all sales across pharmacy desk ordered ", /*#__PURE__*/_jsx("strong", {
            children: "Latest to Oldest"
          }), ". Search by bill #, patient, mobile, or doctor."]
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "d-flex align-items-center gap-2",
        children: [/*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5",
          onClick: () => loadInvoices(page),
          disabled: isLoading,
          children: [/*#__PURE__*/_jsx("i", {
            className: `bi bi-arrow-clockwise {isLoading ? 'spin' : ''}`
          }), "Refresh"]
        }), onEditInPos && /*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-sm btn-primary-custom d-inline-flex align-items-center gap-1.5",
          onClick: () => onEditInPos(null),
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-cart-plus-fill"
          }), "New POS Counter Bill"]
        })]
      })]
    }), /*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-wrap align-items-center gap-2 mb-3 bg-light p-2.5 rounded-3 border",
      children: [/*#__PURE__*/_jsxs("span", {
        className: "text-muted small fw-bold me-1",
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-calendar3 me-1"
        }), " Quick Filter:"]
      }), /*#__PURE__*/_jsx("button", {
        type: "button",
        className: `invoices-date-pill {datePreset === 'today' ? 'active' : ''}`,
        onClick: () => handleDatePreset('today'),
        children: "Today"
      }), /*#__PURE__*/_jsx("button", {
        type: "button",
        className: `invoices-date-pill {datePreset === 'yesterday' ? 'active' : ''}`,
        onClick: () => handleDatePreset('yesterday'),
        children: "Yesterday"
      }), /*#__PURE__*/_jsx("button", {
        type: "button",
        className: `invoices-date-pill {datePreset === 'last7' ? 'active' : ''}`,
        onClick: () => handleDatePreset('last7'),
        children: "Last 7 Days"
      }), /*#__PURE__*/_jsx("button", {
        type: "button",
        className: `invoices-date-pill {datePreset === 'month' ? 'active' : ''}`,
        onClick: () => handleDatePreset('month'),
        children: "This Month"
      }), /*#__PURE__*/_jsx("button", {
        type: "button",
        className: `invoices-date-pill {datePreset === 'all' ? 'active' : ''}`,
        onClick: () => handleDatePreset('all'),
        children: "All Records"
      })]
    }), /*#__PURE__*/_jsxs("div", {
      className: "row g-3 mb-3",
      children: [/*#__PURE__*/_jsx("div", {
        className: "col-6 col-md-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "invoices-stats-card border-start border-primary border-4",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-1",
            children: [/*#__PURE__*/_jsx("span", {
              className: "text-secondary small fw-bold text-uppercase",
              children: "Today's Bills"
            }), /*#__PURE__*/_jsx("i", {
              className: "bi bi-calendar-check text-primary fs-5"
            })]
          }), /*#__PURE__*/_jsx("h4", {
            className: "fw-bold text-dark mb-0",
            children: stats.today_count || 0
          }), /*#__PURE__*/_jsxs("small", {
            className: "text-primary fw-semibold",
            children: ["₹", Number(stats.today_amount || 0).toLocaleString('en-IN', {
              minimumFractionDigits: 2
            })]
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "col-6 col-md-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "invoices-stats-card border-start border-success border-4",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-1",
            children: [/*#__PURE__*/_jsx("span", {
              className: "text-secondary small fw-bold text-uppercase",
              children: "Total Invoices"
            }), /*#__PURE__*/_jsx("i", {
              className: "bi bi-receipt text-success fs-5"
            })]
          }), /*#__PURE__*/_jsx("h4", {
            className: "fw-bold text-dark mb-0",
            children: stats.total_count || 0
          }), /*#__PURE__*/_jsxs("small", {
            className: "text-success fw-semibold",
            children: ["₹", Number(stats.total_amount || 0).toLocaleString('en-IN', {
              minimumFractionDigits: 2
            })]
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "col-6 col-md-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "invoices-stats-card border-start border-warning border-4",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-1",
            children: [/*#__PURE__*/_jsx("span", {
              className: "text-secondary small fw-bold text-uppercase",
              children: "Returns / Refunds"
            }), /*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-return-left text-warning fs-5"
            })]
          }), /*#__PURE__*/_jsxs("h4", {
            className: "fw-bold text-dark mb-0",
            children: ["₹", Number(stats.total_returns || 0).toLocaleString('en-IN', {
              minimumFractionDigits: 2
            })]
          }), /*#__PURE__*/_jsx("small", {
            className: "text-muted",
            children: "Total customer returns"
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "col-6 col-md-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "invoices-stats-card border-start border-info border-4",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex justify-content-between align-items-center mb-1",
            children: [/*#__PURE__*/_jsx("span", {
              className: "text-secondary small fw-bold text-uppercase",
              children: "Net Realized"
            }), /*#__PURE__*/_jsx("i", {
              className: "bi bi-cash-stack text-info fs-5"
            })]
          }), /*#__PURE__*/_jsxs("h4", {
            className: "fw-bold text-dark mb-0",
            children: ["₹", Number((stats.total_amount || 0) - (stats.total_returns || 0)).toLocaleString('en-IN', {
              minimumFractionDigits: 2
            })]
          }), /*#__PURE__*/_jsx("small", {
            className: "text-info fw-semibold",
            children: "Net pharmacy collection"
          })]
        })
      })]
    }), /*#__PURE__*/_jsx("div", {
      className: "card border shadow-sm mb-3",
      children: /*#__PURE__*/_jsx("div", {
        className: "card-body p-3",
        children: /*#__PURE__*/_jsxs("form", {
          onSubmit: handleSearchSubmit,
          className: "row g-2 align-items-center",
          children: [/*#__PURE__*/_jsx("div", {
            className: "col-12 col-md-4",
            children: /*#__PURE__*/_jsxs("div", {
              className: "input-group input-group-sm",
              children: [/*#__PURE__*/_jsx("span", {
                className: "input-group-text bg-white text-muted",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-search"
                })
              }), /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control",
                placeholder: "Search Bill #, Patient, Mobile, Doctor...",
                value: searchQuery,
                onChange: e => setSearchQuery(e.target.value)
              }), searchQuery && /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-outline-secondary",
                onClick: clearSearch,
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-x"
                })
              }), /*#__PURE__*/_jsx("button", {
                type: "submit",
                className: "btn btn-primary px-3",
                children: "Search"
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "col-6 col-md-2",
            children: /*#__PURE__*/_jsxs("div", {
              className: "input-group input-group-sm",
              children: [/*#__PURE__*/_jsx("span", {
                className: "input-group-text bg-light text-muted",
                style: {
                  fontSize: '0.72rem'
                },
                children: "From"
              }), /*#__PURE__*/_jsx("input", {
                type: "date",
                className: "form-control",
                value: fromDate,
                onChange: e => {
                  setFromDate(e.target.value);
                  setDatePreset('custom');
                }
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "col-6 col-md-2",
            children: /*#__PURE__*/_jsxs("div", {
              className: "input-group input-group-sm",
              children: [/*#__PURE__*/_jsx("span", {
                className: "input-group-text bg-light text-muted",
                style: {
                  fontSize: '0.72rem'
                },
                children: "To"
              }), /*#__PURE__*/_jsx("input", {
                type: "date",
                className: "form-control",
                value: toDate,
                onChange: e => {
                  setToDate(e.target.value);
                  setDatePreset('custom');
                }
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "col-6 col-md-2",
            children: /*#__PURE__*/_jsxs("select", {
              className: "form-select form-select-sm",
              value: paymentMode,
              onChange: e => setPaymentMode(e.target.value),
              children: [/*#__PURE__*/_jsx("option", {
                value: "ALL",
                children: "All Payments"
              }), /*#__PURE__*/_jsx("option", {
                value: "Cash",
                children: "Cash"
              }), /*#__PURE__*/_jsx("option", {
                value: "UPI",
                children: "UPI / QR"
              }), /*#__PURE__*/_jsx("option", {
                value: "Card",
                children: "Card"
              }), /*#__PURE__*/_jsx("option", {
                value: "Mixed",
                children: "Split / Mixed"
              }), /*#__PURE__*/_jsx("option", {
                value: "IPD_Credit",
                children: "IPD Credit"
              })]
            })
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-6 col-md-2 d-flex justify-content-end align-items-center gap-2",
            children: [/*#__PURE__*/_jsx("label", {
              className: "text-muted small mb-0",
              children: "Show:"
            }), /*#__PURE__*/_jsxs("select", {
              className: "form-select form-select-sm",
              style: {
                width: 80
              },
              value: limit,
              onChange: e => setLimit(Number(e.target.value)),
              children: [/*#__PURE__*/_jsx("option", {
                value: "15",
                children: "15"
              }), /*#__PURE__*/_jsx("option", {
                value: "25",
                children: "25"
              }), /*#__PURE__*/_jsx("option", {
                value: "50",
                children: "50"
              }), /*#__PURE__*/_jsx("option", {
                value: "100",
                children: "100"
              })]
            })]
          })]
        })
      })
    }), /*#__PURE__*/_jsxs("div", {
      className: "card border shadow-sm mb-3",
      children: [/*#__PURE__*/_jsx("div", {
        className: "table-responsive",
        children: /*#__PURE__*/_jsxs("table", {
          className: "table table-hover align-middle mb-0 invoices-table",
          children: [/*#__PURE__*/_jsx("thead", {
            children: /*#__PURE__*/_jsxs("tr", {
              children: [/*#__PURE__*/_jsx("th", {
                style: {
                  width: 140
                },
                children: "Invoice No"
              }), /*#__PURE__*/_jsx("th", {
                style: {
                  width: 130
                },
                children: "Date & Time"
              }), /*#__PURE__*/_jsx("th", {
                children: "Patient Details"
              }), /*#__PURE__*/_jsx("th", {
                children: "Doctor"
              }), /*#__PURE__*/_jsx("th", {
                children: "Items Dispensed"
              }), /*#__PURE__*/_jsx("th", {
                style: {
                  width: 100
                },
                children: "Payment"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-end",
                style: {
                  width: 110
                },
                children: "Net Total"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-center",
                style: {
                  width: 90
                },
                children: "Status"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-center",
                style: {
                  width: 220
                },
                children: "Actions"
              })]
            })
          }), /*#__PURE__*/_jsx("tbody", {
            children: isLoading ? /*#__PURE__*/_jsx("tr", {
              children: /*#__PURE__*/_jsxs("td", {
                colSpan: "9",
                className: "text-center py-5 text-muted",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "spinner-border spinner-border-sm text-primary me-2"
                }), "Loading invoices..."]
              })
            }) : invoices.length === 0 ? /*#__PURE__*/_jsx("tr", {
              children: /*#__PURE__*/_jsxs("td", {
                colSpan: "9",
                className: "text-center py-5 text-muted",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-inbox fs-2 d-block mb-2 text-secondary"
                }), /*#__PURE__*/_jsx("h6", {
                  children: "No invoices found"
                }), /*#__PURE__*/_jsx("p", {
                  className: "small mb-0",
                  children: "Try changing your search query or date range filters."
                })]
              })
            }) : invoices.map(inv => {
              const hasReturns = Number(inv.return_amount) > 0;
              return /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsxs("td", {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "badge bg-dark-subtle text-dark border font-monospace px-2 py-1 fs-6 cursor-pointer",
                    onClick: () => openPrintDrawer(inv.sale_id),
                    title: "Click to View / Print Invoice",
                    style: {
                      cursor: 'pointer'
                    },
                    children: inv.invoice_no
                  }), inv.abdm_care_context_ref && /*#__PURE__*/_jsxs("span", {
                    className: "d-block mt-1 text-success",
                    style: {
                      fontSize: '0.68rem'
                    },
                    title: "ABDM Linked",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-shield-fill-check me-0.5"
                    }), " ABDM"]
                  })]
                }), /*#__PURE__*/_jsxs("td", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "fw-semibold text-dark",
                    style: {
                      fontSize: '0.82rem'
                    },
                    children: String(inv.sale_date).slice(0, 10)
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '0.72rem'
                    },
                    children: String(inv.sale_date).slice(11, 16)
                  })]
                }), /*#__PURE__*/_jsxs("td", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "fw-bold text-dark",
                    children: inv.patient_name || 'Walk-in Customer'
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex align-items-center gap-1 flex-wrap mt-0.5",
                    children: [inv.uhid && /*#__PURE__*/_jsx("span", {
                      className: "badge bg-light text-primary border",
                      style: {
                        fontSize: '0.68rem'
                      },
                      children: inv.uhid
                    }), inv.patient_mobile && /*#__PURE__*/_jsxs("span", {
                      className: "text-muted",
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-telephone me-0.5"
                      }), " ", inv.patient_mobile]
                    }), /*#__PURE__*/_jsx("span", {
                      className: `badge {inv.patient_type === 'IPD' ? 'bg-danger-subtle text-danger' : (inv.patient_type === 'OPD' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary')}`,
                      style: {
                        fontSize: '0.65rem'
                      },
                      children: inv.patient_type || 'Walk-in'
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("td", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "text-dark fw-medium",
                    style: {
                      fontSize: '0.82rem'
                    },
                    children: inv.doctor_name || 'Self / Walk-in'
                  }), inv.doctor_reg_no && /*#__PURE__*/_jsxs("small", {
                    className: "text-muted",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: ["Reg: ", inv.doctor_reg_no]
                  })]
                }), /*#__PURE__*/_jsxs("td", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "d-flex align-items-center gap-1 mb-0.5",
                    children: /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-info-subtle text-info border border-info-subtle",
                      style: {
                        fontSize: '0.7rem'
                      },
                      children: [inv.items_count || 0, " items"]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "text-secondary text-truncate",
                    style: {
                      maxWidth: 220,
                      fontSize: '0.75rem'
                    },
                    title: inv.items_summary,
                    children: inv.items_summary || 'N/A'
                  })]
                }), /*#__PURE__*/_jsx("td", {
                  children: /*#__PURE__*/_jsx("span", {
                    className: `badge {inv.payment_mode === 'Cash' ? 'bg-success-subtle text-success border border-success-subtle' : (inv.payment_mode === 'UPI' ? 'bg-info-subtle text-info border border-info-subtle' : (inv.payment_mode === 'Card' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'))}`,
                    children: inv.payment_mode || 'Cash'
                  })
                }), /*#__PURE__*/_jsxs("td", {
                  className: "text-end",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "fw-bold text-dark fs-6",
                    children: ["₹", Number(inv.net_amount).toFixed(2)]
                  }), Number(inv.discount_amount) > 0 && /*#__PURE__*/_jsxs("small", {
                    className: "text-danger d-block",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: ["Disc: -₹", Number(inv.discount_amount).toFixed(2)]
                  }), hasReturns && /*#__PURE__*/_jsxs("small", {
                    className: "text-warning-emphasis d-block",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: ["Ret: -₹", Number(inv.return_amount).toFixed(2)]
                  })]
                }), /*#__PURE__*/_jsx("td", {
                  className: "text-center",
                  children: hasReturns ? /*#__PURE__*/_jsx("span", {
                    className: "badge bg-warning text-dark",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: "Exchange"
                  }) : /*#__PURE__*/_jsx("span", {
                    className: "badge bg-success-subtle text-success border border-success-subtle",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: "Paid"
                  })
                }), /*#__PURE__*/_jsx("td", {
                  className: "text-center",
                  children: /*#__PURE__*/_jsxs("div", {
                    className: "d-flex align-items-center justify-content-center gap-1",
                    children: [/*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-xs btn-outline-primary py-1 px-2 d-inline-flex align-items-center gap-1",
                      onClick: () => openPrintDrawer(inv.sale_id),
                      title: "View & Print Invoice (Thermal, A5 Landscape, A4)",
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-printer"
                      }), /*#__PURE__*/_jsx("span", {
                        children: "View"
                      })]
                    }), /*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-xs btn-outline-secondary py-1 px-2 d-inline-flex align-items-center gap-1",
                      onClick: () => openEditModal(inv),
                      title: "Quick Edit Patient Name, Mobile, Doctor, Payment Mode",
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-pencil"
                      }), /*#__PURE__*/_jsx("span", {
                        children: "Edit"
                      })]
                    }), /*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-xs btn-success py-1 px-2 d-inline-flex align-items-center gap-1",
                      onClick: () => handleEditInPos(inv.sale_id),
                      title: "Open in POS Counter Desk to edit medicines, quantities, or re-bill",
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-cart3"
                      }), /*#__PURE__*/_jsx("span", {
                        children: "Edit in POS"
                      })]
                    })]
                  })
                })]
              }, inv.sale_id);
            })
          })]
        })
      }), pagination.total_pages > 1 && /*#__PURE__*/_jsxs("div", {
        className: "card-footer bg-white d-flex flex-wrap justify-content-between align-items-center py-2 px-3",
        children: [/*#__PURE__*/_jsxs("small", {
          className: "text-muted",
          children: ["Showing page ", /*#__PURE__*/_jsx("strong", {
            children: pagination.current_page
          }), " of ", /*#__PURE__*/_jsx("strong", {
            children: pagination.total_pages
          }), " (", pagination.total_records, " total invoices)"]
        }), /*#__PURE__*/_jsxs("div", {
          className: "btn-group btn-group-sm",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-outline-secondary",
            disabled: pagination.current_page <= 1 || isLoading,
            onClick: () => loadInvoices(pagination.current_page - 1),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-chevron-left"
            }), " Prev"]
          }), /*#__PURE__*/_jsxs("span", {
            className: "btn btn-outline-secondary disabled fw-bold text-dark px-3",
            children: [pagination.current_page, " / ", pagination.total_pages]
          }), /*#__PURE__*/_jsxs("button", {
            className: "btn btn-outline-secondary",
            disabled: pagination.current_page >= pagination.total_pages || isLoading,
            onClick: () => loadInvoices(pagination.current_page + 1),
            children: ["Next ", /*#__PURE__*/_jsx("i", {
              className: "bi bi-chevron-right"
            })]
          })]
        })]
      })]
    }), editModalSale && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.65)',
        backdropFilter: 'blur(3px)',
        zIndex: 1060
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-dialog-centered modal-lg",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg rounded-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3",
            children: [/*#__PURE__*/_jsxs("h6", {
              className: "modal-title mb-0 fw-bold d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-pencil-square text-warning"
              }), "Edit Invoice Details: ", /*#__PURE__*/_jsx("span", {
                className: "text-info",
                children: editModalSale.invoice_no
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setEditModalSale(null)
            })]
          }), /*#__PURE__*/_jsxs("form", {
            onSubmit: handleSaveEdit,
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "alert alert-info py-1.5 px-2.5 mb-3",
                style: {
                  fontSize: '0.78rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-info-circle me-1"
                }), "Use this to correct patient information, doctor name, or payment mode on this completed bill. To modify medicine quantities or items, click ", /*#__PURE__*/_jsx("strong", {
                  children: "\"Edit in POS\""
                }), " in the main table."]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Patient Name"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.patient_name,
                    onChange: e => setEditForm({
                      ...editForm,
                      patient_name: e.target.value
                    }),
                    required: true
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Mobile Number"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.patient_mobile,
                    onChange: e => setEditForm({
                      ...editForm,
                      patient_mobile: e.target.value
                    }),
                    placeholder: "10-digit mobile"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Age"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.age,
                    onChange: e => setEditForm({
                      ...editForm,
                      age: e.target.value
                    }),
                    placeholder: "e.g. 45 Y"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Gender"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: editForm.gender,
                    onChange: e => setEditForm({
                      ...editForm,
                      gender: e.target.value
                    }),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "",
                      children: "Select Gender"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Male",
                      children: "Male"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Female",
                      children: "Female"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Other",
                      children: "Other"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Payment Mode"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: editForm.payment_mode,
                    onChange: e => setEditForm({
                      ...editForm,
                      payment_mode: e.target.value
                    }),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "Cash",
                      children: "Cash"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "UPI",
                      children: "UPI / QR"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Card",
                      children: "Card"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Mixed",
                      children: "Mixed / Split"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "IPD_Credit",
                      children: "IPD Credit"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Doctor Name"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.doctor_name,
                    onChange: e => setEditForm({
                      ...editForm,
                      doctor_name: e.target.value
                    }),
                    placeholder: "Prescribing doctor"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Doctor Reg No"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.doctor_reg_no,
                    onChange: e => setEditForm({
                      ...editForm,
                      doctor_reg_no: e.target.value
                    }),
                    placeholder: "MCI / State Medical Council Reg No"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Patient Address"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.patient_address,
                    onChange: e => setEditForm({
                      ...editForm,
                      patient_address: e.target.value
                    }),
                    placeholder: "Address / City"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Payment Ref / UPI UTR / Bank"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.payment_reference || editForm.upi_ref_no || editForm.card_ref_no,
                    onChange: e => setEditForm({
                      ...editForm,
                      payment_reference: e.target.value,
                      upi_ref_no: e.target.value
                    }),
                    placeholder: "Transaction reference ID"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-12",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold text-muted mb-1",
                    children: "Audit Notes / Remarks"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: editForm.remarks,
                    onChange: e => setEditForm({
                      ...editForm,
                      remarks: e.target.value
                    }),
                    placeholder: "Reason for correction or cashier notes"
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer bg-light py-2 px-3 d-flex justify-content-between",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-outline-secondary",
                onClick: () => setEditModalSale(null),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "submit",
                className: "btn btn-sm btn-primary px-3 fw-bold",
                disabled: isUpdating,
                children: isUpdating ? /*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-hourglass-split me-1"
                  }), " Saving..."]
                }) : /*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle me-1"
                  }), " Save Changes"]
                })
              })]
            })]
          })]
        })
      })
    }), /*#__PURE__*/_jsx("div", {
      className: `print-drawer-overlay {isPrintOpen ? 'open' : ''}`,
      onClick: () => setIsPrintOpen(false)
    }), /*#__PURE__*/_jsx("div", {
      className: `print-drawer {isPrintOpen ? 'open' : ''}`,
      id: "invoices-print-drawer",
      children: viewInvoice && /*#__PURE__*/_jsxs(_Fragment, {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "print-drawer-header",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-receipt text-warning fs-5"
            }), /*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsx("h6", {
                className: "mb-0 fw-bold text-white",
                children: "Invoice & Print Desk"
              }), /*#__PURE__*/_jsxs("small", {
                className: "text-secondary",
                style: {
                  fontSize: '0.72rem'
                },
                children: ["Bill: ", /*#__PURE__*/_jsx("span", {
                  className: "text-info fw-bold",
                  children: viewInvoice.sale.invoice_no
                })]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-1.5 flex-wrap",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "btn-group btn-group-sm",
              children: [/*#__PURE__*/_jsxs("button", {
                className: `btn btn-xs py-1 px-2 {printFormat === 'thermal' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setPrintFormat('thermal'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-receipt me-1"
                }), " Thermal (Text)"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn btn-xs py-1 px-2 {printFormat === 'a5' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setPrintFormat('a5'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-pdf me-1"
                }), " A5 Landscape (PDF)"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn btn-xs py-1 px-2 {printFormat === 'a4' ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-white'}`,
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => setPrintFormat('a4'),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-pdf me-1"
                }), " A4 (PDF)"]
              })]
            }), printFormat === 'thermal' ? /*#__PURE__*/_jsxs("button", {
              className: "btn btn-sm btn-warning text-dark fw-bold py-1 px-2.5 ms-0.5",
              onClick: printThermalReceipt,
              title: "Print on 80mm Thermal Receipt Printer",
              style: {
                fontSize: '0.75rem'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-printer-fill me-1"
              }), " Print Thermal"]
            }) : /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-outline-light py-1 px-2 text-white",
                onClick: () => downloadPdf(printFormat),
                title: "Download PDF Document",
                style: {
                  fontSize: '0.75rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-download me-1 text-info"
                }), " PDF"]
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-sm btn-primary py-1 px-2.5 ms-0.5 fw-bold",
                onClick: () => printPdf(printFormat),
                title: "Print Clean Vector PDF",
                style: {
                  fontSize: '0.75rem'
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-printer me-1"
                }), " Print PDF (", printFormat === 'a5' ? 'A5 LANDSCAPE' : printFormat.toUpperCase(), ")"]
              })]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-light rounded-pill px-2.5 py-1 ms-1 d-flex align-items-center gap-1",
              onClick: () => setIsPrintOpen(false),
              title: "Close Print Drawer (Esc)",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-x-lg"
              }), /*#__PURE__*/_jsx("kbd", {
                style: {
                  background: '#334155',
                  color: '#f8fafc',
                  fontSize: '0.62rem',
                  padding: '1px 3px',
                  borderRadius: '3px'
                },
                children: "Esc"
              })]
            })]
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "print-drawer-body",
          children: /*#__PURE__*/_jsx("div", {
            className: "print-paper-card",
            children: /*#__PURE__*/_jsxs("div", {
              id: "printable-container",
              children: [printFormat === 'thermal' && /*#__PURE__*/_jsxs("div", {
                className: "p-2 text-center",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center mb-2 px-2 pb-2 border-bottom",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "badge bg-secondary",
                    children: "Thermal 80mm ESC/POS Text"
                  }), /*#__PURE__*/_jsxs("button", {
                    className: "btn btn-xs btn-warning text-dark fw-bold",
                    onClick: printThermalReceipt,
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-printer-fill me-1"
                    }), " Print Thermal Receipt"]
                  })]
                }), /*#__PURE__*/_jsx("pre", {
                  style: {
                    fontFamily: '"Courier New", Courier, monospace',
                    fontSize: '12px',
                    lineHeight: '1.2',
                    color: '#000000',
                    backgroundColor: '#ffffff',
                    padding: '12px',
                    margin: '0 auto',
                    maxWidth: '380px',
                    textAlign: 'left',
                    whiteSpace: 'pre-wrap',
                    wordBreak: 'break-word',
                    border: '1px dashed #cbd5e1',
                    borderRadius: '6px'
                  },
                  children: generateThermalReceiptText(viewInvoice)
                })]
              }), printFormat === 'a5' && /*#__PURE__*/_jsxs("div", {
                className: "p-3 text-center print-a5-invoice",
                style: {
                  maxWidth: '820px',
                  margin: '0 auto'
                },
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "alert alert-info py-1.5 px-3 mb-3 d-flex justify-content-between align-items-center shadow-sm",
                  style: {
                    fontSize: '0.78rem'
                  },
                  children: [/*#__PURE__*/_jsxs("span", {
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-file-earmark-pdf-fill me-1"
                    }), /*#__PURE__*/_jsx("strong", {
                      children: "A5 Landscape PDF (210mm × 148mm):"
                    }), " Native vector document rendered by mPDF."]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex gap-1.5",
                    children: [/*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-outline-primary py-1 px-2",
                      onClick: () => downloadPdf('a5'),
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-download me-1"
                      }), " Download"]
                    }), /*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-primary py-1 px-2.5 fw-bold",
                      onClick: () => printPdf('a5'),
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-printer me-1"
                      }), " Print PDF (A5 Landscape)"]
                    })]
                  })]
                }), /*#__PURE__*/_jsx("div", {
                  className: "border rounded shadow-sm overflow-hidden",
                  style: {
                    background: '#525659',
                    padding: '12px'
                  },
                  children: /*#__PURE__*/_jsx("iframe", {
                    src: `{API_BASE}sales/invoice-pdf/{viewInvoice.sale.sale_id}?format=a5&view=1`,
                    title: "A5 Landscape PDF Preview",
                    style: {
                      width: '100%',
                      height: '540px',
                      border: 'none',
                      borderRadius: '4px',
                      background: '#ffffff'
                    }
                  })
                })]
              }), printFormat === 'a4' && /*#__PURE__*/_jsxs("div", {
                className: "p-3 text-center print-a4-invoice",
                style: {
                  maxWidth: '780px',
                  margin: '0 auto'
                },
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "alert alert-info py-1.5 px-3 mb-3 d-flex justify-content-between align-items-center shadow-sm",
                  style: {
                    fontSize: '0.78rem'
                  },
                  children: [/*#__PURE__*/_jsxs("span", {
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-file-earmark-pdf-fill me-1"
                    }), /*#__PURE__*/_jsx("strong", {
                      children: "A4 Vector PDF (210mm × 297mm):"
                    }), " Native vector document rendered by mPDF."]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex gap-1.5",
                    children: [/*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-outline-primary py-1 px-2",
                      onClick: () => downloadPdf('a4'),
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-download me-1"
                      }), " Download"]
                    }), /*#__PURE__*/_jsxs("button", {
                      className: "btn btn-xs btn-primary py-1 px-2.5 fw-bold",
                      onClick: () => printPdf('a4'),
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-printer me-1"
                      }), " Print PDF (A4)"]
                    })]
                  })]
                }), /*#__PURE__*/_jsx("div", {
                  className: "border rounded shadow-sm overflow-hidden",
                  style: {
                    background: '#525659',
                    padding: '12px'
                  },
                  children: /*#__PURE__*/_jsx("iframe", {
                    src: `{API_BASE}sales/invoice-pdf/{viewInvoice.sale.sale_id}?format=a4&view=1`,
                    title: "A4 PDF Preview",
                    style: {
                      width: '100%',
                      height: '620px',
                      border: 'none',
                      borderRadius: '4px',
                      background: '#ffffff'
                    }
                  })
                })]
              })]
            })
          })
        })]
      })
    })]
  });
}

// =========================================================================
// 2. LIVE INVENTORY & BATCH MATRIX VIEW
// =========================================================================
function InventoryView({
  store,
  notify,
  activeOperator
}) {
  if (!store) return null;
  const [invSubTab, setInvSubTab] = useState('live'); // 'live', 'physical_audit', 'audit_logs'
  const [stock, setStock] = useState([]);
  const [summary, setSummary] = useState({});
  const [filter, setFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [loading, setLoading] = useState(false);

  // Single Batch Quick Reconcile Modal State
  const [reconcileModalItem, setReconcileModalItem] = useState(null);
  const [reconcilePhysicalQty, setReconcilePhysicalQty] = useState('');
  const [reconcileAuditType, setReconcileAuditType] = useState('PHYSICAL_VARIATION');
  const [reconcileRemarks, setReconcileRemarks] = useState('');
  const [isSubmittingReconcile, setIsSubmittingReconcile] = useState(false);

  // Cycle Count Audit Sheet State
  const [cycleRackFilter, setCycleRackFilter] = useState('all');
  const [cycleSearch, setCycleSearch] = useState('');
  const [cycleCounts, setCycleCounts] = useState({}); // { [batch_id]: { physical_qty, audit_type, remarks } }
  const [isCommittingCycle, setIsCommittingCycle] = useState(false);

  // Audit Log History State
  const [auditLogs, setAuditLogs] = useState([]);
  const [auditTotal, setAuditTotal] = useState(0);
  const [auditFilterType, setAuditFilterType] = useState('all');
  const [auditSearch, setAuditSearch] = useState('');
  const [auditFromDate, setAuditFromDate] = useState('');
  const [auditToDate, setAuditToDate] = useState('');
  const [loadingAuditLogs, setLoadingAuditLogs] = useState(false);
  useEffect(() => {
    if (store?.store_id) {
      loadStock();
    }
  }, [store?.store_id, filter]);
  useEffect(() => {
    if (invSubTab === 'audit_logs' && store?.store_id) {
      loadAuditLogs();
    }
  }, [invSubTab, store?.store_id, auditFilterType, auditFromDate, auditToDate]);
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
  const loadAuditLogs = async () => {
    setLoadingAuditLogs(true);
    try {
      let url = `${API_BASE}stock/audit-list?store_id=${store.store_id}&limit=100`;
      if (auditFilterType !== 'all') url += `&audit_type=${encodeURIComponent(auditFilterType)}`;
      if (auditSearch.trim()) url += `&search=${encodeURIComponent(auditSearch.trim())}`;
      if (auditFromDate) url += `&from_date=${auditFromDate}`;
      if (auditToDate) url += `&to_date=${auditToDate}`;
      const res = await fetch(url);
      const data = await res.json();
      if (data.status) {
        setAuditLogs(data.logs || []);
        setAuditTotal(data.total || 0);
      }
    } catch (e) {
      console.error("Failed to load audit logs:", e);
    } finally {
      setLoadingAuditLogs(false);
    }
  };

  // Open Single Reconcile Modal
  const openReconcileModal = item => {
    setReconcileModalItem(item);
    setReconcilePhysicalQty(item.current_qty !== undefined ? item.current_qty : '');
    setReconcileAuditType('PHYSICAL_VARIATION');
    setReconcileRemarks('');
  };

  // Submit Single Reconcile
  const handleReconcileSubmit = async () => {
    if (!reconcileModalItem) return;
    const pQty = Number(reconcilePhysicalQty);
    if (isNaN(pQty) || pQty < 0) {
      notify('Please enter a valid physical count (0 or greater).', 'error');
      return;
    }
    setIsSubmittingReconcile(true);
    try {
      const res = await fetch(API_BASE + 'stock/reconcile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          item_id: reconcileModalItem.item_id,
          batch_id: reconcileModalItem.batch_id,
          physical_qty: pQty,
          audit_type: reconcileAuditType,
          remarks: reconcileRemarks.trim() || `Physical audit: System ${reconcileModalItem.current_qty} -> Physical ${pQty}`,
          operator_id: activeOperator?.user_id || 1,
          operator_name: activeOperator?.full_name || 'Staff'
        })
      });
      const data = await res.json();
      if (data.status) {
        const diffMsg = data.variation_qty === 0 ? 'Counts verified (0 discrepancy).' : data.variation_qty < 0 ? `Shortage adjusted: ${Math.abs(data.variation_qty)} units (Loss: ₹${data.variation_val})` : `Surplus added: +${data.variation_qty} units (₹${data.variation_val})`;
        notify(`Stock reconciled! ${diffMsg}`, 'success');
        setReconcileModalItem(null);
        loadStock();
      } else {
        notify(data.message || 'Failed to reconcile stock.', 'error');
      }
    } catch (e) {
      notify('Network error during reconciliation.', 'error');
    } finally {
      setIsSubmittingReconcile(false);
    }
  };

  // Cycle Count Handler
  const handleCycleCountChange = (batchId, val) => {
    setCycleCounts(prev => ({
      ...prev,
      [batchId]: {
        ...(prev[batchId] || {}),
        physical_qty: val
      }
    }));
  };
  const handleCycleReasonChange = (batchId, reason) => {
    setCycleCounts(prev => ({
      ...prev,
      [batchId]: {
        ...(prev[batchId] || {}),
        audit_type: reason
      }
    }));
  };
  const handleCycleRemarksChange = (batchId, remarks) => {
    setCycleCounts(prev => ({
      ...prev,
      [batchId]: {
        ...(prev[batchId] || {}),
        remarks: remarks
      }
    }));
  };

  // Submit Batch Cycle Count
  const handleCommitCycleAudit = async () => {
    const changedCounts = [];
    for (const [bId, data] of Object.entries(cycleCounts)) {
      if (data && data.physical_qty !== undefined && data.physical_qty !== '') {
        const bItem = stock.find(s => s.batch_id == bId);
        if (bItem) {
          const pQty = Number(data.physical_qty);
          const sQty = Number(bItem.current_qty);
          if (!isNaN(pQty) && pQty >= 0 && (pQty !== sQty || data.force_log)) {
            changedCounts.push({
              item_id: bItem.item_id,
              batch_id: Number(bId),
              physical_qty: pQty,
              audit_type: data.audit_type || 'PHYSICAL_VARIATION',
              remarks: data.remarks || `Cycle Count Audit: System ${sQty} -> Physical ${pQty}`
            });
          }
        }
      }
    }
    if (changedCounts.length === 0) {
      notify('No stock count adjustments detected in the cycle sheet.', 'warning');
      return;
    }
    setIsCommittingCycle(true);
    try {
      const res = await fetch(API_BASE + 'stock/batch-reconcile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          counts: changedCounts,
          operator_id: activeOperator?.user_id || 1,
          operator_name: activeOperator?.full_name || 'Auditor',
          remarks: `Routine Shelf Cycle Audit by ${activeOperator?.full_name || 'Auditor'}`
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(`Cycle audit committed: ${data.reconciled_count} batches reconciled (Discrepancy: ₹${data.total_value})!`, 'success');
        setCycleCounts({});
        loadStock();
      } else {
        notify(data.message || 'Failed to commit batch audit.', 'error');
      }
    } catch (e) {
      notify('Network error during batch reconciliation.', 'error');
    } finally {
      setIsCommittingCycle(false);
    }
  };

  // Extract unique racks/shelves from stock list
  const uniqueRacks = [...new Set(stock.map(s => s.rack_no).filter(Boolean))];

  // Filter stock for Live View
  const filteredStock = stock.filter(s => s.item_name.toLowerCase().includes(searchTerm.toLowerCase()) || s.generic_name && s.generic_name.toLowerCase().includes(searchTerm.toLowerCase()) || s.batch_no.toLowerCase().includes(searchTerm.toLowerCase()));

  // Filter stock for Cycle Audit View
  const cycleFilteredStock = stock.filter(s => {
    const matchesRack = cycleRackFilter === 'all' || s.rack_no === cycleRackFilter;
    const matchesSearch = !cycleSearch.trim() || s.item_name.toLowerCase().includes(cycleSearch.toLowerCase()) || s.generic_name && s.generic_name.toLowerCase().includes(cycleSearch.toLowerCase()) || s.batch_no.toLowerCase().includes(cycleSearch.toLowerCase()) || s.shelf_no && s.shelf_no.toLowerCase().includes(cycleSearch.toLowerCase());
    return matchesRack && matchesSearch;
  });

  // Summary of discrepancies in cycle sheet
  let cycleDiscrepancyCount = 0;
  let cycleDiscrepancyValue = 0;
  for (const item of cycleFilteredStock) {
    const cntData = cycleCounts[item.batch_id];
    if (cntData && cntData.physical_qty !== undefined && cntData.physical_qty !== '') {
      const p = Number(cntData.physical_qty);
      const s = Number(item.current_qty);
      if (!isNaN(p) && p !== s) {
        cycleDiscrepancyCount++;
        const rate = Number(item.ptr || item.mrp || 0);
        cycleDiscrepancyValue += Math.abs(p - s) * rate;
      }
    }
  }
  return /*#__PURE__*/_jsxs("div", {
    children: [/*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "btn-group shadow-sm",
        children: [/*#__PURE__*/_jsxs("button", {
          className: `btn btn-sm fw-bold d-flex align-items-center gap-1.5 ${invSubTab === 'live' ? 'btn-primary' : 'btn-outline-secondary'}`,
          onClick: () => setInvSubTab('live'),
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-boxes"
          }), " Live Stock & Batches"]
        }), /*#__PURE__*/_jsxs("button", {
          className: `btn btn-sm fw-bold d-flex align-items-center gap-1.5 ${invSubTab === 'physical_audit' ? 'btn-primary' : 'btn-outline-secondary'}`,
          onClick: () => setInvSubTab('physical_audit'),
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-clipboard-check-fill"
          }), " Physical Stock Take & Cycle Audit"]
        }), /*#__PURE__*/_jsxs("button", {
          className: `btn btn-sm fw-bold d-flex align-items-center gap-1.5 ${invSubTab === 'audit_logs' ? 'btn-primary' : 'btn-outline-secondary'}`,
          onClick: () => {
            setInvSubTab('audit_logs');
            loadAuditLogs();
          },
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-journal-check"
          }), " Stock Discrepancy Audit Log"]
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "d-flex align-items-center gap-2",
        children: /*#__PURE__*/_jsxs("span", {
          className: "badge bg-light text-dark border px-2.5 py-1.5 rounded-pill shadow-xs d-flex align-items-center gap-1.5",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-person-badge-fill text-primary"
          }), /*#__PURE__*/_jsxs("span", {
            children: ["Auditor: ", /*#__PURE__*/_jsx("strong", {
              className: "text-dark",
              children: activeOperator ? activeOperator.full_name : 'Store Staff'
            })]
          }), /*#__PURE__*/_jsx("span", {
            className: "badge bg-primary-subtle text-primary",
            style: {
              fontSize: '0.65rem'
            },
            children: activeOperator ? activeOperator.user_role.toUpperCase() : 'COUNTER'
          })]
        })
      })]
    }), invSubTab === 'live' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsxs("div", {
        className: "row g-3 mb-3",
        children: [/*#__PURE__*/_jsx("div", {
          className: "col-md-3 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-primary",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted fw-bold text-uppercase",
              children: "Total Batches in Store"
            }), /*#__PURE__*/_jsx("h4", {
              className: "fw-bolder text-dark mt-1 mb-0",
              children: summary.total_rows || 0
            })]
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-3 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-success",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted fw-bold text-uppercase",
              children: "Stock Cost Valuation (PTR)"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-success mt-1 mb-0",
              children: ["₹", (summary.total_cost_valuation || 0).toLocaleString('en-IN')]
            })]
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-3 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-warning",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted fw-bold text-uppercase",
              children: "Stock Retail Valuation (MRP)"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-dark mt-1 mb-0",
              children: ["₹", (summary.total_mrp_valuation || 0).toLocaleString('en-IN')]
            })]
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-3 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-info",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted fw-bold text-uppercase",
              children: "Counter / URL"
            }), /*#__PURE__*/_jsxs("h6", {
              className: "fw-bolder text-primary mt-1 mb-0 text-truncate",
              children: ["MedicalStore/", store.store_slug]
            })]
          })
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "d-flex flex-wrap justify-content-between align-items-center gap-2",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "btn-group btn-group-sm",
            children: [/*#__PURE__*/_jsx("button", {
              className: `btn ${filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`,
              onClick: () => setFilter('all'),
              children: "All Stock"
            }), /*#__PURE__*/_jsxs("button", {
              className: `btn ${filter === 'low' ? 'btn-primary' : 'btn-outline-secondary'}`,
              onClick: () => setFilter('low'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-arrow-down-circle me-1"
              }), " Low Stock"]
            }), /*#__PURE__*/_jsxs("button", {
              className: `btn ${filter === 'near_expiry' ? 'btn-warning' : 'btn-outline-secondary'}`,
              onClick: () => setFilter('near_expiry'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-clock-history me-1"
              }), " Near Expiry (<90 Days)"]
            }), /*#__PURE__*/_jsxs("button", {
              className: `btn ${filter === 'expired' ? 'btn-danger' : 'btn-outline-secondary'}`,
              onClick: () => setFilter('expired'),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-shield-x me-1"
              }), " Expired Stock"]
            })]
          }), /*#__PURE__*/_jsx("div", {
            style: {
              maxWidth: 320
            },
            className: "w-100",
            children: /*#__PURE__*/_jsxs("div", {
              className: "input-group input-group-sm",
              children: [/*#__PURE__*/_jsx("span", {
                className: "input-group-text bg-light",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-search"
                })
              }), /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control",
                placeholder: "Search item, molecule, batch...",
                value: searchTerm,
                onChange: e => setSearchTerm(e.target.value)
              })]
            })
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-0 overflow-hidden shadow-sm",
        children: /*#__PURE__*/_jsx("div", {
          className: "table-responsive",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-pos table-hover align-middle mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              className: "table-light",
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  children: "#"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Item & Generic Molecule"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Category"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Batch No"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Location"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Expiry"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "System Qty"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "PTR (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "MRP (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "Valuation (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Status"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  children: "Action"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: loading ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsxs("td", {
                  colSpan: "12",
                  className: "text-center py-5 text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-hourglass-split fs-3 d-block mb-2 text-primary"
                  }), " Loading inventory batches..."]
                })
              }) : filteredStock.length === 0 ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsxs("td", {
                  colSpan: "12",
                  className: "text-center py-5 text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-inboxes fs-3 d-block mb-1 text-secondary"
                  }), "No stock records found matching filters."]
                })
              }) : filteredStock.map((s, idx) => /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("td", {
                  children: idx + 1
                }), /*#__PURE__*/_jsxs("td", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "fw-bold text-dark",
                    children: s.item_name
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-muted",
                    children: [s.generic_name || 'Allopathic', " • HSN: ", s.hsn_code || '3004']
                  })]
                }), /*#__PURE__*/_jsx("td", {
                  children: /*#__PURE__*/_jsx("span", {
                    className: "badge bg-secondary-subtle text-secondary",
                    children: s.category || 'Medicine'
                  })
                }), /*#__PURE__*/_jsx("td", {
                  children: /*#__PURE__*/_jsx("span", {
                    className: "badge bg-light text-dark border fw-bold",
                    children: s.batch_no
                  })
                }), /*#__PURE__*/_jsx("td", {
                  children: /*#__PURE__*/_jsxs("small", {
                    className: "text-muted",
                    children: [s.shelf_no ? `Sh: ${s.shelf_no}` : '', " ", s.rack_no ? `• Rk: ${s.rack_no}` : s.shelf_no ? '' : '-']
                  })
                }), /*#__PURE__*/_jsx("td", {
                  children: s.expiry_display || s.expiry_date
                }), /*#__PURE__*/_jsxs("td", {
                  className: "text-end",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: `fw-bolder fs-6 ${s.current_qty <= (s.min_reorder_level || 5) ? 'text-danger' : 'text-success'}`,
                    children: s.current_qty
                  }), /*#__PURE__*/_jsx("small", {
                    className: "text-muted d-block",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: "Units"
                  })]
                }), /*#__PURE__*/_jsxs("td", {
                  className: "text-end",
                  children: ["₹", s.ptr]
                }), /*#__PURE__*/_jsxs("td", {
                  className: "text-end fw-semibold",
                  children: ["₹", s.mrp]
                }), /*#__PURE__*/_jsxs("td", {
                  className: "text-end fw-bold",
                  children: ["₹", s.cost_valuation]
                }), /*#__PURE__*/_jsx("td", {
                  children: s.is_expired ? /*#__PURE__*/_jsx("span", {
                    className: "badge-expired",
                    children: "EXPIRED"
                  }) : s.is_near_expiry ? /*#__PURE__*/_jsx("span", {
                    className: "badge-near-expiry",
                    children: "Near Expiry"
                  }) : /*#__PURE__*/_jsx("span", {
                    className: "badge-fefo",
                    children: "Good Stock"
                  })
                }), /*#__PURE__*/_jsx("td", {
                  className: "text-center",
                  children: /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill shadow-xs d-inline-flex align-items-center gap-1",
                    title: "Check physical stock against system record & adjust",
                    onClick: () => openReconcileModal(s),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-sliders2"
                    }), " Reconcile"]
                  })
                })]
              }, s.stock_id))
            })]
          })
        })
      })]
    }), invSubTab === 'physical_audit' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3 border-start border-4 border-info",
        children: /*#__PURE__*/_jsxs("div", {
          className: "d-flex flex-wrap justify-content-between align-items-center gap-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex flex-wrap align-items-center gap-2",
            children: [/*#__PURE__*/_jsxs("div", {
              style: {
                minWidth: 180
              },
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small fw-bold text-muted mb-1",
                children: "Filter by Shelf / Rack"
              }), /*#__PURE__*/_jsxs("select", {
                className: "form-select form-select-sm",
                value: cycleRackFilter,
                onChange: e => setCycleRackFilter(e.target.value),
                children: [/*#__PURE__*/_jsxs("option", {
                  value: "all",
                  children: ["All Racks & Shelves (", stock.length, " batches)"]
                }), uniqueRacks.map(r => /*#__PURE__*/_jsxs("option", {
                  value: r,
                  children: ["Rack: ", r]
                }, r))]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              style: {
                minWidth: 220
              },
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label small fw-bold text-muted mb-1",
                children: "Search Medicine / Batch"
              }), /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm",
                placeholder: "Type item or batch...",
                value: cycleSearch,
                onChange: e => setCycleSearch(e.target.value)
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "text-end",
              children: [/*#__PURE__*/_jsx("div", {
                className: "small text-muted fw-bold text-uppercase",
                children: "Discrepancies Found"
              }), /*#__PURE__*/_jsxs("div", {
                className: "fw-bolder fs-5 text-dark",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: cycleDiscrepancyCount > 0 ? 'text-danger' : 'text-success',
                  children: [cycleDiscrepancyCount, " items"]
                }), cycleDiscrepancyCount > 0 && /*#__PURE__*/_jsxs("span", {
                  className: "small text-muted ms-1",
                  children: ["(₹", cycleDiscrepancyValue.toFixed(2), ")"]
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-primary btn-sm px-3 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5",
              onClick: handleCommitCycleAudit,
              disabled: isCommittingCycle || cycleDiscrepancyCount === 0,
              children: isCommittingCycle ? /*#__PURE__*/_jsxs(_Fragment, {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "spinner-border spinner-border-sm"
                }), " Committing..."]
              }) : /*#__PURE__*/_jsxs(_Fragment, {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-cloud-arrow-up-fill"
                }), " Commit Cycle Count (", cycleDiscrepancyCount, ")"]
              })
            })]
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-0 overflow-hidden shadow-sm",
        children: /*#__PURE__*/_jsx("div", {
          className: "table-responsive",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-pos table-hover align-middle mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              className: "table-light",
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  children: "#"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Medicine Item"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Batch No"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Location"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  style: {
                    width: 110
                  },
                  children: "System Qty"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  style: {
                    width: 140
                  },
                  children: "Physical Count"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  style: {
                    width: 110
                  },
                  children: "Variance"
                }), /*#__PURE__*/_jsx("th", {
                  style: {
                    minWidth: 170
                  },
                  children: "Audit Reason"
                }), /*#__PURE__*/_jsx("th", {
                  style: {
                    minWidth: 180
                  },
                  children: "Remarks / Notes"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  children: "Action"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: cycleFilteredStock.length === 0 ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsx("td", {
                  colSpan: "10",
                  className: "text-center py-5 text-muted",
                  children: "No items found in this shelf/rack."
                })
              }) : cycleFilteredStock.map((item, idx) => {
                const countData = cycleCounts[item.batch_id] || {};
                const hasInput = countData.physical_qty !== undefined && countData.physical_qty !== '';
                const pVal = hasInput ? Number(countData.physical_qty) : null;
                const sVal = Number(item.current_qty);
                const diff = pVal !== null ? pVal - sVal : 0;
                const rate = Number(item.ptr || item.mrp || 0);
                return /*#__PURE__*/_jsxs("tr", {
                  className: diff !== 0 && hasInput ? diff < 0 ? 'table-danger-subtle' : 'table-info-subtle' : '',
                  children: [/*#__PURE__*/_jsx("td", {
                    children: idx + 1
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsx("div", {
                      className: "fw-bold text-dark",
                      children: item.item_name
                    }), /*#__PURE__*/_jsxs("small", {
                      className: "text-muted",
                      children: [item.generic_name, " • Packing: ", item.unit_pack || 'Unit']
                    })]
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "badge bg-light text-dark border fw-bold",
                      children: item.batch_no
                    }), /*#__PURE__*/_jsxs("small", {
                      className: "text-muted d-block",
                      style: {
                        fontSize: '0.7rem'
                      },
                      children: ["Exp: ", item.expiry_display || item.expiry_date]
                    })]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsxs("small", {
                      className: "text-muted",
                      children: [item.shelf_no ? `Sh: ${item.shelf_no}` : '', " ", item.rack_no ? `• Rk: ${item.rack_no}` : item.shelf_no ? '' : 'Normal']
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-center",
                    children: /*#__PURE__*/_jsx("span", {
                      className: "badge bg-secondary-subtle text-secondary fs-6 px-2 py-1 fw-bolder",
                      children: sVal
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-center",
                    children: /*#__PURE__*/_jsx("input", {
                      type: "number",
                      min: "0",
                      className: `form-control form-control-sm text-center fw-bolder ${hasInput ? diff === 0 ? 'is-valid' : 'is-invalid' : ''}`,
                      placeholder: sVal,
                      value: countData.physical_qty !== undefined ? countData.physical_qty : '',
                      onChange: e => handleCycleCountChange(item.batch_id, e.target.value)
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-center",
                    children: hasInput ? diff === 0 ? /*#__PURE__*/_jsx("span", {
                      className: "badge bg-success-subtle text-success fw-bold px-2 py-1",
                      children: "✓ Match (0)"
                    }) : diff < 0 ? /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-danger text-white fw-bold px-2 py-1",
                      title: `Shortage: ₹${(Math.abs(diff) * rate).toFixed(2)}`,
                      children: [diff, " Units"]
                    }) : /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-info text-dark fw-bold px-2 py-1",
                      title: `Surplus: ₹${(diff * rate).toFixed(2)}`,
                      children: ["+", diff, " Units"]
                    }) : /*#__PURE__*/_jsx("span", {
                      className: "text-muted small",
                      children: "-"
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsxs("select", {
                      className: "form-select form-select-sm",
                      disabled: !hasInput || diff === 0,
                      value: countData.audit_type || 'PHYSICAL_VARIATION',
                      onChange: e => handleCycleReasonChange(item.batch_id, e.target.value),
                      children: [/*#__PURE__*/_jsx("option", {
                        value: "PHYSICAL_VARIATION",
                        children: "Shelf Miscount"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "DAMAGE",
                        children: "Damaged Packaging"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "BREAKAGE",
                        children: "Bottle Broken / Spilt"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "EXPIRY_DUMP",
                        children: "Expired On Shelf"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "SURPLUS_FOUND",
                        children: "Surplus Found"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "THEFT_LOSS",
                        children: "Unaccounted Shortage"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      disabled: !hasInput,
                      placeholder: "Note / Shelf position",
                      value: countData.remarks || '',
                      onChange: e => handleCycleRemarksChange(item.batch_id, e.target.value)
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-center",
                    children: hasInput && /*#__PURE__*/_jsx("button", {
                      type: "button",
                      className: "btn btn-xs btn-outline-secondary py-0.5 px-1.5",
                      title: "Clear entered count",
                      onClick: () => handleCycleCountChange(item.batch_id, ''),
                      children: /*#__PURE__*/_jsx("i", {
                        className: "bi bi-x"
                      })
                    })
                  })]
                }, item.batch_id);
              })
            })]
          })
        })
      })]
    }), invSubTab === 'audit_logs' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "row g-2 align-items-center",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "col-md-3 col-sm-6",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label small fw-bold text-muted mb-1",
              children: "Audit Type / Reason"
            }), /*#__PURE__*/_jsxs("select", {
              className: "form-select form-select-sm",
              value: auditFilterType,
              onChange: e => setAuditFilterType(e.target.value),
              children: [/*#__PURE__*/_jsx("option", {
                value: "all",
                children: "All Audit Events & Movements"
              }), /*#__PURE__*/_jsx("option", {
                value: "PHYSICAL_VARIATION",
                children: "Physical Variation (Shelf vs System)"
              }), /*#__PURE__*/_jsx("option", {
                value: "DAMAGE",
                children: "Damaged Packaging"
              }), /*#__PURE__*/_jsx("option", {
                value: "BREAKAGE",
                children: "Breakage / Spillage"
              }), /*#__PURE__*/_jsx("option", {
                value: "EXPIRY_DUMP",
                children: "Expired Stock Dump"
              }), /*#__PURE__*/_jsx("option", {
                value: "SURPLUS_FOUND",
                children: "Surplus Stock Found"
              }), /*#__PURE__*/_jsx("option", {
                value: "THEFT_LOSS",
                children: "Unaccounted Loss / Pilferage"
              }), /*#__PURE__*/_jsx("option", {
                value: "PURCHASE",
                children: "Purchase Inward"
              }), /*#__PURE__*/_jsx("option", {
                value: "SALE",
                children: "Sale Dispensation"
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-md-3 col-sm-6",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label small fw-bold text-muted mb-1",
              children: "Search Medicine / Auditor"
            }), /*#__PURE__*/_jsxs("div", {
              className: "input-group input-group-sm",
              children: [/*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control",
                placeholder: "Item, batch, auditor...",
                value: auditSearch,
                onChange: e => setAuditSearch(e.target.value),
                onKeyDown: e => {
                  if (e.key === 'Enter') loadAuditLogs();
                }
              }), /*#__PURE__*/_jsx("button", {
                className: "btn btn-outline-secondary",
                onClick: loadAuditLogs,
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-search"
                })
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-md-2 col-sm-4",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label small fw-bold text-muted mb-1",
              children: "From Date"
            }), /*#__PURE__*/_jsx("input", {
              type: "date",
              className: "form-control form-control-sm",
              value: auditFromDate,
              onChange: e => setAuditFromDate(e.target.value)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-md-2 col-sm-4",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label small fw-bold text-muted mb-1",
              children: "To Date"
            }), /*#__PURE__*/_jsx("input", {
              type: "date",
              className: "form-control form-control-sm",
              value: auditToDate,
              onChange: e => setAuditToDate(e.target.value)
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "col-md-2 col-sm-4 d-flex align-items-end",
            children: /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-1",
              onClick: loadAuditLogs,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-arrow-clockwise"
              }), " Refresh Logs"]
            })
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-0 overflow-hidden shadow-sm",
        children: /*#__PURE__*/_jsx("div", {
          className: "table-responsive",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-pos table-hover align-middle mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              className: "table-light",
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  children: "# & Timestamp"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Medicine Item"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Batch No"
                }), /*#__PURE__*/_jsx("th", {
                  children: "System Qty"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Physical Qty"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  children: "Variance Qty"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "Valuation (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Audit Event / Reason"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Remarks"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Conducted By"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: loadingAuditLogs ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsxs("td", {
                  colSpan: "10",
                  className: "text-center py-5 text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-hourglass-split fs-3 d-block mb-2 text-primary"
                  }), " Loading audit trail..."]
                })
              }) : auditLogs.length === 0 ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsxs("td", {
                  colSpan: "10",
                  className: "text-center py-5 text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-clipboard-check fs-3 d-block mb-1 text-secondary"
                  }), "No stock audit events recorded yet for this criteria."]
                })
              }) : auditLogs.map(log => {
                const vQty = Number(log.variation_qty || 0);
                return /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "fw-bold text-dark",
                      children: ["#", log.audit_id]
                    }), /*#__PURE__*/_jsx("small", {
                      className: "text-muted",
                      style: {
                        fontSize: '0.72rem'
                      },
                      children: log.created_at
                    })]
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsx("div", {
                      className: "fw-bold text-dark",
                      children: log.item_name || `Item #${log.item_id}`
                    }), /*#__PURE__*/_jsxs("small", {
                      className: "text-muted",
                      children: [log.generic_name, " • ", log.unit_pack || 'Unit']
                    })]
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "badge bg-light text-dark border fw-bold",
                      children: log.batch_no || `Batch #${log.batch_id}`
                    }), log.expiry_date && /*#__PURE__*/_jsxs("small", {
                      className: "text-muted d-block",
                      style: {
                        fontSize: '0.7rem'
                      },
                      children: ["Exp: ", log.expiry_date]
                    })]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: "fw-semibold text-secondary",
                      children: log.system_qty
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: "fw-bold text-dark",
                      children: log.physical_qty
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-center",
                    children: vQty === 0 ? /*#__PURE__*/_jsx("span", {
                      className: "badge bg-secondary-subtle text-secondary fw-semibold",
                      children: "0"
                    }) : vQty < 0 ? /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-danger text-white fw-bold px-2 py-1",
                      children: [vQty, " Units"]
                    }) : /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-success text-white fw-bold px-2 py-1",
                      children: ["+", vQty, " Units"]
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end fw-bold",
                    children: ["₹", Number(log.total_value || 0).toFixed(2)]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: `badge ${log.audit_type === 'PHYSICAL_VARIATION' ? 'bg-warning-subtle text-dark border border-warning' : log.audit_type === 'DAMAGE' || log.audit_type === 'BREAKAGE' ? 'bg-danger-subtle text-danger border border-danger' : log.audit_type === 'EXPIRY_DUMP' ? 'bg-dark text-white' : log.audit_type === 'SURPLUS_FOUND' ? 'bg-info-subtle text-info border border-info' : log.audit_type === 'PURCHASE' ? 'bg-success-subtle text-success border border-success' : 'bg-secondary-subtle text-secondary'}`,
                      children: log.audit_type ? log.audit_type.replace(/_/g, ' ') : 'AUDIT'
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("small", {
                      className: "text-muted text-break",
                      children: log.remarks || '-'
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-light text-dark border",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-person me-1"
                      }), log.conducted_by_name || 'Staff']
                    })
                  })]
                }, log.audit_id);
              })
            })]
          })
        })
      })]
    }), reconcileModalItem && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)',
        zIndex: 10650
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-dialog-centered",
        style: {
          maxWidth: 540
        },
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-2xl rounded-4 overflow-hidden",
          style: {
            background: '#0f172a',
            color: '#f8fafc',
            border: '1px solid #334155'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header border-bottom border-secondary py-3 px-4",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "p-2 rounded-3 bg-primary bg-opacity-25 text-primary",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-sliders2 fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title fw-bold text-white mb-0",
                  children: "Stock Reconciliation & Discrepancy Audit"
                }), /*#__PURE__*/_jsxs("small", {
                  className: "text-slate-400",
                  style: {
                    color: '#94a3b8'
                  },
                  children: [store.store_name, " • Physical vs System Shelf Count"]
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setReconcileModalItem(null)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-body p-4",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "p-3 rounded-3 mb-3 border border-secondary",
              style: {
                background: '#1e293b'
              },
              children: [/*#__PURE__*/_jsx("div", {
                className: "fw-bold text-white fs-6",
                children: reconcileModalItem.item_name
              }), /*#__PURE__*/_jsxs("div", {
                className: "text-slate-400 small mb-2",
                style: {
                  color: '#94a3b8'
                },
                children: [reconcileModalItem.generic_name, " • Unit Pack: ", reconcileModalItem.unit_pack || 'Unit']
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2 small",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "text-slate-400",
                    style: {
                      color: '#94a3b8'
                    },
                    children: "Batch No:"
                  }), " ", /*#__PURE__*/_jsx("strong", {
                    className: "text-cyan-300",
                    style: {
                      color: '#67e8f9'
                    },
                    children: reconcileModalItem.batch_no
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "text-slate-400",
                    style: {
                      color: '#94a3b8'
                    },
                    children: "Expiry:"
                  }), " ", /*#__PURE__*/_jsx("strong", {
                    className: "text-white",
                    children: reconcileModalItem.expiry_display || reconcileModalItem.expiry_date
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "text-slate-400",
                    style: {
                      color: '#94a3b8'
                    },
                    children: "PTR / Cost:"
                  }), " ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-white",
                    children: ["₹", reconcileModalItem.ptr]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-6",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "text-slate-400",
                    style: {
                      color: '#94a3b8'
                    },
                    children: "MRP:"
                  }), " ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-white",
                    children: ["₹", reconcileModalItem.mrp]
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "row g-3 mb-3",
              children: [/*#__PURE__*/_jsx("div", {
                className: "col-6",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "p-3 rounded-3 border border-secondary text-center",
                  style: {
                    background: '#1e293b'
                  },
                  children: [/*#__PURE__*/_jsx("small", {
                    className: "text-slate-400 text-uppercase fw-bold d-block",
                    style: {
                      color: '#94a3b8'
                    },
                    children: "System Stock"
                  }), /*#__PURE__*/_jsx("span", {
                    className: "fs-3 fw-bolder text-white",
                    children: reconcileModalItem.current_qty
                  }), /*#__PURE__*/_jsx("span", {
                    className: "small text-slate-400 ms-1",
                    style: {
                      color: '#94a3b8'
                    },
                    children: "Units"
                  })]
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "col-6",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "p-3 rounded-3 border border-primary text-center",
                  style: {
                    background: 'rgba(14, 165, 233, 0.15)'
                  },
                  children: [/*#__PURE__*/_jsx("small", {
                    className: "text-cyan-300 text-uppercase fw-bold d-block",
                    style: {
                      color: '#38bdf8'
                    },
                    children: "Physical Shelf Count"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    min: "0",
                    className: "form-control form-control-lg text-center fw-bolder text-cyan-300 border-primary mt-1 bg-dark",
                    value: reconcilePhysicalQty,
                    onChange: e => setReconcilePhysicalQty(e.target.value),
                    autoFocus: true
                  })]
                })
              })]
            }), (() => {
              const sys = Number(reconcileModalItem.current_qty || 0);
              const phy = Number(reconcilePhysicalQty !== '' ? reconcilePhysicalQty : sys);
              const diff = phy - sys;
              const rate = Number(reconcileModalItem.ptr || reconcileModalItem.mrp || 0);
              const val = Math.abs(diff) * rate;
              if (diff === 0) {
                return /*#__PURE__*/_jsxs("div", {
                  className: "p-2.5 rounded-3 border border-success d-flex align-items-center gap-2 mb-3 small",
                  style: {
                    background: 'rgba(16, 185, 129, 0.15)',
                    color: '#34d399'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle-fill fs-5"
                  }), /*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsx("strong", {
                      children: "Counts Match Perfectly."
                    }), " Physical shelf count equals system record (0 discrepancy)."]
                  })]
                });
              } else if (diff < 0) {
                return /*#__PURE__*/_jsxs("div", {
                  className: "p-2.5 rounded-3 border border-danger d-flex align-items-center gap-2 mb-3 small",
                  style: {
                    background: 'rgba(239, 68, 68, 0.15)',
                    color: '#f87171'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-exclamation-octagon-fill fs-5"
                  }), /*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsxs("strong", {
                      children: ["Shortage Detected: ", Math.abs(diff), " Units Short"]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-white-50",
                      children: ["Estimated Discrepancy Value: ", /*#__PURE__*/_jsxs("strong", {
                        children: ["₹", val.toFixed(2)]
                      }), " (System ", sys, " > Shelf ", phy, ")"]
                    })]
                  })]
                });
              } else {
                return /*#__PURE__*/_jsxs("div", {
                  className: "p-2.5 rounded-3 border border-info d-flex align-items-center gap-2 mb-3 small",
                  style: {
                    background: 'rgba(14, 165, 233, 0.15)',
                    color: '#38bdf8'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-plus-circle-fill fs-5"
                  }), /*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsxs("strong", {
                      children: ["Surplus Stock: +", diff, " Extra Units Found"]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-white-50",
                      children: ["Estimated Valuation: ", /*#__PURE__*/_jsxs("strong", {
                        children: ["₹", val.toFixed(2)]
                      }), " (Shelf ", phy, " > System ", sys, ")"]
                    })]
                  })]
                });
              }
            })(), /*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label text-slate-300 small fw-bold",
                style: {
                  color: '#cbd5e1'
                },
                children: "Audit Reason / Discrepancy Cause"
              }), /*#__PURE__*/_jsxs("select", {
                className: "form-select form-select-sm bg-dark text-white border-secondary",
                value: reconcileAuditType,
                onChange: e => setReconcileAuditType(e.target.value),
                children: [/*#__PURE__*/_jsx("option", {
                  value: "PHYSICAL_VARIATION",
                  children: "Physical Count Variation (Shelf vs System Count)"
                }), /*#__PURE__*/_jsx("option", {
                  value: "DAMAGE",
                  children: "Damaged Packaging / Foil Broken"
                }), /*#__PURE__*/_jsx("option", {
                  value: "BREAKAGE",
                  children: "Breakage / Spillage / Bottle Leakage"
                }), /*#__PURE__*/_jsx("option", {
                  value: "EXPIRY_DUMP",
                  children: "Expired Stock (Disposed from Shelf)"
                }), /*#__PURE__*/_jsx("option", {
                  value: "SURPLUS_FOUND",
                  children: "Unrecorded Surplus Stock Found"
                }), /*#__PURE__*/_jsx("option", {
                  value: "THEFT_LOSS",
                  children: "Unaccounted Discrepancy / Shrinkage"
                }), /*#__PURE__*/_jsx("option", {
                  value: "AUDIT_CORRECTION",
                  children: "Manual Inventory Correction"
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "mb-3",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label text-slate-300 small fw-bold",
                style: {
                  color: '#cbd5e1'
                },
                children: "Audit Remarks & Shelf Notes"
              }), /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm bg-dark text-white border-secondary",
                placeholder: "e.g., Verified on shelf during physical stock take",
                value: reconcileRemarks,
                onChange: e => setReconcileRemarks(e.target.value)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center justify-content-between p-2.5 rounded-3 border border-secondary small",
              style: {
                background: '#1e293b'
              },
              children: [/*#__PURE__*/_jsxs("span", {
                className: "text-slate-400",
                style: {
                  color: '#94a3b8'
                },
                children: ["Audited By: ", /*#__PURE__*/_jsx("strong", {
                  className: "text-white",
                  children: activeOperator ? activeOperator.full_name : 'Store Staff'
                }), " (", activeOperator?.user_role || 'auditor', ")"]
              }), /*#__PURE__*/_jsxs("span", {
                className: "text-slate-400",
                style: {
                  color: '#94a3b8'
                },
                children: ["Date: ", /*#__PURE__*/_jsx("strong", {
                  className: "text-white",
                  children: new Date().toLocaleDateString('en-IN')
                })]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-footer border-top border-secondary py-2 px-4",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-outline-secondary",
              onClick: () => setReconcileModalItem(null),
              children: "Cancel"
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-primary px-3 fw-bold d-flex align-items-center gap-1.5",
              onClick: handleReconcileSubmit,
              disabled: isSubmittingReconcile,
              children: isSubmittingReconcile ? /*#__PURE__*/_jsxs(_Fragment, {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "spinner-border spinner-border-sm"
                }), " Reconciling..."]
              }) : /*#__PURE__*/_jsxs(_Fragment, {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-check-lg"
                }), " Commit Stock Adjustment"]
              })
            })]
          })]
        })
      })
    })]
  });
}

// =========================================================================
// 3. INWARD PURCHASES, SUPPLIERS & EXCEL / CSV IMPORT
// =========================================================================
function PurchaseView({
  store,
  notify,
  activeOperator
}) {
  if (!store) return null;
  const [subTab, setSubTab] = useState('inward'); // inward, excel, suppliers
  const [suppliers, setSuppliers] = useState([]);
  const [selectedSupplierId, setSelectedSupplierId] = useState('');
  const [invoiceNo, setInvoiceNo] = useState('');
  const [invoiceDate, setInvoiceDate] = useState(new Date().toISOString().split('T')[0]);

  // Supplier Add/Edit Modal State
  const [showSupplierModal, setShowSupplierModal] = useState(false);
  const [supplierSearch, setSupplierSearch] = useState('');
  const [isSavingSupplier, setIsSavingSupplier] = useState(false);
  const [supplierForm, setSupplierForm] = useState({
    supplier_id: 0,
    supplier_name: '',
    contact_person: '',
    phone: '',
    email: '',
    gstin: '',
    pan_no: '',
    dl_no_20b: '',
    dl_no_21b: '',
    state_code: '07',
    credit_days: 30,
    address: '',
    bank_details: ''
  });

  // Master Medicine Autocomplete & Add Modal State
  const [activeSearchRow, setActiveSearchRow] = useState(null);
  const [suggestions, setSuggestions] = useState([]);
  const [isSearchingMeds, setIsSearchingMeds] = useState(false);
  const [selectedSuggestionIndex, setSelectedSuggestionIndex] = useState(-1);
  const activeSuggestionRef = useRef(null);
  const suggestionsDropdownRef = useRef(null);
  const [showMasterModal, setShowMasterModal] = useState(false);
  const [masterTargetRow, setMasterTargetRow] = useState(null);
  const [supportData, setSupportData] = useState({
    formulations: [],
    companies: [],
    gst_rates: [0, 5, 18, 40, 12, 28],
    schedules: []
  });
  const [isSavingMaster, setIsSavingMaster] = useState(false);
  const [masterForm, setMasterForm] = useState({
    item_name: '',
    generic_name: '',
    formulation: 'Tablet',
    units_per_pack: 10,
    packing: '10',
    hsn_code: '3004',
    gst_rate: 5,
    drug_schedule: 'Schedule H',
    company_name: '',
    min_reorder_level: 10
  });
  const searchTimerRef = useRef(null);
  const [rows, setRows] = useState([]);

  // --- Delivery Challans & Product History States ---
  const [isChallan, setIsChallan] = useState(false);
  const [supplierChallans, setSupplierChallans] = useState([]);
  const [isLoadingChallans, setIsLoadingChallans] = useState(false);
  const [convertedChallanId, setConvertedChallanId] = useState(null);
  const [productHistory, setProductHistory] = useState([]);
  const [isLoadingProductHistory, setIsLoadingProductHistory] = useState(false);
  const [historyTargetItemName, setHistoryTargetItemName] = useState('');

  // --- Active Item Form State (Pharma Purchase Input - Image 2) ---
  const initialItemForm = {
    item_id: 0,
    product_master_id: 0,
    item_name: '',
    generic_name: '',
    category: 'Tablet',
    units_per_pack: 10,
    batch_no: '',
    exp_month: String(new Date().getMonth() + 1).padStart(2, '0'),
    exp_year: String(new Date().getFullYear() + 2).slice(-2),
    mrp: '',
    qty_packs: 1,
    free_qty_packs: 0,
    ptr: '',
    amount: 0,
    discount_pct: 0,
    sch_disc_amount: 0,
    sch_disc_pct: 0,
    taxable_amount: 0,
    cgst_pct: 6.0,
    sgst_pct: 6.0,
    net_amount: 0,
    selling_price: '',
    hsn_code: '3004',
    storage_type: 'Normal',
    shelf_no: '',
    rack_no: '',
    challan_item_ref_id: 0
  };
  const [itemForm, setItemForm] = useState(initialItemForm);
  const [editingItemIndex, setEditingItemIndex] = useState(null);
  const recomputeItemForm = updated => {
    let ptr = parseFloat(updated.ptr) || 0;
    const qty = parseFloat(updated.qty_packs) || 0;
    const disc = parseFloat(updated.discount_pct) || 0;
    const schAmt = parseFloat(updated.sch_disc_amount) || 0;
    const schDisc = parseFloat(updated.sch_disc_pct) || 0;
    if (schDisc > 0) {
      ptr = ptr - ptr * schDisc / 100;
    }
    let amount = ptr * qty;
    if (schAmt > 0) {
      amount = Math.max(0, amount - schAmt);
    }
    const taxable = amount - amount * disc / 100;
    const cgst = parseFloat(updated.cgst_pct) || 0;
    const sgst = parseFloat(updated.sgst_pct) || 0;
    const cgstAmt = taxable * (cgst / 100);
    const sgstAmt = taxable * (sgst / 100);
    const net = taxable + cgstAmt + sgstAmt;
    let sellingPrice = updated.selling_price;
    if (!sellingPrice && updated.mrp) {
      sellingPrice = updated.mrp;
    }
    return {
      ...updated,
      amount: Number(amount.toFixed(2)),
      taxable_amount: Number(taxable.toFixed(2)),
      net_amount: Number(net.toFixed(2)),
      selling_price: sellingPrice
    };
  };
  const handleItemFormChange = (field, val) => {
    setItemForm(prev => {
      const next = {
        ...prev,
        [field]: val
      };
      if (field === 'mrp' && (!prev.selling_price || prev.selling_price == prev.mrp)) {
        next.selling_price = val;
      }
      return recomputeItemForm(next);
    });
  };
  const resetItemForm = (shouldFocusSearch = false) => {
    setItemForm(initialItemForm);
    setEditingItemIndex(null);
    setSelectedSuggestionIndex(-1);
    setActiveSearchRow(false);
    setSuggestions([]);
    if (shouldFocusSearch) {
      setTimeout(() => {
        const searchInput = document.getElementById('pharma_product_search_input');
        if (searchInput) {
          searchInput.focus();
          searchInput.select();
        }
      }, 80);
    }
  };
  const handleFieldKeyDown = (e, nextFieldId) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      handleAddOrUpdateItem();
      return;
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      if (nextFieldId === 'submit') {
        handleAddOrUpdateItem();
      } else if (nextFieldId) {
        const el = document.getElementById(nextFieldId);
        if (el) {
          el.focus();
          if (el.select) el.select();
        }
      }
    }
  };
  const billTotals = useMemo(() => {
    let totalTaxable = 0;
    let totalCgst = 0;
    let totalSgst = 0;
    let totalNet = 0;
    rows.forEach(r => {
      const taxable = Number(r.taxable_amount) || 0;
      const cgst = Number(r.cgst_amount) || taxable * ((Number(r.cgst_pct) || (Number(r.gst_rate) || 12) / 2) / 100);
      const sgst = Number(r.sgst_amount) || taxable * ((Number(r.sgst_pct) || (Number(r.gst_rate) || 12) / 2) / 100);
      const net = Number(r.net_amount) || taxable + cgst + sgst;
      totalTaxable += taxable;
      totalCgst += cgst;
      totalSgst += sgst;
      totalNet += net;
    });
    return {
      taxable: totalTaxable.toFixed(2),
      cgst: totalCgst.toFixed(2),
      sgst: totalSgst.toFixed(2),
      net: totalNet.toFixed(2)
    };
  }, [rows]);
  const fetchSupplierChallans = async supplierId => {
    if (!supplierId) {
      setSupplierChallans([]);
      return;
    }
    setIsLoadingChallans(true);
    try {
      const res = await fetch(API_BASE + 'supplier-challans/' + supplierId);
      const data = await res.json();
      if (data.status && data.challans) {
        setSupplierChallans(data.challans);
      } else {
        setSupplierChallans([]);
      }
    } catch (e) {
      console.error('fetchSupplierChallans error:', e);
      setSupplierChallans([]);
    } finally {
      setIsLoadingChallans(false);
    }
  };
  const fetchProductPurchaseHistory = async (itemId, itemName = '') => {
    if (!itemId) {
      setProductHistory([]);
      setHistoryTargetItemName('');
      return;
    }
    setIsLoadingProductHistory(true);
    setHistoryTargetItemName(itemName || 'Selected Product');
    try {
      const res = await fetch(API_BASE + 'product-purchase-history/' + itemId);
      const data = await res.json();
      if (data.status && data.history) {
        setProductHistory(data.history);
      } else {
        setProductHistory([]);
      }
      if (data.status && data.latest_info) {
        const info = data.latest_info;
        setItemForm(prev => recomputeItemForm({
          ...prev,
          batch_no: info.batch_no || prev.batch_no,
          exp_month: info.exp_month || prev.exp_month,
          exp_year: info.exp_year || prev.exp_year,
          expiry_date: info.expiry_date || prev.expiry_date,
          mrp: info.mrp !== undefined && info.mrp !== '' ? Number(info.mrp) : prev.mrp,
          ptr: info.ptr !== undefined && info.ptr !== '' ? Number(info.ptr) : prev.ptr,
          discount_pct: info.discount_pct !== undefined ? Number(info.discount_pct) : prev.discount_pct,
          gst_rate: info.gst_rate !== undefined ? Number(info.gst_rate) : prev.gst_rate,
          cgst_pct: info.cgst_pct !== undefined ? Number(info.cgst_pct) : prev.cgst_pct,
          sgst_pct: info.sgst_pct !== undefined ? Number(info.sgst_pct) : prev.sgst_pct,
          hsn_code: info.hsn_code || prev.hsn_code || '3004',
          shelf_no: info.shelf_no || prev.shelf_no,
          rack_no: info.rack_no || prev.rack_no,
          storage_type: info.storage_type || prev.storage_type || 'Normal',
          units_per_pack: info.units_per_pack ? Number(info.units_per_pack) : prev.units_per_pack || 10,
          selling_price: info.selling_price ? Number(info.selling_price) : info.mrp ? Number(info.mrp) : prev.selling_price
        }));
      }
    } catch (e) {
      console.error('fetchProductPurchaseHistory error:', e);
      setProductHistory([]);
    } finally {
      setIsLoadingProductHistory(false);
    }
  };
  const handleAddOrUpdateItem = () => {
    if (!itemForm.item_name.trim()) {
      notify('Please select or enter a medicine name.', 'error');
      return;
    }
    if (!itemForm.batch_no.trim()) {
      notify('Please enter a Batch Number.', 'error');
      return;
    }
    const qtyPacks = Number(itemForm.qty_packs);
    if (isNaN(qtyPacks) || qtyPacks === 0) {
      notify('Quantity in packs cannot be 0. Enter positive qty for stock entry, or negative qty for return/debit.', 'error');
      return;
    }
    const isReturnItem = qtyPacks < 0;
    const yy = String(itemForm.exp_year).length === 2 ? '20' + itemForm.exp_year : String(itemForm.exp_year);
    const mm = String(itemForm.exp_month).padStart(2, '0');
    const expiryDate = `${yy}-${mm}-01`;
    const rowItem = recomputeItemForm({
      ...itemForm,
      expiry_date: expiryDate,
      exp_month: mm,
      exp_year: itemForm.exp_year,
      is_return: isReturnItem,
      gst_rate: (Number(itemForm.cgst_pct) || 0) + (Number(itemForm.sgst_pct) || 0)
    });
    if (editingItemIndex !== null && editingItemIndex >= 0 && editingItemIndex < rows.length) {
      const nextRows = [...rows];
      nextRows[editingItemIndex] = rowItem;
      setRows(nextRows);
      setEditingItemIndex(null);
      notify(`Updated "${rowItem.item_name}" in invoice items list.`, 'success');
    } else {
      setRows(prev => [...prev, rowItem]);
      if (isReturnItem) {
        notify(`Added Return / Debit item "${rowItem.item_name}" (${rowItem.qty_packs} packs, ₹${rowItem.net_amount}) to invoice.`, 'warning');
      } else {
        notify(`Added "${rowItem.item_name}" to invoice items list.`, 'success');
      }
    }
    resetItemForm(true);
  };
  const handleEditRow = index => {
    const r = rows[index];
    if (!r) return;
    setEditingItemIndex(index);
    let expMonth = r.exp_month;
    let expYear = r.exp_year;
    if (!expMonth && r.expiry_date) {
      const parts = r.expiry_date.split('-');
      if (parts.length >= 2) {
        expYear = parts[0].slice(-2);
        expMonth = parts[1];
      }
    }
    setItemForm(recomputeItemForm({
      ...initialItemForm,
      ...r,
      exp_month: expMonth || '12',
      exp_year: expYear || '28',
      cgst_pct: r.cgst_pct !== undefined ? r.cgst_pct : (r.gst_rate || 12) / 2,
      sgst_pct: r.sgst_pct !== undefined ? r.sgst_pct : (r.gst_rate || 12) / 2
    }));
    if (r.item_id > 0) {
      fetchProductPurchaseHistory(r.item_id, r.item_name);
    }
    setTimeout(() => {
      const qtyInput = document.getElementById('pharma_qty_input');
      if (qtyInput) {
        qtyInput.focus();
        qtyInput.select();
      }
    }, 120);
  };
  const includeChallanItem = (challan, cItem) => {
    let expMonth = '12';
    let expYear = '28';
    if (cItem.expiry_date) {
      const parts = cItem.expiry_date.split('-');
      if (parts.length >= 2) {
        expYear = parts[0].slice(-2);
        expMonth = parts[1];
      }
    }
    const cgst = (cItem.gst_rate || 12) / 2;
    const sgst = (cItem.gst_rate || 12) / 2;
    const rowItem = recomputeItemForm({
      item_id: cItem.item_id || 0,
      product_master_id: 0,
      item_name: cItem.item_name,
      generic_name: cItem.generic_name || '',
      category: cItem.category || 'Tablet',
      units_per_pack: Number(cItem.units_per_pack) || 10,
      batch_no: cItem.batch_no || 'CHL-BATCH',
      exp_month: expMonth,
      exp_year: expYear,
      expiry_date: cItem.expiry_date || `${'20' + expYear}-${expMonth}-01`,
      mrp: Number(cItem.mrp) || 0,
      qty_packs: Number(cItem.qty_packs) || 1,
      free_qty_packs: Number(cItem.free_qty_packs) || 0,
      ptr: Number(cItem.ptr) || 0,
      discount_pct: Number(cItem.discount_pct) || 0,
      sch_disc_amount: Number(cItem.sch_disc_amount) || 0,
      sch_disc_pct: Number(cItem.sch_disc_pct) || 0,
      cgst_pct: cgst,
      sgst_pct: sgst,
      gst_rate: cItem.gst_rate || 12,
      selling_price: Number(cItem.selling_price) || Number(cItem.mrp) || 0,
      hsn_code: cItem.hsn_code || '3004',
      storage_type: cItem.storage_type || 'Normal',
      shelf_no: cItem.shelf_no || '',
      rack_no: cItem.rack_no || '',
      challan_item_ref_id: cItem.challan_ref_id || challan.id
    });
    setConvertedChallanId(challan.id);
    setRows(prev => [...prev, rowItem]);
    notify(`Challan item "${cItem.item_name}" included into invoice!`, 'success');
  };
  const includeAllChallanItems = challan => {
    if (!challan.items || challan.items.length === 0) return;
    challan.items.forEach(cItem => includeChallanItem(challan, cItem));
    notify(`All ${challan.items.length} items from Challan #${challan.challan_no} included into invoice!`, 'success');
  };

  // --- Supplier Ledger & Payments States ---
  const [ledgerSummary, setLedgerSummary] = useState([]);
  const [ledgerOverall, setLedgerOverall] = useState({
    total_purchases: 0,
    total_paid: 0,
    outstanding_balance: 0,
    total_pending_bills: 0,
    total_suppliers: 0
  });
  const [activeLedgerSupplierId, setActiveLedgerSupplierId] = useState('');
  const [activeLedgerDetail, setActiveLedgerDetail] = useState(null);
  const [isLoadingLedger, setIsLoadingLedger] = useState(false);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [isRecordingPayment, setIsRecordingPayment] = useState(false);
  const [paymentForm, setPaymentForm] = useState({
    supplier_id: '',
    purchase_id: '',
    amount: '',
    payment_mode: 'Bank',
    reference_no: '',
    bank_name: '',
    payment_date: new Date().toISOString().split('T')[0],
    remarks: ''
  });

  // --- Short Items / Deficiency Reorder States ---
  const [shortItems, setShortItems] = useState([]);
  const [selectedShortItemIds, setSelectedShortItemIds] = useState([]);
  const [shortFilter, setShortFilter] = useState('all'); // all, out_of_stock, low_stock
  const [shortSearch, setShortSearch] = useState('');
  const [isLoadingShortItems, setIsLoadingShortItems] = useState(false);
  const [showQuickReorderModal, setShowQuickReorderModal] = useState(false);
  const [quickReorderForm, setQuickReorderForm] = useState({
    item_id: '',
    item_name: '',
    min_reorder_level: 10
  });
  const [isSavingShortItem, setIsSavingShortItem] = useState(false);

  // --- Purchase Orders States ---
  const [purchaseOrders, setPurchaseOrders] = useState([]);
  const [poStatusFilter, setPoStatusFilter] = useState('all');
  const [isLoadingPOs, setIsLoadingPOs] = useState(false);
  const [showPoCreateModal, setShowPoCreateModal] = useState(false);
  const [poCreateForm, setPoCreateForm] = useState({
    supplier_id: '',
    expected_delivery_date: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    remarks: '',
    items: []
  });
  const [isSavingPO, setIsSavingPO] = useState(false);
  const [showPoViewModal, setShowPoViewModal] = useState(false);
  const [activeViewPo, setActiveViewPo] = useState(null);
  const [loadedPoId, setLoadedPoId] = useState(null);
  const [loadedPoNumber, setLoadedPoNumber] = useState('');

  // --- Purchase Invoice History & Edit States ---
  const [editingPurchaseId, setEditingPurchaseId] = useState(null);
  const [recentPurchases, setRecentPurchases] = useState([]);
  const [isLoadingPurchases, setIsLoadingPurchases] = useState(false);
  const [showPurchaseHistoryModal, setShowPurchaseHistoryModal] = useState(false);
  const [purchaseHistorySearch, setPurchaseHistorySearch] = useState('');

  // --- Data Fetching & Action Handlers ---
  const fetchLedgerSummary = async () => {
    setIsLoadingLedger(true);
    try {
      const res = await fetch(API_BASE + 'suppliers/ledger-summary');
      const data = await res.json();
      if (data.status) {
        setLedgerSummary(data.suppliers || []);
        if (data.summary) setLedgerOverall(data.summary);
      }
    } catch (e) {
      console.error('fetchLedgerSummary error:', e);
    } finally {
      setIsLoadingLedger(false);
    }
  };
  const fetchRecentPurchases = async () => {
    setIsLoadingPurchases(true);
    try {
      const res = await fetch(API_BASE + 'purchases?store_id=' + store.store_id);
      const data = await res.json();
      if (data.status && data.purchases) {
        setRecentPurchases(data.purchases);
      }
    } catch (e) {
      console.error('fetchRecentPurchases error:', e);
    } finally {
      setIsLoadingPurchases(false);
    }
  };
  const loadPurchaseForEdit = async purchaseId => {
    if (!purchaseId) return;
    try {
      const res = await fetch(API_BASE + 'purchase/' + purchaseId);
      const data = await res.json();
      if (data.status && data.purchase) {
        setEditingPurchaseId(Number(data.purchase.purchase_id));
        setSelectedSupplierId(String(data.purchase.supplier_id));
        setIsChallan(Number(data.purchase.is_challan || 0) === 1);
        setInvoiceNo(data.purchase.supplier_invoice_no || '');
        setInvoiceDate(data.purchase.invoice_date || new Date().toISOString().split('T')[0]);
        setLoadedPoId(null);
        setLoadedPoNumber('');
        if (data.items && data.items.length > 0) {
          setRows(data.items.map(it => {
            let expMonth = '12';
            let expYear = '28';
            if (it.expiry_date) {
              const parts = it.expiry_date.split('-');
              if (parts.length >= 2) {
                expYear = parts[0].slice(-2);
                expMonth = parts[1];
              }
            }
            const gst = Number(it.gst_rate) || 12;
            return recomputeItemForm({
              item_id: Number(it.item_id || 0),
              product_master_id: 0,
              item_name: it.item_name || '',
              generic_name: it.generic_name || '',
              category: it.category || 'Tablet',
              units_per_pack: Number(it.units_per_pack || 10),
              batch_no: it.batch_no || '',
              exp_month: expMonth,
              exp_year: expYear,
              expiry_date: it.expiry_date || '',
              mrp: Number(it.mrp || 0),
              qty_packs: Number(it.qty_packs || 1),
              free_qty_packs: Number(it.free_qty_packs || 0),
              ptr: Number(it.ptr || 0),
              discount_pct: Number(it.discount_pct || 0),
              sch_disc_amount: Number(it.sch_disc_amount || 0),
              sch_disc_pct: Number(it.sch_disc_pct || 0),
              cgst_pct: gst / 2,
              sgst_pct: gst / 2,
              gst_rate: gst,
              selling_price: Number(it.selling_price || it.mrp || 0),
              hsn_code: it.hsn_code || '3004',
              storage_type: it.storage_type || 'Normal',
              shelf_no: it.shelf_no || '',
              rack_no: it.rack_no || '',
              challan_item_ref_id: Number(it.challan_item_ref_id || 0)
            });
          }));
        }
        setSubTab('inward');
        setShowPurchaseHistoryModal(false);
        fetchSupplierChallans(data.purchase.supplier_id);
        notify('Purchase Invoice #' + data.purchase.supplier_invoice_no + ' loaded for editing.', 'info');
      } else {
        notify(data.message || 'Could not load purchase details.', 'error');
      }
    } catch (e) {
      notify('Error loading purchase invoice: ' + e.message, 'error');
    }
  };
  const cancelEditPurchase = () => {
    setEditingPurchaseId(null);
    setIsChallan(false);
    setInvoiceNo('');
    setLoadedPoId(null);
    setLoadedPoNumber('');
    setConvertedChallanId(null);
    setRows([]);
    resetItemForm();
    notify('Exited purchase edit mode. Ready for new inward entry.', 'info');
  };
  const fetchSupplierLedger = async supplierId => {
    if (!supplierId) {
      setActiveLedgerDetail(null);
      return;
    }
    setIsLoadingLedger(true);
    try {
      const res = await fetch(API_BASE + 'suppliers/ledger/' + supplierId);
      const data = await res.json();
      if (data.status) {
        setActiveLedgerDetail(data);
      } else {
        notify(data.message || 'Failed to fetch supplier ledger.', 'error');
      }
    } catch (e) {
      notify('Error fetching ledger: ' + e.message, 'error');
    } finally {
      setIsLoadingLedger(false);
    }
  };
  const openPaymentModal = (supplierId, purchaseId = 0, defaultAmount = 0) => {
    const sId = supplierId ? String(supplierId) : activeLedgerSupplierId || '';
    setPaymentForm({
      supplier_id: sId,
      purchase_id: purchaseId ? String(purchaseId) : '',
      amount: defaultAmount > 0 ? defaultAmount : '',
      payment_mode: 'Bank',
      reference_no: '',
      bank_name: '',
      payment_date: new Date().toISOString().split('T')[0],
      remarks: purchaseId ? 'Payment for invoice #' + purchaseId : 'On-account settlement'
    });
    setShowPaymentModal(true);
  };
  const handleRecordPayment = async e => {
    if (e) e.preventDefault();
    const supId = Number(paymentForm.supplier_id);
    const amt = parseFloat(paymentForm.amount);
    if (!supId || isNaN(amt) || amt <= 0) {
      notify('Please specify a supplier and valid payment amount.', 'error');
      return;
    }
    setIsRecordingPayment(true);
    try {
      const res = await fetch(API_BASE + 'suppliers/record-payment', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          supplier_id: supId,
          purchase_id: Number(paymentForm.purchase_id) || 0,
          amount: amt,
          payment_mode: paymentForm.payment_mode,
          reference_no: paymentForm.reference_no,
          bank_name: paymentForm.bank_name,
          payment_date: paymentForm.payment_date,
          remarks: paymentForm.remarks
        })
      });
      const data = await res.json();
      if (data.status) {
        notify('Payment recorded! Voucher #' + data.voucher_no, 'success');
        setShowPaymentModal(false);
        fetchLedgerSummary();
        if (activeLedgerSupplierId && String(activeLedgerSupplierId) === String(supId)) {
          fetchSupplierLedger(supId);
        }
      } else {
        notify(data.message || 'Failed to record payment.', 'error');
      }
    } catch (err) {
      notify('Payment error: ' + err.message, 'error');
    } finally {
      setIsRecordingPayment(false);
    }
  };
  const fetchShortItems = async () => {
    setIsLoadingShortItems(true);
    try {
      const res = await fetch(API_BASE + 'stock/short-items?store_id=' + store.store_id);
      const data = await res.json();
      if (data.status) {
        setShortItems(data.short_items || []);
      }
    } catch (e) {
      console.error('fetchShortItems error:', e);
    } finally {
      setIsLoadingShortItems(false);
    }
  };
  const handleSaveShortItemFlag = async e => {
    if (e) e.preventDefault();
    if (!quickReorderForm.item_id) {
      notify('Please select an item to track in Short Book.', 'error');
      return;
    }
    setIsSavingShortItem(true);
    try {
      const res = await fetch(API_BASE + 'stock/flag-short-item', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          item_id: Number(quickReorderForm.item_id),
          min_reorder_level: Number(quickReorderForm.min_reorder_level) || 10
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(data.message || 'Item updated in Short Book!', 'success');
        setShowQuickReorderModal(false);
        fetchShortItems();
      } else {
        notify(data.message || 'Failed to update reorder level.', 'error');
      }
    } catch (err) {
      notify('Error: ' + err.message, 'error');
    } finally {
      setIsSavingShortItem(false);
    }
  };
  const fetchPurchaseOrders = async () => {
    setIsLoadingPOs(true);
    try {
      const res = await fetch(API_BASE + 'purchase-orders?store_id=' + store.store_id + '&status=' + poStatusFilter);
      const data = await res.json();
      if (data.status) {
        setPurchaseOrders(data.purchase_orders || []);
      }
    } catch (e) {
      console.error('fetchPurchaseOrders error:', e);
    } finally {
      setIsLoadingPOs(false);
    }
  };
  const openCreatePoFromShortItems = () => {
    const selected = shortItems.filter(it => selectedShortItemIds.includes(it.item_id));
    if (selected.length === 0) {
      notify('Please select at least one item from the short list.', 'warning');
      return;
    }
    const defaultSupplier = selected.find(s => s.last_supplier_id > 0)?.last_supplier_id || suppliers[0]?.supplier_id || '';
    setPoCreateForm({
      supplier_id: defaultSupplier ? String(defaultSupplier) : '',
      expected_delivery_date: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      remarks: 'Replenishment purchase order from hospital deficiency book',
      items: selected.map(it => ({
        item_id: it.item_id,
        item_name: it.item_name,
        generic_name: it.generic_name || '',
        category: it.category || 'Tablet',
        order_packs: it.suggested_packs || 1,
        units_per_pack: it.units_per_pack || 10,
        estimated_ptr: it.last_ptr || 0,
        gst_rate: it.gst_rate || 12
      }))
    });
    setShowPoCreateModal(true);
  };
  const handleSavePurchaseOrder = async e => {
    if (e) e.preventDefault();
    const supId = Number(poCreateForm.supplier_id);
    if (!supId || poCreateForm.items.length === 0) {
      notify('Supplier and at least one line item are required for PO.', 'error');
      return;
    }
    setIsSavingPO(true);
    try {
      const res = await fetch(API_BASE + 'purchase-orders/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          supplier_id: supId,
          expected_delivery_date: poCreateForm.expected_delivery_date,
          remarks: poCreateForm.remarks,
          items: poCreateForm.items
        })
      });
      const data = await res.json();
      if (data.status) {
        notify('Purchase Order ' + data.po_number + ' created successfully!', 'success');
        setShowPoCreateModal(false);
        setSelectedShortItemIds([]);
        fetchPurchaseOrders();
        setSubTab('purchase_orders');
      } else {
        notify(data.message || 'Failed to create PO.', 'error');
      }
    } catch (err) {
      notify('PO error: ' + err.message, 'error');
    } finally {
      setIsSavingPO(false);
    }
  };
  const handleViewPo = async poId => {
    try {
      const res = await fetch(API_BASE + 'purchase-orders/' + poId);
      const data = await res.json();
      if (data.status) {
        setActiveViewPo(data);
        setShowPoViewModal(true);
      } else {
        notify(data.message || 'PO not found', 'error');
      }
    } catch (err) {
      notify('Error loading PO: ' + err.message, 'error');
    }
  };
  const handleCancelPo = async poId => {
    if (!confirm('Are you sure you want to cancel this Purchase Order?')) return;
    try {
      const res = await fetch(API_BASE + 'purchase-orders/cancel', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          po_id: poId
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(data.message || 'PO cancelled', 'success');
        fetchPurchaseOrders();
      } else {
        notify(data.message || 'Failed to cancel PO', 'error');
      }
    } catch (err) {
      notify('Error: ' + err.message, 'error');
    }
  };
  const loadPoIntoInwardBill = async poId => {
    try {
      const res = await fetch(API_BASE + 'purchase-orders/' + poId);
      const data = await res.json();
      if (data.status && data.purchase_order) {
        const po = data.purchase_order;
        const poItems = data.items || [];
        setSelectedSupplierId(String(po.supplier_id));
        setLoadedPoId(po.po_id);
        setLoadedPoNumber(po.po_number);
        setInvoiceNo('INV-' + po.po_number);
        if (poItems.length > 0) {
          setRows(poItems.map(it => recomputeItemForm({
            ...initialItemForm,
            item_id: it.item_id || 0,
            item_name: it.item_name,
            generic_name: it.generic_name || '',
            category: it.category || 'Tablet',
            batch_no: '',
            expiry_date: '',
            qty_packs: it.order_packs || 1,
            free_qty_packs: 0,
            units_per_pack: it.units_per_pack || 10,
            mrp: it.estimated_ptr ? Math.round(Number(it.estimated_ptr) * 1.35) : 0,
            ptr: Number(it.estimated_ptr) || 0,
            discount_pct: 0,
            gst_rate: Number(it.gst_rate) || 12,
            cgst_pct: (Number(it.gst_rate) || 12) / 2,
            sgst_pct: (Number(it.gst_rate) || 12) / 2,
            hsn_code: '3004'
          })));
        }
        setSubTab('inward');
        setShowPoViewModal(false);
        notify('Loaded ' + poItems.length + ' items from ' + po.po_number + '. Verify batch & expiry to record inward bill.', 'info');
      } else {
        notify('Could not load PO details.', 'error');
      }
    } catch (err) {
      notify('Error loading PO: ' + err.message, 'error');
    }
  };
  useEffect(() => {
    fetchSuppliers();
    fetchMasterSupportData();
    fetchRecentPurchases();
  }, []);

  // Auto-scroll active suggestion into view when navigating with Arrow keys
  useEffect(() => {
    if (activeSuggestionRef.current) {
      activeSuggestionRef.current.scrollIntoView({
        block: 'nearest'
      });
    }
  }, [selectedSuggestionIndex]);

  // Close suggestions dropdown when clicking outside
  useEffect(() => {
    const handleOutsideClick = e => {
      const searchInput = document.getElementById('pharma_product_search_input');
      if (searchInput && searchInput.contains(e.target)) return;
      if (suggestionsDropdownRef.current && suggestionsDropdownRef.current.contains(e.target)) return;
      setActiveSearchRow(false);
      setSelectedSuggestionIndex(-1);
    };
    document.addEventListener('mousedown', handleOutsideClick);
    return () => document.removeEventListener('mousedown', handleOutsideClick);
  }, []);

  // Global F2 hotkey to jump to Product Search anytime
  useEffect(() => {
    const handleGlobalKey = e => {
      if (e.key === 'F2') {
        e.preventDefault();
        const searchInput = document.getElementById('pharma_product_search_input');
        if (searchInput) {
          searchInput.focus();
          searchInput.select();
        }
      }
    };
    window.addEventListener('keydown', handleGlobalKey);
    return () => window.removeEventListener('keydown', handleGlobalKey);
  }, []);
  const fetchMasterSupportData = async () => {
    try {
      const res = await fetch(API_BASE + 'items/master-support-data');
      const data = await res.json();
      if (data.status) {
        setSupportData(data);
      }
    } catch (e) {
      console.error('Error fetching master support data:', e);
    }
  };
  const fetchSuppliers = async () => {
    try {
      const res = await fetch(API_BASE + 'suppliers');
      const data = await res.json();
      if (data.status) setSuppliers(data.suppliers || []);
    } catch (e) {
      console.error(e);
    }
  };
  const openAddSupplier = () => {
    setSupplierForm({
      supplier_id: 0,
      supplier_name: '',
      contact_person: '',
      phone: '',
      email: '',
      gstin: '',
      pan_no: '',
      dl_no_20b: '',
      dl_no_21b: '',
      state_code: '07',
      credit_days: 30,
      address: '',
      bank_details: ''
    });
    setShowSupplierModal(true);
  };
  const openEditSupplier = s => {
    setSupplierForm({
      supplier_id: s.supplier_id,
      supplier_name: s.supplier_name || '',
      contact_person: s.contact_person || '',
      phone: s.phone || '',
      email: s.email || '',
      gstin: s.gstin || '',
      pan_no: s.pan_no || '',
      dl_no_20b: s.dl_no_20b || '',
      dl_no_21b: s.dl_no_21b || '',
      state_code: s.state_code || '07',
      credit_days: s.credit_days || 30,
      address: s.address || '',
      bank_details: s.bank_details || ''
    });
    setShowSupplierModal(true);
  };
  const handleSaveSupplier = async e => {
    if (e) e.preventDefault();
    if (!supplierForm.supplier_name.trim()) {
      notify('Supplier / Distributor name is required.', 'error');
      return;
    }
    setIsSavingSupplier(true);
    try {
      const res = await fetch(API_BASE + 'suppliers/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(supplierForm)
      });
      const data = await res.json();
      if (data.status) {
        notify(supplierForm.supplier_id ? 'Supplier updated successfully!' : 'New supplier added successfully!', 'success');
        setShowSupplierModal(false);
        await fetchSuppliers();
        if (data.supplier_id) {
          setSelectedSupplierId(String(data.supplier_id));
        }
      } else {
        notify(data.message || 'Failed to save supplier.', 'error');
      }
    } catch (err) {
      notify('Network error saving supplier: ' + err.message, 'error');
    } finally {
      setIsSavingSupplier(false);
    }
  };
  const handleDeleteSupplier = async (supplierId, supplierName) => {
    if (!confirm(`Are you sure you want to remove / deactivate supplier "${supplierName}"?`)) return;
    try {
      const res = await fetch(API_BASE + 'suppliers/delete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          supplier_id: supplierId
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(data.message || 'Supplier removed successfully.', 'success');
        await fetchSuppliers();
      } else {
        notify(data.message || 'Failed to remove supplier.', 'error');
      }
    } catch (err) {
      notify('Error: ' + err.message, 'error');
    }
  };
  const filteredSuppliers = suppliers.filter(s => {
    if (!supplierSearch.trim()) return true;
    const q = supplierSearch.toLowerCase();
    return s.supplier_name && s.supplier_name.toLowerCase().includes(q) || s.gstin && s.gstin.toLowerCase().includes(q) || s.dl_no_20b && s.dl_no_20b.toLowerCase().includes(q) || s.dl_no_21b && s.dl_no_21b.toLowerCase().includes(q) || s.phone && s.phone.toLowerCase().includes(q) || s.contact_person && s.contact_person.toLowerCase().includes(q);
  });
  const addRow = () => {
    setRows([...rows, {
      item_id: 0,
      item_name: '',
      generic_name: '',
      category: 'Tablet',
      batch_no: '',
      expiry_date: '',
      qty_packs: 1,
      free_qty_packs: 0,
      units_per_pack: 10,
      mrp: 0,
      ptr: 0,
      discount_pct: 0,
      gst_rate: 12,
      hsn_code: '3004'
    }]);
  };
  const updateRow = (idx, field, val) => {
    const updated = [...rows];
    updated[idx][field] = val;
    setRows(updated);
  };
  const removeRow = idx => {
    setRows(prev => prev.filter((_, i) => i !== idx));
    if (editingItemIndex === idx) {
      resetItemForm();
    }
  };

  // Autocomplete searching for Medicine Name in Product Search
  const handleProductSearchChange = value => {
    setItemForm(prev => ({
      ...prev,
      item_name: value,
      item_id: 0,
      product_master_id: 0
    }));
    setActiveSearchRow(true);
    if (searchTimerRef.current) clearTimeout(searchTimerRef.current);
    if (!value.trim() || value.trim().length < 1) {
      setSuggestions([]);
      setSelectedSuggestionIndex(-1);
      return;
    }
    searchTimerRef.current = setTimeout(async () => {
      setIsSearchingMeds(true);
      try {
        const res = await fetch(API_BASE + 'items/search-master?q=' + encodeURIComponent(value.trim()));
        const data = await res.json();
        if (data.status) {
          const list = data.items || [];
          setSuggestions(list);
          setSelectedSuggestionIndex(list.length > 0 ? 0 : -1);
        }
      } catch (err) {
        console.error('Master search error:', err);
      } finally {
        setIsSearchingMeds(false);
      }
    }, 200);
  };
  const handleProductSearchKeyDown = e => {
    if (!activeSearchRow || suggestions.length === 0) {
      if (e.key === 'ArrowDown' && itemForm.item_name.trim().length >= 1) {
        e.preventDefault();
        handleProductSearchChange(itemForm.item_name);
      }
      return;
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      setSelectedSuggestionIndex(prev => {
        if (suggestions.length === 0) return -1;
        return (prev + 1) % suggestions.length;
      });
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      setSelectedSuggestionIndex(prev => {
        if (suggestions.length === 0) return -1;
        return (prev - 1 + suggestions.length) % suggestions.length;
      });
      return;
    }
    if (e.key === 'Enter') {
      e.preventDefault();
      const targetIdx = selectedSuggestionIndex >= 0 && selectedSuggestionIndex < suggestions.length ? selectedSuggestionIndex : 0;
      const chosen = suggestions[targetIdx];
      if (chosen) {
        handleSelectMedicine(chosen);
      }
      return;
    }
    if (e.key === 'Escape') {
      e.preventDefault();
      setActiveSearchRow(false);
      setSelectedSuggestionIndex(-1);
      return;
    }
  };
  const handleSelectMedicine = item => {
    const gstRate = Number(item.gst_rate) || 12;
    const uPack = Number(item.units_per_pack) || 10;
    const cgst = Number((gstRate / 2).toFixed(2));
    const sgst = Number((gstRate / 2).toFixed(2));
    setItemForm(prev => recomputeItemForm({
      ...prev,
      item_id: item.item_id || 0,
      product_master_id: item.product_master_id || item.item_id || 0,
      item_name: item.item_name,
      generic_name: item.generic_name || '',
      category: item.category || 'Tablet',
      units_per_pack: uPack,
      hsn_code: item.hsn_code || '3004',
      cgst_pct: cgst,
      sgst_pct: sgst,
      gst_rate: gstRate,
      mrp: item.mrp || prev.mrp,
      ptr: item.ptr || prev.ptr
    }));
    setActiveSearchRow(false);
    setSuggestions([]);
    setSelectedSuggestionIndex(-1);
    if (item.item_id) {
      fetchProductPurchaseHistory(item.item_id, item.item_name);
    }
    setTimeout(() => {
      const batchInput = document.getElementById('pharma_batch_no_input');
      if (batchInput) {
        batchInput.focus();
        batchInput.select();
      }
    }, 120);
  };
  const openAddMasterModal = (initialName = '') => {
    const prefillName = initialName || itemForm.item_name || '';
    setMasterForm({
      item_name: prefillName,
      generic_name: '',
      formulation: 'Tablet',
      units_per_pack: 10,
      packing: '10',
      hsn_code: '3004',
      gst_rate: 12,
      drug_schedule: 'Schedule H',
      company_name: '',
      min_reorder_level: 10
    });
    setActiveSearchRow(false);
    setSuggestions([]);
    setShowMasterModal(true);
  };
  const handleSaveMasterMedicine = async e => {
    if (e) e.preventDefault();
    if (!masterForm.item_name.trim()) {
      notify('Medicine name is required.', 'error');
      return;
    }
    setIsSavingMaster(true);
    try {
      const res = await fetch(API_BASE + 'items/save-master', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(masterForm)
      });
      const data = await res.json();
      if (data.status && data.item) {
        notify(`"${data.item.item_name}" saved to Pharmacy Master & selected!`, 'success');
        setShowMasterModal(false);
        handleSelectMedicine(data.item);
      } else {
        notify(data.message || 'Failed to save medicine to master.', 'error');
      }
    } catch (err) {
      notify('Error saving to master: ' + err.message, 'error');
    } finally {
      setIsSavingMaster(false);
    }
  };
  const savePurchaseBill = async () => {
    if (!selectedSupplierId || !invoiceNo.trim()) {
      notify('Please select supplier and enter invoice / challan number.', 'error');
      return;
    }
    if (rows.length === 0) {
      notify('Please add at least one medicine item to this inward bill using the form.', 'error');
      return;
    }
    try {
      const itemsPayload = rows.map(r => ({
        item_id: Number(r.item_id || 0),
        item_name: r.item_name,
        generic_name: r.generic_name || '',
        category: r.category || 'Tablet',
        batch_no: r.batch_no,
        expiry_date: r.expiry_date,
        qty_packs: Number(r.qty_packs),
        free_qty_packs: Number(r.free_qty_packs || 0),
        units_per_pack: Number(r.units_per_pack || 10),
        mrp: Number(r.mrp),
        ptr: Number(r.ptr),
        discount_pct: Number(r.discount_pct || 0),
        sch_disc_amount: Number(r.sch_disc_amount || 0),
        sch_disc_pct: Number(r.sch_disc_pct || 0),
        gst_rate: Number(r.gst_rate || 12),
        hsn_code: r.hsn_code || '3004',
        selling_price: Number(r.selling_price || r.mrp || 0),
        storage_type: r.storage_type || 'Normal',
        shelf_no: r.shelf_no || '',
        rack_no: r.rack_no || '',
        challan_item_ref_id: Number(r.challan_item_ref_id || 0)
      }));
      const endpoint = editingPurchaseId ? API_BASE + 'purchase/update' : API_BASE + 'purchase/save';
      const payload = {
        store_id: store.store_id,
        supplier_id: Number(selectedSupplierId),
        supplier_invoice_no: invoiceNo.trim(),
        is_challan: isChallan ? 1 : 0,
        challan_no: isChallan ? invoiceNo.trim() : null,
        received_by_name: activeOperator?.full_name || 'Store Staff',
        operator_name: activeOperator?.full_name || 'Store Staff',
        user_id: activeOperator?.user_id || 1,
        converted_from_challan_id: convertedChallanId,
        invoice_date: invoiceDate,
        items: itemsPayload
      };
      if (editingPurchaseId) {
        payload.purchase_id = editingPurchaseId;
      } else if (loadedPoId) {
        payload.po_id = loadedPoId;
      }
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status) {
        if (editingPurchaseId) {
          notify('Purchase invoice #' + invoiceNo + ' updated successfully! Stock & supplier ledger adjusted.', 'success');
        } else if (isChallan) {
          notify('Delivery Challan #' + invoiceNo + ' recorded! Stock replenished.', 'success');
        } else {
          notify('Purchase inward recorded! Stock & Supplier Ledger updated.' + (loadedPoNumber ? ' (PO ' + loadedPoNumber + ' marked as received)' : ''), 'success');
        }
        setEditingPurchaseId(null);
        setIsChallan(false);
        setInvoiceNo('');
        setLoadedPoId(null);
        setLoadedPoNumber('');
        setConvertedChallanId(null);
        setRows([]);
        resetItemForm();
        fetchRecentPurchases();
        if (selectedSupplierId) {
          fetchSupplierChallans(selectedSupplierId);
        }
        if (activeLedgerSupplierId) fetchSupplierLedger(activeLedgerSupplierId);
        fetchLedgerSummary();
      } else {
        notify(data.message || 'Failed to save purchase invoice.', 'error');
      }
    } catch (e) {
      notify('Purchase invoice error: ' + e.message, 'error');
    }
  };
  return /*#__PURE__*/_jsxs("div", {
    children: [/*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-wrap gap-2 mb-3 align-items-center",
      children: [/*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${subTab === 'inward' ? 'btn-primary shadow-sm' : 'btn-outline-secondary'}`,
        onClick: () => setSubTab('inward'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-box-arrow-in-down me-1"
        }), " Inward Purchase Bill"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${subTab === 'ledger' ? 'btn-primary shadow-sm' : 'btn-outline-secondary'}`,
        onClick: () => {
          setSubTab('ledger');
          fetchLedgerSummary();
        },
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-journal-bookmark me-1"
        }), " Supplier Ledger & Payments"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${subTab === 'short_items' ? 'btn-warning text-dark fw-bold shadow-sm' : 'btn-outline-secondary'}`,
        onClick: () => {
          setSubTab('short_items');
          fetchShortItems();
        },
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-exclamation-triangle me-1"
        }), " Short Items & PO Generator", shortItems.length > 0 && /*#__PURE__*/_jsx("span", {
          className: "badge bg-danger ms-1",
          children: shortItems.length
        })]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${subTab === 'purchase_orders' ? 'btn-primary shadow-sm' : 'btn-outline-secondary'}`,
        onClick: () => {
          setSubTab('purchase_orders');
          fetchPurchaseOrders();
        },
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-file-earmark-text me-1"
        }), " Purchase Orders"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${subTab === 'suppliers' ? 'btn-primary shadow-sm' : 'btn-outline-secondary'}`,
        onClick: () => setSubTab('suppliers'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-truck me-1"
        }), " Supplier Directory (", suppliers.length, ")"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${subTab === 'excel' ? 'btn-success shadow-sm' : 'btn-outline-secondary'}`,
        onClick: () => setSubTab('excel'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-file-earmark-spreadsheet me-1"
        }), " Excel / CSV Import"]
      })]
    }), subTab === 'inward' && /*#__PURE__*/_jsxs("div", {
      className: "counter-card",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2",
        children: [/*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("h5", {
            className: "fw-bold mb-0",
            children: [/*#__PURE__*/_jsx("i", {
              className: `bi ${isChallan ? 'bi-truck text-warning' : 'bi-receipt text-primary'} me-1.5`
            }), editingPurchaseId ? `Edit Purchase Invoice #${invoiceNo}` : isChallan ? 'Distributor Delivery Challan (Stock Entry)' : 'Distributor Purchase Inward (Tax Invoice)']
          }), /*#__PURE__*/_jsx("small", {
            className: "text-muted",
            children: editingPurchaseId ? `Modifying existing Bill #${invoiceNo} (Ref #${editingPurchaseId}) - adjustments reflect in stock & ledger` : isChallan ? 'Record inward stock under vendor delivery challan (can be converted to tax invoice later)' : 'Enter vendor tax invoice to replenish store inventory and post supplier ledger payable'
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center gap-2",
          children: [/*#__PURE__*/_jsxs("button", {
            type: "button",
            className: "btn btn-sm btn-outline-secondary",
            onClick: () => {
              fetchRecentPurchases();
              setShowPurchaseHistoryModal(true);
            },
            title: "View recent inward bills and select one to edit",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-clock-history me-1"
            }), " Inward Bills History / Edit (", recentPurchases.length, ")"]
          }), loadedPoNumber ? /*#__PURE__*/_jsxs("div", {
            className: "badge bg-info-subtle text-info border border-info px-2 py-1.5 d-flex align-items-center gap-1.5",
            style: {
              fontSize: '12px'
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-link-45deg fs-6"
            }), /*#__PURE__*/_jsxs("span", {
              children: ["Linked PO: ", /*#__PURE__*/_jsx("strong", {
                children: loadedPoNumber
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-dark ms-1",
              style: {
                fontSize: '9px'
              },
              onClick: () => {
                setLoadedPoId(null);
                setLoadedPoNumber('');
              },
              title: "Unlink PO"
            })]
          }) : /*#__PURE__*/_jsxs("button", {
            type: "button",
            className: "btn btn-sm btn-outline-primary",
            onClick: () => {
              fetchPurchaseOrders();
              setSubTab('purchase_orders');
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-box-arrow-in-down-right me-1"
            }), " Load from Purchase Order"]
          })]
        })]
      }), editingPurchaseId && /*#__PURE__*/_jsxs("div", {
        className: "alert alert-warning border border-warning-subtle shadow-sm py-2 px-3 mb-3 d-flex justify-content-between align-items-center",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center gap-2",
          children: [/*#__PURE__*/_jsxs("span", {
            className: "badge bg-warning text-dark fs-6 px-2.5 py-1.5",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-pencil-square me-1"
            }), "EDIT MODE"]
          }), /*#__PURE__*/_jsxs("div", {
            children: [/*#__PURE__*/_jsxs("strong", {
              className: "text-dark d-block",
              children: ["Editing Purchase Invoice #", invoiceNo, " (Purchase ID: #", editingPurchaseId, ")"]
            }), /*#__PURE__*/_jsx("small", {
              className: "text-muted",
              children: "Updating this invoice will recalculate batch stock in this store and adjust supplier ledger balances."
            })]
          })]
        }), /*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-outline-dark btn-sm fw-semibold",
          onClick: cancelEditPurchase,
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-x-circle me-1"
          }), " Cancel Edit & New Inward"]
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "card border-0 shadow-sm p-3 mb-3",
        style: {
          background: '#f8fafc',
          borderRadius: '10px'
        },
        children: /*#__PURE__*/_jsxs("div", {
          className: "row g-2 align-items-center",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "col-md-4",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center mb-1",
              children: [/*#__PURE__*/_jsxs("label", {
                className: "form-label small fw-bold mb-0",
                children: ["Supplier / Distributor ", /*#__PURE__*/_jsx("span", {
                  className: "text-danger",
                  children: "*"
                })]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-link btn-sm p-0 text-decoration-none fw-semibold",
                style: {
                  fontSize: '11px'
                },
                onClick: openAddSupplier,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-plus-circle me-1"
                }), "+ Add New"]
              })]
            }), /*#__PURE__*/_jsxs("select", {
              className: "form-select form-select-sm",
              value: selectedSupplierId,
              onChange: e => {
                const sId = e.target.value;
                setSelectedSupplierId(sId);
                fetchSupplierChallans(sId);
              },
              children: [/*#__PURE__*/_jsx("option", {
                value: "",
                children: "-- Select Supplier --"
              }), suppliers.map(s => /*#__PURE__*/_jsxs("option", {
                value: s.supplier_id,
                children: [s.supplier_name, " (GST: ", s.gstin || 'N/A', ")"]
              }, s.supplier_id))]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-md-3",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label small fw-bold mb-1",
              children: "Entry Type"
            }), /*#__PURE__*/_jsxs("div", {
              className: "btn-group btn-group-sm w-100",
              role: "group",
              children: [/*#__PURE__*/_jsxs("button", {
                type: "button",
                className: `btn ${!isChallan ? 'btn-primary fw-bold' : 'btn-outline-secondary'}`,
                onClick: () => setIsChallan(false),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-receipt me-1"
                }), " Tax Invoice"]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: `btn ${isChallan ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'}`,
                onClick: () => setIsChallan(true),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-truck me-1"
                }), " Challan"]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-md-3",
            children: [/*#__PURE__*/_jsxs("label", {
              className: "form-label small fw-bold mb-1",
              children: [isChallan ? 'Challan Number' : 'Bill / Invoice No.', " ", /*#__PURE__*/_jsx("span", {
                className: "text-danger",
                children: "*"
              })]
            }), /*#__PURE__*/_jsx("input", {
              type: "text",
              className: "form-control form-control-sm",
              placeholder: isChallan ? "e.g. CHL-2026-001" : "e.g. APX-9941",
              value: invoiceNo,
              onChange: e => setInvoiceNo(e.target.value)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-md-2",
            children: [/*#__PURE__*/_jsx("label", {
              className: "form-label small fw-bold mb-1",
              children: isChallan ? 'Challan Date' : 'Invoice Date'
            }), /*#__PURE__*/_jsx("input", {
              type: "date",
              className: "form-control form-control-sm",
              value: invoiceDate,
              onChange: e => setInvoiceDate(e.target.value)
            })]
          }), isChallan && /*#__PURE__*/_jsx("div", {
            className: "col-12 mt-1",
            children: /*#__PURE__*/_jsxs("div", {
              className: "badge bg-warning-subtle text-dark border border-warning px-2.5 py-1 d-inline-flex align-items-center gap-1",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-info-circle-fill text-warning"
              }), /*#__PURE__*/_jsxs("span", {
                children: [/*#__PURE__*/_jsx("strong", {
                  children: "Challan Inward:"
                }), " Adds physical stock immediately without ledger debt. This challan can later be imported directly into a final Tax Invoice."]
              })]
            })
          })]
        })
      }), (!selectedSupplierId || !invoiceNo.trim()) && !editingPurchaseId ? /*#__PURE__*/_jsxs("div", {
        className: "card border border-2 border-dashed bg-light text-center p-4 my-4",
        style: {
          borderRadius: '12px'
        },
        children: [/*#__PURE__*/_jsx("div", {
          className: "mx-auto mb-2 text-primary",
          style: {
            width: '48px',
            height: '48px',
            borderRadius: '50%',
            background: '#e0f2fe',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center'
          },
          children: /*#__PURE__*/_jsx("i", {
            className: "bi bi-arrow-up-circle fs-3"
          })
        }), /*#__PURE__*/_jsxs("h6", {
          className: "fw-bold text-dark mb-1",
          children: ["Step 1: Select Supplier & Enter ", isChallan ? 'Challan' : 'Invoice', " Details Above"]
        }), /*#__PURE__*/_jsxs("p", {
          className: "text-muted small mb-0",
          style: {
            maxWidth: '560px',
            margin: '0 auto'
          },
          children: ["Please choose the ", /*#__PURE__*/_jsx("strong", {
            children: "Supplier / Distributor"
          }), " and provide the ", /*#__PURE__*/_jsx("strong", {
            children: isChallan ? 'Delivery Challan No.' : 'Supplier Bill / Invoice No.'
          }), " above to unlock the medicine entry form, historical rate lookup, and pending challan imports."]
        })]
      }) :
      /*#__PURE__*/
      /* Step 2: Main Pharma Entry Workstation (2-Column Grid) */
      _jsxs("div", {
        className: "row g-3",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "col-xl-8 col-lg-7",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "card border shadow-sm mb-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "fw-bold text-dark small",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-list-check me-1 text-primary"
                }), "Invoice Line Items (", rows.length, ")"]
              }), rows.length > 0 && /*#__PURE__*/_jsxs("span", {
                className: "badge bg-primary-subtle text-primary border",
                children: ["Total Items: ", rows.length]
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "table-responsive",
              style: {
                maxHeight: '280px',
                overflowY: 'auto'
              },
              children: /*#__PURE__*/_jsxs("table", {
                className: "table table-sm table-hover table-bordered mb-0 align-middle",
                style: {
                  fontSize: '11.5px'
                },
                children: [/*#__PURE__*/_jsx("thead", {
                  className: "table-light sticky-top",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("th", {
                      style: {
                        width: '30px'
                      },
                      className: "text-center",
                      children: "#"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '50px'
                      },
                      className: "text-center",
                      children: "RecNo"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        minWidth: '130px',
                        maxWidth: '160px'
                      },
                      children: "Item Name"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '80px'
                      },
                      children: "Batch No"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '65px'
                      },
                      className: "text-center",
                      children: "Exp."
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '65px'
                      },
                      className: "text-end",
                      children: "MRP"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '55px'
                      },
                      className: "text-center",
                      children: "Qty."
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '65px'
                      },
                      className: "text-end",
                      children: "Rate"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '75px'
                      },
                      className: "text-end",
                      children: "Amount"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '50px'
                      },
                      className: "text-end",
                      children: "Disc."
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '70px'
                      },
                      className: "text-end",
                      children: "Tax Amount"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '60px'
                      },
                      className: "text-end",
                      children: "CGST"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '60px'
                      },
                      className: "text-end",
                      children: "SGST"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '80px'
                      },
                      className: "text-end",
                      children: "Net Amount"
                    }), /*#__PURE__*/_jsx("th", {
                      style: {
                        width: '60px'
                      },
                      className: "text-center",
                      children: "Action"
                    })]
                  })
                }), /*#__PURE__*/_jsx("tbody", {
                  children: rows.length === 0 ? /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsxs("td", {
                      colSpan: "15",
                      className: "text-center text-muted py-4",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-inbox fs-4 d-block mb-1 text-secondary"
                      }), /*#__PURE__*/_jsx("span", {
                        children: "No medicines added yet. Use the form below or include pending challans from the right."
                      })]
                    })
                  }) : rows.map((r, i) => {
                    const expDisplay = r.exp_month && r.exp_year ? `${r.exp_month}/${r.exp_year}` : r.expiry_date ? r.expiry_date.slice(2, 7) : '-';
                    const taxAmt = ((Number(r.cgst_amount) || 0) + (Number(r.sgst_amount) || 0)).toFixed(2);
                    const isReturn = Number(r.qty_packs) < 0 || Number(r.net_amount) < 0;
                    return /*#__PURE__*/_jsxs("tr", {
                      className: editingItemIndex === i ? 'table-warning' : isReturn ? 'table-danger bg-danger-subtle' : '',
                      children: [/*#__PURE__*/_jsx("td", {
                        className: "text-center text-secondary",
                        children: i + 1
                      }), /*#__PURE__*/_jsx("td", {
                        className: "text-center text-secondary",
                        children: r.item_id || r.product_master_id || i + 1
                      }), /*#__PURE__*/_jsxs("td", {
                        style: {
                          maxWidth: '160px'
                        },
                        children: [/*#__PURE__*/_jsxs("div", {
                          className: "d-flex align-items-center gap-1",
                          children: [/*#__PURE__*/_jsx("span", {
                            className: "fw-bold text-truncate text-dark",
                            title: r.item_name,
                            children: r.item_name
                          }), isReturn && /*#__PURE__*/_jsx("span", {
                            className: "badge bg-danger text-white py-0 px-1",
                            style: {
                              fontSize: '9px'
                            },
                            children: "RETURN"
                          })]
                        }), r.generic_name && /*#__PURE__*/_jsx("div", {
                          className: "text-muted text-truncate",
                          style: {
                            fontSize: '10px'
                          },
                          title: r.generic_name,
                          children: r.generic_name
                        })]
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("span", {
                          className: "badge bg-light text-dark border font-monospace",
                          children: r.batch_no || '-'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        className: "text-center",
                        children: expDisplay
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "text-end",
                        children: ["₹", Number(r.mrp || 0).toFixed(2)]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "text-center",
                        children: [/*#__PURE__*/_jsx("span", {
                          className: `fw-bold ${isReturn ? 'text-danger' : ''}`,
                          children: r.qty_packs
                        }), Number(r.free_qty_packs) > 0 && /*#__PURE__*/_jsxs("span", {
                          className: "text-success",
                          style: {
                            fontSize: '10px'
                          },
                          children: ["+", r.free_qty_packs, "F"]
                        })]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "text-end",
                        children: ["₹", Number(r.ptr || 0).toFixed(2)]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: `text-end ${isReturn ? 'text-danger fw-semibold' : ''}`,
                        children: ["₹", Number(r.amount || 0).toFixed(2)]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "text-end",
                        children: [Number(r.discount_pct || 0), "%"]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: `text-end ${isReturn ? 'text-danger' : ''}`,
                        children: ["₹", taxAmt]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: `text-end ${isReturn ? 'text-danger' : ''}`,
                        children: ["₹", Number(r.cgst_amount || Number(r.taxable_amount || 0) * (Number(r.cgst_pct || 6) / 100)).toFixed(2)]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: `text-end ${isReturn ? 'text-danger' : ''}`,
                        children: ["₹", Number(r.sgst_amount || Number(r.taxable_amount || 0) * (Number(r.sgst_pct || 6) / 100)).toFixed(2)]
                      }), /*#__PURE__*/_jsxs("td", {
                        className: `text-end fw-bold ${isReturn ? 'text-danger' : 'text-dark'}`,
                        children: ["₹", Number(r.net_amount || 0).toFixed(2)]
                      }), /*#__PURE__*/_jsx("td", {
                        className: "text-center",
                        children: /*#__PURE__*/_jsxs("div", {
                          className: "btn-group btn-group-sm",
                          children: [/*#__PURE__*/_jsx("button", {
                            type: "button",
                            className: "btn btn-outline-primary btn-sm py-0 px-1",
                            onClick: () => handleEditRow(i),
                            title: "Edit this row in form below",
                            children: /*#__PURE__*/_jsx("i", {
                              className: "bi bi-pencil"
                            })
                          }), /*#__PURE__*/_jsx("button", {
                            type: "button",
                            className: "btn btn-outline-danger btn-sm py-0 px-1",
                            onClick: () => removeRow(i),
                            title: "Delete this row",
                            children: /*#__PURE__*/_jsx("i", {
                              className: "bi bi-trash"
                            })
                          })]
                        })
                      })]
                    }, i);
                  })
                })]
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "card-footer bg-light py-2 px-3 d-flex flex-wrap justify-content-end align-items-center gap-4 text-secondary small fw-semibold",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-muted fw-normal",
                  children: "Total Taxable: "
                }), /*#__PURE__*/_jsxs("strong", {
                  className: "text-dark fs-6",
                  children: ["₹", billTotals.taxable]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-muted fw-normal",
                  children: "CGST: "
                }), /*#__PURE__*/_jsxs("strong", {
                  className: "text-dark",
                  children: ["₹", billTotals.cgst]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-muted fw-normal",
                  children: "SGST: "
                }), /*#__PURE__*/_jsxs("strong", {
                  className: "text-dark",
                  children: ["₹", billTotals.sgst]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("span", {
                  className: "text-muted fw-normal",
                  children: "Net Amount: "
                }), /*#__PURE__*/_jsxs("strong", {
                  className: "text-primary fs-5",
                  children: ["₹", billTotals.net]
                })]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "card border shadow-sm p-3 mb-3 bg-white",
            style: {
              borderRadius: '10px'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "fw-bold mb-0 text-dark",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-plus-circle-dotted text-primary me-1"
                }), editingItemIndex !== null ? `Modify Row #${editingItemIndex + 1}` : 'Add Medicine to Inward Bill']
              }), editingItemIndex !== null && /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-link text-danger p-0 text-decoration-none",
                onClick: resetItemForm,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-x-circle me-1"
                }), " Cancel Row Edit"]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "row g-2 mb-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "col-md-9 position-relative",
                children: [/*#__PURE__*/_jsxs("label", {
                  className: "form-label small fw-bold mb-1 d-flex justify-content-between align-items-center",
                  children: [/*#__PURE__*/_jsxs("span", {
                    children: ["Product Search (Master Auto-Search) ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-link btn-sm p-0 text-decoration-none fw-semibold",
                    style: {
                      fontSize: '11px'
                    },
                    onClick: () => openAddMasterModal(itemForm.item_name),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-plus-circle me-1"
                    }), "+ Add to Master"]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "input-group input-group-sm",
                  children: [/*#__PURE__*/_jsx("input", {
                    type: "text",
                    id: "pharma_product_search_input",
                    className: `form-control form-control-sm fw-semibold ${itemForm.item_id ? 'border-success' : ''}`,
                    placeholder: "Type medicine name (e.g. ACEFE-P, Augmentin)...",
                    value: itemForm.item_name,
                    autoComplete: "off",
                    onChange: e => handleProductSearchChange(e.target.value),
                    onKeyDown: handleProductSearchKeyDown,
                    onFocus: () => {
                      if (itemForm.item_name.trim().length >= 1) {
                        handleProductSearchChange(itemForm.item_name);
                      }
                    }
                  }), itemForm.item_id > 0 && /*#__PURE__*/_jsx("span", {
                    className: "input-group-text bg-success text-white py-0 px-2",
                    title: "Matched in Master Catalog",
                    children: /*#__PURE__*/_jsx("i", {
                      className: "bi bi-check2"
                    })
                  })]
                }), itemForm.generic_name ? /*#__PURE__*/_jsxs("div", {
                  className: "text-muted text-truncate mt-1 d-flex align-items-center gap-1",
                  style: {
                    fontSize: '11px'
                  },
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "badge bg-light text-primary border",
                    style: {
                      fontSize: '9.5px'
                    },
                    children: itemForm.category || 'Tablet'
                  }), /*#__PURE__*/_jsx("span", {
                    children: itemForm.generic_name
                  })]
                }) : null, activeSearchRow && /*#__PURE__*/_jsxs("div", {
                  ref: suggestionsDropdownRef,
                  className: "shadow-lg border rounded-2 bg-white",
                  style: {
                    position: 'absolute',
                    top: '100%',
                    left: 0,
                    width: '100%',
                    maxHeight: '280px',
                    overflowY: 'auto',
                    zIndex: 1060,
                    marginTop: '2px'
                  },
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "bg-light px-2.5 py-1.5 border-bottom d-flex justify-content-between align-items-center",
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "small fw-bold text-secondary",
                      style: {
                        fontSize: '11px'
                      },
                      children: isSearchingMeds ? /*#__PURE__*/_jsxs("span", {
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-hourglass-split spinner-border spinner-border-sm me-1"
                        }), "Searching master..."]
                      }) : /*#__PURE__*/_jsxs("span", {
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-database-check me-1 text-primary"
                        }), "Pharmacy Master (", suggestions.length, " found)", suggestions.length > 0 && /*#__PURE__*/_jsx("span", {
                          className: "text-muted ms-2 fw-normal",
                          style: {
                            fontSize: '10px'
                          },
                          children: "(Use ↑ ↓ & Enter)"
                        })]
                      })
                    }), /*#__PURE__*/_jsx("button", {
                      type: "button",
                      className: "btn-close",
                      style: {
                        fontSize: '9px'
                      },
                      onClick: () => {
                        setActiveSearchRow(false);
                        setSelectedSuggestionIndex(-1);
                      }
                    })]
                  }), suggestions.length > 0 ? suggestions.map((sug, sIdx) => {
                    const isSelected = sIdx === selectedSuggestionIndex;
                    return /*#__PURE__*/_jsxs("div", {
                      ref: isSelected ? activeSuggestionRef : null,
                      className: `p-2 border-bottom ${isSelected ? 'bg-primary-subtle' : 'hover-bg-light'}`,
                      style: {
                        cursor: 'pointer',
                        transition: 'all 0.12s ease',
                        borderLeft: isSelected ? '4px solid #0284c7' : '4px solid transparent',
                        backgroundColor: isSelected ? '#e0f2fe' : undefined
                      },
                      onMouseEnter: () => setSelectedSuggestionIndex(sIdx),
                      onMouseDown: e => {
                        e.preventDefault();
                        handleSelectMedicine(sug);
                      },
                      children: [/*#__PURE__*/_jsxs("div", {
                        className: "d-flex justify-content-between align-items-center",
                        children: [/*#__PURE__*/_jsxs("span", {
                          className: `text-dark ${isSelected ? 'fw-bold text-primary' : 'fw-semibold'}`,
                          style: {
                            fontSize: '12.5px'
                          },
                          children: [isSelected && /*#__PURE__*/_jsx("i", {
                            className: "bi bi-arrow-right-short text-primary me-1"
                          }), sug.item_name]
                        }), /*#__PURE__*/_jsxs("div", {
                          className: "d-flex align-items-center gap-1",
                          children: [isSelected && /*#__PURE__*/_jsxs("span", {
                            className: "badge bg-primary text-white",
                            style: {
                              fontSize: '9px',
                              fontWeight: 600
                            },
                            children: [/*#__PURE__*/_jsx("i", {
                              className: "bi bi-arrow-return-left me-1"
                            }), "Enter"]
                          }), /*#__PURE__*/_jsx("span", {
                            className: "badge bg-light text-primary border",
                            style: {
                              fontSize: '10px'
                            },
                            children: sug.category || 'Tablet'
                          })]
                        })]
                      }), sug.generic_name && /*#__PURE__*/_jsx("div", {
                        className: "text-muted text-truncate small",
                        style: {
                          fontSize: '11px'
                        },
                        children: sug.generic_name
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "d-flex gap-2 mt-1 align-items-center",
                        style: {
                          fontSize: '10.5px'
                        },
                        children: [/*#__PURE__*/_jsxs("span", {
                          className: "text-secondary",
                          children: ["Pack: ", /*#__PURE__*/_jsxs("strong", {
                            children: [sug.units_per_pack || 10, "s"]
                          })]
                        }), /*#__PURE__*/_jsxs("span", {
                          className: "text-secondary",
                          children: ["GST: ", /*#__PURE__*/_jsxs("strong", {
                            children: [sug.gst_rate, "%"]
                          })]
                        }), /*#__PURE__*/_jsxs("span", {
                          className: "text-secondary",
                          children: ["HSN: ", /*#__PURE__*/_jsx("strong", {
                            children: sug.hsn_code || '3004'
                          })]
                        }), sug.mrp ? /*#__PURE__*/_jsxs("span", {
                          className: "text-secondary",
                          children: ["MRP: ", /*#__PURE__*/_jsxs("strong", {
                            children: ["₹", sug.mrp]
                          })]
                        }) : null]
                      })]
                    }, sIdx);
                  }) : !isSearchingMeds && /*#__PURE__*/_jsxs("div", {
                    className: "p-3 text-center text-muted",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-search fs-4 d-block mb-1 text-secondary"
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "small fw-semibold",
                      children: ["No medicine found matching \"", itemForm.item_name, "\""]
                    }), /*#__PURE__*/_jsx("div", {
                      className: "text-muted",
                      style: {
                        fontSize: '11px'
                      },
                      children: "Click below to add it directly into Pharmacy Master"
                    })]
                  }), /*#__PURE__*/_jsx("div", {
                    className: "p-2 bg-light border-top text-center",
                    children: /*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-sm btn-outline-primary w-100 fw-bold py-1",
                      style: {
                        fontSize: '12px'
                      },
                      onMouseDown: e => {
                        e.preventDefault();
                        openAddMasterModal(itemForm.item_name);
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-plus-circle-fill me-1"
                      }), "+ Add \"", itemForm.item_name || 'New Medicine', "\" in Master"]
                    })
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "col-md-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small fw-bold mb-1",
                  children: "Product Code"
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm bg-light text-secondary font-monospace",
                  value: itemForm.product_master_id || itemForm.item_id || 'AUTO',
                  readOnly: true
                })]
              })]
            }), itemForm.item_id > 0 || editingItemIndex !== null ? /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center justify-content-between bg-primary-subtle border border-primary-subtle px-2.5 py-1 rounded mb-2",
                children: [/*#__PURE__*/_jsxs("span", {
                  className: "small text-primary fw-semibold",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle-fill me-1 text-success"
                  }), editingItemIndex !== null ? `Editing: ${itemForm.item_name}` : `Selected: ${itemForm.item_name} (Auto-filled with last batch, rate & location)`]
                }), /*#__PURE__*/_jsxs("button", {
                  type: "button",
                  className: "btn btn-link btn-sm p-0 text-danger text-decoration-none fw-semibold",
                  style: {
                    fontSize: '11px'
                  },
                  onClick: () => resetItemForm(true),
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-x-circle me-1"
                  }), " Change Product"]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2 mb-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Packaging"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    id: "pharma_packaging_input",
                    className: "form-control form-control-sm",
                    placeholder: "10",
                    value: itemForm.units_per_pack,
                    onChange: e => handleItemFormChange('units_per_pack', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_batch_no_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label small fw-bold mb-1",
                    children: ["Batch No. ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    id: "pharma_batch_no_input",
                    className: "form-control form-control-sm text-uppercase font-monospace fw-bold",
                    placeholder: "e.g. B-902",
                    value: itemForm.batch_no,
                    onChange: e => handleItemFormChange('batch_no', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_exp_month_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Exp. Month"
                  }), /*#__PURE__*/_jsx("select", {
                    id: "pharma_exp_month_input",
                    className: "form-select form-select-sm",
                    value: itemForm.exp_month,
                    onChange: e => handleItemFormChange('exp_month', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_exp_year_input'),
                    children: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'].map(m => /*#__PURE__*/_jsxs("option", {
                      value: m,
                      children: [m, " (", ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][parseInt(m) - 1], ")"]
                    }, m))
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Exp. Year"
                  }), /*#__PURE__*/_jsx("select", {
                    id: "pharma_exp_year_input",
                    className: "form-select form-select-sm",
                    value: itemForm.exp_year,
                    onChange: e => handleItemFormChange('exp_year', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_mrp_input'),
                    children: ['24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36'].map(y => /*#__PURE__*/_jsxs("option", {
                      value: y,
                      children: ["20", y]
                    }, y))
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "MRP (₹)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.01",
                    id: "pharma_mrp_input",
                    className: "form-control form-control-sm",
                    placeholder: "0.00",
                    value: itemForm.mrp,
                    onChange: e => handleItemFormChange('mrp', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_qty_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-center mb-1",
                    children: [/*#__PURE__*/_jsxs("label", {
                      className: "form-label small fw-bold mb-0",
                      children: ["Qty (Packs) ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger",
                        children: "*"
                      })]
                    }), Number(itemForm.qty_packs) < 0 && /*#__PURE__*/_jsx("span", {
                      className: "badge bg-danger text-white py-0 px-1",
                      style: {
                        fontSize: '9px'
                      },
                      children: "RETURN"
                    })]
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    id: "pharma_qty_input",
                    className: `form-control form-control-sm fw-bold ${Number(itemForm.qty_packs) < 0 ? 'text-danger border-danger bg-danger-subtle' : ''}`,
                    placeholder: "Qty (+ In / - Ret)",
                    value: itemForm.qty_packs,
                    onChange: e => handleItemFormChange('qty_packs', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_free_qty_input')
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2 mb-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Free Qty"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    min: "0",
                    id: "pharma_free_qty_input",
                    className: "form-control form-control-sm",
                    value: itemForm.free_qty_packs,
                    onChange: e => handleItemFormChange('free_qty_packs', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_ptr_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Purchase Rate (PTR)"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.01",
                    id: "pharma_ptr_input",
                    className: "form-control form-control-sm",
                    placeholder: "0.00",
                    value: itemForm.ptr,
                    onChange: e => handleItemFormChange('ptr', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_disc_pct_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Amount"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm bg-light text-secondary fw-semibold",
                    value: '₹' + Number(itemForm.amount || 0).toFixed(2),
                    readOnly: true
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Discount %"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.1",
                    min: "0",
                    max: "100",
                    id: "pharma_disc_pct_input",
                    className: "form-control form-control-sm",
                    value: itemForm.discount_pct,
                    onChange: e => handleItemFormChange('discount_pct', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'submit')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "SCH Amount"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.01",
                    min: "0",
                    id: "pharma_sch_amount_input",
                    className: "form-control form-control-sm",
                    value: itemForm.sch_disc_amount,
                    onChange: e => handleItemFormChange('sch_disc_amount', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_sch_disc_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "SCH Discount %"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.1",
                    min: "0",
                    max: "100",
                    id: "pharma_sch_disc_input",
                    className: "form-control form-control-sm",
                    value: itemForm.sch_disc_pct,
                    onChange: e => handleItemFormChange('sch_disc_pct', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'submit')
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2 mb-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Taxable Amount"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm bg-light text-secondary fw-semibold",
                    value: '₹' + Number(itemForm.taxable_amount || 0).toFixed(2),
                    readOnly: true
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "CGST %"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: itemForm.cgst_pct,
                    onChange: e => {
                      const val = parseFloat(e.target.value) || 0;
                      handleItemFormChange('cgst_pct', val);
                      handleItemFormChange('sgst_pct', val);
                    },
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "0",
                      children: "0% (Nil / Exempt)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "2.5",
                      children: "2.5% (5% GST)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "9",
                      children: "9.0% (18% GST)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "20",
                      children: "20.0% (40% GST)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "6",
                      children: "6.0% (12% Legacy)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "14",
                      children: "14.0% (28% Legacy)"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "SGST / IGST %"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: itemForm.sgst_pct,
                    onChange: e => handleItemFormChange('sgst_pct', parseFloat(e.target.value) || 0),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "0",
                      children: "0% (Nil / Exempt)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "2.5",
                      children: "2.5% (5% GST)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "9",
                      children: "9.0% (18% GST)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "20",
                      children: "20.0% (40% GST)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "6",
                      children: "6.0% (12% Legacy)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "14",
                      children: "14.0% (28% Legacy)"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Net Amount"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm bg-primary-subtle text-primary fw-bold",
                    value: '₹' + Number(itemForm.net_amount || 0).toFixed(2),
                    readOnly: true
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Selling Price"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "number",
                    step: "0.01",
                    id: "pharma_selling_price_input",
                    className: "form-control form-control-sm",
                    placeholder: "MRP",
                    value: itemForm.selling_price,
                    onChange: e => handleItemFormChange('selling_price', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_shelf_no_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "HSNCODE"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    id: "pharma_hsn_input",
                    className: "form-control form-control-sm",
                    value: itemForm.hsn_code,
                    onChange: e => handleItemFormChange('hsn_code', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_shelf_no_input')
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-2 align-items-end",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-3 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Storage"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: itemForm.storage_type,
                    onChange: e => handleItemFormChange('storage_type', e.target.value),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "Normal",
                      children: "Normal"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Cold Storage",
                      children: "Cold Storage (2-8°C)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Cool",
                      children: "Cool (8-15°C)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "Deep Freeze",
                      children: "Deep Freeze (-20°C)"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Shelf No."
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    id: "pharma_shelf_no_input",
                    className: "form-control form-control-sm",
                    placeholder: "e.g. S-1",
                    value: itemForm.shelf_no,
                    onChange: e => handleItemFormChange('shelf_no', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'pharma_rack_no_input')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-2 col-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Rack No."
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    id: "pharma_rack_no_input",
                    className: "form-control form-control-sm",
                    placeholder: "e.g. R-10",
                    value: itemForm.rack_no,
                    onChange: e => handleItemFormChange('rack_no', e.target.value),
                    onKeyDown: e => handleFieldKeyDown(e, 'submit')
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-5 col-12 d-flex gap-2 justify-content-end mt-2 mt-md-0",
                  children: [/*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-outline-secondary btn-sm px-3",
                    onClick: () => resetItemForm(true),
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-arrow-counterclockwise me-1"
                    }), " Clear"]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    id: "pharma_add_stock_btn",
                    className: editingItemIndex !== null ? "btn btn-warning text-dark fw-bold btn-sm px-3 shadow-sm" : Number(itemForm.qty_packs) < 0 ? "btn btn-danger fw-bold btn-sm px-4 shadow-sm" : "btn btn-primary fw-bold btn-sm px-4 shadow-sm",
                    onClick: handleAddOrUpdateItem,
                    title: Number(itemForm.qty_packs) < 0 ? "Add Return / Debit Item (Press Ctrl+Enter anytime)" : "Add Stock (Press Ctrl+Enter anytime)",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: editingItemIndex !== null ? "bi bi-check2-circle me-1" : Number(itemForm.qty_packs) < 0 ? "bi bi-dash-circle me-1" : "bi bi-plus-circle me-1"
                    }), editingItemIndex !== null ? 'Update Stock Item' : Number(itemForm.qty_packs) < 0 ? '⊖ Add Return Item' : '+ Add Stock', /*#__PURE__*/_jsx("span", {
                      className: "badge bg-white bg-opacity-25 ms-1.5 py-0.5 px-1.5 text-white",
                      style: {
                        fontSize: '10px'
                      },
                      children: "Ctrl+↵"
                    })]
                  })]
                })]
              })]
            }) : /*#__PURE__*/_jsxs("div", {
              className: "text-center py-4 text-muted small bg-light rounded-2 border border-dashed mt-2",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-search text-primary me-1.5 fs-5 d-block mb-1"
              }), /*#__PURE__*/_jsxs("span", {
                children: ["Search and select a medicine in ", /*#__PURE__*/_jsx("strong", {
                  children: "Product Search"
                }), " above to enter stock details and auto-fill last batch, expiry, MRP, rate & rack location."]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex flex-wrap justify-content-between align-items-center p-3 bg-light rounded-3 border",
            children: [/*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsx("span", {
                className: "text-muted small",
                children: "Ready to commit to inventory:"
              }), /*#__PURE__*/_jsxs("div", {
                className: "fw-bold text-dark",
                children: [rows.length, " ", rows.length === 1 ? 'medicine' : 'medicines', " | Net Payable: ", /*#__PURE__*/_jsxs("span", {
                  className: "text-primary fs-6",
                  children: ["₹", billTotals.net]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-2",
              children: [editingPurchaseId && /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-outline-secondary btn-sm px-3",
                onClick: cancelEditPurchase,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-x-circle me-1"
                }), " Cancel Edit"]
              }), /*#__PURE__*/_jsxs("button", {
                className: editingPurchaseId ? "btn btn-warning shadow-sm fw-bold px-3 py-2 text-dark" : isChallan ? "btn btn-warning fw-bold text-dark shadow-sm px-4 py-2" : "btn btn-success-custom shadow-sm px-4 py-2",
                onClick: savePurchaseBill,
                children: [/*#__PURE__*/_jsx("i", {
                  className: editingPurchaseId ? "bi bi-check2-all me-1" : isChallan ? "bi bi-truck me-1" : "bi bi-check-circle me-1"
                }), editingPurchaseId ? `Update Purchase Invoice #${invoiceNo}` : isChallan ? 'Record Delivery Challan & Update Stock' : 'Record Inward Bill & Update Inventory']
              })]
            })]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "col-xl-4 col-lg-5",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "card border shadow-sm mb-3",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "fw-bold text-dark small",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-truck text-warning me-1"
                }), "Pending Delivery Challans"]
              }), selectedSupplierId && /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-link p-0 text-decoration-none",
                onClick: () => fetchSupplierChallans(selectedSupplierId),
                title: "Refresh supplier challans",
                children: /*#__PURE__*/_jsx("i", {
                  className: `bi bi-arrow-repeat ${isLoadingChallans ? 'spinner-border spinner-border-sm' : ''}`
                })
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "card-body p-2",
              style: {
                maxHeight: '280px',
                overflowY: 'auto'
              },
              children: !selectedSupplierId ? /*#__PURE__*/_jsx("div", {
                className: "text-muted small p-3 text-center bg-light rounded",
                children: "Select a supplier above to view pending challans."
              }) : isLoadingChallans ? /*#__PURE__*/_jsxs("div", {
                className: "text-center py-3 text-muted small",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "spinner-border spinner-border-sm text-primary me-2"
                }), "Loading supplier challans..."]
              }) : supplierChallans.length === 0 ? /*#__PURE__*/_jsx("div", {
                className: "text-muted small p-3 text-center bg-light rounded",
                children: "No challan items available for this supplier."
              }) : supplierChallans.map((ch, chIdx) => /*#__PURE__*/_jsxs("div", {
                className: "border rounded-2 p-2 mb-2 bg-light",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center mb-1.5 pb-1 border-bottom",
                  children: [/*#__PURE__*/_jsxs("div", {
                    children: [/*#__PURE__*/_jsxs("strong", {
                      className: "text-dark",
                      style: {
                        fontSize: '12px'
                      },
                      children: ["Challan #", ch.challan_no]
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "text-muted",
                      style: {
                        fontSize: '10.5px'
                      },
                      children: ["Date: ", ch.date || 'N/A', " • ", ch.items ? ch.items.length : 0, " items"]
                    })]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-xs btn-outline-primary py-0.5 px-2",
                    style: {
                      fontSize: '11px'
                    },
                    onClick: () => includeAllChallanItems(ch),
                    title: "Import all items from this challan into invoice",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-box-arrow-in-right me-1"
                    }), " Include All"]
                  })]
                }), ch.items && ch.items.length > 0 ? /*#__PURE__*/_jsx("div", {
                  className: "d-flex flex-column gap-1",
                  children: ch.items.map((ci, cItemIdx) => /*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-center bg-white p-1.5 rounded border",
                    style: {
                      fontSize: '11px'
                    },
                    children: [/*#__PURE__*/_jsxs("div", {
                      className: "text-truncate me-2",
                      style: {
                        maxWidth: '170px'
                      },
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-semibold text-dark text-truncate",
                        children: ci.item_name
                      }), /*#__PURE__*/_jsxs("span", {
                        className: "text-muted font-monospace",
                        style: {
                          fontSize: '10px'
                        },
                        children: ["Batch: ", ci.batch_no || '-', " | Qty: ", ci.qty_packs]
                      })]
                    }), /*#__PURE__*/_jsx("button", {
                      type: "button",
                      className: "btn btn-xs btn-primary py-0 px-1.5 text-nowrap",
                      style: {
                        fontSize: '10.5px'
                      },
                      onClick: () => includeChallanItem(ch, ci),
                      title: "Import this item into invoice",
                      children: "+ Include"
                    })]
                  }, cItemIdx))
                }) : /*#__PURE__*/_jsx("div", {
                  className: "text-muted small",
                  style: {
                    fontSize: '11px'
                  },
                  children: "No items found in this challan."
                })]
              }, chIdx))
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "card border shadow-sm",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "fw-bold text-dark small",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history text-info me-1"
                }), "Previous Purchase History"]
              }), historyTargetItemName && /*#__PURE__*/_jsx("span", {
                className: "badge bg-info-subtle text-info border text-truncate",
                style: {
                  maxWidth: '140px',
                  fontSize: '10.5px'
                },
                children: historyTargetItemName
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "card-body p-2",
              style: {
                maxHeight: '310px',
                overflowY: 'auto'
              },
              children: isLoadingProductHistory ? /*#__PURE__*/_jsxs("div", {
                className: "text-center py-3 text-muted small",
                children: [/*#__PURE__*/_jsx("div", {
                  className: "spinner-border spinner-border-sm text-info me-2"
                }), "Fetching purchase rate history..."]
              }) : !historyTargetItemName && productHistory.length === 0 ? /*#__PURE__*/_jsxs("div", {
                className: "text-muted small p-3 text-center bg-light rounded",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-search d-block mb-1 fs-5 text-secondary"
                }), "Type or select a product in ", /*#__PURE__*/_jsx("strong", {
                  children: "Product Search"
                }), " to check previous purchase prices & vendors."]
              }) : productHistory.length === 0 ? /*#__PURE__*/_jsx("div", {
                className: "text-muted small p-3 text-center bg-light rounded",
                children: "No purchase history found for selected product."
              }) : /*#__PURE__*/_jsx("div", {
                className: "d-flex flex-column gap-2",
                children: productHistory.map((ph, phIdx) => /*#__PURE__*/_jsxs("div", {
                  className: "p-2 border rounded-2 bg-light",
                  style: {
                    fontSize: '11px'
                  },
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-center mb-1",
                    children: [/*#__PURE__*/_jsx("strong", {
                      className: "text-primary text-truncate",
                      style: {
                        maxWidth: '160px'
                      },
                      children: ph.supplier_name || 'Distributor'
                    }), /*#__PURE__*/_jsx("span", {
                      className: "text-muted",
                      style: {
                        fontSize: '10px'
                      },
                      children: ph.invoice_date || ph.created_at ? (ph.invoice_date || ph.created_at).slice(0, 10) : ''
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between text-secondary mb-1",
                    style: {
                      fontSize: '10.5px'
                    },
                    children: [/*#__PURE__*/_jsxs("span", {
                      children: ["Inv: ", /*#__PURE__*/_jsx("strong", {
                        children: ph.supplier_invoice_no || '-'
                      })]
                    }), /*#__PURE__*/_jsxs("span", {
                      children: ["Batch: ", /*#__PURE__*/_jsx("span", {
                        className: "font-monospace",
                        children: ph.batch_no || '-'
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between align-items-center pt-1 border-top mt-1",
                    children: [/*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("span", {
                        className: "text-muted",
                        children: "PTR: "
                      }), /*#__PURE__*/_jsxs("strong", {
                        className: "text-dark",
                        children: ["₹", Number(ph.ptr || 0).toFixed(2)]
                      }), ph.mrp ? /*#__PURE__*/_jsxs("span", {
                        className: "text-muted ms-2",
                        children: ["MRP: ₹", Number(ph.mrp).toFixed(2)]
                      }) : null, Number(ph.discount_pct) > 0 ? /*#__PURE__*/_jsxs("span", {
                        className: "text-success ms-1",
                        children: ["(-", ph.discount_pct, "%)"]
                      }) : null]
                    }), /*#__PURE__*/_jsx("button", {
                      type: "button",
                      className: "btn btn-xs btn-outline-info py-0 px-1.5",
                      style: {
                        fontSize: '10.5px'
                      },
                      onClick: () => {
                        setItemForm(prev => recomputeItemForm({
                          ...prev,
                          ptr: ph.ptr || prev.ptr,
                          mrp: ph.mrp || prev.mrp,
                          discount_pct: ph.discount_pct !== undefined ? ph.discount_pct : prev.discount_pct,
                          gst_rate: ph.gst_rate !== undefined ? ph.gst_rate : prev.gst_rate,
                          cgst_pct: (Number(ph.gst_rate) || 12) / 2,
                          sgst_pct: (Number(ph.gst_rate) || 12) / 2
                        }));
                        notify(`Applied historical PTR ₹${ph.ptr} to entry form.`, 'info');
                      },
                      title: "Apply these purchase rates to current item form",
                      children: "Use Rate"
                    })]
                  })]
                }, phIdx))
              })
            })]
          })]
        })]
      })]
    }), subTab === 'excel' && /*#__PURE__*/_jsxs("div", {
      className: "counter-card",
      children: [/*#__PURE__*/_jsxs("h5", {
        className: "fw-bold text-success mb-2",
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-file-earmark-spreadsheet me-1"
        }), " Excel / CSV Inventory Importer"]
      }), /*#__PURE__*/_jsxs("p", {
        className: "text-muted small mb-3",
        children: ["Upload your stock export from Microsoft Excel or CSV format to seamlessly import medicines, batch numbers, MRP, PTR, HSN codes, and opening stock into ", /*#__PURE__*/_jsx("strong", {
          children: store.store_name
        }), "."]
      }), /*#__PURE__*/_jsxs("div", {
        className: "p-4 bg-light rounded-3 border text-center mb-3",
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-cloud-arrow-up fs-1 text-success d-block mb-2"
        }), /*#__PURE__*/_jsx("h6", {
          className: "fw-bold",
          children: "Upload CSV / Excel File"
        }), /*#__PURE__*/_jsx("p", {
          className: "small text-muted mb-3",
          children: "Ensure file is formatted as standard CSV or Excel-exported CSV."
        }), /*#__PURE__*/_jsx("input", {
          type: "file",
          id: "excel_file_input",
          accept: ".csv, .txt",
          className: "form-control form-control-sm w-50 mx-auto mb-3"
        }), /*#__PURE__*/_jsxs("button", {
          className: "btn btn-success-custom",
          onClick: async () => {
            const fileInput = document.getElementById('excel_file_input');
            if (!fileInput.files || fileInput.files.length === 0) {
              alert('Please select a CSV file first.');
              return;
            }
            const formData = new FormData();
            formData.append('store_id', store.store_id);
            formData.append('import_file', fileInput.files[0]);
            try {
              const res = await fetch(API_BASE + 'stock/import-excel', {
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
          },
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-upload me-1"
          }), " Import Inventory into ", store.store_name]
        })]
      })]
    }), subTab === 'suppliers' && /*#__PURE__*/_jsxs("div", {
      className: "counter-card",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3",
        children: [/*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("h5", {
            className: "fw-bold mb-0 text-dark",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-truck text-primary me-1"
            }), " Approved Drug Distributors & Suppliers"]
          }), /*#__PURE__*/_jsx("small", {
            className: "text-muted",
            children: "Manage licensed pharmaceutical distributors, DL Form 20B/21B, GSTIN, and credit terms"
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex gap-2",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "input-group input-group-sm",
            style: {
              width: 250
            },
            children: [/*#__PURE__*/_jsx("span", {
              className: "input-group-text bg-white border-end-0",
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-search text-muted"
              })
            }), /*#__PURE__*/_jsx("input", {
              type: "text",
              className: "form-control border-start-0",
              placeholder: "Search distributor, GST...",
              value: supplierSearch,
              onChange: e => setSupplierSearch(e.target.value)
            }), supplierSearch && /*#__PURE__*/_jsx("button", {
              className: "btn btn-outline-secondary",
              type: "button",
              onClick: () => setSupplierSearch(''),
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-x"
              })
            })]
          }), /*#__PURE__*/_jsxs("button", {
            className: "btn btn-primary btn-sm px-3",
            onClick: openAddSupplier,
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-plus-circle me-1"
            }), " Add New Supplier"]
          })]
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "table-responsive",
        children: /*#__PURE__*/_jsxs("table", {
          className: "table table-pos align-middle mb-0",
          children: [/*#__PURE__*/_jsx("thead", {
            children: /*#__PURE__*/_jsxs("tr", {
              children: [/*#__PURE__*/_jsx("th", {
                style: {
                  width: 45
                },
                children: "#"
              }), /*#__PURE__*/_jsx("th", {
                children: "Distributor Name"
              }), /*#__PURE__*/_jsx("th", {
                children: "DL Form 20B / 21B"
              }), /*#__PURE__*/_jsx("th", {
                children: "GSTIN & PAN"
              }), /*#__PURE__*/_jsx("th", {
                children: "Phone / Contact"
              }), /*#__PURE__*/_jsx("th", {
                children: "Credit Terms"
              }), /*#__PURE__*/_jsx("th", {
                children: "Address / Bank"
              }), /*#__PURE__*/_jsx("th", {
                style: {
                  width: 90
                },
                className: "text-end pe-3",
                children: "Actions"
              })]
            })
          }), /*#__PURE__*/_jsx("tbody", {
            children: filteredSuppliers.length === 0 ? /*#__PURE__*/_jsx("tr", {
              children: /*#__PURE__*/_jsxs("td", {
                colSpan: "8",
                className: "text-center py-4 text-muted",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-truck fs-2 d-block mb-1 text-secondary"
                }), suppliers.length === 0 ? 'No distributors registered yet. Click "Add New Supplier" to create one.' : 'No suppliers match your search query.']
              })
            }) : filteredSuppliers.map((s, i) => /*#__PURE__*/_jsxs("tr", {
              children: [/*#__PURE__*/_jsx("td", {
                className: "fw-semibold text-secondary",
                children: i + 1
              }), /*#__PURE__*/_jsxs("td", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-bold text-dark fs-6",
                  children: s.supplier_name
                }), s.email && /*#__PURE__*/_jsxs("small", {
                  className: "text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-envelope me-1"
                  }), s.email]
                })]
              }), /*#__PURE__*/_jsxs("td", {
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "small",
                  children: [/*#__PURE__*/_jsx("strong", {
                    children: "20B:"
                  }), " ", s.dl_no_20b || /*#__PURE__*/_jsx("span", {
                    className: "text-muted",
                    children: "N/A"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "small",
                  children: [/*#__PURE__*/_jsx("strong", {
                    children: "21B:"
                  }), " ", s.dl_no_21b || /*#__PURE__*/_jsx("span", {
                    className: "text-muted",
                    children: "N/A"
                  })]
                })]
              }), /*#__PURE__*/_jsxs("td", {
                children: [/*#__PURE__*/_jsx("div", {
                  children: /*#__PURE__*/_jsx("code", {
                    className: "text-primary fw-bold",
                    children: s.gstin || 'N/A'
                  })
                }), s.pan_no && /*#__PURE__*/_jsxs("small", {
                  className: "text-muted d-block",
                  children: ["PAN: ", s.pan_no]
                })]
              }), /*#__PURE__*/_jsxs("td", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-semibold text-dark",
                  children: s.phone || 'N/A'
                }), s.contact_person && /*#__PURE__*/_jsxs("small", {
                  className: "text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-person me-1"
                  }), s.contact_person]
                })]
              }), /*#__PURE__*/_jsx("td", {
                children: /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-light text-dark border px-2 py-1",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-calendar-check me-1 text-primary"
                  }), s.credit_days || 30, " Days"]
                })
              }), /*#__PURE__*/_jsxs("td", {
                style: {
                  maxWidth: 220
                },
                children: [s.address && /*#__PURE__*/_jsxs("div", {
                  className: "small text-truncate",
                  title: s.address,
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-geo-alt me-1 text-muted"
                  }), s.address]
                }), s.bank_details && /*#__PURE__*/_jsxs("div", {
                  className: "small text-muted text-truncate",
                  title: s.bank_details,
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-bank me-1"
                  }), s.bank_details]
                }), !s.address && !s.bank_details && /*#__PURE__*/_jsx("span", {
                  className: "text-muted small",
                  children: "-"
                })]
              }), /*#__PURE__*/_jsx("td", {
                className: "text-end pe-3",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "btn-group btn-group-sm",
                  children: [/*#__PURE__*/_jsx("button", {
                    className: "btn btn-outline-primary py-0.5 px-2",
                    title: "Edit Supplier",
                    onClick: () => openEditSupplier(s),
                    children: /*#__PURE__*/_jsx("i", {
                      className: "bi bi-pencil-square"
                    })
                  }), /*#__PURE__*/_jsx("button", {
                    className: "btn btn-outline-danger py-0.5 px-2",
                    title: "Remove Supplier",
                    onClick: () => handleDeleteSupplier(s.supplier_id, s.supplier_name),
                    children: /*#__PURE__*/_jsx("i", {
                      className: "bi bi-trash"
                    })
                  })]
                })
              })]
            }, s.supplier_id))
          })]
        })
      })]
    }), subTab === 'ledger' && /*#__PURE__*/_jsxs("div", {
      className: "counter-card",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "row g-3 mb-4",
        children: [/*#__PURE__*/_jsx("div", {
          className: "col-md-3",
          children: /*#__PURE__*/_jsx("div", {
            className: "card border-0 shadow-sm p-3 bg-white h-100 border-start border-4 border-primary",
            children: /*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-muted fw-semibold text-uppercase",
                  style: {
                    fontSize: '11px'
                  },
                  children: "Total Inward Purchases"
                }), /*#__PURE__*/_jsxs("h4", {
                  className: "fw-bold text-dark mb-0 mt-1",
                  children: ["₹", Number(ledgerOverall.total_purchases || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                  })]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "bg-primary-subtle text-primary p-2.5 rounded-circle",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-box-arrow-in-down fs-4"
                })
              })]
            })
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-3",
          children: /*#__PURE__*/_jsx("div", {
            className: "card border-0 shadow-sm p-3 bg-white h-100 border-start border-4 border-success",
            children: /*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-muted fw-semibold text-uppercase",
                  style: {
                    fontSize: '11px'
                  },
                  children: "Total Payments Settled"
                }), /*#__PURE__*/_jsxs("h4", {
                  className: "fw-bold text-success mb-0 mt-1",
                  children: ["₹", Number(ledgerOverall.total_paid || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                  })]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "bg-success-subtle text-success p-2.5 rounded-circle",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-check2-circle fs-4"
                })
              })]
            })
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-3",
          children: /*#__PURE__*/_jsx("div", {
            className: "card border-0 shadow-sm p-3 bg-white h-100 border-start border-4 border-danger",
            children: /*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-muted fw-semibold text-uppercase",
                  style: {
                    fontSize: '11px'
                  },
                  children: "Balance Outstanding"
                }), /*#__PURE__*/_jsxs("h4", {
                  className: "fw-bold text-danger mb-0 mt-1",
                  children: ["₹", Number(ledgerOverall.outstanding_balance || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                  })]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "bg-danger-subtle text-danger p-2.5 rounded-circle",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-exclamation-octagon fs-4"
                })
              })]
            })
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-3",
          children: /*#__PURE__*/_jsx("div", {
            className: "card border-0 shadow-sm p-3 bg-white h-100 border-start border-4 border-warning",
            children: /*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-muted fw-semibold text-uppercase",
                  style: {
                    fontSize: '11px'
                  },
                  children: "Pending Invoices"
                }), /*#__PURE__*/_jsxs("h4", {
                  className: "fw-bold text-warning-emphasis mb-0 mt-1",
                  children: [ledgerOverall.total_pending_bills || 0, " Bills"]
                })]
              }), /*#__PURE__*/_jsx("div", {
                className: "bg-warning-subtle text-warning-emphasis p-2.5 rounded-circle",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-receipt fs-4"
                })
              })]
            })
          })
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 bg-light p-2.5 rounded-3 border",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center gap-2 flex-grow-1",
          children: [/*#__PURE__*/_jsxs("label", {
            className: "form-label small fw-bold mb-0 text-nowrap",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-funnel me-1"
            }), "Select Supplier:"]
          }), /*#__PURE__*/_jsxs("select", {
            className: "form-select form-select-sm",
            style: {
              maxWidth: 320
            },
            value: activeLedgerSupplierId,
            onChange: e => {
              const val = e.target.value;
              setActiveLedgerSupplierId(val);
              if (val) fetchSupplierLedger(val);else setActiveLedgerDetail(null);
            },
            children: [/*#__PURE__*/_jsx("option", {
              value: "",
              children: "-- All Suppliers Summary --"
            }), suppliers.map(s => /*#__PURE__*/_jsx("option", {
              value: s.supplier_id,
              children: s.supplier_name
            }, s.supplier_id))]
          }), activeLedgerSupplierId && /*#__PURE__*/_jsxs("button", {
            className: "btn btn-outline-secondary btn-sm",
            onClick: () => {
              setActiveLedgerSupplierId('');
              setActiveLedgerDetail(null);
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-x-circle me-1"
            }), " View All"]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex gap-2",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-success btn-sm shadow-sm fw-semibold",
            onClick: () => openPaymentModal(activeLedgerSupplierId),
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-cash-stack me-1"
            }), " Record Supplier Payment"]
          }), /*#__PURE__*/_jsx("button", {
            className: "btn btn-outline-primary btn-sm",
            onClick: () => {
              fetchLedgerSummary();
              if (activeLedgerSupplierId) fetchSupplierLedger(activeLedgerSupplierId);
            },
            title: "Refresh Ledger Data",
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-clockwise"
            })
          })]
        })]
      }), !activeLedgerSupplierId && /*#__PURE__*/_jsx("div", {
        className: "table-responsive shadow-sm rounded border",
        children: /*#__PURE__*/_jsxs("table", {
          className: "table table-hover align-middle mb-0",
          children: [/*#__PURE__*/_jsx("thead", {
            className: "table-light",
            children: /*#__PURE__*/_jsxs("tr", {
              children: [/*#__PURE__*/_jsx("th", {
                style: {
                  width: 45
                },
                children: "#"
              }), /*#__PURE__*/_jsx("th", {
                children: "Distributor / Agency"
              }), /*#__PURE__*/_jsx("th", {
                children: "GSTIN & Phone"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-end",
                children: "Total Purchases"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-end",
                children: "Total Paid"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-end",
                children: "Outstanding Due"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-center",
                children: "Pending Bills"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-end pe-3",
                children: "Actions"
              })]
            })
          }), /*#__PURE__*/_jsx("tbody", {
            children: isLoadingLedger ? /*#__PURE__*/_jsx("tr", {
              children: /*#__PURE__*/_jsxs("td", {
                colSpan: "8",
                className: "text-center py-4",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "spinner-border spinner-border-sm me-2"
                }), "Loading ledger summary..."]
              })
            }) : ledgerSummary.length === 0 ? /*#__PURE__*/_jsx("tr", {
              children: /*#__PURE__*/_jsx("td", {
                colSpan: "8",
                className: "text-center py-4 text-muted",
                children: "No suppliers found."
              })
            }) : ledgerSummary.map((sup, idx) => /*#__PURE__*/_jsxs("tr", {
              children: [/*#__PURE__*/_jsx("td", {
                className: "text-muted fw-semibold",
                children: idx + 1
              }), /*#__PURE__*/_jsxs("td", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-bold text-dark",
                  children: sup.supplier_name
                }), sup.contact_person && /*#__PURE__*/_jsxs("small", {
                  className: "text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-person me-1"
                  }), sup.contact_person]
                })]
              }), /*#__PURE__*/_jsxs("td", {
                children: [/*#__PURE__*/_jsx("div", {
                  children: /*#__PURE__*/_jsx("code", {
                    className: "text-primary fw-semibold",
                    children: sup.gstin || 'No GSTIN'
                  })
                }), sup.phone && /*#__PURE__*/_jsxs("small", {
                  className: "text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-telephone me-1"
                  }), sup.phone]
                })]
              }), /*#__PURE__*/_jsxs("td", {
                className: "text-end fw-semibold",
                children: ["₹", Number(sup.total_purchases || 0).toLocaleString('en-IN', {
                  minimumFractionDigits: 2
                })]
              }), /*#__PURE__*/_jsxs("td", {
                className: "text-end text-success fw-semibold",
                children: ["₹", Number(sup.total_paid || 0).toLocaleString('en-IN', {
                  minimumFractionDigits: 2
                })]
              }), /*#__PURE__*/_jsx("td", {
                className: "text-end",
                children: /*#__PURE__*/_jsxs("span", {
                  className: `badge ${sup.outstanding_balance > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle fs-6' : 'bg-success-subtle text-success border border-success-subtle'} px-2.5 py-1.5`,
                  children: ["₹", Number(sup.outstanding_balance || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2
                  })]
                })
              }), /*#__PURE__*/_jsx("td", {
                className: "text-center",
                children: sup.pending_invoices > 0 ? /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-warning text-dark border border-warning px-2 py-1",
                  children: [sup.pending_invoices, " Unpaid"]
                }) : /*#__PURE__*/_jsx("span", {
                  className: "badge bg-light text-muted border px-2 py-1",
                  children: "All Settled"
                })
              }), /*#__PURE__*/_jsx("td", {
                className: "text-end pe-3",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "btn-group btn-group-sm",
                  children: [/*#__PURE__*/_jsxs("button", {
                    className: "btn btn-outline-primary",
                    onClick: () => {
                      setActiveLedgerSupplierId(String(sup.supplier_id));
                      fetchSupplierLedger(sup.supplier_id);
                    },
                    title: "View detailed ledger statement",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-journal-text me-1"
                    }), " Statement"]
                  }), /*#__PURE__*/_jsxs("button", {
                    className: "btn btn-outline-success",
                    onClick: () => openPaymentModal(sup.supplier_id),
                    title: "Record payment against this supplier",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-credit-card me-1"
                    }), " Pay"]
                  })]
                })
              })]
            }, sup.supplier_id))
          })]
        })
      }), activeLedgerSupplierId && activeLedgerDetail && /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsx("div", {
          className: "card border-0 shadow-sm p-3 mb-3 bg-white border-start border-4 border-primary",
          children: /*#__PURE__*/_jsxs("div", {
            className: "d-flex flex-wrap justify-content-between align-items-center gap-2",
            children: [/*#__PURE__*/_jsxs("div", {
              children: [/*#__PURE__*/_jsxs("h5", {
                className: "fw-bold text-dark mb-1 d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-building text-primary"
                }), activeLedgerDetail.supplier.supplier_name]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex flex-wrap gap-3 text-muted small",
                children: [/*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("strong", {
                    children: "GSTIN:"
                  }), " ", activeLedgerDetail.supplier.gstin || 'N/A']
                }), /*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("strong", {
                    children: "DL No:"
                  }), " ", activeLedgerDetail.supplier.dl_no_20b || activeLedgerDetail.supplier.dl_no_21b || 'N/A']
                }), /*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("strong", {
                    children: "Phone:"
                  }), " ", activeLedgerDetail.supplier.phone || 'N/A']
                }), /*#__PURE__*/_jsxs("span", {
                  children: [/*#__PURE__*/_jsx("strong", {
                    children: "Credit Days:"
                  }), " ", activeLedgerDetail.supplier.credit_days || 30, " Days"]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "text-end",
              children: [/*#__PURE__*/_jsx("small", {
                className: "text-muted text-uppercase d-block",
                style: {
                  fontSize: '11px'
                },
                children: "Net Outstanding Balance"
              }), /*#__PURE__*/_jsxs("span", {
                className: `badge ${activeLedgerDetail.summary.outstanding_balance > 0 ? 'bg-danger text-white fs-5' : 'bg-success text-white fs-5'} px-3 py-2`,
                children: ["₹", Number(activeLedgerDetail.summary.outstanding_balance || 0).toLocaleString('en-IN', {
                  minimumFractionDigits: 2
                })]
              })]
            })]
          })
        }), /*#__PURE__*/_jsxs("div", {
          className: "card border-0 shadow-sm mb-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("h6", {
              className: "fw-bold text-dark mb-0",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-hourglass-split text-warning me-1"
              }), " Pending Invoices (", activeLedgerDetail.pending_invoices?.length || 0, ")"]
            }), /*#__PURE__*/_jsx("small", {
              className: "text-muted",
              children: "Invoices awaiting full or partial payment"
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "table-responsive",
            children: /*#__PURE__*/_jsxs("table", {
              className: "table table-hover align-middle mb-0",
              style: {
                fontSize: '13px'
              },
              children: [/*#__PURE__*/_jsx("thead", {
                className: "table-light",
                children: /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsx("th", {
                    children: "Bill / Invoice No"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Invoice Date"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    children: "Bill Amount"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    children: "Paid Amount"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    children: "Pending Amount"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-center",
                    children: "Status"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end pe-3",
                    children: "Action"
                  })]
                })
              }), /*#__PURE__*/_jsx("tbody", {
                children: !activeLedgerDetail.pending_invoices || activeLedgerDetail.pending_invoices.length === 0 ? /*#__PURE__*/_jsx("tr", {
                  children: /*#__PURE__*/_jsxs("td", {
                    colSpan: "7",
                    className: "text-center py-3 text-muted",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-check2-all text-success me-1"
                    }), "All purchase bills for this supplier are fully paid."]
                  })
                }) : activeLedgerDetail.pending_invoices.map(inv => /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsx("td", {
                    className: "fw-bold text-primary",
                    children: inv.supplier_invoice_no
                  }), /*#__PURE__*/_jsx("td", {
                    children: inv.invoice_date
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end fw-semibold",
                    children: ["₹", Number(inv.net_amount).toFixed(2)]
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end text-success",
                    children: ["₹", Number(inv.paid_amount).toFixed(2)]
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end fw-bold text-danger",
                    children: ["₹", Number(inv.pending_amount).toFixed(2)]
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-center",
                    children: /*#__PURE__*/_jsx("span", {
                      className: `badge ${inv.payment_status === 'partially_paid' ? 'bg-warning text-dark' : 'bg-danger'} px-2 py-1`,
                      children: inv.payment_status === 'partially_paid' ? 'Partially Paid' : 'Unpaid'
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end pe-3",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "btn-group btn-group-sm",
                      children: [/*#__PURE__*/_jsxs("button", {
                        className: "btn btn-outline-primary py-1 px-2 shadow-sm",
                        onClick: () => loadPurchaseForEdit(inv.purchase_id),
                        title: "Edit this Purchase Invoice",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-pencil-square me-1"
                        }), " Edit"]
                      }), /*#__PURE__*/_jsxs("button", {
                        className: "btn btn-success py-1 px-2.5 shadow-sm fw-semibold",
                        onClick: () => openPaymentModal(activeLedgerDetail.supplier.supplier_id, inv.purchase_id, inv.pending_amount),
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-cash me-1"
                        }), " Pay Bill"]
                      })]
                    })
                  })]
                }, inv.purchase_id))
              })]
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card border-0 shadow-sm",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "card-header bg-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("h6", {
              className: "fw-bold text-dark mb-0",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-journal-check text-primary me-1"
              }), " Complete Ledger Statement & Running Balance"]
            }), /*#__PURE__*/_jsxs("span", {
              className: "badge bg-light text-dark border px-2 py-1",
              children: ["Total Transactions: ", activeLedgerDetail.statement?.length || 0]
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "table-responsive",
            children: /*#__PURE__*/_jsxs("table", {
              className: "table table-bordered table-hover align-middle mb-0",
              style: {
                fontSize: '12.5px'
              },
              children: [/*#__PURE__*/_jsx("thead", {
                className: "table-light",
                children: /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsx("th", {
                    style: {
                      width: 100
                    },
                    children: "Date"
                  }), /*#__PURE__*/_jsx("th", {
                    style: {
                      width: 130
                    },
                    children: "Voucher / Ref"
                  }), /*#__PURE__*/_jsx("th", {
                    style: {
                      width: 100
                    },
                    children: "Type"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Particulars & Details"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    style: {
                      width: 120
                    },
                    children: "Debit (₹ Payments)"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    style: {
                      width: 120
                    },
                    children: "Credit (₹ Inward)"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    style: {
                      width: 140
                    },
                    children: "Balance (₹ Payable)"
                  })]
                })
              }), /*#__PURE__*/_jsx("tbody", {
                children: !activeLedgerDetail.statement || activeLedgerDetail.statement.length === 0 ? /*#__PURE__*/_jsx("tr", {
                  children: /*#__PURE__*/_jsx("td", {
                    colSpan: "7",
                    className: "text-center py-4 text-muted",
                    children: "No transactions recorded for this supplier."
                  })
                }) : activeLedgerDetail.statement.map((row, idx) => /*#__PURE__*/_jsxs("tr", {
                  className: row.type === 'PAYMENT' ? 'table-success-subtle' : '',
                  children: [/*#__PURE__*/_jsx("td", {
                    className: "text-nowrap",
                    children: row.date
                  }), /*#__PURE__*/_jsx("td", {
                    className: "fw-semibold text-primary",
                    children: row.type === 'INVOICE' && row.invoice_id ? /*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-link p-0 text-decoration-none fw-bold text-primary",
                      onClick: () => loadPurchaseForEdit(row.invoice_id),
                      title: "Click to edit purchase invoice",
                      children: [row.voucher_no, " ", /*#__PURE__*/_jsx("i", {
                        className: "bi bi-pencil-square text-muted ms-1",
                        style: {
                          fontSize: '11px'
                        }
                      })]
                    }) : row.voucher_no
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: `badge ${row.type === 'INVOICE' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-success text-white'}`,
                      children: row.type === 'INVOICE' ? 'Purchase Bill' : 'Payment'
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: row.particulars
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end text-success fw-bold",
                    children: row.debit > 0 ? '₹' + Number(row.debit).toFixed(2) : '-'
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end text-dark fw-bold",
                    children: row.credit > 0 ? '₹' + Number(row.credit).toFixed(2) : '-'
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end fw-bold",
                    children: /*#__PURE__*/_jsxs("span", {
                      className: row.balance > 0 ? 'text-danger' : 'text-success',
                      children: ["₹", Number(row.balance).toFixed(2)]
                    })
                  })]
                }, idx))
              }), activeLedgerDetail.statement?.length > 0 && /*#__PURE__*/_jsx("tfoot", {
                className: "table-light fw-bold",
                children: /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsx("td", {
                    colSpan: "4",
                    className: "text-end",
                    children: "Total Summary:"
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end text-success",
                    children: ["₹", Number(activeLedgerDetail.summary.total_paid || 0).toFixed(2)]
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end text-dark",
                    children: ["₹", Number(activeLedgerDetail.summary.total_purchases || 0).toFixed(2)]
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end text-danger fs-6",
                    children: ["₹", Number(activeLedgerDetail.summary.outstanding_balance || 0).toFixed(2)]
                  })]
                })
              })]
            })
          })]
        })]
      })]
    }), subTab === 'short_items' && /*#__PURE__*/_jsxs("div", {
      className: "counter-card",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3",
        children: [/*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("h5", {
            className: "fw-bold mb-0 text-dark",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-exclamation-triangle text-warning me-1"
            }), " Short Items & Deficiency Reorder Book"]
          }), /*#__PURE__*/_jsx("small", {
            className: "text-muted",
            children: "Real-time inventory shortage detection. Select items to generate consolidated Purchase Orders in 1-click."
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex gap-2",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-primary btn-sm px-3 shadow-sm fw-bold",
            disabled: selectedShortItemIds.length === 0,
            onClick: openCreatePoFromShortItems,
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-cart-plus me-1"
            }), " Create Purchase Order (", selectedShortItemIds.length, " Selected)"]
          }), /*#__PURE__*/_jsx("button", {
            className: "btn btn-outline-primary btn-sm",
            onClick: fetchShortItems,
            title: "Refresh Short List",
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-clockwise"
            })
          })]
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 bg-light p-2.5 rounded-3 border",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "btn-group btn-group-sm",
          children: [/*#__PURE__*/_jsxs("button", {
            className: `btn ${shortFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`,
            onClick: () => setShortFilter('all'),
            children: ["All Shortages (", shortItems.length, ")"]
          }), /*#__PURE__*/_jsxs("button", {
            className: `btn ${shortFilter === 'out_of_stock' ? 'btn-danger' : 'btn-outline-secondary'}`,
            onClick: () => setShortFilter('out_of_stock'),
            children: ["Out of Stock (", shortItems.filter(s => s.status === 'OUT_OF_STOCK').length, ")"]
          }), /*#__PURE__*/_jsxs("button", {
            className: `btn ${shortFilter === 'low_stock' ? 'btn-warning text-dark' : 'btn-outline-secondary'}`,
            onClick: () => setShortFilter('low_stock'),
            children: ["Low Stock (", shortItems.filter(s => s.status === 'LOW_STOCK').length, ")"]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "input-group input-group-sm",
          style: {
            width: 280
          },
          children: [/*#__PURE__*/_jsx("span", {
            className: "input-group-text bg-white border-end-0",
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-search text-muted"
            })
          }), /*#__PURE__*/_jsx("input", {
            type: "text",
            className: "form-control border-start-0",
            placeholder: "Filter short medicine...",
            value: shortSearch,
            onChange: e => setShortSearch(e.target.value)
          }), shortSearch && /*#__PURE__*/_jsx("button", {
            className: "btn btn-outline-secondary",
            type: "button",
            onClick: () => setShortSearch(''),
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-x"
            })
          })]
        })]
      }), (() => {
        const filtered = shortItems.filter(it => {
          if (shortFilter === 'out_of_stock' && it.status !== 'OUT_OF_STOCK') return false;
          if (shortFilter === 'low_stock' && it.status !== 'LOW_STOCK') return false;
          if (shortSearch.trim()) {
            const q = shortSearch.toLowerCase();
            return it.item_name && it.item_name.toLowerCase().includes(q) || it.category && it.category.toLowerCase().includes(q) || it.last_supplier_name && it.last_supplier_name.toLowerCase().includes(q);
          }
          return true;
        });
        const allSelected = filtered.length > 0 && filtered.every(it => selectedShortItemIds.includes(it.item_id));
        return /*#__PURE__*/_jsx("div", {
          className: "table-responsive shadow-sm rounded border",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-hover align-middle mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              className: "table-light",
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  style: {
                    width: 40
                  },
                  className: "text-center",
                  children: /*#__PURE__*/_jsx("input", {
                    type: "checkbox",
                    className: "form-check-input",
                    checked: allSelected,
                    onChange: e => {
                      if (e.target.checked) {
                        setSelectedShortItemIds(filtered.map(it => it.item_id));
                      } else {
                        setSelectedShortItemIds([]);
                      }
                    }
                  })
                }), /*#__PURE__*/_jsx("th", {
                  children: "Medicine / Formulation"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Stock Status"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  children: "Current Stock"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-center",
                  children: "Min Reorder"
                }), /*#__PURE__*/_jsx("th", {
                  style: {
                    width: 110
                  },
                  children: "Suggested Order"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "Estimated PTR"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Last Supplier"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end pe-3",
                  children: "Action"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: isLoadingShortItems ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsxs("td", {
                  colSpan: "9",
                  className: "text-center py-4",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm me-2"
                  }), "Checking stock levels..."]
                })
              }) : filtered.length === 0 ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsxs("td", {
                  colSpan: "9",
                  className: "text-center py-4 text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle text-success fs-3 d-block mb-1"
                  }), "No medicines currently below reorder levels. Inventory is well-stocked!"]
                })
              }) : filtered.map(it => {
                const isSelected = selectedShortItemIds.includes(it.item_id);
                return /*#__PURE__*/_jsxs("tr", {
                  className: isSelected ? 'table-primary-subtle' : '',
                  children: [/*#__PURE__*/_jsx("td", {
                    className: "text-center",
                    children: /*#__PURE__*/_jsx("input", {
                      type: "checkbox",
                      className: "form-check-input",
                      checked: isSelected,
                      onChange: e => {
                        if (e.target.checked) {
                          setSelectedShortItemIds([...selectedShortItemIds, it.item_id]);
                        } else {
                          setSelectedShortItemIds(selectedShortItemIds.filter(id => id !== it.item_id));
                        }
                      }
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsx("div", {
                      className: "fw-bold text-dark fs-6",
                      children: it.item_name
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "small text-muted d-flex gap-2 align-items-center",
                      children: [/*#__PURE__*/_jsx("span", {
                        className: "badge bg-light text-dark border",
                        children: it.category || 'Tablet'
                      }), /*#__PURE__*/_jsx("span", {
                        children: it.unit_pack || it.units_per_pack + ' Units/Pack'
                      }), it.drug_schedule && /*#__PURE__*/_jsx("span", {
                        className: "badge bg-danger-subtle text-danger border",
                        children: it.drug_schedule
                      })]
                    })]
                  }), /*#__PURE__*/_jsx("td", {
                    children: it.status === 'OUT_OF_STOCK' ? /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-x-circle me-1"
                      }), "Out of Stock"]
                    }) : /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-exclamation-triangle me-1"
                      }), "Low Stock"]
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-center",
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "fw-bold fs-6 text-danger",
                      children: it.current_qty
                    }), /*#__PURE__*/_jsxs("small", {
                      className: "text-muted d-block",
                      style: {
                        fontSize: '11px'
                      },
                      children: ["(", it.current_packs, " packs)"]
                    })]
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-center text-muted fw-semibold",
                    children: [it.min_reorder_level, " units"]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "input-group input-group-sm",
                      children: [/*#__PURE__*/_jsx("input", {
                        type: "number",
                        className: "form-control form-control-sm fw-bold text-center",
                        min: "1",
                        value: it.suggested_packs,
                        onChange: e => {
                          const val = Math.max(1, parseInt(e.target.value) || 1);
                          setShortItems(shortItems.map(si => si.item_id === it.item_id ? {
                            ...si,
                            suggested_packs: val
                          } : si));
                        }
                      }), /*#__PURE__*/_jsx("span", {
                        className: "input-group-text py-0 px-1 text-muted",
                        style: {
                          fontSize: '11px'
                        },
                        children: "Pks"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end fw-semibold",
                    children: it.last_ptr > 0 ? '₹' + Number(it.last_ptr).toFixed(2) : /*#__PURE__*/_jsx("span", {
                      className: "text-muted",
                      children: "-"
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [/*#__PURE__*/_jsx("div", {
                      className: "fw-semibold text-truncate",
                      style: {
                        maxWidth: 180
                      },
                      title: it.last_supplier_name,
                      children: it.last_supplier_name
                    }), it.last_purchase_date && /*#__PURE__*/_jsxs("small", {
                      className: "text-muted",
                      style: {
                        fontSize: '11px'
                      },
                      children: ["Last: ", it.last_purchase_date]
                    })]
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end pe-3",
                    children: /*#__PURE__*/_jsxs("button", {
                      className: "btn btn-sm btn-outline-primary py-1 px-2 text-nowrap",
                      onClick: () => {
                        setSelectedShortItemIds([it.item_id]);
                        openCreatePoFromShortItems();
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-cart-plus me-1"
                      }), " Order"]
                    })
                  })]
                }, it.item_id);
              })
            })]
          })
        });
      })()]
    }), subTab === 'purchase_orders' && /*#__PURE__*/_jsxs("div", {
      className: "counter-card",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3",
        children: [/*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("h5", {
            className: "fw-bold mb-0 text-dark",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-file-earmark-text text-primary me-1"
            }), " Hospital Purchase Orders Directory"]
          }), /*#__PURE__*/_jsx("small", {
            className: "text-muted",
            children: "Track order status, print official supplier order formats, and load directly into inward bills."
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "d-flex gap-2",
          children: [/*#__PURE__*/_jsxs("button", {
            className: "btn btn-primary btn-sm px-3 shadow-sm fw-semibold",
            onClick: () => {
              setPoCreateForm({
                supplier_id: suppliers[0]?.supplier_id ? String(suppliers[0].supplier_id) : '',
                expected_delivery_date: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                remarks: '',
                items: [{
                  item_id: null,
                  item_name: '',
                  generic_name: '',
                  category: 'Tablet',
                  order_packs: 5,
                  units_per_pack: 10,
                  estimated_ptr: 0,
                  gst_rate: 12
                }]
              });
              setShowPoCreateModal(true);
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-plus-circle me-1"
            }), " New Purchase Order"]
          }), /*#__PURE__*/_jsx("button", {
            className: "btn btn-outline-primary btn-sm",
            onClick: fetchPurchaseOrders,
            title: "Refresh PO List",
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-clockwise"
            })
          })]
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "btn-group btn-group-sm mb-3",
        children: [/*#__PURE__*/_jsx("button", {
          className: `btn ${poStatusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`,
          onClick: () => {
            setPoStatusFilter('all');
            fetchPurchaseOrders();
          },
          children: "All Orders"
        }), /*#__PURE__*/_jsx("button", {
          className: `btn ${poStatusFilter === 'ordered' ? 'btn-primary' : 'btn-outline-secondary'}`,
          onClick: () => {
            setPoStatusFilter('ordered');
            fetchPurchaseOrders();
          },
          children: "Ordered / In-Transit"
        }), /*#__PURE__*/_jsx("button", {
          className: `btn ${poStatusFilter === 'received' ? 'btn-success' : 'btn-outline-secondary'}`,
          onClick: () => {
            setPoStatusFilter('received');
            fetchPurchaseOrders();
          },
          children: "Received / Inwarded"
        }), /*#__PURE__*/_jsx("button", {
          className: `btn ${poStatusFilter === 'cancelled' ? 'btn-danger' : 'btn-outline-secondary'}`,
          onClick: () => {
            setPoStatusFilter('cancelled');
            fetchPurchaseOrders();
          },
          children: "Cancelled"
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "table-responsive shadow-sm rounded border",
        children: /*#__PURE__*/_jsxs("table", {
          className: "table table-hover align-middle mb-0",
          children: [/*#__PURE__*/_jsx("thead", {
            className: "table-light",
            children: /*#__PURE__*/_jsxs("tr", {
              children: [/*#__PURE__*/_jsx("th", {
                children: "PO Number"
              }), /*#__PURE__*/_jsx("th", {
                children: "PO Date"
              }), /*#__PURE__*/_jsx("th", {
                children: "Expected Delivery"
              }), /*#__PURE__*/_jsx("th", {
                children: "Supplier / Distributor"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-center",
                children: "Items"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-end",
                children: "Est. Total Amount"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-center",
                children: "Status"
              }), /*#__PURE__*/_jsx("th", {
                className: "text-end pe-3",
                children: "Actions"
              })]
            })
          }), /*#__PURE__*/_jsx("tbody", {
            children: isLoadingPOs ? /*#__PURE__*/_jsx("tr", {
              children: /*#__PURE__*/_jsxs("td", {
                colSpan: "8",
                className: "text-center py-4",
                children: [/*#__PURE__*/_jsx("span", {
                  className: "spinner-border spinner-border-sm me-2"
                }), "Loading purchase orders..."]
              })
            }) : purchaseOrders.length === 0 ? /*#__PURE__*/_jsx("tr", {
              children: /*#__PURE__*/_jsx("td", {
                colSpan: "8",
                className: "text-center py-4 text-muted",
                children: "No purchase orders found. Click \"New Purchase Order\" or generate from Short Items."
              })
            }) : purchaseOrders.map(po => /*#__PURE__*/_jsxs("tr", {
              children: [/*#__PURE__*/_jsx("td", {
                className: "fw-bold text-primary",
                children: po.po_number
              }), /*#__PURE__*/_jsx("td", {
                children: po.po_date
              }), /*#__PURE__*/_jsx("td", {
                children: po.expected_delivery_date || /*#__PURE__*/_jsx("span", {
                  className: "text-muted",
                  children: "-"
                })
              }), /*#__PURE__*/_jsxs("td", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-semibold text-dark",
                  children: po.supplier_name
                }), po.phone && /*#__PURE__*/_jsxs("small", {
                  className: "text-muted",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-telephone me-1"
                  }), po.phone]
                })]
              }), /*#__PURE__*/_jsx("td", {
                className: "text-center fw-bold",
                children: po.total_items
              }), /*#__PURE__*/_jsxs("td", {
                className: "text-end fw-bold",
                children: ["₹", Number(po.total_estimated_amount || 0).toLocaleString('en-IN', {
                  minimumFractionDigits: 2
                })]
              }), /*#__PURE__*/_jsxs("td", {
                className: "text-center",
                children: [po.status === 'ordered' && /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-clock me-1"
                  }), "Ordered"]
                }), po.status === 'received' && /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-success-subtle text-success border border-success-subtle px-2 py-1",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check2-circle me-1"
                  }), "Received"]
                }), po.status === 'cancelled' && /*#__PURE__*/_jsx("span", {
                  className: "badge bg-secondary-subtle text-secondary border px-2 py-1",
                  children: "Cancelled"
                })]
              }), /*#__PURE__*/_jsx("td", {
                className: "text-end pe-3",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "btn-group btn-group-sm",
                  children: [/*#__PURE__*/_jsxs("button", {
                    className: "btn btn-outline-primary",
                    onClick: () => handleViewPo(po.po_id),
                    title: "View & Print PO",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-printer me-1"
                    }), " Print"]
                  }), po.status === 'ordered' && /*#__PURE__*/_jsxs(_Fragment, {
                    children: [/*#__PURE__*/_jsxs("button", {
                      className: "btn btn-success",
                      onClick: () => loadPoIntoInwardBill(po.po_id),
                      title: "Load this PO into Inward Purchase Bill",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-box-arrow-in-down me-1"
                      }), " Inward"]
                    }), /*#__PURE__*/_jsx("button", {
                      className: "btn btn-outline-danger",
                      onClick: () => handleCancelPo(po.po_id),
                      title: "Cancel this PO",
                      children: /*#__PURE__*/_jsx("i", {
                        className: "bi bi-x"
                      })
                    })]
                  })]
                })
              })]
            }, po.po_id))
          })]
        })
      })]
    }), showSupplierModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "bg-primary text-white rounded p-1.5 d-flex align-items-center justify-content-center",
                style: {
                  width: 32,
                  height: 32
                },
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-truck fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: supplierForm.supplier_id ? 'Edit Supplier / Distributor Details' : 'Add New Drug Distributor & Supplier'
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-light opacity-75",
                  children: "Statutory Drug Licenses, GSTIN, contact & credit terms"
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowSupplierModal(false)
            })]
          }), /*#__PURE__*/_jsxs("form", {
            onSubmit: handleSaveSupplier,
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3 bg-light",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card border-0 shadow-sm p-3 mb-3 bg-white",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "fw-bold text-primary mb-2",
                  style: {
                    fontSize: '13px'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-building me-1"
                  }), " Basic Distributor Information"]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-8",
                    children: [/*#__PURE__*/_jsxs("label", {
                      className: "form-label small fw-bold mb-1",
                      children: ["Distributor / Agency Name ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger",
                        children: "*"
                      })]
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. Apex Healthcare Distributors Pvt Ltd",
                      value: supplierForm.supplier_name,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        supplier_name: e.target.value
                      }),
                      required: true,
                      autoFocus: true
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Contact Person"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. Ramesh Gupta",
                      value: supplierForm.contact_person,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        contact_person: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Phone / Mobile No."
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "+91 9811002233",
                      value: supplierForm.phone,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        phone: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Email Address"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "email",
                      className: "form-control form-control-sm",
                      placeholder: "sales@distributor.com",
                      value: supplierForm.email,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        email: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Credit Terms"
                    }), /*#__PURE__*/_jsxs("div", {
                      className: "input-group input-group-sm",
                      children: [/*#__PURE__*/_jsx("input", {
                        type: "number",
                        className: "form-control form-control-sm",
                        min: "0",
                        placeholder: "30",
                        value: supplierForm.credit_days,
                        onChange: e => setSupplierForm({
                          ...supplierForm,
                          credit_days: e.target.value
                        })
                      }), /*#__PURE__*/_jsx("span", {
                        className: "input-group-text bg-light",
                        children: "Days"
                      })]
                    })]
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "card border-0 shadow-sm p-3 mb-3 bg-white",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "fw-bold text-primary mb-2",
                  style: {
                    fontSize: '13px'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-shield-check me-1"
                  }), " Indian Statutory Drug Licenses & GSTIN"]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Drug License Form 20-B (Allopathic)"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. DL-20B-DEL-98441",
                      value: supplierForm.dl_no_20b,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        dl_no_20b: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Drug License Form 21-B (Schedule C & C1)"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. DL-21B-DEL-98442",
                      value: supplierForm.dl_no_21b,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        dl_no_21b: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "GSTIN (15-Digit)"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm text-uppercase",
                      maxLength: "15",
                      placeholder: "07AACCA1234F1Z9",
                      value: supplierForm.gstin,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        gstin: e.target.value.toUpperCase()
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "PAN No. (10-Digit)"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm text-uppercase",
                      maxLength: "10",
                      placeholder: "AACCA1234F",
                      value: supplierForm.pan_no,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        pan_no: e.target.value.toUpperCase()
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "State Code (GST)"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "07 (Delhi)",
                      value: supplierForm.state_code,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        state_code: e.target.value
                      })
                    })]
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "card border-0 shadow-sm p-3 bg-white",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "fw-bold text-primary mb-2",
                  style: {
                    fontSize: '13px'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-geo-alt me-1"
                  }), " Address & Banking Details"]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Full Postal Address"
                    }), /*#__PURE__*/_jsx("textarea", {
                      className: "form-control form-control-sm",
                      rows: "2",
                      placeholder: "Shop / Office No, Market, City, Pincode",
                      value: supplierForm.address,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        address: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Bank Account & Settlement Details"
                    }), /*#__PURE__*/_jsx("textarea", {
                      className: "form-control form-control-sm",
                      rows: "2",
                      placeholder: "Bank Name, A/c Number, IFSC Code, UPI ID",
                      value: supplierForm.bank_details,
                      onChange: e => setSupplierForm({
                        ...supplierForm,
                        bank_details: e.target.value
                      })
                    })]
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer py-2 px-3 bg-white d-flex justify-content-between",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-outline-secondary btn-sm",
                onClick: () => setShowSupplierModal(false),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "submit",
                className: "btn btn-primary btn-sm px-4",
                disabled: isSavingSupplier,
                children: isSavingSupplier ? /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm me-1",
                    role: "status"
                  }), "Saving..."]
                }) : /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check2-circle me-1"
                  }), supplierForm.supplier_id ? 'Update Supplier' : 'Save Supplier']
                })
              })]
            })]
          })]
        })
      })
    }), showMasterModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "bg-success text-white rounded p-1.5 d-flex align-items-center justify-content-center",
                style: {
                  width: 32,
                  height: 32
                },
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-capsule fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: "Add New Medicine to Pharmacy Master"
                }), /*#__PURE__*/_jsxs("small", {
                  className: "text-light opacity-75",
                  children: ["Saved to shared master ", /*#__PURE__*/_jsx("code", {
                    children: "med_product_master"
                  }), " (shared between MedicalStore & HMSPharmacy)"]
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowMasterModal(false)
            })]
          }), /*#__PURE__*/_jsxs("form", {
            onSubmit: handleSaveMasterMedicine,
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3 bg-light",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card border-0 shadow-sm p-3 mb-3 bg-white",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "fw-bold text-primary mb-2",
                  style: {
                    fontSize: '13px'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-prescription2 me-1"
                  }), " Medicine Name & Formulation"]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-7",
                    children: [/*#__PURE__*/_jsxs("label", {
                      className: "form-label small fw-bold mb-1",
                      children: ["Brand / Medicine Name ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger",
                        children: "*"
                      })]
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm fw-semibold",
                      placeholder: "e.g. Augmentin 625 Duo Tablet",
                      value: masterForm.item_name,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        item_name: e.target.value
                      }),
                      required: true,
                      autoFocus: true
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-5",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Formulation / Category"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      list: "ms-formulation-list",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. Tablet, Capsule, Syrup...",
                      value: masterForm.formulation,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        formulation: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("datalist", {
                      id: "ms-formulation-list",
                      children: (supportData.formulations || []).map((f, idx) => /*#__PURE__*/_jsx("option", {
                        value: f
                      }, idx))
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-12",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Generic Molecule Composition"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. Amoxycillin (500mg) + Clavulanic Acid (125mg)",
                      value: masterForm.generic_name,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        generic_name: e.target.value
                      })
                    })]
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "card border-0 shadow-sm p-3 bg-white",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "fw-bold text-primary mb-2",
                  style: {
                    fontSize: '13px'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-box-seam me-1"
                  }), " Packing, Tax & Regulatory Specifications"]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsxs("label", {
                      className: "form-label small fw-bold mb-1",
                      children: ["Units per Pack (Strip/Bottle) ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger",
                        children: "*"
                      })]
                    }), /*#__PURE__*/_jsx("input", {
                      type: "number",
                      min: "1",
                      className: "form-control form-control-sm fw-bold",
                      placeholder: "10",
                      value: masterForm.units_per_pack,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        units_per_pack: e.target.value,
                        packing: e.target.value
                      }),
                      required: true
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "GST Rate (%)"
                    }), /*#__PURE__*/_jsxs("select", {
                      className: "form-select form-select-sm fw-bold",
                      value: masterForm.gst_rate,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        gst_rate: Number(e.target.value)
                      }),
                      children: [/*#__PURE__*/_jsx("option", {
                        value: "0",
                        children: "0% (Nil / Exempt)"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "5",
                        children: "5% (Common / Basic Services)"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "18",
                        children: "18% (Standard - Most Products)"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "40",
                        children: "40% (Demerit / Sin Tax)"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "12",
                        children: "12% (Legacy Medicine Slab)"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "28",
                        children: "28% (Legacy High Slab)"
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "HSN Code"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "3004",
                      value: masterForm.hsn_code,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        hsn_code: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Drug Schedule"
                    }), /*#__PURE__*/_jsx("select", {
                      className: "form-select form-select-sm",
                      value: masterForm.drug_schedule,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        drug_schedule: e.target.value
                      }),
                      children: (supportData.schedules || ['OTC', 'Schedule H', 'Schedule H1', 'Schedule X', 'Narcotic']).map((sc, idx) => /*#__PURE__*/_jsx("option", {
                        value: sc,
                        children: sc
                      }, idx))
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-8",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Manufacturer / Pharma Company"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      list: "ms-company-list",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. Cipla Ltd, Sun Pharma, Alkem...",
                      value: masterForm.company_name,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        company_name: e.target.value
                      })
                    }), /*#__PURE__*/_jsx("datalist", {
                      id: "ms-company-list",
                      children: (supportData.companies || []).map((c, idx) => /*#__PURE__*/_jsx("option", {
                        value: c
                      }, idx))
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Min Reorder Level"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "number",
                      min: "1",
                      className: "form-control form-control-sm",
                      placeholder: "10",
                      value: masterForm.min_reorder_level,
                      onChange: e => setMasterForm({
                        ...masterForm,
                        min_reorder_level: e.target.value
                      })
                    })]
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer py-2 px-3 bg-white d-flex justify-content-between",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-outline-secondary btn-sm",
                onClick: () => setShowMasterModal(false),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "submit",
                className: "btn btn-success btn-sm fw-bold px-4",
                disabled: isSavingMaster,
                children: isSavingMaster ? /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm me-1",
                    role: "status"
                  }), "Saving to Master..."]
                }) : /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check-circle me-1"
                  }), "Save to Master & Select into Bill"]
                })
              })]
            })]
          })]
        })
      })
    }), showPaymentModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-md modal-dialog-centered",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "bg-success text-white rounded p-1.5 d-flex align-items-center justify-content-center",
                style: {
                  width: 32,
                  height: 32
                },
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-cash-coin fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: "Record Supplier Payment Voucher"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-light opacity-75",
                  children: "Outflow accounting against invoice or on-account"
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowPaymentModal(false)
            })]
          }), /*#__PURE__*/_jsxs("form", {
            onSubmit: handleRecordPayment,
            children: [/*#__PURE__*/_jsx("div", {
              className: "modal-body p-3 bg-light",
              children: /*#__PURE__*/_jsx("div", {
                className: "card border-0 shadow-sm p-3 mb-3 bg-white",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-12",
                    children: [/*#__PURE__*/_jsxs("label", {
                      className: "form-label small fw-bold mb-1",
                      children: ["Supplier / Distributor ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger",
                        children: "*"
                      })]
                    }), /*#__PURE__*/_jsxs("select", {
                      className: "form-select form-select-sm fw-semibold",
                      value: paymentForm.supplier_id,
                      onChange: e => setPaymentForm({
                        ...paymentForm,
                        supplier_id: e.target.value
                      }),
                      required: true,
                      children: [/*#__PURE__*/_jsx("option", {
                        value: "",
                        children: "-- Choose Supplier --"
                      }), suppliers.map(s => /*#__PURE__*/_jsxs("option", {
                        value: s.supplier_id,
                        children: [s.supplier_name, " (GST: ", s.gstin || 'N/A', ")"]
                      }, s.supplier_id))]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsxs("label", {
                      className: "form-label small fw-bold mb-1",
                      children: ["Payment Amount (₹) ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger",
                        children: "*"
                      })]
                    }), /*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "0.01",
                      min: "0.01",
                      className: "form-control form-control-sm fw-bold text-success fs-6",
                      placeholder: "0.00",
                      value: paymentForm.amount,
                      onChange: e => setPaymentForm({
                        ...paymentForm,
                        amount: e.target.value
                      }),
                      required: true,
                      autoFocus: true
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Payment Mode"
                    }), /*#__PURE__*/_jsxs("select", {
                      className: "form-select form-select-sm",
                      value: paymentForm.payment_mode,
                      onChange: e => setPaymentForm({
                        ...paymentForm,
                        payment_mode: e.target.value
                      }),
                      children: [/*#__PURE__*/_jsx("option", {
                        value: "Bank",
                        children: "Bank Transfer (NEFT/RTGS)"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "UPI",
                        children: "UPI / QR Code"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "Cash",
                        children: "Cash in Hand"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "Cheque",
                        children: "Bank Cheque"
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Payment Date"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "date",
                      className: "form-control form-control-sm",
                      value: paymentForm.payment_date,
                      onChange: e => setPaymentForm({
                        ...paymentForm,
                        payment_date: e.target.value
                      }),
                      required: true
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "UTR / Cheque / Ref No"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. UTR-9988112",
                      value: paymentForm.reference_no,
                      onChange: e => setPaymentForm({
                        ...paymentForm,
                        reference_no: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-12",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Bank Name / Account Details"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. HDFC Bank - Main Current A/c",
                      value: paymentForm.bank_name,
                      onChange: e => setPaymentForm({
                        ...paymentForm,
                        bank_name: e.target.value
                      })
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-12",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Remarks / Settlement Note"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. Cleared bill #APX-9941",
                      value: paymentForm.remarks,
                      onChange: e => setPaymentForm({
                        ...paymentForm,
                        remarks: e.target.value
                      })
                    })]
                  })]
                })
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer py-2 px-3 bg-white d-flex justify-content-between",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-outline-secondary btn-sm",
                onClick: () => setShowPaymentModal(false),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "submit",
                className: "btn btn-success btn-sm fw-bold px-4 shadow-sm",
                disabled: isRecordingPayment,
                children: isRecordingPayment ? /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm me-1"
                  }), "Recording Voucher..."]
                }) : /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check2-circle me-1"
                  }), "Record Payment Voucher"]
                })
              })]
            })]
          })]
        })
      })
    }), showPoCreateModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "bg-primary text-white rounded p-1.5 d-flex align-items-center justify-content-center",
                style: {
                  width: 32,
                  height: 32
                },
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-cart-check fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: "Generate New Purchase Order (PO)"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-light opacity-75",
                  children: "Order requisition for pharmaceutical distributor"
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowPoCreateModal(false)
            })]
          }), /*#__PURE__*/_jsxs("form", {
            onSubmit: handleSavePurchaseOrder,
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3 bg-light",
              children: [/*#__PURE__*/_jsx("div", {
                className: "card border-0 shadow-sm p-3 mb-3 bg-white",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-5",
                    children: [/*#__PURE__*/_jsxs("label", {
                      className: "form-label small fw-bold mb-1",
                      children: ["Target Supplier / Agency ", /*#__PURE__*/_jsx("span", {
                        className: "text-danger",
                        children: "*"
                      })]
                    }), /*#__PURE__*/_jsxs("select", {
                      className: "form-select form-select-sm fw-semibold",
                      value: poCreateForm.supplier_id,
                      onChange: e => setPoCreateForm({
                        ...poCreateForm,
                        supplier_id: e.target.value
                      }),
                      required: true,
                      children: [/*#__PURE__*/_jsx("option", {
                        value: "",
                        children: "-- Choose Supplier --"
                      }), suppliers.map(s => /*#__PURE__*/_jsxs("option", {
                        value: s.supplier_id,
                        children: [s.supplier_name, " (GST: ", s.gstin || 'N/A', ")"]
                      }, s.supplier_id))]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Expected Delivery Date"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "date",
                      className: "form-control form-control-sm",
                      value: poCreateForm.expected_delivery_date,
                      onChange: e => setPoCreateForm({
                        ...poCreateForm,
                        expected_delivery_date: e.target.value
                      }),
                      required: true
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-4",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Remarks / Order Instructions"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      placeholder: "e.g. Urgent stock replenishment",
                      value: poCreateForm.remarks,
                      onChange: e => setPoCreateForm({
                        ...poCreateForm,
                        remarks: e.target.value
                      })
                    })]
                  })]
                })
              }), /*#__PURE__*/_jsxs("div", {
                className: "card border-0 shadow-sm p-3 bg-white",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between align-items-center mb-2",
                  children: [/*#__PURE__*/_jsxs("h6", {
                    className: "fw-bold text-primary mb-0",
                    style: {
                      fontSize: '13px'
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-box-seam me-1"
                    }), " Ordered Items (", poCreateForm.items.length, ")"]
                  }), /*#__PURE__*/_jsxs("button", {
                    type: "button",
                    className: "btn btn-outline-primary btn-sm py-0.5 px-2",
                    onClick: () => {
                      setPoCreateForm({
                        ...poCreateForm,
                        items: [...poCreateForm.items, {
                          item_id: null,
                          item_name: '',
                          generic_name: '',
                          category: 'Tablet',
                          order_packs: 1,
                          units_per_pack: 10,
                          estimated_ptr: 0,
                          gst_rate: 12
                        }]
                      });
                    },
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-plus me-1"
                    }), " Add Another Medicine"]
                  })]
                }), /*#__PURE__*/_jsx("div", {
                  className: "table-responsive",
                  children: /*#__PURE__*/_jsxs("table", {
                    className: "table table-bordered align-middle mb-0",
                    style: {
                      fontSize: '12.5px'
                    },
                    children: [/*#__PURE__*/_jsx("thead", {
                      className: "table-light",
                      children: /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("th", {
                          style: {
                            minWidth: 240
                          },
                          children: "Medicine Name"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 100
                          },
                          children: "Category"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 90
                          },
                          children: "Packs"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 90
                          },
                          children: "Units/Pack"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 100
                          },
                          children: "Est. PTR (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 85
                          },
                          children: "GST%"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 120
                          },
                          className: "text-end",
                          children: "Est. Total (₹)"
                        }), /*#__PURE__*/_jsx("th", {
                          style: {
                            width: 40
                          }
                        })]
                      })
                    }), /*#__PURE__*/_jsx("tbody", {
                      children: poCreateForm.items.map((it, idx) => {
                        const lineTotal = Number(it.order_packs || 0) * Number(it.estimated_ptr || 0) * (1 + Number(it.gst_rate || 0) / 100);
                        return /*#__PURE__*/_jsxs("tr", {
                          children: [/*#__PURE__*/_jsx("td", {
                            children: /*#__PURE__*/_jsx("input", {
                              type: "text",
                              className: "form-control form-control-sm fw-semibold",
                              placeholder: "Medicine name...",
                              value: it.item_name,
                              onChange: e => {
                                const items = [...poCreateForm.items];
                                items[idx].item_name = e.target.value;
                                setPoCreateForm({
                                  ...poCreateForm,
                                  items
                                });
                              },
                              required: true
                            })
                          }), /*#__PURE__*/_jsx("td", {
                            children: /*#__PURE__*/_jsx("input", {
                              type: "text",
                              className: "form-control form-control-sm",
                              value: it.category,
                              onChange: e => {
                                const items = [...poCreateForm.items];
                                items[idx].category = e.target.value;
                                setPoCreateForm({
                                  ...poCreateForm,
                                  items
                                });
                              }
                            })
                          }), /*#__PURE__*/_jsx("td", {
                            children: /*#__PURE__*/_jsx("input", {
                              type: "number",
                              min: "1",
                              className: "form-control form-control-sm text-center fw-bold",
                              value: it.order_packs,
                              onChange: e => {
                                const items = [...poCreateForm.items];
                                items[idx].order_packs = Math.max(1, parseInt(e.target.value) || 1);
                                setPoCreateForm({
                                  ...poCreateForm,
                                  items
                                });
                              },
                              required: true
                            })
                          }), /*#__PURE__*/_jsx("td", {
                            children: /*#__PURE__*/_jsx("input", {
                              type: "number",
                              min: "1",
                              className: "form-control form-control-sm text-center",
                              value: it.units_per_pack,
                              onChange: e => {
                                const items = [...poCreateForm.items];
                                items[idx].units_per_pack = Math.max(1, parseInt(e.target.value) || 1);
                                setPoCreateForm({
                                  ...poCreateForm,
                                  items
                                });
                              }
                            })
                          }), /*#__PURE__*/_jsx("td", {
                            children: /*#__PURE__*/_jsx("input", {
                              type: "number",
                              step: "0.01",
                              min: "0",
                              className: "form-control form-control-sm text-end",
                              value: it.estimated_ptr,
                              onChange: e => {
                                const items = [...poCreateForm.items];
                                items[idx].estimated_ptr = parseFloat(e.target.value) || 0;
                                setPoCreateForm({
                                  ...poCreateForm,
                                  items
                                });
                              }
                            })
                          }), /*#__PURE__*/_jsx("td", {
                            children: /*#__PURE__*/_jsxs("select", {
                              className: "form-select form-select-sm",
                              value: it.gst_rate,
                              onChange: e => {
                                const items = [...poCreateForm.items];
                                items[idx].gst_rate = parseFloat(e.target.value) || 0;
                                setPoCreateForm({
                                  ...poCreateForm,
                                  items
                                });
                              },
                              children: [/*#__PURE__*/_jsx("option", {
                                value: "0",
                                children: "0% (Exempt)"
                              }), /*#__PURE__*/_jsx("option", {
                                value: "5",
                                children: "5%"
                              }), /*#__PURE__*/_jsx("option", {
                                value: "18",
                                children: "18%"
                              }), /*#__PURE__*/_jsx("option", {
                                value: "40",
                                children: "40%"
                              }), /*#__PURE__*/_jsx("option", {
                                value: "12",
                                children: "12% (Legacy)"
                              }), /*#__PURE__*/_jsx("option", {
                                value: "28",
                                children: "28% (Legacy)"
                              })]
                            })
                          }), /*#__PURE__*/_jsxs("td", {
                            className: "text-end fw-bold",
                            children: ["₹", lineTotal.toFixed(2)]
                          }), /*#__PURE__*/_jsx("td", {
                            className: "text-center",
                            children: poCreateForm.items.length > 1 && /*#__PURE__*/_jsx("button", {
                              type: "button",
                              className: "btn btn-sm btn-outline-danger py-0 px-1",
                              onClick: () => {
                                setPoCreateForm({
                                  ...poCreateForm,
                                  items: poCreateForm.items.filter((_, i) => i !== idx)
                                });
                              },
                              children: /*#__PURE__*/_jsx("i", {
                                className: "bi bi-x"
                              })
                            })
                          })]
                        }, idx);
                      })
                    }), /*#__PURE__*/_jsx("tfoot", {
                      className: "table-light fw-bold",
                      children: /*#__PURE__*/_jsxs("tr", {
                        children: [/*#__PURE__*/_jsx("td", {
                          colSpan: "6",
                          className: "text-end",
                          children: "Estimated Total Amount:"
                        }), /*#__PURE__*/_jsxs("td", {
                          className: "text-end text-primary fs-6",
                          children: ["₹", poCreateForm.items.reduce((sum, it) => sum + Number(it.order_packs || 0) * Number(it.estimated_ptr || 0) * (1 + Number(it.gst_rate || 0) / 100), 0).toFixed(2)]
                        }), /*#__PURE__*/_jsx("td", {})]
                      })
                    })]
                  })
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer py-2 px-3 bg-white d-flex justify-content-between",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-outline-secondary btn-sm",
                onClick: () => setShowPoCreateModal(false),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "submit",
                className: "btn btn-primary btn-sm fw-bold px-4 shadow-sm",
                disabled: isSavingPO,
                children: isSavingPO ? /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "spinner-border spinner-border-sm me-1"
                  }), "Generating PO..."]
                }) : /*#__PURE__*/_jsxs(_Fragment, {
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-check2-circle me-1"
                  }), "Generate Purchase Order"]
                })
              })]
            })]
          })]
        })
      })
    }), showPoViewModal && activeViewPo && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "bg-primary text-white rounded p-1.5 d-flex align-items-center justify-content-center",
                style: {
                  width: 32,
                  height: 32
                },
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-printer fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: ["Purchase Order Document: ", activeViewPo.purchase_order.po_number]
                }), /*#__PURE__*/_jsxs("small", {
                  className: "text-light opacity-75",
                  children: ["Status: ", /*#__PURE__*/_jsx("span", {
                    className: "text-uppercase fw-bold text-info",
                    children: activeViewPo.purchase_order.status
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowPoViewModal(false)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-body p-4 bg-white",
            id: "printable-po-document",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-start border-bottom pb-3 mb-3",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h4", {
                  className: "fw-bold text-primary mb-1",
                  children: store.store_name
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-muted small",
                  children: [store.building_name, " | ", store.room_no]
                }), /*#__PURE__*/_jsx("div", {
                  className: "text-muted small",
                  children: "Hospital Pharmacy & Medical Supplies Dept."
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "text-end",
                children: [/*#__PURE__*/_jsx("h5", {
                  className: "fw-bold mb-0 text-dark",
                  children: "PURCHASE ORDER"
                }), /*#__PURE__*/_jsx("div", {
                  className: "badge bg-primary fs-6 mt-1",
                  children: activeViewPo.purchase_order.po_number
                }), /*#__PURE__*/_jsxs("div", {
                  className: "small text-muted mt-1",
                  children: ["Date: ", /*#__PURE__*/_jsx("strong", {
                    children: activeViewPo.purchase_order.po_date
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "small text-muted",
                  children: ["Delivery: ", /*#__PURE__*/_jsx("strong", {
                    children: activeViewPo.purchase_order.expected_delivery_date || 'Prompt'
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "row g-3 mb-3",
              children: [/*#__PURE__*/_jsx("div", {
                className: "col-md-6",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "p-2.5 bg-light rounded border",
                  children: [/*#__PURE__*/_jsx("small", {
                    className: "text-muted text-uppercase fw-bold",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Vendor / Supplier:"
                  }), /*#__PURE__*/_jsx("h6", {
                    className: "fw-bold mb-1 text-dark",
                    children: activeViewPo.purchase_order.supplier_name
                  }), activeViewPo.purchase_order.address && /*#__PURE__*/_jsx("div", {
                    className: "small text-muted",
                    children: activeViewPo.purchase_order.address
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "small text-muted",
                    children: ["GSTIN: ", /*#__PURE__*/_jsx("strong", {
                      children: activeViewPo.purchase_order.gstin || 'N/A'
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "small text-muted",
                    children: ["Phone: ", activeViewPo.purchase_order.phone || 'N/A']
                  })]
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "col-md-6",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "p-2.5 bg-light rounded border",
                  children: [/*#__PURE__*/_jsx("small", {
                    className: "text-muted text-uppercase fw-bold",
                    style: {
                      fontSize: '10px'
                    },
                    children: "Ship To / Delivery Destination:"
                  }), /*#__PURE__*/_jsx("h6", {
                    className: "fw-bold mb-1 text-dark",
                    children: store.store_name
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "small text-muted",
                    children: [store.building_name, ", ", store.floor_no]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "small text-muted",
                    children: ["Remarks: ", /*#__PURE__*/_jsx("em", {
                      children: activeViewPo.purchase_order.remarks || 'Supply per hospital contract terms'
                    })]
                  })]
                })
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "table-responsive mb-3",
              children: /*#__PURE__*/_jsxs("table", {
                className: "table table-bordered table-sm align-middle mb-0",
                style: {
                  fontSize: '12px'
                },
                children: [/*#__PURE__*/_jsx("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("th", {
                      style: {
                        width: 35
                      },
                      children: "#"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Item Name & Description"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Category"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-center",
                      style: {
                        width: 85
                      },
                      children: "Ordered Packs"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-center",
                      style: {
                        width: 85
                      },
                      children: "Units/Pack"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-end",
                      style: {
                        width: 95
                      },
                      children: "Est. PTR"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-center",
                      style: {
                        width: 65
                      },
                      children: "GST%"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-end",
                      style: {
                        width: 110
                      },
                      children: "Est. Amount"
                    })]
                  })
                }), /*#__PURE__*/_jsx("tbody", {
                  children: activeViewPo.items?.map((it, idx) => /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("td", {
                      className: "text-muted text-center",
                      children: idx + 1
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-bold text-dark",
                        children: it.item_name
                      }), it.generic_name && /*#__PURE__*/_jsx("small", {
                        className: "text-muted",
                        children: it.generic_name
                      })]
                    }), /*#__PURE__*/_jsx("td", {
                      children: it.category || 'Tablet'
                    }), /*#__PURE__*/_jsx("td", {
                      className: "text-center fw-bold",
                      children: it.order_packs
                    }), /*#__PURE__*/_jsx("td", {
                      className: "text-center",
                      children: it.units_per_pack
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "text-end",
                      children: ["₹", Number(it.estimated_ptr).toFixed(2)]
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "text-center",
                      children: [it.gst_rate, "%"]
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "text-end fw-bold",
                      children: ["₹", Number(it.estimated_amount).toFixed(2)]
                    })]
                  }, idx))
                }), /*#__PURE__*/_jsx("tfoot", {
                  className: "table-light fw-bold",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("td", {
                      colSpan: "7",
                      className: "text-end",
                      children: "Total Estimated Value:"
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "text-end text-primary fs-6",
                      children: ["₹", Number(activeViewPo.purchase_order.total_estimated_amount).toFixed(2)]
                    })]
                  })
                })]
              })
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-end pt-4 mt-4 border-top",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "text-center",
                style: {
                  width: 180
                },
                children: [/*#__PURE__*/_jsx("div", {
                  className: "border-bottom pb-4 mb-1"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-muted fw-bold",
                  children: "Prepared By / Pharmacist"
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "text-center",
                style: {
                  width: 180
                },
                children: [/*#__PURE__*/_jsx("div", {
                  className: "border-bottom pb-4 mb-1"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-muted fw-bold",
                  children: "Authorized Signatory"
                })]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-footer py-2 px-3 bg-light d-flex justify-content-between",
            children: [/*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-outline-secondary btn-sm",
              onClick: () => setShowPoViewModal(false),
              children: "Close"
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex gap-2",
              children: [activeViewPo.purchase_order.status === 'ordered' && /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-success btn-sm fw-bold px-3 shadow-sm",
                onClick: () => loadPoIntoInwardBill(activeViewPo.purchase_order.po_id),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-box-arrow-in-down me-1"
                }), " Inward into Purchase Bill"]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-primary btn-sm fw-bold px-3 shadow-sm",
                onClick: () => window.print(),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-printer me-1"
                }), " Print PO"]
              })]
            })]
          })]
        })
      })
    }), showPurchaseHistoryModal && /*#__PURE__*/_jsx("div", {
      className: "modal fade show d-block",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.75)'
      },
      tabIndex: "-1",
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable",
        children: /*#__PURE__*/_jsxs("div", {
          className: "modal-content border-0 shadow-lg",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "modal-header bg-dark text-white py-2.5 px-3 d-flex justify-content-between align-items-center",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-2",
              children: [/*#__PURE__*/_jsx("div", {
                className: "bg-primary text-white rounded p-1.5 d-flex align-items-center justify-content-center",
                style: {
                  width: 32,
                  height: 32
                },
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history fs-5"
                })
              }), /*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("h6", {
                  className: "modal-title mb-0 fw-bold",
                  children: "Inward Purchase Bills History & Edit"
                }), /*#__PURE__*/_jsx("small", {
                  className: "text-light opacity-75",
                  children: "Browse past invoices and click Edit to modify lines, rates, or batches"
                })]
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn-close btn-close-white",
              onClick: () => setShowPurchaseHistoryModal(false)
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "modal-body p-3 bg-light",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex justify-content-between align-items-center gap-2 mb-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "input-group input-group-sm",
                style: {
                  maxWidth: 350
                },
                children: [/*#__PURE__*/_jsx("span", {
                  className: "input-group-text bg-white",
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-search text-muted"
                  })
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control",
                  placeholder: "Search by Bill No or Supplier Name...",
                  value: purchaseHistorySearch,
                  onChange: e => setPurchaseHistorySearch(e.target.value)
                }), purchaseHistorySearch && /*#__PURE__*/_jsx("button", {
                  className: "btn btn-outline-secondary",
                  onClick: () => setPurchaseHistorySearch(''),
                  children: /*#__PURE__*/_jsx("i", {
                    className: "bi bi-x"
                  })
                })]
              }), /*#__PURE__*/_jsxs("button", {
                className: "btn btn-outline-primary btn-sm",
                onClick: fetchRecentPurchases,
                title: "Refresh Invoices",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-arrow-clockwise me-1"
                }), " Refresh"]
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "table-responsive bg-white rounded border shadow-sm",
              children: /*#__PURE__*/_jsxs("table", {
                className: "table table-hover align-middle mb-0",
                style: {
                  fontSize: '13px'
                },
                children: [/*#__PURE__*/_jsx("thead", {
                  className: "table-light",
                  children: /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("th", {
                      style: {
                        width: 110
                      },
                      children: "Date"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Bill / Invoice No"
                    }), /*#__PURE__*/_jsx("th", {
                      children: "Supplier / Distributor"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-center",
                      children: "Items"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-end",
                      children: "Net Bill Amount"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-end",
                      children: "Paid Amount"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-center",
                      children: "Payment Status"
                    }), /*#__PURE__*/_jsx("th", {
                      className: "text-end pe-3",
                      children: "Action"
                    })]
                  })
                }), /*#__PURE__*/_jsx("tbody", {
                  children: isLoadingPurchases ? /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsxs("td", {
                      colSpan: "8",
                      className: "text-center py-4",
                      children: [/*#__PURE__*/_jsx("span", {
                        className: "spinner-border spinner-border-sm me-2"
                      }), "Loading invoices..."]
                    })
                  }) : recentPurchases.length === 0 ? /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsx("td", {
                      colSpan: "8",
                      className: "text-center py-4 text-muted",
                      children: "No inward purchase bills recorded yet."
                    })
                  }) : recentPurchases.filter(p => {
                    if (!purchaseHistorySearch) return true;
                    const q = purchaseHistorySearch.toLowerCase();
                    return (p.supplier_invoice_no || '').toLowerCase().includes(q) || (p.supplier_name || '').toLowerCase().includes(q);
                  }).map(p => /*#__PURE__*/_jsxs("tr", {
                    children: [/*#__PURE__*/_jsx("td", {
                      children: p.invoice_date
                    }), /*#__PURE__*/_jsx("td", {
                      className: "fw-bold text-primary",
                      children: p.supplier_invoice_no
                    }), /*#__PURE__*/_jsxs("td", {
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "fw-semibold text-dark",
                        children: p.supplier_name || 'Unknown Supplier'
                      }), p.gstin && /*#__PURE__*/_jsxs("small", {
                        className: "text-muted",
                        children: ["GST: ", p.gstin]
                      })]
                    }), /*#__PURE__*/_jsx("td", {
                      className: "text-center",
                      children: /*#__PURE__*/_jsxs("span", {
                        className: "badge bg-light text-dark border px-2 py-1",
                        children: [p.items_count || 1, " lines"]
                      })
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "text-end fw-bold text-dark",
                      children: ["₹", Number(p.net_amount || 0).toFixed(2)]
                    }), /*#__PURE__*/_jsxs("td", {
                      className: "text-end text-success",
                      children: ["₹", Number(p.paid_amount || 0).toFixed(2)]
                    }), /*#__PURE__*/_jsx("td", {
                      className: "text-center",
                      children: /*#__PURE__*/_jsx("span", {
                        className: `badge ${p.payment_status === 'paid' ? 'bg-success' : p.payment_status === 'partially_paid' ? 'bg-warning text-dark' : 'bg-danger'} px-2 py-1`,
                        children: p.payment_status === 'paid' ? 'Paid' : p.payment_status === 'partially_paid' ? 'Partially Paid' : 'Unpaid'
                      })
                    }), /*#__PURE__*/_jsx("td", {
                      className: "text-end pe-3",
                      children: /*#__PURE__*/_jsxs("button", {
                        className: "btn btn-sm btn-outline-primary py-1 px-2.5 shadow-sm fw-semibold",
                        onClick: () => loadPurchaseForEdit(p.purchase_id),
                        title: "Load this bill into Inward form to edit items and rates",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-pencil-square me-1"
                        }), " Edit Invoice"]
                      })
                    })]
                  }, p.purchase_id))
                })]
              })
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "modal-footer py-2 bg-light",
            children: /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-secondary btn-sm",
              onClick: () => setShowPurchaseHistoryModal(false),
              children: "Close"
            })
          })]
        })
      })
    })]
  });
}

// =========================================================================
// 4. INTER-STORE INDENTS & TRANSFERS
// =========================================================================
function TransferView({
  store,
  stores,
  notify
}) {
  if (!store) return null;
  const [toStoreId, setToStoreId] = useState('');
  const [remarks, setRemarks] = useState('');
  const [transferItems, setTransferItems] = useState([{
    item_name: '',
    qty: 10
  }]);
  const addTransferRow = () => {
    setTransferItems([...transferItems, {
      item_name: '',
      qty: 10
    }]);
  };
  const submitIndent = async () => {
    if (!toStoreId) {
      notify('Please select destination store counter.', 'error');
      return;
    }
    try {
      const res = await fetch(API_BASE + 'transfer/request', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          from_store_id: store.store_id,
          to_store_id: Number(toStoreId),
          remarks: remarks,
          items: transferItems.map(it => ({
            item_id: 1,
            qty: Number(it.qty)
          }))
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
  return /*#__PURE__*/_jsxs("div", {
    className: "counter-card",
    children: [/*#__PURE__*/_jsxs("h5", {
      className: "fw-bold mb-3",
      children: [/*#__PURE__*/_jsx("i", {
        className: "bi bi-arrow-left-right text-primary me-1"
      }), " Multi-Building Store Requisition (Indent)"]
    }), /*#__PURE__*/_jsx("p", {
      className: "text-muted small",
      children: "Building counters can requisition medicines from the Main Hospital Central Store. Once dispatched and physically verified, stock transfers automatically."
    }), /*#__PURE__*/_jsxs("div", {
      className: "row g-2 mb-3",
      children: [/*#__PURE__*/_jsxs("div", {
        className: "col-md-6",
        children: [/*#__PURE__*/_jsx("label", {
          className: "form-label small fw-bold",
          children: "Source Store (From)"
        }), /*#__PURE__*/_jsx("input", {
          type: "text",
          className: "form-control form-control-sm",
          value: store.store_name,
          disabled: true
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "col-md-6",
        children: [/*#__PURE__*/_jsx("label", {
          className: "form-label small fw-bold",
          children: "Target Counter / Building (To)"
        }), /*#__PURE__*/_jsxs("select", {
          className: "form-select form-select-sm",
          value: toStoreId,
          onChange: e => setToStoreId(e.target.value),
          children: [/*#__PURE__*/_jsx("option", {
            value: "",
            children: "-- Select Destination Store --"
          }), stores.filter(s => s.store_id !== store.store_id).map(s => /*#__PURE__*/_jsxs("option", {
            value: s.store_id,
            children: [s.store_name, " (", s.store_slug, ")"]
          }, s.store_id))]
        })]
      })]
    }), /*#__PURE__*/_jsxs("div", {
      className: "mb-3",
      children: [/*#__PURE__*/_jsx("label", {
        className: "form-label small fw-bold",
        children: "Requested Medicines"
      }), transferItems.map((ti, i) => /*#__PURE__*/_jsxs("div", {
        className: "row g-2 mb-2",
        children: [/*#__PURE__*/_jsx("div", {
          className: "col-8",
          children: /*#__PURE__*/_jsx("input", {
            type: "text",
            className: "form-control form-control-sm",
            placeholder: "Medicine Name",
            value: ti.item_name,
            onChange: e => {
              const updated = [...transferItems];
              updated[i].item_name = e.target.value;
              setTransferItems(updated);
            }
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-4",
          children: /*#__PURE__*/_jsx("input", {
            type: "number",
            className: "form-control form-control-sm",
            placeholder: "Quantity Required",
            min: "1",
            value: ti.qty,
            onChange: e => {
              const updated = [...transferItems];
              updated[i].qty = e.target.value;
              setTransferItems(updated);
            }
          })
        })]
      }, i)), /*#__PURE__*/_jsxs("button", {
        className: "btn btn-sm btn-outline-primary mt-1",
        onClick: addTransferRow,
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-plus-circle me-1"
        }), " Add Another Medicine"]
      })]
    }), /*#__PURE__*/_jsxs("button", {
      className: "btn btn-primary-custom",
      onClick: submitIndent,
      children: [/*#__PURE__*/_jsx("i", {
        className: "bi bi-send me-1"
      }), " Submit Store Indent Requisition"]
    })]
  });
}

// =========================================================================
// 5. ACCOUNTING, LEDGERS, DAYBOOK & GST REPORTS
// =========================================================================
function LedgersView({
  store,
  notify
}) {
  if (!store) return null;
  const [accTab, setAccTab] = useState('daybook'); // 'daybook', 'bank_audit', 'gst', 'schedule_h1', 'credit_accounts'

  // Helper: CSV Download
  const downloadCsv = (filename, rows) => {
    const csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.map(cell => `"${String(cell ?? '').replace(/"/g, '""')}"`).join(",")).join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Helper: Open Print Window
  const openPrintReport = (title, bodyHtml) => {
    const win = window.open('', '_blank', 'width=950,height=750');
    if (!win) {
      notify('Please allow popups to print report', 'warning');
      return;
    }
    const printDoc = win.document;
    printDoc.open();
    const headerHtml = '<div class="no-print mb-3 d-flex justify-content-between align-items-center bg-light p-2 rounded border">' + '<div><strong>' + title + '</strong> &mdash; Ready to print</div>' + '<div><button class="btn btn-primary btn-sm me-2 fw-bold" onclick="window.print()">🖨️ Print Now</button>' + '<button class="btn btn-outline-secondary btn-sm" onclick="window.close()">Close</button></div></div>' + '<div class="print-header d-flex justify-content-between align-items-start">' + '<div><h4 class="fw-bold mb-0 text-dark">' + (store.store_name || '') + '</h4>' + '<div class="text-muted">' + (store.location || 'Hospital Pharmacy Wing') + '</div>' + '<div class="small text-secondary mt-1">DL No: <strong>' + (store.drug_license_no || 'N/A') + '</strong> | GSTIN: <strong>' + (store.gst_no || 'N/A') + '</strong></div></div>' + '<div class="text-end"><h5 class="fw-bold text-primary mb-1">' + title + '</h5>' + '<div class="small text-muted">Generated: ' + new Date().toLocaleString() + '</div></div></div>';
    printDoc.write('<!DOCTYPE html><html><' + 'head><title>' + title + '</title>' + '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">' + '<style>body{font-family:sans-serif;padding:24px;font-size:13px;color:#1e293b;}table th,table td{padding:6px 8px;font-size:12px;}.print-header{border-bottom:2px solid #0f172a;padding-bottom:12px;margin-bottom:16px;}@media print{.no-print{display:none!important;}}</style>' + '</' + 'head><body>' + headerHtml + bodyHtml + '</body></html>');
    printDoc.close();
  };

  // 1. Daybook State
  const todayStr = new Date().toISOString().split('T')[0];
  const [daybookFromDate, setDaybookFromDate] = useState(todayStr);
  const [daybookToDate, setDaybookToDate] = useState(todayStr);
  const [daybookData, setDaybookData] = useState(null);
  const [loadingDaybook, setLoadingDaybook] = useState(false);

  // 2. Bank Audit State
  const [bankAuditData, setBankAuditData] = useState(null);
  const [auditFilterStatus, setAuditFilterStatus] = useState('all');
  const [auditSearch, setAuditSearch] = useState('');
  const [reconcilingSale, setReconcilingSale] = useState(null);
  const [auditNotes, setAuditNotes] = useState('');
  const [auditorName, setAuditorName] = useState('Accountant');
  const [isSubmittingAudit, setIsSubmittingAudit] = useState(false);

  // 3. GST State
  const currentMonthStr = todayStr.substring(0, 7); // YYYY-MM
  const [gstMonth, setGstMonth] = useState(currentMonthStr);
  const [gstData, setGstData] = useState(null);
  const [loadingGst, setLoadingGst] = useState(false);

  // 4. Schedule H1 State
  const [h1FromDate, setH1FromDate] = useState(todayStr);
  const [h1ToDate, setH1ToDate] = useState(todayStr);
  const [h1Search, setH1Search] = useState('');
  const [h1Records, setH1Records] = useState([]);
  const [loadingH1, setLoadingH1] = useState(false);

  // 5. Staff & Customer Credit Accounts State
  const [creditAccounts, setCreditAccounts] = useState([]);
  const [loadingCredit, setLoadingCredit] = useState(false);
  const [creditSearch, setCreditSearch] = useState('');
  const [showAddAccountModal, setShowAddAccountModal] = useState(false);
  const [accFormType, setAccFormType] = useState('staff');
  const [accFormName, setAccFormName] = useState('');
  const [accFormPhone, setAccFormPhone] = useState('');
  const [accFormStaffId, setAccFormStaffId] = useState('');
  const [accFormDept, setAccFormDept] = useState('');
  const [accFormLimit, setAccFormLimit] = useState('5000');
  const [accFormRemarks, setAccFormRemarks] = useState('');
  const [isSavingAccount, setIsSavingAccount] = useState(false);

  // Credit Ledger Statement & Payment Settle Modal
  const [selectedCreditAcc, setSelectedCreditAcc] = useState(null);
  const [creditLedgerData, setCreditLedgerData] = useState(null);
  const [loadingLedger, setLoadingLedger] = useState(false);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [payAmount, setPayAmount] = useState('');
  const [payMode, setPayMode] = useState('Cash');
  const [payRef, setPayRef] = useState('');
  const [payRemarks, setPayRemarks] = useState('');
  const [isRecordingPayment, setIsRecordingPayment] = useState(false);
  useEffect(() => {
    if (!store?.store_id) return;
    if (accTab === 'daybook') loadDaybook(daybookFromDate, daybookToDate);
    if (accTab === 'bank_audit') loadBankAudit(auditFilterStatus, auditSearch);
    if (accTab === 'gst') loadGst(gstMonth);
    if (accTab === 'schedule_h1') loadScheduleH1(h1FromDate, h1ToDate, h1Search);
    if (accTab === 'credit_accounts') loadCreditAccounts();
  }, [accTab, store?.store_id]);

  // DAYBOOK FUNCTIONS
  const loadDaybook = async (from = daybookFromDate, to = daybookToDate) => {
    setLoadingDaybook(true);
    try {
      const res = await fetch(`${API_BASE}accounting/daybook?store_id=${store.store_id}&from_date=${from}&to_date=${to}`);
      const data = await res.json();
      if (data.status) setDaybookData(data);
    } catch (e) {
      console.error(e);
      notify('Error loading daybook: ' + e.message, 'error');
    } finally {
      setLoadingDaybook(false);
    }
  };
  const handleDaybookFilterPreset = preset => {
    const today = new Date();
    let from = todayStr;
    let to = todayStr;
    if (preset === 'today') {
      from = todayStr;
      to = todayStr;
    } else if (preset === 'yesterday') {
      const y = new Date(today);
      y.setDate(today.getDate() - 1);
      from = y.toISOString().split('T')[0];
      to = from;
    } else if (preset === 'last7') {
      const w = new Date(today);
      w.setDate(today.getDate() - 6);
      from = w.toISOString().split('T')[0];
      to = todayStr;
    } else if (preset === 'month') {
      from = `${todayStr.substring(0, 7)}-01`;
      to = todayStr;
    }
    setDaybookFromDate(from);
    setDaybookToDate(to);
    loadDaybook(from, to);
  };
  const exportDaybookCsv = () => {
    if (!daybookData || !daybookData.transactions?.length) {
      notify('No transactions to export for selected dates.', 'warning');
      return;
    }
    const headers = ['Invoice No', 'Sale Date', 'Patient Name', 'UHID', 'Doctor Name', 'Payment Mode', 'Subtotal (Rs)', 'Discount (Rs)', 'CGST (Rs)', 'SGST (Rs)', 'Net Amount (Rs)'];
    const rows = daybookData.transactions.map(t => [t.invoice_no, t.sale_date, t.patient_name, t.uhid || '', t.doctor_name, t.payment_mode, t.subtotal, t.discount_amount, t.cgst_amount, t.sgst_amount, t.net_amount]);
    downloadCsv(`Daybook_${daybookFromDate}_to_${daybookToDate}.csv`, [headers, ...rows]);
    notify('Daybook CSV exported successfully.', 'success');
  };
  const printDaybook = () => {
    if (!daybookData || !daybookData.transactions?.length) {
      notify('No transactions to print.', 'warning');
      return;
    }
    const s = daybookData.summary;
    const html = `
            <div class="row g-2 mb-3">
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>Total Sales:</strong> ₹${Number(s.total_sales || 0).toLocaleString('en-IN')} (${s.total_invoices} bills)</div></div>
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>Cash in Drawer:</strong> ₹${Number(s.cash_in_drawer || 0).toLocaleString('en-IN')}</div></div>
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>UPI / QR:</strong> ₹${Number(s.upi_collection || 0).toLocaleString('en-IN')}</div></div>
                <div class="col-3"><div class="p-2 border rounded bg-light"><strong>IPD / Staff Credit:</strong> ₹${Number(s.ipd_credit || 0).toLocaleString('en-IN')}</div></div>
            </div>
            <p><strong>Period:</strong> ${daybookFromDate} to ${daybookToDate}</p>
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Invoice No</th>
                        <th>Date &amp; Time</th>
                        <th>Patient Name (UHID)</th>
                        <th>Doctor</th>
                        <th>Mode</th>
                        <th>CGST (₹)</th>
                        <th>SGST (₹)</th>
                        <th class="text-end">Net Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ${daybookData.transactions.map(t => `
                        <tr>
                            <td><strong>${t.invoice_no}</strong></td>
                            <td>${t.sale_date}</td>
                            <td>${t.patient_name} ${t.uhid ? '(' + t.uhid + ')' : ''}</td>
                            <td>${t.doctor_name || 'N/A'}</td>
                            <td>${t.payment_mode}</td>
                            <td>₹${t.cgst_amount}</td>
                            <td>₹${t.sgst_amount}</td>
                            <td class="text-end fw-bold">₹${t.net_amount}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    openPrintReport(`Daybook Journal (${daybookFromDate} to ${daybookToDate})`, html);
  };

  // BANK AUDIT FUNCTIONS
  const loadBankAudit = async (status = auditFilterStatus, search = auditSearch) => {
    try {
      const params = new URLSearchParams({
        store_id: store.store_id,
        status: status,
        search: search
      });
      const res = await fetch(`${API_BASE}accounting/bank-reconciliation?${params.toString()}`);
      const data = await res.json();
      if (data.status) setBankAuditData(data);
    } catch (e) {
      console.error(e);
    }
  };
  const handleReconcileSubmit = async (saleId, isReconciled) => {
    setIsSubmittingAudit(true);
    try {
      const res = await fetch(API_BASE + 'accounting/reconcile-bank', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          sale_id: saleId,
          is_reconciled: isReconciled ? 1 : 0,
          reconciled_notes: auditNotes,
          reconciled_by: auditorName
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(data.message, 'success');
        setReconcilingSale(null);
        setAuditNotes('');
        loadBankAudit(auditFilterStatus, auditSearch);
      } else {
        notify(data.message || 'Failed to update reconciliation', 'error');
      }
    } catch (e) {
      notify('Network error updating audit status: ' + e.message, 'error');
    } finally {
      setIsSubmittingAudit(false);
    }
  };
  const exportBankCsv = () => {
    if (!bankAuditData || !bankAuditData.transactions?.length) {
      notify('No bank/UPI transactions to export.', 'warning');
      return;
    }
    const headers = ['Invoice No', 'Sale Date', 'Patient', 'Mode', 'Amount (Rs)', 'UPI Ref No', 'Card Ref No', 'Audit Status', 'Reconciled By', 'Notes'];
    const bankTxns = bankAuditData.transactions || bankAuditData.records || [];
    const rows = bankTxns.map(t => [t.invoice_no, t.sale_date, t.patient_name, t.payment_mode, t.net_amount, t.upi_ref_no || '', t.card_ref_no || '', t.is_reconciled == 1 ? 'RECONCILED' : 'PENDING', t.reconciled_by || '', t.reconciled_notes || '']);
    downloadCsv(`Bank_UPI_Reconciliation_${todayStr}.csv`, [headers, ...rows]);
    notify('Bank & UPI Audit CSV exported.', 'success');
  };
  const printBankAudit = () => {
    if (!bankAuditData || !bankAuditData.transactions?.length) {
      notify('No bank transactions to print.', 'warning');
      return;
    }
    const s = bankAuditData.summary || {};
    const html = `
            <div class="row g-2 mb-3">
                <div class="col-4"><div class="p-2 border rounded bg-light"><strong>Total UPI / Bank Sales:</strong> ₹${Number(s.total_amount || 0).toLocaleString('en-IN')} (${s.total_count} txns)</div></div>
                <div class="col-4"><div class="p-2 border rounded bg-light text-success"><strong>Verified / Reconciled:</strong> ₹${Number(s.reconciled_amount || 0).toLocaleString('en-IN')} (${s.reconciled_count})</div></div>
                <div class="col-4"><div class="p-2 border rounded bg-light text-danger"><strong>Pending Verification:</strong> ₹${Number(s.pending_amount || 0).toLocaleString('en-IN')} (${s.pending_count})</div></div>
            </div>
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Patient Name</th>
                        <th>Mode</th>
                        <th>Amount</th>
                        <th>UPI / UTR Ref</th>
                        <th>Status</th>
                        <th>Audited By</th>
                    </tr>
                </thead>
                <tbody>
                    ${(bankAuditData.transactions || bankAuditData.records || []).map(t => `
                        <tr>
                            <td><strong>${t.invoice_no}</strong></td>
                            <td>${t.sale_date}</td>
                            <td>${t.patient_name}</td>
                            <td>${t.payment_mode}</td>
                            <td class="fw-bold">₹${t.net_amount}</td>
                            <td><code>${t.upi_ref_no || t.card_ref_no || 'N/A'}</code></td>
                            <td>${t.is_reconciled == 1 ? '<span class="badge bg-success">VERIFIED</span>' : '<span class="badge bg-warning text-dark">PENDING</span>'}</td>
                            <td>${t.reconciled_by || 'Unverified'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    openPrintReport('Bank & UPI Statement Audit Report', html);
  };

  // GST REPORT FUNCTIONS
  const loadGst = async (m = gstMonth) => {
    setLoadingGst(true);
    try {
      const res = await fetch(`${API_BASE}accounting/gst-report?store_id=${store.store_id}&month=${m}`);
      const data = await res.json();
      if (data.status) setGstData(data);
    } catch (e) {
      console.error(e);
      notify('Error loading GST report: ' + e.message, 'error');
    } finally {
      setLoadingGst(false);
    }
  };
  const exportGstCsv = () => {
    if (!gstData || !gstData.gstr1_sales?.length) {
      notify('No GST data to export for this month.', 'warning');
      return;
    }
    const headers = ['HSN Code', 'GST Slab (%)', 'Taxable Value (Rs)', 'CGST (Rs)', 'SGST (Rs)', 'IGST (Rs)', 'Total Value (Rs)'];
    const rows = gstData.gstr1_sales.map(g => [g.hsn_code, g.gst_rate, Number(g.taxable_val).toFixed(2), Number(g.cgst_val).toFixed(2), Number(g.sgst_val).toFixed(2), Number(g.igst_val).toFixed(2), Number(g.total_val).toFixed(2)]);
    downloadCsv(`GSTR1_Report_${gstMonth}.csv`, [headers, ...rows]);
    notify('GSTR-1 CSV exported successfully.', 'success');
  };

  // SCHEDULE H1 REGISTER FUNCTIONS
  const loadScheduleH1 = async (from = h1FromDate, to = h1ToDate, search = h1Search) => {
    setLoadingH1(true);
    try {
      const params = new URLSearchParams({
        store_id: store.store_id,
        from_date: from,
        to_date: to,
        search: search
      });
      const res = await fetch(`${API_BASE}compliance/schedule-h1-register?${params.toString()}`);
      const data = await res.json();
      if (data.status) setH1Records(data.records || []);
    } catch (e) {
      console.error(e);
    } finally {
      setLoadingH1(false);
    }
  };
  const exportH1Csv = () => {
    if (!h1Records.length) {
      notify('No Schedule H1 records to export.', 'warning');
      return;
    }
    const headers = ['Date', 'Bill No', 'Patient Name', 'Patient Address', 'Doctor Name', 'Doctor Reg No', 'Medicine Name', 'Batch No', 'Qty Supplied'];
    const rows = h1Records.map(h => [h.sale_date, h.invoice_no, h.patient_name, h.patient_address || '', h.doctor_name, h.doctor_reg_no || '', h.item_name, h.batch_no, h.qty]);
    downloadCsv(`Schedule_H1_Register_${h1FromDate}_to_${h1ToDate}.csv`, [headers, ...rows]);
    notify('Schedule H1 Register CSV exported.', 'success');
  };
  const printH1Register = () => {
    if (!h1Records.length) {
      notify('No Schedule H1 records to print.', 'warning');
      return;
    }
    const html = `
            <div class="alert alert-info py-1 mb-3">
                <strong>Statutory Compliance:</strong> Mandatory Register under Drugs and Cosmetics (4th Amendment) Rules, 2013. Records must be retained for at least 3 years and open for inspection by Drug Inspector.
            </div>
            <p><strong>Record Period:</strong> ${h1FromDate} to ${h1ToDate}</p>
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Bill No</th>
                        <th>Patient Name &amp; Address</th>
                        <th>Prescribing Doctor &amp; Reg No</th>
                        <th>Schedule H1 Drug Name</th>
                        <th>Batch No</th>
                        <th class="text-end">Qty Dispensed</th>
                    </tr>
                </thead>
                <tbody>
                    ${h1Records.map(h => `
                        <tr>
                            <td>${h.sale_date.split(' ')[0]}</td>
                            <td><strong>${h.invoice_no}</strong></td>
                            <td>${h.patient_name} ${h.patient_address ? '<br><small class="text-muted">' + h.patient_address + '</small>' : ''}</td>
                            <td>${h.doctor_name} ${h.doctor_reg_no ? '<br><small class="text-muted">Reg: ' + h.doctor_reg_no + '</small>' : ''}</td>
                            <td class="text-danger fw-bold">${h.item_name}</td>
                            <td><code>${h.batch_no}</code></td>
                            <td class="text-end fw-bold">${h.qty}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    openPrintReport('Statutory Schedule H1 Drug Register', html);
  };

  // CREDIT ACCOUNTS FUNCTIONS
  const loadCreditAccounts = async () => {
    setLoadingCredit(true);
    try {
      const res = await fetch(`${API_BASE}credit/accounts?store_id=${store.store_id}`);
      const data = await res.json();
      if (data.status) setCreditAccounts(data.accounts || []);
    } catch (e) {
      console.error(e);
      notify('Error loading credit accounts: ' + e.message, 'error');
    } finally {
      setLoadingCredit(false);
    }
  };
  const handleSaveCreditAccount = async e => {
    e.preventDefault();
    if (!accFormName.trim()) {
      notify('Account / Person Name is required', 'error');
      return;
    }
    setIsSavingAccount(true);
    try {
      const res = await fetch(`${API_BASE}credit/save-account`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          account_type: accFormType,
          account_name: accFormName.trim(),
          phone: accFormPhone.trim(),
          staff_id: accFormStaffId.trim(),
          department: accFormDept.trim(),
          credit_limit: Number(accFormLimit) || 0,
          remarks: accFormRemarks.trim()
        })
      });
      const data = await res.json();
      if (data.status) {
        notify('Credit account created successfully.', 'success');
        setShowAddAccountModal(false);
        setAccFormName('');
        setAccFormPhone('');
        setAccFormStaffId('');
        setAccFormDept('');
        setAccFormRemarks('');
        loadCreditAccounts();
      } else {
        notify(data.message || 'Failed to create credit account', 'error');
      }
    } catch (err) {
      notify('Error: ' + err.message, 'error');
    } finally {
      setIsSavingAccount(false);
    }
  };
  const openAccountLedger = async acc => {
    setSelectedCreditAcc(acc);
    setLoadingLedger(true);
    try {
      const res = await fetch(`${API_BASE}credit/ledger/${acc.account_id}?store_id=${store.store_id}`);
      const data = await res.json();
      if (data.status) {
        setCreditLedgerData(data);
      }
    } catch (e) {
      notify('Error loading account ledger: ' + e.message, 'error');
    } finally {
      setLoadingLedger(false);
    }
  };
  const openSettlePaymentModal = acc => {
    setSelectedCreditAcc(acc);
    setPayAmount(acc.current_balance > 0 ? String(acc.current_balance) : '');
    setPayMode('Cash');
    setPayRef('');
    setPayRemarks('');
    setShowPaymentModal(true);
  };
  const handleRecordPayment = async e => {
    e.preventDefault();
    const amt = Number(payAmount);
    if (!amt || amt <= 0) {
      notify('Please enter a valid payment amount.', 'error');
      return;
    }
    setIsRecordingPayment(true);
    try {
      const res = await fetch(`${API_BASE}credit/record-payment`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          account_id: selectedCreditAcc.account_id,
          amount: amt,
          payment_mode: payMode,
          payment_ref: payRef.trim(),
          remarks: payRemarks.trim(),
          recorded_by_name: 'Accountant'
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(`Settlement of ₹${amt} recorded successfully. New balance: ₹${data.new_balance}`, 'success');
        setShowPaymentModal(false);
        loadCreditAccounts();
        if (selectedCreditAcc) {
          openAccountLedger({
            ...selectedCreditAcc,
            current_balance: data.new_balance
          });
        }
      } else {
        notify(data.message || 'Failed to record payment', 'error');
      }
    } catch (err) {
      notify('Error recording payment: ' + err.message, 'error');
    } finally {
      setIsRecordingPayment(false);
    }
  };
  const printAccountLedger = () => {
    if (!creditLedgerData || !selectedCreditAcc) return;
    const html = `
            <div class="card p-3 mb-3 bg-light">
                <div class="row">
                    <div class="col-6">
                        <strong>Account Name:</strong> ${selectedCreditAcc.account_name} (${selectedCreditAcc.account_type.toUpperCase()})<br>
                        <strong>Phone:</strong> ${selectedCreditAcc.phone || 'N/A'}<br>
                        <strong>Dept / Staff ID:</strong> ${selectedCreditAcc.department || ''} ${selectedCreditAcc.staff_id ? '(' + selectedCreditAcc.staff_id + ')' : ''}
                    </div>
                    <div class="col-6 text-end">
                        <strong>Credit Limit:</strong> ₹${Number(selectedCreditAcc.credit_limit || 0).toLocaleString('en-IN')}<br>
                        <strong class="text-danger fs-6">Current Outstanding:</strong> ₹${Number(selectedCreditAcc.current_balance || 0).toLocaleString('en-IN')}
                    </div>
                </div>
            </div>
            <table class="table table-bordered table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Invoice / Ref No</th>
                        <th class="text-end">Debit (Bills)</th>
                        <th class="text-end">Credit (Payments)</th>
                        <th class="text-end">Balance After</th>
                        <th>Recorded By / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    ${creditLedgerData.entries.map(e => `
                        <tr>
                            <td>${e.created_at}</td>
                            <td>${e.entry_type === 'DEBIT' ? '<span class="badge bg-danger">BILL DEBIT</span>' : '<span class="badge bg-success">PAYMENT</span>'}</td>
                            <td><strong>${e.invoice_no || e.payment_ref || '-'}</strong></td>
                            <td class="text-end fw-bold text-danger">${Number(e.debit_amount) > 0 ? '₹' + Number(e.debit_amount).toFixed(2) : '-'}</td>
                            <td class="text-end fw-bold text-success">${Number(e.credit_amount) > 0 ? '₹' + Number(e.credit_amount).toFixed(2) : '-'}</td>
                            <td class="text-end fw-bold">₹${Number(e.balance_after).toFixed(2)}</td>
                            <td><small>${e.remarks || ''} (${e.recorded_by_name || 'Staff'})</small></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    openPrintReport(`Credit Account Statement - ${selectedCreditAcc.account_name}`, html);
  };

  // Filtered credit accounts
  const filteredCreditAccounts = creditAccounts.filter(acc => {
    if (!creditSearch) return true;
    const q = creditSearch.toLowerCase();
    return acc.account_name && acc.account_name.toLowerCase().includes(q) || acc.phone && acc.phone.includes(q) || acc.staff_id && acc.staff_id.toLowerCase().includes(q) || acc.department && acc.department.toLowerCase().includes(q);
  });
  const totalOutstandingCredit = creditAccounts.reduce((sum, a) => sum + Number(a.current_balance || 0), 0);
  return /*#__PURE__*/_jsxs("div", {
    children: [/*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-wrap gap-2 mb-3",
      children: [/*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${accTab === 'daybook' ? 'btn-primary' : 'btn-outline-secondary'}`,
        onClick: () => setAccTab('daybook'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-journal-check me-1"
        }), " Daily Cash Book / Day Book"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${accTab === 'bank_audit' ? 'btn-primary' : 'btn-outline-secondary'}`,
        onClick: () => setAccTab('bank_audit'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-bank2 me-1"
        }), " Bank & UPI Statement Audit"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${accTab === 'gst' ? 'btn-primary' : 'btn-outline-secondary'}`,
        onClick: () => setAccTab('gst'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-file-earmark-spreadsheet me-1"
        }), " GST Returns (GSTR-1 & 2)"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${accTab === 'schedule_h1' ? 'btn-primary' : 'btn-outline-secondary'}`,
        onClick: () => setAccTab('schedule_h1'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-shield-check me-1"
        }), " Statutory Schedule H1 Register"]
      }), /*#__PURE__*/_jsxs("button", {
        className: `btn btn-sm ${accTab === 'credit_accounts' ? 'btn-primary' : 'btn-outline-secondary'}`,
        onClick: () => setAccTab('credit_accounts'),
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-people-fill me-1"
        }), " Staff & Customer Credit Accounts", totalOutstandingCredit > 0 && /*#__PURE__*/_jsxs("span", {
          className: "badge bg-danger rounded-pill ms-1.5",
          style: {
            fontSize: '0.65rem'
          },
          children: ["₹", totalOutstandingCredit.toLocaleString('en-IN')]
        })]
      })]
    }), accTab === 'daybook' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "d-flex flex-wrap align-items-center justify-content-between gap-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex flex-wrap align-items-center gap-2",
            children: [/*#__PURE__*/_jsxs("span", {
              className: "fw-bold text-dark small",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-funnel text-primary me-1"
              }), " Quick Filter:"]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-outline-secondary py-1 px-2.5",
              onClick: () => handleDaybookFilterPreset('today'),
              children: "Today"
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-outline-secondary py-1 px-2.5",
              onClick: () => handleDaybookFilterPreset('yesterday'),
              children: "Yesterday"
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-outline-secondary py-1 px-2.5",
              onClick: () => handleDaybookFilterPreset('last7'),
              children: "Last 7 Days"
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-outline-secondary py-1 px-2.5",
              onClick: () => handleDaybookFilterPreset('month'),
              children: "This Month"
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2 flex-wrap",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-1",
              children: [/*#__PURE__*/_jsx("label", {
                className: "small text-muted mb-0",
                children: "From:"
              }), /*#__PURE__*/_jsx("input", {
                type: "date",
                className: "form-control form-control-sm",
                value: daybookFromDate,
                onChange: e => setDaybookFromDate(e.target.value)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-1",
              children: [/*#__PURE__*/_jsx("label", {
                className: "small text-muted mb-0",
                children: "To:"
              }), /*#__PURE__*/_jsx("input", {
                type: "date",
                className: "form-control form-control-sm",
                value: daybookToDate,
                onChange: e => setDaybookToDate(e.target.value)
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-primary px-3 fw-bold",
              onClick: () => loadDaybook(daybookFromDate, daybookToDate),
              disabled: loadingDaybook,
              children: loadingDaybook ? 'Loading...' : 'Apply'
            }), /*#__PURE__*/_jsxs("div", {
              className: "border-start ps-2 d-flex gap-1.5",
              children: [/*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-dark px-2.5 d-flex align-items-center gap-1",
                onClick: printDaybook,
                title: "Print Daybook Statement",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-printer"
                }), " Print"]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-success px-2.5 d-flex align-items-center gap-1",
                onClick: exportDaybookCsv,
                title: "Download Excel / CSV",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-excel"
                }), " Export CSV"]
              })]
            })]
          })]
        })
      }), daybookData && /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "row g-3 mb-3",
          children: [/*#__PURE__*/_jsx("div", {
            className: "col-md-3 col-sm-6",
            children: /*#__PURE__*/_jsxs("div", {
              className: "counter-card p-3 border-start border-4 border-success shadow-sm",
              children: [/*#__PURE__*/_jsx("small", {
                className: "text-muted fw-bold",
                children: "TOTAL PERIOD SALES"
              }), /*#__PURE__*/_jsxs("h4", {
                className: "fw-bolder text-dark mt-1",
                children: ["₹", Number(daybookData.summary.total_sales || 0).toLocaleString('en-IN')]
              }), /*#__PURE__*/_jsxs("small", {
                className: "text-muted",
                children: [daybookData.summary.total_invoices, " Bills"]
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "col-md-3 col-sm-6",
            children: /*#__PURE__*/_jsxs("div", {
              className: "counter-card p-3 border-start border-4 border-primary shadow-sm",
              children: [/*#__PURE__*/_jsx("small", {
                className: "text-muted fw-bold",
                children: "CASH IN DRAWER"
              }), /*#__PURE__*/_jsxs("h4", {
                className: "fw-bolder text-primary mt-1",
                children: ["₹", Number(daybookData.summary.cash_in_drawer || 0).toLocaleString('en-IN')]
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "col-md-3 col-sm-6",
            children: /*#__PURE__*/_jsxs("div", {
              className: "counter-card p-3 border-start border-4 border-warning shadow-sm",
              children: [/*#__PURE__*/_jsx("small", {
                className: "text-muted fw-bold",
                children: "UPI / QR COLLECTIONS"
              }), /*#__PURE__*/_jsxs("h4", {
                className: "fw-bolder text-warning mt-1",
                children: ["₹", Number(daybookData.summary.upi_collection || 0).toLocaleString('en-IN')]
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "col-md-3 col-sm-6",
            children: /*#__PURE__*/_jsxs("div", {
              className: "counter-card p-3 border-start border-4 border-danger shadow-sm",
              children: [/*#__PURE__*/_jsx("small", {
                className: "text-muted fw-bold",
                children: "CREDIT BILLED (IPD + STAFF)"
              }), /*#__PURE__*/_jsxs("h4", {
                className: "fw-bolder text-danger mt-1",
                children: ["₹", Number(daybookData.summary.ipd_credit || 0).toLocaleString('en-IN')]
              })]
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "counter-card p-0 overflow-hidden shadow-sm",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "p-3 border-bottom d-flex justify-content-between align-items-center bg-light",
            children: [/*#__PURE__*/_jsxs("h6", {
              className: "mb-0 fw-bold",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-receipt text-primary me-1"
              }), " Transactions Journal (", daybookFromDate, " to ", daybookToDate, ")"]
            }), /*#__PURE__*/_jsxs("span", {
              className: "badge bg-secondary",
              children: [daybookData.transactions.length, " Records"]
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "table-responsive",
            children: /*#__PURE__*/_jsxs("table", {
              className: "table table-pos mb-0",
              children: [/*#__PURE__*/_jsx("thead", {
                children: /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsx("th", {
                    children: "Invoice No"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Date & Time"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Patient (UHID)"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Doctor"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "Mode"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "CGST (₹)"
                  }), /*#__PURE__*/_jsx("th", {
                    children: "SGST (₹)"
                  }), /*#__PURE__*/_jsx("th", {
                    className: "text-end",
                    children: "Net Amount"
                  })]
                })
              }), /*#__PURE__*/_jsx("tbody", {
                children: daybookData.transactions.length === 0 ? /*#__PURE__*/_jsx("tr", {
                  children: /*#__PURE__*/_jsx("td", {
                    colSpan: "8",
                    className: "text-center py-4 text-muted",
                    children: "No transactions found for the selected date range."
                  })
                }) : daybookData.transactions.map(t => /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsx("td", {
                    className: "fw-bold",
                    children: t.invoice_no
                  }), /*#__PURE__*/_jsx("td", {
                    children: t.sale_date
                  }), /*#__PURE__*/_jsxs("td", {
                    children: [t.patient_name, " ", t.uhid ? `(${t.uhid})` : '']
                  }), /*#__PURE__*/_jsx("td", {
                    children: t.doctor_name
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: "badge bg-secondary",
                      children: t.payment_mode
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    children: ["₹", t.cgst_amount]
                  }), /*#__PURE__*/_jsxs("td", {
                    children: ["₹", t.sgst_amount]
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "text-end fw-bold text-success",
                    children: ["₹", t.net_amount]
                  })]
                }, t.sale_id))
              })]
            })
          })]
        })]
      })]
    }), accTab === 'bank_audit' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsxs("div", {
        className: "row g-3 mb-3",
        children: [/*#__PURE__*/_jsx("div", {
          className: "col-md-4 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-primary shadow-sm",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted fw-bold",
              children: "TOTAL ONLINE / UPI SALES"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-dark mt-1",
              children: ["₹", Number(bankAuditData?.summary?.total_amount || 0).toLocaleString('en-IN', {
                minimumFractionDigits: 2
              })]
            }), /*#__PURE__*/_jsxs("small", {
              className: "text-muted",
              children: [bankAuditData?.summary?.total_count || 0, " Transactions Recorded"]
            })]
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-4 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-success shadow-sm",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-success fw-bold",
              children: "STATEMENT RECONCILED (VERIFIED)"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-success mt-1",
              children: ["₹", Number(bankAuditData?.summary?.reconciled_amount || 0).toLocaleString('en-IN', {
                minimumFractionDigits: 2
              })]
            }), /*#__PURE__*/_jsxs("small", {
              className: "text-success",
              children: [bankAuditData?.summary?.reconciled_count || 0, " Bills Matched with Bank"]
            })]
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-4 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-warning shadow-sm",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-warning fw-bold",
              children: "PENDING AUDIT VERIFICATION"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-warning mt-1",
              children: ["₹", Number(bankAuditData?.summary?.pending_amount || 0).toLocaleString('en-IN', {
                minimumFractionDigits: 2
              })]
            }), /*#__PURE__*/_jsxs("small", {
              className: "text-warning",
              children: [bankAuditData?.summary?.pending_count || 0, " Bills Unverified"]
            })]
          })
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "row g-2 align-items-center",
          children: [/*#__PURE__*/_jsx("div", {
            className: "col-md-5",
            children: /*#__PURE__*/_jsxs("div", {
              className: "btn-group btn-group-sm",
              children: [/*#__PURE__*/_jsxs("button", {
                className: `btn ${auditFilterStatus === 'all' ? 'btn-primary' : 'btn-outline-secondary'}`,
                onClick: () => {
                  setAuditFilterStatus('all');
                  loadBankAudit('all', auditSearch);
                },
                children: ["All (", bankAuditData?.summary?.total_count || 0, ")"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn ${auditFilterStatus === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'}`,
                onClick: () => {
                  setAuditFilterStatus('pending');
                  loadBankAudit('pending', auditSearch);
                },
                children: ["Pending Verification (", bankAuditData?.summary?.pending_count || 0, ")"]
              }), /*#__PURE__*/_jsxs("button", {
                className: `btn ${auditFilterStatus === 'reconciled' ? 'btn-success fw-bold' : 'btn-outline-secondary'}`,
                onClick: () => {
                  setAuditFilterStatus('reconciled');
                  loadBankAudit('reconciled', auditSearch);
                },
                children: ["Reconciled (", bankAuditData?.summary?.reconciled_count || 0, ")"]
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "col-md-4",
            children: /*#__PURE__*/_jsxs("div", {
              className: "input-group input-group-sm",
              children: [/*#__PURE__*/_jsx("span", {
                className: "input-group-text bg-light",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-search"
                })
              }), /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control",
                placeholder: "Search by Bill No, UTR / UPI Ref...",
                value: auditSearch,
                onChange: e => {
                  setAuditSearch(e.target.value);
                  loadBankAudit(auditFilterStatus, e.target.value);
                }
              })]
            })
          }), /*#__PURE__*/_jsxs("div", {
            className: "col-md-3 text-end d-flex gap-1.5 justify-content-end",
            children: [/*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-dark",
              onClick: printBankAudit,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-printer me-1"
              }), " Print"]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-success",
              onClick: exportBankCsv,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-file-earmark-excel me-1"
              }), " Export CSV"]
            })]
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-0 overflow-hidden shadow-sm",
        children: /*#__PURE__*/_jsx("div", {
          className: "table-responsive",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-pos mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  children: "Invoice No"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Date & Time"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Patient"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Mode"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Bill Total"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Bank / UTR Reference"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Audit Status"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "Action"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: (() => {
                const txns = bankAuditData?.transactions || bankAuditData?.records || [];
                if (!bankAuditData) {
                  return /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsxs("td", {
                      colSpan: "8",
                      className: "text-center py-4 text-muted",
                      children: [/*#__PURE__*/_jsx("div", {
                        className: "spinner-border spinner-border-sm text-primary me-2"
                      }), "Loading bank audit records..."]
                    })
                  });
                }
                if (txns.length === 0) {
                  return /*#__PURE__*/_jsx("tr", {
                    children: /*#__PURE__*/_jsx("td", {
                      colSpan: "8",
                      className: "text-center py-4 text-muted",
                      children: "No transactions found matching the filter."
                    })
                  });
                }
                return txns.map(t => /*#__PURE__*/_jsxs("tr", {
                  className: t.is_reconciled == 1 ? 'bg-success-subtle' : '',
                  children: [/*#__PURE__*/_jsx("td", {
                    className: "fw-bold",
                    children: t.invoice_no
                  }), /*#__PURE__*/_jsx("td", {
                    children: t.sale_date
                  }), /*#__PURE__*/_jsx("td", {
                    children: t.patient_name
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: "badge bg-secondary",
                      children: t.payment_mode
                    })
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "fw-bold text-primary",
                    children: ["₹", t.net_amount]
                  }), /*#__PURE__*/_jsx("td", {
                    children: t.upi_ref_no ? /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("span", {
                        className: "badge bg-info text-dark",
                        children: "UPI"
                      }), " ", /*#__PURE__*/_jsx("code", {
                        children: t.upi_ref_no
                      })]
                    }) : t.card_ref_no ? /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsx("span", {
                        className: "badge bg-primary",
                        children: "CARD"
                      }), " ", /*#__PURE__*/_jsx("code", {
                        children: t.card_ref_no
                      })]
                    }) : /*#__PURE__*/_jsx("span", {
                      className: "text-muted",
                      children: "Direct QR"
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: t.is_reconciled == 1 ? /*#__PURE__*/_jsxs("div", {
                      children: [/*#__PURE__*/_jsxs("span", {
                        className: "badge bg-success",
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-check-circle me-1"
                        }), " Reconciled"]
                      }), /*#__PURE__*/_jsxs("div", {
                        className: "small text-muted mt-0.5",
                        children: [t.reconciled_by, " • ", t.reconciled_at?.split(' ')[0]]
                      })]
                    }) : /*#__PURE__*/_jsxs("span", {
                      className: "badge bg-warning text-dark",
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-clock me-1"
                      }), " Pending Statement"]
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end",
                    children: t.is_reconciled == 1 ? /*#__PURE__*/_jsx("button", {
                      className: "btn btn-sm btn-outline-danger py-0.5 px-2",
                      style: {
                        fontSize: '0.75rem'
                      },
                      onClick: () => handleReconcileSubmit(t.sale_id, 0),
                      children: "Un-reconcile"
                    }) : /*#__PURE__*/_jsxs("button", {
                      className: "btn btn-sm btn-success py-0.5 px-2.5 fw-bold",
                      style: {
                        fontSize: '0.75rem'
                      },
                      onClick: () => {
                        setReconcilingSale(t);
                        setAuditNotes('');
                      },
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-check2-circle me-1"
                      }), " Verify Entry"]
                    })
                  })]
                }, t.sale_id));
              })()
            })]
          })
        })
      }), reconcilingSale && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        tabIndex: "-1",
        style: {
          backgroundColor: 'rgba(15, 23, 42, 0.7)'
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content shadow-lg border-0",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-dark text-white py-2.5 px-3",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "modal-title fw-bold mb-0",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-bank me-2 text-primary"
                }), " Reconcile Bank / UPI Statement"]
              }), /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn-close btn-close-white",
                onClick: () => setReconcilingSale(null)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "card bg-light border p-2.5 mb-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between mb-1",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "text-muted",
                    children: "Invoice No:"
                  }), /*#__PURE__*/_jsx("span", {
                    className: "fw-bold text-dark",
                    children: reconcilingSale.invoice_no
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between mb-1",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "text-muted",
                    children: "Amount:"
                  }), /*#__PURE__*/_jsxs("span", {
                    className: "fw-bold text-success fs-6",
                    children: ["₹", reconcilingSale.net_amount]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "d-flex justify-content-between mb-1",
                  children: [/*#__PURE__*/_jsx("span", {
                    className: "text-muted",
                    children: "Recorded Ref / UTR:"
                  }), /*#__PURE__*/_jsx("code", {
                    children: reconcilingSale.upi_ref_no || reconcilingSale.card_ref_no || 'Direct Store QR'
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "mb-2",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small fw-bold mb-1",
                  children: "Audited By (Accountant / User)"
                }), /*#__PURE__*/_jsx("input", {
                  type: "text",
                  className: "form-control form-control-sm",
                  value: auditorName,
                  onChange: e => setAuditorName(e.target.value)
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "mb-3",
                children: [/*#__PURE__*/_jsx("label", {
                  className: "form-label small fw-bold mb-1",
                  children: "Accountant Statement Audit Note"
                }), /*#__PURE__*/_jsx("textarea", {
                  className: "form-control form-control-sm",
                  rows: "2",
                  value: auditNotes,
                  onChange: e => setAuditNotes(e.target.value),
                  placeholder: "e.g. Matched in Bank A/c statement entry on 08-Sep-2026, Credit Ref verified"
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer py-2 px-3 bg-light",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-outline-secondary",
                onClick: () => setReconcilingSale(null),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-success fw-bold",
                onClick: () => handleReconcileSubmit(reconcilingSale.sale_id, 1),
                disabled: isSubmittingAudit,
                children: isSubmittingAudit ? 'Saving...' : 'Mark Verified with Statement'
              })]
            })]
          })
        })
      })]
    }), accTab === 'gst' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "d-flex flex-wrap align-items-center justify-content-between gap-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2",
            children: [/*#__PURE__*/_jsxs("label", {
              className: "small fw-bold text-dark mb-0",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-calendar-month text-primary me-1"
              }), " Return Filing Month:"]
            }), /*#__PURE__*/_jsx("input", {
              type: "month",
              className: "form-control form-control-sm",
              value: gstMonth,
              onChange: e => {
                setGstMonth(e.target.value);
                loadGst(e.target.value);
              }
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-primary px-3 fw-bold",
              onClick: () => loadGst(gstMonth),
              disabled: loadingGst,
              children: loadingGst ? 'Loading...' : 'Refresh'
            })]
          }), /*#__PURE__*/_jsx("div", {
            children: /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-success px-3 fw-bold d-flex align-items-center gap-1.5",
              onClick: exportGstCsv,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-file-earmark-excel"
              }), " Export GSTR-1 Excel / CSV"]
            })
          })]
        })
      }), gstData && /*#__PURE__*/_jsxs("div", {
        className: "counter-card mb-3 shadow-sm",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "p-3 border-bottom d-flex justify-content-between align-items-center",
          children: [/*#__PURE__*/_jsxs("h6", {
            className: "fw-bold mb-0",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-file-earmark-spreadsheet text-primary me-1"
            }), " GSTR-1 Outward Sales Summary (HSN-wise) for ", gstMonth]
          }), /*#__PURE__*/_jsxs("span", {
            className: "badge bg-primary",
            children: ["GSTIN: ", store.gst_no || 'N/A']
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "table-responsive",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-pos mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  children: "HSN Code"
                }), /*#__PURE__*/_jsx("th", {
                  children: "GST Slab"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Taxable Value (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  children: "CGST (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  children: "SGST (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  children: "IGST (₹)"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "Total Invoice Value (₹)"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: gstData.gstr1_sales.length === 0 ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsxs("td", {
                  colSpan: "7",
                  className: "text-center py-4 text-muted",
                  children: ["No sales recorded for GST filing in month ", gstMonth, "."]
                })
              }) : gstData.gstr1_sales.map((g, i) => /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("td", {
                  children: /*#__PURE__*/_jsx("code", {
                    children: g.hsn_code
                  })
                }), /*#__PURE__*/_jsx("td", {
                  children: /*#__PURE__*/_jsxs("span", {
                    className: "badge bg-primary",
                    children: [g.gst_rate, "%"]
                  })
                }), /*#__PURE__*/_jsxs("td", {
                  children: ["₹", Number(g.taxable_val).toFixed(2)]
                }), /*#__PURE__*/_jsxs("td", {
                  children: ["₹", Number(g.cgst_val).toFixed(2)]
                }), /*#__PURE__*/_jsxs("td", {
                  children: ["₹", Number(g.sgst_val).toFixed(2)]
                }), /*#__PURE__*/_jsxs("td", {
                  children: ["₹", Number(g.igst_val).toFixed(2)]
                }), /*#__PURE__*/_jsxs("td", {
                  className: "text-end fw-bold",
                  children: ["₹", Number(g.total_val).toFixed(2)]
                })]
              }, i))
            })]
          })
        })]
      })]
    }), accTab === 'schedule_h1' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3",
        children: /*#__PURE__*/_jsxs("div", {
          className: "d-flex flex-wrap align-items-center justify-content-between gap-3",
          children: [/*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2 flex-wrap",
            children: [/*#__PURE__*/_jsxs("span", {
              className: "small fw-bold text-dark",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-funnel text-success me-1"
              }), " Date Filter:"]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center gap-1",
              children: [/*#__PURE__*/_jsx("input", {
                type: "date",
                className: "form-control form-control-sm",
                value: h1FromDate,
                onChange: e => setH1FromDate(e.target.value)
              }), /*#__PURE__*/_jsx("span", {
                children: "to"
              }), /*#__PURE__*/_jsx("input", {
                type: "date",
                className: "form-control form-control-sm",
                value: h1ToDate,
                onChange: e => setH1ToDate(e.target.value)
              })]
            }), /*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-sm btn-primary fw-bold",
              onClick: () => loadScheduleH1(h1FromDate, h1ToDate, h1Search),
              disabled: loadingH1,
              children: loadingH1 ? 'Loading...' : 'Filter'
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center gap-2",
            children: [/*#__PURE__*/_jsx("input", {
              type: "text",
              className: "form-control form-control-sm",
              placeholder: "Search medicine or doctor...",
              value: h1Search,
              onChange: e => {
                setH1Search(e.target.value);
                loadScheduleH1(h1FromDate, h1ToDate, e.target.value);
              }
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-dark",
              onClick: printH1Register,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-printer me-1"
              }), " Print"]
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-outline-success",
              onClick: exportH1Csv,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-file-earmark-excel me-1"
              }), " Export CSV"]
            })]
          })]
        })
      }), /*#__PURE__*/_jsxs("div", {
        className: "counter-card shadow-sm",
        children: [/*#__PURE__*/_jsxs("div", {
          className: "p-3 border-bottom d-flex justify-content-between align-items-center",
          children: [/*#__PURE__*/_jsxs("div", {
            children: [/*#__PURE__*/_jsxs("h6", {
              className: "fw-bold mb-0",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-shield-check text-success me-1"
              }), " Statutory Schedule H1 Drug Register"]
            }), /*#__PURE__*/_jsx("small", {
              className: "text-muted",
              children: "Mandatory under Indian Drugs & Cosmetics Rules (4th Amendment 2013). Retain for 3 years."
            })]
          }), /*#__PURE__*/_jsxs("span", {
            className: "badge bg-danger-subtle text-danger border border-danger",
            children: [h1Records.length, " Dispensed Entries"]
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "table-responsive",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-pos table-bordered mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  children: "Date"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Bill No"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Patient Name & Address"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Prescribing Doctor & Reg No"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Medicine Name"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Batch No"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Qty Supplied"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: h1Records.length === 0 ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsx("td", {
                  colSpan: "7",
                  className: "text-center py-4 text-muted",
                  children: "No Schedule H1 medicines dispensed in this period."
                })
              }) : h1Records.map((h, i) => /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("td", {
                  children: h.sale_date?.split(' ')[0]
                }), /*#__PURE__*/_jsx("td", {
                  className: "fw-bold",
                  children: h.invoice_no
                }), /*#__PURE__*/_jsxs("td", {
                  children: [h.patient_name, " ", h.patient_address ? `(${h.patient_address})` : '']
                }), /*#__PURE__*/_jsxs("td", {
                  children: [h.doctor_name, " ", h.doctor_reg_no ? `(Reg: ${h.doctor_reg_no})` : '']
                }), /*#__PURE__*/_jsx("td", {
                  className: "fw-bold text-danger",
                  children: h.item_name
                }), /*#__PURE__*/_jsx("td", {
                  children: /*#__PURE__*/_jsx("code", {
                    children: h.batch_no
                  })
                }), /*#__PURE__*/_jsx("td", {
                  className: "fw-bold",
                  children: h.qty
                })]
              }, i))
            })]
          })
        })]
      })]
    }), accTab === 'credit_accounts' && /*#__PURE__*/_jsxs("div", {
      children: [/*#__PURE__*/_jsxs("div", {
        className: "row g-3 mb-3",
        children: [/*#__PURE__*/_jsx("div", {
          className: "col-md-4 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-primary shadow-sm",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-muted fw-bold",
              children: "TOTAL REGISTERED ACCOUNTS"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-dark mt-1",
              children: [creditAccounts.length, " Accounts"]
            }), /*#__PURE__*/_jsx("small", {
              className: "text-muted",
              children: "Staff, Doctors, Institutional & Patients"
            })]
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-4 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-danger shadow-sm",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-danger fw-bold",
              children: "TOTAL OUTSTANDING DUES"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-danger mt-1",
              children: ["₹", totalOutstandingCredit.toLocaleString('en-IN', {
                minimumFractionDigits: 2
              })]
            }), /*#__PURE__*/_jsx("small", {
              className: "text-danger",
              children: "Unsettled Pharmacy Debits"
            })]
          })
        }), /*#__PURE__*/_jsx("div", {
          className: "col-md-4 col-sm-6",
          children: /*#__PURE__*/_jsxs("div", {
            className: "counter-card p-3 border-start border-4 border-success shadow-sm",
            children: [/*#__PURE__*/_jsx("small", {
              className: "text-success fw-bold",
              children: "TOTAL CREDIT LIMIT SANCTIONED"
            }), /*#__PURE__*/_jsxs("h4", {
              className: "fw-bolder text-success mt-1",
              children: ["₹", creditAccounts.reduce((sum, a) => sum + Number(a.credit_limit || 0), 0).toLocaleString('en-IN', {
                minimumFractionDigits: 2
              })]
            }), /*#__PURE__*/_jsx("small", {
              className: "text-success",
              children: "Authorized Spending Cap"
            })]
          })
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-3 mb-3 shadow-sm",
        children: /*#__PURE__*/_jsxs("div", {
          className: "d-flex flex-wrap align-items-center justify-content-between gap-3",
          children: [/*#__PURE__*/_jsx("div", {
            className: "d-flex align-items-center gap-2 flex-grow-1",
            style: {
              maxWidth: 420
            },
            children: /*#__PURE__*/_jsxs("div", {
              className: "input-group input-group-sm",
              children: [/*#__PURE__*/_jsx("span", {
                className: "input-group-text bg-light",
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-search"
                })
              }), /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control",
                placeholder: "Search by name, phone, dept, staff ID...",
                value: creditSearch,
                onChange: e => setCreditSearch(e.target.value)
              })]
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "d-flex align-items-center gap-2",
            children: /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-primary px-3 py-1.5 fw-bold shadow-sm d-flex align-items-center gap-1.5",
              onClick: () => setShowAddAccountModal(true),
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-person-plus-fill"
              }), " New Credit Account"]
            })
          })]
        })
      }), /*#__PURE__*/_jsx("div", {
        className: "counter-card p-0 overflow-hidden shadow-sm",
        children: /*#__PURE__*/_jsx("div", {
          className: "table-responsive",
          children: /*#__PURE__*/_jsxs("table", {
            className: "table table-pos mb-0",
            children: [/*#__PURE__*/_jsx("thead", {
              children: /*#__PURE__*/_jsxs("tr", {
                children: [/*#__PURE__*/_jsx("th", {
                  children: "Account / Person Name"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Account Type"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Dept / Staff ID"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Phone"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Credit Limit"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Current Balance"
                }), /*#__PURE__*/_jsx("th", {
                  children: "Status"
                }), /*#__PURE__*/_jsx("th", {
                  className: "text-end",
                  children: "Actions"
                })]
              })
            }), /*#__PURE__*/_jsx("tbody", {
              children: loadingCredit ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsx("td", {
                  colSpan: "8",
                  className: "text-center py-4 text-muted",
                  children: "Loading credit accounts..."
                })
              }) : filteredCreditAccounts.length === 0 ? /*#__PURE__*/_jsx("tr", {
                children: /*#__PURE__*/_jsx("td", {
                  colSpan: "8",
                  className: "text-center py-4 text-muted",
                  children: "No credit accounts found. Click \"New Credit Account\" to create one."
                })
              }) : filteredCreditAccounts.map(acc => {
                const typeColor = acc.account_type === 'staff' ? 'primary' : acc.account_type === 'doctor' ? 'success' : acc.account_type === 'corporate' ? 'dark' : 'warning';
                const bal = Number(acc.current_balance || 0);
                return /*#__PURE__*/_jsxs("tr", {
                  children: [/*#__PURE__*/_jsxs("td", {
                    className: "fw-bold text-dark",
                    children: [acc.account_name, acc.remarks && /*#__PURE__*/_jsx("small", {
                      className: "d-block text-muted",
                      children: acc.remarks
                    })]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: `badge bg-${typeColor}-subtle text-${typeColor} border border-${typeColor} text-uppercase px-2`,
                      style: {
                        fontSize: '0.65rem'
                      },
                      children: acc.account_type
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: acc.department || acc.staff_id || '-'
                  }), /*#__PURE__*/_jsx("td", {
                    children: acc.phone || '-'
                  }), /*#__PURE__*/_jsxs("td", {
                    className: "fw-bold",
                    children: ["₹", Number(acc.credit_limit || 0).toLocaleString('en-IN')]
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsxs("span", {
                      className: `fw-bold fs-6 ${bal > 0 ? 'text-danger' : 'text-success'}`,
                      children: ["₹", bal.toLocaleString('en-IN', {
                        minimumFractionDigits: 2
                      })]
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    children: /*#__PURE__*/_jsx("span", {
                      className: `badge ${acc.is_active == 1 ? 'bg-success' : 'bg-secondary'}`,
                      children: acc.is_active == 1 ? 'ACTIVE' : 'INACTIVE'
                    })
                  }), /*#__PURE__*/_jsx("td", {
                    className: "text-end",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "btn-group btn-group-sm",
                      children: [/*#__PURE__*/_jsxs("button", {
                        type: "button",
                        className: "btn btn-outline-primary py-0.5 px-2",
                        style: {
                          fontSize: '0.75rem'
                        },
                        onClick: () => openAccountLedger(acc),
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-journal-text me-1"
                        }), " Ledger"]
                      }), /*#__PURE__*/_jsxs("button", {
                        type: "button",
                        className: "btn btn-outline-success py-0.5 px-2 fw-bold",
                        style: {
                          fontSize: '0.75rem'
                        },
                        onClick: () => openSettlePaymentModal(acc),
                        children: [/*#__PURE__*/_jsx("i", {
                          className: "bi bi-cash-coin me-1"
                        }), " Settle"]
                      })]
                    })
                  })]
                }, acc.account_id);
              })
            })]
          })
        })
      }), showAddAccountModal && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        tabIndex: "-1",
        style: {
          backgroundColor: 'rgba(15, 23, 42, 0.7)'
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered",
          children: /*#__PURE__*/_jsx("div", {
            className: "modal-content shadow-lg border-0",
            children: /*#__PURE__*/_jsxs("form", {
              onSubmit: handleSaveCreditAccount,
              children: [/*#__PURE__*/_jsxs("div", {
                className: "modal-header bg-dark text-white py-2.5 px-3",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "modal-title fw-bold mb-0",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-person-plus-fill text-primary me-2"
                  }), " Create Credit Account"]
                }), /*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn-close btn-close-white",
                  onClick: () => setShowAddAccountModal(false)
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "modal-body p-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "row g-3 mb-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Account Type"
                    }), /*#__PURE__*/_jsxs("select", {
                      className: "form-select form-select-sm",
                      value: accFormType,
                      onChange: e => setAccFormType(e.target.value),
                      children: [/*#__PURE__*/_jsx("option", {
                        value: "staff",
                        children: "Staff Member"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "doctor",
                        children: "Consultant / Doctor"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "corporate",
                        children: "Hospital Trust / Corporate"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "customer",
                        children: "Regular Customer / VIP"
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Credit Limit (₹)"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "number",
                      className: "form-control form-control-sm",
                      value: accFormLimit,
                      onChange: e => setAccFormLimit(e.target.value),
                      placeholder: "5000"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "mb-2",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label small fw-bold mb-1",
                    children: ["Person / Organization Name ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    required: true,
                    value: accFormName,
                    onChange: e => setAccFormName(e.target.value),
                    placeholder: "e.g. Dr. Rajesh Sharma / Sister Sunita"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-3 mb-2",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Phone Number"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      value: accFormPhone,
                      onChange: e => setAccFormPhone(e.target.value),
                      placeholder: "e.g. 9876543210"
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Staff ID / Employee Code"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      value: accFormStaffId,
                      onChange: e => setAccFormStaffId(e.target.value),
                      placeholder: "e.g. EMP-1042"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "mb-2",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Department"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: accFormDept,
                    onChange: e => setAccFormDept(e.target.value),
                    placeholder: "e.g. ICU Nursing / General Surgery / Administration"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "mb-2",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Remarks / Terms"
                  }), /*#__PURE__*/_jsx("textarea", {
                    className: "form-control form-control-sm",
                    rows: "2",
                    value: accFormRemarks,
                    onChange: e => setAccFormRemarks(e.target.value),
                    placeholder: "e.g. Deducted monthly from hospital payroll or settled at month end"
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "modal-footer py-2 px-3 bg-light",
                children: [/*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn btn-sm btn-outline-secondary",
                  onClick: () => setShowAddAccountModal(false),
                  children: "Cancel"
                }), /*#__PURE__*/_jsx("button", {
                  type: "submit",
                  className: "btn btn-sm btn-primary fw-bold",
                  disabled: isSavingAccount,
                  children: isSavingAccount ? 'Creating...' : 'Create Account'
                })]
              })]
            })
          })
        })
      }), selectedCreditAcc && creditLedgerData && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        tabIndex: "-1",
        style: {
          backgroundColor: 'rgba(15, 23, 42, 0.7)'
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered modal-xl",
          children: /*#__PURE__*/_jsxs("div", {
            className: "modal-content shadow-lg border-0",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-dark text-white py-2.5 px-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-journal-text text-primary fs-5"
                }), /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsxs("h6", {
                    className: "modal-title fw-bold mb-0",
                    children: [selectedCreditAcc.account_name, " — Credit Statement"]
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-muted",
                    children: [selectedCreditAcc.account_type.toUpperCase(), " • ", selectedCreditAcc.department || 'General']
                  })]
                })]
              }), /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn-close btn-close-white",
                onClick: () => {
                  setSelectedCreditAcc(null);
                  setCreditLedgerData(null);
                }
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsx("div", {
                className: "card p-3 mb-3 bg-light border",
                children: /*#__PURE__*/_jsxs("div", {
                  className: "row g-2 align-items-center",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("small", {
                      className: "text-muted fw-bold",
                      children: "CREDIT LIMIT"
                    }), /*#__PURE__*/_jsxs("h5", {
                      className: "fw-bold mb-0",
                      children: ["₹", Number(selectedCreditAcc.credit_limit || 0).toLocaleString('en-IN')]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("small", {
                      className: "text-danger fw-bold",
                      children: "OUTSTANDING BALANCE"
                    }), /*#__PURE__*/_jsxs("h4", {
                      className: "fw-bold text-danger mb-0",
                      children: ["₹", Number(selectedCreditAcc.current_balance || 0).toLocaleString('en-IN', {
                        minimumFractionDigits: 2
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3",
                    children: [/*#__PURE__*/_jsx("small", {
                      className: "text-success fw-bold",
                      children: "AVAILABLE CREDIT"
                    }), /*#__PURE__*/_jsxs("h5", {
                      className: "fw-bold text-success mb-0",
                      children: ["₹", Math.max(0, Number(selectedCreditAcc.credit_limit || 0) - Number(selectedCreditAcc.current_balance || 0)).toLocaleString('en-IN')]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-3 text-end",
                    children: [/*#__PURE__*/_jsxs("button", {
                      type: "button",
                      className: "btn btn-sm btn-success fw-bold px-3 py-1.5 shadow-sm me-2",
                      onClick: () => openSettlePaymentModal(selectedCreditAcc),
                      children: [/*#__PURE__*/_jsx("i", {
                        className: "bi bi-cash-stack me-1"
                      }), " Settle Payment"]
                    }), /*#__PURE__*/_jsx("button", {
                      type: "button",
                      className: "btn btn-sm btn-outline-dark px-2.5 py-1.5",
                      onClick: printAccountLedger,
                      title: "Print Account Statement",
                      children: /*#__PURE__*/_jsx("i", {
                        className: "bi bi-printer"
                      })
                    })]
                  })]
                })
              }), /*#__PURE__*/_jsx("div", {
                className: "table-responsive",
                style: {
                  maxHeight: 380,
                  overflowY: 'auto'
                },
                children: /*#__PURE__*/_jsxs("table", {
                  className: "table table-pos table-bordered mb-0",
                  children: [/*#__PURE__*/_jsx("thead", {
                    className: "table-dark sticky-top",
                    children: /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("th", {
                        children: "Timestamp"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Type"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Invoice / Receipt Ref"
                      }), /*#__PURE__*/_jsx("th", {
                        className: "text-end",
                        children: "Debit (Bills)"
                      }), /*#__PURE__*/_jsx("th", {
                        className: "text-end",
                        children: "Credit (Payments)"
                      }), /*#__PURE__*/_jsx("th", {
                        className: "text-end",
                        children: "Running Balance"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Mode"
                      }), /*#__PURE__*/_jsx("th", {
                        children: "Recorded By / Notes"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("tbody", {
                    children: creditLedgerData.entries.length === 0 ? /*#__PURE__*/_jsx("tr", {
                      children: /*#__PURE__*/_jsx("td", {
                        colSpan: "8",
                        className: "text-center py-4 text-muted",
                        children: "No transactions on this account yet."
                      })
                    }) : creditLedgerData.entries.map(ent => /*#__PURE__*/_jsxs("tr", {
                      children: [/*#__PURE__*/_jsx("td", {
                        children: ent.created_at
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("span", {
                          className: `badge ${ent.entry_type === 'DEBIT' ? 'bg-danger' : 'bg-success'}`,
                          children: ent.entry_type
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("strong", {
                          children: ent.invoice_no || ent.payment_ref || '-'
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        className: "text-end fw-bold text-danger",
                        children: Number(ent.debit_amount) > 0 ? '₹' + Number(ent.debit_amount).toFixed(2) : '-'
                      }), /*#__PURE__*/_jsx("td", {
                        className: "text-end fw-bold text-success",
                        children: Number(ent.credit_amount) > 0 ? '₹' + Number(ent.credit_amount).toFixed(2) : '-'
                      }), /*#__PURE__*/_jsxs("td", {
                        className: "text-end fw-bold",
                        children: ["₹", Number(ent.balance_after).toFixed(2)]
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsx("span", {
                          className: "badge bg-secondary",
                          children: ent.payment_mode
                        })
                      }), /*#__PURE__*/_jsx("td", {
                        children: /*#__PURE__*/_jsxs("small", {
                          className: "text-muted",
                          children: [ent.remarks || 'Bill Charge', " (", ent.recorded_by_name || 'System', ")"]
                        })
                      })]
                    }, ent.ledger_id))
                  })]
                })
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "modal-footer py-2 px-3 bg-light",
              children: /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-outline-secondary",
                onClick: () => {
                  setSelectedCreditAcc(null);
                  setCreditLedgerData(null);
                },
                children: "Close Statement"
              })
            })]
          })
        })
      }), showPaymentModal && selectedCreditAcc && /*#__PURE__*/_jsx("div", {
        className: "modal show d-block",
        tabIndex: "-1",
        style: {
          backgroundColor: 'rgba(15, 23, 42, 0.7)'
        },
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-dialog modal-dialog-centered",
          children: /*#__PURE__*/_jsx("div", {
            className: "modal-content shadow-lg border-0",
            children: /*#__PURE__*/_jsxs("form", {
              onSubmit: handleRecordPayment,
              children: [/*#__PURE__*/_jsxs("div", {
                className: "modal-header bg-dark text-white py-2.5 px-3",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "modal-title fw-bold mb-0",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-cash-coin text-success me-2"
                  }), " Record Credit Settlement Payment"]
                }), /*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn-close btn-close-white",
                  onClick: () => setShowPaymentModal(false)
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "modal-body p-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "card bg-light border p-2.5 mb-3",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between mb-1",
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "text-muted",
                      children: "Account:"
                    }), /*#__PURE__*/_jsx("span", {
                      className: "fw-bold text-dark",
                      children: selectedCreditAcc.account_name
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "d-flex justify-content-between",
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "text-muted",
                      children: "Total Outstanding Due:"
                    }), /*#__PURE__*/_jsxs("span", {
                      className: "fw-bold text-danger fs-6",
                      children: ["₹", Number(selectedCreditAcc.current_balance || 0).toFixed(2)]
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "mb-3",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label small fw-bold mb-1",
                    children: ["Settlement Amount Received (₹) ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "input-group input-group-sm",
                    children: [/*#__PURE__*/_jsx("span", {
                      className: "input-group-text bg-light fw-bold",
                      children: "₹"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "number",
                      step: "0.01",
                      className: "form-control form-control-sm fs-6 fw-bold text-success",
                      required: true,
                      value: payAmount,
                      onChange: e => setPayAmount(e.target.value),
                      placeholder: "Enter amount"
                    }), /*#__PURE__*/_jsx("button", {
                      type: "button",
                      className: "btn btn-outline-secondary",
                      onClick: () => setPayAmount(String(selectedCreditAcc.current_balance)),
                      children: "Full Pay"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2 mb-3",
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Payment Method"
                    }), /*#__PURE__*/_jsxs("select", {
                      className: "form-select form-select-sm",
                      value: payMode,
                      onChange: e => setPayMode(e.target.value),
                      children: [/*#__PURE__*/_jsx("option", {
                        value: "Cash",
                        children: "Cash in Drawer"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "UPI",
                        children: "UPI / QR Code"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "Bank",
                        children: "Direct Bank Transfer / NEFT"
                      }), /*#__PURE__*/_jsx("option", {
                        value: "Salary_Deduction",
                        children: "Payroll / Salary Deduction"
                      })]
                    })]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "col-md-6",
                    children: [/*#__PURE__*/_jsx("label", {
                      className: "form-label small fw-bold mb-1",
                      children: "Receipt / UTR / Ref No"
                    }), /*#__PURE__*/_jsx("input", {
                      type: "text",
                      className: "form-control form-control-sm",
                      value: payRef,
                      onChange: e => setPayRef(e.target.value),
                      placeholder: "e.g. UTR-991823"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "mb-2",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Remarks / Receipt Note"
                  }), /*#__PURE__*/_jsx("textarea", {
                    className: "form-control form-control-sm",
                    rows: "2",
                    value: payRemarks,
                    onChange: e => setPayRemarks(e.target.value),
                    placeholder: "e.g. Cash received at pharmacy counter by cashier"
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "modal-footer py-2 px-3 bg-light",
                children: [/*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn btn-sm btn-outline-secondary",
                  onClick: () => setShowPaymentModal(false),
                  children: "Cancel"
                }), /*#__PURE__*/_jsx("button", {
                  type: "submit",
                  className: "btn btn-sm btn-success fw-bold",
                  disabled: isRecordingPayment,
                  children: isRecordingPayment ? 'Recording...' : 'Confirm Payment & Update Balance'
                })]
              })]
            })
          })
        })
      })]
    })]
  });
}
function StaffManagementView({
  store,
  notify,
  activeOperator,
  onOperatorUpdated,
  onSelectOperator
}) {
  if (!store) return null;
  const [staffList, setStaffList] = useState([]);
  const [loading, setLoading] = useState(false);

  // Modal state for Add/Edit Staff
  const [showEditModal, setShowEditModal] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [formFullName, setFormFullName] = useState('');
  const [formUsername, setFormUsername] = useState('');
  const [formPinCode, setFormPinCode] = useState('');
  const [formRole, setFormRole] = useState('pharmacist');
  const [formPhone, setFormPhone] = useState('');
  const [formRegNo, setFormRegNo] = useState('');
  const [formHprId, setFormHprId] = useState('');
  const [formIsDefault, setFormIsDefault] = useState(false);
  const [formIsActive, setFormIsActive] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  // 6 Granular Permissions Matrix
  const defaultPermissions = {
    admin: false,
    sale: true,
    purchase: false,
    sale_edit_after_1h: false,
    purchase_edit_verified: false,
    bank_upi_audit: false
  };
  const [formPermissions, setFormPermissions] = useState(defaultPermissions);
  const applyRoleDefaults = role => {
    setFormRole(role);
    if (role === 'store_manager') {
      setFormPermissions({
        admin: true,
        sale: true,
        purchase: true,
        sale_edit_after_1h: true,
        purchase_edit_verified: true,
        bank_upi_audit: true
      });
    } else if (role === 'pharmacist') {
      setFormPermissions({
        admin: false,
        sale: true,
        purchase: true,
        sale_edit_after_1h: false,
        purchase_edit_verified: false,
        bank_upi_audit: false
      });
    } else if (role === 'cashier') {
      setFormPermissions({
        admin: false,
        sale: true,
        purchase: false,
        sale_edit_after_1h: false,
        purchase_edit_verified: false,
        bank_upi_audit: false
      });
    } else if (role === 'auditor') {
      setFormPermissions({
        admin: false,
        sale: false,
        purchase: false,
        sale_edit_after_1h: false,
        purchase_edit_verified: false,
        bank_upi_audit: true
      });
    }
  };
  useEffect(() => {
    if (store?.store_id) {
      loadStaff();
    }
  }, [store?.store_id]);
  const loadStaff = async () => {
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}users?store_id=${store.store_id}`);
      const data = await res.json();
      if (data.status) {
        setStaffList(data.users || []);
      }
    } catch (e) {
      console.error("Failed to load staff:", e);
    } finally {
      setLoading(false);
    }
  };
  const openAddModal = () => {
    setEditingUser(null);
    setFormFullName('');
    setFormUsername('');
    setFormPinCode('1234');
    applyRoleDefaults('pharmacist');
    setFormPhone('');
    setFormRegNo('');
    setFormHprId('');
    setFormIsDefault(false);
    setFormIsActive(true);
    setShowEditModal(true);
  };
  const openEditModal = user => {
    setEditingUser(user);
    setFormFullName(user.full_name || '');
    setFormUsername(user.username || '');
    setFormPinCode(''); // Keep empty unless resetting
    setFormRole(user.user_role || 'pharmacist');
    setFormPhone(user.phone || '');
    setFormRegNo(user.pharmacist_reg_no || '');
    setFormHprId(user.pharmacist_hpr_id || '');
    setFormIsDefault(user.is_default == 1);
    setFormIsActive(user.is_active == 1);

    // Parse permissions
    let perms = {
      ...defaultPermissions
    };
    if (user.permissions) {
      try {
        const parsed = typeof user.permissions === 'string' ? JSON.parse(user.permissions) : user.permissions;
        perms = {
          ...defaultPermissions,
          ...parsed
        };
      } catch (e) {
        console.warn('Error parsing user permissions:', e);
      }
    }
    setFormPermissions(perms);
    setShowEditModal(true);
  };
  const togglePermission = key => {
    setFormPermissions(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };
  const handleSaveStaff = async e => {
    e.preventDefault();
    if (!formFullName.trim() || !formUsername.trim()) {
      notify('Full Name and Username are required.', 'error');
      return;
    }
    setIsSaving(true);
    try {
      const payload = {
        user_id: editingUser ? editingUser.user_id : 0,
        store_id: store.store_id,
        full_name: formFullName.trim(),
        username: formUsername.trim(),
        user_role: formRole,
        phone: formPhone.trim(),
        pharmacist_reg_no: formRegNo.trim(),
        pharmacist_hpr_id: formHprId.trim(),
        is_default: formIsDefault ? 1 : 0,
        is_active: formIsActive ? 1 : 0,
        permissions: JSON.stringify(formPermissions)
      };
      if (formPinCode.trim()) {
        payload.pin_code = formPinCode.trim();
      }
      const res = await fetch(`${API_BASE}users/save`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status) {
        notify('Staff profile and permission matrix saved successfully.', 'success');
        setShowEditModal(false);
        loadStaff();
        if (onOperatorUpdated) onOperatorUpdated();
      } else {
        notify(data.message || 'Failed to save staff profile.', 'error');
      }
    } catch (err) {
      notify('Network error saving staff profile.', 'error');
    } finally {
      setIsSaving(false);
    }
  };
  return /*#__PURE__*/_jsxs("div", {
    children: [/*#__PURE__*/_jsxs("div", {
      className: "d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom",
      children: [/*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("h5", {
          className: "fw-bolder text-dark mb-1 d-flex align-items-center gap-2",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-shield-lock-fill text-primary"
          }), " Medical Store Staff & 6-Tier Permission Matrix"]
        }), /*#__PURE__*/_jsxs("p", {
          className: "text-muted small mb-0",
          children: ["Manage pharmacy staff credentials, cashier PINs, and granular audit/sale/purchase rights for ", /*#__PURE__*/_jsx("strong", {
            children: store.store_name
          })]
        })]
      }), /*#__PURE__*/_jsx("div", {
        className: "d-flex align-items-center gap-2",
        children: /*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-sm btn-primary px-3 py-2 fw-bold shadow-sm d-flex align-items-center gap-1.5",
          onClick: openAddModal,
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-person-plus-fill"
          }), " Add Staff Member"]
        })
      })]
    }), /*#__PURE__*/_jsx("div", {
      className: "row g-3",
      children: loading ? /*#__PURE__*/_jsxs("div", {
        className: "col-12 text-center py-5 text-muted",
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-hourglass-split fs-3 d-block mb-2 text-primary"
        }), " Loading staff profiles..."]
      }) : staffList.length === 0 ? /*#__PURE__*/_jsxs("div", {
        className: "col-12 text-center py-5 text-muted",
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-people fs-3 d-block mb-1 text-secondary"
        }), "No staff members assigned to this store yet. Click \"Add Staff Member\" to get started."]
      }) : staffList.map(user => {
        const isCurrent = activeOperator?.user_id == user.user_id;
        const roleColor = user.user_role === 'store_manager' ? 'primary' : user.user_role === 'pharmacist' ? 'success' : user.user_role === 'auditor' ? 'info' : user.user_role === 'cashier' ? 'warning' : 'secondary';
        let perms = {};
        try {
          perms = typeof user.permissions === 'string' ? JSON.parse(user.permissions || '{}') : user.permissions || {};
        } catch (e) {
          perms = {};
        }
        return /*#__PURE__*/_jsx("div", {
          className: "col-md-6 col-lg-4",
          children: /*#__PURE__*/_jsxs("div", {
            className: `counter-card p-3 h-100 position-relative border-start border-4 border-${roleColor} shadow-sm`,
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-start justify-content-between gap-2 mb-2",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "d-flex align-items-center gap-2",
                children: [/*#__PURE__*/_jsx("div", {
                  className: `rounded-circle bg-${roleColor}-subtle text-${roleColor} fw-bold d-flex align-items-center justify-content-center`,
                  style: {
                    width: 42,
                    height: 42,
                    fontSize: '1rem'
                  },
                  children: user.full_name ? user.full_name[0] : 'U'
                }), /*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("h6", {
                    className: "fw-bold text-dark mb-0",
                    children: user.full_name
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-muted",
                    children: ["@", user.username]
                  })]
                })]
              }), /*#__PURE__*/_jsx("span", {
                className: `badge bg-${roleColor}-subtle text-${roleColor} border border-${roleColor} text-uppercase px-2 py-1`,
                style: {
                  fontSize: '0.65rem'
                },
                children: user.user_role ? user.user_role.replace(/_/g, ' ') : 'STAFF'
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "small text-muted mb-2.5 d-flex flex-column gap-1",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-telephone me-1.5 text-secondary"
                }), " ", user.phone || 'No phone set']
              }), user.pharmacist_reg_no ? /*#__PURE__*/_jsxs("div", {
                className: "text-truncate",
                title: user.pharmacist_reg_no,
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-file-earmark-medical me-1.5 text-success"
                }), " Reg: ", /*#__PURE__*/_jsx("strong", {
                  children: user.pharmacist_reg_no
                })]
              }) : null]
            }), /*#__PURE__*/_jsxs("div", {
              className: "p-2 bg-light rounded-2 mb-3 border",
              children: [/*#__PURE__*/_jsx("div", {
                className: "text-muted fw-bold mb-1",
                style: {
                  fontSize: '0.68rem',
                  textTransform: 'uppercase'
                },
                children: "Assigned Rights"
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex flex-wrap gap-1",
                children: [/*#__PURE__*/_jsx("span", {
                  className: `badge ${perms.admin ? 'bg-danger text-white' : 'bg-secondary-subtle text-muted text-decoration-line-through'}`,
                  style: {
                    fontSize: '0.62rem'
                  },
                  children: "Admin"
                }), /*#__PURE__*/_jsx("span", {
                  className: `badge ${perms.sale ? 'bg-primary text-white' : 'bg-secondary-subtle text-muted text-decoration-line-through'}`,
                  style: {
                    fontSize: '0.62rem'
                  },
                  children: "Sale"
                }), /*#__PURE__*/_jsx("span", {
                  className: `badge ${perms.purchase ? 'bg-success text-white' : 'bg-secondary-subtle text-muted text-decoration-line-through'}`,
                  style: {
                    fontSize: '0.62rem'
                  },
                  children: "Purchase"
                }), /*#__PURE__*/_jsx("span", {
                  className: `badge ${perms.sale_edit_after_1h ? 'bg-warning text-dark' : 'bg-secondary-subtle text-muted text-decoration-line-through'}`,
                  style: {
                    fontSize: '0.62rem'
                  },
                  title: "Can edit sale bill after 1 hour",
                  children: "Sale Edit >1h"
                }), /*#__PURE__*/_jsx("span", {
                  className: `badge ${perms.purchase_edit_verified ? 'bg-warning text-dark' : 'bg-secondary-subtle text-muted text-decoration-line-through'}`,
                  style: {
                    fontSize: '0.62rem'
                  },
                  title: "Can edit verified purchase challan",
                  children: "Purch Edit Verif"
                }), /*#__PURE__*/_jsx("span", {
                  className: `badge ${perms.bank_upi_audit ? 'bg-info text-dark' : 'bg-secondary-subtle text-muted text-decoration-line-through'}`,
                  style: {
                    fontSize: '0.62rem'
                  },
                  children: "Bank Audit"
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center justify-content-between pt-2 border-top",
              children: [/*#__PURE__*/_jsx("div", {
                children: isCurrent ? /*#__PURE__*/_jsxs("span", {
                  className: "badge bg-success-subtle text-success border border-success px-2 py-1",
                  style: {
                    fontSize: '0.7rem'
                  },
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-person-check-fill me-1"
                  }), " Active Cashier"]
                }) : /*#__PURE__*/_jsxs("button", {
                  type: "button",
                  className: "btn btn-sm btn-outline-secondary py-0.5 px-2",
                  style: {
                    fontSize: '0.72rem'
                  },
                  onClick: () => onSelectOperator && onSelectOperator(user),
                  title: "Switch active counter login to this user",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-arrow-left-right me-1"
                  }), " Switch To"]
                })
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-sm btn-outline-primary py-0.5 px-2",
                style: {
                  fontSize: '0.72rem'
                },
                onClick: () => openEditModal(user),
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-pencil-square me-1"
                }), " Edit & Rights"]
              })]
            })]
          })
        }, user.user_id);
      })
    }), showEditModal && /*#__PURE__*/_jsx("div", {
      className: "modal show d-block",
      tabIndex: "-1",
      style: {
        backgroundColor: 'rgba(15, 23, 42, 0.7)'
      },
      children: /*#__PURE__*/_jsx("div", {
        className: "modal-dialog modal-dialog-centered modal-lg",
        children: /*#__PURE__*/_jsx("div", {
          className: "modal-content shadow-lg border-0",
          children: /*#__PURE__*/_jsxs("form", {
            onSubmit: handleSaveStaff,
            children: [/*#__PURE__*/_jsxs("div", {
              className: "modal-header bg-dark text-white py-2.5 px-3",
              children: [/*#__PURE__*/_jsxs("h6", {
                className: "modal-title fw-bold mb-0",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-person-badge-fill text-primary me-2"
                }), editingUser ? 'Edit Staff Profile & Permissions' : 'Register New Staff Member']
              }), /*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn-close btn-close-white",
                onClick: () => setShowEditModal(false)
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-body p-3",
              children: [/*#__PURE__*/_jsxs("div", {
                className: "row g-3 mb-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label small fw-bold mb-1",
                    children: ["Full Name ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    required: true,
                    value: formFullName,
                    onChange: e => setFormFullName(e.target.value),
                    placeholder: "e.g. Rahul Sharma"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsxs("label", {
                    className: "form-label small fw-bold mb-1",
                    children: ["Username (Login ID) ", /*#__PURE__*/_jsx("span", {
                      className: "text-danger",
                      children: "*"
                    })]
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    required: true,
                    value: formUsername,
                    onChange: e => setFormUsername(e.target.value),
                    placeholder: "e.g. rahul_pharma"
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-3 mb-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Role"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm",
                    value: formRole,
                    onChange: e => applyRoleDefaults(e.target.value),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "store_manager",
                      children: "Store Manager (Admin)"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "pharmacist",
                      children: "Pharmacist"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "cashier",
                      children: "Cashier"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "auditor",
                      children: "Auditor / Accountant"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: editingUser ? 'Reset Quick PIN (4 Digits)' : 'Quick Login PIN (4 Digits)'
                  }), /*#__PURE__*/_jsx("input", {
                    type: "password",
                    maxLength: "6",
                    className: "form-control form-control-sm",
                    value: formPinCode,
                    onChange: e => setFormPinCode(e.target.value),
                    placeholder: editingUser ? 'Leave blank to keep current PIN' : 'e.g. 1234'
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-4",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Phone Number"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: formPhone,
                    onChange: e => setFormPhone(e.target.value),
                    placeholder: "e.g. 9876543210"
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "row g-3 mb-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "Pharmacy State Reg No"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: formRegNo,
                    onChange: e => setFormRegNo(e.target.value),
                    placeholder: "e.g. PB-PH-99214"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "col-md-6",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label small fw-bold mb-1",
                    children: "ABDM Healthcare Professional Registry (HPR) ID"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm",
                    value: formHprId,
                    onChange: e => setFormHprId(e.target.value),
                    placeholder: "e.g. rahul@hpr.abdm"
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "card bg-light border p-3 mb-3 rounded-2",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "d-flex align-items-center justify-content-between mb-2",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "fw-bold small text-dark",
                    children: [/*#__PURE__*/_jsx("i", {
                      className: "bi bi-shield-check text-success me-1"
                    }), " Granular Access & Audit Permissions"]
                  }), /*#__PURE__*/_jsx("span", {
                    className: "badge bg-secondary-subtle text-secondary",
                    style: {
                      fontSize: '0.65rem'
                    },
                    children: "Audit Compliant"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "row g-2",
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "col-md-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "form-check form-switch",
                      children: [/*#__PURE__*/_jsx("input", {
                        className: "form-check-input",
                        type: "checkbox",
                        id: "permAdmin",
                        checked: formPermissions.admin || false,
                        onChange: () => togglePermission('admin')
                      }), /*#__PURE__*/_jsx("label", {
                        className: "form-check-label small fw-semibold",
                        htmlFor: "permAdmin",
                        children: "1. Admin Control"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "Store config & user management"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-md-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "form-check form-switch",
                      children: [/*#__PURE__*/_jsx("input", {
                        className: "form-check-input",
                        type: "checkbox",
                        id: "permSale",
                        checked: formPermissions.sale || false,
                        onChange: () => togglePermission('sale')
                      }), /*#__PURE__*/_jsx("label", {
                        className: "form-check-label small fw-semibold",
                        htmlFor: "permSale",
                        children: "2. Sale Billing"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "Counter POS and draft billing"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-md-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "form-check form-switch",
                      children: [/*#__PURE__*/_jsx("input", {
                        className: "form-check-input",
                        type: "checkbox",
                        id: "permPurchase",
                        checked: formPermissions.purchase || false,
                        onChange: () => togglePermission('purchase')
                      }), /*#__PURE__*/_jsx("label", {
                        className: "form-check-label small fw-semibold",
                        htmlFor: "permPurchase",
                        children: "3. Purchase Inward"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "Challans & stock additions"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-md-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "form-check form-switch",
                      children: [/*#__PURE__*/_jsx("input", {
                        className: "form-check-input",
                        type: "checkbox",
                        id: "permSaleEdit",
                        checked: formPermissions.sale_edit_after_1h || false,
                        onChange: () => togglePermission('sale_edit_after_1h')
                      }), /*#__PURE__*/_jsx("label", {
                        className: "form-check-label small fw-semibold",
                        htmlFor: "permSaleEdit",
                        children: "4. Sale Edit >1 Hour"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "Modify bills older than 60 mins"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-md-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "form-check form-switch",
                      children: [/*#__PURE__*/_jsx("input", {
                        className: "form-check-input",
                        type: "checkbox",
                        id: "permPurchEdit",
                        checked: formPermissions.purchase_edit_verified || false,
                        onChange: () => togglePermission('purchase_edit_verified')
                      }), /*#__PURE__*/_jsx("label", {
                        className: "form-check-label small fw-semibold",
                        htmlFor: "permPurchEdit",
                        children: "5. Purch Edit Verified"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "Edit purchase after verification"
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-md-4",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "form-check form-switch",
                      children: [/*#__PURE__*/_jsx("input", {
                        className: "form-check-input",
                        type: "checkbox",
                        id: "permBankAudit",
                        checked: formPermissions.bank_upi_audit || false,
                        onChange: () => togglePermission('bank_upi_audit')
                      }), /*#__PURE__*/_jsx("label", {
                        className: "form-check-label small fw-semibold",
                        htmlFor: "permBankAudit",
                        children: "6. Bank & UPI Audit"
                      }), /*#__PURE__*/_jsx("div", {
                        className: "text-muted",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "Verify statements & reconciliations"
                      })]
                    })
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "d-flex gap-4",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "form-check",
                  children: [/*#__PURE__*/_jsx("input", {
                    className: "form-check-input",
                    type: "checkbox",
                    id: "isDefCheck",
                    checked: formIsDefault,
                    onChange: e => setFormIsDefault(e.target.checked)
                  }), /*#__PURE__*/_jsx("label", {
                    className: "form-check-label small",
                    htmlFor: "isDefCheck",
                    children: "Default Cashier on store open"
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "form-check",
                  children: [/*#__PURE__*/_jsx("input", {
                    className: "form-check-input",
                    type: "checkbox",
                    id: "isActiveCheck",
                    checked: formIsActive,
                    onChange: e => setFormIsActive(e.target.checked)
                  }), /*#__PURE__*/_jsx("label", {
                    className: "form-check-label small",
                    htmlFor: "isActiveCheck",
                    children: "Account Active"
                  })]
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "modal-footer py-2 px-3 bg-light",
              children: [/*#__PURE__*/_jsx("button", {
                type: "button",
                className: "btn btn-sm btn-outline-secondary",
                onClick: () => setShowEditModal(false),
                children: "Cancel"
              }), /*#__PURE__*/_jsx("button", {
                type: "submit",
                className: "btn btn-sm btn-primary fw-bold",
                disabled: isSaving,
                children: isSaving ? 'Saving...' : editingUser ? 'Update Staff & Permissions' : 'Save Staff Member'
              })]
            })]
          })
        })
      })
    })]
  });
}

// Note: Store & Statutory Licensing Setup is managed centrally in HMS -> Admin -> Medical Store.

// =========================================================================
// 8. STAFF & OPERATOR ROLE MANAGEMENT (END)
// =========================================================================

function MobileAppView({
  store,
  stores,
  notify,
  activeOperator,
  onSwitchOperator,
  onExitMobile,
  triggerPwaInstall
}) {
  if (!store) return null;
  const [mobTab, setMobTab] = useState('dashboard'); // 'dashboard', 'collector', 'auditor', 'purchase_photo'

  // Dashboard Data
  const [dashData, setDashData] = useState(null);
  const [loadingDash, setLoadingDash] = useState(false);

  // Rack Collector (Draft Bill) State
  const [collectorItems, setCollectorItems] = useState([]);
  const [itemSearchQuery, setItemSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [selectedItem, setSelectedItem] = useState(null);
  const [selectedBatch, setSelectedBatch] = useState(null);
  const [collectQty, setCollectQty] = useState(1);
  const [collectUnit, setCollectUnit] = useState('Tablet');
  const [patientName, setPatientName] = useState('');
  const [patientMobile, setPatientMobile] = useState('');
  const [bedNo, setBedNo] = useState('');
  const [wardName, setWardName] = useState('');
  const [doctorName, setDoctorName] = useState('');
  const [isSavingDraft, setIsSavingDraft] = useState(false);

  // Stock Auditor State
  const [auditSearch, setAuditSearch] = useState('');
  const [auditResults, setAuditResults] = useState([]);
  const [auditModalItem, setAuditModalItem] = useState(null);
  const [auditCount, setAuditCount] = useState('');
  const [auditReason, setAuditReason] = useState('PHYSICAL_VARIATION');
  const [auditRemarks, setAuditRemarks] = useState('');
  const [isSubmittingAudit, setIsSubmittingAudit] = useState(false);

  // Purchase Invoice Camera State
  const [recentPurchases, setRecentPurchases] = useState([]);
  const [selectedPurchaseId, setSelectedPurchaseId] = useState('');
  const [capturedImageBase64, setCapturedImageBase64] = useState(null);
  const [isUploadingPhoto, setIsUploadingPhoto] = useState(false);
  const [previewPhotoUrl, setPreviewPhotoUrl] = useState(null);
  useEffect(() => {
    if (store?.store_id) {
      loadDashboard();
      loadRecentPurchases();
    }
  }, [store?.store_id]);
  const loadDashboard = async () => {
    setLoadingDash(true);
    try {
      const res = await fetch(API_BASE + `mobile/dashboard?store_id=${store.store_id}`);
      const data = await res.json();
      if (data.status) setDashData(data);
    } catch (e) {
      console.error("Failed to load mobile dashboard:", e);
    } finally {
      setLoadingDash(false);
    }
  };
  const loadRecentPurchases = async () => {
    try {
      const res = await fetch(API_BASE + `purchases?store_id=${store.store_id}&limit=15`);
      const data = await res.json();
      if (data.status) {
        setRecentPurchases(data.purchases || []);
        if (data.purchases && data.purchases.length > 0) {
          setSelectedPurchaseId(data.purchases[0].purchase_id);
        }
      }
    } catch (e) {
      console.error("Failed to load purchases:", e);
    }
  };

  // Medicine Search for Collector
  const handleSearchItems = async q => {
    setItemSearchQuery(q);
    if (q.trim().length < 2) {
      setSearchResults([]);
      return;
    }
    try {
      const res = await fetch(API_BASE + `items/search?store_id=${store.store_id}&q=${encodeURIComponent(q.trim())}`);
      const data = await res.json();
      if (data.status) {
        setSearchResults(data.items || []);
      }
    } catch (e) {
      console.error(e);
    }
  };
  const addItemToDraft = () => {
    if (!selectedItem || !selectedBatch) {
      notify('Please select medicine item and batch.', 'error');
      return;
    }
    const q = Number(collectQty);
    if (q <= 0) {
      notify('Quantity must be greater than 0.', 'error');
      return;
    }
    const upp = Number(selectedItem.units_per_pack || selectedBatch.units_per_pack || 1);
    const stripMrp = Number(selectedBatch.mrp || 0);
    const perUnitMrp = upp > 0 ? stripMrp / upp : stripMrp;
    const totalUnits = collectUnit === 'Strip' ? q * upp : q;
    const rate = collectUnit === 'Strip' ? stripMrp : perUnitMrp;
    const lineGross = collectUnit === 'Strip' ? q * stripMrp : q * perUnitMrp;
    const newItem = {
      item_id: selectedItem.item_id,
      item_name: selectedItem.item_name,
      generic_name: selectedItem.generic_name || '',
      unit_pack: selectedItem.unit_pack || (upp > 1 ? `Pack: ${upp} Tabs` : 'Unit'),
      units_per_pack: upp,
      drug_schedule: selectedItem.drug_schedule || '',
      hsn_code: selectedItem.hsn_code || '3004',
      gst_rate: Number(selectedBatch.gst_rate ?? selectedItem.gst_rate ?? 12.0),
      batch_id: selectedBatch.batch_id,
      batch_no: selectedBatch.batch_no,
      expiry_display: selectedBatch.expiry_display || (selectedBatch.expiry_date ? selectedBatch.expiry_date.substring(0, 7) : ''),
      expiry_date: selectedBatch.expiry_date,
      current_qty: selectedBatch.current_qty || 0,
      unit_mrp: stripMrp,
      mrp: stripMrp,
      qty: q,
      sell_unit: collectUnit,
      strip_qty: collectUnit === 'Strip' ? q : 0,
      loose_qty: collectUnit === 'Tablet' || collectUnit === 'Unit' ? q : 0,
      total_units: totalUnits,
      effective_rate: Number(rate.toFixed(2)),
      line_gross: Number(lineGross.toFixed(2))
    };
    setCollectorItems(prev => [...prev, newItem]);
    setSelectedItem(null);
    setSelectedBatch(null);
    setItemSearchQuery('');
    setSearchResults([]);
    setCollectQty(1);
    notify(`Added ${newItem.item_name} (${newItem.qty} ${collectUnit}) to draft basket`, 'success');
  };
  const handleSaveDraft = async () => {
    if (collectorItems.length === 0) {
      notify('Draft basket is empty! Add items from shelves.', 'error');
      return;
    }
    setIsSavingDraft(true);
    try {
      const res = await fetch(API_BASE + 'drafts/save', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          patient_name: patientName.trim() || 'Walk-in Customer',
          patient_mobile: patientMobile.trim(),
          bed_no: bedNo.trim(),
          ward_name: wardName.trim(),
          doctor_name: doctorName.trim(),
          collected_by_name: activeOperator ? activeOperator.full_name : 'Mobile Pharmacist',
          items: collectorItems
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(`✓ Draft Bill #${data.draft_code} saved! Cashier on counter PC can now bill it.`, 'success');
        setCollectorItems([]);
        setPatientName('');
        setPatientMobile('');
        setBedNo('');
        setWardName('');
        setDoctorName('');
      } else {
        notify(data.message || 'Failed to save draft bill.', 'error');
      }
    } catch (e) {
      notify('Network error saving draft bill: ' + e.message, 'error');
    } finally {
      setIsSavingDraft(false);
    }
  };

  // Auditor Search
  const handleSearchAudit = async q => {
    setAuditSearch(q);
    if (q.trim().length < 2) {
      setAuditResults([]);
      return;
    }
    try {
      const res = await fetch(API_BASE + `stock/list?store_id=${store.store_id}&limit=20&filter=all`);
      const data = await res.json();
      if (data.status && data.stock) {
        const filtered = data.stock.filter(s => s.item_name.toLowerCase().includes(q.toLowerCase()) || s.generic_name && s.generic_name.toLowerCase().includes(q.toLowerCase()) || s.batch_no.toLowerCase().includes(q.toLowerCase()) || s.shelf_no && s.shelf_no.toLowerCase().includes(q.toLowerCase()));
        setAuditResults(filtered);
      }
    } catch (e) {
      console.error(e);
    }
  };
  const handleMobileReconcile = async () => {
    if (!auditModalItem) return;
    const pQty = Number(auditCount);
    if (isNaN(pQty) || pQty < 0) {
      notify('Please enter valid physical count (>=0).', 'error');
      return;
    }
    setIsSubmittingAudit(true);
    try {
      const res = await fetch(API_BASE + 'stock/reconcile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          store_id: store.store_id,
          item_id: auditModalItem.item_id,
          batch_id: auditModalItem.batch_id,
          physical_qty: pQty,
          audit_type: auditReason,
          remarks: auditRemarks.trim() || `Mobile Shelf Audit by ${activeOperator?.full_name || 'Staff'}`,
          operator_id: activeOperator?.user_id || 1,
          operator_name: activeOperator?.full_name || 'Mobile Auditor'
        })
      });
      const data = await res.json();
      if (data.status) {
        notify(`Reconciled ${auditModalItem.item_name}! System ${auditModalItem.current_qty} -> Shelf ${pQty}`, 'success');
        setAuditModalItem(null);
        handleSearchAudit(auditSearch);
      } else {
        notify(data.message || 'Failed to reconcile.', 'error');
      }
    } catch (e) {
      notify('Network error.', 'error');
    } finally {
      setIsSubmittingAudit(false);
    }
  };

  // Purchase Invoice Photo Capture
  const handleCaptureFile = e => {
    const file = e.target.files && e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = evt => {
        setCapturedImageBase64(evt.target.result);
      };
      reader.readAsDataURL(file);
    }
  };
  const handleUploadPhoto = async () => {
    if (!selectedPurchaseId || !capturedImageBase64) {
      notify('Please select an inward purchase and take/select a photo.', 'error');
      return;
    }
    setIsUploadingPhoto(true);
    try {
      const res = await fetch(API_BASE + 'purchase/upload-photo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          purchase_id: Number(selectedPurchaseId),
          image_data: capturedImageBase64
        })
      });
      const data = await res.json();
      if (data.status) {
        notify('Purchase paper invoice photo attached successfully!', 'success');
        setCapturedImageBase64(null);
        loadRecentPurchases();
      } else {
        notify(data.message || 'Failed to attach photo.', 'error');
      }
    } catch (e) {
      notify('Network error uploading photo: ' + e.message, 'error');
    } finally {
      setIsUploadingPhoto(false);
    }
  };
  return /*#__PURE__*/_jsxs("div", {
    className: "mobile-app-wrapper",
    style: {
      background: '#0b1329',
      minHeight: '100vh',
      color: '#f8fafc',
      paddingBottom: '75px'
    },
    children: [/*#__PURE__*/_jsxs("div", {
      className: "p-3 border-bottom border-slate-800 d-flex align-items-center justify-content-between sticky-top",
      style: {
        background: '#0f172a',
        zIndex: 1020
      },
      children: [/*#__PURE__*/_jsxs("div", {
        className: "d-flex align-items-center gap-2",
        children: [/*#__PURE__*/_jsx("div", {
          className: "p-2 rounded-3 bg-primary text-white d-flex align-items-center justify-content-center",
          style: {
            width: 34,
            height: 34
          },
          children: /*#__PURE__*/_jsx("i", {
            className: "bi bi-capsule fs-6"
          })
        }), /*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsx("h6", {
            className: "mb-0 fw-bold text-white fs-6 lh-1",
            children: store.store_name
          }), /*#__PURE__*/_jsxs("small", {
            className: "text-cyan-400",
            style: {
              fontSize: '0.68rem',
              color: '#38bdf8'
            },
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-person-fill me-0.5"
            }), " ", activeOperator ? activeOperator.full_name : 'Staff']
          })]
        })]
      }), /*#__PURE__*/_jsxs("div", {
        className: "d-flex align-items-center gap-1.5",
        children: [/*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-xs btn-outline-info text-white rounded-pill px-2 py-1",
          onClick: onSwitchOperator,
          title: "Switch Operator",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-person-badge"
          }), " PIN"]
        }), /*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-xs btn-outline-success rounded-pill px-2 py-1",
          onClick: triggerPwaInstall,
          title: "Install PWA on Phone",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-phone"
          }), " Install"]
        }), /*#__PURE__*/_jsxs("button", {
          type: "button",
          className: "btn btn-xs btn-outline-secondary text-white rounded-pill px-2 py-1",
          onClick: onExitMobile,
          title: "Switch to Desktop Mode",
          children: [/*#__PURE__*/_jsx("i", {
            className: "bi bi-laptop"
          }), " Desktop"]
        })]
      })]
    }), /*#__PURE__*/_jsxs("div", {
      className: "p-3",
      children: [mobTab === 'dashboard' && /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center justify-content-between mb-3",
          children: [/*#__PURE__*/_jsxs("h6", {
            className: "fw-bold mb-0 text-white d-flex align-items-center gap-1.5",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-speedometer2 text-primary"
            }), " Pharmacy Health Dashboard"]
          }), /*#__PURE__*/_jsxs("button", {
            className: "btn btn-xs btn-outline-secondary text-white",
            onClick: loadDashboard,
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-arrow-clockwise"
            }), " Refresh"]
          })]
        }), loadingDash || !dashData ? /*#__PURE__*/_jsxs("div", {
          className: "text-center py-5 text-slate-400",
          children: [/*#__PURE__*/_jsx("div", {
            className: "spinner-border spinner-border-sm text-primary mb-2"
          }), /*#__PURE__*/_jsx("div", {
            children: "Loading store telemetry..."
          })]
        }) : /*#__PURE__*/_jsxs("div", {
          children: [/*#__PURE__*/_jsxs("div", {
            className: "row g-2 mb-3",
            children: [/*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 rounded-3 border border-secondary",
                style: {
                  background: '#1e293b'
                },
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-slate-400 d-block",
                  style: {
                    fontSize: '0.68rem',
                    color: '#94a3b8'
                  },
                  children: "TOTAL BATCHES"
                }), /*#__PURE__*/_jsx("span", {
                  className: "fs-5 fw-bolder text-white",
                  children: dashData.summary?.total_batches || 0
                }), /*#__PURE__*/_jsxs("span", {
                  className: "small text-slate-400 ms-1",
                  style: {
                    fontSize: '0.68rem'
                  },
                  children: ["(", dashData.summary?.total_units || 0, " Units)"]
                })]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 rounded-3 border border-primary",
                style: {
                  background: 'rgba(14, 165, 233, 0.15)'
                },
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-cyan-300 d-block",
                  style: {
                    fontSize: '0.68rem',
                    color: '#38bdf8'
                  },
                  children: "STOCK VALUATION (PTR)"
                }), /*#__PURE__*/_jsxs("span", {
                  className: "fs-5 fw-bolder text-white",
                  children: ["₹", Number(dashData.summary?.cost_valuation || 0).toLocaleString('en-IN')]
                })]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 rounded-3 border border-warning",
                style: {
                  background: 'rgba(234, 179, 8, 0.12)'
                },
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-warning d-block",
                  style: {
                    fontSize: '0.68rem'
                  },
                  children: "NEAR EXPIRY (<90d)"
                }), /*#__PURE__*/_jsxs("span", {
                  className: "fs-5 fw-bolder text-warning",
                  children: [dashData.summary?.near_expiry_count || 0, " Batches"]
                })]
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 rounded-3 border border-danger",
                style: {
                  background: 'rgba(239, 68, 68, 0.12)'
                },
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-danger d-block",
                  style: {
                    fontSize: '0.68rem'
                  },
                  children: "SUPPLIER PENDING"
                }), /*#__PURE__*/_jsxs("span", {
                  className: "fs-5 fw-bolder text-danger",
                  children: ["₹", Number(dashData.summary?.total_supplier_pending || 0).toLocaleString('en-IN')]
                })]
              })
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "card border-0 mb-3 rounded-3 overflow-hidden",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header border-bottom border-secondary py-2 px-3 d-flex align-items-center justify-content-between",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "small fw-bold text-warning d-flex align-items-center gap-1",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-clock-history"
                }), " Nearest Expiry Batches"]
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-warning text-dark",
                style: {
                  fontSize: '0.65rem'
                },
                children: "Priority Action"
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "p-0",
              children: dashData.near_expiry?.length === 0 ? /*#__PURE__*/_jsx("div", {
                className: "p-3 text-center small text-slate-400",
                children: "No batches expiring in next 90 days."
              }) : dashData.near_expiry.map((item, i) => /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 border-bottom border-slate-700 d-flex align-items-center justify-content-between small",
                children: [/*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "fw-bold text-white",
                    children: item.item_name
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-slate-400",
                    style: {
                      fontSize: '0.7rem',
                      color: '#94a3b8'
                    },
                    children: ["Batch: ", /*#__PURE__*/_jsx("strong", {
                      className: "text-cyan-300",
                      children: item.batch_no
                    }), " ", item.shelf_no ? `• Sh: ${item.shelf_no}` : '']
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-end",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "badge bg-danger-subtle text-danger border border-danger mb-0.5",
                    style: {
                      fontSize: '0.68rem'
                    },
                    children: ["Exp: ", item.expiry_date]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-slate-400",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: [item.current_qty, " Units"]
                  })]
                })]
              }, i))
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "card border-0 mb-3 rounded-3 overflow-hidden",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header border-bottom border-secondary py-2 px-3 d-flex align-items-center justify-content-between",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "small fw-bold text-success d-flex align-items-center gap-1",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-graph-up-arrow"
                }), " Fast Moving Medicines"]
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-success-subtle text-success",
                style: {
                  fontSize: '0.65rem'
                },
                children: "Top Sales"
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "p-0",
              children: dashData.high_sales?.length === 0 ? /*#__PURE__*/_jsx("div", {
                className: "p-3 text-center small text-slate-400",
                children: "No sale records yet."
              }) : dashData.high_sales.map((item, i) => /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 border-bottom border-slate-700 d-flex align-items-center justify-content-between small",
                children: [/*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsxs("div", {
                    className: "fw-bold text-white",
                    children: ["#", i + 1, " ", item.item_name]
                  }), /*#__PURE__*/_jsx("div", {
                    className: "text-slate-400",
                    style: {
                      fontSize: '0.7rem',
                      color: '#94a3b8'
                    },
                    children: item.generic_name || 'Allopathic'
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-end",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "badge bg-success",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: [item.total_sold, " Units Sold"]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-slate-400",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: ["₹", item.total_revenue]
                  })]
                })]
              }, i))
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "card border-0 mb-3 rounded-3 overflow-hidden",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header border-bottom border-secondary py-2 px-3 d-flex align-items-center justify-content-between",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "small fw-bold text-danger d-flex align-items-center gap-1",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-exclamation-triangle-fill"
                }), " Dead / Dump Stock (>60d No Movement)"]
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-danger-subtle text-danger",
                style: {
                  fontSize: '0.65rem'
                },
                children: "Capital Blocked"
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "p-0",
              children: dashData.dump_stock?.length === 0 ? /*#__PURE__*/_jsx("div", {
                className: "p-3 text-center small text-slate-400",
                children: "No stagnant stock detected."
              }) : dashData.dump_stock.map((item, i) => /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 border-bottom border-slate-700 d-flex align-items-center justify-content-between small",
                children: [/*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "fw-bold text-white",
                    children: item.item_name
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-slate-400",
                    style: {
                      fontSize: '0.7rem',
                      color: '#94a3b8'
                    },
                    children: ["Batch: ", item.batch_no, " • Exp: ", item.expiry_date]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-end",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "badge bg-secondary",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: [item.current_qty, " Units Stagnant"]
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-slate-400",
                    style: {
                      fontSize: '0.7rem'
                    },
                    children: ["MRP: ₹", item.mrp]
                  })]
                })]
              }, i))
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "card border-0 mb-3 rounded-3 overflow-hidden",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "card-header border-bottom border-secondary py-2 px-3 d-flex align-items-center justify-content-between",
              children: [/*#__PURE__*/_jsxs("span", {
                className: "small fw-bold text-info d-flex align-items-center gap-1",
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-wallet2"
                }), " Supplier Payments Pending"]
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-info-subtle text-info",
                style: {
                  fontSize: '0.65rem'
                },
                children: "Accounts Payable"
              })]
            }), /*#__PURE__*/_jsx("div", {
              className: "p-0",
              children: dashData.supplier_pending?.length === 0 ? /*#__PURE__*/_jsx("div", {
                className: "p-3 text-center small text-slate-400",
                children: "All supplier bills are settled!"
              }) : dashData.supplier_pending.map((item, i) => /*#__PURE__*/_jsxs("div", {
                className: "p-2.5 border-bottom border-slate-700 d-flex align-items-center justify-content-between small",
                children: [/*#__PURE__*/_jsxs("div", {
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "fw-bold text-white",
                    children: item.supplier_name || 'Distributor'
                  }), /*#__PURE__*/_jsxs("div", {
                    className: "text-slate-400",
                    style: {
                      fontSize: '0.7rem',
                      color: '#94a3b8'
                    },
                    children: ["Inv: ", item.supplier_invoice_no, " • Due: ", item.due_date]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-end",
                  children: [/*#__PURE__*/_jsxs("span", {
                    className: "fw-bold text-danger",
                    children: ["₹", Number(item.pending_amount).toFixed(2)]
                  }), /*#__PURE__*/_jsxs("small", {
                    className: "text-slate-400 d-block",
                    style: {
                      fontSize: '0.65rem'
                    },
                    children: ["Paid: ₹", item.paid_amount]
                  })]
                })]
              }, i))
            })]
          })]
        })]
      }), mobTab === 'collector' && /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center justify-content-between mb-2",
          children: [/*#__PURE__*/_jsxs("h6", {
            className: "fw-bold mb-0 text-white d-flex align-items-center gap-1.5",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-basket-fill text-warning"
            }), " Shelf & Rack Collector"]
          }), /*#__PURE__*/_jsxs("span", {
            className: "badge bg-warning text-dark small fw-bold",
            children: [collectorItems.length, " Items in Basket"]
          })]
        }), /*#__PURE__*/_jsx("p", {
          className: "text-slate-400 small mb-3",
          style: {
            fontSize: '0.75rem',
            color: '#94a3b8'
          },
          children: "Collect medicines from shelves, add to mobile basket, and tap \"Save Draft\" so the cashier on counter PC can bill it immediately."
        }), /*#__PURE__*/_jsxs("div", {
          className: "mb-3",
          children: [/*#__PURE__*/_jsx("label", {
            className: "form-label small fw-bold text-slate-300 mb-1",
            children: "Search Medicine to Pick"
          }), /*#__PURE__*/_jsxs("div", {
            className: "input-group",
            children: [/*#__PURE__*/_jsx("span", {
              className: "input-group-text bg-dark border-secondary text-slate-400",
              children: /*#__PURE__*/_jsx("i", {
                className: "bi bi-search"
              })
            }), /*#__PURE__*/_jsx("input", {
              type: "text",
              className: "form-control form-control-sm bg-dark text-white border-secondary",
              placeholder: "Type medicine name / barcode...",
              value: itemSearchQuery,
              onChange: e => handleSearchItems(e.target.value)
            })]
          }), searchResults.length > 0 && /*#__PURE__*/_jsx("div", {
            className: "rounded-3 border border-secondary mt-1 p-1 shadow-lg",
            style: {
              background: '#1e293b',
              maxHeight: 200,
              overflowY: 'auto'
            },
            children: searchResults.map(item => /*#__PURE__*/_jsxs("div", {
              className: "p-2 border-bottom border-slate-700 cursor-pointer text-start hover-bg",
              onClick: () => {
                setSelectedItem(item);
                setSelectedBatch(item.batches && item.batches[0] ? item.batches[0] : null);
                setSearchResults([]);
              },
              style: {
                cursor: 'pointer'
              },
              children: [/*#__PURE__*/_jsx("div", {
                className: "fw-bold text-white small",
                children: item.item_name
              }), /*#__PURE__*/_jsxs("small", {
                className: "text-slate-400",
                style: {
                  fontSize: '0.7rem',
                  color: '#94a3b8'
                },
                children: [item.generic_name, " • Stock: ", /*#__PURE__*/_jsx("strong", {
                  className: "text-success",
                  children: item.total_stock
                }), " Units"]
              })]
            }, item.item_id))
          })]
        }), selectedItem && /*#__PURE__*/_jsxs("div", {
          className: "p-3 rounded-3 mb-3 border border-primary",
          style: {
            background: 'rgba(14, 165, 233, 0.12)'
          },
          children: [/*#__PURE__*/_jsx("div", {
            className: "fw-bold text-white fs-6 mb-1",
            children: selectedItem.item_name
          }), /*#__PURE__*/_jsx("small", {
            className: "text-slate-400 d-block mb-2",
            children: selectedItem.generic_name
          }), /*#__PURE__*/_jsxs("div", {
            className: "row g-2 mb-2",
            children: [/*#__PURE__*/_jsxs("div", {
              className: "col-12",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label text-slate-300 small mb-1",
                children: "Select Batch from Shelf"
              }), /*#__PURE__*/_jsx("select", {
                className: "form-select form-select-sm bg-dark text-white border-secondary",
                value: selectedBatch?.batch_id || '',
                onChange: e => {
                  const b = selectedItem.batches?.find(bt => bt.batch_id == e.target.value);
                  setSelectedBatch(b || null);
                },
                children: (selectedItem.batches || []).map(b => /*#__PURE__*/_jsxs("option", {
                  value: b.batch_id,
                  children: ["Batch: ", b.batch_no, " | Exp: ", b.expiry_date, " | Stock: ", b.current_qty, " | Sh: ", b.shelf_no || '-']
                }, b.batch_id))
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "col-6",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label text-slate-300 small mb-1",
                children: "Sell Unit"
              }), /*#__PURE__*/_jsxs("select", {
                className: "form-select form-select-sm bg-dark text-white border-secondary",
                value: collectUnit,
                onChange: e => setCollectUnit(e.target.value),
                children: [/*#__PURE__*/_jsx("option", {
                  value: "Tablet",
                  children: "Tablet / Loose"
                }), /*#__PURE__*/_jsx("option", {
                  value: "Strip",
                  children: "Strip / Full Pack"
                })]
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "col-6",
              children: [/*#__PURE__*/_jsx("label", {
                className: "form-label text-slate-300 small mb-1",
                children: "Quantity"
              }), /*#__PURE__*/_jsxs("div", {
                className: "input-group input-group-sm",
                children: [/*#__PURE__*/_jsx("button", {
                  className: "btn btn-outline-secondary text-white",
                  type: "button",
                  onClick: () => setCollectQty(Math.max(1, collectQty - 1)),
                  children: "-"
                }), /*#__PURE__*/_jsx("input", {
                  type: "number",
                  min: "1",
                  className: "form-control text-center fw-bold bg-dark text-white border-secondary",
                  value: collectQty,
                  onChange: e => setCollectQty(Math.max(1, Number(e.target.value)))
                }), /*#__PURE__*/_jsx("button", {
                  className: "btn btn-outline-secondary text-white",
                  type: "button",
                  onClick: () => setCollectQty(collectQty + 1),
                  children: "+"
                })]
              })]
            })]
          }), /*#__PURE__*/_jsxs("div", {
            className: "d-flex align-items-center justify-content-between pt-2",
            children: [/*#__PURE__*/_jsx("button", {
              type: "button",
              className: "btn btn-xs btn-outline-secondary text-white",
              onClick: () => setSelectedItem(null),
              children: "Cancel"
            }), /*#__PURE__*/_jsxs("button", {
              type: "button",
              className: "btn btn-sm btn-primary fw-bold",
              onClick: addItemToDraft,
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-plus-circle me-1"
              }), " Add to Basket"]
            })]
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card border-0 rounded-3 mb-3",
          style: {
            background: '#1e293b'
          },
          children: [/*#__PURE__*/_jsxs("div", {
            className: "card-header border-bottom border-secondary py-2 px-3 d-flex align-items-center justify-content-between",
            children: [/*#__PURE__*/_jsxs("span", {
              className: "small fw-bold text-white",
              children: ["Draft Basket Items (", collectorItems.length, ")"]
            }), collectorItems.length > 0 && /*#__PURE__*/_jsx("button", {
              className: "btn btn-xs btn-outline-danger",
              onClick: () => setCollectorItems([]),
              children: "Clear"
            })]
          }), /*#__PURE__*/_jsx("div", {
            className: "p-0",
            children: collectorItems.length === 0 ? /*#__PURE__*/_jsxs("div", {
              className: "p-4 text-center text-slate-400 small",
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-cart3 fs-3 d-block mb-1 text-slate-600"
              }), "No medicines collected yet. Search and pick items above."]
            }) : collectorItems.map((it, idx) => /*#__PURE__*/_jsxs("div", {
              className: "p-2.5 border-bottom border-slate-700 d-flex align-items-center justify-content-between small",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-bold text-white",
                  children: it.item_name
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-slate-400",
                  style: {
                    fontSize: '0.7rem'
                  },
                  children: ["Batch: ", it.batch_no, " • Qty: ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-cyan-300",
                    children: [it.qty, " ", it.sell_unit]
                  }), " • Rate: ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-warning",
                    children: ["₹", it.effective_rate]
                  }), " • Total: ", /*#__PURE__*/_jsxs("strong", {
                    className: "text-success",
                    children: ["₹", it.line_gross]
                  })]
                })]
              }), /*#__PURE__*/_jsx("button", {
                className: "btn btn-xs btn-outline-danger p-1",
                onClick: () => setCollectorItems(collectorItems.filter((_, i) => i !== idx)),
                children: /*#__PURE__*/_jsx("i", {
                  className: "bi bi-trash"
                })
              })]
            }, idx))
          })]
        }), collectorItems.length > 0 && /*#__PURE__*/_jsxs("div", {
          className: "p-3 rounded-3 mb-3 border border-secondary",
          style: {
            background: '#1e293b'
          },
          children: [/*#__PURE__*/_jsx("label", {
            className: "form-label text-slate-300 small fw-bold text-uppercase mb-2",
            style: {
              color: '#cbd5e1'
            },
            children: "Patient / Counter Destination"
          }), /*#__PURE__*/_jsxs("div", {
            className: "row g-2 mb-3",
            children: [/*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm bg-dark text-white border-secondary",
                placeholder: "Patient Name / Walk-in",
                value: patientName,
                onChange: e => setPatientName(e.target.value)
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm bg-dark text-white border-secondary",
                placeholder: "Mobile Number",
                value: patientMobile,
                onChange: e => setPatientMobile(e.target.value)
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm bg-dark text-white border-secondary",
                placeholder: "Bed / Room No (optional)",
                value: bedNo,
                onChange: e => setBedNo(e.target.value)
              })
            }), /*#__PURE__*/_jsx("div", {
              className: "col-6",
              children: /*#__PURE__*/_jsx("input", {
                type: "text",
                className: "form-control form-control-sm bg-dark text-white border-secondary",
                placeholder: "Ward Name",
                value: wardName,
                onChange: e => setWardName(e.target.value)
              })
            })]
          }), /*#__PURE__*/_jsx("button", {
            type: "button",
            className: "btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow",
            onClick: handleSaveDraft,
            disabled: isSavingDraft,
            children: isSavingDraft ? /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsx("span", {
                className: "spinner-border spinner-border-sm"
              }), " Transmitting to Counter Desk..."]
            }) : /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-send-check-fill"
              }), " Save Draft to Counter Desk"]
            })
          })]
        })]
      }), mobTab === 'auditor' && /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center justify-content-between mb-2",
          children: [/*#__PURE__*/_jsxs("h6", {
            className: "fw-bold mb-0 text-white d-flex align-items-center gap-1.5",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-sliders2 text-info"
            }), " Mobile Shelf Auditor"]
          }), /*#__PURE__*/_jsx("span", {
            className: "badge bg-info-subtle text-info border border-info",
            style: {
              fontSize: '0.65rem'
            },
            children: "Routine Count"
          })]
        }), /*#__PURE__*/_jsx("p", {
          className: "text-slate-400 small mb-3",
          style: {
            fontSize: '0.75rem',
            color: '#94a3b8'
          },
          children: "During low workload hours, search medicine or rack, check shelf count against system records, and reconcile stock with 1 tap."
        }), /*#__PURE__*/_jsxs("div", {
          className: "input-group mb-3",
          children: [/*#__PURE__*/_jsx("span", {
            className: "input-group-text bg-dark border-secondary text-slate-400",
            children: /*#__PURE__*/_jsx("i", {
              className: "bi bi-search"
            })
          }), /*#__PURE__*/_jsx("input", {
            type: "text",
            className: "form-control form-control-sm bg-dark text-white border-secondary",
            placeholder: "Search medicine, batch, shelf no...",
            value: auditSearch,
            onChange: e => handleSearchAudit(e.target.value)
          })]
        }), /*#__PURE__*/_jsx("div", {
          className: "d-flex flex-column gap-2",
          children: auditResults.length === 0 ? /*#__PURE__*/_jsx("div", {
            className: "text-center py-4 text-slate-500 small",
            children: "Type medicine or batch to verify shelf stock."
          }) : auditResults.map((item, idx) => /*#__PURE__*/_jsxs("div", {
            className: "p-3 rounded-3 border border-secondary",
            style: {
              background: '#1e293b'
            },
            children: [/*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-start justify-content-between gap-2 mb-1",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-bold text-white small",
                  children: item.item_name
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-slate-400",
                  style: {
                    fontSize: '0.72rem',
                    color: '#94a3b8'
                  },
                  children: ["Batch: ", /*#__PURE__*/_jsx("strong", {
                    className: "text-cyan-300",
                    children: item.batch_no
                  }), " • Exp: ", item.expiry_display || item.expiry_date]
                })]
              }), /*#__PURE__*/_jsx("span", {
                className: "badge bg-secondary",
                style: {
                  fontSize: '0.65rem'
                },
                children: item.shelf_no ? `Shelf: ${item.shelf_no}` : 'Normal'
              })]
            }), /*#__PURE__*/_jsxs("div", {
              className: "d-flex align-items-center justify-content-between pt-2 border-top border-slate-700 mt-2",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("small", {
                  className: "text-slate-400 d-block",
                  style: {
                    fontSize: '0.65rem'
                  },
                  children: "SYSTEM STOCK"
                }), /*#__PURE__*/_jsxs("span", {
                  className: "fs-6 fw-bold text-success",
                  children: [item.current_qty, " Units"]
                })]
              }), /*#__PURE__*/_jsxs("button", {
                type: "button",
                className: "btn btn-xs btn-outline-info text-white px-2.5 py-1 rounded-pill d-flex align-items-center gap-1",
                onClick: () => {
                  setAuditModalItem(item);
                  setAuditCount(item.current_qty);
                  setAuditReason('PHYSICAL_VARIATION');
                  setAuditRemarks('');
                },
                children: [/*#__PURE__*/_jsx("i", {
                  className: "bi bi-pencil-square"
                }), " Count & Reconcile"]
              })]
            })]
          }, idx))
        }), auditModalItem && /*#__PURE__*/_jsx("div", {
          className: "modal fade show d-block",
          style: {
            backgroundColor: 'rgba(15, 23, 42, 0.85)',
            zIndex: 10700
          },
          tabIndex: "-1",
          children: /*#__PURE__*/_jsx("div", {
            className: "modal-dialog modal-dialog-centered",
            children: /*#__PURE__*/_jsxs("div", {
              className: "modal-content border-0 shadow-2xl rounded-4 overflow-hidden",
              style: {
                background: '#0f172a',
                color: '#f8fafc',
                border: '1px solid #334155'
              },
              children: [/*#__PURE__*/_jsxs("div", {
                className: "modal-header border-bottom border-secondary py-2.5 px-3",
                children: [/*#__PURE__*/_jsxs("h6", {
                  className: "modal-title fw-bold text-white mb-0 small",
                  children: ["Reconcile: ", auditModalItem.item_name]
                }), /*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn-close btn-close-white",
                  onClick: () => setAuditModalItem(null)
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "modal-body p-3",
                children: [/*#__PURE__*/_jsxs("div", {
                  className: "row g-2 mb-3",
                  children: [/*#__PURE__*/_jsx("div", {
                    className: "col-6",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "p-2 rounded-3 border border-secondary text-center",
                      style: {
                        background: '#1e293b'
                      },
                      children: [/*#__PURE__*/_jsx("small", {
                        className: "text-slate-400 d-block",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "System Qty"
                      }), /*#__PURE__*/_jsx("span", {
                        className: "fs-4 fw-bolder text-white",
                        children: auditModalItem.current_qty
                      })]
                    })
                  }), /*#__PURE__*/_jsx("div", {
                    className: "col-6",
                    children: /*#__PURE__*/_jsxs("div", {
                      className: "p-2 rounded-3 border border-primary text-center",
                      style: {
                        background: 'rgba(14, 165, 233, 0.15)'
                      },
                      children: [/*#__PURE__*/_jsx("small", {
                        className: "text-cyan-300 d-block",
                        style: {
                          fontSize: '0.65rem'
                        },
                        children: "Physical Count"
                      }), /*#__PURE__*/_jsx("input", {
                        type: "number",
                        min: "0",
                        className: "form-control form-control-sm text-center fw-bolder text-cyan-300 bg-dark border-primary",
                        value: auditCount,
                        onChange: e => setAuditCount(e.target.value),
                        autoFocus: true
                      })]
                    })
                  })]
                }), (() => {
                  const sys = Number(auditModalItem.current_qty);
                  const phy = Number(auditCount !== '' ? auditCount : sys);
                  const diff = phy - sys;
                  const rate = Number(auditModalItem.ptr || auditModalItem.mrp || 0);
                  const val = Math.abs(diff) * rate;
                  if (diff === 0) {
                    return /*#__PURE__*/_jsx("div", {
                      className: "alert alert-success py-1.5 px-2 small mb-2",
                      children: "✓ Counts match (0 diff)."
                    });
                  } else if (diff < 0) {
                    return /*#__PURE__*/_jsxs("div", {
                      className: "alert alert-danger py-1.5 px-2 small mb-2",
                      children: [/*#__PURE__*/_jsxs("strong", {
                        children: ["Shortage: ", Math.abs(diff), " units"]
                      }), " (Loss: ₹", val.toFixed(2), ")"]
                    });
                  } else {
                    return /*#__PURE__*/_jsxs("div", {
                      className: "alert alert-info py-1.5 px-2 small mb-2",
                      children: [/*#__PURE__*/_jsxs("strong", {
                        children: ["Surplus: +", diff, " units"]
                      }), " (Valuation: ₹", val.toFixed(2), ")"]
                    });
                  }
                })(), /*#__PURE__*/_jsxs("div", {
                  className: "mb-2",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label text-slate-300 small mb-1",
                    children: "Reason"
                  }), /*#__PURE__*/_jsxs("select", {
                    className: "form-select form-select-sm bg-dark text-white border-secondary",
                    value: auditReason,
                    onChange: e => setAuditReason(e.target.value),
                    children: [/*#__PURE__*/_jsx("option", {
                      value: "PHYSICAL_VARIATION",
                      children: "Physical Shelf Miscount"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "DAMAGE",
                      children: "Damaged Foil / Bottle"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "BREAKAGE",
                      children: "Breakage / Leakage"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "EXPIRY_DUMP",
                      children: "Expired Stock Disposed"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "SURPLUS_FOUND",
                      children: "Surplus Found"
                    }), /*#__PURE__*/_jsx("option", {
                      value: "THEFT_LOSS",
                      children: "Unaccounted Shortage"
                    })]
                  })]
                }), /*#__PURE__*/_jsxs("div", {
                  className: "mb-2",
                  children: [/*#__PURE__*/_jsx("label", {
                    className: "form-label text-slate-300 small mb-1",
                    children: "Remarks"
                  }), /*#__PURE__*/_jsx("input", {
                    type: "text",
                    className: "form-control form-control-sm bg-dark text-white border-secondary",
                    placeholder: "Shelf location / note",
                    value: auditRemarks,
                    onChange: e => setAuditRemarks(e.target.value)
                  })]
                })]
              }), /*#__PURE__*/_jsxs("div", {
                className: "modal-footer border-top border-secondary py-2 px-3",
                children: [/*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn btn-xs btn-outline-secondary",
                  onClick: () => setAuditModalItem(null),
                  children: "Cancel"
                }), /*#__PURE__*/_jsx("button", {
                  type: "button",
                  className: "btn btn-sm btn-primary fw-bold",
                  onClick: handleMobileReconcile,
                  disabled: isSubmittingAudit,
                  children: isSubmittingAudit ? 'Saving...' : 'Commit Adjustment'
                })]
              })]
            })
          })
        })]
      }), mobTab === 'purchase_photo' && /*#__PURE__*/_jsxs("div", {
        children: [/*#__PURE__*/_jsxs("div", {
          className: "d-flex align-items-center justify-content-between mb-2",
          children: [/*#__PURE__*/_jsxs("h6", {
            className: "fw-bold mb-0 text-white d-flex align-items-center gap-1.5",
            children: [/*#__PURE__*/_jsx("i", {
              className: "bi bi-camera-fill text-primary"
            }), " Inward Bill Camera Attachment"]
          }), /*#__PURE__*/_jsx("span", {
            className: "badge bg-primary-subtle text-primary border border-primary",
            style: {
              fontSize: '0.65rem'
            },
            children: "Audit Proof"
          })]
        }), /*#__PURE__*/_jsx("p", {
          className: "text-slate-400 small mb-3",
          style: {
            fontSize: '0.75rem',
            color: '#94a3b8'
          },
          children: "Take a direct photo of the distributor's delivery challan or paper tax invoice and attach it to the inward record."
        }), /*#__PURE__*/_jsxs("div", {
          className: "p-3 rounded-3 mb-3 border border-secondary",
          style: {
            background: '#1e293b'
          },
          children: [/*#__PURE__*/_jsx("label", {
            className: "form-label text-slate-300 small fw-bold text-uppercase mb-1",
            style: {
              color: '#cbd5e1'
            },
            children: "1. Select Inward Purchase Bill"
          }), /*#__PURE__*/_jsx("select", {
            className: "form-select form-select-sm bg-dark text-white border-secondary mb-3",
            value: selectedPurchaseId,
            onChange: e => setSelectedPurchaseId(e.target.value),
            children: recentPurchases.map(p => /*#__PURE__*/_jsxs("option", {
              value: p.purchase_id,
              children: ["#", p.purchase_id, " | Inv: ", p.supplier_invoice_no, " | ", p.supplier_name || 'Distributor', " (₹", p.net_amount, ")"]
            }, p.purchase_id))
          }), /*#__PURE__*/_jsx("label", {
            className: "form-label text-slate-300 small fw-bold text-uppercase mb-1",
            style: {
              color: '#cbd5e1'
            },
            children: "2. Take Paper Invoice Photo"
          }), /*#__PURE__*/_jsx("div", {
            className: "mb-3",
            children: /*#__PURE__*/_jsxs("label", {
              className: "btn btn-outline-primary btn-sm w-100 py-3 d-flex flex-column align-items-center justify-content-center gap-1.5 rounded-3",
              style: {
                borderStyle: 'dashed',
                borderWidth: '2px',
                cursor: 'pointer'
              },
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-camera fs-3"
              }), /*#__PURE__*/_jsx("span", {
                className: "fw-bold small",
                children: "Snap Photo with Phone Camera"
              }), /*#__PURE__*/_jsx("small", {
                className: "text-slate-400",
                style: {
                  fontSize: '0.68rem'
                },
                children: "or choose from device gallery"
              }), /*#__PURE__*/_jsx("input", {
                type: "file",
                accept: "image/*",
                capture: "environment",
                className: "d-none",
                onChange: handleCaptureFile
              })]
            })
          }), capturedImageBase64 && /*#__PURE__*/_jsxs("div", {
            className: "mb-3 text-center",
            children: [/*#__PURE__*/_jsx("div", {
              className: "small text-slate-400 mb-1 text-start",
              children: "Preview of Captured Bill:"
            }), /*#__PURE__*/_jsx("img", {
              src: capturedImageBase64,
              alt: "Captured Invoice",
              className: "rounded-3 border border-secondary shadow-sm",
              style: {
                maxWidth: '100%',
                maxHeight: 220,
                objectFit: 'contain',
                background: '#000'
              }
            })]
          }), /*#__PURE__*/_jsx("button", {
            type: "button",
            className: "btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2 shadow",
            onClick: handleUploadPhoto,
            disabled: isUploadingPhoto || !capturedImageBase64,
            children: isUploadingPhoto ? /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsx("span", {
                className: "spinner-border spinner-border-sm"
              }), " Attaching Image..."]
            }) : /*#__PURE__*/_jsxs(_Fragment, {
              children: [/*#__PURE__*/_jsx("i", {
                className: "bi bi-cloud-arrow-up-fill"
              }), " Upload & Attach to Purchase"]
            })
          })]
        }), /*#__PURE__*/_jsxs("div", {
          className: "card border-0 rounded-3",
          style: {
            background: '#1e293b'
          },
          children: [/*#__PURE__*/_jsx("div", {
            className: "card-header border-bottom border-secondary py-2 px-3",
            children: /*#__PURE__*/_jsx("span", {
              className: "small fw-bold text-white",
              children: "Recent Purchases with Photo Status"
            })
          }), /*#__PURE__*/_jsx("div", {
            className: "p-0",
            children: recentPurchases.slice(0, 5).map(p => /*#__PURE__*/_jsxs("div", {
              className: "p-2.5 border-bottom border-slate-700 d-flex align-items-center justify-content-between small",
              children: [/*#__PURE__*/_jsxs("div", {
                children: [/*#__PURE__*/_jsx("div", {
                  className: "fw-bold text-white",
                  children: p.supplier_invoice_no
                }), /*#__PURE__*/_jsxs("div", {
                  className: "text-slate-400",
                  style: {
                    fontSize: '0.7rem'
                  },
                  children: [p.supplier_name || 'Distributor', " • ₹", p.net_amount]
                })]
              }), /*#__PURE__*/_jsx("div", {
                children: p.invoice_photo ? /*#__PURE__*/_jsxs("a", {
                  href: p.invoice_photo,
                  target: "_blank",
                  rel: "noreferrer",
                  className: "btn btn-xs btn-outline-success d-flex align-items-center gap-1",
                  children: [/*#__PURE__*/_jsx("i", {
                    className: "bi bi-file-image"
                  }), " View Photo"]
                }) : /*#__PURE__*/_jsx("span", {
                  className: "badge bg-secondary-subtle text-secondary",
                  style: {
                    fontSize: '0.65rem'
                  },
                  children: "No Photo"
                })
              })]
            }, p.purchase_id))
          })]
        })]
      })]
    }), /*#__PURE__*/_jsxs("div", {
      className: "fixed-bottom border-top border-slate-800 d-flex align-items-center justify-content-around py-2 px-1 shadow-lg",
      style: {
        background: '#0f172a',
        zIndex: 1030
      },
      children: [/*#__PURE__*/_jsxs("button", {
        type: "button",
        className: `btn btn-link text-decoration-none p-1 d-flex flex-column align-items-center ${mobTab === 'dashboard' ? 'text-primary fw-bold' : 'text-slate-400'}`,
        onClick: () => setMobTab('dashboard'),
        style: {
          fontSize: '0.72rem',
          color: mobTab === 'dashboard' ? '#38bdf8' : '#94a3b8'
        },
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-speedometer2 fs-5"
        }), /*#__PURE__*/_jsx("span", {
          children: "Dashboard"
        })]
      }), /*#__PURE__*/_jsxs("button", {
        type: "button",
        className: `btn btn-link text-decoration-none p-1 d-flex flex-column align-items-center position-relative ${mobTab === 'collector' ? 'text-primary fw-bold' : 'text-slate-400'}`,
        onClick: () => setMobTab('collector'),
        style: {
          fontSize: '0.72rem',
          color: mobTab === 'collector' ? '#38bdf8' : '#94a3b8'
        },
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-basket-fill fs-5"
        }), /*#__PURE__*/_jsx("span", {
          children: "Rack Drafts"
        }), collectorItems.length > 0 && /*#__PURE__*/_jsx("span", {
          className: "position-absolute top-0 start-50 translate-middle badge rounded-pill bg-danger",
          style: {
            fontSize: '0.6rem'
          },
          children: collectorItems.length
        })]
      }), /*#__PURE__*/_jsxs("button", {
        type: "button",
        className: `btn btn-link text-decoration-none p-1 d-flex flex-column align-items-center ${mobTab === 'auditor' ? 'text-primary fw-bold' : 'text-slate-400'}`,
        onClick: () => setMobTab('auditor'),
        style: {
          fontSize: '0.72rem',
          color: mobTab === 'auditor' ? '#38bdf8' : '#94a3b8'
        },
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-sliders2 fs-5"
        }), /*#__PURE__*/_jsx("span", {
          children: "Shelf Audit"
        })]
      }), /*#__PURE__*/_jsxs("button", {
        type: "button",
        className: `btn btn-link text-decoration-none p-1 d-flex flex-column align-items-center ${mobTab === 'purchase_photo' ? 'text-primary fw-bold' : 'text-slate-400'}`,
        onClick: () => setMobTab('purchase_photo'),
        style: {
          fontSize: '0.72rem',
          color: mobTab === 'purchase_photo' ? '#38bdf8' : '#94a3b8'
        },
        children: [/*#__PURE__*/_jsx("i", {
          className: "bi bi-camera-fill fs-5"
        }), /*#__PURE__*/_jsx("span", {
          children: "Bill Camera"
        })]
      })]
    })]
  });
}
const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(/*#__PURE__*/_jsx(ErrorBoundary, {
  children: /*#__PURE__*/_jsx(App, {})
}));