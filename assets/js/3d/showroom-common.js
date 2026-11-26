/**
 * assets/js/3d/showroom-common.js
 *
 * Shared by the showroom layouts (showroom.js = wall, showroom-cupboards.js =
 * cupboards): hanger / cord / clip geometry and swing springs (same as rack.js),
 * canvas textures, the model loader (Draco / Meshopt / KTX2 decoders only when a
 * file needs them), per-product instanced parts, and the quick-view panel.
 */

import * as THREE from 'three';
import * as BufferGeometryUtils from 'three/addons/utils/BufferGeometryUtils.js';

/* ============================ constants ============================= */

export const SWING_W = 2 * Math.PI * 0.85;           // pendulum, same as rack.js
export const SWING_ZETA = 0.16;
export const SWING_K = SWING_W * SWING_W;
export const SWING_C = 2 * SWING_ZETA * SWING_W;
export const TILT_K = SWING_K * 1.35;
export const YAW_W = 7;
export const YAW_K = YAW_W * YAW_W;
export const YAW_C = 2 * YAW_W;
export const EPS = 0.0006;
export const HARDWARE_RE = /zipper|metal|hardware|eyelet|puller|button|slider|aglet|teeth|cord_end/i;
export const HEX_RE = /^#[0-9a-fA-F]{6}$/;
export const GLB_PATH_RE = /^assets\/models\/(?:[A-Za-z0-9_-]+\/)?[A-Za-z0-9_-]+\.glb$/;
export const GARMENT_CATEGORIES = new Set(['tees_polos', 'hoodies', 'jackets']);
export const LOOP_R = 0.022;                          // rail loop radius (m); rail radius 0.016
export const LOOP_ARC = Math.PI * 1.35;
export const LOOP_START = Math.PI * 1.5 - LOOP_ARC;   // arc ends straight below the rail
export const CORD_R = 0.0016;
export const TAP_SLOP = 6;                            // px before a press becomes a drag

export const num = (v, d) => (typeof v === 'number' && isFinite(v) ? v : d);
export const mat4 = () => new THREE.Matrix4();
export function placed(geo, m) { geo.applyMatrix4(m); return geo; }

