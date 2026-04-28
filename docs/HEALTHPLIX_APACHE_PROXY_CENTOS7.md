# HealthPlix Reverse Proxy Setup (CentOS 7 + Apache)

This guide is for a central public Apache server that forwards each hospital domain to its own local HMS over VPN.

Example topology:
- Public Apache server: 139.59.13.39
- Hospital A domain: 100001.dhms.in -> A record -> 139.59.13.39
- Hospital A HMS private VPN IP: 10.7.0.32
- Hospital B domain: 100002.dhms.in -> A record -> 139.59.13.39
- Hospital B HMS private VPN IP: 10.8.0.45

Recommended exposure model:
- Expose only callback/fetch path to internet.
- Do not expose full HMS unless required.

## 1) Install and enable required Apache modules

Run on CentOS 7 Apache host:

  sudo yum install -y mod_ssl
  sudo httpd -M | egrep "proxy|ssl|headers|rewrite"

If any are missing, enable in Apache config and restart httpd.

## 2) Reusable virtual-host template (callback-only)

Create one file per hospital in /etc/httpd/conf.d/

Template file name:
- /etc/httpd/conf.d/DOMAIN.conf

Template content:

  <VirtualHost *:80>
      ServerName DOMAIN
      RewriteEngine On
      RewriteRule ^/(.*)$ https://DOMAIN/$1 [R=301,L]
  </VirtualHost>

  <VirtualHost *:443>
      ServerName DOMAIN

      SSLEngine On
      SSLCertificateFile /etc/letsencrypt/live/DOMAIN/fullchain.pem
      SSLCertificateKeyFile /etc/letsencrypt/live/DOMAIN/privkey.pem

      ProxyRequests Off
      ProxyPreserveHost On
      SSLProxyEngine On

      RequestHeader set X-Forwarded-Proto "https"
      RequestHeader append X-Forwarded-For %{REMOTE_ADDR}s

      ProxyTimeout 60
      Timeout 60

      # Callback/fetch endpoint only
      ProxyPass        /healthplix/fetch http://VPN_HMS_IP/healthplix/fetch
      ProxyPassReverse /healthplix/fetch http://VPN_HMS_IP/healthplix/fetch

      ErrorLog  /var/log/httpd/DOMAIN-healthplix-error.log
      CustomLog /var/log/httpd/DOMAIN-healthplix-access.log combined
  </VirtualHost>

Replace:
- DOMAIN with hospital DNS name (example 100001.dhms.in)
- VPN_HMS_IP with local HMS VPN IP (example 10.7.0.32)

## 3) Hardened variant (recommended)

Use this inside the :443 VirtualHost when HealthPlix source IPs and secret are available.

  # 3.1 Limit endpoint to known HealthPlix source IPs
  <Location /healthplix/fetch>
      Require all denied
      Require ip 203.0.113.10
      Require ip 203.0.113.11
  </Location>

  # 3.2 Shared-secret header validation at edge
  RewriteEngine On
  RewriteCond %{REQUEST_URI} ^/healthplix/fetch$
  RewriteCond %{HTTP:X-Healthplix-Secret} !^REPLACE_WITH_STRONG_SECRET$
  RewriteRule ^ - [F,L]

  # 3.3 Restrict methods
  <Location /healthplix/fetch>
      <LimitExcept POST>
          Require all denied
      </LimitExcept>
  </Location>

  # 3.4 Body size limits (adjust to payload)
  LimitRequestBody 1048576

Notes:
- Replace placeholder IPs with official HealthPlix outbound IPs.
- Replace secret with a long random value.
- Validate same secret again in HMS controller before processing.

## 4) Multi-hospital mapping examples

Hospital A:
- Domain: 100001.dhms.in
- Target: 10.7.0.32

Hospital B:
- Domain: 100002.dhms.in
- Target: 10.8.0.45

Hospital C:
- Domain: 100003.dhms.in
- Target: 10.9.0.20

All domains can point to same public IP 139.59.13.39.
Apache routes by ServerName.

## 5) Enable and reload Apache

  sudo apachectl configtest
  sudo systemctl reload httpd

If using firewalld:

  sudo firewall-cmd --add-service=http --permanent
  sudo firewall-cmd --add-service=https --permanent
  sudo firewall-cmd --reload

## 6) HMS application-side settings

For outbound sync from HMS to HealthPlix:
- Keep HEALTHPLIX_BASE_URL as official HealthPlix API base URL.
- Keep token/patient/appointment paths as currently configured.

For inbound fetch/callback from HealthPlix:
- Give HealthPlix callback URL as:
  https://DOMAIN/healthplix/fetch

## 7) Validation checklist

1. DNS resolves correctly:
   nslookup DOMAIN
2. TLS certificate is valid:
   curl -Iv https://DOMAIN/healthplix/fetch
3. Reverse proxy reaches HMS private node:
   curl -i -X POST https://DOMAIN/healthplix/fetch -H "X-Healthplix-Secret: REPLACE_WITH_STRONG_SECRET" -d '{}'
4. Apache logs show routed request in domain-specific log file.
5. HMS receives request and returns expected response.

## 8) Operations notes

- Keep one vhost file per hospital for isolation.
- Keep one log file pair per hospital for easier troubleshooting.
- Rotate logs and monitor 4xx/5xx rates.
- Keep VPN routes and ACLs documented and version-controlled.
- Do not log bearer tokens or patient-sensitive payloads in plaintext.
