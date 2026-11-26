/**
 * assets/js/3d/showroom-cupboards.js
 *
 * Showroom hero, cupboard layout: six wooden display cupboards (3 x 2, phones 2 x 3)
 * on the dark stage. Each cupboard has a frame, back panel, rail, two panelled glass
 * doors with brass handles, a warm interior light and a plinth; the featured product
 * hangs inside on its hanger (same hanger / cord / swing code as the wall and rack).
 * Hover (desktop): doors open a little, the product swings. Click / tap: the camera
 * zooms in, the doors swing fully open, the other cupboards fade back behind a dark
 * veil, and the cupboard shows the product's variations (colours for now) as tinted
 * copies on rails, up to CONFIG.variationLayout.perRow per row. Back button / Esc returns.
 * Click / tap a copy: it lifts off its rail, flies along an arc and shrinks into the
 * "Your pick" garment bag in the corner (thread sparkle, bag bounce), while the quick
 * view opens with name, colour and "Customise". Picking another copy sends the first
 * one back to its rail. Reduced motion: no flight, instant.
 * A featured product without a model shows a locked cupboard with a "Coming soon" plate.
 *
 * Same entry point as the wall (showroom.js): `mountShowroom(container, options)`,
 * so the page and the future home-page hero mount either layout the same way.
 *
 * Performance: one renderer; every cupboard part is one InstancedMesh shared by all
 * six cupboards (wood tint per instance), every product one InstancedMesh per model
 * part. Renders on demand, pauses off screen / hidden, pixel ratio capped.
 *
 * Featured list, colours: models.manifest.json "showroom" (featured, sections,
 * colorNames, optional variations). Everything else: CUPBOARD_CONFIG below; the
 * manifest may override it with "showroom": { "cupboards": { ...same keys } }.
 *
 * Events on `container`: showroom:interact (first hover-free interaction),
 * showroom:zoom {id}, showroom:grid, showroom:pick {product, color},
 * showroom:pull (quick view open), showroom:release (quick view closed).
 */

import * as THREE from 'three';
import * as BufferGeometryUtils from 'three/addons/utils/BufferGeometryUtils.js';
import { createProceduralProduct } from './procedural.js';
import {
    HEX_RE, GLB_PATH_RE, LOOP_R, CORD_R, TAP_SLOP, num, mergeDeep, mat4, placed, shortName,
    canvasTexture, radialTexture, wallTexture, floorTexture,
    loopGeometry, hookGeometry, hangerGeometry, clipGeometry, normaliseRack, stepHanging,
    createModelLoader, toBaseFrame, gatherHandles, buildProductParts, PANEL_CSS, createQuickView
} from './showroom-common.js';

/* ============================== CONFIG ============================== */

export const CUPBOARD_CONFIG = {
    maxDpr: 1.5,
    featured: ['tshirt', 'polo', 'hoodie', 'varsity', 'cap', 'apron'],   // manifest "showroom.featured" wins
    maxFeatured: 6,
    maxVariations: 18,
    grid: {
        cols: 3, rows: 2,             // wide and tablet screens
        narrowCols: 2, narrowRows: 3, // phones
        narrowBelow: 0.7              // stage width / height below this = phone layout
    },
    cupboard: {
        height: 1.3,                  // body height (m), plinth included
        cornice: 0.035,               // top trim
        depth: 0.42,
        minWidth: 0.72, maxWidth: 1.45,
        gapX: 0.16, gapY: 0.2,
        frame: 0.05,                  // side / top / bottom board thickness
        plinth: 0.1,
        doorThickness: 0.024,
        stile: 0.05,                  // door frame width
        railDrop: 0.07,               // rail below the interior top
        woodTints: ['#a8744a', '#8d5b36', '#b07d52', '#7f5233', '#9a6a43', '#86593a']
    },
    frame: {
        fov: 34,
        top: 0.09,                    // stage fraction kept for the page heading
        bottom: 0.08,                 // stage fraction under the grid (floor, links)
        side: 0.035,
        pitch: 3                      // camera looks down this many degrees
    },
    product: {
        hookLength: 0.11,
        fillW: 0.9,                   // garment width / interior width (max; sleeves may nearly touch)
        bottomMargin: 0.05,           // interior fraction kept free under a garment
        cordFill: 0.42,               // caps / bags: item height / interior height
        maxScale: 1.15, maxCordScale: 3
    },
    doors: {
        hover: 0.38,                  // rad, hover peek
        open: 1.05,                   // rad, tap / click
        stiffness: 70, damping: 0.62  // spring (damping ratio < 1 = a little overshoot)
    },
    zoom: {
        duration: 950,                // ms, camera in / out
        forward: 0.9,                 // m the chosen cupboard moves toward the camera
        veil: 0.78,                   // darkness over the other cupboards (0..1)
        margin: 0.05,                 // space around the cupboard, fraction of its size
        topPx: 56,                    // px kept for the back button
        doors: 1.95                   // rad, doors fully open
    },
    variationLayout: {
        perRow: 6,                    // copies per rail
        perRowNarrow: 3,              // phones (2 x 3 grid): fewer, larger copies per rail
        fillW: 0.86,                  // copy width / slot width (max)
        cordFill: 0.62,               // caps / bags: copy height / row height
        gap: 0.03,                    // m between a row's hems and the rail below
        stagger: 45                   // ms between copies popping in
    },
    pick: {
        flight: 850,                  // ms, rail -> bag
        back: 600,                    // ms, bag -> rail
        arc: 0.3,                     // arc height, fraction of the close-up view height
        endScale: 0.14,               // size when it drops into the bag
        bagDepth: 0.55,               // flight end: this fraction of the camera distance
        reframe: 500                  // ms, camera slides aside for the quick view
    },
    lighting: {
        background: '#0b0c0f',
        fog: [1.5, 10],               // metres beyond the cupboard fronts
        exposure: 1.05,
        hemi: 0.5, key: 1.6, fill: 0.35, rim: 0.7, warm: 0.55,
        wall: '#262a31',
        floor: '#2a2b2f',
        spotColor: '#ffe7c2',
        beamOpacity: 0.06, wallPoolOpacity: 0.4, floorPoolOpacity: 0.5,
        interior: '#ffcf8a',          // warm interior light colour
        interiorOff: '#1a1510'        // locked cupboard
    },
    intro: { stagger: 110, peek: 0.3, hold: 420 },   // ms between cupboards, rad, ms
    sections: [], colorNames: {}, variations: {}
};

const clamp = THREE.MathUtils.clamp;
const smooth = THREE.MathUtils.smoothstep;
const V3 = (x = 0, y = 0, z = 0) => new THREE.Vector3(x, y, z);

/* ====================== pure layout (testable) ====================== */

/** Featured cupboards in order: { id, name, locked, conf }. */
export function featuredList(cfg, products) {
    const ids = Array.isArray(cfg.featured) ? cfg.featured : [];
    const out = [];
    for (const raw of ids) {
        const id = typeof raw === 'string' ? raw : (raw && raw.product);
        if (typeof id !== 'string' || !/^[a-z0-9_-]{1,32}$/.test(id) || out.some((f) => f.id === id)) continue;
        const conf = products[id] || null;
        const locked = !conf || !(typeof conf.glb === 'string' && GLB_PATH_RE.test(conf.glb));
        out.push({ id, conf, locked, name: shortName(conf && conf.name, id) });
        if (out.length >= cfg.maxFeatured) break;
    }
    return out;
}

/**
 * Variations of a product, ready for more types later: [{ type, value, label }].
 * Manifest "showroom.variations[id]" if given, else the product's colours from the
 * "showroom.sections" lists.
 */
export function variationsFor(cfg, id) {
    const names = cfg.colorNames || {};
    const list = [];
    const add = (type, value, label) => {
        if (type !== 'color' || typeof value !== 'string' || !HEX_RE.test(value)) return;
        const v = value.toLowerCase();
        if (list.some((x) => x.value === v)) return;
        list.push({ type, value: v, label: label || names[v] || v.toUpperCase() });
    };
    const explicit = cfg.variations && cfg.variations[id];
    if (Array.isArray(explicit)) {
        for (const v of explicit) if (v) add(v.type || 'color', v.value, v.label);
    } else {
        for (const sec of cfg.sections || []) {
            for (const item of (sec && sec.items) || []) if (item && item.product === id) for (const c of item.colors || []) add('color', c);
        }
    }
    if (!list.length) add('color', '#f4f4f2', 'White');
    return list.slice(0, Math.max(1, cfg.maxVariations));
}

/** Grid of `count` cupboards for a stage of view.width x view.height px. */
export function computeGridLayout(cfg, view, count) {
    const G = cfg.grid, CB = cfg.cupboard, F = cfg.frame;
    const Wpx = Math.max(1, view.width), Hpx = Math.max(1, view.height);
    const aspect = Wpx / Hpx;
    const narrow = aspect < G.narrowBelow;
    const cols = Math.max(1, Math.min(narrow ? G.narrowCols : G.cols, count));
    const rows = Math.max(1, Math.min(narrow ? G.narrowRows : G.rows, Math.ceil(count / cols)));
    const Ht = CB.height + CB.cornice;
    const fh = 1 - F.top - F.bottom, fw = 1 - 2 * F.side;
    const gh = rows * Ht + (rows - 1) * CB.gapY;
    let visH = (gh * 1.035) / fh;                            // +3.5 %: perspective makes the top row look taller
    const W = clamp((aspect * visH * fw - (cols - 1) * CB.gapX) / cols, CB.minWidth, CB.maxWidth);
    const gw = cols * W + (cols - 1) * CB.gapX;
    visH = Math.max(visH, gw / (aspect * fw));
    const tanV = Math.tan(THREE.MathUtils.degToRad(F.fov / 2));
    const dist = visH / (2 * tanV);
    const pitch = THREE.MathUtils.degToRad(F.pitch);
    const centreFrac = F.top + fh / 2;                       // grid centre, fraction from the top
    const lookY = gh / 2 + (centreFrac - 0.5) * visH;
    const cells = [];
    for (let i = 0; i < count; i++) {
        const row = Math.floor(i / cols), col = i % cols;
        const inRow = Math.min(cols, count - row * cols);    // centre a short last row
        const rowW = inRow * W + (inRow - 1) * CB.gapX;
        cells.push({ i, row, col, x: -rowW / 2 + W / 2 + col * (W + CB.gapX), y: (rows - 1 - row) * (Ht + CB.gapY) });
    }
    return {
        width: Wpx, height: Hpx, aspect, narrow, cols, rows, W, H: CB.height, Ht, D: CB.depth, gw, gh,
        visH, visW: visH * aspect, dist, mpp: visH / Hpx, tanV, pitch, lookY, lookZ: CB.depth,
        camY: lookY + dist * Math.sin(pitch), camZ: CB.depth + dist * Math.cos(pitch), cells
    };
}

