/**
 * assets/js/3d/procedural.js
 *
 * Procedural fallback 3D garment generator for Welzee.
 * Generates stylized, clean, low-poly procedural meshes when real .glb files
 * are not yet available, ensuring full interactivity today.
 *
 * Real GLB Spec for 3D artists:
 * - Format: Binary glTF (.glb)
 * - Coordinate convention: Y-up, front facing +Z
 * - Scale: Real-world meters (e.g. torso height ~0.7m, width ~0.5m)
 * - Mesh hierarchy: One main garment mesh named "garmentMesh" (or root child)
 * - Material: Primary material named "fabric" (MeshStandardMaterial or MeshPhysicalMaterial)
 * - Triangles: Under 60,000 tris
 * - Textures: Embedded WebP, max 1024x1024 (BaseColor, Normal, Roughness, AO)
 * - Compression: Draco or Meshopt compressed, file size < 2 MB
 */

import * as THREE from 'three';

/**
 * Creates a subtle procedural cotton/canvas weave bump texture using HTML5 Canvas.
 * Cached so it is only generated once.
 */
let cachedFabricBump = null;
export function getFabricBumpTexture() {
    if (cachedFabricBump) return cachedFabricBump;

    const size = 128;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');

    ctx.fillStyle = '#808080';
    ctx.fillRect(0, 0, size, size);

    // Subtle cross-hatch weave pattern
    ctx.strokeStyle = '#999999';
    ctx.lineWidth = 1;
    for (let x = 0; x < size; x += 4) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, size);
        ctx.stroke();
    }
    ctx.strokeStyle = '#666666';
    for (let y = 0; y < size; y += 4) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(size, y);
        ctx.stroke();
    }

    const texture = new THREE.CanvasTexture(canvas);
    texture.wrapS = THREE.RepeatWrapping;
    texture.wrapT = THREE.RepeatWrapping;
    texture.repeat.set(8, 8);
    texture.needsUpdate = true;
    cachedFabricBump = texture;
    return texture;
}

/**
 * Generates a soft contact shadow texture for the ground plane.
 */
let cachedShadowTexture = null;
export function getContactShadowTexture() {
    if (cachedShadowTexture) return cachedShadowTexture;

    const size = 256;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');

    const grad = ctx.createRadialGradient(size / 2, size / 2, 10, size / 2, size / 2, size / 2);
    grad.addColorStop(0, 'rgba(0, 0, 0, 0.45)');
    grad.addColorStop(0.5, 'rgba(0, 0, 0, 0.15)');
    grad.addColorStop(1, 'rgba(0, 0, 0, 0)');

    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, size, size);

    const texture = new THREE.CanvasTexture(canvas);
    cachedShadowTexture = texture;
    return texture;
}

/**
 * Helper to create the default fabric material.
 */
export function createFabricMaterial(colorHex = '#ffffff') {
    const bump = getFabricBumpTexture();
    const material = new THREE.MeshStandardMaterial({
        color: new THREE.Color(colorHex),
        roughness: 0.85,
        metalness: 0.05,
        bumpMap: bump,
        bumpScale: 0.003,
        side: THREE.DoubleSide
    });
    material.name = 'fabric';
    return material;
}

/**
 * Creates a procedural 3D model for the given product ID.
 * Returns a THREE.Group containing the mesh hierarchy.
 */
export function createProceduralProduct(productId, colorHex = '#ffffff') {
    const group = new THREE.Group();
    group.name = 'product_' + productId;

    const fabricMat = createFabricMaterial(colorHex);

    switch (productId) {
        case 'tshirt':
            buildTShirt(group, fabricMat);
            break;
        case 'polo':
            buildPolo(group, fabricMat);
            break;
        case 'hoodie':
            buildHoodie(group, fabricMat);
            break;
        case 'varsity':
            buildVarsity(group, fabricMat);
            break;
        case 'cap':
            buildCap(group, fabricMat);
            break;
        case 'visor':
            buildVisor(group, fabricMat);
            break;
        case 'bag':
            buildBag(group, fabricMat);
            break;
        default:
            buildTShirt(group, fabricMat);
            break;
    }

    return group;
}

/* -------------------------------------------------------------
 * Procedural Model Builders
 * ------------------------------------------------------------- */

