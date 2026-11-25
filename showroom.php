<?php
// showroom.php
// Showroom hero (full-bleed, the future home-page hero): dark stage, spotlights, one rail
// with labelled sections, and an editable headline overlay (left column or top block).
// Poster first: a static wall (product photos on a CSS rail) is the first paint and
// the plain fallback. The WebGL wall (assets/js/3d/showroom.js) starts after load;
// low-spec / Save-Data devices tap "Explore in 3D". Not indexed, not linked.

header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/config/bootstrap.php';

$manifestFile = __DIR__ . '/assets/js/3d/models.manifest.json';
$manifest = json_decode((string)@file_get_contents($manifestFile), true);
$products = (is_array($manifest) && is_array($manifest['products'] ?? null)) ? $manifest['products'] : [];
$manifestVersion = preg_replace('/[^0-9A-Za-z.\-]/', '', (string)($manifest['version'] ?? '1')) ?: '1';
$showroom = is_array($manifest['showroom'] ?? null) ? $manifest['showroom'] : [];
$colorNames = is_array($showroom['colorNames'] ?? null) ? $showroom['colorNames'] : [];
$debug = (($_GET['debug'] ?? '') === '1');

/** Poster image for a product: manifest "poster" if given, else products/<id>.png or <id>_*.png */
function sr_poster(string $id, array $p): string
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

// Sections for the static wall, validated (same data the 3D wall uses)
$sections = [];
foreach (($showroom['sections'] ?? []) as $sec) {
    if (!is_array($sec) || !is_array($sec['items'] ?? null)) continue;
    $items = [];
    foreach ($sec['items'] as $item) {
        $id = is_string($item['product'] ?? null) ? $item['product'] : '';
        if (!preg_match('/^[a-z0-9_-]{1,32}$/', $id) || !is_array($products[$id] ?? null)) continue;
        $colors = array_values(array_filter((array)($item['colors'] ?? []), function ($c) {
            return is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c);
        }));
        if (!$colors) continue;
        $items[] = [
            'id'     => $id,
            'name'   => is_string($products[$id]['name'] ?? null) ? $products[$id]['name'] : ucfirst($id),
            'poster' => sr_poster($id, $products[$id]),
            'colors' => array_map('strtolower', $colors),
        ];
    }
    if ($items) {
        $sections[] = ['label' => is_string($sec['label'] ?? null) ? $sec['label'] : '', 'items' => $items];
    }
}

$pageTitle = 'The Welzee Showroom | Welzee';
$pageDescription = 'Walk along the Welzee showroom wall: custom T-shirts, hoodies, jackets, caps and bags in every colour. Pick a piece and customise it in 3D.';

$extraHead = '
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="' . app_base_url() . 'assets/css/showroom.css?v=2.0.0">
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
$imgIndex = 0;
?>

