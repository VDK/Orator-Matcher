# Orator Matcher

Orator Matcher helps turn a conference page, Flickr album, or pasted list of names into possible Wikidata matches. It is mostly meant for contemporary speaker/event pages, but the result page can now be adjusted for historical sets too.

Live version: https://orator-matcher.toolforge.org/

## What It Does

1. Extracts candidate names from a webpage, Flickr album, pasted text, or a pasted list.
2. Lets you remove false positives from the extracted name list.
3. Searches Wikidata for human items matching each name.
4. Shows likely matches with sitelink counts, descriptions, dates, occupations, countries, images, and Commons search links.
5. Lets you filter result matches by life dates, sports people, ORCID people, peerage people, and occupations.

## Main Files

- `index.php` - landing page, URL/Flickr/text/list input, name extraction, and pre-match cleanup.
- `name_filters.php` - configurable extraction filter words.
- `sparql.php` - result page markup and filters.
- `query.php` - Wikidata and SPARQL adapter that returns JSON match data.
- `query.js` - result-page loading, pagination, client-side filtering, rendering, and "load more" behavior.
- `slider.js` and `css/slider.css` - alive-between year slider.
- `css/style.css` - active shared styling.

## Requirements

- PHP with cURL enabled.
- Composer dependencies installed.
- A local web server such as XAMPP/EasyPHP.

Install PHP dependencies with:

```bash
composer install
```

The only Composer dependency at the moment is `fivefilters/readability.php`, used to extract readable text from webpages.

## Local Config

Create a local `variables.php` with API constants:

```php
<?php
define('POSTLIGHTAPI', 'your-postlight-api-key');
define('FLICKRAPIKEY', 'your-flickr-api-key');
```

`variables.php` is local configuration and should not be committed.

## Workflow

Open `index.php` in the local web server. You can:

- paste a URL to scrape,
- paste direct text and let the app extract names,
- paste a pre-existing list of names,
- or use a Flickr album URL.

After extraction, remove false positives from the candidate list, then continue to the Wikidata matching page.

## Result Filters

The result page loads names in pages and fetches Wikidata matches lazily. It supports:

- an "alive between" year range, defaulting to 2010 through the current year,
- include/exclude toggles for sports people, ORCID people, and peerage people,
- occupation filters based on currently visible loaded matches,
- per-name "load more search results" for ambiguous names.

Filtering is done client-side after `query.php` returns structured JSON for each Wikidata item.

## Notes

- Wikidata descriptions are used when available, with generated country/occupation text as fallback.
- Commons thumbnails are requested at 120px width and displayed in square 120px frames.
- API keys and generated dependencies should stay out of commits.
