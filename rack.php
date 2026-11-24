<?php
// rack.php
// Hanging-rack showcase: products hang on a rail; hover/tap swings them and turns
// them to show the back; click opens custom_order_3d.php?product=<id>.
// Poster-first: the CSS rack with product images is the first paint. WebGL (one
// shared renderer, one scene) starts after load on every device, except Save-Data
// (tap "Explore in 3D") or a remembered "classic view" choice ("Try 3D").
// Not indexed, not linked from nav, footer or sitemap.

header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/config/bootstrap.php';

// Product list, model paths and names come from the 3D manifest
$manifestFile = __DIR__ . '/assets/js/3d/models.manifest.json';
$manifest = json_decode((string)@file_get_contents($manifestFile), true);
$products = (is_array($manifest) && is_array($manifest['products'] ?? null)) ? $manifest['products'] : [];
$manifestVersion = preg_replace('/[^0-9A-Za-z.\-]/', '', (string)($manifest['version'] ?? '1')) ?: '1';

/** Poster image for a product: manifest "poster" if given, else products/<id>.png or <id>_*.png */
function rack_poster(string $id, array $p): string
{
    $given = is_string($p['poster'] ?? null) ? $p['poster'] : '';
    if ($given !== '' && preg_match('#^assets/[A-Za-z0-9_\-/]+\.(png|jpe?g|webp)$#', $given) && is_file(__DIR__ . '/' . $given)) {
        return $given;
    }
    if (is_file(__DIR__ . "/assets/images/products/{$id}.png")) {
        return "assets/images/products/{$id}.png";
    }
    $match = glob(__DIR__ . "/assets/images/products/{$id}_*.png") ?: [];
    return $match ? 'assets/images/products/' . basename($match[0]) : '';
}

/** Number for CSS custom properties (locale independent, clamped). */
function rack_num($v, float $default, float $min = -100.0, float $max = 100.0): string
{
    $f = (is_int($v) || is_float($v)) ? (float)$v : $default;
    $f = max($min, min($max, $f));
    return rtrim(rtrim(sprintf('%.4F', $f), '0'), '.') ?: '0';
}

/**
 * CSS variables for one slot, from the manifest "rack" block. rack.js uses the SAME
 * numbers, so the static poster and the 3D model share one size and position.
 */