<section class="sr-hero<?php echo $debug ? ' is-debug' : ''; ?>" id="showroom" aria-label="The Welzee Showroom"
         data-module="<?php echo e($base_url . 'assets/js/3d/showroom.js?v=2.0.0'); ?>"
         data-manifest="<?php echo e($base_url . 'assets/js/3d/models.manifest.json?v=' . $manifestVersion); ?>"
         data-base="<?php echo e($base_url); ?>"
         <?php if ($debug): ?>data-debug="1"<?php endif; ?>>

    <!-- Static wall: first paint, plain fallback, and a usable product list without 3D -->
    <div class="sr-poster" id="srPoster">
        <div class="sr-poster-track" aria-label="Products in the showroom">
            <?php foreach ($sections as $sec): ?>
            <div class="sr-poster-section">
                <p class="sr-poster-label"><?php echo e($sec['label']); ?></p>
                <ul class="sr-poster-items">
                    <?php foreach ($sec['items'] as $it): $first = $it['colors'][0]; ?>
                    <li class="sr-poster-item">
                        <a href="<?php echo e($base_url . 'custom_order_3d.php?product=' . rawurlencode($it['id']) . '&color=' . rawurlencode($first)); ?>">
                            <span class="sr-poster-hook" aria-hidden="true"></span>
                            <?php if ($it['poster'] !== ''): ?>
                            <img src="<?php echo e($base_url . $it['poster']); ?>" alt="" width="500" height="500" decoding="async"
                                 <?php echo $imgIndex++ < 3 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
                            <?php endif; ?>
                            <span class="sr-poster-name"><?php echo e($it['name']); ?></span>
                            <span class="sr-poster-swatches" aria-label="<?php echo e(count($it['colors']) . ' colours'); ?>">
                                <?php foreach ($it['colors'] as $c): ?>
                                <span class="sr-dot" style="background:<?php echo e($c); ?>" title="<?php echo e($colorNames[$c] ?? strtoupper($c)); ?>"></span>
                                <?php endforeach; ?>
                            </span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 3D wall mounts here (hidden until the first product is on screen) -->
    <div class="sr-mount" id="srMount"></div>
    <div class="sr-fade" aria-hidden="true"></div>

    <!-- Hero overlay: page content (placeholder copy, edit freely). Left column on wide
         screens, top block on portrait; the 3D framing keeps products out of this area. -->
    <div class="sr-overlay">
        <div class="sr-copy" id="srCopy">
            <p class="sr-kicker"><i class="fa-solid fa-store" aria-hidden="true"></i> The Welzee Showroom</p>
            <h1>Your brand on every piece.</h1>
            <p class="sr-sub">Custom embroidery and print on tees, hoodies, jackets, caps and bags. Pick a piece and make it yours.</p>
            <div class="sr-actions">
                <a class="sr-cta" href="<?php echo e($base_url . welzee_customizer_href()); ?>">Customise yours <span aria-hidden="true">&rarr;</span></a>
                <p class="sr-cue" aria-hidden="true">
                    <i class="fa-solid fa-arrows-left-right"></i>
                    <span class="sr-cue-mouse">Drag to explore</span><span class="sr-cue-touch">Swipe to explore</span>
                </p>
            </div>
        </div>
    </div>

    <button type="button" class="sr-start" id="srStart" hidden>
        <i class="fa-solid fa-cube" aria-hidden="true"></i> <span id="srStartLabel">Explore in 3D</span>
    </button>

    <div class="sr-slow" id="srSlow" role="status" hidden>
        <span><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Taking long?
            <button type="button" class="sr-linkbtn" id="srSlowClassic">Switch to the classic view</button></span>
        <button type="button" class="sr-slow-close" id="srSlowClose" aria-label="Dismiss">&times;</button>
    </div>

    <button type="button" class="sr-classic" id="srClassicToggle">Classic view</button>
</section>

