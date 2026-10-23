#!/usr/bin/env node
// Take sharp screenshots of the running app for an implementation summary.
//
// Usage: SHOT_USER=qa_super SHOT_PASS=... node take_screenshots.mjs shots.json
//
// shots.json:
// {
//   "baseUrl": "http://localhost:8001",          // local QA server, never production
//   "outDir": "docs/implementation-summaries/screenshots/2026-10-23",
//   "width": 1440, "height": 900,                // viewport (optional)
//   "shots": [
//     { "file": "01-reports-default.png",
//       "path": "/reports.php",                  // opened after login
//       "run": "document.getElementById('reportForm').requestSubmit()",   // optional JS (page may reload)
//       "waitMs": 1200,                          // optional, after load / run
//       "scrollTo": "#reportCard",               // optional selector scrolled to the top
//       "clip": "#reportCard",                   // optional selector: capture only that element
//       "fullPage": false }
//   ]
// }
// Needs Google Chrome (macOS path below, or set CHROME). No npm packages.
import { spawn } from 'node:child_process';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { setTimeout as sleep } from 'node:timers/promises';
import { join } from 'node:path';

const cfg = JSON.parse(readFileSync(process.argv[2], 'utf8'));
const chrome = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const user = process.env.SHOT_USER, pass = process.env.SHOT_PASS;
const W = cfg.width || 1440, H = cfg.height || 900, port = 9300 + Math.floor(Math.random() * 500);
mkdirSync(cfg.outDir, { recursive: true });

const proc = spawn(chrome, ['--headless=new', `--remote-debugging-port=${port}`, `--window-size=${W},${H}`,
  '--no-first-run', '--user-data-dir=' + join('/tmp', 'shot-profile-' + port), 'about:blank'], { stdio: 'ignore' });

async function wsUrl() {
  for (let i = 0; i < 50; i++) {
    try { const t = await (await fetch(`http://127.0.0.1:${port}/json`)).json(); const p = t.find(x => x.type === 'page'); if (p) return p.webSocketDebuggerUrl; } catch {}
    await sleep(200);
  }
  throw new Error('Chrome did not start');
}
const ws = new WebSocket(await wsUrl());
await new Promise(r => ws.addEventListener('open', r));
let id = 0; const pending = new Map();
ws.addEventListener('message', e => { const m = JSON.parse(e.data); if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); } });
const send = (method, params = {}) => new Promise((res, rej) => { const i = ++id; pending.set(i, m => m.error ? rej(new Error(method + ': ' + m.error.message)) : res(m.result)); ws.send(JSON.stringify({ id: i, method, params })); });
const evalJs = async (expr) => (await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true })).result?.value;
async function go(url) { await send('Page.navigate', { url }); await sleep(900); for (let i = 0; i < 40; i++) { if (await evalJs('document.readyState') === 'complete') break; await sleep(150); } }

await send('Page.enable'); await send('Runtime.enable');
await send('Emulation.setDeviceMetricsOverride', { width: W, height: H, deviceScaleFactor: 2, mobile: false });

if (user && pass) {
  await go(cfg.baseUrl + '/login.php');
  await evalJs(`(()=>{const f=document.querySelector('form');f.querySelector('[name=username]').value=${JSON.stringify(user)};f.querySelector('[name=password]').value=${JSON.stringify(pass)};f.requestSubmit();})()`);
  await sleep(1500);
}
for (const s of cfg.shots) {
  await go(cfg.baseUrl + s.path);
  if (s.run) { await evalJs(s.run); await sleep(s.waitMs || 1200); for (let i = 0; i < 40; i++) { if (await evalJs('document.readyState') === 'complete') break; await sleep(150); } }
  else if (s.waitMs) await sleep(s.waitMs);
  if (s.scrollTo) await evalJs(`document.querySelector(${JSON.stringify(s.scrollTo)})?.scrollIntoView({block:'start'})`), await sleep(300);
  const shot = { format: 'png' };
  if (s.clip) {
    const r = await evalJs(`(()=>{const e=document.querySelector(${JSON.stringify(s.clip)});if(!e)return null;const b=e.getBoundingClientRect();return {x:b.x+scrollX,y:b.y+scrollY,width:b.width,height:b.height}})()`);
    if (!r) throw new Error('clip selector not found: ' + s.clip);
    shot.clip = { ...r, scale: 1 }; shot.captureBeyondViewport = true;
  } else if (s.fullPage) {
    const r = await evalJs('({w:document.documentElement.scrollWidth,h:document.documentElement.scrollHeight})');
    shot.clip = { x: 0, y: 0, width: r.w, height: r.h, scale: 1 }; shot.captureBeyondViewport = true;
  }
  const { data } = await send('Page.captureScreenshot', shot);
  writeFileSync(join(cfg.outDir, s.file), Buffer.from(data, 'base64'));
  console.log('saved', join(cfg.outDir, s.file));
}
ws.close(); proc.kill();