function rack_slot_style(array $p, array $layout): array
{
    $r = is_array($p['rack'] ?? null) ? $p['rack'] : [];
    $th = (is_int($p['targetHeight'] ?? null) || is_float($p['targetHeight'] ?? null)) ? (float)$p['targetHeight'] : 0.7;
    $garment = in_array($p['category'] ?? '', ['tees_polos', 'hoodies', 'jackets'], true);
    $type = in_array($r['type'] ?? '', ['hanger', 'cord'], true) ? $r['type'] : ($garment ? 'hanger' : 'cord');
    $fs = (is_array($r['frontSize'] ?? null) && count($r['frontSize']) === 2) ? $r['frontSize'] : [$th, $th];
    $w = max(0.01, (float)$fs[0]);
    $h = max(0.01, (float)$fs[1]);
    $fitH = (float)rack_num($r['fitHeight'] ?? null, $type === 'hanger' ? 0.8 : (isset($r['fitWidth']) ? 9 : 0.6), 0.05, 9);
    $fitW = (float)rack_num($r['fitWidth'] ?? null, $type === 'hanger' ? 0.9 : 9, 0.05, 9);

    $vars = [
        '--ar'    => rack_num($w / $h, 1, 0.05, 20),
        '--hw'    => rack_num($h / $w, 1, 0.05, 20),
        '--fit-h' => rack_num($fitH, 0.8),
        '--fit-w' => rack_num($fitW, 0.9),
        '--fbh'   => rack_num(min($fitH, $fitW * $h / $w), 0.8, 0.05, 1), // no container queries: assume a square slot
    ];
    if ($type === 'hanger') {
        $prof = $r['hangerProfile'][0][1] ?? ($h * 0.93);
        $vars['--hook-in'] = rack_num(($h - (float)$prof) / $h, 0.07, 0, 1);
    } else {
        $top = (float)rack_num($r['frontTop'] ?? null, $h);
        $vars['--vc']  = rack_num(($top - (float)rack_num($r['centerY'] ?? null, $h / 2)) / $h, 0.5, 0, 1);
        $vars['--att'] = rack_num(($top - (float)rack_num($r['attach'][1] ?? null, $top)) / $h, 0, 0, 1);
    }

    // Poster image: map its visible content (no transparent padding) into the model box
    $img = '';
    $ps = $r['poster']['size'] ?? null;
    $pc = $r['poster']['content'] ?? null;
    if (is_array($ps) && is_array($pc) && count($ps) === 2 && count($pc) === 4 && $pc[2] > 0 && $pc[3] > 0) {
        [$iw, $ih] = [(float)$ps[0], (float)$ps[1]];
        [$cl, $ct, $cw, $ch] = array_map('floatval', $pc);
        $boxAr = $w / $h;
        $conAr = $cw / $ch;
        if ($conAr > $boxAr) { $cW = 100; $cH = 100 * $boxAr / $conAr; }
        else                 { $cH = 100; $cW = 100 * $conAr / $boxAr; }
        $cLeft = (100 - $cW) / 2;
        $cTop = $type === 'hanger' ? 0 : (100 - $cH) / 2;      // garments hang from the top
        $img = sprintf('left:%s%%;top:%s%%;width:%s%%;height:%s%%',
            rack_num($cLeft - $cl / $cw * $cW, 0, -1000, 1000), rack_num($cTop - $ct / $ch * $cH, 0, -1000, 1000),
            rack_num($iw / $cw * $cW, 100, 0, 1000), rack_num($ih / $ch * $cH, 100, 0, 1000));
    }

    $style = '';
    foreach ($vars as $k => $v) $style .= $k . ':' . $v . ';';
    return ['type' => $type, 'style' => $style, 'img' => $img];
}

$rackLayout = is_array($manifest['rackLayout'] ?? null) ? $manifest['rackLayout'] : [];
$layoutStyle = '--gtop:' . rack_num($rackLayout['garmentTop'] ?? null, 0.07, 0, 0.5)
             . ';--dl:' . rack_num($rackLayout['displayLine'] ?? null, 0.47, 0.1, 0.9) . ';';
$debug = (($_GET['debug'] ?? '') === '1');   // tuning overlay, only when asked for

$items = [];
foreach ($products as $id => $p) {
    $id = (string)$id;
    if (!preg_match('/^[a-z0-9_-]{1,32}$/', $id) || !is_array($p)) {
        continue;
    }
    $glb = is_string($p['glb'] ?? null) && preg_match('#^assets/models/[A-Za-z0-9_\-]+\.glb$#', $p['glb']) ? $p['glb'] : '';
    $items[] = [
        'id'     => $id,
        'name'   => is_string($p['name'] ?? null) ? $p['name'] : ucfirst($id),
        'glb'    => $glb,
        'poster' => rack_poster($id, $p),
        'rack'   => rack_slot_style($p, $rackLayout),
    ];
}

$pageTitle = 'The Welzee Rack: Custom Apparel Showcase | Welzee';
$pageDescription = 'Browse Welzee custom apparel hanging on the rack. Hover or tap a piece to see it swing and turn, then customise it in 3D.';

$extraHead = '
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="' . app_base_url() . 'assets/css/rack.css?v=1.2.0">
    <script type="importmap">
    {
      "imports": {
        "three": "' . app_base_url() . 'assets/js/3d/lib/three-r162/three.module.min.js",
        "three/addons/": "' . app_base_url() . 'assets/js/3d/lib/three-r162/addons/"
      }
    }
    </script>
';
include __DIR__ . '/includes/header.php';
?>

