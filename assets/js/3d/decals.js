/**
 * assets/js/3d/decals.js
 *
 * Projects the 2D artwork onto the 3D garment, one decal per print zone
 * (front, back, rear ... and later sleeves), using Three.js DecalGeometry.
 *
 * Every zone ("side") owns its own artwork canvas + CanvasTexture, so the front
 * and back show different artwork at the same time. The visible Fabric editor
 * always edits the ACTIVE side; its renders are copied into that side's canvas.
 * Inactive sides keep their last image (or are filled from saved Fabric JSON by
 * order-state.js via setSideArtwork()).
 *
 * Embroidery = soft raised relief + satin sheen (bump map); Print = flat matte.
 */

import * as THREE from 'three';
import { DecalGeometry } from 'three/addons/geometries/DecalGeometry.js';
import * as BufferGeometryUtils from 'three/addons/utils/BufferGeometryUtils.js';

// Artwork editor is 340x280 CSS px; side textures are kept at 2x for crisp decals.
export const ARTWORK_ASPECT = 340 / 280;
const SIDE_TEX_W = 680;
const SIDE_TEX_H = 560;
const BUMP_SIZE = 512;
const HARDWARE_RE = /zipper|metal|hardware|eyelet|button|puller|string|cord/i;

/**
 * Picks out the triangles of `mesh` that can receive the decal for one zone:
 *  1. inside the projector box (+8% margin), and
 *  2. facing the projector (outer cloth layer; skips the inner layer and the
 *     opposite side of thin products, e.g. the front of a bag for a back print).
 * Result is cached per mesh + zone box. Index buffer is ALWAYS 32-bit, because the
 * values are vertex numbers of the full mesh (the hoodie has ~104k vertices).
 */
function getPrintZoneSubmesh(mesh, position, orientation, size, zoneNormal) {
    const geo = mesh.geometry;
    if (!geo || !geo.attributes.position) return mesh;

    const posAttr = geo.attributes.position;
    const normAttr = geo.attributes.normal || null;
    // Fast path needs plain float arrays; anything exotic goes straight to DecalGeometry
    if (posAttr.isInterleavedBufferAttribute || posAttr.normalized ||
        (normAttr && (normAttr.isInterleavedBufferAttribute || normAttr.normalized))) {
        return mesh;
    }

    const f = (n) => n.toFixed(3);
    const cacheKey = `_submesh_${f(position.x)}_${f(position.y)}_${f(position.z)}_` +
        `${f(orientation.x)}_${f(orientation.y)}_${f(orientation.z)}_${f(size.x)}_${f(size.y)}_${f(size.z)}`;
    if (mesh.userData[cacheKey]) return mesh.userData[cacheKey];

    const hx = size.x * 0.5 * 1.08;
    const hy = size.y * 0.5 * 1.08;
    const hz = size.z * 0.5 * 1.08;

    // Projector space -> world -> mesh local
    const projectorMatrix = new THREE.Matrix4().makeRotationFromEuler(orientation).setPosition(position);
    const worldToLocal = mesh.matrixWorld.clone().invert();
    const projToLocal = projectorMatrix.clone().premultiply(worldToLocal);

    let minX = Infinity, minY = Infinity, minZ = Infinity;
    let maxX = -Infinity, maxY = -Infinity, maxZ = -Infinity;
    const corner = new THREE.Vector3();
    for (const cx of [-hx, hx]) for (const cy of [-hy, hy]) for (const cz of [-hz, hz]) {
        corner.set(cx, cy, cz).applyMatrix4(projToLocal);
        minX = Math.min(minX, corner.x); maxX = Math.max(maxX, corner.x);
        minY = Math.min(minY, corner.y); maxY = Math.max(maxY, corner.y);
        minZ = Math.min(minZ, corner.z); maxZ = Math.max(maxZ, corner.z);
    }

    // Zone normal in mesh-local space (valid for rotation + uniform scale)
    const nl = zoneNormal.clone().transformDirection(worldToLocal);
    const nx = nl.x, ny = nl.y, nz = nl.z;

    const pos = posAttr.array;
    const nrm = normAttr ? normAttr.array : null;
    const idx = geo.index ? geo.index.array : null;
    const triCount = idx ? idx.length / 3 : posAttr.count / 3;

    const inBox = [];
    const facing = [];
    for (let t = 0; t < triCount; t++) {
        const i0 = idx ? idx[t * 3] : t * 3;
        const i1 = idx ? idx[t * 3 + 1] : t * 3 + 1;
        const i2 = idx ? idx[t * 3 + 2] : t * 3 + 2;
        const p0 = i0 * 3, p1 = i1 * 3, p2 = i2 * 3;

        const x0 = pos[p0], x1 = pos[p1], x2 = pos[p2];
        if (Math.max(x0, x1, x2) < minX || Math.min(x0, x1, x2) > maxX) continue;
        const y0 = pos[p0 + 1], y1 = pos[p1 + 1], y2 = pos[p2 + 1];
        if (Math.max(y0, y1, y2) < minY || Math.min(y0, y1, y2) > maxY) continue;
        const z0 = pos[p0 + 2], z1 = pos[p1 + 2], z2 = pos[p2 + 2];
        if (Math.max(z0, z1, z2) < minZ || Math.min(z0, z1, z2) > maxZ) continue;

        inBox.push(i0, i1, i2);

        // Facing test: averaged vertex normals, else geometric face normal
        let fx, fy, fz;
        if (nrm) {
            fx = nrm[p0] + nrm[p1] + nrm[p2];
            fy = nrm[p0 + 1] + nrm[p1 + 1] + nrm[p2 + 1];
            fz = nrm[p0 + 2] + nrm[p1 + 2] + nrm[p2 + 2];
        } else {
            const ax = x1 - x0, ay = y1 - y0, az = z1 - z0;
            const bx = x2 - x0, by = y2 - y0, bz = z2 - z0;
            fx = ay * bz - az * by; fy = az * bx - ax * bz; fz = ax * by - ay * bx;
        }
        if (fx * nx + fy * ny + fz * nz > 0) facing.push(i0, i1, i2);
    }

    // Prefer the facing set; fall back to the plain box set if normals are unusable
    const chosen = facing.length >= 9 ? facing : inBox;
    if (chosen.length === 0) return mesh;

    const subGeo = new THREE.BufferGeometry();
    subGeo.setAttribute('position', posAttr);
    if (normAttr) subGeo.setAttribute('normal', normAttr);
    subGeo.setIndex(new THREE.BufferAttribute(new Uint32Array(chosen), 1));

    const subMesh = new THREE.Mesh(subGeo, mesh.material);
    subMesh.matrixWorld = mesh.matrixWorld;
    mesh.userData[cacheKey] = subMesh;
    return subMesh;
}

