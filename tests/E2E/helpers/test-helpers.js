const { test, expect } = require('@playwright/test');

const templates = [
	{
		name: 'Table',
		slug: 'default_table',
		selector: '.gv-view-types-module:has(h5:text("Table"))',
		container: '.gv-table-container',
		contains: 'table.gv-table-view'
	},
	{
		name: 'List',
		slug: 'default_list',
		selector: '.gv-view-types-module:has(h5:text("List"))',
		container: '.gv-list-container',
		contains: 'ul.gv-list-view'
	},
	{
		name: 'DataTables Table',
		slug: 'datatables_table',
		selector: '.gv-view-types-module:has(h5:text("DataTables Table"))',
		container: '.gv-datatables-container',
		contains: 'table.dataTable'
	}
];

const viewTemplatesMap = {
	table: {
		name: 'Table',
		slug: 'default_table',
		selector: '.gv-view-types-module:has(h5:text("Table"))',
		container: '.gv-table-container',
		contains: 'table.gv-table-view'
	},
	list: {
		name: 'List',
		slug: 'default_list',
		selector: '.gv-view-types-module:has(h5:text("List"))',
		container: '.gv-list-container',
		contains: 'ul.gv-list-view'
	},
	dataTables: {
		name: 'DataTables Table',
		slug: 'datatables_table',
		selector: '.gv-view-types-module:has(h5:text("DataTables Table"))',
		container: '.gv-datatables-container',
		contains: 'table.dataTable'
	}
};

/**
 * Selects a Gravity Form from the dropdown by matching the form title.
 * Throws an error if the form title is not found.
 *
 * @param {object} page - The Playwright page object.
 * @param {string} formTitle - The title of the Gravity Form to select.
 * @param {import('@playwright/test').TestInfo | null} [testInfo=null] - Optional Playwright test information object.
 * @throws Will throw an error if the form title is not found.
 */
async function selectGravityFormByTitle(page, formTitle, testInfo = null) {
	const formSelector = '#gravityview_form_id';
	const noFormsMessage = "Form(s) not found. Use 'npm run import:forms-entries' to import forms.";

	const formLocator = page.locator(formSelector);
	const optionCount = await formLocator.evaluate((form) => {
		return form.options.length;
	});

	if (optionCount < 2) {
		console.warn(noFormsMessage);
		testInfo ? testInfo.skip(true, noFormsMessage) : test.skip(noFormsMessage);
	}

	const optionValue = await getOptionValueBySearchTerm(page, formSelector, formTitle);

	if (optionValue) {
		await page.selectOption(formSelector, optionValue);
	} else {
		throw new Error(`Form with title "${formTitle}" not found.`);
	}
}

/**
 * Helper function to create a GravityView.
 *
 * @param {import('playwright').Page} page - The Playwright page object.
 * @param {Object} params - Parameters for creating the view.
 * @param {string} params.formTitle - The title of the Gravity Form to select.
 * @param {string} params.viewName - The name to assign to the new view.
 * @param {Object} params.template - The template details.
 * @param {string} params.template.name - The name of the template.
 * @param {string} params.template.selector - The CSS selector for the template.
 * @param {string} params.template.slug - The slug of the template.
 * @param {string} params.template.container - The CSS selector for the container to check.
 * @param {string} params.template.contains - Optional CSS selector for specific content check.
 * @param {import('@playwright/test').TestInfo | null} [testInfo=null] - Optional Playwright test information object.
 */
