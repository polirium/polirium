let mix = require('laravel-mix');
let path = require('path');
let directory = path.basename(path.resolve(__dirname));

// Get the relative path from root to this directory
const rootPath = path.resolve(__dirname, '../../..');
const relativePath = path.relative(rootPath, __dirname);

// Path configuration
const source = relativePath;
const assets = source + '/resources/assets';
const publicPath = source + '/public';
const productFolder = 'public/vendor/polirium/modules/' + directory;

mix.disableNotifications();

// CSS files
const cssFiles = [
    'editor',
];

// JS files
const jsFiles = [
    'editor',
];

// Compile CSS files
cssFiles.forEach(function (file) {
    mix.copy(assets + '/css/' + file + '.css', productFolder + '/css/' + file + '.min.css');
});

// Compile JS files
jsFiles.forEach(function (file) {
    mix.js(assets + '/js/' + file + '.js', productFolder + '/js/' + file + '.min.js');
});

// Copy built files back to public folder
mix.then(() => {
    const fs = require('fs');

    // Copy CSS files
    cssFiles.forEach(function (file) {
        const sourceFile = productFolder + '/css/' + file + '.min.css';
        const targetFile = publicPath + '/css/' + file + '.min.css';

        if (fs.existsSync(sourceFile)) {
            const targetDir = path.dirname(targetFile);
            if (!fs.existsSync(targetDir)) {
                fs.mkdirSync(targetDir, { recursive: true });
            }
            fs.copyFileSync(sourceFile, targetFile);
        }
    });

    // Copy JS files
    jsFiles.forEach(function (file) {
        const sourceFile = productFolder + '/js/' + file + '.min.js';
        const targetFile = publicPath + '/js/' + file + '.min.js';

        if (fs.existsSync(sourceFile)) {
            const targetDir = path.dirname(targetFile);
            if (!fs.existsSync(targetDir)) {
                fs.mkdirSync(targetDir, { recursive: true });
            }
            fs.copyFileSync(sourceFile, targetFile);
        }
    });
});