export class DecalManager {
    constructor(viewer) {
        this.viewer = viewer;
        this.fabricCanvas = null;
        this.designMethod = 'embroidery';
        this.activeSide = 'front';
        this.sides = new Map();      // sideId -> side record (canvas, textures, material)
        this.decalMeshes = [];
        this._updateTimer = null;
        this._updateRaf = null;
        // One period of a soft diagonal thread ripple (height offsets, 0-255 scale)
        this._threadRipple = Array.from({ length: 6 }, (_, k) => 5 * Math.sin((2 * Math.PI * k) / 6));
    }

    /** Returns (creating on first use) the texture/material record for one side. */
    getSide(sideId) {
        let s = this.sides.get(sideId);
        if (s) return s;

        const canvas = document.createElement('canvas');
        canvas.width = SIDE_TEX_W;
        canvas.height = SIDE_TEX_H;
        const ctx = canvas.getContext('2d');

        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.minFilter = THREE.LinearFilter;
        texture.magFilter = THREE.LinearFilter;
        texture.generateMipmaps = false;
        const maxAniso = this.viewer?.renderer?.capabilities?.getMaxAnisotropy?.() || 1;
        texture.anisotropy = Math.min(4, maxAniso);

        const bumpCanvas = document.createElement('canvas');
        bumpCanvas.width = BUMP_SIZE;
        bumpCanvas.height = BUMP_SIZE;
        const bumpCtx = bumpCanvas.getContext('2d', { willReadFrequently: true });
        // Height data (not colour): default NoColorSpace. Mipmaps stop the fine
        // thread ripple from shimmering into lines when the camera zooms out.
        const bumpTexture = new THREE.CanvasTexture(bumpCanvas);
        bumpTexture.minFilter = THREE.LinearMipmapLinearFilter;
        bumpTexture.magFilter = THREE.LinearFilter;
        bumpTexture.generateMipmaps = true;

        const material = new THREE.MeshStandardMaterial({
            map: texture,
            transparent: true,
            depthTest: true,
            depthWrite: false,
            polygonOffset: true,
            polygonOffsetFactor: -4,
            polygonOffsetUnits: -4,
            side: THREE.FrontSide
        });

        s = { id: sideId, canvas, ctx, texture, bumpCanvas, bumpCtx, bumpTexture, material, hasArt: false };
        this.applyMethodToSide(s);
        this.sides.set(sideId, s);
        return s;
    }

