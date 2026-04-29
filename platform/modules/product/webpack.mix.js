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

// JS files
const jsFiles = [
    'product',
    'print-helper',
];

// Compile JS files
jsFiles.forEach(function (file) {
    mix.js(assets + '/js/' + file + '.js', productFolder + '/js/' + file + '.min.js');
});

// Copy built files back to public folder
mix.then(() => {
    const fs = require('fs');

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