function buildTShirt(group, mat) {
    // Torso: gently tapered smoothed cylinder
    const torsoGeo = new THREE.CylinderGeometry(0.24, 0.22, 0.65, 20, 1, false);
    torsoGeo.scale(1.0, 1.0, 0.65); // flatter front/back
    const torso = new THREE.Mesh(torsoGeo, mat);
    torso.position.y = 0.05;
    torso.name = 'garmentMesh';
    group.add(torso);

    // Left sleeve
    const sleeveLGeo = new THREE.CylinderGeometry(0.08, 0.09, 0.22, 16);
    const sleeveL = new THREE.Mesh(sleeveLGeo, mat);
    sleeveL.rotation.z = -Math.PI / 4;
    sleeveL.position.set(-0.28, 0.26, 0);
    group.add(sleeveL);

    // Right sleeve
    const sleeveRGeo = new THREE.CylinderGeometry(0.08, 0.09, 0.22, 16);
    const sleeveR = new THREE.Mesh(sleeveRGeo, mat);
    sleeveR.rotation.z = Math.PI / 4;
    sleeveR.position.set(0.28, 0.26, 0);
    group.add(sleeveR);

    // Collar ring (crewneck)
    const collarGeo = new THREE.TorusGeometry(0.10, 0.018, 10, 20);
    collarGeo.rotateX(Math.PI / 2);
    const collar = new THREE.Mesh(collarGeo, mat);
    collar.position.set(0, 0.38, 0);
    group.add(collar);
}

function buildPolo(group, mat) {
    // Torso: structured polo shirt
    const torsoGeo = new THREE.CylinderGeometry(0.24, 0.225, 0.65, 20, 1, false);
    torsoGeo.scale(1.0, 1.0, 0.66);
    const torso = new THREE.Mesh(torsoGeo, mat);
    torso.position.y = 0.05;
    torso.name = 'garmentMesh';
    group.add(torso);

    // Sleeves with cuffs
    const sleeveLGeo = new THREE.CylinderGeometry(0.08, 0.085, 0.20, 16);
    const sleeveL = new THREE.Mesh(sleeveLGeo, mat);
    sleeveL.rotation.z = -Math.PI / 4.2;
    sleeveL.position.set(-0.27, 0.26, 0);
    group.add(sleeveL);

    const sleeveRGeo = new THREE.CylinderGeometry(0.08, 0.085, 0.20, 16);
    const sleeveR = new THREE.Mesh(sleeveRGeo, mat);
    sleeveR.rotation.z = Math.PI / 4.2;
    sleeveR.position.set(0.27, 0.26, 0);
    group.add(sleeveR);

    // Polo collar & placket
    const placketGeo = new THREE.BoxGeometry(0.045, 0.16, 0.02);
    const placket = new THREE.Mesh(placketGeo, mat);
    placket.position.set(0, 0.28, 0.145);
    group.add(placket);

    // Collar wings
    const collarL = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.035, 0.015), mat);
    collarL.rotation.set(-0.2, 0.3, -0.4);
    collarL.position.set(-0.06, 0.36, 0.12);
    group.add(collarL);

    const collarR = new THREE.Mesh(new THREE.BoxGeometry(0.09, 0.035, 0.015), mat);
    collarR.rotation.set(-0.2, -0.3, 0.4);
    collarR.position.set(0.06, 0.36, 0.12);
    group.add(collarR);
}

function buildHoodie(group, mat) {
    // Torso: thicker, relaxed cut
    const torsoGeo = new THREE.CylinderGeometry(0.26, 0.24, 0.68, 20, 1, false);
    torsoGeo.scale(1.05, 1.0, 0.72);
    const torso = new THREE.Mesh(torsoGeo, mat);
    torso.position.y = 0.05;
    torso.name = 'garmentMesh';
    group.add(torso);

    // Long sleeves
    const sleeveLGeo = new THREE.CylinderGeometry(0.085, 0.07, 0.44, 16);
    const sleeveL = new THREE.Mesh(sleeveLGeo, mat);
    sleeveL.rotation.z = -Math.PI / 4.8;
    sleeveL.position.set(-0.35, 0.14, 0);
    group.add(sleeveL);

    const sleeveRGeo = new THREE.CylinderGeometry(0.085, 0.07, 0.44, 16);
    const sleeveR = new THREE.Mesh(sleeveRGeo, mat);
    sleeveR.rotation.z = Math.PI / 4.8;
    sleeveR.position.set(0.35, 0.14, 0);
    group.add(sleeveR);

    // Kangaroo front pocket
    const pocketGeo = new THREE.BoxGeometry(0.22, 0.14, 0.03);
    const pocket = new THREE.Mesh(pocketGeo, mat);
    pocket.position.set(0, -0.12, 0.165);
    group.add(pocket);

    // Hood draped at neck/back
    const hoodGeo = new THREE.SphereGeometry(0.18, 18, 12, 0, Math.PI * 2, 0, Math.PI * 0.65);
    hoodGeo.scale(0.9, 1.1, 1.1);
    const hood = new THREE.Mesh(hoodGeo, mat);
    hood.position.set(0, 0.40, -0.05);
    hood.rotation.x = -0.4;
    group.add(hood);
}

