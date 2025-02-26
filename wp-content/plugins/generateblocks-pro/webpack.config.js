const { resolve } = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const ESLintPlugin = require( 'eslint-webpack-plugin' );
const CopyPlugin = require( 'copy-webpack-plugin' );
const {
	camelCaseDash,
} = require( '@wordpress/dependency-extraction-webpack-plugin/lib/util' );
const DependencyExtractionWebpackPlugin = require( './config/webpack-dependency-extraction' );

const isProduction = process.env.NODE_ENV === 'production';
const defaultEntries = defaultConfig.entry();
const { dependencies } = require( './package' );

const EDGE22_NAMESPACE = '@edge22/';

// Get all edge22 packages.
const edge22Packages = Object.keys( dependencies )
	.filter(
		( packageName ) => packageName.startsWith( EDGE22_NAMESPACE )
	)
	.map( ( packageName ) => packageName.replace( EDGE22_NAMESPACE, '' ) );

// Setup entries for each edge22 package.
const packageEntries = Object.fromEntries(
	edge22Packages.map( ( packageName ) => {
		const filename = 'styles-builder' === packageName ? 'generateblocks-pro' : 'index';
		return [
			packageName,
			{
				import: require.resolve( `./node_modules/@edge22/${ packageName }/dist/${ filename }.js` ),
				library: {
					name: [ 'gbp', camelCaseDash( packageName ) ],
					type: 'window',
				},
			},
		];
	} )
);

const packageCopyPatterns = edge22Packages.map( ( packageName ) => {
	const filename = 'styles-builder' === packageName ? 'generateblocks-pro' : 'index';
	const from = resolve( `./node_modules/@edge22/${ packageName }/dist/${ filename }.asset.php` );
	const to = resolve( __dirname, `dist/${ packageName }-imported.asset.php` );
	return {
		from,
		to,
	};
} );

// Declare any other entries specific to this plugin.
const pluginEntries = {
	'asset-library': './src/asset-library.js',
	'global-class-dashboard': './src/global-class-dashboard.js',
	'pattern-library': './src/pattern-library.js',
	accordion: './src/accordion.js',
	'accordion-style': './src/blocks/accordion/accordion.scss',
	blocks: './src/blocks.js',
	packages: './src/packages.scss',
	dashboard: './src/dashboard.js',
	tabs: './src/tabs.js',
	editor: './src/editor.js',
};

const config = {
	...defaultConfig,
	entry: {
		...defaultEntries,
		...pluginEntries,
		...packageEntries,
	},
	output: {
		...defaultConfig.output,
		path: __dirname + '/dist',
	},
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...defaultConfig.resolve.alias,
			'@utils': resolve( __dirname, 'src/utils' ),
			'@components': resolve( __dirname, 'src/components' ),
			'@store': resolve( __dirname, 'src/store' ),
			'@global-classes': resolve( __dirname, './src/global-classes' ),
			'@hooks': resolve( __dirname, './src/hooks' ),
			'@hoc': resolve( __dirname, './src/hoc' ),
		},
		enforceExtension: false,
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			useDefaults: true,
			requestToExternal( request ) {
				// Only externalize edge22 package imports.
				if ( request.startsWith( '@edge22/' ) && ! request.includes( 'dist' ) ) {
					return [
						'gbp',
						camelCaseDash( request.substring( EDGE22_NAMESPACE.length ) ),
					];
				}

				return undefined;
			},
			requestToHandle( request ) {
				if ( request.startsWith( EDGE22_NAMESPACE ) ) {
					return 'generateblocks-pro-' + request.substring( EDGE22_NAMESPACE.length );
				}
			},
		} ),
		new CopyPlugin( {
			patterns: [
				{ from: './src/assets', to: 'assets' },
				...packageCopyPatterns,
			],
		} ),
	],
};

if ( ! isProduction ) {
	config.plugins.push(
		new ESLintPlugin( {
			failOnError: false,
			fix: false,
			lintDirtyModulesOnly: true,
		} ),
	);
}

module.exports = config;