async function createView(page, { formTitle, viewName, template }, testInfo = null) {
	await page.waitForSelector('text=New View', { state: 'visible' });
	await page.click('text=New View');

	try {
		await selectGravityFormByTitle(page, formTitle, testInfo);
	} catch {
		const formMissingMessage = `The form '${formTitle}' doesn't exist.`;
		testInfo ? testInfo.skip(true, formMissingMessage) : test.skip();
	}

	await page.fill('#title', viewName);

	await page.waitForSelector('#gravityview_select_template', {
		state: 'visible'
	});
	await page.waitForSelector('.gv-view-types-module', { state: 'visible' });

	const templateSelector = await page.$(template.selector);
	const isPlaceholder = await templateSelector.evaluate((element) =>
		element.classList.contains('gv-view-template-placeholder')
	);
	const skipMessage = `Skipping test: ${template.name} template not found.`;

	if (isPlaceholder) {
		console.warn(skipMessage);
		test.skip(skipMessage);
	}

	const selectButtonLocator = page.locator(
		`a.gv_select_template[data-templateid="${template.slug}"]`
	);
	await templateSelector.hover();
	await page.dispatchEvent(template.selector, 'mouseenter');
	await selectButtonLocator.waitFor({ state: 'visible' });
	await selectButtonLocator.click();

	await page.waitForSelector('#gravityview_settings', { state: 'visible' });

	const checkbox = page.locator('#gravityview_se_show_only_approved');
	if (await checkbox.isVisible()) {
		await checkbox.uncheck();
	}
}

/**
 * Helper function to publish a GravityView.
 *
 * @param {import('playwright').Page} page - The Playwright page object.
 */
