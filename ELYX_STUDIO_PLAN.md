# ELYX Studio implementation plan

## Product direction

ELYX Studio is a commerce and digital studio platform for selling curated digital products, studio goods, and website/product services. The public experience should feel editorial and premium; the authenticated Laravel application remains the operational foundation for fulfillment, account management, reporting, and future admin tooling.

## Framework baseline

ELYX is a standalone commerce application with a deliberately small, auditable foundation:

- Laravel 13 with Fortify authentication, email verification, passkeys, two-factor authentication, and Inertia server-side routing.
- Vue 3, TypeScript, Vite, Tailwind CSS v4, Lucide icons, and reusable layouts/components.
- Eloquent migrations, models, seeders, factories, authorization middleware, queued jobs, notifications, and tests.
- Shared Inertia middleware, activity logging, secure headers, sky-blue theme handling, and deployment files.

The application contains only ELYX commerce and studio workflows. Commerce code is organized into `Commerce` and `Storefront` modules so catalog, orders, users, and seller operations remain clearly separated.

## Current vertical slice

- Public home page with studio positioning, featured products, website services, process, and CTA.
- Shop catalog with category, type, and text search filters.
- Product/service detail pages with features, pricing, related products, and add-to-cart actions.
- Session cart with quantity changes, removal, item counts, and server-calculated totals.
- Guest checkout with validation and persisted orders/order items.
- Confirmation page with a non-guessable confirmation token and order number.
- Seeded catalog covering digital assets, studio goods, websites, and strategy.

## Domain model

### `products`

Catalog records for physical, digital, and service offerings. `type` distinguishes product, digital, and service items. `features` is JSON to keep package inclusions flexible. Prices are snapshotted into order items at purchase time.

### `orders`

Customer checkout and fulfillment state. `status` starts at `pending`; `payment_status` starts at `unpaid`. Physical inventory is reserved while an order is active, consumed when an order is completed, and released when an order is cancelled. Add payment provider identifiers, fulfillment status, invoice data, and user ownership when the payment flow is selected.

### `order_items`

Immutable purchase lines. The item name and unit price are copied from the catalog so historical orders do not change when a product is edited.

## Delivery phases

### Phase 1 — storefront foundation (complete)

- Establish ELYX brand system and public layout.
- Add product/service catalog schema and seed content.
- Implement shop, product detail, cart, checkout, and confirmation.
- Validate server-side quantities and totals.

### Phase 2 — operations (partially complete)

- [x] Add superadmin-only catalog management for products, digital goods, studio goods, and services.
- [x] Add order dashboard with status filtering and status transitions.
- [x] Add inventory reservation, completion consumption, cancellation release, and basic operational statistics.
- [ ] Add image/media upload and managed local or S3 storage.
- [ ] Add order notes, customer history, CSV/PDF export, and fulfillment records for digital delivery links and service project stages.
- [ ] Add email notifications for order received, payment confirmed, and delivery completed.

### Phase 3 — payments and accounts

- [ ] Choose a payment provider based on launch geography and currencies.
- [ ] Replace the current order-request checkout with hosted checkout and webhook verification; never trust client payment state.
- [x] Add authenticated customer order history and profile preferences.
- [ ] Add discount codes, tax configuration, shipping rules, and abandoned-cart events.

### Phase 4 — studio services

- Add service intake forms attached to order items.
- Add project workspace with milestones, files, messages, approvals, and delivery links.
- Add contracts, invoices, deposits, and recurring retainers where needed.

### Phase 5 — growth

- Add product reviews, bundles, campaigns, analytics events, SEO metadata, sitemap, and content pages.
- Add API endpoints for a future mobile/partner client, preserving Laravel as the source of truth.

## Security and quality rules

- Calculate price and availability on the server.
- Use validated requests and authorization policies for every write operation.
- Use signed/webhook-verified payment callbacks.
- Do not store card data.
- Keep catalog, order, and fulfillment audit events immutable where practical.
- Add feature, browser, and unit tests before enabling payment capture.

## Launch checklist

- Configure production `.env`, database, mail, queue, storage, and HTTPS.
- Run `php artisan migrate --force` and `php artisan db:seed --class=ElyxStudioSeeder`.
- Select and configure a payment provider in test mode, then verify webhook signatures before enabling payment capture.
- Configure managed product media storage before replacing seeded remote image URLs.
- Test guest checkout, authenticated checkout, out-of-stock behavior, duplicate submissions, reservation expiry, and email delivery.
- Run targeted ELYX tests plus the project lint, format, type, and full test checks before each release.
