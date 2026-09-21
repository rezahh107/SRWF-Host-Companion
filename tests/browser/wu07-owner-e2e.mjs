import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';

const env = {
  baseUrl: process.env.SRWF_WU07_BASE_URL,
  evidencePath: process.env.SRWF_WU07_EVIDENCE_PATH,
  fixturePath: process.env.SRWF_WU06_FIXTURE_PATH,
  artifactDir: process.env.SRWF_ARTIFACT_DIR,
  scratchDir: process.env.SRWF_WU07_SCRATCH_DIR,
  wpCli: process.env.SRWF_WP_CLI,
  wpPath: process.env.SRWF_WP_PATH,
  workspace: process.env.GITHUB_WORKSPACE,
  testedSha: process.env.SRWF_TESTED_COMMIT_SHA,
  adminPassword: process.env.SRWF_WU07_ADMIN_PASSWORD,
};

for (const [key, value] of Object.entries(env)) {
  if (!value) throw new Error(`Required WU-07 environment value is missing: ${key}`);
}

const fixture = JSON.parse(fs.readFileSync(env.fixturePath, 'utf8'));
const helperPath = path.join(env.workspace, 'tests/runtime-lab/wu06-admin-fixtures.php');
const adminUrl = `${env.baseUrl}/wp-admin/options-general.php?page=srwf-host`;
const dashboardUrl = `${env.baseUrl}/wp-admin/`;
const loginUrl = `${env.baseUrl}/wp-login.php`;
const viewport = { width: 1440, height: 1100 };

const evidence = {
  schema: 'srwf-host-companion-wu07-owner-e2e-v1',
  evidence_class: 'DISPOSABLE_CI_REAL_BROWSER_MECHANICAL_OWNER_E2E',
  tested_commit_sha: env.testedSha,
  observed_at_utc: new Date().toISOString(),
  environment: fixture.environment,
  browser: {},
  owner_journey: {},
  persistent_state: {},
  frontend: {},
  check_again: {},
  runtime_issues: [],
  claim_ceiling: {
    human_comprehension: 'NOT_PROVEN',
    production_host_confirmation: 'NOT_PROVEN',
    complete_wcag_2_2_aa_conformance: 'NOT_PROVEN',
    gravity_forms_integration: 'ENVIRONMENT_UNAVAILABLE_NOT_PROVEN',
    orbital_integration: 'ENVIRONMENT_UNAVAILABLE_NOT_PROVEN',
    gtb_integration: 'ENVIRONMENT_UNAVAILABLE_NOT_PROVEN',
    vazir_vazirmatn_integration: 'ENVIRONMENT_UNAVAILABLE_NOT_PROVEN',
    production_qualified_for_srwf: 'NOT_PROVEN',
  },
  overall_status: 'FAIL',
  failures: [],
};

function jsonRead(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

function helper(command, state = '') {
  const safe = state || command;
  const outputPath = path.join(env.scratchDir, `wu07-${safe}-${command}.json`);
  const childEnv = {
    ...process.env,
    SRWF_WU06_COMMAND: command,
    SRWF_WU06_STATE: state,
    SRWF_WU06_OUTPUT_PATH: outputPath,
  };
  execFileSync('php', [env.wpCli, `--path=${env.wpPath}`, 'eval-file', helperPath], {
    env: childEnv,
    stdio: ['ignore', 'pipe', 'pipe'],
  });
  return jsonRead(outputPath);
}

function prepare(state) {
  return helper('prepare', state);
}

function snapshot(label) {
  return helper('snapshot', label);
}

function saveEvidence() {
  fs.writeFileSync(env.evidencePath, `${JSON.stringify(evidence, null, 2)}\n`, 'utf8');
}

function fail(message) {
  evidence.failures.push(message);
  throw new Error(message);
}

function attachRuntimeCollector(page) {
  const issues = [];
  page.on('console', (message) => {
    if (message.type() === 'error') issues.push({ type: 'console_error', message: message.text(), location: message.location() });
  });
  page.on('pageerror', (error) => issues.push({ type: 'page_error', message: error.message }));
  page.on('requestfailed', (request) => issues.push({ type: 'request_failed', url: request.url(), error: request.failure()?.errorText || 'unknown' }));
  return issues;
}

async function login(page) {
  const response = await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });
  assert(response && response.status() < 400, 'WordPress login page did not load.');
  await page.locator('#user_login').fill(fixture.users.admin);
  await page.locator('#user_pass').fill(env.adminPassword);
  await Promise.all([
    page.waitForURL(/\/wp-admin\//, { timeout: 15000 }),
    page.locator('#wp-submit').click(),
  ]);
}

