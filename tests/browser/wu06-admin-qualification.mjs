import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';

const require = createRequire(import.meta.url);
const playwrightVersion = require('playwright/package.json').version;
let axeCoreVersion = 'unknown';
try {
  axeCoreVersion = require('axe-core/package.json').version;
} catch {
  // The pinned @axe-core/playwright package version is still recorded below.
}

const env = {
  baseUrl: process.env.SRWF_WU06_BASE_URL,
  evidencePath: process.env.SRWF_WU06_EVIDENCE_PATH,
  fixturePath: process.env.SRWF_WU06_FIXTURE_PATH,
  artifactDir: process.env.SRWF_ARTIFACT_DIR,
  scratchDir: process.env.SRWF_WU06_SCRATCH_DIR,
  wpCli: process.env.SRWF_WP_CLI,
  wpPath: process.env.SRWF_WP_PATH,
  workspace: process.env.GITHUB_WORKSPACE,
  testedSha: process.env.SRWF_TESTED_COMMIT_SHA,
  adminPassword: process.env.SRWF_WU06_ADMIN_PASSWORD,
  settingsPassword: process.env.SRWF_WU06_SETTINGS_PASSWORD,
  subscriberPassword: process.env.SRWF_WU06_SUBSCRIBER_PASSWORD,
  axePlaywrightVersion: process.env.SRWF_AXE_PLAYWRIGHT_VERSION,
};

for (const [key, value] of Object.entries(env)) {
  if (!value) throw new Error(`Required WU-06 environment value is missing: ${key}`);
}

const helperPath = path.join(env.workspace, 'tests/runtime-lab/wu06-admin-fixtures.php');
const fixture = JSON.parse(fs.readFileSync(env.fixturePath, 'utf8'));
const adminUrl = `${env.baseUrl}/wp-admin/options-general.php?page=srwf-host`;
const loginUrl = `${env.baseUrl}/wp-login.php`;
const adminPostUrl = `${env.baseUrl}/wp-admin/admin-post.php`;
const desktopViewport = { width: 1440, height: 1100 };
const narrowViewport = { width: 390, height: 900 };
const requiredStates = [
  'first_run',
  'valid_published',
  'valid_non_published',
  'missing_configured',
  'trashed_page',
  'wrong_post_type',
  'wrong_assignment',
  'canonical_active',
  'database_override',
  'theme_override',
  'missing_template',
  'unknown',
];
const ownerFragments = {
  first_run: 'هنوز صفحه ثبت‌نام انتخاب نشده است',
  valid_published: 'اتصال قالب مطابق انتظار است',
  valid_non_published: 'صفحه معتبر است اما منتشرشده نیست',
  missing_configured: 'صفحه ثبت‌نام نیاز به اصلاح دارد',
  trashed_page: 'صفحه ثبت‌نام نیاز به اصلاح دارد',
  wrong_post_type: 'صفحه ثبت‌نام نیاز به اصلاح دارد',
  wrong_assignment: 'قالب مورد انتظار به صفحه ثبت‌نام متصل نیست',
  canonical_active: 'اتصال قالب مطابق انتظار است',
  database_override: 'یک نسخه سفارشی‌شده در پایگاه داده بر قالب مرجع مقدم است',
  theme_override: 'پوسته فعال نسخه‌ای با همین نام قالب دارد',
  missing_template: 'قالب مرجع SRWF در حال حاضر resolve نمی‌شود',
  unknown: 'منبع قالب با اطمینان قابل طبقه‌بندی نیست',
};

const evidence = {
  schema: 'srwf-host-companion-wu06-admin-qualification-v1',
  evidence_class: 'DISPOSABLE_CI_REAL_BROWSER_ADMIN_QUALIFICATION',
  tested_commit_sha: env.testedSha,
  observed_at_utc: new Date().toISOString(),
  environment: fixture.environment,
  browser: {},
  locale_direction: {},
  viewports: {
    desktop: desktopViewport,
    narrow_admin: narrowViewport,
  },
  state_matrix: {},
  security_authorization: {},
  successful_apply: {},
  check_again_read_only: {},
  mutation_sentinels: {},
  rtl: {},
  technical_ltr_isolation: {},
  narrow_admin_layout: {},
  keyboard_focus: {},
  semantics_accessibility_tree: {},
  automated_accessibility_scan: {
    engine: 'axe-core',
    axe_core_version: axeCoreVersion,
    package: '@axe-core/playwright',
    package_version: env.axePlaywrightVersion,
    scope: '.wrap',
    claim: 'MACHINE_DETECTABLE_ACCESSIBILITY_CHECK',
    states: {},
  },
  diagnostic_privacy: {},
  browser_runtime: {
    normal_path_issues: [],
    classifications: {},
  },
  regression_workflows: {
    wu01_runtime_facts: { status: 'NOT_COLLECTED_YET' },
    wu02_runtime_core: { status: 'NOT_COLLECTED_YET' },
    wu03_owner_settings: { status: 'NOT_COLLECTED_YET' },
    wu04_diagnostics_drift: { status: 'NOT_COLLECTED_YET' },
    wu05_full_width_geometry: { status: 'NOT_COLLECTED_YET' },
    wu06_admin_qualification: { status: 'CURRENT_WORKFLOW_IN_PROGRESS' },
  },
  claim_ceiling: {
    wcag_2_2_aa_conformance: 'NOT_PROVEN',
    human_comprehension: 'NOT_RUN',
    production_host_qualification: 'NOT_PROVEN',
    wu07_owner_e2e_comprehension_release_gate: 'NOT_RUN',
    gravity_forms_orbital_gtb_vazir_integration: 'NOT_PROVEN',
    production_qualified_for_srwf: 'NOT_PROVEN',
  },
  failures: [],
  overall_status: 'FAIL',
};

