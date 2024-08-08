const { exec } = require('child_process');

async function stopDockerContainer() {
  return new Promise((resolve, reject) => {
    exec(
      'docker ps -q --filter ancestor=jacoblincool/playwright:chromium-server-1.45.0',
      (err, stdout) => {
        if (err) {
          console.error('Error retrieving Docker container ID:', err);

          return reject(err);
        }

        const containerId = stdout.trim().substring(0, 12);

        if (!containerId) {
          console.log('No Docker container found to stop');

          return resolve();
        }

        exec(`docker stop ${containerId}`, (stopErr) => {
          if (stopErr) {
            console.error('Error stopping Docker container:', stopErr);

            return reject(stopErr);
          }
          console.log(`Docker container stopped with ID: ${containerId}`);

          resolve();
        });
      }
    );
  });
}

module.exports = async () => {
  if (global.__BROWSER__) {
    await global.__BROWSER__.close();
  }

  try {
    await stopDockerContainer();
  } catch (error) {
    console.error('Failed to stop Docker container:', error);

    throw error;
  }
};
