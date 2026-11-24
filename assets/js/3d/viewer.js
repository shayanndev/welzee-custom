/**
 * assets/js/3d/viewer.js
 *
 * Robust, production-grade 3D Garment Viewport engine using Three.js r162.
 * OrbitControls, lightweight directional + hemisphere lighting,
 * automatic unit & scale normalization, dynamic extension decoding (Draco/Meshopt/KTX2),
 * material preservation & PBR color tinting, and on-demand rendering.
 */

import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { createProceduralProduct, getContactShadowTexture } from './procedural.js';
import { DecalManager } from './decals.js?v=1.3.0';

const HEX6_RE = /^#[0-9a-fA-F]{6}$/;

/**
 * Manifest "coordinates": "base" means cameraTarget and printZone positions are in
 * metres with the model's base at y = 0 and centred on X/Z (after scaling to
 * targetHeight). Older manifests used viewer world space (base at y = -0.35).
 */
function isBaseFrame(manifest) {
    return manifest?.coordinates === 'base';
}

/** Some exports ship without normals; decals and smooth lighting need them. */
function ensureNormals(root) {
    root.traverse((child) => {
        if (!child.isMesh || !child.geometry || child.geometry.attributes.normal) return;
        child.geometry.computeVertexNormals();
        (Array.isArray(child.material) ? child.material : [child.material]).forEach((m) => {
            if (m && m.flatShading) { m.flatShading = false; m.needsUpdate = true; }
        });
    });
}

export class ApparelViewer {
    constructor(containerId, manifestPath = 'assets/js/3d/models.manifest.json') {
        this.container = document.getElementById(containerId);
        if (!this.container) throw new Error(`Container #${containerId} not found`);

        this.manifestPath = manifestPath;
        this.manifest = null;
        this.currentProductId = 'tshirt';
        this.currentColorHex = '#ffffff';
        this.designMethod = 'embroidery';

        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.controls = null;
        this.currentModelGroup = null;
        this.garmentMesh = null;
        this.contactShadow = null;
        this.decalManager = new DecalManager(this);

        this.isRendering = false;
        this.renderRequested = false;
        this.autoRotateActive = true;
        this.firstFrameRendered = false;
        this.modelCache = new Map();

        // Check prefers-reduced-motion
        this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (this.prefersReducedMotion) {
            this.autoRotateActive = false;
        }

        this.initPromise = this.init();
    }

