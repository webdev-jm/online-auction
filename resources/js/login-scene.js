import '@fontsource-variable/manrope';
import {
    AdditiveBlending,
    BufferAttribute,
    BufferGeometry,
    CanvasTexture,
    Fog,
    PerspectiveCamera,
    Points,
    PointsMaterial,
    Scene,
    WebGLRenderer,
} from 'three';

/**
 * "Rising bids": soft gold points drift upward through a dark navy field, with a faint
 * blue layer behind them for depth. The pointer tilts the camera slightly.
 */

const BACKGROUND = 0x0d1320;
const HEIGHT = 16;

function softDotTexture() {
    const size = 64;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const context = canvas.getContext('2d');
    const gradient = context.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    gradient.addColorStop(0, 'rgba(255,255,255,1)');
    gradient.addColorStop(0.35, 'rgba(255,255,255,.55)');
    gradient.addColorStop(1, 'rgba(255,255,255,0)');
    context.fillStyle = gradient;
    context.fillRect(0, 0, size, size);

    return new CanvasTexture(canvas);
}

function particleLayer({ count, spread, depth, size, color, opacity, texture }) {
    const positions = new Float32Array(count * 3);
    const speeds = new Float32Array(count);

    for (let i = 0; i < count; i++) {
        positions[i * 3] = (Math.random() - 0.5) * spread;
        positions[i * 3 + 1] = (Math.random() - 0.5) * HEIGHT;
        positions[i * 3 + 2] = (Math.random() - 0.5) * depth;
        speeds[i] = 0.25 + Math.random() * 0.9;
    }

    const geometry = new BufferGeometry();
    geometry.setAttribute('position', new BufferAttribute(positions, 3));

    const material = new PointsMaterial({
        color,
        size,
        map: texture,
        transparent: true,
        opacity,
        depthWrite: false,
        blending: AdditiveBlending,
    });

    return { points: new Points(geometry, material), positions, speeds, geometry, material, count };
}

function start(canvas) {
    let renderer;
    try {
        renderer = new WebGLRenderer({ canvas, antialias: true, powerPreference: 'low-power' });
    } catch (error) {
        canvas.hidden = true;
        return;
    }

    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.setClearColor(BACKGROUND, 1);

    const scene = new Scene();
    scene.fog = new Fog(BACKGROUND, 10, 28);

    const camera = new PerspectiveCamera(50, 1, 0.1, 100);
    camera.position.set(0, 0, 13);

    const texture = softDotTexture();
    const gold = particleLayer({ count: 1100, spread: 34, depth: 16, size: 0.16, color: 0xd9a84f, opacity: 0.9, texture });
    const blue = particleLayer({ count: 700, spread: 40, depth: 22, size: 0.1, color: 0x6f8fd0, opacity: 0.45, texture });
    scene.add(blue.points, gold.points);

    const pointer = { x: 0, y: 0 };
    const onPointerMove = (event) => {
        pointer.x = (event.clientX / window.innerWidth - 0.5) * 2;
        pointer.y = (event.clientY / window.innerHeight - 0.5) * 2;
    };
    window.addEventListener('pointermove', onPointerMove, { passive: true });

    const resize = () => {
        const width = canvas.clientWidth;
        const height = canvas.clientHeight;
        if (!width || !height) {
            return;
        }
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    };
    resize();
    window.addEventListener('resize', resize);

    const rise = (layer, delta, factor) => {
        for (let i = 0; i < layer.count; i++) {
            const index = i * 3 + 1;
            layer.positions[index] += layer.speeds[i] * delta * factor;
            if (layer.positions[index] > HEIGHT / 2) {
                layer.positions[index] = -HEIGHT / 2;
            }
        }
        layer.geometry.attributes.position.needsUpdate = true;
    };

    const draw = (time, delta) => {
        rise(gold, delta, 0.75);
        rise(blue, delta, 0.4);
        gold.points.rotation.y = time * 0.025;
        blue.points.rotation.y = -time * 0.015;
        camera.position.x += (pointer.x * 1.2 - camera.position.x) * 0.03;
        camera.position.y += (-pointer.y * 0.8 - camera.position.y) * 0.03;
        camera.lookAt(0, 0, 0);
        renderer.render(scene, camera);
    };

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let frame = null;
    let last = performance.now();

    const loop = (now) => {
        const delta = Math.min((now - last) / 1000, 0.05);
        last = now;
        draw(now / 1000, delta);
        frame = requestAnimationFrame(loop);
    };

    const play = () => {
        if (frame === null && !document.hidden && !reducedMotion.matches) {
            last = performance.now();
            frame = requestAnimationFrame(loop);
        }
    };

    const pause = () => {
        if (frame !== null) {
            cancelAnimationFrame(frame);
            frame = null;
        }
    };

    document.addEventListener('visibilitychange', () => (document.hidden ? pause() : play()));
    reducedMotion.addEventListener?.('change', () => (reducedMotion.matches ? pause() : play()));

    canvas.addEventListener('webglcontextlost', (event) => {
        event.preventDefault();
        pause();
        canvas.hidden = true;
    });

    draw(4, 0);
    canvas.classList.add('is-ready');
    play();

    window.addEventListener('pagehide', () => {
        pause();
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('resize', resize);
        gold.geometry.dispose();
        gold.material.dispose();
        blue.geometry.dispose();
        blue.material.dispose();
        texture.dispose();
        renderer.dispose();
    });
}

const canvas = document.getElementById('oa-scene');
if (canvas) {
    start(canvas);
}
