'use strict';

const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const artifactDir = process.env.SRWF_ARTIFACT_DIR;
const fixturePath = process.env.SRWF_WU05_FIXTURE_EVIDENCE_PATH;
const evidencePath = process.env.SRWF_WU05_EVIDENCE_PATH;
const testedCommit = process.env.SRWF_TESTED_COMMIT_SHA;
const mode = process.env.SRWF_WU05_MODE || 'qualification';

for (const [name, value] of Object.entries({ artifactDir, fixturePath, evidencePath, testedCommit })) {
  if (!value) {
    throw new Error(`Missing required environment value: ${name}`);
  }
}

const fixture = JSON.parse(fs.readFileSync(fixturePath, 'utf8'));
const viewports = [320, 390, 430, 1440];

function round(value) {
  return Math.round(value * 100) / 100;
}

function classifyViewport(observation) {
  const width = observation.viewport.inner_width;
  const app = observation.application_region;
  const shell = observation.shell;
  const overflow = observation.viewport.scroll_width - observation.viewport.client_width;
  const maxGutter = width >= 1000 ? 96 : 32;
  const minGutter = 12;
  const desktopMinimumWidth = width >= 1000 ? width * 0.75 : 0;

  const checks = {
    rtl: observation.direction === 'rtl',
    shell_present: Boolean(shell),
    application_present: Boolean(app),
    horizontal_overflow_absent: overflow <= 1,
    header_integrity: observation.header && observation.header.visible && observation.header.rect.width > 0,
    footer_integrity: observation.footer && observation.footer.visible && observation.footer.rect.width > 0,
    navigation_integrity: observation.navigation
      ? observation.navigation.visible && observation.navigation.rect.width > 0
      : true,
    application_inside_viewport:
      Boolean(app) && app.rect.left >= -1 && app.rect.right <= width + 1,
    safe_inline_start_gutter:
      Boolean(app) && app.inline_start_gutter >= minGutter && app.inline_start_gutter <= maxGutter,
    safe_inline_end_gutter:
      Boolean(app) && app.inline_end_gutter >= minGutter && app.inline_end_gutter <= maxGutter,
    mobile_canvas_uses_available_width:
      width >= 1000 || (Boolean(app) && app.rect.width >= width - 2 * maxGutter),
    desktop_canvas_escapes_article_width:
      width < 1000 || (Boolean(app) && app.rect.width >= desktopMinimumWidth),
  };

  const failed = Object.entries(checks).filter(([, passed]) => !passed).map(([name]) => name);
  return {
    status: failed.length === 0 ? 'PASS' : 'FAIL',
    checks,
    failed_checks: failed,
  };
}

