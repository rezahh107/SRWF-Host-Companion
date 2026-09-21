import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { createRequire } from 'node:module';
import { chromium } from 'playwright';

const require = createRequire(import.meta.url);
const playwrightVersion = require('playwright/package.json').version;
const evidencePath = process.env.SRWF_WU05_EVIDENCE_PATH;
const artifactDir = process.env.SRWF_ARTIFACT_DIR;

if (!evidencePath || !artifactDir) {
  throw new Error('SRWF_WU05_EVIDENCE_PATH and SRWF_ARTIFACT_DIR are required.');
}

const evidence = JSON.parse(fs.readFileSync(evidencePath, 'utf8'));
const pageUrl = evidence?.fixture?.page_url;
if (!pageUrl) {
  throw new Error('WU-05 evidence does not contain fixture.page_url.');
}

const viewportWidths = [320, 390, 430, 1440];
const viewportHeight = 1100;
const browser = await chromium.launch({ headless: true });
evidence.browser = {
  engine: 'chromium',
  browser_version: browser.version(),
  playwright_version: playwrightVersion,
  node_version: process.version,
};

const failures = [];
const results = {};

function rounded(value) {
  return Math.round(value * 100) / 100;
}

function assertion(name, pass, details) {
  return { name, status: pass ? 'PASS' : 'FAIL', details };
}

