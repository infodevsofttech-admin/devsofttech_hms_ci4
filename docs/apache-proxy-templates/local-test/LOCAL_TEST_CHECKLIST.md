# Local-First Validation Checklist (My Host + VPN Server)

## A) My Host local test (10.7.0.31 machine)

1. Start CI4 locally:
   php spark serve --host localhost --port 8080

2. Add hosts entry from my-host/hosts.example:
   127.0.0.1 100001.localtest

3. Enable local Apache vhost:
   my-host/100001.localtest.conf

4. Reload local Apache.

5. Test through local domain (same callback path):
   curl -i -X POST http://100001.localtest/healthplix/fetch -H "X-Healthplix-Secret: CHANGE_ME_TO_STRONG_SECRET" -d '{}'

Expected:
- Request reaches CI4 route at localhost:8080/healthplix/fetch.

## B) VPN server test (10.7.0.1)

1. Install config:
   vpn-server/100001.dhms.in.conf -> /etc/httpd/conf.d/100001.dhms.in.conf

2. Validate and reload Apache:
   apachectl configtest
   systemctl reload httpd

3. From your VPN client machine (10.7.0.31), verify DNS and TLS:
   nslookup 100001.dhms.in
   curl -Iv https://100001.dhms.in/healthplix/fetch

4. Verify callback path through edge proxy:
   curl -i -X POST https://100001.dhms.in/healthplix/fetch -H "X-Healthplix-Secret: CHANGE_ME_TO_STRONG_SECRET" -d '{}'

Expected:
- Edge server accepts request.
- Request is forwarded to khrc.dhms.in over VPN.
- HMS responds with your fetch endpoint response.

## Notes

- Replace placeholder source IPs and secret before production.
- If backend HMS certificate is private/self-signed, import internal CA cert to proxy trust store.
- Keep healthplix/fetch endpoint method-restricted to POST.
