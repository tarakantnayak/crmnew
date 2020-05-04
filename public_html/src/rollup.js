/**
 * build vue files in dev mode
 *
 * @description
 * @license YetiForce Public License 3.0
 * @author Tomasz Poradzewski <t.poradzewski@yetiforce.com>
 */

const rollup = require('rollup'),
	finder = require('findit')('layouts'),
	alias = require('rollup-plugin-alias'),
	path = require('path'),
	vue = require('rollup-plugin-vue'),
	sass = require('rollup-plugin-sass'),
	commonjs = require('rollup-plugin-commonjs'),
	resolve = require('rollup-plugin-node-resolve'),
	globals = require('rollup-plugin-node-globals'),
	json = require('rollup-plugin-json'),
	babel = require('rollup-plugin-babel'),
	minify = require('rollup-plugin-babel-minify')

let filesToMin = []
const sourcemap = true
const plugins = [
	alias({
		resolve: ['.vue', '.js', '.json'],
		entries: [
			{ find: '~', replacement: __dirname },
			{ find: 'store', replacement: `${__dirname}/store/index` },
			{ find: 'components', replacement: `${__dirname}/components` }
		]
	}),
	json(),
	sass(),
	vue({
		needMap: false,
		scss: {
			indentedSyntax: true
		}
	}),
	resolve(),
	commonjs(),
	globals(),
	babel({
		presets: ['vue'],
		exclude: 'node_modules/**'
	})
]

if (process.env.NODE_ENV === 'production') {
	plugins.push(minify())
}

async function build(filePath, isWatched = false) {
	const outputFile = `../${filePath.replace('.js', '.vue.js')}`
	const inputOptions = {
		input: filePath,
		external: 'vue',
		plugins
	}

	const outputOptions = {
		name: outputFile,
		file: outputFile,
		format: 'iife',
		globals: {
			vue: 'Vue'
		},
		sourcemap
	}

	if (process.env.NODE_ENV === 'development') {
		if (!isWatched) {
			const watcher = rollup.watch({
				...inputOptions,
				output: [outputOptions],
				watch: {
					exclude: 'node_modules/**'
				}
			})

			watcher.on('event', event => {
				if (event.code === 'START') {
					console.log('Building... ' + filePath)
					build(filePath, true).then(e => {
						console.log('Finished! ' + filePath)
					})
				}
			})
		}
	}

	const bundle = await rollup.rollup(inputOptions)
	const { code, map } = await bundle.generate(outputOptions)
	await bundle.write(outputOptions)
}

finder.on('directory', (dir, stat, stop) => {
	const base = path.basename(dir)
	if (
		base === 'node_modules' ||
		base === 'libraries' ||
		base === 'vendor' ||
		base === '_private' ||
		base === 'store' ||
		base === 'utils'
	)
		stop()
})

finder.on('file', (file, stat) => {
	const re = new RegExp('(?<!\\.min)\\.js$')
	if (file.includes('roundcube') && !(!file.includes('skins') && file.includes('yetiforce'))) return
	if (file.match(re)) filesToMin.push(file)
})

finder.on('end', () => {
	filesToMin.forEach(file => {
		console.log(file)
		build(file)
	})
})
