/**
 * assets/js/3d/showroom.js
 *
 * Boutique showroom wall: dark stage, spotlights, one rail across the whole width,
 * products hanging in labelled sections. Drag / swipe / scroll sideways: the camera
 * slides along the wall with inertia. Hover or tap swings a piece and turns it to
 * the back; click or double-tap pulls it toward the camera and opens a slim panel.
 * Intro: glass cupboard doors open by themselves (already open for reduced motion).
 *
 * Page independent: `mountShowroom(container, options)` builds its own canvas, panel,
 * section buttons and styles inside `container` (give it a size), so the same module
 * can be dropped into the home page hero later.
 *
 * Performance: ONE renderer; every product is loaded once and all its colour copies
 * share that geometry through InstancedMesh (per-instance colour + matrix), so the
 * whole wall is about 30 draw calls. Renders on demand, pauses off screen / hidden,
 * pixel ratio capped (CONFIG.maxDpr).
 *
 * Sections, products and colours: models.manifest.json "showroom" block.
 * Layout, camera, lighting, intro: SHOWROOM_CONFIG below (manifest "showroom" may
 * override any of these keys). Hanger / cord / swing logic is the same as rack.js.
 */

import * as THREE from 'three';
import * as BufferGeometryUtils from 'three/addons/utils/BufferGeometryUtils.js';
import { createProceduralProduct } from './procedural.js';

/* ============================== CONFIG ============================== */

export const SHOWROOM_CONFIG = {
    maxDpr: 1.5,
    layout: {
        railY: 1.85,          // rail height (m)
        railZ: 0.32,          // rail distance in front of the wall (m)
        hookLength: 0.12,     // garment hanger: rail -> hanger centre (m)
        displayY: 1.33,       // cord items (caps, visors, bags): visual centre height (m)
        pieceGap: 0.14,       // space between pieces (m)
        sectionGap: 0.8,      // extra space between sections (m)
        labelY: 2.27,         // section label height on the wall (m)
        labelHeight: 0.13,    // label text height (m)
        endPadding: 1.0,      // wall beyond the first / last piece (m)
        idleYaw: 0.12         // alternating slight turn, like a real rail (rad)
    },
    camera: {
        fov: 40,
        distance: 3.6,        // from the rail (m)
        height: 1.75,
        lookY: 1.25,
        dragSpeed: 1.0,       // 1 = wall follows the finger exactly
        friction: 4.2,        // inertia decay per second (higher = stops sooner)
        maxSpeed: 9,          // m/s
        wheelSpeed: 0.0022,   // m per wheel delta unit
        wheelVertical: true,  // vertical wheel moves the wall (released at both ends)
        keyStep: 0.9,         // m per arrow key
        presentFill: 0.6      // pulled piece: fraction of the view height
    },
    lighting: {
        background: '#0b0c0f',
        fog: [4.5, 13],
        exposure: 1.05,
        hemi: 0.55,
        key: 1.9,
        fill: 0.35,
        rim: 0.9,
        wall: '#262a31',
        floor: '#2a2b2f',
        spotColor: '#ffe7c2',
        spotSpacing: 1.6,     // one spotlight every n metres
        beamOpacity: 0.07,
        wallPoolOpacity: 0.5,
        floorPoolOpacity: 0.35,
        shadowOpacity: 0.45
    },
    intro: {
        delay: 1000,          // doors open by themselves after this (ms)
        duration: 1500,       // door opening time (ms)
        dolly: 0.5            // camera starts this much further back (m)
    },
    sections: [],             // from the manifest "showroom" block
    colorNames: {}
};

/* ============================ constants ============================= */

const SWING_W = 2 * Math.PI * 0.85;           // same pendulum as rack.js
const SWING_ZETA = 0.16;
const SWING_K = SWING_W * SWING_W;
const SWING_C = 2 * SWING_ZETA * SWING_W;
const TILT_K = SWING_K * 1.35;
const YAW_W = 7;
const YAW_K = YAW_W * YAW_W;
const YAW_C = 2 * YAW_W;
const EPS = 0.0006;
const HARDWARE_RE = /zipper|metal|hardware|eyelet|puller|button|slider|aglet|teeth|cord_end/i;
const HEX_RE = /^#[0-9a-fA-F]{6}$/;
const GLB_PATH_RE = /^assets\/models\/(?:[A-Za-z0-9_-]+\/)?[A-Za-z0-9_-]+\.glb$/;
const GARMENT_CATEGORIES = new Set(['tees_polos', 'hoodies', 'jackets']);
const LOOP_R = 0.022;                          // rail loop radius (m); rail radius 0.016
const LOOP_ARC = Math.PI * 1.35;
const LOOP_START = Math.PI * 1.5 - LOOP_ARC;   // arc ends straight below the rail
const CORD_R = 0.0016;
const TAP_SLOP = 6;                            // px before a press becomes a drag

const num = (v, d) => (typeof v === 'number' && isFinite(v) ? v : d);
const clamp = THREE.MathUtils.clamp;

function mergeDeep(base, extra) {
    if (!extra || typeof extra !== 'object') return base;
    const out = Array.isArray(base) ? base.slice() : { ...base };
    for (const k of Object.keys(extra)) {
        const v = extra[k];
        if (v && typeof v === 'object' && !Array.isArray(v) && base[k] && typeof base[k] === 'object' && !Array.isArray(base[k])) {
            out[k] = mergeDeep(base[k], v);
        } else if (v !== undefined) {
            out[k] = v;
        }
    }
    return out;
}

/* ============================== styles ============================== */

const STYLE_ID = 'welzee-showroom-module-css';
function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const s = document.createElement('style');
    s.id = STYLE_ID;
    s.textContent = `
.srm-root{position:absolute;inset:0;overflow:hidden;outline:none;-webkit-user-select:none;user-select:none;font-family:inherit}
.srm-root:focus-visible{box-shadow:inset 0 0 0 3px rgba(34,197,94,.7)}
.srm-canvas{position:absolute;inset:0;width:100%;height:100%;display:block;touch-action:pan-y;cursor:grab}
.srm-root.is-dragging .srm-canvas{cursor:grabbing}
.srm-root.is-hovering .srm-canvas{cursor:pointer}
.srm-nav{position:absolute;left:12px;right:12px;bottom:12px;display:flex;gap:6px;flex-wrap:wrap;justify-content:center;pointer-events:none;transition:opacity .3s}
.srm-nav button{pointer-events:auto;border:1px solid rgba(255,255,255,.18);background:rgba(17,19,23,.72);color:#e5e7eb;font:600 12px/1 inherit;letter-spacing:.04em;padding:8px 12px;border-radius:999px;cursor:pointer;backdrop-filter:blur(6px)}
.srm-nav button:hover,.srm-nav button.is-current{background:rgba(34,197,94,.22);border-color:rgba(34,197,94,.6);color:#fff}
.srm-nav button:focus-visible{outline:2px solid #22c55e;outline-offset:2px}
.srm-root.is-pulled .srm-nav,.srm-root.is-intro .srm-nav{opacity:0;pointer-events:none}
.srm-root.is-pulled .srm-nav button,.srm-root.is-intro .srm-nav button{pointer-events:none}
.srm-hint{position:absolute;left:50%;top:14px;transform:translateX(-50%);padding:7px 14px;border-radius:999px;background:rgba(17,19,23,.7);color:#d1d5db;font:500 12px/1.2 inherit;pointer-events:none;transition:opacity .5s;opacity:0}
.srm-root.show-hint .srm-hint{opacity:1}
.srm-panel{position:absolute;top:16px;right:16px;bottom:16px;width:300px;max-width:calc(100% - 32px);display:flex;flex-direction:column;justify-content:center;gap:10px;padding:22px 20px;border-radius:18px;background:rgba(14,16,20,.88);border:1px solid rgba(255,255,255,.12);color:#f3f4f6;box-shadow:0 20px 40px rgba(0,0,0,.45);backdrop-filter:blur(10px);transform:translateX(24px);opacity:0;pointer-events:none;transition:opacity .3s,transform .3s}
.srm-root.is-pulled .srm-panel{opacity:1;transform:none;pointer-events:auto}
.srm-kicker{margin:0;font:700 11px/1 inherit;letter-spacing:.12em;text-transform:uppercase;color:#86efac}
.srm-name{margin:0;font:800 22px/1.2 inherit;color:#fff}
.srm-color{margin:0;display:flex;align-items:center;gap:8px;font:500 14px/1.3 inherit;color:#d1d5db}
.srm-swatch{width:18px;height:18px;border-radius:50%;border:2px solid rgba(255,255,255,.7);flex:0 0 auto}
.srm-cta{margin-top:6px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 18px;border-radius:12px;background:#16a34a;color:#fff;font:700 15px/1 inherit;text-decoration:none}
.srm-cta:hover{background:#15803d}
.srm-cta:focus-visible{outline:3px solid #86efac;outline-offset:2px}
.srm-close{position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:50%;border:0;background:rgba(255,255,255,.08);color:#e5e7eb;font-size:20px;line-height:1;cursor:pointer}
.srm-close:hover{background:rgba(255,255,255,.16)}
.srm-close:focus-visible{outline:2px solid #22c55e}
@media (max-width:719px){
 .srm-panel{top:auto;left:12px;right:12px;bottom:12px;width:auto;max-width:none;padding:16px 16px 18px;gap:8px;transform:translateY(24px)}
 .srm-name{font-size:18px}
}
.srm-hud{position:absolute;top:8px;left:8px;font:11px/1.35 ui-monospace,Menlo,Consolas,monospace;color:#a7f3d0;background:rgba(0,0,0,.6);padding:6px 8px;border-radius:6px;pointer-events:none;white-space:pre}
`;
    document.head.appendChild(s);
}