function buildVarsity(group, mat) {
    // Torso
    const torsoGeo = new THREE.CylinderGeometry(0.25, 0.23, 0.64, 20, 1, false);
    torsoGeo.scale(1.05, 1.0, 0.72);
    const torso = new THREE.Mesh(torsoGeo, mat);
    torso.position.y = 0.05;
    torso.name = 'garmentMesh';
    group.add(torso);

    // Contrasting/stylized leather/wool sleeves
    const sleeveMat = mat.clone();
    sleeveMat.roughness = 0.6; // slight sheen for leather-like sleeves

    const sleeveL = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.075, 0.44, 16), sleeveMat);
    sleeveL.rotation.z = -Math.PI / 4.8;
    sleeveL.position.set(-0.35, 0.14, 0);
    group.add(sleeveL);

    const sleeveR = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.075, 0.44, 16), sleeveMat);
    sleeveR.rotation.z = Math.PI / 4.8;
    sleeveR.position.set(0.35, 0.14, 0);
    group.add(sleeveR);

    // Front snap placket
    const snapBar = new THREE.Mesh(new THREE.BoxGeometry(0.035, 0.62, 0.015), mat);
    snapBar.position.set(0, 0.05, 0.17);
    group.add(snapBar);

    // Striped ribbed collar
    const collar = new THREE.Mesh(new THREE.TorusGeometry(0.11, 0.025, 10, 20), mat);
    collar.rotateX(Math.PI / 2);
    collar.position.set(0, 0.38, 0);
    group.add(collar);
}

function buildCap(group, mat) {
    // Crown dome
    const crownGeo = new THREE.SphereGeometry(0.14, 20, 12, 0, Math.PI * 2, 0, Math.PI * 0.52);
    crownGeo.scale(1.0, 0.9, 1.05);
    const crown = new THREE.Mesh(crownGeo, mat);
    crown.name = 'garmentMesh';
    crown.position.set(0, 0, 0);
    group.add(crown);

    // Curved front brim / visor
    const brimGeo = new THREE.CylinderGeometry(0.15, 0.15, 0.008, 20, 1, false, -Math.PI * 0.35, Math.PI * 0.7);
    brimGeo.scale(1.0, 1.0, 1.3);
    const brim = new THREE.Mesh(brimGeo, mat);
    brim.rotation.x = 0.18;
    brim.position.set(0, -0.01, 0.09);
    group.add(brim);

    // Top squatchee button
    const button = new THREE.Mesh(new THREE.SphereGeometry(0.015, 12, 6), mat);
    button.position.set(0, 0.13, 0);
    group.add(button);
}

function buildVisor(group, mat) {
    // Open top headband ring
    const bandGeo = new THREE.CylinderGeometry(0.135, 0.135, 0.05, 20, 1, true);
    bandGeo.scale(1.0, 1.0, 1.05);
    const band = new THREE.Mesh(bandGeo, mat);
    band.name = 'garmentMesh';
    band.position.set(0, 0.02, 0);
    group.add(band);

    // Curved front visor bill
    const billGeo = new THREE.CylinderGeometry(0.145, 0.145, 0.008, 20, 1, false, -Math.PI * 0.35, Math.PI * 0.7);
    billGeo.scale(1.0, 1.0, 1.35);
    const bill = new THREE.Mesh(billGeo, mat);
    bill.rotation.x = 0.18;
    bill.position.set(0, 0, 0.09);
    group.add(bill);
}

function buildBag(group, mat) {
    // Rectangular tote bag body
    const bodyGeo = new THREE.BoxGeometry(0.36, 0.42, 0.14);
    const body = new THREE.Mesh(bodyGeo, mat);
    body.position.set(0, -0.05, 0);
    body.name = 'garmentMesh';
    group.add(body);

    // Dual shoulder straps
    const strapL = new THREE.Mesh(new THREE.TorusGeometry(0.14, 0.012, 10, 20, Math.PI), mat);
    strapL.rotation.y = Math.PI / 2;
    strapL.position.set(-0.04, 0.16, 0);
    group.add(strapL);

    const strapR = new THREE.Mesh(new THREE.TorusGeometry(0.14, 0.012, 10, 20, Math.PI), mat);
    strapR.rotation.y = Math.PI / 2;
    strapR.position.set(0.04, 0.16, 0);
    group.add(strapR);
}
