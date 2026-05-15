# WT Multi SEO Pro — Usage & configuration guide

This document explains how to install the plugin, where each setting lives, and how global options interact with per-content overrides (posts, terms, CPT archives).

---

## 1. What this plugin does

WT Multi SEO Pro is a WordPress SEO plugin built around **Polylang**. It provides:

- **Global settings** under **Polylang SEO** (title templates, sitemap, social, schema, robots, performance, tools).
- **Per-post / per-page SEO** via an editor metabox (where enabled).
- **Per-term SEO** for enabled taxonomies.
- **Custom post type (CPT) archive SEO** when the post type has `has_archive` but no editable “page” for that archive.
- **XML sitemaps** (index + sub-sitemaps, optional News/Video, optional per-language files).
- **Hreflang**, **canonical**, **Open Graph / Twitter**, **JSON-LD schema**, **robots.txt** additions, and **IndexNow** (optional).

All options are stored in the database with the **`plseo_`** prefix (for example `plseo_sitemap_enabled`).

---

## 2. Requirements

| Requirement | Notes |
|-------------|--------|
| WordPress | 6.0 or newer |
| PHP | 8.0 or newer |
| Polylang | **Must be active.** If Polylang is missing, the plugin shows an admin notice and does not load its features. |

---

## 3. Installation & first visit

1. Copy the plugin folder to `wp-content/plugins/wt-multi-seo-pro` (or your chosen folder name).
2. In **Plugins**, activate **WT Multi SEO Pro**.
3. In the admin sidebar, open **Polylang SEO**.

**Recommended first-time order**

1. **Polylang**: add languages and set the **default language** (e.g. English).
2. **Polylang SEO → General**: set title templates and enable metabox coverage for the post types / taxonomies you use.
3. **Social / OG**: set a **default OG image** (strongly recommended).
4. **Sitemap**: confirm **Enable Sitemap**, include the right post types, set **URLs Per Sitemap**, then open **Tools → View Sitemap Index** to verify.
5. **Robots & Index**: review noindex rules and canonical behavior to match your URL strategy.
6. **Structured Data**: fill Organization (or Person) and optional Local Business if relevant.

After changing permalink-related behavior, use **Polylang SEO → Tools → Flush Rewrite Rules** if sitemap or archive URLs misbehave.

---

## 4. Admin navigation (tabs)

The top tabs under **Polylang SEO** are separate admin pages (each saves its own form):

| Tab | Purpose |
|-----|---------|
| **General** | Title templates, metabox coverage, CPT archive SEO, site verification codes |
| **Sitemap** | XML sitemap, News/Video sitemaps, multilingual sitemap options, pings, IndexNow |
| **Social / OG** | Open Graph, Twitter/X cards, social profile URLs (used in schema `sameAs`) |
| **Structured Data** | JSON-LD: Organization/Person, Article, GEO/AI-related schema, Local Business |
| **Robots & Index** | Noindex rules, meta robots “max-*” SERP controls, AI crawler / robots.txt, canonical & redirects |
| **Performance** | Head cleanup (RSD, shortlink, oEmbed), preconnect / DNS-prefetch hints |
| **Tools** | Open sitemap URLs, ping search engines, flush rewrite rules, quick debug info |

Each settings page ends with **Save Settings** for that tab (except **Tools**, which uses separate actions).

---

## 5. General settings (detailed)

### 5.1 Title templates

**Title Separator** — Character used wherever `%%sep%%` appears in templates (e.g. `-`, `|`, `»`).

**Tokens** (as shown in the UI):

- `%%title%%` — Current content title (or archive name where applicable).
- `%%sitename%%` — Site name from WordPress settings.
- `%%tagline%%` — Site tagline.
- `%%sep%%` — Separator chosen above.
- `%%page%%` — Pagination suffix when relevant.
- `%%currentyear%%` — Current year.
- `%%searchterm%%` — Search query on search results.

**Fields**

- **Homepage Title** / **Homepage Description** — Front page meta when the homepage is a “blog” or you rely on global homepage SEO.
- **Single Title Template** — Default pattern for single posts/pages (and other singles using the same pipeline).
- **Archive Title Template** — Archive views (categories, tags, date, author, CPT archives where not overridden).
- **Search Title Template** — Internal search results.
- **404 Title Template** — Not found pages.

Use templates that read naturally in **each language** if you translate strings via your theme or multilingual workflow; the plugin applies the same token logic site-wide.

### 5.2 SEO Metabox coverage

**Post Types** — Check every public type that should show the **SEO Settings** metabox in the block/classic editor.

