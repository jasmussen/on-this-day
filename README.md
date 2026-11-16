# On This Day (prototype)

A dedicated "On This Day" dashboard widget, for [Trac #65116](https://core.trac.wordpress.org/ticket/65116). It lists the posts published on today's month and day in previous years, grouped by year.

A proof of concept for design discussion, not a patch.

## Trying it

In a local [wp-env](https://developer.wordpress.org/block-editor/getting-started/devenv/):

```bash
npx wp-env run cli wp plugin activate on-this-day
```

Seed some history to look at:

```bash
npx wp-env run cli wp eval-file wp-content/plugins/on-this-day/tools/seed.php
```

The seed creates posts on today's month and day across several past years, adds a second author if the site has only one, and is safe to run twice.

Add `?otd_date=MM-DD` to the dashboard URL to look at another day, including one with nothing on it.

## Sharing it

`blueprint.json` boots a throwaway WordPress in the browser with the plugin installed and the posts seeded:

```text
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/jasmussen/on-this-day/main/blueprint.json
```

The seed always targets today, so the link stays populated whenever it is opened.

GitHub's "Download ZIP" is also directly installable through **Plugins → Add New → Upload Plugin**. There is no build step.