    /** Binds the visible Fabric editor; its renders feed the ACTIVE side. */
    bindFabricCanvas(fabricCanvas) {
        this.fabricCanvas = fabricCanvas;
        fabricCanvas.on('after:render', () => this.scheduleActiveUpdate());
    }

    scheduleActiveUpdate() {
        if (this._updateTimer) clearTimeout(this._updateTimer);
        this._updateTimer = setTimeout(() => {
            this._updateTimer = null;
            if (this._updateRaf) cancelAnimationFrame(this._updateRaf);
            this._updateRaf = requestAnimationFrame(() => {
                this._updateRaf = null;
                this.copyEditorToActiveSide();
            });
        }, 40);
    }

    copyEditorToActiveSide() {
        if (!this.fabricCanvas) return;
        this.refreshSide(this.activeSide, this.fabricCanvas.getElement());
    }

    /** Writes any pending editor change into the current active side right now. */
    flushActive() {
        if (this._updateTimer || this._updateRaf) {
            clearTimeout(this._updateTimer);
            cancelAnimationFrame(this._updateRaf);
            this._updateTimer = null;
            this._updateRaf = null;
            this.copyEditorToActiveSide();
        }
    }

    /** Switches which side the editor feeds (flushes the old side first). */
    setActiveSide(sideId) {
        this.flushActive();
        this.activeSide = sideId;
        this.getSide(sideId);
    }

    /** Sets a side's artwork from any canvas element (null clears it). */
    setSideArtwork(sideId, sourceEl) {
        this.refreshSide(sideId, sourceEl);
    }

    /** Clears every side's artwork (used when the product changes). */
    clearAllSides() {
        this.flushActive();
        for (const id of this.sides.keys()) this.refreshSide(id, null);
    }

    refreshSide(sideId, sourceEl) {
        const s = this.getSide(sideId);
        s.ctx.clearRect(0, 0, SIDE_TEX_W, SIDE_TEX_H);
        s.hasArt = false;
        if (sourceEl && sourceEl.width > 0 && sourceEl.height > 0) {
            s.ctx.drawImage(sourceEl, 0, 0, SIDE_TEX_W, SIDE_TEX_H);
            s.hasArt = true;
        }
        s.texture.needsUpdate = true;
        if (this.designMethod === 'embroidery') {
            this.generateEmbroideryBump(s);
        }
        this.viewer?.requestRender?.();
    }

    /**
     * Height map from the artwork alpha: a softly padded plateau where the artwork
     * is, with a gentle diagonal thread ripple on top.
     */
    generateEmbroideryBump(s) {
        const ctx = s.bumpCtx;
        const w = BUMP_SIZE;
        const h = BUMP_SIZE;

        // Transparent buffer so alpha gives the true artwork shape; light blur rounds edges
        ctx.clearRect(0, 0, w, h);
        ctx.filter = 'blur(1.5px)';
        ctx.drawImage(s.canvas, 0, 0, w, h);
        ctx.filter = 'none';

        try {
            const imgData = ctx.getImageData(0, 0, w, h);
            const data = imgData.data;
            const ripple = this._threadRipple;
            const period = ripple.length;
            // 128 = flat fabric; artwork raised by ~26; +/-5 thread ripple fading with edge alpha
            for (let y = 0, i = 0; y < h; y++) {
                for (let x = 0; x < w; x++, i += 4) {
                    const a = data[i + 3] / 255;
                    const v = 128 + a * (26 + ripple[(x + y) % period]);
                    data[i] = v;
                    data[i + 1] = v;
                    data[i + 2] = v;
                    data[i + 3] = 255;
                }
            }
            ctx.putImageData(imgData, 0, 0);
        } catch (e) {
            // Tainted canvas: keep a flat bump
        }
        s.bumpTexture.needsUpdate = true;
    }

    applyMethodToSide(s) {
        const mat = s.material;
        if (this.designMethod === 'embroidery') {
            mat.roughness = 0.45; // satin thread sheen
            mat.metalness = 0.08;
            mat.bumpMap = s.bumpTexture;
            // r162 bump tilts the normal by ~bumpScale x height change per pixel;
            // 0.6 gives a subtle relief. Tune here.
            mat.bumpScale = 0.6;
        } else {
            mat.roughness = 0.85; // flat ink, matte
            mat.metalness = 0.02;
            mat.bumpMap = null;
            mat.bumpScale = 0.0;
        }
        mat.needsUpdate = true;
    }

