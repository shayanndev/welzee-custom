/**
 * assets/js/3d/rack.js
 *
 * WebGL hanging rack for rack.php: ONE renderer, ONE scene, ONE canvas over the
 * whole rack. The CSS layout decides where each slot is (responsive, accessible
 * links); this module puts each 3D product exactly where its poster is, using an
 * orthographic camera mapped 1:1 to CSS pixels, so no 3D hit-testing is needed:
 * hover / focus / tap are plain DOM events on the links.
 *
 * Every product has a "rack" block in models.manifest.json (one line to tune):
 *  - type "hanger" (garments): a wooden hanger built from the garment's measured
 *    shoulder line (hangerProfile) sits INSIDE the shoulders; its hook goes up
 *    through the neck opening to the rail. Garment top at rackLayout.garmentTop.
 *  - type "cord" (cap, visor, bag): a thin cord from the rail to a ring / clip /
 *    hook at "attach"; the item's visual centre (centerY) sits on rackLayout.displayLine.
 *  - fitHeight / fitWidth: fraction of the slot's hanging area; the smaller scale wins.
 *  - level + tilt (degrees): front-down rotation (level = fix the file, tilt = lean to viewer).
 * rack.php sizes the static posters with the SAME numbers, so the 3D fade-in does not jump.
 *
 * Motion: damped pendulum swing from the rail + critically damped turn to the back.
 * Hanger / cord / clip are in the same group as the item, so they move together.
 * Renders on demand only; stops when idle, off screen, or the tab is hidden.
 * Debug view: rack.php?debug=1 (slot boxes, rail, display line, pivots, model boxes).
 */

import * as THREE from 'three';
import { createProceduralProduct } from './procedural.js';

const MAX_DPR = 1.5;
const SWING_W = 2 * Math.PI * 0.85;           // pendulum frequency (rad/s), ~1.2 s period
const SWING_ZETA = 0.16;                      // damping ratio: 2-3 visible swings, then settles
const SWING_K = SWING_W * SWING_W;
const SWING_C = 2 * SWING_ZETA * SWING_W;
const TILT_K = SWING_K * 1.35;                // small front/back sway, slightly faster
const YAW_W = 7;                              // turn to back: critically damped
const YAW_K = YAW_W * YAW_W;
const YAW_C = 2 * YAW_W;
const EPS = 0.0006;
const HARDWARE_RE = /zipper|metal|hardware|eyelet|puller|button|slider|aglet|teeth|cord_end/i;
const HEX_RE = /^#[0-9a-fA-F]{6}$/;
const GLB_PATH_RE = /^assets\/models\/(?:[A-Za-z0-9_-]+\/)?[A-Za-z0-9_-]+\.glb$/;
const GARMENT_CATEGORIES = new Set(['tees_polos', 'hoodies', 'jackets']);

// Screen-constant sizes (CSS px), so thin parts look the same on every product
const WIRE_PX = 1.3;       // hanger hook wire radius
const CURL_PX = 5;         // hook / loop radius around the rail
const CORD_PX = 0.9;       // cord radius
const MIN_CORD_PX = 12;    // never glue an item to the rail
// Rail loop: 1.35 turn arc centred on the rail; rotated so it ENDS straight below the rail
// (where the wire / cord continues down) and starts behind the rail, tip slightly up.
const RAIL_LOOP_ARC = Math.PI * 1.35;
const RAIL_LOOP_START = Math.PI * 1.5 - RAIL_LOOP_ARC;

const num = (v, d) => (typeof v === 'number' && isFinite(v) ? v : d);

