Applications - WSO2 Identity Server
===================================

This document translates the WSO2 Identity Server Console content into a compact developer guide and maps the common application management actions to the REST API documented in `application.yaml`.

Overview
--------
WSO2 Identity Server manages applications (service providers/clients). The API (see `application.yaml`) covers creating, reading, updating, deleting, importing/exporting, and configuring inbound protocols (SAML / OIDC / others), templates, sharing and authorized APIs.

Register an application
----------------------
WSO2 Identity Server supports five application types. When you register an app the server provides reasonable defaults; you can then customize protocol settings.

Application types (what they mean and when to pick them)

- Single-page applications (SPA)
  - What: Browser apps (React, Vue, Angular) that run application logic in the browser and use APIs for data.
  - When: Use for client-side web apps that require interactive UX and typically use OIDC with Authorization Code + PKCE.
  - Register: Create using `POST /applications` and configure OIDC inbound protocol under `/applications/{applicationId}/inbound-protocols/oidc`.

- Web applications
  - What: Server-rendered apps (traditional web apps) that run logic on the server and redirect users for authentication.
  - When: Use when you need server-side sessions or can protect a client secret.
  - Register with OIDC: `POST /applications` + configure OIDC inbound protocol.
  - Register with SAML: `POST /applications` + configure SAML via `/applications/{applicationId}/inbound-protocols/saml`.

- Mobile applications
  - What: Native mobile apps for iOS/Android.
  - When: Use Authorization Code + PKCE and platform-specific redirect URIs.
  - Register: `POST /applications`, configure OIDC for mobile.

- Standard-based applications
  - What: You manage protocol settings manually (OIDC, SAML, etc.).
  - When: Advanced integrations or non-standard flows.
  - Register: `POST /applications`, then configure required inbound protocols endpoints for precise control.

- Machine-to-Machine (M2M) applications
  - What: Non-interactive clients (daemons, services, IoT devices).
  - When: Use client credentials or other machine auth flows.
  - Register: `POST /applications` and configure the appropriate inbound protocol for M2M.

API mapping: register & initial setup

- Create application: POST /applications
- Import application from file: POST /applications/import (multipart/form-data)
- Update application from import (replace/update using file): PUT /applications/import

Make an application discoverable
--------------------------------
Discoverable applications show up in the My Account portal so users can find and open them.

Console steps (UI):

- Console: Applications → Select application → General tab → Discoverable application → Enable and set access URL.

API approach (recommended):

- There is no single explicit "discoverable" endpoint in the OpenAPI file; use the application update endpoint to set the flag/attribute that controls discoverability. Practical approach:
  - Read the application model: GET /applications/{applicationId}
  - Inspect attributes that control discovery in the model (e.g., a boolean field such as `discoverable` or an `accessUrl` property). If absent, check `AdditionalProperties` or request server-side field name from your WSO2 instance.
  - Update with: PATCH /applications/{applicationId} (or PUT if replacing) to toggle the discoverable flag and set the `accessUrl`.

Assumption: The server exposes a discoverable flag or access URL in the application model; if not, use the console or contact the WSO2 admin to confirm the model field name.

Enable / Disable an application
-------------------------------
Console behaviour (UI):

- Disabling blocks new logins, revokes active access tokens and removes user consents. Re-enabling requires users to sign-in and re-consent.

API mapping:

- Toggle enable/disable via PATCH /applications/{applicationId} — update the `enabled` (or similarly named) attribute in the application model.
- On disabling you may also want to call endpoints to revoke tokens if your integration needs explicit token revocation flows (the API includes revoke endpoints for inbound protocols where applicable).

Delete an application
---------------------
Console steps (UI):

- Applications → Select app → General tab → Danger Zone → Delete application → Confirm.

API mapping:

- DELETE /applications/{applicationId}

Export and secrets
------------------
- Export application as XML: GET /applications/{applicationId}/export
- Export as file (XML/YAML/JSON): GET /applications/{applicationId}/exportFile (use `exportSecrets` query param to include secrets; default is false)

Security note: `exportSecrets=true` is highly sensitive; only call it in secure server-to-server contexts, or ensure the current user has the required admin scopes.

Protocol configuration (inbound protocols)
----------------------------------------
- SAML: GET/PUT/DELETE /applications/{applicationId}/inbound-protocols/saml
- OIDC: GET/PUT/DELETE /applications/{applicationId}/inbound-protocols/oidc
  - Regenerate client secret: POST /applications/{applicationId}/inbound-protocols/oidc/regenerate-secret
  - Revoke client: POST /applications/{applicationId}/inbound-protocols/oidc/revoke
- Passive STS / WS-Trust / Custom inbound protocols: similar endpoints in `/inbound-protocols/...` paths.

UI guidance (based on your screenshots)
-------------------------------------
- Applications list page: implement server-side pagination (GET /applications?limit=&offset=), search and filter, and show client ID, type, and quick actions (edit/delete).
- New Application flow: present templates (SPA, Web App, Mobile, Standard, M2M). After selecting a template call `POST /applications` and then open the relevant inbound protocol config for the user.
- OIDC secrets: show the secret only immediately on creation or regeneration; do not persist it client-side.

Best practices and security
---------------------------

- Authentication for frontend:
  - SPAs: use Authorization Code with PKCE (do not store client secrets in the browser).
  - Server-side apps: Authorization Code with client secret stored on the server.
  - Admin operations: prefer server-to-server calls or a backend-for-frontend that holds elevated credentials and enforces RBAC.

- Protect sensitive endpoints (regenerate/revoke/exportSecrets) behind admin scopes (e.g., `internal_application_mgt_update`, `internal_application_mgt_create`).

- CORS: ensure WSO2 allows your frontend origin for the endpoints you call directly from the browser.

Quick implementation checklist
------------------------------

1. Verify auth flow: confirm WSO2 supports Authorization Code + PKCE for your SPA or plan a backend-for-frontend.
2. Implement API client: generate types from `application.yaml` or hand-author TypeScript interfaces for the models you need.
3. Build pages: Applications list, Create (templates), Application detail (tabs for General, Inbound protocols, Sharing, Templates, Export).
4. Implement import flow: multipart upload to POST /applications/import and show server validation errors.
5. Harden: confirm CORS, RBAC, and ensure secrets are shown only once and transmitted over HTTPS.

Assumptions made
----------------
- The application model exposes obvious flags for `enabled` and `discoverable`. If your WSO2 schema uses different field names, adjust the request payload accordingly.

Next steps I can take for you
----------------------------
- Generate a TypeScript client and interfaces from `application.yaml` and add a small `src/api/applications.ts` wrapper tailored to the UI flows in your screenshots.
- Or produce React route/component stubs (ApplicationsList, ApplicationDetail, CreateApplication) wired to the generated client.

Which would you like me to do next?