prepare('first_run');

const browser = await chromium.launch({ headless: true });
evidence.browser = {
  engine: 'chromium',
  version: browser.version(),
  viewport,
  locale: 'fa-IR',
};

const context = await browser.newContext({ viewport, locale: 'fa-IR' });
const page = await context.newPage();
const runtimeIssues = attachRuntimeCollector(page);

try {
  await login(page);

  const dashboard = await page.goto(dashboardUrl, { waitUntil: 'domcontentloaded' });
  assert(dashboard && dashboard.status() === 200, 'WordPress dashboard did not load.');

  const settingsMenu = page.locator('#menu-settings');
  assert.equal(await settingsMenu.count(), 1, 'WordPress Settings menu was not present.');
  await settingsMenu.hover();
  const srwfMenuLink = settingsMenu.locator('a[href*="options-general.php?page=srwf-host"]');
  assert.equal(await srwfMenuLink.count(), 1, 'SRWF Host submenu entry was not reachable from Settings.');
  await Promise.all([
    page.waitForURL(/options-general\.php\?page=srwf-host/, { timeout: 15000 }),
    srwfMenuLink.click(),
  ]);

  const wrap = page.locator('.wrap');
  await wrap.waitFor({ state: 'visible' });
  const firstRunText = await wrap.innerText();
  if (!firstRunText.includes('هنوز صفحه ثبت‌نام انتخاب نشده است')) fail('First-run guidance was not visible in the real Owner route.');
  assert.equal(await page.getByLabel('صفحه ثبت‌نام').count(), 1, 'Registration selector is missing from first-run UI.');
  assert.equal(await page.getByRole('button', { name: 'ذخیره و اعمال قالب تمام‌عرض' }).count(), 1, 'Explicit save/apply action is missing from first-run UI.');

  evidence.owner_journey.login = 'PASS';
  evidence.owner_journey.settings_menu_reachability = 'PASS';
  evidence.owner_journey.first_run_guidance_visible = 'PASS';
  evidence.owner_journey.registration_selector_visible = 'PASS';
  evidence.owner_journey.explicit_apply_action_visible = 'PASS';

  const beforeApply = snapshot('before-apply');
  const targetId = Number(fixture.pages.apply_target);
  const selector = page.getByLabel('صفحه ثبت‌نام');
  await selector.selectOption(String(targetId));
  assert.equal(await selector.inputValue(), String(targetId), 'Synthetic Registration page could not be selected.');

  await Promise.all([
    page.waitForURL(/srwf_result=success/, { timeout: 15000 }),
    page.getByRole('button', { name: 'ذخیره و اعمال قالب تمام‌عرض' }).click(),
  ]);

  const successText = await wrap.innerText();
  if (!successText.includes('تنظیم ذخیره شد و قالب تمام‌عرض به صفحه انتخاب‌شده اعمال و بازخوانی شد')) {
    fail('Real Owner apply path did not show the truthful success result.');
  }

  const afterApply = snapshot('after-apply');
  const schemaOk = afterApply.configuration_raw?.schema_version === 1
    && afterApply.configuration_raw?.roles?.registration?.page_id === targetId;
  const assignment = afterApply.tracked_pages?.apply_target?.page_template_readback;
  const contentUnchanged = beforeApply.tracked_pages?.apply_target?.post_content_sha256
    === afterApply.tracked_pages?.apply_target?.post_content_sha256;
  if (!schemaOk || assignment !== 'registration-full-width' || !contentUnchanged) {
    fail('Persisted configuration/template assignment diverged from the bounded Owner mutation contract.');
  }

  evidence.owner_journey.select_registration_page = 'PASS';
  evidence.owner_journey.explicit_save_apply = 'PASS';
  evidence.owner_journey.truthful_success_result = 'PASS';
  evidence.persistent_state = {
    status: 'PASS',
    schema_v1_registration_page_id: targetId,
    canonical_assignment_readback: assignment,
    page_content_unchanged: 'PASS',
  };

  const frontendUrl = `${env.baseUrl}/?page_id=${targetId}`;
  const frontendResponse = await page.goto(frontendUrl, { waitUntil: 'domcontentloaded' });
  assert(frontendResponse && frontendResponse.status() === 200, 'Selected Registration frontend page did not load.');
  const shell = page.locator('.srwf-host-companion-registration-shell');
  await shell.waitFor({ state: 'visible' });
  const frontendText = await shell.innerText();
  if (!frontendText.includes('WU06 apply fixture')) fail('Selected page content was not rendered inside the canonical SRWF host shell.');
  const frontendFacts = await page.evaluate(() => ({
    direction: getComputedStyle(document.documentElement).direction,
    header_count: document.querySelectorAll('header').length,
    footer_count: document.querySelectorAll('footer').length,
    nav_count: document.querySelectorAll('nav').length,
    client_width: document.documentElement.clientWidth,
    scroll_width: document.documentElement.scrollWidth,
  }));
  if (frontendFacts.direction !== 'rtl' || frontendFacts.header_count < 1 || frontendFacts.footer_count < 1 || frontendFacts.nav_count < 1) {
    fail('Frontend host integrity/RTL condition failed in the Owner E2E route.');
  }
  if (frontendFacts.scroll_width > frontendFacts.client_width + 1) fail('Unexpected horizontal overflow occurred in the Owner E2E frontend route.');

  evidence.owner_journey.frontend_registration_opened = 'PASS';
  evidence.frontend = {
    status: 'PASS',
    url: frontendUrl,
    canonical_shell_visible: 'PASS',
    selected_page_content_visible: 'PASS',
    rtl: 'PASS',
    header: 'PASS',
    footer: 'PASS',
    navigation: 'PASS',
    overflow: 'PASS',
    measurements: frontendFacts,
  };

  const beforeCheck = snapshot('before-check-again');
  const adminReturn = await page.goto(adminUrl, { waitUntil: 'domcontentloaded' });
  assert(adminReturn && adminReturn.status() === 200, 'Could not return to SRWF Host settings after frontend visit.');
  await wrap.waitFor({ state: 'visible' });
  const canonicalText = await wrap.innerText();
  if (!canonicalText.includes('اتصال قالب مطابق انتظار است')) fail('Returned admin status did not truthfully show canonical state.');

  const checkAgain = page.getByRole('link', { name: 'بررسی دوباره' });
  assert.equal(await checkAgain.count(), 1, 'Check Again action is missing after the Owner journey.');
  await Promise.all([
    page.waitForURL(/srwf_check=1/, { timeout: 15000 }),
    checkAgain.click(),
  ]);
  const afterCheck = snapshot('after-check-again');
  assert.deepStrictEqual(afterCheck, beforeCheck, 'Check Again mutated persistent state in the WU-07 journey.');
  const checkedText = await wrap.innerText();
  if (!checkedText.includes('اتصال قالب مطابق انتظار است')) fail('Check Again did not preserve truthful canonical status.');

  evidence.owner_journey.return_to_settings = 'PASS';
  evidence.owner_journey.check_again = 'PASS';
  evidence.check_again = {
    status: 'PASS',
    read_only_sentinel: 'PASS',
    truthful_canonical_status: 'PASS',
  };

  evidence.runtime_issues = runtimeIssues;
  if (runtimeIssues.length > 0) fail(`WU-07 normal Owner path emitted ${runtimeIssues.length} browser runtime issue(s).`);

  evidence.overall_status = 'WU07_MECHANICAL_OWNER_E2E_PASS_ON_PINNED_TARGET_TUPLE';
} catch (error) {
  if (!evidence.failures.includes(error.message)) evidence.failures.push(error.message);
  try {
    await page.screenshot({ path: path.join(env.artifactDir, 'wu07-owner-e2e-failure.png'), fullPage: true });
  } catch {
    // Machine-readable evidence remains primary if screenshot capture is unavailable.
  }
  saveEvidence();
  await context.close();
  await browser.close();
  console.error(error.stack || error.message);
  process.exit(1);
}

saveEvidence();
await context.close();
await browser.close();
console.log(JSON.stringify({
  overall_status: evidence.overall_status,
  owner_journey: evidence.owner_journey,
  claim_ceiling: evidence.claim_ceiling,
}, null, 2));
