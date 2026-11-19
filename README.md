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

Two links boot a throwaway WordPress in the browser with the plugin installed and the posts seeded. They differ only in when the widget asks.

**Opt-out**, the proposed behaviour: the posts are there, with the question underneath them.

```text
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/jasmussen/on-this-day/main/blueprint.json
```

**Opt-in**: nothing is queried or shown until the question is answered.

```text
https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/jasmussen/on-this-day/main/blueprint-opt-in.json
```

Locally, add `?otd_mode=opt-in` to the dashboard URL for the same thing. Neither answer is remembered, so the question returns on the next plain load; in the real feature it would persist.

The seed always targets today, so both links stay populated whenever they are opened.

GitHub's "Download ZIP" is also directly installable through **Plugins → Add New → Upload Plugin**. There is no build step.