**Taxonomies** — Check every public taxonomy that should show term-level SEO fields on **Edit term** screens.

If a type is unchecked, editors cannot set plugin SEO fields for that content (global templates and fallbacks may still apply on the front end depending on context).

### 5.3 Custom Post Type Archive SEO

Shown only for **custom** (non-built-in) post types that are **public** and have **`has_archive`**.

**Layout**

- **Top row (horizontal tabs)** — One tab per CPT archive (e.g. Products, Events). Reduces scrolling when you have many CPTs.
- **Left column (vertical tabs)** — One tab per **Polylang language** (or a single “Default” fallback if Polylang has no languages yet).
- **Right panel** — Fields for the selected language.

**Fields (per language, per CPT)**

- **Inherit Defaults** — When enabled, empty Open Graph / Twitter fields fall back to SEO title, meta description, and image-style defaults as described in the UI.
- **SEO Title**, **Meta Description**, **Canonical URL**
- **Open Graph** title, description, image URL
- **Twitter Image URL**

**Copy from default language** — For non-default languages, copies values from the **default Polylang language** row into the current language (same CPT). Useful as a starting point before translation.

**Saving** — Always click **Save Settings** on the General page after editing CPT archive fields.

### 5.4 Site verification

The **Site Verification** block on **General** is for the **HTML meta tag** method of proving you control the site. You paste **only the verification string** (the value inside `content="…"`), **not** the full `<meta …>` tag — the same idea as the “Verification code only” placeholder for Google.

#### What the plugin outputs

When a field is filled, the plugin prints the matching tag in the public page `<head>`:

| Field in admin | Meta tag on the site |
|----------------|----------------------|
| Google Search Console | `<meta name="google-site-verification" content="…" />` |
| Bing Webmaster Tools | `<meta name="msvalidate.01" content="…" />` |
| Yandex Webmaster | `<meta name="yandex-verification" content="…" />` |
| Pinterest | `<meta name="p:domain_verify" content="…" />` |

#### Do you need to edit DNS?

**No — not for this approach.** Search consoles offer **different** verification options; you normally complete **one** of them per property:

| Method | What you do |
|--------|-------------|
| **HTML tag** (this plugin) | In Google Search Console, choose **HTML tag** verification, copy the **code** only, paste it into the plugin field, save, then click **Verify** in Google. No DNS TXT record is required **for that verification**. |
| **DNS TXT record** | You add a record at your domain registrar/DNS host. The plugin **does not** create DNS records. Use DNS **instead** of the meta tag if you pick that method in the search console. |

If you already verified the property with **DNS** (or a file upload, etc.), the site is already verified — you do **not** have to fill these fields unless you still want the meta tags for consistency or another tool.

#### Google Search Console — typical steps

1. In **Google Search Console**, add the property and select **HTML tag** as the verification method.
2. Google shows a tag like `<meta name="google-site-verification" content="AbCdEf123…" />` — copy **only** `AbCdEf123…` (the part inside `content="…"`).
3. In WordPress: **Polylang SEO → General → Site verification → Google Search Console**, paste that string and **Save Settings**.
4. Confirm the homepage (or the URL Google is checking) loads with the meta tag in the HTML source (`View Page Source` or devtools → Elements → `<head>`).
5. In Search Console, click **Verify**.

Use the same pattern for Bing, Yandex, and Pinterest: use each provider’s **meta tag** verification flow and paste **only** the content value into the matching field.

---

## 6. Sitemap settings (detailed)

### 6.1 Main XML sitemap

- **Enable Sitemap** — When on, the index is served at **`/sitemap.xml`** (pretty permalinks assumed). WordPress core sitemaps are disabled to avoid duplicates.
- **Include Posts / Pages / Taxonomies / Authors** — Toggle each section.
- **Include Images** — Adds image entries (featured + in-content images, capped per post).
- **Custom Post Types** — Check each non-core public type that should appear in **`sitemap-{slug}.xml`** (subject to pagination).

### 6.2 Google News sitemap

- **Enable News Sitemap** — Adds **`/sitemap-news.xml`** to the index when enabled.
- **Publication Name** — News publication name (defaults conceptually to site name if empty).
- **News Post Types** — Which types can appear (only recent articles per Google’s 48-hour window are included).

### 6.3 Video sitemap

- **Enable Video Sitemap** — **`/sitemap-video.xml`** when enabled.
- **Video Post Types** — Types scanned for video meta.
- Posts need **`_video_url`** and/or **`_video_embed_url`** post meta (and optionally duration/thumbnail fields as implemented in your theme) for entries to appear.