/** Interior box of a cupboard of width W (cupboard space: bottom centre, back at z = 0). */
export function cupboardInterior(cfg, W) {
    const CB = cfg.cupboard;
    const y0 = CB.plinth + CB.frame, y1 = CB.height - CB.frame;
    const z0 = 0.012, z1 = CB.depth - CB.doorThickness - 0.008;
    return { x0: -W / 2 + CB.frame, x1: W / 2 - CB.frame, iw: W - 2 * CB.frame, y0, y1, ih: y1 - y0, z0, z1, railY: y1 - CB.railDrop, railZ: (z0 + z1) / 2 };
}

/** Scale and hanging offsets so one product fits the interior. */
export function fitProduct(cfg, info, interior) {
    const P = cfg.product, r = info.rack;
    const [w, h] = r.frontSize;
    if (info.type === 'hanger') {
        const A1 = r.hangerProfile[0][1];
        const sH = (interior.railY - P.hookLength - interior.y0 - P.bottomMargin * interior.ih) / A1;
        const s = Math.max(0.05, Math.min(sH, (P.fillW * interior.iw) / w, P.maxScale));
        return { s, centreBelow: P.hookLength + s * (A1 - h / 2), cordLen: 0, w: w * s, h: h * s };
    }
    const s = Math.max(0.05, Math.min((P.cordFill * interior.ih) / h, (P.fillW * interior.iw) / w, P.maxCordScale));
    const cy = (interior.y0 + interior.y1) / 2;
    const above = s * (r.attach[1] - r.centerY);
    const cordLen = Math.max(0.04, interior.railY - cy - above);
    return { s, centreBelow: cordLen + above, cordLen, w: w * s, h: h * s };
}

/**
 * Variations inside the cupboard: rows of rails (the cupboard rail is row 0), up to
 * perRow copies per row, a short last row centred. Same scale for every copy.
 */
export function layoutVariations(cfg, info, interior, n, narrow) {
    const V = cfg.variationLayout, P = cfg.product, r = info.rack;
    const [w, h] = r.frontSize;
    const perRow = Math.max(1, Math.min(narrow ? V.perRowNarrow : V.perRow, n));
    const rows = Math.max(1, Math.ceil(n / perRow));
    const top = interior.railY, bottom = interior.y0 + 0.02;
    const rh = (top - bottom) / rows;
    const sw = interior.iw / perRow;
    let s, centreBelow, cordLen = 0;
    if (info.type === 'hanger') {
        const A1 = r.hangerProfile[0][1];
        s = Math.max(0.05, Math.min((rh - P.hookLength - V.gap) / A1, (V.fillW * sw) / w, P.maxScale));
        centreBelow = P.hookLength + s * (A1 - h / 2);
    } else {
        s = Math.max(0.05, Math.min((V.cordFill * rh) / h, (V.fillW * sw) / w, P.maxCordScale));
        const above = s * (r.attach[1] - r.centerY);
        cordLen = Math.max(0.03, rh * 0.5 - above);          // copy centred in its row
        centreBelow = cordLen + above;
    }
    const rails = [], slots = [];
    for (let k = 0; k < rows; k++) rails.push(top - k * rh);
    for (let i = 0; i < n; i++) {
        const row = Math.floor(i / perRow), c = i % perRow;
        const inRow = Math.min(perRow, n - row * perRow);
        slots.push({ i, row, x: -(inRow * sw) / 2 + sw / 2 + c * sw, y: rails[row] });
    }
    return { perRow, rows, rh, sw, s, centreBelow, cordLen, w: w * s, h: h * s, rails, slots };
}

/**
 * Close-up camera for one cupboard (moved `zoom.forward` toward the camera), centred
 * in the stage minus `insets` (px: right = side panel, bottom = bottom sheet) and the
 * back-button bar on top. Straight-on view (no pitch).
 */
export function computeZoomView(cfg, L, cell, view, insets = {}) {
    const Z = cfg.zoom;
    const Wpx = Math.max(1, view.width), Hpx = Math.max(1, view.height), aspect = Wpx / Hpx;
    const right = clamp(num(insets.right, 0), 0, Wpx * 0.6), bottom = clamp(num(insets.bottom, 0), 0, Hpx * 0.6);
    const freeW = (Wpx - right) / Wpx, freeH = (Hpx - bottom - Z.topPx) / Hpx;
    const visH = Math.max((L.Ht * (1 + 2 * Z.margin)) / freeH, (L.W * (1 + 2 * Z.margin)) / (freeW * aspect));
    const dist = visH / (2 * L.tanV), mpp = visH / Hpx;
    const cx = cell.x, cy = cell.y + L.Ht / 2, frontZ = Z.forward + L.D;
    const offX = -right / 2, offY = (Z.topPx - bottom) / 2;        // free-area centre, px from the screen centre (y down)
    const tx = cx - offX * mpp, ty = cy + offY * mpp;
    return { visH, dist, mpp, look: V3(tx, ty, frontZ), cam: V3(tx, ty, frontZ + dist) };
}

/* ============================ textures ============================== */

const TAU = Math.PI * 2;
const woodTexture = () => canvasTexture(256, 512, (ctx, w, h) => {
    const img = ctx.createImageData(w, h), d = img.data;
    for (let y = 0; y < h; y++) {
        for (let x = 0; x < w; x++) {
            const warp = Math.sin(y * TAU / h + x * TAU / w) * 9 + Math.sin(y * 3 * TAU / h) * 3;
            const ring = Math.sin((x + warp) * 8 * TAU / w) * 0.5 + 0.5;
            const fine = Math.sin((x + warp * 0.5) * 53 * TAU / w) * 0.5 + 0.5;
            const v = 228 - ring * ring * 40 - fine * 9 + (Math.random() - 0.5) * 10;
            const i = (y * w + x) * 4;
            d[i] = v; d[i + 1] = v * 0.93; d[i + 2] = v * 0.84; d[i + 3] = 255;
        }
    }
    ctx.putImageData(img, 0, 0);
}, { repeat: true });
const glassTexture = () => canvasTexture(128, 256, (x, w, h) => {
    x.fillStyle = 'rgba(185,215,232,0.10)'; x.fillRect(0, 0, w, h);
    const g = x.createLinearGradient(0, h, w, 0);                     // soft diagonal streaks
    g.addColorStop(0.30, 'rgba(255,255,255,0)');
    g.addColorStop(0.40, 'rgba(255,255,255,0.22)');
    g.addColorStop(0.46, 'rgba(255,255,255,0)');
    g.addColorStop(0.55, 'rgba(255,255,255,0.10)');
    g.addColorStop(0.60, 'rgba(255,255,255,0)');
    x.fillStyle = g; x.fillRect(0, 0, w, h);
    x.strokeStyle = 'rgba(255,255,255,0.55)'; x.lineWidth = 3; x.strokeRect(1.5, 1.5, w - 3, h - 3);   // edge highlight
    x.strokeStyle = 'rgba(255,255,255,0.16)'; x.lineWidth = 1; x.strokeRect(7.5, 7.5, w - 15, h - 15);
});

/* ============================ geometry ============================== */

/** Box with grain-aligned UVs and a shade (vertex colour) so one wood material serves every part. */
function woodBox(w, h, d, x, y, z, shade, horizontal) {
    const g = new THREE.BoxGeometry(w, h, d);
    const uv = g.attributes.uv;
    const long = Math.max(w, d);
    for (let i = 0; i < uv.count; i++) {
        const u = uv.getX(i), v = uv.getY(i);
        if (horizontal) uv.setXY(i, v * h / 0.25, u * long / 0.8);   // grain along the length
        else uv.setXY(i, u * long / 0.25, v * h / 0.8);              // grain up the height
    }
    const col = new Float32Array(g.attributes.position.count * 3).fill(shade);
    g.setAttribute('color', new THREE.BufferAttribute(col, 3));
    return placed(g, mat4().makeTranslation(x, y, z));
}

/** All cupboard geometry for a width W (shared by every cupboard instance). */
function cupboardGeometry(cfg, W) {
    const CB = cfg.cupboard, H = CB.height, D = CB.depth, fr = CB.frame, pl = CB.plinth;
    const body = BufferGeometryUtils.mergeGeometries([
        woodBox(W - 0.04, pl, D - 0.04, 0, pl / 2, (D - 0.04) / 2, 0.55, true),               // plinth (recessed)
        woodBox(W, fr, D, 0, pl + fr / 2, D / 2, 0.95, true),                                // bottom
        woodBox(W, fr, D, 0, H - fr / 2, D / 2, 0.95, true),                                 // top
        woodBox(fr, H - pl, D, -(W / 2 - fr / 2), pl + (H - pl) / 2, D / 2, 1, false),        // sides
        woodBox(fr, H - pl, D, W / 2 - fr / 2, pl + (H - pl) / 2, D / 2, 1, false),
        woodBox(W - 2 * fr, H - pl - 2 * fr, 0.012, 0, pl + fr + (H - pl - 2 * fr) / 2, 0.006, 0.38, false),   // back panel
        woodBox(W + 0.04, CB.cornice, D + 0.03, 0, H + CB.cornice / 2, (D + 0.03) / 2, 0.85, true)              // cornice
    ]);
    const dw = (W - 2 * fr) / 2 - 0.003, dh = H - pl - 2 * fr - 0.006, dt = CB.doorThickness, st = CB.stile;
    const door = BufferGeometryUtils.mergeGeometries([
        woodBox(st, dh, dt, -(dw / 2 - st / 2), 0, 0, 1, false),
        woodBox(st, dh, dt, dw / 2 - st / 2, 0, 0, 1, false),
        woodBox(dw - 2 * st, st, dt, 0, dh / 2 - st / 2, 0, 0.96, true),
        woodBox(dw - 2 * st, st * 1.3, dt, 0, -(dh / 2 - st * 0.65), 0, 0.96, true),
        woodBox(dw - 2 * st, 0.018, dt * 0.8, 0, dh * 0.12, 0, 0.96, true)                    // glazing bar
    ]);
    const glass = new THREE.PlaneGeometry(dw - 2 * st + 0.01, dh - 2.3 * st + 0.01);
    glass.translate(0, -0.15 * st, 0);
    const handle = BufferGeometryUtils.mergeGeometries([
        placed(new THREE.CylinderGeometry(0.006, 0.006, 0.13, 10), mat4().makeTranslation(0, 0, 0.024)),
        placed(new THREE.CylinderGeometry(0.0045, 0.0045, 0.024, 8), mat4().makeRotationX(Math.PI / 2).setPosition(0, 0.05, 0.012)),
        placed(new THREE.CylinderGeometry(0.0045, 0.0045, 0.024, 8), mat4().makeRotationX(Math.PI / 2).setPosition(0, -0.05, 0.012))
    ]);
    const I = cupboardInterior(cfg, W);
    const rail = placed(new THREE.CylinderGeometry(0.008, 0.008, I.iw, 12), mat4().makeRotationZ(Math.PI / 2));
    // interior light: soft glow on the back panel + a bright strip under the top (one mesh)
    const glowBack = new THREE.PlaneGeometry(I.iw, I.ih);
    const uvB = glowBack.attributes.uv;
    for (let i = 0; i < uvB.count; i++) uvB.setXY(i, uvB.getX(i), -0.3 + uvB.getY(i) * 1.1);   // brightest near the top
    glowBack.translate(0, I.y0 + I.ih / 2, 0.0135);
    const strip = new THREE.PlaneGeometry(I.iw * 0.92, 0.012);
    const uvS = strip.attributes.uv;
    for (let i = 0; i < uvS.count; i++) uvS.setXY(i, 0.5, 0.5);
    strip.translate(0, I.y1 - 0.012, I.z1 - 0.03);
    const glow = BufferGeometryUtils.mergeGeometries([glowBack, strip]);
    const lock = BufferGeometryUtils.mergeGeometries([
        placed(new THREE.BoxGeometry(0.05, 0.058, 0.018), mat4().makeTranslation(0, 0, 0.03)),
        placed(new THREE.TorusGeometry(0.017, 0.0045, 8, 18, Math.PI), mat4().makeTranslation(0, 0.029, 0.03))
    ]);
    return { body, door, glass, handle, rail, glow, lock, dims: { W, H, D, fr, pl, dw, dh, dt } };
}

