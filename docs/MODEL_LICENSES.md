# Welzee 3D models: source and licence register

Private document. It is not deployed (the deploy workflow excludes `*.md`) and the root `.htaccess` returns 404 for `.md` files.

**Owner: fill in every TODO before the 3D pages go public.**

The downloads came from Sketchfab. Sketchfab models use different licences (CC-BY, CC-BY-NC, CC0, Standard/Editorial, ...). Do NOT assume one: open each model's Sketchfab page and copy the licence shown there. "NC" (non-commercial) or "Editorial" licences do NOT allow use on a shop website.

| Product | File (as loaded by the site) | Source (URL) | Licence | Attribution required (yes/no) | Author |
|---|---|---|---|---|---|
| hoodie | assets/models/hoodie.glb | TODO | TODO | TODO | TODO |
| tshirt | assets/models/optimized/tshirt.glb | TODO | TODO | TODO | TODO |
| polo | assets/models/optimized/polo.glb | TODO | TODO | TODO | TODO |
| varsity | assets/models/optimized/varsity_jacket.glb | TODO | TODO | TODO | TODO |
| cap | assets/models/optimized/cap.glb | TODO | TODO | TODO | TODO |
| visor | assets/models/optimized/visor.glb | TODO | TODO | TODO | TODO |
| bag | assets/models/optimized/tote-bag.glb | TODO (source unknown: file was exported with trimesh) | TODO | TODO | TODO |

If "Attribution required" is yes (for example CC-BY), the author's name and the licence must be shown somewhere public on the site (for example a credits line on the 3D pages). Tell the developer and it will be added.

## Notes per model

- **hoodie**: Draco-compressed web copy; the raw high-poly original is kept outside the repository.
- **tshirt, polo, varsity, cap, visor**: optimised Draco copies in `assets/models/optimized/`; the raw downloads are kept outside git (`newDownloadedModels/`, excluded).
- **varsity**: two materials: the body takes the chosen colour, the sleeves stay cream (#f1ece0).
- **bag**: uncompressed (205 KB), no normals in the file (computed in the browser); source unknown.