<section class="rack-page">
    <div class="rack-intro">
        <span class="rack-kicker"><i class="fa-solid fa-shirt" aria-hidden="true"></i> The Welzee Rack</span>
        <h1>Pick a piece off the rail</h1>
        <p class="rack-howto">
            <span class="rack-howto-mouse">Hover a piece to swing it and see the back. Click to customise it in 3D.</span>
            <span class="rack-howto-touch">Tap a piece to swing it, tap again to customise it in 3D.</span>
        </p>
        <p class="rack-classic-note">
            <button type="button" class="rack-linkbtn" id="rackClassicToggle">Classic view (photos only)</button>
        </p>
    </div>

    <div class="rack<?php echo $debug ? ' is-debug' : ''; ?>" id="rack" style="<?php echo e($layoutStyle); ?>"
         <?php if ($debug): ?>data-debug="1"<?php endif; ?>
         data-manifest="<?php echo e($base_url . 'assets/js/3d/models.manifest.json?v=' . $manifestVersion); ?>"
         data-module="<?php echo e($base_url . 'assets/js/3d/rack.js?v=1.2.0'); ?>"
         data-base="<?php echo e($base_url); ?>">

        <canvas class="rack-canvas" id="rackCanvas" aria-hidden="true"></canvas>

        <ul class="rack-grid">
            <?php foreach ($items as $i => $it): ?>
            <li class="rack-slot rack-slot--<?php echo e($it['rack']['type']); ?>" data-product="<?php echo e($it['id']); ?>" data-glb="<?php echo e($it['glb']); ?>"
                style="<?php echo e($it['rack']['style']); ?>">
                <a class="rack-item" href="<?php echo e($base_url . 'custom_order_3d.php?product=' . rawurlencode($it['id'])); ?>"
                   data-product="<?php echo e($it['id']); ?>"
                   aria-label="<?php echo e('Customise: ' . $it['name']); ?>">
                    <span class="rack-hang">
                        <span class="<?php echo $it['rack']['type'] === 'hanger' ? 'rack-hook' : 'rack-cord'; ?>" aria-hidden="true"></span>
                        <span class="rack-figure">
                            <?php if ($it['poster'] !== ''): ?>
                            <img class="rack-poster" src="<?php echo e($base_url . $it['poster']); ?>" alt=""
                                 width="500" height="500" decoding="async"
                                 <?php if ($it['rack']['img'] !== ''): ?>style="<?php echo e($it['rack']['img']); ?>"<?php endif; ?>
                                 <?php echo $i < 4 ? 'fetchpriority="' . ($i < 2 ? 'high' : 'auto') . '"' : 'loading="lazy"'; ?>>
                            <?php endif; ?>
                        </span>
                    </span>
                    <span class="rack-label">
                        <span class="rack-name"><?php echo e($it['name']); ?></span>
                        <span class="rack-cta">
                            <span class="rack-cta-default">Customise <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
                            <span class="rack-cta-armed">Tap again to customise</span>
                        </span>
                    </span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <button type="button" class="rack-explore" id="rackExplore" hidden>
            <i class="fa-solid fa-cube" aria-hidden="true"></i> <span id="rackExploreLabel">Explore in 3D</span>
        </button>

        <div class="rack-slow" id="rackSlowBanner" role="status" hidden>
            <span><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Taking long?
                <button type="button" class="rack-linkbtn" id="rackSlowClassic">Switch to the classic view</button></span>
            <button type="button" class="rack-slow-close" id="rackSlowClose" aria-label="Dismiss">&times;</button>
        </div>
    </div>
</section>