<script>
// Showroom bootstrap (tiny, no dependencies): overlay mode, and when (or whether) to start the 3D wall.
(function () {
    var stage = document.getElementById('showroom');
    if (!stage) return;
    var mount = document.getElementById('srMount');
    var copy = document.getElementById('srCopy');
    var poster = document.getElementById('srPoster');
    var startBtn = document.getElementById('srStart');
    var startLabel = document.getElementById('srStartLabel');
    var slow = document.getElementById('srSlow');
    var toggle = document.getElementById('srClassicToggle');

    // Overlay mode: text column on the left (wide stage) or a block on top (portrait, tablets, small windows)
    function setMode() {
        var w = stage.clientWidth;
        stage.classList.toggle('is-wide', w >= 900 && w / Math.max(1, stage.clientHeight) >= 1.2);
    }
    setMode();
    // Area covered by the overlay text, in px from the stage's top-left (the 3D framing avoids it)
    function insets() {
        var s = stage.getBoundingClientRect(), c = copy.getBoundingClientRect();
        if (stage.classList.contains('is-wide')) return { left: Math.max(0, c.right - s.left + 24), top: 0 };
        return { left: 0, top: Math.max(0, c.bottom - s.top + 4) };
    }

    var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var tour = !/[?&]tour=0(?:&|$)/.test(window.location.search);   // ?tour=0 switches the drift off
    var conn = navigator.connection || {};
    var saveData = !!conn.saveData;
    var lowSpec = (navigator.deviceMemory && navigator.deviceMemory <= 4) ||
                  (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 4);

    // Classic view preference, shared with the 3D customizer and the rack
    var PREF = 'welzee_view_pref';
    function getPref() { try { return window.localStorage.getItem(PREF); } catch (e) { return null; } }
    function setPref(v) { try { if (v) window.localStorage.setItem(PREF, v); else window.localStorage.removeItem(PREF); } catch (e) {} }

    var controller = null, starting = false, shown = false, timer = 0;
    function isClassic() { return stage.classList.contains('is-classic'); }
    function showStart(label) { startLabel.textContent = label; startBtn.hidden = false; }
    function relayout() { setMode(); if (controller) controller.relayout(); }
    window.addEventListener('resize', relayout);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(relayout);

    // Module events -> page state (cue fades after the first interaction; text steps aside for the panel)
    mount.addEventListener('showroom:interact', function () { stage.classList.add('is-interacted'); });
    mount.addEventListener('showroom:pull', function () { stage.classList.add('is-pulled'); });
    mount.addEventListener('showroom:release', function () { stage.classList.remove('is-pulled'); });
    poster.addEventListener('scroll', function () { stage.classList.add('is-interacted'); }, { passive: true });

    function start() {
        if (starting || controller) return;
        starting = true; shown = false;
        startBtn.hidden = true;
        stage.classList.add('is-loading');
        clearTimeout(timer);
        // Slow-load safeguard: nothing on screen after 6 s -> offer the classic view
        timer = setTimeout(function () { if (!shown && !isClassic()) slow.hidden = false; }, 6000);
        import(stage.getAttribute('data-module')).then(function (m) {
            return m.mountShowroom(mount, {
                manifestUrl: stage.getAttribute('data-manifest'),
                base: stage.getAttribute('data-base'),
                reducedMotion: reduced,
                debug: stage.getAttribute('data-debug') === '1',
                tour: tour,
                hint: false,                        // the page shows its own "drag to explore" cue
                insets: insets,
                onProductsShown: function () {
                    shown = true;
                    slow.hidden = true;
                    stage.classList.remove('is-loading');
                    stage.classList.add('is-3d-live');
                }
            });
        }).then(function (c) {
            starting = false;
            if (isClassic()) { c.destroy(); return; }
            controller = c;
        }).catch(function (err) {
            starting = false;
            console.warn('[Welzee Showroom] 3D unavailable, keeping the static wall.', err);
            mount.innerHTML = '';
            stage.classList.remove('is-loading', 'is-3d-live', 'is-pulled');
            if (!isClassic()) slow.hidden = false;
        });
    }

    // Classic view: stop 3D, show the static wall (always fully usable)
    function goClassic() {
        setPref('classic');
        stage.classList.add('is-classic');
        stage.classList.remove('is-3d-live', 'is-loading', 'is-pulled');
        clearTimeout(timer);
        slow.hidden = true;
        if (controller) { controller.destroy(); controller = null; }
        toggle.hidden = true;
        showStart('Try 3D');
    }
    toggle.addEventListener('click', goClassic);
    document.getElementById('srSlowClassic').addEventListener('click', goClassic);
    document.getElementById('srSlowClose').addEventListener('click', function () { slow.hidden = true; });
    startBtn.addEventListener('click', function () {
        if (isClassic()) {                         // "Try 3D": forget the classic choice
            setPref(null);
            stage.classList.remove('is-classic');
            toggle.hidden = false;
        }
        start();
    });

    // WebGL available?
    var gl = false;
    try { var c = document.createElement('canvas'); gl = !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl'))); } catch (err) {}
    if (!gl || !('IntersectionObserver' in window) || !('ResizeObserver' in window)) {
        toggle.hidden = true;                      // static wall only
        return;
    }
    if (getPref() === 'classic') {
        stage.classList.add('is-classic');
        toggle.hidden = true;
        showStart('Try 3D');
        return;
    }
    if (saveData || lowSpec) {                     // static wall + tap to start
        showStart('Explore in 3D');
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
