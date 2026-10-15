/**
 * CSRF Guard - Universal Cross-Site Request Forgery Protection Handler
 * 
 * Automatically protects ALL HTTP requests across the application:
 * 1. jQuery AJAX ($.ajax, $.post, $.get with payload)
 * 2. Fetch API (window.fetch)
 * 3. XMLHttpRequest (raw XHR, axios, third-party libraries)
 * 4. HTML Forms (native submit & programmatic form.submit())
 */
(function(window, document) {
    'use strict';

    var DEFAULT_TOKEN_NAME = 'csrf_test_name';
    var DEFAULT_HEADER_NAME = 'X-CSRF-TOKEN';

    var currentToken = '';
    var tokenName = DEFAULT_TOKEN_NAME;
    var headerName = DEFAULT_HEADER_NAME;

    function getCookie(name) {
        if (!document.cookie) return null;
        var parts = document.cookie.split(';');
        for (var i = 0; i < parts.length; i++) {
            var c = parts[i].trim();
            if (c.indexOf(name + '=') === 0) {
                return decodeURIComponent(c.substring(name.length + 1));
            }
        }
        return null;
    }

    function readMetaTags() {
        var metaToken = document.querySelector('meta[name="csrf-token"]') ||
                        document.querySelector('meta[name="X-CSRF-TOKEN"]') ||
                        document.querySelector('meta[name="csrf-hash"]');
        if (metaToken && metaToken.getAttribute('content')) {
            currentToken = metaToken.getAttribute('content');
        }

        var metaName = document.querySelector('meta[name="csrf-token-name"]');
        if (metaName && metaName.getAttribute('content')) {
            tokenName = metaName.getAttribute('content');
        }

        var metaHeader = document.querySelector('meta[name="csrf-header"]');
        if (metaHeader && metaHeader.getAttribute('content')) {
            headerName = metaHeader.getAttribute('content');
        }

        if (!currentToken) {
            var cookieToken = getCookie('csrf_cookie_name') || getCookie('csrf_test_name');
            if (cookieToken) {
                currentToken = cookieToken;
            }
        }
    }

    readMetaTags();

    function updateToken(newToken) {
        if (!newToken || typeof newToken !== 'string' || newToken === currentToken) {
            return;
        }
        currentToken = newToken;

        // Update meta tag
        var metaToken = document.querySelector('meta[name="csrf-token"]') ||
                        document.querySelector('meta[name="X-CSRF-TOKEN"]') ||
                        document.querySelector('meta[name="csrf-hash"]');
        if (metaToken) {
            metaToken.setAttribute('content', newToken);
        }

        // Update all existing hidden inputs across DOM
        var inputs = document.querySelectorAll('input[name="' + tokenName + '"]');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].value = newToken;
        }

        // Update global jQuery ajaxSetup if available
        if (window.jQuery && window.jQuery.ajaxSetup) {
            var headers = {};
            headers[headerName] = newToken;
            window.jQuery.ajaxSetup({ headers: headers });
        }
    }

    var CsrfGuard = {
        getToken: function() {
            if (!currentToken) readMetaTags();
            return currentToken;
        },
        getTokenName: function() {
            return tokenName;
        },
        getHeaderName: function() {
            return headerName;
        },
        updateToken: updateToken,
        init: function(config) {
            if (config) {
                if (config.token) currentToken = config.token;
                if (config.tokenName) tokenName = config.tokenName;
                if (config.headerName) headerName = config.headerName;
            }
            readMetaTags();
            this.setupJQuery();
        },
        setupJQuery: function() {
            if (!window.jQuery) return;
            var $ = window.jQuery;

            if ($._csrfGuardInstalled) return;
            $._csrfGuardInstalled = true;

            var defaultHeaders = {};
            if (currentToken) {
                defaultHeaders[headerName] = currentToken;
            }
            $.ajaxSetup({
                headers: defaultHeaders
            });

            $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
                var method = (options.type || options.method || 'GET').toUpperCase();
                if (method === 'GET' || method === 'HEAD' || method === 'OPTIONS') {
                    return;
                }

                var token = CsrfGuard.getToken();
                if (!token) return;

                // Set header on every non-GET request
                jqXHR.setRequestHeader(headerName, token);

                // Inject into payload data as well
                if (options.data === undefined || options.data === null) {
                    options.data = encodeURIComponent(tokenName) + '=' + encodeURIComponent(token);
                } else if (typeof options.data === 'string') {
                    if (options.data.indexOf(tokenName + '=') === -1) {
                        options.data += (options.data.length > 0 ? '&' : '') +
                                        encodeURIComponent(tokenName) + '=' + encodeURIComponent(token);
                    }
                } else if (window.FormData && options.data instanceof window.FormData) {
                    if (!options.data.has(tokenName)) {
                        options.data.append(tokenName, token);
                    }
                } else if (typeof options.data === 'object') {
                    options.data[tokenName] = token;
                }
            });

            $(document).ajaxComplete(function(event, xhr, settings) {
                var respToken = xhr.getResponseHeader(headerName) ||
                                xhr.getResponseHeader('X-CSRF-TOKEN') ||
                                xhr.getResponseHeader('x-csrf-token');
                if (respToken) {
                    updateToken(respToken);
                }

                try {
                    if (xhr.responseJSON && xhr.responseJSON.csrf_hash) {
                        updateToken(xhr.responseJSON.csrf_hash);
                    }
                } catch (e) {}
            });
        }
    };

    window.CSRF_GUARD = CsrfGuard;
    window.getCsrfToken = CsrfGuard.getToken;
    window.getCsrfTokenName = CsrfGuard.getTokenName;
    window.getCsrfHeader = CsrfGuard.getHeaderName;
    window.updateCsrfToken = CsrfGuard.updateToken;

    // 1. Hook HTML Form Submissions
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        var method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method !== 'POST') return;

        var token = CsrfGuard.getToken();
        if (!token) return;

        var input = form.querySelector('input[name="' + tokenName + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = tokenName;
            input.value = token;
            form.appendChild(input);
        } else {
            input.value = token;
        }
    }, true);

    // Hook programmatic form.submit()
    if (window.HTMLFormElement && window.HTMLFormElement.prototype) {
        var originalSubmit = window.HTMLFormElement.prototype.submit;
        window.HTMLFormElement.prototype.submit = function() {
            var method = (this.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'POST') {
                var token = CsrfGuard.getToken();
                if (token) {
                    var input = this.querySelector('input[name="' + tokenName + '"]');
                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = tokenName;
                        input.value = token;
                        this.appendChild(input);
                    } else {
                        input.value = token;
                    }
                }
            }
            return originalSubmit.apply(this, arguments);
        };
    }

    // 2. Hook Fetch API
    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function(resource, init) {
            init = init || {};
            var method = (init.method || 'GET').toUpperCase();

            if (method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS') {
                var token = CsrfGuard.getToken();
                if (token) {
                    // Inject Header
                    if (!init.headers) {
                        init.headers = {};
                    }

                    if (window.Headers && init.headers instanceof Headers) {
                        if (!init.headers.has(headerName)) {
                            init.headers.set(headerName, token);
                        }
                    } else if (Array.isArray(init.headers)) {
                        init.headers.push([headerName, token]);
                    } else {
                        init.headers[headerName] = token;
                    }

                    // Inject Body (FormData or URLSearchParams)
                    if (window.FormData && init.body instanceof FormData) {
                        if (!init.body.has(tokenName)) {
                            init.body.append(tokenName, token);
                        }
                    } else if (typeof init.body === 'string') {
                        var ct = '';
                        if (init.headers) {
                            if (window.Headers && init.headers instanceof Headers) {
                                ct = init.headers.get('Content-Type') || '';
                            } else {
                                ct = init.headers['Content-Type'] || init.headers['content-type'] || '';
                            }
                        }
                        if (ct.indexOf('application/x-www-form-urlencoded') !== -1 && init.body.indexOf(tokenName + '=') === -1) {
                            init.body += (init.body.length ? '&' : '') +
                                         encodeURIComponent(tokenName) + '=' + encodeURIComponent(token);
                        }
                    }
                }
            }

            return originalFetch.apply(this, [resource, init]).then(function(response) {
                var newToken = response.headers.get(headerName) ||
                               response.headers.get('X-CSRF-TOKEN') ||
                               response.headers.get('x-csrf-token');
                if (newToken) {
                    updateToken(newToken);
                }
                return response;
            });
        };
    }

    // 3. Hook Raw XMLHttpRequest
    if (window.XMLHttpRequest) {
        var originalOpen = XMLHttpRequest.prototype.open;
        var originalSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function(method, url) {
            this._csrfMethod = (method || 'GET').toUpperCase();
            return originalOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function(body) {
            if (this._csrfMethod && this._csrfMethod !== 'GET' && this._csrfMethod !== 'HEAD' && this._csrfMethod !== 'OPTIONS') {
                var token = CsrfGuard.getToken();
                if (token) {
                    try {
                        this.setRequestHeader(headerName, token);
                    } catch (e) {}
                }
            }

            var self = this;
            var onReadyHandler = function() {
                if (self.readyState === 4) {
                    try {
                        var newToken = self.getResponseHeader(headerName) ||
                                       self.getResponseHeader('X-CSRF-TOKEN') ||
                                       self.getResponseHeader('x-csrf-token');
                        if (newToken) {
                            updateToken(newToken);
                        }
                    } catch (e) {}
                }
            };
            this.addEventListener('readystatechange', onReadyHandler, false);

            return originalSend.apply(this, arguments);
        };
    }

    // Try setting up jQuery now, or poll briefly until jQuery is loaded
    CsrfGuard.setupJQuery();
    if (!window.jQuery) {
        var attempts = 0;
        var checkJq = setInterval(function() {
            attempts++;
            if (window.jQuery) {
                CsrfGuard.setupJQuery();
                clearInterval(checkJq);
            } else if (attempts > 50) {
                clearInterval(checkJq);
            }
        }, 100);
    }

})(window, document);
