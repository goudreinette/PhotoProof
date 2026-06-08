# PhotoProof — WordPress Proofing Plugin

> Self-hosted gallery proofing for photographers. Share galleries with your clients, let them pick their favorite photos, get the selection back as CSV or email.

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/photoproof?label=WordPress.org)](https://wordpress.org/plugins/photoproof/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/photoproof?label=downloads)](https://wordpress.org/plugins/photoproof/advanced/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/photoproof?label=rating)](https://wordpress.org/plugins/photoproof/#reviews)
[![License: GPL v2+](https://img.shields.io/badge/license-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-8892BF.svg)](https://www.php.net/)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759B.svg)](https://wordpress.org/)

**[📥 Install from WordPress.org](https://wordpress.org/plugins/photoproof/)** · **[🌐 Landing page](https://photoproof.cedricstoecklin.com/)** · **[☕ Support on Ko-fi](https://ko-fi.com/cedricstoecklin/tip)**

---

## About

PhotoProof turns WordPress into a proofing platform for photographers. Create galleries, share them with your clients, let them select their favorites, and recover the validated selection — all on your own WordPress install. No SaaS, no subscription, no third-party servers.

**Built for photographers, by a photographer.** Maintained by [Cédric Stoecklin](https://github.com/stoeck74), a photographer who needed a proofing tool that wouldn't lock him into a SaaS or share clients' photos with third parties.

---

## Features

- **Standalone gallery template** — distraction-free page, fully isolated from your theme (compatible with Barba.js, page builders, etc.)
- **Client selection workflow** — click to select, auto-saved every 1.5s, confirm with a single button
- **Client comments** — clients can leave a note per photo (optional, configurable)
- **Watermark protection** — automatic logo overlay on all images (GD or Imagick), adjustable opacity, originals untouched
- **Automatic file renaming** — configurable pattern (`{gallery_title}-{index}`), with optional custom prefix per gallery
- **Private UUID links** — replace gallery slugs with impossible-to-guess UUIDs, no permalink flush needed
- **Access expiration** — auto-archive galleries after 30 days
- **Email notifications** — customizable templates for photographer and client, sent on selection validation
- **Photographer recommendations** — mark your favorites to guide the client (configurable icon)
- **CSV export** — download the validated selection as a spreadsheet
- **Masonry or grid layout** — choose the layout that fits your photographic style
- **Client dashboard** — `[photoproof_galleries_client]` shortcode + PHP template tags
- **Multilingual** — ships with French, German, Spanish and Italian translations
- **Customizable design** — background, accent, text colors, custom logo, rounded corners option

---

## Quick Start

```bash
# Via WordPress.org (recommended for users)
# Plugins → Add New → search "PhotoProof" → Install → Activate

# Via Git (for developers / forkers)
cd wp-content/plugins
git clone https://github.com/stoeck74/PhotoProof.git photoproof
```

Then:

1. Activate the plugin in **Plugins**
2. Go to **PhotoProof → Settings** to configure watermark, renaming, colors and emails
3. Create your first gallery under **PhotoProof → Add New**

**Requirements:** WordPress 6.0+ · PHP 8.0+ · GD or Imagick (for watermark generation)

---

## Workflow

### 1. Create a gallery

1. **PhotoProof → Add New**, give it a title
2. Save as draft (required before uploading)
3. Upload photos via drag & drop in the metabox
4. Optionally mark photos as recommended
5. Assign a client (WordPress user)
6. **Publish** → triggers renaming + watermark generation + status update

### 2. Share with the client

Copy the URL from the metabox.

- Standard URL: `https://example.com/galerie-epreuve/gallery-title/`
- With UUID enabled: `https://example.com/galerie-epreuve/550e8400-e29b-...`

### 3. Client selection

1. Client browses the gallery (5-column grid or masonry, no crop)
2. Clicks photos to select (circle indicator, bottom-right)
3. Optionally leaves a comment on any photo
4. Selection auto-saves every 1.5 seconds
5. Clicks **Validate selection** → irreversible confirmation
6. Two emails sent automatically (photographer + client)

### 4. After validation

- Gallery is locked for the client
- Unselected photos are dimmed
- Photographer sees the recap with thumbnails in the metabox
- Photographer can **reopen** (keep or reset selection)

---

## Gallery Statuses

```
brouillon → publie → valide → ferme
               ↑________↓
            (reopen)
```

| Status | Description | Client can select |
|--------|-------------|-------------------|
| `brouillon` | Draft — admin only | No |
| `publie` | Published — waiting for selection | Yes |
| `valide` | Selection confirmed by client | No (locked) |
| `ferme` | Archived (expired or manual) | No |

> Note: status values are kept in French in the database for historical reasons. They are translated for display.

---

## Settings

Accessible via **PhotoProof → Settings**.

| Tab | Options |
|---|---|
| **General** | Random UUID URLs · Automatic renaming · Recommendations icon · Auto-expiration · Client comments · Layout (grid/masonry) |
| **Security & Watermark** | Logo upload · Opacity (10-100%) · Custom login URL · File deletion on uninstall |
| **Theme Design** | Header title & logo · Background/active/text colors · Rounded corners toggle |
| **Emails** | Photographer & client templates · Variables: `{client_name}`, `{gallery_title}`, `{count}`, `{file_list}`, `{gallery_url}`, `{studio_name}`, `{commented_not_selected}` |

---

## For Developers

PhotoProof is built to be extended. It exposes action hooks, filter hooks, helper functions, and a shortcode — everything you need to wire it into your own workflow or build a plugin on top.

### Action hooks

```php
// Fires when a client validates their selection
add_action( 'photoproof_gallery_selection_confirmed', function( $post_id, $client_id ) {
    // Sync to your CRM, trigger a Slack notification, etc.
    my_crm_sync( $post_id, $client_id );
}, 10, 2 );

// Fires after a photo is uploaded (post-watermark, post-rename)
add_action( 'photoproof_attachment_uploaded', function( $attachment_id, $post_id ) {
    // Push to external storage, generate additional thumbnails, etc.
    push_to_s3( $attachment_id );
}, 10, 2 );
```

### Filter hooks

```php
// Customize email subjects and bodies before sending
add_filter( 'photoproof_email_photographer_subject', function( $subject, $vars ) {
    return '[' . $vars['studio_name'] . '] ' . $subject;
}, 10, 2 );

add_filter( 'photoproof_email_photographer_body',  function( $body, $vars ) { /* ... */ }, 10, 2 );
add_filter( 'photoproof_email_client_subject',     function( $subject, $vars ) { /* ... */ }, 10, 2 );
add_filter( 'photoproof_email_client_body',        function( $body, $vars ) { /* ... */ }, 10, 2 );
```

### Template tags

```php
$galleries = photoproof_get_client_galleries( $user_id );    // Array of galleries for a user
$status    = photoproof_get_gallery_status( $post_id );      // brouillon/publie/valide/ferme
$count     = photoproof_get_gallery_photo_count( $post_id ); // int
$url       = photoproof_get_gallery_thumbnail( $post_id, 'medium' ); // string URL
$ids       = photoproof_get_gallery_selection( $post_id );   // Array of selected attachment IDs
$locked    = photoproof_is_gallery_locked( $post_id );       // bool
```

### Shortcode

```
[photoproof_galleries_client columns="3" show_status="true" show_count="true" show_date="false"]
```

| Attribute | Default | Description |
|---|---|---|
| `columns` | `1` | Number of columns in the grid |
| `show_status` | `true` | Show status badge |
| `show_count` | `true` | Show photo count |
| `show_date` | `true` | Show creation date |

### AJAX endpoints

<details>
<summary>Show endpoint list</summary>

| Action | Access | Description |
|--------|--------|-------------|
| `photoproof_upload_photo` | Admin | Upload a photo to a gallery |
| `photoproof_detach_photo` | Admin | Remove a photo from a gallery |
| `photoproof_toggle_recommendation` | Admin | Toggle photographer recommendation badge |
| `photoproof_get_gallery_photos` | Admin | List gallery photos |
| `photoproof_reopen_gallery` | Admin | Reopen a validated gallery |
| `photoproof_export_selection` | Admin | Export selection as CSV |
| `photoproof_save_selection` | Public | Save client selection (priv + nopriv) |
| `photoproof_get_selection` | Public | Get current selection (priv + nopriv) |
| `photoproof_save_comment` | Public | Save a client comment on a photo (priv + nopriv) |

All endpoints verify a nonce and appropriate capabilities/ownership before processing.

</details>

### CSS Custom Properties

The plugin injects CSS variables based on the settings, allowing theme overrides:

```css
:root {
    --pp-bg:         #f5f4f2;
    --pp-active:     #2271b1;
    --pp-text:       #1e293b;
    --pp-img-radius: 0px;
}
```

---

## Architecture

<details>
<summary>File structure</summary>

```
photoproof/
├── photoproof.php                          ← Main entry point
├── readme.txt                              ← WordPress.org readme
├── uninstall.php                           ← Clean uninstall
├── admin/
│   ├── class-photoproof-settings.php       ← Settings page
│   ├── class-photoproof-metaboxes.php      ← Gallery editor metabox
│   ├── class-photoproof-assets.php         ← Admin scripts/styles
│   ├── class-photoproof-admin-columns.php  ← Admin columns + auto-publish
│   ├── css/admin-settings.css
│   └── js/
│       ├── admin-gallery.js                ← Drag & drop upload + recommendations
│       ├── admin-settings.js               ← Settings UI
│       └── vendor/anime.min.js
├── includes/
│   ├── class-photoproof-uploader.php       ← Custom upload + AJAX
│   ├── class-photoproof-renamer.php        ← Deferred renaming on save
│   ├── class-photoproof-watermark.php      ← Watermark generation (GD/Imagick)
│   ├── class-photoproof-router.php         ← UUID routing (parse_request, no rewrite rules)
│   ├── class-photoproof-export.php         ← CSV export
│   ├── class-photoproof-expiration.php     ← Auto-expiration + cron
│   ├── class-photoproof-mailer.php         ← Email notifications
│   └── class-photoproof-helpers.php        ← Template tags + shortcode
├── public/
│   ├── class-photoproof-public.php         ← Front-end AJAX + assets
│   ├── css/
│   │   ├── photoproof-public.css           ← Client gallery styles
│   │   └── photoproof-shortcode.css        ← Shortcode styles
│   └── js/
│       ├── photoproof-public.js            ← Selection, lightbox, auto-save
│       ├── photoproof-selection-anim.js    ← Selection tray animations
│       └── photoproof-masonry.js           ← Masonry layout
├── templates/
│   └── single-photoproof_gallery.php       ← Standalone gallery template
└── languages/
    ├── photoproof.pot
    ├── photoproof-fr_FR.po / .mo / .l10n.php
    ├── photoproof-de_DE.po / .mo / .l10n.php
    ├── photoproof-es_ES.po / .mo / .l10n.php
    └── photoproof-it_IT.po / .mo / .l10n.php
```

</details>

<details>
<summary>Database schema</summary>

The plugin creates `wp_photoproof_galleries` on activation:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Auto-increment |
| `post_id` | bigint | Gallery post ID |
| `client_id` | bigint | Assigned WP user ID (nullable) |
| `folder_path` | varchar | Relative folder path |
| `status` | varchar | `brouillon` / `publie` / `valide` / `ferme` |
| `watermark_settings` | text | Watermark active flag |
| `selection_data` | longtext | Reserved for future use |
| `created_at` | datetime | Creation date |

Gallery photos and client selections also use standard WordPress `wp_posts`, `wp_postmeta` and options tables.

</details>

---

## Privacy & data

PhotoProof is fully self-hosted. **No data ever leaves your WordPress installation.** No tracking, no analytics, no external API calls, no telemetry.

All data — galleries, photos, client selections, comments, settings — is stored locally on your server. Uninstalling the plugin (not deactivating) permanently removes all of it.

See the [`readme.txt`](readme.txt) for the full Privacy and External services sections (required by WordPress.org).

---

## Local Development

### Setup

```bash
git clone https://github.com/stoeck74/PhotoProof.git
# Place inside wp-content/plugins/photoproof in a local WordPress install
# (MAMP, LocalWP, DDEV, wp-env, etc.)
```

### Quality checks

Before submitting a PR, please run:

```bash
# WordPress coding standards (install once: composer global require wp-coding-standards/wpcs)
phpcs --standard=WordPress photoproof/

# Plugin Check (recommended — install from WP.org plugin directory in your dev WP)
# Tools → Plugin Check → run on PhotoProof
```

The plugin currently passes Plugin Check with zero errors. Please keep it that way.

### Email testing

Use **MailHog** to capture outgoing emails locally:

1. Download [MailHog](https://github.com/mailhog/MailHog/releases) and run it
2. Install **WP Mail SMTP**, configure: host `localhost`, port `1025`, no auth
3. Open `http://localhost:8025` to see captured emails

---

## Contributing

Contributions are very welcome! This is a solo-maintained plugin and any help — bug reports, fixes, features, translations — is appreciated.

### Reporting bugs

Open an issue on [GitHub Issues](https://github.com/stoeck74/PhotoProof/issues) with:

- WordPress version
- PHP version
- Steps to reproduce
- Expected vs actual behavior
- Screenshots if relevant

### Suggesting features

Open a discussion or issue with the `enhancement` label. Please describe the use case (real-world photographer scenario) rather than just the technical feature.

### Submitting a Pull Request

1. Fork the repo
2. Create a feature branch (`git checkout -b feature/my-improvement`)
3. Follow WordPress coding standards (`phpcs --standard=WordPress`)
4. Ensure Plugin Check still passes with zero errors
5. Update the changelog in both `readme.txt` and the README
6. Open a PR with a clear description of what changes and why

### Translations

The `.pot` file is in `languages/`. Add or improve translations via [Poedit](https://poedit.net/) or any compatible tool. Existing partial translations (DE, ES, IT) are AI-generated and would benefit from native speakers' review.

---

## Support

- 💬 **WordPress users / general questions** — [WP.org support forum](https://wordpress.org/support/plugin/photoproof/)
- 🐛 **Bug reports / feature requests** — [GitHub Issues](https://github.com/stoeck74/PhotoProof/issues)
- ☕ **Sponsor development** — [Ko-fi](https://ko-fi.com/cedricstoecklin/tip) or [GitHub Sponsors](https://github.com/sponsors/stoeck74)

PhotoProof is free and always will be. If it saved you a monthly subscription or a long afternoon of coding, consider throwing something in the jar — every donation goes directly into development time.

---

## Changelog

### 1.0.0 — First stable release
- Watermark and file renaming logic redesigned: settings provide the tools, gallery metabox decides whether to use them. Both can be toggled at any time.
- Custom file name field in the gallery metabox (overrides the global pattern)
- Live preview of the resulting file name in the metabox
- Complete uninstall: deleting the plugin removes all galleries, photos, settings and database tables
- UUID private links: fixed edge case on subdirectory installs where the slug could collide with the WP path
- Auto-expiration cron is properly scheduled on activation
- Client comments feature: optional, per-photo notes from clients
- Masonry / grid layout choice
- Multilingual: full FR, partial DE / ES / IT
- Code quality: full WordPress.org plugin guidelines compliance, all identifiers properly prefixed, zero Plugin Check errors

### 0.2.0
- Animated selection tray
- Recap panel with expand animation
- Full i18n support

### 0.1.0
- Initial release

---

## License

PhotoProof is released under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

Bundled libraries: anime.js (MIT), Masonry (MIT), imagesLoaded (MIT).

---

<p align="center">
  <em>Built for photographers, by a photographer.</em><br>
  <a href="https://photoproof.cedricstoecklin.com/">photoproof.cedricstoecklin.com</a>
</p>