function jsonRead(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

function helper(command, state = '') {
  const safe = state || command;
  const outputPath = path.join(env.scratchDir, `wu06-${safe}-${command}.json`);
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

function reducedViolation(violation) {
  return {
    id: violation.id,
    impact: violation.impact,
    help: violation.help,
    help_url: violation.helpUrl,
    nodes: violation.nodes.map((node) => ({
      target: node.target,
      html: node.html,
      failure_summary: node.failureSummary,
    })),
  };
}

function classifyAxeViolation(violation) {
  const wordpressContextRules = new Set([
    'region',
    'landmark-one-main',
    'landmark-no-duplicate-main',
    'landmark-unique',
    'page-has-heading-one',
  ]);
  return wordpressContextRules.has(violation.id) ? 'WORDPRESS_ADMIN_CONTEXT' : 'PLUGIN_SCOPE';
}

async function axeScan(page, state) {
  const results = await new AxeBuilder({ page })
    .include('.wrap')
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  const violations = results.violations.map((violation) => ({
    ...reducedViolation(violation),
    classification: classifyAxeViolation(violation),
  }));
  const pluginViolations = violations.filter((violation) => violation.classification === 'PLUGIN_SCOPE');
  evidence.automated_accessibility_scan.states[state] = {
    status: pluginViolations.length === 0 ? 'PASS' : 'FAIL',
    plugin_scope_violation_count: pluginViolations.length,
    wordpress_admin_context_finding_count: violations.length - pluginViolations.length,
    violations,
  };
  if (pluginViolations.length > 0) {
    fail(`${state}: axe detected ${pluginViolations.length} plugin-scope violation(s).`);
  }
  return evidence.automated_accessibility_scan.states[state];
}

function makeRuntimeCollector(page) {
  const issues = [];
  const onConsole = (message) => {
    if (message.type() === 'error') {
      issues.push({ type: 'console_error', message: message.text(), location: message.location() });
    }
  };
  const onPageError = (error) => issues.push({ type: 'page_error', message: error.message });
  const onRequestFailed = (request) => issues.push({
    type: 'request_failed',
    url: request.url(),
    method: request.method(),
    failure: request.failure()?.errorText || 'unknown',
  });
  const onResponse = (response) => {
    if (response.status() >= 400) {
      issues.push({ type: 'http_error', url: response.url(), status: response.status() });
    }
  };
  page.on('console', onConsole);
  page.on('pageerror', onPageError);
  page.on('requestfailed', onRequestFailed);
  page.on('response', onResponse);
  return {
    issues,
    reset() { issues.length = 0; },
    detach() {
      page.off('console', onConsole);
      page.off('pageerror', onPageError);
      page.off('requestfailed', onRequestFailed);
      page.off('response', onResponse);
    },
  };
}

function classifyRuntimeIssue(issue) {
  const text = `${issue.url || ''} ${issue.message || ''}`;
  if (text.includes('/wp-content/plugins/srwf-host-companion/') || text.toLowerCase().includes('srwf-host-companion')) {
    return 'PLUGIN_OWNED';
  }
  return 'UNRESOLVED_NON_PLUGIN_OR_LAB';
}

function recordRuntimeIssues(state, issues) {
  const classified = issues.map((issue) => ({ ...issue, classification: classifyRuntimeIssue(issue) }));
  evidence.browser_runtime.normal_path_issues.push(...classified.map((item) => ({ state, ...item })));
  for (const item of classified) {
    evidence.browser_runtime.classifications[item.classification] = (evidence.browser_runtime.classifications[item.classification] || 0) + 1;
  }
  if (classified.length > 0) {
    fail(`${state}: new browser/runtime issue(s) observed on a normal qualification path.`);
  }
}

async function login(context, username, password, viewport = desktopViewport) {
  const page = await context.newPage();
  await page.setViewportSize(viewport);
  const response = await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });
  assert(response && response.status() < 400, 'WordPress login page did not load.');
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await Promise.all([
    page.waitForURL(/\/wp-admin\//, { timeout: 15000 }),
    page.locator('#wp-submit').click(),
  ]);
  return page;
}

async function openAdmin(page) {
  const response = await page.goto(adminUrl, { waitUntil: 'domcontentloaded' });
  if (!response || response.status() !== 200) {
    throw new Error(`Settings screen HTTP status was ${response ? response.status() : 'no response'}.`);
  }
  await page.locator('.wrap').waitFor({ state: 'visible' });
  return response;
}

async function directionAndSemantics(page) {
  return page.locator('.wrap').evaluate((wrap) => {
    const root = document.documentElement;
    const details = wrap.querySelector('details');
    const summary = details?.querySelector('summary');
    const select = wrap.querySelector('#srwf_registration_page_id');
    const label = wrap.querySelector('label[for="srwf_registration_page_id"]');
    const primary = wrap.querySelector('input[type="submit"], button[type="submit"]');
    const checkAgain = Array.from(wrap.querySelectorAll('a')).find((link) => link.textContent?.includes('بررسی دوباره'));
    const report = wrap.querySelector('#srwf-host-diagnostic-report');
    const headings = Array.from(wrap.querySelectorAll('h1,h2,h3,h4,h5,h6')).map((heading) => ({
      level: Number.parseInt(heading.tagName.substring(1), 10),
      text: heading.textContent?.trim() || '',
    }));
    const codes = Array.from(wrap.querySelectorAll('code[dir="ltr"]'));
    const codeDirections = codes.map((code) => getComputedStyle(code).direction);
    return {
      document_dir_attribute: root.getAttribute('dir') || '',
      document_computed_direction: getComputedStyle(root).direction,
      wrap_dir_attribute: wrap.getAttribute('dir') || '',
      wrap_computed_direction: getComputedStyle(wrap).direction,
      select_has_matching_label: !!select && !!label && label.htmlFor === select.id && label.textContent.trim().length > 0,
      primary_control: primary ? { tag: primary.tagName.toLowerCase(), type: primary.getAttribute('type') || '', value: primary.getAttribute('value') || primary.textContent?.trim() || '' } : null,
      check_again: checkAgain ? { tag: checkAgain.tagName.toLowerCase(), text: checkAgain.textContent?.trim() || '' } : null,
      details_semantics: details && summary ? { details_tag: details.tagName.toLowerCase(), summary_tag: summary.tagName.toLowerCase(), summary_text: summary.textContent?.trim() || '' } : null,
      report: report ? { tag: report.tagName.toLowerCase(), readonly: report.hasAttribute('readonly'), dir: report.getAttribute('dir') || '', computed_direction: getComputedStyle(report).direction } : null,
      code_count: codes.length,
      code_directions: codeDirections,
      headings,
      status_role_count: wrap.querySelectorAll('[role="status"]').length,
    };
  });
}

async function ariaEvidence(page) {
  const select = page.getByLabel('صفحه ثبت‌نام');
  const primary = page.getByRole('button', { name: 'ذخیره و اعمال قالب تمام‌عرض' });
  const check = page.getByRole('link', { name: 'بررسی دوباره' });
  const report = page.getByRole('textbox', { name: 'گزارش فنی امن برای پشتیبانی' });
  assert.equal(await select.count(), 1, 'Page selector accessible name is missing or ambiguous.');
  assert.equal(await primary.count(), 1, 'Primary action accessible name is missing or ambiguous.');
  assert.equal(await check.count(), 1, 'Check Again accessible name is missing or ambiguous.');
  assert.equal(await report.count(), 1, 'Diagnostic report accessible name is missing or ambiguous.');
  const summary = page.locator('.wrap details > summary');
  assert.equal(await summary.count(), 1, 'Technical details summary is missing.');
  return {
    select: await select.ariaSnapshot(),
    primary_action: await primary.ariaSnapshot(),
    check_again: await check.ariaSnapshot(),
    technical_details_summary: await summary.ariaSnapshot(),
    diagnostic_report: await report.ariaSnapshot(),
  };
}

async function stateMatrix(browser) {
  const context = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
  const page = await login(context, fixture.users.admin, env.adminPassword, desktopViewport);
  const runtime = makeRuntimeCollector(page);

  for (const state of requiredStates) {
    const prepared = prepare(state);
    const before = snapshot(`${state}-before`);
    runtime.reset();
    await openAdmin(page);
    const bodyText = await page.locator('.wrap').innerText();
    if (!bodyText.includes(ownerFragments[state])) fail(`${state}: Owner-facing state text is not truthful/expected.`);
    if (prepared.actual.primary_state !== prepared.expected.primary || prepared.actual.recommended_action !== prepared.expected.action) {
      fail(`${state}: prepared WU-04 evidence model diverged from expected state/action.`);
    }

    const semantic = await directionAndSemantics(page);
    if (semantic.document_computed_direction !== 'rtl' || semantic.wrap_computed_direction !== 'rtl') fail(`${state}: Persian RTL direction was not active.`);
    if (!semantic.select_has_matching_label) fail(`${state}: Registration selector label association failed.`);
    if (!semantic.primary_control || !semantic.check_again || !semantic.details_semantics || !semantic.report?.readonly) fail(`${state}: native semantic controls are incomplete.`);
    if (semantic.headings.length < 2 || semantic.headings[0].level !== 1 || semantic.headings[1].level !== 2) fail(`${state}: plugin heading hierarchy is not h1 → h2.`);
    if (semantic.status_role_count < 1) fail(`${state}: status/result semantic structure is missing.`);

    await page.locator('.wrap details > summary').click();
    const ltr = await page.locator('.wrap').evaluate((wrap) => {
      const codes = Array.from(wrap.querySelectorAll('code[dir="ltr"]'));
      const textarea = wrap.querySelector('#srwf-host-diagnostic-report');
      return {
        code_count: codes.length,
        code_all_ltr: codes.length > 0 && codes.every((code) => getComputedStyle(code).direction === 'ltr'),
        textarea_ltr: !!textarea && getComputedStyle(textarea).direction === 'ltr',
        textarea_selectable: !!textarea && !textarea.hasAttribute('disabled'),
      };
    });
    if (!ltr.code_all_ltr || !ltr.textarea_ltr || !ltr.textarea_selectable) fail(`${state}: technical LTR isolation/copyability failed.`);

    const axe = await axeScan(page, state);
    const after = snapshot(`${state}-after`);
    try {
      assert.deepStrictEqual(after, before);
    } catch {
      fail(`${state}: read-only browser rendering/accessibility scan mutated persistent sentinel state.`);
    }
    recordRuntimeIssues(state, runtime.issues);

    evidence.state_matrix[state] = {
      status: 'PASS',
      underlying_evidence_model: prepared.actual,
      expected_recommended_action: prepared.expected.action,
      owner_facing_fragment: ownerFragments[state],
      read_only_sentinel: 'PASS',
      rtl: 'PASS',
      technical_ltr: 'PASS',
      semantic_controls: 'PASS',
      accessibility_scan: axe.status,
    };
  }

  evidence.rtl = {
    status: 'PASS',
    states_exercised: requiredStates,
    basis: 'REAL_WP_ADMIN_COMPUTED_DIRECTION_AND_RENDERED_PERSIAN_SURFACE',
  };
  evidence.technical_ltr_isolation = {
    status: 'PASS',
    states_exercised: requiredStates,
    basis: 'CODE_AND_READONLY_REPORT_COMPUTED_DIRECTION_LTR',
  };

  prepare('canonical_active');
  await openAdmin(page);
  await page.locator('.wrap details > summary').click();
  evidence.semantics_accessibility_tree = {
    status: 'PASS',
    dom: await directionAndSemantics(page),
    aria_snapshots: await ariaEvidence(page),
    state_not_color_only: (await page.locator('.wrap [role="status"]').first().innerText()).trim().length > 0 ? 'PASS' : 'FAIL',
  };
  if (evidence.semantics_accessibility_tree.state_not_color_only !== 'PASS') fail('Status state is communicated only by styling/color.');

  runtime.detach();
  await context.close();
}

async function narrowLayout(browser) {
  prepare('canonical_active');
  const context = await browser.newContext({ viewport: narrowViewport, locale: 'fa-IR' });
  const page = await login(context, fixture.users.admin, env.adminPassword, narrowViewport);
  const runtime = makeRuntimeCollector(page);
  runtime.reset();
  await openAdmin(page);
  await page.locator('.wrap details > summary').click();
  await page.getByRole('button', { name: 'ذخیره و اعمال قالب تمام‌عرض' }).scrollIntoViewIfNeeded();
  const layout = await page.locator('.wrap').evaluate((wrap) => {
    const viewportWidth = window.innerWidth;
    const visible = Array.from(wrap.querySelectorAll('*')).filter((element) => {
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    });
    const escapes = visible.map((element) => {
      const rect = element.getBoundingClientRect();
      return {
        tag: element.tagName.toLowerCase(),
        id: element.id || '',
        class: element.className && typeof element.className === 'string' ? element.className : '',
        left: rect.left,
        right: rect.right,
        width: rect.width,
      };
    }).filter((item) => item.left < -1 || item.right > viewportWidth + 1);
    const keySelectors = [
      'label[for="srwf_registration_page_id"]',
      '#srwf_registration_page_id',
      '.wrap [role="status"]',
      '.wrap details',
      '#srwf-host-diagnostic-report',
      'input[type="submit"],button[type="submit"]',
    ];
    const keyBounds = {};
    for (const selector of keySelectors) {
      const element = selector.startsWith('.wrap') ? document.querySelector(selector) : wrap.querySelector(selector);
      if (!element) continue;
      const rect = element.getBoundingClientRect();
      keyBounds[selector] = { left: rect.left, right: rect.right, top: rect.top, bottom: rect.bottom, width: rect.width, height: rect.height };
    }
    const label = wrap.querySelector('label[for="srwf_registration_page_id"]')?.getBoundingClientRect();
    const select = wrap.querySelector('#srwf_registration_page_id')?.getBoundingClientRect();
    const overlap = label && select
      ? Math.max(0, Math.min(label.right, select.right) - Math.max(label.left, select.left)) * Math.max(0, Math.min(label.bottom, select.bottom) - Math.max(label.top, select.top))
      : 0;
    return {
      viewport_width: viewportWidth,
      client_width: document.documentElement.clientWidth,
      document_scroll_width: document.documentElement.scrollWidth,
      body_scroll_width: document.body.scrollWidth,
      wrap_scroll_width: wrap.scrollWidth,
      wrap_client_width: wrap.clientWidth,
      escaping_visible_plugin_elements: escapes,
      key_bounds: keyBounds,
      label_select_overlap_area: overlap,
    };
  });
  const pass = layout.document_scroll_width <= layout.client_width + 1
    && layout.body_scroll_width <= layout.client_width + 1
    && layout.escaping_visible_plugin_elements.length === 0
    && layout.label_select_overlap_area === 0;
  evidence.narrow_admin_layout = {
    status: pass ? 'PASS' : 'FAIL',
    viewport: narrowViewport,
    rationale: '390px matches the established narrow RTL browser matrix and represents phone-like wp-admin access without introducing a product breakpoint.',
    measurements: layout,
    controls_reachable: 'PASS',
    technical_details_opened: true,
  };
  recordRuntimeIssues('narrow_admin', runtime.issues);
  if (!pass) {
    await page.screenshot({ path: path.join(env.artifactDir, 'wu06-narrow-admin-failure.png'), fullPage: true });
    fail('Narrow admin layout is widened/escaped by plugin-owned UI or label/control overlap was observed.');
  }
  runtime.detach();
  await context.close();
}

async function activeDescriptor(page) {
  return page.evaluate(() => {
    const element = document.activeElement;
    if (!element) return null;
    return {
      tag: element.tagName.toLowerCase(),
      id: element.id || '',
      name: element.getAttribute('name') || '',
      text: (element.textContent || element.getAttribute('value') || '').trim().slice(0, 120),
      href: element.getAttribute('href') || '',
    };
  });
}

async function tabUntil(page, selector, maxTabs = 220, backwards = false) {
  for (let i = 0; i < maxTabs; i += 1) {
    await page.keyboard.press(backwards ? 'Shift+Tab' : 'Tab');
    const matched = await page.evaluate((targetSelector) => document.activeElement?.matches(targetSelector) === true, selector);
    if (matched) return { tabs: i + 1, active: await activeDescriptor(page) };
  }
  throw new Error(`Keyboard traversal did not reach ${selector} within ${maxTabs} Tab presses.`);
}

async function focusStyle(locator, baseline = null) {
  const focused = await locator.evaluate((element) => {
    const style = getComputedStyle(element);
    return {
      is_active: document.activeElement === element,
      focus_visible: element.matches(':focus-visible'),
      outline_style: style.outlineStyle,
      outline_width: style.outlineWidth,
      outline_color: style.outlineColor,
      box_shadow: style.boxShadow,
      border_color: style.borderColor,
    };
  });
  const outlineWidth = Number.parseFloat(focused.outline_width) || 0;
  const visible = focused.focus_visible && (
    (focused.outline_style !== 'none' && outlineWidth > 0)
    || focused.box_shadow !== 'none'
    || (baseline && focused.border_color !== baseline.border_color)
  );
  return { ...focused, visible_focus: visible };
}

async function styleBaseline(locator) {
  return locator.evaluate((element) => {
    const style = getComputedStyle(element);
    return { border_color: style.borderColor, box_shadow: style.boxShadow, outline_style: style.outlineStyle, outline_width: style.outlineWidth };
  });
}

async function keyboardAndCheckAgain(browser) {
  prepare('canonical_active');
  const context = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
  let page = await login(context, fixture.users.admin, env.adminPassword, desktopViewport);
  await openAdmin(page);
  await page.evaluate(() => document.activeElement?.blur());

  const sequence = [];
  let reachedSubmit = false;
  for (let i = 0; i < 220; i += 1) {
    await page.keyboard.press('Tab');
    const descriptor = await activeDescriptor(page);
    if (descriptor) sequence.push(descriptor);
    if (descriptor?.name === 'submit') {
      reachedSubmit = true;
      break;
    }
  }
  if (!reachedSubmit) fail('Keyboard sequence did not reach the primary submit action.');
  const findIndex = (predicate) => sequence.findIndex(predicate);
  const checkIndex = findIndex((item) => item.href.includes('srwf_check=1'));
  const summaryIndex = findIndex((item) => item.tag === 'summary');
  const selectIndex = findIndex((item) => item.id === 'srwf_registration_page_id');
  const submitIndex = findIndex((item) => item.name === 'submit');
  if (!(checkIndex >= 0 && summaryIndex > checkIndex && selectIndex > summaryIndex && submitIndex > selectIndex)) {
    fail('Plugin keyboard focus progression is not logical (Check Again → details → selector → primary action).');
  }
  await page.keyboard.press('Shift+Tab');
  const reverseReturnsToSelect = await page.evaluate(() => document.activeElement?.id === 'srwf_registration_page_id');
  if (!reverseReturnsToSelect) fail('Shift+Tab did not move back from primary action to Registration selector.');

  await page.reload({ waitUntil: 'domcontentloaded' });
  await page.evaluate(() => document.activeElement?.blur());
  const summaryLocator = page.locator('.wrap details > summary');
  const summaryBaseline = await styleBaseline(summaryLocator);
  await tabUntil(page, '.wrap details > summary');
  const summaryFocus = await focusStyle(summaryLocator, summaryBaseline);
  await page.keyboard.press('Space');
  const detailsOpen = await page.locator('.wrap details').evaluate((details) => details.open);
  if (!detailsOpen || !summaryFocus.visible_focus) fail('Technical details disclosure lacks keyboard operation or visible focus.');
  await page.keyboard.press('Tab');
  const afterDetails = await activeDescriptor(page);
  if (!afterDetails || afterDetails.tag === 'summary') fail('Technical details introduced a keyboard trap.');

  prepare('theme_override');
  await page.goto(adminUrl, { waitUntil: 'domcontentloaded' });
  await page.evaluate(() => document.activeElement?.blur());
  const checkLocator = page.getByRole('link', { name: 'بررسی دوباره' });
  const checkBaseline = await styleBaseline(checkLocator);
  const checkTraversal = await tabUntil(page, 'a[href*="srwf_check=1"]');
  const checkFocus = await focusStyle(checkLocator, checkBaseline);
  const beforeCheck = snapshot('check-again-before');
  await Promise.all([
    page.waitForURL(/srwf_check=1/, { timeout: 15000 }),
    page.keyboard.press('Enter'),
  ]);
  await page.locator('.wrap').waitFor({ state: 'visible' });
  const afterCheck = snapshot('check-again-after');
  try {
    assert.deepStrictEqual(afterCheck, beforeCheck);
  } catch {
    fail('Check Again changed configuration/page/theme-file sentinel state.');
  }
  if (!checkFocus.visible_focus) fail('Check Again did not have a visible rendered focus indication.');
  evidence.check_again_read_only = {
    status: 'PASS',
    interaction: 'REAL_KEYBOARD_ENTER_ON_SETTINGS_LINK',
    traversal: checkTraversal,
    visible_focus: checkFocus,
    sentinel_scope: ['schema-v1 configuration', 'selected Registration page', 'page-template assignment', 'page content hashes', 'DB override source', 'theme-file source'],
    theme_file_source_preserved: beforeCheck.theme_file_source.exists && afterCheck.theme_file_source.sha256 === beforeCheck.theme_file_source.sha256,
  };

  evidence.keyboard_focus = {
    status: 'PASS',
    sequence_to_primary_action: sequence,
    logical_progression: 'PASS',
    shift_tab_reverse: 'PASS',
    details_disclosure: { status: 'PASS', focus: summaryFocus, focus_after_space_tab: afterDetails },
    check_again: { status: 'PASS', focus: checkFocus },
  };

  await context.close();

  prepare('first_run');
  const applyContext = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
  page = await login(applyContext, fixture.users.admin, env.adminPassword, desktopViewport);
  await openAdmin(page);
  const beforeApply = snapshot('authorized-apply-before');
  await page.evaluate(() => document.activeElement?.blur());
  const select = page.locator('#srwf_registration_page_id');
  const selectBaseline = await styleBaseline(select);
  const selectorTraversal = await tabUntil(page, '#srwf_registration_page_id');
  const selectFocus = await focusStyle(select, selectBaseline);
  if (!selectFocus.visible_focus) fail('Registration selector does not have visible focus in the rendered browser.');

  await page.keyboard.press('Home');
  const optionCount = await select.locator('option').count();
  for (let i = 0; i <= optionCount; i += 1) {
    if ((await select.inputValue()) === String(fixture.pages.apply_target)) break;
    await page.keyboard.press('ArrowDown');
  }
  if ((await select.inputValue()) !== String(fixture.pages.apply_target)) fail('Keyboard could not select the synthetic valid Registration page.');

  const primary = page.getByRole('button', { name: 'ذخیره و اعمال قالب تمام‌عرض' });
  const primaryBaseline = await styleBaseline(primary);
  await page.keyboard.press('Tab');
  if (!(await page.evaluate(() => document.activeElement?.getAttribute('name') === 'submit'))) {
    await tabUntil(page, '[name="submit"]');
  }
  const primaryFocus = await focusStyle(primary, primaryBaseline);
  if (!primaryFocus.visible_focus) fail('Primary save/apply action does not have visible focus.');
  await Promise.all([
    page.waitForURL(/srwf_result=success/, { timeout: 15000 }),
    page.keyboard.press('Enter'),
  ]);
  await page.locator('.wrap').waitFor({ state: 'visible' });
  const successText = await page.locator('.wrap').innerText();
  if (!successText.includes('تنظیم ذخیره شد و قالب تمام‌عرض به صفحه انتخاب‌شده اعمال و بازخوانی شد')) fail('Authorized apply did not present truthful success result.');
  const afterApply = snapshot('authorized-apply-after');
  const expectedId = Number(fixture.pages.apply_target);
  const schemaOk = afterApply.configuration_raw?.schema_version === 1
    && afterApply.configuration_raw?.roles?.registration?.page_id === expectedId;
  const assignmentOk = afterApply.tracked_pages?.apply_target?.page_template_readback === 'registration-full-width';
  const contentUnchanged = Object.keys(beforeApply.tracked_pages || {}).every((key) => (
    beforeApply.tracked_pages[key].post_content_sha256 === afterApply.tracked_pages?.[key]?.post_content_sha256
  ));
  const previousPageUnchanged = beforeApply.tracked_pages?.healthy?.page_template_readback === afterApply.tracked_pages?.healthy?.page_template_readback;
  if (!schemaOk || !assignmentOk || !contentUnchanged || !previousPageUnchanged) fail('Authorized apply did not preserve the bounded WU-03 mutation contract.');
  evidence.successful_apply = {
    status: 'PASS',
    interaction: 'REAL_KEYBOARD_SELECTOR_AND_ENTER_SUBMIT',
    selected_page_id: expectedId,
    schema_v1_configuration_persisted: 'PASS',
    canonical_assignment_readback: afterApply.tracked_pages.apply_target.page_template_readback,
    page_content_unchanged: 'PASS',
    previous_page_not_rewritten: 'PASS',
    truthful_success_notice: 'PASS',
    selector_traversal: selectorTraversal,
    selector_visible_focus: selectFocus,
    primary_visible_focus: primaryFocus,
  };
  await applyContext.close();
}

async function securityQualification(browser) {
  prepare('canonical_active');
  const beforeNoCapability = snapshot('security-no-cap-before');
  const noCapContext = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
  const noCapPage = await login(noCapContext, fixture.users.subscriber, env.subscriberPassword, desktopViewport);
  const deniedResponse = await noCapPage.goto(adminUrl, { waitUntil: 'domcontentloaded' });
  const deniedStatus = deniedResponse?.status() || 0;
  const deniedBody = await noCapPage.locator('body').innerText();
  const mutationResponse = await noCapContext.request.post(adminPostUrl, {
    form: {
      action: 'srwf_host_companion_save_apply',
      srwf_registration_page_id: String(fixture.pages.apply_target),
      _srwf_host_nonce: 'not-relevant-without-capability',
    },
    maxRedirects: 0,
  });
  const afterNoCapability = snapshot('security-no-cap-after');
  assert.deepStrictEqual(afterNoCapability, beforeNoCapability, 'No-capability requests mutated persistent state.');
  if (deniedStatus !== 403 || mutationResponse.status() !== 403 || deniedBody.includes('ذخیره و اعمال قالب تمام‌عرض')) {
    fail('User without manage_options reached protected SRWF Host UI/mutation boundary.');
  }
  evidence.security_authorization.no_manage_options = {
    status: 'PASS',
    settings_http_status: deniedStatus,
    mutation_http_status: mutationResponse.status(),
    protected_form_absent: !deniedBody.includes('ذخیره و اعمال قالب تمام‌عرض'),
    sentinel_unchanged: 'PASS',
  };
  await noCapContext.close();

  prepare('canonical_active');
  const beforeEditDenied = snapshot('security-edit-denied-before');
  const limitedContext = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
  const limitedPage = await login(limitedContext, fixture.users.settings_only, env.settingsPassword, desktopViewport);
  await openAdmin(limitedPage);
  await limitedPage.locator('#srwf_registration_page_id').selectOption(String(fixture.pages.apply_target));
  await Promise.all([
    limitedPage.waitForURL(/srwf_result=target_edit_denied/, { timeout: 15000 }),
    limitedPage.getByRole('button', { name: 'ذخیره و اعمال قالب تمام‌عرض' }).click(),
  ]);
  const deniedText = await limitedPage.locator('.wrap').innerText();
  const afterEditDenied = snapshot('security-edit-denied-after');
  assert.deepStrictEqual(afterEditDenied, beforeEditDenied, 'Target edit denial mutated persistent state.');
  if (!deniedText.includes('شما اجازه ویرایش برگه انتخاب‌شده را ندارید') || deniedText.includes('تنظیم ذخیره شد و قالب تمام‌عرض به صفحه انتخاب‌شده اعمال و بازخوانی شد')) {
    fail('Target-page edit denial did not fail closed with truthful UI.');
  }
  evidence.security_authorization.manage_options_without_edit_target = {
    status: 'PASS',
    real_browser_form_submission: true,
    result_code_observed: 'target_edit_denied',
    sentinel_unchanged: 'PASS',
    fake_success_absent: true,
  };
  await limitedContext.close();

  prepare('canonical_active');
  const beforeNonce = snapshot('security-nonce-before');
  const adminContext = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
  const adminPage = await login(adminContext, fixture.users.admin, env.adminPassword, desktopViewport);
  await openAdmin(adminPage);
  const invalidResponse = await adminContext.request.post(adminPostUrl, {
    form: {
      action: 'srwf_host_companion_save_apply',
      srwf_registration_page_id: String(fixture.pages.apply_target),
      _srwf_host_nonce: 'invalid-wu06-nonce',
    },
  });
  const invalidText = await invalidResponse.text();
  const afterInvalid = snapshot('security-invalid-nonce-after');
  assert.deepStrictEqual(afterInvalid, beforeNonce, 'Invalid nonce mutated persistent state.');
  const missingResponse = await adminContext.request.post(adminPostUrl, {
    form: {
      action: 'srwf_host_companion_save_apply',
      srwf_registration_page_id: String(fixture.pages.apply_target),
    },
  });
  const missingText = await missingResponse.text();
  const afterMissing = snapshot('security-missing-nonce-after');
  assert.deepStrictEqual(afterMissing, beforeNonce, 'Missing nonce mutated persistent state.');
  const nonceMessage = 'درخواست امنیتی معتبر نبود؛ هیچ تنظیم یا قالبی تغییر نکرد';
  const fakeSuccess = 'تنظیم ذخیره شد و قالب تمام‌عرض به صفحه انتخاب‌شده اعمال و بازخوانی شد';
  if (!invalidText.includes(nonceMessage) || !missingText.includes(nonceMessage) || invalidText.includes(fakeSuccess) || missingText.includes(fakeSuccess)) {
    fail('Invalid/missing nonce did not reject mutation with truthful no-success result.');
  }
  evidence.security_authorization.invalid_nonce = {
    status: 'PASS',
    request_boundary: 'REAL_AUTHENTICATED_HTTP_POST',
    sentinel_unchanged: 'PASS',
    fake_success_absent: true,
  };
  evidence.security_authorization.missing_nonce = {
    status: 'PASS',
    request_boundary: 'REAL_AUTHENTICATED_HTTP_POST',
    sentinel_unchanged: 'PASS',
    fake_success_absent: true,
  };
  await adminContext.close();

  evidence.security_authorization.valid_authorized_apply = { status: evidence.successful_apply.status };
  evidence.security_authorization.check_again_read_only = { status: evidence.check_again_read_only.status };
}

async function privacyQualification(browser) {
  prepare('canonical_active');
  const context = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
  await context.addCookies([{
    name: 'srwf_wu06_secret_cookie',
    value: 'SRWF_WU06_COOKIE_SECRET',
    url: env.baseUrl,
  }]);
  const page = await login(context, fixture.users.admin, env.adminPassword, desktopViewport);
  await openAdmin(page);
  await page.locator('.wrap details > summary').click();
  const report = await page.locator('#srwf-host-diagnostic-report').inputValue();
  const forbidden = [
    ...fixture.privacy_markers,
    'SRWF_WU06_COOKIE_SECRET',
    env.adminPassword,
    env.settingsPassword,
    env.subscriberPassword,
  ];
  const leaked = forbidden.filter((marker) => marker && report.includes(marker));
  const prohibitedTokens = ['cookie=', 'nonce=', '_wpnonce', '_srwf_host_nonce', 'password=', 'authorization=', 'bearer '];
  const tokenLeaks = prohibitedTokens.filter((token) => report.toLowerCase().includes(token.toLowerCase()));
  const allowed = [
    `page_id=${fixture.pages.healthy}`,
    'expected_template_slug=registration-full-width',
    `wordpress_version=${fixture.environment.wordpress_version}`,
    `php_version=${fixture.environment.php_version}`,
  ];
  const allowedPresent = allowed.every((item) => report.includes(item));
  if (leaked.length > 0 || tokenLeaks.length > 0 || !allowedPresent) fail('Diagnostic report privacy boundary failed or useful bounded technical evidence disappeared.');
  evidence.diagnostic_privacy = {
    status: 'PASS',
    checked_sensitive_classes: [
      'synthetic student/form values',
      'synthetic uploaded-file URL',
      'cookies',
      'authentication credentials',
      'session/nonces',
      'passwords',
      'secrets/tokens',
      'synthetic user email PII',
    ],
    prohibited_marker_count: forbidden.length + prohibitedTokens.length,
    leaked_marker_count: 0,
    useful_allowed_fields_present: 'PASS',
  };
  await context.close();
}

const browser = await chromium.launch({ headless: true });
evidence.browser = {
  engine: 'chromium',
  browser_version: browser.version(),
  playwright_version: playwrightVersion,
  node_version: process.version,
};

try {
  await stateMatrix(browser);
  await narrowLayout(browser);
  await keyboardAndCheckAgain(browser);
  await securityQualification(browser);
  await privacyQualification(browser);

  evidence.mutation_sentinels = {
    status: 'PASS',
    read_only_state_matrix: 'PASS',
    check_again: 'PASS',
    protected_fields: [
      'schema-v1 configuration',
      'selected Registration page',
      'page-template assignment',
      'page content hashes',
      'active DB override source',
      'theme-file source',
    ],
  };
  const statePass = requiredStates.every((state) => evidence.state_matrix[state]?.status === 'PASS');
  const axePass = requiredStates.every((state) => evidence.automated_accessibility_scan.states[state]?.status === 'PASS');
  const securityPass = [
    'no_manage_options',
    'manage_options_without_edit_target',
    'invalid_nonce',
    'missing_nonce',
  ].every((key) => evidence.security_authorization[key]?.status === 'PASS');
  const noRuntimeIssues = evidence.browser_runtime.normal_path_issues.length === 0;
  if (!statePass || !axePass || !securityPass || !noRuntimeIssues
    || evidence.successful_apply.status !== 'PASS'
    || evidence.check_again_read_only.status !== 'PASS'
    || evidence.narrow_admin_layout.status !== 'PASS'
    || evidence.keyboard_focus.status !== 'PASS'
    || evidence.semantics_accessibility_tree.status !== 'PASS'
    || evidence.diagnostic_privacy.status !== 'PASS') {
    fail('WU-06 aggregate browser qualification did not satisfy all bounded subclaims.');
  }

  evidence.automated_accessibility_scan.status = 'PASS';
  evidence.browser_runtime.status = 'PASS';
  evidence.locale_direction = { locale: fixture.environment.locale, direction: 'rtl', status: 'PASS' };
  evidence.overall_status = 'ADMIN_BROWSER_QUALIFICATION_PASS_ON_PINNED_TARGET_TUPLE';
} catch (error) {
  if (!evidence.failures.includes(error.message)) evidence.failures.push(error.message);
  evidence.overall_status = 'FAIL';
  try {
    const diagnosticContext = await browser.newContext({ viewport: desktopViewport, locale: 'fa-IR' });
    const diagnosticPage = await login(diagnosticContext, fixture.users.admin, env.adminPassword, desktopViewport);
    await diagnosticPage.goto(adminUrl, { waitUntil: 'domcontentloaded' });
    await diagnosticPage.screenshot({ path: path.join(env.artifactDir, 'wu06-browser-failure.png'), fullPage: true });
    await diagnosticContext.close();
  } catch {
    // The machine-readable failure remains available even if a screenshot cannot be captured.
  }
  saveEvidence();
  await browser.close();
  console.error(error.stack || error.message);
  process.exit(1);
}

saveEvidence();
await browser.close();
console.log(JSON.stringify({
  overall_status: evidence.overall_status,
  state_matrix: Object.fromEntries(requiredStates.map((state) => [state, evidence.state_matrix[state]?.status])),
  accessibility: evidence.automated_accessibility_scan.status,
  narrow_admin: evidence.narrow_admin_layout.status,
  keyboard_focus: evidence.keyboard_focus.status,
  security: evidence.security_authorization,
  privacy: evidence.diagnostic_privacy.status,
}, null, 2));
