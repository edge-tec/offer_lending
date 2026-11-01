# Enterprise Multi-Offer Dating Affiliate Platform & Landing Page Builder

A complete, enterprise-grade, multi-campaign Dating Affiliate Landing Page Management Platform. Built for administrators to create, customize, publish, manage, track, and optimize unlimited independent landing pages from a single centralized Admin Dashboard.

---

## 🌟 Platform Highlights & Core Capabilities

### 1. Dedicated Initial Dating Landing Pages + 5 Professionally Designed Additions
Each landing page is individually branded, mobile-first, and contains multiple associated offer cards with direct conversion tracking:

| # | Dating Website / Brand | Landing Page Direct URL | Unique Short Tracking Link | Target Website |
|---|---|---|---|---|
| **01** | **Friends With Benefits** | [`/friends-with-benefits`](http://127.0.0.1:8000/friends-with-benefits) | [`/go/fwb01`](http://127.0.0.1:8000/go/fwb01) | `https://friends-with-benefits.com` |
| **02** | **InstaBang** | [`/instabang`](http://127.0.0.1:8000/instabang) | [`/go/ib02`](http://127.0.0.1:8000/go/ib02) | `https://www.instabang.com` |
| **03** | **ZoomFlirts** | [`/zoomflirts`](http://127.0.0.1:8000/zoomflirts) | [`/go/zf03`](http://127.0.0.1:8000/go/zf03) | `https://zoomflirts.com` |
| **04** | **CindyMatches** | [`/cindymatches`](http://127.0.0.1:8000/cindymatches) | [`/go/cm04`](http://127.0.0.1:8000/go/cm04) | `https://cindymatches.com` |
| **05** | **Premium Dating** | [`/premium-dating`](http://127.0.0.1:8000/premium-dating) | [`/go/vip05`](http://127.0.0.1:8000/go/vip05) | Curated VIP Offers |
| **06** | **International Dating** | [`/international-dating`](http://127.0.0.1:8000/international-dating) | [`/go/world06`](http://127.0.0.1:8000/go/world06) | Global Travel Dating |
| **07** | **Social Connections** | [`/social-connections`](http://127.0.0.1:8000/social-connections) | [`/go/social07`](http://127.0.0.1:8000/go/social07) | Nightlife & Drinks |
| **08** | **Online Chat & Dating** | [`/online-chat-dating`](http://127.0.0.1:8000/online-chat-dating) | [`/go/chat08`](http://127.0.0.1:8000/go/chat08) | Live Chat & Video Flirt |
| **09** | **Modern Relationships** | [`/modern-relationships`](http://127.0.0.1:8000/modern-relationships) | [`/go/modern09`](http://127.0.0.1:8000/go/modern09) | Mutual Intentions |

---

### 2. Multi-Offer Architecture Per Landing Page
- Every landing page can present **multiple independent dating offers**.
- The primary offer powers the main Hero CTA, and secondary offers appear in an interactive **Featured Dating Opportunities** comparison grid.
- Centralized tracking URLs: updating an offer's affiliate tracking link in **Offer Management** immediately propagates to **every landing page referencing that offer** without manual code edits or redeployment.

---

### 3. Critical Short Link Redirect Architecture
The platform enforces this conversion flow:

```
Traffic Source (Ad / Social / Email)
       │
       ▼
Unique Short Link (/go/{code})
       │  (Records visitor, captures SubID/UTMs, sets session attribution)
       ▼
Assigned Landing Page (e.g. /friends-with-benefits)
       │  (Displays custom branding, hero headline & multiple offer cards)
       ▼
Selected Offer CTA Click (/click?offer_id=...)
       │  (Generates ClickID, records offer click, merges tracking params)
       ▼
Destination Affiliate Tracking URL
       │
       ▼
Third-Party Dating Website (Friends With Benefits, InstaBang, etc.)
```

---

### 4. S2S Affiliate Postback Conversion Tracking
- **Postback Endpoint:** `/postback` or `/api/postback.php`
- **Supported Parameters:** `clickid`, `subid`, `txid` (Transaction ID), `payout` (Revenue), `status`, `currency`.
- **Anti-Fraud & Reconciliation:**
  - Matches conversions against logged click events.
  - Automatically suppresses duplicate transaction IDs.
  - Full HTTP request audit log in `/admin/postback.php`.

---

### 5. Centralized Enterprise Admin Modules

| Admin Module | Route | Purpose |
|---|---|---|
| **Overview Dashboard** | `/admin/index.php` | Real-time aggregate KPIs (Pages, Offers, Clicks, Conversions, Revenue, Top Performers). |
| **Landing Page Builder** | `/admin/page-edit.php` | Visual section-based page creator with color pickers, custom CSS, headline & offer assignments. |
| **Manage Pages** | `/admin/pages.php` | Status filters, duplication, live preview links, and short link copy shortcuts. |
| **Offer Management** | `/admin/offers.php` | Add, edit, replace affiliate URLs, adjust priorities, and toggle active/paused states. |
| **Short Link Generator** | `/admin/short-links.php` | Generate custom slugs, remap pages, and inspect short link traffic velocity. |
| **Media Library** | `/admin/media.php` | Upload, categorize, compress, and copy image assets directly into pages. |
| **Campaigns** | `/admin/campaigns.php` | Multi-campaign tracking URL management. |
| **Click Logs** | `/admin/clicks.php` | Real-time stream of incoming clicks, SubIDs, device types, and geographic data. |
| **S2S Postbacks** | `/admin/postback.php` | Conversion reconciliations, revenue metrics, and network integration URLs. |
| **Domains** | `/admin/domains.php` | Multi-domain management and landing page mapping. |
| **User Roles** | `/admin/users.php` | Role-Based Access Control (Super Admin, Manager, Editor, Viewer). |
| **System Settings** | `/admin/settings.php` | Open-redirect protection, domain whitelist, GA4, and Meta Pixel controls. |
| **Audit Trail** | `/admin/audit.php` | Tamper-evident log of all administrative modifications. |

---

## 🔑 Administrative Access

- **Admin Portal:** [http://127.0.0.1:8000/admin/login.php](http://127.0.0.1:8000/admin/login.php)
- **Default Username:** `admin`
- **Default Password:** `AdminPassword2026!`

---

## 🧪 Automated Test Verification

Run the acceptance test suite anytime via CLI:
```bash
php test_suite.php
```

All **10/10 Acceptance Tests** verify:
1. Complete 19-table database schema.
2. Initial 4 dedicated landing pages + 5 additional landing pages.
3. Multi-offer associations per page.
4. Independent short tracking link resolution.
5. Short links resolving landing pages without premature redirects.
6. Offer CTA redirection with parameter preservation.
7. Instant dynamic tracking URL propagation.
8. Unlimited new landing page creation.
9. S2S conversion postback and duplicate prevention.
10. Open-redirect prevention and automated whitelist authorization.