### 6.4 Multilingual SEO (sitemap)

- **hreflang Links** — Adds `xhtml:link` alternate entries inside URL sitemap entries for translated sets (when applicable).
- **x-default** — Adds an `x-default` alternate when enabled (strategy also relates to **Robots & Index → x-default Strategy** for `<link rel="alternate">` output).
- **Per-Language Sitemaps** — When enabled, the index lists **additional** sitemaps per language and post type, e.g. `sitemap-fr-posts.xml`, `sitemap-ar-product.xml`.

**Important:** The **Polylang default language** is **not** duplicated in per-language filenames. Default-language URLs are already included in the **main** sitemaps (`sitemap-posts.xml`, `sitemap-product.xml`, etc.) together with hreflang. Only **non-default** languages get extra `sitemap-{lang}-{type}.xml` files.

The **Detected Languages** table on this screen shows each language slug, locale, and derived hreflang tag for debugging.

### 6.5 Search engine notifications

- **Ping Google / Ping Bing** — After publishing, the plugin can notify search engines (debounced; see Tools for manual ping).
- **IndexNow** — Optional instant URL submission; requires **IndexNow API Key**. When configured, verification file behavior is handled by the plugin as described in the UI.
- **URLs Per Sitemap** — Maximum URLs per file (50–2500). Larger sets are split into **`sitemap-{slug}-2.xml`**, **`sitemap-{slug}-3.xml`**, etc. The same pagination applies to **per-language** sitemaps (e.g. `sitemap-ar-product-2.xml`).

### 6.6 Exclude Post IDs

Comma-separated list of post IDs to **exclude from all post-type sitemaps** (useful for landing pages you do not want listed).

### 6.7 Useful URLs (with pretty permalinks)

| URL | Role |
|-----|------|
| `/sitemap.xml` | Sitemap index |
| `/sitemap-xsl.xsl` | Human-readable XSL view |
| `/sitemap-posts.xml` | Blog posts (paginated if large) |
| `/sitemap-pages.xml` | Pages |
| `/sitemap-{cpt}.xml` | Custom post type |
| `/sitemap-taxonomies.xml` | Enabled taxonomy archives |
| `/sitemap-authors.xml` | Author archives (if enabled) |

Use **`/sitemap.xml`** without relying on a trailing slash after `.xml`; if a URL returns blank, flush rewrites (Tools) and confirm **Enable Sitemap** is on.

---

## 7. Social / OG settings (detailed)

### Open Graph

- **Enable Open Graph** — Master switch for OG tags.
- **Default OG Image** — Fallback when a post has no featured image; use a 1200×630 style image when possible.
- **Facebook App ID / Facebook Admin IDs** — Optional; for Facebook insights / administration.

### Twitter / X cards

- **Enable Twitter Cards**
- **Card Type** — `summary` or `summary_large_image`.
- **Site @handle** / **Creator @handle** — Optional `twitter:site` / `twitter:creator`.

### Social profiles (schema `sameAs`)

Full URLs for Facebook, X/Twitter, LinkedIn, YouTube, Instagram, Pinterest, TikTok. These feed **Organization/Person** `sameAs` in JSON-LD when schema is enabled.

---

## 8. Structured Data settings (detailed)

### Core schema

- **Enable Schema** — Global JSON-LD `@graph` output.
- **Entity Type** — **Organization** vs **Person**.
- **Name**, **URL**, **Logo URL** — Brand identity (logo can fall back to the Customizer site logo if left empty, per UI hint).
- **Article Type** — `BlogPosting`, `Article`, `NewsArticle`, or `TechArticle` for article-style schema.
- **Contact Type** — Optional; used with Local Business phone for `ContactPoint` when applicable.

### GEO & AI-related blocks

Toggles for **BreadcrumbList**, **Article** enrichment, **SearchAction** (sitelinks search box), **SpeakableSpecification**, and **SiteNavigationElement** (primary menu). Turn off any block you do not want in the graph.

### Local Business

- **Enable Local Business** — Outputs a LocalBusiness (or subtype) with address, phone, hours, geo coordinates, price range, etc.
- Choose a **Business Type** matching your physical or service business.

---

## 9. Robots & Index settings (detailed)

### Indexing rules

Global **noindex** toggles for: search, 404, author archives, date archives, attachment pages, empty taxonomies, and **paginated archives beyond page 1** (`page/2/`, etc.).

### SERP appearance (advanced robots)

Controls **max-snippet**, **max-image-preview**, **max-video-preview**, and optional **Googlebot** / **Bingbot**-specific directives. These affect how snippets may appear in Google/Bing.

