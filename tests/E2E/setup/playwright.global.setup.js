const { exec } = require("child_process");

async function startDockerContainer() {
	return new Promise((resolve, reject) => {
		exec(
			"docker run -d --rm --network host --ipc=host jacoblincool/playwright:chromium-server",
			(err, stdout) => {
				if (err) {
					console.error("Error starting Docker container:", err);

					return reject(err);
				}

				const containerId = stdout.trim().substring(0, 12);

				console.log("Docker container started with ID:", containerId);

				resolve(containerId);
			},
		);
	});
}

module.exports = async () => {
	try {
		await startDockerContainer();
	} catch (error) {
		console.error("Failed to start Docker container:", error);

		throw error;
	}
};