/* ============================== styles ============================== */

const STYLE_ID = 'welzee-showroom-cupboards-css';
function injectStyles() {
    if (document.getElementById(STYLE_ID)) return;
    const s = document.createElement('style');
    s.id = STYLE_ID;
    s.textContent = `
.srm-root{position:absolute;inset:0;overflow:hidden;outline:none;-webkit-user-select:none;user-select:none;font-family:inherit}
.srm-canvas{position:absolute;inset:0;width:100%;height:100%;display:block;touch-action:pan-y}
.srm-root.is-hovering .srm-canvas{cursor:pointer}
.srm-plates{position:absolute;inset:0;pointer-events:none}
.srm-plate{position:absolute;left:0;top:0;transform:translate(-50%,-50%);pointer-events:auto;white-space:nowrap;border:0;cursor:pointer;
 padding:4px 12px;border-radius:4px;font:700 11px/1.2 inherit;letter-spacing:.12em;text-transform:uppercase;color:#2b1d0e;
 background:linear-gradient(180deg,#f3d99b 0%,#c9a15a 48%,#a77d3c 100%);box-shadow:0 2px 6px rgba(0,0,0,.5),inset 0 1px 0 rgba(255,255,255,.55);transition:opacity .35s,filter .2s}
.srm-plate::before,.srm-plate::after{content:"";position:absolute;top:50%;width:3px;height:3px;margin-top:-1.5px;border-radius:50%;background:#7a5a2a}
.srm-plate::before{left:4px}.srm-plate::after{right:4px}
.srm-plate:hover,.srm-plate.is-hover{filter:brightness(1.12)}
.srm-plate:focus-visible{outline:2px solid #22c55e;outline-offset:3px}
.srm-plate.is-locked{cursor:default;color:#4a3a26;background:linear-gradient(180deg,#d9c7a0,#a99064);filter:saturate(.6)}
.srm-plate .srm-soon{display:block;font-size:9px;letter-spacing:.14em;opacity:.8}
.srm-hud{position:absolute;top:8px;right:8px;font:11px/1.35 ui-monospace,Menlo,Consolas,monospace;color:#a7f3d0;background:rgba(0,0,0,.6);padding:6px 8px;border-radius:6px;pointer-events:none;white-space:pre;z-index:9}
@media (max-width:719px){.srm-plate{font-size:10px;padding:3px 9px;letter-spacing:.1em}}
.srm-root.is-zoomed .srm-plates{opacity:0;pointer-events:none}
.srm-root.is-zoomed .srm-plate{pointer-events:none}
.srm-zoombar{position:absolute;left:12px;top:10px;right:12px;display:flex;align-items:center;gap:12px;pointer-events:none;opacity:0;transform:translateY(-6px);transition:opacity .35s,transform .35s;z-index:3}
.srm-root.is-zoomed .srm-zoombar{opacity:1;transform:none}
.srm-root.is-zoomed .srm-zoombar .srm-back{pointer-events:auto}
.srm-back{display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(255,255,255,.2);background:rgba(17,19,23,.75);color:#f3f4f6;font:600 13px/1 inherit;padding:9px 14px;border-radius:999px;cursor:pointer;-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)}
.srm-back:hover{background:rgba(34,197,94,.22);border-color:rgba(34,197,94,.6)}
.srm-back:focus-visible{outline:2px solid #22c55e;outline-offset:2px}
.srm-zoomtitle{margin:0;font:700 12px/1.3 inherit;letter-spacing:.14em;text-transform:uppercase;color:#e5e7eb;text-shadow:0 2px 8px rgba(0,0,0,.6);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.srm-zoomtitle span{color:#9ca3af;font-weight:600;letter-spacing:.08em}
.srm-bag{position:absolute;left:16px;bottom:14px;width:64px;display:flex;flex-direction:column;align-items:center;gap:3px;pointer-events:none;z-index:3;opacity:0;transform:translateY(8px) scale(.9);transition:opacity .3s,transform .3s}
.srm-root.has-pick .srm-bag{opacity:1;transform:none}
.srm-bag svg{width:46px;height:58px;filter:drop-shadow(0 6px 10px rgba(0,0,0,.55));overflow:visible}
.srm-bag .srm-bag-dot{fill:var(--pick,#fff)}
.srm-bag-label{font:700 10px/1 inherit;letter-spacing:.14em;text-transform:uppercase;color:#e5e7eb;text-shadow:0 1px 4px rgba(0,0,0,.7);white-space:nowrap}
.srm-bag.is-bounce svg{animation:srm-bounce .55s cubic-bezier(.3,1.6,.5,1)}
@keyframes srm-bounce{0%{transform:scale(1)}30%{transform:scale(1.14,.9)}60%{transform:scale(.95,1.06)}100%{transform:scale(1)}}
.srm-sparks{position:absolute;left:50%;top:26px;width:0;height:0}
.srm-sparks i{position:absolute;left:-1px;top:-5px;width:2px;height:10px;border-radius:2px;background:var(--c,#f3d99b);opacity:0}
.srm-bag.is-sparkle .srm-sparks i{animation:srm-spark .7s ease-out forwards}
@keyframes srm-spark{0%{opacity:1;transform:rotate(var(--a)) translateY(-6px) scaleY(.6)}100%{opacity:0;transform:rotate(var(--a)) translateY(-34px) scaleY(1)}}
@media (max-width:719px){
 .srm-bag{left:auto;right:12px;bottom:auto;top:8px;width:56px}
 .srm-bag svg{width:38px;height:48px}
 .srm-zoombar{right:78px}
}
@media (prefers-reduced-motion:reduce){.srm-bag.is-bounce svg,.srm-bag.is-sparkle .srm-sparks i{animation:none}}
` + PANEL_CSS;
    document.head.appendChild(s);
}

/* ============================ the module ============================ */

/**
 * @param {HTMLElement} container  sized element; the cupboards fill it
 * @param {object} options  { manifestUrl, base, reducedMotion, debug, config,
 *        customizeUrl(productId, hex), onFirstFrame(), onProductsShown(), onModelError(id, err) }
 * @returns controller { destroy(), relayout() }
 */