for (const width of viewportWidths) {
  const context = await browser.newContext({
    viewport: { width, height: viewportHeight },
    locale: 'fa-IR',
  });
  const page = await context.newPage();
  const consoleErrors = [];
  page.on('console', (message) => {
    if (message.type() === 'error') {
      consoleErrors.push(message.text());
    }
  });
  page.on('pageerror', (error) => consoleErrors.push(error.message));

  let result;
  try {
    const response = await page.goto(pageUrl, { waitUntil: 'networkidle' });
    if (!response || response.status() !== 200) {
      throw new Error(`Unexpected HTTP status: ${response ? response.status() : 'no response'}`);
    }

    const raw = await page.evaluate(() => {
      const rect = (selector) => {
        const element = document.querySelector(selector);
        if (!element) return null;
        const r = element.getBoundingClientRect();
        return {
          left: r.left,
          right: r.right,
          top: r.top,
          bottom: r.bottom,
          width: r.width,
          height: r.height,
        };
      };
      const root = document.documentElement;
      const rootStyle = getComputedStyle(root);
      const shellElement = document.querySelector('main.srwf-host-companion-registration-shell');
      const shellStyle = shellElement ? getComputedStyle(shellElement) : null;
      const nav = document.querySelector('header.wp-block-template-part nav.wp-block-navigation');
      return {
        viewportWidth: window.innerWidth,
        clientWidth: root.clientWidth,
        documentScrollWidth: root.scrollWidth,
        bodyScrollWidth: document.body ? document.body.scrollWidth : null,
        htmlDir: root.getAttribute('dir') || rootStyle.direction,
        rootDirection: rootStyle.direction,
        tt25GlobalContentSize: rootStyle.getPropertyValue('--wp--style--global--content-size').trim(),
        tt25GlobalWideSize: rootStyle.getPropertyValue('--wp--style--global--wide-size').trim(),
        shellPaddingLeft: shellStyle ? shellStyle.paddingLeft : null,
        shellPaddingRight: shellStyle ? shellStyle.paddingRight : null,
        shell: rect('main.srwf-host-companion-registration-shell'),
        postContent: rect('main.srwf-host-companion-registration-shell .wp-block-post-content'),
        application: rect('.srwf-wu05-application-region'),
        header: rect('header.wp-block-template-part'),
        footer: rect('footer.wp-block-template-part'),
        navigation: rect('header.wp-block-template-part nav.wp-block-navigation'),
        navigationLinkCount: nav ? nav.querySelectorAll('a').length : 0,
        shellMarkerCount: document.querySelectorAll('main.srwf-host-companion-registration-shell').length,
        applicationMarkerCount: document.querySelectorAll('.srwf-wu05-application-region').length,
      };
    });

    const app = raw.application;
    const shell = raw.shell;
    const postContent = raw.postContent;
    const physicalLeftGutter = app ? app.left : null;
    const physicalRightGutter = app ? raw.viewportWidth - app.right : null;
    const isRtl = raw.htmlDir === 'rtl' || raw.rootDirection === 'rtl';
    const inlineStartGutter = isRtl ? physicalRightGutter : physicalLeftGutter;
    const inlineEndGutter = isRtl ? physicalLeftGutter : physicalRightGutter;
    const noHorizontalOverflow = raw.documentScrollWidth <= raw.clientWidth + 1 && (raw.bodyScrollWidth === null || raw.bodyScrollWidth <= raw.clientWidth + 1);
    const rootContentSize = Number.parseFloat(raw.tt25GlobalContentSize);

    const checks = [
      assertion('http_and_canonical_shell_rendered', raw.shellMarkerCount === 1 && raw.applicationMarkerCount === 1, raw),
      assertion('rtl_direction', isRtl, { htmlDir: raw.htmlDir, rootDirection: raw.rootDirection }),
      assertion('shell_inside_viewport', !!shell && shell.left >= -1 && shell.right <= raw.viewportWidth + 1, shell),
      assertion('post_content_inside_shell', !!postContent && !!shell && postContent.left >= shell.left - 1 && postContent.right <= shell.right + 1, { shell, postContent }),
      assertion('safe_inline_gutters', !!app && inlineStartGutter >= 16 && inlineEndGutter >= 16, { inlineStartGutter, inlineEndGutter }),
      assertion('application_uses_available_width', !!app && app.width >= raw.viewportWidth - 120, { applicationWidth: app?.width, viewportWidth: raw.viewportWidth }),
      assertion('no_horizontal_overflow', noHorizontalOverflow, { documentScrollWidth: raw.documentScrollWidth, bodyScrollWidth: raw.bodyScrollWidth, clientWidth: raw.clientWidth }),
      assertion('header_present', !!raw.header && raw.header.width > 0 && raw.header.height > 0, raw.header),
      assertion('footer_present', !!raw.footer && raw.footer.width > 0 && raw.footer.height > 0, raw.footer),
      assertion('navigation_present', !!raw.navigation && raw.navigation.width > 0 && raw.navigation.height > 0, { navigation: raw.navigation, linkCount: raw.navigationLinkCount }),
    ];

    if (width === 1440) {
      checks.push(
        assertion(
          'desktop_escapes_tt25_article_content_size',
          !!app && Number.isFinite(rootContentSize) && app.width > rootContentSize + 250,
          { applicationWidth: app?.width, tt25GlobalContentSize: raw.tt25GlobalContentSize },
        ),
      );
    }

    const pass = checks.every((check) => check.status === 'PASS');
    result = {
      status: pass ? 'PASS' : 'FAIL',
      viewport: { width: raw.viewportWidth, height: viewportHeight, direction: isRtl ? 'rtl' : raw.rootDirection },
      theme_layout: {
        global_content_size: raw.tt25GlobalContentSize,
        global_wide_size: raw.tt25GlobalWideSize,
      },
      shell: shell && Object.fromEntries(Object.entries(shell).map(([key, value]) => [key, rounded(value)])),
      shell_padding: { left: raw.shellPaddingLeft, right: raw.shellPaddingRight },
      post_content: postContent && Object.fromEntries(Object.entries(postContent).map(([key, value]) => [key, rounded(value)])),
      application_region: app && {
        ...Object.fromEntries(Object.entries(app).map(([key, value]) => [key, rounded(value)])),
        physical_left_gutter: rounded(physicalLeftGutter),
        physical_right_gutter: rounded(physicalRightGutter),
        inline_start_gutter: rounded(inlineStartGutter),
        inline_end_gutter: rounded(inlineEndGutter),
      },
      overflow: {
        client_width: raw.clientWidth,
        document_scroll_width: raw.documentScrollWidth,
        body_scroll_width: raw.bodyScrollWidth,
        status: noHorizontalOverflow ? 'PASS' : 'FAIL',
      },
      host_integrity: {
        header: raw.header ? 'PASS' : 'FAIL',
        footer: raw.footer ? 'PASS' : 'FAIL',
        navigation: raw.navigation ? 'PASS' : 'FAIL',
        navigation_link_count: raw.navigationLinkCount,
      },
      console_errors: consoleErrors,
      checks,
    };

    if (!pass) {
      failures.push(`${width}px geometry/assertion failure`);
      await page.screenshot({ path: path.join(artifactDir, `wu05-${width}-failure.png`), fullPage: true });
    }
  } catch (error) {
    failures.push(`${width}px browser failure: ${error.message}`);
    result = {
      status: 'FAIL',
      viewport: { width, height: viewportHeight, direction: 'unknown' },
      error: error.stack || error.message,
      console_errors: consoleErrors,
    };
    try {
      await page.screenshot({ path: path.join(artifactDir, `wu05-${width}-failure.png`), fullPage: true });
    } catch {
      // The browser/page may already be unavailable. The JSON error is still retained.
    }
  } finally {
    results[String(width)] = result;
    await context.close();
  }
}

