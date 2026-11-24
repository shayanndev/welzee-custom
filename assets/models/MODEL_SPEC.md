# Welzee 3D Apparel Model Specification

This document provides technical guidelines and production budgets for 3D garment models supplied for the **Welzee 3D Apparel Customizer & Showcase**.

---

## 1. Technical Budgets & Target Metrics

| Parameter | Target Budget | Hard Maximum | Reason / Impact |
| :--- | :--- | :--- | :--- |
| **File Format** | Binary glTF (`.glb`) | `.glb` only | Compact, self-contained single file |
| **File Size** | **< 1.5 MB** | **2.0 MB** | Mobile 4G data & instant viewport loading |
| **Polygon Count** | **50,000 – 120,000 tris** | **150,000 tris** | Real-time 60fps rendering & fast Decal projection |
| **Vertex Count** | **30,000 – 70,000 verts** | **90,000 verts** | GPU buffer allocation & memory efficiency |
| **Textures** | WebP / PNG (max 1024×1024) | 2048×2048 | Mobile GPU VRAM limits |
| **Materials** | **1 primary fabric material** | 2 fabric mats | Clean runtime tinting & low draw-calls |
| **Compression** | Draco (`KHR_draco_mesh_compression`) | Draco or uncompressed | 85–95% file size reduction |

---

## 2. World Coordinate System & Orientation

To ensure seamless automatic alignment with the 3D scene, hangers, and camera presets:
1. **Up Axis**: Positive Y (`+Y` is Up).
2. **Front Direction**: **Positive Z (`+Z` is Front)**. The chest/front of the garment must face towards `+Z`.
3. **Lateral Axis**: Positive X (`+X` is Right, `-X` is Left).
4. **Origin (0, 0, 0)**: Centered laterally on the garment's vertical symmetry axis.
5. **Real-World Units**: Modeled in **meters** (`1 unit = 1 meter`). E.g., a hoodie height should be ~0.70m to 0.74m.

---

## 3. Mesh Topology & Decal Projection Zones

Welzee projects customer text and uploaded logos live onto the garment using Three.js `DecalGeometry`.
1. **Clean Chest / Placement Panels**:
   - The front chest area (or front cap crown / bag face) must have clean, uniform, manifold quad/tri topology.
   - Avoid intersecting or duplicate coplanar polygons in the print area to prevent z-fighting.
2. **Multi-Mesh Primitives**:
   - If the garment uses multiple primitives, the chest panel must not have empty zero-triangle fragments.
3. **No Hidden Junk Geometry**:
   - Strip invisible inner linings, mannequins, hangers, pins, or interior body meshes that are never visible to the customer.

---

## 4. Materials & Shading

1. **Fabric PBR Material**:
   - Material name must include `fabric` or `garment` (e.g., `Hoodie_Fabric_PBR`).
   - Standard metallic-roughness workflow (Roughness: ~0.75–0.90, Metallic: 0.0–0.05).
   - Neutral base color texture (white / light grey) so the customizer can apply runtime hex color tinting.
2. **Hardware & Accents**:
   - Metal zippers, sliders, aglets, eyelets, and drawstrings must use separate materials clearly named with identifying keywords: `zipper`, `metal`, `hardware`, `eyelet`, `puller`, `button`, or `cord`.
   - Hardware is preserved in original metallic finishes and excluded from fabric color tinting.

---

## 5. Licensing & Attribution Requirements

All supplied models must include clear provenance:
1. **Source / Creator**: Official author name and source URL.
2. **License Type**: CC-BY, CC0, Commercial Royalty-Free, or Custom Enterprise License.
3. Every model delivered must be accompanied by an entry in `docs/MODEL_LICENSES.md` (private, not deployed) documenting the license terms.