export async function mountShowroom(container, options = {}) {
    if (!container) throw new Error('mountShowroom: container missing');
    const base = options.base || '';
    const reduced = !!options.reducedMotion;
    const debug = !!options.debug;
    const emit = (name, detail) => container.dispatchEvent(new CustomEvent('showroom:' + name, { detail: detail || {} }));

    // ---------- manifest + config ----------
    let manifest = { products: {} };
    if (options.manifestUrl) {
        const resp = await fetch(options.manifestUrl, { credentials: 'same-origin', cache: 'no-cache' });
        if (!resp.ok) throw new Error(`manifest HTTP ${resp.status}`);
        manifest = await resp.json();
    }
    const products = manifest.products || {};
    const version = String(manifest.version || '1');
    const sr = manifest.showroom || {};
    let cfg = mergeDeep(CUPBOARD_CONFIG, { featured: sr.featured, sections: sr.sections, colorNames: sr.colorNames, variations: sr.variations });
    cfg = mergeDeep(mergeDeep(cfg, sr.cupboards || {}), options.config || {});
    const CB = cfg.cupboard, LT = cfg.lighting, P = cfg.product, DR = cfg.doors;

    const featured = featuredList(cfg, products);
    if (!featured.length) throw new Error('showroom: no featured products');

    // ---------- DOM ----------
    injectStyles();
    if (getComputedStyle(container).position === 'static') container.style.position = 'relative';
    const root = document.createElement('div');
    root.className = 'srm-root';
    root.setAttribute('role', 'region');
    root.setAttribute('aria-label', 'Showroom cupboards');
    const canvas = document.createElement('canvas');
    canvas.className = 'srm-canvas';
    canvas.setAttribute('aria-hidden', 'true');
    root.appendChild(canvas);
    const platesEl = document.createElement('div');
    platesEl.className = 'srm-plates';
    root.appendChild(platesEl);
    const zoombar = document.createElement('div');
    zoombar.className = 'srm-zoombar';
    zoombar.innerHTML = '<button type="button" class="srm-back"><span aria-hidden="true">&larr;</span> All pieces</button><p class="srm-zoomtitle" aria-live="polite"></p>';
    root.appendChild(zoombar);
    const backBtn = zoombar.querySelector('.srm-back');
    const zoomTitle = zoombar.querySelector('.srm-zoomtitle');
    backBtn.tabIndex = -1;
    backBtn.addEventListener('click', () => zoomOut());
    // "Your pick" garment bag (the picked copy flies into it) + thread sparkle
    const bag = document.createElement('div');
    bag.className = 'srm-bag';
    bag.setAttribute('aria-hidden', 'true');
    bag.innerHTML = '<svg viewBox="0 0 46 58"><path d="M23 2a4 4 0 0 1 4 4c0 2-2 3-4 4v3" fill="none" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round"/>' +
        '<path d="M8 14h30l5 8v30a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V22z" fill="#1f2937" stroke="#94a3b8" stroke-width="1.5"/>' +
        '<path d="M23 15v38" stroke="#64748b" stroke-width="1.2" stroke-dasharray="2 2"/>' +
        '<rect x="13" y="38" width="20" height="10" rx="2" fill="#c9a15a"/><circle class="srm-bag-dot" cx="23" cy="27" r="6" stroke="#fff" stroke-width="1.5"/></svg>' +
        '<span class="srm-sparks">' + Array.from({ length: 10 }, (_, i) => `<i style="--a:${i * 36}deg;--c:${['#f3d99b', '#86efac', '#ffffff'][i % 3]}"></i>`).join('') + '</span>' +
        '<span class="srm-bag-label">Your pick</span>';
    root.appendChild(bag);
    const quick = createQuickView(root, { onClose: () => releasePick() });
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
    scene.fog = new THREE.Fog(LT.background, 5, 15);
    const camera = new THREE.PerspectiveCamera(cfg.frame.fov, 1, 0.05, 80);

    scene.add(new THREE.HemisphereLight(0x9aa6b8, 0x0b0c0e, LT.hemi));
    const key = new THREE.DirectionalLight(0xfff4e5, LT.key); key.position.set(1.5, 4, 3); scene.add(key);
    const fill = new THREE.DirectionalLight(0xdbe7ff, LT.fill); fill.position.set(-3, 1.5, 2); scene.add(fill);
    const rim = new THREE.DirectionalLight(0xffffff, LT.rim); rim.position.set(0, 3, -2); scene.add(rim);
    const warm = new THREE.DirectionalLight(LT.interior, LT.warm); warm.position.set(0, 3, 1.2); scene.add(warm);

    const disposables = [];
    const woodTex = woodTexture();
    const glassTex = glassTexture();
    const radial = radialTexture();
    const woodMat = new THREE.MeshStandardMaterial({ map: woodTex, vertexColors: true, roughness: 0.6, metalness: 0 });
    const hangerMat = new THREE.MeshStandardMaterial({ color: 0x8a5a35, roughness: 0.5 });
    const brassMat = new THREE.MeshStandardMaterial({ color: 0xc9a15a, metalness: 0.9, roughness: 0.32 });
    const wireMat = new THREE.MeshStandardMaterial({ color: 0x9ca3af, metalness: 0.85, roughness: 0.3 });
    const darkMat = new THREE.MeshStandardMaterial({ color: 0x2b2f36, metalness: 0.55, roughness: 0.4 });
    const cordMat = new THREE.MeshStandardMaterial({ color: 0x8b7b69, roughness: 0.9 });
    const glassMat = new THREE.MeshBasicMaterial({ map: glassTex, transparent: true, depthWrite: false, side: THREE.DoubleSide });
    const glowMat = new THREE.MeshBasicMaterial({ map: radial, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, opacity: 0 });
    disposables.push(woodTex, glassTex, radial, woodMat, hangerMat, brassMat, wireMat, darkMat, cordMat, glassMat, glowMat);

    function instanced(geo, mat, capacity, count = capacity) {
        const m = new THREE.InstancedMesh(geo, mat, Math.max(1, capacity));
        m.count = count;
        m.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
        const zero = mat4().makeScale(0, 0, 0);
        for (let i = 0; i < capacity; i++) m.setMatrixAt(i, zero);
        scene.add(m);
        return m;
    }
    const ZERO = mat4().makeScale(0, 0, 0);

    // ---------- cupboards ----------
    const N = featured.length;
    const nLocked = featured.filter((f) => f.locked).length;
    const varLists = featured.map((f) => (f.locked ? [] : variationsFor(cfg, f.id)));
    const minPerRow = Math.max(1, Math.min(cfg.variationLayout.perRow, cfg.variationLayout.perRowNarrow));
    const maxRows = Math.max(1, ...varLists.map((v) => Math.ceil(v.length / minPerRow)));
    const RAIL_CAP = N + maxRows;                                 // + extra rails for variation rows
    let geo = cupboardGeometry(cfg, 1);
    const bodyMesh = instanced(geo.body, woodMat, N);
    const doorMesh = instanced(geo.door, woodMat, N * 2);
    const glassMesh = instanced(geo.glass, glassMat, N * 2);
    const handleMesh = instanced(geo.handle, brassMat, N * 2);
    const railMesh = instanced(geo.rail, wireMat, RAIL_CAP, N);
    const glowMesh = instanced(geo.glow, glowMat, N);
    const lockMesh = instanced(geo.lock, brassMat, Math.max(1, nLocked), nLocked);
    glassMesh.renderOrder = 2;
    glowMesh.renderOrder = 1;
    // Veil: darkens everything behind the chosen cupboard (depth-tested, so the
    // cupboard that moved forward stays bright; drawn after the other glass)
    const veilMat = new THREE.MeshBasicMaterial({ color: LT.background, transparent: true, opacity: 0, depthWrite: false, fog: false });
    const veil = new THREE.Mesh(new THREE.PlaneGeometry(80, 50), veilMat);
    veil.renderOrder = 3;
    veil.visible = false;
    scene.add(veil);
    disposables.push(veilMat, veil.geometry);
    const col = new THREE.Color();
    const cups = featured.map((f, i) => {
        const tint = col.set(CB.woodTints[i % CB.woodTints.length]).clone();
        bodyMesh.setColorAt(i, tint);
        doorMesh.setColorAt(i * 2, tint); doorMesh.setColorAt(i * 2 + 1, tint);
        glowMesh.setColorAt(i, col.set(f.locked ? LT.interiorOff : LT.interior));
        return {
            i, f, origin: V3(), fwd: 0,
            door: { a: 0, v: 0, target: 0 }, doorMoving: false,
            box: new THREE.Box3(), group: null, plate: null, lockIdx: -1
        };
    });
    for (const m of [bodyMesh, doorMesh, glowMesh]) if (m.instanceColor) m.instanceColor.needsUpdate = true;
    let li = 0;
    for (const c of cups) if (c.f.locked) c.lockIdx = li++;

    // Brass name plates (HTML: crisp at any size, focusable)
    for (const c of cups) {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'srm-plate' + (c.f.locked ? ' is-locked' : '');
        if (c.f.locked) {
            b.innerHTML = `${escapeHtml(c.f.name)}<span class="srm-soon">Coming soon</span>`;
            b.setAttribute('aria-disabled', 'true');
            b.setAttribute('aria-label', `${c.f.name}: coming soon`);
        } else {
            b.textContent = c.f.name;
            b.setAttribute('aria-label', `Open the ${c.f.name} cupboard`);
        }
        b.addEventListener('mouseenter', () => setHover(c));
        b.addEventListener('mouseleave', () => setHover(null));
        b.addEventListener('focus', () => setHover(c));
        b.addEventListener('blur', () => setHover(null));
        b.addEventListener('click', () => activateCupboard(c));
        platesEl.appendChild(b);
        c.plate = b;
    }

    // ---------- stage: wall, floor, spotlights ----------
    const wallTex = wallTexture();
    wallTex.repeat.set(40 / 1.2, 1);
    const wall = new THREE.Mesh(new THREE.PlaneGeometry(40, 14), new THREE.MeshStandardMaterial({ color: LT.wall, map: wallTex, roughness: 0.95 }));
    wall.position.set(0, 6.5, 0);
    scene.add(wall);
    const floorTex = floorTexture();
    floorTex.repeat.set(40 / 2.4, 30 / 1.2);
    const floor = new THREE.Mesh(new THREE.PlaneGeometry(40, 30), new THREE.MeshStandardMaterial({ color: LT.floor, map: floorTex, roughness: 0.62, metalness: 0.05 }));
    floor.rotation.x = -Math.PI / 2;
    floor.position.set(0, 0, 15);
    scene.add(floor);
    disposables.push(wall.geometry, wall.material, wallTex, floor.geometry, floor.material, floorTex);

    const beamMat = new THREE.MeshBasicMaterial({ color: LT.spotColor, transparent: true, opacity: 0, vertexColors: true, blending: THREE.AdditiveBlending, depthWrite: false, side: THREE.DoubleSide });
    const wallPoolMat = new THREE.MeshBasicMaterial({ color: LT.spotColor, map: radial, transparent: true, opacity: 0, blending: THREE.AdditiveBlending, depthWrite: false });
    const floorPoolMat = wallPoolMat.clone();
    disposables.push(beamMat, wallPoolMat, floorPoolMat);
    const beamGeo = new THREE.ConeGeometry(1, 1, 24, 1, true);
    beamGeo.translate(0, -0.5, 0);                                  // apex at the origin, base at y = -1
    {
        const cols = new Float32Array(beamGeo.attributes.position.count * 4);
        for (let i = 0; i < beamGeo.attributes.position.count; i++) cols.set([1, 1, 1, 0.9 * (1 + beamGeo.attributes.position.getY(i)) + 0.05], i * 4);
        beamGeo.setAttribute('color', new THREE.BufferAttribute(cols, 4));
    }
    const SPOTS = 4;                                                // max columns
    const beams = instanced(beamGeo, beamMat, SPOTS, 0);
    const wallPools = instanced(new THREE.PlaneGeometry(1, 1), wallPoolMat, SPOTS, 0);
    const floorPools = instanced(new THREE.PlaneGeometry(1, 1), floorPoolMat, SPOTS, 0);
    let lightsT = reduced ? 1 : 0;
    function applyLightFade() {
        const k = THREE.MathUtils.smootherstep(lightsT, 0, 1);
        beamMat.opacity = LT.beamOpacity * k;
        wallPoolMat.opacity = LT.wallPoolOpacity * (0.25 + 0.75 * k);
        floorPoolMat.opacity = LT.floorPoolOpacity * (0.25 + 0.75 * k);
        glowMat.opacity = 0.15 + 0.85 * k;
    }
    applyLightFade();

    // ---------- products (one group per featured product with a model) ----------
    const hookMesh = instanced(hookGeometry(P.hookLength), wireMat, 1, 0);
    const loopMesh = instanced(loopGeometry(0.003), wireMat, 1, 0);
    const cordMesh = instanced(new THREE.CylinderGeometry(1, 1, 1, 8), cordMat, 1, 0);
    const clipMeshes = {};
    const groups = [];
    for (const c of cups) {
        if (c.f.locked) continue;
        const info = normaliseRack(c.f.conf);
        const g = { id: c.f.id, conf: c.f.conf, rack: info.rack, type: info.type, info, cup: c, parts: [], hanger: null, ready: false, pieces: [] };
        c.group = g;
        groups.push(g);
    }
    // Shared hook / cord pools: one slot per piece that can exist at the same time
    const pool = { hook: 0, cord: 0, ring: 0, clip: 0, hook2: 0 };
    function resizePool(mesh, cap) {
        const m = new THREE.InstancedMesh(mesh.geometry, mesh.material, Math.max(1, cap));
        m.count = cap;
        m.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
        for (let i = 0; i < cap; i++) m.setMatrixAt(i, ZERO);
        scene.remove(mesh);
        mesh.dispose();
        scene.add(m);
        return m;
    }
    function makePiece(g, hex, label) {
        const p = {
            group: g, hex, colorName: label || (cfg.colorNames[hex] || hex.toUpperCase()),
            s: 1, w: 0, h: 0, local: V3(), pos: V3(), centreBelow: 0, cordLen: 0,
            swing: { a: 0, v: 0 }, tilt: { a: 0, v: 0 }, yaw: { a: 0, v: 0, target: 0 },
            k: 1, kTarget: 1, slot: 0, hIdx: -1, cIdx: -1, kIdx: -1, box: new THREE.Box3()
        };
        if (g.type === 'hanger') p.hIdx = pool.hook++;
        else { p.cIdx = pool.cord++; p.kIdx = g.rack.clip === 'hook' ? pool.hook2++ : pool[g.rack.clip]++; }
        return p;
    }
    for (const g of groups) {
        g.vars = varLists[g.cup.i];
        g.capacity = 1 + g.vars.length;
        const gp = makePiece(g, g.vars[0].value, g.vars[0].label);              // grid copy: first colour, slot 0
        gp.slot = 0;
        g.pieces.push(gp);
        g.vars.forEach((v, j) => {                                               // variation copies: slots 1..n, hidden
            const p = makePiece(g, v.value, v.label);
            p.slot = 1 + j; p.k = p.kTarget = 0; p.variation = v; p.delay = 0;
            g.pieces.push(p);
        });
    }
    const hookM = pool.hook ? resizePool(hookMesh, pool.hook) : hookMesh;
    const loopM = pool.cord ? resizePool(loopMesh, pool.cord) : loopMesh;
    const cordM = pool.cord ? resizePool(cordMesh, pool.cord) : cordMesh;
    for (const kind of ['ring', 'clip', 'hook']) {
        const n = kind === 'hook' ? pool.hook2 : pool[kind];
        if (n) clipMeshes[kind] = instanced(clipGeometry(kind), kind === 'clip' ? darkMat : wireMat, n);
    }
    disposables.push(hookM.geometry, loopM.geometry, cordM.geometry);

    // ---------- layout ----------
    let L = null, geoW = 1;
    let camPos = V3(), camLook = V3();
    const gridCam = V3(), gridLook = V3();
    let interior = cupboardInterior(cfg, 1);
    // zoom state: zoomT 0 (grid) .. 1 (close-up), zoomDir +1 in / -1 out / 0 still
    let zoomCup = null, zoomView = null, zoomT = 0, zoomDir = 0;
    const zoomInsets = { right: 0, bottom: 0 };                 // px covered by the quick view
    let reframe = null;                                          // { cam, look, t }: camera slide for the quick view
    const viewSize = () => ({ width: Math.max(1, root.clientWidth), height: Math.max(1, root.clientHeight) });
    function layoutScene() {
        const w = Math.max(1, root.clientWidth), h = Math.max(1, root.clientHeight);
        renderer.setSize(w, h, false);
        L = computeGridLayout(cfg, { width: w, height: h }, N);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        if (Math.abs(L.W - geoW) > 0.001) {                    // cupboard width changed: rebuild shared geometry
            const old = geo;
            geo = cupboardGeometry(cfg, L.W);
            bodyMesh.geometry = geo.body; doorMesh.geometry = geo.door; glassMesh.geometry = geo.glass;
            handleMesh.geometry = geo.handle; railMesh.geometry = geo.rail; glowMesh.geometry = geo.glow; lockMesh.geometry = geo.lock;
            for (const k of ['body', 'door', 'glass', 'handle', 'rail', 'glow', 'lock']) old[k].dispose();
            geoW = L.W;
        }
        interior = cupboardInterior(cfg, L.W);
        for (const c of cups) {
            const cell = L.cells[c.i];
            c.cell = cell;
            c.origin.set(cell.x, cell.y, c.fwd);
        }
        for (const g of groups) { fitGridPiece(g); layoutVariationPieces(g); }
        // spotlights: one per column, a pool on the wall above, contact glow on the floor
        const colsX = [...new Set(L.cells.map((c) => +c.x.toFixed(4)))].slice(0, SPOTS);
        const top = (L.rows - 1) * (L.Ht + CB.gapY) + L.Ht;
        const m = mat4(), q = new THREE.Quaternion(), down = V3(0, -1, 0);
        colsX.forEach((sx, i) => {
            const from = V3(sx, top + 1.4, L.D + 1.3), to = V3(sx, top * 0.45, L.D * 0.5);
            const dir = to.clone().sub(from); const len = dir.length();
            q.setFromUnitVectors(down, dir.normalize());
            beams.setMatrixAt(i, m.compose(from, q, V3(L.W * 0.42, len, L.W * 0.42)));
            wallPools.setMatrixAt(i, m.compose(V3(sx, top + 0.25, 0.004), new THREE.Quaternion(), V3(L.W * 1.6, 1.1, 1)));
            q.setFromAxisAngle(V3(1, 0, 0), -Math.PI / 2);
            floorPools.setMatrixAt(i, m.compose(V3(sx, 0.003, L.D + 0.25), q, V3(L.W * 1.5, 0.8, 1)));
        });
        for (const mm of [beams, wallPools, floorPools]) { mm.count = colsX.length; mm.instanceMatrix.needsUpdate = true; mm.computeBoundingSphere(); }
        gridCam.set(0, L.camY, L.camZ);
        gridLook.set(0, L.lookY, L.lookZ);
        if (zoomCup) zoomView = computeZoomView(cfg, L, zoomCup.cell, { width: w, height: h }, zoomInsets);
        poseCamera();
        for (const c of cups) writeCupboard(c);
        writeExtraRails();
        for (const g of groups) for (const p of g.pieces) writePiece(p);
        flush(true);
        platesDirty = true;
        if (debug) buildDebug();
    }
    function applyCamera() {
        camera.position.copy(camPos);
        camera.lookAt(camLook);
        camera.updateMatrixWorld();
    }
    /** Camera, forward offset, veil and fog for the current zoom progress. */
    function poseCamera() {
        const e = THREE.MathUtils.smootherstep(zoomT, 0, 1);
        if (zoomView && e > 0) { camPos.lerpVectors(gridCam, zoomView.cam, e); camLook.lerpVectors(gridLook, zoomView.look, e); }
        else { camPos.copy(gridCam); camLook.copy(gridLook); }
        if (reframe && zoomView && e > 0) {                    // quick view opened / closed: slide aside
            const k = THREE.MathUtils.smootherstep(reframe.t, 0, 1);
            camPos.lerpVectors(reframe.cam, camPos, k);
            camLook.lerpVectors(reframe.look, camLook, k);
        }
        applyCamera();
        if (zoomCup) { zoomCup.fwd = cfg.zoom.forward * e; zoomCup.origin.z = zoomCup.fwd; }
        veil.visible = e > 0.001;
        veilMat.opacity = cfg.zoom.veil * smooth(e, 0, 0.7);
        veil.position.set(camLook.x, camLook.y, L.D + 0.25);
        const dist = camPos.distanceTo(camLook);
        scene.fog.near = dist + LT.fog[0];
        scene.fog.far = dist + LT.fog[1] * (1 - 0.4 * e);
    }
    /** Variation copies of a product laid out on rails inside its cupboard. */
    function layoutVariationPieces(g) {
        const V = layoutVariations(cfg, g.info, interior, g.vars.length, L.narrow);
        g.varLayout = V;
        for (let j = 1; j < g.pieces.length; j++) {
            const p = g.pieces[j], sl = V.slots[j - 1];
            p.s = V.s; p.w = V.w; p.h = V.h; p.centreBelow = V.centreBelow; p.cordLen = V.cordLen;
            p.local.set(sl.x, sl.y, interior.railZ);
        }
    }
    /** Extra rails (variation rows 2..n) of the zoomed cupboard; they grow from the middle. */
    function writeExtraRails() {
        const V = zoomCup && zoomCup.group ? zoomCup.group.varLayout : null;
        const k = smooth(zoomT, 0.2, 0.7);
        for (let r = 0; r < RAIL_CAP - N; r++) {
            if (V && r + 1 < V.rails.length && k > 0.001) {
                const O = zoomCup.origin;
                M.A.compose(_v.set(O.x, O.y + V.rails[r + 1], O.z + interior.railZ), _q.identity(), _s.set(k, 1, 1));
                railMesh.setMatrixAt(N + r, M.A);
            } else railMesh.setMatrixAt(N + r, ZERO);
        }
        railMesh.count = RAIL_CAP;
        dirty.add(railMesh);
    }
    function fitGridPiece(g) {
        const p = g.pieces[0];
        const fit = fitProduct(cfg, g.info, interior);
        p.s = fit.s; p.w = fit.w; p.h = fit.h; p.centreBelow = fit.centreBelow; p.cordLen = fit.cordLen;
        p.local.set(0, interior.railY, interior.railZ);
    }

    // ---------- matrices ----------
    const M = { P: mat4(), S: mat4(), A: mat4(), B: mat4(), T: mat4(), O: mat4(), Z: mat4(), R: mat4() };
    const _e = new THREE.Euler(), _v = V3(), _q = new THREE.Quaternion(), _s = V3(), _h = V3(), _c = V3(), _d = V3(), _r = V3();
    const dirty = new Set();
    function writeCupboard(c) {
        const d = geo.dims, O = c.origin;
        M.T.makeTranslation(O.x, O.y, O.z);
        bodyMesh.setMatrixAt(c.i, M.T);
        M.A.makeTranslation(O.x, O.y + interior.railY, O.z + interior.railZ);
        railMesh.setMatrixAt(c.i, M.A);
        glowMesh.setMatrixAt(c.i, M.T);
        const cy = O.y + d.pl + d.fr + 0.003 + d.dh / 2;
        for (const side of [-1, 1]) {                          // -1 = left door (hinge on the left)
            const idx = c.i * 2 + (side < 0 ? 0 : 1);
            _h.set(O.x + side * (d.W / 2 - d.fr - 0.0015), cy, O.z + d.D - d.dt / 2);
            M.R.makeRotationY(side < 0 ? -c.door.a : c.door.a);
            M.A.makeTranslation(_h.x, _h.y, _h.z).multiply(M.R);
            M.B.copy(M.A).multiply(M.T.makeTranslation(-side * d.dw / 2, 0, 0));
            doorMesh.setMatrixAt(idx, M.B);
            glassMesh.setMatrixAt(idx, M.B);
            M.B.copy(M.A).multiply(M.T.makeTranslation(-side * (d.dw - 0.045), -d.dh * 0.04, d.dt / 2));
            handleMesh.setMatrixAt(idx, M.B);
        }
        if (c.lockIdx >= 0) {
            M.T.makeTranslation(O.x, cy - d.dh * 0.04, O.z + d.D);
            lockMesh.setMatrixAt(c.lockIdx, M.T);
        }
        c.box.set(V3(O.x - d.W / 2, O.y, O.z), V3(O.x + d.W / 2, O.y + L.Ht, O.z + d.D));
        for (const m of [bodyMesh, railMesh, glowMesh, doorMesh, glassMesh, handleMesh, lockMesh]) dirty.add(m);
    }
    function zeroPiece(p) {
        const g = p.group;
        if (p.hIdx >= 0) { hookM.setMatrixAt(p.hIdx, ZERO); dirty.add(hookM); }
        if (g.hanger) { g.hanger.setMatrixAt(p.slot, ZERO); dirty.add(g.hanger); }
        if (p.cIdx >= 0) {
            loopM.setMatrixAt(p.cIdx, ZERO); cordM.setMatrixAt(p.cIdx, ZERO); dirty.add(loopM); dirty.add(cordM);
            const cm = clipMeshes[g.rack.clip]; if (cm) { cm.setMatrixAt(p.kIdx, ZERO); dirty.add(cm); }
        }
        for (const part of g.parts) { part.mesh.setMatrixAt(p.slot, ZERO); dirty.add(part.mesh); }
        p.box.makeEmpty();
    }
    function piecePosition(p, out) {
        out.copy(p.group.cup.origin).add(p.local);
        if (p.fly) {                                           // quadratic arc: rail -> bag (follows the bag on screen) or back
            const f = p.fly;
            const a = f.from;
            const b = f.dir > 0 ? bagPoint(f.to) : _r.copy(out);
            const e = f.dir > 0 ? THREE.MathUtils.smootherstep(f.t, 0, 1) : 1 - Math.pow(1 - f.t, 3);
            _c.addVectors(a, b).multiplyScalar(0.5).addScaledVector(camera.up, f.arc);
            const u = 1 - e;
            out.set(0, 0, 0).addScaledVector(a, u * u).addScaledVector(_c, 2 * u * e).addScaledVector(b, e * e);
        }
        return out;
    }
    function writePiece(p) {
        const g = p.group;
        if (!g.ready) return;
        if (p.k < 0.002) { zeroPiece(p); return; }
        piecePosition(p, p.pos);
        _e.set(p.tilt.a, 0, p.swing.a, 'XYZ');
        M.P.makeRotationFromEuler(_e).scale(_s.set(p.k, p.k, p.k)).setPosition(p.pos.x, p.pos.y, p.pos.z);
        M.S.makeRotationY(p.yaw.a);
        M.Z.makeScale(p.s, p.s, p.s);
        if (g.type === 'hanger') {
            M.A.multiplyMatrices(M.P, M.S);
            hookM.setMatrixAt(p.hIdx, M.A); dirty.add(hookM);
            M.T.makeTranslation(0, -P.hookLength, 0);
            M.B.multiplyMatrices(M.A, M.T).multiply(M.Z).multiply(g.holder);
            g.hanger.setMatrixAt(p.slot, M.B); dirty.add(g.hanger);
        } else {
            loopM.setMatrixAt(p.cIdx, M.P); dirty.add(loopM);
            const len = Math.max(1e-3, p.cordLen - LOOP_R);
            M.T.compose(_v.set(0, -(p.cordLen + LOOP_R) / 2, 0), _q.identity(), _s.set(CORD_R, len, CORD_R));
            M.A.multiplyMatrices(M.P, M.T);
            cordM.setMatrixAt(p.cIdx, M.A); dirty.add(cordM);
            M.T.makeTranslation(0, -p.cordLen, 0);
            M.A.multiplyMatrices(M.P, M.T).multiply(g.tiltM).multiply(M.S);
            const cm = clipMeshes[g.rack.clip];
            if (cm) { cm.setMatrixAt(p.kIdx, M.A); dirty.add(cm); }
            M.B.multiplyMatrices(M.A, M.Z).multiply(g.holder);
        }
        for (const part of g.parts) {
            M.O.multiplyMatrices(M.B, part.local);
            part.mesh.setMatrixAt(p.slot, M.O);
            dirty.add(part.mesh);
        }
        const cy = p.pos.y - p.centreBelow * p.k;
        p.box.set(V3(p.pos.x - p.w * p.k / 2, cy - p.h * p.k / 2, p.pos.z - 0.15), V3(p.pos.x + p.w * p.k / 2, cy + p.h * p.k / 2, p.pos.z + 0.15));
    }
    function flush(spheres) {
        for (const m of dirty) {
            m.instanceMatrix.needsUpdate = true;
            if (spheres) m.computeBoundingSphere();
        }
        dirty.clear();
    }

    // ---------- model loading ----------
    const models = createModelLoader({ base, version, renderer });
    let productsShown = false, destroyed = false;
    async function loadGroup(g) {
        let model = null;
        try { model = await models.load(g.conf.glb); } catch (err) {
            if (options.onModelError) options.onModelError(g.id, err);
            console.warn(`[Welzee Showroom] ${g.id}: model unavailable.`, err);
        }
        if (destroyed) return;
        if (!model) { lockCupboard(g.cup); return; }
        toBaseFrame(model, g.conf);
        if (g.type === 'cord' && g.rack.gatherHandles) gatherHandles(model, num(g.conf.targetHeight, 0.5));
        const cap = g.capacity || 1;
        g.parts = buildProductParts(model, g.conf, cap, disposables);
        for (const part of g.parts) scene.add(part.mesh);
        if (g.type === 'hanger') {
            const A = g.rack.hangerProfile[0];
            g.holder = mat4().makeTranslation(-A[0], -A[1], -num(g.rack.hangerZ, 0));
            const hg = hangerGeometry(g.rack);
            g.hanger = new THREE.InstancedMesh(hg, g.rack.hangerStyle === 'metal' ? darkMat : hangerMat, cap);
            g.hanger.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
            scene.add(g.hanger);
            disposables.push(hg);
        } else {
            g.tiltM = mat4().makeRotationX(THREE.MathUtils.degToRad(num(g.rack.level, 0) + num(g.rack.tilt, 0)));
            g.holder = mat4().makeTranslation(-g.rack.attach[0], -g.rack.attach[1], -g.rack.attach[2]);
        }
        g.ready = true;
        setGroupCount(g, 1);
        writePiece(g.pieces[0]);
        flush(true);
        if (!reduced) g.pieces[0].swing.v += 0.3;
        wake();
        if (!productsShown) {
            productsShown = true;
            requestAnimationFrame(() => { if (options.onProductsShown) options.onProductsShown(); });
        }
        if (debug) buildDebug();
    }
    function setGroupCount(g, n) {
        const col2 = new THREE.Color();
        for (const part of g.parts) {
            part.mesh.count = n;
            part.mesh.visible = n > 0;
            g.pieces.forEach((p) => { if (p.slot < n) part.mesh.setColorAt(p.slot, col2.set(part.policy || p.hex)); });
            if (part.mesh.instanceColor) part.mesh.instanceColor.needsUpdate = true;
            dirty.add(part.mesh);
        }
        if (g.hanger) { g.hanger.count = n; g.hanger.visible = n > 0; dirty.add(g.hanger); }
    }
    function lockCupboard(c) {                                 // model failed to load: show it as coming soon
        if (c.f.locked) return;
        c.f.locked = true;
        glowMesh.setColorAt(c.i, col.set(LT.interiorOff));
        if (glowMesh.instanceColor) glowMesh.instanceColor.needsUpdate = true;
        c.plate.classList.add('is-locked');
        c.plate.innerHTML = `${escapeHtml(c.f.name)}<span class="srm-soon">Coming soon</span>`;
        c.plate.setAttribute('aria-disabled', 'true');
        wake();
    }
    function loadAll() {
        const order = groups.slice();
        let next = 0;
        const worker = async () => {
            while (next < order.length && !destroyed) {
                const g = order[next++];
                try { await loadGroup(g); } catch (err) { console.warn('[Welzee Showroom]', g.id, err); lockCupboard(g.cup); }
            }
        };
        worker(); worker();                                    // two downloads at a time
        // nothing loadable at all: still reveal the (locked) cupboards
        if (!groups.length) requestAnimationFrame(() => { productsShown = true; if (options.onProductsShown) options.onProductsShown(); });
    }

    // ---------- plates (HTML, projected) ----------
    let platesDirty = true;
    const _p = V3();
    function updatePlates() {
        platesDirty = false;
        const w = root.clientWidth, h = root.clientHeight;
        for (const c of cups) {
            _p.set(c.origin.x, c.origin.y + CB.plinth * 0.5, c.origin.z + CB.depth - 0.03).project(camera);
            c.plate.style.transform = `translate(${((_p.x + 1) / 2 * w).toFixed(1)}px, ${((1 - _p.y) / 2 * h).toFixed(1)}px) translate(-50%, -50%)`;
        }
    }

    // ---------- debug outlines ----------
    let debugLines = null;
    function buildDebug() {
        if (!L) return;
        const pts = [];
        const box = (b) => {
            const a = b.min, c = b.max, xs = [a.x, c.x], ys = [a.y, c.y], zs = [a.z, c.z];
            for (const y of ys) for (const z of zs) pts.push(xs[0], y, z, xs[1], y, z);
            for (const x of xs) for (const z of zs) pts.push(x, ys[0], z, x, ys[1], z);
            for (const x of xs) for (const y of ys) pts.push(x, y, zs[0], x, y, zs[1]);
        };
        for (const c of cups) {
            box(c.box);                                                            // cupboard
            const O = c.origin;
            box(new THREE.Box3(V3(O.x + interior.x0, O.y + interior.y0, O.z + interior.z0), V3(O.x + interior.x1, O.y + interior.y1, O.z + interior.z1)));
        }
        for (const g of groups) for (const p of g.pieces) if (!p.box.isEmpty()) box(p.box);
        // safe area on the front plane: heading band (top) and floor band (bottom)
        const yTop = L.lookY + (0.5 - cfg.frame.top) * L.visH, yBot = L.lookY - (0.5 - cfg.frame.bottom) * L.visH;
        pts.push(-L.visW / 2, yTop, L.D, L.visW / 2, yTop, L.D, -L.visW / 2, yBot, L.D, L.visW / 2, yBot, L.D);
        if (debugLines) { scene.remove(debugLines); debugLines.geometry.dispose(); }
        const g2 = new THREE.BufferGeometry();
        g2.setAttribute('position', new THREE.Float32BufferAttribute(pts, 3));
        debugLines = new THREE.LineSegments(g2, new THREE.LineBasicMaterial({ color: 0xff3d9a, fog: false, toneMapped: false }));
        scene.add(debugLines);
        wake();
    }

    // ---------- interaction ----------
    let hovered = null, opened = null, interacted = false;
    const raycaster = new THREE.Raycaster();
    const ndc = new THREE.Vector2();
    function rayAt(cx, cy) {
        const r = canvas.getBoundingClientRect();
        ndc.set(((cx - r.left) / r.width) * 2 - 1, -((cy - r.top) / r.height) * 2 + 1);
        raycaster.setFromCamera(ndc, camera);
        return raycaster.ray;
    }
    function pickCupboard(cx, cy) {
        const ray = rayAt(cx, cy);
        let best = null, bd = Infinity;
        for (const c of cups) {
            const hit = ray.intersectBox(c.box, _v);
            if (hit) { const d = hit.distanceTo(ray.origin); if (d < bd) { bd = d; best = c; } }
        }
        return best;
    }
    function poke(p, strength = 1) {
        if (!p) return;
        const kick = (reduced ? 0.25 : 0.6) * strength;
        const dir = p.swing.v === 0 ? 1 : Math.sign(p.swing.v);
        p.swing.v = clamp(p.swing.v + kick * dir, -1, 1);
        p.tilt.v = clamp(p.tilt.v + (reduced ? 0.06 : 0.22) * strength, -0.4, 0.4);
        wake();
    }
    function doorTarget(c) {
        if (c.f.locked) return 0;
        if (zoomCup) return c === zoomCup && zoomDir >= 0 ? cfg.zoom.doors : 0;
        if (c === opened) return DR.open;
        if (c === hovered) return DR.hover;
        return 0;
    }
    function refreshDoors() {
        for (const c of cups) {
            const t = doorTarget(c);
            if (t !== c.door.target) { c.door.target = t; c.doorMoving = true; }
            c.plate.classList.toggle('is-hover', c === hovered && !c.f.locked);
        }
        root.classList.toggle('is-hovering', !!hovered && !hovered.f.locked);
        wake();
    }
    function setHover(c) {
        if (zoomCup) c = null;                                 // no peeking while zoomed
        if (c === hovered) return;
        hovered = c;
        if (c && !c.f.locked && c.group) poke(c.group.pieces[0], 0.8);
        refreshDoors();
    }
    function firstInteraction() {
        if (interacted) return;
        interacted = true;
        emit('interact');
    }
    function activateCupboard(c) {
        firstInteraction();
        if (!c || c.f.locked) return;                          // coming soon: no action
        if (c.group && c.group.ready) { zoomIn(c); return; }
        opened = opened === c ? null : c;                      // model still loading: just open the doors
        if (c.group) poke(c.group.pieces[0], 1);
        refreshDoors();
    }

    // ---------- zoom: close-up of one cupboard with its variations ----------
    let varHover = null, armed = null;
    function zoomIn(c) {
        if (zoomCup && zoomCup !== c) return;
        if (zoomCup === c && zoomDir >= 0) return;
        const g = c.group;
        zoomCup = c; zoomDir = 1; opened = null; hovered = null;
        layoutVariationPieces(g);
        zoomView = computeZoomView(cfg, L, c.cell, viewSize(), zoomInsets);
        setGroupCount(g, g.capacity);
        showVariations(g, true);
        root.classList.add('is-zoomed');
        zoomTitle.innerHTML = `${escapeHtml(c.f.name)} <span>&middot; ${g.vars.length} colour${g.vars.length === 1 ? '' : 's'}</span>`;
        backBtn.tabIndex = 0;
        refreshDoors();
        emit('zoom', { id: g.id });
        later(() => { if (zoomCup === c && zoomDir >= 0) backBtn.focus({ preventScroll: true }); }, reduced ? 0 : cfg.zoom.duration * 0.6);
        wake();
    }
    function zoomOut() {
        if (!zoomCup || zoomDir < 0) return;
        const c = zoomCup;
        const hadFocus = root.contains(document.activeElement);
        releasePick(true);                                     // the pick goes straight back to its rail
        zoomDir = -1;
        setVarHover(null);
        if (armed) { armed.yaw.target = 0; armed = null; }
        showVariations(c.group, false);
        for (const g of groups) if (g !== c.group && g.ready) setGroupCount(g, 1);   // bring the others back
        root.classList.remove('is-zoomed');
        backBtn.tabIndex = -1;
        refreshDoors();
        emit('grid');
        if (hadFocus) later(() => { if (!zoomCup) c.plate.focus({ preventScroll: true }); }, reduced ? 0 : cfg.zoom.duration);
        wake();
    }
    /** Grid copy out / variations in (staggered pop), or the reverse. */
    function showVariations(g, on) {
        const D = reduced ? 0 : cfg.zoom.duration / 1000;
        g.pieces.forEach((p, j) => {
            if (j === 0) { p.kTarget = on ? 0 : 1; p.delay = on ? 0 : D * 0.35; return; }
            p.kTarget = on ? 1 : 0;
            p.delay = on ? (reduced ? 0 : D * 0.4 + (j - 1) * cfg.variationLayout.stagger / 1000) : 0;
            p.popped = false;
            if (!on) p.yaw.target = 0;
        });
    }
    function pickVariation(cx, cy) {
        if (!zoomCup || zoomDir < 0 || zoomT < 0.85) return null;
        const ray = rayAt(cx, cy);
        let best = null, bd = Infinity;
        const ps = zoomCup.group.pieces;
        for (let j = 1; j < ps.length; j++) {
            const p = ps[j];
            if (p.k < 0.6 || p.box.isEmpty()) continue;
            const hit = ray.intersectBox(p.box, _v);
            if (hit) { const d = hit.distanceTo(ray.origin); if (d < bd) { bd = d; best = p; } }
        }
        return best;
    }
    function setVarHover(p) {
        if (p === varHover) return;
        if (varHover && varHover !== armed) varHover.yaw.target = 0;
        varHover = p;
        root.classList.toggle('is-hovering', !!p);
        if (p) { poke(p, 0.8); p.yaw.target = Math.PI; }       // swing and turn to the back
        wake();
    }
    function tapVariation(p) { pickPiece(p); }

    // ---------- pick: fly into the bag, quick view ----------
    let picked = null;
    const customizeUrl = typeof options.customizeUrl === 'function'
        ? options.customizeUrl
        : (id, hex) => `${base}custom_order_3d.php?product=${encodeURIComponent(id)}&color=${encodeURIComponent(hex)}`;
    /** World point in front of the camera that projects onto the bag's centre. */
    function bagPoint(out) {
        const r = canvas.getBoundingClientRect(), b = bag.getBoundingClientRect();
        const cx = b.left + b.width / 2, cy = b.top + Math.min(b.height, 58) * 0.45;
        ndc.set(((cx - r.left) / r.width) * 2 - 1, -((cy - r.top) / r.height) * 2 + 1);
        _d.set(ndc.x, ndc.y, 0.5).unproject(camera).sub(camera.position).normalize();
        return out.copy(camera.position).addScaledVector(_d, cfg.pick.bagDepth * camPos.distanceTo(camLook));
    }
    function startFlight(p, dir) {
        const view = zoomView ? zoomView.visH : 1;
        const from = dir > 0 ? piecePosition(p, V3()) : bagPoint(V3());     // out of the bag: start at the bag
        p.fly = { dir, t: 0, dur: (dir > 0 ? cfg.pick.flight : cfg.pick.back) / 1000, from, to: V3(), arc: cfg.pick.arc * view * (dir > 0 ? 1 : 0.6) };
        p.swing.a = p.swing.v = p.tilt.a = p.tilt.v = 0;
        if (dir > 0) p.yaw.target = p.yaw.a + Math.PI * 2;      // one turn on the way
    }
    function landInBag(p) {
        bag.style.setProperty('--pick', p.hex);
        if (reduced) return;
        for (const cls of ['is-bounce', 'is-sparkle']) { bag.classList.remove(cls); void bag.offsetWidth; bag.classList.add(cls); }
    }
    function pickPiece(p) {
        if (!p || p.fly || p === picked) return;
        firstInteraction();
        if (picked) sendBack(picked);
        picked = p;
        if (varHover === p) { varHover = null; root.classList.remove('is-hovering'); }
        root.classList.add('has-pick');
        if (reduced) { p.k = p.kTarget = 0; writePiece(p); landInBag(p); }
        else { startFlight(p, 1); p.kTarget = p.k; }
        const g = p.group;
        quick.open({ kicker: 'Your pick', name: g.cup.f.name, hex: p.hex, colorName: p.colorName, href: customizeUrl(g.id, p.hex) });
        setQuickInsets(true);
        emit('pick', { product: g.id, color: p.hex });
        emit('pull', { product: g.id, color: p.hex });
        wake();
    }
    function sendBack(p, instant) {
        if (!p) return;
        if (reduced || instant) { p.fly = null; p.k = p.kTarget = 1; p.yaw.a = p.yaw.target = 0; writePiece(p); return; }
        if (p.fly && p.fly.dir > 0) {                          // still flying in: turn around from where it is
            const from = piecePosition(p, V3());
            p.fly = { dir: -1, t: 0, dur: cfg.pick.back / 1000, from, to: V3(), arc: 0.05 };
        } else startFlight(p, -1);
        p.yaw.a = p.yaw.a % (Math.PI * 2); p.yaw.target = 0;
    }
    /** Quick view closed (button, Esc, tap outside): the copy returns to its rail. */
    function releasePick(instant) {
        if (!picked && !quick.isOpen()) return;
        const p = picked;
        picked = null;
        sendBack(p, instant);
        quick.close();
        root.classList.remove('has-pick');
        setQuickInsets(false);
        emit('release');
        wake();
    }
    /** Keep the zoomed cupboard clear of the quick view: side panel (wide) or bottom sheet (phones). */
    function setQuickInsets(on) {
        const w = root.clientWidth;
        const right = on && w >= 720 ? Math.min(300, w - 32) + 32 : 0;
        const bottom = on && w < 720 ? quick.el.offsetHeight + 24 : 0;
        if (right === zoomInsets.right && bottom === zoomInsets.bottom) return;
        if (zoomCup && zoomT > 0) reframe = { cam: camPos.clone(), look: camLook.clone(), t: reduced ? 1 : 0 };
        zoomInsets.right = right; zoomInsets.bottom = bottom;
        if (zoomCup) zoomView = computeZoomView(cfg, L, zoomCup.cell, viewSize(), zoomInsets);
        wake();
    }

    let down = null;
    canvas.addEventListener('pointerdown', (e) => {
        if (e.button !== undefined && e.button > 0) return;
        down = { x: e.clientX, y: e.clientY, id: e.pointerId, type: e.pointerType || 'mouse' };
    });
    canvas.addEventListener('pointermove', (e) => {
        if ((e.pointerType || 'mouse') !== 'mouse' || down) return;
        if (zoomCup) setVarHover(pickVariation(e.clientX, e.clientY));
        else setHover(pickCupboard(e.clientX, e.clientY));
    });
    canvas.addEventListener('pointerleave', (e) => { if ((e.pointerType || 'mouse') === 'mouse') { setHover(null); setVarHover(null); } });
    canvas.addEventListener('pointercancel', () => { down = null; });
    canvas.addEventListener('pointerup', (e) => {
        if (!down || e.pointerId !== down.id) return;
        const moved = Math.hypot(e.clientX - down.x, e.clientY - down.y) > TAP_SLOP * 2;
        down = null;
        if (moved) return;                                     // a scroll / swipe, not a tap
        if (zoomCup) {
            if (zoomDir < 0) return;
            const p = pickVariation(e.clientX, e.clientY);
            if (p) { tapVariation(p, e.pointerType || 'mouse'); return; }
            if (quick.isOpen()) { releasePick(); return; }        // first tap outside closes the quick view
            if (!pickCupboard(e.clientX, e.clientY)) zoomOut();   // tap on the dimmed stage: back to the grid
            return;
        }
        const c = pickCupboard(e.clientX, e.clientY);
        if (c) activateCupboard(c);
        else if (opened) { opened = null; refreshDoors(); }
    });
    const onDocKey = (e) => {
        if (e.key !== 'Escape') return;
        if (quick.isOpen()) releasePick();
        else if (zoomCup) zoomOut();
        else if (opened) { opened = null; refreshDoors(); }
    };
    document.addEventListener('keydown', onDocKey);

    // ---------- loop ----------
    let running = false, visible = true, last = 0, raf = 0, introStart = 0, firstFrame = false, hudT = 0, frames = 0, fps = 0;
    const timers = new Set();
    const later = (fn, ms) => { const t = setTimeout(() => { timers.delete(t); if (!destroyed) fn(); }, ms); timers.add(t); };
    function wake() {
        if (destroyed) return;
        if (!running && visible && !document.hidden) {
            running = true;
            last = performance.now();
            raf = requestAnimationFrame(tick);
        }
    }
    const DK = DR.stiffness, DC = 2 * DR.damping * Math.sqrt(DR.stiffness);
    function tick(now) {
        if (destroyed) return;
        if (!visible || document.hidden) { running = false; return; }
        const dt = Math.min(0.033, Math.max(0, (now - last) / 1000));
        last = now;
        let active = false;

        // intro: lights fade up, then a door peek ripples across the cupboards
        if (!introStart && productsShown) {
            introStart = now;
            if (!reduced) cups.forEach((c, i) => {
                if (c.f.locked) return;
                later(() => { if (!hovered && !opened) { c.door.target = cfg.intro.peek; c.doorMoving = true; poke(c.group && c.group.pieces[0], 0.5); wake(); } }, 500 + i * cfg.intro.stagger);
                later(() => { refreshDoors(); }, 500 + i * cfg.intro.stagger + cfg.intro.hold);
            });
        }
        if (introStart && lightsT < 1) { lightsT = Math.min(1, lightsT + dt / 0.8); applyLightFade(); active = true; }

        // doors (spring: a little overshoot like a real cabinet door)
        for (const c of cups) {
            if (!c.doorMoving) continue;
            const d = c.door;
            if (reduced) { d.a = d.target; d.v = 0; }
            else for (let i = 0; i < 2; i++) { d.v += (-DK * (d.a - d.target) - DC * d.v) * dt / 2; d.a += d.v * dt / 2; }
            if (Math.abs(d.a - d.target) < 0.0008 && Math.abs(d.v) < 0.002) { d.a = d.target; d.v = 0; c.doorMoving = false; }
            writeCupboard(c);
            active = true;
        }

        // zoom in / out
        let spheres = false;
        if (zoomDir !== 0 && zoomCup) {
            const c = zoomCup;
            zoomT = clamp(zoomT + zoomDir * dt / (reduced ? 0.001 : cfg.zoom.duration / 1000), 0, 1);
            if (zoomDir < 0 && zoomT <= 0) {                     // back on the grid
                zoomDir = 0; zoomCup = null; zoomView = null;
                c.fwd = 0; c.origin.z = 0;
                setGroupCount(c.group, 1);
                platesDirty = true;
            } else if (zoomDir > 0 && zoomT >= 1) {              // arrived: hide the products behind the veil
                zoomDir = 0;
                for (const g of groups) if (g !== c.group && g.ready) setGroupCount(g, 0);
            }
            poseCamera();
            writeCupboard(c);
            writeExtraRails();
            for (const p of c.group.pieces) writePiece(p);
            spheres = true;
            active = true;
        }

        // camera slide for the quick view
        if (reframe) {
            reframe.t = Math.min(1, reframe.t + dt / (cfg.pick.reframe / 1000));
            if (zoomDir === 0) poseCamera();
            if (reframe.t >= 1) reframe = null;
            active = true;
        }

        // hanging pieces (swing, turn, pop in / out, fly to the bag and back)
        for (const g of groups) {
            if (!g.ready) continue;
            for (const p of g.pieces) {
                if (p.fly) {
                    const f = p.fly;
                    f.t = Math.min(1, f.t + dt / f.dur);
                    const e = f.dir > 0 ? f.t * f.t : 1 - Math.pow(1 - f.t, 2);
                    p.k = p.kTarget = f.dir > 0 ? 1 + (cfg.pick.endScale - 1) * e : cfg.pick.endScale + (1 - cfg.pick.endScale) * e;
                    if (f.t >= 1) {
                        p.fly = null;
                        if (f.dir > 0) { p.k = p.kTarget = picked === p ? 0 : 1; if (picked === p) landInBag(p); }
                        else { p.k = p.kTarget = 1; p.yaw.a = p.yaw.target = 0; poke(p, 0.5); }
                    }
                    spheres = true;
                }
                let moving = stepHanging(p, dt) || !!p.fly;
                if (p.k !== p.kTarget) {
                    if (p.delay > 0) p.delay -= dt;
                    else {
                        p.k += (p.kTarget - p.k) * (reduced ? 1 : 1 - Math.exp(-10 * dt));
                        if (Math.abs(p.k - p.kTarget) < 0.003) p.k = p.kTarget;
                        if (!p.popped && p.kTarget > 0 && p.k > 0.4) { p.popped = true; poke(p, 0.45); }
                    }
                    moving = true;
                    spheres = true;
                }
                if (moving) { writePiece(p); active = true; }
            }
        }
        flush(spheres);
        if (platesDirty) updatePlates();

        renderer.render(scene, camera);
        if (!firstFrame) { firstFrame = true; if (options.onFirstFrame) options.onFirstFrame(); }
        if (hud) {
            frames++;
            if (now - hudT > 500) {
                fps = Math.round(frames * 1000 / (now - hudT || 1)); frames = 0; hudT = now;
                const i = renderer.info.render;
                hud.textContent = `draw calls ${i.calls}\ntriangles  ${i.triangles.toLocaleString()}\nfps        ${fps}\ngrid       ${L.cols} x ${L.rows}, cupboard ${L.W.toFixed(2)} m\nview       ${L.visW.toFixed(2)} x ${L.visH.toFixed(2)} m\nloaded     ${groups.filter((g) => g.ready).length}/${groups.length}\nmode       ${zoomCup ? 'zoom ' + zoomCup.f.id + ' ' + zoomT.toFixed(2) : 'grid'}`;
            }
            active = true;
        }
        if (active) raf = requestAnimationFrame(tick);
        else running = false;
    }

    // ---------- size, visibility ----------
    const ro = new ResizeObserver(() => { layoutScene(); wake(); });
    ro.observe(root);
    const io = new IntersectionObserver((list) => { visible = list[0] ? list[0].isIntersecting : true; if (visible) wake(); });
    io.observe(root);
    const onVis = () => { if (!document.hidden) wake(); };
    document.addEventListener('visibilitychange', onVis);

    // ---------- start ----------
    layoutScene();
    wake();
    loadAll();

    return {
        relayout: () => { if (!destroyed) { layoutScene(); wake(); } },
        zoomTo(id) { const c = cups.find((q) => q.f.id === id); if (c) activateCupboard(c); },
        back: () => zoomOut(),
        destroy() {
            if (destroyed) return;
            destroyed = true;
            cancelAnimationFrame(raf);
            for (const t of timers) clearTimeout(t);
            ro.disconnect(); io.disconnect();
            document.removeEventListener('visibilitychange', onVis);
            document.removeEventListener('keydown', onDocKey);
            scene.traverse((o) => { if (o.isInstancedMesh) o.dispose(); });
            for (const k of ['body', 'door', 'glass', 'handle', 'rail', 'glow', 'lock']) geo[k].dispose();
            beamGeo.dispose();
            for (const d of disposables) { try { d.dispose(); } catch (e) { /* ignore */ } }
            if (debugLines) debugLines.geometry.dispose();
            models.dispose();
            renderer.dispose();
            if (renderer.forceContextLoss) renderer.forceContextLoss();
            root.remove();
        }
    };
}

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
}