export async function startRack(rackEl, opts = {}) {
    const reduced = !!opts.reducedMotion;
    const debug = rackEl.dataset.debug === '1';
    const canvas = rackEl.querySelector('#rackCanvas');
    const base = rackEl.dataset.base || '';
    if (!canvas) throw new Error('rack canvas missing');

    // ---------- Manifest ----------
    let manifest = { products: {} };
    try {
        const resp = await fetch(rackEl.dataset.manifest, { credentials: 'same-origin', cache: 'no-cache' });
        if (resp.ok) manifest = await resp.json();
    } catch (e) { /* procedural fallback for everything */ }
    const products = manifest.products || {};
    const version = String(manifest.version || '1');
    const rl = manifest.rackLayout || {};
    const GARMENT_TOP = num(rl.garmentTop, 0.07);
    const DISPLAY_LINE = num(rl.displayLine, 0.47);
    // Rail centre sits this many px above each hanging area (rack.css --rail-above)
    const RAIL_ABOVE = parseFloat(getComputedStyle(rackEl).getPropertyValue('--rail-above')) || 5.5;

    // ---------- Renderer / scene / camera (shared by every product) ----------
    const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, MAX_DPR));
    renderer.setClearColor(0x000000, 0);
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.18;
    renderer.outputColorSpace = THREE.SRGBColorSpace;

    const scene = new THREE.Scene();
    scene.add(new THREE.HemisphereLight(0xffffff, 0x94a3b8, 1.35));
    const key = new THREE.DirectionalLight(0xffffff, 1.3);
    key.position.set(2, 4, 3);
    scene.add(key);
    const fill = new THREE.DirectionalLight(0xf8fafc, 0.7);
    fill.position.set(-2, 1, -2);
    scene.add(fill);
    const back = new THREE.DirectionalLight(0xffffff, 0.5);
    back.position.set(0, 3, -3);
    scene.add(back);

    // World units = CSS pixels of the rack box; y is flipped (world y = -css y)
    const camera = new THREE.OrthographicCamera(0, 1, 0, -1, -5000, 5000);
    camera.position.set(0, 0, 1000);

    // Shared materials / geometries
    const wireMat = new THREE.MeshStandardMaterial({ color: 0x3f4650, metalness: 0.8, roughness: 0.32 });
    const woodMat = new THREE.MeshStandardMaterial({ color: 0x8a5a35, metalness: 0, roughness: 0.5 });
    const darkMat = new THREE.MeshStandardMaterial({ color: 0x2b2f36, metalness: 0.55, roughness: 0.4 });
    const cordMat = new THREE.MeshStandardMaterial({ color: 0x6b5b4b, metalness: 0, roughness: 0.9 });
    const unitCyl = new THREE.CylinderGeometry(1, 1, 1, 10);            // scaled per use
    const railLoopGeo = new THREE.TorusGeometry(1, 0.26, 8, 24, RAIL_LOOP_ARC);
    const shadowTex = makeShadowTexture();
    const shadowMat = new THREE.MeshBasicMaterial({ map: shadowTex, transparent: true, depthWrite: false, opacity: 0.22 });
    const unitPlane = new THREE.PlaneGeometry(1, 1);

    // ---------- Products (one entry per DOM slot) ----------
    const entries = [];
    rackEl.querySelectorAll('.rack-slot').forEach((li, index) => {
        const id = li.dataset.product;
        const conf = products[id] || {};
        const rack = defaultRack(conf, conf.rack);
        const pivot = new THREE.Group();   // at the rail: swing (z) + sway (x); scaled px-per-metre
        pivot.visible = false;
        scene.add(pivot);
        const idleYaw = (index % 2 ? -1 : 1) * (rack.type === 'hanger' ? 0.2 : 0.12);
        entries.push({
            id, li, index, conf, rack, pivot, idleYaw,
            link: li.querySelector('a.rack-item'),
            hang: li.querySelector('.rack-hang'),
            glb: (typeof conf.glb === 'string' && GLB_PATH_RE.test(conf.glb)) ? conf.glb : (li.dataset.glb || ''),
            state: 'idle', parts: null,
            swing: { a: 0, v: 0 }, tilt: { a: 0, v: 0 },
            yaw: { a: idleYaw, v: 0, target: idleYaw }
        });
    });
    const byId = new Map(entries.map(e => [e.id, e]));

    /** Fills gaps so a product without a "rack" block still hangs sensibly. */
    function defaultRack(conf, r) {
        const th = num(conf.targetHeight, 0.7);
        const garment = GARMENT_CATEGORIES.has(conf.category);
        const out = Object.assign({ type: garment ? 'hanger' : 'cord' }, r || {});
        out.frontSize = Array.isArray(out.frontSize) ? out.frontSize : [th, th];
        out.fitHeight = num(out.fitHeight, out.type === 'hanger' ? 0.8 : (r && r.fitWidth ? 9 : 0.6));
        out.fitWidth = num(out.fitWidth, out.type === 'hanger' ? 0.9 : 9);
        if (out.type === 'hanger') {
            if (!Array.isArray(out.hangerProfile) || out.hangerProfile.length < 2) {
                const h = out.frontSize[1], w = out.frontSize[0];
                out.hangerProfile = [[0, h * 0.93], [w * 0.12, h * 0.92], [w * 0.33, h * 0.87]];
            }
            out.hangerZ = num(out.hangerZ, 0);
        } else {
            out.attach = Array.isArray(out.attach) ? out.attach : [0, out.frontSize[1], 0];
            out.frontTop = num(out.frontTop, out.frontSize[1]);
            out.centerY = num(out.centerY, out.frontSize[1] / 2);
            out.level = num(out.level, 0);
            out.tilt = num(out.tilt, 0);
        }
        return out;
    }

    // ---------- GLB loading (decoders only when the file needs them) ----------
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
        const scene = gltf.scene || gltf.scenes[0];
        // Some exports ship without normals: compute them for smooth shading
        if (scene) scene.traverse((child) => {
            if (!child.isMesh || !child.geometry || child.geometry.attributes.normal) return;
            child.geometry.computeVertexNormals();
            (Array.isArray(child.material) ? child.material : [child.material]).forEach((m) => {
                if (m && m.flatShading) { m.flatShading = false; m.needsUpdate = true; }
            });
        });
        return scene;
    }

    /** Rack colour on fabric; fixed parts (e.g. varsity sleeves) keep their manifest colour. */
    function tint(model, hex, fixedColors) {
        const color = hex ? new THREE.Color(hex) : null;
        const fixed = (fixedColors && typeof fixedColors === 'object') ? fixedColors : {};
        model.traverse((child) => {
            if (!child.isMesh || !child.material) return;
            (Array.isArray(child.material) ? child.material : [child.material]).forEach((m) => {
                if (!m.color) return;
                const f = fixed[m.name];
                if (typeof f === 'string' && HEX_RE.test(f)) m.color.set(f);
                else if (color && !HARDWARE_RE.test(m.name || '')) m.color.copy(color);
            });
        });
    }

    /** Manifest model space: scaled to targetHeight, base at y = 0, centred on X/Z. */
    function toBaseFrame(model, conf) {
        if (typeof conf.frontRotation === 'number' && conf.frontRotation !== 0) {
            model.rotation.y += (conf.frontRotation * Math.PI) / 180;
        }
        model.updateMatrixWorld(true);
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        if (size.y > 1e-6) model.scale.multiplyScalar(num(conf.targetHeight, 0.7) / size.y);
        model.updateMatrixWorld(true);
        box.setFromObject(model);
        const c = box.getCenter(new THREE.Vector3());
        model.position.x -= c.x;
        model.position.z -= c.z;
        model.position.y -= box.min.y;
        model.updateMatrixWorld(true);
    }

    /** Tote bag: pull both handles together towards the hook so they hang naturally. */
    function gatherHandles(model, top) {
        const y0 = top * 0.64;                    // handles start just above the bag body
        const v = new THREE.Vector3();
        model.traverse((child) => {
            if (!child.isMesh || !/handle/i.test(child.material?.name || '')) return;
            child.geometry = child.geometry.clone();
            const m = child.matrixWorld, inv = m.clone().invert();
            const pos = child.geometry.attributes.position;
            for (let i = 0; i < pos.count; i++) {
                v.fromBufferAttribute(pos, i).applyMatrix4(m);
                const t = THREE.MathUtils.smoothstep(v.y, y0, top);
                v.z *= 1 - 0.85 * t;
                v.applyMatrix4(inv);
                pos.setXYZ(i, v.x, v.y, v.z);
            }
            pos.needsUpdate = true;
            child.geometry.computeVertexNormals();
            child.geometry.computeBoundingBox();
            child.geometry.computeBoundingSphere();
        });
    }

    // ---------- Hardware builders (all in model metres) ----------
    function rodBetween(a, b, r, mat) {
        const d = b.clone().sub(a);
        const mesh = new THREE.Mesh(unitCyl, mat);
        mesh.scale.set(r, d.length(), r);
        mesh.position.copy(a).add(b).multiplyScalar(0.5);
        mesh.quaternion.setFromUnitVectors(new THREE.Vector3(0, 1, 0), d.normalize());
        return mesh;
    }

    /** Slim wooden (or dark metal) hanger following the measured shoulder line. */
    function buildHangerBody(rack) {
        const g = new THREE.Group();
        const mat = rack.hangerStyle === 'metal' ? darkMat : woodMat;
        const z = rack.hangerZ;
        const prof = rack.hangerProfile;
        const H = 0.022, T = 0.011;               // arm height and thickness (m)
        for (const sgn of [1, -1]) {
            for (let i = 0; i + 1 < prof.length; i++) {
                const a = new THREE.Vector2(sgn * prof[i][0], prof[i][1]);
                const b = new THREE.Vector2(sgn * prof[i + 1][0], prof[i + 1][1]);
                const len = a.distanceTo(b);
                if (len < 1e-4) continue;
                const seg = new THREE.Mesh(new THREE.BoxGeometry(len, H, T), mat);
                seg.position.set((a.x + b.x) / 2, (a.y + b.y) / 2 - H / 2, z);
                seg.rotation.z = Math.atan2(b.y - a.y, b.x - a.x);
                g.add(seg);
            }
            // rounded joints and tips
            for (let i = 1; i < prof.length; i++) {
                const j = new THREE.Mesh(unitCyl, mat);
                j.scale.set(H / 2, T, H / 2);
                j.rotation.x = Math.PI / 2;
                j.position.set(sgn * prof[i][0], prof[i][1] - H / 2, z);
                g.add(j);
            }
        }
        // metal nut where the hook enters the wood
        const nut = new THREE.Mesh(unitCyl, wireMat);
        nut.scale.set(0.006, 0.012, 0.006);
        nut.position.set(0, prof[0][1] + 0.004, z);
        g.add(nut);
        return g;
    }

    /** Ring (cap), spring clip (visor) or J-hook (bag) at the attach point. */
    function buildClip(kind) {
        const g = new THREE.Group();
        if (kind === 'clip') {
            for (const s of [1, -1]) {
                const plate = new THREE.Mesh(new THREE.BoxGeometry(0.014, 0.018, 0.0025), darkMat);
                plate.position.set(0, -0.004, s * 0.004);
                g.add(plate);
            }
            const spring = new THREE.Mesh(unitCyl, wireMat);
            spring.scale.set(0.004, 0.012, 0.004);
            spring.rotation.x = Math.PI / 2;
            spring.position.set(0, 0.007, 0);
            g.add(spring);
        } else if (kind === 'hook') {
            const j = new THREE.Mesh(new THREE.TorusGeometry(0.012, 0.0028, 8, 18, Math.PI), wireMat);
            j.rotation.z = Math.PI;               // arc below its centre: catches both handles
            j.position.set(0.012, 0.006, 0);
            g.add(j);
            g.add(rodBetween(new THREE.Vector3(0, 0.006, 0), new THREE.Vector3(0, 0.03, 0), 0.0028, wireMat));
        } else {
            const ring = new THREE.Mesh(new THREE.TorusGeometry(0.008, 0.0018, 8, 24), wireMat);
            ring.position.y = 0.006;
            g.add(ring);
        }
        return g;
    }

    function makeShadowTexture() {
        const c = document.createElement('canvas');
        c.width = c.height = 64;
        const x = c.getContext('2d');
        const grd = x.createRadialGradient(32, 32, 2, 32, 32, 32);
        grd.addColorStop(0, 'rgba(0,0,0,1)');
        grd.addColorStop(0.55, 'rgba(0,0,0,0.45)');
        grd.addColorStop(1, 'rgba(0,0,0,0)');
        x.fillStyle = grd;
        x.fillRect(0, 0, 64, 64);
        const t = new THREE.CanvasTexture(c);
        t.colorSpace = THREE.SRGBColorSpace;
        return t;
    }

    // ---------- Build one product ----------
    async function loadEntry(e) {
        if (e.state !== 'idle') return;
        e.state = 'loading';
        const hex = HEX_RE.test(e.conf.rackColor || '') ? e.conf.rackColor : null;
        const r = e.rack;

        let model = null;
        if (e.glb) {
            try {
                model = await loadGLB(e.glb);
                if (model) tint(model, hex, e.conf.fixedMaterialColors);
            } catch (err) {
                console.warn(`[Welzee Rack] ${e.id}: model unavailable, using placeholder.`, err);
                model = null;
            }
        }
        if (!model) model = createProceduralProduct(e.id, hex || '#ffffff');
        toBaseFrame(model, e.conf);
        if (r.type === 'cord' && r.gatherHandles) gatherHandles(model, num(e.conf.targetHeight, 0.5));

        const parts = { model };
        // Soft shadow on the rack wall, follows the swing (not the turn)
        parts.shadow = new THREE.Mesh(unitPlane, shadowMat);
        parts.shadow.renderOrder = -1;
        e.pivot.add(parts.shadow);

        if (r.type === 'hanger') {
            parts.spin = new THREE.Group();          // turns around the hook (vertical through the rail point)
            parts.holder = new THREE.Group();        // model space (base frame)
            parts.holder.add(model);
            parts.holder.add(buildHangerBody(r));
            parts.neck = new THREE.Mesh(unitCyl, wireMat);   // hook wire: rail -> hanger centre
            parts.curl = new THREE.Mesh(railLoopGeo, wireMat);
            parts.curl.rotation.set(0, Math.PI / 2, RAIL_LOOP_START);   // wraps over the rail (rail runs along x)
            parts.holder.add(parts.neck, parts.curl);
            parts.spin.add(parts.holder);
            e.pivot.add(parts.spin);
        } else {
            parts.loop = new THREE.Mesh(railLoopGeo, wireMat);   // small loop over the rail
            parts.loop.rotation.set(0, Math.PI / 2, RAIL_LOOP_START);
            parts.cord = new THREE.Mesh(unitCyl, cordMat);
            parts.hang = new THREE.Group();          // bottom of the cord = clip point
            parts.tiltG = new THREE.Group();
            parts.tiltG.rotation.x = THREE.MathUtils.degToRad(r.level + r.tilt);   // front down, towards viewer
            parts.spin = new THREE.Group();          // turns around the vertical through the clip
            parts.holder = new THREE.Group();
            parts.holder.position.set(-r.attach[0], -r.attach[1], -r.attach[2]);
            parts.holder.add(model);
            parts.spin.add(parts.holder, buildClip(r.clip));
            parts.tiltG.add(parts.spin);
            parts.hang.add(parts.tiltG);
            e.pivot.add(parts.loop, parts.cord, parts.hang);
        }

        if (debug) {
            parts.box = new THREE.BoxHelper(model, 0xdb2777);
            scene.add(parts.box);
            const dot = new THREE.Mesh(new THREE.SphereGeometry(1, 10, 8), new THREE.MeshBasicMaterial({ color: 0xdc2626 }));
            parts.pivotDot = dot;
            e.pivot.add(dot);
        }

        e.parts = parts;
        e.state = 'ready';
        e.pivot.visible = true;
        layoutEntry(e);
        if (!classic) e.li.classList.add('is-3d');

        if (!reduced) {
            // Gentle staggered settle-in swing when a piece appears
            setTimeout(() => { e.swing.v += 0.35 * (e.index % 2 ? -1 : 1); wake(); }, 80 * (e.index % 4));
        }
        wake();
    }

    // ---------- Layout: same maths as the posters in rack.php / rack.css ----------
    let W = 1, H = 1, rackRect = null;
    function measureSlot(e) {
        // Untransformed boxes only (the poster swing rotates .rack-hang)
        const a = e.link.getBoundingClientRect();
        e.areaW = e.hang.offsetWidth;
        e.areaH = e.hang.offsetHeight;
        e.areaLeft = a.left - rackRect.left + e.hang.offsetLeft;
        e.areaTop = a.top - rackRect.top + e.hang.offsetTop;
    }

    function layoutEntry(e) {
        if (!e.parts) return;
        const r = e.rack, p = e.parts;
        const [fw, fh] = r.frontSize;
        const s = Math.max(1e-3, Math.min((r.fitHeight * e.areaH) / fh, (r.fitWidth * e.areaW) / fw));   // px per metre
        const railY = e.areaTop - RAIL_ABOVE;
        e.pivot.position.set(e.areaLeft + e.areaW / 2, -railY, 0);
        e.pivot.scale.setScalar(s);

        let centreBelow;   // metres from the rail point down to the item's visual centre (for the shadow)
        if (r.type === 'hanger') {
            const A = r.hangerProfile[0];                              // hanger centre (base frame)
            const topCss = e.areaTop + GARMENT_TOP * e.areaH;          // garment top, like the poster
            const d = (topCss - railY) / s + (fh - A[1]);              // rail -> hanger centre (m)
            p.holder.position.set(-A[0], -d - A[1], -r.hangerZ);
            const curlR = CURL_PX / s, wire = WIRE_PX / s;
            p.curl.scale.setScalar(curlR);
            p.curl.position.set(A[0], A[1] + d, r.hangerZ);          // centred on the rail
            const neckTop = A[1] + d - curlR;
            p.neck.scale.set(wire, Math.max(1e-4, neckTop - A[1]), wire);
            p.neck.position.set(A[0], (neckTop + A[1]) / 2, r.hangerZ);
            centreBelow = d + A[1] - fh / 2;
        } else {
            const above = r.attach[1] - r.centerY;                    // clip above visual centre (m, after tilt)
            const centreCss = e.areaTop + DISPLAY_LINE * e.areaH;
            const cordPx = Math.max(MIN_CORD_PX, centreCss - above * s - railY);
            const L = cordPx / s;
            const curlR = CURL_PX / s;
            p.loop.scale.setScalar(curlR);
            p.loop.position.set(0, 0, 0);                            // centred on the rail
            p.cord.scale.set(CORD_PX / s, Math.max(1e-4, L - curlR), CORD_PX / s);
            p.cord.position.set(0, -(L + curlR) / 2, 0);
            p.hang.position.set(0, -L, 0);
            centreBelow = L + above;
        }
        // just behind the deepest item (small z keeps the sway from moving it vertically)
        p.shadow.position.set(0.012 * fh, -centreBelow - 0.02 * fh, -0.35);
        p.shadow.scale.set(fw * 1.15, fh * 1.1, 1);
        if (p.pivotDot) p.pivotDot.scale.setScalar(3 / s);
    }

    function layout() {
        rackRect = rackEl.getBoundingClientRect();
        W = Math.max(1, Math.round(rackRect.width));
        H = Math.max(1, Math.round(rackRect.height));
        renderer.setSize(W, H, false);
        camera.left = 0; camera.right = W; camera.top = 0; camera.bottom = -H;
        camera.updateProjectionMatrix();
        for (const e of entries) {
            measureSlot(e);
            layoutEntry(e);
        }
        wake();
    }

    // ---------- Motion ----------
    function spring(s, target, k, c, h) {
        s.v += (-k * (s.a - target) - c * s.v) * h;
        s.a += s.v * h;
    }
    function step(e, dt) {
        const n = 2, h = dt / n;
        for (let i = 0; i < n; i++) {
            spring(e.swing, 0, SWING_K, SWING_C, h);
            spring(e.tilt, 0, TILT_K, SWING_C, h);
            spring(e.yaw, e.yaw.target, YAW_K, YAW_C, h);
        }
        const moving = Math.abs(e.swing.a) > EPS || Math.abs(e.swing.v) > EPS ||
                       Math.abs(e.tilt.a) > EPS || Math.abs(e.tilt.v) > EPS ||
                       Math.abs(e.yaw.a - e.yaw.target) > EPS || Math.abs(e.yaw.v) > EPS;
        if (!moving) {
            e.swing.a = e.swing.v = e.tilt.a = e.tilt.v = e.yaw.v = 0;
            e.yaw.a = e.yaw.target;
        }
        e.pivot.rotation.set(e.tilt.a, 0, e.swing.a);
        e.parts.spin.rotation.y = e.yaw.a;
        return moving;
    }

    function poke(e) {
        if (classic) return;
        if (e.state !== 'ready') { loadEntry(e); return; }
        const kick = reduced ? 0.3 : 0.85;
        const dir = e.swing.v === 0 ? (e.index % 2 ? -1 : 1) : Math.sign(e.swing.v);
        e.swing.v = THREE.MathUtils.clamp(e.swing.v + kick * dir, -1.2, 1.2);
        e.tilt.v = THREE.MathUtils.clamp(e.tilt.v + (reduced ? 0.08 : 0.3), -0.5, 0.5);
    }
    function showBack(e, on) {
        e.yaw.target = on ? e.idleYaw + Math.PI : e.idleYaw;
    }

    // ---------- Classic view (photo rack): stop drawing, give the posters back ----------
    let classic = rackEl.classList.contains('is-classic');
    rackEl.addEventListener('rack:classic', () => {
        classic = true;
        entries.forEach((e) => e.li.classList.remove('is-3d'));
    });
    rackEl.addEventListener('rack:3d', () => {
        classic = false;
        entries.forEach((e) => { if (e.state === 'ready') e.li.classList.add('is-3d'); });
        layout();
    });

    // ---------- Render loop (on demand) ----------
    let running = false, visible = true, last = 0, dirty = true;
    function wake() {
        if (classic) return;
        dirty = true;
        if (!running && visible && !document.hidden) {
            running = true;
            last = performance.now();
            requestAnimationFrame(tick);
        }
    }
    function tick(now) {
        if (classic || !visible || document.hidden) { running = false; return; }
        const dt = Math.min(0.033, Math.max(0, (now - last) / 1000));
        last = now;
        let moving = false;
        for (const e of entries) {
            if (e.state === 'ready' && step(e, dt)) moving = true;
            if (debug && e.parts && e.parts.box) e.parts.box.update();
        }
        renderer.render(scene, camera);
        dirty = false;
        if (moving || dirty) requestAnimationFrame(tick);
        else running = false;
    }

    // ---------- Events ----------
    for (const e of entries) {
        if (!e.link) continue;
        e.link.addEventListener('pointerenter', (ev) => {
            if (ev.pointerType !== 'mouse') return;
            poke(e); showBack(e, true); wake();
        });
        e.link.addEventListener('pointerleave', (ev) => {
            if (ev.pointerType !== 'mouse') return;
            showBack(e, false); wake();
        });
        e.link.addEventListener('focus', () => { poke(e); showBack(e, true); wake(); });
        e.link.addEventListener('blur', () => { showBack(e, false); wake(); });
    }
    // Touch first tap (from the page bootstrap): swing + show back; others turn front again
    rackEl.addEventListener('rack:poke', (ev) => {
        const target = byId.get(ev.detail && ev.detail.id);
        for (const e of entries) if (e !== target) showBack(e, false);
        if (target) { poke(target); showBack(target, true); }
        wake();
    });

    // Load models only for slots near the viewport
    const slotObserver = new IntersectionObserver((list) => {
        list.forEach((it) => {
            if (!it.isIntersecting) return;
            const e = entries.find(x => x.li === it.target);
            if (e) { loadEntry(e); slotObserver.unobserve(it.target); }
        });
    }, { rootMargin: '200px 0px' });
    entries.forEach(e => slotObserver.observe(e.li));

    // Pause when the rack is off screen or the tab is hidden
    new IntersectionObserver((list) => {
        visible = list[0] ? list[0].isIntersecting : true;
        if (visible) wake();
    }).observe(rackEl);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) wake(); });

    // Follow CSS layout changes (rotation, resize, font load)
    let layoutRaf = 0;
    const relayout = () => {
        cancelAnimationFrame(layoutRaf);
        layoutRaf = requestAnimationFrame(layout);
    };
    new ResizeObserver(relayout).observe(rackEl);
    window.addEventListener('resize', relayout);

    layout();
    rackEl.classList.add('is-3d-live');
    rackEl.classList.remove('is-loading-3d');
    return { renderer, scene, entries };
}
