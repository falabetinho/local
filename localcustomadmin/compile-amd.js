const fs = require('fs');
const path = require('path');
const UglifyJS = require('uglify-js');

const srcDir = path.join(__dirname, 'amd', 'src');
const buildDir = path.join(__dirname, 'amd', 'build');

// Ensure build directory exists
if (!fs.existsSync(buildDir)) {
    fs.mkdirSync(buildDir, { recursive: true });
}

// Read the source file
const srcFile = path.join(srcDir, 'price_manager.js');
const buildFile = path.join(buildDir, 'price_manager.min.js');
const mapFile = path.join(buildDir, 'price_manager.min.js.map');

console.log('Reading source file:', srcFile);
const sourceCode = fs.readFileSync(srcFile, 'utf8');

console.log('Minifying...');
const result = UglifyJS.minify(sourceCode, {
    compress: true,
    mangle: {
        reserved: ['M', 'Y', 'require', 'define', 'module', 'exports']
    },
    sourceMap: {
        filename: 'price_manager.min.js',
        url: 'price_manager.min.js.map',
        includeSources: true
    },
    output: {
        comments: /^!/
    }
});

if (result.error) {
    console.error('Error minifying:', result.error);
    process.exit(1);
}

console.log('Writing minified file:', buildFile);
fs.writeFileSync(buildFile, result.code, 'utf8');

if (result.map) {
    console.log('Writing source map:', mapFile);
    fs.writeFileSync(mapFile, result.map, 'utf8');
}

console.log('Done!');