await browser.close();

evidence.viewports = results;
const allPass = viewportWidths.every((width) => results[String(width)]?.status === 'PASS');
const desktop = results['1440'];
const canonicalRendered = viewportWidths.every((width) => results[String(width)]?.checks?.some((check) => check.name === 'http_and_canonical_shell_rendered' && check.status === 'PASS'));
const overflowPass = viewportWidths.every((width) => results[String(width)]?.overflow?.status === 'PASS');
const hostIntegrityPass = viewportWidths.every((width) => {
  const integrity = results[String(width)]?.host_integrity;
  return integrity?.header === 'PASS' && integrity?.footer === 'PASS' && integrity?.navigation === 'PASS';
});
const rtlPass = viewportWidths.every((width) => results[String(width)]?.viewport?.direction === 'rtl');
const desktopEscapePass = desktop?.checks?.some((check) => check.name === 'desktop_escapes_tt25_article_content_size' && check.status === 'PASS') === true;

evidence.layout_hypothesis.status = allPass && desktopEscapePass ? 'SUPPORTED_ON_PINNED_TARGET_TUPLE' : 'FALSIFIED_OR_NOT_PROVEN';
evidence.layout_hypothesis.falsification_basis = 'REAL_BROWSER_GEOMETRY_320_390_430_1440_RTL';
evidence.claims.canonical_assignment_render = canonicalRendered ? 'PASS' : 'FAIL';
evidence.claims.full_width_geometry = allPass && desktopEscapePass ? 'PASS' : 'FAIL';
evidence.claims.horizontal_overflow = overflowPass ? 'PASS' : 'FAIL';
evidence.claims.header_footer_navigation = hostIntegrityPass ? 'PASS' : 'FAIL';
evidence.claims.rtl_frontend = rtlPass ? 'PASS' : 'FAIL';
evidence.overall_status = allPass && desktopEscapePass
  ? 'FULL_WIDTH_GEOMETRY_AUTOMATED_QUALIFIED_ON_PINNED_TARGET_TUPLE'
  : 'FAIL';
evidence.failures = failures;

fs.writeFileSync(evidencePath, `${JSON.stringify(evidence, null, 2)}\n`, 'utf8');

if (evidence.overall_status !== 'FULL_WIDTH_GEOMETRY_AUTOMATED_QUALIFIED_ON_PINNED_TARGET_TUPLE') {
  console.error(JSON.stringify({ overall_status: evidence.overall_status, failures }, null, 2));
  process.exit(1);
}

console.log(JSON.stringify({
  overall_status: evidence.overall_status,
  browser: evidence.browser,
  viewports: Object.fromEntries(viewportWidths.map((width) => [width, {
    status: results[String(width)].status,
    application_width: results[String(width)].application_region?.width,
    inline_start_gutter: results[String(width)].application_region?.inline_start_gutter,
    inline_end_gutter: results[String(width)].application_region?.inline_end_gutter,
    overflow: results[String(width)].overflow?.status,
  }])),
}, null, 2));
