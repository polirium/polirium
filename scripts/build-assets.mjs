import { build, transform } from 'esbuild'
import { watch } from 'node:fs'
import { cp, mkdir, readFile, writeFile } from 'node:fs/promises'
import path from 'node:path'
import process from 'node:process'
import { fileURLToPath } from 'node:url'
import * as sass from 'sass'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const production = process.argv.includes('--production')
const watchMode = process.argv.includes('--watch')

function requestedTarget(type) {
    const argument = process.argv.find((value) => value.startsWith(`--${type}=`))

    return argument?.split('=', 2)[1] ?? process.env[`npm_config_${type}`]
}

const targets = [
    {
        selector: 'core/ui',
        source: 'platform/core/ui',
        publish: 'public/vendor/polirium/core/ui',
        sass: [
            'polirium-core',
            'polirium-flags',
            'polirium-marketing',
            'polirium-payments',
            'polirium-props',
            'polirium-social',
            'polirium-themes',
            'polirium-vendors',
            'app',
        ],
        js: ['polirium', 'theme', 'app', 'dashboard'],
        copies: [
            ['platform/core/ui/resources/assets/libs', 'public/vendor/polirium/core/ui/libs'],
            ['platform/core/ui/resources/assets/libs', 'platform/core/ui/public/libs'],
            ['node_modules/tinymce', 'public/vendor/polirium/core/ui/libs/tinymce'],
            ['node_modules/tinymce', 'platform/core/ui/public/libs/tinymce'],
        ],
    },
    {
        selector: 'core/media',
        source: 'platform/core/media',
        publish: 'public/vendor/polirium/core/media',
        sass: ['media-manager'],
        js: ['media-manager'],
    },
    {
        selector: 'modules/product',
        source: 'platform/modules/product',
        publish: 'public/vendor/polirium/modules/product',
        js: ['product', 'print-helper'],
    },
    {
        selector: 'modules/accounting',
        source: 'platform/modules/accounting',
        publish: 'public/vendor/polirium/modules/accounting',
        js: ['accounting'],
    },
    {
        selector: 'modules/print-forms',
        source: 'platform/modules/print-forms',
        publish: 'public/vendor/polirium/modules/print-forms',
        css: ['editor'],
        js: ['editor'],
    },
]

const requested = {
    core: requestedTarget('core'),
    modules: requestedTarget('modules'),
}
const hasSelection = Object.values(requested).some(Boolean)

const selected = targets.filter((target) => {
    const [type, name] = target.selector.split('/')
    const targetRequest = requested[type]

    if (hasSelection && !targetRequest) {
        return false
    }

    return !targetRequest || targetRequest === 'true' || targetRequest.split(',').includes(name)
})

async function ensureParent(file) {
    await mkdir(path.dirname(file), { recursive: true })
}

const strictSassLogger = {
    warn(message, options) {
        const location = options.span?.url
            ? ` (${fileURLToPath(options.span.url)}:${options.span.start.line + 1})`
            : ''

        throw new Error(`Sass warning${location}: ${message}`)
    },
}

// Sass leaves `@import "file.css"` as a plain CSS import. Laravel Mix inlined
// those files; served from /vendor/polirium/... the relative paths 404, so the
// datatable, flatpickr and tom-select styles were silently missing.
async function inlineCssImports(css, fromDirectory, seen = new Set()) {
    const pattern = /@import\s*(?:url\()?\s*["']([^"']+\.css)["']\s*\)?\s*;/g
    let output = ''
    let last = 0

    for (const match of css.matchAll(pattern)) {
        const file = path.resolve(fromDirectory, match[1])

        if (seen.has(file)) {
            throw new Error(`Circular CSS import: ${file}`)
        }

        let content

        try {
            content = await readFile(file, 'utf8')
        } catch {
            throw new Error(`CSS import not found: ${match[1]} (resolved to ${file})`)
        }

        output += css.slice(last, match.index)
            + await inlineCssImports(content, path.dirname(file), new Set([...seen, file]))
        last = match.index + match[0].length
    }

    return output + css.slice(last)
}

async function writeCss(source, destination, compileSass = false) {
    const sourcePath = path.join(root, source)
    const compiled = compileSass
        ? sass.compile(sourcePath, {
            loadPaths: [path.join(root, 'node_modules')],
            // Bootstrap 5.3's supported customization API still uses the
            // legacy import graph. Keep that API compatible while treating
            // every other Sass warning as a build failure.
            quietDeps: true,
            silenceDeprecations: ['import'],
            fatalDeprecations: ['global-builtin', 'if-function'],
            logger: strictSassLogger,
            style: production ? 'compressed' : 'expanded',
        }).css
        : await readFile(sourcePath, 'utf8')
    const css = await inlineCssImports(compiled, path.dirname(sourcePath))
    const output = production
        ? (await transform(css, { loader: 'css', minify: true, target: 'es2020' })).code
        : css

    await ensureParent(destination)
    await writeFile(destination, output)
}

async function buildTarget(target) {
    for (const name of target.sass ?? []) {
        const destination = path.join(root, target.publish, 'css', `${name}.min.css`)
        await writeCss(path.join(target.source, 'resources/assets/scss', `${name}.scss`), destination, true)
        await ensureParent(path.join(root, target.source, 'public/css', `${name}.min.css`))
        await cp(destination, path.join(root, target.source, 'public/css', `${name}.min.css`))
    }

    for (const name of target.css ?? []) {
        const destination = path.join(root, target.publish, 'css', `${name}.min.css`)
        await writeCss(path.join(target.source, 'resources/assets/css', `${name}.css`), destination)
        await ensureParent(path.join(root, target.source, 'public/css', `${name}.min.css`))
        await cp(destination, path.join(root, target.source, 'public/css', `${name}.min.css`))
    }

    for (const name of target.js ?? []) {
        const destination = path.join(root, target.publish, 'js', `${name}.min.js`)
        await ensureParent(destination)
        await build({
            entryPoints: [path.join(root, target.source, 'resources/assets/js', `${name}.js`)],
            outfile: destination,
            bundle: true,
            format: 'iife',
            minify: production,
            platform: 'browser',
            sourcemap: production ? false : 'inline',
            target: ['es2020'],
        })
        await ensureParent(path.join(root, target.source, 'public/js', `${name}.min.js`))
        await cp(destination, path.join(root, target.source, 'public/js', `${name}.min.js`))
    }

    for (const [source, destination] of target.copies ?? []) {
        await mkdir(path.join(root, destination), { recursive: true })
        await cp(path.join(root, source), path.join(root, destination), {
            force: true,
            recursive: true,
        })
    }
}

async function buildSelectedTargets() {
    for (const target of selected) {
        await buildTarget(target)
        process.stdout.write(`Built ${target.selector}\n`)
    }
}

await buildSelectedTargets()

if (watchMode) {
    let timer
    let building = false
    let pending = false

    const rebuild = async () => {
        if (building) {
            pending = true

            return
        }

        building = true

        try {
            await buildSelectedTargets()
        } catch (error) {
            console.error(error)
        } finally {
            building = false

            if (pending) {
                pending = false
                await rebuild()
            }
        }
    }

    watch(path.join(root, 'platform'), { recursive: true }, (_event, filename) => {
        if (!filename?.includes('resources/assets/')) {
            return
        }

        clearTimeout(timer)
        timer = setTimeout(rebuild, 100)
    })

    process.stdout.write('Watching platform asset sources...\n')
}
