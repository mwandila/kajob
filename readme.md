# Kajob WordPress Theme

Kajob is a job-board theme built around WP Job Manager. The customer setup guide is in [`docs/index.html`](docs/index.html).

## Requirements

- WordPress 6.4 or newer
- PHP 7.4 or newer
- Kajob Core companion plugin supplied as a separate ZIP with the release
- [WP Job Manager](https://wordpress.org/plugins/wp-job-manager/) for job listings, submissions and employer dashboards
- Kajob Core companion plugin supplied with the Kajob release for job alerts, shortcode overrides, and encrypted candidate files

Without WP Job Manager or Kajob Core, general WordPress pages and posts still work, but the corresponding job-board features are unavailable.

Install the supplied Kajob Core ZIP through **Plugins → Add New → Upload Plugin** and activate it before using job alerts, Kajob shortcode overrides, or protected candidate file features.

## Included features

- Responsive job board, search, filters and saved jobs
- Candidate account, encrypted CV storage, application history and email applications
- Optional AI assistant, disabled until the site owner supplies an API key
- Demo content importer in **Appearance → Demo Content**
- Appearance settings in the WordPress Customizer

## Privacy and external services

- Candidate CVs and saved cover letters are encrypted in WordPress user metadata, not stored in the Media Library. Candidate application history (up to the most recent 100 applications) and encrypted application details are also stored in user metadata for signed-in candidates. These records remain until the candidate deletes the account/data or the site administrator processes an erasure request. Site owners should set and publish a retention policy appropriate to their jurisdiction.
- Applications are emailed to the address on the job. Uploaded application documents are placed in a private temporary directory and deleted after WordPress hands the message to the mail transport. Email providers and mail-server retention are controlled by the site owner and their providers.
- Kajob Resend Mailer is an optional companion plugin. If enabled, it sends recipients, sender details, subject, message body and attachments to Resend's API for delivery. Each site owner must supply their own Resend account/API key and verified sender domain and disclose the service in their privacy policy.
- WordPress privacy export/erasure tools include Kajob candidate account data. Candidates can also remove a saved CV and cover letter from their account. Account deletion or a WordPress erasure request removes Kajob candidate data; it does not recall application emails already sent to employers.
- When enabled, the AI assistant sends visitor messages and relevant public job/blog data to the configured NVIDIA-compatible inference API. The site owner supplies and pays for (or uses under the terms of) that provider account. Do not send sensitive personal data through the assistant. The feature is off until an API key is configured.
- Configure reliable outgoing email (SMTP recommended) before accepting applications.

## Assets and licenses

- The bundled Inter and Space Grotesk fonts are licensed under the SIL Open Font License 1.1. License texts are included in `assets/fonts/`.
- Original demo illustrations and their authorship are recorded in [`assets/images/home-demo/ASSET-CREDITS.md`](assets/images/home-demo/ASSET-CREDITS.md).
- WP Job Manager is a separate plugin and is not bundled with the theme.
- Kajob Core is a separate required companion plugin. Install and activate it alongside the theme.

## Release status

The theme source includes a customer setup guide, but the guide must be published at a stable public URL before a ThemeForest submission. Replace the brand author value in `style.css` with the seller identity if Kajob is not the author, and add the final marketplace listing/support URLs to the hosted documentation. Run the Theme Check and feature checks against the exact release ZIP before submitting it.