<script>
// Rack bootstrap (tiny, no dependencies): touch "tap to swing, tap again to open",
// then decides when (or whether) to start the WebGL rack module.
(function () {
    var rack = document.getElementById('rack');
    if (!rack) return;

    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var saveData = !!(navigator.connection && navigator.connection.saveData);
    if (reduced) rack.classList.add('is-reduced');

    // Classic view (photo rack) preference, shared with the 3D customizer
    var PREF = 'welzee_view_pref';
    function getPref() { try { return window.localStorage.getItem(PREF); } catch (e) { return null; } }
    function setPref(v) { try { if (v) window.localStorage.setItem(PREF, v); else window.localStorage.removeItem(PREF); } catch (e) {} }
    var prefersClassic = getPref() === 'classic';

    // Touch: first tap swings (and turns to the back in 3D), second tap opens
    var pointerType = 'mouse', armed = null, armedAt = 0;
    rack.addEventListener('pointerdown', function (e) { pointerType = e.pointerType || 'mouse'; }, true);
    rack.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a.rack-item');
        if (!a || (pointerType !== 'touch' && pointerType !== 'pen')) return; // mouse/keyboard: open directly
        if (armed === a && Date.now() - armedAt < 6000) return;               // second tap: follow the link
        e.preventDefault();
        if (armed) armed.classList.remove('is-armed');
        armed = a; armedAt = Date.now();
        a.classList.add('is-armed');
        a.classList.remove('is-poked'); void a.offsetWidth; a.classList.add('is-poked');
        rack.dispatchEvent(new CustomEvent('rack:poke', { detail: { id: a.getAttribute('data-product') } }));
    });
    rack.addEventListener('animationend', function (e) {
        var a = e.target.closest && e.target.closest('a.rack-item');
        if (a) a.classList.remove('is-poked');
    });

    var btn = document.getElementById('rackExplore');
    var btnLabel = document.getElementById('rackExploreLabel');
    var slow = document.getElementById('rackSlowBanner');
    var toggle = document.getElementById('rackClassicToggle');

    // Classic view: stop 3D, show the photo rack (always fully usable)
    function goClassic() {
        setPref('classic');
        rack.classList.add('is-classic');
        rack.dispatchEvent(new CustomEvent('rack:classic'));
        if (slow) slow.hidden = true;
        if (btn) { btn.hidden = false; if (btnLabel) btnLabel.textContent = 'Try 3D'; }
        if (toggle) toggle.hidden = true;
    }
    if (toggle) toggle.addEventListener('click', goClassic);
    var slowBtn = document.getElementById('rackSlowClassic');
    if (slowBtn) slowBtn.addEventListener('click', goClassic);
    var slowClose = document.getElementById('rackSlowClose');
    if (slowClose) slowClose.addEventListener('click', function () { slow.hidden = true; });

    // WebGL available?
    var gl = false;
    try { var c = document.createElement('canvas'); gl = !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl'))); } catch (err) {}
    if (!gl || !('IntersectionObserver' in window)) {    // poster rack stays fully usable
        if (toggle) toggle.hidden = true;
        return;
    }

    var started = false;
    function start() {
        if (started) return;
        started = true;
        rack.classList.add('is-loading-3d');
        // Slow-load safeguard: no 3D piece on screen after 6 s -> offer the classic view
        setTimeout(function () {
            if (!rack.querySelector('.rack-slot.is-3d') && !rack.classList.contains('is-classic') && slow) slow.hidden = false;
        }, 6000);
        import(rack.getAttribute('data-module')).then(function (m) {
            return m.startRack(rack, { reducedMotion: reduced });
        }).catch(function (err) {
            console.warn('[Welzee Rack] 3D unavailable, keeping the poster rack.', err);
            rack.classList.remove('is-loading-3d');
            if (slow && !rack.classList.contains('is-classic')) slow.hidden = false;
        });
    }

    if (btn) {
        btn.addEventListener('click', function () {
            btn.hidden = true;
            if (rack.classList.contains('is-classic')) {          // "Try 3D": forget the classic choice
                setPref(null);
                rack.classList.remove('is-classic');
                rack.dispatchEvent(new CustomEvent('rack:3d'));
                if (toggle) toggle.hidden = false;
            }
            start();
        });
    }

    // Everyone starts 3D automatically after load, except Save-Data (tap) and a remembered classic choice
    if (prefersClassic) {
        rack.classList.add('is-classic');
        if (btn) { btn.hidden = false; if (btnLabel) btnLabel.textContent = 'Try 3D'; }
        if (toggle) toggle.hidden = true;
        return;
    }
    if (saveData) {
        if (btn) btn.hidden = false;
        return;
    }
    function onLoad() {
        if ('requestIdleCallback' in window) requestIdleCallback(start, { timeout: 2000 });
        else setTimeout(start, 200);
    }
    if (document.readyState === 'complete') onLoad(); else window.addEventListener('load', onLoad);
})();
</script>

<?php
include __DIR__ . '/includes/footer.php';
?>
