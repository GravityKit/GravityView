const path = require( 'path' );
const fs = require( 'fs' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const entryPoints = { ...defaultConfig?.entry };
const blocksFolder = path.resolve( process.cwd(), 'blocks' );
const sharedSourcesFolder = path.resolve( process.cwd(), 'shared' );

module.exports = ( async () => {
	const blocks = await fs.promises.readdir( blocksFolder, ( err, folders ) => folders );

	for ( const block of blocks ) {
		// Skip system files and hidden files.
		if ( block.startsWith( '.' ) || ! fs.statSync( path.resolve( blocksFolder, block ) ).isDirectory() ) {
			continue;
		}

		entryPoints[ block ] = path.resolve( process.cwd(), `${ blocksFolder }/${ block }` );
	}

	return {
		...defaultConfig,
		devServer: {
			...defaultConfig.devServer,
			allowedHosts: 'all',
		},
		entry: entryPoints,
		resolve: {
			alias: {
				...defaultConfig.resolve.alias,
				'shared': sharedSourcesFolder,
			},
		},
	};
} )();