(async () => {
  fs.mkdirSync(artifactDir, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const observations = [];

  try {
    for (const viewportWidth of viewports) {
      const context = await browser.newContext({
        viewport: { width: viewportWidth, height: 1100 },
        locale: 'fa-IR',
      });
      const page = await context.newPage();
      const consoleMessages = [];
      page.on('console', (message) => {
        consoleMessages.push({ type: message.type(), text: message.text() });
      });

      const response = await page.goto(fixture.page_url, { waitUntil: 'networkidle' });
      if (!response || response.status() !== 200) {
        throw new Error(`Expected HTTP 200 at ${viewportWidth}px, got ${response ? response.status() : 'no response'}`);
      }

      const observation = await page.evaluate(() => {
        const viewportWidth = window.innerWidth;
        const direction = getComputedStyle(document.documentElement).direction;

        const rectObject = (rect) => ({
          x: rect.x,
          y: rect.y,
          width: rect.width,
          height: rect.height,
          top: rect.top,
          right: rect.right,
          bottom: rect.bottom,
          left: rect.left,
        });

        const snapshot = (element) => {
          if (!element) {
            return null;
          }
          const rect = element.getBoundingClientRect();
          const style = getComputedStyle(element);
          const inlineStartGutter = direction === 'rtl' ? viewportWidth - rect.right : rect.left;
          const inlineEndGutter = direction === 'rtl' ? rect.left : viewportWidth - rect.right;
          return {
            tag: element.tagName.toLowerCase(),
            id: element.id || '',
            class_name: element.className || '',
            rect: rectObject(rect),
            inline_start_gutter: inlineStartGutter,
            inline_end_gutter: inlineEndGutter,
            visible: rect.width > 0 && rect.height > 0 && style.visibility !== 'hidden' && style.display !== 'none',
            computed: {
              display: style.display,
              width: style.width,
              max_width: style.maxWidth,
              margin_inline_start: style.marginInlineStart,
              margin_inline_end: style.marginInlineEnd,
              padding_inline_start: style.paddingInlineStart,
              padding_inline_end: style.paddingInlineEnd,
              box_sizing: style.boxSizing,
              position: style.position,
            },
          };
        };

        const ancestry = (element) => {
          const result = [];
          let current = element;
          for (let depth = 0; current && depth < 9; depth += 1, current = current.parentElement) {
            const snap = snapshot(current);
            if (snap) {
              result.push(snap);
            }
          }
          return result;
        };

        const shell = document.querySelector('.srwf-host-companion-registration-shell');
        const postContent = shell ? shell.querySelector('.wp-block-post-content') : document.querySelector('.wp-block-post-content');
        const app = document.querySelector('.srwf-wu05-application-region');
        const header = document.querySelector('header');
        const footer = document.querySelector('footer');
        const navigation = document.querySelector('header nav, nav.wp-block-navigation, nav');
        const rootStyle = getComputedStyle(document.documentElement);

        return {
          direction,
          viewport: {
            inner_width: window.innerWidth,
            client_width: document.documentElement.clientWidth,
            scroll_width: document.documentElement.scrollWidth,
            body_scroll_width: document.body ? document.body.scrollWidth : null,
          },
          shell: snapshot(shell),
          post_content: snapshot(postContent),
          application_region: snapshot(app),
          header: snapshot(header),
          footer: snapshot(footer),
          navigation: snapshot(navigation),
          global_layout_variables: {
            content_size: rootStyle.getPropertyValue('--wp--style--global--content-size').trim(),
            wide_size: rootStyle.getPropertyValue('--wp--style--global--wide-size').trim(),
          },
          ancestry: {
            shell: ancestry(shell),
            post_content: ancestry(postContent),
            application_region: ancestry(app),
          },
          markers: {
            shell: Boolean(shell),
            application: Boolean(app),
            content_text: document.body ? document.body.textContent.includes('SRWF_WU05_APPLICATION_CONTENT_MARKER') : false,
          },
        };
      });

      for (const key of ['shell', 'post_content', 'application_region', 'header', 'footer', 'navigation']) {
        const item = observation[key];
        if (item && item.rect) {
          for (const rectKey of Object.keys(item.rect)) {
            item.rect[rectKey] = round(item.rect[rectKey]);
          }
          item.inline_start_gutter = round(item.inline_start_gutter);
          item.inline_end_gutter = round(item.inline_end_gutter);
        }
      }
      for (const ancestryKey of Object.keys(observation.ancestry)) {
        observation.ancestry[ancestryKey] = observation.ancestry[ancestryKey].map((item) => {
          for (const rectKey of Object.keys(item.rect)) {
            item.rect[rectKey] = round(item.rect[rectKey]);
          }
          item.inline_start_gutter = round(item.inline_start_gutter);
          item.inline_end_gutter = round(item.inline_end_gutter);
          return item;
        });
      }

      observation.qualification = classifyViewport(observation);
      observation.navigation_result = observation.navigation ? 'PASS_IF_VISIBLE' : 'NOT_APPLICABLE_ABSENT_IN_FIXTURE_HEADER';
      observation.console = consoleMessages;
      observations.push(observation);

      await page.screenshot({
        path: path.join(artifactDir, `wu05-${viewportWidth}px.png`),
        fullPage: true,
      });
      await context.close();
    }
  } finally {
    await browser.close();
  }

  const viewportStatus = Object.fromEntries(
    observations.map((item) => [String(item.viewport.inner_width), item.qualification.status])
  );
  const qualificationPass = observations.every((item) => item.qualification.status === 'PASS');
  const fixtureContractPass =
    fixture.assignment && fixture.assignment.success === true &&
    fixture.assignment_readback === 'registration-full-width' &&
    fixture.configuration && fixture.configuration.roles &&
    fixture.configuration.roles.registration.page_id === fixture.page_id &&
    fixture.environment.is_rtl === true;

  const evidence = {
    schema: 'srwf-host-companion-wu05-full-width-v1',
    tested_commit_sha: testedCommit,
    mode,
    environment: fixture.environment,
    fixture: {
      page_id: fixture.page_id,
      page_url: fixture.page_url,
      assignment: fixture.assignment,
      assignment_readback: fixture.assignment_readback,
      template: fixture.template,
      contract_status: fixtureContractPass ? 'PASS' : 'FAIL',
    },
    viewport_matrix: observations,
    viewport_status: viewportStatus,
    dependencies: {
      gravity_forms: { availability: 'ENVIRONMENT_UNAVAILABLE', claim: 'NOT_PROVEN' },
      orbital: { availability: 'ENVIRONMENT_UNAVAILABLE', claim: 'NOT_PROVEN' },
      gtb: { availability: 'ENVIRONMENT_UNAVAILABLE', claim: 'NOT_PROVEN' },
      approved_vazir_vazirmatn_typography: { availability: 'ENVIRONMENT_UNAVAILABLE', claim: 'NOT_PROVEN' },
    },
    claims: {
      canonical_template_assignment_render: fixtureContractPass ? 'PASS' : 'FAIL',
      rtl_browser_geometry: qualificationPass ? 'PASS' : 'FAIL',
      horizontal_overflow: observations.every((item) => item.qualification.checks.horizontal_overflow_absent) ? 'PASS' : 'FAIL',
      header_footer_integrity: observations.every((item) => item.qualification.checks.header_integrity && item.qualification.checks.footer_integrity) ? 'PASS' : 'FAIL',
      navigation_integrity: observations.every((item) => item.qualification.checks.navigation_integrity) ? 'PASS' : 'FAIL',
      gravity_forms_orbital_gtb: 'NOT_PROVEN',
      approved_frontend_typography: 'NOT_PROVEN',
    },
    overall_wu05_automated_qualification: qualificationPass && fixtureContractPass ? 'PASS' : 'FAIL',
    claim_ceiling: {
      bounded_status: qualificationPass && fixtureContractPass ? 'FULL_WIDTH_GEOMETRY_AUTOMATED_QUALIFIED_ON_PINNED_TARGET_TUPLE' : 'NOT_PROVEN',
      wu06_admin_rtl_accessibility_security: 'NOT_RUN',
      wu07_owner_browser_e2e_comprehension: 'NOT_RUN',
      production_host_qualification: 'NOT_PROVEN',
      production_qualified_for_srwf: 'NOT_PROVEN',
    },
  };

  fs.writeFileSync(evidencePath, `${JSON.stringify(evidence, null, 2)}\n`, 'utf8');
  console.log(JSON.stringify({
    mode,
    viewport_status: viewportStatus,
    overall_wu05_automated_qualification: evidence.overall_wu05_automated_qualification,
  }, null, 2));

  if (mode === 'qualification' && evidence.overall_wu05_automated_qualification !== 'PASS') {
    process.exitCode = 1;
  }
})();
