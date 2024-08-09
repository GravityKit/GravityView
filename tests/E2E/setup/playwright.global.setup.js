const { chromium } = require( 'playwright' );
const { exec } = require( 'child_process' );
const { wpLogin } = require( '../helpers/wp-login' );
const { promises: fs } = require( 'fs' );

async function startDockerContainer() {
	return new Promise( ( resolve, reject ) => {
		exec(
			'docker run -d --rm --network host --ipc=host jacoblincool/playwright:chromium-light-server-1.46.0',
			( error, stdout ) => {
				if ( error ) {
					console.error( 'Error starting Docker container:', error );

					return reject( error );
				}

				const containerId = stdout.trim().substring( 0, 12 );

				console.log( 'Docker container started with ID:', containerId );

				resolve( containerId );
			}
		);
	} );
}

async function waitForWsEndpoint( wsEndpoint, retries = 5, delay = 2000 ) {
	for ( let i = 0; i < retries; i++ ) {
		try {
			const browser = await chromium.connect( { wsEndpoint } );

			return browser;
		} catch ( error ) {
			console.log(
				`Retrying connection to wsEndpoint: ${ wsEndpoint } (${ i + 1 }/${ retries })`
			);

			await new Promise( ( resolve ) => setTimeout( resolve, delay ) );
		}
	}

	throw new Error(
		`Failed to connect to wsEndpoint: ${ wsEndpoint } after ${ retries } retries`
	);
}

module.exports = async ( config ) => {
	require( 'dotenv' ).config( { path: `${ process.env.INIT_CWD }/.env` } );

	const projectConfig = config.projects.find(
		( project ) => project.name === 'chromium'
	);

	const {
		storageState: stateFile,
		connectOptions: { wsEndpoint },
	} = projectConfig.use;

	try {
		await startDockerContainer();
	} catch ( error ) {
		console.error( 'Failed to start Docker container: ', error );

		throw error;
	}

	try {
		await fs.access( stateFile );

		console.log( 'Loading previously saved state…' );
	} catch ( error ) {
		const browser = await waitForWsEndpoint( wsEndpoint );
		const context = await browser.newContext();
		const page = await context.newPage();

		await wpLogin( { page, stateFile } );
	}
};