/* ============================ textures ============================== */

function canvasTexture(w, h, draw, opts = {}) {
    const c = document.createElement('canvas');
    c.width = w; c.height = h;
    draw(c.getContext('2d'), w, h);
    const t = new THREE.CanvasTexture(c);
    t.colorSpace = THREE.SRGBColorSpace;
    if (opts.repeat) { t.wrapS = THREE.RepeatWrapping; t.wrapT = THREE.RepeatWrapping; }
    if (opts.noMips) { t.generateMipmaps = false; t.minFilter = THREE.LinearFilter; }
    return t;
}
const radialTexture = () => canvasTexture(128, 128, (x, w) => {
    const g = x.createRadialGradient(w / 2, w / 2, 0, w / 2, w / 2, w / 2);
    g.addColorStop(0, 'rgba(255,255,255,1)');
    g.addColorStop(0.45, 'rgba(255,255,255,0.45)');
    g.addColorStop(1, 'rgba(255,255,255,0)');
    x.fillStyle = g; x.fillRect(0, 0, w, w);
});
const wallTexture = () => canvasTexture(512, 256, (x, w, h) => {
    x.fillStyle = '#ffffff'; x.fillRect(0, 0, w, h);
    for (let i = 0; i < 2600; i++) {               // fine plaster grain
        x.fillStyle = `rgba(0,0,0,${Math.random() * 0.05})`;
        x.fillRect(Math.random() * w, Math.random() * h, 1.5, 1.5);
    }
    for (const sx of [0, w / 2]) {                 // two panels per tile: shadow + highlight seam
        x.fillStyle = 'rgba(0,0,0,0.35)'; x.fillRect(sx, 0, 3, h);
        x.fillStyle = 'rgba(255,255,255,0.25)'; x.fillRect(sx + 3, 0, 1, h);
    }
}, { repeat: true });
const floorTexture = () => canvasTexture(512, 512, (x, w, h) => {
    const rows = 8, rh = h / rows;
    for (let r = 0; r < rows; r++) {               // planks along the wall
        const off = (r % 2) * w * 0.37;
        for (let k = -1; k < 3; k++) {
            const v = 150 + Math.floor(Math.random() * 60);
            x.fillStyle = `rgb(${v},${v - 6},${v - 14})`;
            x.fillRect(off + k * w * 0.62, r * rh, w * 0.62 - 2, rh - 2);
        }
        x.fillStyle = 'rgba(0,0,0,0.5)'; x.fillRect(0, r * rh + rh - 2, w, 2);
    }
}, { repeat: true });
const sheenTexture = () => canvasTexture(256, 256, (x, w, h) => {
    const g = x.createLinearGradient(0, h, w, 0);
    g.addColorStop(0.0, 'rgba(255,255,255,0)');
    g.addColorStop(0.42, 'rgba(255,255,255,0)');
    g.addColorStop(0.5, 'rgba(255,255,255,0.9)');
    g.addColorStop(0.56, 'rgba(255,255,255,0)');
    g.addColorStop(0.66, 'rgba(255,255,255,0.35)');
    g.addColorStop(0.7, 'rgba(255,255,255,0)');
    x.fillStyle = g; x.fillRect(0, 0, w, h);
});

/* ============================ geometry ============================== */

function placed(geo, m) { geo.applyMatrix4(m); return geo; }
const mat4 = () => new THREE.Matrix4();

/** Rail loop (arc around the rail, ends straight below it), at the origin. */
function loopGeometry(tube) {
    const g = new THREE.TorusGeometry(LOOP_R, tube, 8, 24, LOOP_ARC);
    return placed(g, mat4().makeRotationFromEuler(new THREE.Euler(0, Math.PI / 2, LOOP_START)));
}

/** Garment hook in spin space: rail loop + wire neck down to the hanger centre + nut. */
function hookGeometry(hookLength) {
    const wire = 0.0035;
    const neckLen = hookLength - LOOP_R;
    const neck = placed(new THREE.CylinderGeometry(wire, wire, neckLen, 8), mat4().makeTranslation(0, -LOOP_R - neckLen / 2, 0));
    const nut = placed(new THREE.CylinderGeometry(0.006, 0.006, 0.012, 10), mat4().makeTranslation(0, -hookLength + 0.004, 0));
    return BufferGeometryUtils.mergeGeometries([loopGeometry(wire), neck, nut]);
}

/** Wooden hanger following the measured shoulder line (model base frame), like rack.js. */
function hangerGeometry(rack) {
    const parts = [];
    const z = num(rack.hangerZ, 0);
    const prof = rack.hangerProfile;
    const H = 0.022, T = 0.011;
    for (const sgn of [1, -1]) {
        for (let i = 0; i + 1 < prof.length; i++) {
            const ax = sgn * prof[i][0], ay = prof[i][1], bx = sgn * prof[i + 1][0], by = prof[i + 1][1];
            const len = Math.hypot(bx - ax, by - ay);
            if (len < 1e-4) continue;
            const m = mat4().makeRotationZ(Math.atan2(by - ay, bx - ax)).setPosition((ax + bx) / 2, (ay + by) / 2 - H / 2, z);
            parts.push(placed(new THREE.BoxGeometry(len, H, T), m));
        }
        for (let i = 1; i < prof.length; i++) {
            const m = mat4().makeRotationX(Math.PI / 2).setPosition(sgn * prof[i][0], prof[i][1] - H / 2, z);
            parts.push(placed(new THREE.CylinderGeometry(H / 2, H / 2, T, 12), m));
        }
    }
    return BufferGeometryUtils.mergeGeometries(parts);
}

/** Ring (cap), spring clip (visor) or J-hook (bag) at the attach point, like rack.js. */
function clipGeometry(kind) {
    if (kind === 'clip') {
        return BufferGeometryUtils.mergeGeometries([
            placed(new THREE.BoxGeometry(0.014, 0.018, 0.0025), mat4().makeTranslation(0, -0.004, 0.004)),
            placed(new THREE.BoxGeometry(0.014, 0.018, 0.0025), mat4().makeTranslation(0, -0.004, -0.004)),
            placed(new THREE.CylinderGeometry(0.004, 0.004, 0.012, 10), mat4().makeRotationX(Math.PI / 2).setPosition(0, 0.007, 0))
        ]);
    }
    if (kind === 'hook') {
        return BufferGeometryUtils.mergeGeometries([
            placed(new THREE.TorusGeometry(0.012, 0.0028, 8, 18, Math.PI), mat4().makeRotationZ(Math.PI).setPosition(0.012, 0.006, 0)),
            placed(new THREE.CylinderGeometry(0.0028, 0.0028, 0.024, 8), mat4().makeTranslation(0, 0.018, 0))
        ]);
    }
    return placed(new THREE.TorusGeometry(0.008, 0.0018, 8, 24), mat4().makeTranslation(0, 0.006, 0));
}

/* ============================ the module ============================ */

/**
 * @param {HTMLElement} container  sized element; the showroom fills it
 * @param {object} options  { manifestUrl, base, reducedMotion, debug, config,
 *        customizeUrl(productId, hex), onFirstFrame(), onProductsShown(), onModelError(id, err) }
 * @returns controller { destroy(), goToSection(id), openDoors() }
 */