async function publishView(page) {
	await page.locator('#publish:not(.disabled)').waitFor();
	await Promise.all([
		page.click('#publish'),
		page.waitForURL(/\/wp-admin\/post(?:-new)?\.php(?:\?[^#]*)?$/)
	]);

	await page.waitForSelector('.notice-success');
	const successMessage = await page.textContent('.notice-success');
	expect(successMessage).toMatch(/View (published|updated)/);
}

/**
 * Helper function to check a newly created GravityView on the front end.
 *
 * @param {import('playwright').Page} page - The Playwright page object.
 * @param {string} [permalinkSelector="#sample-permalink"] - The CSS selector for the permalink element.
 * @param {boolean} [assertResponse=true] - Whether to assert the response status (default: true).
 */
async function checkViewOnFrontEnd(
	page,
	permalinkSelector = '#sample-permalink',
	assertResponse = true
) {
	await page.waitForLoadState('networkidle');
	const permalinkEl = page.locator(permalinkSelector);
	await permalinkEl.waitFor({ state: 'visible' });
	const viewUrl = await getViewUrl(page, permalinkSelector);

	const response = await page.goto(viewUrl);
	await page.waitForURL(viewUrl);
	await page.waitForLoadState('networkidle');

	if (assertResponse) {
		expect(response.status()).toBe(200);
	}
}

/**
 * Helper function to count the number of entries in a GravityView table on the front end.
 *
 * @param {import('playwright').Page} page - The Playwright page object.
 * @param {string} tableSelector - The CSS selector for the table element.
 * @returns {Promise<number>} - The number of entries in the table.
 */
async function countTableEntries(page, tableSelector = '.gv-table-view') {
	await page.waitForSelector(tableSelector, { state: 'visible' });
	const rowCount = await page.$$eval(`${tableSelector} tbody tr`, (rows) => rows.length);
	return rowCount;
}

/**
 * Helper function to create a download button and click it.
 * @param {Page} page - The Playwright page instance.
 * @param {string} downloadUrl - The URL to download from.
 * @param {string} buttonId - A unique ID for the download button.
 */
async function clickDownloadButton(page, downloadUrl, buttonId = 'download-button') {
	const result = await page.evaluate(
		({ url, id }) => {
			try {
				const button = document.createElement('button');
				button.innerText = 'Download';
				button.id = id;
				button.onclick = () => {
					window.location.href = url;
				};
				document.body.appendChild(button);
				return {
					success: true,
					message: 'Button appended successfully.'
				};
			} catch (error) {
				return { success: false, message: error.message };
			}
		},
		{ url: downloadUrl, id: buttonId }
	);

	if (!result.success) {
		throw new Error(`Failed to create button: ${result.message}`);
	}

	const downloadButton = await page.waitForSelector(`#${buttonId}`, {
		state: 'visible'
	});

	await downloadButton.click();
}

/**
 * Creates a new WordPress page, inserts a shortcode block, publishes the page,
 * and returns the URL of the published page.
 *
 * @param {object} page - The Playwright `page` object used to interact with the browser.
 * @param {object} options - Options for creating the page.
 * @param {string} options.shortcode - The shortcode to insert into the page.
 * @param {string} options.title - The title of the page to create.
 *
 * @returns {Promise<string>} - The URL of the published page.
 *
 * @example
 * const url = await createPageWithShortcode(page, { shortcode: 'your_shortcode_here', title: 'My Page Title' });
 * console.log('Published page URL:', url);
 */
async function createPageWithShortcode(page, { shortcode, title }) {
	await page.goto('/wp-admin/post-new.php?post_type=page');

	await page.locator('.wp-block-post-title').fill(title);

	// Close "Welcome to the block Editor" modal if present.
	const modal = page.locator('.components-modal__content');

	if (await modal.isVisible()) {
		await modal.getByLabel('Close', { exact: true }).click();
	}

	await page.click('.components-dropdown.block-editor-inserter');
	await page.fill('input[placeholder="Search"]', 'Shortcode');
	await page.click('.components-popover .editor-block-list-item-shortcode');

	await page.locator('div.wp-block-shortcode textarea').fill(`[${shortcode}]`);
	await page.click('button.editor-post-publish-panel__toggle');
	await page.click('button.editor-post-publish-button');
	await page.waitForSelector('input.components-text-control__input');

	const pageUrl = await page.getAttribute('input.components-text-control__input', 'value');
	return pageUrl;
}

/**
 * Retrieves the view URL from a given permalink selector on the page.
 *
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 * @param {string} [permalinkSelector="#sample-permalink"] - The CSS selector for the permalink element.
 * @returns {Promise<string | null>} - The URL as a string, or `null` if no anchor tag is found.
 */
async function getViewUrl(page, permalinkSelector = '#sample-permalink') {
	const element = page.locator(permalinkSelector);
	const isAnchor = await element.evaluate((el) => el.tagName === 'A');
	if (isAnchor) {
		return await element.getAttribute('href');
	}
	return await element.locator('a').first().getAttribute('href');
}

/**
 * Gets the value of an option from a dropdown by matching a search term.
 * Throws an error if no matching option is found.
 *
 * @param {object} page - The Playwright page object.
 * @param {string} selector - The CSS selector for the dropdown.
 * @param {string} searchTerm - The term to match the dropdown option.
 * @returns {Promise<string>} - The value of the matching option.
 * @throws Will throw an error if the matching option is not found.
 */
async function getOptionValueBySearchTerm(page, selector, searchTerm) {
	return await page.evaluate(
		({ searchTerm, selector }) => {
			const select = document.querySelector(selector);
			const options = Array.from(select.options);
			const lowerCaseSearchTerm = searchTerm.toLowerCase();
			const option = options.find((opt) =>
				opt.textContent.trim().toLowerCase().startsWith(lowerCaseSearchTerm)
			);
			return option ? option.value : '';
		},
		{ searchTerm, selector }
	);
}

/**
 * Clicks the first visible element matching the given selector or Locator.
 *
 * @param {import('@playwright/test').Page} page - Playwright Page object
 * @param {number} [timeout=5000] - Optional timeout in milliseconds
 */
async function clickFirstVisible(page, selectorOrLocator, timeout = 5000) {
	const locator = typeof selectorOrLocator === 'string'
	  ? page.locator(selectorOrLocator)
	  : selectorOrLocator;
  
	await locator.first().waitFor({ state: 'attached', timeout });
  
	const elements = await locator.elementHandles();
  
	for (const el of elements) {
	  if (await el.isVisible()) {
		await el.scrollIntoViewIfNeeded();
		await el.click();
		return;
	  }
	}
  
	throw new Error(
	  `No visible element found. Type: ${typeof selectorOrLocator}, Count: ${elements.length}`
	);
}

module.exports = {
	templates,
	viewTemplatesMap,
	selectGravityFormByTitle,
	createView,
	publishView,
	checkViewOnFrontEnd,
	countTableEntries,
	clickDownloadButton,
	createPageWithShortcode,
	getViewUrl,
	getOptionValueBySearchTerm,
	clickFirstVisible
};