    /** 'embroidery' | 'print' — applies to every side. */
    setDesignMethod(method) {
        this.designMethod = method;
        for (const s of this.sides.values()) {
            this.applyMethodToSide(s);
            if (method === 'embroidery') this.generateEmbroideryBump(s);
        }
        this.viewer?.requestRender?.();
    }

    removeDecals() {
        for (const m of this.decalMeshes) {
            m.geometry.dispose();
            if (m.parent) m.parent.remove(m);
        }
        this.decalMeshes = [];
    }

    /**
     * Builds one decal mesh per zone on the given model.
     * zones: [{ id, position:[x,y,z], normal:[x,y,z], size:[w,h,depth] }] in world space.
     */
    applyDecals(rootObject, zones) {
        this.removeDecals();
        if (!rootObject || !Array.isArray(zones)) return;
        rootObject.updateMatrixWorld(true);
        for (const zone of zones) {
            try {
                const mesh = this.buildZoneDecal(rootObject, zone);
                if (mesh) {
                    rootObject.add(mesh);
                    this.decalMeshes.push(mesh);
                }
            } catch (err) {
                console.warn(`[Welzee 3D Decal] Zone "${zone?.id}" failed:`, err);
            }
        }
        this.viewer?.requestRender?.();
    }

    /** Backwards-compatible single-zone entry point. */
    applyDecal(rootObject, printZoneConfig) {
        this.applyDecals(rootObject, [{ id: 'front', ...printZoneConfig }]);
    }

    buildZoneDecal(rootObject, zone) {
        const position = new THREE.Vector3(...zone.position);
        const normal = new THREE.Vector3(...zone.normal).normalize();

        // Fit the artwork's aspect inside the zone so logos/text are never stretched
        let sx = zone.size[0], sy = zone.size[1];
        if (sx / sy > ARTWORK_ASPECT) sx = sy * ARTWORK_ASPECT; else sy = sx / ARTWORK_ASPECT;
        const size = new THREE.Vector3(sx, sy, zone.size[2]);

        // Projector orientation: +Z along the zone normal
        const dummy = new THREE.Object3D();
        dummy.position.copy(position);
        dummy.lookAt(position.clone().add(normal));
        const orientation = dummy.rotation;

        // Coarse filter: only meshes whose bounds touch the zone neighbourhood
        const zoneBox = new THREE.Box3(
            position.clone().sub(new THREE.Vector3(sx, sy, zone.size[2]).multiplyScalar(2.0)),
            position.clone().add(new THREE.Vector3(sx, sy, zone.size[2]).multiplyScalar(2.0))
        );

        const isFabric = (child) => {
            if (!child.isMesh || !child.geometry || child.name === 'artworkDecal') return false;
            const n = child.name || '';
            const m = child.material?.name || '';
            return !HARDWARE_RE.test(n) && !HARDWARE_RE.test(m);
        };

        let candidates = [];
        rootObject.traverse((child) => {
            if (!isFabric(child)) return;
            if (!child.geometry.boundingBox) child.geometry.computeBoundingBox();
            const wb = child.geometry.boundingBox.clone().applyMatrix4(child.matrixWorld);
            if (wb.intersectsBox(zoneBox)) candidates.push(child);
        });
        if (candidates.length === 0) {
            rootObject.traverse((child) => { if (isFabric(child)) candidates.push(child); });
        }

        const geometries = [];
        for (const mesh of candidates) {
            mesh.updateMatrixWorld(true);
            try {
                const target = getPrintZoneSubmesh(mesh, position, orientation, size, normal);
                const g = new DecalGeometry(target, position, orientation, size);
                if (g.attributes.position && g.attributes.position.count > 0) geometries.push(g);
                else g.dispose();
            } catch (err) {
                // Skip meshes that cannot be projected
            }
        }
        if (geometries.length === 0) {
            console.warn('[Welzee 3D Decal] No decal vertices generated for zone:', zone.id);
            return null;
        }

        const merged = geometries.length > 1 ? BufferGeometryUtils.mergeGeometries(geometries) : geometries[0];
        if (geometries.length > 1) geometries.forEach(g => g.dispose());

        // Vertices are in world space; move them into rootObject space so they follow the garment
        merged.applyMatrix4(rootObject.matrixWorld.clone().invert());

        const side = this.getSide(zone.id);
        const decal = new THREE.Mesh(merged, side.material);
        decal.name = 'artworkDecal';
        decal.userData.sideId = zone.id;
        decal.renderOrder = 1;
        return decal;
    }
}