    async init() {
        // Load manifest with cache busting
        try {
            const manifestUrl = this.manifestPath.includes('?') 
                ? this.manifestPath 
                : `${this.manifestPath}?v=1.1.0`;
            const resp = await fetch(manifestUrl, { cache: 'no-cache' }); // cheap 304 revalidation
            this.manifest = await resp.json();
        } catch (e) {
            console.warn('[Welzee 3D] Could not load 3D manifest, using defaults', e);
            this.manifest = { products: {}, version: '1.1.0' };
        }

        // Set up Scene
        this.scene = new THREE.Scene();
        this.scene.background = null;

        // Set up Camera
        const aspect = (this.container.clientWidth || 400) / (this.container.clientHeight || 480) || 1;
        this.camera = new THREE.PerspectiveCamera(40, aspect, 0.1, 20);
        this.camera.position.set(0, 0.05, 1.7);

        // Hardware capping for mobile fill rate
        const isMobile = window.innerWidth < 768 || ('ontouchstart' in window);
        const maxDPR = isMobile ? 1.5 : 2.0;

        // Set up Renderer
        this.renderer = new THREE.WebGLRenderer({
            antialias: !isMobile,
            alpha: true,
            preserveDrawingBuffer: true,
            powerPreference: 'high-performance'
        });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, maxDPR));
        this.renderer.setSize(this.container.clientWidth || 400, this.container.clientHeight || 480);
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1.18;
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;

        const canvasEl = this.renderer.domElement;
        canvasEl.style.width = '100%';
        canvasEl.style.height = '100%';
        canvasEl.style.display = 'block';
        canvasEl.style.outline = 'none';
        canvasEl.setAttribute('role', 'img');
        canvasEl.setAttribute('aria-label', 'Interactive 3D Apparel Preview');
        this.container.appendChild(canvasEl);

        // Studio lighting (tuned for crisp white apparel rendering with realistic fold shadows)
        const hemiLight = new THREE.HemisphereLight(0xffffff, 0x94a3b8, 1.35);
        hemiLight.position.set(0, 10, 0);
        this.scene.add(hemiLight);

        const keyLight = new THREE.DirectionalLight(0xffffff, 1.30);
        keyLight.position.set(2, 4, 3);
        this.scene.add(keyLight);

        const fillLight = new THREE.DirectionalLight(0xf8fafc, 0.70);
        fillLight.position.set(-2, 1, -2);
        this.scene.add(fillLight);

        const backLight = new THREE.DirectionalLight(0xffffff, 0.50);
        backLight.position.set(0, 3, -3);
        this.scene.add(backLight);

        // Ground contact shadow
        const shadowGeo = new THREE.PlaneGeometry(1.2, 1.2);
        const shadowMat = new THREE.MeshBasicMaterial({
            map: getContactShadowTexture(),
            transparent: true,
            depthWrite: false
        });
        this.contactShadow = new THREE.Mesh(shadowGeo, shadowMat);
        this.contactShadow.rotation.x = -Math.PI / 2;
        this.contactShadow.position.y = -0.38;
        this.scene.add(this.contactShadow);

        // OrbitControls
        this.controls = new OrbitControls(this.camera, canvasEl);
        this.controls.enableDamping = true;
        this.controls.dampingFactor = 0.05;
        this.controls.enablePan = false;
        this.controls.minDistance = 0.6;
        this.controls.maxDistance = 3.5;
        this.controls.maxPolarAngle = Math.PI / 2 + 0.05;
        this.controls.autoRotate = this.autoRotateActive;
        this.controls.autoRotateSpeed = 1.2;

        this.controls.addEventListener('start', () => {
            // A manual drag cancels any running "rotate to side" animation
            if (this._sideAnim) {
                cancelAnimationFrame(this._sideAnim);
                this._sideAnim = null;
            }
            this.autoRotateActive = false;
            this.controls.autoRotate = false;
            this.requestRender();
        });

        this.controls.addEventListener('change', () => {
            this.requestRender();
        });

        // ResizeObserver
        this.resizeObserver = new ResizeObserver(() => this.onResize());
        this.resizeObserver.observe(this.container);

        // Load initial product
        await this.loadProduct(this.currentProductId);

        // Async shader compile
        if (this.renderer.compileAsync) {
            try {
                await this.renderer.compileAsync(this.scene, this.camera);
            } catch (err) {
                // Non-fatal
            }
        }

        // First frame render
        this.render();

        return this;
    }

    onResize() {
        if (!this.container || !this.renderer || !this.camera) return;
        const w = this.container.clientWidth;
        const h = this.container.clientHeight;
        if (w === 0 || h === 0) return;

        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(w, h);
        this.requestRender();
    }

    requestRender() {
        if (!this.renderRequested) {
            this.renderRequested = true;
            requestAnimationFrame(() => this.animate());
        }
    }

    animate() {
        this.renderRequested = false;

        let needsAnother = false;
        if (this.controls) {
            const dampingActive = this.controls.enableDamping && (
                Math.abs(this.controls.sphericalDelta?.theta || 0) > 0.0001 ||
                Math.abs(this.controls.sphericalDelta?.phi || 0) > 0.0001
            );
            if (this.controls.autoRotate || dampingActive) {
                this.controls.update();
                needsAnother = true;
            }
        }

        this.render();

        if (needsAnother) {
            this.requestRender();
        }
    }

    render() {
        if (!this.renderer || !this.scene || !this.camera) return;
        this.renderer.render(this.scene, this.camera);

        if (!this.firstFrameRendered) {
            this.firstFrameRendered = true;
            this.container.dispatchEvent(new CustomEvent('welzee:3d-ready', { bubbles: true }));
        }
    }

    toggleAutoRotate(force) {
        if (typeof force === 'boolean') {
            this.autoRotateActive = force;
        } else {
            this.autoRotateActive = !this.autoRotateActive;
        }
        if (this.controls) {
            this.controls.autoRotate = this.autoRotateActive;
        }
        if (this.autoRotateActive) {
            this.requestRender();
        }
        return this.autoRotateActive;
    }

    resetCamera() {
        const prodConf = this.manifest?.products?.[this.currentProductId];
        if (this.currentModelGroup) {
            this.frameCamera(this.currentModelGroup, prodConf);
        } else {
            const dist = prodConf?.cameraDistance || 1.7;
            this.camera.position.set(0, 0.1, dist);
            this.controls.target.set(0, 0.05, 0);
            this.controls.update();
        }
        this.requestRender();
    }

    showModelLoadWarning(msg) {
        console.warn('[Welzee 3D Warning]', msg);
        let warnEl = document.getElementById('modelLoadWarning');
        if (!warnEl && this.container) {
            warnEl = document.createElement('div');
            warnEl.id = 'modelLoadWarning';
            warnEl.className = 'absolute top-3 left-3 right-16 z-30 bg-amber-500/90 text-white text-xs px-3 py-1.5 rounded-lg shadow-md backdrop-blur-sm flex items-center space-x-2 transition-opacity duration-300 pointer-events-none';
            this.container.appendChild(warnEl);
        }
        if (warnEl) {
            warnEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i><span>${msg}</span>`;
            warnEl.classList.remove('hidden', 'opacity-0');
            setTimeout(() => {
                warnEl.classList.add('opacity-0');
                setTimeout(() => warnEl.classList.add('hidden'), 300);
            }, 6000);
        }
    }

    async loadProduct(productId) {
        this.currentProductId = productId;
        const prodConf = this.manifest?.products?.[productId];

        // Hide previous model
        if (this.currentModelGroup) {
            this.scene.remove(this.currentModelGroup);
            this.currentModelGroup = null;
            this.garmentMesh = null;
        }

        let modelGroup = this.modelCache.get(productId);

        if (!modelGroup) {
            // Attempt to load real GLB if specified
            if (prodConf?.glb) {
                try {
                    const glbScene = await this.tryLoadGLB(prodConf.glb, prodConf);
                    if (glbScene) {
                        modelGroup = glbScene;
                    }
                } catch (loadErr) {
                    this.showModelLoadWarning(`Model error: ${loadErr.message || 'GLB load failed'}. Using procedural view.`);
                    // Lets the page offer the classic view (custom_order_3d.php listens)
                    this.container.dispatchEvent(new CustomEvent('welzee:3d-error', {
                        bubbles: true, detail: { product: productId, message: loadErr.message || 'GLB load failed' }
                    }));
                }
            }

            // Fallback to procedural model if GLB not available or failed
            if (!modelGroup) {
                modelGroup = createProceduralProduct(productId);
            }

            // Auto-normalise every loaded model (GLB or procedural):
            this.autoNormalizeModel(modelGroup, prodConf);

            this.modelCache.set(productId, modelGroup);
        }

        this.currentModelGroup = modelGroup;
        this.scene.add(modelGroup);

        // Identify primary mesh for decal projection
        this.garmentMesh = this.findPrimaryMesh(modelGroup, prodConf);

        // Apply selected color
        this.setProductColor(this.currentColorHex);

        // Remove old decals first so they never affect the bounding box used for zone defaults
        if (this.decalManager) this.decalManager.removeDecals();

        // Frame this product every time (cached models too: sizes differ a lot, cap vs jacket)
        this.frameCamera(modelGroup, prodConf);

        // Determine print zones (manifest, with bounding-box defaults for missing fields)
        this.currentZones = this.resolvePrintZones(modelGroup, prodConf);

        // Project one artwork decal per zone onto the garment
        if (this.decalManager && modelGroup) {
            this.decalManager.applyDecals(modelGroup, this.currentZones);
        }

        this.requestRender();
    }

    /** Zone ids/labels for a product straight from the manifest (no model needed). */
    getZoneList(productId) {
        const prodConf = this.manifest?.products?.[productId];
        const list = Array.isArray(prodConf?.printZones) ? prodConf.printZones
            : (prodConf?.printZone ? [{ id: 'front', label: 'Front' }] : []);
        return list
            .filter(z => z && typeof z.id === 'string')
            .map(z => ({ id: z.id, label: z.label || (z.id.charAt(0).toUpperCase() + z.id.slice(1)) }));
    }

    /**
     * Smoothly orbits the camera to face a zone (e.g. 'back'). Manual rotation
     * afterwards is free; it never changes the selected side.
     */
    rotateToSide(sideId) {
        const zone = (this.currentZones || []).find(z => z.id === sideId);
        if (!zone || !this.camera || !this.controls) return;

        const dir = new THREE.Vector3(...zone.normal);
        dir.y = 0;
        if (dir.lengthSq() < 1e-6) return;
        dir.normalize();

        this.toggleAutoRotate(false);
        if (this._sideAnim) cancelAnimationFrame(this._sideAnim);

        const target = this.controls.target;
        const sph = new THREE.Spherical().setFromVector3(this.camera.position.clone().sub(target));
        const startTheta = sph.theta;
        let delta = Math.atan2(dir.x, dir.z) - startTheta;
        delta = ((delta + Math.PI) % (2 * Math.PI) + 2 * Math.PI) % (2 * Math.PI) - Math.PI; // shortest way
        const duration = this.prefersReducedMotion ? 0 : 700;
        const t0 = performance.now();
        const offset = new THREE.Vector3();

        const step = (now) => {
            const t = duration ? Math.min(1, (now - t0) / duration) : 1;
            const e = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; // ease in-out
            sph.theta = startTheta + delta * e;
            this.camera.position.copy(target).add(offset.setFromSpherical(sph));
            this.camera.lookAt(target);
            this.controls.update();
            this.render();
            this._sideAnim = t < 1 ? requestAnimationFrame(step) : null;
        };
        this._sideAnim = requestAnimationFrame(step);
    }

    /**
     * Renders one image per requested side (camera facing each zone) on the light grey
     * studio background. Returns { sideId: HTMLCanvasElement }.
     */
    getSideSnapshots(sideIds, width = 800, height = 900) {
        const out = {};
        if (!this.renderer || !this.scene || !this.camera || !this.controls) return out;

        const off = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true, alpha: false });
        off.setSize(width, height);
        off.toneMapping = this.renderer.toneMapping;
        off.toneMappingExposure = this.renderer.toneMappingExposure;
        off.outputColorSpace = THREE.SRGBColorSpace;

        const prevBg = this.scene.background;
        this.scene.background = new THREE.Color('#f3f4f6');

        const prodConf = this.manifest?.products?.[this.currentProductId];
        const target = this.controls.target.clone();
        const dist = prodConf?.cameraDistance || this.camera.position.distanceTo(target);

        try {
            for (const id of sideIds) {
                const zone = (this.currentZones || []).find(z => z.id === id);
                const dir = zone ? new THREE.Vector3(...zone.normal) : new THREE.Vector3(0, 0, 1);
                dir.y = 0;
                if (dir.lengthSq() < 1e-6) dir.set(0, 0, 1);
                dir.normalize();

                const cam = this.camera.clone();
                cam.aspect = width / height;
                cam.position.copy(target).addScaledVector(dir, dist);
                cam.position.y += 0.05;
                cam.lookAt(target);
                cam.updateProjectionMatrix();

                off.render(this.scene, cam);
                const c = document.createElement('canvas');
                c.width = width;
                c.height = height;
                c.getContext('2d').drawImage(off.domElement, 0, 0);
                out[id] = c;
            }
        } finally {
            this.scene.background = prevBg;
            off.dispose();
            if (off.forceContextLoss) off.forceContextLoss();
        }
        return out;
    }

    autoNormalizeModel(modelGroup, prodConf) {
        modelGroup.updateMatrixWorld(true);

        // 1. Optional front rotation (degrees, e.g. 180 if model faces -Z)
        if (typeof prodConf?.frontRotation === 'number' && prodConf.frontRotation !== 0) {
            const rad = (prodConf.frontRotation * Math.PI) / 180;
            modelGroup.rotation.y += rad;
            modelGroup.updateMatrixWorld(true);
        }

        // 2. Measure raw bounding box
        let bbox = new THREE.Box3().setFromObject(modelGroup);
        let size = new THREE.Vector3(); bbox.getSize(size);
        let center = new THREE.Vector3(); bbox.getCenter(center);

        // 3. Auto-scale to standard target height (meters)
        const targetHeight = prodConf?.targetHeight || 0.72;
        if (size.y > 0.001) {
            const scaleFactor = targetHeight / size.y;
            modelGroup.scale.multiplyScalar(scaleFactor);

            if (prodConf?.modelScale && Array.isArray(prodConf.modelScale)) {
                modelGroup.scale.x *= prodConf.modelScale[0];
                modelGroup.scale.y *= prodConf.modelScale[1];
                modelGroup.scale.z *= prodConf.modelScale[2];
            }
            modelGroup.updateMatrixWorld(true);
        }

        // 4. Re-calculate bounding box after scaling
        bbox.setFromObject(modelGroup);
        bbox.getSize(size);
        bbox.getCenter(center);

        // 5. Center horizontally (X, Z) and position on ground plane (y = -0.35 above shadow plane)
        const groundY = -0.35;
        modelGroup.position.x += -center.x;
        modelGroup.position.y += (groundY - bbox.min.y);
        modelGroup.position.z += -center.z;

        if (prodConf?.positionOffset && Array.isArray(prodConf.positionOffset)) {
            modelGroup.position.x += prodConf.positionOffset[0];
            modelGroup.position.y += prodConf.positionOffset[1];
            modelGroup.position.z += prodConf.positionOffset[2];
        }
        modelGroup.updateMatrixWorld(true);

        // 6. Remember where the manifest's model space (base at y = 0, centred X/Z) sits in the world
        const off = Array.isArray(prodConf?.positionOffset) ? prodConf.positionOffset : [0, 0, 0];
        modelGroup.userData.baseOrigin = new THREE.Vector3(off[0] || 0, groundY + (off[1] || 0), off[2] || 0);
    }

    /** Points the camera at the product's front: manifest cameraTarget/cameraDistance, else auto. */
    frameCamera(modelGroup, prodConf) {
        const bbox = new THREE.Box3().setFromObject(modelGroup);
        const size = new THREE.Vector3(); bbox.getSize(size);
        const center = new THREE.Vector3(); bbox.getCenter(center);

        const maxDim = Math.max(size.x, size.y, size.z);
        const fovRad = (this.camera.fov * Math.PI) / 180;
        const autoDist = (maxDim / 2) / Math.tan(fovRad / 2) * 1.35;
        const dist = prodConf?.cameraDistance || autoDist;

        let target;
        if (Array.isArray(prodConf?.cameraTarget)) {
            target = new THREE.Vector3(...prodConf.cameraTarget);
            if (isBaseFrame(this.manifest)) target.add(modelGroup.userData.baseOrigin || new THREE.Vector3());
        } else {
            target = new THREE.Vector3(center.x, center.y + size.y * 0.05, center.z);
        }

        // Small products (cap, visor) need the camera closer than the default 0.6 m limit
        this.controls.minDistance = Math.min(0.6, dist * 0.6);
        this.controls.maxDistance = Math.max(3.5, dist * 2.5);

        this.camera.position.set(target.x, target.y + dist * 0.03, target.z + dist);
        this.controls.target.copy(target);
        this.controls.update();
    }

    findPrimaryMesh(modelGroup, prodConf) {
        const materialHint = prodConf?.materialName?.toLowerCase();
        let primary = null;

        // Pass 1: Look for mesh with name/material matching 'front', 'fabric', or material hint
        modelGroup.traverse((child) => {
            if (child.isMesh && !primary) {
                const name = (child.name || '').toLowerCase();
                const matName = (child.material?.name || '').toLowerCase();
                if (materialHint && (name.includes(materialHint) || matName.includes(materialHint))) {
                    primary = child;
                } else if (name.includes('front') || matName.includes('front') || name.includes('garment')) {
                    primary = child;
                }
            }
        });

        // Pass 2: Fallback to largest mesh by geometry bounding box volume
        if (!primary) {
            let maxVolume = -1;
            modelGroup.traverse((child) => {
                if (child.isMesh && child.geometry) {
                    if (!child.geometry.boundingBox) child.geometry.computeBoundingBox();
                    const b = child.geometry.boundingBox;
                    if (b) {
                        const vol = (b.max.x - b.min.x) * (b.max.y - b.min.y) * (b.max.z - b.min.z);
                        if (vol > maxVolume) {
                            maxVolume = vol;
                            primary = child;
                        }
                    }
                }
            });
        }

        // Pass 3: Any mesh
        if (!primary) {
            modelGroup.traverse((child) => {
                if (child.isMesh && !primary) primary = child;
            });
        }

        return primary;
    }

    /**
     * Returns the product's print zones in world space. Manifest values win; any missing
     * position/normal/size for ids front/back/rear is derived from the bounding box, so
     * procedural placeholders work too. Other ids (e.g. future sleeves) need explicit values.
     */
    resolvePrintZones(modelGroup, prodConf) {
        let list = Array.isArray(prodConf?.printZones) ? prodConf.printZones : null;
        if (!list) list = prodConf?.printZone ? [{ id: 'front', label: 'Front', ...prodConf.printZone }] : [{ id: 'front', label: 'Front' }];

        const bbox = new THREE.Box3().setFromObject(modelGroup);
        const size = new THREE.Vector3(); bbox.getSize(size);
        const center = new THREE.Vector3(); bbox.getCenter(center);
        const y = center.y + size.y * 0.08;

        const derived = {
            front: { position: [center.x, y, bbox.max.z - 0.03], normal: [0, 0, 1] },
            back:  { position: [center.x, y, bbox.min.z + 0.03], normal: [0, 0, -1] },
            rear:  { position: [center.x, y, bbox.min.z + 0.03], normal: [0, 0, -1] }
        };
        const defaultSize = [size.x * 0.22, size.y * 0.32, 0.18];
        const origin = isBaseFrame(this.manifest)
            ? (modelGroup.userData.baseOrigin || new THREE.Vector3())
            : new THREE.Vector3();
        const toWorld = (p) => [p[0] + origin.x, p[1] + origin.y, p[2] + origin.z];

        return list.map((z) => {
            if (!z || typeof z.id !== 'string') return null;
            const d = derived[z.id];
            if (!d && !(z.position && z.normal && z.size)) {
                console.warn(`[Welzee 3D] Zone "${z.id}" needs position, normal and size in the manifest.`);
                return null;
            }
            return {
                id: z.id,
                label: z.label || (z.id.charAt(0).toUpperCase() + z.id.slice(1)),
                position: z.position ? toWorld(z.position) : d.position,
                normal: z.normal || d.normal,
                size: z.size || defaultSize,
                guideAspect: z.guideAspect || 1.0
            };
        }).filter(Boolean);
    }

    async tryLoadGLB(glbPath, prodConf) {
        if (!glbPath) return null;

        const version = this.manifest?.version || '1.1.0';
        const url = glbPath + (glbPath.includes('?') ? '&' : '?') + 'v=' + encodeURIComponent(version);

        // 1. Verify existence via HEAD request
        let check;
        try {
            check = await fetch(url, { method: 'HEAD' });
        } catch (netErr) {
            console.warn(`[Welzee 3D] Network error reaching ${url}:`, netErr);
            throw new Error(`Cannot reach ${glbPath} (${netErr.message})`);
        }

        if (!check.ok) {
            console.warn(`[Welzee 3D] File not found: ${url} (HTTP ${check.status})`);
            throw new Error(`HTTP ${check.status} for ${glbPath}`);
        }

        // 2. Exact GLB header parsing (12-byte GLB header + 8-byte JSON chunk header)
        // Never fragile: reads exact JSON chunk length without arbitrary truncation
        let extensionsUsed = [];
        try {
            const headResp = await fetch(url, { headers: { 'Range': 'bytes=0-19' } });
            if (headResp.status === 206 || headResp.status === 200) {
                const headBuf = await headResp.arrayBuffer();
                const headView = new DataView(headBuf);
                // Magic 0x46546C67 = "glTF"
                if (headView.byteLength >= 20 && headView.getUint32(0, true) === 0x46546C67) {
                    const jsonChunkLen = headView.getUint32(12, true);
                    const chunkType = headView.getUint32(16, true);
                    // Chunk type 0x4E4F534A = "JSON"
                    if (chunkType === 0x4E4F534A && jsonChunkLen > 0) {
                        const jsonRangeResp = await fetch(url, {
                            headers: { 'Range': `bytes=20-${20 + jsonChunkLen - 1}` }
                        });
                        if (jsonRangeResp.status === 206 || jsonRangeResp.status === 200) {
                            const jsonBuf = await jsonRangeResp.arrayBuffer();
                            const jsonStr = new TextDecoder('utf-8').decode(jsonBuf);
                            const parsed = JSON.parse(jsonStr);
                            extensionsUsed = parsed.extensionsUsed || parsed.extensionsRequired || [];
                        }
                    }
                }
            }
        } catch (e) {
            // Non-fatal, loader will attempt standard load
        }

        // 3. Dynamically import GLTFLoader
        const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
        const loader = new GLTFLoader();

        // 4. Configure compression decoders ONLY if extensionsUsed requires them
        if (extensionsUsed.includes('KHR_draco_mesh_compression')) {
            const { DRACOLoader } = await import('three/addons/loaders/DRACOLoader.js');
            const dracoLoader = new DRACOLoader();
            dracoLoader.setDecoderPath('assets/js/3d/lib/draco/gltf/');
            loader.setDRACOLoader(dracoLoader);
        }

        if (extensionsUsed.includes('EXT_meshopt_compression')) {
            const { MeshoptDecoder } = await import('./lib/meshopt/meshopt_decoder.module.js');
            loader.setMeshoptDecoder(MeshoptDecoder);
        }

        if (extensionsUsed.includes('KHR_texture_basisu')) {
            const { KTX2Loader } = await import('three/addons/loaders/KTX2Loader.js');
            const ktx2Loader = new KTX2Loader();
            ktx2Loader.setTranscoderPath('assets/js/3d/lib/basis/');
            ktx2Loader.detectSupport(this.renderer);
            loader.setKTX2Loader(ktx2Loader);
        }

        // 5. Load GLTF Scene
        return new Promise((resolve, reject) => {
            loader.load(
                url,
                (gltf) => {
                    const scene = gltf.scene || gltf.scenes[0];
                    if (scene) ensureNormals(scene);
                    resolve(scene);
                },
                undefined,
                (err) => {
                    console.warn(`[Welzee 3D] GLTFLoader error on ${url}:`, err);
                    reject(new Error(err.message || 'GLTF parse failed'));
                }
            );
        });
    }

    setProductColor(hex) {
        this.currentColorHex = hex;
        if (!this.currentModelGroup) return;

        const threeColor = new THREE.Color(hex);
        const prodConf = this.manifest?.products?.[this.currentProductId];
        const materialHint = prodConf?.materialName?.toLowerCase();

        // Exclude hardware/zipper/trim parts from fabric tinting
        const nonFabricRegex = /zipper|metal|hardware|eyelet|puller|button|slider|aglet|teeth|cord_end/i;
        // Parts with a fixed colour (e.g. varsity sleeves), keyed by exact material name
        const fixed = (prodConf?.fixedMaterialColors && typeof prodConf.fixedMaterialColors === 'object')
            ? prodConf.fixedMaterialColors : {};

        this.currentModelGroup.traverse((child) => {
            if (child.isMesh && child.material && child.name !== 'artworkDecal') {
                const materials = Array.isArray(child.material) ? child.material : [child.material];
                materials.forEach(mat => {
                    const fixedHex = fixed[mat.name];
                    if (typeof fixedHex === 'string' && HEX6_RE.test(fixedHex)) {
                        mat.color.set(fixedHex);
                        mat.needsUpdate = true;
                        return;
                    }
                    const matName = (mat.name || '').toLowerCase();
                    if (nonFabricRegex.test(matName)) {
                        return; // Keep metal/hardware intact
                    }

                    // Apply color if matches hint or no hint provided
                    if (!materialHint || matName.includes(materialHint) || matName.includes('fabric')) {
                        mat.color.copy(threeColor);
                        mat.needsUpdate = true;
                    }
                });
            }
        });

        this.requestRender();
    }

    setDesignMethod(method) {
        this.designMethod = method;
        if (this.decalManager) {
            this.decalManager.setDesignMethod(method);
        }
        this.requestRender();
    }

    getDesignSnapshot(options = {}) {
        const snapWidth = options.width || 1200;
        const snapHeight = options.height || 960;

        const offRenderer = new THREE.WebGLRenderer({
            antialias: true,
            preserveDrawingBuffer: true,
            alpha: false
        });
        offRenderer.setSize(snapWidth, snapHeight);
        offRenderer.toneMapping = this.renderer.toneMapping;
        offRenderer.toneMappingExposure = this.renderer.toneMappingExposure;
        offRenderer.outputColorSpace = THREE.SRGBColorSpace;

        const prevBg = this.scene.background;
        this.scene.background = new THREE.Color('#f3f4f6');

        const snapCamera = this.camera.clone();
        snapCamera.aspect = snapWidth / snapHeight;

        if (options.position) {
            snapCamera.position.set(...options.position);
            const target = options.target || this.controls.target;
            const tx = target[0] !== undefined ? target[0] : target.x;
            const ty = target[1] !== undefined ? target[1] : target.y;
            const tz = target[2] !== undefined ? target[2] : target.z;
            snapCamera.lookAt(tx, ty, tz);
        }
        snapCamera.updateProjectionMatrix();

        offRenderer.render(this.scene, snapCamera);

        const format = options.format || 'image/jpeg';
        const quality = options.quality !== undefined ? options.quality : 0.90;
        const dataUrl = offRenderer.domElement.toDataURL(format, quality);

        this.scene.background = prevBg;
        offRenderer.dispose();

        return dataUrl;
    }
}