### AI crawler management

- **Block All AI Bots** — Adds robots.txt rules for common AI crawlers (does not replace normal Google/Bing indexing).
- **Custom AI Bot Rules** — Raw `robots.txt` fragments when granular control is needed (see UI for when this applies).

### Canonical & redirects

- **Enable Canonical URLs**
- **Force HTTPS**, **Trailing Slash**, **Strip Query Parameters** — Normalize canonical URLs.
- **x-default Strategy** — Whether `hreflang` `x-default` points at the default language version or is omitted.
- **Redirect 404 to Home** — Aggressive; usually leave off unless you have a specific reason.
- **Keep Old Slug Redirects** — WordPress native old-slug behavior.

### robots.txt

- **Crawl Delay** — Optional; often left empty on modern hosts.
- **Custom Rules** — Appended to the plugin-managed `robots.txt` output.

---

## 10. Performance settings (detailed)

The **Performance** tab controls optional **`<head>` cleanup** and **resource hints** (browser performance, not SEO ranking directly).

### 10.1 Head cleanup

#### Remove RSD link (EditURI / RSD)

**What it is:** WordPress outputs discovery links such as `<link rel="EditUri" …>` (RSD — *Really Simple Discovery*). They told old **remote blog editors** (desktop clients) that the site supported XML-RPC–style **remote publishing**.

**Why turn it on:** Few sites use that today. Removing it **cleans up `<head>`** and stops advertising that discovery surface on every page. It does **not** by itself disable all of XML-RPC everywhere; it mainly removes the **HTML discovery** link.

#### Remove Shortlink (`wp_shortlink` meta)

**What it is:** WordPress can add `<link rel='shortlink' href='…'>` — a **short alternate URL** for the same post (often a `?p=123` style URL).

**Why turn it on:** Your canonical URL is normally the **pretty permalink**. The shortlink is **redundant** and adds an extra URL signal in the document. Removing it is common **housekeeping** and does **not** break normal permalinks or sharing when canonicals are correct.

#### Remove oEmbed discovery

**What it is:** For content that supports it, WordPress adds **oEmbed discovery** `<link>` tags in `<head>` and may load **`wp-embed.js`** so that when someone pastes your post URL on **another WordPress site**, that site can **fetch and iframe-embed** your content as a preview card.

**Why turn it on:** If you do **not** need other WordPress sites to auto-embed your posts, enabling this **removes extra `<head>` links and the embed host script** — slightly leaner pages.

**Tradeoff:** **Off-site WordPress embeds** of your URLs may stop working or degrade. **Embeds inside your own content** (YouTube blocks, X cards, etc.) use different mechanisms and are **not** the same thing. This plugin defaults **oEmbed removal to off** so embed discovery stays unless you explicitly enable it.

**Summary**

| Option | Removes | Typical reason |
|--------|---------|----------------|
| **RSD** | Remote-editor discovery links | Legacy noise; cleaner head |
| **Shortlink** | Alternate short URL `<link>` | One clear URL story in markup |
| **oEmbed discovery** | oEmbed discovery links + related host JS | Leaner pages if you don’t need external WP-to-WP embeds |

### 10.2 Resource hints

**What “resource hints” are:** Extra `<link>` tags in `<head>` that tell the browser **work you will need soon** — for example “we will request files from this other hostname.” The browser can start **DNS lookup** and (for preconnect) **TCP + TLS** early, so when your CSS or JS references that host, the first real request is faster. This is a **front-end performance** tweak; it does not change rankings by itself.

#### “One absolute URL per line” — what that means

The plugin reads each textarea **line by line**. Every **non-empty line** becomes **one** `<link rel="preconnect" …>` or `<link rel="dns-prefetch" …>` tag. So:

- Put **exactly one URL on each line** (no commas, no semicolons, no multiple domains on the same line).
- Use an **absolute URL**: include the scheme and host (and usually **only** the origin you care about — path is allowed but the hint is about the **server** you will connect to).

**Good examples (each on its own line):**

```text
https://fonts.googleapis.com
https://fonts.gstatic.com
https://cdn.example.com
```

**Avoid:**

- **Relative URLs** like `/wp-content/…` — hints are for **other origins** (different host than your page), not paths on the same site.
- **Multiple URLs on one line** — only the first might be used correctly, or the line may be invalid after sanitization.
- **Bare hostnames** without `https://` — always use a full URL the browser can resolve (the plugin passes values through URL sanitization).

Empty lines are ignored.

#### Preconnect vs DNS prefetch