export function mergeDeep(base, extra) {
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

/** Deterministic value in [-1, 1] per index (gentle size variation). */
export function jitter(i) {
    const s = Math.sin(i * 12.9898 + 78.233) * 43758.5453;
    return (s - Math.floor(s)) * 2 - 1;
}

/** Display name without the "Custom " prefix used in the product list. */
export function shortName(name, id) {
    const n = typeof name === 'string' && name ? name : String(id || '');
    const s = n.replace(/^custom\s+/i, '');
    return s.charAt(0).toUpperCase() + s.slice(1);
}

/* ============================ textures ============================== */

export function canvasTexture(w, h, draw, opts = {}) {
    const c = document.createElement('canvas');
    c.width = w; c.height = h;
    draw(c.getContext('2d'), w, h);
    const t = new THREE.CanvasTexture(c);
    t.colorSpace = THREE.SRGBColorSpace;
    if (opts.repeat) { t.wrapS = THREE.RepeatWrapping; t.wrapT = THREE.RepeatWrapping; }
    if (opts.noMips) { t.generateMipmaps = false; t.minFilter = THREE.LinearFilter; }
    return t;
}
export const radialTexture = () => canvasTexture(128, 128, (x, w) => {
    const g = x.createRadialGradient(w / 2, w / 2, 0, w / 2, w / 2, w / 2);
    g.addColorStop(0, 'rgba(255,255,255,1)');
    g.addColorStop(0.45, 'rgba(255,255,255,0.45)');
    g.addColorStop(1, 'rgba(255,255,255,0)');
    x.fillStyle = g; x.fillRect(0, 0, w, w);
});
export const wallTexture = () => canvasTexture(512, 256, (x, w, h) => {
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
export const floorTexture = () => canvasTexture(512, 512, (x, w, h) => {
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
export const sheenTexture = () => canvasTexture(256, 256, (x, w, h) => {
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

/** Rail loop (arc around the rail, ends straight below it), at the origin. */
export function loopGeometry(tube) {
    const g = new THREE.TorusGeometry(LOOP_R, tube, 8, 24, LOOP_ARC);
    return placed(g, mat4().makeRotationFromEuler(new THREE.Euler(0, Math.PI / 2, LOOP_START)));
}

/** Garment hook in spin space: rail loop + wire neck down to the hanger centre + nut. */
export function hookGeometry(hookLength) {
    const wire = 0.0035;
    const neckLen = hookLength - LOOP_R;
    const neck = placed(new THREE.CylinderGeometry(wire, wire, neckLen, 8), mat4().makeTranslation(0, -LOOP_R - neckLen / 2, 0));
    const nut = placed(new THREE.CylinderGeometry(0.006, 0.006, 0.012, 10), mat4().makeTranslation(0, -hookLength + 0.004, 0));
    return BufferGeometryUtils.mergeGeometries([loopGeometry(wire), neck, nut]);
}

/** Wooden hanger following the measured shoulder line (model base frame), like rack.js. */
export function hangerGeometry(rack) {
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
export function clipGeometry(kind) {
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

/**
 * Normalised rack data for a product (manifest "rack" block with defaults):
 * type 'hanger' (garments on a wooden hanger) or 'cord' (caps, visors, bags).
 */
export function normaliseRack(conf) {
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
    rack.clip = ['ring', 'clip', 'hook'].includes(rack.clip) ? rack.clip : 'ring';
    return { rack, type };
}

/* ============================ springs =============================== */

export function spring(s, target, k, c, h) { s.v += (-k * (s.a - target) - c * s.v) * h; s.a += s.v * h; }

/** Swing / tilt / turn of a hanging piece; returns true while it still moves. */
export function stepHanging(p, dt) {
    const n = 2, h = dt / n;
    for (let i = 0; i < n; i++) {
        spring(p.swing, 0, SWING_K, SWING_C, h);
        spring(p.tilt, 0, TILT_K, SWING_C, h);
        spring(p.yaw, p.yaw.target, YAW_K, YAW_C, h);
    }
    const moving = Math.abs(p.swing.a) > EPS || Math.abs(p.swing.v) > EPS || Math.abs(p.tilt.a) > EPS ||
        Math.abs(p.tilt.v) > EPS || Math.abs(p.yaw.a - p.yaw.target) > EPS || Math.abs(p.yaw.v) > EPS;
    if (!moving) { p.swing.a = p.swing.v = p.tilt.a = p.tilt.v = p.yaw.v = 0; p.yaw.a = p.yaw.target; }
    return moving;
}

/* ============================ models ================================ */

/** Model loader for one renderer; decoders are fetched only when a file needs them. */
export function createModelLoader({ base = '', version = '1', renderer }) {
    let dracoLoader = null, ktx2 = null;
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
    async function load(path) {
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
            if (!ktx2) {
                const { KTX2Loader } = await import('three/addons/loaders/KTX2Loader.js');
                ktx2 = new KTX2Loader();
                ktx2.setTranscoderPath(base + 'assets/js/3d/lib/basis/');
                ktx2.detectSupport(renderer);
            }
            loader.setKTX2Loader(ktx2);
        }
        const gltf = await loader.loadAsync(url);
        return gltf.scene || gltf.scenes[0];
    }
    function dispose() {
        if (dracoLoader) dracoLoader.dispose();
        if (ktx2) ktx2.dispose();
        dracoLoader = null; ktx2 = null;
    }
    return { load, dispose };
}

/** Rotate to the front, scale to targetHeight, base at y = 0, centred on X/Z (as rack.js). */
export function toBaseFrame(model, conf) {
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

/** Tote bags: pull the handles together so they hang from one point (as rack.js). */
export function gatherHandles(model, top) {
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

/**
 * One InstancedMesh per model part (capacity copies, count 0, hidden). Fabric parts
 * take the per-copy colour; parts named in fixedMaterialColors or hardware keep theirs
 * (`policy`). Caller adds the meshes to the scene. Returns [{ mesh, policy, local }].
 */
export function buildProductParts(model, conf, capacity, disposables) {
    const fixed = (conf.fixedMaterialColors && typeof conf.fixedMaterialColors === 'object') ? conf.fixedMaterialColors : {};
    const parts = [];
    const white = new THREE.Color('#ffffff');
    model.updateMatrixWorld(true);
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
        const mesh = new THREE.InstancedMesh(geo, mat, Math.max(1, capacity));
        mesh.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
        mesh.setColorAt(0, white);                                                       // creates instanceColor
        mesh.count = 0;
        mesh.visible = false;
        parts.push({ mesh, policy, local: child.matrixWorld.clone() });
        if (disposables) disposables.push(mat, geo);
    });
    return parts;
}

/* ============================ quick view ============================ */

export const PANEL_CSS = `
.srm-panel{position:absolute;top:16px;right:16px;bottom:16px;width:300px;max-width:calc(100% - 32px);max-height:520px;margin:auto 0;display:flex;flex-direction:column;justify-content:center;gap:10px;padding:22px 20px;border-radius:18px;background:rgba(14,16,20,.88);border:1px solid rgba(255,255,255,.12);color:#f3f4f6;box-shadow:0 20px 40px rgba(0,0,0,.45);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);transform:translateX(24px);opacity:0;pointer-events:none;transition:opacity .3s,transform .3s;z-index:4}
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
 .srm-panel{top:auto;left:12px;right:12px;bottom:12px;width:auto;max-width:none;max-height:none;margin:0;padding:16px 16px 18px;gap:8px;transform:translateY(24px)}
 .srm-name{font-size:18px}
}
`;

/**
 * The quick-view panel (same markup and look on every layout).
 * open({ kicker, name, hex, colorName, href }), close(), isOpen(), el.
 */
export function createQuickView(root, { onClose } = {}) {
    const panel = document.createElement('div');
    panel.className = 'srm-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Selected piece');
    panel.innerHTML = '<button type="button" class="srm-close" aria-label="Close">&times;</button>' +
        '<p class="srm-kicker"></p><h3 class="srm-name"></h3>' +
        '<p class="srm-color"><span class="srm-swatch"></span><span class="srm-color-name"></span></p>' +
        '<a class="srm-cta" href="#">Customise <span aria-hidden="true">&rarr;</span></a>';
    root.appendChild(panel);
    panel.inert = true;
    panel.setAttribute('aria-hidden', 'true');
    const q = (s) => panel.querySelector(s);
    const kicker = q('.srm-kicker'), name = q('.srm-name'), swatch = q('.srm-swatch'), color = q('.srm-color-name'), cta = q('.srm-cta');
    q('.srm-close').addEventListener('click', () => { if (onClose) onClose(); });
    let open = false;
    return {
        el: panel,
        isOpen: () => open,
        open(d, focus = true) {
            kicker.textContent = d.kicker || '';
            name.textContent = d.name || '';
            swatch.style.background = d.hex || '#ffffff';
            color.textContent = d.colorName || '';
            cta.href = d.href || '#';
            cta.setAttribute('aria-label', `Customise ${d.name || ''} in ${d.colorName || ''}`.trim());
            root.classList.add('is-pulled');
            panel.inert = false;
            panel.removeAttribute('aria-hidden');
            if (focus && !open) setTimeout(() => { if (open) cta.focus({ preventScroll: true }); }, 350);
            open = true;
        },
        close() {
            open = false;
            root.classList.remove('is-pulled');
            panel.inert = true;
            panel.setAttribute('aria-hidden', 'true');
        }
    };
}