export async function mountShowroom(container, options = {}) {
    if (!container) throw new Error('mountShowroom: container missing');
    const base = options.base || '';
    const reduced = !!options.reducedMotion;
    const debug = !!options.debug;
    const customizeUrl = typeof options.customizeUrl === 'function'
        ? options.customizeUrl
        : (id, hex) => `${base}custom_order_3d.php?product=${encodeURIComponent(id)}&color=${encodeURIComponent(hex)}`;

    // ---------- manifest + config ----------
    let manifest = { products: {} };
    if (options.manifestUrl) {
        const resp = await fetch(options.manifestUrl, { credentials: 'same-origin', cache: 'no-cache' });
        if (!resp.ok) throw new Error(`manifest HTTP ${resp.status}`);
        manifest = await resp.json();
    }
    const products = manifest.products || {};
    const version = String(manifest.version || '1');
    const cfg = mergeDeep(mergeDeep(SHOWROOM_CONFIG, manifest.showroom || {}), options.config || {});
    if (!Array.isArray(cfg.sections) || !cfg.sections.length) {          // fallback: one of everything
        cfg.sections = [{ id: 'all', label: 'Collection', items: Object.keys(products).map((p) => ({ product: p, colors: ['#f4f4f2'] })) }];
    }
    const L = cfg.layout, C = cfg.camera, LT = cfg.lighting;

    // ---------- DOM ----------
    injectStyles();
    if (getComputedStyle(container).position === 'static') container.style.position = 'relative';
    const root = document.createElement('div');
    root.className = 'srm-root is-intro';
    root.tabIndex = 0;
    root.setAttribute('role', 'region');
    root.setAttribute('aria-label', 'Showroom wall. Drag or use the arrow keys to move along the wall, Enter to pick the piece in the middle.');
    const canvas = document.createElement('canvas');
    canvas.className = 'srm-canvas';
    canvas.setAttribute('aria-hidden', 'true');
    root.appendChild(canvas);

    const hint = document.createElement('div');
    hint.className = 'srm-hint';
    hint.textContent = 'Drag to explore the wall';
    root.appendChild(hint);

    const nav = document.createElement('div');
    nav.className = 'srm-nav';
    root.appendChild(nav);

    const panel = document.createElement('div');
    panel.className = 'srm-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Selected piece');
    panel.innerHTML = '<button type="button" class="srm-close" aria-label="Put it back">&times;</button>' +
        '<p class="srm-kicker"></p><h3 class="srm-name"></h3>' +
        '<p class="srm-color"><span class="srm-swatch"></span><span class="srm-color-name"></span></p>' +
        '<a class="srm-cta" href="#">Customise <span aria-hidden="true">&rarr;</span></a>';
    root.appendChild(panel);
    panel.inert = true;                   // not focusable while closed
    panel.setAttribute('aria-hidden', 'true');
    let hud = null;
    if (debug) { hud = document.createElement('div'); hud.className = 'srm-hud'; root.appendChild(hud); }
    container.appendChild(root);

    // ---------- renderer / scene / camera ----------
    const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, cfg.maxDpr));
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = LT.exposure;
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    const scene = new THREE.Scene();
    scene.background = new THREE.Color(LT.background);
    scene.fog = new THREE.Fog(LT.background, LT.fog[0], LT.fog[1]);
    const camera = new THREE.PerspectiveCamera(C.fov, 1, 0.05, 40);

    scene.add(new THREE.HemisphereLight(0x9aa6b8, 0x0b0c0e, LT.hemi));
    const key = new THREE.DirectionalLight(0xfff4e5, LT.key); key.position.set(1.5, 4, 3); scene.add(key);
    const fill = new THREE.DirectionalLight(0xdbe7ff, LT.fill); fill.position.set(-3, 1.5, 2); scene.add(fill);
    const rim = new THREE.DirectionalLight(0xffffff, LT.rim); rim.position.set(0, 3, -2); scene.add(rim);

    const wireMat = new THREE.MeshStandardMaterial({ color: 0x9ca3af, metalness: 0.85, roughness: 0.3 });
    const woodMat = new THREE.MeshStandardMaterial({ color: 0x8a5a35, metalness: 0, roughness: 0.5 });
    const darkMat = new THREE.MeshStandardMaterial({ color: 0x2b2f36, metalness: 0.55, roughness: 0.4 });
    const cordMat = new THREE.MeshStandardMaterial({ color: 0x8b7b69, metalness: 0, roughness: 0.9 });
    const radial = radialTexture();
    const disposables = [wireMat, woodMat, darkMat, cordMat, radial];

    // ---------- layout from the manifest (no model needed) ----------
    const groups = new Map();     // productId -> group
    const pieces = [];
    const sectionsOut = [];
    let x = 0;
    cfg.sections.forEach((sec, si) => {
        if (si > 0) x += L.sectionGap;
        const startX = x;
        (sec.items || []).forEach((item) => {
            const id = item.product;
            const conf = products[id];
            if (!conf) return;
            let g = groups.get(id);
            if (!g) {
                const rack = Object.assign({}, conf.rack || {});
                const type = rack.type || (GARMENT_CATEGORIES.has(conf.category) ? 'hanger' : 'cord');
                rack.frontSize = Array.isArray(rack.frontSize) ? rack.frontSize : [num(conf.targetHeight, 0.6), num(conf.targetHeight, 0.6)];
                if (type === 'hanger' && !(Array.isArray(rack.hangerProfile) && rack.hangerProfile.length > 1)) {
                    const [w, h] = rack.frontSize;
                    rack.hangerProfile = [[0, h * 0.93], [w * 0.12, h * 0.92], [w * 0.33, h * 0.87]];
                }
                if (type === 'cord') {
                    rack.attach = Array.isArray(rack.attach) ? rack.attach : [0, rack.frontSize[1], 0];
                    rack.centerY = num(rack.centerY, rack.frontSize[1] / 2);
                }
                g = { id, conf, rack, type, pieces: [], parts: [], hanger: null, ready: false, section: sec.label };
                groups.set(id, g);
            }
            const w = g.rack.frontSize[0];
            (item.colors || []).forEach((hex) => {
                if (!HEX_RE.test(hex)) return;
                x += w / 2;
                const p = {
                    idx: pieces.length, group: g, hex: hex.toLowerCase(),
                    colorName: (cfg.colorNames && cfg.colorNames[hex.toLowerCase()]) || hex.toUpperCase(),
                    x, rest: new THREE.Vector3(x, L.railY, L.railZ), pos: new THREE.Vector3(x, L.railY, L.railZ),
                    idleYaw: (pieces.length % 2 ? -1 : 1) * L.idleYaw,
                    swing: { a: 0, v: 0 }, tilt: { a: 0, v: 0 }, yaw: { a: 0, v: 0, target: 0 },
                    pull: 0, pullTarget: 0, ready: false, box: new THREE.Box3(), restBox: new THREE.Box3()
                };
                p.yaw.a = p.yaw.target = p.idleYaw;
                g.pieces.push(p);
                pieces.push(p);
                x += w / 2 + L.pieceGap;
            });
        });
        const endX = x - L.pieceGap;
        if (endX > startX) sectionsOut.push({ id: sec.id || `s${si}`, label: sec.label || '', startX, endX, centerX: (startX + endX) / 2 });
    });
    if (!pieces.length) throw new Error('showroom: no products to show');
    const wallStart = pieces[0].x - pieces[0].group.rack.frontSize[0] / 2 - L.endPadding;
    const lastPiece = pieces[pieces.length - 1];
    const wallEnd = lastPiece.x + lastPiece.group.rack.frontSize[0] / 2 + L.endPadding;
    const wallW = wallEnd - wallStart + 14;            // extra so the edges stay in the fog
    const wallMid = (wallStart + wallEnd) / 2;

    // Per-group indices into shared meshes; vertical geometry per piece
    let nGarment = 0, nCord = 0;
    const clipCount = { ring: 0, clip: 0, hook: 0 };
    for (const p of pieces) {
        const g = p.group, r = g.rack;
        p.local = g.pieces.indexOf(p);
        if (g.type === 'hanger') {
            p.gIdx = nGarment++;
            const A = r.hangerProfile[0];
            g.holder = mat4().makeTranslation(-A[0], -L.hookLength - A[1], -num(r.hangerZ, 0));
            p.centreBelow = L.hookLength + A[1] - r.frontSize[1] / 2;
        } else {
            p.cIdx = nCord++;
            g.clip = ['ring', 'clip', 'hook'].includes(r.clip) ? r.clip : 'ring';
            p.kIdx = clipCount[g.clip]++;
            const above = r.attach[1] - r.centerY;
            p.cordLen = Math.max(0.06, L.railY - L.displayY - above);
            p.centreBelow = p.cordLen + above;
            g.tiltM = mat4().makeRotationX(THREE.MathUtils.degToRad(num(r.level, 0) + num(r.tilt, 0)));
            g.holder = mat4().makeTranslation(-r.attach[0], -r.attach[1], -r.attach[2]);
        }
        const [w, h] = r.frontSize;
        const cy = L.railY - p.centreBelow;
        p.restBox.set(new THREE.Vector3(p.x - w / 2, cy - h / 2, L.railZ - 0.22), new THREE.Vector3(p.x + w / 2, cy + h / 2, L.railZ + 0.22));
        p.box.copy(p.restBox);
    }

    // ---------- shared instanced meshes ----------
    function instanced(geo, mat, count) {
        const m = new THREE.InstancedMesh(geo, mat, Math.max(1, count));
        m.count = count;
        m.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
        const zero = new THREE.Matrix4().makeScale(0, 0, 0);
        for (let i = 0; i < count; i++) m.setMatrixAt(i, zero);   // hidden until the product is ready
        scene.add(m);
        disposables.push(geo);
        return m;
    }
    const hookMesh = instanced(hookGeometry(L.hookLength), wireMat, nGarment);
    const loopMesh = instanced(loopGeometry(0.003), wireMat, nCord);
    const cordMesh = instanced(new THREE.CylinderGeometry(1, 1, 1, 8), cordMat, nCord);
    const clipMeshes = {};
    for (const k of Object.keys(clipCount)) if (clipCount[k]) clipMeshes[k] = instanced(clipGeometry(k), k === 'clip' ? darkMat : wireMat, clipCount[k]);
    const shadowMat = new THREE.MeshBasicMaterial({ color: 0x000000, map: radial, transparent: true, opacity: LT.shadowOpacity, depthWrite: false });
    disposables.push(shadowMat);
    const shadowMesh = instanced(new THREE.PlaneGeometry(1, 1), shadowMat, pieces.length);
    shadowMesh.renderOrder = -1;

    // ---------- stage: wall, floor, rail, labels, spotlights ----------
    const wallTex = wallTexture();
    wallTex.repeat.set(wallW / 1.2, 1);
    const wall = new THREE.Mesh(new THREE.PlaneGeometry(wallW, 4.6), new THREE.MeshStandardMaterial({ color: LT.wall, map: wallTex, roughness: 0.95 }));
    wall.position.set(wallMid, 2.3, 0);
    scene.add(wall);
    const floorTex = floorTexture();
    floorTex.repeat.set(wallW / 2.4, 6 / 1.2);
    const floor = new THREE.Mesh(new THREE.PlaneGeometry(wallW, 6), new THREE.MeshStandardMaterial({ color: LT.floor, map: floorTex, roughness: 0.62, metalness: 0.05 }));
    floor.rotation.x = -Math.PI / 2;
    floor.position.set(wallMid, 0, 3);
    scene.add(floor);
    disposables.push(wall.geometry, wall.material, wallTex, floor.geometry, floor.material, floorTex);

    // Rail across the whole wall + brackets every ~1.8 m (one merged mesh)
    {
        const railLen = wallEnd - wallStart;
        const parts = [placed(new THREE.CylinderGeometry(0.016, 0.016, railLen, 16), mat4().makeRotationZ(Math.PI / 2).setPosition(wallMid, L.railY, L.railZ))];
        const n = Math.max(2, Math.round(railLen / 1.8) + 1);
        for (let i = 0; i < n; i++) {
            const bx = wallStart + 0.2 + (railLen - 0.4) * (i / (n - 1));
            parts.push(placed(new THREE.BoxGeometry(0.03, 0.03, L.railZ), mat4().makeTranslation(bx, L.railY, L.railZ / 2)));
            parts.push(placed(new THREE.CylinderGeometry(0.035, 0.035, 0.012, 16), mat4().makeRotationX(Math.PI / 2).setPosition(bx, L.railY, 0.006)));
        }
        const rail = new THREE.Mesh(BufferGeometryUtils.mergeGeometries(parts), wireMat);
        scene.add(rail);
        disposables.push(rail.geometry);
    }

    // Section labels: one canvas atlas, one merged mesh
    {
        const rowH = 96, W = 1024;
        const tex = canvasTexture(W, rowH * sectionsOut.length, (x2) => {
            x2.textBaseline = 'middle';
            x2.font = '700 46px Inter, "Segoe UI", system-ui, sans-serif';
            if ('letterSpacing' in x2) x2.letterSpacing = '8px';
            sectionsOut.forEach((s, i) => {
                const t = s.label.toUpperCase();
                const tw = Math.min(W - 40, x2.measureText(t).width);
                s.texW = tw + 40;
                x2.fillStyle = '#e5e7eb';
                x2.fillText(t, 20, i * rowH + rowH * 0.42, W - 40);
                x2.fillStyle = '#22c55e';
                x2.fillRect(20, i * rowH + rowH * 0.8, Math.min(90, tw), 4);
            });
        }, { noMips: true });
        const geos = sectionsOut.map((s, i) => {
            const h = L.labelHeight * (rowH / 46);
            const w = h * (s.texW / rowH);
            const g2 = new THREE.PlaneGeometry(w, h);
            const uv = g2.attributes.uv;
            const u1 = s.texW / W, v0 = 1 - (i + 1) / sectionsOut.length, v1 = 1 - i / sectionsOut.length;
            for (let k = 0; k < uv.count; k++) uv.setXY(k, uv.getX(k) * u1, v0 + uv.getY(k) * (v1 - v0));
            return placed(g2, mat4().makeTranslation(s.centerX, L.labelY, 0.01));
        });
        const labels = new THREE.Mesh(BufferGeometryUtils.mergeGeometries(geos), new THREE.MeshBasicMaterial({ map: tex, transparent: true, toneMapped: false }));
        scene.add(labels);
        disposables.push(labels.geometry, labels.material, tex);
    }

    // Spotlights (fake): beams, pools on the wall and on the floor (3 instanced meshes)
    const beamMat = new THREE.MeshBasicMaterial({ color: LT.spotColor, transparent: true, opacity: 0, vertexColors: true, blending: THREE.AdditiveBlending, depthWrite: false, side: THREE.DoubleSide });
    const wallPoolMat = new THREE.MeshBasicMaterial({ color: LT.spotColor, map: radial, transparent: true, opacity: 0, blending: THREE.AdditiveBlending, depthWrite: false });
    const floorPoolMat = wallPoolMat.clone();
    disposables.push(beamMat, wallPoolMat, floorPoolMat);
    {
        const spots = [];
        for (const s of sectionsOut) {
            const n = Math.max(1, Math.round((s.endX - s.startX) / LT.spotSpacing));
            for (let i = 0; i < n; i++) spots.push(s.startX + (s.endX - s.startX) * ((i + 0.5) / n));
        }
        const beamGeo = new THREE.ConeGeometry(1, 1, 24, 1, true);
        beamGeo.translate(0, -0.5, 0);                              // apex at the origin, base at y = -1
        const cols = new Float32Array(beamGeo.attributes.position.count * 4);
        for (let i = 0; i < beamGeo.attributes.position.count; i++) {
            const y = beamGeo.attributes.position.getY(i);           // 0 (lamp) .. -1 (wall)
            cols.set([1, 1, 1, 0.9 * (1 + y) + 0.05], i * 4);
        }
        beamGeo.setAttribute('color', new THREE.BufferAttribute(cols, 4));
        const beams = instanced(beamGeo, beamMat, spots.length);
        const wallPools = instanced(new THREE.PlaneGeometry(1, 1), wallPoolMat, spots.length);
        const floorPools = instanced(new THREE.PlaneGeometry(1, 1), floorPoolMat, spots.length);
        const m = mat4(), q = new THREE.Quaternion(), down = new THREE.Vector3(0, -1, 0);
        spots.forEach((sx, i) => {
            const from = new THREE.Vector3(sx, 3.1, 1.3), to = new THREE.Vector3(sx, 1.4, 0.02);
            const dir = to.clone().sub(from); const len = dir.length();
            q.setFromUnitVectors(down, dir.normalize());
            beams.setMatrixAt(i, m.compose(from, q, new THREE.Vector3(0.62, len, 0.62)));
            wallPools.setMatrixAt(i, m.compose(new THREE.Vector3(sx, 1.42, 0.004), new THREE.Quaternion(), new THREE.Vector3(1.9, 2.4, 1)));
            q.setFromAxisAngle(new THREE.Vector3(1, 0, 0), -Math.PI / 2);
            floorPools.setMatrixAt(i, m.compose(new THREE.Vector3(sx, 0.003, 0.45), q, new THREE.Vector3(1.6, 0.9, 1)));
        });
        for (const mm of [beams, wallPools, floorPools]) { mm.instanceMatrix.needsUpdate = true; mm.computeBoundingSphere(); }
    }
    let lightsT = reduced ? 1 : 0;
    function applyLightFade() {
        const k = THREE.MathUtils.smootherstep(lightsT, 0, 1);
        beamMat.opacity = LT.beamOpacity * k;
        wallPoolMat.opacity = LT.wallPoolOpacity * (0.25 + 0.75 * k);
        floorPoolMat.opacity = LT.floorPoolOpacity * (0.25 + 0.75 * k);
    }
    applyLightFade();

    // ---------- glass cupboard doors (intro) ----------
    const glassMat = new THREE.MeshBasicMaterial({ color: 0xa9cfe0, transparent: true, opacity: 0.16, depthWrite: false, side: THREE.DoubleSide });
    const edgeMat = new THREE.MeshBasicMaterial({ color: 0xe6f6ff, transparent: true, opacity: 0.8, depthWrite: false });
    const sheenMat = new THREE.MeshBasicMaterial({ map: sheenTexture(), transparent: true, opacity: 0.32, blending: THREE.AdditiveBlending, depthWrite: false, side: THREE.DoubleSide });
    disposables.push(glassMat, edgeMat, sheenMat, sheenMat.map);
    const doorZ = L.railZ + 0.5;
    const doorY0 = 0.05, doorY1 = 2.6;
    const doors = [];
    for (const side of [-1, 1]) {
        const hinge = new THREE.Group();
        const panelG = new THREE.Group();
        hinge.add(panelG);
        const glass = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), glassMat);
        const sheen = new THREE.Mesh(new THREE.PlaneGeometry(1, 1), sheenMat);
        sheen.position.z = 0.002;
        const edge = new THREE.Mesh(new THREE.BufferGeometry(), edgeMat);
        panelG.add(glass, sheen, edge);
        scene.add(hinge);
        doors.push({ side, hinge, panelG, glass, sheen, edge });
        disposables.push(glass.geometry, sheen.geometry);
    }
    let doorsT = reduced ? 1 : 0, doorsOpening = reduced, doorsDone = reduced;
    function layoutDoors() {
        if (doorsDone) return;
        // Cover the whole view at the farthest intro camera position
        const camZ = L.railZ + C.distance + (reduced ? 0 : cfg.intro.dolly);
        const halfH = Math.tan(THREE.MathUtils.degToRad(C.fov / 2)) * (camZ - doorZ);
        const halfW = halfH * camera.aspect + 0.2;
        const h = doorY1 - doorY0;
        for (const d of doors) {
            d.hinge.position.set(camX + d.side * halfW, (doorY0 + doorY1) / 2, doorZ);
            d.panelG.position.x = -d.side * halfW / 2;
            d.glass.scale.set(halfW, h, 1);
            d.sheen.scale.set(halfW * 0.98, h * 0.98, 1);
            const t = 0.012, w = halfW;
            d.edge.geometry.dispose();
            d.edge.geometry = BufferGeometryUtils.mergeGeometries([
                placed(new THREE.BoxGeometry(w, t, t), mat4().makeTranslation(0, h / 2, 0)),
                placed(new THREE.BoxGeometry(w, t, t), mat4().makeTranslation(0, -h / 2, 0)),
                placed(new THREE.BoxGeometry(t, h, t), mat4().makeTranslation(w / 2, 0, 0)),
                placed(new THREE.BoxGeometry(t, h, t), mat4().makeTranslation(-w / 2, 0, 0)),
                // brushed handle near the meeting edge
                placed(new THREE.BoxGeometry(0.014, 0.34, 0.03), mat4().makeTranslation(d.side * (w / 2 - 0.09), -0.05, 0.02))
            ]);
        }
        applyDoors();
    }
    function applyDoors() {
        const k = THREE.MathUtils.smootherstep(doorsT, 0, 1);
        for (const d of doors) {
            d.hinge.rotation.y = d.side * 1.95 * k;    // both doors swing toward the camera (left -, right +)
            const fade = 1 - THREE.MathUtils.smoothstep(doorsT, 0.7, 1);
            d.hinge.visible = !doorsDone && fade > 0.01;
        }
        glassMat.opacity = 0.16 * (1 - THREE.MathUtils.smoothstep(doorsT, 0.7, 1));
        edgeMat.opacity = 0.8 * (1 - THREE.MathUtils.smoothstep(doorsT, 0.7, 1));
        sheenMat.opacity = 0.32 * (1 - THREE.MathUtils.smoothstep(doorsT, 0.5, 1));
    }
    function openDoors() {
        if (doorsOpening || doorsDone) return;
        doorsOpening = true;
        wake();
    }

    // ---------- camera motion ----------
    let camX = 0, camVel = 0, camGoal = null, dolly = reduced ? 0 : cfg.intro.dolly;
    let minX = wallStart, maxX = wallEnd;
    const _dir = new THREE.Vector3(), _right = new THREE.Vector3(), _up = new THREE.Vector3();
    function halfWidthAt(d) { return Math.tan(THREE.MathUtils.degToRad(C.fov / 2)) * d * camera.aspect; }
    function viewHalfWidthAtWall() { return halfWidthAt(L.railZ + C.distance); }
    function updateBounds() {
        const hw = viewHalfWidthAtWall();
        minX = wallStart + L.endPadding * 0.4 + hw * 0.85;
        maxX = wallEnd - L.endPadding * 0.4 - hw * 0.85;
        if (minX > maxX) minX = maxX = (wallStart + wallEnd) / 2;
    }
    function placeCamera() {
        camera.position.set(camX, C.height, L.railZ + C.distance + dolly);
        camera.lookAt(camX, C.lookY, 0);
        camera.updateMatrixWorld();
    }

    // ---------- piece matrices ----------
    const M = { P: mat4(), S: mat4(), A: mat4(), B: mat4(), T: mat4(), O: mat4() };
    const _e = new THREE.Euler();
    const _v = new THREE.Vector3(), _q = new THREE.Quaternion(), _s = new THREE.Vector3();
    const dirtyMeshes = new Set();
    function writePiece(p) {
        if (!p.ready) return;
        const g = p.group;
        _e.set(p.tilt.a, 0, p.swing.a, 'XYZ');
        M.P.makeRotationFromEuler(_e).setPosition(p.pos.x, p.pos.y, p.pos.z);
        M.S.makeRotationY(p.yaw.a);
        if (g.type === 'hanger') {
            M.A.multiplyMatrices(M.P, M.S);
            hookMesh.setMatrixAt(p.gIdx, M.A); dirtyMeshes.add(hookMesh);
            M.B.multiplyMatrices(M.A, g.holder);
            g.hanger.setMatrixAt(p.local, M.B); dirtyMeshes.add(g.hanger);
        } else {
            loopMesh.setMatrixAt(p.cIdx, M.P); dirtyMeshes.add(loopMesh);
            const len = Math.max(1e-3, p.cordLen - LOOP_R);
            M.T.compose(_v.set(0, -(p.cordLen + LOOP_R) / 2, 0), _q.identity(), _s.set(CORD_R, len, CORD_R));
            M.A.multiplyMatrices(M.P, M.T);
            cordMesh.setMatrixAt(p.cIdx, M.A); dirtyMeshes.add(cordMesh);
            M.T.makeTranslation(0, -p.cordLen, 0);
            M.A.multiplyMatrices(M.P, M.T).multiply(g.tiltM).multiply(M.S);
            clipMeshes[g.clip].setMatrixAt(p.kIdx, M.A); dirtyMeshes.add(clipMeshes[g.clip]);
            M.B.multiplyMatrices(M.A, g.holder);
        }
        for (const part of g.parts) {
            M.O.multiplyMatrices(M.B, part.local);
            part.mesh.setMatrixAt(p.local, M.O);
            dirtyMeshes.add(part.mesh);
        }
        // Soft shadow on the wall at the rest position (fades when pulled forward)
        const [w, h] = g.rack.frontSize;
        const k = 1 - Math.min(1, p.pull * 2);
        M.T.compose(_v.set(p.rest.x + 0.03, L.railY - p.centreBelow - 0.04, 0.005), _q.identity(), _s.set(w * 1.15 * k, h * 1.12 * k, 1));
        shadowMesh.setMatrixAt(p.idx, M.T); dirtyMeshes.add(shadowMesh);
        // Pick box follows the pivot
        p.box.copy(p.restBox).translate(_v.subVectors(p.pos, p.rest));
    }
    function flushMatrices(recomputeSpheres) {
        for (const m of dirtyMeshes) {
            m.instanceMatrix.needsUpdate = true;
            if (recomputeSpheres) m.computeBoundingSphere();
        }
        dirtyMeshes.clear();
    }

    // ---------- model loading (once per product; nearest sections first) ----------
    let dracoLoader = null;
    async function readGlbExtensions(url) {
        try {
            const head = await fetch(url, { headers: { Range: 'bytes=0-19' } });
            if (head.status !== 206 && head.status !== 200) return [];
            const view = new DataView(await head.arrayBuffer());
            if (view.byteLength < 20 || view.getUint32(0, true) !== 0x46546C67) return [];
            const len = view.getUint32(12, true);
            if (view.getUint32(16, true) !== 0x4E4F534A || len <= 0) return [];
            const json = await fetch(url, { headers: { Range: `bytes=20-${20 + len - 1}` } });
            if (json.status !== 206 && json.status !== 200) return [];
            const parsed = JSON.parse(new TextDecoder('utf-8').decode(await json.arrayBuffer()));
            return parsed.extensionsUsed || parsed.extensionsRequired || [];
        } catch (e) {
            return [];
        }
    }
    async function loadGLB(path) {
        const url = base + path + (path.includes('?') ? '&' : '?') + 'v=' + encodeURIComponent(version);
        const exts = await readGlbExtensions(url);
        const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
        const loader = new GLTFLoader();
        if (exts.includes('KHR_draco_mesh_compression')) {
            if (!dracoLoader) {
                const { DRACOLoader } = await import('three/addons/loaders/DRACOLoader.js');
                dracoLoader = new DRACOLoader();
                dracoLoader.setDecoderPath(base + 'assets/js/3d/lib/draco/gltf/');
            }
            loader.setDRACOLoader(dracoLoader);
        }
        if (exts.includes('EXT_meshopt_compression')) {
            const { MeshoptDecoder } = await import('./lib/meshopt/meshopt_decoder.module.js');
            loader.setMeshoptDecoder(MeshoptDecoder);
        }
        if (exts.includes('KHR_texture_basisu')) {
            const { KTX2Loader } = await import('three/addons/loaders/KTX2Loader.js');
            const ktx2 = new KTX2Loader();
            ktx2.setTranscoderPath(base + 'assets/js/3d/lib/basis/');
            ktx2.detectSupport(renderer);
            loader.setKTX2Loader(ktx2);
        }
        const gltf = await loader.loadAsync(url);
        return gltf.scene || gltf.scenes[0];
    }
    function toBaseFrame(model, conf) {                       // same as rack.js
        if (typeof conf.frontRotation === 'number' && conf.frontRotation !== 0) model.rotation.y += (conf.frontRotation * Math.PI) / 180;
        model.updateMatrixWorld(true);
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        if (size.y > 1e-6) model.scale.multiplyScalar(num(conf.targetHeight, 0.7) / size.y);
        model.updateMatrixWorld(true);
        box.setFromObject(model);
        const c = box.getCenter(new THREE.Vector3());
        model.position.x -= c.x; model.position.z -= c.z; model.position.y -= box.min.y;
        model.updateMatrixWorld(true);
    }
    function gatherHandles(model, top) {                      // same as rack.js
        const y0 = top * 0.64, v = new THREE.Vector3();
        model.traverse((child) => {
            if (!child.isMesh || !/handle/i.test(child.material?.name || '')) return;
            child.geometry = child.geometry.clone();
            const m = child.matrixWorld, inv = m.clone().invert();
            const pos = child.geometry.attributes.position;
            for (let i = 0; i < pos.count; i++) {
                v.fromBufferAttribute(pos, i).applyMatrix4(m);
                v.z *= 1 - 0.85 * THREE.MathUtils.smoothstep(v.y, y0, top);
                v.applyMatrix4(inv);
                pos.setXYZ(i, v.x, v.y, v.z);
            }
            pos.needsUpdate = true;
            child.geometry.computeVertexNormals();
            child.geometry.computeBoundingBox();
            child.geometry.computeBoundingSphere();
        });
    }

    let productsShown = false, destroyed = false;
    async function loadGroup(g) {
        let model = null;
        const path = typeof g.conf.glb === 'string' && GLB_PATH_RE.test(g.conf.glb) ? g.conf.glb : '';
        if (path) {
            try { model = await loadGLB(path); } catch (err) {
                if (options.onModelError) options.onModelError(g.id, err);
                console.warn(`[Welzee Showroom] ${g.id}: model unavailable, using placeholder.`, err);
            }
        }
        if (destroyed) return;
        if (!model) model = createProceduralProduct(g.id, '#ffffff');
        toBaseFrame(model, g.conf);
        if (g.type === 'cord' && g.rack.gatherHandles) gatherHandles(model, num(g.conf.targetHeight, 0.5));
        model.updateMatrixWorld(true);

        const fixed = (g.conf.fixedMaterialColors && typeof g.conf.fixedMaterialColors === 'object') ? g.conf.fixedMaterialColors : {};
        const count = g.pieces.length;
        model.traverse((child) => {
            if (!child.isMesh || !child.geometry) return;
            const geo = child.geometry;
            if (!geo.attributes.normal) geo.computeVertexNormals();
            const src = Array.isArray(child.material) ? child.material[0] : child.material;
            const mat = src.clone();
            mat.flatShading = false;
            const name = src.name || '';
            const fixedHex = typeof fixed[name] === 'string' && HEX_RE.test(fixed[name]) ? fixed[name] : null;
            const original = src.color ? '#' + src.color.getHexString() : '#ffffff';
            const policy = fixedHex ? fixedHex : (HARDWARE_RE.test(name) ? original : null);   // null = tint
            if (mat.color) mat.color.set('#ffffff');                                         // instance colour decides
            const mesh = new THREE.InstancedMesh(geo, mat, count);
            mesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
            const col = new THREE.Color();
            g.pieces.forEach((p, i) => mesh.setColorAt(i, col.set(policy || p.hex)));
            if (mesh.instanceColor) mesh.instanceColor.needsUpdate = true;
            scene.add(mesh);
            g.parts.push({ mesh, local: child.matrixWorld.clone() });
            disposables.push(mat, geo);
        });
        if (g.type === 'hanger') {
            const hg = hangerGeometry(g.rack);
            g.hanger = new THREE.InstancedMesh(hg, g.rack.hangerStyle === 'metal' ? darkMat : woodMat, count);
            g.hanger.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
            scene.add(g.hanger);
            disposables.push(hg);
        }
        g.ready = true;
        for (const p of g.pieces) { p.ready = true; writePiece(p); }
        flushMatrices(true);
        // spheres of the shared meshes span the wall: recompute once more for the new pieces
        if (!reduced) g.pieces.forEach((p, i) => { p.swing.v += 0.25 * (i % 2 ? -1 : 1); });
        wake();
        if (!productsShown) {
            productsShown = true;
            requestAnimationFrame(() => { if (options.onProductsShown) options.onProductsShown(); });
        }
        if (debug) buildDebug();
    }
    function loadAll() {
        const order = [...groups.values()].sort((a, b) => Math.abs(a.pieces[0].x - camX) - Math.abs(b.pieces[0].x - camX));
        let next = 0;
        const worker = async () => {
            while (next < order.length && !destroyed) {
                const g = order[next++];
                try { await loadGroup(g); } catch (err) { console.warn('[Welzee Showroom]', g.id, err); }
            }
        };
        worker(); worker();                                    // two downloads at a time
    }

    // ---------- debug outlines ----------
    let debugLines = null;
    function buildDebug() {
        const pts = [];
        const seg = (a, b) => pts.push(a.x, a.y, a.z, b.x, b.y, b.z);
        const V = (x2, y, z) => new THREE.Vector3(x2, y, z);
        seg(V(wallStart, L.railY, L.railZ), V(wallEnd, L.railY, L.railZ));                    // rail
        seg(V(wallStart, L.displayY, L.railZ), V(wallEnd, L.displayY, L.railZ));              // display line
        for (const s of sectionsOut) {                                                       // section bounds
            seg(V(s.startX - 0.05, 0.1, 0.02), V(s.startX - 0.05, 2.45, 0.02));
            seg(V(s.endX + 0.05, 0.1, 0.02), V(s.endX + 0.05, 2.45, 0.02));
        }
        for (const p of pieces) {
            const b = p.restBox, a = b.min, c = b.max;                                        // pick boxes
            const xs = [a.x, c.x], ys = [a.y, c.y], zs = [a.z, c.z];
            for (const yy of ys) for (const zz of zs) seg(V(xs[0], yy, zz), V(xs[1], yy, zz));
            for (const xx of xs) for (const zz of zs) seg(V(xx, ys[0], zz), V(xx, ys[1], zz));
            for (const xx of xs) for (const yy of ys) seg(V(xx, yy, zs[0]), V(xx, yy, zs[1]));
            seg(V(p.rest.x - 0.03, L.railY, L.railZ), V(p.rest.x + 0.03, L.railY, L.railZ));  // pivot cross
            seg(V(p.rest.x, L.railY - 0.03, L.railZ), V(p.rest.x, L.railY + 0.03, L.railZ));
        }
        if (debugLines) { scene.remove(debugLines); debugLines.geometry.dispose(); }
        const g2 = new THREE.BufferGeometry();
        g2.setAttribute('position', new THREE.Float32BufferAttribute(pts, 3));
        debugLines = new THREE.LineSegments(g2, new THREE.LineBasicMaterial({ color: 0xff3d9a, fog: false, toneMapped: false }));
        scene.add(debugLines);
        wake();
    }

    // ---------- interaction ----------
    let hovered = null, armed = null, armedAt = 0, pulled = null, lastTap = { t: 0, p: null };
    const raycaster = new THREE.Raycaster();
    const ndc = new THREE.Vector2();
    function pick(clientX, clientY) {
        const r = canvas.getBoundingClientRect();
        ndc.set(((clientX - r.left) / r.width) * 2 - 1, -((clientY - r.top) / r.height) * 2 + 1);
        raycaster.setFromCamera(ndc, camera);
        let best = null, bestD = Infinity;
        for (const p of pieces) {
            if (!p.ready) continue;
            const hit = raycaster.ray.intersectBox(p.box, _v);
            if (hit) { const d = hit.distanceTo(raycaster.ray.origin); if (d < bestD) { bestD = d; best = p; } }
        }
        return best;
    }
    function poke(p, strength = 1) {
        const kick = (reduced ? 0.3 : 0.85) * strength;
        const dir = p.swing.v === 0 ? (p.idx % 2 ? -1 : 1) : Math.sign(p.swing.v);
        p.swing.v = clamp(p.swing.v + kick * dir, -1.2, 1.2);
        p.tilt.v = clamp(p.tilt.v + (reduced ? 0.08 : 0.3) * strength, -0.5, 0.5);
        wake();
    }
    function showBack(p, on) {
        if (!p || p === pulled) return;
        p.yaw.target = on ? p.idleYaw + Math.PI : p.idleYaw;
        wake();
    }
    function setHover(p) {
        if (p === hovered) return;
        if (hovered) showBack(hovered, false);
        hovered = p;
        root.classList.toggle('is-hovering', !!p);
        if (p) { poke(p); showBack(p, true); }
    }

    const panelKicker = panel.querySelector('.srm-kicker');
    const panelName = panel.querySelector('.srm-name');
    const panelSwatch = panel.querySelector('.srm-swatch');
    const panelColor = panel.querySelector('.srm-color-name');
    const panelCta = panel.querySelector('.srm-cta');
    panel.querySelector('.srm-close').addEventListener('click', () => putBack());

    function pullForward(p) {
        if (!p || !p.ready) return;
        if (pulled && pulled !== p) putBack(true);
        setHover(null);
        if (armed) { showBack(armed, false); armed = null; }
        pulled = p;
        p.pullTarget = 1;
        p.yaw.target = p.idleYaw - p.idleYaw;           // face the camera first
        camVel = 0; camGoal = null;
        panelKicker.textContent = p.group.section || '';
        panelName.textContent = p.group.conf.name || p.group.id;
        panelSwatch.style.background = p.hex;
        panelColor.textContent = p.colorName;
        panelCta.href = customizeUrl(p.group.id, p.hex);
        panelCta.setAttribute('aria-label', `Customise ${panelName.textContent} in ${p.colorName}`);
        root.classList.add('is-pulled');
        panel.inert = false;
        panel.removeAttribute('aria-hidden');
        setTimeout(() => { if (pulled === p) panelCta.focus({ preventScroll: true }); }, 350);
        poke(p, 0.4);
        wake();
    }
    function putBack(silent) {
        const p = pulled;
        if (!p) return;
        pulled = null;
        p.pullTarget = 0;
        // turn back to the nearest front angle, not through many spins
        p.yaw.target = p.idleYaw + Math.round((p.yaw.a - p.idleYaw) / (2 * Math.PI)) * 2 * Math.PI;
        root.classList.remove('is-pulled');
        panel.inert = true;
        panel.setAttribute('aria-hidden', 'true');
        if (!silent) root.focus({ preventScroll: true });
        wake();
    }
    function presentPivot(p, out) {
        const [w, h] = p.group.rack.frontSize;
        const tanV = Math.tan(THREE.MathUtils.degToRad(C.fov / 2));
        let d = Math.max(h / (C.presentFill * 2 * tanV), w / (0.75 * 2 * tanV * camera.aspect));
        d = clamp(d, 0.65, C.distance - 0.4);
        camera.getWorldDirection(_dir);
        _right.crossVectors(_dir, camera.up).normalize();
        _up.crossVectors(_right, _dir).normalize();
        const visH = 2 * tanV * d, visW = visH * camera.aspect;
        const rw = root.clientWidth || 1, rh = root.clientHeight || 1;
        let offX = 0, offY = 0;
        if (rw >= 720) offX = -(Math.min(300, rw - 32) + 16) / rw * visW / 2;    // keep clear of the side panel
        else offY = (panel.offsetHeight + 24) / rh * visH / 2;                  // and of the bottom sheet
        out.copy(camera.position).addScaledVector(_dir, d).addScaledVector(_right, offX).addScaledVector(_up, offY);
        out.y += p.centreBelow;
        return out;
    }

    // Pointer: drag the wall (or turn the pulled piece), tap / click to pick
    let down = null, dragging = false, lastMoveT = 0, lastMoveX = 0, velSample = 0;
    function metresPerPixel() {           // measured at the rail, so the pieces follow the finger
        return (2 * halfWidthAt(C.distance)) / Math.max(1, canvas.clientWidth);
    }
    canvas.addEventListener('pointerdown', (e) => {
        if (e.button !== undefined && e.button > 0) return;
        down = { x: e.clientX, y: e.clientY, t: performance.now(), type: e.pointerType || 'mouse', id: e.pointerId };
        dragging = false;
        lastMoveT = down.t; lastMoveX = e.clientX; velSample = 0;
        camGoal = null;
        if (!doorsDone) openDoors();
        firstInteraction();
    });
    canvas.addEventListener('pointermove', (e) => {
        if (down && e.pointerId === down.id) {
            const dx = e.clientX - down.x, dy = e.clientY - down.y;
            if (!dragging && Math.abs(dx) > TAP_SLOP && Math.abs(dx) > Math.abs(dy)) {
                dragging = true;
                try { canvas.setPointerCapture(e.pointerId); } catch (err) { /* ignore */ }
                root.classList.add('is-dragging');
                setHover(null);
            }
            if (dragging) {
                const now = performance.now();
                const mx = e.clientX - lastMoveX;
                if (pulled) {
                    pulled.yaw.target += mx * 0.012;           // turn the piece in your hand
                } else if (doorsT > 0.5) {
                    const dm = -mx * metresPerPixel() * C.dragSpeed;
                    camX = clamp(camX + dm, minX - 0.6, maxX + 0.6);
                    const dt = Math.max(1, now - lastMoveT) / 1000;
                    velSample = 0.6 * velSample + 0.4 * (dm / dt);
                }
                lastMoveT = now; lastMoveX = e.clientX;
                wake();
            }
        } else if ((e.pointerType || 'mouse') === 'mouse' && !pulled && doorsDone) {
            setHover(pick(e.clientX, e.clientY));
        }
    });
    function endPress(e, cancelled) {
        if (!down || (e && e.pointerId !== down.id)) return;
        const wasDrag = dragging;
        const press = down;
        down = null; dragging = false;
        root.classList.remove('is-dragging');
        if (wasDrag) {
            if (!pulled && performance.now() - lastMoveT < 80) camVel = clamp(velSample, -C.maxSpeed, C.maxSpeed);
            wake();
            return;
        }
        if (cancelled) return;
        handleTap(e.clientX, e.clientY, press.type);
    }
    canvas.addEventListener('pointerup', (e) => endPress(e, false));
    canvas.addEventListener('pointercancel', (e) => endPress(e, true));
    canvas.addEventListener('pointerleave', (e) => { if ((e.pointerType || 'mouse') === 'mouse' && !down) setHover(null); });

    function handleTap(cx, cy, type) {
        if (!doorsDone && doorsT < 0.5) return;
        const p = pick(cx, cy);
        if (pulled) {
            if (p !== pulled) putBack();
            return;
        }
        if (!p) {                                              // tap on the stage: release the armed piece
            if (armed) { showBack(armed, false); armed = null; }
            return;
        }
        const now = performance.now();
        if (type === 'mouse') { pullForward(p); return; }
        const doubleTap = lastTap.p === p && now - lastTap.t < 350;
        lastTap = { t: now, p };
        if (doubleTap || (armed === p && now - armedAt < 6000)) { pullForward(p); return; }
        if (armed && armed !== p) showBack(armed, false);
        armed = p; armedAt = now;
        poke(p); showBack(p, true);
    }

    // Wheel: sideways (trackpad) always; vertical wheel only while the wall can still move
    root.addEventListener('wheel', (e) => {
        if (pulled) return;
        if (!doorsDone) { openDoors(); firstInteraction(); }
        const horizontal = Math.abs(e.deltaX) > Math.abs(e.deltaY);
        const delta = horizontal ? e.deltaX : (C.wheelVertical ? e.deltaY : 0);
        if (!delta) return;
        const unit = e.deltaMode === 1 ? 16 : (e.deltaMode === 2 ? 400 : 1);
        const dm = delta * unit * C.wheelSpeed;
        const atEnd = (dm < 0 && camX <= minX + 0.001) || (dm > 0 && camX >= maxX - 0.001);
        if (!horizontal && atEnd) return;                      // let the page scroll on
        e.preventDefault();
        camGoal = null;
        camVel = clamp(camVel + dm * 12, -C.maxSpeed, C.maxSpeed);
        firstInteraction();
        wake();
    }, { passive: false });

    // Keyboard
    root.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && pulled) { e.preventDefault(); putBack(); return; }
        if (!doorsDone) openDoors();
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            if (pulled) return;
            e.preventDefault();
            goTo(clamp((camGoal ?? camX) + (e.key === 'ArrowRight' ? 1 : -1) * C.keyStep, minX, maxX));
            firstInteraction();
        } else if ((e.key === 'Enter' || e.key === ' ') && e.target === root && !pulled) {
            e.preventDefault();
            let best = null, bd = Infinity;                    // piece nearest the middle of the view
            for (const p of pieces) if (p.ready && Math.abs(p.x - camX) < bd) { bd = Math.abs(p.x - camX); best = p; }
            if (best) pullForward(best);
        }
    });
    const onDocKey = (e) => { if (e.key === 'Escape' && pulled) putBack(); };
    document.addEventListener('keydown', onDocKey);
    root.addEventListener('mouseenter', () => { if (!doorsDone) openDoors(); });
    const onPageScroll = () => { if (!doorsDone) openDoors(); };          // doors also open on page scroll
    window.addEventListener('scroll', onPageScroll, { passive: true, once: true });

    // Section buttons
    const navButtons = sectionsOut.map((s) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = s.label;
        b.addEventListener('click', () => { if (!doorsDone) openDoors(); goTo(clamp(s.centerX, minX, maxX)); firstInteraction(); });
        nav.appendChild(b);
        return { s, b };
    });
    function updateNavCurrent() {
        let cur = null, bd = Infinity;
        for (const n of navButtons) { const d = Math.abs(n.s.centerX - camX); if (d < bd) { bd = d; cur = n; } }
        for (const n of navButtons) n.b.classList.toggle('is-current', n === cur);
    }
    function goTo(x2) { camGoal = x2; camVel = 0; wake(); }

    let hintShown = false, interacted = false;
    function firstInteraction() {
        if (interacted) return;
        interacted = true;
        root.classList.remove('show-hint');
    }

    // ---------- loop ----------
    let running = false, visible = true, last = 0, raf = 0, introStart = 0, firstFrame = false, hudT = 0, frames = 0, fps = 0;
    function wake() {
        if (destroyed) return;
        if (!running && visible && !document.hidden) {
            running = true;
            last = performance.now();
            raf = requestAnimationFrame(tick);
        }
    }
    function spring(s, target, k, c, h) { s.v += (-k * (s.a - target) - c * s.v) * h; s.a += s.v * h; }
    const _target = new THREE.Vector3();
    function stepPiece(p, dt) {
        const n = 2, h = dt / n;
        for (let i = 0; i < n; i++) {
            spring(p.swing, 0, SWING_K, SWING_C, h);
            spring(p.tilt, 0, TILT_K, SWING_C, h);
            spring(p.yaw, p.yaw.target, YAW_K, YAW_C, h);
        }
        let moving = Math.abs(p.swing.a) > EPS || Math.abs(p.swing.v) > EPS || Math.abs(p.tilt.a) > EPS ||
            Math.abs(p.tilt.v) > EPS || Math.abs(p.yaw.a - p.yaw.target) > EPS || Math.abs(p.yaw.v) > EPS;
        if (!moving) { p.swing.a = p.swing.v = p.tilt.a = p.tilt.v = p.yaw.v = 0; p.yaw.a = p.yaw.target; }
        // pull toward the camera / back to the rail
        let pulling = false;
        if (p.pull !== p.pullTarget || p === pulled) {
            p.pull += (p.pullTarget - p.pull) * (1 - Math.exp(-(reduced ? 14 : 6) * dt));
            if (Math.abs(p.pull - p.pullTarget) < 0.002) p.pull = p.pullTarget;
            const e = p.pull;
            if (e > 0) {
                presentPivot(p, _target);
                p.pos.lerpVectors(p.rest, _target, e);
                p.pos.y += Math.sin(Math.PI * e) * 0.12;                 // small lift off the rail
            } else {
                p.pos.copy(p.rest);
            }
            if (p === pulled && !reduced && !(down && dragging)) p.yaw.target += 0.35 * dt;   // slow turn in the hand
            pulling = true;
            moving = true;
        }
        return { moving, pulling };
    }
    function tick(now) {
        if (destroyed) return;
        if (!visible || document.hidden) { running = false; return; }
        const dt = Math.min(0.033, Math.max(0, (now - last) / 1000));
        last = now;
        let active = false;

        // intro: lights, doors, dolly. The clock starts when the first product is on
        // screen (the page shows the canvas only then), so nobody misses the doors.
        if (!introStart && productsShown) introStart = now;
        if (introStart && lightsT < 1) { lightsT = Math.min(1, lightsT + dt / 0.8); applyLightFade(); active = true; }
        if (!doorsDone && !doorsOpening && introStart && now - introStart > cfg.intro.delay) doorsOpening = true;
        if (doorsOpening && !doorsDone) {
            doorsT = Math.min(1, doorsT + dt / (cfg.intro.duration / 1000));
            applyDoors();
            if (doorsT >= 1) {
                doorsDone = true;
                applyDoors();
                root.classList.remove('is-intro');
                if (!interacted && !hintShown) {
                    hintShown = true;
                    root.classList.add('show-hint');
                    setTimeout(() => root.classList.remove('show-hint'), 3500);
                }
                if (!reduced) {                                           // ripple along the visible wall
                    const hw = viewHalfWidthAtWall();
                    pieces.forEach((p) => { if (p.ready && Math.abs(p.x - camX) < hw + 0.5) setTimeout(() => poke(p, 0.45), 60 + (p.x - (camX - hw)) * 140); });
                }
            }
            active = true;
        }
        if (introStart && (!doorsDone || dolly > 0)) {
            const k = THREE.MathUtils.smootherstep(Math.min(1, (now - introStart) / 2600), 0, 1);
            dolly = reduced ? 0 : cfg.intro.dolly * (1 - k);
            if (dolly > 1e-4) active = true; else dolly = 0;
        }

        // camera: goal tween, inertia, soft ends
        if (camGoal !== null) {
            camX += (camGoal - camX) * (1 - Math.exp(-5 * dt));
            if (Math.abs(camGoal - camX) < 0.002) { camX = camGoal; camGoal = null; }
            active = true;
        } else if (!dragging) {
            if (Math.abs(camVel) > 0.002) {
                camX += camVel * dt;
                camVel *= Math.exp(-C.friction * dt);
                active = true;
            } else camVel = 0;
            if (camX < minX) { camX += (minX - camX) * (1 - Math.exp(-9 * dt)); camVel *= 0.5; active = active || minX - camX > 0.001; if (minX - camX <= 0.001) camX = minX; }
            if (camX > maxX) { camX += (maxX - camX) * (1 - Math.exp(-9 * dt)); camVel *= 0.5; active = active || camX - maxX > 0.001; if (camX - maxX <= 0.001) camX = maxX; }
        } else active = true;
        placeCamera();
        if (!doorsDone) for (const d of doors) d.hinge.position.x = camX + d.side * Math.abs(d.hinge.position.x - camX);
        updateNavCurrent();

        // pieces
        let pullingAny = false;
        for (const p of pieces) {
            if (!p.ready) continue;
            const r = stepPiece(p, dt);
            if (r.moving) { writePiece(p); active = true; }
            if (r.pulling) pullingAny = true;
        }
        flushMatrices(pullingAny);

        renderer.render(scene, camera);
        if (!firstFrame) { firstFrame = true; if (options.onFirstFrame) options.onFirstFrame(); }
        if (hud) {
            frames++;
            if (now - hudT > 500) {
                fps = Math.round(frames * 1000 / (now - hudT || 1)); frames = 0; hudT = now;
                const i = renderer.info.render;
                hud.textContent = `draw calls ${i.calls}\ntriangles  ${i.triangles.toLocaleString()}\nfps        ${fps}\ncamera x   ${camX.toFixed(2)} m\npieces     ${pieces.filter((p) => p.ready).length}/${pieces.length}`;
            }
            active = true;                                               // keep the HUD live in debug
        }
        if (active || pulled) raf = requestAnimationFrame(tick);
        else running = false;
    }

    // ---------- size, visibility ----------
    function resize() {
        const w = Math.max(1, root.clientWidth), h = Math.max(1, root.clientHeight);
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        updateBounds();
        camX = clamp(camX, minX, maxX);
        layoutDoors();
        wake();
    }
    const ro = new ResizeObserver(() => resize());
    ro.observe(root);
    const io = new IntersectionObserver((list) => {
        visible = list[0] ? list[0].isIntersecting : true;
        if (visible) wake();
    });
    io.observe(root);
    const onVis = () => { if (!document.hidden) wake(); };
    document.addEventListener('visibilitychange', onVis);

    // ---------- start ----------
    resize();
    camX = minX;
    updateBounds();
    layoutDoors();
    placeCamera();
    if (reduced) { root.classList.remove('is-intro'); for (const d of doors) d.hinge.visible = false; }
    if (debug) buildDebug();
    wake();
    loadAll();

    return {
        openDoors,
        goToSection(id) { const s = sectionsOut.find((q) => q.id === id); if (s) { openDoors(); goTo(clamp(s.centerX, minX, maxX)); } },
        destroy() {
            if (destroyed) return;
            destroyed = true;
            cancelAnimationFrame(raf);
            ro.disconnect(); io.disconnect();
            document.removeEventListener('visibilitychange', onVis);
            document.removeEventListener('keydown', onDocKey);
            window.removeEventListener('scroll', onPageScroll);
            scene.traverse((o) => { if (o.isInstancedMesh) o.dispose(); });
            for (const d of disposables) { try { d.dispose(); } catch (e) { /* ignore */ } }
            for (const d of doors) d.edge.geometry.dispose();
            if (debugLines) debugLines.geometry.dispose();
            if (dracoLoader) dracoLoader.dispose();
            renderer.dispose();
            if (renderer.forceContextLoss) renderer.forceContextLoss();
            root.remove();
        }
    };
}