| Field | HTML output | When it helps |
|-------|-------------|----------------|
| **Preconnect Domains** | `<link rel="preconnect" href="…" crossorigin />` | You **will** load important assets (fonts, scripts, styles) from that origin **soon**. Does DNS + connection setup early. Slightly more work than prefetch alone — use for 1–4 origins you really hit on every page. |
| **DNS Prefetch Domains** | `<link rel="dns-prefetch" href="…" />` | You may load from that origin **later** or less critically. Only starts **DNS resolution** early — cheaper than preconnect. Good for analytics, secondary CDNs, or embeds. |

**Typical pattern (Google Fonts):** preconnect to `https://fonts.googleapis.com` and `https://fonts.gstatic.com` if your theme loads fonts from Google — many tutorials list exactly those two lines.

**Caution:** Listing many origins or origins you never request adds **small wasted work** on every page load. Only add hosts your templates or plugins **actually** request.

---

## 11. Tools page

- **View Sitemap Index** (and News/Video when enabled) — Opens URLs in a new tab.
- **Ping Search Engines** — Manually triggers the same notification routine used after publishes (AJAX; status message appears next to the button).
- **Flush Rewrite Rules** — Re-saves WordPress rewrite rules; use after permalink changes or if `sitemap.xml` is not handled by the plugin.
- **Debug Information** — Plugin version, Polylang status, language list, sitemap and `robots.txt` links.

---

## 12. Post & page editor (SEO metabox)

On enabled post types, the **SEO Settings** metabox typically includes:

- SEO title, meta description, canonical  
- Open Graph title/description/image  
- Twitter image  
- **Noindex** / **Nofollow**  
- Option to **disable plugin SEO output** for that single item  

**Fallbacks when fields are empty** (typical behavior):

- SEO title → post title  
- Meta description → trimmed excerpt/content  
- Canonical → permalink  
- OG fields → chain from SEO title/description/image  
- Twitter image → featured image or defaults  

Always fill SEO fields **per translation** in Polylang for full control in each language.

---

## 13. Taxonomy term screens

For enabled taxonomies, edit a term and set SEO title, description, canonical, social fields, and robots as offered. These override global/archive defaults for that term’s archive URL.

---

## 14. Multilingual workflow (Polylang)

1. Create content in the **default language**, set SEO fields.  
2. **Translate** the post or page in Polylang; open each translation and adjust SEO fields for that locale.  
3. For **CPT archives**, use **General → Custom Post Type Archive SEO** language tabs.  
4. In **Sitemap**, enable **Per-Language Sitemaps** only if you need separate files for non-default languages; hreflang in the main sitemaps often suffices for many sites.

---

## 15. Troubleshooting

| Issue | What to check |
|-------|----------------|
| Polylang notice, no SEO features | Install and activate Polylang; configure at least one language. |
| Blank `sitemap.xml` | **Sitemap → Enable Sitemap**; **Tools → Flush Rewrite Rules**; open `/sitemap.xml` (not only a trailing-slash variant). |
| Too few URLs in a language sitemap | **URLs Per Sitemap**; check pagination links in the **index** (`…-2.xml`, `…-3.xml`). |
| CPT archive SEO not shown | CPT must be `public` and `has_archive => true`. |
| Settings “lost” on save | Unchecked checkboxes in WordPress often mean “off”; re-check boxes that must stay enabled. |
| Frontend unchanged | Caching (plugin, host, CDN); view HTML source; hard-refresh. |

---

## 16. Notes for developers

- **Options**: keys in code are usually referenced **without** the `plseo_` prefix via `PLSEO_Helpers::get_option()`, but the database stores **`plseo_*`**.  
- **Post meta**: `_plseo_*` keys on posts/terms.  
- **CPT archive SEO** option: `plseo_cpt_archive_seo` — nested array:  
  `[ post_type_slug => [ lang_slug => [ field => value, … ], … ], … ]`  
  Legacy flat keys under a post type may still be read for backward compatibility in some code paths.

---

## 17. Quick checklist before launch

- [ ] Polylang languages + default language set  
- [ ] General: title templates + metabox coverage  
- [ ] CPT archive SEO completed for each relevant CPT × language  
- [ ] Social: default OG image set  
- [ ] Sitemap: includes correct types; index opens; submit URL in Search Console / Bing  
- [ ] Robots: noindex rules match staging vs production  
- [ ] Schema: Organization/Person + Article toggles reviewed  
- [ ] Performance: resource hints for CDN/fonts if used  

---

*Document version: aligned with plugin admin screens and sitemap behavior as of WT Multi SEO Pro 2.x.*
