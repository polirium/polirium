# Polirium ERP - Asset Build Guide

Polirium uses an internal Node build script backed by esbuild and Dart Sass. Laravel Mix and its unmaintained webpack development stack are no longer dependencies.

## Requirements

- Node.js 22.12 or newer; Node 24 LTS is recommended.
- npm 10 or newer.
- Run commands from the repository root.

## Commands

```bash
npm install
npm run dev
npm run production
```

`npm run dev` creates readable assets with inline source maps. `npm run production` creates minified assets.

Build a smaller target while developing:

```bash
npm run production -- --core=ui
npm run production -- --core=media
npm run production -- --modules=product
```

The build definition is [scripts/build-assets.mjs](../../scripts/build-assets.mjs). It compiles and publishes these targets:

- `core/ui`: shared SCSS, JavaScript, bundled libraries and TinyMCE.
- `core/media`: media manager SCSS and JavaScript.
- `modules/product`: product and print helper JavaScript.
- `modules/accounting`: accounting JavaScript.
- `modules/print-forms`: editor CSS and JavaScript.

Compiled files are written both to the package `public` directory and to `public/vendor/polirium`, so Composer asset publishing and a local application use the same output.

## Adding module assets

1. Put source files in `platform/modules/<module>/resources/assets/js`, `scss`, or `css`.
2. Add the module and its entry names to the `targets` array in `scripts/build-assets.mjs`.
3. Run the scoped production build.
4. Register the published path in the module asset configuration or Blade view.

Example target:

```js
{
    selector: 'modules/example',
    source: 'platform/modules/example',
    publish: 'public/vendor/polirium/modules/example',
    sass: ['example'],
    js: ['example'],
}
```

## Verification

```bash
npm ls --all
npm audit
npm run production
```

All three commands must exit successfully. Do not suppress dependency advisories or commit generated output from a failed build.

## Troubleshooting

- `ENOENT`: verify the source path in `scripts/build-assets.mjs` and run from the repository root.
- Missing browser asset: verify both the package `public` output and `public/vendor/polirium` output.
- SCSS failure: fix the first Sass error; warnings from upstream Bootstrap compatibility code do not stop the build.
- Stale output: rerun `npm run production` and clear Laravel caches with `php artisan optimize:clear`.

Last updated: 2026-08-15.
